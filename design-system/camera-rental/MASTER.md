# Design System Master File: Website Quản lý Sản phẩm & Tồn kho (ProGear Studio)

> **Quy tắc áp dụng:** khi dựng một trang cụ thể, kiểm tra trước file `design-system/pages/[ten-trang].md`.
> Nếu file đó tồn tại, quy tắc trong đó **ghi đè** file Master này. Nếu không, tuân thủ nghiêm ngặt file này.
> File này là nguồn duy nhất cho giao diện; không trộn với bất kỳ bảng màu hay font nào khác.

- **Dự án:** Website quản lý sản phẩm & tồn kho thiết bị chụp ảnh (khu vực khách và khu vực admin)
- **Công nghệ giao diện:** Bootstrap 5.3, Bootstrap Icons, JavaScript thuần
- **Cập nhật:** 30/10/2026 (Đã cập nhật theo Phụ lục A)

---

## 1. Định hướng thiết kế

- **Phong cách:** chuyên nghiệp, thực dụng như một cửa hàng thiết bị nhiếp ảnh thật. Ảnh sản phẩm là nhân vật chính, giao diện chỉ làm nền.
- **Chế độ màu:** Dark mode mặc định (`data-bs-theme="dark"` trên thẻ `<html lang="vi">`). Nền xám than đậm, chữ sáng dịu, **một màu nhấn duy nhất** là hổ phách.
- **Font chữ:** Dùng duy nhất 1 font sans-serif **Be Vietnam Pro** trên toàn hệ thống; số liệu dùng `font-variant-numeric: tabular-nums`.

### Tuyệt đối tránh

- Màu mặc định của Bootstrap (xanh dương `#0d6efd`, `bg-primary` gốc).
- Gradient tím–xanh, hiệu ứng kính mờ (`backdrop-filter`), bóng đổ lớn, bo góc quá lớn.
- Hero căn giữa kèm khẩu hiệu chung chung; dùng bố cục bất đối xứng.
- Emoji làm icon; chỉ dùng Bootstrap Icons.
- Chỉ dùng màu để phân biệt trạng thái tồn kho (luôn kèm chữ và icon).
- Hiệu ứng hover làm dịch chuyển bố cục (`scale`, đổi kích thước).

---

## 2. Design tokens

### 2.1. Typography

- **Font duy nhất (tiêu đề, nội dung, số liệu):** `'Be Vietnam Pro', system-ui, -apple-system, sans-serif`, weight 400, 500, 600, 700.
- **Số liệu (`.num`, `.price`, `.code`):** bật `font-variant-numeric: tabular-nums` để thẳng cột trong bảng.
- **Cỡ chữ cơ sở:** 16 px (`1rem`), `line-height: 1.5`.

| Cấp | Cỡ | Weight | Dùng cho |
|---|---|---|---|
| H1 | 2rem | 700 | Tiêu đề trang |
| H2 | 1.5rem | 600 | Tiêu đề khu vực |
| H3 | 1.25rem | 600 | Tiêu đề card, khối |
| Body | 1rem | 400 | Nội dung |
| Small | 0.875rem | 400 | Chú thích, nhãn phụ |
| Số liệu | 1rem | 600 | Giá, mã SKU, số tồn (`tabular-nums`) |

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
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
| `--bs-primary` | `#f0a23b` | Màu nhấn hổ phách: nút chính, giá bán, trạng thái active, focus |
| `--bs-primary-rgb` | `240, 162, 59` | RGB của màu nhấn |
| `--accent-hover` | `#f5b45c` | Hover của nút chính và liên kết |
| `--btn-primary-text` | `#14110b` | Chữ đen ấm trên nền nút hổ phách |

### 2.3. Badge tồn kho (Mục 9.3)

Mỗi nhãn tồn kho là một badge có **viền, icon và chữ**, không chỉ dựa vào màu:

| Nhãn | Điều kiện | Màu chữ và viền | Nền | Icon |
|---|---|---|---|---|
| **Còn hàng** (`stock-in`) | `quantity > min_stock` | `#34d399` | `rgba(52,211,153,0.12)` | `bi-check-circle` |
| **Sắp hết** (`stock-low`) | `quantity > 0` và `quantity <= min_stock` | `#facc15` | `rgba(250,204,21,0.12)` | `bi-exclamation-triangle` |
| **Hết hàng** (`stock-out`) | `quantity = 0` | `#f87171` | `rgba(248,113,113,0.12)` | `bi-x-circle` |
| **Ngừng kinh doanh** (`stock-discontinued`) | `status = 'INACTIVE'` | `#9ca3af` | `rgba(156,163,175,0.12)` | `bi-slash-circle` |

**Tô dòng cảnh báo tồn kho trong bảng Admin:**
- Dòng **Hết hàng** (`.row-out-of-stock`): nền `rgba(220,53,69,0.12)`, viền trái 3 px `#f87171`.
- Dòng **Sắp hết** (`.row-low-stock`): nền `rgba(250,204,21,0.08)`, viền trái 3 px `#facc15`.

### 2.4. Hình khối, khoảng cách, chuyển động

- **Bo góc:** `6px` nhất quán cho card, nút, input, badge, modal.
- **Đổ bóng:** không dùng bóng đổ trên card; chỉ modal, toast và thanh so sánh dùng `0 8px 24px rgba(0,0,0,0.45)`.
- **Thang khoảng cách:** `4px`, `8px`, `16px`, `24px`, `48px`, `96px`.
- **Chuyển động:** `150ms–200ms ease-out`, tôn trọng `prefers-reduced-motion: reduce`.
- **Chiều rộng nội dung:** container tối đa `1200px`.

---

## 3. Mẫu bố cục

### 3.1. Khu vực khách

- **Navbar:** logo, ô tìm kiếm, liên kết "Sản phẩm", liên kết "So sánh" kèm badge số lượng (cập nhật bằng AJAX).
- **Trang chủ** (bất đối xứng): nửa trái tiêu đề + nút "Xem sản phẩm", nửa phải ảnh sản phẩm nổi bật; lưới danh mục, sản phẩm mới, khối "Đã xem gần đây".
- **Danh sách sản phẩm:** cột lọc sticky bên trái (3/12, trên di động trong `offcanvas`), lưới thẻ bên phải (9/12), dải chip bộ lọc đang áp dụng phía trên lưới.
- **Chi tiết sản phẩm:** ảnh lớn bên trái (6/12), thông tin + giá + nhãn tồn kho + nút so sánh bên phải (6/12); bên dưới là bảng thông số, khối sản phẩm liên quan và khối đã xem gần đây.
- **Thanh so sánh:** cố định ở cuối màn hình khi có sản phẩm được chọn (tối đa 3 sản phẩm).

### 3.2. Khu vực admin

- Sidebar trái 240 px (`offcanvas` trên di động), mục đang chọn có viền trái 3 px hổ phách.
- Cạnh mục "Sản phẩm" hiển thị badge đếm số sản phẩm **Sắp hết + Hết hàng** (thay cho trang Dashboard).
- Topbar mỏng, nút đăng xuất là form POST kèm CSRF.
- Bảng sản phẩm có ô sửa nhanh (giá, ngưỡng sắp hết, trạng thái) kèm trạng thái đang lưu.
