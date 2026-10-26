<?php

namespace App\Services;

use App\Models\Review;

/**
 * Phân tích cảm xúc đánh giá (tiếng Việt) dựa trên từ điển + xử lý phủ định/nhấn mạnh,
 * kết hợp số sao. Có thêm phân tích theo khía cạnh (Aspect-based): nội dung, chất lượng in, giao hàng, giá, dịch thuật.
 */
class SentimentService
{
    const POSITIVE = [
        'hay' => 2, 'rat hay' => 3, 'tuyet voi' => 3, 'xuat sac' => 3, 'dep' => 2, 'tot' => 2, 'thich' => 2,
        'yeu thich' => 2, 'hai long' => 2, 'cam dong' => 2, 'y nghia' => 2, 'bo ich' => 2, 'huu ich' => 2,
        'nhanh' => 1, 'can than' => 1, 'chac chan' => 1, 'ky' => 1, 'net' => 1, 'ro' => 1, 're' => 1,
        'hop ly' => 1, 'dang mua' => 3, 'dang doc' => 3, 'nen doc' => 2, 'recommend' => 2, 'ok' => 1, 'cuon hut' => 2,
        'on' => 1, 'chat luong' => 1, 'moi tinh' => 1, 'thu vi' => 2, 'hap dan' => 2, 'gay can' => 2,
        'chuan' => 1, 'xin' => 2, 'dinh' => 2, 'tuyet' => 3, 'perfect' => 3, 'good' => 2, 'ung ho' => 2, 'cam on' => 1,
    ];

    const NEGATIVE = [
        'te' => 2, 'do te' => 3, 'chan' => 2, 'nham chan' => 3, 'that vong' => 3, 'kem' => 2, 'xau' => 2, 'rach' => 3, 'mop' => 2,
        'bi nhau' => 2, 'cham' => 2, 'giao tre' => 2, 'bi loi' => 2, 'loi in' => 2, 'sai' => 2, 'thieu' => 2, 'mo' => 1, 'nhoe' => 2,
        'hoi dat' => 1, 'qua dat' => 2, 'phi tien' => 3, 'khong dang' => 3, 'giao nham' => 2, 'buon ngu' => 2, 'bi hong' => 2,
        'bong troc' => 2, 'am moc' => 2, 'kho chiu' => 2, 'te hai' => 3, 'bad' => 2, 'lau' => 1,
        'mat trang' => 3, 'in lem' => 3, 'dich te' => 3, 'kho doc' => 2, 'boring' => 2,
        'giay mong' => 2, 'mong' => 1, 'so sai' => 2, 'in mo' => 2, 'khong dep' => 2,
    ];

    const NEGATIONS = ['khong', 'chang', 'chua', 'ko', 'k', 'khong he'];
    const INTENSIFIERS = ['rat' => 1.5, 'qua' => 1.3, 'cuc' => 1.7, 'cuc ky' => 1.8, 'sieu' => 1.6, 'vo cung' => 1.8, 'hoi' => 0.6];

    const ASPECTS = [
        'content'  => ['label' => 'Nội dung', 'words' => ['noi dung', 'cot truyen', 'nhan vat', 'tinh tiet', 'cau chuyen', 'ket thuc', 've', 'net ve', 'tac gia', 'van', 'y nghia']],
        'quality'  => ['label' => 'Chất lượng in/giấy', 'words' => ['giay', 'in', 'bia', 'chu', 'mau', 'trang', 'gay', 'ban in', 'chat luong']],
        'delivery' => ['label' => 'Giao hàng/đóng gói', 'words' => ['giao', 'ship', 'dong goi', 'van chuyen', 'nhan hang', 'hop', 'shipper']],
        'price'    => ['label' => 'Giá cả', 'words' => ['gia', 'tien', 're', 'dat', 'khuyen mai', 'sale']],
        'translate'=> ['label' => 'Dịch thuật', 'words' => ['dich', 'ban dich', 'nguoi dich']],
    ];

    /**
     * Chấm điểm 1 đoạn văn. Trả về số thực ~[-1, 1].
     */
    public function scoreText($text)
    {
        $words = explode(' ', TextAnalyzer::normalize($text));
        $n = count($words);
        $score = 0.0;
        $hits = 0;
        for ($i = 0; $i < $n; $i++) {
            foreach ([2, 1] as $len) {        // ưu tiên cụm 2 từ
                if ($i + $len > $n) continue;
                $phrase = implode(' ', array_slice($words, $i, $len));
                $val = 0;
                if (isset(self::POSITIVE[$phrase])) $val = self::POSITIVE[$phrase];
                elseif (isset(self::NEGATIVE[$phrase])) $val = -self::NEGATIVE[$phrase];
                if ($val == 0) continue;

                // nhìn 1-2 từ phía trước để bắt phủ định / nhấn mạnh
                $prev1 = $words[$i - 1] ?? '';
                $prev2 = $i >= 2 ? $words[$i - 2] . ' ' . $words[$i - 1] : '';
                $prevWin = [$prev1, $prev2, $words[$i - 2] ?? ''];
                foreach (self::INTENSIFIERS as $w => $mul) {
                    if (in_array($w, $prevWin)) { $val *= $mul; break; }
                }
                foreach (self::NEGATIONS as $neg) {
                    if (in_array($neg, $prevWin)) { $val = -0.8 * $val; break; }
                }
                $score += $val;
                $hits++;
                $i += $len - 1;
                break;
            }
        }
        if (!$hits) return 0.0;
        return max(-1, min(1, $score / (2.0 * sqrt($hits) + 1)));
    }

    /** Phân tích khía cạnh: chia câu/mệnh đề, câu nhắc tới khía cạnh nào thì tính điểm cho khía cạnh đó */
    public function aspects($text)
    {
        $clauses = [];
        foreach (preg_split('/[\.\!\?,;\n]+/u', (string) $text) ?: [] as $piece) {
            // tách tiếp theo từ nối tương phản: "nội dung hay nhưng giấy mỏng"
            foreach (preg_split('/\b(?:nhung|tuy nhien|ma)\b/', TextAnalyzer::normalize($piece)) ?: [] as $c) {
                $clauses[] = $c;
            }
        }

        $result = [];
        foreach (self::ASPECTS as $key => $def) {
            $vals = [];
            foreach ($clauses as $c) {
                $c = ' ' . trim($c) . ' ';
                if (trim($c) === '') continue;
                foreach ($def['words'] as $w) {
                    if (strpos($c, ' ' . $w . ' ') !== false) {
                        $vals[] = $this->scoreText($c);
                        break;
                    }
                }
            }
            $vals = array_filter($vals, function ($v) { return $v != 0; });
            if ($vals) {
                $avg = array_sum($vals) / count($vals);
                $result[$key] = ['label' => $def['label'], 'score' => round($avg, 2),
                                 'sentiment' => $avg > 0.05 ? 'positive' : ($avg < -0.05 ? 'negative' : 'neutral')];
            }
        }
        return $result;
    }

    /** Phân tích & lưu cho 1 review */
    public function analyze(Review $review)
    {
        $textScore = $this->scoreText($review->comment);
        $ratingScore = ($review->rating - 3) / 2; // 1 sao = -1, 5 sao = 1
        $score = $review->comment ? 0.6 * $textScore + 0.4 * $ratingScore : $ratingScore;

        $review->sentiment_score = round($score, 3);
        $review->sentiment = $score > 0.15 ? 'positive' : ($score < -0.15 ? 'negative' : 'neutral');
        $review->aspects = $this->aspects($review->comment);
        $review->save();
        return $review;
    }

    public function analyzeAll()
    {
        $n = 0;
        foreach (Review::all() as $r) {
            $this->analyze($r);
            $n++;
        }
        return $n;
    }
}
