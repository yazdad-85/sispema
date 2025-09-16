#!/bin/bash

# Script untuk mengecek error saat ini di VPS
# Jalankan dengan: bash check_current_errors.sh

echo "=========================================="
echo "SISPEMA YASMU - Current Error Check"
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

# 1. Cek fileinfo extension
print_header "FILEINFO EXTENSION CHECK"
if php -m | grep -q "fileinfo"; then
    print_success "fileinfo extension is loaded"
else
    print_error "fileinfo extension is NOT loaded"
fi

# 2. Cek error terbaru di Laravel log
print_header "LATEST LARAVEL ERRORS"
echo "Checking last 20 error entries..."

if [ -f "storage/logs/laravel.log" ]; then
    # Cari error terbaru
    echo -e "\n${YELLOW}Recent ERROR entries:${NC}"
    grep -i "ERROR" storage/logs/laravel.log | tail -5
    
    echo -e "\n${YELLOW}Recent EXCEPTION entries:${NC}"
    grep -i "EXCEPTION" storage/logs/laravel.log | tail -5
    
    echo -e "\n${YELLOW}Recent FATAL entries:${NC}"
    grep -i "FATAL" storage/logs/laravel.log | tail -3
else
    print_warning "Laravel log file not found"
fi

# 3. Cek error spesifik untuk import
print_header "IMPORT SPECIFIC ERRORS"
echo "Checking import-related errors..."

if [ -f "storage/logs/laravel.log" ]; then
    echo -e "\n${YELLOW}Import errors:${NC}"
    grep -i "import\|file\|upload\|mime" storage/logs/laravel.log | tail -10
fi

# 4. Test fileinfo functionality
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

# 5. Cek PHP extensions yang diperlukan
print_header "REQUIRED PHP EXTENSIONS CHECK"
required_extensions=("fileinfo" "pdo" "pdo_mysql" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath" "zip")
missing_extensions=()

for ext in "${required_extensions[@]}"; do
    if php -m | grep -q "$ext"; then
        print_success "$ext extension loaded"
    else
        print_error "$ext extension missing"
        missing_extensions+=("$ext")
    fi
done

# 6. Cek Laravel application status
print_header "LARAVEL APPLICATION STATUS"
echo "Checking Laravel application..."

# Cek artisan
if [ -f "artisan" ]; then
    print_success "Laravel artisan found"
    
    # Test basic Laravel functionality
    echo "Testing Laravel routes..."
    php artisan route:list --name=import 2>/dev/null | head -5
    
    # Cek config
    echo "Testing Laravel config..."
    php artisan config:show app.name 2>/dev/null
    
else
    print_error "Laravel artisan not found!"
fi

# 7. Cek file permissions
print_header "FILE PERMISSIONS CHECK"
storage_writable=$(test -w storage && echo "yes" || echo "no")
bootstrap_writable=$(test -w bootstrap/cache && echo "yes" || echo "no")

if [ "$storage_writable" = "yes" ]; then
    print_success "Storage directory is writable"
else
    print_error "Storage directory is not writable!"
fi

if [ "$bootstrap_writable" = "yes" ]; then
    print_success "Bootstrap cache directory is writable"
else
    print_error "Bootstrap cache directory is not writable!"
fi

# 8. Cek database connection
print_header "DATABASE CONNECTION CHECK"
db_check=$(php artisan tinker --execute="try { \DB::connection()->getPdo(); echo 'Database connected successfully'; } catch(\Exception \$e) { echo 'Database connection failed: ' . \$e->getMessage(); }" 2>/dev/null)
echo "$db_check"

# 9. Cek error log terbaru dengan timestamp
print_header "LATEST ERROR WITH TIMESTAMP"
if [ -f "storage/logs/laravel.log" ]; then
    echo "Latest error entry:"
    tail -1 storage/logs/laravel.log
fi

# 10. Cek apakah ada error yang sama berulang
print_header "REPEATING ERRORS CHECK"
if [ -f "storage/logs/laravel.log" ]; then
    echo "Checking for repeating errors in last 100 lines..."
    
    # Cari error yang berulang
    tail -100 storage/logs/laravel.log | grep -i "ERROR" | sort | uniq -c | sort -nr | head -5
fi

# 11. Test import functionality secara langsung
print_header "DIRECT IMPORT TEST"
echo "Testing import functionality..."

# Buat file test CSV
cat > test_import_direct.csv << EOF
NIS,Nama Lengkap,Institusi ID,Tahun Ajaran ID,Kelas ID,Email,No HP,Alamat,Nama Orang Tua,No HP Orang Tua,Kategori Beasiswa
TEST001,Test User,1,1,1,test@example.com,081234567890,Test Address,Test Parent,081234567891,Beasiswa Prestasi
EOF

echo "Test file created: test_import_direct.csv"

# Test validasi file
php -r "
try {
    \$file = new \Illuminate\Http\UploadedFile(
        'test_import_direct.csv',
        'test_import_direct.csv',
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
rm -f test_import_direct.csv

# 12. Rekomendasi berdasarkan hasil
print_header "RECOMMENDATIONS"
echo "Based on the checks above:"

if [ ${#missing_extensions[@]} -gt 0 ]; then
    echo -e "\n${RED}Missing PHP extensions:${NC}"
    for ext in "${missing_extensions[@]}"; do
        echo "- Install: sudo apt install php8.1-${ext}"
    done
fi

if [ "$storage_writable" = "no" ] || [ "$bootstrap_writable" = "no" ]; then
    echo -e "\n${RED}Permission issues:${NC}"
    echo "- Fix permissions: sudo chown -R www-data:www-data storage bootstrap/cache"
fi

echo -e "\n${YELLOW}Next steps:${NC}"
echo "1. Check the specific error messages above"
echo "2. If fileinfo is working, the error might be in the import logic"
echo "3. Check if all required data exists in database"
echo "4. Test import with a simple file first"

print_success "=========================================="
echo "Error check completed!"
echo "=========================================="
