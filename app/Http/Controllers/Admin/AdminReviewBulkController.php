<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

/** Moderation of a selection of reviews: approve, withdraw the approval, or delete. */
class AdminReviewBulkController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:reviews,id'],
            'action' => ['required', 'in:approve,unapprove,delete'],
        ], [
            'ids.required' => 'Sélectionnez au moins un avis.',
            'ids.max' => 'Sélectionnez au plus 100 avis à la fois.',
            'action.required' => 'Choisissez une action.',
        ]);

        $query = Review::whereIn('id', $validated['ids']);

        [$count, $message] = match ($validated['action']) {
            'approve' => [$query->update(['is_approved' => true]), 'avis approuvé(s).'],
            'unapprove' => [$query->update(['is_approved' => false]), 'approbation(s) retirée(s).'],
            'delete' => [$query->delete(), 'avis supprimé(s).'],
        };

        return back()->with('success', $count.' '.$message);
    }
}
