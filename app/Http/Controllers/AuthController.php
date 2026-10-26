<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ], [], ['password' => 'mật khẩu']);

        // Giữ lại giỏ hàng của khách vãng lai sau khi đăng nhập
        $cart = session('cart', []);
        $voucher = session('cart_voucher');

        if (!Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])->withInput($request->only('email'));
        }
        if (!Auth::user()->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Tài khoản của bạn đã bị khóa.'])->withInput($request->only('email'));
        }

        $request->session()->regenerate();
        session(['cart' => $cart, 'cart_voucher' => $voucher]);

        $user = Auth::user();
        if ($user->isBackend()) {
            return redirect()->intended(route('admin.dashboard'));
        }
        if ($user->isShipper()) {
            return redirect()->route('shipper.index');
        }
        return redirect()->intended(route('home'))->with('success', 'Chào mừng ' . $user->name . ' quay lại!');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:users,email',
            'phone'    => ['required', 'regex:/^0\d{9}$/'],
            'password' => 'required|string|min:6|confirmed',
        ], ['phone.regex' => 'Số điện thoại phải gồm 10 chữ số, bắt đầu bằng 0.'],
           ['name' => 'họ tên', 'phone' => 'số điện thoại', 'password' => 'mật khẩu']);

        $cart = session('cart', []);
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'],
            'password' => Hash::make($data['password']),
            'role'     => 'customer',
        ]);
        Auth::login($user);
        $request->session()->regenerate();
        session(['cart' => $cart]);

        return redirect()->route('home')->with('success', 'Đăng ký thành công! Chào mừng bạn đến với BenBooks.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
