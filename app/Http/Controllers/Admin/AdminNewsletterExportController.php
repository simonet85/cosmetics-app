<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Downloads the filtered subscriber list as a spreadsheet-friendly CSV (Excel FR: ";" separator, BOM). */
class AdminNewsletterExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['subscribed', 'unsubscribed'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $query = NewsletterSubscriber::query();

        if (($validated['status'] ?? null) === 'subscribed') {
            $query->whereNull('unsubscribed_at');
        } elseif (($validated['status'] ?? null) === 'unsubscribed') {
            $query->whereNotNull('unsubscribed_at');
        }

        if (($search = trim((string) ($validated['search'] ?? ''))) !== '') {
            $query->where('email', 'like', '%'.$search.'%');
        }

        $query->reorder()->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");
            $this->line($out, ['Email', "Date d'inscription", 'Date de désinscription', 'Statut']);

            $query->chunkById(500, function ($subscribers) use ($out) {
                foreach ($subscribers as $subscriber) {
                    $this->line($out, [
                        $subscriber->email,
                        ($subscriber->subscribed_at ?? $subscriber->created_at)?->format('d/m/Y H:i'),
                        $subscriber->unsubscribed_at?->format('d/m/Y H:i'),
                        $subscriber->unsubscribed_at ? 'Désabonné' : 'Abonné',
                    ]);
                }
            });

            fclose($out);
        }, 'newsletter-'.now()->format('Y-m-d-Hi').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * A cell starting with "=", "+", "-" or "@" would run as a formula in Excel/LibreOffice.
     * Email addresses are customer-provided, so they are neutralized with a leading quote.
     *
     * @param  resource  $out
     * @param  array<int, mixed>  $row
     */
    private function line($out, array $row): void
    {
        fputcsv($out, array_map(fn ($value) => $this->cell($value), $row), ';', '"', '');
    }

    private function cell(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
