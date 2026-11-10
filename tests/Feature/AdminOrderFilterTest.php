<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_the_order_list_is_for_admins_only(): void
    {
        $this->get(route('admin.orders.index'))->assertRedirect();
        $this->actingAs(User::factory()->create())->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.orders.index'))->assertOk();
    }

    public function test_search_matches_number_email_phone_and_guest_name(): void
    {
        Order::factory()->create(['order_number' => 'ORD-A1', 'customer_email' => 'awa@example.com']);
        Order::factory()->create(['order_number' => 'ORD-B2', 'customer_email' => 'koffi@example.com', 'customer_phone' => '0767647896']);
        Order::factory()->create([
            'order_number' => 'ORD-C3',
            'user_id' => null,
            'customer_email' => 'fatou@example.com',
            'shipping_address' => ['first_name' => 'Fatou', 'last_name' => 'Diallo', 'city' => 'Abidjan'],
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.orders.index', ['q' => 'A1']))
            ->assertSee('ORD-A1')->assertDontSee('ORD-B2');

        $this->actingAs($admin)->get(route('admin.orders.index', ['q' => 'koffi@example.com']))
            ->assertSee('ORD-B2')->assertDontSee('ORD-A1');

        $this->actingAs($admin)->get(route('admin.orders.index', ['q' => '0767647896']))
            ->assertSee('ORD-B2')->assertDontSee('ORD-C3');

        $this->actingAs($admin)->get(route('admin.orders.index', ['q' => 'Diallo']))
            ->assertSee('ORD-C3')->assertDontSee('ORD-A1');
    }

    public function test_the_list_shows_the_customer_phone_as_a_callable_link(): void
    {
        Order::factory()->create(['order_number' => 'ORD-PH', 'customer_phone' => '0702030405']);

        $html = $this->actingAs($this->admin())->get(route('admin.orders.index'))->assertOk()->getContent();

        $this->assertStringContainsString('href="tel:0702030405"', $html);
        $this->assertStringContainsString('>0702030405<', $html);
    }

    public function test_status_payment_and_period_filters_combine(): void
    {
        Order::factory()->create([
            'order_number' => 'ORD-KEEP', 'status' => 'processing', 'payment_status' => 'paid',
            'created_at' => now()->subDays(2),
        ]);
        Order::factory()->create([
            'order_number' => 'ORD-OLD', 'status' => 'processing', 'payment_status' => 'paid',
            'created_at' => now()->subDays(60),
        ]);
        Order::factory()->create([
            'order_number' => 'ORD-OTHER', 'status' => 'pending', 'payment_status' => 'paid',
            'created_at' => now()->subDays(2),
        ]);

        $this->actingAs($this->admin())->get(route('admin.orders.index', [
            'status' => 'processing',
            'payment_status' => 'paid',
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]))->assertSee('ORD-KEEP')->assertDontSee('ORD-OLD')->assertDontSee('ORD-OTHER');
    }

    public function test_the_list_can_be_sorted_and_the_links_keep_the_filters(): void
    {
        Order::factory()->create(['order_number' => 'ORD-SMALL', 'total' => 1000]);
        Order::factory()->create(['order_number' => 'ORD-BIG', 'total' => 9000]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['sort' => 'total', 'direction' => 'asc', 'status' => 'pending']))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(strpos($html, 'ORD-BIG'), strpos($html, 'ORD-SMALL'));
        $this->assertStringContainsString('aria-sort="ascending"', $html);
        $this->assertStringContainsString('status=pending', $html);
        $this->assertStringContainsString('direction=desc', $html);
    }

    public function test_an_unknown_sort_column_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['sort' => 'password']))
            ->assertSessionHasErrors('sort');

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['status' => 'refunded']))
            ->assertSessionHasErrors('status');
    }

    public function test_pagination_keeps_the_filters(): void
    {
        Order::factory()->count(21)->create(['status' => 'processing']);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['status' => 'processing']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('status=processing', $html);
        $this->assertStringContainsString('page=2', $html);
    }
}
