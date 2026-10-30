# Design System Master File: Camera Rental (ProGear Studio)

> **Quy tắc áp dụng:** khi dựng một trang cụ thể, kiểm tra trước file `design-system/pages/[ten-trang].md`.
> Nếu file đó tồn tại, quy tắc trong đó **ghi đè** file Master này. Nếu không, tuân thủ nghiêm ngặt file này.
> File này là nguồn duy nhất cho giao diện; không trộn với bất kỳ bảng màu hay font nào khác.

- **Dự án:** Website cho thuê thiết bị chụp ảnh (khu vực khách và khu vực admin)
- **Công nghệ giao diện:** Bootstrap 5.3, Bootstrap Icons, JavaScript thuần
- **Cập nhật:** 30/10/2026

---

## 1. Định hướng thiết kế

- **Phong cách:** chuyên nghiệp, thực dụng như một studio cho thuê thiết bị nhiếp ảnh và quay phim thật. Ảnh sản phẩm là nhân vật chính, giao diện chỉ làm nền.
- **Chế độ màu:** Dark mode mặc định (`data-bs-theme="dark"` trên thẻ `<html lang="vi">`). Nền xám than đậm, chữ sáng dịu, **một màu nhấn duy nhất** là hổ phách.
- **Cảm giác mong muốn:** gọn, tin cậy, rõ giá và rõ trạng thái. Không bóng bẩy, không "vui nhộn".

### Tuyệt đối tránh

- Màu mặc định của Bootstrap (xanh dương `#0d6efd`, `bg-primary` gốc).
- Gradient tím–xanh, hiệu ứng kính mờ (`backdrop-filter`), bóng đổ lớn, bo góc quá lớn.
- Hero căn giữa kèm khẩu hiệu chung chung; dùng bố cục bất đối xứng.
- Emoji làm icon; chỉ dùng Bootstrap Icons.
- Ảnh placeholder vô nghĩa; dùng ảnh thiết bị thật, cùng tỷ lệ khung.
- Chỉ dùng màu để phân biệt trạng thái (luôn kèm chữ hoặc icon).
- Hiệu ứng hover làm dịch chuyển bố cục (scale, đổi kích thước).

---

## 2. Design tokens

### 2.1. Typography

- **Font chính (tiêu đề và nội dung):** `'Be Vietnam Pro', system-ui, -apple-system, sans-serif`, weight 400, 500, 600, 700.
- **Font số liệu (mã đơn, giá tiền, thông số kỹ thuật, ngày):** `'JetBrains Mono', ui-monospace, monospace`, kèm `font-variant-numeric: tabular-nums`.
- **Cỡ chữ cơ sở:** 16 px (`1rem`), `line-height: 1.5`.

| Cấp | Cỡ | Weight | Dùng cho |
|---|---|---|---|
| H1 | 2rem | 700 | Tiêu đề trang |
| H2 | 1.5rem | 600 | Tiêu đề khu vực |
| H3 | 1.25rem | 600 | Tiêu đề card, khối |
| Body | 1rem | 400 | Nội dung |
| Small | 0.875rem | 400 | Chú thích, nhãn phụ |
| Số liệu | 1rem | 500 | Giá, mã đơn, ngày (font mono) |

Định dạng tiền: `450.000 đ` (dấu chấm ngăn cách hàng nghìn, đơn vị `đ`).

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
```

### 2.2. Bảng màu

| Token | Giá trị | Mục đích |
|---|---|---|
| `--bs-body-bg` | `#131416` | Nền chính toàn trang (không dùng đen tuyền) |
| `--surface-elevated` | `#1b1d21` | Nền card, sidebar, navbar, bảng, modal |
| `--surface-input` | `#17191c` | Nền ô nhập liệu |
| `--bs-body-color` | `#e6e4df` | Chữ chính |
| `--text-muted` | `#9ca3af` | Chữ phụ, nhãn, chú thích |
| `--bs-border-color` | `#2a2c30` | Viền card, bảng, đường phân cách |
| `--bs-primary` | `#f0a23b` | Màu nhấn hổ phách: nút chính, giá thuê, trạng thái active, focus |
| `--bs-primary-rgb` | `240, 162, 59` | RGB của màu nhấn |
| `--accent-hover` | `#f5b45c` | Hover của nút chính và liên kết |
| `--btn-primary-text` | `#14110b` | Chữ đen ấm trên nền nút hổ phách |

**Quy ước dùng màu nhấn:** hổ phách dành cho **hành động và giá**. Không dùng hổ phách để biểu thị trạng thái đơn, để tránh nhầm với nút bấm.

### 2.3. Màu trạng thái

Mỗi trạng thái là một badge có **viền, icon và chữ**, không chỉ đổi màu.

| Trạng thái | Nhãn | Màu chữ và viền | Nền | Icon gợi ý |
|---|---|---|---|---|
| `PENDING` | Chờ duyệt | `#aab2bd` | `rgba(170,178,189,0.12)` | `bi-hourglass-split` |
| `APPROVED` | Đã duyệt | `#5eead4` | `rgba(94,234,212,0.12)` | `bi-check2-circle` |
| `RENTING` | Đang thuê | `#7cc4e8` | `rgba(124,196,232,0.12)` | `bi-camera` |
| `RETURNED` | Đã trả | `#34d399` | `rgba(52,211,153,0.12)` | `bi-box-arrow-in-down` |
| `REJECTED` | Từ chối | `#f87171` | `rgba(248,113,113,0.12)` | `bi-x-circle` |
| `CANCELLED` | Đã hủy | `#9ca3af` | `rgba(156,163,175,0.12)` | `bi-slash-circle` |

**Cảnh báo theo hạn trả (dòng trong bảng đơn thuê):**

| Mức | Điều kiện | Cách hiển thị |
|---|---|---|
| Quá hạn | `RENTING` và `end_date` nhỏ hơn hôm nay | Nền `rgba(220,53,69,0.14)`, viền trái 3 px `#f87171`, badge "Quá hạn" kèm `bi-exclamation-triangle` |
| Sắp đến hạn | `RENTING` và `end_date` trong 2 ngày tới | Nền `rgba(240,162,59,0.10)`, viền trái 3 px `#f0a23b`, badge "Sắp đến hạn" kèm `bi-clock` |

Viền trái và icon giúp người dùng không phải dựa vào màu để nhận ra, và tách được cảnh báo khỏi nút bấm.

### 2.4. Hình khối, khoảng cách, chuyển động

- **Bo góc:** `6px` nhất quán cho card, nút, input, badge, modal.
- **Đổ bóng:** không dùng bóng đổ trên card; phân tầng bằng màu nền và viền. Chỉ modal và dropdown được dùng `0 8px 24px rgba(0,0,0,0.45)`.
- **Thang khoảng cách:** `4px` (nhỏ), `8px`, `16px`, `24px`, `48px`, `96px`.
- **Chuyển động:** `150ms–200ms ease-out`, chỉ cho `background-color`, `border-color`, `color`, `opacity`. Tôn trọng `prefers-reduced-motion: reduce`.
- **Chiều rộng nội dung:** container tối đa `1200px`.

### 2.5. Khối CSS gốc (`theme.css`)

```css
:root, [data-bs-theme="dark"] {
  --bs-body-font-family: 'Be Vietnam Pro', system-ui, -apple-system, sans-serif;
  --bs-body-bg: #131416;
  --bs-body-bg-rgb: 19, 20, 22;
  --bs-body-color: #e6e4df;
  --bs-body-color-rgb: 230, 228, 223;
  --bs-border-color: #2a2c30;
  --bs-border-radius: 6px;
  --bs-border-radius-sm: 6px;
  --bs-border-radius-lg: 6px;
  --bs-primary: #f0a23b;
  --bs-primary-rgb: 240, 162, 59;
  --bs-link-color: #f0a23b;
  --bs-link-hover-color: #f5b45c;

  --surface-elevated: #1b1d21;
  --surface-input: #17191c;
  --text-muted: #9ca3af;
  --accent-hover: #f5b45c;
  --btn-primary-text: #14110b;
  --font-mono: 'JetBrains Mono', ui-monospace, monospace;
}

.num, .price, .code { font-family: var(--font-mono); font-variant-numeric: tabular-nums; }

:focus-visible { outline: 2px solid #f0a23b; outline-offset: 2px; }

@media (prefers-reduced-motion: reduce) {
  * { transition: none !important; animation: none !important; }
}
```

---

## 3. Đặc tả thành phần

### 3.1. Nút

```css
.btn { min-height: 44px; font-weight: 600; transition: background-color .15s ease-out, border-color .15s ease-out, color .15s ease-out; cursor: pointer; }

.btn-primary {
  --bs-btn-color: var(--btn-primary-text);
  --bs-btn-bg: #f0a23b;
  --bs-btn-border-color: #f0a23b;
  --bs-btn-hover-color: var(--btn-primary-text);
  --bs-btn-hover-bg: var(--accent-hover);
  --bs-btn-hover-border-color: var(--accent-hover);
  --bs-btn-active-bg: #d98f2f;
  --bs-btn-disabled-bg: #6b5a3c;
  --bs-btn-disabled-border-color: #6b5a3c;
}

.btn-outline-secondary {
  --bs-btn-color: #e6e4df;
  --bs-btn-border-color: #3a3d44;
  --bs-btn-hover-bg: transparent;
  --bs-btn-hover-color: #f0a23b;
  --bs-btn-hover-border-color: #f0a23b;
}
```

- Một màn hình chỉ có **một** nút chính (`btn-primary`).
- Nút nguy hiểm (xóa, từ chối) dùng `btn-outline-danger`, luôn kèm modal xác nhận.
- Nút chỉ có icon bắt buộc có `aria-label`.
- Khi đang gửi AJAX: vô hiệu hóa nút và hiện `spinner-border spinner-border-sm`.

### 3.2. Card

```css
.card {
  background: var(--surface-elevated);
  border: 1px solid var(--bs-border-color);
  border-radius: 6px;
  box-shadow: none;
  transition: border-color .15s ease-out;
}
.card:hover { border-color: #3a3d44; }      /* không dịch chuyển bố cục */
```

**Thẻ sản phẩm:**

- Ảnh tỷ lệ `4 / 3` (`aspect-ratio`, `object-fit: cover`), nền ảnh trung tính `#0f1012`.
- Thứ tự nội dung: thương hiệu (chữ phụ), tên thiết bị, 2 đến 3 chip thông số (font mono, cỡ nhỏ), giá thuê/ngày nổi bật (màu hổ phách, font mono).
- Cả thẻ là một liên kết, có `cursor: pointer` và focus rõ.
- Thiết bị đã hết hàng: giảm độ mờ ảnh còn 0.6 và hiện badge "Hết hàng" kèm chữ.

### 3.3. Form và ô nhập liệu

```css
.form-control, .form-select {
  min-height: 44px;
  background: var(--surface-input);
  border: 1px solid var(--bs-border-color);
  color: var(--bs-body-color);
  border-radius: 6px;
  transition: border-color .15s ease-out, box-shadow .15s ease-out;
}
.form-control:focus, .form-select:focus {
  background: var(--surface-input);
  border-color: #f0a23b;
  box-shadow: 0 0 0 3px rgba(240, 162, 59, 0.25);
}
input[type="date"] { color-scheme: dark; }       /* bộ chọn ngày gốc hiển thị theo nền tối */
.invalid-feedback { color: #f87171; }
```

- Mọi ô nhập có `<label>` hiển thị phía trên, không chỉ dùng `placeholder`.
- Lỗi hiện ngay dưới ô nhập (`.invalid-feedback`), giữ lại dữ liệu đã nhập khi form lỗi.
- Ô chọn ngày kèm vùng kết quả có `aria-live="polite"` để thông báo số còn trống và tổng tiền cho trình đọc màn hình.

### 3.4. Bảng dữ liệu

```css
.table {
  --bs-table-bg: transparent;
  --bs-table-border-color: var(--bs-border-color);
  --bs-table-hover-bg: rgba(255, 255, 255, 0.03);
  color: var(--bs-body-color);
}
.table thead th { color: var(--text-muted); font-size: .8125rem; font-weight: 600; }
.row-overdue  { background: rgba(220, 53, 69, 0.14); box-shadow: inset 3px 0 0 #f87171; }
.row-due-soon { background: rgba(240, 162, 59, 0.10); box-shadow: inset 3px 0 0 #f0a23b; }
```

- Luôn bọc trong `.table-responsive` để không tràn ngang trên màn hình nhỏ.
- Cột số tiền, mã đơn, ngày dùng font mono, căn phải với số tiền.
- Trạng thái rỗng có thông điệp ngắn và hành động gợi ý, không để bảng trống trơn.

### 3.5. Badge trạng thái

```css
.badge-status {
  display: inline-flex; align-items: center; gap: .375rem;
  padding: .25rem .625rem; border: 1px solid currentColor;
  border-radius: 6px; font-size: .8125rem; font-weight: 600;
}
```

Mỗi badge có icon `aria-hidden="true"` và nhãn chữ như bảng ở mục 2.3.

### 3.6. Modal

```css
.modal-content { background: var(--surface-elevated); border: 1px solid var(--bs-border-color); border-radius: 6px; box-shadow: 0 8px 24px rgba(0,0,0,.45); }
.modal-backdrop { --bs-backdrop-bg: #000; --bs-backdrop-opacity: .6; }   /* không dùng blur */
```

Dùng cho xác nhận xóa, từ chối đơn (kèm ô lý do), nhận trả (kèm ô ghi chú tình trạng). Tiêu đề nêu rõ hành động, nút xác nhận đặt tên theo hành động ("Xóa sản phẩm"), không dùng "OK".

### 3.7. Navbar, sidebar, phân trang, toast

- **Navbar khách:** nền `--surface-elevated`, viền dưới 1 px. Biểu tượng giỏ thuê có badge số lượng nền hổ phách, chữ `#14110b`.
- **Sidebar admin:** rộng 240 px, nền `--surface-elevated`; mục đang chọn có viền trái 3 px hổ phách và chữ sáng. Trên màn hình nhỏ chuyển thành `offcanvas`.
- **Phân trang:** trang hiện tại nền hổ phách, chữ `#14110b`; các nút có chiều cao tối thiểu 44 px.
- **Toast:** nền `--surface-elevated`, viền trái 3 px (xanh lá thành công, đỏ lỗi), tự đóng sau 4 giây, có nút đóng với `aria-label`.

### 3.8. Khối đặt thuê (trang chi tiết sản phẩm)

Khối này là điểm tương tác chính, đặt bên phải ảnh và dính khi cuộn (`position: sticky`) trên màn hình lớn:

1. Giá thuê/ngày và tiền cọc.
2. Hai ô chọn ngày (bắt đầu, kết thúc) có `label`.
3. Vùng kết quả AJAX: số còn trống, số ngày, tổng tiền tạm tính. Khi đang tải, làm mờ nhẹ và hiện spinner nhỏ.
4. Ô số lượng và nút chính "Thêm vào giỏ thuê" (vô hiệu hóa khi hết hàng hoặc ngày không hợp lệ, kèm lý do bằng chữ).

---

## 4. Mẫu bố cục

### 4.1. Khu vực khách

- **Trang chủ** (bất đối xứng, không căn giữa): nửa trái là tiêu đề ngắn cụ thể và nút "Xem thiết bị", nửa phải là ảnh thiết bị lớn. Tiếp theo là lưới danh mục, thiết bị mới, mục "Quy trình thuê 3 bước" căn trái có đánh số, cuối cùng là footer.
- **Danh sách sản phẩm:** cột lọc bên trái (3/12), lưới thẻ bên phải (9/12): 3 thẻ mỗi hàng từ 1200 px, 2 thẻ từ 768 px, 1 thẻ trên di động. Trên di động bộ lọc nằm trong `offcanvas`.
- **Chi tiết sản phẩm:** ảnh 7/12, khối đặt thuê 5/12; bên dưới là bảng thông số và khối "Thuê kèm".
- **Giỏ thuê, tra cứu đơn, chính sách:** một cột nội dung tối đa 800 px, dễ đọc.
- **Footer:** liên hệ, địa chỉ nhận thiết bị, liên kết chính sách thuê.

### 4.2. Khu vực admin

- Sidebar trái, topbar mỏng, khu vực nội dung có tiêu đề trang, breadcrumb và nút hành động chính ở góc phải.
- **Dashboard:** hàng thẻ số liệu (đơn chờ duyệt, yêu cầu gia hạn, đang thuê, sắp đến hạn, quá hạn), bên dưới là bảng "Cần chú ý". Không dùng biểu đồ.
- **Danh sách và form:** bảng gọn ở trên, form dùng chung cho thêm và sửa, thao tác xóa luôn qua modal.
- **Phiếu thuê in:** trang riêng với `print.css` (nền trắng, chữ đen, ẩn navbar và nút), có chỗ ký tên.

---

## 5. Quy tắc UX và Accessibility

1. **Accessibility (ưu tiên cao nhất)**
   - Nút chỉ có icon phải có `aria-label` (xóa dòng trong giỏ, đóng modal, đổi trạng thái).
   - Focus ring rõ ràng bằng màu hổ phách ở mọi phần tử tương tác (`:focus-visible`).
   - Độ tương phản chữ tối thiểu 4.5:1; không dùng màu làm tín hiệu duy nhất.
   - Ảnh có `alt` mô tả thiết bị; icon trang trí có `aria-hidden="true"`.
2. **Tương tác và cảm ứng**
   - Nút, ô chọn ngày, nút phân trang cao tối thiểu 44 px; mọi phần tử bấm được có `cursor: pointer`.
   - Mọi thao tác AJAX (kiểm tra còn trống, lọc, thêm giỏ, duyệt đơn, gia hạn) có trạng thái đang tải và thông báo toast khi thành công hoặc thất bại; debounce 300 ms với ô nhập liên tục.
   - Không để phần tử đổi kích thước hoặc nhảy bố cục khi hover hoặc khi tải xong dữ liệu.
3. **Form và validation**
   - Nhãn hiển thị phía trên, lỗi ngay dưới trường, giữ lại dữ liệu khi lỗi.
   - Thông báo lỗi nói rõ cách sửa ("Ngày kết thúc phải sau ngày bắt đầu").
   - Xác nhận trước thao tác không thể hoàn tác.
4. **Bố cục và responsive**
   - Kiểm tra ở 375 px, 768 px, 1024 px và 1440 px.
   - Không tràn ngang; bảng luôn nằm trong `.table-responsive`.
   - Không để nội dung bị navbar cố định che mất.
5. **Nội dung**
   - Chữ ngắn, cụ thể, dùng thông số và giá thật; dữ liệu mẫu là tên thiết bị thật.

---

## 6. Danh sách kiểm tra trước khi bàn giao giao diện

- [ ] Không có emoji làm icon; toàn bộ icon dùng Bootstrap Icons
- [ ] Không còn màu xanh dương mặc định của Bootstrap ở nút, liên kết, phân trang
- [ ] Chỉ dùng một màu nhấn hổ phách; trạng thái dùng badge có viền, icon và chữ
- [ ] Font là Be Vietnam Pro, số liệu dùng JetBrains Mono, đã kiểm tra hiển thị dấu tiếng Việt
- [ ] Bo góc 6 px, không dùng `backdrop-filter`, không bóng đổ trên card
- [ ] Hover và focus có chuyển động 150–200 ms, không làm dịch chuyển bố cục
- [ ] Focus ring thấy rõ khi dùng bàn phím
- [ ] Chữ đạt tương phản tối thiểu 4.5:1 trên nền tối
- [ ] Mọi thao tác AJAX có trạng thái tải, toast và xử lý lỗi
- [ ] Dòng đơn quá hạn và sắp đến hạn có viền trái, icon và chữ, không chỉ đổi màu
- [ ] Responsive ở 375, 768, 1024, 1440 px, không tràn ngang
- [ ] Bản in phiếu thuê dùng nền trắng chữ đen
- [ ] `prefers-reduced-motion` được tôn trọng
