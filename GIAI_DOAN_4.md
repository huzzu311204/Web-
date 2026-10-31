# Báo cáo Giai đoạn 4: So sánh sản phẩm + Rà soát AJAX Admin

- **Dự án:** ProGear Studio — Quản lý sản phẩm & tồn kho thiết bị chụp ảnh
- **Thư mục mã nguồn:** `C:\xampp\htdocs\camera-rental`
- **Repository:** `https://github.com/huzzu311204/Web-.git` (nhánh `main`)
- **Phạm vi theo `ANTIGRAVITY.md` (mục GĐ4):** (1) So sánh tối đa 3 sản phẩm; (2) Rà soát các AJAX admin đã có theo TC21 / TC22 / TC27.

---

## 1. Phần A — So sánh sản phẩm (đã làm, commit `f4c6f1a`)

| Thành phần | File | Nghiệp vụ |
|---|---|---|
| API | `app/controllers/api/CompareApi.php` | `POST api/compare` với `action = add / remove / toggle / clear / list`; lưu `$_SESSION['compare_ids']`; **tối đa 3**, vượt thì HTTP `422`; chỉ nhận sản phẩm `ACTIVE` (không tồn tại / ngừng bán → `404`). |
| Trang so sánh | `app/controllers/storefront/CompareController.php`, `app/views/storefront/compare/index.php` | Cột = sản phẩm; hàng = ảnh, tên, thương hiệu, giá, tồn kho + **hợp (union) các thông số kỹ thuật**; ô thiếu hiển thị `—`. Cần ≥ 2 sản phẩm. |
| Giao diện chung | `app/views/layouts/front.php`, `public/js/app.js` | Thanh so sánh cố định `#compare-sticky-bar`, bộ đếm `#navbar-compare-count` cập nhật bằng AJAX. |

## 2. Phần B — Rà soát AJAX Admin (làm trong lần hoàn thiện này)

Đã đối chiếu từng endpoint `api/admin/*` với `Router`, `Csrf`, `Auth` và `public/js/admin-products.js`.

| Endpoint | Kết quả rà soát |
|---|---|
| `api/admin/products` | Lọc / sắp xếp / phân trang đúng; `sort` lạ rơi về mặc định. |
| `api/admin/sku-check` | Phân biệt: trùng / hợp lệ / sai định dạng. |
| `api/admin/product-inline` | Validate giá, ngưỡng, trạng thái; **từ chối `quantity` (422)**; lỗi trả kèm `current` để khôi phục. |
| `api/admin/product-action` | `toggle_status`, `delete` (xóa mềm nếu có lịch sử kho — TC05); action lạ → 400. |

**Phát hiện & sửa (3 điểm, đều ở phía trình duyệt):**

1. `public/js/app.js` — `StudioUI.fetchJson`:
   - Trước: nếu server trả HTML (lỗi 500) thì `response.json()` ném lỗi, người dùng chỉ thấy thông báo chung.
   - Sau: bắt lỗi parse và trả `{ ok:false, message }` rõ ràng.
   - Thêm: khi gặp `401` (phiên admin hết hạn hoặc bị khóa — TC27/TC30) hiển thị toast và tự chuyển về trang đăng nhập sau 1,5 giây.
2. `public/js/admin-products.js` — `loadAdminProducts`: thêm bộ đếm `loadSeq` để **bỏ qua phản hồi cũ đến muộn** khi gõ tìm kiếm / đổi bộ lọc liên tục (tránh bảng bị ghi đè bằng kết quả cũ).
3. `public/js/admin-products.js` — sửa nhanh: khi **mất kết nối** (nhánh `catch`) cũng khôi phục giá trị cũ, trước đây chỉ khôi phục khi server trả lỗi (TC21).

## 3. Phần C — Truy cập

- `index.php` (gốc) nay **chuyển hướng** sang `public/index.php` (giữ query string) để CSS / JS / ảnh không bị 404 khi mở `http://localhost/camera-rental/`.
- Thêm lối tắt `admin/index.php` và `public/admin/index.php` để vào `http://localhost/camera-rental/admin`.

---

## 4. Kết quả kiểm thử thực tế (MySQL thật, qua HTTP)

Kịch bản kiểm thử gọi trực tiếp các endpoint bằng `curl` (có cookie phiên, CSRF thật). **30/30 bước đạt.**

| Mã | Tình huống | Kết quả mong đợi | Thực tế |
|---|---|---|---|
| TC26 | Vào `admin/products` khi chưa đăng nhập | Về trang đăng nhập | `302` ✔ |
| TC27 | 3 endpoint AJAX admin khi chưa đăng nhập | 401 JSON | `401` ✔ |
| TC02 | `sku-check` mã trùng / mới / sai định dạng | Thông báo tương ứng | ✔ |
| — | `api/admin/products` có lọc + sort + `sort` chứa SQL | 200, không lỗi | ✔ |
| TC21 | Sửa nhanh giá hợp lệ | Lưu, trả giá đã định dạng | `200` ✔ |
| TC21 | Giá âm / giá là chữ / trạng thái lạ | 422, trả `current` để khôi phục | `422` ✔ |
| TC21 | `id` không tồn tại | 404 | `404` ✔ |
| TC22 | Gửi `quantity` vào endpoint sửa nhanh | 422 | `422` ✔ |
| TC28 | POST thiếu CSRF (admin + compare) | 403 | `403` ✔ |
| TC34 | `GET` vào endpoint chỉ nhận `POST` | 405 | `405` ✔ |
| TC05 | Xóa sản phẩm đã có lịch sử kho | Chuyển `INACTIVE`, không xóa cứng | `soft_deleted` ✔ |
| — | `toggle_status` hai lần, action lạ | Đổi trạng thái / 400 | ✔ |
| TC11 | Thêm sản phẩm thứ 4 vào so sánh | 422 + thông báo | `422` ✔ |
| TC11 | Sản phẩm không tồn tại | 404 | `404` ✔ |
| TC12 | So sánh máy ảnh với ống kính | Hợp thông số, thiếu hiển thị `—` | Có `—`, `Tiêu cự`, `Cảm biến` ✔ |

Dữ liệu được khôi phục sau mỗi bước thay đổi trạng thái / giá (kiểm tra lại: SP #1 giá `36.000.000`, SP #3 `ACTIVE`).

**Cách kiểm tra lại bằng tay**
1. Mở `http://localhost/camera-rental/admin`, đăng nhập (mật khẩu xem ở `README.md`).
2. Trang Sản phẩm: gõ nhanh vài ký tự vào ô tìm kiếm → bảng chỉ hiện kết quả của lần gõ cuối.
3. Sửa nhanh giá thành `-5` rồi bấm lưu → hiện toast lỗi và giá trở về giá cũ.
4. Mở 2 tab: đăng xuất ở tab 1, bấm lưu sửa nhanh ở tab 2 → toast hết phiên và tự về trang đăng nhập.
5. Trang khách: thêm 3 thiết bị vào so sánh, thêm thiết bị thứ 4 → bị từ chối kèm thông báo; mở bảng so sánh kiểm tra dấu `—`.

## 5. Hạn chế đã biết

1. Danh sách so sánh và "đã xem gần đây" lưu theo session trình duyệt (xóa cookie là mất).
2. Chưa có test tự động (PHPUnit); kiểm thử thực hiện bằng kịch bản `curl` trên dữ liệu mẫu.
3. Kiểm thử giao diện (nhấp chuột, toast, responsive TC35) chưa chạy tự động — thuộc GĐ5.

## 6. Còn lại cho GĐ5

CSV `TC23`/`TC24`, upload `TC25`, bảo mật `TC29`–`TC34`, responsive `TC35`, ảnh minh họa → ảnh thật, kịch bản demo 8–10 phút.
