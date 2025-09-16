# Panduan Deploy ke VPS

## File yang Perlu Diupload

### 1. File Controller yang Diperbaiki
- `app/Http/Controllers/FinancialReportController.php` (perbaikan balance sheet)
- `app/Http/Controllers/ClassController.php` (jika ada perubahan)

### 2. File Model yang Diperbaiki
- `app/Models/ClassModel.php` (jika ada perubahan)

### 3. File View yang Diperbaiki
- `resources/views/classes/edit.blade.php` (perbaikan dropdown kelas)

### 4. File Command Baru
- `app/Console/Commands/CreateBillingRecords.php` (command untuk membuat billing records)
- `app/Console/Commands/CreateMissingFeeStructures.php` (command untuk membuat fee structures)
- `app/Console/Commands/CreateSppActivityPlans.php` (command untuk membuat activity plans SPP)

### 5. File Service (jika ada)
- `app/Services/ImportLogService.php` (jika belum ada)
- `app/Http/Controllers/ImportLogController.php` (jika belum ada)

## Langkah-langkah Deploy

### 1. Upload File ke VPS
```bash
# Upload file-file yang telah dimodifikasi ke VPS
# Pastikan struktur folder sama dengan lokal
```

### 2. Jalankan Command di VPS

#### A. Buat Fee Structures yang Hilang
```bash
cd /www/wwwroot/presispema.yasmumanyar.or.id
php artisan fee:create-missing
```

#### B. Buat Billing Records untuk Semua Siswa
```bash
php artisan billing:create-records
```

#### C. Buat Activity Plans SPP
```bash
php artisan spp:create-activity-plans
```

### 3. Verifikasi Hasil
```bash
# Cek billing records
php artisan tinker --execute="
echo 'Total Students: ' . \App\Models\Student::count() . PHP_EOL;
echo 'Total Billing Records: ' . \App\Models\BillingRecord::count() . PHP_EOL;
echo 'Students with Billing Records: ' . \App\Models\Student::whereHas('billingRecords')->count() . PHP_EOL;
"

# Cek activity plans
php artisan tinker --execute="
echo 'Total Activity Plans: ' . \App\Models\ActivityPlan::count() . PHP_EOL;
echo 'SPP Activity Plans: ' . \App\Models\ActivityPlan::whereHas('category', function(\$q) {
    \$q->where('name', 'Pembayaran SPP');
})->count() . PHP_EOL;
"
```

### 4. Clear Cache (Opsional)
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## File yang Perlu Diupload (Daftar Lengkap)

1. `app/Http/Controllers/FinancialReportController.php`
2. `resources/views/classes/edit.blade.php`
3. `app/Console/Commands/CreateBillingRecords.php`
4. `app/Console/Commands/CreateMissingFeeStructures.php`
5. `app/Console/Commands/CreateSppActivityPlans.php`

## Hasil yang Diharapkan

Setelah menjalankan semua command:
- ✅ Semua siswa (1,319) memiliki billing records
- ✅ 15 activity plans SPP dibuat
- ✅ Halaman activity-plans menampilkan data SPP
- ✅ Balance sheet tidak error 500
- ✅ Dropdown kelas di form edit berfungsi

## Troubleshooting

Jika ada error:
1. Cek log: `tail -f storage/logs/laravel.log`
2. Pastikan database connection berfungsi
3. Pastikan semua file sudah terupload dengan benar
4. Jalankan command satu per satu untuk debug
