# Báo cáo chi tiết Giai đoạn 2: Quản lý Kho, Phiếu nhập nhiều dòng & Lịch sử Tồn kho

- **Dự án:** Website quản lý sản phẩm & tồn kho thiết bị chụp ảnh (ProGear Studio)
- **Thư mục mã nguồn:** `C:\xampp\htdocs\camera-rental`
- **Repository GitHub:** `https://github.com/huzzu311204/Web-.git` (nhánh `main`)
- **Ngày hoàn thành:** 31/10/2026

---

## 1. Các hạng mục sửa chữa nền (`P0-1` đến `P0-5`) và rà soát bảo mật kho

Mỗi mục được thực hiện và kiểm tra trong một commit độc lập:
1. **`P0-1` (`7023014`):** Sửa `.gitignore` (`/uploads/` thay vì `uploads/`), sửa biểu thức `FilesMatch` trong `public/uploads/products/.htaccess` và đưa file `.htaccess` vào Git (`git ls-files` đã ghi nhận).
2. **`P0-2` & Xoay mật khẩu Admin (`3bda5f6`, `1c87c9a`):** Gỡ bỏ mật khẩu admin dạng văn bản rõ khỏi tài liệu/schema, đổi sang bcrypt hash mới trong `database/schema.sql` và trong MySQL đang chạy; tạo `DUMMY_HASH` riêng biệt trong `AuthController`.
3. **`P0-3` (`694e7b4`):** Bổ sung `Category::isDescendantOrSelf()` và dùng tập `$visited` trong `Category::getCategoryAndChildrenIds()` để chặn vòng lặp cha–con và giữ chặt quy tắc cây danh mục tối đa 2 cấp.
4. **`P0-4` & Bắt đúng `SQLSTATE 23000` (`1d9569a`, `28824a7`):** Chỉ xóa ảnh cũ sau khi `updateWithSpecs()` thành công; tại `AdminProductApi::action()` chỉ chuyển sang `INACTIVE` khi `PDOException` có mã `'23000'` (vi phạm khóa ngoại), các lỗi DB khác ném lại cho Router ghi log và trả thông báo chung.
5. **`P0-5` (`fdf1aa4`):** Escape ký tự đại diện `%` và `_` trong `LIKE ... ESCAPE '\\'` của `ProductQuery`; thêm tùy chọn *"Xóa ảnh hiện tại"* trong form chỉnh sửa sản phẩm; siết chặt `Csrf::safeRedirectTarget()`.
6. **Chặn `delta` tùy ý ở `AdminStockApi::adjust` (`6c77311`):** Loại bỏ nhánh nhận trực tiếp `delta` từ client; chỉ chấp nhận `EXPORT`, `ADJUSTMENT_ADD`, `ADJUSTMENT_SUB` với `quantity` nguyên dương `1..100000` và kiểm tra chặn ngày nhập kho ở tương lai trong `StockService::createReceipt()`.

---

## 2. Danh sách các file đã xây dựng trong Giai đoạn 2

| Nhóm | File | Chi tiết nghiệp vụ |
|---|---|---|
| **Services** | `app/services/StockService.php` | • `adjust($productId, $delta, $type, $reason, $userId)`: Mở PDO Transaction, khóa dòng sản phẩm bằng `SELECT ... FOR UPDATE`, kiểm tra tồn kho sau thao tác `>= 0`, cập nhật `products.quantity` và ghi `stock_logs` (`EXPORT` hoặc `ADJUSTMENT`) trong cùng transaction.<br>• `createReceipt($supplierId, $importDate, $lines, $note, $userId)`: Kiểm tra nhà cung cấp, chặn ngày nhập tương lai, kiểm tra từng dòng hàng, từ chối trùng sản phẩm trong cùng phiếu, tự tính lại `subtotal` và `total_amount` ở server, sắp xếp `product_id` tăng dần để tránh deadlock, khóa dòng `FOR UPDATE`, ghi `import_receipts`, `import_receipt_details`, cộng `products.quantity` và ghi `stock_logs` (`IMPORT`). Nếu 1 dòng lỗi thì `rollBack()` toàn bộ. |
| **Models** | `app/models/ImportReceipt.php` | Phân trang danh sách phiếu nhập kho (lọc theo nhà cung cấp, khoảng ngày) và truy vấn chi tiết phiếu nhập kèm danh sách dòng hàng (`findWithDetails()`). |
| | `app/models/StockLog.php` | Phân trang lịch sử biến động tồn kho (`stock_logs`), hỗ trợ lọc theo sản phẩm, loại giao dịch (`IMPORT`, `EXPORT`, `ADJUSTMENT`) và khoảng ngày. |
| **Controllers** | `app/controllers/admin/ImportReceiptController.php` | 4 action: `index` (danh sách phiếu nhập), `create` (lập phiếu nhập kho nhiều dòng), `detail` (chi tiết phiếu nhập), `printReceipt` (trang in phiếu nhập khổ A4). |
| | `app/controllers/admin/StockController.php` | `index` (hiển thị lịch sử tồn kho) và `adjust` (endpoint POST dự phòng khi trình duyệt tắt JS). |
| | `app/controllers/api/AdminStockApi.php` | Endpoint AJAX `POST api/admin/stock-adjust`: chỉ nhận `EXPORT`, `ADJUSTMENT_ADD`, `ADJUSTMENT_SUB`, gọi `StockService::adjust()` và trả về số tồn mới, HTML badge tồn kho mới, class tô màu dòng mới và tổng số sản phẩm sắp hết/hết hàng để cập nhật badge trên Sidebar Admin. |
| **Views & JS** | `app/views/admin/import_receipts/index.php` | Màn hình danh sách phiếu nhập kho có bộ lọc Nhà cung cấp, Từ ngày – Đến ngày và phân trang. |
| | `app/views/admin/import_receipts/form.php` | Màn hình lập phiếu nhập nhiều dòng động, tự tính tạm tính phía client. |
| | `app/views/admin/import_receipts/detail.php` | Màn hình chi tiết phiếu nhập (thông tin NCC, người lập, bảng dòng hàng, nút In phiếu A4). |
| | `app/views/admin/import_receipts/print.php` | Giao diện bản in phiếu nhập kho A4 dùng `app/views/layouts/print.php` và `public/css/print.css` (chỉ dùng font `Be Vietnam Pro` + `tabular-nums`). |
| | `app/views/admin/stock/index.php` | Màn hình Lịch sử biến động tồn kho hiển thị rõ: Thời điểm, Thiết bị, Loại giao dịch, Tồn trước, Thay đổi (`+/-`), Tồn sau, Lý do / Mã phiếu nhập liên kết và Người thao tác. |
| | `public/js/admin-stock.js` | Xử lý thêm/xóa dòng sản phẩm trong form lập phiếu nhập, gợi ý đơn giá nhập và tính tổng tiền tạm tính. |
| | `public/js/admin-products.js` | Nối form trong Modal Xuất kho / Điều chỉnh vào `api/admin/stock-adjust`, cập nhật trực tiếp số tồn, badge, màu dòng và badge cảnh báo Sidebar mà không cần tải lại trang. |

---

## 3. Hạn chế đã biết (Known Limitations)

1. **Giới hạn đăng nhập sai liên tiếp:** Bộ đếm lần đăng nhập sai hiện được lưu trong `$_SESSION['_login_attempts']` (khóa tạm 120 giây sau 5 lần thử sai); nếu người dùng xóa cookie session thì bộ đếm trong phiên sẽ được đặt lại.
2. **Không cho sửa / xóa phiếu nhập kho:** Hệ thống chủ ý khóa sửa/xóa phiếu nhập sau khi đã lưu để lịch sử `import_receipts` luôn khớp tuyệt đối với `stock_logs`. Nếu cần điều chỉnh số lượng thực tế, quản trị viên thực hiện qua nghiệp vụ **Điều chỉnh kiểm kê (`ADJUSTMENT`)** có ghi lý do.

---

## 4. Kết quả kiểm thử thực tế Giai đoạn 2 (`TC14` – `TC20`)

1. **Kiểm tra cú pháp (`php -l`):** Đã chạy `php -l` trên toàn bộ các file PHP trong dự án — tất cả đều báo `No syntax errors detected`.
2. **Kết quả chạy kiểm thử với MySQL thật (`camera_store_db`):**
   - **`TC14` (4 nhãn tồn kho):** Kiểm tra `UiHelper::stockState()` tại `quantity = 0` (`OUT_OF_STOCK` — Hết hàng), `quantity = min_stock` (`LOW_STOCK` — Sắp hết), `quantity > min_stock` (`IN_STOCK` — Còn hàng) và `status = 'INACTIVE'` (`DISCONTINUED` — Ngừng kinh doanh) $\rightarrow$ **ĐẠT**.
   - **`TC15` (Lập phiếu nhập hợp lệ):** Tạo phiếu nhập với 2 thiết bị đơn giá `800.000 đ` $\rightarrow$ Server tự tính `total_amount = 1.600.000 đ`, tồn kho tăng từ `0` lên `2`, có bản ghi `stock_logs` loại `IMPORT` gắn đúng `receipt_id` $\rightarrow$ **ĐẠT**.
   - **`TC16` (Phiếu nhập lỗi giữa chừng):** Tạo phiếu nhập 2 dòng trong đó dòng 1 hợp lệ (`product_id = 5`), dòng 2 không tồn tại (`product_id = 999999`) $\rightarrow$ `StockService::createReceipt()` ném `DomainException` và `rollBack()` toàn bộ: tồn kho sản phẩm `#5` giữ nguyên `0`, số phiếu nhập (`2`) và số dòng `stock_logs` (`37`) không thay đổi $\rightarrow$ **ĐẠT**.
   - **`TC17` (Xuất nhiều hơn tồn):** Gọi `StockService::adjust()` xuất `1` cho sản phẩm đang có `quantity = 0` $\rightarrow$ Bị từ chối với thông báo *"Tồn kho không đủ để thực hiện thao tác này (Hiện tồn: 0)."*, tồn kho không đổi $\rightarrow$ **ĐẠT**.
   - **`TC18` (Điều chỉnh về đúng 0):** Điều chỉnh giảm tồn kho về đúng `0` $\rightarrow$ Hợp lệ, `quantity_after = 0`, hiển thị nhãn **Hết hàng** $\rightarrow$ **ĐẠT**.
   - **`TC19` (Hai yêu cầu cùng xuất khi tồn = 1):** Khi tồn kho còn `1`, yêu cầu xuất thứ nhất khóa dòng `FOR UPDATE` và đưa tồn về `0`; yêu cầu xuất thứ hai bị từ chối với lỗi không đủ tồn kho $\rightarrow$ **ĐẠT**.
   - **`TC20` (Đối chiếu tồn kho với tổng `change_qty` trong `stock_logs`):** Đã chạy truy vấn đối chiếu trên toàn bộ 28 sản phẩm trong `camera_store_db` $\rightarrow$ **0/28 sản phẩm bị lệch** (khớp 100%).

### Truy vấn SQL kiểm tra tính toàn vẹn tồn kho (`TC20`) đã chạy thực tế:

```sql
SELECT 
    p.id,
    p.code,
    p.name,
    p.quantity AS current_stock,
    COALESCE(SUM(l.change_qty), 0) AS total_logged_change,
    (p.quantity - COALESCE(SUM(l.change_qty), 0)) AS diff
FROM products p
LEFT JOIN stock_logs l ON l.product_id = p.id
GROUP BY p.id, p.code, p.name, p.quantity
ORDER BY p.id ASC;
```
