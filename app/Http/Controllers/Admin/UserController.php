<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/** Quản lý tài khoản & phân quyền (Admin / Nhân viên / Shipper / Khách hàng) */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withCount('orders')
            ->when($request->q, function ($q, $kw) {
                $q->where(function ($w) use ($kw) { $w->where('name', 'like', "%$kw%")->orWhere('email', 'like', "%$kw%")->orWhere('phone', 'like', "%$kw%"); });
            })
            ->when($request->role, function ($q, $r) { $q->where('role', $r); })
            ->when($request->segment, function ($q, $s) { $q->where('segment', $s); })
            ->latest()->paginate(20)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100', 'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20', 'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => 'required|string|min:6',
        ], [], ['name' => 'họ tên', 'role' => 'vai trò', 'password' => 'mật khẩu']);
        $data['password'] = Hash::make($data['password']);
        User::create($data + ['is_active' => true]);
        return back()->with('success', 'Đã tạo tài khoản.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => 'nullable|string|min:6',
        ]);
        if ($user->id == Auth::id() && $data['role'] !== 'admin') {
            return back()->with('error', 'Bạn không thể tự hạ quyền của chính mình.');
        }
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $data['is_active'] = $request->boolean('is_active');
        if ($user->id == Auth::id()) $data['is_active'] = true;
        $user->update($data);
        return back()->with('success', 'Đã cập nhật tài khoản ' . $user->email);
    }

    public function destroy(User $user)
    {
        if ($user->id == Auth::id()) return back()->with('error', 'Không thể tự xóa tài khoản của mình.');
        if ($user->orders()->exists()) {
            $user->update(['is_active' => false]);
            return back()->with('success', 'Tài khoản đã có đơn hàng nên được KHÓA thay vì xóa.');
        }
        $user->delete();
        return back()->with('success', 'Đã xóa tài khoản.');
    }
}
