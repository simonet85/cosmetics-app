<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactAbuseTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_honeypot_silently_discards_bot_messages(): void
    {
        Mail::fake();

        $this->from(route('pages.contact'))
            ->post(route('pages.contact.submit'), [
                'website' => 'https://spam.example',
                'name' => 'Bot',
                'email' => 'bot@example.com',
                'subject' => 'Spam',
                'message' => 'Buy now',
            ])
            ->assertRedirect(route('pages.contact'))
            ->assertSessionHas('success');

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_contact_submissions_are_rate_limited(): void
    {
        $payload = [
            'name' => 'Awa',
            'email' => 'awa@example.com',
            'subject' => 'Question',
            'message' => 'Bonjour, où est ma commande ?',
        ];

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('pages.contact.submit'), $payload)->assertRedirect(route('pages.contact'));
        }

        $this->post(route('pages.contact.submit'), $payload)->assertStatus(429);
    }
}
