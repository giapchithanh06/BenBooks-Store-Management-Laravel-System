<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['book', 'user'])
            ->when($request->sentiment, function ($q, $s) { $q->where('sentiment', $s); })
            ->when($request->rating, function ($q, $r) { $q->where('rating', $r); })
            ->latest()->paginate(20)->withQueryString();
        return view('admin.reviews.index', compact('reviews'));
    }

    public function toggle(Review $review)
    {
        $review->update(['is_visible' => !$review->is_visible]);
        if ($review->book) $review->book->recalcRating();
        return back()->with('success', $review->is_visible ? 'Đã hiển thị đánh giá.' : 'Đã ẩn đánh giá.');
    }

    public function destroy(Review $review)
    {
        $book = $review->book;
        $review->delete();
        if ($book) $book->recalcRating();
        return back()->with('success', 'Đã xóa đánh giá.');
    }
}
