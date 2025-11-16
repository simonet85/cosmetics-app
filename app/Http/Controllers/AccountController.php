<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        // Get user's recent orders
        $recentOrders = Order::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // Get order statistics
        $totalOrders = Order::where('user_id', $user->id)->count();
        $pendingOrders = Order::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
        $completedOrders = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
        $totalSpent = Order::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->sum('total');

        return view('account.dashboard', compact(
            'user',
            'recentOrders',
            'totalOrders',
            'pendingOrders',
            'completedOrders',
            'totalSpent'
        ));
    }

    public function profile()
    {
        $user = auth()->user();
        return view('account.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => 'nullable|min:8|confirmed',
        ], [
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'avatar.image' => 'Le fichier doit être une image.',
            'avatar.mimes' => 'L\'image doit être au format JPEG, JPG ou PNG.',
            'avatar.max' => 'L\'image ne doit pas dépasser 2 Mo.',
            'current_password.required_with' => 'Le mot de passe actuel est requis pour changer le mot de passe.',
            'new_password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'new_password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $avatar = $request->file('avatar');

            // Get extension and ensure it's not empty
            $extension = $avatar->getClientOriginalExtension();
            if (empty($extension)) {
                $extension = $avatar->guessExtension() ?? 'jpg';
            }

            // Validate extension
            if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png'])) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Format d\'image non supporté. Utilisez JPG, JPEG ou PNG.');
            }

            try {
                // Delete old avatar if exists
                if ($user->avatar) {
                    $oldAvatarPath = public_path($user->avatar);
                    if (file_exists($oldAvatarPath)) {
                        @unlink($oldAvatarPath);
                    }
                }

                // Ensure directory exists
                $avatarDir = public_path('storage/images/avatars');
                if (!file_exists($avatarDir)) {
                    mkdir($avatarDir, 0755, true);
                }

                // Generate unique filename
                $filename = time() . '-' . uniqid() . '.' . $extension;

                // Move uploaded file using Symfony's move method
                $avatar->move($avatarDir, $filename);

                // Set the avatar path
                $validated['avatar'] = 'storage/images/avatars/' . $filename;

            } catch (\Exception $e) {
                \Log::error('Avatar upload error', [
                    'message' => $e->getMessage(),
                    'file' => $avatar->getClientOriginalName() ?? 'unknown',
                    'size' => $avatar->getSize() ?? 0,
                    'extension' => $extension ?? 'unknown',
                    'trace' => $e->getTraceAsString()
                ]);

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Erreur lors de l\'upload de l\'avatar. Veuillez réessayer.');
            }
        }

        // Update user info
        $updateData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'country' => $validated['country'] ?? null,
        ];

        if (isset($validated['avatar'])) {
            $updateData['avatar'] = $validated['avatar'];
        }

        $user->update($updateData);

        // Update password if provided
        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return redirect()->back()
                    ->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.'])
                    ->withInput();
            }

            $user->update([
                'password' => Hash::make($request->new_password)
            ]);
        }

        return redirect()->back()->with('success', 'Profil mis à jour avec succès!');
    }

    public function orders()
    {
        $orders = Order::where('user_id', auth()->id())
            ->with('items.product')
            ->latest()
            ->paginate(10);

        return view('account.orders', compact('orders'));
    }

    public function orderDetail(Order $order)
    {
        // Check if user owns this order
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['items.product.images', 'items.variant']);

        return view('account.order-detail', compact('order'));
    }

    public function cancelOrder(Order $order)
    {
        // Check if user owns this order
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Only allow cancellation if order is pending
        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', 'Cette commande ne peut pas être annulée.');
        }

        // Update order status
        $order->update([
            'status' => 'cancelled'
        ]);

        // Restore product stock
        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('stock', $item->quantity);
            }
        }

        return redirect()->route('account.orders')->with('success', 'Commande annulée avec succès. Le stock des produits a été restauré.');
    }
}
