-- ============================================================
-- Trà Chuyện - Database Schema & Seed Data
-- MySQL 8.0 / utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS trachuyen_db
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE trachuyen_db;

-- ============================================================
-- DANH MỤC
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
  id int NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL COMMENT 'Tên danh mục',
  slug varchar(255) NOT NULL COMMENT 'Đường dẫn thân thiện',
  description text COMMENT 'Mô tả danh mục',
  image_url varchar(255) DEFAULT NULL COMMENT 'Ảnh đại diện',
  parent_id int DEFAULT NULL COMMENT 'Danh mục cha (NULL = cấp 1)',
  is_active tinyint(1) DEFAULT 1 COMMENT 'Trạng thái hoạt động',
  sort_order int DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug),
  KEY parent_id (parent_id),
  CONSTRAINT categories_ibfk_1 FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SẢN PHẨM
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
  id int NOT NULL AUTO_INCREMENT,
  code varchar(50) DEFAULT NULL COMMENT 'Mã sản phẩm',
  search_key varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Từ khóa tìm kiếm không dấu',
  category_id int DEFAULT NULL COMMENT 'ID danh mục, có thể để trống',
  name varchar(255) NOT NULL COMMENT 'Tên sản phẩm',
  slug varchar(255) NOT NULL COMMENT 'Đường dẫn thân thiện',
  description text COMMENT 'Mô tả chi tiết',
  short_description varchar(500) DEFAULT NULL COMMENT 'Mô tả ngắn',
  price decimal(15,2) NOT NULL COMMENT 'Giá hiện tại',
  old_price decimal(15,2) DEFAULT NULL COMMENT 'Giá cũ (để hiển thị giảm giá)',
  badge enum('hot','sale','new','') DEFAULT '' COMMENT 'Nhãn dán: Hot, Sale, Mới',
  image_url varchar(255) DEFAULT NULL COMMENT 'Ảnh đại diện',
  rating_avg decimal(2,1) DEFAULT 0.0 COMMENT 'Điểm đánh giá trung bình',
  review_count int DEFAULT 0 COMMENT 'Tổng số lượt đánh giá',
  is_best_seller tinyint(1) DEFAULT 0 COMMENT 'Sản phẩm bán chạy',
  stock_quantity int DEFAULT 0 COMMENT 'Số lượng tồn kho',
  capacity int DEFAULT NULL COMMENT 'Dung tích (ml)',
  is_active tinyint(1) DEFAULT 1 COMMENT 'Trạng thái hoạt động',
  views int DEFAULT 0 COMMENT 'Số lượt xem',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug),
  UNIQUE KEY uk_products_code (code),
  KEY category_id (category_id),
  CONSTRAINT products_ibfk_1 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED: DANH MỤC
-- ============================================================
INSERT INTO categories (id, name, slug, description, image_url, is_active, sort_order) VALUES
(1, 'Trà Xanh',   'tra-xanh',     'Trà xanh Việt Nam chất lượng cao', '/img/category/tra-xanh.jpg',     1, 1),
(2, 'Trà Đen',    'tra-den',      'Trà đen đậm đà, hậu ngọt',         '/img/category/tra-den.jpg',      1, 2),
(3, 'Trà Ô Long', 'tra-o-long',   'Trà ô long đỉnh cao',               '/img/category/tra-o-long.jpg',   1, 3),
(4, 'Trà Thảo Mộc','tra-thao-moc','Trà thảo mộc tự nhiên',             '/img/category/tra-thao-moc.jpg', 1, 4),
(5, 'Ấm Tử Sa',   'am-tu-sa',     'Ấm tử sa Nghi Hưng chính hãng',     '/img/category/am-tu-sa.jpg',     1, 5),
(6, 'Bộ Trà Cụ',  'bo-tra-cu',    'Bộ trà cụ cao cấp',                 '/img/category/bo-tra-cu.jpg',    1, 6),
(7, 'Hộp Quà Tặng','hop-qua-tang','Hộp quà tặng trà sang trọng',       '/img/category/hop-qua-tang.jpg', 1, 7),
(8, 'Phụ Kiện Trà','phu-kien-tra','Phụ kiện pha trà tiện lợi',         '/img/category/phu-kien-tra.jpg', 1, 8);

-- ============================================================
-- SEED: SẢN PHẨM
-- ============================================================
INSERT INTO products
  (code, category_id, name, slug, description, short_description, price, old_price, badge, image_url, rating_avg, review_count, is_best_seller, stock_quantity, capacity, is_active) VALUES
('TC-001', 1, 'Trà Xanh Thái Nguyên Đặc Biệt', 'tra-xanh-thai-nguyen-dac-biet', 'Trà xanh Thái Nguyên đặc biệt, hái từ những búp non đầu mùa, vị chát dịu hậu ngọt sâu.', 'Búp non đầu mùa, vị chát dịu hậu ngọt', 250000, 300000, 'hot', 'https://via.placeholder.com/600x500/5d4037/ffffff?text=Trà+Xanh+Thái+Nguyên', 5.0, 42, 1, 25, 1),
('TC-002', 1, 'Trà Xanh Tân Cương', 'tra-xanh-tan-cuong', 'Trà xanh Tân Cương hương thơm nhẹ, vị chát đậm đà đặc trưng vùng trung du.', 'Hương thơm nhẹ, vị chát đậm đà', 180000, NULL, '', 'https://via.placeholder.com/600x500/6d4c41/ffffff?text=Trà+Xanh+Tân+Cương', 4.0, 28, 0, 18, 1),
('TC-003', 1, 'Trà Xanh Shan Tuyết', 'tra-xanh-shan-tuyet', 'Trà xanh Shan Tuyết từ những cây chè cổ thụ vùng cao, hương cốm thoảng, vị đượm.', 'Chè cổ thụ vùng cao, hương cốm thoảng', 320000, 280000, 'new', 'https://via.placeholder.com/600x500/8d6e63/ffffff?text=Trà+Xanh+Shan+Tuyết', 5.0, 35, 1, 12, 1),
('TC-004', 1, 'Trà Xanh Hương Sen', 'tra-xanh-huong-sen', 'Trà xanh ướp hương sen tự nhiên, thơm nhẹ, dễ uống, thích hợp phái nữ.', 'Ướp hương sen tự nhiên, dễ uống', 220000, 190000, '', 'https://via.placeholder.com/600x500/a1887f/ffffff?text=Trà+Xanh+Hương+Sen', 4.0, 20, 0, 20, 1),
('TC-005', 1, 'Trà Xanh Mộc Châu', 'tra-xanh-moc-chau', 'Trà xanh Mộc Châu cao nguyên, vị thanh mát, hương cỏ nội.', 'Vị thanh mát, hương cỏ nội', 160000, NULL, '', 'https://via.placeholder.com/600x500/795548/ffffff?text=Trà+Xanh+Mộc+Châu', 4.0, 15, 0, 30, 1),

('TC-006', 2, 'Trà Đen Shan Tuyết', 'tra-den-shan-tuyet', 'Trà đen Shan Tuyết vị đậm đà, hậu ngọt, phù hợp pha sữa hoặc uống nguyên chất.', 'Vị đậm đà, hậu ngọt', 280000, NULL, '', 'https://via.placeholder.com/600x500/3e2723/ffffff?text=Trà+Đen+Shan+Tuyết', 5.0, 15, 1, 15, 1),
('TC-007', 2, 'Hồng Trà Kim Tuyền', 'hong-tra-kim-tuyen', 'Hồng trà kim tuyền Đà Lạt, cánh trà vàng óng, nước pha màu hổ phách đẹp.', 'Cánh vàng óng, nước hổ phách', 210000, 240000, 'sale', 'https://via.placeholder.com/600x500/4e342e/ffffff?text=Hồng+Trà+Kim+Tuyền', 4.5, 12, 0, 22, 1),
('TC-008', 2, 'Trà Đen Lài ướp hương', 'tra-den-lai', 'Trà đen ướp hương hoa lài, thơm nồng, vị ngọt hậu.', 'Ướp hương hoa lài', 190000, NULL, '', 'https://via.placeholder.com/600x500/5d4037/ffffff?text=Trà+Đen+Lài', 4.0, 9, 0, 20, 1),

('TC-009', 3, 'Trà Ô Long Đài Loan Cao Sơn', 'tra-o-long-dai-loan-cao-son', 'Trà ô long Đài Loan trồng ở độ cao 1000m, hương hoa quả thanh thoát, hậu vị dài.', 'Cao sơn, hương hoa quả', 350000, 280000, 'hot', 'https://via.placeholder.com/600x500/a1887f/ffffff?text=Ô+Long+Đài+Loan', 5.0, 33, 1, 20, 1),
('TC-010', 3, 'Trà Ô Long Sắt Vàng', 'tra-o-long-sat-vang', 'Trà ô long sắt vàng, cánh trà xoắn chặt, nước vàng sánh, vị đậm.', 'Cánh trà xoắn chặt', 260000, NULL, '', 'https://via.placeholder.com/600x500/8d6e63/ffffff?text=Ô+Long+Sắt+Vàng', 4.5, 18, 0, 16, 1),
('TC-011', 3, 'Trà Ô Long Nhân Sâm', 'tra-o-long-nhan-sam', 'Trà ô long tẩm ướp nhân sâm Hàn Quốc, bổ dưỡng, hậu ngọt nhẹ.', 'Tẩm ướp nhân sâm', 290000, 330000, 'sale', 'https://via.placeholder.com/600x500/6d4c41/ffffff?text=Ô+Long+Nhân+Sâm', 4.0, 11, 0, 10, 1),

('TC-012', 4, 'Trà Thảo Mộc Hoa Hồng', 'tra-thao-moc-hoa-hong', 'Trà thảo mộc hoa hồng, hỗ trợ ngủ ngon, thư giãn tinh thần.', 'Hoa hồng, hỗ trợ ngủ ngon', 210000, 180000, '', 'https://via.placeholder.com/600x500/6d4c41/ffffff?text=Thảo+Mộc+Hoa+Hồng', 5.0, 22, 0, 22, 1),
('TC-013', 4, 'Trà Thảo Mộc Gừng Mật Ong', 'tra-thao-moc-gung-mat-ong', 'Trà gừng mật ong ấm bụng, giải cảm, tăng sức đề kháng.', 'Gừng mật ong, ấm bụng', 170000, NULL, '', 'https://via.placeholder.com/600x500/795548/ffffff?text=Gừng+Mật+Ong', 4.5, 14, 0, 25, 1),

('TC-014', 5, 'Ấm Tử Sa Nghi Hưng Cổ Phong', 'am-tu-sa-nghi-hung-co-phong', 'Ấm tử sa Nghi Hưng chế tác thủ công bởi nghệ nhân lành nghề, chất đất nguyên khoáng, giữ nhiệt tốt.', 'Thủ công, chất đất nguyên khoáng', 1200000, 1500000, 'hot', 'https://via.placeholder.com/600x500/b8860b/ffffff?text=Ấm+Tử+Sa', 5.0, 30, 1, 8, 200, 1),
('TC-015', 5, 'Bộ Ấm Chén Tử Sa', 'bo-am-chen-tu-sa', 'Bộ ấm chén tử sa đồng bộ, tinh xảo, thích hợp thưởng trà hàng ngày.', 'Đồng bộ ấm chén', 950000, 1100000, 'new', 'https://via.placeholder.com/600x500/8d6e63/ffffff?text=Bộ+Ấm+Chén+Tử+Sa', 4.5, 14, 0, 10, 300, 1),

('TC-016', 6, 'Bộ Trà Cụ Gốm Sứ Cao Cấp', 'bo-tra-cu-gom-su-cao-cap', 'Bộ trà cụ gốm sứ Bát Tràng cao cấp, men ngọc, sang trọng.', 'Gốm sứ Bát Tràng', 850000, 1000000, 'sale', 'https://via.placeholder.com/600x500/4e342e/ffffff?text=Bộ+Trà+Cụ', 4.0, 18, 1, 10, 200, 1),
('TC-017', 6, 'Khay Trà Gỗ Sưa Tự Nhiên', 'khay-tra-go-sua-tu-nhien', 'Khay trà gỗ sưa tự nhiên, vân gỗ đẹp, chống thấm tốt.', 'Gỗ sưa tự nhiên', 1500000, 1800000, '', 'https://via.placeholder.com/600x500/795548/ffffff?text=Khay+Trà+Gỗ+Sưa', 5.0, 8, 0, 5, 250, 1),

('TC-018', 7, 'Hộp Quà Tặng Trà Thái Nguyên', 'hop-qua-tang-tra-thai-nguyen', 'Hộp quà tặng trà Thái Nguyên cao cấp, kèm túi lụa, thiệp chúc.', 'Hộp quà tặng kèm thiệp', 450000, 530000, 'sale', 'https://via.placeholder.com/600x500/3e2723/ffffff?text=Hộp+Quà+Tặng', 5.0, 20, 1, 15, 1),
('TC-019', 7, 'Set Quà Trà Đen Shan Tuyết', 'set-qua-tra-den-shan-tuyet', 'Set quà trà đen shan tuyết với hộp gỗ sang trọng.', 'Hộp gỗ sang trọng', 520000, NULL, '', 'https://via.placeholder.com/600x500/5d4037/ffffff?text=Set+Quà+Trà+Đen', 4.5, 7, 0, 12, 1),

('TC-020', 8, 'Cốc Trà Thủy Tinh Dung Nham', 'coc-tra-thuy-tinh-dung-nham', 'Cốc trà thủy tinh dung nham, chịu nhiệt tốt, bắt mắt.', 'Thủy tinh chịu nhiệt', 180000, 200000, '', 'https://via.placeholder.com/600x500/a1887f/ffffff?text=Cốc+Trà+Thủy+Tinh', 4.0, 10, 0, 30, 1),
('TC-021', 8, 'Phụ Kiện Pha Trà Cao Cấp', 'phu-kien-pha-tra-cao-cap', 'Bộ phụ kiện pha trà: kẹp chén, muỗng, bàn lọc, thìa.', 'Bộ phụ kiện đầy đủ', 150000, 180000, 'new', 'https://via.placeholder.com/600x500/6d4c41/ffffff?text=Phụ+Kiện+Pha+Trà', 4.5, 9, 0, 40, 1);

-- ============================================================
-- TÀI KHOẢN ADMIN (tạo tài khoản riêng; không dùng mật khẩu mặc định)
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_users (
  id int NOT NULL AUTO_INCREMENT,
  username varchar(50) NOT NULL,
  password_hash varchar(255) NOT NULL,
  full_name varchar(100) DEFAULT NULL,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ví dụ tạo admin:  password_hash = password_hash('mat-khau-manh-moi', PASSWORD_DEFAULT)
-- INSERT INTO admin_users (username, password_hash, full_name) VALUES
-- ('admin', '<hash>', 'Quản trị viên');

-- ============================================================
-- BANNER TRANG CHỦ (quản lý từ Admin > Banner trang chủ)
-- ============================================================
CREATE TABLE IF NOT EXISTS banners (
  id int NOT NULL AUTO_INCREMENT,
  image_url varchar(500) NOT NULL COMMENT 'Đường dẫn ảnh banner',
  sort_order int NOT NULL DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  is_active tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Bật/tắt hiển thị',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO banners (image_url, sort_order, is_active) VALUES
('img/1.png', 1, 1),
('img/2.png', 2, 1),
('img/3.png', 3, 1),
('img/4.png', 4, 1),
('img/5.png', 5, 1);

-- ============================================================
-- CÀI ĐẶT / NỘI DUNG KEY-VALUE (Admin > Nội dung trang Trà)
-- Giá trị trống/thiếu = dùng mặc định trong includes/tea-info-defaults.php
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
  skey varchar(100) NOT NULL,
  svalue longtext,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (skey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
