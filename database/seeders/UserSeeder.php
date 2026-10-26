<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Tài khoản demo (mật khẩu chung: 123456) – tên khách hàng là tên giả lập */
class UserSeeder extends Seeder
{
    public function run()
    {
        $pass = Hash::make('123456');
        $staff = [
            ['Quản Trị Viên', 'admin@motsach.vn', '0901000001', 'admin'],
            ['Trần Thị Nhân Viên', 'staff@motsach.vn', '0901000002', 'staff'],
            ['Lê Văn Giao', 'shipper@motsach.vn', '0901000003', 'shipper'],
            ['Phạm Minh Tốc', 'shipper2@motsach.vn', '0901000004', 'shipper'],
            ['Nguyễn Văn Khách', 'khach@motsach.vn', '0912345678', 'customer'],
        ];
        foreach ($staff as $s) {
            $u = new User(['name' => $s[0], 'email' => $s[1], 'phone' => $s[2], 'role' => $s[3], 'password' => $pass]);
            $u->created_at = now()->subMonths(12);
            $u->save();
        }

        $ho = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương'];
        $lot = ['Văn', 'Thị', 'Minh', 'Ngọc', 'Thanh', 'Hoàng', 'Gia', 'Quốc', 'Thu', 'Bảo'];
        $ten = ['An', 'Bình', 'Châu', 'Dũng', 'Giang', 'Hà', 'Hải', 'Hạnh', 'Hiếu', 'Hoa', 'Huy', 'Khánh', 'Lan', 'Linh', 'Long',
                'Mai', 'Nam', 'Ngân', 'Nhi', 'Phúc', 'Quân', 'Quỳnh', 'Sơn', 'Tâm', 'Thảo', 'Trang', 'Trí', 'Tuấn', 'Vy', 'Yến'];
        mt_srand(2026);
        for ($i = 1; $i <= 40; $i++) {
            $name = $ho[mt_rand(0, count($ho) - 1)] . ' ' . $lot[mt_rand(0, count($lot) - 1)] . ' ' . $ten[($i * 7) % count($ten)];
            $u = new User(['name' => $name, 'email' => 'khach' . $i . '@motsach.vn', 'phone' => '09' . str_pad((string) (20000000 + $i * 137), 8, '0', STR_PAD_LEFT),
                           'role' => 'customer', 'password' => $pass]);
            $u->created_at = now()->subDays(mt_rand(20, 360));
            $u->save();
        }
    }
}
