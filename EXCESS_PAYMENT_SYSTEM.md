# Sistem Kelebihan Bayar (Excess Payment System)

## Overview
Sistem ini menangani siswa yang mempunyai kelebihan bayar dari tahun ajaran sebelumnya. Kelebihan bayar akan otomatis ditransfer ke tagihan tahun ajaran baru sehingga sisa pembayaran bisa otomatis terbayarkan.

## Cara Kerja

### 1. Deteksi Kelebihan Bayar
- Sistem akan memeriksa setiap pembayaran siswa
- Membandingkan jumlah pembayaran dengan jumlah tagihan untuk billing record yang sama
- Jika pembayaran > tagihan, maka ada kelebihan bayar

### 2. Transfer Kelebihan Bayar
- Kelebihan bayar akan dibuat sebagai billing record baru di tahun ajaran baru
- Billing record ini memiliki status "active" dan bisa dibayarkan
- Notes: "Excess Payment Transfer from previous year - Amount: [jumlah]"

### 3. Penerapan Otomatis ke Tagihan
- Kelebihan bayar otomatis diterapkan ke tagihan tahun ajaran baru
- Tagihan yang ada akan berkurang sesuai dengan kelebihan bayar
- Status billing record akan berubah menjadi "partially_paid" atau "fully_paid"

### 4. Otomatis Terintegrasi
- Sistem otomatis berjalan saat:
  - Student promotion (promosi siswa)
  - Previous debt calculation (perhitungan hutang tahun lalu)
  - Manual command execution

## Commands

### 1. Handle Excess Payments
```bash
# Handle excess payment untuk siswa tertentu
php artisan excess:handle --student=8

# Handle excess payment untuk semua siswa
php artisan excess:handle --all
```

### 2. Apply Excess Payments
```bash
# Apply excess payment ke tagihan untuk siswa tertentu
php artisan excess:apply --student=8

# Apply excess payment ke tagihan untuk semua siswa
php artisan excess:apply --all
```

### 3. Fix Previous Debt (sudah terintegrasi)
```bash
# Fix previous debt untuk siswa tertentu
php artisan debt:fix-previous --student-id=8

# Fix previous debt untuk semua siswa
php artisan debt:fix-previous --all
```

## Contoh Kasus

### Siswa ID 8: MOH. SULTHAN NAZIRUL ASROFI
- **Tagihan**: 3.000.000 (billing record ID 560)
- **Pembayaran**: 3.500.000 (payment ID 3)
- **Kelebihan**: 500.000
- **Hasil**: Billing record baru dibuat dengan amount 500.000

### Billing Record yang Dibuat
```
ID: 575
Amount: 500,000
Remaining balance: 500,000
Status: active
Due date: 2025-10-14
Notes: Excess Payment Transfer from previous year - Amount: 500,000
```

## Integrasi dengan Sistem Lain

### 1. Student Promotion
- Saat siswa dipromosikan ke tahun ajaran baru
- Sistem otomatis mengecek dan menangani kelebihan bayar
- File: `app/Http/Controllers/StudentPromotionController.php`

### 2. Previous Debt Calculation
- Saat menghitung hutang tahun lalu
- Sistem otomatis mengecek dan menangani kelebihan bayar
- File: `app/Console/Commands/FixPreviousDebt.php`

### 3. Academic Year Model
- Method `calculatePreviousDebt()` sudah terintegrasi
- File: `app/Models/AcademicYear.php`

## Fitur Keamanan

### 1. Duplicate Prevention
- Sistem mengecek apakah sudah ada excess payment billing record
- Mencegah duplikasi billing record

### 2. Validation
- Mengecek apakah ada fee structure yang tersedia
- Mengecek apakah academic year valid
- Mengecek apakah student memiliki class room

### 3. Logging
- Semua aktivitas excess payment dicatat di log
- Memudahkan debugging dan audit

## Monitoring

### 1. Check Excess Payment Status
```php
// Cek apakah siswa memiliki excess payment
$excessBilling = BillingRecord::where('student_id', $studentId)
    ->where('notes', 'LIKE', '%Excess Payment Transfer%')
    ->first();
```

### 2. Check All Excess Payments
```php
// Cek semua excess payment di sistem
$allExcessPayments = BillingRecord::where('notes', 'LIKE', '%Excess Payment Transfer%')
    ->get();
```

## Troubleshooting

### 1. Excess Payment Tidak Terdeteksi
- Pastikan payment memiliki `billing_record_id` yang valid
- Pastikan billing record memiliki amount yang benar
- Pastikan payment status adalah 'verified' atau 'completed'

### 2. Billing Record Tidak Dibuat
- Pastikan ada fee structure yang aktif
- Pastikan student memiliki academic year
- Pastikan student memiliki class room

### 3. Duplicate Billing Record
- Sistem sudah memiliki duplicate prevention
- Jika masih terjadi, cek log untuk detail error

## Future Enhancements

### 1. Auto Payment Application
- Otomatis menerapkan excess payment ke tagihan bulanan
- Mengurangi manual work

### 2. Excess Payment Report
- Report untuk melihat semua excess payment
- Export ke Excel/PDF

### 3. Notification System
- Notifikasi ke admin saat ada excess payment
- Email notification ke orang tua

## Testing

### 1. Test dengan Siswa ID 8
```bash
php artisan excess:handle --student=8
```

### 2. Test dengan Semua Siswa
```bash
php artisan excess:handle --all
```

### 3. Verify Results
```php
// Cek billing record yang dibuat
$excessBilling = BillingRecord::where('student_id', 8)
    ->where('notes', 'LIKE', '%Excess Payment Transfer%')
    ->first();
```

## Conclusion

Sistem excess payment sudah terintegrasi dengan baik dan otomatis menangani kelebihan bayar siswa. Sistem ini memastikan bahwa kelebihan bayar tidak hilang dan bisa dimanfaatkan untuk tagihan tahun ajaran baru.
