# BenBooks – Website bán sách & truyện tranh (Laravel 8 + MySQL)

Đồ án môn **Lập trình mã nguồn mở** – đề tài *Thương mại điện tử* (Nhóm 2 trong file "Hướng phát triển đề tài"), áp dụng cho nhà sách: có thêm quản lý **tác giả, nhà xuất bản, ISBN, năm xuất bản** (lấy từ đề tài Nhóm 1 – Hệ thống quản lý sách).

- Framework: **Laravel 8** (chạy được trên **PHP 7.3 / 7.4** của XAMPP 3.2.4), MySQL/MariaDB.
- Giao diện: **phong cách SmartBen** (header xanh than, màu chính #2F6BFF, giá cam, nút bo tròn, Font Awesome 5, font Be Vietnam Pro + Open Sans) trên Blade + Bootstrap 5, Chart.js, vis-network – **tất cả để offline trong `public/assets`**, không cần Internet.
- Không cài thêm package Composer nào ngoài bộ mặc định của Laravel 8.

---

## 1. Cài đặt trên máy trường (XAMPP)

Thư mục này là **project Laravel 8 đầy đủ** (giống cấu trúc BAI1: `app`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes`, `storage`, `tests`, `artisan`, `composer.json`, `.env`…). Chỉ thiếu thư mục **`vendor`** (thư viện Composer, ~40MB, không đóng gói kèm).

1. Giải nén vào `C:\xampp\htdocs\MotSach`.
2. Lấy thư mục `vendor` – chọn **một** trong hai cách:
   - **Có Internet:** mở terminal tại `C:\xampp\htdocs\MotSach` chạy `composer install`
   - **Không có Internet:** copy nguyên thư mục `vendor` từ project Laravel 8 có sẵn (ví dụ `BAI1`) sang `MotSach`, rồi chạy `composer dump-autoload` (nếu máy có composer; không có cũng chạy được).
3. Mở XAMPP → Start **Apache** và **MySQL** → vào phpMyAdmin (http://localhost:8081/phpmyadmin) tạo database **`motsach`**, collation `utf8mb4_unicode_ci`.
4. File `.env` đã cấu hình sẵn (`DB_DATABASE=motsach`, user `root`, mật khẩu trống, `APP_KEY` đã tạo). Nếu MySQL khác cổng/mật khẩu thì sửa lại.
5. Tạo bảng + dữ liệu mẫu:
   ```bash
   php artisan migrate:fresh --seed
   ```
   (Không có `php` trong PATH thì dùng `C:\xampp\php\php.exe artisan migrate:fresh --seed`.)
6. Truy cập:
   - Qua Apache XAMPP: **http://localhost:8081/MotSach/public**
   - Hoặc `php artisan serve` → http://127.0.0.1:8000

Chạy kiểm thử: `php artisan test` (Unit: bỏ dấu tiếng Việt, hồi quy tuyến tính, định dạng tiền; Feature: các trang chính mở được, admin yêu cầu đăng nhập).

### Tài khoản demo (mật khẩu `123456`)

| Vai trò | Email | Vào đâu |
|---|---|---|
| Quản trị viên | admin@motsach.vn | /admin – toàn quyền, gồm nhóm AI & phân tích |
| Nhân viên | staff@motsach.vn | /admin – sách, kho, đơn hàng, đổi trả, đánh giá |
| Nhân viên giao hàng | shipper@motsach.vn | /shipper – app giao hàng cho điện thoại |
| Khách hàng | khach@motsach.vn (và khach1…khach40) | trang cửa hàng |

Mã giảm giá demo: `MOTSACH10`, `SACHHAY50K`, `MANGA15`, `FREESHIP`. Thanh toán online dùng **cổng mô phỏng** (sandbox) – bấm "Xác nhận thanh toán".

---

## 2. Đối chiếu chức năng với file yêu cầu

### A. Nhóm chức năng cơ bản
| Yêu cầu | Đã làm |
|---|---|
| Đăng ký / đăng nhập | `AuthController` – đăng ký, đăng nhập, ghi nhớ, đăng xuất, khóa tài khoản; giữ giỏ hàng khi đăng nhập |
| Quản lý tài khoản | Khách: sửa thông tin, đổi mật khẩu. Admin: tạo/sửa/khóa tài khoản |
| Quản lý danh mục | Thể loại 2 cấp (Sách › Văn học…, Truyện tranh › Manga/Manhwa/Manhua/Comic/Truyện tranh Việt) + Tác giả + Nhà xuất bản |
| Quản lý sản phẩm | Thêm/sửa/xóa mềm sách, ảnh bìa, ISBN, năm XB, số trang, nhiều tác giả, từ khóa, biến thể |
| Tìm kiếm sản phẩm | Ô tìm kiếm có gợi ý tức thì (AJAX) + tìm kiếm nâng cao: ISBN, tác giả, thể loại, NXB, năm XB, khoảng giá, phiên bản, số sao, còn hàng, sắp xếp |
| Xem chi tiết sản phẩm | Trang sách: chọn phiên bản, giá/flash sale, tồn kho, thông tin chi tiết, đánh giá, sách tương tự |
| Giỏ hàng | Lưu session, thêm nhanh AJAX, cập nhật/xóa, kiểm tra tồn kho, áp mã giảm giá |
| Đặt hàng | Checkout chọn địa chỉ/nhập mới, phí ship theo tỉnh (miễn phí từ 300k), khóa tồn kho khi đặt (`lockForUpdate`) |
| Quản lý địa chỉ | Sổ địa chỉ: thêm/sửa/xóa/đặt mặc định |
| Theo dõi đơn hàng | Danh sách đơn theo trạng thái, timeline, hủy đơn, mua lại, tra cứu đơn bằng mã + SĐT không cần đăng nhập |
| Quản lý đơn hàng Admin | Lọc/tìm đơn, chuyển trạng thái theo luồng, phân công shipper, mã vận đơn, in hóa đơn |

### B. Nhóm chức năng trung bình
| Yêu cầu | Đã làm |
|---|---|
| Phân quyền Admin/Nhân viên/Khách hàng | 4 vai trò (thêm Shipper), middleware `role:` đăng ký trong `AppServiceProvider` |
| Quản lý biến thể sản phẩm | Bìa mềm / Bìa cứng / Bản đặc biệt… mỗi biến thể có SKU, giá, giá bìa, tồn kho riêng |
| Quản lý tồn kho | Phiếu nhập/xuất/kiểm kê, lịch sử biến động kho, cảnh báo sắp hết, tự trừ/hoàn kho khi đặt/hủy/trả |
| Wishlist | Yêu thích sách, trang danh sách yêu thích |
| Voucher / mã giảm giá | % hoặc số tiền, giảm tối đa, đơn tối thiểu, tổng lượt, lượt/khách, thời hạn; trang "Săn mã" |
| Khuyến mãi / Flash Sale | Nhiều đợt, giới hạn suất, đếm ngược, thanh tiến độ đã bán, giá tự áp dụng vào giỏ |
| Thanh toán online | Cổng thanh toán mô phỏng theo luồng VNPay có chữ ký **HMAC-SHA512**, thanh toán lại, ghi giao dịch |
| Đổi / trả hàng | Khách gửi yêu cầu trong 7 ngày, chọn sản phẩm/số lượng; admin duyệt → hoàn kho → hoàn tiền |
| Đánh giá sản phẩm | Chỉ khách đã mua & nhận hàng, 1–5 sao, admin ẩn/xóa, thống kê sao |
| Thông báo đơn hàng | Thông báo khi đặt/đổi trạng thái/thanh toán/đổi trả; chuông thông báo |
| Dashboard doanh thu | KPI, biểu đồ doanh thu–số đơn 30 ngày, trạng thái đơn, top sách, cảnh báo |
| Báo cáo bán hàng | Theo khoảng ngày: theo ngày, sách, thể loại, phương thức thanh toán, tồn kho; **xuất CSV mở bằng Excel** |
| Tích hợp app giao hàng điện thoại | Trang `/shipper` tối ưu điện thoại: nhận đơn, gọi khách, mở bản đồ, xác nhận giao/thu COD, báo giao thất bại |

### C. Nhóm chức năng nâng cao (chạy offline, tự cài đặt thuật toán bằng PHP)
| Yêu cầu | Đã làm (file chính) |
|---|---|
| Recommendation System | Hybrid: content-based (cosine TF-IDF) + item-based collaborative filtering (mua cùng nhau) – `RecommendationService` |
| Personalized Recommendation | Hồ sơ sở thích theo hành vi (xem 1, giỏ 3, yêu thích 3, mua 5) → "Gợi ý dành cho bạn" ở trang chủ |
| Semantic Product Search | Vector TF-IDF (unigram + bigram, bỏ dấu, stopword) lưu bảng `book_vectors`, mở rộng đồng nghĩa – `SemanticSearchService` |
| Tìm kiếm bằng ngôn ngữ tự nhiên | Hiểu khoảng giá ("dưới 50k", "từ 100k đến 200k"), năm XB, thể loại, tác giả, "rẻ nhất/bán chạy/mới nhất" – trang `/tim-kiem-thong-minh` |
| AI chatbot tư vấn sản phẩm | Nhận diện ý định: tư vấn/tìm sách, hỏi giá/tồn kho 1 cuốn, tra cứu đơn, voucher/flash sale, chính sách – `ChatbotService` |
| RAG hỏi đáp chính sách | Kho tri thức `knowledge_docs` → truy xuất câu liên quan bằng cosine → trả lời kèm nguồn. Tùy chọn gọi LLM nếu khai báo `LLM_API_KEY` |
| Phân tích hành vi khách hàng | Ghi `user_activities` (xem, tìm, giỏ, yêu thích, mua): phễu chuyển đổi, từ khóa, giờ cao điểm, sách xem nhiều mua ít |
| Customer Segmentation | RFM + **K-Means** (k=4, k-means++), tự gán nhãn VIP / Thân thiết / Mới / Nguy cơ rời bỏ – `CustomerSegmentationService` |
| Dự đoán nhu cầu sản phẩm | Hồi quy tuyến tính + trung bình trượt theo tuần → nhu cầu 4 tuần, số tuần đủ bán, đề xuất nhập hàng – `ForecastService` |
| Dự báo doanh thu | Doanh thu 12 tháng + dự báo 3 tháng, R², độ dốc xu hướng |
| Fraud / Anomaly Detection | z-score giá trị đơn + bộ luật (tài khoản mới, đặt dồn, tỉ lệ hủy, SĐT dùng chung, số lượng lớn…) → điểm rủi ro 0–100, cảnh báo admin – `FraudDetectionService` |
| Phân tích cảm xúc đánh giá | Từ điển cảm xúc tiếng Việt + phủ định/nhấn mạnh + số sao; **phân tích theo khía cạnh** (nội dung, chất lượng in, giao hàng, giá, dịch thuật) – `SentimentService` |
| Knowledge Graph sản phẩm | Đồ thị Sách–Tác giả–Thể loại–NXB vẽ bằng vis-network + "tri thức suy ra" – `AnalyticsService` |
| Realtime notification | AJAX polling 10 giây, toast thông báo + đếm chưa đọc (chạy trên XAMPP, không cần WebSocket) |
| Message Queue | Laravel Queue (database driver, bảng `jobs`): `ProcessNewOrder`, `RefreshAiData`; trang theo dõi & nút xử lý hàng đợi; hoặc `php artisan queue:work database` |

Lệnh tiện ích: `php artisan motsach:ai-refresh` – chạy lại toàn bộ vector index, cảm xúc, phân cụm, quét bất thường.

---

## 3. Cấu trúc mã nguồn

```
app/
  helpers.php                    money(), vn_slug(), vn_unaccent(), stars()
  Providers/AppServiceProvider   nạp helpers, đăng ký middleware 'role', view composer menu
  Http/Middleware/RoleMiddleware phân quyền theo vai trò
  Http/Controllers/              Auth, Home, Book, Cart, Checkout, Order, Account, Notification,
                                 Chatbot, Payment, Shipper
  Http/Controllers/Admin/        Dashboard, Book, Category, Author, Publisher, Inventory, Order,
                                 Return, Review, User, Voucher, FlashSale, Report, Ai, Knowledge
  Models/                        25 model Eloquent
  Services/                      Cart, Order, Payment, TextAnalyzer, SemanticSearch, Recommendation,
                                 Chatbot, Sentiment, CustomerSegmentation, Forecast, FraudDetection, Analytics
  Jobs/                          ProcessNewOrder, RefreshAiData (message queue)
  Console/Commands/AiRefresh     php artisan motsach:ai-refresh
database/migrations/             4 file migration (users, catalog, shop, ai)
database/seeders/                Catalog, User, Shop, Order + data/books.json
resources/views/                 layouts (shop, admin, shipper), shop/*, admin/*, shipper/*, auth/*
public/assets/                   bootstrap, icons, chart.js, vis-network, app.css, app.js
public/images/books/             ảnh bìa minh họa tự tạo
routes/web.php                   ~125 route, nhóm theo khách / tài khoản / shipper / admin
```

## 4. Lỗi thường gặp
- `Specified key was too long`: đã xử lý bằng `Schema::defaultStringLength(191)`.
- `Class "..." not found` / thiếu `vendor/autoload.php`: chưa có thư mục `vendor` – chạy `composer install` hoặc copy `vendor` từ BAI1 rồi `composer dump-autoload`.
- Ảnh không hiện khi chạy qua Apache: kiểm tra `APP_URL` và truy cập đúng đường dẫn `/MotSach/public`.
- Muốn reset dữ liệu: `php artisan migrate:fresh --seed`.
