<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Support\PaymentMethods;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StorefrontFoundationTest extends TestCase
{
    use RefreshDatabase;

    private const NBSP = "\u{00A0}";

    private function setting(string $key, string $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], [
            'value' => $value, 'type' => 'text', 'group' => 'general', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function page(string $body = '<p>Contenu</p>', string $attributes = 'title="Boutique"'): string
    {
        return (string) $this->blade("<x-layouts.storefront {$attributes}>{$body}</x-layouts.storefront>");
    }

    // ------------------------------------------------------------ helper and settings source

    public function test_format_fcfa_uses_whole_francs_and_non_breaking_spaces(): void
    {
        $this->assertSame('12'.self::NBSP.'500'.self::NBSP.'FCFA', format_fcfa(12500));
        $this->assertSame('1'.self::NBSP.'234'.self::NBSP.'567'.self::NBSP.'FCFA', format_fcfa(1234567));
        $this->assertSame('0'.self::NBSP.'FCFA', format_fcfa(0));
        $this->assertSame('0'.self::NBSP.'FCFA', format_fcfa(null));
    }

    public function test_format_fcfa_rounds_away_decimals_because_the_franc_has_no_subunit(): void
    {
        $this->assertSame('30'.self::NBSP.'FCFA', format_fcfa(29.99));
        $this->assertSame('14'.self::NBSP.'197'.self::NBSP.'FCFA', format_fcfa('14196.75'));
    }

    public function test_every_payment_method_is_accepted_by_default(): void
    {
        $this->assertSame(array_keys(PaymentMethods::LABELS), array_keys(PaymentMethods::acceptedLabels()));
        $this->assertContains(true, PaymentMethods::enabled());
    }

    public function test_a_payment_method_switched_off_in_the_settings_is_no_longer_accepted(): void
    {
        $this->setting('payment_paypal_enabled', '0');
        $this->setting('payment_credit_card_enabled', '0');

        $this->assertFalse(PaymentMethods::enabled()['paypal']);
        $this->assertArrayNotHasKey('paypal', PaymentMethods::acceptedLabels());
        $this->assertArrayHasKey('moneyfusion', PaymentMethods::acceptedLabels());
    }

    // ------------------------------------------------------------ layout

    public function test_the_layout_is_an_accessible_french_page_shell(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('<html lang="fr">', $html);
        $this->assertStringContainsString('name="viewport" content="width=device-width, initial-scale=1"', $html);
        $this->assertStringNotContainsString('user-scalable=no', $html);
        $this->assertStringContainsString('<a href="#content" class="skip-link">Aller au contenu</a>', $html);
        $this->assertSame(1, substr_count($html, '<main id="content"'));
        $this->assertSame(1, substr_count($html, 'id="toast-region"'));
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('name="csrf-token"', $html);
        $this->assertStringContainsString("classList.add('js')", $html);
    }

    public function test_the_title_and_share_tags_come_from_the_page_and_the_brand(): void
    {
        $html = $this->page('<p>x</p>', 'title="Soins visage" description="Une sélection de soins."');

        $this->assertStringContainsString('<title>Soins visage | '.config('app.name').'</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Une sélection de soins.">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Soins visage | '.config('app.name').'">', $html);
        $this->assertStringContainsString('<link rel="canonical"', $html);
        $this->assertStringContainsString('<meta name="theme-color" content="#0b7a4b">', $html);
    }

    public function test_without_a_page_title_the_title_is_just_the_brand(): void
    {
        $this->assertStringContainsString('<title>'.config('app.name').'</title>', $this->page('<p>x</p>', ''));
    }

    public function test_the_storefront_pulls_in_no_third_party_scripts_or_icon_fonts(): void
    {
        $html = $this->page();

        foreach (['jquery', 'slick', 'font-awesome', 'fontawesome', 'cdnjs', 'googleapis', 'bootstrap'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $html, "{$forbidden} must not appear in the storefront layout");
        }
    }

    // ------------------------------------------------------------ header

    public function test_the_header_has_the_main_links_and_named_icon_buttons(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('href="'.route('shop.index').'"', $html);
        $this->assertStringContainsString('href="'.route('pages.about').'"', $html);
        $this->assertStringContainsString('href="'.route('pages.contact').'"', $html);
        $this->assertStringContainsString('href="'.route('cart.index').'"', $html);

        foreach (['Ouvrir le menu', 'Rechercher', 'Se connecter', 'Mes favoris', 'Mon panier', 'Fermer le menu', 'Fermer la recherche'] as $name) {
            $this->assertStringContainsString('<span class="sr-only">'.$name.'</span>', $html, "icon control '{$name}' needs an accessible name");
        }
    }

    public function test_the_account_link_depends_on_whether_the_visitor_is_signed_in(): void
    {
        $this->assertStringContainsString('href="'.route('login').'"', $this->page());

        $this->actingAs(User::factory()->create());
        $html = $this->page();
        $this->assertStringContainsString('href="'.route('account.dashboard').'"', $html);
        $this->assertStringContainsString('Mon compte', $html);
        $this->assertStringContainsString('Se déconnecter', $html);
        $this->assertStringNotContainsString('Administration', $html);
    }

    public function test_admins_get_a_link_to_the_administration_in_the_menu(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->assertStringContainsString('href="'.route('admin.dashboard').'"', $this->page());
    }

    public function test_the_cart_counter_is_read_from_the_session_and_hidden_when_empty(): void
    {
        $this->assertMatchesRegularExpression('/data-count="cart" aria-hidden="true"\s+hidden/', $this->page());

        $html = (string) $this->withSession(['cart' => [
            '1' => ['product_id' => 1, 'variant_id' => null, 'quantity' => 2],
            '2' => ['product_id' => 2, 'variant_id' => null, 'quantity' => 3],
        ]])->blade('<x-layouts.storefront>x</x-layouts.storefront>');

        $this->assertMatchesRegularExpression('/data-count="cart" aria-hidden="true"\s+class="[^"]*">5</', $html);
    }

    public function test_the_navigation_lists_the_real_active_categories_only(): void
    {
        Category::factory()->create(['name' => 'Soins du visage', 'slug' => 'soins-visage', 'is_active' => true, 'order' => 1]);
        Category::factory()->create(['name' => 'Catégorie cachée', 'slug' => 'cachee', 'is_active' => false]);
        $child = Category::factory()->create(['name' => 'Sous-catégorie', 'slug' => 'sous', 'is_active' => true]);
        $child->update(['parent_id' => Category::where('slug', 'soins-visage')->value('id')]);

        $html = $this->page();

        $this->assertStringContainsString('Soins du visage', $html);
        $this->assertStringContainsString('href="'.route('shop.category', 'soins-visage').'"', $html);
        $this->assertStringNotContainsString('Catégorie cachée', $html);
        $this->assertStringNotContainsString('Sous-catégorie', $html);
    }

    public function test_the_promise_band_reads_the_free_shipping_threshold_and_payments_from_the_settings(): void
    {
        $this->setting('standard_shipping_cost', '2000');
        $this->setting('free_shipping_threshold', '75000');
        $this->setting('payment_credit_card_enabled', '0');
        $this->setting('payment_paypal_enabled', '0');

        $html = $this->page();

        $this->assertStringContainsString('Livraison gratuite dès 75'.self::NBSP.'000'.self::NBSP.'FCFA', $html);
        $this->assertStringContainsString('mobile money', $html);
        $this->assertStringContainsString('paiement à la livraison', $html);
        $this->assertStringContainsString('virement bancaire', $html);
        $this->assertStringNotContainsString('carte bancaire', $html);
        $this->assertStringNotContainsString('paypal', strtolower($html));
    }

    public function test_when_shipping_costs_nothing_the_band_does_not_invent_a_threshold(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('Livraison gratuite', $html);
        $this->assertStringNotContainsString('Livraison gratuite dès', $html);
    }

    public function test_the_promise_band_falls_back_to_the_default_threshold(): void
    {
        $this->setting('standard_shipping_cost', '2000');

        $this->assertStringContainsString('Livraison gratuite dès 50'.self::NBSP.'000'.self::NBSP.'FCFA', $this->page());
    }

    public function test_the_current_section_is_marked_in_the_navigation(): void
    {
        // A probe route named like the shop section, so the layout sees a real current route
        Route::get('/_probe-shop', fn () => Blade::render('<x-layouts.storefront>x</x-layouts.storefront>'))->name('shop.probe');

        $html = $this->get('/_probe-shop')->getContent();

        $this->assertMatchesRegularExpression('/<a href="[^"]*\/shop"\s+aria-current="page"/', $html);
        $this->assertSame(2, substr_count($html, 'aria-current="page"'), 'the desktop nav and the drawer each mark it once');
    }

    public function test_no_section_is_marked_current_on_the_home_page(): void
    {
        Route::get('/_probe-home', fn () => Blade::render('<x-layouts.storefront>x</x-layouts.storefront>'))->name('probe.home');

        $this->assertStringNotContainsString('aria-current="page"', $this->get('/_probe-home')->getContent());
    }

    // ------------------------------------------------------------ footer

    public function test_the_footer_links_to_real_legal_pages_and_has_a_labelled_newsletter_form(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('href="'.route('terms').'"', $html);
        $this->assertStringContainsString('href="'.route('privacy').'"', $html);
        $this->assertStringContainsString('href="'.route('pages.faq').'"', $html);
        $this->assertStringNotContainsString('href="#"', $html);

        $this->assertStringContainsString('<label for="newsletter-email"', $html);
        $this->assertStringContainsString('action="'.route('newsletter.subscribe').'"', $html);
        $this->assertStringContainsString('autocomplete="email"', $html);
    }

    // ------------------------------------------------------------ components

    public function test_price_shows_whole_francs_with_a_non_breaking_unit(): void
    {
        $html = (string) $this->blade('<x-ui.price :amount="12500" />');

        $this->assertStringContainsString('12'.self::NBSP.'500', $html);
        $this->assertStringContainsString('FCFA', $html);
        $this->assertStringContainsString('tnum', $html);
        $this->assertStringNotContainsString('<s ', $html);
    }

    public function test_price_strikes_through_the_list_price_and_announces_the_discount(): void
    {
        $html = (string) $this->blade('<x-ui.price :amount="8000" :original="10000" />');

        $this->assertStringContainsString('<s class="text-base text-ink-muted">10'.self::NBSP.'000'.self::NBSP.'FCFA</s>', $html);
        $this->assertStringContainsString('Prix actuel', $html);
        $this->assertStringContainsString('au lieu de', $html);
    }

    public function test_price_ignores_an_original_that_is_not_higher(): void
    {
        foreach ([8000, 5000, null] as $original) {
            $html = (string) $this->blade('<x-ui.price :amount="8000" :original="$original" />', ['original' => $original]);
            $this->assertStringNotContainsString('<s ', $html);
        }
    }

    public function test_button_renders_a_link_with_an_href_and_a_button_otherwise(): void
    {
        $link = (string) $this->blade('<x-ui.button href="/shop">Voir</x-ui.button>');
        $this->assertStringContainsString('<a href="/shop"', $link);
        $this->assertStringContainsString('min-h-12', $link);

        $button = (string) $this->blade('<x-ui.button type="submit" variant="secondary">Envoyer</x-ui.button>');
        $this->assertStringContainsString('<button type="submit"', $button);
        $this->assertStringContainsString('border-ink', $button);
    }

    public function test_badge_carries_its_meaning_in_text(): void
    {
        $html = (string) $this->blade('<x-ui.badge tone="ticket">-15 %</x-ui.badge>');

        $this->assertStringContainsString('-15 %', $html);
        $this->assertStringContainsString('bg-ticket', $html);
    }

    public function test_field_has_a_visible_label_and_links_help_and_error_to_the_input(): void
    {
        $html = (string) $this->blade('<x-ui.field label="Téléphone" name="phone" type="tel" inputmode="tel" autocomplete="tel" :required="true" help="Format local : 07 00 00 00 00" error="Le numéro est invalide." />');

        $this->assertStringContainsString('<label for="field-phone"', $html);
        $this->assertStringContainsString('Téléphone', $html);
        $this->assertStringContainsString('(obligatoire)', $html);
        $this->assertStringContainsString('id="field-phone"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="field-phone-help field-phone-error"', $html);
        $this->assertStringContainsString('id="field-phone-help"', $html);
        $this->assertStringContainsString('id="field-phone-error"', $html);
        $this->assertStringContainsString('inputmode="tel"', $html);
        $this->assertStringContainsString('autocomplete="tel"', $html);
    }

    public function test_field_without_error_or_help_has_no_described_by_and_is_not_invalid(): void
    {
        $html = (string) $this->blade('<x-ui.field label="Nom" name="name" />');

        $this->assertStringNotContainsString('aria-describedby', $html);
        $this->assertStringNotContainsString('aria-invalid', $html);
        $this->assertStringContainsString('border-field', $html);
    }

    public function test_icons_are_decorative_by_default(): void
    {
        $html = (string) $this->blade('<x-ui.icon name="bag" />');

        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('<path', $html);
        $this->assertStringContainsString('stroke-width="1.75"', $html);
    }

    public function test_section_heading_has_no_kicker_above_the_title(): void
    {
        $html = (string) $this->blade('<x-ui.section-heading title="Notre sélection" lead="Peu de produits, bien choisis." />');

        $this->assertMatchesRegularExpression('/^\s*<div[^>]*>\s*<h2 class="[^"]*">Notre sélection<\/h2>/s', $html);
        $this->assertStringContainsString('Peu de produits, bien choisis.', $html);
    }

    public function test_empty_state_says_what_is_missing_and_offers_the_action(): void
    {
        $html = (string) $this->blade('<x-ui.empty-state title="Panier vide" text="Ajoutez un produit."><a href="/shop">Voir la boutique</a></x-ui.empty-state>');

        $this->assertStringContainsString('Panier vide', $html);
        $this->assertStringContainsString('Ajoutez un produit.', $html);
        $this->assertStringContainsString('<a href="/shop">Voir la boutique</a>', $html);
    }
}
