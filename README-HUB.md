# Shopee Profit Hub

UI baru dengan tema maroon/putih, responsif (mobile, tablet, desktop).

## Halaman utama

| URL | Fungsi |
|-----|--------|
| `/` | **Monitoring** — KPI toko, tren bulanan, laporan per produk |
| `/manage` | **Kelola Data** — sync Shopee, HPP, packaging, operasional |

## Setup database

```bash
composer install
php artisan migrate --force
```

Tabel baru:
- `shopee_product_ads_daily` — performa iklan per produk/hari
- `shop_monthly_costs` — biaya operasional manual per bulan

## Sync Ads (API)

Setelah permission **Marketing/Ads** disetujui Shopee:

```bash
php artisan shopee:sync-ads --days=30
```

Atau dari UI **Kelola Data → Sync Iklan / Sync Semua**.

Env opsional:

```env
SHOPEE_ADS_SYNC_DAYS=30
```

## Rumus laba per produk

```
Laba bersih = Net (alokasi) − HPP − Packaging − Ads − Operasional (alokasi)
ROAS = Gross / Ads spend
```

## Cron

Scheduler sudah mencakup `shopee:sync-ads` harian jam 02:30 (jika `schedule:run` aktif).
