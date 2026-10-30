-- =============================================================================
-- CƠ SỞ DỮ LIỆU: WEBSITE QUẢN LÝ SẢN PHẨM & TỒN KHO (camera_store_db)
-- Môn học: Lập trình Web (Bài TH3)
-- Gồm 9 bảng: 6 bảng gốc (giữ nguyên tên cột gốc) + 3 bảng mới (brands, product_specs, stock_logs)
-- Engine: InnoDB | Charset: utf8mb4_unicode_ci
-- =============================================================================

CREATE DATABASE IF NOT EXISTS camera_store_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE camera_store_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Xóa các bảng cũ (bao gồm cả các bảng cho thuê nếu từng tạo trước đó)
DROP TABLE IF EXISTS rental_extensions;
DROP TABLE IF EXISTS rental_details;
DROP TABLE IF EXISTS rentals;
DROP TABLE IF EXISTS stock_logs;
DROP TABLE IF EXISTS import_receipt_details;
DROP TABLE IF EXISTS import_receipts;
DROP TABLE IF EXISTS product_specs;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- 1. BẢNG GỐC: users (Tài khoản quản trị viên - thêm cột status)
-- =============================================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Lưu bcrypt hash từ password_hash()',
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'ADMIN',
    status ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. BẢNG GỐC: suppliers (Nhà cung cấp)
-- =============================================================================
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    address VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 3. BẢNG GỐC (mở rộng parent_id): categories (Danh mục sản phẩm dạng cây)
-- =============================================================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NULL COMMENT 'NULL nếu là danh mục gốc',
    name VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 4. BẢNG MỚI: brands (Thương hiệu)
-- =============================================================================
CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    logo VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 5. BẢNG GỐC (bổ sung brand_id, min_stock, image): products (Sản phẩm)
-- =============================================================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    supplier_id INT NOT NULL,
    brand_id INT NULL,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã SKU duy nhất',
    name VARCHAR(200) NOT NULL,
    price DECIMAL(12,0) NOT NULL DEFAULT 0 COMMENT 'Giá bán (VNĐ)',
    quantity INT NOT NULL DEFAULT 0 COMMENT 'Tồn kho thực tế (không sửa trực tiếp)',
    min_stock INT NOT NULL DEFAULT 0 COMMENT 'Ngưỡng cảnh báo sắp hết hàng',
    description TEXT NULL,
    image VARCHAR(255) NULL,
    status ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_products_qty CHECK (quantity >= 0 AND min_stock >= 0 AND price >= 0),
    INDEX idx_products_filter (status, category_id, brand_id),
    INDEX idx_products_price (price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 6. BẢNG MỚI: product_specs (Thông số kỹ thuật từng sản phẩm)
-- =============================================================================
CREATE TABLE product_specs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    spec_name VARCHAR(100) NOT NULL COMMENT 'Ví dụ: Ngàm, Độ phân giải, Tiêu cự',
    spec_value VARCHAR(255) NOT NULL COMMENT 'Ví dụ: Sony E, 24.2 MP, 24-70 mm',
    CONSTRAINT fk_specs_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_specs_lookup (product_id, spec_name, spec_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 7. BẢNG GỐC: import_receipts (Phiếu nhập kho - Master, giữ đúng cột gốc)
-- =============================================================================
CREATE TABLE import_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    user_id INT NOT NULL COMMENT 'Admin lập phiếu',
    import_date DATE NOT NULL,
    total_amount DECIMAL(14,0) NOT NULL DEFAULT 0,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_imports_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_imports_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 8. BẢNG GỐC: import_receipt_details (Chi tiết phiếu nhập - Detail, giữ đúng cột gốc)
-- =============================================================================
CREATE TABLE import_receipt_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL COMMENT 'Số lượng nhập (> 0)',
    import_price DECIMAL(12,0) NOT NULL DEFAULT 0 COMMENT 'Đơn giá nhập',
    subtotal DECIMAL(14,0) NOT NULL DEFAULT 0 COMMENT 'quantity * import_price',
    CONSTRAINT fk_import_details_receipt FOREIGN KEY (receipt_id) REFERENCES import_receipts(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_import_details_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_import_details_positive CHECK (quantity > 0 AND import_price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 9. BẢNG MỚI: stock_logs (Lịch sử biến động tồn kho)
-- =============================================================================
CREATE TABLE stock_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('IMPORT', 'EXPORT', 'ADJUSTMENT') NOT NULL,
    change_qty INT NOT NULL COMMENT 'Số lượng thay đổi có dấu (+ tăng, - giảm)',
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    reason VARCHAR(255) NOT NULL COMMENT 'Lý do biến động tồn kho',
    receipt_id INT NULL COMMENT 'Liên kết phiếu nhập nếu type = IMPORT',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_logs_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_stock_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_stock_logs_receipt FOREIGN KEY (receipt_id) REFERENCES import_receipts(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_stock_logs_non_negative CHECK (quantity_before >= 0 AND quantity_after >= 0),
    INDEX idx_stock_logs_product_date (product_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DỮ LIỆU MẪU (SEED DATA)
-- =============================================================================

-- 1. Tài khoản Admin duy nhất (Mật khẩu: ProGear@2026! - đã tạo mới và xác minh bằng password_verify)
INSERT INTO users (id, username, password, full_name, email, role, status) VALUES
(1, 'admin', '$2y$10$k5lyE5PkAkOgRqu4DkrLQ.fkyjuKraN8cSH8ddpUgM3/IrVMCEL/m', 'Nguyễn Quản Trị (Admin)', 'admin@progear.vn', 'ADMIN', 'ACTIVE');

-- 2. Nhà cung cấp
INSERT INTO suppliers (id, name, phone, email, address) VALUES
(1, 'Công ty TNHH Sony Electronics Việt Nam', '02838222222', 'b2b@sony.com.vn', 'Tầng 6, Tòa nhà President Place, 93 Nguyễn Du, Q.1, TP.HCM'),
(2, 'Công ty Cổ phần Lê Bảo Minh (Canon)', '02838386666', 'contact@lebaominh.vn', '184 Nam Kỳ Khởi Nghĩa, Phường Võ Thị Sáu, Q.3, TP.HCM'),
(3, 'Nhà phân phối Hoằng Quân (Sigma, Godox, Benro)', '02839270304', 'info@hoangquanco.com', '747 Điện Biên Phủ, Phường 1, Q.3, TP.HCM'),
(4, 'Công ty TNHH Fujifilm Việt Nam', '02839390888', 'support@fujifilm.com.vn', 'Tòa nhà Gemadept, 6 Lê Thánh Tôn, Q.1, TP.HCM'),
(5, 'Tokyo Camera & Studio Supply', '0988777666', 'supply@tokyocamera.vn', '162 Kim Mã, Ba Đình, Hà Nội');

-- 3. Danh mục (Cây danh mục 2 cấp)
INSERT INTO categories (id, parent_id, name, description) VALUES
(1, NULL, 'Thân máy ảnh (Bodies)', 'Các dòng máy ảnh chuyên nghiệp phục vụ chụp ảnh và quay phim'),
(2, 1, 'Máy ảnh Mirrorless', 'Máy ảnh không gương lật Full-Frame và APS-C đời mới'),
(3, 1, 'Máy ảnh DSLR', 'Máy ảnh phản xạ ống kính đơn bền bỉ, pin khỏe'),
(4, NULL, 'Ống kính (Lenses)', 'Ống kính đa dụng, ống kính fix chân dung và tele'),
(5, NULL, 'Đèn & Ánh sáng (Lighting)', 'Đèn flash ngoại cảnh, đèn LED liên tục và modifier'),
(6, NULL, 'Chân máy & Ổn định (Support)', 'Chân máy ảnh, chân đèn và gimbal chống rung'),
(7, NULL, 'Phụ kiện (Accessories)', 'Thẻ nhớ tốc độ cao, pin dự phòng, kính lọc ND/CPL, micro');

-- 4. Thương hiệu
INSERT INTO brands (id, name, logo) VALUES
(1, 'Sony', 'sony.png'),
(2, 'Canon', 'canon.png'),
(3, 'Fujifilm', 'fujifilm.png'),
(4, 'Nikon', 'nikon.png'),
(5, 'Sigma', 'sigma.png'),
(6, 'Godox', 'godox.png'),
(7, 'Aputure', 'aputure.png'),
(8, 'DJI', 'dji.png'),
(9, 'Manfrotto', 'manfrotto.png'),
(10, 'SanDisk', 'sandisk.png');

-- 5. Sản phẩm (28 sản phẩm trải đều các danh mục)
-- Đảm bảo đủ các trạng thái tồn kho theo Mục 16:
--   + Hết hàng (quantity = 0): id = 5 (Canon EOS R5), id = 16 (Canon RF 50mm f/1.2L)
--   + Sắp hết (0 < quantity <= min_stock): id = 2 (Sony A7M4: 2 <= 2), id = 12 (Sony 70-200GM: 1 <= 2), id = 24 (DJI RS 3 Pro: 1 <= 2)
--   + Ngừng kinh doanh (status = 'INACTIVE'): id = 9 (Nikon D750)
--   + Còn hàng (quantity > min_stock): 22 sản phẩm còn lại
INSERT INTO products (id, category_id, supplier_id, brand_id, code, name, price, quantity, min_stock, description, image, status) VALUES
-- Máy ảnh Mirrorless (category_id = 2)
(1, 2, 1, 1, 'CAM-SN-A7M3', 'Sony Alpha A7 Mark III (Body)', 36000000, 6, 2, 'Máy ảnh Full-Frame Mirrorless đa dụng, lấy nét mắt nhạy bén, quay 4K HDR, kèm pin NP-FZ100 chính hãng.', 'sony-a7m3.svg', 'ACTIVE'),
(2, 2, 1, 1, 'CAM-SN-A7M4', 'Sony Alpha A7 Mark IV (Body)', 52000000, 2, 2, 'Cảm biến 33MP thế hệ mới, quay 4K 60p 10-bit 4:2:2, S-Cinetone, lấy nét AI thời gian thực.', 'sony-a7m4.svg', 'ACTIVE'),
(3, 2, 1, 1, 'CAM-SN-FX3', 'Sony FX3 Cinema Line Full-Frame', 82000000, 4, 2, 'Máy quay Cinema Full-Frame nhỏ gọn, quạt tản nhiệt tích hợp, Dual Base ISO 800/12800, kèm tay cầm XLR.', 'sony-fx3.svg', 'ACTIVE'),
(4, 2, 2, 2, 'CAM-CN-R6M2', 'Canon EOS R6 Mark II (Body)', 50000000, 5, 2, 'Cảm biến Full-Frame 24.2MP tốc độ chụp 40fps, chống rung IBIS 8 stops, quay 4K60p oversampled.', 'canon-r6m2.svg', 'ACTIVE'),
(5, 2, 2, 2, 'CAM-CN-R5', 'Canon EOS R5 (Body)', 72000000, 0, 2, 'Độ phân giải siêu cao 45MP, quay 8K RAW, phù hợp chụp quảng cáo thương mại và studio.', 'canon-r5.svg', 'ACTIVE'),
(6, 2, 4, 3, 'CAM-FJ-XT4', 'Fujifilm X-T4 (Body) Silver', 32000000, 5, 2, 'Cảm biến X-Trans CMOS 4 26.1MP, chống rung IBIS, giả lập màu phim Classic Neg đặc trưng.', 'fuji-xt4.svg', 'ACTIVE'),
(7, 2, 4, 3, 'CAM-FJ-XS20', 'Fujifilm X-S20 (Body)', 29000000, 6, 2, 'Thân máy nhỏ gọn báng cầm sâu, pin NP-W235 dung lượng lớn, quay 6.2K Open Gate.', 'fuji-xs20.svg', 'ACTIVE'),

-- Máy ảnh DSLR (category_id = 3)
(8, 3, 2, 2, 'CAM-CN-5DM4', 'Canon EOS 5D Mark IV (Body)', 35000000, 4, 2, 'Dòng DSLR huyền thoại cho chụp sự kiện và cưới hỏi, cảm biến 30.4MP, Dual Pixel AF.', 'canon-5dm4.svg', 'ACTIVE'),
(9, 3, 5, 4, 'CAM-NK-D750', 'Nikon D750 (Body)', 20000000, 2, 1, 'Máy ảnh DSLR Full-Frame bền bỉ, dải tương phản động rộng (Mã ngừng kinh doanh để kiểm thử).', 'nikon-d750.svg', 'INACTIVE'),
(10, 3, 5, 4, 'CAM-NK-D850', 'Nikon D850 (Body)', 45000000, 3, 1, 'Cảm biến 45.7MP BSI CMOS không bộ lọc răng cưa, chất lượng ảnh phong cảnh và kiến trúc đỉnh cao.', 'nikon-d850.svg', 'ACTIVE'),

-- Ống kính (category_id = 4)
(11, 4, 1, 1, 'LEN-SN-2470GM', 'Sony FE 24-70mm f/2.8 GM', 34000000, 6, 2, 'Ống kính zoom tiêu chuẩn dòng G Master cho máy Sony E-mount, nét căng toàn dải.', 'sony-2470gm.svg', 'ACTIVE'),
(12, 4, 1, 1, 'LEN-SN-70200GM', 'Sony FE 70-200mm f/2.8 GM OSS', 44000000, 1, 2, 'Ống kính tele khẩu lớn chuyên trị sân khấu, thể thao và chân dung xóa phông.', 'sony-70200gm.svg', 'ACTIVE'),
(13, 4, 3, 5, 'LEN-SG-35A-E', 'Sigma 35mm f/1.4 DG DN Art (for Sony E)', 18500000, 7, 2, 'Ống kính fix góc rộng khẩu lớn f/1.4 thế hệ DG DN mới nhất dành riêng cho Mirrorless Sony E.', 'sigma-35art.svg', 'ACTIVE'),
(14, 4, 3, 5, 'LEN-SG-85A-E', 'Sigma 85mm f/1.4 DG DN Art (for Sony E)', 23500000, 5, 2, 'Ống kính chân dung ngoại cảnh trên ngàm Sony E, bokeh tròn mịn và kiểm soát quang sai tuyệt vời.', 'sigma-85art.svg', 'ACTIVE'),
(15, 4, 2, 2, 'LEN-CN-RF2470', 'Canon RF 24-70mm f/2.8L IS USM', 48000000, 4, 2, 'Ống kính đa dụng cao cấp ngàm Canon RF có chống rung quang học 5 stops.', 'canon-rf2470.svg', 'ACTIVE'),
(16, 4, 2, 2, 'LEN-CN-RF50L', 'Canon RF 50mm f/1.2L USM', 49000000, 0, 1, 'Ống kính tiêu cự chuẩn khẩu siêu lớn f/1.2 cho chất lượng quang học xuất sắc trên hệ EOS R.', 'canon-rf50l.svg', 'ACTIVE'),
(17, 4, 2, 2, 'LEN-CN-EF2470M2', 'Canon EF 24-70mm f/2.8L II USM', 28000000, 4, 2, 'Ống kính zoom chuẩn sắc nét cho dòng DSLR Canon EF (5D4, 6D2).', 'canon-ef2470.svg', 'ACTIVE'),
(18, 4, 4, 3, 'LEN-FJ-1655', 'Fujifilm XF 16-55mm f/2.8 R LM WR', 21000000, 5, 2, 'Ống kính Red Badge cao cấp cho hệ máy Fujifilm X, kháng thời tiết.', 'fuji-1655.svg', 'ACTIVE'),
(19, 4, 5, 4, 'LEN-NK-2470N', 'Nikon AF-S 24-70mm f/2.8G ED Nano', 22000000, 4, 2, 'Ống kính đa dụng lớp tráng phủ Nano Crystal cho máy DSLR Nikon ngàm F.', 'nikon-2470.svg', 'ACTIVE'),

-- Đèn & Ánh sáng (category_id = 5)
(20, 5, 3, 6, 'LGT-GD-AD200P', 'Đèn Flash Godox AD200 Pro Pocket', 7200000, 8, 3, 'Đèn flash ngoại cảnh dùng pin Lithium 200Ws, hỗ trợ TTL và HSS 1/8000s, kèm 2 đầu đèn.', 'godox-ad200pro.svg', 'ACTIVE'),
(21, 5, 3, 6, 'LGT-GD-AD600P', 'Đèn Studio Ngoại Cảnh Godox AD600 Pro', 15500000, 5, 2, 'Công suất mạnh 600Ws đánh át nắng, thời gian hồi đèn 0.01-0.9 giây, ngàm Bowens thông dụng.', 'godox-ad600pro.svg', 'ACTIVE'),
(22, 5, 5, 7, 'LGT-AP-200X', 'Đèn LED Aputure Amaran 200x S Bi-Color', 7800000, 6, 2, 'Đèn LED quay phim 200W đổi màu 2700K-6500K, CRI 95+, điều khiển qua app Sidus Link.', 'aputure-200x.svg', 'ACTIVE'),

-- Chân máy & Ổn định (category_id = 6)
(23, 6, 3, 9, 'SUP-MF-055X', 'Chân máy Manfrotto MT055XPRO3 + Đầu bi BHQ2', 8500000, 6, 2, 'Chân máy hợp kim nhôm vững chắc tải trọng 9kg, trụ giữa xoay ngang 90 độ chụp sản phẩm.', 'manfrotto-055.svg', 'ACTIVE'),
(24, 6, 5, 8, 'SUP-DJ-RS3P', 'Gimbal Chống Rung DJI RS 3 Pro Combo', 18500000, 1, 2, 'Gimbal sợi carbon tải trọng 4.5kg cho máy ảnh kèm ống kính lớn, khóa trục tự động.', 'dji-rs3pro.svg', 'ACTIVE'),

-- Phụ kiện (category_id = 7)
(25, 7, 5, 8, 'ACC-DJ-MIC2', 'Bộ Micro Không Dây DJI Mic 2 (2 TX + 1 RX)', 8400000, 7, 2, 'Thu âm nội bộ 32-bit float, khử ồn thông minh, hộp sạc đi kèm thời lượng 18 giờ.', 'dji-mic2.svg', 'ACTIVE'),
(26, 7, 5, 10, 'ACC-SD-128V90', 'Thẻ nhớ SanDisk Extreme PRO SDXC 128GB V90 300MB/s', 3800000, 12, 4, 'Thẻ nhớ chuẩn UHS-II V90 chuyên quay 4K/8K bitrate cao không bị dừng hình.', 'sandisk-128v90.svg', 'ACTIVE'),
(27, 7, 1, 1, 'ACC-SN-FZ100', 'Combo 2 Pin Sony NP-FZ100 Chính Hãng + Sạc Đôi', 3600000, 10, 3, 'Bộ 2 pin dung lượng cao cho Sony A7M3, A7M4, FX3, A7R4 kèm đế sạc nhanh 2 cổng.', 'sony-fz100.svg', 'ACTIVE'),
(28, 7, 3, 6, 'ACC-ND-82V', 'Kính lọc Variable ND2-ND400 Phi 82mm Nano', 2500000, 9, 3, 'Filter giảm sáng khả biến từ 1 đến 9 stops cho quay phim ngoài trời nắng gắt.', 'filter-nd82.svg', 'ACTIVE');

-- 6. Thông số kỹ thuật (product_specs) - Chuẩn hóa 'Ngàm' để gợi ý sản phẩm liên quan và so sánh chính xác
INSERT INTO product_specs (product_id, spec_name, spec_value) VALUES
-- Sony A7M3
(1, 'Ngàm', 'Sony E'),
(1, 'Cảm biến', 'Full-Frame BSI CMOS'),
(1, 'Độ phân giải', '24.2 MP'),
(1, 'Quay video tối đa', '4K 30p (6K Oversampled)'),
-- Sony A7M4
(2, 'Ngàm', 'Sony E'),
(2, 'Cảm biến', 'Full-Frame Exmor R'),
(2, 'Độ phân giải', '33.0 MP'),
(2, 'Quay video tối đa', '4K 60p 10-bit 4:2:2'),
-- Sony FX3
(3, 'Ngàm', 'Sony E'),
(3, 'Cảm biến', 'Full-Frame Cinema CMOS'),
(3, 'Độ phân giải', '10.2 MP (Video) / 12.1 MP'),
(3, 'Quay video tối đa', '4K 120p 10-bit 4:2:2'),
-- Canon R6M2
(4, 'Ngàm', 'Canon RF'),
(4, 'Cảm biến', 'Full-Frame CMOS'),
(4, 'Độ phân giải', '24.2 MP'),
(4, 'Quay video tối đa', '4K 60p IPB'),
-- Canon R5
(5, 'Ngàm', 'Canon RF'),
(5, 'Cảm biến', 'Full-Frame CMOS'),
(5, 'Độ phân giải', '45.0 MP'),
(5, 'Quay video tối đa', '8K 30p RAW / 4K 120p'),
-- Fujifilm X-T4
(6, 'Ngàm', 'Fujifilm X'),
(6, 'Cảm biến', 'APS-C X-Trans CMOS 4'),
(6, 'Độ phân giải', '26.1 MP'),
(6, 'Quay video tối đa', '4K 60p 10-bit'),
-- Fujifilm X-S20
(7, 'Ngàm', 'Fujifilm X'),
(7, 'Cảm biến', 'APS-C X-Trans CMOS 4'),
(7, 'Độ phân giải', '26.1 MP'),
(7, 'Quay video tối đa', '6.2K 30p 4:2:2 10-bit'),
-- Canon 5D Mark IV
(8, 'Ngàm', 'Canon EF'),
(8, 'Cảm biến', 'Full-Frame CMOS'),
(8, 'Độ phân giải', '30.4 MP'),
(8, 'Số điểm lấy nét', '61 điểm Cross-type'),
-- Nikon D750
(9, 'Ngàm', 'Nikon F'),
(9, 'Cảm biến', 'Full-Frame FX CMOS'),
(9, 'Độ phân giải', '24.3 MP'),
(9, 'Số điểm lấy nét', '51 điểm Multi-CAM 3500FX'),
-- Nikon D850
(10, 'Ngàm', 'Nikon F'),
(10, 'Cảm biến', 'Full-Frame BSI CMOS'),
(10, 'Độ phân giải', '45.7 MP'),
(10, 'Số điểm lấy nét', '153 điểm AF'),
-- Ống kính
(11, 'Ngàm', 'Sony E'),
(11, 'Tiêu cự', '24-70 mm'),
(11, 'Khẩu độ lớn nhất', 'f/2.8'),
(11, 'Đường kính filter', '82 mm'),
(12, 'Ngàm', 'Sony E'),
(12, 'Tiêu cự', '70-200 mm'),
(12, 'Khẩu độ lớn nhất', 'f/2.8'),
(12, 'Đường kính filter', '77 mm'),
(13, 'Ngàm', 'Sony E'),
(13, 'Tiêu cự', '35 mm'),
(13, 'Khẩu độ lớn nhất', 'f/1.4'),
(13, 'Đường kính filter', '67 mm'),
(14, 'Ngàm', 'Sony E'),
(14, 'Tiêu cự', '85 mm'),
(14, 'Khẩu độ lớn nhất', 'f/1.4'),
(14, 'Đường kính filter', '77 mm'),
(15, 'Ngàm', 'Canon RF'),
(15, 'Tiêu cự', '24-70 mm'),
(15, 'Khẩu độ lớn nhất', 'f/2.8'),
(15, 'Đường kính filter', '82 mm'),
(16, 'Ngàm', 'Canon RF'),
(16, 'Tiêu cự', '50 mm'),
(16, 'Khẩu độ lớn nhất', 'f/1.2'),
(16, 'Đường kính filter', '77 mm'),
(17, 'Ngàm', 'Canon EF'),
(17, 'Tiêu cự', '24-70 mm'),
(17, 'Khẩu độ lớn nhất', 'f/2.8'),
(17, 'Đường kính filter', '82 mm'),
(18, 'Ngàm', 'Fujifilm X'),
(18, 'Tiêu cự', '16-55 mm'),
(18, 'Khẩu độ lớn nhất', 'f/2.8'),
(18, 'Đường kính filter', '77 mm'),
(19, 'Ngàm', 'Nikon F'),
(19, 'Tiêu cự', '24-70 mm'),
(19, 'Khẩu độ lớn nhất', 'f/2.8'),
(19, 'Đường kính filter', '77 mm'),
-- Đèn, Chân máy, Phụ kiện
(20, 'Công suất', '200 Ws'),
(20, 'Nhiệt độ màu', '5600K ± 200K'),
(20, 'Nguồn điện', 'Pin Lithium 14.4V 2900mAh'),
(21, 'Công suất', '600 Ws'),
(21, 'Nhiệt độ màu', '5600K ± 75K'),
(21, 'Nguồn điện', 'Pin Lithium 28.8V 2600mAh'),
(22, 'Công suất', '200 W'),
(22, 'Nhiệt độ màu', '2700K - 6500K (Bi-Color)'),
(22, 'Nguồn điện', 'Điện AC 100-240V hoặc Pin V-Mount'),
(23, 'Tải trọng', '9.0 kg'),
(23, 'Chiều cao tối đa', '170 cm'),
(24, 'Tải trọng', '4.5 kg'),
(24, 'Thời lượng pin', '12 giờ'),
(25, 'Khoảng cách truyền', '250 m'),
(25, 'Ghi âm nội bộ', '32-bit Float / 14 giờ'),
(26, 'Dung lượng', '128 GB'),
(26, 'Tốc độ đọc/ghi', '300 MB/s (Chuẩn V90)'),
(27, 'Dung lượng', '2280 mAh (x2 viên)'),
(27, 'Tương thích', 'Sony A7M3, A7M4, FX3, A7R4, A6600'),
(28, 'Đường kính', '82 mm'),
(28, 'Mức giảm sáng', '1 - 9 Stops (ND2 - ND400)');

-- 7. Phiếu nhập kho mẫu (import_receipts & import_receipt_details - giữ đúng tên cột gốc)
INSERT INTO import_receipts (id, supplier_id, user_id, import_date, total_amount, note) VALUES
(1, 1, 1, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 126000000, 'Nhập bổ sung thân máy Sony A7M3 và ống kính 24-70GM đầu quý'),
(2, 3, 1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 38200000, 'Nhập bổ sung lens Sigma 35mm Art và thẻ nhớ SanDisk V90');

INSERT INTO import_receipt_details (receipt_id, product_id, quantity, import_price, subtotal) VALUES
(1, 1, 2, 32000000, 64000000),
(1, 11, 2, 31000000, 62000000),
(2, 13, 2, 16000000, 32000000),
(2, 26, 2, 3100000, 6200000);

-- 8. Lịch sử biến động kho (stock_logs)
-- Đảm bảo tổng SUM(change_qty) của từng sản phẩm khớp 100% với products.quantity (TC20)
INSERT INTO stock_logs (product_id, user_id, type, change_qty, quantity_before, quantity_after, reason, receipt_id, created_at) VALUES
-- Tồn đầu kỳ cho 28 sản phẩm
(1,  1, 'ADJUSTMENT',  4, 0,  4, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(2,  1, 'ADJUSTMENT',  3, 0,  3, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(3,  1, 'ADJUSTMENT',  4, 0,  4, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(4,  1, 'ADJUSTMENT',  5, 0,  5, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(5,  1, 'ADJUSTMENT',  1, 0,  1, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(6,  1, 'ADJUSTMENT',  5, 0,  5, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(7,  1, 'ADJUSTMENT',  6, 0,  6, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(8,  1, 'ADJUSTMENT',  4, 0,  4, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(9,  1, 'ADJUSTMENT',  2, 0,  2, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(10, 1, 'ADJUSTMENT',  3, 0,  3, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(11, 1, 'ADJUSTMENT',  4, 0,  4, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(12, 1, 'ADJUSTMENT',  2, 0,  2, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(13, 1, 'ADJUSTMENT',  5, 0,  5, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(14, 1, 'ADJUSTMENT',  5, 0,  5, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(15, 1, 'ADJUSTMENT',  4, 0,  4, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(16, 1, 'ADJUSTMENT',  1, 0,  1, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(17, 1, 'ADJUSTMENT',  4, 0,  4, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(18, 1, 'ADJUSTMENT',  5, 0,  5, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(19, 1, 'ADJUSTMENT',  4, 0,  4, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(20, 1, 'ADJUSTMENT',  8, 0,  8, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(21, 1, 'ADJUSTMENT',  5, 0,  5, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(22, 1, 'ADJUSTMENT',  6, 0,  6, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(23, 1, 'ADJUSTMENT',  6, 0,  6, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(24, 1, 'ADJUSTMENT',  2, 0,  2, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(25, 1, 'ADJUSTMENT',  7, 0,  7, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(26, 1, 'ADJUSTMENT', 10, 0, 10, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(27, 1, 'ADJUSTMENT', 10, 0, 10, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(28, 1, 'ADJUSTMENT',  9, 0,  9, 'Tồn đầu kỳ', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),

-- Biến động từ Phiếu nhập #1 (PN-000001)
(1,  1, 'IMPORT',      2, 4,  6, 'Nhập kho theo phiếu #PN-000001', 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(11, 1, 'IMPORT',      2, 4,  6, 'Nhập kho theo phiếu #PN-000001', 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),

-- Biến động từ Phiếu nhập #2 (PN-000002)
(13, 1, 'IMPORT',      2, 5,  7, 'Nhập kho theo phiếu #PN-000002', 2, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(26, 1, 'IMPORT',      2, 10, 12, 'Nhập kho theo phiếu #PN-000002', 2, DATE_SUB(NOW(), INTERVAL 7 DAY)),

-- Biến động Xuất kho (EXPORT) & Điều chỉnh kiểm kê (ADJUSTMENT) mẫu
(2,  1, 'EXPORT',     -1, 3,  2, 'Xuất bán khách lẻ theo hóa đơn #HD-1024', NULL, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(5,  1, 'EXPORT',     -1, 1,  0, 'Xuất giao đơn hàng studio hợp đồng #HD-1029', NULL, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(12, 1, 'EXPORT',     -1, 2,  1, 'Xuất bán khách lẻ theo hóa đơn #HD-1035', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(16, 1, 'EXPORT',     -1, 1,  0, 'Xuất bán khách lẻ theo hóa đơn #HD-1041', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(24, 1, 'ADJUSTMENT', -1, 2,  1, 'Chuyển 1 bộ sang khu vực trưng bày showroom', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY));
