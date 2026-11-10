<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNewsletterManageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function subscriber(array $attributes = []): NewsletterSubscriber
    {
        return NewsletterSubscriber::create(array_merge([
            'email' => 'awa@example.com',
            'token' => str_repeat('a', 32),
            'subscribed_at' => now(),
        ], $attributes));
    }

    public function test_an_address_can_be_added_manually(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.store'), ['email' => 'Nouveau@Example.COM'])
            ->assertSessionHas('success');

        $subscriber = NewsletterSubscriber::sole();
        $this->assertSame('nouveau@example.com', $subscriber->email);
        $this->assertNotNull($subscriber->subscribed_at);
        $this->assertNull($subscriber->unsubscribed_at);
        $this->assertSame(32, strlen($subscriber->token));
    }

    public function test_a_duplicate_address_is_refused(): void
    {
        $this->subscriber();

        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.store'), ['email' => 'AWA@example.com'])
            ->assertSessionHas('error');

        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertNull(NewsletterSubscriber::sole()->unsubscribed_at);
    }

    public function test_an_invalid_address_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.store'), ['email' => 'pas-une-adresse'])
            ->assertSessionHasErrors('email');

        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_an_unsubscribed_address_is_reactivated(): void
    {
        $this->subscriber([
            'subscribed_at' => now()->subYear(),
            'unsubscribed_at' => now()->subMonth(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.store'), ['email' => 'awa@example.com'])
            ->assertSessionHas('success');

        $subscriber = NewsletterSubscriber::sole();
        $this->assertNull($subscriber->unsubscribed_at);
        $this->assertTrue($subscriber->subscribed_at->isAfter(now()->subMinute()));
        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_a_subscriber_can_be_deleted(): void
    {
        $subscriber = $this->subscriber();

        $this->actingAs($this->admin())
            ->delete(route('admin.newsletter.destroy', $subscriber))
            ->assertSessionHas('success');

        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_the_list_offers_the_manual_form_and_a_delete_button(): void
    {
        $this->subscriber();

        $html = $this->actingAs($this->admin())->get(route('admin.newsletter.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Ajouter un abonné', $html);
        $this->assertStringContainsString(route('admin.newsletter.store'), $html);
        $this->assertStringContainsString(route('admin.newsletter.destroy', NewsletterSubscriber::sole()), $html);
    }

    public function test_subscriber_management_is_admin_only(): void
    {
        $subscriber = $this->subscriber();
        $customer = User::factory()->create();

        $this->get(route('admin.newsletter.index'))->assertRedirect();
        $this->actingAs($customer)->post(route('admin.newsletter.store'), ['email' => 'autre@example.com'])->assertForbidden();
        $this->actingAs($customer)->delete(route('admin.newsletter.destroy', $subscriber))->assertForbidden();

        $this->assertSame(1, NewsletterSubscriber::count());
    }
}
