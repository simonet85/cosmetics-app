<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;

/** Messages sent through the public contact form. */
class AdminMessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(20);
        $unread = ContactMessage::unread()->count();

        return view('admin.messages.index', compact('messages', 'unread'));
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
