<div class="mb-2"><label class="form-label small">Tên *</label><input name="name" value="{{ optional($cat)->name }}" class="form-control form-control-sm" required></div>
<div class="mb-2"><label class="form-label small">Slug (để trống tự sinh)</label><input name="slug" value="{{ optional($cat)->slug }}" class="form-control form-control-sm"></div>
<div class="mb-2"><label class="form-label small">Thể loại cha</label>
    <select name="parent_id" class="form-select form-select-sm">
        <option value="">— Không (thể loại gốc) —</option>
        @foreach($parents as $p)
            @if(!$cat || $p->id != $cat->id)<option value="{{ $p->id }}" @if(optional($cat)->parent_id == $p->id) selected @endif>{{ $p->name }}</option>@endif
        @endforeach
    </select>
</div>
<div class="mb-2"><label class="form-label small">Mô tả</label><textarea name="description" rows="2" class="form-control form-control-sm">{{ optional($cat)->description }}</textarea></div>
<div class="mb-3"><label class="form-label small">Thứ tự</label><input type="number" name="sort_order" value="{{ optional($cat)->sort_order ?? 0 }}" class="form-control form-control-sm"></div>
