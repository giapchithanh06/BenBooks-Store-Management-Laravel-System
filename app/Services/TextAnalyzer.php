<?php

namespace App\Services;

/**
 * Xử lý văn bản tiếng Việt: chuẩn hóa, bỏ dấu, tách từ (unigram + bigram), bỏ stopword, mở rộng đồng nghĩa.
 * Dùng chung cho Semantic Search, Chatbot/RAG và phân tích cảm xúc.
 */
class TextAnalyzer
{
    const STOPWORDS = [
        'la', 'va', 'cua', 'co', 'cho', 'cac', 'nhung', 'mot', 'nhu', 'voi', 'trong', 'khi', 'thi', 'ma', 'de',
        'duoc', 'nay', 'do', 'den', 'tu', 'tai', 've', 'o', 'nhieu', 'lai', 'ra', 'vao', 'len', 'cung', 'roi',
        'toi', 'minh', 'ban', 'em', 'anh', 'chi', 'shop', 'ad', 'oi', 'a', 'nhe', 'nha', 'ha', 'vay', 'the', 'nao',
        'gi', 'can', 'muon', 'tim', 'kiem', 'giup', 'hay', 'goi', 'y', 'xin', 'cuon', 'quyen', 'loai', 'nhat',
        'khong', 'it', 'qua', 'rat', 'bi', 'se', 'da', 'dang', 'con', 'nua', 'hon', 'ko', 'k', 'sach', 'truyen',
    ];

    /** Từ đồng nghĩa / khái niệm liên quan (đã bỏ dấu) – giúp tìm kiếm "hiểu nghĩa" hơn khớp chữ */
    const SYNONYMS = [
        'manga'        => ['truyen tranh', 'nhat ban', 'shounen'],
        'comic'        => ['truyen tranh', 'my', 'sieu anh hung'],
        'manhwa'       => ['truyen tranh', 'han quoc'],
        'manhua'       => ['truyen tranh', 'trung quoc'],
        'trinh tham'   => ['pha an', 'tham tu', 'bi an'],
        'tham tu'      => ['trinh tham', 'pha an'],
        'kinh di'      => ['ma', 'quy', 'dark fantasy'],
        'hanh dong'    => ['chien dau', 'vo thuat', 'phieu luu'],
        'danh nhau'    => ['hanh dong', 'vo thuat'],
        'lam giau'     => ['tai chinh', 'tien bac', 'dau tu', 'kinh doanh'],
        'tien'         => ['tai chinh', 'tien bac', 'lam giau'],
        'dau tu'       => ['tai chinh', 'lam giau'],
        'kinh doanh'   => ['quan tri', 'doanh nghiep', 'khoi nghiep'],
        'khoi nghiep'  => ['kinh doanh', 'thanh cong'],
        'giao tiep'    => ['ung xu', 'ky nang mem'],
        'dong luc'     => ['truyen cam hung', 'phat trien ban than'],
        'self help'    => ['phat trien ban than', 'ky nang'],
        'thoi quen'    => ['phat trien ban than', 'ky luat'],
        'tinh yeu'     => ['lang man', 'tinh cam'],
        'lang man'     => ['tinh yeu'],
        'buon'         => ['cam dong', 'hoai niem'],
        'cam dong'     => ['tinh than', 'tuoi tho'],
        'tre em'       => ['thieu nhi', 'tuoi tho'],
        'con nit'      => ['thieu nhi'],
        'be'           => ['thieu nhi'],
        'thieu nhi'    => ['tre em', 'tuoi tho'],
        'vu tru'       => ['thien van', 'vat ly', 'khoa hoc'],
        'khoa hoc'     => ['vu tru', 'kham pha'],
        'lich su'      => ['su hoc', 'dan toc'],
        'hai'          => ['hai huoc', 'vui nhon'],
        'hai huoc'     => ['vui nhon', 'dí dỏm'],
        'vui'          => ['hai huoc', 'vui nhon'],
        'game'         => ['esports', 'the thao dien tu', 'thang cap'],
        'tu tien'      => ['huyen huyen', 'tu luyen'],
        'kiem hiep'    => ['vo thuat', 'tu tien'],
        'nhat'         => ['nhat ban', 'manga'],
        'han'          => ['han quoc', 'manhwa'],
        'trung'        => ['trung quoc', 'manhua'],
        'viet nam'     => ['truyen tranh viet', 'dan gian'],
    ];

    /** Chuẩn hóa: chữ thường, bỏ dấu, bỏ ký tự đặc biệt */
    public static function normalize($text)
    {
        $text = mb_strtolower((string) $text, 'UTF-8');
        $text = vn_unaccent($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /** Tách từ: unigram (bỏ stopword) + bigram (từ ghép tiếng Việt như "truyen tranh", "tai chinh") */
    public static function tokenize($text, $withBigrams = true)
    {
        $words = $text === '' ? [] : explode(' ', static::normalize($text));
        $words = array_values(array_filter($words, function ($w) { return $w !== ''; }));
        $tokens = [];
        foreach ($words as $w) {
            if (!in_array($w, self::STOPWORDS) && (strlen($w) > 1 || ctype_digit($w))) {
                $tokens[] = $w;
            }
        }
        if ($withBigrams) {
            for ($i = 0; $i < count($words) - 1; $i++) {
                $tokens[] = $words[$i] . '_' . $words[$i + 1];
            }
        }
        return $tokens;
    }

    /** Mở rộng truy vấn bằng từ đồng nghĩa (trọng số thấp hơn từ gốc) */
    public static function expand($text)
    {
        $norm = ' ' . static::normalize($text) . ' ';
        $extra = [];
        foreach (self::SYNONYMS as $key => $list) {
            if (strpos($norm, ' ' . $key . ' ') !== false) {
                foreach ($list as $s) {
                    $extra[] = $s;
                }
            }
        }
        return $extra;
    }

    /** Đếm tần suất token */
    public static function termFrequency(array $tokens)
    {
        $tf = [];
        foreach ($tokens as $t) {
            $tf[$t] = ($tf[$t] ?? 0) + 1;
        }
        return $tf;
    }

    /** Cosine similarity giữa 2 vector thưa dạng [term => weight] */
    public static function cosine(array $a, array $b, $normA = null, $normB = null)
    {
        if (!$a || !$b) {
            return 0.0;
        }
        if (count($a) > count($b)) {
            $tmp = $a; $a = $b; $b = $tmp;
            $tmpN = $normA; $normA = $normB; $normB = $tmpN;
        }
        $dot = 0.0;
        foreach ($a as $t => $w) {
            if (isset($b[$t])) {
                $dot += $w * $b[$t];
            }
        }
        $normA = $normA ?: static::norm($a);
        $normB = $normB ?: static::norm($b);
        return ($normA && $normB) ? $dot / ($normA * $normB) : 0.0;
    }

    public static function norm(array $v)
    {
        $s = 0.0;
        foreach ($v as $w) {
            $s += $w * $w;
        }
        return sqrt($s);
    }

    /** Tách câu (cho RAG) */
    public static function sentences($text)
    {
        $parts = preg_split('/(?<=[\.\!\?;])\s+|\n+/u', (string) $text);
        return array_values(array_filter(array_map('trim', $parts), function ($s) { return mb_strlen($s) > 3; }));
    }
}
