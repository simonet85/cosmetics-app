<?php

namespace Tests\Feature;

use App\Mail\ContactReply;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminMessageInboxTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function message(array $attributes = []): ContactMessage
    {
        return ContactMessage::create(array_merge([
            'name' => 'Awa Koné', 'email' => 'awa@example.com', 'subject' => 'Ma commande',
            'message' => "Bonjour\nOù est ORD-1 ?",
        ], $attributes));
    }

    public function test_the_inbox_is_for_admins_only(): void
    {
        $message = $this->message();
        $customer = User::factory()->create();

        $this->get(route('admin.messages.index'))->assertRedirect();
        $this->actingAs($customer)->get(route('admin.messages.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.messages.show', $message))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.messages.bulk'), ['ids' => [$message->id], 'action' => 'read'])->assertForbidden();

        $this->actingAs($this->admin())->get(route('admin.messages.index'))->assertOk();
    }

    public function test_messages_can_be_searched_by_sender_subject_or_body(): void
    {
        $this->message(['name' => 'Awa Koné', 'subject' => 'Livraison', 'message' => 'Question sur ma commande']);
        $this->message(['name' => 'Koffi Traoré', 'email' => 'koffi@example.com', 'subject' => 'Facture', 'message' => 'Où est la facture ?']);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.messages.index', ['q' => 'Awa Koné']))
            ->assertOk()->assertSee('awa@example.com')->assertDontSee('koffi@example.com');
        $this->actingAs($admin)->get(route('admin.messages.index', ['q' => 'koffi@example.com']))
            ->assertOk()->assertSee('Facture')->assertDontSee('Awa Koné');
        $this->actingAs($admin)->get(route('admin.messages.index', ['q' => 'facture']))
            ->assertOk()->assertSee('koffi@example.com')->assertDontSee('awa@example.com');
        $this->actingAs($admin)->get(route('admin.messages.index', ['q' => 'QUESTION']))
            ->assertOk()->assertSee('awa@example.com')->assertDontSee('koffi@example.com');
    }

    public function test_search_wildcards_are_kept_literal(): void
    {
        $this->message();

        $this->actingAs($this->admin())->get(route('admin.messages.index', ['q' => '%']))
            ->assertOk()->assertDontSee('awa@example.com');
    }

    public function test_messages_can_be_filtered_by_read_state(): void
    {
        $this->message(['subject' => 'En attente']);
        $this->message(['subject' => 'Déjà traité', 'is_read' => true]);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.messages.index', ['status' => 'unread']))
            ->assertOk()->assertSee('En attente')->assertDontSee('Déjà traité');
        $this->actingAs($admin)->get(route('admin.messages.index', ['status' => 'read']))
            ->assertOk()->assertSee('Déjà traité')->assertDontSee('En attente');
    }

    public function test_sorting_only_accepts_known_columns_and_directions(): void
    {
        $this->message(['name' => 'Zulu Client', 'email' => 'zulu@example.com']);
        $this->message(['name' => 'Alpha Client', 'email' => 'alpha@example.com']);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.messages.index', ['sort' => 'name', 'direction' => 'asc']))
            ->assertOk()->assertSeeInOrder(['alpha@example.com', 'zulu@example.com']);

        $this->actingAs($admin)->get(route('admin.messages.index', ['sort' => 'password']))
            ->assertSessionHasErrors('sort');
        $this->actingAs($admin)->get(route('admin.messages.index', ['direction' => 'sideways']))
            ->assertSessionHasErrors('direction');
    }

    public function test_pagination_keeps_the_filters(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $this->message(['subject' => 'Commande '.$i, 'message' => 'Question numéro '.$i]);
        }

        $html = $this->actingAs($this->admin())
            ->get(route('admin.messages.index', ['q' => 'Question']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('q=Question', $html);
        $this->assertStringContainsString('page=2', $html);
    }

    public function test_opening_a_message_marks_it_read_and_shows_it(): void
    {
        $message = $this->message();

        $this->actingAs($this->admin())->get(route('admin.messages.show', $message))
            ->assertOk()
            ->assertSee('Où est ORD-1 ?')
            ->assertSee('mailto:awa@example.com');

        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_a_reply_is_stored_and_emailed(): void
    {
        Mail::fake();
        $message = $this->message(['subject' => 'Ma commande']);

        $this->actingAs($this->admin())
            ->post(route('admin.messages.reply', $message), ['reply' => 'Votre colis arrive demain.'])
            ->assertSessionHas('success');

        $message->refresh();
        $this->assertSame('Votre colis arrive demain.', $message->admin_reply);
        $this->assertNotNull($message->replied_at);
        $this->assertTrue($message->is_read);

        Mail::assertSent(ContactReply::class, fn (ContactReply $mail) => $mail->hasTo('awa@example.com')
            && $mail->replySubject === 'Re: Ma commande');
    }

    public function test_a_reply_needs_a_text(): void
    {
        Mail::fake();
        $message = $this->message();

        $this->actingAs($this->admin())
            ->post(route('admin.messages.reply', $message), ['reply' => ''])
            ->assertSessionHasErrors('reply');

        $this->assertNull($message->fresh()->admin_reply);
        Mail::assertNothingSent();
    }

    public function test_bulk_actions_mark_read_unread_and_delete(): void
    {
        $a = $this->message();
        $b = $this->message();
        $read = $this->message(['is_read' => true]);

        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.messages.bulk'), ['ids' => [$a->id, $b->id], 'action' => 'read'])
            ->assertSessionHas('success');
        $this->assertTrue($a->fresh()->is_read);
        $this->assertTrue($b->fresh()->is_read);

        $this->actingAs($admin)->post(route('admin.messages.bulk'), ['ids' => [$read->id], 'action' => 'unread'])
            ->assertSessionHas('success');
        $this->assertFalse($read->fresh()->is_read);

        $this->actingAs($admin)->post(route('admin.messages.bulk'), ['ids' => [$a->id], 'action' => 'delete'])
            ->assertSessionHas('success');
        $this->assertNull($a->fresh());
        $this->assertSame(2, ContactMessage::count());
    }

    public function test_bulk_actions_refuse_more_than_a_hundred_messages(): void
    {
        $message = $this->message();
        $ids = array_merge([$message->id], range(2001, 2100));

        $this->actingAs($this->admin())
            ->post(route('admin.messages.bulk'), ['ids' => $ids, 'action' => 'delete'])
            ->assertSessionHasErrors('ids');

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_the_unread_badge_follows_model_and_bulk_actions(): void
    {
        $message = $this->message();
        $admin = $this->admin();

        $this->assertSame(1, ContactMessage::unreadCount());

        // Mass updates do not fire model events: the bulk action clears the badge by hand
        $this->actingAs($admin)->post(route('admin.messages.bulk'), ['ids' => [$message->id], 'action' => 'read']);
        $this->assertSame(0, ContactMessage::unreadCount());

        $this->message();
        $this->assertSame(1, ContactMessage::unreadCount());

        $this->actingAs($admin)->patch(route('admin.messages.read', $message));
        $this->assertSame(2, ContactMessage::unreadCount());

        $this->actingAs($admin)->delete(route('admin.messages.destroy', $message));
        $this->assertSame(1, ContactMessage::unreadCount());
    }
}
