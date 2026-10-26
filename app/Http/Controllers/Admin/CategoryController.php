<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')->with(['children' => function ($q) { $q->withCount('books'); }])
            ->withCount('books')->orderBy('sort_order')->get();
        $parents = Category::whereNull('parent_id')->orderBy('sort_order')->get();
        return view('admin.categories.index', compact('categories', 'parents'));
    }

    protected function data(Request $request, Category $category = null)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => ['nullable', 'string', 'max:120', Rule::unique('categories', 'slug')->ignore(optional($category)->id)],
            'parent_id'   => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'nullable|integer',
        ], [], ['name' => 'tên thể loại', 'parent_id' => 'thể loại cha']);
        $data['slug'] = !empty($data['slug']) ? $data['slug'] : vn_slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        if ($category && isset($data['parent_id']) && $data['parent_id'] == $category->id) {
            $data['parent_id'] = null;
        }
        return $data;
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        if (Category::where('slug', $data['slug'])->exists()) {
            return back()->withInput()->with('error', 'Slug "' . $data['slug'] . '" đã tồn tại.');
        }
        Category::create($data);
        return back()->with('success', 'Đã thêm thể loại.');
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->data($request, $category));
        return back()->with('success', 'Đã cập nhật thể loại.');
    }

    public function destroy(Category $category)
    {
        if ($category->books()->withTrashed()->exists() || $category->children()->exists()) {
            return back()->with('error', 'Không thể xóa: thể loại đang có sách hoặc thể loại con.');
        }
        $category->delete();
        return back()->with('success', 'Đã xóa thể loại.');
    }
}
