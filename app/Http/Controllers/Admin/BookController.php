<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RefreshAiData;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookVariant;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $q = Book::with(['category', 'authors', 'variants', 'publisher']);
        if ($kw = $request->input('q')) {
            $q->where(function ($w) use ($kw) { $w->where('title', 'like', "%$kw%")->orWhere('isbn', 'like', "%$kw%"); });
        }
        if ($cat = $request->input('category_id')) {
            $q->whereIn('category_id', optional(Category::find($cat))->descendantIds() ?: [$cat]);
        }
        if ($request->input('status') === 'hidden') $q->where('is_active', false);
        if ($request->input('status') === 'active') $q->where('is_active', true);
        if ($request->input('status') === 'trashed') $q->onlyTrashed();

        return view('admin.books.index', [
            'books'      => $q->latest('id')->paginate(15)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    protected function formData()
    {
        return [
            'categories' => Category::with('parent')->orderBy('parent_id')->orderBy('sort_order')->get(),
            'authors'    => Author::orderBy('name')->get(),
            'publishers' => Publisher::orderBy('name')->get(),
        ];
    }

    public function create()
    {
        return view('admin.books.form', ['book' => new Book(['is_active' => true, 'language' => 'Tiếng Việt'])] + $this->formData());
    }

    public function edit(Book $book)
    {
        $book->load(['authors', 'variants']);
        return view('admin.books.form', compact('book') + $this->formData());
    }

    protected function validated(Request $request, Book $book = null)
    {
        return $request->validate([
            'title'          => 'required|string|max:200',
            'category_id'    => 'required|exists:categories,id',
            'publisher_id'   => 'nullable|exists:publishers,id',
            'author_ids'     => 'required|array|min:1',
            'author_ids.*'   => 'exists:authors,id',
            'isbn'           => ['nullable', 'regex:/^[0-9\-Xx]{10,17}$/', Rule::unique('books', 'isbn')->ignore(optional($book)->id)],
            'publish_year'   => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'pages'          => 'nullable|integer|min:1|max:10000',
            'language'       => 'nullable|string|max:50',
            'description'    => 'nullable|string',
            'tags'           => 'nullable|string|max:250',
            'cover'          => 'nullable|image|max:2048',
            'variants'               => 'required|array|min:1',
            'variants.*.id'          => 'nullable|integer',
            'variants.*.name'        => 'required|string|max:100',
            'variants.*.sku'         => 'nullable|string|max:50',
            'variants.*.price'       => 'required|integer|min:0',
            'variants.*.original_price' => 'nullable|integer|min:0',
            'variants.*.stock'       => 'nullable|integer|min:0',
        ], ['isbn.regex' => 'ISBN chỉ gồm 10–17 ký tự số, dấu gạch ngang hoặc X.'], [
            'title' => 'tên sách', 'category_id' => 'thể loại', 'author_ids' => 'tác giả', 'publish_year' => 'năm xuất bản',
            'pages' => 'số trang', 'cover' => 'ảnh bìa', 'variants' => 'phiên bản', 'variants.*.name' => 'tên phiên bản',
            'variants.*.price' => 'giá bán',
        ]);
    }

    protected function save(Request $request, Book $book)
    {
        $data = $this->validated($request, $book->exists ? $book : null);

        DB::transaction(function () use ($request, $book, $data) {
            $book->fill(collect($data)->only(['title', 'category_id', 'publisher_id', 'isbn', 'publish_year', 'pages',
                                              'language', 'description', 'tags'])->all());
            $book->is_active = $request->boolean('is_active');
            $book->is_featured = $request->boolean('is_featured');
            if (!$book->slug || $book->isDirty('title')) {
                $slug = vn_slug($data['title']);
                $base = $slug; $i = 2;
                while (Book::withTrashed()->where('slug', $slug)->where('id', '!=', $book->id ?: 0)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $book->slug = $slug;
            }
            if ($request->hasFile('cover')) {
                $file = $request->file('cover');
                $name = $book->slug . '-' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/books'), $name);
                $book->cover_image = 'uploads/books/' . $name;
            }
            $book->save();
            $book->authors()->sync($data['author_ids']);

            // Biến thể: cập nhật / thêm / xóa
            $keep = [];
            foreach ($data['variants'] as $row) {
                $variant = !empty($row['id']) ? BookVariant::where('book_id', $book->id)->find($row['id']) : null;
                $stock = (int) ($row['stock'] ?? 0);
                $attrs = [
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'original_price' => $row['original_price'] ?? null,
                    'sku' => !empty($row['sku']) ? $row['sku'] : strtoupper('MS' . $book->id . '-' . Str::random(4)),
                ];
                if ($variant) {
                    $variant->update($attrs);
                    if ($stock != $variant->stock) {
                        $variant->changeStock($stock - $variant->stock, 'adjust', 'Điều chỉnh khi sửa sách', Auth::id());
                    }
                } else {
                    $variant = $book->variants()->create($attrs + ['stock' => 0]);
                    if ($stock > 0) $variant->changeStock($stock, 'import', 'Tồn đầu kỳ', Auth::id());
                }
                $keep[] = $variant->id;
            }
            $book->variants()->whereNotIn('id', $keep)->get()->each(function ($v) {
                $v->delete();
            });
        });

        RefreshAiData::dispatch('search_index')->onConnection('database');
    }

    public function store(Request $request)
    {
        $book = new Book();
        $this->save($request, $book);
        return redirect()->route('admin.books.index')->with('success', 'Đã thêm sách "' . $book->title . '".');
    }

    public function update(Request $request, Book $book)
    {
        $this->save($request, $book);
        return redirect()->route('admin.books.index')->with('success', 'Đã cập nhật sách "' . $book->title . '".');
    }

    public function destroy(Book $book)
    {
        $book->delete(); // xóa mềm – vẫn giữ lịch sử đơn hàng
        RefreshAiData::dispatch('search_index')->onConnection('database');
        return back()->with('success', 'Đã xóa sách "' . $book->title . '".');
    }
}
