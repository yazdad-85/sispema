#!/bin/bash

# Script untuk menghapus ZendGuardLoader dan memperbaiki fileinfo
# Jalankan dengan: bash remove_zendguard.sh

echo "=========================================="
echo "SISPEMA YASMU - Remove ZendGuardLoader"
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

# 1. Cek file php.ini yang aktif
print_header "FINDING PHP.INI FILE"
php_ini_path=$(php --ini | grep "Loaded Configuration File" | cut -d: -f2 | xargs)
echo "Active PHP ini file: $php_ini_path"

if [ ! -f "$php_ini_path" ]; then
    print_error "PHP ini file not found!"
    exit 1
fi

# 2. Backup php.ini
print_header "BACKING UP PHP.INI"
backup_file="${php_ini_path}.backup.$(date +%Y%m%d_%H%M%S)"
cp "$php_ini_path" "$backup_file"
print_success "PHP ini backed up to: $backup_file"

# 3. Hapus ZendGuardLoader
print_header "REMOVING ZENDGUARDLOADER"
echo "Searching for ZendGuardLoader references..."

# Cek apakah ada ZendGuardLoader
if grep -i "zendguard\|zend" "$php_ini_path"; then
    print_warning "ZendGuardLoader found, removing..."
    
    # Hapus baris yang mengandung zendguard
    sed -i '/zendguard/d' "$php_ini_path"
    sed -i '/zend_extension.*zend/d' "$php_ini_path"
    sed -i '/extension.*zend/d' "$php_ini_path"
    
    print_success "ZendGuardLoader removed"
else
    print_success "ZendGuardLoader not found"
fi

# 4. Pastikan fileinfo aktif
print_header "ENABLING FILEINFO"
if ! grep -q "extension=fileinfo" "$php_ini_path"; then
    echo "extension=fileinfo" >> "$php_ini_path"
    print_success "fileinfo extension added"
else
    print_success "fileinfo extension already exists"
fi

# 5. Cek konfigurasi yang sudah diperbaiki
print_header "VERIFYING CONFIGURATION"
echo "Checking for problematic extensions..."
grep -i "zend\|guard" "$php_ini_path" || echo "No ZendGuardLoader found (good!)"

echo "Checking fileinfo configuration..."
grep -i "fileinfo" "$php_ini_path"

# 6. Restart PHP-FPM
print_header "RESTARTING PHP-FPM"
if systemctl is-active --quiet php8.1-fpm; then
    sudo systemctl restart php8.1-fpm
    print_success "PHP 8.1-FPM restarted"
elif systemctl is-active --quiet php8.0-fpm; then
    sudo systemctl restart php8.0-fpm
    print_success "PHP 8.0-FPM restarted"
else
    print_warning "PHP-FPM not found running"
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

# 8. Test fileinfo
print_header "TESTING FILEINFO"
echo "Testing fileinfo extension..."

# Test 1: Cek apakah extension loaded
if php -m | grep -q "fileinfo"; then
    print_success "fileinfo extension is loaded"
else
    print_error "fileinfo extension is NOT loaded"
fi

# Test 2: Cek apakah function tersedia
php -r "
if (function_exists('finfo_open')) {
    echo 'fileinfo functions are available' . PHP_EOL;
} else {
    echo 'fileinfo functions are NOT available' . PHP_EOL;
}
"

# Test 3: Test MIME detection
echo "test content" > test_mime.txt
php -r "
if (function_exists('finfo_open')) {
    \$finfo = finfo_open(FILEINFO_MIME_TYPE);
    \$mime = finfo_file(\$finfo, 'test_mime.txt');
    echo 'MIME type detected: ' . \$mime . PHP_EOL;
    finfo_close(\$finfo);
} else {
    echo 'MIME detection failed' . PHP_EOL;
}
"
rm -f test_mime.txt

# 9. Test Laravel UploadedFile
print_header "TESTING LARAVEL UPLOADEDFILE"
echo "Testing Laravel UploadedFile class..."

# Buat file test
echo "test content for upload" > test_upload.txt

php -r "
try {
    require_once 'vendor/autoload.php';
    \$file = new \Illuminate\Http\UploadedFile('test_upload.txt', 'test_upload.txt', 'text/plain', null, true);
    echo 'Laravel UploadedFile class works' . PHP_EOL;
    echo 'File MIME type: ' . \$file->getMimeType() . PHP_EOL;
    echo 'File size: ' . \$file->getSize() . ' bytes' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Laravel UploadedFile error: ' . \$e->getMessage() . PHP_EOL;
}
"

# Cleanup
rm -f test_upload.txt

# 10. Final verification
print_header "FINAL VERIFICATION"
echo "Running final checks..."

# Cek error log
echo "Checking for ZendGuardLoader errors..."
if php -r "echo 'test';" 2>&1 | grep -q "ZendGuardLoader"; then
    print_error "ZendGuardLoader error still present!"
else
    print_success "No ZendGuardLoader errors found"
fi

# Cek fileinfo
if php -r "echo extension_loaded('fileinfo') ? 'YES' : 'NO';" | grep -q "YES"; then
    print_success "fileinfo is working correctly"
else
    print_error "fileinfo is still not working"
fi

print_success "=========================================="
echo "ZendGuardLoader removal completed!"
echo "=========================================="
echo ""
echo "If fileinfo is still not working, try:"
echo "1. Check PHP version: php -v"
echo "2. Check PHP ini: php --ini"
echo "3. Restart the entire server: sudo reboot"
echo "4. Check web server error logs"
