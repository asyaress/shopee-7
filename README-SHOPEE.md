# Shopee Open Platform (v2) – Integration (Sandbox)

## 1) Setup di Shopee Open Platform
- **Test Redirect URL Domain**: isi domain + protocol saja, contoh: `https://printingsamarinda.com` (tanpa path).
- **Redirect URL (callback)** yang dipakai aplikasi Laravel ini: `https://printingsamarinda.com/integrations/shopee/callback`

## 2) Env Laravel
Tambahkan ke `.env`:

```env
# Shopee Open Platform (v2)
SHOPEE_ENV=test
SHOPEE_PARTNER_ID=1206726
SHOPEE_PARTNER_KEY=YOUR_TEST_PARTNER_KEY
SHOPEE_REDIRECT_URL=https://printingsamarinda.com/integrations/shopee/callback

# Optional
SHOPEE_SHOP_ID=
SHOPEE_SYNC_DAYS=7
```

> Pastikan `APP_URL` juga sesuai domain kamu.

## 3) Database
Jalankan migration:

```bash
php artisan migrate
```

Ini akan membuat tabel `shopee_tokens` untuk menyimpan `access_token` & `refresh_token`.

## 4) Cara pakai (via web)
1. Buka: `/integrations/shopee`
2. Klik **Connect Shopee** → login/authorize di sandbox
3. Setelah redirect balik, token akan tersimpan.
4. Klik **Sync Now** untuk menarik pesanan beberapa hari terakhir.
5. Klik **Sync Products Now** untuk menarik produk (item + model/variasi) ke menu **Products**.
6. Klik **Sync ALL** untuk menarik produk + order sekaligus.

## 5) Cara pakai (via CLI)

Sync order:

```bash
php artisan shopee:sync-orders --days=7
```

Sync produk:

```bash
php artisan shopee:sync-products --page_size=100
```

Sync semuanya:

```bash
php artisan shopee:sync-all --days=7 --page_size=100
```

## Mapping data
- `order_sn` → `orders.order_number`
- `buyer_username` → `orders.customer_name`
- `item_list` → `order_items`

## Mapping produk
- Shopee `item_id` → `products.external_item_id`
- Shopee `model_id` (variasi) → `product_variants.external_model_id`

## Catatan
- Default sync menggunakan `create_time`.
- Status Shopee dimapping menjadi: `pending / in_progress / completed / cancelled`.
