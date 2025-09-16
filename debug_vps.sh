#!/bin/bash

# Script Debug VPS untuk SISPEMA YASMU
# Jalankan dengan: bash debug_vps.sh

echo "=========================================="
echo "SISPEMA YASMU - Debug VPS Script"
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

if php -v | grep -q "8.1"; then
    print_success "PHP 8.1 detected"
else
    print_error "PHP 8.1 not found!"
fi

# 2. Cek Laravel Application
print_header "LARAVEL APPLICATION CHECK"
if [ -f "artisan" ]; then
    print_success "Laravel application found"
    
    # Cek environment
    if [ -f ".env" ]; then
        print_success ".env file exists"
    else
        print_error ".env file not found!"
    fi
    
    # Cek app key
    app_key=$(php artisan key:generate --show 2>/dev/null)
    if [ ! -z "$app_key" ]; then
        print_success "Application key is set"
    else
        print_warning "Application key might not be set"
    fi
else
    print_error "Laravel application not found! Make sure you're in the correct directory."
    exit 1
fi

# 3. Cek Database Connection
print_header "DATABASE CONNECTION CHECK"
db_check=$(php artisan tinker --execute="try { \DB::connection()->getPdo(); echo 'Database connected successfully'; } catch(\Exception \$e) { echo 'Database connection failed: ' . \$e->getMessage(); }" 2>/dev/null)
echo "$db_check"

if echo "$db_check" | grep -q "connected successfully"; then
    print_success "Database connection OK"
else
    print_error "Database connection failed!"
fi

# 4. Cek Laravel Logs
print_header "LARAVEL LOGS CHECK"
if [ -f "storage/logs/laravel.log" ]; then
    print_success "Laravel log file exists"
    
    # Tampilkan 10 error terakhir
    echo -e "\n${YELLOW}Last 10 ERROR entries:${NC}"
    grep -i "error\|exception\|fatal" storage/logs/laravel.log | tail -10
    
    # Tampilkan 5 warning terakhir
    echo -e "\n${YELLOW}Last 5 WARNING entries:${NC}"
    grep -i "warning" storage/logs/laravel.log | tail -5
else
    print_warning "Laravel log file not found"
fi

# 5. Cek Import Logs
print_header "IMPORT LOGS CHECK"
if [ -d "storage/app/import_logs" ]; then
    import_log_count=$(find storage/app/import_logs -name "*.json" | wc -l)
    print_success "Import logs directory exists ($import_log_count files)"
    
    if [ $import_log_count -gt 0 ]; then
        echo -e "\n${YELLOW}Recent import logs:${NC}"
        ls -la storage/app/import_logs/ | tail -5
    fi
else
    print_warning "Import logs directory not found"
fi

# 6. Cek File Permissions
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

# 7. Cek Composer Dependencies
print_header "COMPOSER DEPENDENCIES CHECK"
if [ -f "composer.json" ]; then
    print_success "Composer.json found"
    
    # Cek apakah vendor directory ada
    if [ -d "vendor" ]; then
        print_success "Vendor directory exists"
    else
        print_error "Vendor directory not found! Run: composer install"
    fi
else
    print_error "Composer.json not found!"
fi

# 8. Cek Web Server
print_header "WEB SERVER CHECK"
if command -v nginx >/dev/null 2>&1; then
    nginx_status=$(systemctl is-active nginx 2>/dev/null || echo "unknown")
    echo "Nginx status: $nginx_status"
    
    if [ "$nginx_status" = "active" ]; then
        print_success "Nginx is running"
    else
        print_warning "Nginx is not running"
    fi
elif command -v apache2 >/dev/null 2>&1; then
    apache_status=$(systemctl is-active apache2 2>/dev/null || echo "unknown")
    echo "Apache status: $apache_status"
    
    if [ "$apache_status" = "active" ]; then
        print_success "Apache is running"
    else
        print_warning "Apache is not running"
    fi
else
    print_warning "No web server detected"
fi

# 9. Cek Disk Space
print_header "DISK SPACE CHECK"
df -h | grep -E "(Filesystem|/dev/)"

# 10. Cek Memory Usage
print_header "MEMORY USAGE CHECK"
free -h

# 11. Cek PHP Extensions
print_header "PHP EXTENSIONS CHECK"
required_extensions=("pdo" "pdo_mysql" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath" "fileinfo" "zip")
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
    echo -e "\n${RED}Missing extensions: ${missing_extensions[*]}${NC}"
    echo "Install with: sudo apt-get install php8.1-${missing_extensions[*]}"
fi

# 12. Cek Import Logs Error
print_header "IMPORT LOGS ERROR CHECK"
if [ -d "storage/app/import_logs" ]; then
    error_logs=$(find storage/app/import_logs -name "*.json" -exec grep -l '"error_count":[1-9]' {} \; 2>/dev/null | wc -l)
    if [ $error_logs -gt 0 ]; then
        print_warning "Found $error_logs import logs with errors"
        
        echo -e "\n${YELLOW}Recent import errors:${NC}"
        find storage/app/import_logs -name "*.json" -exec grep -l '"error_count":[1-9]' {} \; | head -3 | while read file; do
            echo "File: $file"
            jq -r '.errors[] | "Row \(.row): \(.message)"' "$file" 2>/dev/null | head -3
            echo "---"
        done
    else
        print_success "No import errors found"
    fi
fi

# 13. Test Import Function
print_header "IMPORT FUNCTION TEST"
echo "Testing import functionality..."

# Buat file test sederhana
cat > test_import.csv << EOF
NIS,Nama Lengkap,Institusi ID,Tahun Ajaran ID,Kelas ID,Email,No HP,Alamat,Nama Orang Tua,No HP Orang Tua,Kategori Beasiswa
TEST001,Test User,1,1,1,test@example.com,081234567890,Test Address,Test Parent,081234567891,Beasiswa Prestasi
EOF

echo "Test file created: test_import.csv"

# 14. Cek Laravel Routes
print_header "LARAVEL ROUTES CHECK"
route_check=$(php artisan route:list --name=import-logs 2>/dev/null)
if [ ! -z "$route_check" ]; then
    print_success "Import logs routes are registered"
else
    print_error "Import logs routes not found!"
fi

# 15. Cek Cache
print_header "CACHE CHECK"
php artisan config:cache --quiet 2>/dev/null
if [ $? -eq 0 ]; then
    print_success "Config cache cleared successfully"
else
    print_warning "Failed to clear config cache"
fi

echo -e "\n${GREEN}=========================================="
echo "Debug check completed!"
echo "==========================================${NC}"

# Tampilkan rekomendasi
echo -e "\n${YELLOW}RECOMMENDATIONS:${NC}"
echo "1. Check Laravel logs: tail -f storage/logs/laravel.log"
echo "2. Check import logs: ls -la storage/app/import_logs/"
echo "3. Clear cache: php artisan cache:clear"
echo "4. Check permissions: sudo chown -R www-data:www-data storage bootstrap/cache"
echo "5. Restart web server: sudo systemctl restart nginx (or apache2)"
