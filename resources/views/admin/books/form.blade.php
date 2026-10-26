@extends('layouts.admin')
@section('title', $book->exists ? 'Sửa sách' : 'Thêm sách')

@section('content')
@php
    $variantRows = old('variants', $book->exists ? $book->variants->map(function ($v) {
        return ['id' => $v->id, 'name' => $v->name, 'sku' => $v->sku, 'price' => $v->price, 'original_price' => $v->original_price, 'stock' => $v->stock];
    })->all() : [['id' => '', 'name' => 'Bìa mềm', 'sku' => '', 'price' => '', 'original_price' => '', 'stock' => 0]]);
    $selectedAuthors = old('author_ids', $book->exists ? $book->authors->pluck('id')->all() : []);
@endphp
<form method="post" enctype="multipart/form-data" action="{{ $book->exists ? route('admin.books.update', $book) : route('admin.books.store') }}">
    @csrf
    @if($book->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card"><div class="card-body row g-3">
                <div class="col-12"><label class="form-label">Tên sách *</label><input name="title" value="{{ old('title', $book->title) }}" class="form-control" required></div>
                <div class="col-md-6">
                    <label class="form-label">Thể loại *</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Chọn --</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @if(old('category_id', $book->category_id) == $c->id) selected @endif>{{ $c->parent ? $c->parent->name . ' › ' : '' }}{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nhà xuất bản</label>
                    <select name="publisher_id" class="form-select">
                        <option value="">-- Chọn --</option>
                        @foreach($publishers as $p)<option value="{{ $p->id }}" @if(old('publisher_id', $book->publisher_id) == $p->id) selected @endif>{{ $p->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Tác giả * <small class="text-muted">(giữ Ctrl để chọn nhiều)</small></label>
                    <select name="author_ids[]" class="form-select" multiple size="6" required>
                        @foreach($authors as $a)<option value="{{ $a->id }}" @if(in_array($a->id, $selectedAuthors)) selected @endif>{{ $a->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">ISBN</label><input name="isbn" value="{{ old('isbn', $book->isbn) }}" class="form-control" placeholder="978604xxxxxxx"></div>
                <div class="col-md-3"><label class="form-label">Năm XB</label><input type="number" name="publish_year" value="{{ old('publish_year', $book->publish_year) }}" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Số trang</label><input type="number" name="pages" value="{{ old('pages', $book->pages) }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Ngôn ngữ</label><input name="language" value="{{ old('language', $book->language) }}" class="form-control"></div>
                <div class="col-12"><label class="form-label">Mô tả</label><textarea name="description" rows="5" class="form-control">{{ old('description', $book->description) }}</textarea></div>
                <div class="col-12"><label class="form-label">Từ khóa <small class="text-muted">(phân cách bằng dấu phẩy – giúp tìm kiếm ngữ nghĩa & gợi ý chính xác hơn)</small></label><input name="tags" value="{{ old('tags', $book->tags) }}" class="form-control" placeholder="phiêu lưu, tình bạn, hài hước"></div>
            </div></div>

            <div class="card mt-3"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 fw-bold m-0">Phiên bản (biến thể) – giá & tồn kho</h2>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addVariant"><i class="fas fa-plus"></i> Thêm phiên bản</button>
                </div>
                <table class="table table-sm align-middle small mb-0">
                    <thead><tr><th>Tên (Bìa mềm, Bìa cứng…)</th><th>SKU</th><th>Giá bán</th><th>Giá bìa</th><th>Tồn kho</th><th></th></tr></thead>
                    <tbody id="variantRows">
                    @foreach($variantRows as $i => $v)
                        <tr>
                            <td><input type="hidden" name="variants[{{ $i }}][id]" value="{{ $v['id'] ?? '' }}"><input name="variants[{{ $i }}][name]" value="{{ $v['name'] ?? '' }}" class="form-control form-control-sm" required></td>
                            <td><input name="variants[{{ $i }}][sku]" value="{{ $v['sku'] ?? '' }}" class="form-control form-control-sm" placeholder="tự sinh"></td>
                            <td><input type="number" name="variants[{{ $i }}][price]" value="{{ $v['price'] ?? '' }}" class="form-control form-control-sm" required min="0" step="1000"></td>
                            <td><input type="number" name="variants[{{ $i }}][original_price]" value="{{ $v['original_price'] ?? '' }}" class="form-control form-control-sm" min="0" step="1000"></td>
                            <td><input type="number" name="variants[{{ $i }}][stock]" value="{{ $v['stock'] ?? 0 }}" class="form-control form-control-sm" min="0"></td>
                            <td><button type="button" class="btn btn-sm btn-link text-danger rm-variant"><i class="fas fa-times"></i></button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <p class="small text-muted mb-0 mt-2">Thay đổi tồn kho ở đây sẽ được ghi vào lịch sử kho (phiếu điều chỉnh). Nên dùng mục <em>Tồn kho → Nhập kho</em> cho nghiệp vụ nhập hàng.</p>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <label class="form-label">Ảnh bìa</label>
                <div class="text-center mb-2"><img id="coverPreview" src="{{ $book->cover_url }}" style="max-height:260px" class="img-fluid rounded shadow-sm" alt=""></div>
                <input type="file" name="cover" accept="image/*" class="form-control form-control-sm" onchange="if(this.files[0]) document.getElementById('coverPreview').src = URL.createObjectURL(this.files[0])">
                <hr>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="act" @if(old('is_active', $book->is_active)) checked @endif><label class="form-check-label" for="act">Đang bán</label></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_featured" value="1" id="feat" @if(old('is_featured', $book->is_featured)) checked @endif><label class="form-check-label" for="feat">Sách nổi bật (trang chủ)</label></div>
                <hr>
                <button class="btn btn-primary w-100"><i class="fas fa-save"></i> Lưu</button>
                <a href="{{ route('admin.books.index') }}" class="btn btn-link w-100">Hủy</a>
            </div></div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var tbody = document.getElementById('variantRows'), idx = {{ count($variantRows) + 10 }};
    document.getElementById('addVariant').addEventListener('click', function () {
        var i = idx++, tr = document.createElement('tr');
        tr.innerHTML = '<td><input type="hidden" name="variants[' + i + '][id]" value=""><input name="variants[' + i + '][name]" class="form-control form-control-sm" required></td>' +
            '<td><input name="variants[' + i + '][sku]" class="form-control form-control-sm" placeholder="tự sinh"></td>' +
            '<td><input type="number" name="variants[' + i + '][price]" class="form-control form-control-sm" required min="0" step="1000"></td>' +
            '<td><input type="number" name="variants[' + i + '][original_price]" class="form-control form-control-sm" min="0" step="1000"></td>' +
            '<td><input type="number" name="variants[' + i + '][stock]" value="0" class="form-control form-control-sm" min="0"></td>' +
            '<td><button type="button" class="btn btn-sm btn-link text-danger rm-variant"><i class="fas fa-times"></i></button></td>';
        tbody.appendChild(tr);
    });
    tbody.addEventListener('click', function (e) {
        var b = e.target.closest('.rm-variant');
        if (b && tbody.rows.length > 1) b.closest('tr').remove();
    });
})();
</script>
@endpush
