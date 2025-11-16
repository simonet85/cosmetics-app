<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount(['orders', 'reviews']);

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->role($request->role);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $customers = $query->paginate(20)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer)
    {
        $customer->load(['orders.items', 'reviews.product']);
        $customer->loadCount(['orders', 'reviews']);

        // Calculate total spent
        $totalSpent = $customer->orders()
            ->whereIn('status', ['delivered', 'processing', 'shipped'])
            ->sum('total');

        return view('admin.customers.show', compact('customer', 'totalSpent'));
    }

    public function destroy(User $customer)
    {
        // Prevent deleting admin users
        if ($customer->hasAnyRole(['admin', 'super_admin'])) {
            return back()->with('error', 'Impossible de supprimer un administrateur.');
        }

        // Prevent deleting users with orders
        if ($customer->orders()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer un client avec des commandes.');
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', 'Client supprimé avec succès.');
    }
}
