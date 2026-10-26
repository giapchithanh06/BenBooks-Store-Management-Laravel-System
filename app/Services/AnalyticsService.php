<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\UserActivity;
use Illuminate\Support\Facades\DB;

/**
 * Phân tích hành vi khách hàng + dữ liệu cho Knowledge Graph sản phẩm.
 */
class AnalyticsService
{
    /** Phễu chuyển đổi: xem -> thêm giỏ -> mua (đếm theo phiên/người dùng duy nhất) */
    public function funnel($days = 30)
    {
        $from = now()->subDays($days);
        $count = function ($action) use ($from) {
            return UserActivity::where('action', $action)->where('created_at', '>=', $from)
                ->select(DB::raw('COUNT(DISTINCT COALESCE(user_id, session_id)) as c'))->value('c');
        };
        $view = (int) $count('view');
        $cart = (int) $count('add_cart');
        $buy = (int) $count('purchase');
        return [
            ['label' => 'Xem sách', 'value' => $view, 'rate' => 100],
            ['label' => 'Thêm vào giỏ', 'value' => $cart, 'rate' => $view ? round($cart * 100 / $view, 1) : 0],
            ['label' => 'Mua hàng', 'value' => $buy, 'rate' => $view ? round($buy * 100 / $view, 1) : 0],
        ];
    }

    public function topKeywords($days = 30, $limit = 15)
    {
        return UserActivity::where('action', 'search')->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('keyword')
            ->select('keyword', DB::raw('COUNT(*) as c'))->groupBy('keyword')->orderByDesc('c')->take($limit)->get();
    }

    /** Sách được xem nhiều nhưng ít mua -> cần xem lại giá/mô tả */
    public function viewedNotBought($days = 30, $limit = 10)
    {
        $from = now()->subDays($days);
        $rows = UserActivity::where('created_at', '>=', $from)->whereNotNull('book_id')
            ->select('book_id',
                DB::raw("SUM(CASE WHEN action = 'view' THEN 1 ELSE 0 END) as views"),
                DB::raw("SUM(CASE WHEN action = 'add_cart' THEN 1 ELSE 0 END) as carts"),
                DB::raw("SUM(CASE WHEN action = 'purchase' THEN 1 ELSE 0 END) as buys"))
            ->groupBy('book_id')->having('views', '>=', 3)->get();
        $books = Book::withTrashed()->whereIn('id', $rows->pluck('book_id'))->get()->keyBy('id');
        return $rows->map(function ($r) use ($books) {
            $r->book = $books->get($r->book_id);
            $r->conversion = $r->views ? round($r->buys * 100 / $r->views, 1) : 0;
            return $r;
        })->filter(function ($r) { return $r->book; })->sortBy('conversion')->take($limit)->values();
    }

    /** Hoạt động theo giờ trong ngày (0-23) */
    public function hourly($days = 30)
    {
        $data = array_fill(0, 24, 0);
        $rows = UserActivity::where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('HOUR(created_at) as h'), DB::raw('COUNT(*) as c'))->groupBy('h')->get();
        foreach ($rows as $r) $data[(int) $r->h] = (int) $r->c;
        return $data;
    }

    /** Quan tâm theo thể loại (lượt xem) */
    public function categoryInterest($days = 30)
    {
        return UserActivity::where('user_activities.created_at', '>=', now()->subDays($days))
            ->where('action', 'view')
            ->join('books', 'books.id', '=', 'user_activities.book_id')
            ->join('categories', 'categories.id', '=', 'books.category_id')
            ->select('categories.name', DB::raw('COUNT(*) as c'))
            ->groupBy('categories.name')->orderByDesc('c')->get();
    }

    public function actionCounts($days = 30)
    {
        return UserActivity::where('created_at', '>=', now()->subDays($days))
            ->select('action', DB::raw('COUNT(*) as c'))->groupBy('action')->pluck('c', 'action');
    }

    /* ------------------------------------------------ Knowledge Graph */

    /**
     * Đồ thị tri thức Sách – Tác giả – Thể loại – NXB (định dạng nodes/edges cho vis-network).
     */
    public function knowledgeGraph($categoryId = null)
    {
        $books = Book::active()->with(['authors', 'category.parent', 'publisher'])
            ->when($categoryId, function ($q) use ($categoryId) {
                $ids = optional(Category::find($categoryId))->descendantIds() ?: [$categoryId];
                $q->whereIn('category_id', $ids);
            })->get();

        $nodes = []; $edges = [];
        $add = function ($id, $label, $group, $title = null) use (&$nodes) {
            if (!isset($nodes[$id])) $nodes[$id] = ['id' => $id, 'label' => $label, 'group' => $group, 'title' => $title ?: $label];
        };
        foreach ($books as $b) {
            $bid = 'b' . $b->id;
            $add($bid, mb_strimwidth($b->title, 0, 28, '…'), 'book', $b->title . ' (' . $b->publish_year . ')');
            if ($b->category) {
                $add('c' . $b->category->id, $b->category->name, 'category');
                $edges[] = ['from' => $bid, 'to' => 'c' . $b->category->id, 'label' => 'thuộc'];
                if ($b->category->parent) {
                    $add('c' . $b->category->parent->id, $b->category->parent->name, 'category');
                    $edges['cc' . $b->category->id] = ['from' => 'c' . $b->category->id, 'to' => 'c' . $b->category->parent->id, 'label' => 'con của'];
                }
            }
            foreach ($b->authors as $a) {
                $add('a' . $a->id, $a->name, 'author');
                $edges[] = ['from' => 'a' . $a->id, 'to' => $bid, 'label' => 'viết'];
            }
            if ($b->publisher) {
                $add('p' . $b->publisher->id, $b->publisher->name, 'publisher');
                $edges[] = ['from' => 'p' . $b->publisher->id, 'to' => $bid, 'label' => 'xuất bản'];
            }
        }
        return ['nodes' => array_values($nodes), 'edges' => array_values($edges)];
    }

    /** Truy vấn tri thức đơn giản: tác giả viết thể loại nào, NXB nào phát hành tác giả nào */
    public function graphFacts()
    {
        $facts = [];
        foreach (Author::with('books.category', 'books.publisher')->get() as $a) {
            if ($a->books->isEmpty()) continue;
            $facts[] = [
                'author' => $a->name,
                'books' => $a->books->count(),
                'categories' => $a->books->pluck('category.name')->filter()->unique()->implode(', '),
                'publishers' => $a->books->pluck('publisher.name')->filter()->unique()->implode(', '),
            ];
        }
        return $facts;
    }

    public function graphSummary()
    {
        return ['books' => Book::active()->count(), 'authors' => Author::count(),
                'categories' => Category::count(), 'publishers' => Publisher::count()];
    }
}
