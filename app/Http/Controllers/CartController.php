<?php

namespace App\Http\Controllers;

use App\Models\BookVariant;
use App\Models\UserActivity;
use App\Models\Voucher;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $cart;

    public function __construct(CartService $cart)
    {
        $this->cart = $cart;
    }

    public function index()
    {
        $user = Auth::user();
        $subtotal = $this->cart->subtotal();
        $discount = $this->cart->discount($user);
        $now = now();
        return view('shop.cart', [
            'items'    => $this->cart->items(),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'voucher'  => $this->cart->voucher($user),
            'voucherCode' => $this->cart->voucherCode(),
            'freeShipFrom' => CartService::FREE_SHIP_FROM,
            'shipping' => CartService::shippingFee($subtotal, optional($user ? $user->defaultAddress() : null)->province),
            'availableVouchers' => Voucher::where('is_active', true)
                ->where(function ($q) use ($now) { $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now); })
                ->where(function ($q) use ($now) { $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now); })
                ->take(6)->get(),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate(['variant_id' => 'required|integer', 'quantity' => 'nullable|integer|min:1|max:99']);
        $err = $this->cart->add($request->variant_id, $request->input('quantity', 1));

        if (!$err) {
            $variant = BookVariant::find($request->variant_id);
            UserActivity::log('add_cart', optional($variant)->book_id);
        }
        if ($request->expectsJson()) {
            return response()->json(['ok' => !$err, 'message' => $err ?: 'Đã thêm vào giỏ hàng!', 'count' => $this->cart->count()], $err ? 422 : 200);
        }
        if ($err) return back()->with('error', $err);
        if ($request->input('buy_now')) return redirect()->route('checkout.index');
        return back()->with('success', 'Đã thêm vào giỏ hàng!');
    }

    public function update(Request $request)
    {
        foreach ((array) $request->input('qty', []) as $variantId => $qty) {
            $this->cart->update((int) $variantId, (int) $qty);
        }
        return redirect()->route('cart.index')->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function remove($variant)
    {
        $this->cart->remove((int) $variant);
        return redirect()->route('cart.index')->with('success', 'Đã xóa sản phẩm khỏi giỏ.');
    }

    public function applyVoucher(Request $request)
    {
        $request->validate(['code' => 'required|string|max:50'], [], ['code' => 'mã giảm giá']);
        $err = $this->cart->applyVoucher($request->code, Auth::user());
        return back()->with($err ? 'error' : 'success', $err ?: 'Áp dụng mã giảm giá thành công!');
    }

    public function removeVoucher()
    {
        $this->cart->removeVoucher();
        return back()->with('success', 'Đã bỏ mã giảm giá.');
    }
}
