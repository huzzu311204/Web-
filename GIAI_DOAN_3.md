# Báo cáo chi tiết Giai đoạn 3 & 4: Storefront Showroom (Lọc đa tiêu chí AJAX, Chi tiết thiết bị, Gợi ý theo Ngàm, Đã xem gần đây & So sánh 3 sản phẩm)

- **Dự án:** Website quản lý sản phẩm & tồn kho thiết bị chụp ảnh (ProGear Studio)
- **Thư mục mã nguồn:** `C:\xampp\htdocs\camera-rental`
- **Repository GitHub:** `https://github.com/huzzu311204/Web-.git` (nhánh `main`)
- **Ngày hoàn thành:** 31/10/2026

---

## 1. Mục tiêu của Giai đoạn 3 & 4

Hoàn thiện toàn bộ khu vực **Showroom Khách hàng (`K1`, `K2`, `K3`, `K4`)** theo Mục 7 (`GĐ3 — Storefront` & `GĐ4 — So sánh`) trong `ANTIGRAVITY.md` và `SUA_GIAI_DOAN_2_3.md`:
1. **Trang Danh mục & Bộ lọc chuyên sâu (`index.php?r=catalog`):**
   - Tìm kiếm theo tên hoặc mã SKU (có escape ký tự `%` và `_`).
   - Lọc kết hợp theo: **Cây danh mục** (chọn danh mục cha lấy luôn danh mục con), **Thương hiệu** (chọn nhiều checkbox `brands[]`), **Khoảng giá** (`min_price` – `max_price`: kiểm tra chặt bằng `ctype_digit` độ dài `<= 12` và tự động hoán đổi nếu `min_price > max_price`), **Tình trạng tồn kho** (`in_stock`, `low_stock`, `out_of_stock`).
   - Sắp xếp theo: *Mới cập nhật* (`newest`), *Giá: Thấp → Cao* (`price_asc`), *Giá: Cao → Thấp* (`price_desc`), *Tên: A → Z* (`name_asc`) với whitelist cột chống SQL Injection (`TC10`).
   - Chỉ hiển thị các sản phẩm đang **`ACTIVE`**.
   - Hiển thị **Chip bộ lọc đang áp dụng** (`.filter-chip`) khớp 100% với điều kiện lọc sau chuẩn hóa kèm nút xóa từng điều kiện và nút *"Xóa tất cả"* (`TC07`).
   - **Tương tác AJAX (`api/products`):** Trả về `{ ok, html, total, page, pages }`; sử dụng `history.pushState` và sự kiện `popstate` để khi bấm nút Back/Forward của trình duyệt vẫn khôi phục đúng kết quả (`TC08`); mở trực tiếp URL có tham số lọc thì server dựng sẵn kết quả ngay từ lần tải đầu (`TC09`).
2. **Trang Chi tiết thiết bị (`index.php?r=product&id=...`):**
   - Hiển thị ảnh đại diện tỷ lệ 4:3, thương hiệu, mã SKU, giá niêm yết, nhãn tồn kho + số lượng sẵn có tại showroom, mô tả chi tiết và bảng đầy đủ các thông số kỹ thuật (`product_specs`).
   - **Sản phẩm liên quan thông minh:** Đối với thân máy ảnh (`category_id IN (1, 2, 3)`), hệ thống tự động đối chiếu thông số `Ngàm` (ví dụ `Sony E`, `Canon RF`, `Fujifilm X`) để gợi ý đúng các **Ống kính tương thích cùng Ngàm**.
3. **Lịch sử *"Đã xem gần đây"* (`$_SESSION['recent_viewed']` — `TC13`):**
   - Lưu tối đa **8 sản phẩm**, sản phẩm vừa xem nằm ở đầu danh sách (mới nhất trước), tự động bỏ trùng khi xem lại sản phẩm cũ.
   - Hiển thị ở cuối trang Chủ, trang Danh mục và trang Chi tiết thiết bị; có nút *"Xóa lịch sử xem"* gửi `POST index.php?r=recent/clear` kèm CSRF token.
4. **So sánh trực diện tối đa 3 sản phẩm (`index.php?r=compare` & `POST api/compare` — `TC11`, `TC12`):**
   - Lưu danh sách ID trong `$_SESSION['compare_ids']` (chỉ nhận sản phẩm `ACTIVE`, tối đa **3 thiết bị**; nếu chọn sản phẩm thứ 4 trả về mã HTTP `422` kèm thông báo rõ ràng).
   - Có **Thanh so sánh cố định cuối màn hình** (`#compare-sticky-bar`) và bộ đếm `#navbar-compare-count` cập nhật tức thì bằng AJAX khi bấm nút `.btn-toggle-compare`.
   - Trang bảng so sánh (`CompareController::index`) tự động **hợp (union)** toàn bộ tên thông số kỹ thuật của các sản phẩm đang chọn; những thông số mà thiết bị không có sẽ hiển thị dấu `"—"` (`TC12`).

---

## 2. Danh sách các file đã xây dựng / cập nhật

| Nhóm | File | Chi tiết nghiệp vụ |
|---|---|---|
| **Controllers & API** | `app/controllers/storefront/CatalogController.php` | Trang danh mục lọc đa tiêu chí, dựng mảng chip bộ lọc khớp với `ProductQuery::normalizePriceRange()`, chi tiết thiết bị, gợi ý cùng `Ngàm`, lưu `$_SESSION['recent_viewed']` tối đa 8. |
| | `app/controllers/api/ProductApi.php` | Endpoint AJAX `GET index.php?r=api/products` trả về JSON `{ ok, html, total, page, pages }`. |
| | `app/controllers/storefront/CompareController.php` | Trang bảng so sánh `GET index.php?r=compare`: hợp (union) toàn bộ các dòng `product_specs` của tối đa 3 sản phẩm, ô thiếu hiển thị `"—"`. |
| | `app/controllers/api/CompareApi.php` | Endpoint AJAX `POST index.php?r=api/compare` (`toggle`, `add`, `remove`, `clear`, `list`), chặn vượt quá 3 sản phẩm (`422` - `TC11`). |
| | `app/services/ProductQuery.php` | Bổ sung `ProductQuery::normalizePriceRange()` kiểm tra `ctype_digit` độ dài `<= 12` và tự động hoán đổi khi `min_price > max_price`. |
| **Views & JS** | `app/views/storefront/catalog/index.php`, `_grid_wrapper.php`, `_card.php`, `detail.php` | Giao diện Danh mục, Thẻ sản phẩm, Chip bộ lọc và Chi tiết sản phẩm. |
| | `app/views/storefront/compare/index.php` | Giao diện Bảng so sánh trực diện thông số kỹ thuật. |
| | `app/views/layouts/front.php` | Bổ sung Thanh so sánh cố định cuối màn hình (`#compare-sticky-bar`). |
| | `public/js/catalog.js` & `public/js/app.js` | Xử lý AJAX lọc danh mục (`pushState`/`popstate`) và AJAX thêm/xóa sản phẩm so sánh + cập nhật thanh so sánh cuối màn hình. |

---

## 3. Hạn chế đã biết (Known Limitations)

1. **Lưu trạng thái So sánh & Đã xem gần đây theo Session:** Danh sách so sánh (`$_SESSION['compare_ids']`) và lịch sử đã xem (`$_SESSION['recent_viewed']`) được lưu trong phiên làm việc của trình duyệt mà không yêu cầu khách đăng nhập; nếu khách xóa cookie phiên thì danh sách này sẽ được làm mới.
2. **Giới hạn đăng nhập sai phía Admin:** Bộ đếm khóa tạm khi đăng nhập sai nhiều lần lưu trong session.

---

## 4. Kết quả kiểm thử thực tế (`TC07` – `TC13`)

1. **Kiểm tra cú pháp (`php -l`):** Đã chạy `php -l` trên toàn bộ **60 file PHP** trong dự án — tất cả đều báo `No syntax errors detected`.
2. **Kết quả kiểm thử với MySQL thật (`camera_store_db`):**
   - **`TC07` & `TC09` (Lọc kết hợp + Mở URL có bộ lọc + Kiểm tra khoảng giá đảo ngược):**
     - Lọc kết hợp `category_id=1`, `brands[]=[1,2]`, `min_price=30000000`, `max_price=70000000`, `stock=in_stock` $\rightarrow$ Ra đúng **3 sản phẩm `ACTIVE`** và **6 chip bộ lọc**.
     - Truyền `min_price=70000000&max_price=30000000` $\rightarrow$ Tự động hoán đổi thành `[30000000, 70000000]` (ra 10 sản phẩm, chip hiển thị đúng); truyền `min_price=1e30` $\rightarrow$ Bị bỏ qua an toàn (`[null, null]`) $\rightarrow$ **ĐẠT**.
   - **`TC08` (Đổi bộ lọc rồi Back/Forward):** `catalog.js` gọi `api/products`, đẩy URL bằng `history.pushState` và khôi phục form + lưới trong sự kiện `window.onpopstate` $\rightarrow$ **ĐẠT**.
   - **`TC10` (`sort` chứa ký tự lạ / SQL Injection):** Truyền `sort = "price ASC; DROP TABLE products; --"` $\rightarrow$ Tự động dùng mặc định `'newest'` $\rightarrow$ **ĐẠT**.
   - **`TC11` (So sánh quá 3 sản phẩm):** Thêm 3 sản phẩm (`id = 1, 2, 4`) thành công (`count = 3`); khi bấm thêm sản phẩm thứ 4 (`id = 6`), `CompareApi` trả về HTTP `422` với thông báo *"Chỉ so sánh tối đa 3 sản phẩm cùng lúc. Vui lòng gỡ bớt 1 thiết bị trước khi thêm mới."* $\rightarrow$ **ĐẠT**.
   - **`TC12` (So sánh các sản phẩm có thông số khác nhau):** So sánh thân máy *Sony A7 III* (`id = 1`, có thông số `Cảm biến`, `Chống rung`) với ống kính *Sony FE 24-70mm GM* (`id = 11`, có thông số `Tiêu cự`, `Khẩu độ tối đa`, `Đường kính filter`) $\rightarrow$ Bảng so sánh hợp đầy đủ các dòng thông số và hiển thị `"—"` ở những ô thiết bị không có thông số tương ứng $\rightarrow$ **ĐẠT**.
   - **`TC13` (Xem hơn 8 sản phẩm):** Xem 10 sản phẩm rồi xem lại sản phẩm `#5` $\rightarrow$ `$_SESSION['recent_viewed']` chỉ giữ đúng 8 sản phẩm mới nhất `[5, 10, 9, 8, 7, 6, 4, 3]` $\rightarrow$ **ĐẠT**.
