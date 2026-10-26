<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookVariant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $monthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth();
        $valid = function ($q) { return $q->whereNotIn('status', ['cancelled', 'returned']); };

        $revenueMonth = $valid(Order::where('created_at', '>=', $monthStart))->sum('total');
        $revenueLastMonth = $valid(Order::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd]))->sum('total');

        $kpi = [
            'revenue_today'  => $valid(Order::whereDate('created_at', $today))->sum('total'),
            'revenue_month'  => $revenueMonth,
            'growth'         => $revenueLastMonth ? round(($revenueMonth - $revenueLastMonth) * 100 / $revenueLastMonth, 1) : null,
            'orders_today'   => Order::whereDate('created_at', $today)->count(),
            'orders_pending' => Order::where('status', 'pending')->count(),
            'customers'      => User::where('role', 'customer')->count(),
            'new_customers'  => User::where('role', 'customer')->where('created_at', '>=', $monthStart)->count(),
            'books'          => Book::count(),
            'low_stock'      => BookVariant::where('stock', '<=', 5)->count(),
            'returns'        => ReturnRequest::where('status', 'pending')->count(),
            'anomalies'      => Order::where('fraud_score', '>=', 50)->whereIn('status', ['pending', 'confirmed'])->count(),
            'negative_reviews' => Review::where('sentiment', 'negative')->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        // Doanh thu 30 ngày
        $daily = $valid(Order::where('created_at', '>=', now()->subDays(29)->startOfDay()))
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('d')->get()->keyBy('d');
        $chart = ['labels' => [], 'revenue' => [], 'orders' => []];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $chart['labels'][] = now()->subDays($i)->format('d/m');
            $chart['revenue'][] = (int) optional($daily->get($d))->revenue;
            $chart['orders'][] = (int) optional($daily->get($d))->orders;
        }

        $statusCounts = Order::select('status', DB::raw('COUNT(*) as c'))->groupBy('status')->pluck('c', 'status');

        $topBooks = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->select('order_items.book_title', DB::raw('SUM(order_items.quantity) as qty'), DB::raw('SUM(order_items.total) as revenue'))
            ->groupBy('order_items.book_title')->orderByDesc('qty')->take(8)->get();

        return view('admin.dashboard', [
            'kpi'          => $kpi,
            'chart'        => $chart,
            'statusCounts' => $statusCounts,
            'topBooks'     => $topBooks,
            'recentOrders' => Order::with('user')->latest()->take(8)->get(),
            'lowStock'     => BookVariant::with('book')->where('stock', '<=', 5)->orderBy('stock')->take(8)->get(),
        ]);
    }
}
