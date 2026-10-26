<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| API công khai (JSON) – ví dụ dùng cho ứng dụng di động / giao hàng.
*/

Route::get('/books', function (Request $request) {
    return \App\Models\Book::active()->with(['authors:id,name', 'variants:id,book_id,name,price,stock'])
        ->select('id', 'title', 'slug', 'isbn', 'publish_year', 'cover_image', 'rating_avg', 'sold_count')
        ->paginate(20);
});

Route::get('/orders/track', function (Request $request) {
    $order = \App\Models\Order::where('code', $request->query('code'))->where('phone', $request->query('phone'))
        ->with('histories:id,order_id,status,note,created_at')->first(['id', 'code', 'status', 'total', 'tracking_code', 'shipping_carrier']);
    return $order ?: response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
});
