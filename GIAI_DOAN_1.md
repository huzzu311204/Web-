# Báo cáo chi tiết Giai đoạn 1: CRUD Quản trị, AJAX Phía Admin & Trang chủ Khách

- **Dự án:** Website quản lý sản phẩm & tồn kho thiết bị chụp ảnh (ProGear Studio)
- **Thư mục mã nguồn:** `C:\xampp\htdocs\camera-rental`
- **Repository GitHub:** `https://github.com/huzzu311204/Web-.git` (nhánh `main`)
- **Ngày hoàn thành:** 30/10/2026

---

## 1. Mục tiêu của Giai đoạn 1

Hoàn thành toàn bộ phần **CRUD bắt buộc (5 điểm)**, các tương tác **AJAX phía Admin** và **Trang chủ khách hàng (`K1`)** theo Mục 5, Mục 6 và Mục 12 của tài liệu đặc tả:
1. **CRUD Sản phẩm (`A2`, `A5`, `A7`, `A8`):**
   - Danh sách sản phẩm Admin có tìm kiếm, lọc theo danh mục/thương hiệu/tình trạng tồn/trạng thái, sắp xếp, phân trang chạy bằng AJAX (`api/admin/products`).
   - Tô màu dòng sản phẩm **Hết hàng** (viền đỏ `#f87171`) và **Sắp hết** (viền vàng `#facc15`), có nút lọc nhanh *"Sắp hết / Hết hàng"*.
   - Thêm / Sửa sản phẩm kèm **danh sách thông số kỹ thuật động** (`product_specs`), tự động ghi `stock_logs` *"Tồn đầu kỳ"* trong cùng transaction khi tạo mới có số lượng > 0.
   - Kiểm tra mã SKU trùng tức thì bằng AJAX (`api/admin/sku-check`, debounce 300ms) và kiểm tra lại ở backend khi lưu (`TC02`).
   - Upload ảnh an toàn bằng `ImageUploader` (`finfo` + `getimagesize()`, chỉ nhận `jpg`, `png`, `webp` tối đa 2 MB, từ chối `svg` và file giả mạo - `TC25`).
   - Xóa sản phẩm qua modal xác nhận (`api/admin/product-action`): xóa cứng + dọn ảnh nếu chưa có dữ liệu liên quan (`TC06`), hoặc chuyển sang `INACTIVE` (xóa mềm) nếu đã có phiếu nhập hoặc lịch sử kho (`TC05`).
   - Sửa nhanh Giá bán, Ngưỡng sắp hết, Trạng thái ngay trên bảng (`api/admin/product-inline`) và chặn tuyệt đối việc gửi trường `quantity` lên sửa nhanh (`TC21`, `TC22`).
   - Xuất CSV theo đúng bộ lọc hiện tại (`CsvExporter`), có UTF-8 BOM và chống CSV Formula Injection (`TC23`, `TC24`).
2. **CRUD Danh mục (`A3`):** Cây danh mục phân cấp (`parent_id`), hiển thị số sản phẩm thuộc danh mục, chặn xóa khi còn sản phẩm hoặc danh mục con (`TC04`).
3. **CRUD Thương hiệu (`A3`):** Thêm, sửa, xóa qua modal, hiển thị số sản phẩm, chặn xóa khi còn sản phẩm tham chiếu (`TC04`).
4. **CRUD Nhà cung cấp (`A3`):** Thêm, sửa, xóa qua modal, validate SĐT Việt Nam và email, chặn xóa khi còn sản phẩm hoặc phiếu nhập tham chiếu.
5. **Trang chủ Khách hàng (`K1` — `index.php?r=home`):**
   - Hero bất đối xứng theo đúng `MASTER.md` Mục 3.1 (nửa trái là tiêu đề + 1 nút chính *"Xem toàn bộ sản phẩm"*, nửa phải là sản phẩm nổi bật; tách biệt hoàn toàn, không chứa nút Quản trị).
   - Lưới danh mục thiết bị kèm số lượng sản phẩm.
   - Danh sách thiết bị mới cập nhật kèm 3 thông số chính (`product_specs`) và nhãn trạng thái tồn kho.
   - Khu vực *Sản phẩm đã xem gần đây* đọc từ `$_SESSION['recent_viewed']`.

---

## 2. Danh sách các file đã xây dựng trong Giai đoạn 1

| Nhóm | File | Chi tiết nghiệp vụ |
|---|---|---|
| **Models** | `app/models/Category.php` | Quản lý cây danh mục 2 cấp (`getTreeOptions()`), đếm số sản phẩm & danh mục con, kiểm tra trùng tên và kiểm tra ràng buộc trước khi xóa. |
| | `app/models/Brand.php` | CRUD thương hiệu, đếm số sản phẩm thuộc thương hiệu, kiểm tra trùng tên và ràng buộc khóa ngoại. |
| | `app/models/Supplier.php` | CRUD nhà cung cấp, đếm số sản phẩm và số phiếu nhập liên kết, chặn xóa khi đã phát sinh dữ liệu. |
| | `app/models/ProductSpec.php` | Truy vấn thông số theo 1 hoặc nhiều sản phẩm (`WHERE product_id IN (?,?)`), cập nhật danh sách thông số động của sản phẩm. |
| | `app/models/Product.php` | Tạo/cập nhật sản phẩm kèm thông số kỹ thuật và `stock_logs` (Tồn đầu kỳ) trong PDO Transaction; sửa nhanh `updateInline()`; kiểm tra lịch sử kho `hasInventoryHistory()`; gợi ý sản phẩm liên quan theo `Ngàm`. |
| **Services** | `app/services/ProductQuery.php` | Dựng câu truy vấn lọc đa tiêu chí bằng Prepared Statements, whitelist cột sắp xếp (`TC10`), phân trang `LIMIT / OFFSET` dùng chung cho cả Admin, Storefront và Xuất CSV. |
| | `app/services/ImageUploader.php` | Kiểm tra dung lượng ≤ 2MB, xác minh MIME bằng `finfo`, kiểm tra kích thước ảnh thật bằng `getimagesize()`, đổi tên ngẫu nhiên `bin2hex(random_bytes(16))`, dọn ảnh cũ khi cập nhật/xóa (`TC06`, `TC25`). |
| | `app/services/CsvExporter.php` | Xuất file CSV theo bộ lọc hiện tại với UTF-8 BOM (`\xEF\xBB\xBF`) và hàm `sanitizeCell()` thêm dấu `'` nếu ô bắt đầu bằng `=`, `+`, `-`, `@` (`TC23`, `TC24`). |
| **Controllers** | `app/controllers/admin/ProductController.php` | Xử lý danh sách, thêm mới, chỉnh sửa sản phẩm và xuất CSV. |
| | `app/controllers/admin/CategoryController.php` | Xử lý hiển thị, thêm/sửa và xóa danh mục sản phẩm. |
| | `app/controllers/admin/BrandController.php` | Xử lý hiển thị, thêm/sửa và xóa thương hiệu. |
| | `app/controllers/admin/SupplierController.php` | Xử lý hiển thị, thêm/sửa và xóa nhà cung cấp. |
| | `app/controllers/api/AdminProductApi.php` | 4 endpoint AJAX Admin: `api/admin/products`, `api/admin/sku-check`, `api/admin/product-inline`, `api/admin/product-action`. |
| | `app/controllers/storefront/HomeController.php` | Trang chủ khách hàng (`index.php?r=home`) hiển thị Hero thiết bị, lưới danh mục, sản phẩm mới cập nhật và lịch sử đã xem. |
| **Views & JS** | `app/views/admin/products/index.php`, `_table_rows.php`, `_pagination.php`, `form.php` | Giao diện danh sách sản phẩm Admin (có ô sửa nhanh, modal xóa, modal xuất/điều chỉnh kho) và form thêm/sửa sản phẩm có thêm/bớt dòng thông số kỹ thuật động. |
| | `app/views/admin/categories/index.php` | Giao diện cây danh mục + form thêm/sửa + modal xác nhận xóa. |
| | `app/views/admin/brands/index.php` | Giao diện thương hiệu + form thêm/sửa + modal xác nhận xóa. |
| | `app/views/admin/suppliers/index.php` | Giao diện nhà cung cấp + form thêm/sửa + modal xác nhận xóa. |
| | `app/views/storefront/home/index.php` | Giao diện trang chủ khách hàng sạch sẽ, tách biệt hoàn toàn với khu vực Admin. |
| | `public/js/admin-products.js` | Xử lý AJAX lọc/tìm kiếm/phân trang Admin, kiểm tra SKU trùng, sửa nhanh tại bảng (khôi phục giá trị cũ nếu lỗi), xóa sản phẩm và thêm/bớt dòng thông số. |

---

## 3. Kiểm tra nghiệm thu Giai đoạn 1

- **Kiểm tra cú pháp (`php -l`):** Tất cả 39 file PHP trong dự án đều hợp lệ (`No syntax errors detected`).
- **Cơ sở dữ liệu MySQL (`camera_store_db`):** Đã import đầy đủ với `--default-character-set=utf8mb4`, hiển thị tiếng Việt có dấu chuẩn xác 100% trên cả trang Khách và trang Quản trị.
- **Đáp ứng các Test Case trong Kế hoạch kiểm thử (Mục 13):**
  - `TC01`, `TC02`, `TC03`: Thêm/sửa sản phẩm, báo trùng SKU bằng AJAX + backend, validate độ dài chuỗi và số không âm.
  - `TC04`, `TC05`, `TC06`: Chặn xóa danh mục/thương hiệu/nhà cung cấp đang có sản phẩm; xóa mềm (`INACTIVE`) sản phẩm đã có lịch sử kho; xóa cứng + xóa ảnh sản phẩm chưa có lịch sử.
  - `TC10`: Whitelist cột sắp xếp trong `ProductQuery`.
  - `TC21`, `TC22`: Sửa nhanh giá/ngưỡng/trạng thái bằng AJAX và từ chối nếu gửi trường `quantity`.
  - `TC23`, `TC24`, `TC25`: Xuất CSV đúng bộ lọc + chống chèn công thức; upload ảnh chặn SVG và file giả mạo.
