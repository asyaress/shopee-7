# Deploy ke Hostinger (akses SSH)

Panduan singkat untuk deploy Laravel di Hostinger.

## Struktur folder yang direkomendasikan
1. Upload project ke folder di luar `public_html`, misalnya: `~/laravel-app`
2. Arahkan web root domain ke folder: `~/laravel-app/public`
   - Di Hostinger: hPanel → Domain → *Document Root*

## Langkah deploy
Masuk SSH ke hostinger, lalu:

```bash
cd ~/laravel-app

# install dependency
composer install --no-dev --optimize-autoloader

# set environment
cp .env.example .env
php artisan key:generate

# setup storage link
php artisan storage:link

# migrate database
php artisan migrate --force

# cache config & route (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Cron untuk auto-sync Shopee
Paling mudah: jalankan scheduler Laravel tiap menit:

```bash
* * * * * php /home/USERNAME/laravel-app/artisan schedule:run >> /dev/null 2>&1
```

Lalu aktifkan schedule sesuai kebutuhan (kamu bisa panggil command manual juga):

```bash
php artisan shopee:sync-all --days=7 --page_size=100
```

## Catatan permission
Pastikan folder ini writable:
- `storage/`
- `bootstrap/cache/`

Jika perlu:

```bash
chmod -R ug+rwX storage bootstrap/cache
```
