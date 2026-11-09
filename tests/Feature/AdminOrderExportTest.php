<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderExportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_the_export_is_for_admins_only(): void
    {
        $this->get(route('admin.orders.export'))->assertRedirect();
        $this->actingAs(User::factory()->create())->get(route('admin.orders.export'))->assertForbidden();
    }

    public function test_the_filtered_list_is_downloaded_as_a_semicolon_csv_with_a_bom(): void
    {
        Order::factory()->create([
            'order_number' => 'ORD-EXP1', 'total' => 12500.4, 'status' => 'processing',
            'payment_status' => 'paid', 'payment_method' => 'cash_on_delivery',
            'shipping_carrier' => 'DHL', 'tracking_number' => 'TRACK-9', 'tracking_url' => 'https://track.example/9',
        ]);
        Order::factory()->create(['order_number' => 'ORD-EXP2', 'status' => 'pending']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.orders.export', ['status' => 'processing']))
            ->assertOk();

        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment; filename=commandes-', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // fputcsv quotes the headers that contain a space; the columns are still separated by ";"
        foreach (['N° commande', 'Date', 'Client', 'Email', 'Téléphone', 'Total (FCFA)', 'Statut', 'Paiement', 'Moyen', 'Transaction', 'Transporteur', 'N° de suivi', 'Lien de suivi'] as $header) {
            $this->assertStringContainsString($header, $content);
        }

        $this->assertStringContainsString(';Statut;Paiement;Moyen;Transaction;Transporteur;', $content);
        $this->assertStringContainsString('ORD-EXP1', $content);
        $this->assertStringContainsString(';12500;', $content);
        $this->assertStringContainsString('Paiement à la livraison', $content);
        $this->assertStringContainsString('DHL;TRACK-9;https://track.example/9', $content);
        $this->assertStringNotContainsString('ORD-EXP2', $content);
    }

    public function test_customer_text_cannot_run_as_a_spreadsheet_formula(): void
    {
        Order::factory()->create([
            'order_number' => 'ORD-FORMULA',
            'user_id' => null,
            'shipping_address' => ['first_name' => '=HYPERLINK("http://evil.example")', 'last_name' => 'Koné', 'city' => 'Abidjan'],
        ]);

        $content = $this->actingAs($this->admin())
            ->get(route('admin.orders.export'))
            ->assertOk()
            ->streamedContent();

        // The leading quote makes the cell text: a spreadsheet never evaluates it
        $this->assertStringContainsString('\'=HYPERLINK', $content);
        $this->assertStringNotContainsString(';=HYPERLINK', $content);
    }
}
