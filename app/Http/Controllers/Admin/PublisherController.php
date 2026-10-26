<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publisher;
use Illuminate\Http\Request;

class PublisherController extends Controller
{
    public function index(Request $request)
    {
        $publishers = Publisher::withCount('books')
            ->when($request->q, function ($q, $kw) { $q->where('name', 'like', "%$kw%"); })
            ->orderBy('name')->paginate(20)->withQueryString();
        return view('admin.publishers.index', compact('publishers'));
    }

    protected function data(Request $request, Publisher $publisher = null)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100', 'address' => 'nullable|string|max:200', 'website' => 'nullable|url|max:200',
        ], [], ['name' => 'tên NXB', 'address' => 'địa chỉ']);
        $slug = vn_slug($data['name']); $base = $slug; $i = 2;
        while (Publisher::where('slug', $slug)->where('id', '!=', optional($publisher)->id ?: 0)->exists()) $slug = $base . '-' . $i++;
        return $data + ['slug' => $slug];
    }

    public function store(Request $request)
    {
        Publisher::create($this->data($request));
        return back()->with('success', 'Đã thêm nhà xuất bản.');
    }

    public function update(Request $request, Publisher $publisher)
    {
        $publisher->update($this->data($request, $publisher));
        return back()->with('success', 'Đã cập nhật nhà xuất bản.');
    }

    public function destroy(Publisher $publisher)
    {
        if ($publisher->books()->withTrashed()->exists()) {
            return back()->with('error', 'Không thể xóa: NXB đang có sách.');
        }
        $publisher->delete();
        return back()->with('success', 'Đã xóa nhà xuất bản.');
    }
}
