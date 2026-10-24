<?php

namespace Tests\Feature;

use App\Support\JsStrings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_storefront_is_french_by_default(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<html lang="fr"', $html);
        $this->assertStringContainsString('Mon panier', $html);
    }

    public function test_the_switch_route_sets_a_cookie_and_goes_back_to_the_page(): void
    {
        $this->from(route('pages.faq'))
            ->get(route('locale.switch', 'en'))
            ->assertRedirect(route('pages.faq'))
            ->assertCookie('locale', 'en');
    }

    public function test_an_unknown_locale_is_a_404(): void
    {
        $this->get(route('locale.switch', 'xx'))->assertNotFound();
    }

    public function test_the_switch_never_redirects_to_another_host(): void
    {
        $this->withHeader('Referer', 'https://evil.example/phish')
            ->get(route('locale.switch', 'en'))
            ->assertRedirect(route('home'));
    }

    public function test_the_cookie_switches_the_page_to_english(): void
    {
        $html = $this->withCookie('locale', 'en')->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<html lang="en"', $html);
        $this->assertStringContainsString('My cart', $html);
        $this->assertStringNotContainsString('Mon panier', $html);
    }

    public function test_the_browser_language_is_used_when_there_is_no_cookie(): void
    {
        $html = $this->withHeader('Accept-Language', 'en-GB,en;q=0.9')->get(route('home'))->getContent();

        $this->assertStringContainsString('<html lang="en"', $html);
    }

    public function test_an_unsupported_cookie_value_falls_back_to_french(): void
    {
        $html = $this->withCookie('locale', 'de')->get(route('home'))->getContent();

        $this->assertStringContainsString('<html lang="fr"', $html);
    }

    public function test_the_language_switcher_marks_the_current_language(): void
    {
        $html = $this->withCookie('locale', 'en')->get(route('home'))->getContent();

        $this->assertStringContainsString('hreflang="fr"', $html);
        $this->assertMatchesRegularExpression('/<a[^>]*aria-current="true"[^>]*hreflang="en"|<a[^>]*hreflang="en"[^>]*aria-current="true"/', $html);
    }

    public function test_the_browser_gets_the_translated_strings_of_the_script(): void
    {
        $html = $this->withCookie('locale', 'en')->get(route('home'))->getContent();

        $this->assertStringContainsString('id="i18n"', $html);
        $this->assertStringContainsString(':amount more for free delivery.', $html);
    }

    public function test_every_string_of_the_script_exists_in_the_english_file(): void
    {
        $en = json_decode(file_get_contents(lang_path('en.json')), true);

        foreach (JsStrings::keys() as $key) {
            $this->assertArrayHasKey($key, $en, "Missing English translation for: {$key}");
        }
    }

    public function test_labels_translated_through_a_variable_exist_in_the_english_file(): void
    {
        $en = json_decode(file_get_contents(lang_path('en.json')), true);

        $labels = [
            ...array_values(\App\Support\PaymentMethods::LABELS),
            'En préparation', 'Expédiée', 'Livrée', 'Annulée', 'Payé', 'Échoué', 'Remboursé',
            'Nouveautés', 'Prix croissant', 'Prix décroissant', 'Nom A à Z', 'Popularité',
        ];

        foreach ($labels as $label) {
            $this->assertArrayHasKey($label, $en, "Missing English translation for: {$label}");
        }
    }

    public function test_every_english_entry_keeps_the_placeholders_of_its_french_key(): void
    {
        $en = json_decode(file_get_contents(lang_path('en.json')), true);

        foreach ($en as $fr => $translated) {
            preg_match_all('/:\w+/', $fr, $expected);
            preg_match_all('/:\w+/', $translated, $actual);
            sort($expected[0]);
            sort($actual[0]);

            $this->assertSame($expected[0], $actual[0], "Placeholders differ for: {$fr}");
        }
    }

    public function test_legal_pages_warn_that_they_are_only_in_french(): void
    {
        $this->withCookie('locale', 'en')->get(route('terms'))
            ->assertSee('only available in French');
    }

    public function test_legal_pages_carry_no_such_warning_in_french(): void
    {
        $this->get(route('terms'))->assertDontSee('only available in French')->assertDontSee('qu\'en français');
    }
}
