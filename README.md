# ProGear Studio — Website Quản lý Sản phẩm & Tồn kho Thiết bị Nhiếp ảnh

Đồ án môn học **Lập trình Web (Bài thực hành số 3)** xây dựng bằng **PHP 8+ thuần (MVC tự viết)**, **PDO (`utf8mb4`, `EMULATE_PREPARES = false`)**, **MySQL/MariaDB (`camera_store_db`, 9 bảng)**, **Bootstrap 5.3** và **JavaScript thuần (`fetch()`)**.

---

## 1. Yêu cầu hệ thống

- **Môi trường máy chủ:** XAMPP (Apache 2.4+ & MySQL 8.0+ / MariaDB 10.4+) trên Windows, macOS hoặc Linux.
- **Phiên bản PHP:** PHP `>= 8.0` (đã bật các extension `pdo_mysql`, `mbstring`, `fileinfo`).
- **Trình duyệt:** Chrome, Edge, Firefox hoặc Safari phiên bản mới (hỗ trợ ES6 `fetch`, `URLSearchParams`, `history.pushState`).
- **Kết nối mạng (tùy chọn):** Để tải font *Be Vietnam Pro*, *Bootstrap 5.3.3* và *Bootstrap Icons 1.11.3* từ CDN.

---

## 2. Hướng dẫn cài đặt và chạy dự án trên XAMPP

1. **Đặt mã nguồn vào thư mục `htdocs`:**
   - Đường dẫn chuẩn: `C:\xampp\htdocs\camera-rental`
2. **Khởi động dịch vụ:**
   - Mở **XAMPP Control Panel** và bấm **Start** cho **Apache** và **MySQL**.
3. **Khởi tạo Cơ sở dữ liệu (`camera_store_db`):**
   - Nạp file `database/schema.sql` (gồm 9 bảng, 28 sản phẩm mẫu thuộc 6 danh mục và 37 dòng lịch sử `stock_logs` khớp tuyệt đối số tồn kho) với bộ mã **`utf8mb4`**:
   ```powershell
   C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 -e "SOURCE C:/xampp/htdocs/camera-rental/database/schema.sql"
   ```
4. **Cấu hình kết nối CSDL (nếu máy có đặt mật khẩu MySQL riêng):**
   - Mặc định hệ thống đọc `config/database.php` (`host=127.0.0.1`, `dbname=camera_store_db`, `username=root`, `password=''`).
   - Nếu MySQL trên máy có mật khẩu riêng, tạo file `config/database.local.php` (file này đã nằm trong `.gitignore`, không bị đẩy lên Git) trả về mảng ghi đè `username` / `password`.
5. **Truy cập ứng dụng trên trình duyệt:**
   - **Trang Showroom Khách hàng:** `http://localhost/camera-rental/` *(tự động chuyển vào `public/index.php?r=home`)*
   - **Trang Quản trị (Admin):** `http://localhost/camera-rental/admin` *(hoặc `http://localhost/camera-rental/public/index.php?r=admin/login`)*

---

## 3. Tài khoản Quản trị viên

- **Tên đăng nhập:** `admin` *(hoặc email `admin@camerastore.local`)*
- **Mật khẩu:** Tài khoản `admin`, mật khẩu được cung cấp riêng khi bàn giao; đổi ngay sau khi cài đặt *(không lưu mật khẩu dạng văn bản rõ trong mã nguồn)*.

---

## 4. Cấu trúc thư mục dự án

```text
camera-rental/
├── admin/
│   └── index.php                 # Lối tắt chuyển hướng nhanh vào khu vực Quản trị (/camera-rental/admin)
├── app/
│   ├── controllers/
│   │   ├── admin/                # AuthController, ProductController, CategoryController, BrandController,
│   │   │                         # SupplierController, ImportReceiptController, StockController, UserController
│   │   ├── api/                  # ProductApi, CompareApi, AdminProductApi, AdminStockApi (trả JSON cho AJAX)
│   │   └── storefront/           # HomeController, CatalogController, CompareController
│   ├── core/                     # Database (PDO), Router (tập trung Auth/Method/CSRF), Auth, Csrf,
│   │                             # Validator, Response, UiHelper
│   ├── models/                   # Product, Category, Brand, Supplier, ImportReceipt, StockLog, User
│   ├── services/                 # StockService (PDO Transaction + FOR UPDATE), ProductQuery,
│   │                             # ImageUploader (finfo + getimagesize), CsvExporter (UTF-8 BOM + chống Formula Injection)
│   └── views/
│       ├── admin/                # Giao diện Quản trị (Sản phẩm, Danh mục, Thương hiệu, NCC, Phiếu nhập, Kho, User)
│       ├── layouts/              # front.php (Showroom), admin.php (Sidebar 240px), print.php (In phiếu A4)
│       └── storefront/           # Giao diện Khách (Trang chủ, Danh mục lọc đa tiêu chí, Chi tiết, So sánh, 404)
├── config/
│   ├── .htaccess                 # Chặn truy cập trực tiếp từ trình duyệt
│   └── database.php              # Cấu hình kết nối PDO MySQL utf8mb4
├── database/
│   ├── .htaccess                 # Chặn truy cập trực tiếp từ trình duyệt
│   └── schema.sql                # Schema 9 bảng + dữ liệu mẫu 28 thiết bị & 37 bản ghi stock_logs
├── design-system/
│   └── camera-rental/MASTER.md   # Đặc tả hệ thống thiết kế Dark Studio (#131416 / #1b1d21 / #f0a23b)
├── public/
│   ├── css/                      # theme.css (Dark Studio, responsive 375–1440px), print.css (mẫu in A4)
│   ├── images/products/          # 28 ảnh minh họa thiết bị SVG + default-gear.svg
│   ├── js/                       # app.js, catalog.js, admin-products.js, admin-stock.js
│   ├── uploads/products/         # Thư mục lưu ảnh upload thực tế (có .htaccess tắt thực thi PHP)
│   └── index.php                 # Front Controller duy nhất của toàn bộ ứng dụng
├── index.php                     # Chuyển hướng từ thư mục gốc sang public/index.php
├── ANTIGRAVITY.md                # Đặc tả kỹ thuật & 35 tình huống kiểm thử (TC01–TC35)
└── README.md                     # Tài liệu hướng dẫn cài đặt & vận hành
```

---

## 5. Điểm nhấn Kỹ thuật & Bảo mật

1. **Kiểm soát tồn kho bất biến qua `StockService`:**
   - Số lượng tồn (`products.quantity`) **không bao giờ** chỉnh trực tiếp trên form sản phẩm hay endpoint sửa nhanh (`TC04`, `TC22`).
   - Mọi thay đổi tồn kho đều đi qua `StockService::createImportReceipt()` hoặc `StockService::adjust()` sử dụng **PDO Transaction + `SELECT ... FOR UPDATE`** chống âm kho khi nhiều tab thao tác đồng thời (`TC17`, `TC19`) và luôn ghi nhận vết đối soát trong `stock_logs` (`TC20`).
2. **Bảo vệ tập trung tại `Router`:**
   - Tự động kiểm tra phương thức HTTP (`GET`/`POST` — trả `405` kèm header `Allow` nếu sai), kiểm tra quyền Admin đối chiếu trực tiếp `users.status = 'ACTIVE'` trong DB ở mỗi request (`TC26`, `TC27`, `TC30`), và kiểm tra CSRF token cho toàn bộ request `POST` (`TC28`).
3. **Upload ảnh & Xuất CSV an toàn:**
   - Upload ảnh kiểm tra MIME thực bằng `finfo`, xác thực kích thước ảnh bằng `getimagesize()`, giới hạn `<= 2 MB`, từ chối SVG và file `.php` giả dạng ảnh (`TC25`), đổi tên ngẫu nhiên `bin2hex(random_bytes(16))` và khóa thực thi script trong `public/uploads/products/.htaccess`.
   - Xuất CSV theo đúng bộ lọc đang chọn, có ghi **UTF-8 BOM** để mở trực tiếp trong Microsoft Excel không lỗi dấu tiếng Việt (`TC23`) và tự động thêm dấu nháy đơn `'` nếu ô bắt đầu bằng `=`, `+`, `-`, `@`, `Tab`, `CR/LF` chống CSV Formula Injection (`TC24`).

---

## 6. Hạn chế đã biết (Known Limitations)

1. **Giới hạn số lần đăng nhập sai liên tiếp:** Hiện lưu bộ đếm trong `$_SESSION['_login_attempts']` (khóa tạm 120 giây sau 5 lần nhập sai liên tiếp — `TC31`). Nếu người dùng xóa cookie phiên làm việc trên trình duyệt thì bộ đếm trong phiên sẽ được tạo mới.
2. **Lưu trạng thái So sánh & Đã xem gần đây theo Session:** Danh sách so sánh tối đa 3 thiết bị (`$_SESSION['compare_ids']`) và lịch sử 8 sản phẩm đã xem gần đây (`$_SESSION['recent_viewed']`) được lưu theo phiên trình duyệt để khách hàng trải nghiệm ngay mà không bắt buộc tạo tài khoản.
3. **Chỉnh sửa / xóa phiếu nhập kho:** Hệ thống chủ ý không cho phép sửa hoặc xóa phiếu nhập kho sau khi đã lưu nhằm bảo toàn lịch sử chứng từ kế toán và khớp với `stock_logs`. Khi cần điều chỉnh tăng/giảm số lượng thực tế, quản trị viên sử dụng chức năng **Xuất kho / Điều chỉnh kiểm kê** để mọi thay đổi đều để lại vết trong `stock_logs`.
