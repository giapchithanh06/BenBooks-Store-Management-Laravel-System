<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookVector;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * Semantic Search / Tìm kiếm bằng ngôn ngữ tự nhiên.
 *
 * 1. Mỗi sách được biểu diễn thành vector TF-IDF (tiêu đề, tác giả, thể loại, từ khóa, mô tả) và lưu ở bảng
 *    book_vectors (đóng vai trò "vector database" đơn giản).
 * 2. Câu truy vấn tự nhiên được phân tích để lấy điều kiện cứng: khoảng giá, năm XB, thể loại, tác giả, cách sắp xếp.
 * 3. Phần chữ còn lại được mở rộng đồng nghĩa, vector hóa và so khớp cosine với vector sách.
 */
class SemanticSearchService
{
    const CACHE_IDF = 'semantic_idf_v1';

    /* ---------------------------------------------------------------- Index */

    protected function documentTokens(Book $book)
    {
        $parts = [
            str_repeat($book->title . ' ', 3),
            str_repeat($book->authors->pluck('name')->implode(' ') . ' ', 2),
            str_repeat(optional($book->category)->name . ' ', 2),
            optional(optional($book->category)->parent)->name,
            str_repeat((string) $book->tags . ' ', 2),
            $book->description,
            optional($book->publisher)->name,
        ];
        $text = implode(' ', array_map('strval', $parts));
        return TextAnalyzer::tokenize($text);
    }

    /** Xây lại toàn bộ index. Trả về số sách đã index. */
    public function rebuildIndex()
    {
        $books = Book::active()->with(['authors', 'category.parent', 'publisher'])->get();
        $docs = [];
        $df = [];
        foreach ($books as $book) {
            $tf = TextAnalyzer::termFrequency($this->documentTokens($book));
            $docs[$book->id] = $tf;
            foreach (array_keys($tf) as $term) {
                $df[$term] = ($df[$term] ?? 0) + 1;
            }
        }

        $n = max(1, count($docs));
        $idf = [];
        foreach ($df as $term => $count) {
            $idf[$term] = log(($n + 1) / ($count + 1)) + 1;
        }

        BookVector::query()->delete();
        foreach ($docs as $bookId => $tf) {
            $vec = [];
            foreach ($tf as $term => $count) {
                $vec[$term] = round((1 + log($count)) * $idf[$term], 4);
            }
            BookVector::create(['book_id' => $bookId, 'vector' => $vec, 'norm' => TextAnalyzer::norm($vec)]);
        }

        Cache::forever(self::CACHE_IDF, $idf);
        Cache::forget('semantic_vectors');
        return count($docs);
    }

    protected function idf()
    {
        $idf = Cache::get(self::CACHE_IDF);
        if (!$idf || !BookVector::exists()) {
            $this->rebuildIndex();
            $idf = Cache::get(self::CACHE_IDF, []);
        }
        return $idf;
    }

    /** [book_id => ['v' => vector, 'n' => norm]] */
    public function vectors()
    {
        return Cache::remember('semantic_vectors', 600, function () {
            $out = [];
            foreach (BookVector::all() as $bv) {
                $out[$bv->book_id] = ['v' => $bv->vector, 'n' => $bv->norm];
            }
            return $out;
        });
    }

    public function vectorizeQuery($text)
    {
        $idf = $this->idf();
        $vec = [];
        foreach (TextAnalyzer::termFrequency(TextAnalyzer::tokenize($text)) as $term => $c) {
            if (isset($idf[$term])) {
                $vec[$term] = (1 + log($c)) * $idf[$term];
            }
        }
        // từ đồng nghĩa: trọng số 0.5
        foreach (TextAnalyzer::tokenize(implode(' ', TextAnalyzer::expand($text))) as $term) {
            if (isset($idf[$term]) && !isset($vec[$term])) {
                $vec[$term] = 0.5 * $idf[$term];
            }
        }
        return $vec;
    }

    /* ---------------------------------------------------------------- Phân tích câu tự nhiên */

    protected function parseMoney($num, $unit)
    {
        $num = (float) str_replace(',', '.', $num);
        $unit = trim((string) $unit);
        if (in_array($unit, ['tr', 'trieu', 'm'])) return (int) ($num * 1000000);
        if (in_array($unit, ['k', 'nghin', 'ngan', 'n'])) return (int) ($num * 1000);
        return $num < 1000 ? (int) ($num * 1000) : (int) $num; // "dưới 50" hiểu là 50k
    }

    /**
     * Phân tích câu hỏi tự nhiên -> điều kiện.
     * Ví dụ: "truyện tranh hành động của Nhật dưới 50k mới nhất"
     */
    public function parse($query)
    {
        $q = ' ' . TextAnalyzer::normalize($query) . ' ';
        $f = ['min_price' => null, 'max_price' => null, 'year_from' => null, 'year_to' => null,
              'category_ids' => [], 'category_names' => [], 'author_ids' => [], 'author_names' => [], 'sort' => null, 'text' => $q];
        $unit = '\s*(k|nghin|ngan|tr|trieu|d|dong)?\b';

        if (preg_match('/\btu\s+(\d+(?:[\.,]\d+)?)' . $unit . '\s*(?:den|toi|-)\s*(\d+(?:[\.,]\d+)?)' . $unit . '/', $q, $m)) {
            $f['min_price'] = $this->parseMoney($m[1], $m[2] ?? '');
            $f['max_price'] = $this->parseMoney($m[3], $m[4] ?? '');
            $q = str_replace($m[0], ' ', $q);
        }
        if (preg_match('/\b(duoi|re hon|khong qua|toi da|it hon|<)\s*(\d+(?:[\.,]\d+)?)' . $unit . '/', $q, $m)) {
            $f['max_price'] = $this->parseMoney($m[2], $m[3] ?? '');
            $q = str_replace($m[0], ' ', $q);
        }
        if (preg_match('/\b(tren|dat hon|hon|toi thieu|>)\s*(\d+(?:[\.,]\d+)?)' . $unit . '/', $q, $m)) {
            $f['min_price'] = $this->parseMoney($m[2], $m[3] ?? '');
            $q = str_replace($m[0], ' ', $q);
        }
        if (preg_match('/(?:sau|tu)\s*(?:nam\s*)?((?:19|20)\d{2})\b/', $q, $m)) {
            $f['year_from'] = (int) $m[1];
            $q = str_replace($m[0], ' ', $q);
        } elseif (preg_match('/(?:truoc)\s*(?:nam\s*)?((?:19|20)\d{2})\b/', $q, $m)) {
            $f['year_to'] = (int) $m[1];
            $q = str_replace($m[0], ' ', $q);
        } elseif (preg_match('/(?:nam|xuat ban)\s*((?:19|20)\d{2})\b/', $q, $m)) {
            $f['year_from'] = $f['year_to'] = (int) $m[1];
            $q = str_replace($m[0], ' ', $q);
        }

        $sorts = ['re nhat' => 'price_asc', 'gia re' => 'price_asc', 'dat nhat' => 'price_desc', 'ban chay' => 'best',
                  'hot' => 'best', 'pho bien' => 'best', 'moi nhat' => 'newest', 'moi ra' => 'newest',
                  'danh gia cao' => 'rating', 'hay nhat' => 'rating'];
        foreach ($sorts as $k => $v) {
            if (strpos($q, ' ' . $k . ' ') !== false) {
                $f['sort'] = $v;
                $q = str_replace(' ' . $k . ' ', ' ', $q);
                break;
            }
        }

        // Thể loại (khớp tên đã bỏ dấu, ưu tiên tên dài trước)
        $cats = Category::with('children')->get()->sortByDesc(function ($c) { return mb_strlen($c->name); });
        $matched = [];
        foreach ($cats as $cat) {
            $name = TextAnalyzer::normalize($cat->name);
            if ($name && strpos($q, ' ' . $name . ' ') !== false) {
                $matched[$cat->id] = $cat;
                $q = str_replace(' ' . $name . ' ', ' ', $q);
            }
        }
        // "sách thiếu nhi": nếu khớp cả thể loại cha lẫn con thì chỉ giữ thể loại con (cụ thể hơn)
        foreach ($matched as $cat) {
            if ($cat->parent_id && isset($matched[$cat->parent_id])) {
                unset($matched[$cat->parent_id]);
            }
        }
        foreach ($matched as $cat) {
            $f['category_ids'] = array_merge($f['category_ids'], $cat->descendantIds());
            $f['category_names'][] = $cat->name;
        }

        // Tác giả: khớp đủ họ tên, hoặc tên cuối/2 chữ cuối nếu có chữ "cua"/"tac gia"
        foreach (Author::all() as $author) {
            $name = TextAnalyzer::normalize($author->name);
            $words = explode(' ', $name);
            $short = count($words) >= 2 ? implode(' ', array_slice($words, -2)) : $name;
            if (strpos($q, ' ' . $name . ' ') !== false) {
                $f['author_ids'][] = $author->id;
                $f['author_names'][] = $author->name;
                $q = str_replace(' ' . $name . ' ', ' ', $q);
            } elseif (mb_strlen($short) > 5 && preg_match('/(cua|tac gia)\s+' . preg_quote($short, '/') . '\b/', $q)) {
                $f['author_ids'][] = $author->id;
                $f['author_names'][] = $author->name;
                $q = str_replace($short, ' ', $q);
            }
        }

        $f['text'] = trim(preg_replace('/\s+/', ' ', $q));
        return $f;
    }

    /* ---------------------------------------------------------------- Tìm kiếm */

    /**
     * @return array ['filters' => [...], 'results' => Collection<Book> (có thuộc tính score)]
     */
    public function search($query, $limit = 24)
    {
        $filters = $this->parse($query);

        $q = Book::active()->with(['authors', 'category', 'variants']);
        if ($filters['category_ids']) $q->whereIn('category_id', $filters['category_ids']);
        if ($filters['author_ids']) {
            $ids = $filters['author_ids'];
            $q->whereHas('authors', function ($a) use ($ids) { $a->whereIn('authors.id', $ids); });
        }
        if ($filters['year_from']) $q->where('publish_year', '>=', $filters['year_from']);
        if ($filters['year_to']) $q->where('publish_year', '<=', $filters['year_to']);
        $min = $filters['min_price']; $max = $filters['max_price'];
        if ($min || $max) {
            $q->whereHas('variants', function ($v) use ($min, $max) {
                if ($min) $v->where('price', '>=', $min);
                if ($max) $v->where('price', '<=', $max);
            });
        }
        $books = $q->get();

        $qvec = $this->vectorizeQuery($filters['text']);
        $vectors = $this->vectors();
        $qnorm = TextAnalyzer::norm($qvec);

        foreach ($books as $book) {
            $sim = 0.0;
            if ($qvec && isset($vectors[$book->id])) {
                $sim = TextAnalyzer::cosine($qvec, $vectors[$book->id]['v'], $qnorm, $vectors[$book->id]['n']);
            }
            $book->score = round($sim, 4);
        }

        // Nếu có phần chữ thì bỏ các sách không liên quan (trừ khi điều kiện cứng đã lọc)
        $hasHardFilter = $filters['category_ids'] || $filters['author_ids'] || $min || $max || $filters['year_from'] || $filters['year_to'];
        if ($qvec && !$hasHardFilter) {
            $books = $books->filter(function ($b) { return $b->score > 0.03; });
        } elseif (!$qvec && $filters['text'] !== '' && !$hasHardFilter) {
            $books = collect(); // toàn từ lạ -> không có kết quả
        }

        switch ($filters['sort']) {
            case 'price_asc':  $books = $books->sortBy(function ($b) { return optional($b->variants->first())->price; }); break;
            case 'price_desc': $books = $books->sortByDesc(function ($b) { return optional($b->variants->first())->price; }); break;
            case 'best':       $books = $books->sortByDesc('sold_count'); break;
            case 'newest':     $books = $books->sortByDesc('publish_year'); break;
            case 'rating':     $books = $books->sortByDesc('rating_avg'); break;
            default:
                $books = $books->sortByDesc(function ($b) { return $b->score * 1000 + min($b->sold_count, 999) / 1000; });
        }

        return ['filters' => $filters, 'results' => $books->values()->take($limit)];
    }

    /** Diễn giải điều kiện đã hiểu (hiển thị cho người dùng) */
    public function explain(array $f)
    {
        $out = [];
        if ($f['category_names']) $out[] = 'Thể loại: ' . implode(', ', $f['category_names']);
        if ($f['author_names']) $out[] = 'Tác giả: ' . implode(', ', $f['author_names']);
        if ($f['min_price']) $out[] = 'Giá từ ' . money($f['min_price']);
        if ($f['max_price']) $out[] = 'Giá đến ' . money($f['max_price']);
        if ($f['year_from'] && $f['year_from'] === $f['year_to']) $out[] = 'Năm XB: ' . $f['year_from'];
        elseif ($f['year_from']) $out[] = 'XB từ năm ' . $f['year_from'];
        elseif ($f['year_to']) $out[] = 'XB trước năm ' . $f['year_to'];
        $sorts = ['price_asc' => 'Giá thấp → cao', 'price_desc' => 'Giá cao → thấp', 'best' => 'Bán chạy', 'newest' => 'Mới nhất', 'rating' => 'Đánh giá cao'];
        if ($f['sort']) $out[] = 'Sắp xếp: ' . $sorts[$f['sort']];
        if ($f['text'] !== '') $out[] = 'Nội dung: "' . $f['text'] . '"';
        return $out;
    }
}
