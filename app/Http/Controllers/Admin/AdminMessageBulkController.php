<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Read state or deletion applied to a selection of contact messages. */
class AdminMessageBulkController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:contact_messages,id'],
            'action' => ['required', 'in:read,unread,delete'],
        ], [
            'ids.required' => 'Sélectionnez au moins un message.',
            'ids.max' => 'Sélectionnez au plus 100 messages à la fois.',
            'action.required' => 'Choisissez une action.',
        ]);

        $query = ContactMessage::whereIn('id', $validated['ids']);

        [$count, $message] = match ($validated['action']) {
            'read' => [$query->update(['is_read' => true]), 'message(s) marqué(s) comme lu(s).'],
            'unread' => [$query->update(['is_read' => false]), 'message(s) marqué(s) comme non lu(s).'],
            'delete' => [$query->delete(), 'message(s) supprimé(s).'],
        };

        // Mass updates and deletes do not fire model events: clear the menu badge by hand
        Cache::forget(ContactMessage::UNREAD_CACHE_KEY);

        return back()->with('success', $count.' '.$message);
    }
}
