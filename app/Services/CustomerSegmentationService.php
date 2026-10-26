<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;

/**
 * Customer Segmentation / Phân cụm khách hàng.
 * Đặc trưng RFM (Recency – số ngày từ lần mua cuối, Frequency – số đơn, Monetary – tổng chi tiêu),
 * chuẩn hóa min-max rồi phân cụm bằng thuật toán K-Means (k = 4, khởi tạo k-means++ tất định).
 */
class CustomerSegmentationService
{
    const K = 4;
    const NO_ORDER = 'Chưa mua hàng';
    const SEGMENTS = [
        'VIP'        => ['label' => 'Khách hàng VIP', 'color' => 'danger', 'action' => 'Ưu đãi riêng, voucher giá trị cao, ưu tiên sách mới'],
        'LOYAL'      => ['label' => 'Khách hàng thân thiết', 'color' => 'primary', 'action' => 'Tích điểm, gợi ý bộ sách/series đang theo dõi'],
        'NEW'        => ['label' => 'Khách hàng mới / tiềm năng', 'color' => 'success', 'action' => 'Gửi voucher đơn thứ 2, gợi ý sách cùng thể loại'],
        'AT_RISK'    => ['label' => 'Có nguy cơ rời bỏ', 'color' => 'warning', 'action' => 'Email nhắc nhở, flash sale theo sở thích cũ'],
    ];

    /** Lấy dữ liệu RFM của mọi khách hàng đã có đơn hợp lệ */
    public function rfm()
    {
        $rows = Order::query()
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->selectRaw('user_id, MAX(created_at) as last_order, COUNT(*) as frequency, SUM(total) as monetary')
            ->groupBy('user_id')->get();
        $data = [];
        foreach ($rows as $r) {
            $data[$r->user_id] = [
                'recency'   => max(0, now()->diffInDays(\Carbon\Carbon::parse($r->last_order))),
                'frequency' => (int) $r->frequency,
                'monetary'  => (int) $r->monetary,
            ];
        }
        return $data;
    }

    protected function normalize(array $data)
    {
        $keys = ['recency', 'frequency', 'monetary'];
        $min = []; $max = [];
        foreach ($keys as $k) {
            $col = array_column($data, $k);
            $min[$k] = $col ? min($col) : 0;
            $max[$k] = $col ? max($col) : 1;
        }
        $out = [];
        foreach ($data as $id => $row) {
            $vec = [];
            foreach ($keys as $k) {
                $range = $max[$k] - $min[$k];
                $v = $range ? ($row[$k] - $min[$k]) / $range : 0;
                // recency: càng nhỏ càng tốt -> đảo chiều để "lớn = tốt"
                $vec[] = $k === 'recency' ? 1 - $v : $v;
            }
            $out[$id] = $vec;
        }
        return $out;
    }

    protected function dist(array $a, array $b)
    {
        $s = 0;
        foreach ($a as $i => $v) {
            $s += ($v - $b[$i]) * ($v - $b[$i]);
        }
        return $s;
    }

    /** K-Means. Trả về [assignments => [id => cluster], centroids => [...]] */
    public function kmeans(array $points, $k)
    {
        $ids = array_keys($points);
        $k = min($k, count($ids));
        if ($k === 0) return ['assignments' => [], 'centroids' => []];

        // k-means++ tất định: điểm đầu = khách có tổng điểm lớn nhất, các điểm sau = xa nhất
        usort($ids, function ($a, $b) use ($points) { return array_sum($points[$b]) <=> array_sum($points[$a]); });
        $centroids = [$points[$ids[0]]];
        while (count($centroids) < $k) {
            $best = null; $bestD = -1;
            foreach ($ids as $id) {
                $d = min(array_map(function ($c) use ($points, $id) { return $this->dist($points[$id], $c); }, $centroids));
                if ($d > $bestD) { $bestD = $d; $best = $id; }
            }
            $centroids[] = $points[$best];
        }

        $assign = [];
        for ($iter = 0; $iter < 50; $iter++) {
            $changed = false;
            foreach ($ids as $id) {
                $bestC = 0; $bestD = INF;
                foreach ($centroids as $ci => $c) {
                    $d = $this->dist($points[$id], $c);
                    if ($d < $bestD) { $bestD = $d; $bestC = $ci; }
                }
                if (!isset($assign[$id]) || $assign[$id] !== $bestC) { $assign[$id] = $bestC; $changed = true; }
            }
            foreach ($centroids as $ci => $c) {
                $members = array_keys($assign, $ci, true);
                if (!$members) continue;
                $new = array_fill(0, count($c), 0);
                foreach ($members as $id) {
                    foreach ($points[$id] as $d => $v) $new[$d] += $v;
                }
                $centroids[$ci] = array_map(function ($v) use ($members) { return $v / count($members); }, $new);
            }
            if (!$changed) break;
        }
        return ['assignments' => $assign, 'centroids' => $centroids];
    }

    /** Gán nhãn cụm theo tâm cụm: [recency_score, frequency, monetary] (đã chuẩn hóa, lớn = tốt) */
    protected function labelClusters(array $centroids)
    {
        $labels = [];
        $remaining = array_keys($centroids);
        $pick = function ($fn) use (&$remaining, $centroids) {
            usort($remaining, function ($a, $b) use ($fn, $centroids) { return $fn($centroids[$b]) <=> $fn($centroids[$a]); });
            return array_shift($remaining);
        };
        if ($remaining) $labels[$pick(function ($c) { return $c[1] + 1.5 * $c[2]; })] = 'VIP';
        if ($remaining) $labels[$pick(function ($c) { return -$c[0]; })] = 'AT_RISK';       // lâu không mua nhất
        if ($remaining) $labels[$pick(function ($c) { return $c[1] + $c[0]; })] = 'LOYAL';
        foreach ($remaining as $ci) $labels[$ci] = 'NEW';
        return $labels;
    }

    /** Chạy phân cụm và lưu nhãn vào users.segment. Trả về thống kê từng cụm. */
    public function run()
    {
        $rfm = $this->rfm();
        $result = $this->kmeans($this->normalize($rfm), self::K);
        $labels = $this->labelClusters($result['centroids']);

        User::where('role', 'customer')->update(['segment' => self::NO_ORDER]);
        $stats = [];
        foreach ($result['assignments'] as $userId => $ci) {
            $key = $labels[$ci];
            User::where('id', $userId)->where('role', 'customer')->update(['segment' => self::SEGMENTS[$key]['label']]);
            if (!isset($stats[$key])) {
                $stats[$key] = ['count' => 0, 'recency' => 0, 'frequency' => 0, 'monetary' => 0];
            }
            $stats[$key]['count']++;
            foreach (['recency', 'frequency', 'monetary'] as $f) $stats[$key][$f] += $rfm[$userId][$f];
        }
        foreach ($stats as $key => &$s) {
            foreach (['recency', 'frequency', 'monetary'] as $f) $s[$f] = round($s[$f] / max(1, $s['count']), 1);
            $s = array_merge($s, self::SEGMENTS[$key]);
        }
        unset($s);
        return ['stats' => $stats, 'rfm' => $rfm];
    }
}
