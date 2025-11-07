<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShoppingCart;
use App\Models\TourRegistration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    public function __invoke()
    {
        $totalRevenue = (float) Order::sum('grand_total');
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $distinctCustomers = Order::distinct('email')->count('email');

        $activeCarts = ShoppingCart::where('items_count', '>', 0)->count();

        $topProducts = OrderItem::query()
            ->select([
                'product_id',
                'product_name',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(line_total) as total_sales'),
            ])
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'total_quantity' => (int) $item->total_quantity,
                    'total_sales' => (float) $item->total_sales,
                ];
            });

        $recentOrders = Order::query()
            ->latest()
            ->limit(6)
            ->get(['order_number', 'customer_name', 'email', 'status', 'payment_status', 'grand_total', 'created_at'])
            ->map(fn ($order) => [
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'email' => $order->email,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'grand_total' => (float) $order->grand_total,
                'created_at' => $order->created_at,
            ]);

        $totalTours = TourRegistration::count();
        $upcomingTours = TourRegistration::whereDate('preferred_date', '>=', Carbon::today())->count();

        return response()->json([
            'cards' => [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'unique_customers' => $distinctCustomers,
                'active_carts' => $activeCarts,
                'total_tour_registrations' => $totalTours,
                'upcoming_tours' => $upcomingTours,
            ],
            'top_products' => $topProducts,
            'recent_orders' => $recentOrders,
        ]);
    }
}
