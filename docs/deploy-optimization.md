# Optimasi Deploy MAWA Center

Jalankan ini setiap selesai upload kode ke server:

```bash
composer install --no-dev --optimize-autoloader
npm install --ignore-scripts
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan view:cache
```

Atau pakai script composer yang sudah disiapkan:

```bash
composer run deploy:optimize
```

Untuk production, pastikan `.env` memakai nilai berikut:

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
CACHE_STORE=database
QUERY_CACHE_ENABLED=true
DASHBOARD_CACHE_TTL=120
BILL_STATS_CACHE_TTL=120
APP_SETTINGS_CACHE_TTL=3600
```

Jika server menyediakan Redis, ganti `CACHE_STORE=redis` agar cache lebih cepat daripada database.
