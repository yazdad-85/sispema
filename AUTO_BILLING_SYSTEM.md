# Sistem Otomatis Billing Record dan Activity Plan

## Overview
Sistem ini memungkinkan pembuatan billing record dan update activity plan secara otomatis ketika siswa baru ditambahkan ke sistem.

## Fitur

### 1. Auto Billing Record
- **Trigger**: Saat siswa baru ditambahkan (manual atau import)
- **Aksi**: Otomatis membuat 12 billing record (Jan-Des) untuk siswa baru
- **Field yang dibuat**:
  - `student_id`: ID siswa baru
  - `fee_structure_id`: Berdasarkan kelas dan lembaga
  - `billing_month`: 1-12 (Jan-Des)
  - `origin_year`: Tahun ajaran siswa
  - `origin_class`: ID kelas siswa
  - `amount`: Dari `monthly_amount` fee structure
  - `remaining_balance`: Sama dengan amount
  - `status`: 'active'
  - `notes`: 'ANNUAL'
  - `due_date`: 30 hari dari sekarang

### 2. Auto Activity Plan Update
- **Trigger**: Saat siswa baru ditambahkan
- **Aksi**: Update activity plan SPP untuk level kelas yang sama
- **Field yang diupdate**:
  - `budget_amount`: Total amount dari semua billing records
  - `equivalent_1`: Jumlah siswa
  - `equivalent_2`: 'Per bulan'

## Implementasi

### 1. StudentController
Method `createBillingRecordForStudent()` dipanggil saat:
- Manual create student (method `store`)
- Import student (method `importStudentRowWithLogging`)

### 2. StudentObserver
Observer yang mendengarkan event `created` dan `updated` pada model Student:
- File: `app/Observers/StudentObserver.php`
- Didaftarkan di: `app/Providers/AppServiceProvider.php`

### 3. Logging
Semua proses dicatat di Laravel log:
- Success: Info level
- Warning: Jika fee structure tidak ditemukan
- Error: Jika ada exception

## Testing

### Command Test
```bash
php artisan test:auto-billing-system
```

### Manual Test
1. Buka http://localhost:8000/students
2. Klik "Tambah Siswa"
3. Isi form dan submit
4. Cek billing records di database
5. Cek activity plans di http://localhost:8000/activity-plans

## Database Requirements

### Fee Structures
- Harus memiliki `monthly_amount` yang valid
- Harus sesuai dengan `institution_id`, `academic_year_id`, dan `class_id`

### Billing Records
- Field required: `origin_class`, `remaining_balance`
- Field optional: `notes`, `due_date`

## Troubleshooting

### 1. Billing Record Gagal Dibuat
- Cek fee structure ada dan valid
- Cek field `monthly_amount` tidak null
- Cek log untuk error detail

### 2. Activity Plan Tidak Update
- Cek Observer terdaftar di AppServiceProvider
- Cek kategori "Pembayaran SPP" ada
- Cek log untuk error detail

### 3. Import Excel Gagal
- Cek file Excel format benar
- Cek data institution, academic year, class valid
- Cek log import di `/financial-reports/import-logs`

## Monitoring

### Log Files
- `storage/logs/laravel.log`: Semua log sistem
- `storage/logs/import/`: Log khusus import Excel

### Database Queries
```sql
-- Cek billing records siswa baru
SELECT * FROM billing_records 
WHERE student_id = [STUDENT_ID] 
ORDER BY billing_month;

-- Cek activity plans update
SELECT * FROM activity_plans 
WHERE category_id = (SELECT id FROM categories WHERE name = 'Pembayaran SPP');
```

## Promosi Siswa

### Auto Billing Record Saat Promosi
- **Trigger**: Saat siswa dipromosi ke kelas/tahun ajaran baru
- **Aksi**: 
  - Hapus billing record lama untuk tahun ajaran baru (jika ada)
  - Buat 12 billing record baru untuk tahun ajaran baru
  - Update `origin_year` sesuai tahun ajaran baru
- **File**: `app/Http/Controllers/StudentPromotionController.php`

### Auto Activity Plan Update Saat Promosi
- **Trigger**: Observer mendeteksi perubahan `class_id` atau `academic_year_id`
- **Aksi**: Update activity plan SPP untuk level kelas yang baru
- **File**: `app/Observers/StudentObserver.php`

### Testing Promosi
```bash
php artisan test:promotion-system
```

## Auto Copy Fee Structure

### Alur yang Benar
1. **Buat Tahun Ajaran Baru** → Tidak otomatis copy fee structure
2. **Buat Kelas Baru** → Manual atau otomatis saat promosi
3. **Copy Fee Structure** → Manual atau command

### Manual Copy Fee Structure
- **Trigger**: Klik tombol "Salin dari Tahun Sebelumnya" di halaman fee structures
- **Aksi**: 
  - Copy fee structure dari tahun ajaran sebelumnya
  - Hanya copy untuk kelas yang sudah ada
  - Copy semua field: monthly_amount, yearly_amount, scholarship_discount
- **File**: `app/Http/Controllers/FeeStructureController.php`

### Command Copy Fee Structure
```bash
# Copy untuk semua tahun ajaran yang belum memiliki fee structure (hanya jika kelas sudah ada)
php artisan fee:auto-copy

# Copy untuk tahun ajaran tertentu
php artisan fee:auto-copy {academic_year_id}

# Force copy meskipun kelas sudah ada
php artisan fee:auto-copy --force
```

### Testing Auto Copy
```bash
php artisan test:auto-copy-fee-structure
```

## Status
✅ **SELESAI** - Sistem otomatis sudah berfungsi dengan baik
✅ **PROMOSI** - Sistem promosi dengan billing dan activity plan sudah berfungsi
✅ **COPY FEE** - Sistem otomatis copy fee structure sudah berfungsi
