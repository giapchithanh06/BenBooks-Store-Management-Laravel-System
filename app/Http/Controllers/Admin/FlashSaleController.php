<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookVariant;
use App\Models\FlashSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FlashSaleController extends Controller
{
    public function index()
    {
        $sales = FlashSale::with('items')->latest('starts_at')->paginate(15);
        return view('admin.flash-sales.index', compact('sales'));
    }

    protected function variants()
    {
        return BookVariant::with('book')->whereHas('book', function ($q) { $q->where('is_active', true); })->get()
            ->sortBy(function ($v) { return $v->book->title; });
    }

    public function create()
    {
        $flashSale = new FlashSale(['is_active' => true, 'starts_at' => now()->startOfHour(), 'ends_at' => now()->startOfHour()->addHours(6)]);
        return view('admin.flash-sales.form', ['flashSale' => $flashSale, 'variants' => $this->variants()]);
    }

    public function edit(FlashSale $flashSale)
    {
        $flashSale->load('items');
        return view('admin.flash-sales.form', ['flashSale' => $flashSale, 'variants' => $this->variants()]);
    }

    protected function save(Request $request, FlashSale $flashSale)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:150',
            'starts_at'          => 'required|date',
            'ends_at'            => 'required|date|after:starts_at',
            'items'              => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:book_variants,id|distinct',
            'items.*.sale_price' => 'required|integer|min:1000',
            'items.*.quantity'   => 'required|integer|min:1',
        ], ['items.*.variant_id.distinct' => 'Mỗi sản phẩm chỉ được thêm 1 lần.'],
           ['name' => 'tên chương trình', 'ends_at' => 'thời gian kết thúc', 'items' => 'sản phẩm', 'items.*.sale_price' => 'giá sale']);

        foreach ($data['items'] as $row) {
            $v = BookVariant::find($row['variant_id']);
            if ($row['sale_price'] >= $v->price) {
                return 'Giá flash sale của "' . optional($v->book)->title . ' – ' . $v->name . '" phải thấp hơn giá bán ' . money($v->price) . '.';
            }
        }

        DB::transaction(function () use ($request, $flashSale, $data) {
            $flashSale->fill(['name' => $data['name'], 'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'],
                              'is_active' => $request->boolean('is_active')])->save();
            $keep = [];
            foreach ($data['items'] as $row) {
                $item = $flashSale->items()->firstOrNew(['variant_id' => $row['variant_id']]);
                $item->sale_price = $row['sale_price'];
                $item->quantity = $row['quantity'];
                $item->save();
                $keep[] = $item->id;
            }
            $flashSale->items()->whereNotIn('id', $keep)->delete();
        });
        return null;
    }

    public function store(Request $request)
    {
        $fs = new FlashSale();
        if ($err = $this->save($request, $fs)) return back()->withInput()->with('error', $err);
        return redirect()->route('admin.flash-sales.index')->with('success', 'Đã tạo Flash Sale.');
    }

    public function update(Request $request, FlashSale $flashSale)
    {
        if ($err = $this->save($request, $flashSale)) return back()->withInput()->with('error', $err);
        return redirect()->route('admin.flash-sales.index')->with('success', 'Đã cập nhật Flash Sale.');
    }

    public function destroy(FlashSale $flashSale)
    {
        if ($flashSale->items()->where('sold', '>', 0)->exists()) {
            $flashSale->update(['is_active' => false]);
            return back()->with('success', 'Flash Sale đã có đơn nên chỉ bị tắt.');
        }
        $flashSale->delete();
        return back()->with('success', 'Đã xóa Flash Sale.');
    }
}
