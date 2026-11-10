<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponActionsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_a_coupon_can_be_switched_on_and_off(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'PROMO', 'is_active' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.coupons.toggle', $coupon))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertFalse($coupon->fresh()->is_active);

        $this->actingAs($admin)->patch(route('admin.coupons.toggle', $coupon))
            ->assertSessionHas('success');
        $this->assertTrue($coupon->fresh()->is_active);
    }

    public function test_a_coupon_can_be_duplicated_as_an_inactive_copy(): void
    {
        $coupon = Coupon::factory()->create([
            'code' => 'PRINTEMPS',
            'type' => 'fixed',
            'value' => 1500,
            'min_amount' => 20000,
            'max_discount' => null,
            'usage_limit' => 50,
            'used_count' => 7,
            'is_active' => true,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.coupons.duplicate', $coupon))
            ->assertSessionHas('success');

        $copy = Coupon::where('code', 'PRINTEMPS-COPIE')->firstOrFail();

        $this->assertSame($copy->id, (int) Coupon::max('id')); // it is the brand new row
        $this->assertSame('fixed', $copy->type);
        $this->assertEquals(1500, (float) $copy->value);
        $this->assertEquals(20000, (float) $copy->min_amount);
        $this->assertNull($copy->max_discount);
        $this->assertSame(50, (int) $copy->usage_limit);
        $this->assertSame(0, (int) $copy->used_count);
        $this->assertFalse($copy->is_active);
        $this->assertSame($coupon->start_date->toDateString(), $copy->start_date->toDateString());
        $this->assertSame($coupon->end_date->toDateString(), $copy->end_date->toDateString());

        // The original is left untouched
        $this->assertSame(7, (int) $coupon->fresh()->used_count);
        $this->assertTrue($coupon->fresh()->is_active);
    }

    public function test_duplicates_get_incrementing_codes(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'PROMO']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.coupons.duplicate', $coupon));
        $this->actingAs($admin)->post(route('admin.coupons.duplicate', $coupon));

        $this->assertSame(
            ['PROMO-COPIE', 'PROMO-COPIE-2'],
            Coupon::where('id', '!=', $coupon->id)->orderBy('id')->pluck('code')->all()
        );
    }

    public function test_the_expired_filter_lists_only_finished_coupons(): void
    {
        Coupon::factory()->create(['code' => 'ANCIEN', 'end_date' => now()->subDay()->toDateString()]);
        Coupon::factory()->create(['code' => 'VALIDE', 'end_date' => now()->addMonth()->toDateString()]);

        $this->actingAs($this->admin())
            ->get(route('admin.coupons.index', ['status' => 'expired']))
            ->assertOk()
            ->assertSee('ANCIEN')
            ->assertDontSee('VALIDE');
    }

    public function test_coupon_actions_are_admin_only(): void
    {
        $coupon = Coupon::factory()->create();
        $customer = User::factory()->create();

        $this->get(route('admin.coupons.index'))->assertRedirect();
        $this->actingAs($customer)->get(route('admin.coupons.index'))->assertForbidden();
        $this->actingAs($customer)->patch(route('admin.coupons.toggle', $coupon))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.coupons.duplicate', $coupon))->assertForbidden();

        $this->assertTrue($coupon->fresh()->is_active);
        $this->assertSame(1, Coupon::count());
    }
}
