<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookVariant;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Báo cáo bán hàng / doanh thu / tồn kho + xuất CSV (mở được bằng Excel) */
class ReportController extends Controller
{
    protected function range(Request $request)
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDays(29)->startOfDay();
        $to = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();
        return [$from, $to];
    }

    protected function build(Request $request)
    {
        list($from, $to) = $this->range($request);
        $orders = Order::whereBetween('created_at', [$from, $to]);
        $valid = (clone $orders)->whereNotIn('status', ['cancelled', 'returned']);

        $summary = [
            'orders'     => (clone $orders)->count(),
            'delivered'  => (clone $orders)->where('status', 'delivered')->count(),
            'cancelled'  => (clone $orders)->where('status', 'cancelled')->count(),
            'gross'      => (clone $valid)->sum('subtotal'),
            'discount'   => (clone $valid)->sum('discount'),
            'shipping'   => (clone $valid)->sum('shipping_fee'),
            'net'        => (clone $valid)->sum('total'),
            'items'      => OrderItem::whereHas('order', function ($q) use ($from, $to) {
                                $q->whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled', 'returned']);
                            })->sum('quantity'),
        ];
        $summary['aov'] = $summary['orders'] - $summary['cancelled'] > 0 ? round($summary['net'] / max(1, $summary['orders'] - $summary['cancelled'])) : 0;

        $byDay = (clone $valid)->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total) as revenue'))
            ->groupBy('d')->orderBy('d')->get();

        $base = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$from, $to])->whereNotIn('orders.status', ['cancelled', 'returned']);

        $byBook = (clone $base)->select('order_items.book_title', DB::raw('SUM(order_items.quantity) as qty'), DB::raw('SUM(order_items.total) as revenue'))
            ->groupBy('order_items.book_title')->orderByDesc('revenue')->take(20)->get();

        $byCategory = (clone $base)->join('books', 'books.id', '=', 'order_items.book_id')
            ->join('categories', 'categories.id', '=', 'books.category_id')
            ->select('categories.name', DB::raw('SUM(order_items.quantity) as qty'), DB::raw('SUM(order_items.total) as revenue'))
            ->groupBy('categories.name')->orderByDesc('revenue')->get();

        $byPayment = (clone $valid)->select('payment_method', DB::raw('COUNT(*) as c'), DB::raw('SUM(total) as revenue'))
            ->groupBy('payment_method')->get();

        $inventory = BookVariant::with('book')->whereHas('book')->orderBy('stock')->get();

        return compact('from', 'to', 'summary', 'byDay', 'byBook', 'byCategory', 'byPayment', 'inventory');
    }

    public function index(Request $request)
    {
        return view('admin.reports.index', $this->build($request));
    }

    public function export(Request $request)
    {
        $d = $this->build($request);
        $type = $request->input('type', 'books');
        $filename = 'bao-cao-' . $type . '-' . $d['from']->format('Ymd') . '-' . $d['to']->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($d, $type) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM để Excel đọc đúng tiếng Việt
            if ($type === 'days') {
                fputcsv($out, ['Ngày', 'Số đơn', 'Doanh thu']);
                foreach ($d['byDay'] as $r) fputcsv($out, [$r->d, $r->orders, $r->revenue]);
            } elseif ($type === 'categories') {
                fputcsv($out, ['Thể loại', 'Số lượng', 'Doanh thu']);
                foreach ($d['byCategory'] as $r) fputcsv($out, [$r->name, $r->qty, $r->revenue]);
            } elseif ($type === 'inventory') {
                fputcsv($out, ['SKU', 'Sách', 'Phiên bản', 'Giá', 'Tồn kho', 'Giá trị tồn']);
                foreach ($d['inventory'] as $v) fputcsv($out, [$v->sku, $v->book->title, $v->name, $v->price, $v->stock, $v->price * $v->stock]);
            } else {
                fputcsv($out, ['Sách', 'Số lượng bán', 'Doanh thu']);
                foreach ($d['byBook'] as $r) fputcsv($out, [$r->book_title, $r->qty, $r->revenue]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
