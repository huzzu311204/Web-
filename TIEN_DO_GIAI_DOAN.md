# Nhật ký triển khai dự án: Website cho thuê thiết bị chụp ảnh (ProGear Studio)

- **Thư mục dự án:** `C:\xampp\htdocs\camera-rental`
- **Chuẩn thiết kế:** `design-system/camera-rental/MASTER.md`
- **Cập nhật lần cuối:** 30/10/2026

---

## Giai đoạn 1 — Thiết kế Cơ sở dữ liệu & Khởi tạo Core MVC

### 1.1. Mục tiêu giai đoạn
- Thiết kế đầy đủ 11 bảng CSDL (`camera_rental_db`) gồm 6 bảng gốc và 5 bảng mở rộng cho nghiệp vụ thuê & gia hạn.
- Chuẩn bị dữ liệu mẫu thực tế (28 thiết bị thật, thông số kỹ thuật chuẩn hóa, phiếu nhập, các đơn thuê đủ trạng thái dùng ngày động `CURDATE()`).
- Khởi tạo kiến trúc PHP 8.x MVC thuần, kết nối PDO, định tuyến, xác thực phiên, chống CSRF, chống XSS và kiểm tra dữ liệu đầu vào.

### 1.2. Danh sách file đã tạo trong Giai đoạn 1

| File | Vai trò & Nghiệp vụ đã cài đặt |
|---|---|
| `database/schema.sql` | Tạo database `camera_rental_db` (`InnoDB`, `utf8mb4_unicode_ci`), 11 bảng (`users`, `suppliers`, `categories`, `brands`, `products`, `product_specs`, `import_receipts`, `import_receipt_details`, `rentals`, `rental_details`, `rental_extensions`), khóa ngoại, index lịch trống và dữ liệu mẫu. |
| `config/database.php` | Cấu hình thông số kết nối MySQL/MariaDB (`127.0.0.1`, port `3306`, `camera_rental_db`, charset `utf8mb4`). |
| `app/core/Database.php` | Lớp kết nối PDO Singleton, bật `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, tắt `EMULATE_PREPARES`. |
| `app/core/Router.php` | Bộ định tuyến tách biệt 3 khu vực: Storefront (khách hàng), Admin (quản trị) và API (8 endpoint AJAX trả JSON). |
| `app/core/Auth.php` | Quản lý phiên đăng nhập Admin, gọi `session_regenerate_id(true)` sau khi đăng nhập, kiểm tra quyền trên mọi request bằng `Auth::requireAdmin()`. |
| `app/core/Csrf.php` | Sinh token mật mã học (`random_bytes(32)`), render input ẩn `Csrf::field()` và kiểm tra token từ cả form `POST` lẫn header `X-CSRF-Token` của AJAX. |
| `app/core/Validator.php` | Kiểm tra SĐT Việt Nam, email, khoảng ngày thuê hợp lệ (1–30 ngày, không ở quá khứ), whitelist cột sắp xếp. |
| `app/core/Response.php` | Render view kèm layout, trả JSON cho AJAX (`Response::json()`), quản lý flash message, escape XSS (`Response::e()`) và định dạng tiền tệ (`Response::money()` -> `450.000 đ`). |
| `public/index.php` & `index.php` | Front Controller khởi tạo session an toàn (`HttpOnly`, `SameSite=Lax`), PSR-4 autoloader cho namespace `App\*`, khai báo toàn bộ route. |

### 1.3. Kiểm tra nghiệm thu Giai đoạn 1
- Đã kiểm tra cú pháp PHP (`php -l`) trên toàn bộ file Core và cấu hình: `No syntax errors detected`.
- Dữ liệu mẫu trong `schema.sql` có sẵn 2 tài khoản Admin (`admin` / `Admin@123` và `staff` / `Admin@123`, lưu bằng bcrypt hash).

---

## Giai đoạn 2 — Hệ thống Giao diện (`theme.css`, `print.css`), Layout & Xác thực Admin

### 2.1. Mục tiêu giai đoạn
- Thực thi nghiêm ngặt file chuẩn thiết kế `design-system/camera-rental/MASTER.md`.
- Tạo 3 layout dùng chung (`front.php`, `admin.php`, `print.php`) và helper giao diện.
- Xây dựng chức năng Đăng nhập/Đăng xuất Admin, trang Dashboard thẻ số liệu (cảnh báo đơn quá hạn & sắp đến hạn) và Quản lý tài khoản Admin.
- Tạo bộ ảnh sản phẩm tỷ lệ `4:3` đồng nhất trên nền `#0f1012` cho toàn bộ 28 thiết bị mẫu.

### 2.2. Danh sách file đã tạo trong Giai đoạn 2

| File | Vai trò & Nghiệp vụ đã cài đặt |
|---|---|
| `design-system/camera-rental/MASTER.md` | Nguồn chuẩn duy nhất cho giao diện (Design Tokens, Typography, Bảng màu, 6 Badge trạng thái, quy tắc Component, Layout và Checklist 13 mục). |
| `public/css/theme.css` | Ghi đè biến Bootstrap 5.3 sang nền tối `#131416`, khối nổi `#1b1d21`, ô nhập `#17191c`, màu nhấn Hổ phách `#f0a23b` (chữ `#14110b`), font `Be Vietnam Pro` + `JetBrains Mono` (`tabular-nums`), bo góc `6px`, `.badge-status`, `.row-overdue`, `.row-due-soon`, tôn trọng `prefers-reduced-motion`. |
| `public/css/print.css` | CSS chuyên biệt cho trang in phiếu thuê A4 (nền trắng `#ffffff`, chữ đen `#111111`, ẩn các phần tử `.no-print`). |
| `app/core/UiHelper.php` | Render 6 badge trạng thái đơn kèm Bootstrap Icons (`aria-hidden="true"`), badge cảnh báo "Quá hạn" / "Sắp đến hạn", class tô dòng `.row-overdue` / `.row-due-soon` và đường dẫn ảnh thiết bị. |
| `public/js/app.js` | Thư viện JS dùng chung (`window.StudioUI`): hiển thị Toast tự đóng sau 4 giây, gắn header `X-CSRF-Token` cho `fetch()`, bật/tắt trạng thái loading có spinner trên nút bấm, cập nhật số lượng giỏ thuê trên navbar. |
| `app/views/layouts/front.php` | Layout khách hàng: Navbar có tìm kiếm, giỏ thuê kèm badge số lượng (`aria-live="polite"`), vùng flash message, toast container và footer. |
| `app/views/layouts/admin.php` | Layout quản trị: Sidebar trái rộng `240px` (chuyển sang `offcanvas` trên di động), mục active có viền trái 3px hổ phách, topbar mỏng hiển thị thông tin admin và form đăng xuất có CSRF. |
| `app/views/layouts/print.php` | Layout bản in phiếu thuê A4. |
| `app/views/storefront/404.php` | Trang thông báo 404 thân thiện khi truy cập đường dẫn không tồn tại. |
| `app/models/User.php` | Truy vấn bảng `users` bằng PDO prepared statements, tạo/cập nhật tài khoản với `password_hash()`, đổi trạng thái khóa/mở. |
| `app/controllers/admin/AuthController.php` & `app/views/admin/auth/login.php` | Xử lý đăng nhập bằng `password_verify()`, kiểm tra trạng thái `ACTIVE`, chống CSRF, tái tạo session ID, hiển thị lỗi ngay dưới trường nhập. |
| `app/controllers/admin/DashboardController.php` & `app/views/admin/dashboard/index.php` | Truy vấn 5 thẻ số liệu thực tế từ MySQL (Đơn chờ duyệt, Gia hạn chờ duyệt, Đang thuê, Sắp đến hạn ≤ 2 ngày, Quá hạn) và bảng "Đơn thuê cần chú ý". |
| `app/controllers/admin/UserController.php` & `app/views/admin/users/index.php` | Xem danh sách, thêm mới, cập nhật thông tin/mật khẩu và khóa/mở tài khoản Admin (chặn tự khóa chính mình). |
| `public/images/products/*.svg` | 29 ảnh minh họa thiết bị studio tỷ lệ `4:3` (`800×600`, nền `#0f1012`) khớp với 28 sản phẩm trong `schema.sql`. |

### 2.3. Kiểm tra nghiệm thu Giai đoạn 2
- Toàn bộ 18 file PHP trong dự án đã được kiểm tra bằng `php -l` và không có lỗi cú pháp.
- Tuân thủ đầy đủ checklist trong `MASTER.md`: không dùng emoji, không còn màu xanh dương mặc định của Bootstrap, nút và input đạt chiều cao tối thiểu `44px`, có `aria-label` cho các nút chỉ có icon.

---

## Các giai đoạn tiếp theo (Sẽ cập nhật ngay sau khi hoàn thành từng giai đoạn)

- [ ] **Giai đoạn 3 — CRUD Quản trị (Nền 5 điểm):** Sản phẩm (kèm thông số động & upload ảnh), Danh mục (cây phân cấp), Thương hiệu, Nhà cung cấp, Phiếu nhập kho Master–Detail.
- [ ] **Giai đoạn 4 — Storefront:** Trang chủ bất đối xứng, Danh sách sản phẩm + Bộ lọc AJAX, Chi tiết sản phẩm, Trang chính sách thuê.
- [ ] **Giai đoạn 5 — Nghiệp vụ thuê & AJAX (+1 điểm):** Kiểm tra lịch trống theo từng ngày (`AvailabilityService`), Giỏ thuê Session, Gửi yêu cầu thuê, Tra cứu & Hủy đơn, Xử lý vòng đời đơn thuê phía Admin.
- [ ] **Giai đoạn 6 — Gia hạn thuê, Gợi ý thuê kèm, In phiếu thuê & Kiểm thử hoàn thiện.**

