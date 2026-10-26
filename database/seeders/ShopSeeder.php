<?php

namespace Database\Seeders;

use App\Models\BookVariant;
use App\Models\FlashSale;
use App\Models\KnowledgeDoc;
use App\Models\Voucher;
use Illuminate\Database\Seeder;

/** Voucher, Flash Sale, kho tri thức cho chatbot RAG */
class ShopSeeder extends Seeder
{
    public function run()
    {
        $vouchers = [
            ['MOTSACH10', 'Chào bạn mới – giảm 10%', 'percent', 10, 30000, 0, null, 1],
            ['SACHHAY50K', 'Giảm 50K đơn từ 300K', 'fixed', 50000, null, 300000, 200, 2],
            ['MANGA15', 'Fan truyện tranh – giảm 15%', 'percent', 15, 40000, 100000, 100, 3],
            ['FREESHIP', 'Hỗ trợ phí ship 30K', 'fixed', 30000, null, 150000, null, 5],
            ['BLACKFRIDAY', 'Black Friday giảm 25%', 'percent', 25, 100000, 200000, 50, 1],
        ];
        foreach ($vouchers as $i => $v) {
            Voucher::create([
                'code' => $v[0], 'name' => $v[1], 'type' => $v[2], 'value' => $v[3], 'max_discount' => $v[4],
                'min_order' => $v[5], 'usage_limit' => $v[6], 'per_user_limit' => $v[7],
                'starts_at' => $v[0] === 'BLACKFRIDAY' ? now()->addDays(25) : now()->subMonths(11),
                'ends_at' => $v[0] === 'BLACKFRIDAY' ? now()->addDays(30) : now()->addMonths(3),
                'is_active' => true,
            ]);
        }
        Voucher::create(['code' => 'HE2026', 'name' => 'Ưu đãi hè (đã hết hạn)', 'type' => 'percent', 'value' => 20,
                         'max_discount' => 50000, 'min_order' => 0, 'per_user_limit' => 1,
                         'starts_at' => now()->subMonths(5), 'ends_at' => now()->subMonths(3), 'is_active' => true]);

        // Flash Sale đang diễn ra (để demo) + một đợt sắp diễn ra
        $running = FlashSale::create(['name' => 'Siêu sale cuối tháng', 'starts_at' => now()->subHours(2), 'ends_at' => now()->addDays(3)->setTime(23, 59), 'is_active' => true]);
        $upcoming = FlashSale::create(['name' => 'Flash Sale 11.11', 'starts_at' => now()->addDays(10)->setTime(0, 0), 'ends_at' => now()->addDays(10)->setTime(23, 59), 'is_active' => true]);
        $picks = ['mat-biec', 'dac-nhan-tam', 'one-piece-tap-105', 'tham-tu-lung-danh-conan-tap-100', 'nha-gia-kim', 'spy-x-family-tap-1', 'cha-giau-cha-ngheo', 'hoang-tu-be'];
        foreach ($picks as $i => $slug) {
            $v = BookVariant::whereHas('book', function ($q) use ($slug) { $q->where('slug', $slug); })->orderBy('price')->first();
            if (!$v || $v->stock <= 0) continue;
            $sale = $i < 6 ? $running : $upcoming;
            $sale->items()->create(['variant_id' => $v->id, 'sale_price' => (int) (round($v->price * 0.7 / 1000) * 1000),
                                    'quantity' => 30, 'sold' => $sale === $running ? mt_rand(5, 22) : 0]);
        }

        $docs = [
            ['Chính sách đổi trả hàng', 'policy', "BenBooks hỗ trợ đổi trả trong vòng 7 ngày kể từ khi khách nhận hàng.\nSách được đổi trả khi bị lỗi in, thiếu trang, rách, ẩm mốc hoặc giao nhầm sản phẩm.\nSách cần còn nguyên tem, không có dấu hiệu viết vẽ hoặc hư hỏng do người dùng.\nKhách tạo yêu cầu đổi trả trong mục Đơn hàng của tôi, chọn sản phẩm và ghi rõ lý do.\nSau khi yêu cầu được duyệt và cửa hàng nhận lại sách, tiền sẽ được hoàn trong 3 đến 5 ngày làm việc.\nPhí vận chuyển đổi trả do BenBooks chi trả nếu lỗi thuộc về cửa hàng."],
            ['Chính sách giao hàng', 'policy', "BenBooks giao hàng toàn quốc qua BenBooks Express, Giao Hàng Nhanh, Giao Hàng Tiết Kiệm và Viettel Post.\nPhí ship nội thành TP.HCM là 20.000đ, các tỉnh lân cận như Bình Dương, Đồng Nai, Long An là 25.000đ, các tỉnh thành khác là 35.000đ.\nMiễn phí giao hàng cho đơn hàng từ 300.000đ trở lên.\nThời gian giao hàng nội thành 1 đến 2 ngày, các tỉnh khác 3 đến 5 ngày làm việc.\nKhách có thể theo dõi đơn hàng bằng mã vận đơn hoặc tại trang Tra cứu đơn hàng."],
            ['Phương thức thanh toán', 'policy', "BenBooks hỗ trợ thanh toán khi nhận hàng (COD) và thanh toán online qua thẻ ATM, thẻ Visa/Master, ví điện tử.\nVới thanh toán online, đơn hàng được xác nhận ngay sau khi giao dịch thành công.\nNếu thanh toán online thất bại, khách có thể thanh toán lại trong trang chi tiết đơn hàng.\nĐơn hàng đã thanh toán online nếu bị hủy sẽ được hoàn tiền trong 3 đến 7 ngày làm việc."],
            ['Hủy đơn hàng', 'policy', "Khách hàng có thể tự hủy đơn khi đơn đang ở trạng thái Chờ xác nhận hoặc Đã xác nhận.\nKhi đơn đã chuyển sang Đang giao thì không thể hủy, khách vui lòng từ chối nhận hàng hoặc liên hệ hotline.\nMã giảm giá đã dùng cho đơn bị hủy sẽ được hoàn lại lượt sử dụng."],
            ['Mã giảm giá và Flash Sale', 'guide', "Mã giảm giá được nhập ở trang Giỏ hàng, mỗi đơn chỉ áp dụng một mã.\nMỗi mã có điều kiện đơn tối thiểu, số lượt dùng và thời hạn riêng.\nFlash Sale là chương trình giảm giá sâu trong thời gian ngắn với số suất giới hạn, giá flash sale được áp dụng tự động khi thêm vào giỏ.\nCó thể dùng mã giảm giá cùng với sản phẩm Flash Sale."],
            ['Tài khoản và bảo mật', 'guide', "Khách hàng đăng ký tài khoản bằng email và số điện thoại để đặt hàng và theo dõi đơn.\nThông tin cá nhân của khách hàng được bảo mật và chỉ dùng cho việc giao hàng, chăm sóc khách hàng.\nKhách có thể đổi mật khẩu, cập nhật thông tin và quản lý sổ địa chỉ trong trang Tài khoản.\nMật khẩu được mã hóa một chiều, nhân viên cửa hàng không thể xem mật khẩu của khách."],
            ['Giới thiệu BenBooks', 'about', "BenBooks là nhà sách và truyện tranh trực tuyến với hàng trăm đầu sách văn học, kinh tế, kỹ năng, thiếu nhi, khoa học và truyện tranh manga, manhwa, manhua, comic.\nĐịa chỉ cửa hàng: 140 Lê Trọng Tấn, phường Tây Thạnh, quận Tân Phú, TP.HCM.\nGiờ mở cửa từ 8 giờ đến 21 giờ tất cả các ngày trong tuần.\nHotline hỗ trợ 1900 6868, email hotro@motsach.vn."],
            ['Câu hỏi thường gặp', 'faq', "Sách tại BenBooks là sách chính hãng từ các nhà xuất bản và công ty phát hành uy tín.\nKhách có thể đặt hàng không cần tài khoản không? Khách cần đăng nhập để đặt hàng, nhưng có thể thêm sách vào giỏ trước khi đăng nhập.\nTích điểm thành viên: khách hàng thân thiết và VIP được tặng mã giảm giá riêng theo phân hạng.\nSách bản đặc biệt thường kèm quà tặng như postcard, bookmark hoặc bìa áo giới hạn."],
        ];
        foreach ($docs as $d) {
            KnowledgeDoc::create(['title' => $d[0], 'category' => $d[1], 'content' => $d[2], 'is_active' => true]);
        }
    }
}
