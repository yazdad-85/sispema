# Panduan Deployment SISPEMA YASMU ke VPS

## File yang Perlu Diupload

1. **Database Export:** `presispema_export_20250912_081112.sql`
2. **File .env:** `.env.production` (rename menjadi `.env` di VPS)
3. **Seluruh folder aplikasi** (kecuali `vendor/` dan `node_modules/`)

## Langkah-langkah Deployment

### 1. Upload File ke VPS
```bash
# Upload seluruh folder aplikasi ke VPS
scp -r /path/to/sispema/ user@your-vps-ip:/var/www/html/

# Atau gunakan rsync
rsync -avz --exclude 'vendor/' --exclude 'node_modules/' /path/to/sispema/ user@your-vps-ip:/var/www/html/sispema/
```

### 2. Setup Database di VPS
```bash
# Login ke VPS
ssh user@your-vps-ip

# Masuk ke MySQL
mysql -u root -p

# Buat database
CREATE DATABASE presispema;
exit

# Import database
mysql -u root -p presispema < presispema_export_20250912_081112.sql
```

### 3. Konfigurasi Aplikasi
```bash
# Masuk ke folder aplikasi
cd /var/www/html/sispema

# Rename file .env
mv .env.production .env

# Edit file .env sesuai VPS
nano .env

# Update konfigurasi database di .env:
DB_HOST=localhost
DB_DATABASE=presispema
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
APP_URL=http://your-domain.com
```

### 4. Install Dependencies
```bash
# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data /var/www/html/sispema
sudo chmod -R 755 /var/www/html/sispema
sudo chmod -R 775 /var/www/html/sispema/storage
sudo chmod -R 775 /var/www/html/sispema/bootstrap/cache
```

### 5. Generate Application Key
```bash
php artisan key:generate
```

### 6. Clear Cache
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Setup Web Server (Nginx/Apache)

#### Untuk Nginx:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/sispema/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Akun Login yang Tersedia

- **Super Admin:** `superadmin@yasmu.ac.id` / `password`
- **Staff 1:** `staff1@yasmu.ac.id` / `password`
- **Staff 2:** `staff2@yasmu.ac.id` / `password`

## Catatan Penting

1. Pastikan PHP 8.1+ sudah terinstall di VPS
2. Pastikan MySQL/MariaDB sudah terinstall
3. Pastikan Composer sudah terinstall
4. Setelah deployment, ubah password default untuk keamanan
5. Pastikan file `.env` tidak di-commit ke repository

## Troubleshooting

Jika ada masalah:
1. Check log: `tail -f storage/logs/laravel.log`
2. Check permissions: `ls -la storage/`
3. Check database connection: `php artisan tinker`
4. Clear cache: `php artisan cache:clear`
