# Sistem Logging Import Excel

## Fitur yang Ditambahkan

### 1. ImportLogService
**File:** `app/Services/ImportLogService.php`

Service untuk menangani logging import Excel dengan fitur:
- Log error, warning, dan success per baris
- Simpan log detail ke file JSON
- Statistik import (total, berhasil, error, warning)
- Format error untuk display

### 2. ImportLogController
**File:** `app/Http/Controllers/ImportLogController.php`

Controller untuk menampilkan dan mengelola log import:
- `index()` - Daftar semua log import
- `show($logId)` - Detail log import
- `download($logId)` - Download log file
- `clear()` - Hapus semua log

### 3. Views
**Files:**
- `resources/views/import-logs/index.blade.php` - Daftar log
- `resources/views/import-logs/show.blade.php` - Detail log

### 4. Routes
**File:** `routes/web.php`

Route baru untuk import logs:
```php
Route::get('/import-logs', [ImportLogController::class, 'index'])->name('import-logs.index');
Route::get('/import-logs/{logId}', [ImportLogController::class, 'show'])->name('import-logs.show');
Route::get('/import-logs/{logId}/download', [ImportLogController::class, 'download'])->name('import-logs.download');
Route::delete('/import-logs/clear', [ImportLogController::class, 'clear'])->name('import-logs.clear');
```

## Cara Menggunakan

### 1. Import Siswa dengan Logging
- Import Excel seperti biasa di halaman Siswa
- Sistem akan otomatis log semua error, warning, dan success
- Setelah import, akan muncul link "Lihat Detail Log" jika ada error

### 2. Melihat Log Import
- Klik tombol "Log Import" di halaman Siswa
- Atau akses langsung: `/import-logs?type=students`

### 3. Filter Log
- Filter berdasarkan tipe import (students, classes, payments)
- Filter berdasarkan User ID
- Reset filter untuk melihat semua log

### 4. Detail Log
- Klik "Detail" untuk melihat error/warning per baris
- Klik "Download" untuk download log file JSON
- Lihat statistik success rate dan error rate

## Struktur Log File

Log disimpan di `storage/app/import_logs/` dengan format:
```
{import_type}_{Y_m_d_H_i_s}_user_{user_id}.json
```

Contoh: `students_2025_09_12_08_30_45_user_1.json`

### Isi Log File:
```json
{
    "import_type": "students",
    "user_id": 1,
    "file_name": "data_siswa.xlsx",
    "started_at": "2025-09-12T08:30:45.000000Z",
    "finished_at": "2025-09-12T08:30:50.000000Z",
    "duration": 5,
    "errors": [
        {
            "row": 5,
            "message": "NIS '12345' sudah ada",
            "data": ["12345", "John Doe", ...],
            "timestamp": "2025-09-12T08:30:47.000000Z"
        }
    ],
    "warnings": [...],
    "success": [...],
    "summary": {
        "total_rows": 100,
        "processed_rows": 100,
        "success_count": 95,
        "error_count": 3,
        "warning_count": 2
    }
}
```

## Error Handling

### Jenis Error yang Dicatat:
1. **Validasi Data:**
   - NIS dan Nama wajib diisi
   - Email tidak valid
   - NIS duplikat

2. **Referensi Data:**
   - Institusi ID tidak ditemukan
   - Tahun Ajaran ID tidak ditemukan
   - Kelas ID tidak ditemukan
   - Kategori Beasiswa tidak ditemukan

3. **File Error:**
   - File tidak bisa dibaca
   - Format file tidak valid

### Jenis Warning:
1. **Data Opsional:**
   - Kategori beasiswa tidak ditemukan (akan diabaikan)
   - Data kosong di field opsional

## Keamanan

- Log hanya bisa diakses oleh user yang login
- Log file disimpan di storage yang aman
- Tidak ada data sensitif yang di-log

## Maintenance

### Hapus Log Lama:
```bash
# Hapus semua log
php artisan tinker
>>> \App\Services\ImportLogService::clearOldLogs(30); // Hapus log > 30 hari
```

### Backup Log:
```bash
# Backup log ke folder backup
cp -r storage/app/import_logs/ backup/import_logs_$(date +%Y%m%d)/
```

## Troubleshooting

### Log tidak muncul:
1. Check permission folder `storage/app/import_logs/`
2. Check Laravel log: `tail -f storage/logs/laravel.log`
3. Pastikan ImportLogService di-include di controller

### Error saat save log:
1. Check disk space
2. Check permission storage folder
3. Check JSON encoding error

### Performance:
- Log file otomatis dibatasi (max 50 log di list)
- Log lama bisa dihapus otomatis
- File log di-compress jika perlu
