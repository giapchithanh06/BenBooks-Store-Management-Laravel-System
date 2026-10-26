<div class="modal fade" id="{{ $id }}" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}">
    @csrf
    @if($user) @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title">{{ $user ? 'Sửa tài khoản' : 'Tạo tài khoản' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-start">
        <div class="mb-2"><label class="form-label small">Họ tên</label><input name="name" value="{{ optional($user)->name }}" class="form-control" required></div>
        <div class="mb-2"><label class="form-label small">Email</label><input type="email" name="email" value="{{ optional($user)->email }}" class="form-control" required></div>
        <div class="mb-2"><label class="form-label small">SĐT</label><input name="phone" value="{{ optional($user)->phone }}" class="form-control"></div>
        <div class="mb-2"><label class="form-label small">Vai trò</label>
            <select name="role" class="form-select">@foreach(\App\Models\User::ROLES as $k => $l)<option value="{{ $k }}" @if(optional($user)->role == $k) selected @endif>{{ $l }}</option>@endforeach</select>
        </div>
        <div class="mb-2"><label class="form-label small">Mật khẩu {{ $user ? '(để trống nếu không đổi)' : '' }}</label><input type="password" name="password" class="form-control" @if(!$user) required @endif minlength="6"></div>
        @if($user)<div class="form-check form-switch"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="{{ $id }}act" @if($user->is_active) checked @endif><label class="form-check-label" for="{{ $id }}act">Đang hoạt động</label></div>@endif
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Lưu</button></div>
</form></div></div>
