# Shopee Open Platform — Product Brief (7sinar.shop)

## Isi form di Shopee Developer Console

| Field | Nilai |
|-------|--------|
| **Live product URL** | `https://7sinar.shop/` |
| **Test username** | `administrator` |
| **Test password** | `123456` |
| **Test redirect domain** | `https://7sinar.shop` |
| **Live redirect domain** | `https://7sinar.shop` |
| **Redirect URI (callback)** | `https://7sinar.shop/integrations/shopee/callback` |
| **IP whitelist** | Lihat `.env` / hosting panel — paste satu IP per baris |

IP yang Anda cantumkan (contoh hosting):

```
55.60.252.0
198.240.80.0
```

Centang **Enable IP Address Whitelist** jika server outbound ke Shopee API memakai IP tersebut.

---

## Brief Introduction (copy-paste, ≤500 karakter)

```
Toedjoe Profit Hub (7sinar.shop) helps Shopee sellers monitor profit, ads ROAS, and SKU decisions in one dashboard. It uses Shopee Open API v2 to sync orders, products, escrow financials, and ads performance. Key modules: P&L per product, fee breakdown, BCG funnel (traffic/conversion import), CEO targets & cash flow, and actionable recommendations (cut ads, fix price, scale winners). Login with the test account, open Monitoring → Ringkasan, then Integrasi Shopee to connect a shop.
```

---

## Screenshot untuk reviewer (3 file)

1. **Login** — `https://7sinar.shop/login`
2. **Monitoring Ringkasan** — setelah login, dashboard KPI + chart
3. **Integrasi Shopee** — `https://7sinar.shop/integrations/shopee` (tombol Connect)

Opsional: halaman **CEO Brief** (`/monitoring/ceo`) atau **Rekap** (`/monitoring/rekap`).

---

## Setup server (sekali deploy)

1. Upload project Laravel ke hosting `7sinar.shop`
2. Copy env: `cp .env.7sinar.example .env` lalu edit DB + Shopee keys
3. `composer install --no-dev --optimize-autoloader`
4. `php artisan key:generate`
5. `php artisan migrate --force`
6. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
7. Document root = folder `public/`
8. Pastikan `APP_TEST_USERNAME` / `APP_TEST_PASSWORD` sama dengan form Shopee

---

## Redirect URL — harus exact match

Di Shopee app settings, **redirect_uri** domain harus `7sinar.shop`.

Di `.env` server:

```env
SHOPEE_REDIRECT_URL=https://7sinar.shop/integrations/shopee/callback
```

Route callback **tidak** memerlukan login (supaya Shopee bisa redirect dengan `?code=&shop_id=`).

---

## Alur uji untuk reviewer Shopee

1. Buka `https://7sinar.shop/login` → `administrator` / `123456`
2. Menu **Monitoring** → lihat laporan profit
3. **Kelola Data** atau **Integrasi Shopee** → Connect shop (OAuth)
4. Setelah connect → Sync orders/products/ads

---

## Sandbox vs Live

| Tahap | `SHOPEE_ENV` | Host API |
|-------|----------------|----------|
| Review / testing | `test` | partner.test-stable.shopeemobile.com |
| Production | `prod` | partner.shopeemobile.com |

Partner ID & Key **berbeda** antara sandbox dan live — ambil dari console masing-masing environment.
