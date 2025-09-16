#!/bin/bash

# Script untuk mempersiapkan file-file yang perlu diupload ke VPS

echo "=== Mempersiapkan file untuk VPS ==="

# Buat direktori untuk file VPS
mkdir -p vps_files

# Copy file-file yang diperlukan
echo "Copying files..."

# 1. Controller yang diperbaiki
cp app/Http/Controllers/FinancialReportController.php vps_files/

# 2. View yang diperbaiki  
cp resources/views/classes/edit.blade.php vps_files/

# 3. Command baru
cp app/Console/Commands/CreateBillingRecords.php vps_files/
cp app/Console/Commands/CreateMissingFeeStructures.php vps_files/
cp app/Console/Commands/CreateSppActivityPlans.php vps_files/

# 4. Service (jika ada)
if [ -f "app/Services/ImportLogService.php" ]; then
    cp app/Services/ImportLogService.php vps_files/
fi

if [ -f "app/Http/Controllers/ImportLogController.php" ]; then
    cp app/Http/Controllers/ImportLogController.php vps_files/
fi

# 5. Model (jika ada perubahan)
if [ -f "app/Models/ClassModel.php" ]; then
    cp app/Models/ClassModel.php vps_files/
fi

echo "Files prepared in vps_files/ directory:"
ls -la vps_files/

echo ""
echo "=== Instruksi Upload ke VPS ==="
echo "1. Upload semua file dari vps_files/ ke VPS dengan struktur folder yang sama"
echo "2. Jalankan command berikut di VPS:"
echo "   cd /www/wwwroot/presispema.yasmumanyar.or.id"
echo "   php artisan fee:create-missing"
echo "   php artisan billing:create-records" 
echo "   php artisan spp:create-activity-plans"
echo "3. Verifikasi hasil dengan command di VPS_DEPLOYMENT_GUIDE.md"
