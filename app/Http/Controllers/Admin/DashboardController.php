<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistics
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $completedOrders = Order::where('status', 'completed')->count();

        $totalRevenue = Order::where('payment_status', 'paid')->sum('total');
        $monthlyRevenue = Order::where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $lowStockProducts = Product::where('stock', '<=', 10)->count();

        $totalCustomers = User::role('customer')->count();
        $newCustomersThisMonth = User::role('customer')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalReviews = Review::count();
        $pendingReviews = Review::where('is_approved', false)->count();

        // Recent orders
        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get();

        // Top selling products
        $topProducts = Product::with('images')
            ->withCount(['orderItems as total_sales' => function($query) {
                $query->select(DB::raw('SUM(quantity)'));
            }])
            ->orderBy('total_sales', 'desc')
            ->take(5)
            ->get();

        // Monthly revenue chart data (last 6 months)
        $monthlyRevenueData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $revenue = Order::where('payment_status', 'paid')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('total');

            $monthlyRevenueData[] = [
                'month' => $date->format('M Y'),
                'revenue' => $revenue
            ];
        }

        return view('admin.dashboard', compact(
            'totalOrders',
            'pendingOrders',
            'completedOrders',
            'totalRevenue',
            'monthlyRevenue',
            'totalProducts',
            'activeProducts',
            'lowStockProducts',
            'totalCustomers',
            'newCustomersThisMonth',
            'totalReviews',
            'pendingReviews',
            'recentOrders',
            'topProducts',
            'monthlyRevenueData'
        ));
    }
}
