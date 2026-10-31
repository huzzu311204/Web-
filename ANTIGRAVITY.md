# ANTIGRAVITY.md — Hướng dẫn làm việc cho dự án ProGear Studio

> Đặt file này ở thư mục gốc dự án (có thể đổi tên thành `AGENTS.md` nếu Antigravity chỉ đọc tên đó).
> Đọc **toàn bộ** file trước khi sửa code. Làm theo thứ tự ở Mục 5 → 6.

---

## 1. Vai trò và quy tắc làm việc

Bạn là lập trình viên PHP thuần (không framework) đang hoàn thiện đồ án **Website quản lý sản phẩm & tồn kho thiết bị chụp ảnh**.

**Quy tắc bắt buộc**
1. Làm **từng nhiệm vụ nhỏ**, mỗi nhiệm vụ một commit (`feat:`, `fix:`, `docs:`). Không gộp nhiều giai đoạn vào một commit.
2. **Không đổi tên cột/bảng** đã có trong `database/schema.sql`. Nếu cần thêm cột thì viết file migration mới `database/migrations/NNN_ten.sql` và cập nhật `schema.sql` cho khớp.
3. **Mọi truy vấn SQL dùng prepared statements** (dấu `?` hoặc tham số tên). Không nối chuỗi từ dữ liệu người dùng. Cột `ORDER BY` lấy từ whitelist.
4. **Mọi dữ liệu xuất ra HTML đi qua `Response::e()`**. Không echo trực tiếp biến người dùng/DB.
5. Không tự thêm kiểm tra quyền/CSRF trong controller; **router đã kiểm tra tập trung** qua tham số `auth`, `method`, `ajax` khi khai báo route.
6. Không dùng thư viện PHP bên ngoài (không Composer). Frontend chỉ dùng Bootstrap 5 + Bootstrap Icons + JS thuần (`fetch`).
7. Mọi thao tác đổi `products.quantity` **chỉ đi qua `StockService`** (Mục 6) và **luôn ghi `stock_logs`** trong cùng transaction.
8. Khi xong mỗi nhiệm vụ: chạy `php -l` cho các file đã sửa, chạy test thủ công liên quan (Mục 8), rồi mới commit.
9. **Không commit** mật khẩu, file `config/database.local.php`, ảnh upload thật.
10. Viết tiếng Việt có dấu cho mọi thông báo hiển thị cho người dùng; comment code có thể bằng tiếng Việt.

---

## 2. Bối cảnh dự án

- **Đề tài:** quản lý sản phẩm & tồn kho thiết bị nhiếp ảnh (máy ảnh, ống kính, đèn, phụ kiện). Chuyển đổi từ mã nguồn cũ "cho thuê camera" nên thư mục vẫn tên `camera-rental`.
- **Stack:** PHP 8+ (strict_types), MySQL/MariaDB (`camera_store_db`, utf8mb4), PDO, kiến trúc MVC tự viết, chạy trên XAMPP.
- **Chạy local:** `C:\xampp\htdocs\camera-rental`, mở `http://localhost/camera-rental/` (file `index.php` ở gốc chuyển tiếp vào `public/`).
- **Repo:** `https://github.com/huzzu311204/Web-` (nhánh `main`).
- **Tài khoản demo:** `admin` (mật khẩu xem file cấu hình local, **không ghi trong tài liệu/repo**).
- **Khách hàng không cần đăng nhập.** Chỉ có 1 vai trò quản trị `ADMIN`.
- **Không có dashboard biểu đồ.** Trang mặc định sau đăng nhập là danh sách sản phẩm admin.
- **Giao diện:** 1 font sans-serif **Be Vietnam Pro** (số liệu dùng `tabular-nums`), 1 màu nhấn, theo `design-system/camera-rental/MASTER.md`. Layout khách (`layouts/front.php`) **tuyệt đối không có link/nút Quản trị**.

---

## 3. Cấu trúc thư mục và quy ước code

```
public/index.php          # entry, autoloader PSR-4 (App\*), bảng route
app/core/                 # Router, Auth, Csrf, Database, Response, Validator, UiHelper
app/models/               # Product, ProductSpec, Category, Brand, Supplier, User
app/services/             # ProductQuery, ImageUploader, CsvExporter  (+ StockService sẽ thêm)
app/controllers/admin/    # Auth, User, Product, Category, Brand, Supplier
app/controllers/api/      # AdminProductApi (+ AdminStockApi, ProductApi, CompareApi cần thêm)
app/controllers/storefront/ # HomeController (+ Catalog, Compare cần thêm)
app/views/{admin,storefront,layouts}/
public/{css/theme.css, js/app.js, js/admin-products.js, uploads/products/}
database/schema.sql
```

**Namespace:** `App\Core`, `App\Models`, `App\Services`, `App\Controllers\{Admin,Api,Storefront}`. Mỗi file bắt đầu `declare(strict_types=1);`.

**API nội bộ có sẵn (dùng lại, đừng viết trùng)**

| Lớp | Hàm chính |
|---|---|
| `Response` | `json($data,$code)`, `redirect($url)`, `view($path,$data,$layout)`, `setFlash/getFlashes`, `e($v)`, `money($n)` |
| `Csrf` | `token()`, `field()`, `verify()`, `requireValid()`, `safeRedirectTarget()` |
| `Auth` | `check()`, `user()`, `id()`, `login()`, `logout()`, `requireAdmin()` |
| `Database` | `Database::getConnection(): PDO` |
| `Validator` | `isValidLength`, `isNonNegativeInt`, `isPositiveInt`, `sanitizeSort`, `clampInt`, `isValidEmail`, `isValidVietnamPhone` |
| `UiHelper` | `stockState`, `stockBadge`, `productStatusBadge`, `stockLogTypeBadge`, `productRowClass`, `formatReceiptCode` (PN-000001), `imageUrl` |
| `ProductQuery` | `paginate($filters,$isStorefront,$perPage)`, `fetchAllFiltered(...)` — dùng chung admin/khách/CSV |
| `Product` (model) | `findById`, `codeExists`, `hasInventoryHistory`, `createWithSpecs`, `updateWithSpecs`, `updateInline`, `setStatus`, `hardDelete`, `getRelatedProducts` |

**Mẫu route** (`public/index.php`):
```php
$router->add('admin/products/save', [ProductController::class,'save'], ['auth'=>'admin','method'=>'POST']);
$router->add('api/admin/stock-adjust', [AdminStockApi::class,'adjust'], ['auth'=>'admin','method'=>'POST','ajax'=>true]);
```
**Phản hồi AJAX:** thành công `{ "ok": true, ... }`, thất bại `{ "ok": false, "message": "..." }` kèm mã HTTP đúng (401/403/405/422).
**Nhãn tồn kho (4 trạng thái):** `quantity = 0` → Hết hàng; `0 < quantity <= min_stock` → Sắp hết; `quantity > min_stock` → Còn hàng; `status = INACTIVE` → Ngừng kinh doanh. Luôn dùng `UiHelper`.

---

## 4. Trạng thái hiện tại

**Đã xong (đã đối chiếu code):**
- **GĐ0:** schema 9 bảng + dữ liệu mẫu (28 sản phẩm, 37 dòng `stock_logs`, tồn khớp), Router kiểm soát tập trung, Auth đối chiếu DB mỗi request, CSRF, headers bảo mật, login chống timing + giới hạn thử sai, theme + badge.
- **GĐ1:** CRUD sản phẩm (thông số động, upload ảnh, SKU check AJAX, sửa nhanh, xóa cứng/mềm, xuất CSV), CRUD danh mục/thương hiệu/nhà cung cấp, danh sách admin AJAX, trang chủ khách.

**Chưa có controller (route đã khai báo → hiện đang trả 500):**
`StockController`, `ImportReceiptController`, `AdminStockApi`, `CatalogController`, `CompareController`, `ProductApi`, `CompareApi`.

---

## 5. VIỆC CẦN SỬA NGAY (làm trước GĐ2) — mỗi mục 1 commit

### P0-1. Commit `.htaccess` của thư mục upload
- File `public/uploads/products/.htaccess` có ở máy local nhưng **không nằm trong Git** vì `.gitignore` có dòng `uploads/`.
- Sửa `.gitignore`: đổi `uploads/` thành `/uploads/`; giữ `public/uploads/products/*` và `!public/uploads/products/.htaccess`.
- Chạy `git add -f public/uploads/products/.htaccess`.
- Trong file `.htaccess` đó, sửa danh sách đuôi: `sh| shtml|svg` → `sh|shtml|svg` (đang thừa dấu cách).
- **Nghiệm thu:** `git ls-files | grep uploads` thấy `.htaccess`.

### P0-2. Bỏ mật khẩu admin khỏi tài liệu công khai
- Xóa mật khẩu admin mặc định khỏi `GIAI_DOAN_0.md` và comment trong `database/schema.sql`.
- Tạo `DUMMY_HASH` riêng trong `AuthController` (hiện trùng hash admin): sinh bằng `password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)`.
- Ghi chú trong README: "đổi mật khẩu admin sau khi cài".

### P0-3. Chặn vòng cha–con trong danh mục
- `CategoryController::save`: khi chọn `parent_id`, từ chối nếu `parent_id` là chính nó **hoặc là hậu duệ của nó** (duyệt cây bằng vòng lặp có giới hạn độ sâu). Giữ quy tắc cây 2 cấp nếu schema/giao diện đang dùng.
- Đảm bảo `Category::getCategoryAndChildrenIds` không lặp vô hạn (dùng tập `visited`).
- **Nghiệm thu:** đặt A làm con B rồi đặt B làm con A → báo lỗi rõ ràng.

### P0-4. Độ bền khi sửa/xóa sản phẩm
- `ProductController::edit`: chỉ gọi `ImageUploader::deleteOldImage()` **sau khi** `updateWithSpecs()` thành công; nếu DB lỗi thì xóa ảnh mới vừa upload.
- `ProductController::create`: nếu `createWithSpecs()` ném lỗi thì xóa ảnh vừa upload.
- `api/admin/product-action` (xóa): bọc `hasInventoryHistory` + `hardDelete` trong transaction hoặc bắt `PDOException` (lỗi khóa ngoại) và chuyển sang `INACTIVE` thay vì trả 500.

### P0-5. Việc nhỏ
- Escape `%` và `_` trong từ khóa `LIKE` của `ProductQuery` (`ESCAPE '\\'`).
- Thêm ô "Xóa ảnh hiện tại" trong form sản phẩm.
- Giới hạn đăng nhập sai hiện lưu trong session (xóa cookie là bỏ qua được) → ghi chú đây là hạn chế đã biết trong README; **không bắt buộc sửa**.
- `safeRedirectTarget()`: kế hoạch ghi "không tin Referer" → ưu tiên chuyển về route cố định (ví dụ `home` hoặc `admin/products`), chỉ dùng Referer nếu cùng host.

---

## 6. GIAI ĐOẠN 2 — Kho (làm tiếp ngay sau mục 5)

### 6.1. File cần tạo
```
app/services/StockService.php
app/models/ImportReceipt.php        # master + details
app/models/StockLog.php
app/controllers/admin/ImportReceiptController.php   # index, create, detail, printReceipt
app/controllers/admin/StockController.php           # index (lịch sử), adjust (form POST dự phòng)
app/controllers/api/AdminStockApi.php               # adjust (AJAX)
app/views/admin/import_receipts/{index,form,detail,print}.php
app/views/admin/stock/index.php
public/js/admin-stock.js
```
Route đã khai báo sẵn trong `public/index.php`: `admin/import-receipts`, `/create`, `/detail`, `/print`, `admin/stock-logs`, `admin/stock/adjust`, `api/admin/stock-adjust`.

### 6.2. `StockService` (bắt buộc dùng transaction + khóa dòng)
```php
adjust(int $productId, int $delta, string $type /* EXPORT|ADJUSTMENT */, string $reason, int $userId): array
createReceipt(int $supplierId, string $importDate, array $lines, ?string $note, int $userId): int
```
Quy tắc `adjust`:
1. `beginTransaction()`; `SELECT quantity FROM products WHERE id=? FOR UPDATE`.
2. `$after = $before + $delta`; nếu `$after < 0` → ném `DomainException('Tồn kho không đủ để thực hiện thao tác này.')`.
3. `UPDATE products SET quantity=?` và `INSERT stock_logs (product_id,user_id,type,change_qty,quantity_before,quantity_after,reason)`; `commit()`; lỗi thì `rollBack()` rồi ném lại.
4. `EXPORT`: `delta` luôn **âm** (client gửi số dương, server đổi dấu). `ADJUSTMENT`: cho phép ± nhưng khác 0. **Lý do bắt buộc** (1–255 ký tự). Tồn sau = 0 là hợp lệ (TC18).

Quy tắc `createReceipt` (TC15, TC16):
- Validate: nhà cung cấp tồn tại, ít nhất 1 dòng, mỗi dòng `product_id` tồn tại, `quantity > 0` (nguyên), `import_price >= 0`; **gộp hoặc từ chối dòng trùng sản phẩm**.
- **Backend tự tính** `subtotal = quantity * import_price` và `total_amount`; bỏ qua mọi giá trị do client gửi.
- Trong 1 transaction: ghi `import_receipts` → với từng dòng (sắp theo `product_id` tăng dần để tránh deadlock): ghi `import_receipt_details`, `SELECT ... FOR UPDATE`, `UPDATE products SET quantity = quantity + ?`, ghi `stock_logs` (`IMPORT`, `receipt_id`, `quantity_before/after`, `user_id`).
- Một dòng lỗi → **rollback toàn bộ**, tồn kho và log không đổi.
- Không có chức năng sửa/xóa phiếu nhập (để lịch sử không bị phá). Nếu cần hủy thì tạo điều chỉnh.
- Mã phiếu hiển thị bằng `UiHelper::formatReceiptCode($id)`.

### 6.3. Màn hình
| Màn hình | Yêu cầu |
|---|---|
| Danh sách phiếu nhập | Mã PN, ngày nhập, nhà cung cấp, người lập, tổng tiền; phân trang |
| Form phiếu nhập | Chọn NCC + ngày; thêm/bớt dòng (sản phẩm, SL, giá nhập) bằng JS; hiện tạm tính nhưng server tính lại |
| Chi tiết phiếu | Header + bảng dòng + nút "In phiếu" (dùng `layouts/print.php`, `print.css`) |
| Modal Xuất kho / Điều chỉnh (trong `admin/products`) | Hiện tồn hiện tại và **tồn sau thao tác**; lý do bắt buộc; gọi `api/admin/stock-adjust`; thành công thì cập nhật dòng (số tồn + badge + tô dòng) và hiện toast; lỗi thì toast, giữ modal |
| Lịch sử tồn kho | Lọc theo sản phẩm, loại giao dịch, khoảng ngày; cột: thời điểm, người thao tác, tồn trước, thay đổi (+/−), tồn sau, lý do, phiếu nhập liên quan; phân trang |

- Modal xuất/điều chỉnh đã có sẵn khung trong `app/views/admin/products/index.php` và `public/js/admin-products.js` → **kiểm tra rồi nối vào endpoint mới**, đừng làm lại.
- Sidebar admin: badge số sản phẩm sắp hết/hết hàng cạnh "Sản phẩm" đã có; đảm bảo cập nhật đúng sau khi nhập/xuất.
- Nút lọc nhanh "Sắp hết / Hết hàng" ở danh sách admin đã có; kiểm tra hoạt động.

### 6.4. Nghiệm thu GĐ2
TC14–TC20 (Mục 8). Đặc biệt: TC19 (hai tab cùng xuất khi tồn còn 1 → chỉ một thành công), TC20 (tổng `change_qty` = `products.quantity`, viết thêm một script SQL kiểm tra để dán vào báo cáo).

---

## 7. Các giai đoạn tiếp theo

### GĐ3 — Storefront (khách)
- Tạo `CatalogController` (`index`, `detail`, `clearRecent`), `ProductApi::index`, views `storefront/catalog/{index,detail,_card}.php`.
- Danh sách: tìm kiếm tên/mã; lọc danh mục (cây), thương hiệu (chọn nhiều), khoảng giá, tình trạng tồn; sắp xếp (newest, price_asc, price_desc, name_asc); phân trang server; **chip bộ lọc** có nút xóa từng cái và "Xóa tất cả".
- Dùng `ProductQuery::paginate($filters, true, ...)`. Khách **chỉ thấy sản phẩm `ACTIVE`** (theo `ProductQuery` hiện tại; xác nhận lại).
- AJAX: `api/products` trả `{ok, html, total, page, pages}`; JS dùng `history.pushState` + `popstate` để Back/Forward khôi phục (TC08); mở thẳng URL có bộ lọc thì **server dựng sẵn kết quả** (TC09). Debounce 300ms cho ô tìm kiếm/giá; làm mờ danh sách khi đang tải; lỗi → toast.
- Bố cục: cột lọc sticky bên trái (≥ lg), `offcanvas` trên di động.
- Chi tiết: ảnh, tên, thương hiệu, giá, nhãn tồn, mô tả, bảng thông số, nút So sánh, **sản phẩm liên quan** (`Product::getRelatedProducts`, ưu tiên ống kính cùng "Ngàm" cho máy ảnh), khối "Đã xem gần đây".
- "Đã xem gần đây": `$_SESSION['recent_viewed']`, tối đa **8**, mới nhất trước, bỏ trùng (TC13); hiển thị ở trang chủ, chi tiết, cuối danh sách; nút xóa lịch sử (`recent/clear` POST + CSRF).

### GĐ4 — So sánh + AJAX admin còn thiếu
- `CompareApi::handle` (POST, `ajax`): action `add|remove|clear`, lưu session, **tối đa 3** (TC11 → 422 "Chỉ so sánh tối đa 3 sản phẩm"), chỉ nhận sản phẩm `ACTIVE`; trả danh sách + số lượng.
- Thanh so sánh cố định cuối màn hình (`.compare-bar-fixed`) và bộ đếm `#navbar-compare-count`.
- `CompareController::index`: bảng cột = sản phẩm, hàng = ảnh, tên, thương hiệu, giá, tồn kho + **hợp các thông số kỹ thuật**; ô thiếu hiển thị "—" (TC12). Cần ≥ 2 sản phẩm.
- Rà soát các AJAX admin đã có (sku-check, inline, action, products) theo TC21/22/27.

### GĐ5 — Hoàn thiện
- Xuất CSV đã có → kiểm tra TC23/24.
- Nhóm B theo thứ tự **B4 (in phiếu nhập) → B1 (nhập CSV có xem trước, giới hạn dung lượng/đuôi, validate từng dòng) → B2 (yêu thích) → B3 (yêu cầu tư vấn, bảng `inquiries`) → B5 (ảnh thứ hai khi hover)**. Chỉ làm khi mọi thứ trên đã ổn.
- Responsive 375/768/1024/1440 px (TC35), tắt `display_errors`, thay ảnh minh họa bằng ảnh thật, viết `README.md` (cài đặt, import SQL bằng `--default-character-set=utf8mb4`, tài khoản, hạn chế đã biết), chụp ảnh màn hình, soạn kịch bản demo 8–10 phút.

**Thứ tự cắt khi thiếu thời gian (cắt từ dưới lên):** Nhóm B → xuất CSV → đã xem gần đây → so sánh. Ưu tiên một bản chạy ổn, không lỗi khi demo.

---

## 8. Kế hoạch kiểm thử (tự chạy thủ công sau mỗi giai đoạn)

| Mã | Tình huống | Mong đợi |
|---|---|---|
| TC01 | Thêm sản phẩm hợp lệ | Thành công, hiện trong danh sách |
| TC02 | Mã trùng | Báo trùng (AJAX + khi lưu) |
| TC03 | Tên rỗng, giá âm, chuỗi quá dài | Báo lỗi validate, không lỗi DB |
| TC04 | Xóa danh mục/thương hiệu đang có sản phẩm | Từ chối |
| TC05 | Xóa sản phẩm đã có lịch sử kho | Chuyển `INACTIVE` |
| TC06 | Xóa sản phẩm chưa có dữ liệu | Xóa hẳn + dọn ảnh |
| TC07 | Lọc kết hợp danh mục, thương hiệu, giá, tồn | Kết quả + chip đúng |
| TC08 | Đổi bộ lọc rồi Back/Forward | Khôi phục đúng |
| TC09 | Mở URL có bộ lọc | Đúng kết quả ngay |
| TC10 | `sort` chứa ký tự lạ/SQL | Dùng mặc định, không lỗi |
| TC11 | So sánh quá 3 | Từ chối, báo rõ |
| TC12 | So sánh thông số khác nhau | Hợp thông số, thiếu là "—" |
| TC13 | Xem hơn 8 sản phẩm | Chỉ giữ 8 mới nhất |
| TC14 | Nhãn ở quantity = 0 / = min_stock / > min_stock | Hết hàng / Sắp hết / Còn hàng |
| TC15 | Lập phiếu nhập hợp lệ | Tồn tăng, có `stock_logs`, tổng tiền do server tính |
| TC16 | Phiếu nhập lỗi giữa chừng | Rollback toàn bộ |
| TC17 | Xuất nhiều hơn tồn | Từ chối, tồn không đổi |
| TC18 | Điều chỉnh về đúng 0 | Cho phép, nhãn Hết hàng |
| TC19 | Hai tab cùng xuất khi tồn = 1 | Chỉ một thành công |
| TC20 | Đối chiếu tồn với tổng `change_qty` | Khớp |
| TC21 | Sửa nhanh giá hợp lệ/không | Hợp lệ lưu; sai thì khôi phục + toast |
| TC22 | Gửi `quantity` vào endpoint sửa nhanh | Từ chối (422) |
| TC23 | Xuất CSV có bộ lọc | Đúng dòng lọc, đọc được tiếng Việt trong Excel |
| TC24 | Tên bắt đầu `=` hoặc `@` | Thêm `'` ở đầu ô |
| TC25 | Upload SVG / `.php` đổi `.jpg` / > 2 MB | Từ chối |
| TC26 | Vào admin khi chưa đăng nhập | Về trang đăng nhập |
| TC27 | Gọi AJAX admin khi chưa đăng nhập | 401 JSON |
| TC28 | POST sai CSRF | Từ chối, không redirect ra ngoài host |
| TC29 | `admin/logout` bằng GET | Không đăng xuất (405) |
| TC30 | Admin bị khóa khi đang đăng nhập | Request kế tiếp bị từ chối |
| TC31 | Đăng nhập sai nhiều lần | Bị khóa tạm |
| TC32 | `<script>` trong tên SP/lý do kho | Được escape |
| TC33 | Gây lỗi truy vấn có chủ ý | Chỉ thấy câu thông báo chung |
| TC34 | Route không tồn tại / sai phương thức | 404/405, không lộ tên class |
| TC35 | 375/768/1024/1440 px | Không tràn ngang |

---

## 9. Quy trình Git và định nghĩa hoàn thành

- Nhánh: làm trực tiếp trên `main` được, nhưng mỗi nhiệm vụ một commit; nên đặt tag `gd0`, `gd1`, `gd2`… khi xong giai đoạn.
- Mỗi giai đoạn xong: cập nhật `GIAI_DOAN_N.md` (mục tiêu, danh sách file, kết quả kiểm thử **thật**). **Chỉ ghi điều đã chạy thật**; nếu chưa chạy `php -l` hoặc chưa test thì ghi rõ "chưa kiểm tra".
- Không ghi mật khẩu hay khóa bí mật vào bất kỳ file nào trong repo.

**Dự án đạt khi:** chạy được theo README; CRUD đủ với MySQL thật; không trùng mã, không tồn âm; mọi thay đổi tồn đều có log và khớp số tồn; ≥ 4 chức năng AJAX có xử lý lỗi; khách xem–lọc–so sánh không cần đăng nhập, Back/chia sẻ URL đúng; admin được bảo vệ tập trung ở router; CSV và upload an toàn; giao diện nhất quán, responsive; có báo cáo + dữ liệu demo + kịch bản demo đã chạy thử một lần.

---

## 10. Việc KHÔNG được làm
- Không thêm lại module cho thuê, dashboard biểu đồ, hay link Quản trị ở giao diện khách.
- Không cho sửa `quantity` qua endpoint sửa nhanh hoặc form sản phẩm sau khi tạo (chỉ qua `StockService`).
- Không tin dữ liệu từ client cho `subtotal`, `total_amount`, `quantity_before/after`, `user_id`.
- Không dùng `innerHTML` với dữ liệu người dùng/server chưa escape; toast dùng `textContent`.
- Không bỏ qua lỗi: ghi `error_log`, hiển thị câu thông báo chung, không lộ SQL/tên bảng/tên class.
