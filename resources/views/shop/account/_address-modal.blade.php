<div class="modal fade sb-modal" id="{{ $id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="{{ $action }}">
        @csrf
        @if($method !== 'POST') @method($method) @endif
        <h3 class="mb-3">{{ $address ? 'Sửa địa chỉ' : 'Thêm địa chỉ mới' }}</h3>
        <div class="row">
            <div class="col-md-6 sb-field"><label>Người nhận <span class="req">*</span></label><input class="sb-control" name="receiver_name" value="{{ optional($address)->receiver_name ?? auth()->user()->name }}" required></div>
            <div class="col-md-6 sb-field"><label>Số điện thoại <span class="req">*</span></label><input class="sb-control" name="phone" value="{{ optional($address)->phone ?? auth()->user()->phone }}" required pattern="0[0-9]{9}"></div>
            <div class="col-md-4 sb-field"><label>Tỉnh/Thành <span class="req">*</span></label><input class="sb-control" name="province" value="{{ optional($address)->province }}" required></div>
            <div class="col-md-4 sb-field"><label>Quận/Huyện <span class="req">*</span></label><input class="sb-control" name="district" value="{{ optional($address)->district }}" required></div>
            <div class="col-md-4 sb-field"><label>Phường/Xã <span class="req">*</span></label><input class="sb-control" name="ward" value="{{ optional($address)->ward }}" required></div>
            <div class="col-12 sb-field"><label>Số nhà, đường <span class="req">*</span></label><input class="sb-control" name="detail" value="{{ optional($address)->detail }}" required></div>
            @if(!$address)<div class="col-12 mb-2"><label class="sb-check"><input type="checkbox" name="is_default" value="1"> Đặt làm địa chỉ mặc định</label></div>@endif
        </div>
        <div class="sb-modal-actions justify-content-end"><button type="button" class="sb-btn sb-btn-outline" data-bs-dismiss="modal">Hủy</button><button class="sb-btn"><i class="fas fa-save"></i> Lưu</button></div>
    </form></div>
</div>
