<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookView;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\Review;
use App\Models\UserActivity;
use App\Services\RecommendationService;
use App\Services\SemanticSearchService;
use App\Services\SentimentService;
use App\Services\TextAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    /**
     * Danh sách + Tìm kiếm nâng cao: từ khóa, ISBN, tác giả, thể loại, NXB, năm XB, khoảng giá, loại bìa, sắp xếp.
     */
    public function index(Request $request, $preset = [])
    {
        $f = array_merge($request->only(['q', 'isbn', 'category', 'author', 'publisher', 'year_from', 'year_to',
                                         'price_min', 'price_max', 'format', 'in_stock', 'sort', 'rating']), $preset);

        $query = Book::active()->with(['authors', 'variants', 'category']);

        if (!empty($f['q'])) {
            $kw = trim($f['q']);
            $query->where(function ($q) use ($kw) {
                $q->where('title', 'like', "%{$kw}%")
                  ->orWhere('isbn', 'like', "%{$kw}%")
                  ->orWhere('tags', 'like', "%{$kw}%")
                  ->orWhereHas('authors', function ($a) use ($kw) { $a->where('name', 'like', "%{$kw}%"); })
                  ->orWhereHas('publisher', function ($p) use ($kw) { $p->where('name', 'like', "%{$kw}%"); });
            });
        }
        if (!empty($f['isbn'])) {
            $query->where('isbn', 'like', '%' . preg_replace('/[^0-9Xx]/', '', $f['isbn']) . '%');
        }
        if (!empty($f['category'])) {
            $cat = Category::where('slug', $f['category'])->orWhere('id', $f['category'])->first();
            if ($cat) $query->whereIn('category_id', $cat->descendantIds());
        }
        if (!empty($f['author'])) {
            $authorId = $f['author'];
            $query->whereHas('authors', function ($a) use ($authorId) { $a->where('authors.id', $authorId)->orWhere('authors.slug', $authorId); });
        }
        if (!empty($f['publisher'])) {
            $pub = $f['publisher'];
            $query->whereHas('publisher', function ($p) use ($pub) { $p->where('publishers.id', $pub)->orWhere('publishers.slug', $pub); });
        }
        if (!empty($f['year_from'])) $query->where('publish_year', '>=', (int) $f['year_from']);
        if (!empty($f['year_to'])) $query->where('publish_year', '<=', (int) $f['year_to']);
        if (!empty($f['rating'])) $query->where('rating_avg', '>=', (float) $f['rating']);
        $min = !empty($f['price_min']) ? (int) $f['price_min'] : null;
        $max = !empty($f['price_max']) ? (int) $f['price_max'] : null;
        $format = $f['format'] ?? null;
        $inStock = !empty($f['in_stock']);
        if ($min || $max || $format || $inStock) {
            $query->whereHas('variants', function ($v) use ($min, $max, $format, $inStock) {
                if ($min) $v->where('price', '>=', $min);
                if ($max) $v->where('price', '<=', $max);
                if ($format) $v->where('name', $format);
                if ($inStock) $v->where('stock', '>', 0);
            });
        }

        switch ($f['sort'] ?? '') {
            case 'price_asc':
                $query->orderBy(\App\Models\BookVariant::selectRaw('MIN(price)')->whereColumn('book_variants.book_id', 'books.id'));
                break;
            case 'price_desc':
                $query->orderByDesc(\App\Models\BookVariant::selectRaw('MIN(price)')->whereColumn('book_variants.book_id', 'books.id'));
                break;
            case 'best':   $query->orderByDesc('sold_count'); break;
            case 'rating': $query->orderByDesc('rating_avg'); break;
            case 'view':   $query->orderByDesc('view_count'); break;
            case 'year':   $query->orderByDesc('publish_year'); break;
            default:       $query->orderByDesc('id');
        }

        if (!empty($f['q'])) UserActivity::log('search', null, $f['q']);

        return view('shop.books.index', [
            'books'      => $query->paginate(16)->withQueryString(),
            'filters'    => $f,
            'title'      => $preset['_title'] ?? (!empty($f['q']) ? 'Kết quả tìm kiếm "' . $f['q'] . '"' : 'Tất cả sách & truyện'),
            'heading'    => $preset['_heading'] ?? null,
            'categories' => Category::whereNull('parent_id')->with('children')->orderBy('sort_order')->get(),
            'authors'    => Author::orderBy('name')->get(),
            'publishers' => Publisher::orderBy('name')->get(),
            'formats'    => \App\Models\BookVariant::select('name')->distinct()->pluck('name'),
        ]);
    }

    public function search(Request $request)
    {
        return $this->index($request);
    }

    public function category(Request $request, $slug)
    {
        $cat = Category::where('slug', $slug)->firstOrFail();
        return $this->index($request, ['category' => $cat->slug, '_title' => $cat->name, '_heading' => $cat]);
    }

    public function author(Request $request, $slug)
    {
        $author = Author::where('slug', $slug)->firstOrFail();
        return $this->index($request, ['author' => $author->id, '_title' => 'Tác giả: ' . $author->name, '_heading' => $author]);
    }

    public function publisher(Request $request, $slug)
    {
        $pub = Publisher::where('slug', $slug)->firstOrFail();
        return $this->index($request, ['publisher' => $pub->id, '_title' => 'Nhà xuất bản: ' . $pub->name, '_heading' => $pub]);
    }

    /** Semantic Search – tìm bằng ngôn ngữ tự nhiên */
    public function smartSearch(Request $request, SemanticSearchService $search)
    {
        $q = trim((string) $request->input('q'));
        $result = $q !== '' ? $search->search($q, 24) : null;
        if ($q !== '') UserActivity::log('search', null, '[AI] ' . $q);
        return view('shop.books.smart', [
            'q'          => $q,
            'books'      => $result ? $result['results'] : collect(),
            'understood' => $result ? $search->explain($result['filters']) : [],
            'examples'   => ['truyện tranh hành động Nhật Bản dưới 40k', 'sách giúp làm giàu và quản lý tiền bạc',
                             'sách thiếu nhi về tình bạn', 'manga trinh thám phá án', 'sách của Nguyễn Nhật Ánh rẻ nhất',
                             'sách khoa học vũ trụ xuất bản sau 2019', 'truyện tu tiên Trung Quốc'],
        ]);
    }

    /** Gợi ý tự động khi gõ ô tìm kiếm (AJAX) */
    public function suggest(Request $request)
    {
        $kw = trim((string) $request->input('q'));
        if (mb_strlen($kw) < 2) return response()->json([]);
        $norm = TextAnalyzer::normalize($kw);
        $books = Book::active()->with(['authors', 'variants'])->get()->filter(function ($b) use ($norm) {
            return strpos(TextAnalyzer::normalize($b->title . ' ' . $b->author_names . ' ' . $b->isbn), $norm) !== false;
        })->take(6);
        return response()->json($books->map(function ($b) {
            $v = $b->display_variant;
            return ['title' => $b->title, 'author' => $b->author_names, 'url' => route('books.show', $b->slug),
                    'cover' => $b->cover_url, 'price' => $v ? money($v->effective_price) : ''];
        })->values());
    }

    public function show($slug, RecommendationService $reco)
    {
        $book = Book::active()->where('slug', $slug)
            ->with(['authors', 'variants', 'category.parent', 'publisher', 'reviews' => function ($q) {
                $q->where('is_visible', true)->with('user');
            }])->firstOrFail();

        $book->increment('view_count');
        UserActivity::log('view', $book->id);
        if (Auth::check()) {
            BookView::firstOrCreate(['user_id' => Auth::id(), 'book_id' => $book->id])->touch();
        }

        $user = Auth::user();
        $ratingStats = [];
        for ($i = 5; $i >= 1; $i--) {
            $ratingStats[$i] = $book->reviews->where('rating', $i)->count();
        }

        return view('shop.books.show', [
            'book'        => $book,
            'similar'     => $reco->similar($book, 6),
            'alsoBought'  => $reco->alsoBought($book, 6),
            'inWishlist'  => $user ? $user->wishlistBooks()->where('books.id', $book->id)->exists() : false,
            'canReview'   => $user ? $user->hasPurchased($book->id) : false,
            'myReview'    => $user ? $book->reviews->firstWhere('user_id', $user->id) : null,
            'ratingStats' => $ratingStats,
        ]);
    }

    /** Đánh giá sản phẩm – chỉ khách đã mua và nhận hàng */
    public function review(Request $request, Book $book, SentimentService $sentiment)
    {
        $data = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ], [], ['rating' => 'số sao', 'comment' => 'nhận xét']);

        if (!Auth::user()->hasPurchased($book->id)) {
            return back()->with('error', 'Bạn chỉ có thể đánh giá sách đã mua và nhận hàng thành công.');
        }
        $review = Review::updateOrCreate(
            ['book_id' => $book->id, 'user_id' => Auth::id()],
            $data + ['is_visible' => true]
        );
        $sentiment->analyze($review);
        $book->recalcRating();

        return back()->with('success', 'Cảm ơn bạn đã đánh giá!');
    }
}
