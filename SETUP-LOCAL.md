# Setup Laravel Lokal (Windows)

Panduan singkat jika project belum pernah di-setup.

## Prasyarat

- **PHP 8.1+** (Anda: PHP 8.2 ✓)
- **Composer** ([getcomposer.org](https://getcomposer.org))
- **MySQL** (XAMPP / Laragon / MySQL standalone)

## Setup cepat (PowerShell)

```powershell
cd "D:\A. SHOPEE-7"
.\setup.ps1
```

Atau manual:

```powershell
composer install
copy .env.example .env
# Edit .env → sesuaikan DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate
php artisan storage:link
```

## Database

### Opsi A — Import dump lengkap (disarankan jika ada data lama)

1. Buat database di MySQL, misalnya `toedjoe_order`
2. Import file `toedjoe-order-system.sql`
3. Di `.env` set:
   ```env
   DB_DATABASE=toedjoe_order
   ```
4. Jalankan migrasi tabel baru saja:
   ```powershell
   php artisan migrate --path=database/migrations/2026_05_29_000001_create_shopee_product_ads_daily_table.php --force
   php artisan migrate --path=database/migrations/2026_05_29_000002_create_shop_monthly_costs_table.php --force
   php artisan migrate --path=database/migrations/add_costs_to_product_variants_table.php --force
   ```

### Opsi B — Database kosong

```powershell
# Buat database dulu di phpMyAdmin / MySQL CLI
php artisan migrate --force
```

## Jalankan aplikasi

```powershell
php artisan serve
```

Buka: **http://127.0.0.1:8000**

Login default (dari `.env`):

| Field | Nilai |
|-------|--------|
| Username | `admin` |
| Password | `admin` |

## Shopee (setelah login)

Edit `.env`:

```env
SHOPEE_ENV=test
SHOPEE_PARTNER_ID=...
SHOPEE_PARTNER_KEY=...
SHOPEE_REDIRECT_URL=http://127.0.0.1:8000/integrations/shopee/callback
APP_URL=http://127.0.0.1:8000
```

Lalu: **Kelola Data** → Connect Shopee → Sync Semua.

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `vendor/autoload.php` tidak ada | `composer install` |
| `No application encryption key` | `php artisan key:generate` |
| `Access denied for user` | Periksa `DB_*` di `.env` |
| `Table already exists` saat migrate | Pakai Opsi A (import SQL) + migrate path tabel baru saja |
| Port 8000 dipakai | `php artisan serve --port=8001` |
