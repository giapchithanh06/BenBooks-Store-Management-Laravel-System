<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ShipperController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| BenBooks – Website bán sách & truyện tranh (Laravel 8)
|--------------------------------------------------------------------------
*/

// ---------------- Khách (không cần đăng nhập)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', function () { return redirect()->route('home'); });
Route::get('/sach', [BookController::class, 'index'])->name('books.index');
Route::get('/sach/{slug}', [BookController::class, 'show'])->name('books.show');
Route::get('/the-loai/{slug}', [BookController::class, 'category'])->name('books.category');
Route::get('/tac-gia/{slug}', [BookController::class, 'author'])->name('books.author');
Route::get('/nha-xuat-ban/{slug}', [BookController::class, 'publisher'])->name('books.publisher');
Route::get('/tim-kiem', [BookController::class, 'search'])->name('books.search');
Route::get('/tim-kiem-thong-minh', [BookController::class, 'smartSearch'])->name('books.smart');
Route::get('/goi-y-tim-kiem', [BookController::class, 'suggest'])->name('books.suggest');
Route::get('/flash-sale', [HomeController::class, 'flashSale'])->name('flash-sale');
Route::get('/ma-giam-gia', [HomeController::class, 'vouchers'])->name('vouchers');
Route::get('/chinh-sach', [HomeController::class, 'policies'])->name('policies');
Route::get('/tra-cuu-don-hang', [OrderController::class, 'track'])->name('orders.track');

Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
Route::post('/gio-hang/them', [CartController::class, 'add'])->name('cart.add');
Route::post('/gio-hang/cap-nhat', [CartController::class, 'update'])->name('cart.update');
Route::post('/gio-hang/xoa/{variant}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/gio-hang/voucher', [CartController::class, 'applyVoucher'])->name('cart.voucher');
Route::post('/gio-hang/bo-voucher', [CartController::class, 'removeVoucher'])->name('cart.voucher.remove');

Route::post('/chatbot', [ChatbotController::class, 'ask'])->name('chatbot.ask');

// Cổng thanh toán mô phỏng (sandbox)
Route::get('/payment-gateway', [PaymentController::class, 'gateway'])->name('payment.gateway');
Route::post('/payment-gateway', [PaymentController::class, 'gatewaySubmit'])->name('payment.gateway.submit');
Route::get('/payment/return', [PaymentController::class, 'paymentReturn'])->name('payment.return');

// ---------------- Đăng ký / đăng nhập
Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->name('login.post');
    Route::get('/dang-ky', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'register'])->name('register.post');
});
Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ---------------- Khách hàng đã đăng nhập
Route::middleware('auth')->group(function () {
    Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/thanh-toan', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/dat-hang-thanh-cong/{code}', [CheckoutController::class, 'success'])->name('checkout.success');

    Route::get('/tai-khoan', [AccountController::class, 'profile'])->name('account.profile');
    Route::post('/tai-khoan', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::post('/tai-khoan/mat-khau', [AccountController::class, 'changePassword'])->name('account.password');
    Route::get('/tai-khoan/dia-chi', [AccountController::class, 'addresses'])->name('account.addresses');
    Route::post('/tai-khoan/dia-chi', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
    Route::put('/tai-khoan/dia-chi/{address}', [AccountController::class, 'updateAddress'])->name('account.addresses.update');
    Route::delete('/tai-khoan/dia-chi/{address}', [AccountController::class, 'deleteAddress'])->name('account.addresses.delete');
    Route::post('/tai-khoan/dia-chi/{address}/mac-dinh', [AccountController::class, 'defaultAddress'])->name('account.addresses.default');
    Route::get('/tai-khoan/yeu-thich', [AccountController::class, 'wishlist'])->name('account.wishlist');
    Route::post('/yeu-thich/{book}', [AccountController::class, 'toggleWishlist'])->name('wishlist.toggle');
    Route::get('/tai-khoan/thong-bao', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/tai-khoan/thong-bao/doc-het', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::get('/thong-bao/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::get('/thong-bao-moi', [NotificationController::class, 'poll'])->name('notifications.poll');

    Route::get('/don-hang', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/don-hang/{code}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/don-hang/{code}/huy', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/don-hang/{code}/thanh-toan-lai', [OrderController::class, 'repay'])->name('orders.repay');
    Route::post('/don-hang/{code}/mua-lai', [OrderController::class, 'reorder'])->name('orders.reorder');
    Route::get('/don-hang/{code}/doi-tra', [OrderController::class, 'returnForm'])->name('orders.return');
    Route::post('/don-hang/{code}/doi-tra', [OrderController::class, 'returnStore'])->name('orders.return.store');

    Route::post('/sach/{book}/danh-gia', [BookController::class, 'review'])->name('books.review');
});

// ---------------- Nhân viên giao hàng (giao diện mobile)
Route::prefix('shipper')->name('shipper.')->middleware(['auth', 'role:shipper,admin'])->group(function () {
    Route::get('/', [ShipperController::class, 'index'])->name('index');
    Route::get('/don/{order}', [ShipperController::class, 'show'])->name('show');
    Route::post('/don/{order}/nhan', [ShipperController::class, 'pickup'])->name('pickup');
    Route::post('/don/{order}/da-giao', [ShipperController::class, 'delivered'])->name('delivered');
    Route::post('/don/{order}/that-bai', [ShipperController::class, 'failed'])->name('failed');
});

// ---------------- Quản trị: Admin + Nhân viên
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,staff'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('books', Admin\BookController::class)->except(['show']);
    Route::resource('categories', Admin\CategoryController::class)->except(['show', 'create', 'edit']);
    Route::resource('authors', Admin\AuthorController::class)->except(['show', 'create', 'edit']);
    Route::resource('publishers', Admin\PublisherController::class)->except(['show', 'create', 'edit']);

    Route::get('inventory', [Admin\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/import', [Admin\InventoryController::class, 'import'])->name('inventory.import');
    Route::get('inventory/history', [Admin\InventoryController::class, 'history'])->name('inventory.history');

    Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('orders/{order}/shipping', [Admin\OrderController::class, 'updateShipping'])->name('orders.shipping');
    Route::get('orders/{order}/invoice', [Admin\OrderController::class, 'invoice'])->name('orders.invoice');

    Route::get('returns', [Admin\ReturnController::class, 'index'])->name('returns.index');
    Route::post('returns/{returnRequest}', [Admin\ReturnController::class, 'update'])->name('returns.update');

    Route::get('reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::post('reviews/{review}/toggle', [Admin\ReviewController::class, 'toggle'])->name('reviews.toggle');
    Route::delete('reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

    // ---- Chỉ Admin
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', Admin\UserController::class)->except(['show', 'create', 'edit']);
        Route::resource('vouchers', Admin\VoucherController::class)->except(['show']);
        Route::resource('flash-sales', Admin\FlashSaleController::class)->except(['show'])->parameters(['flash-sales' => 'flashSale']);

        Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');

        Route::prefix('ai')->name('ai.')->group(function () {
            Route::get('behavior', [Admin\AiController::class, 'behavior'])->name('behavior');
            Route::get('segments', [Admin\AiController::class, 'segments'])->name('segments');
            Route::get('forecast', [Admin\AiController::class, 'forecast'])->name('forecast');
            Route::get('anomalies', [Admin\AiController::class, 'anomalies'])->name('anomalies');
            Route::post('anomalies/scan', [Admin\AiController::class, 'scanAnomalies'])->name('anomalies.scan');
            Route::get('sentiment', [Admin\AiController::class, 'sentiment'])->name('sentiment');
            Route::get('graph', [Admin\AiController::class, 'graph'])->name('graph');
            Route::get('chatbot', [Admin\AiController::class, 'chatLogs'])->name('chatbot');
            Route::get('search-lab', [Admin\AiController::class, 'searchLab'])->name('search');
            Route::get('queue', [Admin\AiController::class, 'queue'])->name('queue');
            Route::post('queue/dispatch', [Admin\AiController::class, 'queueDispatch'])->name('queue.dispatch');
            Route::post('queue/work', [Admin\AiController::class, 'queueWork'])->name('queue.work');
            Route::resource('knowledge', Admin\KnowledgeController::class)->except(['show']);
        });
    });
});
