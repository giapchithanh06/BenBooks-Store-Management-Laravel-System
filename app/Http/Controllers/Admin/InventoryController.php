<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookVariant;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Quản lý tồn kho: xem tồn theo biến thể, nhập/xuất/điều chỉnh kho, lịch sử biến động.
 */
class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $q = BookVariant::with('book.category')->whereHas('book');
        if ($kw = $request->input('q')) {
            $q->where(function ($w) use ($kw) {
                $w->where('sku', 'like', "%$kw%")->orWhereHas('book', function ($b) use ($kw) { $b->where('title', 'like', "%$kw%"); });
            });
        }
        $filter = $request->input('filter');
        if ($filter === 'out') $q->where('stock', '<=', 0);
        if ($filter === 'low') $q->whereBetween('stock', [1, 10]);

        $summary = [
            'variants'  => BookVariant::count(),
            'units'     => BookVariant::sum('stock'),
            'value'     => BookVariant::sum(DB::raw('stock * price')),
            'out'       => BookVariant::where('stock', '<=', 0)->count(),
            'low'       => BookVariant::whereBetween('stock', [1, 10])->count(),
        ];
        return view('admin.inventory.index', [
            'variants' => $q->orderBy('stock')->paginate(20)->withQueryString(),
            'summary'  => $summary,
            'filter'   => $filter,
        ]);
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|exists:book_variants,id',
            'type'       => 'required|in:import,export,adjust',
            'quantity'   => 'required|integer|min:1|max:100000',
            'note'       => 'nullable|string|max:200',
        ], [], ['quantity' => 'số lượng', 'type' => 'loại phiếu']);

        $variant = BookVariant::with('book')->findOrFail($data['variant_id']);
        $qty = $data['type'] === 'export' ? -$data['quantity'] : $data['quantity'];
        if ($data['type'] === 'adjust') {
            $qty = $data['quantity'] - $variant->stock; // điều chỉnh về đúng số lượng kiểm kê
        }
        if ($variant->stock + $qty < 0) {
            return back()->with('error', 'Số lượng xuất vượt quá tồn kho hiện tại (' . $variant->stock . ').');
        }
        $variant->changeStock($qty, $data['type'], $data['note'] ?? null, Auth::id());
        return back()->with('success', StockMovement::TYPES[$data['type']] . ' "' . $variant->book->title . ' – ' . $variant->name . '": tồn mới ' . $variant->stock . '.');
    }

    public function history(Request $request)
    {
        $movements = StockMovement::with(['variant.book', 'user'])
            ->when($request->type, function ($q, $t) { $q->where('type', $t); })
            ->when($request->variant_id, function ($q, $v) { $q->where('variant_id', $v); })
            ->latest()->paginate(30)->withQueryString();
        return view('admin.inventory.history', compact('movements'));
    }
}
