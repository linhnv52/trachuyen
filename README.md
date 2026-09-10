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

## Trien khai VPS an toan

- Bat HTTPS truoc khi mo admin; dat `TRACHUYEN_DB_HOST`, `TRACHUYEN_DB_NAME`, `TRACHUYEN_DB_USER`, `TRACHUYEN_DB_PASS` trong environment cua PHP-FPM, khong ghi mat khau vao source.
- Tao MySQL user rieng cho website, khong dung `root`, khong mo cong MySQL ra Internet.
- Web root chi tro vao thu muc website; khong cho public `.git`, file backup `.sql`, `.env` hoac thu muc `storage/`.
- Cap quyen ghi cho `img/products`, `img/categories`, `img/banners`, `img/logo`, `img/videos` va `storage/rate-limit`; cac thu muc con lai chi can quyen doc.
- Neu dung Nginx, chan truy cap den `.git`, `config/`, `database.sql`, `storage/` va cac file `*.log`, `*.sql`; khong cho PHP chay trong thu muc `img/`.
- Doi mat khau admin manh, khong dung tai khoan mau. Nen gioi han `/admin` bang VPN, IP allowlist hoac Basic Auth cua web server.
- Sau deploy kiem tra `https://domain/api/search.php?q=test&admin=1` phai tra `403`, va kiem tra header `Strict-Transport-Security`, `X-Frame-Options`, `X-Content-Type-Options`.

## Huong dan day du: deploy Tra Chuyen len VPS Ubuntu 24.04 LTS

Phan nay dung cho VPS moi, chay website PHP dong voi Nginx va MySQL. Thay cac gia tri trong dau `<...>` truoc khi chay. Khong copy mat khau vao GitHub va khong gui private SSH key.

### 1. Chuan bi truoc khi VPS hoat dong

Can co:

- IP VPS.
- Domain `trachuyen.com` da nam trong Cloudflare.
- SSH key tren may tinh ca nhan.
- Ban backup database local.
- Tai khoan admin va mat khau manh.

Kiem tra DNS sau khi co IP:

    nslookup trachuyen.com

Ket qua can tro ve dung IP VPS. Trong Cloudflare DNS tao:

    A      @      <IP_VPS>       Proxied
    CNAME  www    trachuyen.com  Proxied

### 2. Dang nhap va cap nhat Ubuntu

Dang nhap bang tai khoan VPS nha cung cap cap, sau do chay:

    ssh <user_vps>@<IP_VPS>
    sudo apt update && sudo apt full-upgrade -y
    sudo timedatectl set-timezone Asia/Ho_Chi_Minh

Tao user deploy rieng:

    sudo adduser deploy
    sudo usermod -aG sudo,www-data deploy

Them SSH public key cho user deploy. Chay tren may ca nhan de xem public key:

    type $env:USERPROFILE\.ssh\id_ed25519.pub

Tren VPS:

    sudo install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
    sudo nano /home/deploy/.ssh/authorized_keys
    sudo chown deploy:deploy /home/deploy/.ssh/authorized_keys
    sudo chmod 600 /home/deploy/.ssh/authorized_keys

Mo terminal moi va kiem tra dang nhap:

    ssh deploy@<IP_VPS>

Chi sau khi dang nhap bang user deploy thanh cong moi khoa root/password SSH:

    sudo nano /etc/ssh/sshd_config.d/hardening.conf

Noi dung:

    PermitRootLogin no
    PasswordAuthentication no
    KbdInteractiveAuthentication no
    PubkeyAuthentication yes

    sudo sshd -t
    sudo systemctl reload ssh

Khong dong phien SSH dang dung cho den khi da mo mot phien moi kiem tra thanh cong.

### 3. Cai cac goi can thiet

    sudo apt install -y nginx mysql-server git unzip curl ufw fail2ban logrotate \
      php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-fileinfo php8.3-gd php8.3-curl php8.3-xml

Kiem tra:

    php -v
    nginx -v
    mysql --version
    sudo systemctl enable --now nginx mysql php8.3-fpm fail2ban

### 4. Cau hinh firewall

    sudo ufw default deny incoming
    sudo ufw default allow outgoing
    sudo ufw allow OpenSSH
    sudo ufw allow 80/tcp
    sudo ufw allow 443/tcp
    sudo ufw enable
    sudo ufw status verbose

Khong mo cong `3306` ra Internet.

### 5. Tao database va user rieng

    sudo mysql

Trong MySQL:

    CREATE DATABASE trachuyen_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER 'trachuyen_app'@'localhost' IDENTIFIED BY '<MAT_KHAU_DB_MANH>';
    GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES ON trachuyen_db.* TO 'trachuyen_app'@'localhost';
    FLUSH PRIVILEGES;
    EXIT;

Khong dung user `root` cho ung dung.

### 6. Tai source

    sudo mkdir -p /var/www
    sudo chown deploy:www-data /var/www
    cd /var/www
    git clone -b main https://github.com/linhnv52/trachuyen.git trachuyen
    cd /var/www/trachuyen

Import schema va seed data mot lan:

    mysql -u root -p trachuyen_db < database.sql

Neu `database.sql` tu tao database, co the dung:

    mysql -u root -p < database.sql

Neu dang phuc hoi database backup:

    mysql -u root -p trachuyen_db < /duong-dan/trachuyen_backup.sql

### 7. Cau hinh bien moi truong cho PHP-FPM

Mo pool PHP-FPM:

    sudo nano /etc/php/8.3/fpm/pool.d/www.conf

Them cuoi file:

    clear_env = no
    env[TRACHUYEN_DB_HOST] = 127.0.0.1
    env[TRACHUYEN_DB_NAME] = trachuyen_db
    env[TRACHUYEN_DB_USER] = trachuyen_app
    env[TRACHUYEN_DB_PASS] = <MAT_KHAU_DB_MANH>

Khong ghi mat khau nay vao `config/db.php`.

### 8. Cap quyen thu muc

    sudo chown -R deploy:www-data /var/www/trachuyen
    sudo find /var/www/trachuyen -type d -exec chmod 755 {} \;
    sudo find /var/www/trachuyen -type f -exec chmod 644 {} \;
    sudo chmod 640 /var/www/trachuyen/config/db.php
    sudo mkdir -p /var/www/trachuyen/storage/rate-limit
    sudo chown -R deploy:www-data /var/www/trachuyen/storage /var/www/trachuyen/img
    sudo find /var/www/trachuyen/storage /var/www/trachuyen/img -type d -exec chmod 2775 {} \;
    sudo find /var/www/trachuyen/storage /var/www/trachuyen/img -type f -exec chmod 664 {} \;

PHP-FPM can ghi vao `img/*` va `storage/rate-limit`; cac file PHP khac chi can quyen doc.

### 9. Cau hinh Nginx

Tao file:

    sudo nano /etc/nginx/sites-available/trachuyen.com

Noi dung:

    server {
        listen 80;
        listen [::]:80;
        server_name trachuyen.com www.trachuyen.com;
        root /var/www/trachuyen;
        index index.php index.html;

        client_max_body_size 8M;

        location ~ /\. { deny all; }
        location ~* ^/(config|storage|tools|admin/logs)(/|$) { deny all; }
        location ~* \.(sql|env|log|ini|bak|backup)$ { deny all; }
        location = /admin/rebuild.php { deny all; }
        location ~* ^/img/.*\.(php|php[0-9]?|phtml|phar|cgi|pl|py|sh)$ { deny all; }

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
            try_files $uri =404;
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        }
    }

Bat site va kiem tra:

    sudo ln -s /etc/nginx/sites-available/trachuyen.com /etc/nginx/sites-enabled/trachuyen.com
    sudo rm -f /etc/nginx/sites-enabled/default
    sudo nginx -t
    sudo systemctl reload nginx

Truoc khi cai SSL, mo thu `http://trachuyen.com`.

### 10. Cai HTTPS va ket noi Cloudflare

Trong Cloudflare tam thoi dat SSL/TLS mode la `Full` neu dung certificate Let’s Encrypt tren VPS. Chay:

    sudo apt install -y certbot python3-certbot-nginx
    sudo certbot --nginx -d trachuyen.com -d www.trachuyen.com
    sudo certbot renew --dry-run

Sau khi certificate hoat dong, chuyen Cloudflare SSL/TLS mode sang:

    Full (strict)

Bat them:

    Always Use HTTPS
    Automatic HTTPS Rewrites

Khong dung `Flexible`.

### 11. Cau hinh PHP upload va restart

    sudo nano /etc/php/8.3/fpm/php.ini

Dam bao co:

    upload_max_filesize = 8M
    post_max_size = 16M
    max_execution_time = 60
    expose_php = Off

Ap dung:

    sudo systemctl restart php8.3-fpm
    sudo nginx -t
    sudo systemctl reload nginx

### 12. Chay kiem tra sau deploy

    curl -I https://trachuyen.com
    curl -i "https://trachuyen.com/api/search.php?q=tra&admin=1"
    curl -i https://trachuyen.com/admin/login.php

Ket qua can dat:

- Trang chu tra ve `200`.
- API co `admin=1` khi chua dang nhap tra `403`.
- Admin login hien thi binh thuong.
- Header co `strict-transport-security`, `x-frame-options`, `x-content-type-options`.
- Upload anh, logo va banner thanh cong.
- MySQL khong truy cap duoc tu Internet.

Kiem tra log khi co loi:

    sudo tail -f /var/log/nginx/error.log
    sudo journalctl -u php8.3-fpm -f

### 13. Bat fail2ban

    sudo nano /etc/fail2ban/jail.d/sshd.local

Noi dung:

    [sshd]
    enabled = true
    port = ssh
    maxretry = 5
    findtime = 10m
    bantime = 1h

Ap dung:

    sudo systemctl restart fail2ban
    sudo fail2ban-client status sshd

### 14. Tu dong don log va rate-limit files

Tao cron root:

    sudo crontab -e

Them:

    0 4 * * * find /var/www/trachuyen/storage/rate-limit -type f -mtime +2 -delete
    30 4 * * * journalctl --vacuum-time=14d >/dev/null 2>&1

Nginx da duoc Ubuntu quan ly log bang `logrotate`. Kiem tra:

    sudo logrotate -d /etc/logrotate.conf

### 15. Backup database hang ngay

Tao file:

    sudo nano /root/backup-trachuyen.sh

Noi dung:

    #!/bin/bash
    set -euo pipefail
    backup_dir=/var/backups/trachuyen
    mkdir -p "$backup_dir"
    chmod 700 "$backup_dir"
    date_tag=$(date +%F-%H%M)
    mysqldump --single-transaction --routines --events trachuyen_db | gzip > "$backup_dir/trachuyen-$date_tag.sql.gz"
    find "$backup_dir" -type f -name '*.sql.gz' -mtime +14 -delete

    sudo chmod 700 /root/backup-trachuyen.sh
    sudo crontab -e

Them:

    15 3 * * * /root/backup-trachuyen.sh

Backup tren cung VPS khong phai backup day du. Nen tai dinh ky file backup ve may ca nhan hoac object storage.

### 16. Cap nhat website ve sau

    ssh deploy@trachuyen.com
    cd /var/www/trachuyen
    git pull --ff-only origin main
    sudo chown -R deploy:www-data /var/www/trachuyen
    sudo find img storage -type d -exec chmod 2775 {} \;
    sudo systemctl restart php8.3-fpm
    sudo nginx -t && sudo systemctl reload nginx

Khong dung nut `Cap nhat website` de Git push tu production. Nginx o tren da chan `admin/rebuild.php`; hay deploy qua SSH de giam rui ro.

### 17. Rollback

Truoc moi lan cap nhat:

    cd /var/www/trachuyen
    git log -5 --oneline

Neu ban moi loi:

    git log --oneline -5
    git checkout <COMMIT_ON_DINH>
    sudo systemctl restart php8.3-fpm
    sudo nginx -t && sudo systemctl reload nginx

Chi rollback code. Neu co thay doi database, phai phuc hoi backup database phu hop truoc do.
