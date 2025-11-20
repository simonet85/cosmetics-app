<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\OrderStatusUpdated;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Search by order number or customer email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product.primaryImage']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'tracking_number' => 'nullable|string|max:255',
            'shipping_carrier' => 'nullable|string|max:255',
            'tracking_url' => 'nullable|url|max:500',
        ]);

        $oldStatus = $order->status;

        // Prepare update data
        $updateData = ['status' => $request->status];

        // Add tracking information if provided
        if ($request->filled('tracking_number')) {
            $updateData['tracking_number'] = $request->tracking_number;
        }
        if ($request->filled('shipping_carrier')) {
            $updateData['shipping_carrier'] = $request->shipping_carrier;
        }
        if ($request->filled('tracking_url')) {
            $updateData['tracking_url'] = $request->tracking_url;
        }

        $order->update($updateData);

        // If order is cancelled, restore stock
        if ($request->status === 'cancelled' && $oldStatus !== 'cancelled') {
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }
        }

        // If order was cancelled but now is not, reduce stock again
        if ($oldStatus === 'cancelled' && $request->status !== 'cancelled') {
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->decrement('stock', $item->quantity);
                }
            }
        }

        // Send email notification if status changed
        if ($oldStatus !== $request->status) {
            try {
                Mail::to($order->customer_email)->send(new OrderStatusUpdated($order, $oldStatus));
                Log::info('Order status update email sent', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                    'email' => $order->customer_email
                ]);
            } catch (\Exception $emailError) {
                Log::error('Failed to send order status update email', [
                    'order_id' => $order->id,
                    'error' => $emailError->getMessage()
                ]);
                // Don't fail the status update if email fails
            }
        }

        return back()->with('success', 'Statut de la commande mis à jour avec succès.');
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        $order->update(['payment_status' => $request->payment_status]);

        return back()->with('success', 'Statut de paiement mis à jour avec succès.');
    }
}
