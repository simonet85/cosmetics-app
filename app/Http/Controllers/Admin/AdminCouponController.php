<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query();

        // Filter by status
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search by code
        if ($request->filled('search')) {
            $query->where('code', 'like', "%{$request->search}%");
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $coupons = $query->paginate(20)->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ], [
            'code.required' => 'Le code du coupon est requis.',
            'code.unique' => 'Ce code existe déjà.',
            'type.required' => 'Le type de réduction est requis.',
            'type.in' => 'Le type doit être fixe ou pourcentage.',
            'value.required' => 'La valeur est requise.',
            'value.numeric' => 'La valeur doit être un nombre.',
            'value.min' => 'La valeur doit être positive.',
            'end_date.after_or_equal' => 'La date de fin doit être après ou égale à la date de début.',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['used_count'] = 0;
        $validated['is_active'] = $request->has('is_active');

        Coupon::create($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon créé avec succès.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ], [
            'code.required' => 'Le code du coupon est requis.',
            'code.unique' => 'Ce code existe déjà.',
            'type.required' => 'Le type de réduction est requis.',
            'type.in' => 'Le type doit être fixe ou pourcentage.',
            'value.required' => 'La valeur est requise.',
            'value.numeric' => 'La valeur doit être un nombre.',
            'value.min' => 'La valeur doit être positive.',
            'end_date.after_or_equal' => 'La date de fin doit être après ou égale à la date de début.',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['is_active'] = $request->has('is_active');

        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon mis à jour avec succès.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon supprimé avec succès.');
    }
}
