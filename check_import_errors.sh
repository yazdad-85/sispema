#!/bin/bash

# Script untuk mengecek error import di VPS
# Jalankan dengan: bash check_import_errors.sh

echo "=========================================="
echo "SISPEMA YASMU - Import Error Checker"
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

# 1. Cek Import Logs Directory
print_header "IMPORT LOGS DIRECTORY CHECK"
if [ -d "storage/app/import_logs" ]; then
    print_success "Import logs directory exists"
    
    # Hitung jumlah file log
    log_count=$(find storage/app/import_logs -name "*.json" | wc -l)
    echo "Total log files: $log_count"
    
    if [ $log_count -eq 0 ]; then
        print_warning "No import log files found"
        exit 0
    fi
else
    print_error "Import logs directory not found!"
    echo "Creating directory..."
    mkdir -p storage/app/import_logs
    chmod 755 storage/app/import_logs
    print_success "Directory created"
fi

# 2. Cek Log Files dengan Error
print_header "IMPORT LOGS WITH ERRORS"
error_logs=$(find storage/app/import_logs -name "*.json" -exec grep -l '"error_count":[1-9]' {} \; 2>/dev/null)
error_count=$(echo "$error_logs" | wc -l)

if [ $error_count -gt 0 ]; then
    print_warning "Found $error_count import logs with errors"
    
    echo -e "\n${YELLOW}Error Log Files:${NC}"
    echo "$error_logs"
    
    # Tampilkan detail error dari setiap file
    echo "$error_logs" | while read file; do
        if [ ! -z "$file" ]; then
            echo -e "\n${BLUE}--- File: $(basename "$file") ---${NC}"
            
            # Tampilkan summary
            echo "Import Type: $(jq -r '.import_type' "$file" 2>/dev/null)"
            echo "User ID: $(jq -r '.user_id' "$file" 2>/dev/null)"
            echo "File Name: $(jq -r '.file_name' "$file" 2>/dev/null)"
            echo "Started: $(jq -r '.started_at' "$file" 2>/dev/null)"
            echo "Total Rows: $(jq -r '.summary.total_rows' "$file" 2>/dev/null)"
            echo "Success: $(jq -r '.summary.success_count' "$file" 2>/dev/null)"
            echo "Errors: $(jq -r '.summary.error_count' "$file" 2>/dev/null)"
            echo "Warnings: $(jq -r '.summary.warning_count' "$file" 2>/dev/null)"
            
            # Tampilkan error details
            echo -e "\n${RED}Error Details:${NC}"
            jq -r '.errors[] | "Row \(.row): \(.message)"' "$file" 2>/dev/null | head -10
            
            # Tampilkan warning details
            warning_count=$(jq -r '.summary.warning_count' "$file" 2>/dev/null)
            if [ "$warning_count" -gt 0 ]; then
                echo -e "\n${YELLOW}Warning Details:${NC}"
                jq -r '.warnings[] | "Row \(.row): \(.message)"' "$file" 2>/dev/null | head -5
            fi
            
            echo "---"
        fi
    done
else
    print_success "No import errors found"
fi

# 3. Cek Log Files dengan Warning
print_header "IMPORT LOGS WITH WARNINGS"
warning_logs=$(find storage/app/import_logs -name "*.json" -exec grep -l '"warning_count":[1-9]' {} \; 2>/dev/null)
warning_count=$(echo "$warning_logs" | wc -l)

if [ $warning_count -gt 0 ]; then
    print_warning "Found $warning_count import logs with warnings"
    
    echo -e "\n${YELLOW}Warning Log Files:${NC}"
    echo "$warning_logs"
else
    print_success "No import warnings found"
fi

# 4. Tampilkan Log Terbaru
print_header "RECENT IMPORT LOGS"
recent_logs=$(find storage/app/import_logs -name "*.json" -printf '%T@ %p\n' | sort -n | tail -5 | cut -d' ' -f2-)

if [ ! -z "$recent_logs" ]; then
    echo -e "\n${YELLOW}Recent Import Logs:${NC}"
    echo "$recent_logs" | while read file; do
        if [ ! -z "$file" ]; then
            echo "File: $(basename "$file")"
            echo "  Type: $(jq -r '.import_type' "$file" 2>/dev/null)"
            echo "  User: $(jq -r '.user_id' "$file" 2>/dev/null)"
            echo "  Started: $(jq -r '.started_at' "$file" 2>/dev/null)"
            echo "  Status: $(jq -r '.summary | "\(.success_count)/\(.total_rows) success, \(.error_count) errors, \(.warning_count) warnings"' "$file" 2>/dev/null)"
            echo "---"
        fi
    done
else
    print_warning "No recent import logs found"
fi

# 5. Cek Laravel Log untuk Import Errors
print_header "LARAVEL LOG IMPORT ERRORS"
if [ -f "storage/logs/laravel.log" ]; then
    print_success "Laravel log file exists"
    
    echo -e "\n${YELLOW}Recent Import Errors in Laravel Log:${NC}"
    grep -i "import.*error\|import.*exception\|import.*failed" storage/logs/laravel.log | tail -10
    
    echo -e "\n${YELLOW}Recent Import Warnings in Laravel Log:${NC}"
    grep -i "import.*warning" storage/logs/laravel.log | tail -5
else
    print_warning "Laravel log file not found"
fi

# 6. Cek Database untuk Data Import
print_header "DATABASE IMPORT DATA CHECK"
echo "Checking imported students..."

# Cek jumlah siswa
student_count=$(php artisan tinker --execute="echo \App\Models\Student::count();" 2>/dev/null)
echo "Total students in database: $student_count"

# Cek siswa terbaru
echo -e "\n${YELLOW}Recent students:${NC}"
php artisan tinker --execute="\App\Models\Student::latest()->take(5)->get(['nis', 'name', 'created_at'])->each(function(\$s) { echo \$s->nis . ' - ' . \$s->name . ' - ' . \$s->created_at . PHP_EOL; });" 2>/dev/null

# 7. Cek File Permissions untuk Import
print_header "IMPORT FILE PERMISSIONS CHECK"
storage_writable=$(test -w storage && echo "yes" || echo "no")
import_logs_writable=$(test -w storage/app/import_logs && echo "yes" || echo "no")

if [ "$storage_writable" = "yes" ]; then
    print_success "Storage directory is writable"
else
    print_error "Storage directory is not writable!"
fi

if [ "$import_logs_writable" = "yes" ]; then
    print_success "Import logs directory is writable"
else
    print_error "Import logs directory is not writable!"
fi

# 8. Test Import Function
print_header "IMPORT FUNCTION TEST"
echo "Testing import functionality..."

# Buat file test
cat > test_import_debug.csv << EOF
NIS,Nama Lengkap,Institusi ID,Tahun Ajaran ID,Kelas ID,Email,No HP,Alamat,Nama Orang Tua,No HP Orang Tua,Kategori Beasiswa
DEBUG001,Debug User,1,1,1,debug@example.com,081234567890,Debug Address,Debug Parent,081234567891,Beasiswa Prestasi
EOF

echo "Test file created: test_import_debug.csv"
echo "You can test import with this file"

# 9. Rekomendasi
print_header "RECOMMENDATIONS"
echo -e "\n${YELLOW}If you found errors:${NC}"
echo "1. Check the specific error messages above"
echo "2. Verify your Excel file format matches the template"
echo "3. Check if all required data (institutions, academic years, classes) exist"
echo "4. Check file permissions: sudo chown -R www-data:www-data storage"
echo "5. Clear cache: php artisan cache:clear"
echo "6. Check Laravel logs: tail -f storage/logs/laravel.log"

echo -e "\n${YELLOW}To fix common issues:${NC}"
echo "1. Fix permissions: sudo chown -R www-data:www-data storage bootstrap/cache"
echo "2. Clear cache: php artisan cache:clear && php artisan config:cache"
echo "3. Restart web server: sudo systemctl restart nginx"
echo "4. Check database connection: php artisan tinker"

echo -e "\n${GREEN}=========================================="
echo "Import error check completed!"
echo "==========================================${NC}"
