<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_subscribe_from_any_page(): void
    {
        $this->from('/')
            ->post(route('newsletter.subscribe'), ['email' => 'Awa@Example.COM'])
            ->assertRedirect('/')
            ->assertSessionHas('newsletter_success');

        $subscriber = NewsletterSubscriber::firstOrFail();
        $this->assertSame('awa@example.com', $subscriber->email);
        $this->assertNotNull($subscriber->subscribed_at);
        $this->assertNull($subscriber->unsubscribed_at);
        $this->assertGreaterThanOrEqual(32, strlen($subscriber->token));
    }

    public function test_the_footer_shows_the_confirmation_message(): void
    {
        $this->from('/')->followingRedirects()
            ->post(route('newsletter.subscribe'), ['email' => 'awa@example.com'])
            ->assertOk()
            ->assertSee('bien inscrite à la newsletter');
    }

    public function test_subscribing_twice_keeps_a_single_row(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'awa@example.com']);
        $this->post(route('newsletter.subscribe'), ['email' => 'AWA@example.com']);

        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertSame('awa@example.com', NewsletterSubscriber::firstOrFail()->email);
    }

    public function test_an_unsubscribed_address_can_sign_up_again(): void
    {
        NewsletterSubscriber::create([
            'email' => 'awa@example.com',
            'token' => str_repeat('a', 32),
            'subscribed_at' => now()->subYear(),
            'unsubscribed_at' => now()->subMonth(),
        ]);

        $this->post(route('newsletter.subscribe'), ['email' => 'awa@example.com']);

        $subscriber = NewsletterSubscriber::firstOrFail();
        $this->assertNull($subscriber->unsubscribed_at);
        $this->assertTrue($subscriber->subscribed_at->isAfter(now()->subMinute()));
    }

    public function test_an_invalid_address_is_rejected_in_the_footer(): void
    {
        $this->from('/')->followingRedirects()
            ->post(route('newsletter.subscribe'), ['email' => 'not-an-email'])
            ->assertSee('L\'adresse e-mail doit être valide.');

        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_newsletter_signups_are_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('newsletter.subscribe'), ['email' => 'awa@example.com'])->assertRedirect();
        }

        $this->post(route('newsletter.subscribe'), ['email' => 'awa@example.com'])->assertStatus(429);
    }
}
