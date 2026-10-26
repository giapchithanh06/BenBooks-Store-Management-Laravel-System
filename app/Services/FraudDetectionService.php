<?php

namespace App\Services;

use App\Models\Order;
use App\Models\UserNotification;

/**
 * Fraud / Anomaly Detection cho đơn hàng.
 * Kết hợp kiểm định thống kê (z-score giá trị đơn) với bộ luật nghiệp vụ, cho điểm rủi ro 0–100.
 */
class FraudDetectionService
{
    const THRESHOLD = 50;

    /** Trung bình & độ lệch chuẩn giá trị đơn (bỏ qua đơn đang xét) */
    protected function stats($excludeId = null)
    {
        $totals = Order::where('status', '!=', 'cancelled')
            ->when($excludeId, function ($q) use ($excludeId) { $q->where('id', '!=', $excludeId); })
            ->pluck('total')->all();
        $n = count($totals);
        if ($n < 5) return [null, null];
        $mean = array_sum($totals) / $n;
        $var = 0;
        foreach ($totals as $t) $var += ($t - $mean) ** 2;
        return [$mean, sqrt($var / $n)];
    }

    /** Tính điểm & lý do cho 1 đơn. Trả về [score, flags[]] */
    public function evaluate(Order $order)
    {
        $score = 0;
        $flags = [];
        $user = $order->user;

        list($mean, $std) = $this->stats($order->id);
        if ($std) {
            $z = ($order->total - $mean) / $std;
            if ($z > 3) { $score += 35; $flags[] = 'Giá trị đơn cao bất thường (z = ' . round($z, 1) . ')'; }
            elseif ($z > 2) { $score += 20; $flags[] = 'Giá trị đơn cao (z = ' . round($z, 1) . ')'; }
        }

        if ($user && $user->created_at && $user->created_at->gt(now()->subDay()) && $order->total >= 1000000) {
            $score += 20; $flags[] = 'Tài khoản mới tạo < 24h đặt đơn lớn';
        }

        if ($user) {
            $recent = Order::where('user_id', $user->id)->where('id', '!=', $order->id)
                ->where('created_at', '>=', $order->created_at->copy()->subHour())->count();
            if ($recent >= 3) { $score += 25; $flags[] = "Đặt {$recent} đơn khác trong 1 giờ"; }

            $all = Order::where('user_id', $user->id)->count();
            $cancelled = Order::where('user_id', $user->id)->where('status', 'cancelled')->count();
            if ($all >= 3 && $cancelled / $all >= 0.5) {
                $score += 20; $flags[] = 'Tỉ lệ hủy đơn cao (' . round($cancelled * 100 / $all) . '%)';
            }

            $otherAccounts = Order::where('phone', $order->phone)->where('user_id', '!=', $user->id)
                ->distinct()->count('user_id');
            if ($otherAccounts >= 2) { $score += 20; $flags[] = "SĐT nhận hàng dùng chung {$otherAccounts} tài khoản khác"; }
        }

        $maxQty = $order->items->max('quantity');
        if ($maxQty >= 10) { $score += 15; $flags[] = "Mua {$maxQty} cuốn cùng một sản phẩm (nghi mua sỉ/đầu cơ)"; }

        if ($order->payment_method === 'cod' && $order->total >= 3000000) {
            $score += 10; $flags[] = 'Đơn COD giá trị lớn';
        }
        if ($order->discount > 0 && $order->subtotal > 0 && $order->discount / $order->subtotal > 0.4) {
            $score += 10; $flags[] = 'Mức giảm giá > 40%';
        }

        return [min(100, $score), $flags];
    }

    /** Đánh giá & lưu; báo cho admin nếu vượt ngưỡng */
    public function check(Order $order, $notify = true)
    {
        list($score, $flags) = $this->evaluate($order);
        $order->fraud_score = $score;
        $order->fraud_flags = $flags ? mb_substr(implode('; ', $flags), 0, 250) : null;
        $order->save();

        if ($notify && $score >= self::THRESHOLD) {
            UserNotification::sendToBackend(
                'Cảnh báo đơn bất thường ' . $order->code,
                "Điểm rủi ro {$score}/100: " . implode('; ', $flags),
                route('admin.orders.show', $order)
            );
        }
        return $score;
    }

    public function scanAll()
    {
        $n = 0;
        foreach (Order::with(['user', 'items'])->get() as $o) {
            $this->check($o, false);
            $n++;
        }
        return $n;
    }
}
