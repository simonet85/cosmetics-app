<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StorefrontInfoPagesTest extends TestCase
{
    use RefreshDatabase;

    private function setting(string $key, string $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], [
            'value' => $value, 'type' => 'text', 'group' => 'general', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_every_info_page_is_on_the_storefront_layout_with_one_h1(): void
    {
        foreach (['pages.about', 'pages.contact', 'pages.faq', 'privacy', 'terms'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, '<h1'), $route);
            $this->assertStringContainsString('id="content"', $html, $route);
            $this->assertStringContainsString('aria-label="'.e('Fil d\'Ariane').'"', $html, $route);
            $this->assertStringNotContainsString('container-custom', $html, $route);
        }
    }

    public function test_no_page_carries_the_template_leftovers_of_another_country(): void
    {
        foreach (['pages.about', 'pages.contact', 'pages.faq', 'privacy', 'terms'] as $route) {
            $html = $this->get(route($route))->getContent();

            foreach (['Canada', 'Montréal', '+1 (514)', '123 Rue de la Beauté', '@glowing.com', '50$', '10$'] as $leftover) {
                $this->assertStringNotContainsString($leftover, $html, "{$route} still contains {$leftover}");
            }
        }
    }

    public function test_about_states_real_promises_from_the_settings(): void
    {
        $this->setting('estimated_delivery_days', '1-3');

        $this->get(route('pages.about'))->assertOk()
            ->assertSee('Livraison gratuite')->assertSee('Délai estimé : 1-3 jours')->assertSee('mobile money')
            ->assertSee(route('terms').'#retours', false)
            ->assertDontSee('certifié')->assertDontSee('7j/7');
    }

    public function test_the_faq_answers_follow_the_shop_settings(): void
    {
        $this->setting('standard_shipping_cost', '2000');
        $this->setting('free_shipping_threshold', '30000');

        $html = $this->get(route('pages.faq'))->assertOk()->getContent();

        $this->assertStringContainsString("La livraison coûte 2\u{00A0}000\u{00A0}FCFA et elle est gratuite dès 30\u{00A0}000\u{00A0}FCFA", $html);
        $this->assertGreaterThanOrEqual(8, substr_count($html, '<details'));
        $this->assertStringContainsString(route('password.request'), $html);
        $this->assertStringContainsString(route('terms').'#retours', $html);
        $this->assertStringNotContainsString('faq-question', $html);
    }

    public function test_faq_says_delivery_is_free_when_there_is_no_shipping_cost(): void
    {
        $this->get(route('pages.faq'))->assertSee('La livraison est gratuite.')->assertDontSee('gratuite dès');
    }

    public function test_contact_shows_only_the_details_set_in_the_shop_settings(): void
    {
        $this->get(route('pages.contact'))->assertOk()->assertDontSee('Téléphone</dt>', false)->assertSee('nous vous répondons par e-mail');

        $this->setting('contact_email', 'bonjour@glowing.test');
        $this->setting('phone', '+225 07 00 00 00 00');

        $html = $this->get(route('pages.contact'))->getContent();

        $this->assertStringContainsString('mailto:bonjour@glowing.test', $html);
        $this->assertStringContainsString('tel:+2250700000000', $html);
    }

    public function test_contact_form_labels_and_errors(): void
    {
        $html = $this->followingRedirects()->from(route('pages.contact'))->post(route('pages.contact.submit'), [])->getContent();

        foreach (['name', 'email', 'subject'] as $field) {
            $this->assertStringContainsString('for="field-'.$field.'"', $html);
        }
        $this->assertStringContainsString('Indiquez votre nom.', $html);
        $this->assertStringContainsString('Écrivez votre message.', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_a_contact_message_is_stored_and_mailed_to_the_shop(): void
    {
        $this->setting('contact_email', 'bonjour@glowing.test');

        $this->post(route('pages.contact.submit'), [
            'name' => 'Awa Koné', 'email' => 'awa@example.com', 'phone' => '0701020304',
            'subject' => 'Ma commande', 'message' => 'Bonjour, où est ma commande ORD-123 ?',
        ])->assertRedirect(route('pages.contact'))->assertSessionHas('success');

        $stored = ContactMessage::firstOrFail();
        $this->assertSame('awa@example.com', $stored->email);
        $this->assertStringContainsString('ORD-123', $stored->message);
        $this->assertStringContainsString('0701020304', $stored->message);
        $this->assertFalse($stored->is_read);

        $sent = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $sent);
        $email = $sent->first()->getOriginalMessage();
        $this->assertSame('bonjour@glowing.test', $email->getTo()[0]->getAddress());
        $this->assertSame('awa@example.com', $email->getReplyTo()[0]->getAddress());
        $this->assertStringContainsString('ORD-123', $email->getTextBody());
    }

    public function test_the_message_is_kept_even_if_the_mail_cannot_be_sent(): void
    {
        config(['mail.default' => 'failover', 'mail.mailers.failover' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 1]]);

        $this->post(route('pages.contact.submit'), [
            'name' => 'Awa', 'email' => 'awa@example.com', 'subject' => 'Test', 'message' => 'Message à conserver',
        ])->assertRedirect(route('pages.contact'))->assertSessionHas('success');

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_legal_pages_keep_their_text_and_get_the_prose_style(): void
    {
        $terms = $this->get(route('terms'))->assertOk()->getContent();
        $privacy = $this->get(route('privacy'))->assertOk()->getContent();

        $this->assertStringContainsString('info-prose', $terms);
        $this->assertStringContainsString('id="retours"', $terms);
        $this->assertStringContainsString('Retours et remboursements', $terms);
        $this->assertStringContainsString('Dernière mise à jour : 21 novembre 2025', $privacy);
        $this->assertStringContainsString('MoneyFusion', $privacy);
        $this->assertStringNotContainsString('class="text-gray', $terms.$privacy);
    }
}
