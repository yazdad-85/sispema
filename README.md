# Aplikasi Pembayaran SPP YASMU

Aplikasi sistem pembayaran SPP (Sumbangan Pembinaan Pendidikan) untuk Yayasan Muhammadiyah dengan fitur multi-tenant dan tracking tagihan berkelanjutan.

## Fitur Utama

### 🏢 Multi-Tenant System
- **Admin Pusat**: Akses ke semua lembaga, laporan konsolidasi
- **Kasir per Lembaga**: Akses terbatas pada lembaga masing-masing
- Database terpusat dengan isolasi data per lembaga

### 💰 Sistem Tagihan Berkelanjutan
- Tracking tagihan berdasarkan tahun akademik + kelas saat tagihan terbentuk
- Sistem carry-over otomatis saat naik kelas
- Pembayaran dapat dialokasikan ke tagihan tahun manapun
- Status tagihan: ACTIVE, PARTIALLY_PAID, FULLY_PAID, OVERDUE

### 🎓 Sistem Beasiswa
- **Yatim-piatu**: 100% (gratis total)
- **Yatim**: 75% diskon
- **Piatu**: 75% diskon  
- **Anak guru/tendik**: 50% diskon
- **Tidak mampu**: 25% diskon

### 💳 Metode Pembayaran
- **Tunai**: Pembayaran langsung di kasir
- **Transfer**: Transfer bank manual
- **Digital Payment**: Integrasi Midtrans dan BTN Bank

### 🧾 Kwitansi Dual-Column
- **Kolom 1**: Detail transaksi hari ini (payment_id, tanggal, jumlah, metode)
- **Kolom 2**: Status rekap 12 bulan dengan saldo berjalan dan sisa tunggakan

### 📊 Laporan Komprehensif
- Laporan tunggakan per tahun akademik
- Laporan per jenjang dan kelas
- Laporan siswa dengan tagihan berkelanjutan
- Export PDF dan Excel
- Backup otomatis harian

## Teknologi

- **Backend**: Laravel 10
- **Database**: MySQL
- **Frontend**: Bootstrap 5 + PWA
- **Payment Gateway**: Midtrans, BTN Bank
- **Security**: 2FA, Audit Trail, SSL

## Instalasi

### 1. Prerequisites
- PHP 8.1+
- MySQL 8.0+
- Composer
- Node.js & NPM

### 2. Clone Repository
```bash
git clone <repository-url>
cd spp-yasmu-app
```

### 3. Install Dependencies
```bash
composer install
npm install
```

### 4. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spp_yasmu
DB_USERNAME=root
DB_PASSWORD=

MIDTRANS_MERCHANT_ID=your_merchant_id
MIDTRANS_CLIENT_KEY=your_client_key
MIDTRANS_SERVER_KEY=your_server_key
MIDTRANS_IS_PRODUCTION=false

BTN_MERCHANT_ID=your_btn_merchant_id
BTN_TERMINAL_ID=your_btn_terminal_id
BTN_CLIENT_ID=your_btn_client_id
BTN_CLIENT_SECRET=your_btn_client_secret
BTN_IS_PRODUCTION=false
```

### 5. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 6. Compile Assets
```bash
npm run dev
```

### 7. Run Application
```bash
php artisan serve
```

## Struktur Database

### Tabel Utama
- `institutions` - Data lembaga
- `users` - Pengguna dengan role admin_pusat/kasir
- `academic_years` - Tahun akademik
- `classes` - Kelas per lembaga
- `students` - Data siswa
- `fee_structures` - Struktur biaya per kelas/tahun
- `billing_records` - Tagihan individual
- `payments` - Pembayaran
- `payment_allocations` - Alokasi pembayaran ke tagihan

### Tabel Pendukung
- `scholarship_categories` - Kategori beasiswa
- `audit_logs` - Log audit trail
- `two_factor_auth` - 2FA untuk admin
- `payment_gateways` - Konfigurasi payment gateway
- `digital_payments` - Pembayaran digital

## Workflow Sistem

### 1. Pembentukan Tagihan
- Setiap siswa otomatis mendapat tagihan bulanan sesuai fee_structure
- Tagihan memiliki "origin_year" dan "origin_class" untuk tracking

### 2. Sistem Carry-Over
- Saat naik kelas, tagihan lama tetap aktif
- Tagihan baru terbentuk untuk tahun akademik baru
- Pembayaran bisa dialokasikan ke tagihan manapun

### 3. Alokasi Pembayaran
- Pembayaran dicatat di tabel `payments`
- Sistem alokasi otomatis ke tagihan tertua (FIFO)
- Bisa manual override alokasi jika diperlukan

## API Endpoints

### Payment Gateway
- `POST /payment/midtrans/callback` - Callback Midtrans
- `POST /payment/btn/callback` - Callback BTN Bank

### Reports
- `GET /reports/outstanding` - Laporan tunggakan
- `GET /reports/payments` - Laporan pembayaran
- `GET /reports/students` - Laporan siswa

## Keamanan

### Authentication & Authorization
- Laravel Sanctum untuk API authentication
- Role-based access control (RBAC)
- 2FA wajib untuk admin pusat

### Audit Trail
- Log semua perubahan data
- Track user yang melakukan perubahan
- Export log untuk compliance

### Data Protection
- Password hashing (bcrypt)
- Session management
- IP whitelist untuk admin pusat
- Encryption untuk data sensitif

## Backup & Maintenance

### Backup Otomatis
- Backup harian jam 02:00 WIB
- Backup ke cloud storage
- Retention policy: 30 hari backup harian, 12 bulan backup bulanan

### Monitoring
- Log error dan exception
- Performance monitoring
- Database optimization

## Deployment

### Production Checklist
- [ ] SSL certificate aktif
- [ ] Environment production
- [ ] Database backup schedule
- [ ] Error monitoring setup
- [ ] Performance optimization
- [ ] Security audit

### Server Requirements
- Apache/Nginx
- PHP 8.1+
- MySQL 8.0+
- Redis (optional, untuk cache)
- SSL certificate

## Support & Maintenance

### Contact
- **Developer**: [Nama Developer]
- **Email**: [email@domain.com]
- **Phone**: [nomor telepon]

### Documentation
- API Documentation: `/api/docs`
- User Manual: `/docs/user-manual.pdf`
- Admin Guide: `/docs/admin-guide.pdf`

## License

Copyright © 2024 Yayasan Muhammadiyah. All rights reserved.

---

**Versi**: 1.0.0  
**Last Updated**: January 2024  
**Compatible**: Laravel 10.x, PHP 8.1+
