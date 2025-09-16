#!/bin/bash

# Script untuk menjalankan command di VPS
# Jalankan di VPS: bash vps_commands.sh

echo "=== Menjalankan Command di VPS ==="

# Pastikan di direktori yang benar
cd /www/wwwroot/presispema.yasmumanyar.or.id

echo "Current directory: $(pwd)"

# 1. Buat Fee Structures yang Hilang
echo ""
echo "=== 1. Membuat Fee Structures yang Hilang ==="
php artisan fee:create-missing

# 2. Buat Billing Records untuk Semua Siswa
echo ""
echo "=== 2. Membuat Billing Records untuk Semua Siswa ==="
php artisan billing:create-records

# 3. Buat Activity Plans SPP
echo ""
echo "=== 3. Membuat Activity Plans SPP ==="
php artisan spp:create-activity-plans

# 4. Verifikasi Hasil
echo ""
echo "=== 4. Verifikasi Hasil ==="
php artisan tinker --execute="
echo 'Total Students: ' . \App\Models\Student::count() . PHP_EOL;
echo 'Total Billing Records: ' . \App\Models\BillingRecord::count() . PHP_EOL;
echo 'Students with Billing Records: ' . \App\Models\Student::whereHas('billingRecords')->count() . PHP_EOL;
echo 'Students without Billing Records: ' . \App\Models\Student::whereDoesntHave('billingRecords')->count() . PHP_EOL;
echo PHP_EOL;
echo 'Total Activity Plans: ' . \App\Models\ActivityPlan::count() . PHP_EOL;
echo 'SPP Activity Plans: ' . \App\Models\ActivityPlan::whereHas('category', function(\$q) {
    \$q->where('name', 'Pembayaran SPP');
})->count() . PHP_EOL;
"

# 5. Clear Cache
echo ""
echo "=== 5. Clear Cache ==="
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo ""
echo "=== SELESAI ==="
echo "Sekarang cek halaman:"
echo "- http://presispema.yasmumanyar.or.id/activity-plans"
echo "- http://presispema.yasmumanyar.or.id/financial-reports/balance-sheet"
echo "- http://presispema.yasmumanyar.or.id/classes/10/edit"
