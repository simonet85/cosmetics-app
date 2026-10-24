<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminScreensTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The dashboard counts customers through their role
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function message(array $attributes = []): ContactMessage
    {
        return ContactMessage::create(array_merge([
            'name' => 'Awa Koné', 'email' => 'awa@example.com', 'subject' => 'Ma commande', 'message' => "Bonjour\nOù est ORD-1 ?",
        ], $attributes));
    }

    public function test_the_messages_page_is_for_admins_only(): void
    {
        $this->get(route('admin.messages.index'))->assertRedirect();
        $this->actingAs(User::factory()->create())->get(route('admin.messages.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.messages.index'))->assertOk();
    }

    public function test_messages_are_listed_with_unread_first_marked_and_counted_in_the_menu(): void
    {
        $this->message(['subject' => 'Non lu']);
        $this->message(['subject' => 'Déjà lu', 'is_read' => true]);

        $html = $this->actingAs($this->admin())->get(route('admin.messages.index'))->assertOk()->getContent();

        $this->assertStringContainsString('1 non lu', $html);
        $this->assertStringContainsString('Où est ORD-1 ?', $html);
        $this->assertStringContainsString('mailto:awa@example.com', $html);
        $this->assertStringContainsString('1<span class="sr-only"> non lus</span>', $html);
    }

    public function test_a_message_can_be_marked_read_unread_and_deleted(): void
    {
        $admin = $this->admin();
        $message = $this->message();

        $this->actingAs($admin)->patch(route('admin.messages.read', $message))->assertSessionHas('success');
        $this->assertTrue($message->fresh()->is_read);

        $this->actingAs($admin)->patch(route('admin.messages.read', $message));
        $this->assertFalse($message->fresh()->is_read);

        $this->actingAs($admin)->delete(route('admin.messages.destroy', $message))->assertSessionHas('success');
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_the_dashboard_counts_delivered_orders_and_speaks_french(): void
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Order::factory()->create(['status' => 'delivered', 'total' => 20000]);
        Order::factory()->create(['status' => 'pending', 'total' => 5000]);

        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('1 livrées', $html);
        $this->assertStringNotContainsString('complétées', $html);
        $this->assertStringNotContainsString('fa-dollar-sign', $html);
        $this->assertStringNotContainsString('>Pending<', $html);
        $this->assertStringContainsString('En attente', $html);
    }

    public function test_admin_amounts_are_whole_francs(): void
    {
        $order = Order::factory()->create(['subtotal' => 109.97, 'tax' => 16.5, 'shipping_cost' => 0, 'total' => 126]);

        $list = $this->actingAs($this->admin())->get(route('admin.orders.index'))->getContent();
        $detail = $this->actingAs($this->admin())->get(route('admin.orders.show', $order))->getContent();

        $this->assertStringContainsString("126\u{00A0}FCFA", $list);
        $this->assertStringNotContainsString('126.00', $list.$detail);
        $this->assertStringContainsString("110\u{00A0}FCFA", $detail);
    }

    public function test_mobile_money_can_be_switched_from_the_payment_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.settings.index'))
            ->assertOk()->assertSee('name="payment_moneyfusion_enabled"', false)->assertSee('Mobile money (MoneyFusion)');

        $this->actingAs($admin)->put(route('admin.settings.update'), ['site_name' => 'Glowing', 'payment_cash_on_delivery_enabled' => '1'])
            ->assertSessionHas('success');

        $this->assertSame('0', DB::table('settings')->where('key', 'payment_moneyfusion_enabled')->value('value'));
        $this->assertFalse(\App\Support\PaymentMethods::enabled()['moneyfusion']);
        $this->assertTrue(\App\Support\PaymentMethods::enabled()['cash_on_delivery']);
    }

    public function test_the_admin_shell_is_accessible_and_self_hosted(): void
    {
        $admin = User::factory()->admin()->create(['first_name' => 'Fanta']);

        $html = $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk()->getContent();

        $this->assertStringContainsString('class="skip-link"', $html);
        $this->assertStringContainsString('id="content"', $html);
        $this->assertStringContainsString('aria-label="Administration"', $html);
        $this->assertMatchesRegularExpression('/href="[^"]*\/admin\/orders"\s+aria-current="page"/', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('>Fanta<', $html);                 // the account menu shows the first name
        $this->assertStringContainsString('vendors/fontawesome/css/all.min.css', $html);
        $this->assertStringNotContainsString('cdnjs.cloudflare.com', $html);
        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertStringNotContainsString('5a7c6f', $html);
    }

    public function test_the_dashboard_chart_script_is_served_from_this_site(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('vendors/chartjs/chart.min.js', $html);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $html);
        $this->assertFileExists(public_path('vendors/chartjs/chart.min.js'));
        $this->assertFileExists(public_path('vendors/fontawesome/css/all.min.css'));
    }

    public function test_the_legacy_storefront_templates_are_gone(): void
    {
        foreach (['layouts/app', 'layouts/dashboard', 'home/index', 'shop/index', 'shop/show', 'cart/index', 'checkout/index', 'checkout/success', 'checkout/pending'] as $view) {
            $this->assertFileDoesNotExist(resource_path("views/{$view}.blade.php"), $view);
        }
        $this->assertDirectoryDoesNotExist(public_path('vendors/jquery-toc'));
        $this->assertDirectoryDoesNotExist(public_path('css'));
    }
}
