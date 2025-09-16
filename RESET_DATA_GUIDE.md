# 🔄 Panduan Reset Data SISPEMA

## 📋 Overview
Command ini digunakan untuk menghapus seluruh data sistem kecuali:
- ✅ **Users** (Pengguna)
- ✅ **Institutions** (Lembaga) 
- ✅ **Scholarship Categories** (Kategori Beasiswa)

## 🚀 Cara Penggunaan

### 1. **Reset Lengkap (Semua Data)**
```bash
php artisan sispema:reset-data
```
- Akan menampilkan konfirmasi sebelum menghapus
- Menghapus semua data kecuali users, institutions, scholarship categories

### 2. **Reset dengan Opsi Tambahan**
```bash
# Reset tanpa konfirmasi (langsung hapus)
php artisan sispema:reset-data --force

# Reset tapi pertahankan Academic Years
php artisan sispema:reset-data --keep-academic-years

# Reset tapi pertahankan Classes
php artisan sispema:reset-data --keep-classes

# Kombinasi opsi
php artisan sispema:reset-data --force --keep-academic-years --keep-classes
```

## ❌ Data yang AKAN DIHAPUS

| Tabel | Deskripsi | Catatan |
|-------|-----------|---------|
| `students` | Semua data siswa | Termasuk NIS, nama, kelas, dll |
| `classes` | Semua kelas | Kecuali jika `--keep-classes` |
| `academic_years` | Tahun ajaran | Kecuali jika `--keep-academic-years` |
| `fee_structures` | Struktur biaya | Semua jenis biaya per kelas |
| `billing_records` | Catatan penagihan | Semua tagihan siswa |
| `payments` | Pembayaran | Semua transaksi pembayaran |
| `activity_plans` | Rencana kegiatan | Rencana anggaran |
| `activity_realizations` | Realisasi kegiatan | Penggunaan anggaran |
| `cash_books` | Buku kas | Semua transaksi kas |
| `app_settings` | Pengaturan aplikasi | Konfigurasi sistem |
| `categories` | Kategori | Kategori umum |

## ✅ Data yang DIPERTAHANKAN

| Tabel | Deskripsi |
|-------|-----------|
| `users` | Semua pengguna (admin, staff, super admin) |
| `institutions` | Data lembaga/sekolah |
| `scholarship_categories` | Kategori beasiswa (Yatim Piatu, Alumni, dll) |

## ⚠️ Peringatan Penting

1. **BACKUP DATABASE** sebelum menjalankan command ini!
2. **Tidak bisa di-undo** - data yang dihapus tidak dapat dikembalikan
3. **Foreign key constraints** akan otomatis diatasi dengan urutan penghapusan yang benar
4. **Transaction rollback** akan dilakukan jika terjadi error

## 🔍 Contoh Output

```
🔄 SISPEMA Data Reset Tool
========================
✅ Data yang AKAN DIPERTAHANKAN:
   - Users (Pengguna)
   - Institutions (Lembaga)
   - Scholarship Categories (Kategori Beasiswa)

❌ Data yang AKAN DIHAPUS:
   - Students (Siswa)
   - Classes (Kelas)
   - Academic Years (Tahun Ajaran)
   - Fee Structures (Struktur Biaya)
   - Billing Records (Catatan Penagihan)
   - Payments (Pembayaran)
   - Activity Plans (Rencana Kegiatan)
   - Activity Realizations (Realisasi Kegiatan)
   - Cash Book (Buku Kas)
   - App Settings (Pengaturan Aplikasi)
   - Categories (Kategori)

Apakah Anda yakin ingin menghapus semua data tersebut? (yes/no) [no]:
> yes

🚀 Memulai proses reset data...
🗑️  Menghapus 150 records dari cash_books...
🗑️  Menghapus 89 records dari activity_realizations...
🗑️  Menghapus 12 records dari activity_plans...
🗑️  Menghapus 234 records dari payments...
🗑️  Menghapus 456 records dari billing_records...
🗑️  Menghapus 78 records dari fee_structures...
🗑️  Menghapus 89 students...
🗑️  Menghapus 23 classes...
🗑️  Menghapus 3 academic years...
🗑️  Menghapus 5 app settings...
🗑️  Menghapus 8 categories...

✅ Reset data berhasil!

📊 Data yang tersisa:
   - Users: 5
   - Institutions: 2
   - Scholarship Categories: 4

🎉 Sistem siap untuk data baru!
```

## 🛡️ Fitur Keamanan

- **Konfirmasi interaktif** (kecuali `--force`)
- **Database transaction** untuk konsistensi
- **Rollback otomatis** jika terjadi error
- **Logging** untuk audit trail
- **Urutan penghapusan** yang benar untuk foreign key constraints

## 📝 Logging

Semua aktivitas reset akan dicatat di:
- **Laravel Log**: `storage/logs/laravel.log`
- **Console Output**: Real-time progress

## 🔧 Troubleshooting

### Error: "Foreign key constraint fails"
- Command sudah mengatasi ini dengan urutan penghapusan yang benar
- Jika masih error, cek apakah ada tabel yang tidak terdaftar

### Error: "Table doesn't exist"
- Pastikan migrasi sudah dijalankan: `php artisan migrate`
- Cek nama tabel di database

### Command tidak ditemukan
- Pastikan file command ada di `app/Console/Commands/ResetSystemData.php`
- Jalankan: `php artisan list` untuk melihat daftar command
