# ============================================================
#  Tra Chuyen - Cai dat moi truong localhost tren may Windows moi
#  Chay:   powershell -ExecutionPolicy Bypass -File setup-localhost.ps1
#  Yeu cau: Windows co winget (Windows 10/11 hien dai)
# ============================================================

param(
    [string]$Branch = "main",
    [string]$DbName = "trachuyen_db",
    [string]$DbUser = "root",
    [string]$DbPass = "",
    [int]$Port = 8080,
    [switch]$SkipClone,
    [switch]$SkipDb
)

$ErrorActionPreference = 'Stop'

function Write-Step($msg) {
    Write-Host ""
    Write-Host "=== $msg ===" -ForegroundColor Cyan
}

function Have-Cmd($name) {
    return [bool](Get-Command $name -ErrorAction SilentlyContinue)
}

$repoDir = Join-Path $PWD 'TraChuyenProduct'

# ============ 1. PHP ============
Write-Step "1. Kiem tra PHP (8.1+)"
if (-not (Have-Cmd php)) {
    Write-Host "Chua co PHP -> cai bang winget..." -ForegroundColor Yellow
    winget install --id PHP.PHP.8.1 -e --accept-source-agreements --accept-package-agreements
    $env:Path = [Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [Environment]::GetEnvironmentVariable('Path','User')
}
php -v | Select-Object -First 1 | ForEach-Object { Write-Host $_ -ForegroundColor Green }

# Kiem tra extension can thiet
Write-Step "1b. Kiem tra extension PHP"
$loaded = @(php -m)
foreach ($ext in @('pdo_mysql','mbstring','fileinfo','gd','json')) {
    if ($loaded -contains $ext) {
        Write-Host "  - OK: $ext" -ForegroundColor Green
    } else {
        Write-Host "  - CANH BAO: thieu extension '$ext' (bat trong php.ini)" -ForegroundColor Yellow
    }
}

# ============ 2. MySQL / MariaDB ============
Write-Step "2. Kiem tra MySQL/MariaDB"
if (-not (Have-Cmd mysql)) {
    Write-Host "Chua co MySQL -> cai bang winget..." -ForegroundColor Yellow
    winget install --id Oracle.MySQL --exact --accept-source-agreements --accept-package-agreements 2>$null
    if (-not (Have-Cmd mysql)) {
        winget install --id MariaDB.Server --accept-source-agreements --accept-package-agreements
    }
}
$mysqlCmd = (Get-Command mysql -ErrorAction SilentlyContinue).Source
Write-Host "mysql: $mysqlCmd" -ForegroundColor Green

# ============ 3. Clone repo ============
Write-Step "3. Clone repository"
if (-not (Have-Cmd git)) {
    winget install --id Git.Git -e --accept-source-agreements --accept-package-agreements
    $env:Path = [Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [Environment]::GetEnvironmentVariable('Path','User')
}
if ($SkipClone -or (Test-Path (Join-Path $repoDir 'index.php'))) {
    Write-Host "Da co source tai $repoDir (bo qua clone)" -ForegroundColor Green
} else {
    git clone -b $Branch https://github.com/linhnv52/trachuyen.git $repoDir
    Write-Host "Da clone -> $repoDir" -ForegroundColor Green
}

# ============ 4. Import database.sql ============
Write-Step "4. Import database"
if (-not $SkipDb -and (Have-Cmd mysql)) {
    $sqlFile = Join-Path $repoDir 'database.sql'
    if (Test-Path $sqlFile) {
        $env:MYSQL_PWD = $DbPass
        Get-Content $sqlFile -Raw | & mysql -h 127.0.0.1 -u $DbUser --default-character-set=utf8mb4
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  - Da import database.sql vao '$DbName'" -ForegroundColor Green
            # Tao admin neu admin_users rong
            $countSql = "SELECT COUNT(*) FROM $DbName.admin_users"
            $cntRaw = & mysql -h 127.0.0.1 -u $DbUser -N -e $countSql
            if (($cntRaw -join '').Trim() -eq '0') {
                $env:ADMIN_PASS = 'admin123'
                $hash = php -r "echo password_hash(getenv('ADMIN_PASS'), PASSWORD_DEFAULT);"
                Remove-Item Env:ADMIN_PASS -ErrorAction SilentlyContinue
                $hash = $hash.Trim()
                $insertSql = "INSERT INTO $DbName.admin_users (username, password_hash, full_name) VALUES ('admin', '$hash', 'Quan tri vien')"
                & mysql -h 127.0.0.1 -u $DbUser -e $insertSql
                Write-Host "  - Da tao admin: 'admin' / 'admin123' (HAY DOI MAT KHAU sau khi dang nhap)" -ForegroundColor Green
            } else {
                Write-Host "  - admin_users da co du lieu (bo qua tao admin)." -ForegroundColor Green
            }
        } else {
            Write-Host "  - Import DB THAT BAI. Kiem tra user '$DbUser' / pass va MySQL dang chay." -ForegroundColor Red
        }
    } else {
        Write-Host "  - Khong thay $sqlFile trong source." -ForegroundColor Yellow
    }
}

# ============ 5. Huong dan chay ============
Write-Step "5. Chay website"
Write-Host "Chay server PHP (built-in) tai source:" -ForegroundColor Cyan
Write-Host "  cd '$repoDir'"
Write-Host "  php -S localhost:$Port" -ForegroundColor White
Write-Host ""
Write-Host "Truy cap:" -ForegroundColor Cyan
Write-Host "  Trang chu  :  http://localhost:$Port/index.php"
Write-Host "  San pham   :  http://localhost:$Port/product.php"
Write-Host "  Admin      :  http://localhost:$Port/admin/login.php"
Write-Host ""
Write-Host "Sinh ban tinh (docs/ cho GitHub Pages):" -ForegroundColor Cyan
Write-Host "  cd '$repoDir'"
Write-Host "  php tools/build-static.php" -ForegroundColor White
Write-Host "  php tools/gen-gallery.php" -ForegroundColor White
Write-Host "Cau hinh DB o $repoDir config\db.php neu can doi user/pass." -ForegroundColor Green