<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Phân quyền theo vai trò. Dùng: ->middleware('role:admin,staff')
 */
class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Tài khoản đã bị khóa.']);
        }
        if (!in_array($user->role, $roles)) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }
        return $next($request);
    }
}
