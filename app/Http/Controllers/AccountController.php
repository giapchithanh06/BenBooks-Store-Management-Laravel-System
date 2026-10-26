<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Book;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        $stats = [
            'orders'   => $user->orders()->count(),
            'spent'    => $user->orders()->where('status', 'delivered')->sum('total'),
            'wishlist' => $user->wishlistBooks()->count(),
            'reviews'  => $user->reviews()->count(),
        ];
        return view('shop.account.profile', compact('user', 'stats'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^0\d{9}$/'],
        ], ['phone.regex' => 'Số điện thoại không hợp lệ.'], ['name' => 'họ tên', 'phone' => 'số điện thoại']);
        $user->update($data);
        return back()->with('success', 'Đã cập nhật thông tin tài khoản.');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:6|confirmed',
        ], [], ['current_password' => 'mật khẩu hiện tại', 'password' => 'mật khẩu mới']);
        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }
        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Đổi mật khẩu thành công.');
    }

    /* ---------------- Quản lý địa chỉ */

    protected function addressRules()
    {
        return [
            'receiver_name' => 'required|string|max:100',
            'phone'         => ['required', 'regex:/^0\d{9}$/'],
            'province'      => 'required|string|max:100',
            'district'      => 'required|string|max:100',
            'ward'          => 'required|string|max:100',
            'detail'        => 'required|string|max:200',
        ];
    }

    protected function addressNames()
    {
        return ['receiver_name' => 'người nhận', 'phone' => 'số điện thoại', 'province' => 'tỉnh/thành',
                'district' => 'quận/huyện', 'ward' => 'phường/xã', 'detail' => 'địa chỉ chi tiết'];
    }

    public function addresses()
    {
        $addresses = Auth::user()->addresses()->get();
        return view('shop.account.addresses', compact('addresses'));
    }

    public function storeAddress(Request $request)
    {
        $data = $request->validate($this->addressRules(), ['phone.regex' => 'Số điện thoại không hợp lệ.'], $this->addressNames());
        $user = Auth::user();
        $isDefault = $request->boolean('is_default') || !$user->addresses()->exists();
        if ($isDefault) $user->addresses()->update(['is_default' => false]);
        $user->addresses()->create($data + ['is_default' => $isDefault]);
        return back()->with('success', 'Đã thêm địa chỉ mới.');
    }

    public function updateAddress(Request $request, Address $address)
    {
        abort_if($address->user_id != Auth::id(), 403);
        $data = $request->validate($this->addressRules(), ['phone.regex' => 'Số điện thoại không hợp lệ.'], $this->addressNames());
        $address->update($data);
        return back()->with('success', 'Đã cập nhật địa chỉ.');
    }

    public function deleteAddress(Address $address)
    {
        abort_if($address->user_id != Auth::id(), 403);
        $wasDefault = $address->is_default;
        $address->delete();
        if ($wasDefault) {
            $first = Auth::user()->addresses()->first();
            if ($first) $first->update(['is_default' => true]);
        }
        return back()->with('success', 'Đã xóa địa chỉ.');
    }

    public function defaultAddress(Address $address)
    {
        abort_if($address->user_id != Auth::id(), 403);
        Auth::user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
        return back()->with('success', 'Đã đặt làm địa chỉ mặc định.');
    }

    /* ---------------- Wishlist */

    public function wishlist()
    {
        $books = Auth::user()->wishlistBooks()->with(['authors', 'variants'])->latest('wishlists.created_at')->get();
        return view('shop.account.wishlist', compact('books'));
    }

    public function toggleWishlist(Request $request, Book $book)
    {
        $user = Auth::user();
        $exists = $user->wishlistBooks()->where('books.id', $book->id)->exists();
        if ($exists) {
            $user->wishlistBooks()->detach($book->id);
            $msg = 'Đã bỏ khỏi danh sách yêu thích.';
        } else {
            $user->wishlistBooks()->attach($book->id);
            UserActivity::log('wishlist', $book->id);
            $msg = 'Đã thêm vào danh sách yêu thích.';
        }
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'in' => !$exists, 'message' => $msg]);
        }
        return back()->with('success', $msg);
    }
}
