<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** Trang chủ, danh sách sách và trang đăng nhập phải mở được (cần đã migrate + seed). */
    public function test_cac_trang_chinh_mo_duoc()
    {
        $this->get('/')->assertStatus(200);
        $this->get('/sach')->assertStatus(200);
        $this->get('/dang-nhap')->assertStatus(200);
    }

    public function test_trang_admin_yeu_cau_dang_nhap()
    {
        $this->get('/admin')->assertRedirect('/dang-nhap');
    }
}
