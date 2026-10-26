<div class="sb-steps">
    @foreach([['Giỏ hàng', 'Kiểm tra sản phẩm'], ['Thanh toán', 'Địa chỉ & phương thức'], ['Hoàn tất', 'Theo dõi đơn hàng']] as $i => $s)
        <div class="sb-step {{ $i + 1 < $step ? 'xong' : ($i + 1 == $step ? 'hien-tai' : '') }}">
            <span class="so">@if($i + 1 < $step)<i class="fas fa-check"></i>@else{{ $i + 1 }}@endif</span>
            <div><strong>{{ $s[0] }}</strong><small>{{ $s[1] }}</small></div>
        </div>
    @endforeach
</div>
