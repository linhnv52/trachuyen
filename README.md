# Tra Chuyen

Website ban tra su dung PHP 8, MySQL/MariaDB va Apache.

## Cai dat tren may Windows moi

### Phan mem can cai

- Laragon Full: Apache, MySQL/MariaDB va PHP 8.1+.
- Git for Windows.
- Visual Studio Code (khong bat buoc).

### Tai source

Mo PowerShell:

    cd C:\laragon\www
    git clone -b main https://github.com/linhnv52/trachuyen.git TraChuyenProduct
    cd TraChuyenProduct

Mo Laragon va bam Start All de khoi dong Apache va MySQL. Neu source nam trong C:\laragon\www\TraChuyenProduct, Laragon thuong tao domain http://trachuyenproduct.test. Co the dung virtual host trachuyen.test neu document root tro dung vao thu muc chua index.php.

### Import database

Mo http://localhost/phpmyadmin, chon Import, chon file database.sql, sau do bam Go. File nay tu tao database trachuyen_db va cac bang can thiet.

Hoac dung MySQL CLI:

    mysql -h 127.0.0.1 -u root -p < database.sql

Kiem tra config/db.php:

    DB_HOST = 127.0.0.1
    DB_NAME = trachuyen_db
    DB_USER = root
    DB_PASS =

Neu MySQL co mat khau, sua DB_PASS. PHP can bat cac extension pdo_mysql, mbstring, fileinfo, gd va json.

### Tao tai khoan admin

Tao hash mat khau:

    php -r "echo password_hash('MatKhauMoi', PASSWORD_DEFAULT), PHP_EOL;"

Copy hash ket qua va chay SQL:

    USE trachuyen_db;
    INSERT INTO admin_users (username, password_hash, full_name)
    VALUES ('admin', 'DAN_HASH_VAO_DAY', 'Quan tri vien');

Dang nhap tai http://trachuyen.test/admin/login.php.

### Chay website

Voi Apache/Laragon:

    http://trachuyen.test/index.php
    http://trachuyen.test/product.php
    http://trachuyen.test/admin/login.php

Neu khong dung Apache, co the chay PHP built-in server:

    cd C:\laragon\www\TraChuyenProduct
    php -S localhost:8080

Sau do mo http://localhost:8080/index.php.

### Thu muc upload

Cac thu muc sau can ton tai va co quyen ghi:

    img/products/
    img/categories/
    img/banners/
    img/logo/
    img/videos/

### Cap nhat code

    cd C:\laragon\www\TraChuyenProduct
    git pull origin main

Tai lai trinh duyet bang Ctrl + F5. Khong can import lai database neu chi cap nhat UI, CSS hoac PHP.

### Build GitHub Pages

GitHub Pages chi chay ban HTML tinh, khong chay admin va database. Khi can build:

    cd C:\laragon\www\TraChuyenProduct
    php tools/build-static.php
    git add docs
    git commit -m "build: update static site"
    git push origin main

GitHub Pages can cau hinh source la branch main, thu muc /docs.

### Loi thuong gap

- Loi database: kiem tra MySQL dang chay va cau hinh trong config/db.php.
- Loi 404 domain .test: kiem tra Apache virtual host va document root.
- Anh khong hien thi: kiem tra duong dan va quyen doc thu muc img.
- Upload loi: kiem tra quyen ghi va upload_max_filesize/post_max_size trong php.ini.
- Loi gads-scrapper.js hoac gserp-scrapper.js: thuong do extension trinh duyet, khong phai source website.

## Cong nghe

- PHP 8 + MySQL/MariaDB + PDO
- HTML/CSS/JavaScript thuan
- GitHub Pages cho ban HTML tinh
