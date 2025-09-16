#!/bin/bash

# Script untuk monitoring error Laravel secara real-time
# Jalankan dengan: bash monitor_errors.sh

echo "=========================================="
echo "SISPEMA YASMU - Error Monitor"
echo "=========================================="
echo "Press Ctrl+C to stop monitoring"
echo ""

# Warna untuk output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fungsi untuk menampilkan timestamp
timestamp() {
    date '+%Y-%m-%d %H:%M:%S'
}

# Fungsi untuk menampilkan error dengan warna
print_error() {
    echo -e "${RED}[$(timestamp)] ERROR: $1${NC}"
}

# Fungsi untuk menampilkan warning dengan warna
print_warning() {
    echo -e "${YELLOW}[$(timestamp)] WARNING: $1${NC}"
}

# Fungsi untuk menampilkan info dengan warna
print_info() {
    echo -e "${BLUE}[$(timestamp)] INFO: $1${NC}"
}

# Cek apakah file log ada
if [ ! -f "storage/logs/laravel.log" ]; then
    print_error "Laravel log file not found: storage/logs/laravel.log"
    exit 1
fi

print_info "Starting error monitoring..."
print_info "Monitoring file: storage/logs/laravel.log"
echo ""

# Monitor log file secara real-time
tail -f storage/logs/laravel.log | while read line; do
    # Cek error
    if echo "$line" | grep -qi "error\|exception\|fatal"; then
        print_error "$line"
    # Cek warning
    elif echo "$line" | grep -qi "warning"; then
        print_warning "$line"
    # Cek info penting
    elif echo "$line" | grep -qi "import\|database\|connection"; then
        print_info "$line"
    fi
done
