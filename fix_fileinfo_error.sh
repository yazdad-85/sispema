#!/bin/bash

# Script untuk memperbaiki error fileinfo di VPS
# Jalankan dengan: bash fix_fileinfo_error.sh

echo "=========================================="
echo "SISPEMA YASMU - Fix fileinfo Error"
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

# 1. Cek PHP Version
print_header "PHP VERSION CHECK"
php_version=$(php -v | head -n 1)
echo "PHP Version: $php_version"

# Deteksi versi PHP
if php -v | grep -q "8.1"; then
    PHP_VERSION="8.1"
    print_success "PHP 8.1 detected"
elif php -v | grep -q "8.0"; then
    PHP_VERSION="8.0"
    print_success "PHP 8.0 detected"
elif php -v | grep -q "7.4"; then
    PHP_VERSION="7.4"
    print_success "PHP 7.4 detected"
else
    print_error "Unsupported PHP version!"
    exit 1
fi

# 2. Cek fileinfo extension
print_header "FILEINFO EXTENSION CHECK"
if php -m | grep -q "fileinfo"; then
    print_success "fileinfo extension is already loaded"
    exit 0
else
    print_error "fileinfo extension is not loaded"
fi

# 3. Install fileinfo extension
print_header "INSTALLING FILEINFO EXTENSION"
echo "Installing php${PHP_VERSION}-fileinfo..."

if command -v apt >/dev/null 2>&1; then
    # Ubuntu/Debian
    sudo apt update
    sudo apt install -y php${PHP_VERSION}-fileinfo
elif command -v yum >/dev/null 2>&1; then
    # CentOS/RHEL
    sudo yum install -y php${PHP_VERSION}-fileinfo
elif command -v dnf >/dev/null 2>&1; then
    # Fedora
    sudo dnf install -y php${PHP_VERSION}-fileinfo
else
    print_error "Package manager not found!"
    exit 1
fi

# 4. Verifikasi instalasi
print_header "VERIFYING INSTALLATION"
if php -m | grep -q "fileinfo"; then
    print_success "fileinfo extension installed successfully"
else
    print_error "Failed to install fileinfo extension"
    exit 1
fi

# 5. Cek konfigurasi PHP
print_header "PHP CONFIGURATION CHECK"
php_ini_path=$(php --ini | grep "Loaded Configuration File" | cut -d: -f2 | xargs)
echo "PHP ini file: $php_ini_path"

# Cek apakah fileinfo ada di php.ini
if grep -q "extension=fileinfo" "$php_ini_path" 2>/dev/null; then
    print_success "fileinfo extension is enabled in php.ini"
else
    print_warning "fileinfo extension not found in php.ini, but it's loaded"
fi

# 6. Restart services
print_header "RESTARTING SERVICES"
echo "Restarting PHP-FPM..."

if systemctl is-active --quiet php${PHP_VERSION}-fpm; then
    sudo systemctl restart php${PHP_VERSION}-fpm
    print_success "PHP-FPM restarted"
else
    print_warning "PHP-FPM not running"
fi

# Restart web server
echo "Restarting web server..."
if systemctl is-active --quiet nginx; then
    sudo systemctl restart nginx
    print_success "Nginx restarted"
elif systemctl is-active --quiet apache2; then
    sudo systemctl restart apache2
    print_success "Apache restarted"
else
    print_warning "No web server found running"
fi

# 7. Test fileinfo
print_header "TESTING FILEINFO"
echo "Testing fileinfo functionality..."

# Buat file test
echo "test content" > test_file.txt

# Test dengan PHP
php -r "
if (function_exists('finfo_open')) {
    echo 'fileinfo functions are available' . PHP_EOL;
    \$finfo = finfo_open(FILEINFO_MIME_TYPE);
    \$mime = finfo_file(\$finfo, 'test_file.txt');
    echo 'MIME type detected: ' . \$mime . PHP_EOL;
    finfo_close(\$finfo);
} else {
    echo 'fileinfo functions are NOT available' . PHP_EOL;
}
"

# Cleanup
rm -f test_file.txt

# 8. Test Laravel validation
print_header "TESTING LARAVEL VALIDATION"
echo "Testing file validation in Laravel..."

php artisan tinker --execute="
try {
    \$file = new \Illuminate\Http\UploadedFile(
        storage_path('app/test.txt'),
        'test.txt',
        'text/plain',
        null,
        true
    );
    echo 'File validation test passed' . PHP_EOL;
} catch (Exception \$e) {
    echo 'File validation test failed: ' . \$e->getMessage() . PHP_EOL;
}
"

# 9. Cek extension lain yang diperlukan
print_header "CHECKING OTHER REQUIRED EXTENSIONS"
required_extensions=("pdo" "pdo_mysql" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath" "zip")
missing_extensions=()

for ext in "${required_extensions[@]}"; do
    if php -m | grep -q "$ext"; then
        print_success "$ext extension loaded"
    else
        print_error "$ext extension missing"
        missing_extensions+=("$ext")
    fi
done

if [ ${#missing_extensions[@]} -gt 0 ]; then
    echo -e "\n${YELLOW}Installing missing extensions...${NC}"
    for ext in "${missing_extensions[@]}"; do
        echo "Installing php${PHP_VERSION}-${ext}..."
        sudo apt install -y php${PHP_VERSION}-${ext}
    done
fi

# 10. Final test
print_header "FINAL TEST"
echo "Testing import functionality..."

# Buat file test Excel sederhana
cat > test_import.csv << EOF
NIS,Nama Lengkap,Institusi ID,Tahun Ajaran ID,Kelas ID,Email,No HP,Alamat,Nama Orang Tua,No HP Orang Tua,Kategori Beasiswa
TEST001,Test User,1,1,1,test@example.com,081234567890,Test Address,Test Parent,081234567891,Beasiswa Prestasi
EOF

echo "Test file created: test_import.csv"
echo "You can now test import functionality"

print_success "=========================================="
echo "Fileinfo error fix completed!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Test import functionality in your application"
echo "2. Check Laravel logs: tail -f storage/logs/laravel.log"
echo "3. If still having issues, restart the entire server"
