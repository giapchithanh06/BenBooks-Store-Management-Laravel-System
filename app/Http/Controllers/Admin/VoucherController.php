<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::withCount('orders')->latest()->paginate(20);
        return view('admin.vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        return view('admin.vouchers.form', ['voucher' => new Voucher(['type' => 'percent', 'is_active' => true, 'per_user_limit' => 1, 'min_order' => 0])]);
    }

    public function edit(Voucher $voucher)
    {
        return view('admin.vouchers.form', compact('voucher'));
    }

    protected function data(Request $request, Voucher $voucher = null)
    {
        $data = $request->validate([
            'code'           => ['required', 'alpha_dash', 'max:50', Rule::unique('vouchers', 'code')->ignore(optional($voucher)->id)],
            'name'           => 'required|string|max:150',
            'type'           => 'required|in:percent,fixed',
            'value'          => 'required|integer|min:1' . ($request->type === 'percent' ? '|max:100' : ''),
            'max_discount'   => 'nullable|integer|min:0',
            'min_order'      => 'nullable|integer|min:0',
            'usage_limit'    => 'nullable|integer|min:1',
            'per_user_limit' => 'required|integer|min:1',
            'starts_at'      => 'nullable|date',
            'ends_at'        => 'nullable|date|after_or_equal:starts_at',
        ], [], ['code' => 'mã', 'name' => 'tên chương trình', 'value' => 'giá trị', 'ends_at' => 'ngày kết thúc', 'starts_at' => 'ngày bắt đầu']);
        $data['code'] = strtoupper($data['code']);
        $data['min_order'] = $data['min_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }

    public function store(Request $request)
    {
        Voucher::create($this->data($request));
        return redirect()->route('admin.vouchers.index')->with('success', 'Đã tạo mã giảm giá.');
    }

    public function update(Request $request, Voucher $voucher)
    {
        $voucher->update($this->data($request, $voucher));
        return redirect()->route('admin.vouchers.index')->with('success', 'Đã cập nhật mã giảm giá.');
    }

    public function destroy(Voucher $voucher)
    {
        if ($voucher->orders()->exists()) {
            $voucher->update(['is_active' => false]);
            return back()->with('success', 'Mã đã được dùng nên chỉ bị khóa (giữ lịch sử).');
        }
        $voucher->delete();
        return back()->with('success', 'Đã xóa mã giảm giá.');
    }
}
