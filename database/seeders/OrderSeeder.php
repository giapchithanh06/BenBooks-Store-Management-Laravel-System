<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Voucher;
use App\Services\CartService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sinh dữ liệu lịch sử ~11 tháng: đơn hàng, đánh giá, hành vi người dùng, wishlist
 * để các chức năng báo cáo / dự báo / phân cụm / gợi ý / phát hiện bất thường có dữ liệu demo.
 */
class OrderSeeder extends Seeder
{
    protected $locations = [
        ['Hồ Chí Minh', 'Quận 1', 'Phường Bến Nghé', '12 Nguyễn Huệ'],
        ['Hồ Chí Minh', 'Quận Tân Phú', 'Phường Tây Thạnh', '140 Lê Trọng Tấn'],
        ['Hồ Chí Minh', 'Quận Gò Vấp', 'Phường 10', '45 Quang Trung'],
        ['Hà Nội', 'Quận Cầu Giấy', 'Phường Dịch Vọng', '88 Xuân Thủy'],
        ['Bình Dương', 'TP. Thủ Dầu Một', 'Phường Phú Cường', '9 Yersin'],
        ['Đà Nẵng', 'Quận Hải Châu', 'Phường Thạch Thang', '21 Bạch Đằng'],
        ['Cần Thơ', 'Quận Ninh Kiều', 'Phường An Hòa', '3 Mậu Thân'],
        ['Đồng Nai', 'TP. Biên Hòa', 'Phường Tân Mai', '17 Phạm Văn Thuận'],
    ];

    const REVIEWS = [
        5 => ['Sách rất hay, đọc một mạch không dừng được. Đóng gói cẩn thận, giao hàng nhanh!', 'Tuyệt vời, nội dung ý nghĩa, giấy đẹp, rất đáng mua.',
              'Truyện hay xuất sắc, nét vẽ đẹp, in rõ nét. Sẽ ủng hộ shop dài dài.', 'Mua tặng em, bé rất thích. Bìa đẹp, giao nhanh.',
              'Cuốn sách thay đổi cách mình suy nghĩ, rất bổ ích. Đáng đọc!'],
        4 => ['Nội dung hay, giấy ổn. Giao hàng hơi chậm một chút nhưng vẫn hài lòng.', 'Sách tốt, bản dịch dễ đọc, giá hợp lý.',
              'Truyện cuốn, nhân vật thú vị. Bìa hơi bị móp nhẹ nhưng không sao.', 'Hài lòng, đóng gói cẩn thận, sẽ mua tiếp tập sau.'],
        3 => ['Nội dung bình thường, không như kỳ vọng lắm nhưng giao hàng nhanh.', 'Sách ổn, giấy hơi mỏng.',
              'Truyện cũng được, giá hơi đắt so với số trang.'],
        2 => ['Giấy mỏng, in hơi mờ, giao hàng chậm. Hơi thất vọng.', 'Nội dung nhàm chán, không hay như review.',
              'Bìa bị rách góc, đóng gói sơ sài.'],
        1 => ['Sách bị lỗi in mất trang, rất thất vọng. Giao hàng trễ cả tuần.', 'Giao nhầm tập, bìa bị nhàu, chất lượng tệ.'],
    ];

    const KEYWORDS = ['one piece', 'conan', 'doraemon', 'nguyễn nhật ánh', 'sách kinh tế', 'đắc nhân tâm', 'manga', 'truyện tranh hành động',
                      'sách thiếu nhi', 'làm giàu', 'harari', 'solo leveling', 'kỹ năng giao tiếp', 'sách lịch sử', 'mắt biếc', 'spy x family'];

    public function run()
    {
        mt_srand(4242);
        $customers = User::where('role', 'customer')->orderBy('id')->get()->values();
        $shippers = User::where('role', 'shipper')->pluck('id')->all();
        $adminId = User::where('role', 'admin')->value('id');
        $books = Book::with(['variants', 'category'])->get();
        $byGroup = $books->groupBy(function ($b) { return $b->category->slug; });
        $groups = $byGroup->keys()->all();
        $vouchers = Voucher::whereIn('code', ['MOTSACH10', 'SACHHAY50K', 'MANGA15', 'FREESHIP'])->get();

        // Địa chỉ + sở thích (2 thể loại) cho từng khách
        $prefs = [];
        $addr = [];
        foreach ($customers as $i => $c) {
            $loc = $this->locations[$i % count($this->locations)];
            $addr[$c->id] = Address::create(['user_id' => $c->id, 'receiver_name' => $c->name, 'phone' => $c->phone,
                'province' => $loc[0], 'district' => $loc[1], 'ward' => $loc[2], 'detail' => $loc[3], 'is_default' => true]);
            $prefs[$c->id] = [$groups[$i % count($groups)], $groups[($i * 3 + 1) % count($groups)]];
        }

        $activities = [];
        $reviewed = [];
        $total = 380;
        for ($n = 0; $n < $total; $n++) {
            // càng gần hiện tại càng nhiều đơn (tăng trưởng)
            $daysAgo = (int) floor(330 * (1 - sqrt(mt_rand() / mt_getrandmax())));
            $created = now()->subDays($daysAgo)->setTime(mt_rand(7, 22), mt_rand(0, 59));
            if ($created->isFuture()) $created = now()->subMinutes(mt_rand(5, 300));
            // một số khách mua thường xuyên hơn (khách VIP)
            $c = $customers[min(count($customers) - 1, (int) floor(count($customers) * pow(mt_rand() / mt_getrandmax(), 1.7)))];
            if ($c->created_at && $c->created_at->gt($created)) {
                $created = $c->created_at->copy()->addDays(mt_rand(1, 10));
                if ($created->isFuture()) $created = now()->subHours(mt_rand(2, 30));
                $daysAgo = (int) $created->diffInDays(now());
            }

            $lines = [];
            $count = mt_rand(1, 100) <= 60 ? 1 : mt_rand(2, 3);
            for ($k = 0; $k < $count; $k++) {
                $pool = mt_rand(1, 100) <= 70 ? $byGroup[$prefs[$c->id][mt_rand(0, 1)]] : $books;
                $book = $pool[mt_rand(0, count($pool) - 1)];
                $variant = $book->variants[mt_rand(0, count($book->variants) - 1)];
                if (isset($lines[$variant->id])) continue;
                $qty = $book->category->slug === 'manga' ? mt_rand(1, 3) : (mt_rand(1, 100) <= 85 ? 1 : 2);
                $lines[$variant->id] = ['book' => $book, 'variant' => $variant, 'qty' => $qty];
            }
            $subtotal = 0;
            foreach ($lines as $l) $subtotal += $l['variant']->price * $l['qty'];

            $voucher = null; $discount = 0;
            if (mt_rand(1, 100) <= 22) {
                $v = $vouchers[mt_rand(0, count($vouchers) - 1)];
                if ($subtotal >= $v->min_order) { $voucher = $v; $discount = $v->discountFor($subtotal); }
            }
            $address = $addr[$c->id];
            $ship = CartService::shippingFee($subtotal, $address->province);

            // trạng thái theo "tuổi" đơn
            $r = mt_rand(1, 100);
            if ($daysAgo >= 6) {
                $status = $r <= 86 ? 'delivered' : ($r <= 95 ? 'cancelled' : 'returned');
            } elseif ($daysAgo >= 2) {
                $status = $r <= 50 ? 'delivered' : ($r <= 80 ? 'shipping' : ($r <= 92 ? 'confirmed' : 'cancelled'));
            } else {
                $status = $r <= 55 ? 'pending' : ($r <= 85 ? 'confirmed' : 'shipping');
            }
            $method = mt_rand(1, 100) <= 35 ? 'online' : 'cod';
            $paid = ($method === 'online' && $status !== 'cancelled') || in_array($status, ['delivered', 'returned']);
            $payStatus = $paid ? ($status === 'returned' ? 'refunded' : 'paid') : 'unpaid';
            if ($status === 'cancelled' && $method === 'online' && mt_rand(0, 1)) $payStatus = 'refunded';

            $order = new Order([
                'code' => 'MS' . $created->format('ymd') . strtoupper(substr(md5($n . 'motsach'), 0, 5)),
                'user_id' => $c->id, 'receiver_name' => $address->receiver_name, 'phone' => $address->phone, 'address' => $address->full,
                'subtotal' => $subtotal, 'discount' => $discount, 'shipping_fee' => $ship, 'total' => max(0, $subtotal - $discount + $ship),
                'voucher_id' => optional($voucher)->id, 'voucher_code' => optional($voucher)->code,
                'payment_method' => $method, 'payment_status' => $payStatus, 'status' => $status,
                'cancel_reason' => $status === 'cancelled' ? 'Khách hủy: Đặt nhầm' : null,
                'paid_at' => $paid ? $created->copy()->addMinutes(5) : null,
                'delivered_at' => in_array($status, ['delivered', 'returned']) ? $created->copy()->addDays(mt_rand(1, 4)) : null,
                'shipper_id' => in_array($status, ['shipping', 'delivered', 'returned']) ? $shippers[mt_rand(0, count($shippers) - 1)] : null,
            ]);
            if ($order->shipper_id) {
                $order->shipping_carrier = 'BenBooks Express';
                $order->tracking_code = 'MSX' . strtoupper(substr(md5($order->code), 0, 8));
            }
            $order->created_at = $created;
            $order->updated_at = $created;
            $order->save();

            foreach ($lines as $l) {
                $item = new OrderItem(['book_id' => $l['book']->id, 'variant_id' => $l['variant']->id, 'book_title' => $l['book']->title,
                    'variant_name' => $l['variant']->name, 'price' => $l['variant']->price, 'quantity' => $l['qty'], 'total' => $l['variant']->price * $l['qty']]);
                $item->order_id = $order->id;
                $item->created_at = $created;
                $item->save();

                // hành vi trước khi mua: xem 1-3 lần, thêm giỏ, mua
                for ($v = 0, $views = mt_rand(1, 3); $v < $views; $v++) {
                    $activities[] = $this->act($c->id, 'view', $l['book']->id, null, $created->copy()->subMinutes(mt_rand(10, 2000)));
                }
                $activities[] = $this->act($c->id, 'add_cart', $l['book']->id, null, $created->copy()->subMinutes(mt_rand(1, 9)));
                if ($status !== 'cancelled') {
                    $activities[] = $this->act($c->id, 'purchase', $l['book']->id, null, $created);
                }

                // đánh giá cho đơn đã giao
                $key = $c->id . '-' . $l['book']->id;
                if ($status === 'delivered' && !isset($reviewed[$key]) && mt_rand(1, 100) <= 48) {
                    $reviewed[$key] = true;
                    $p = mt_rand(1, 100);
                    $rating = $p <= 50 ? 5 : ($p <= 78 ? 4 : ($p <= 88 ? 3 : ($p <= 95 ? 2 : 1)));
                    $texts = self::REVIEWS[$rating];
                    $rv = new Review(['book_id' => $l['book']->id, 'user_id' => $c->id, 'rating' => $rating,
                                      'comment' => mt_rand(1, 100) <= 85 ? $texts[mt_rand(0, count($texts) - 1)] : null, 'is_visible' => true]);
                    $rv->created_at = $order->delivered_at->copy()->addDays(mt_rand(0, 3));
                    $rv->save();
                }
            }

            // lịch sử trạng thái
            $flow = ['pending'];
            if (in_array($status, ['confirmed', 'shipping', 'delivered', 'returned'])) $flow[] = 'confirmed';
            if (in_array($status, ['shipping', 'delivered', 'returned'])) $flow[] = 'shipping';
            if (in_array($status, ['delivered', 'returned'])) $flow[] = 'delivered';
            if ($status === 'returned') $flow[] = 'returned';
            if ($status === 'cancelled') $flow[] = 'cancelled';
            $notes = ['pending' => 'Khách hàng đặt hàng', 'confirmed' => 'Nhân viên đã xác nhận đơn', 'shipping' => 'Đã giao cho đơn vị vận chuyển',
                      'delivered' => 'Giao hàng thành công', 'returned' => 'Hoàn tất trả hàng, đã hoàn tiền', 'cancelled' => 'Khách hủy: Đặt nhầm'];
            foreach ($flow as $idx => $st) {
                $at = $st === 'delivered' ? $order->delivered_at : $created->copy()->addHours($idx * 9);
                DB::table('order_status_histories')->insert(['order_id' => $order->id, 'user_id' => $st === 'pending' || $st === 'cancelled' ? $c->id : $adminId,
                    'status' => $st, 'note' => $notes[$st], 'created_at' => $at, 'updated_at' => $at]);
            }
            if ($method === 'online' && $paid) {
                $pm = new Payment(['order_id' => $order->id, 'method' => 'online', 'amount' => $order->total,
                                   'transaction_code' => $order->code . '-' . $created->timestamp, 'status' => 'success', 'bank_code' => ['NCB', 'VCB', 'MOMO', 'VISA'][mt_rand(0, 3)]]);
                $pm->created_at = $created;
                $pm->save();
            }
            if ($voucher && $status !== 'cancelled') $voucher->increment('used_count');
        }

        // Duyệt web không mua + tìm kiếm (khách đăng nhập và khách vãng lai)
        foreach ($customers as $c) {
            for ($i = 0, $m = mt_rand(5, 20); $i < $m; $i++) {
                $pool = mt_rand(1, 100) <= 75 ? $byGroup[$prefs[$c->id][mt_rand(0, 1)]] : $books;
                $activities[] = $this->act($c->id, 'view', $pool[mt_rand(0, count($pool) - 1)]->id, null, now()->subMinutes(mt_rand(30, 60 * 24 * 80)));
            }
            for ($i = 0, $m = mt_rand(1, 4); $i < $m; $i++) {
                $activities[] = $this->act($c->id, 'search', null, self::KEYWORDS[mt_rand(0, count(self::KEYWORDS) - 1)], now()->subMinutes(mt_rand(30, 60 * 24 * 60)));
            }
            // wishlist
            foreach ($books->random(mt_rand(0, 4)) as $b) {
                DB::table('wishlists')->insertOrIgnore(['user_id' => $c->id, 'book_id' => $b->id, 'created_at' => now()->subDays(mt_rand(1, 60)), 'updated_at' => now()]);
                $activities[] = $this->act($c->id, 'wishlist', $b->id, null, now()->subDays(mt_rand(1, 60)));
            }
        }
        for ($g = 0; $g < 60; $g++) {
            $sid = 'guest-' . md5('g' . $g);
            for ($i = 0, $m = mt_rand(1, 6); $i < $m; $i++) {
                $activities[] = $this->act(null, 'view', $books[mt_rand(0, count($books) - 1)]->id, null, now()->subMinutes(mt_rand(30, 60 * 24 * 30)), $sid);
            }
            if (mt_rand(0, 1)) $activities[] = $this->act(null, 'search', null, self::KEYWORDS[mt_rand(0, count(self::KEYWORDS) - 1)], now()->subMinutes(mt_rand(30, 60 * 24 * 30)), $sid);
        }
        foreach (array_chunk($activities, 500) as $chunk) {
            DB::table('user_activities')->insert($chunk);
        }

        $this->anomalies($books);

        // Cập nhật số đã bán, lượt xem, điểm đánh giá
        foreach ($books as $b) {
            $sold = OrderItem::where('book_id', $b->id)->whereHas('order', function ($q) { $q->where('status', '!=', 'cancelled'); })->sum('quantity');
            $views = DB::table('user_activities')->where('book_id', $b->id)->where('action', 'view')->count();
            Book::where('id', $b->id)->update(['sold_count' => $sold, 'view_count' => $views * 3 + mt_rand(10, 80)]);
            $b->recalcRating();
        }

        // Thông báo mẫu cho tài khoản demo
        $demo = User::where('email', 'khach@motsach.vn')->first();
        if ($demo) {
            UserNotification::send($demo->id, 'Chào mừng bạn đến với BenBooks!', 'Nhập mã MOTSACH10 để được giảm 10% cho đơn đầu tiên.', route('vouchers', [], false));
            UserNotification::send($demo->id, 'Flash Sale đang diễn ra ⚡', 'Giảm đến 30% cho nhiều đầu sách hot, số lượng có hạn.', route('flash-sale', [], false));
        }
    }

    protected function act($userId, $action, $bookId, $keyword, Carbon $at, $session = null)
    {
        return ['user_id' => $userId, 'session_id' => $session ?: ($userId ? 'u' . $userId : null), 'action' => $action,
                'book_id' => $bookId, 'keyword' => $keyword, 'created_at' => $at->format('Y-m-d H:i:s'), 'updated_at' => $at->format('Y-m-d H:i:s')];
    }

    /** Vài đơn bất thường để demo chức năng Fraud/Anomaly Detection */
    protected function anomalies($books)
    {
        $pass = User::where('email', 'admin@motsach.vn')->value('password');
        $new = new User(['name' => 'Tài Khoản Mới Lạ', 'email' => 'nghivan@motsach.vn', 'phone' => '0999888777', 'role' => 'customer', 'password' => $pass]);
        $new->created_at = now()->subHours(3);
        $new->save();

        $expensive = $books->sortByDesc(function ($b) { return $b->variants->max('price'); })->first();
        $v = $expensive->variants->sortByDesc('price')->first();
        $qty = 15;
        $o = new Order(['code' => 'MS' . now()->format('ymd') . 'FRAUD', 'user_id' => $new->id, 'receiver_name' => 'Nguyễn Văn X', 'phone' => '0912345678',
            'address' => '1 Đường Không Tên, Phường 1, Quận 1, Hồ Chí Minh', 'subtotal' => $v->price * $qty, 'discount' => 0, 'shipping_fee' => 0,
            'total' => $v->price * $qty, 'payment_method' => 'cod', 'payment_status' => 'unpaid', 'status' => 'pending']);
        $o->created_at = now()->subHours(1);
        $o->save();
        $item = new OrderItem(['book_id' => $expensive->id, 'variant_id' => $v->id, 'book_title' => $expensive->title, 'variant_name' => $v->name,
                               'price' => $v->price, 'quantity' => $qty, 'total' => $v->price * $qty]);
        $item->order_id = $o->id;
        $item->save();
        DB::table('order_status_histories')->insert(['order_id' => $o->id, 'user_id' => $new->id, 'status' => 'pending', 'note' => 'Khách hàng đặt hàng',
                                                    'created_at' => $o->created_at, 'updated_at' => $o->created_at]);

        // đặt dồn dập nhiều đơn trong 1 giờ
        $spam = User::where('email', 'khach5@motsach.vn')->first();
        if ($spam) {
            for ($i = 0; $i < 4; $i++) {
                $b = $books[$i * 3];
                $vv = $b->variants->first();
                $oo = new Order(['code' => 'MS' . now()->format('ymd') . 'SPAM' . $i, 'user_id' => $spam->id, 'receiver_name' => $spam->name, 'phone' => $spam->phone,
                    'address' => '99 Đường Lạ, Phường 2, Quận 3, Hồ Chí Minh', 'subtotal' => $vv->price, 'discount' => 0, 'shipping_fee' => 20000,
                    'total' => $vv->price + 20000, 'payment_method' => 'cod', 'payment_status' => 'unpaid', 'status' => 'pending']);
                $oo->created_at = now()->subMinutes(50 - $i * 10);
                $oo->save();
                $it = new OrderItem(['book_id' => $b->id, 'variant_id' => $vv->id, 'book_title' => $b->title, 'variant_name' => $vv->name,
                                     'price' => $vv->price, 'quantity' => 1, 'total' => $vv->price]);
                $it->order_id = $oo->id;
                $it->save();
            }
        }
    }
}
