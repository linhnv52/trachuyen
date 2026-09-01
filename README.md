# Trà Chuyện

Website bán trà "Trà Chuyện" — Giai đoạn 1: trưng bày sản phẩm + quản trị sản phẩm.

## Công nghệ
- PHP 8 + MySQL 8 (PDO)
- Front-end: HTML/CSS/JS thuần (giữ nguyên giao diện có sẵn)

## Cài đặt & chạy
1. Bật **Laragon** (Apache + MySQL).
2. Import database: file `database.sql` vào MySQL (`trachuyen_db`). Lần đầu, tạo tài khoản admin trong bảng `admin_users` (hash mật khẩu bằng `password_hash`, không dùng mật khẩu mặc định).
   - Nếu DB đã có sẵn, chỉ cần chạy phần seed + tạo bảng `admin_users` trong `database.sql`.
3. Truy cập:
   - Trang chủ: http://trachuyen.test (hoặc http://localhost:8080 nếu chạy `php -S localhost:8080`)
   - Trang sản phẩm: http://trachuyen.test/product.php
   - Admin: http://trachuyen.test/admin/login.php

## Cấu hình DB
Sửa các hằng số trong `config/db.php` nếu cần (host, user, pass).

## Cấu trúc thư mục
```
config/db.php            Kết nối DB + helper
index.php                Trang chủ (danh mục + bán chạy từ DB)
product.php              Danh sách sản phẩm (tìm kiếm/lọc/sắp xếp/phân trang)
productdetal.php         Chi tiết sản phẩm
includes/                Header/footer dùng chung
admin/login.php          Đăng nhập admin
admin/index.php          Dashboard
admin/product/list.php   Danh sách sản phẩm (quản lý)
admin/product/add.php    Thêm sản phẩm
admin/product/update.php Sửa sản phẩm
admin/product/model.php  CRUD + upload ảnh
admin/product/_form.php  Form dùng chung
database.sql             Schema + dữ liệu mẫu
img/products/            Ảnh sản phẩm upload
```

## Bản tĩnh (GitHub Pages)
Để phục vụ qua GitHub Pages (chỉ hỗ trợ tĩnh), chạy lệnh sinh bản tĩnh vào `docs/`:

```
php tools/build-static.php
```

Lệnh này đọc DB (cần MySQL đang chạy), xuất `docs/data/products.json` + render 7 trang chính và từng trang sản phẩm thành `docs/*.html`. Bật GitHub Pages với source là thư mục `/docs`.

## Ghi chú
- Các file `.html` cũ (`index.html`, `product.html`, `product-detail.html`, `admin.html`) là bản giao diện tĩnh tham khảo; bản chạy thật là các file `.php`.
- Giai đoạn sau: giỏ hàng, đặt hàng, tài khoản khách, quản lý danh mục/đơn hàng.