<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class AdminCampaignController extends Controller
{
    public function index()
    {
        $campaigns = NewsletterCampaign::orderByDesc('created_at')->orderByDesc('id')->paginate(20);
        $activeSubscribers = NewsletterSubscriber::whereNull('unsubscribed_at')->count();

        return view('admin.campaigns.index', compact('campaigns', 'activeSubscribers'));
    }

    public function create()
    {
        return view('admin.campaigns.create');
    }

    public function store(Request $request)
    {
        NewsletterCampaign::create($this->validateCampaign($request) + [
            'status' => NewsletterCampaign::STATUS_DRAFT,
        ]);

        return redirect()->route('admin.campaigns.index')
            ->with('success', 'Campagne enregistrée en brouillon. Vérifiez l\'aperçu avant de l\'envoyer.');
    }

    public function edit(NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isDraft(), 403);

        return view('admin.campaigns.edit', compact('campaign'));
    }

    public function update(Request $request, NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isDraft(), 403);

        $campaign->update($this->validateCampaign($request));

        return redirect()->route('admin.campaigns.index')->with('success', 'Campagne mise à jour.');
    }

    public function destroy(NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isDraft(), 403);

        $campaign->delete();

        return redirect()->route('admin.campaigns.index')->with('success', 'Campagne supprimée.');
    }

    /** Rendered in a sandboxed iframe: the preview must never run anything it contains. */
    public function preview(NewsletterCampaign $campaign)
    {
        $subscriber = new NewsletterSubscriber(['token' => str_repeat('0', 32)]);

        return response()
            ->make((new NewsletterCampaignMail($campaign, $subscriber))->render())
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Security-Policy', "sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src data: https:");
    }

    public function send(NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isDraft(), 403);

        if (NewsletterSubscriber::whereNull('unsubscribed_at')->doesntExist()) {
            return back()->with('error', 'Aucun abonné actif : la campagne ne peut pas être envoyée.');
        }

        // The command takes the audience snapshot and serves the recipients, batch by batch
        $campaign->forceFill([
            'status' => NewsletterCampaign::STATUS_SENDING,
            'max_subscriber_id' => null,
            'last_subscriber_id' => null,
            'recipients_count' => null,
            'sent_count' => 0,
            'failed_count' => 0,
            'started_at' => null,
            'sent_at' => null,
        ])->save();

        return redirect()->route('admin.campaigns.index')
            ->with('success', 'Envoi démarré : les e-mails partent par lots de 20 chaque minute.');
    }

    public function cancel(NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isSending(), 403);

        $campaign->update(['status' => NewsletterCampaign::STATUS_CANCELLED]);

        return redirect()->route('admin.campaigns.index')
            ->with('success', 'Campagne annulée. Les destinataires déjà servis ne seront pas recontactés.');
    }

    /** @return array{subject: string, body: string} */
    private function validateCampaign(Request $request): array
    {
        return $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
        ], [
            'subject.required' => 'Le sujet est requis.',
            'subject.max' => 'Le sujet ne peut pas dépasser 255 caractères.',
            'body.required' => 'Le contenu du message est requis.',
            'body.max' => 'Le contenu ne peut pas dépasser 20 000 caractères.',
        ]);
    }
}
