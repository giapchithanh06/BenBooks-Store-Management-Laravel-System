<?php

namespace App\Services;

use App\Models\BookVariant;
use App\Models\Voucher;

/**
 * Giỏ hàng lưu trong session: ['variant_id' => quantity]. Mã giảm giá lưu ở session 'cart_voucher'.
 */
class CartService
{
    const KEY = 'cart';
    const VOUCHER_KEY = 'cart_voucher';
    const FREE_SHIP_FROM = 300000;

    protected $cachedItems = null;

    public function raw()
    {
        return session(self::KEY, []);
    }

    protected function put(array $cart)
    {
        session([self::KEY => $cart]);
        $this->cachedItems = null;
    }

    public function count()
    {
        return array_sum($this->raw());
    }

    /** Thêm vào giỏ. Trả về thông báo lỗi (string) hoặc null nếu thành công */
    public function add($variantId, $qty = 1)
    {
        $variant = BookVariant::with('book')->find($variantId);
        if (!$variant || !$variant->book || !$variant->book->is_active) {
            return 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.';
        }
        $cart = $this->raw();
        $newQty = ($cart[$variantId] ?? 0) + max(1, (int) $qty);
        if ($newQty > $variant->stock) {
            return 'Chỉ còn ' . $variant->stock . ' sản phẩm "' . $variant->book->title . ' – ' . $variant->name . '" trong kho.';
        }
        $flash = $variant->flash_item;
        if ($flash && $newQty > $flash->remaining) {
            return 'Suất Flash Sale chỉ còn ' . $flash->remaining . ' sản phẩm.';
        }
        $cart[$variantId] = $newQty;
        $this->put($cart);
        return null;
    }

    public function update($variantId, $qty)
    {
        $cart = $this->raw();
        $qty = (int) $qty;
        if ($qty <= 0) {
            unset($cart[$variantId]);
        } else {
            $variant = BookVariant::find($variantId);
            if (!$variant) {
                unset($cart[$variantId]);
            } else {
                $cart[$variantId] = min($qty, max(0, $variant->stock));
                if ($cart[$variantId] <= 0) unset($cart[$variantId]);
            }
        }
        $this->put($cart);
    }

    public function remove($variantId)
    {
        $cart = $this->raw();
        unset($cart[$variantId]);
        $this->put($cart);
    }

    public function clear()
    {
        session()->forget([self::KEY, self::VOUCHER_KEY]);
        $this->cachedItems = null;
    }

    /** Danh sách dòng giỏ hàng đã tính giá */
    public function items()
    {
        if ($this->cachedItems !== null) return $this->cachedItems;
        $cart = $this->raw();
        if (!$cart) return $this->cachedItems = collect();

        $variants = BookVariant::with('book.authors')->whereIn('id', array_keys($cart))->get()->keyBy('id');
        $items = collect();
        foreach ($cart as $variantId => $qty) {
            $v = $variants->get($variantId);
            if (!$v || !$v->book) continue;
            $price = $v->effective_price;
            $items->push((object) [
                'variant'    => $v,
                'book'       => $v->book,
                'quantity'   => (int) $qty,
                'price'      => $price,
                'compare'    => $v->compare_at_price,
                'flash_item' => $v->flash_item,
                'total'      => $price * $qty,
                'error'      => $qty > $v->stock ? 'Không đủ hàng (còn ' . $v->stock . ')' : null,
            ]);
        }
        return $this->cachedItems = $items;
    }

    public function subtotal()
    {
        return (int) $this->items()->sum('total');
    }

    /** Phí ship theo tỉnh/thành – miễn phí cho đơn từ 300k */
    public static function shippingFee($subtotal, $province = null)
    {
        if ($subtotal <= 0) return 0;
        if ($subtotal >= self::FREE_SHIP_FROM) return 0;
        $p = TextAnalyzer::normalize($province);
        if (strpos($p, 'ho chi minh') !== false || strpos($p, 'hcm') !== false) return 20000;
        if (preg_match('/binh duong|dong nai|long an|ba ria|tay ninh/', $p)) return 25000;
        return 35000;
    }

    /* ----------------------------------------------- Voucher */

    public function voucherCode()
    {
        return session(self::VOUCHER_KEY);
    }

    public function applyVoucher($code, $user = null)
    {
        $voucher = Voucher::where('code', strtoupper(trim($code)))->first();
        if (!$voucher) return 'Mã giảm giá không tồn tại.';
        $err = $voucher->checkUsable($this->subtotal(), $user);
        if ($err) return $err;
        session([self::VOUCHER_KEY => $voucher->code]);
        return null;
    }

    public function removeVoucher()
    {
        session()->forget(self::VOUCHER_KEY);
    }

    /** Voucher hợp lệ hiện tại (tự gỡ nếu không còn hợp lệ) */
    public function voucher($user = null)
    {
        $code = $this->voucherCode();
        if (!$code) return null;
        $v = Voucher::where('code', $code)->first();
        if (!$v || $v->checkUsable($this->subtotal(), $user)) {
            return null;
        }
        return $v;
    }

    public function discount($user = null)
    {
        $v = $this->voucher($user);
        return $v ? $v->discountFor($this->subtotal()) : 0;
    }
}
