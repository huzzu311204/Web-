# Báo cáo chi tiết Giai đoạn 3: Storefront Showroom (Lọc đa tiêu chí AJAX, Chi tiết thiết bị, Gợi ý theo Ngàm & Đã xem gần đây)

- **Dự án:** Website quản lý sản phẩm & tồn kho thiết bị chụp ảnh (ProGear Studio)
- **Thư mục mã nguồn:** `C:\xampp\htdocs\camera-rental`
- **Repository GitHub:** `https://github.com/huzzu311204/Web-.git` (nhánh `main`)
- **Ngày hoàn thành:** 31/10/2026

---

## 1. Mục tiêu của Giai đoạn 3

Hoàn thiện toàn bộ khu vực **Storefront Showroom (`K1`, `K2`, `K4`)** theo Mục 7 (`GĐ3 — Storefront`) trong `ANTIGRAVITY.md` và quy chuẩn giao diện `MASTER.md`:
1. **Trang Danh mục & Bộ lọc chuyên sâu (`index.php?r=catalog`):**
   - Tìm kiếm theo tên hoặc mã SKU (có escape ký tự `%` và `_`).
   - Lọc kết hợp theo: **Cây danh mục** (chọn danh mục cha lấy luôn danh mục con), **Thương hiệu** (chọn nhiều checkbox `brands[]`), **Khoảng giá** (`min_price` – `max_price`), **Tình trạng tồn kho** (`in_stock`, `low_stock`, `out_of_stock`).
   - Sắp xếp theo: *Mới cập nhật* (`newest`), *Giá: Thấp → Cao* (`price_asc`), *Giá: Cao → Thấp* (`price_desc`), *Tên: A → Z* (`name_asc`) với whitelist cột chống SQL Injection (`TC10`).
   - Chỉ hiển thị các sản phẩm đang **`ACTIVE`** (những sản phẩm `INACTIVE` tự động ẩn khỏi Showroom).
   - Hiển thị **Chip bộ lọc đang áp dụng** (`.filter-chip`) kèm nút xóa từng điều kiện và nút *"Xóa tất cả"* (`TC07`).
   - **Tương tác AJAX (`api/products`):** Trả về `{ ok, html, total, page, pages }`; sử dụng `history.pushState` và sự kiện `popstate` để khi bấm nút Back/Forward của trình duyệt vẫn khôi phục đúng kết quả (`TC08`); mở trực tiếp URL có tham số lọc thì server dựng sẵn kết quả ngay từ lần tải đầu (`TC09`).
   - Bố cục responsive: cột lọc dính (`sticky-lg-top`) bên trái trên màn hình lớn (`>= lg`), tự gom vào `offcanvas` trên màn hình di động.
2. **Trang Chi tiết thiết bị (`index.php?r=product&id=...`):**
   - Hiển thị ảnh đại diện tỷ lệ 4:3, thương hiệu, mã SKU, giá niêm yết, nhãn tồn kho + số lượng sẵn có tại showroom, mô tả chi tiết và bảng đầy đủ các thông số kỹ thuật (`product_specs`).
   - **Sản phẩm liên quan thông minh:** Đối với thân máy ảnh (`category_id IN (1, 2, 3)`), hệ thống tự động đối chiếu thông số `Ngàm` (ví dụ `Sony E`, `Canon RF`, `Fujifilm X`) để gợi ý đúng các **Ống kính tương thích cùng Ngàm**; với các thiết bị khác thì gợi ý sản phẩm cùng danh mục & thương hiệu.
3. **Lịch sử *"Đã xem gần đây"* (`$_SESSION['recent_viewed']` — `TC13`):**
   - Lưu tối đa **8 sản phẩm**, sản phẩm vừa xem nằm ở đầu danh sách (mới nhất trước), tự động bỏ trùng khi xem lại sản phẩm cũ.
   - Hiển thị ở cuối trang Chủ, trang Danh mục và trang Chi tiết thiết bị; có nút *"Xóa lịch sử xem"* gửi `POST index.php?r=recent/clear` kèm CSRF token.

---

## 2. Danh sách các file đã xây dựng / cập nhật trong Giai đoạn 3

| Nhóm | File | Chi tiết nghiệp vụ |
|---|---|---|
| **Controllers** | `app/controllers/storefront/CatalogController.php` | • `index()`: Trang danh mục lọc đa tiêu chí, dựng mảng chip bộ lọc (`buildActiveFilterChips()`).<br>• `detail()`: Trang chi tiết thiết bị (trả `404` nếu không tồn tại hoặc `INACTIVE`), đẩy ID vào `$_SESSION['recent_viewed']` (`pushRecentViewed()`), lấy danh sách gợi ý cùng `Ngàm`.<br>• `clearRecent()`: Xóa `$_SESSION['recent_viewed']` (POST + CSRF). |
| | `app/controllers/api/ProductApi.php` | Endpoint AJAX `GET index.php?r=api/products` trả về JSON `{ ok, html, total, page, pages }` dùng chung `ProductQuery::paginate($filters, true, 9)`. |
| | `app/controllers/storefront/HomeController.php` | Đồng bộ khóa session `$_SESSION['recent_viewed']` với `CatalogController`. |
| **Views & JS** | `app/views/storefront/catalog/index.php` | Giao diện trang Danh mục thiết bị (Sidebar bộ lọc sticky / Offcanvas mobile + vùng lưới AJAX + khối Đã xem gần đây). |
| | `app/views/storefront/catalog/_grid_wrapper.php` | Partial render thanh Chip bộ lọc, lưới thẻ sản phẩm (hoặc trạng thái rỗng) và thanh phân trang (dùng chung cho cả SSR lần đầu và AJAX `api/products`). |
| | `app/views/storefront/catalog/_card.php` | Partial thẻ sản phẩm chuẩn `MASTER.md` (ảnh 4:3, mã SKU, badge tồn kho, 3 spec chip, giá bán, nút So sánh & nút Chi tiết). |
| | `app/views/storefront/catalog/detail.php` | Giao diện trang Chi tiết thiết bị + Bảng thông số kỹ thuật + Gợi ý ống kính cùng `Ngàm` + Đã xem gần đây. |
| | `public/js/catalog.js` | Xử lý AJAX lọc danh mục, debounce 300ms cho ô tìm kiếm/khoảng giá, gỡ từng chip bộ lọc, chuyển trang AJAX và đồng bộ `history.pushState` / `window.onpopstate`. |

---

## 3. Kết quả kiểm thử thực tế Giai đoạn 3 (`TC07`, `TC08`, `TC09`, `TC10`, `TC13`)

1. **Kiểm tra cú pháp (`php -l`):** Đã chạy `php -l` trên toàn bộ **58 file PHP** trong dự án — tất cả đều báo `No syntax errors detected`.
2. **Kiểm thử với MySQL thật (`camera_store_db`) và HTTP Endpoint:**
   - **`TC07` & `TC09` (Lọc kết hợp nhiều tiêu chí + Mở thẳng URL có bộ lọc + Chip bộ lọc):**
     - Lọc kết hợp `category_id=1` (Thân máy ảnh cha $\rightarrow$ tự lấy cả `id IN (1, 2, 3)`), `brands[] = [1, 2]` (Sony, Canon), `min_price = 30.000.000`, `max_price = 70.000.000`, `stock = in_stock` $\rightarrow$ Trả về đúng **3 sản phẩm `ACTIVE`** thỏa mãn đồng thời mọi điều kiện và sinh đúng **6 chip bộ lọc** $\rightarrow$ **ĐẠT**.
   - **`TC08` (AJAX `api/products` + `pushState` / `popstate`):**
     - Gọi HTTP `GET index.php?r=api/products&category_id=4&stock=in_stock` kèm header `X-Requested-With: XMLHttpRequest` $\rightarrow$ Trả về `{"ok": true, "total": 7}` kèm HTML lưới sản phẩm và chip bộ lọc; `catalog.js` đồng bộ URL bằng `history.pushState` và lắng nghe `popstate` để khôi phục form + lưới khi bấm Back/Forward $\rightarrow$ **ĐẠT**.
   - **`TC10` (`sort` chứa ký tự lạ / SQL Injection):**
     - Truyền `sort = "price ASC; DROP TABLE products; --"` $\rightarrow$ `Validator::sanitizeSort()` tự động đưa về mặc định `'newest'`, không xảy ra lỗi SQL $\rightarrow$ **ĐẠT**.
   - **`TC13` (Xem hơn 8 sản phẩm trong *"Đã xem gần đây"*):**
     - Xem lần lượt 10 sản phẩm (`1..10`) rồi xem lại sản phẩm `#5` $\rightarrow$ Mảng `$_SESSION['recent_viewed']` giữ đúng **8 phần tử** `[5, 10, 9, 8, 7, 6, 4, 3]` (sản phẩm `#5` nhảy lên đầu, bỏ trùng và cắt bỏ `1, 2`) $\rightarrow$ **ĐẠT**.
   - **Kiểm tra gợi ý Ống kính cùng `Ngàm`:**
     - Mở chi tiết thân máy *Sony Alpha A7 Mark III* (`id = 1`, `Ngàm = Sony E`) $\rightarrow$ `Product::getRelatedProducts()` tự động trả về đúng 4 ống kính có `Ngàm = Sony E` (`LEN-SN-2470GM`, `LEN-SN-70200GM`, `LEN-SG-35A-E`, `LEN-SG-85A-E`) $\rightarrow$ **ĐẠT**.
