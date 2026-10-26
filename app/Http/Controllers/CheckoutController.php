<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function index(CartService $cart)
    {
        $items = $cart->items();
        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống.');
        }
        if ($items->contains(function ($i) { return $i->error; })) {
            return redirect()->route('cart.index')->with('error', 'Một số sản phẩm không đủ hàng, vui lòng cập nhật giỏ.');
        }
        $user = Auth::user();
        $addresses = $user->addresses()->get();
        $subtotal = $cart->subtotal();
        $default = $addresses->first();
        return view('shop.checkout', [
            'items'     => $items,
            'addresses' => $addresses,
            'subtotal'  => $subtotal,
            'discount'  => $cart->discount($user),
            'voucher'   => $cart->voucher($user),
            'shipping'  => CartService::shippingFee($subtotal, optional($default)->province),
            'shippingTable' => [
                'hcm' => CartService::shippingFee($subtotal, 'Hồ Chí Minh'),
                'near' => CartService::shippingFee($subtotal, 'Bình Dương'),
                'other' => CartService::shippingFee($subtotal, 'Hà Nội'),
            ],
            'methods'   => Order::PAYMENT_METHODS,
        ]);
    }

    public function store(Request $request, OrderService $orders, PaymentService $payment)
    {
        $user = Auth::user();
        $rules = [
            'payment_method' => 'required|in:cod,online',
            'note'           => 'nullable|string|max:250',
            'address_id'     => 'nullable|integer',
        ];
        if (!$request->filled('address_id')) {
            $rules += [
                'receiver_name' => 'required|string|max:100',
                'phone'         => ['required', 'regex:/^0\d{9}$/'],
                'province'      => 'required|string|max:100',
                'district'      => 'required|string|max:100',
                'ward'          => 'required|string|max:100',
                'detail'        => 'required|string|max:200',
            ];
        }
        $data = $request->validate($rules, ['phone.regex' => 'Số điện thoại không hợp lệ.'], [
            'receiver_name' => 'người nhận', 'phone' => 'số điện thoại', 'province' => 'tỉnh/thành',
            'district' => 'quận/huyện', 'ward' => 'phường/xã', 'detail' => 'địa chỉ chi tiết',
        ]);

        if ($request->filled('address_id')) {
            $address = Address::where('user_id', $user->id)->findOrFail($request->address_id);
        } else {
            $address = new Address($data + ['user_id' => $user->id]);
            if ($request->boolean('save_address')) {
                $address->is_default = !$user->addresses()->exists();
                $address->save();
            }
        }

        try {
            $order = $orders->placeOrder($user, [
                'receiver_name' => $address->receiver_name,
                'phone'         => $address->phone,
                'address'       => $address->full,
                'province'      => $address->province,
                'note'          => $data['note'] ?? null,
            ], $data['payment_method']);
        } catch (\RuntimeException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        if ($order->payment_method === 'online') {
            return redirect()->away($payment->createPaymentUrl($order));
        }
        return redirect()->route('checkout.success', $order->code);
    }

    public function success($code)
    {
        $order = Order::where('code', $code)->where('user_id', Auth::id())->with('items')->firstOrFail();
        return view('shop.checkout-success', compact('order'));
    }
}
