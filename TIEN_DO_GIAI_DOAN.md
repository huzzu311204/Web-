# Nhật ký triển khai dự án: Website Quản lý Sản phẩm & Tồn kho (ProGear Studio)

- **Thư mục dự án:** `C:\xampp\htdocs\camera-rental`
- **Chuẩn thiết kế:** `design-system/camera-rental/MASTER.md`
- **Cập nhật lần cuối:** 30/10/2026

---

## Giai đoạn 0 — Chuyển đổi sang Quản lý Sản phẩm & Tồn kho (Phụ lục A) và Vá bảo mật nền

### 0.1. Mục tiêu giai đoạn
- Áp dụng Phụ lục A: loại bỏ hoàn toàn các bảng và route cho thuê, chuyển sang CSDL `camera_store_db` gồm **9 bảng** (giữ nguyên tên cột của 6 bảng gốc + thêm `brands`, `product_specs`, `stock_logs`).
- Bổ sung kiểm soát bảo mật tập trung trong `Router::dispatch()` (`auth`, `method`, `ajax` và kiểm tra CSRF tự động cho mọi request `POST`).
- Nâng cấp bảo mật đăng nhập, đối chiếu trạng thái tài khoản `ACTIVE` trong DB trên mỗi request, chống Open Redirect và bảo vệ thư mục nội bộ bằng `.htaccess`.
- Chuẩn hóa lại giao diện theo đúng yêu cầu *"Chọn 1 font sans-serif"* (**Be Vietnam Pro**, bật `tabular-nums` cho số liệu) và bộ 4 badge tồn kho.

### 0.2. Danh sách thay đổi trong Giai đoạn 0

| Thành phần | Nội dung thực hiện |
|---|---|
| `database/schema.sql` | Tạo database `camera_store_db` gồm 9 bảng: `users` (cột gốc + `status`), `suppliers`, `categories` (`parent_id`), `brands`, `products` (giữ cột gốc + thêm `brand_id`, `min_stock`, `image` và `CHECK (quantity >= 0 AND min_stock >= 0 AND price >= 0)`), `product_specs`, `import_receipts` (bỏ cột `code`, giữ cột gốc), `import_receipt_details` (giữ cột gốc `receipt_id`, `import_price`), `stock_logs`. Dữ liệu mẫu có đủ trạng thái Còn hàng, Sắp hết, Hết hàng, Ngừng kinh doanh và lịch sử `stock_logs` khớp 100% với `products.quantity`. |
| `app/core/Router.php` | Hỗ trợ khai báo tham số `['auth' => 'admin', 'method' => 'POST', 'ajax' => true]`. Tự động chặn sai phương thức HTTP (`405`), kiểm tra quyền Admin và kiểm tra CSRF token cho mọi route `POST` trước khi gọi Controller. |
| `app/core/Auth.php` | Đối chiếu lại trạng thái tài khoản (`ACTIVE` & `ADMIN`) trong database trên mỗi request (tài khoản bị khóa mất hiệu lực ngay lập tức - TC30). Hủy session và xóa cookie đầy đủ khi đăng xuất. |
| `app/controllers/admin/AuthController.php` | Luôn gọi `password_verify()` (dùng dummy hash khi không tìm thấy username) để chống dò tài khoản, giới hạn đăng nhập sai liên tiếp (TC31), thông báo lỗi chung, sau khi đăng nhập chuyển về `admin/products`. |
| `app/core/Csrf.php` | Thêm `Csrf::safeRedirectTarget()` chỉ cho phép chuyển hướng trong cùng host khi CSRF lỗi (chống Open Redirect - TC28). |
| `app/core/Validator.php` | Bỏ `validateDateRange`; bổ sung `isValidLength()`, `isNonNegativeInt()`, `isPositiveInt()`, `clampInt()`. |
| `app/core/UiHelper.php` | Đổi sang 4 badge tồn kho (`Còn hàng` `#34d399`, `Sắp hết` `#facc15`, `Hết hàng` `#f87171`, `Ngừng kinh doanh` `#9ca3af`), badge loại giao dịch kho (`IMPORT`, `EXPORT`, `ADJUSTMENT`), class tô dòng `.row-out-of-stock` / `.row-low-stock` và mã phiếu nhập `PN-000001`. |
| `design-system/camera-rental/MASTER.md` & `public/css/theme.css` | Dùng duy nhất 1 font sans-serif **Be Vietnam Pro** (`tabular-nums` cho `.num`, `.price`, `.code`), cập nhật style 4 badge tồn kho, thanh so sánh cố định cuối trang và badge cảnh báo ở Sidebar Admin. |
| `app/views/layouts/front.php` & `admin.php` | Navbar khách hiển thị nút **So sánh** kèm số lượng; Sidebar Admin hiển thị số lượng sản phẩm sắp hết/hết hàng cạnh mục "Sản phẩm", bỏ trang Dashboard. |
| `public/index.php` & `.htaccess` | Bật `session.use_strict_mode = 1`, gửi header `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, thông báo lỗi chung không lộ thông tin DB (TC33), thêm `.htaccess` chặn truy cập `app/`, `config/`, `database/` và chặn chạy PHP/SVG trong `public/uploads/products/`. |

---

## Các giai đoạn tiếp theo

- [ ] **Giai đoạn 1 — CRUD (Nền 5 điểm):** CRUD Sản phẩm (thông số động, upload ảnh an toàn bằng `finfo` + `getimagesize()`, kiểm tra mã SKU trùng bằng AJAX), CRUD Danh mục (cây), Thương hiệu, Nhà cung cấp; Danh sách sản phẩm Admin có tìm kiếm, lọc, sắp xếp, phân trang.
- [ ] **Giai đoạn 2 — Quản lý Tồn kho:** Phiếu nhập Master–Detail cập nhật tồn kho + `stock_logs`; `StockService` (Xuất kho, Điều chỉnh kiểm kê có khóa dòng `SELECT ... FOR UPDATE`), Lịch sử biến động kho.
- [ ] **Giai đoạn 3 — Storefront:** Trang chủ bất đối xứng, Danh sách sản phẩm + Bộ lọc đa tiêu chí AJAX + Chip bộ lọc + Đồng bộ URL (`pushState` / `popstate`), Chi tiết sản phẩm + Sản phẩm liên quan.
- [ ] **Giai đoạn 4 — So sánh, Đã xem gần đây & AJAX phía Admin (+1 điểm):** So sánh 2–3 sản phẩm (`api/compare`), Đã xem gần đây (tối đa 8 sản phẩm), Sửa nhanh tại bảng (`api/admin/product-inline`), Ẩn/Xóa bằng AJAX.
- [ ] **Giai đoạn 5 — Xuất CSV, Nhóm B, Kiểm thử & Hoàn thiện báo cáo.**
