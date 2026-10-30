# Báo cáo chi tiết Giai đoạn 0: Chuyển đổi theo Phụ lục A & Vá bảo mật nền

- **Dự án:** Website quản lý sản phẩm & tồn kho thiết bị chụp ảnh (ProGear Studio)
- **Thư mục mã nguồn:** `C:\xampp\htdocs\camera-rental`
- **Repository GitHub:** `https://github.com/huzzu311204/Web-.git` (nhánh `main`)
- **Ngày hoàn thành:** 30/10/2026

---

## 1. Mục tiêu của Giai đoạn 0

Giai đoạn 0 thực hiện đầy đủ **Phụ lục A** và **Mục 11 (Bảo mật & Chất lượng dữ liệu)** trong tài liệu đặc tả dự án:
1. Chuyển đổi cơ sở dữ liệu sang mô hình **Quản lý sản phẩm và tồn kho (`camera_store_db` — 9 bảng)**, giữ nguyên 100% tên cột của 6 bảng gốc.
2. Loại bỏ toàn bộ các bảng, controller, view và route liên quan đến cho thuê/gia hạn và bỏ trang Dashboard biểu đồ/thẻ số liệu.
3. Xây dựng cơ chế **kiểm soát bảo mật tập trung tại Router** (`auth`, `method`, `ajax` và kiểm tra CSRF tự động cho mọi request `POST`).
4. Vá các điểm bảo mật trọng yếu: chống dò tên đăng nhập (timing attack), giới hạn số lần đăng nhập sai liên tiếp, đối chiếu trạng thái tài khoản `ACTIVE` trong DB trên mỗi request, chống Open Redirect khi lỗi CSRF, ẩn thông báo lỗi nhạy cảm và chặn truy cập thư mục nội bộ bằng `.htaccess`.
5. Chuẩn hóa giao diện theo `MASTER.md`:
   - Tách biệt hoàn toàn giao diện **Khách hàng** (`app/views/layouts/front.php` — không chứa nút/link Quản trị) và giao diện **Quản trị viên** (`app/views/layouts/admin.php`).
   - Dùng duy nhất 1 font sans-serif **Be Vietnam Pro** (bật `tabular-nums` cho số liệu), đổi sang bộ **4 badge tồn kho** và đưa cảnh báo sắp hết/hết hàng lên Sidebar Admin.

---

## 2. Chi tiết các hạng mục đã thực hiện

### 2.1. Thiết kế lại Cơ sở dữ liệu (`database/schema.sql` & `config/database.php`)

- **Tên cơ sở dữ liệu:** `camera_store_db` (`InnoDB`, `utf8mb4_unicode_ci`).
- **Cấu trúc 9 bảng chuẩn:**
  1. `users` *(bảng gốc + thêm `status`)*: `id`, `username`, `password`, `full_name`, `email`, `role`, `status` (`ACTIVE`/`INACTIVE`), `created_at`. Đã bỏ cột `phone` và `updated_at` để khớp bảng gốc.
  2. `suppliers` *(bảng gốc)*: `id`, `name`, `phone`, `email`, `address`, `created_at`.
  3. `categories` *(bảng gốc + thêm `parent_id`)*: `id`, `parent_id`, `name`, `description`, `created_at`. Có khóa ngoại tự tham chiếu `fk_categories_parent`.
  4. `brands` *(bảng mới)*: `id`, `name`, `logo`, `created_at`.
  5. `products` *(bảng gốc + thêm `brand_id`, `min_stock`, `image`)*:
     - Giữ nguyên các cột gốc: `id`, `category_id`, `supplier_id`, `code`, `name`, `price`, `quantity`, `description`, `status`, `created_at`, `updated_at`.
     - Đã bỏ `rent_price` và `deposit`.
     - Thêm ràng buộc tầng CSDL: `CONSTRAINT chk_products_qty CHECK (quantity >= 0 AND min_stock >= 0 AND price >= 0)`.
     - Thêm index: `idx_products_filter (status, category_id, brand_id)` và `idx_products_price (price)`.
  6. `product_specs` *(bảng mới)*: `id`, `product_id`, `spec_name`, `spec_value`.
  7. `import_receipts` *(bảng gốc)*: `id`, `supplier_id`, `user_id`, `import_date`, `total_amount`, `note`, `created_at` (đã bỏ cột `code`, mã phiếu hiển thị từ `id` dạng `PN-000001`).
  8. `import_receipt_details` *(bảng gốc)*: `id`, `receipt_id`, `product_id`, `quantity`, `import_price`, `subtotal` (đã trả lại đúng tên cột gốc `receipt_id` và `import_price`).
  9. `stock_logs` *(bảng mới)*: `id`, `product_id`, `user_id`, `type` (`IMPORT`, `EXPORT`, `ADJUSTMENT`), `change_qty`, `quantity_before`, `quantity_after`, `reason`, `receipt_id`, `created_at`. Có index `idx_stock_logs_product_date (product_id, created_at)`.

- **Dữ liệu mẫu (`SEED DATA` — Đã nạp vào MySQL với chuẩn `utf8mb4`):**
  - Tài khoản Admin duy nhất: `admin` / mật khẩu `ProGear@2026!` (sử dụng bcrypt hash tạo riêng, đã kiểm tra bằng `password_verify()`).
  - 28 sản phẩm thiết bị nhiếp ảnh thật trải đều 6 danh mục, bao gồm đủ 4 trạng thái để demo:
    - **Hết hàng (`quantity = 0`):** Canon EOS R5 (`id = 5`), Canon RF 50mm f/1.2L (`id = 16`).
    - **Sắp hết (`0 < quantity <= min_stock`):** Sony Alpha A7 Mark IV (`id = 2`, tồn 2 / ngưỡng 2), Sony FE 70-200mm GM (`id = 12`, tồn 1 / ngưỡng 2), Gimbal DJI RS 3 Pro (`id = 24`, tồn 1 / ngưỡng 2).
    - **Ngừng kinh doanh (`status = 'INACTIVE'`):** Nikon D750 (`id = 9`).
    - **Còn hàng (`quantity > min_stock`):** 22 sản phẩm còn lại.
  - 2 phiếu nhập mẫu và **37 dòng `stock_logs`** (gồm Tồn đầu kỳ, `IMPORT` từ phiếu nhập, `EXPORT` xuất bán và `ADJUSTMENT` điều chỉnh kiểm kê) khớp 100% với cột `products.quantity` của từng sản phẩm (`TC20`).

---

### 2.2. Kiểm soát bảo mật tập trung ở Core & Router

| File | Chi tiết nâng cấp trong Giai đoạn 0 |
|---|---|
| `app/core/Router.php` | • Nhận tham số cấu hình cho từng route: `['auth' => 'admin', 'method' => 'POST', 'ajax' => true]`.<br>• Tự động gửi header `Cache-Control: no-store` cho mọi trang/API admin.<br>• Kiểm tra phương thức HTTP trước khi gọi Controller; nếu sai phương thức trả về `405 Method Not Allowed` (`TC29`, `TC34`).<br>• Kiểm tra đăng nhập Admin tập trung (`Auth::requireAdmin()`).<br>• Tự động kiểm tra CSRF token (`Csrf::requireValid()`) cho **mọi request `POST`**. |
| `app/core/Auth.php` | • Trong `Auth::check()`, mỗi request đều truy vấn lại bảng `users` để đối chiếu `status === 'ACTIVE'` và `role === 'ADMIN'`. Nếu tài khoản đang đăng nhập bị khóa ở tab khác, request tiếp theo bị từ chối ngay lập tức (`TC30`).<br>• Trong `Auth::logout()`, xóa toàn bộ `$_SESSION`, xóa cookie phiên và gọi `session_destroy()` + `session_regenerate_id(true)`. |
| `app/core/Csrf.php` | • Bổ sung `Csrf::safeRedirectTarget()` phân tích `HTTP_REFERER` bằng `parse_url()` và chỉ cho phép chuyển hướng nếu cùng `HTTP_HOST` hiện tại, chống lỗ hổng Open Redirect (`TC28`). |
| `app/core/Validator.php` | • Xóa hàm `validateDateRange()` của bài toán thuê cũ.<br>• Bổ sung `isValidLength()`, `isNonNegativeInt()`, `isPositiveInt()`, `clampInt()` để kiểm tra chặt độ dài chuỗi và số nguyên không âm trước khi ghi DB (`TC03`). |
| `public/index.php` | • Bật `ini_set('display_errors', '0')` và `ini_set('session.use_strict_mode', '1')`.<br>• Gửi các HTTP Security Headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`.<br>• Khai báo bảng route mới theo đúng Mục 8.1.<br>• Khối `catch (\Throwable $e)` chỉ ghi chi tiết lỗi vào `error_log` và hiển thị thông báo chung cho người dùng, tuyệt đối không lộ tên bảng hay câu truy vấn SQL (`TC33`). |
| `.htaccess` | • Tạo `app/.htaccess`, `config/.htaccess`, `database/.htaccess` chặn truy cập trực tiếp qua trình duyệt.<br>• Tạo `public/uploads/products/.htaccess` tắt `ExecCGI`, tắt `Indexes` và chặn thực thi mọi file `.php`, `.phtml`, `.phar`, `.svg`... (`TC25`). |

---

### 2.3. Nâng cấp Xác thực & Quản lý Tài khoản Admin

- **`app/controllers/admin/AuthController.php` & `app/views/admin/auth/login.php`:**
  - Luôn gọi `password_verify()` trên mọi lần gửi form đăng nhập (nếu không tìm thấy username thì xác minh với `DUMMY_HASH`) để thời gian phản hồi như nhau, chống dò tên tài khoản.
  - Hiển thị thông báo lỗi chung: *"Thông tin đăng nhập không hợp lệ hoặc tài khoản không có quyền truy cập"*.
  - Giới hạn số lần đăng nhập sai liên tiếp (`MAX_ATTEMPTS = 5`, khóa tạm thời `120 giây` trong session - `TC31`).
  - Sau khi đăng nhập thành công, chuyển hướng về `index.php?r=admin/products` (thay vì Dashboard).
  - Không hiển thị mật khẩu mẫu công khai trên giao diện đăng nhập.
- **`app/models/User.php`, `app/controllers/admin/UserController.php`, `app/views/admin/users/index.php`:**
  - Đồng bộ với cấu trúc bảng `users` gốc (bỏ trường `phone`).
  - Mật khẩu tạo mới hoặc cập nhật yêu cầu tối thiểu 8 ký tự và băm bằng `password_hash()`.
  - Chặn quản trị viên tự khóa tài khoản đang đăng nhập của chính mình.

---

### 2.4. Chuẩn hóa Giao diện (`MASTER.md`, `theme.css`, `UiHelper`, Layouts)

- **`design-system/camera-rental/MASTER.md` & `public/css/theme.css`:**
  - Sử dụng duy nhất 1 font sans-serif **Be Vietnam Pro** (bỏ `JetBrains Mono` theo Phụ lục A.2), bật `font-variant-numeric: tabular-nums` cho các class `.num`, `.price`, `.code`.
  - Cập nhật 4 class badge tồn kho:
    - `.stock-in` (Còn hàng — `#34d399`, icon `bi-check-circle`)
    - `.stock-low` (Sắp hết — `#facc15`, icon `bi-exclamation-triangle`)
    - `.stock-out` (Hết hàng — `#f87171`, icon `bi-x-circle`)
    - `.stock-discontinued` (Ngừng kinh doanh — `#9ca3af`, icon `bi-slash-circle`)
  - Cập nhật 2 class tô dòng cảnh báo tồn kho trong bảng Admin: `.row-out-of-stock` (viền trái 3px `#f87171`) và `.row-low-stock` (viền trái 3px `#facc15`).
  - Thêm style cho chip bộ lọc đang áp dụng (`.filter-chip`), thanh so sánh cố định cuối màn hình (`.compare-bar-fixed`) và badge cảnh báo trên Sidebar Admin (`.sidebar-alert-badge`).
- **`app/core/UiHelper.php`:**
  - Cài đặt `UiHelper::stockState()`, `UiHelper::stockBadge()`, `UiHelper::productStatusBadge()`, `UiHelper::stockLogTypeBadge()`, `UiHelper::productRowClass()` và `UiHelper::formatReceiptCode()` (`PN-000001`).
- **`app/views/layouts/front.php` & `app/views/layouts/admin.php`:**
  - **Layout khách hàng (`front.php`)**: Hoàn toàn tách biệt với khu vực quản trị, chỉ hiển thị Logo, Ô tìm kiếm, *Trang chủ*, *Danh mục thiết bị* và nút *So sánh* kèm bộ đếm số lượng (`#navbar-compare-count`).
  - **Sidebar Admin (`admin.php`)**: Bỏ mục Dashboard và Đơn thuê; bổ sung mục **Lịch sử tồn kho** (`admin/stock-logs`) và hiển thị trực tiếp badge số lượng sản phẩm sắp hết/hết hàng cạnh mục **Sản phẩm**.

---

## 3. Kiểm tra nghiệm thu Giai đoạn 0

1. **Kiểm tra cú pháp PHP (`php -l`):** Toàn bộ các file PHP trong dự án đều báo `No syntax errors detected`.
2. **Kiểm tra tính toàn vẹn tồn kho trong `schema.sql` (`TC20`):** Tổng `SUM(change_qty)` trong `stock_logs` của cả 28 sản phẩm khớp chính xác 100% với cột `quantity` trong bảng `products`.
3. **Đồng bộ GitHub:** Đã commit và push lên nhánh `main` tại `https://github.com/huzzu311204/Web-`.
