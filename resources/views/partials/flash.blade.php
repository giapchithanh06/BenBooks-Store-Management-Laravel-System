@if(session('success'))
    <div class="sb-alert ok"><i class="fas fa-check-circle mt-1"></i><div>{{ session('success') }}</div><button class="x" type="button" aria-label="Đóng">&times;</button></div>
@endif
@if(session('error'))
    <div class="sb-alert loi"><i class="fas fa-exclamation-circle mt-1"></i><div>{{ session('error') }}</div><button class="x" type="button" aria-label="Đóng">&times;</button></div>
@endif
@if($errors->any())
    <div class="sb-alert loi"><i class="fas fa-exclamation-triangle mt-1"></i>
        <div>Vui lòng kiểm tra lại thông tin:<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        <button class="x" type="button" aria-label="Đóng">&times;</button>
    </div>
@endif
