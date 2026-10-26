<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;

/**
 * Dự báo doanh thu & dự đoán nhu cầu sản phẩm.
 * Mô hình: hồi quy tuyến tính (bình phương tối thiểu) theo thời gian, kết hợp trung bình trượt (moving average)
 * để giảm nhiễu: dự báo = 0.6 * xu hướng tuyến tính + 0.4 * trung bình 3 kỳ gần nhất.
 */
class ForecastService
{
    /** Hồi quy tuyến tính y = a + b*x. Trả về [a, b, r2] */
    public function linearRegression(array $ys)
    {
        $n = count($ys);
        if ($n < 2) return [$n ? $ys[0] : 0, 0, 0];
        $xs = range(0, $n - 1);
        $mx = array_sum($xs) / $n;
        $my = array_sum($ys) / $n;
        $sxy = 0; $sxx = 0; $syy = 0;
        for ($i = 0; $i < $n; $i++) {
            $sxy += ($xs[$i] - $mx) * ($ys[$i] - $my);
            $sxx += ($xs[$i] - $mx) ** 2;
            $syy += ($ys[$i] - $my) ** 2;
        }
        $b = $sxx ? $sxy / $sxx : 0;
        $a = $my - $b * $mx;
        $r2 = ($sxx && $syy) ? ($sxy * $sxy) / ($sxx * $syy) : 0;
        return [$a, $b, $r2];
    }

    public function predict(array $history, $periods)
    {
        list($a, $b, $r2) = $this->linearRegression($history);
        $n = count($history);
        $recent = array_slice($history, -3);
        $ma = $recent ? array_sum($recent) / count($recent) : 0;
        $out = [];
        for ($i = 0; $i < $periods; $i++) {
            $trend = $a + $b * ($n + $i);
            $out[] = max(0, round(0.6 * $trend + 0.4 * $ma));
        }
        return ['values' => $out, 'slope' => $b, 'r2' => round($r2, 3)];
    }

    /** Doanh thu 12 tháng gần nhất + dự báo 3 tháng tới */
    public function revenue($monthsBack = 12, $ahead = 3)
    {
        $labels = []; $values = [];
        $start = now()->startOfMonth()->subMonths($monthsBack - 1);
        $rows = Order::where('status', 'delivered')->where('created_at', '>=', $start)
            ->get(['total', 'created_at'])
            ->groupBy(function ($o) { return $o->created_at->format('Y-m'); });
        for ($i = 0; $i < $monthsBack; $i++) {
            $m = $start->copy()->addMonths($i);
            $labels[] = $m->format('m/Y');
            $values[] = (int) optional($rows->get($m->format('Y-m')))->sum('total');
        }
        $pred = $this->predict($values, $ahead);
        $futureLabels = [];
        for ($i = 1; $i <= $ahead; $i++) {
            $futureLabels[] = now()->startOfMonth()->addMonths($i)->format('m/Y');
        }
        return ['labels' => $labels, 'values' => $values, 'future_labels' => $futureLabels,
                'forecast' => $pred['values'], 'slope' => $pred['slope'], 'r2' => $pred['r2']];
    }

    /**
     * Dự đoán nhu cầu từng đầu sách 4 tuần tới dựa trên số lượng bán 8 tuần gần nhất,
     * so với tồn kho để đề xuất nhập thêm.
     */
    public function productDemand($weeks = 8, $ahead = 4)
    {
        $start = now()->startOfWeek()->subWeeks($weeks - 1);
        $items = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', ['cancelled'])
            ->where('orders.created_at', '>=', $start)
            ->whereNotNull('order_items.book_id')
            ->get(['order_items.book_id', 'order_items.quantity', 'orders.created_at as ordered_at']);

        $series = [];
        foreach ($items as $it) {
            $w = (int) floor(Carbon::parse($it->ordered_at)->diffInDays($start) / 7);
            $w = min($weeks - 1, max(0, $w));
            if (!isset($series[$it->book_id])) $series[$it->book_id] = array_fill(0, $weeks, 0);
            $series[$it->book_id][$w] += $it->quantity;
        }

        $books = Book::with('variants')->whereIn('id', array_keys($series))->get()->keyBy('id');
        $rows = [];
        foreach ($series as $bookId => $hist) {
            $book = $books->get($bookId);
            if (!$book) continue;
            $pred = $this->predict($hist, $ahead);
            $demand = array_sum($pred['values']);
            $stock = $book->variants->sum('stock');
            $weekly = $demand / $ahead;
            $rows[] = [
                'book'        => $book,
                'history'     => $hist,
                'sold'        => array_sum($hist),
                'forecast'    => $demand,
                'trend'       => $pred['slope'] > 0.05 ? 'up' : ($pred['slope'] < -0.05 ? 'down' : 'flat'),
                'stock'       => $stock,
                'weeks_cover' => $weekly > 0 ? round($stock / $weekly, 1) : null,
                'suggest'     => max(0, (int) ceil($demand * 1.2 - $stock)), // dư 20% an toàn
            ];
        }
        usort($rows, function ($a, $b) { return $b['forecast'] <=> $a['forecast']; });
        return $rows;
    }
}
