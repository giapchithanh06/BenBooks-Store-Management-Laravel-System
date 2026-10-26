<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\KnowledgeDoc;
use App\Models\Voucher;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index(RecommendationService $reco)
    {
        $with = ['authors', 'variants'];
        $flashSale = FlashSale::current();
        $flashItems = $flashSale
            ? $flashSale->items()->with('variant.book.authors')->get()->filter(function ($i) { return $i->variant && $i->variant->book; })
            : collect();

        $comicRoot = Category::where('slug', 'truyen-tranh')->first();
        $comics = $comicRoot
            ? Book::active()->with($with)->whereIn('category_id', $comicRoot->descendantIds())->orderByDesc('sold_count')->take(10)->get()
            : collect();

        return view('shop.home', [
            'flashSale'   => $flashSale,
            'flashItems'  => $flashItems,
            'featured'    => Book::active()->with($with)->where('is_featured', true)->take(10)->get(),
            'newest'      => Book::active()->with($with)->orderByDesc('publish_year')->orderByDesc('id')->take(10)->get(),
            'bestSellers' => $reco->bestSellers(10),
            'comics'      => $comics,
            'recommended' => $reco->forUser(Auth::id(), Auth::check() ? null : session()->getId(), 10),
            'interests'   => $reco->topInterests(Auth::id(), Auth::check() ? null : session()->getId()),
            'categories'  => Category::whereNull('parent_id')->with(['children' => function ($q) { $q->withCount('books'); }])->orderBy('sort_order')->get(),
            'promoVoucher' => Voucher::where('is_active', true)
                ->where(function ($q) { $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()); })
                ->where(function ($q) { $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()); })
                ->where(function ($q) { $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'); })
                ->orderByDesc('value')->first(),
            'stats' => [
                'books'     => Book::active()->count(),
                'authors'   => \App\Models\Author::count(),
                'customers' => \App\Models\User::where('role', 'customer')->count(),
            ],
            'needs' => [
                'cheap'  => Book::active()->whereHas('variants', function ($q) { $q->where('price', '<=', 100000); })->count(),
                'comic'  => $comicRoot ? Book::active()->whereIn('category_id', $comicRoot->descendantIds())->count() : 0,
                'skill'  => Book::active()->whereHas('category', function ($q) { $q->whereIn('slug', ['ky-nang-song', 'kinh-te']); })->count(),
                'kids'   => Book::active()->whereHas('category', function ($q) { $q->where('slug', 'thieu-nhi'); })->count(),
            ],
        ]);
    }

    public function flashSale()
    {
        $sales = FlashSale::where('is_active', true)->where('ends_at', '>=', now())->orderBy('starts_at')
            ->with(['items.variant.book.authors'])->get();
        return view('shop.flash-sale', compact('sales'));
    }

    public function vouchers()
    {
        $now = now();
        $vouchers = Voucher::where('is_active', true)
            ->where(function ($q) use ($now) { $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now); })
            ->orderBy('ends_at')->get();
        return view('shop.vouchers', compact('vouchers'));
    }

    public function policies()
    {
        $docs = KnowledgeDoc::where('is_active', true)->orderBy('category')->get()->groupBy('category_name');
        return view('shop.policies', compact('docs'));
    }
}
