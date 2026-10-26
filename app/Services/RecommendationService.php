<?php

namespace App\Services;

use App\Models\Book;
use App\Models\OrderItem;
use App\Models\UserActivity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Recommendation System (hybrid):
 *  - Content-based: cosine giữa vector TF-IDF của sách (cùng tác giả/thể loại/chủ đề).
 *  - Item-based Collaborative Filtering: các sách thường được mua cùng nhau (đồng xuất hiện trong đơn hàng).
 *  - Personalized: hồ sơ sở thích từ hành vi (xem, yêu thích, thêm giỏ, mua) của từng người dùng.
 */
class RecommendationService
{
    const WEIGHTS = ['view' => 1, 'search' => 0, 'add_cart' => 3, 'wishlist' => 3, 'purchase' => 5];

    protected $search;

    public function __construct(SemanticSearchService $search)
    {
        $this->search = $search;
    }

    /** Ma trận đồng xuất hiện: [book_id => [other_id => số đơn mua cùng]] */
    public function coPurchaseMatrix()
    {
        return Cache::remember('reco_copurchase', 600, function () {
            $rows = OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', '!=', 'cancelled')
                ->whereNotNull('order_items.book_id')
                ->select('order_items.order_id', 'order_items.book_id')
                ->distinct()->get()->groupBy('order_id');
            $matrix = [];
            foreach ($rows as $items) {
                $ids = $items->pluck('book_id')->unique()->values()->all();
                foreach ($ids as $a) {
                    foreach ($ids as $b) {
                        if ($a != $b) {
                            $matrix[$a][$b] = ($matrix[$a][$b] ?? 0) + 1;
                        }
                    }
                }
            }
            return $matrix;
        });
    }

    protected function loadBooks(array $scores, $limit, array $exclude = [])
    {
        foreach ($exclude as $id) {
            unset($scores[$id]);
        }
        arsort($scores);
        $ids = array_slice(array_keys($scores), 0, $limit);
        if (!$ids) {
            return collect();
        }
        $books = Book::active()->with(['authors', 'variants'])->whereIn('id', $ids)->get()->keyBy('id');
        return collect($ids)->map(function ($id) use ($books) { return $books->get($id); })->filter()->values();
    }

    /** Sách tương tự (content-based + mua cùng) */
    public function similar(Book $book, $limit = 6)
    {
        $vectors = $this->search->vectors();
        $scores = [];
        if (isset($vectors[$book->id])) {
            $base = $vectors[$book->id];
            foreach ($vectors as $id => $vec) {
                if ($id != $book->id) {
                    $scores[$id] = TextAnalyzer::cosine($base['v'], $vec['v'], $base['n'], $vec['n']);
                }
            }
        }
        $co = $this->coPurchaseMatrix()[$book->id] ?? [];
        $maxCo = $co ? max($co) : 1;
        foreach ($co as $id => $c) {
            $scores[$id] = ($scores[$id] ?? 0) + 0.3 * $c / $maxCo;
        }
        return $this->loadBooks($scores, $limit, [$book->id]);
    }

    /** "Khách hàng mua sách này cũng mua" (item-based CF) */
    public function alsoBought(Book $book, $limit = 6)
    {
        return $this->loadBooks($this->coPurchaseMatrix()[$book->id] ?? [], $limit, [$book->id]);
    }

    /** Hồ sơ quan tâm của người dùng: [book_id => điểm] */
    public function interestProfile($userId = null, $sessionId = null)
    {
        $q = UserActivity::query()->whereNotNull('book_id')->where('created_at', '>=', now()->subDays(90));
        if ($userId) {
            $q->where('user_id', $userId);
        } elseif ($sessionId) {
            $q->where('session_id', $sessionId);
        } else {
            return [];
        }
        $profile = [];
        foreach ($q->select('book_id', 'action', DB::raw('COUNT(*) as c'))->groupBy('book_id', 'action')->get() as $row) {
            $w = self::WEIGHTS[$row->action] ?? 1;
            $profile[$row->book_id] = ($profile[$row->book_id] ?? 0) + $w * min(3, $row->c);
        }
        return $profile;
    }

    /** Gợi ý cá nhân hóa cho người dùng / khách vãng lai */
    public function forUser($userId = null, $sessionId = null, $limit = 8)
    {
        $profile = $this->interestProfile($userId, $sessionId);
        if (!$profile) {
            return $this->bestSellers($limit);
        }

        $vectors = $this->search->vectors();
        // vector sở thích = tổng có trọng số các vector sách đã tương tác
        $userVec = [];
        foreach ($profile as $bookId => $w) {
            if (!isset($vectors[$bookId])) continue;
            foreach ($vectors[$bookId]['v'] as $term => $val) {
                $userVec[$term] = ($userVec[$term] ?? 0) + $w * $val;
            }
        }
        $userNorm = TextAnalyzer::norm($userVec);

        $scores = [];
        foreach ($vectors as $id => $vec) {
            $scores[$id] = TextAnalyzer::cosine($userVec, $vec['v'], $userNorm, $vec['n']);
        }
        // cộng điểm collaborative filtering
        $matrix = $this->coPurchaseMatrix();
        foreach ($profile as $bookId => $w) {
            foreach ($matrix[$bookId] ?? [] as $other => $c) {
                $scores[$other] = ($scores[$other] ?? 0) + 0.05 * $c * $w;
            }
        }

        // không gợi ý lại sách đã mua
        $purchased = [];
        if ($userId) {
            $purchased = OrderItem::whereHas('order', function ($q) use ($userId) {
                $q->where('user_id', $userId)->where('status', '!=', 'cancelled');
            })->pluck('book_id')->filter()->all();
        }
        $result = $this->loadBooks($scores, $limit, $purchased);
        return $result->count() >= 4 ? $result : $this->bestSellers($limit);
    }

    public function bestSellers($limit = 8)
    {
        return Book::active()->with(['authors', 'variants'])->orderByDesc('sold_count')->take($limit)->get();
    }

    /** Lý do gợi ý (để hiển thị) – thể loại/tác giả người dùng quan tâm nhiều nhất */
    public function topInterests($userId = null, $sessionId = null)
    {
        $profile = $this->interestProfile($userId, $sessionId);
        if (!$profile) return [];
        $books = Book::with(['category', 'authors'])->whereIn('id', array_keys($profile))->get();
        $cats = [];
        foreach ($books as $b) {
            $name = optional($b->category)->name;
            if ($name) $cats[$name] = ($cats[$name] ?? 0) + $profile[$b->id];
        }
        arsort($cats);
        return array_slice(array_keys($cats), 0, 3);
    }
}
