# ProGear Studio — Website Quản lý Sản phẩm & Tồn kho Thiết bị Nhiếp ảnh

Đồ án môn học **Lập trình Web** xây dựng bằng **PHP 8+ thuần (MVC tự viết)**, **PDO (`utf8mb4`)**, **MySQL/MariaDB (`camera_store_db`)**, **Bootstrap 5.3** và **JavaScript thuần (`fetch()`)**.

---

## 1. Hướng dẫn cài đặt và chạy dự án trên XAMPP

1. Đặt mã nguồn tại thư mục `C:\xampp\htdocs\camera-rental`.
2. Khởi động **Apache** và **MySQL** trong XAMPP Control Panel.
3. Nạp cơ sở dữ liệu `camera_store_db` (9 bảng + 28 sản phẩm + 37 dòng `stock_logs`) bằng chuẩn `utf8mb4`:
   ```powershell
   C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 -e "SOURCE C:/xampp/htdocs/camera-rental/database/schema.sql"
   ```
4. Cấu hình kết nối CSDL tại `config/database.php` (hoặc tạo `config/database.local.php` nếu dùng mật khẩu MySQL riêng trên máy).
5. Truy cập ứng dụng:
   - **Trang Showroom (Khách hàng):** `http://localhost/camera-rental/public/index.php?r=home`
   - **Trang Quản trị (Admin):** `http://localhost/camera-rental/public/index.php?r=admin/login`

---

## 2. Tài khoản Quản trị viên

- **Tên đăng nhập:** `admin`
- **Mật khẩu:** Tài khoản `admin`, mật khẩu được cung cấp riêng khi bàn giao; đổi ngay sau khi cài đặt.

---

## 3. Hạn chế đã biết (Known Limitations)

1. **Giới hạn số lần đăng nhập sai liên tiếp:** Hiện lưu bộ đếm trong `$_SESSION['_login_attempts']` (khóa tạm 120 giây sau 5 lần nhập sai). Nếu người dùng xóa cookie phiên làm việc trên trình duyệt thì bộ đếm trong phiên sẽ được tạo mới.
2. **Chỉnh sửa / xóa phiếu nhập kho:** Hệ thống chủ ý không cho phép sửa hoặc xóa phiếu nhập kho sau khi đã lưu nhằm bảo toàn lịch sử đối chiếu với `stock_logs`. Khi cần điều chỉnh tăng/giảm số lượng thực tế, quản trị viên sử dụng chức năng **Xuất kho / Điều chỉnh kiểm kê** để mọi thay đổi đều để lại vết trong `stock_logs`.
