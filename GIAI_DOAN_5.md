# Báo cáo chi tiết Giai đoạn 5: Hoàn thiện Giao diện Responsive, Kiểm thử Bảo mật Toàn diện (TC23–TC35) & Kịch bản Demo Bảo vệ

- **Dự án:** Website quản lý sản phẩm & tồn kho thiết bị chụp ảnh (ProGear Studio)
- **Thư mục mã nguồn:** `C:\xampp\htdocs\camera-rental`
- **Repository GitHub:** `https://github.com/huzzu311204/Web-.git` (nhánh `main`)
- **Ngày hoàn thành:** 31/10/2026

---

## 1. Mục tiêu của Giai đoạn 5

Hoàn thiện toàn bộ các yêu cầu nghiệm thu cuối cùng theo Mục `GĐ5 — Hoàn thiện` trong `ANTIGRAVITY.md` và Mục B trong `NHIEM_VU_ANTIGRAVITY.md`:
1. **Giao diện Responsive (`375px`, `768px`, `1024px`, `1440px` — `TC35`):**
   - Không tràn ngang (`overflow-x: hidden`, `.admin-main { min-width: 0 }`, `.table-responsive { max-width: 100%; overflow-x: auto }`).
   - Tự động thu gọn Sidebar Admin sang Offcanvas trên màn hình `< 992px`, thu gọn bộ lọc danh mục khách hàng trên màn hình di động, điều chỉnh cỡ chữ tiêu đề (`h1`, `h2`) và khoảng đệm hợp lý trên màn hình `375px`.
2. **Tắt `display_errors` & Ẩn chi tiết lỗi nội bộ (`TC33`, `TC34`):**
   - `ini_set('display_errors', '0')` ngay đầu `public/index.php`.
   - Khối `try ... catch (\Throwable $e)` toàn cục ghi chi tiết lỗi vào `error_log` máy chủ, chỉ trả thông báo thân thiện cho người dùng (HTML 500 hoặc JSON 500 đối với route `api/*`), tuyệt đối không lộ câu lệnh SQL, tên bảng hay đường dẫn hệ thống.
3. **Kiểm thử thực tế toàn bộ nhóm Xuất CSV, Upload ảnh & Bảo mật (`TC23` – `TC35`):**
   - Xuất CSV theo bộ lọc kèm UTF-8 BOM (`TC23`) và chống CSV Formula Injection (`TC24`).
   - Chặn upload SVG, file `.php` ngụy trang `.jpg`, và file `> 2 MB` (`TC25`).
   - Kiểm soát truy cập tập trung tại `Router`, chống Open Redirect khi lỗi CSRF (`TC28`), chặn `GET admin/logout` (`TC29`), thu hồi quyền tức thì khi khóa tài khoản (`TC30`), khóa tạm sau 5 lần đăng nhập sai (`TC31`), chống XSS bằng `Response::e()` (`TC32`).
4. **Hoàn thiện tài liệu `README.md` & Kịch bản Demo 8–10 phút:**
   - Cung cấp hướng dẫn cài đặt từng bước trên XAMPP, lệnh nạp SQL với `--default-character-set=utf8mb4`, cấu trúc thư mục, điểm nhấn kỹ thuật, hạn chế đã biết và kịch bản trình diễn 8–10 phút.

---

## 2. Danh sách các file đã cập nhật / bổ sung trong Giai đoạn 5

| File | Nội dung hoàn thiện |
|---|---|
| `public/css/theme.css` | Bổ sung quy tắc responsive Mục 9 cho màn hình `375px` (`max-width: 575.98px`) và `.table-responsive` chống tràn ngang (`TC35`). |
| `README.md` | Hoàn thiện tài liệu cài đặt XAMPP, lệnh import `schema.sql` chuẩn `utf8mb4`, cấu trúc thư mục, điểm nhấn kiến trúc & hạn chế đã biết (không lưu mật khẩu plaintext). |
| `GIAI_DOAN_5.md` | Báo cáo nghiệm thu Giai đoạn 5, kết quả chạy kiểm thử thực tế `TC23`–`TC35`, bảng tổng kết `TC01`–`TC35` và kịch bản demo 8–10 phút. |

---

## 3. Kết quả kiểm thử thực tế Giai đoạn 5 (`TC23` – `TC35`)

Đã thực thi kịch bản kiểm thử tự động kết hợp PHP CLI và HTTP `curl` trực tiếp trên máy chủ Apache + MySQL (`camera_store_db`):

| Mã TC | Tình huống kiểm thử | Kết quả thực tế | Trạng thái |
|---|---|---|---|
| **TC23** | Xuất CSV có bộ lọc (`GET index.php?r=admin/products/export-csv&brand_id=1`) | File trả về có 3 byte đầu là UTF-8 BOM (`\xEF\xBB\xBF`), chứa đúng các sản phẩm Sony (`CAM-SN-FX3`...) và loại trừ sản phẩm hãng khác (`CAM-CN-R5`). | **ĐẠT** |
| **TC24** | Tên / mã bắt đầu bằng `=`, `+`, `-`, `@`, `\t`, `\r` khi xuất CSV | `CsvExporter::sanitizeCell()` tự động thêm dấu nháy đơn `'` ở đầu (`'=1+1`, `'+SUM(A1:A2)`, `'@HYPERLINK()`) chống thực thi công thức độc hại trong Excel. | **ĐẠT** |
| **TC25** | Upload file `.svg`, file `.php` đổi đuôi thành `.jpg`, hoặc file `> 2 MB` | `ImageUploader::uploadProductImage()` kiểm tra bằng `finfo` + `getimagesize()` và từ chối cả 3 trường hợp với thông báo lỗi rõ ràng. | **ĐẠT** |
| **TC26** | Truy cập `index.php?r=admin/products` khi chưa đăng nhập | `Router` chuyển hướng `HTTP/1.1 302 Found` về `index.php?r=admin/login`. | **ĐẠT** |
| **TC27** | Gọi AJAX admin (`api/admin/products`, `api/admin/sku-check`, `api/admin/product-inline`) khi chưa đăng nhập | Trả về `HTTP/1.1 401 Unauthorized` kèm JSON `{"ok":false,"message":"Truy cập bị từ chối. Vui lòng đăng nhập quyền Quản trị viên."}`. | **ĐẠT** |
| **TC28** | Gửi `POST` thiếu/sai CSRF token kèm `Referer: https://external.example.org/...` | AJAX trả về `403 Forbidden`; form thường chặn Open Redirect và chỉ chuyển về `index.php?r=home` (hoặc route nội bộ hợp lệ cùng host). | **ĐẠT** |
| **TC29** | Gọi `GET index.php?r=admin/logout` | Trả về `HTTP/1.1 405 Method Not Allowed` (kèm header `Allow: POST`), phiên đăng nhập của Admin vẫn giữ nguyên (`GET admin/products` sau đó trả `200 OK`). | **ĐẠT** |
| **TC30** | Admin đang đăng nhập nhưng bị chuyển `status = 'INACTIVE'` trong DB | Ngay ở request kế tiếp, `Auth::user()` đối chiếu DB phát hiện `status !== 'ACTIVE'`, hủy phiên và trả về `401 Unauthorized` (AJAX) hoặc chuyển về trang login. | **ĐẠT** |
| **TC31** | Đăng nhập sai mật khẩu 5 lần liên tiếp | Lần thử thứ 6 bị chặn ngay từ đầu và hiển thị thông báo khóa tạm 120 giây. | **ĐẠT** |
| **TC32** | Chèn `<script>console.log(1)</script>` vào dữ liệu hiển thị | `Response::e()` mã hóa thành `&lt;script&gt;console.log(1)&lt;/script&gt;`, trình duyệt hiển thị dưới dạng văn bản thuần, không thực thi script. | **ĐẠT** |
| **TC33** | `display_errors` tắt & bắt ngoại lệ toàn cục khi lỗi DB/hệ thống | `ini_set('display_errors', '0')` được bật tại `public/index.php`; lỗi bất ngờ được ghi vào `error_log` và chỉ hiện thông báo chung, không lộ SQL hay cấu trúc bảng. | **ĐẠT** |
| **TC34** | Truy cập route không tồn tại (`?r=non-existent-route-xyz` & `?r=api/non-existent-xyz`) | Trả về đúng mã `HTTP/1.1 404 Not Found` (trang 404 giao diện Dark Studio cho web, JSON 404 cho API), không lộ tên class hay đường dẫn file. | **ĐẠT** |
| **TC35** | Kiểm tra bố cục ở các độ rộng `375px`, `768px`, `1024px`, `1440px` | Các bảng dữ liệu cuộn ngang mượt bên trong `.table-responsive`, Sidebar tự chuyển sang Offcanvas dưới `992px`, không tràn ngang trang. | **ĐẠT** |

---

## 4. Tổng hợp trạng thái 35 Tình huống Kiểm thử (`TC01` – `TC35`)

- **Giai đoạn 0 & 1 (`TC01` – `TC06`):** Thêm sản phẩm + thông số (`TC01`), chặn trùng mã SKU (`TC02`), chặn vòng lặp danh mục cha–con (`TC03`), không sửa trực tiếp tồn kho khi sửa SP (`TC04`), xóa mềm khi có lịch sử kho (`TC05`), xóa cứng + dọn file ảnh khi chưa có lịch sử (`TC06`) $\rightarrow$ **ĐẠT (6/6)**.
- **Giai đoạn 3 & 4 (`TC07` – `TC13`):** Lọc kết hợp + chip bộ lọc (`TC07`), AJAX `pushState`/`popstate` (`TC08`), mở trực tiếp URL có bộ lọc (`TC09`), whitelist `sort` chống SQLi (`TC10`), giới hạn so sánh tối đa 3 SP (`TC11`), hợp thông số kỹ thuật hiển thị `"—"` khi thiếu (`TC12`), lưu tối đa 8 SP đã xem gần đây (`TC13`) $\rightarrow$ **ĐẠT (7/7)**.
- **Giai đoạn 2 (`TC14` – `TC20`):** Nhãn tồn kho chính xác (`TC14`), lập phiếu nhập tăng tồn + ghi log (`TC15`), rollback toàn bộ khi có dòng lỗi (`TC16`), chặn xuất quá tồn (`TC17`), điều chỉnh về 0 (`TC18`), khóa dòng `FOR UPDATE` chống âm kho khi 2 tab cùng xuất (`TC19`), tổng `SUM(change_qty)` khớp 100% `products.quantity` trên cả 28 sản phẩm (`TC20`) $\rightarrow$ **ĐẠT (7/7)**.
- **Giai đoạn 4 & 5 (`TC21` – `TC35`):** Sửa nhanh + khôi phục khi lỗi (`TC21`), chặn gửi `quantity` vào inline edit (`TC22`), xuất CSV theo bộ lọc + UTF-8 BOM (`TC23`), chống Formula Injection (`TC24`), chặn upload độc hại (`TC25`), bảo mật phân quyền / CSRF / Method / Rate-limit / XSS / 404 / Responsive (`TC26`–`TC35`) $\rightarrow$ **ĐẠT (15/15)**.

---

## 5. Kịch bản Demo Bảo vệ Đồ án (8 – 10 phút)

### Phần 1 — Giới thiệu & Showroom Khách hàng (3 phút)
1. **Trang chủ (`http://localhost/camera-rental/`):**
   - Giới thiệu giao diện **Dark Studio** (`#131416`, điểm nhấn vàng hổ phách `#f0a23b`, font *Be Vietnam Pro* với `tabular-nums` giúp các cột số và giá thẳng hàng).
   - Chỉ vào sản phẩm tiêu điểm (*Sony FX3*), danh mục thiết bị kèm số lượng SP đang bán và lưới thiết bị mới cập nhật.
2. **Lọc đa tiêu chí bằng AJAX (`http://localhost/camera-rental/public/index.php?r=catalog`):**
   - Chọn danh mục **Máy ảnh** (tự động lấy cả *Mirrorless* và *DSLR*), tích chọn thương hiệu **Sony** và **Canon**, nhập khoảng giá `30.000.000` – `70.000.000`, chọn **Đang còn hàng**.
   - Chỉ ra lưới sản phẩm, tổng số kết quả, các **Chip bộ lọc** và thanh URL tự cập nhật mượt mà không tải lại trang (`history.pushState`). Bấm nút **Back** trên trình duyệt để chứng minh trạng thái lọc được khôi phục (`popstate` — `TC08`).
3. **Chi tiết thiết bị & Gợi ý theo Ngàm + So sánh 3 thiết bị (`TC11`, `TC12`, `TC13`):**
   - Mở chi tiết máy ảnh **Sony Alpha A7 Mark III**: chỉ vào bảng thông số kỹ thuật và khu vực **"Ống kính & Thiết bị cùng Ngàm (Sony E)"** tự động gợi ý các ống kính `Sony E` tương thích.
   - Bấm **"Thêm vào Bảng so sánh"** cho 3 sản phẩm (1 thân máy + 2 ống kính), sau đó bấm thử sản phẩm thứ 4 để trình diễn thông báo chặn **tối đa 3 thiết bị (`422`)**.
   - Bấm **"So sánh ngay (3/3)"** ở thanh cố định cuối màn hình: chỉ ra bảng so sánh đã tự động hợp (union) toàn bộ thông số của cả máy ảnh và ống kính, những ô không có thông số hiển thị dấu `"—"` (`TC12`).

### Phần 2 — Nghiệp vụ Quản trị Sản phẩm & Kiểm soát Tồn kho Bất biến (4 phút)
1. **Đăng nhập Admin (`http://localhost/camera-rental/admin`):**
   - Đăng nhập bằng tài khoản `admin`. Chỉ ra **Badge cảnh báo đỏ** trên Sidebar cạnh mục *Sản phẩm* đếm số thiết bị đang hết hàng hoặc chạm ngưỡng sắp hết.
2. **Sửa nhanh bằng AJAX & Xuất CSV (`index.php?r=admin/products`):**
   - Lọc nhanh theo thương hiệu **Sony**, thử nhập giá `-5` rồi bấm Lưu trên dòng sản phẩm $\rightarrow$ Hệ thống báo lỗi Toast đỏ và tự khôi phục về giá cũ (`TC21`).
   - Bấm nút **"Xuất CSV"** $\rightarrow$ Mở file CSV tải về để chứng minh tiếng Việt hiển thị chuẩn trong Excel nhờ UTF-8 BOM và chỉ xuất đúng các sản phẩm đang lọc (`TC23`).
3. **Lập Phiếu nhập kho đa dòng (`index.php?r=admin/import-receipts/create` — `TC15`, `TC16`):**
   - Tạo phiếu nhập mới từ nhà cung cấp *Sony Việt Nam*, thêm 2 dòng thiết bị (ví dụ nhập thêm 3 chiếc cho 1 mã đang hết hàng `quantity = 0`).
   - Lưu phiếu $\rightarrow$ Chuyển sang trang Chi tiết phiếu nhập: chỉ ra tồn kho của thiết bị đã tăng tức thì, trạng thái tự chuyển từ *Hết hàng* sang *Còn hàng*, và bấm **"In phiếu (A4)"** để mở bản in chứng từ có khung chữ ký.
4. **Điều chỉnh kiểm kê bằng AJAX & Đối soát `stock_logs` (`index.php?r=admin/stock-logs` — `TC17`, `TC20`):**
   - Quay lại danh sách sản phẩm, mở Modal **Xuất kho / Điều chỉnh**, thử nhập số lượng xuất lớn hơn số tồn $\rightarrow$ Hệ thống khóa nút xác nhận và chặn âm kho (`TC17`).
   - Thực hiện xuất 1 sản phẩm hợp lệ kèm lý do *"Xuất máy trưng bày tủ kính Showroom"* $\rightarrow$ Số tồn và nhãn tồn trên dòng cập nhật ngay lập tức bằng AJAX.
   - Mở trang **Lịch sử tồn kho (`stock_logs`)** để chứng minh mọi biến động vừa thực hiện đều ghi lại đầy đủ *Tồn trước*, *Lượng đổi*, *Tồn sau*, *Người thực hiện* và *Lý do*.

### Phần 3 — Điểm nhấn Kiến trúc & Bảo mật (2 phút)
- Mở nhanh file `app/core/Router.php` và `app/services/StockService.php` để giải thích:
  1. **Kiểm soát tập trung tại `Router`:** Chặn phương thức HTTP sai (`405`), kiểm tra quyền Admin đối chiếu DB mỗi request (`TC30`), và kiểm tra CSRF cho mọi `POST` (`TC28`).
  2. **PDO Transaction + `SELECT ... FOR UPDATE` trong `StockService`:** Đảm bảo tính nguyên tử (Atomicity) và chống xung đột (Race Condition) khi nhiều nhân viên kho thao tác cùng lúc (`TC16`, `TC19`).
