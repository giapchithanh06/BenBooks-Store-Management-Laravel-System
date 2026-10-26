<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function index(Request $request)
    {
        $authors = Author::withCount('books')
            ->when($request->q, function ($q, $kw) { $q->where('name', 'like', "%$kw%"); })
            ->orderBy('name')->paginate(20)->withQueryString();
        return view('admin.authors.index', compact('authors'));
    }

    protected function data(Request $request, Author $author = null)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'bio' => 'nullable|string|max:2000'], [], ['name' => 'tên tác giả', 'bio' => 'tiểu sử']);
        $slug = vn_slug($data['name']); $base = $slug; $i = 2;
        while (Author::where('slug', $slug)->where('id', '!=', optional($author)->id ?: 0)->exists()) $slug = $base . '-' . $i++;
        return $data + ['slug' => $slug];
    }

    public function store(Request $request)
    {
        Author::create($this->data($request));
        return back()->with('success', 'Đã thêm tác giả.');
    }

    public function update(Request $request, Author $author)
    {
        $author->update($this->data($request, $author));
        return back()->with('success', 'Đã cập nhật tác giả.');
    }

    public function destroy(Author $author)
    {
        if ($author->books()->exists()) {
            return back()->with('error', 'Không thể xóa: tác giả đang có sách.');
        }
        $author->delete();
        return back()->with('success', 'Đã xóa tác giả.');
    }
}
