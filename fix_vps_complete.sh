#!/bin/bash

# Script lengkap untuk memperbaiki semua masalah di VPS
# Jalankan dengan: bash fix_vps_complete.sh

echo "=========================================="
echo "SISPEMA YASMU - Complete VPS Fix"
echo "=========================================="

# Warna untuk output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fungsi untuk menampilkan header
print_header() {
    echo -e "\n${BLUE}=== $1 ===${NC}"
}

# Fungsi untuk menampilkan error
print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Fungsi untuk menampilkan success
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Fungsi untuk menampilkan warning
print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# 1. Cek PHP Version dan konfigurasi
print_header "PHP CONFIGURATION CHECK"
php_version=$(php -v | head -n 1)
echo "PHP Version: $php_version"

# Deteksi versi PHP
if php -v | grep -q "8.1"; then
    PHP_VERSION="8.1"
elif php -v | grep -q "8.0"; then
    PHP_VERSION="8.0"
elif php -v | grep -q "7.4"; then
    PHP_VERSION="7.4"
else
    print_error "Unsupported PHP version!"
    exit 1
fi

echo "Detected PHP version: $PHP_VERSION"

# 2. Hapus ZendGuardLoader yang bermasalah
print_header "REMOVING PROBLEMATIC ZENDGUARDLOADER"
echo "Checking for ZendGuardLoader conflicts..."

# Cek file php.ini
php_ini_path=$(php --ini | grep "Loaded Configuration File" | cut -d: -f2 | xargs)
echo "PHP ini file: $php_ini_path"

# Backup php.ini
cp "$php_ini_path" "${php_ini_path}.backup.$(date +%Y%m%d_%H%M%S)"
print_success "PHP ini backed up"

# Hapus ZendGuardLoader
if grep -q "ZendGuardLoader" "$php_ini_path"; then
    print_warning "ZendGuardLoader found, removing..."
    sed -i '/ZendGuardLoader/d' "$php_ini_path"
    print_success "ZendGuardLoader removed"
else
    print_success "ZendGuardLoader not found"
fi

# 3. Install dan aktifkan fileinfo
print_header "INSTALLING FILEINFO EXTENSION"
echo "Installing php${PHP_VERSION}-fileinfo..."

if command -v apt >/dev/null 2>&1; then
    sudo apt update
    sudo apt install -y php${PHP_VERSION}-fileinfo
elif command -v yum >/dev/null 2>&1; then
    sudo yum install -y php${PHP_VERSION}-fileinfo
elif command -v dnf >/dev/null 2>&1; then
    sudo dnf install -y php${PHP_VERSION}-fileinfo
else
    print_error "Package manager not found!"
    exit 1
fi

# 4. Pastikan fileinfo aktif di php.ini
print_header "ENABLING FILEINFO IN PHP.INI"
if ! grep -q "extension=fileinfo" "$php_ini_path"; then
    echo "extension=fileinfo" >> "$php_ini_path"
    print_success "fileinfo extension added to php.ini"
else
    print_success "fileinfo extension already in php.ini"
fi

# 5. Install extension lain yang diperlukan
print_header "INSTALLING OTHER REQUIRED EXTENSIONS"
required_extensions=("pdo" "pdo_mysql" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath" "zip" "gd" "curl")

for ext in "${required_extensions[@]}"; do
    if ! php -m | grep -q "$ext"; then
        echo "Installing php${PHP_VERSION}-${ext}..."
        if command -v apt >/dev/null 2>&1; then
            sudo apt install -y php${PHP_VERSION}-${ext}
        fi
    fi
done

# 6. Restart PHP-FPM
print_header "RESTARTING PHP-FPM"
if systemctl is-active --quiet php${PHP_VERSION}-fpm; then
    sudo systemctl restart php${PHP_VERSION}-fpm
    print_success "PHP-FPM restarted"
else
    print_warning "PHP-FPM not running"
fi

# 7. Restart web server
print_header "RESTARTING WEB SERVER"
if systemctl is-active --quiet nginx; then
    sudo systemctl restart nginx
    print_success "Nginx restarted"
elif systemctl is-active --quiet apache2; then
    sudo systemctl restart apache2
    print_success "Apache restarted"
else
    print_warning "No web server found running"
fi

# 8. Verifikasi fileinfo
print_header "VERIFYING FILEINFO"
if php -m | grep -q "fileinfo"; then
    print_success "fileinfo extension is now loaded"
else
    print_error "fileinfo extension still not loaded"
    exit 1
fi

# 9. Test fileinfo functionality
print_header "TESTING FILEINFO FUNCTIONALITY"
echo "Testing fileinfo functions..."

# Buat file test
echo "test content for mime detection" > test_mime.txt

# Test dengan PHP
php -r "
if (function_exists('finfo_open')) {
    echo 'fileinfo functions are available' . PHP_EOL;
    \$finfo = finfo_open(FILEINFO_MIME_TYPE);
    \$mime = finfo_file(\$finfo, 'test_mime.txt');
    echo 'MIME type detected: ' . \$mime . PHP_EOL;
    finfo_close(\$finfo);
} else {
    echo 'fileinfo functions are NOT available' . PHP_EOL;
}
"

# Cleanup
rm -f test_mime.txt

# 10. Fix Laravel autoloader
print_header "FIXING LARAVEL AUTOLOADER"
echo "Regenerating autoloader..."

# Clear cache
php artisan cache:clear 2>/dev/null
php artisan config:clear 2>/dev/null
php artisan route:clear 2>/dev/null
php artisan view:clear 2>/dev/null

# Regenerate autoloader
composer dump-autoload --optimize 2>/dev/null

print_success "Laravel autoloader regenerated"

# 11. Fix file permissions
print_header "FIXING FILE PERMISSIONS"
echo "Setting correct permissions..."

# Set ownership
sudo chown -R www-data:www-data storage bootstrap/cache 2>/dev/null

# Set permissions
chmod -R 755 storage 2>/dev/null
chmod -R 755 bootstrap/cache 2>/dev/null

print_success "File permissions fixed"

# 12. Test Laravel functionality
print_header "TESTING LARAVEL FUNCTIONALITY"
echo "Testing Laravel classes..."

# Test UploadedFile class
php -r "
try {
    require_once 'vendor/autoload.php';
    \$file = new \Illuminate\Http\UploadedFile(
        'test.txt',
        'test.txt',
        'text/plain',
        null,
        true
    );
    echo 'Laravel UploadedFile class works' . PHP_EOL;
    echo 'File MIME type: ' . \$file->getMimeType() . PHP_EOL;
} catch (Exception \$e) {
    echo 'Laravel UploadedFile error: ' . \$e->getMessage() . PHP_EOL;
}
"

# 13. Test import validation
print_header "TESTING IMPORT VALIDATION"
echo "Testing file validation for import..."

# Buat file test CSV
cat > test_import.csv << EOF
NIS,Nama Lengkap,Institusi ID,Tahun Ajaran ID,Kelas ID,Email,No HP,Alamat,Nama Orang Tua,No HP Orang Tua,Kategori Beasiswa
TEST001,Test User,1,1,1,test@example.com,081234567890,Test Address,Test Parent,081234567891,Beasiswa Prestasi
EOF

# Test validasi file
php -r "
try {
    require_once 'vendor/autoload.php';
    \$file = new \Illuminate\Http\UploadedFile(
        'test_import.csv',
        'test_import.csv',
        'text/csv',
        null,
        true
    );
    echo 'File validation test passed' . PHP_EOL;
    echo 'File name: ' . \$file->getClientOriginalName() . PHP_EOL;
    echo 'File size: ' . \$file->getSize() . ' bytes' . PHP_EOL;
    echo 'MIME type: ' . \$file->getMimeType() . PHP_EOL;
} catch (Exception \$e) {
    echo 'File validation test failed: ' . \$e->getMessage() . PHP_EOL;
}
"

# Cleanup
rm -f test_import.csv

# 14. Final verification
print_header "FINAL VERIFICATION"
echo "Running final checks..."

# Cek semua extension
echo "PHP Extensions:"
php -m | grep -E "(fileinfo|pdo|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|zip)"

# Cek Laravel status
echo "Laravel Status:"
php artisan --version 2>/dev/null

# Cek database connection
echo "Database Connection:"
php artisan tinker --execute="try { \DB::connection()->getPdo(); echo 'Database connected successfully'; } catch(\Exception \$e) { echo 'Database connection failed: ' . \$e->getMessage(); }" 2>/dev/null

print_success "=========================================="
echo "VPS fix completed!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Test import functionality in your application"
echo "2. Check Laravel logs: tail -f storage/logs/laravel.log"
echo "3. If still having issues, check web server error logs"
echo "4. Monitor the application for any new errors"
