<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessageFilterRequest;
use App\Mail\ContactReply;
use App\Models\ContactMessage;
use App\Queries\MessageListQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Messages sent through the public contact form. */
class AdminMessageController extends Controller
{
    public function index(MessageFilterRequest $request)
    {
        $filters = $request->filters();
        $messages = (new MessageListQuery)->paginate($filters);
        $unread = ContactMessage::unreadCount();

        return view('admin.messages.index', compact('messages', 'unread', 'filters'));
    }

    public function show(ContactMessage $message)
    {
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return view('admin.messages.show', ['contactMessage' => $message]);
    }

    public function reply(Request $request, ContactMessage $message)
    {
        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:5000'],
            'subject' => ['nullable', 'string', 'max:255'],
        ], [
            'reply.required' => 'Écrivez votre réponse.',
            'reply.max' => 'La réponse ne peut pas dépasser 5000 caractères.',
        ]);

        $message->update([
            'admin_reply' => $validated['reply'],
            'replied_at' => now(),
            'is_read' => true,
        ]);

        $subject = filled($validated['subject'] ?? null)
            ? $validated['subject']
            : 'Re: '.($message->subject ?: 'votre message');

        try {
            Mail::to($message->email)->send(new ContactReply($message, $validated['reply'], $subject));
        } catch (Throwable $e) {
            report($e);
            Log::warning('Contact reply could not be e-mailed', ['message_id' => $message->id]);

            return back()->with('error', 'La réponse est enregistrée, mais l\'e-mail n\'a pas pu être envoyé. Vérifiez la messagerie.');
        }

        return back()->with('success', 'Réponse envoyée à '.$message->email.'.');
    }

    public function toggleRead(ContactMessage $message)
    {
        $message->update(['is_read' => ! $message->is_read]);

        return back()->with('success', $message->is_read ? 'Message marqué comme lu.' : 'Message marqué comme non lu.');
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return back()->with('success', 'Message supprimé.');
    }
}
