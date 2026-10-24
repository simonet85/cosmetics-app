<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontAccountTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(User $user, array $attributes = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'user_id' => $user->id, 'status' => 'pending', 'payment_status' => 'pending',
            'payment_method' => 'cash_on_delivery', 'total' => 12000,
            'shipping_address' => ['first_name' => 'Awa', 'last_name' => 'Koné', 'city' => 'Abidjan', 'quartier' => 'Cocody', 'phone' => '0701020304'],
        ], $attributes));
        $product = Product::factory()->create(['name' => 'Sérum Éclat', 'stock' => 5]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Sérum Éclat', 'sku' => 'S1', 'quantity' => 2, 'price' => 6000, 'total' => 12000]);

        return $order;
    }

    // ---------------------------------------------------------------- sign in / sign up

    public function test_login_page_has_labelled_fields_and_the_useful_links(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('for="field-email"', $html);
        $this->assertStringContainsString('autocomplete="current-password"', $html);
        $this->assertStringContainsString(route('password.request'), $html);
        $this->assertStringContainsString(route('register'), $html);
    }

    public function test_social_sign_in_is_only_offered_when_the_provider_is_configured(): void
    {
        $this->get(route('login'))->assertDontSee('Ou continuez avec')->assertDontSee('Facebook');

        config(['services.google.client_id' => 'abc']);

        $this->get(route('login'))->assertSee('Ou continuez avec')->assertSee('Google')->assertDontSee('Facebook');
    }

    public function test_a_wrong_password_shows_the_error_on_the_email_field(): void
    {
        User::factory()->create(['email' => 'awa@example.com', 'password' => bcrypt('bon-mot-de-passe')]);

        $html = $this->followingRedirects()->from(route('login'))
            ->post(route('login.post'), ['email' => 'awa@example.com', 'password' => 'mauvais'])->getContent();

        $this->assertStringContainsString('ne correspondent pas', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('value="awa@example.com"', $html);
    }

    public function test_the_register_page_links_the_real_legal_pages(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('privacy').'"', $html);
        $this->assertStringContainsString('href="'.route('terms').'"', $html);
        $this->assertStringNotContainsString('href="#"', $html);
        $this->assertStringContainsString('autocomplete="new-password"', $html);
    }

    public function test_registering_creates_the_account_and_signs_in(): void
    {
        Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $this->post(route('register.post'), [
            'first_name' => 'Awa', 'last_name' => 'Koné', 'email' => 'awa@example.com',
            'password' => 'un-bon-mot-de-passe', 'password_confirmation' => 'un-bon-mot-de-passe', 'terms' => '1',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertTrue(User::where('email', 'awa@example.com')->firstOrFail()->hasRole('customer'));
    }

    public function test_register_errors_name_the_fields(): void
    {
        $html = $this->followingRedirects()->from(route('register'))
            ->post(route('register.post'), ['first_name' => '', 'email' => 'pas-un-mail'])->getContent();

        $this->assertStringContainsString('Le prénom est obligatoire.', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('conditions d\'utilisation', $html);
    }

    // ---------------------------------------------------------------- account area

    public function test_the_account_area_needs_a_signed_in_customer(): void
    {
        foreach (['account.dashboard', 'account.orders', 'account.profile'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_dashboard_counts_are_real_and_use_the_delivered_status(): void
    {
        $user = User::factory()->create(['first_name' => 'Fanta']);
        $this->orderFor($user, ['status' => 'delivered', 'payment_status' => 'paid', 'total' => 20000]);
        $this->orderFor($user, ['status' => 'pending', 'payment_status' => 'pending']);
        $this->orderFor(User::factory()->create());

        $html = $this->actingAs($user)->get(route('account.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Bonjour Fanta', $html);
        $this->assertMatchesRegularExpression('/Commandes<\/dt><dd[^>]*>2</', $html);
        $this->assertMatchesRegularExpression('/En attente<\/dt><dd[^>]*>1</', $html);
        $this->assertMatchesRegularExpression('/Livrées<\/dt><dd[^>]*>1</', $html);
        $this->assertStringContainsString("20\u{00A0}000\u{00A0}FCFA", $html);
        $this->assertStringContainsString('aria-label="Mon compte"', $html);
    }

    public function test_orders_list_shows_only_my_orders_with_french_states(): void
    {
        $user = User::factory()->create();
        $mine = $this->orderFor($user, ['status' => 'shipped']);
        $other = $this->orderFor(User::factory()->create());

        $html = $this->actingAs($user)->get(route('account.orders'))->assertOk()->getContent();

        $this->assertStringContainsString($mine->order_number, $html);
        $this->assertStringNotContainsString($other->order_number, $html);
        $this->assertStringContainsString('Expédiée', $html);
        $this->assertStringNotContainsString('shipped', $html);
    }

    public function test_the_order_detail_is_private_and_offers_cancel_only_while_pending(): void
    {
        $user = User::factory()->create();
        $pending = $this->orderFor($user);
        $shipped = $this->orderFor($user, ['status' => 'shipped']);

        $this->actingAs(User::factory()->create())->get(route('account.orders.show', $pending))->assertForbidden();

        $this->actingAs($user)->get(route('account.orders.show', $pending))
            ->assertOk()->assertSee('Annuler cette commande')->assertSee('Sérum Éclat');
        $this->actingAs($user)->get(route('account.orders.show', $shipped))
            ->assertOk()->assertDontSee('Annuler cette commande')->assertSee('Expédiée');
    }

    public function test_an_unpaid_mobile_money_order_offers_to_pay(): void
    {
        $user = User::factory()->create();
        $order = $this->orderFor($user, ['payment_method' => 'moneyfusion', 'moneyfusion_payment_url' => 'https://pay.moneyfusion.test/x']);

        $this->actingAs($user)->get(route('account.orders.show', $order))
            ->assertOk()->assertSee('Régler cette commande')->assertSee('https://pay.moneyfusion.test/x');
    }

    public function test_cancelling_a_pending_order_gives_the_stock_back(): void
    {
        $user = User::factory()->create();
        $order = $this->orderFor($user);
        $product = Product::where('name', 'Sérum Éclat')->first();
        $product->update(['stock' => 3]);

        $this->actingAs($user)->put(route('account.orders.cancel', $order))->assertRedirect(route('account.orders'));

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
    }

    // ---------------------------------------------------------------- profile

    public function test_profile_form_is_prefilled_and_labelled(): void
    {
        $user = User::factory()->create(['first_name' => 'Fanta', 'city' => 'Bouaké']);

        $html = $this->actingAs($user)->get(route('account.profile'))->assertOk()->getContent();

        $this->assertStringContainsString('value="Fanta"', $html);
        $this->assertStringContainsString('value="Bouaké"', $html);
        $this->assertStringContainsString('for="field-first_name"', $html);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
    }

    public function test_updating_the_profile_saves_the_changes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.profile.update'), [
            'first_name' => 'Nouveau', 'last_name' => 'Nom', 'email' => 'nouveau@example.com', 'city' => 'Daloa',
        ])->assertSessionHas('success');

        $this->assertSame('Nouveau', $user->fresh()->first_name);
        $this->assertSame('Daloa', $user->fresh()->city);
    }

    public function test_a_refused_password_change_leaves_the_rest_of_the_profile_untouched(): void
    {
        $user = User::factory()->create(['first_name' => 'Ancien', 'password' => bcrypt('mot-de-passe-actuel')]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'first_name' => 'Nouveau', 'last_name' => 'Nom', 'email' => $user->email,
            'current_password' => 'mauvais', 'new_password' => 'un-autre-mot-de-passe', 'new_password_confirmation' => 'un-autre-mot-de-passe',
        ])->assertSessionHasErrors('current_password');

        $this->assertSame('Ancien', $user->fresh()->first_name);
        $this->assertTrue(Hash::check('mot-de-passe-actuel', $user->fresh()->password));
    }

    public function test_the_password_changes_with_the_right_current_one(): void
    {
        $user = User::factory()->create(['password' => bcrypt('mot-de-passe-actuel')]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'first_name' => 'A', 'last_name' => 'B', 'email' => $user->email,
            'current_password' => 'mot-de-passe-actuel', 'new_password' => 'un-autre-mot-de-passe', 'new_password_confirmation' => 'un-autre-mot-de-passe',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('un-autre-mot-de-passe', $user->fresh()->password));
    }

    // ---------------------------------------------------------------- wishlist

    public function test_wishlist_lists_only_products_that_can_still_be_bought(): void
    {
        $visible = Product::factory()->create(['name' => 'Produit visible']);
        $hidden = Product::factory()->create(['name' => 'Produit retiré', 'is_active' => false]);

        $html = $this->withSession(['wishlist' => [$visible->id, $hidden->id]])->get(route('wishlist.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('Produit visible', $html);
        $this->assertStringNotContainsString('Produit retiré', $html);
        $this->assertStringContainsString('data-add-to-cart', $html);
        $this->assertStringContainsString('data-wishlist-remove="'.$visible->id.'"', $html);
    }

    public function test_an_empty_wishlist_points_to_the_shop(): void
    {
        $this->get(route('wishlist.index'))->assertOk()->assertSee('pas encore de favoris')->assertSee(route('shop.index'));
    }

    public function test_the_header_wishlist_count_matches_the_session_for_signed_in_customers_too(): void
    {
        $product = Product::factory()->create();

        $html = $this->actingAs(User::factory()->create())->withSession(['wishlist' => [$product->id]])->get(route('wishlist.index'))->getContent();

        $this->assertMatchesRegularExpression('/data-count="wishlist"[^>]*>\s*1\s*</', $html);
    }
}
