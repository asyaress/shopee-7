<?php

/**
 * Asisten CEO — pertanyaan & jawaban preset (bahasa awam).
 */
return [

    'bot' => [
        'name' => 'Asisten CEO',
        'subtitle' => 'Panduan Shopee Profit Hub · online',
        'welcome' => 'Halo! 👋 Senang bisa bantu.\n\nSaya asisten panduan toko Anda — **bukan AI**, tapi sudah dilatih dengan SOP & rumus di sistem ini. Pilih pertanyaan di bawah, atau ketik bebas (misal: ROAS, HPP, laba).',
        'welcome_back' => 'Halo lagi! Ada yang ingin ditanyakan? Saya siap bantu 😊',
        'loading' => 'Menyiapkan asisten…',
        'loading_data' => 'Membaca ringkasan toko…',
        'fallback' => 'Maaf, saya belum punya jawaban spesifik untuk itu. Coba pilih salah satu pertanyaan populer di bawah, atau buka **Pusat Aksi** untuk rekomendasi otomatis dari data toko.',
        'placeholder' => 'Ketik pertanyaan… misal: ROAS, HPP, laba',
    ],

    'personality' => [
        'acknowledgments' => [
            'Baik, ini penjelasannya ya 👇',
            'Pertanyaan bagus! Saya rangkum di bawah.',
            'Siap — ini yang perlu CEO tahu:',
            'Oke, saya jelaskan pelan-pelan ya:',
            'Mantap, ini jawaban lengkapnya:',
        ],
        'thinking' => [
            'Sebentar ya, saya cek panduan…',
            'Sedang menyiapkan jawaban…',
            'Tunggu sebentar…',
            'Oke, saya susun penjelasannya…',
        ],
        'empathy' => [
            'Hmm, belum ketemu jawaban pas untuk itu 🤔',
            'Wah, pertanyaannya unik — belum ada di panduan saya.',
            'Maaf ya, topik itu belum saya cover.',
        ],
        'fallbacks' => [
            'Belum ada jawaban khusus untuk itu, tapi saya punya beberapa topik populer di bawah — silakan pilih yang paling dekat.\n\nKalau butuh rekomendasi dari **data toko live**, buka **Pusat Aksi** — angkanya di-update dari order & iklan Anda.',
            'Topik itu belum masuk panduan saya. Coba tap salah satu pertanyaan di bawah — biasanya CEO mulai dari laba, HPP, atau ROAS.\n\nTips: ketik kata kunci pendek seperti **rugi**, **sync**, atau **scale**.',
            'Saya belum punya materi khusus untuk pertanyaan itu. Pilih chip di bawah, atau ketik kata kunci seperti **HPP**, **ROAS**, **BCG** — saya akan cocokkan otomatis.',
        ],
        'thanks' => [
            'Semoga membantu! Kalau masih bingung, tap pertanyaan lain di bawah ya.',
            'Gampang kan? Kalau perlu, tanya lagi — saya di sini 😊',
        ],
    ],

    'quick_starters' => [
        'Apa yang harus CEO lakukan hari ini?',
        'Apa itu laba bersih?',
        'Bagaimana cara baca Set ROAS?',
        'Kenapa HPP wajib diisi?',
        'Toko rugi — langkah apa dulu?',
    ],

    'faqs' => [

        [
            'id' => 'ceo-harian',
            'keywords' => ['hari ini', 'daily', 'rutin', 'mulai', 'ceo lakukan', 'checklist'],
            'routes' => ['monitoring.index', 'monitoring.actions'],
            'question' => 'Apa yang harus CEO lakukan hari ini?',
            'answer' => <<<'TXT'
Alur harian yang disarankan:

1. **Buka Ringkasan** — lihat laba bersih & alert merah/kuning.
2. **Pusat Aksi** — kerjakan item urgent dari atas (rugi, HPP kosong, iklan boros).
3. **Sync data** (Kelola Data) — minimal 1× sehari agar angka akurat.
4. **Analisa Iklan** — cek Set ROAS & produk Stop/Kurangi/Scale.
5. **Target Bulan** — pastikan on-track; kalau off-track, review harga atau iklan.

**Aturan emas:** Laba bersih merah → jangan scale iklan dulu. Perbaiki HPP, harga, atau potong iklan produk bleeder.
TXT,
            'link' => ['label' => 'Buka Pusat Aksi', 'route' => 'monitoring.actions'],
        ],

        [
            'id' => 'laba-bersih',
            'keywords' => ['laba bersih', 'untung bersih', 'net profit', 'sisa uang', 'laba'],
            'question' => 'Apa itu laba bersih?',
            'answer' => <<<'TXT'
**Laba bersih** = uang yang benar-benar tersisa setelah semua potongan.

Alurnya:
Penjualan kotor → potong HPP (biaya barang) → potong fee Shopee → potong iklan → potong biaya operasional → **sisanya laba bersih**.

**Rumus:** Kotor − COGS − Fee − Iklan − Operasional = Laba bersih

Ini angka paling penting di dashboard — bukan penjualan kotor semata.
TXT,
            'link' => ['label' => 'Lihat Laba Detail', 'route' => 'monitoring.profit'],
        ],

        [
            'id' => 'hpp-cogs',
            'keywords' => ['hpp', 'cogs', 'biaya barang', 'modal', 'harga pokok'],
            'question' => 'Apa itu HPP / COGS?',
            'answer' => <<<'TXT'
**HPP (Harga Pokok Penjualan)** = biaya modal per unit: bahan baku + kemasan.

**COGS** = total HPP × qty terjual di periode.

Kenapa wajib diisi?
Tanpa HPP, sistem tidak tahu produk **untung atau rugi**. ROAS, rekomendasi iklan, dan laba per SKU bisa **salah total**.

Target: minimal **70%** produk sudah punya HPP sebelum percaya saran iklan.
TXT,
            'link' => ['label' => 'Input HPP', 'route' => 'hpp.index'],
        ],

        [
            'id' => 'roas-set',
            'keywords' => ['set roas', 'roas shopee', 'input roas', 'angka roas', 'dashboard iklan'],
            'question' => 'Bagaimana cara baca & pakai Set ROAS?',
            'answer' => <<<'TXT'
**Set ROAS** = angka yang CEO input di dashboard Shopee Ads agar iklan tidak boros.

Cara pakai:
1. Buka **Analisa Iklan** — lihat angka besar di atas (Set ROAS).
2. Copy angka itu → paste di Shopee Ads → set sebagai target ROAS kampanye.
3. Scroll kartu produk: **Stop** = matikan, **Kurangi** = naikkan Set ROAS / turunkan budget, **Scale** = tambah pelan (Star saja).

**Rumus (Excel ROAS HLP):**
Target ROAS = 1 ÷ Target ACOS
Set ROAS Shopee = Target ROAS ÷ 70% (buffer platform)

**ROAS Shopee (di dashboard)** = GMV iklan ÷ Spend — beda dengan ROAS bisnis (omzet order nyata ÷ iklan).
TXT,
            'link' => ['label' => 'Buka Analisa Iklan', 'route' => 'ceo.roas'],
        ],

        [
            'id' => 'roas-bisnis',
            'keywords' => ['roas bisnis', 'business roas', 'roas aman', 'target roas'],
            'question' => 'ROAS Shopee vs ROAS bisnis — bedanya?',
            'answer' => <<<'TXT'
**ROAS Shopee (GMV):** Omzet yang Shopee atribusikan ke iklan ÷ spend. Angka di dashboard Ads — bisa terlihat bagus karena termasuk atribusi, belum tentu untung riil.

**ROAS bisnis:** Penjualan kotor order (termasuk organik) ÷ spend iklan. Ini yang menentukan **untung/rugi iklan** di laporan kita.

CEO harus pantau **ROAS bisnis**. Kalau di bawah target aman → potong iklan atau naikkan Set ROAS.

**Breakeven ROAS** ≈ 1 ÷ (Laba kotor ÷ Kotor) — di bawah ini iklan pasti boros.
TXT,
            'link' => ['label' => 'Halaman Iklan', 'route' => 'monitoring.ads'],
        ],

        [
            'id' => 'toko-rugi',
            'keywords' => ['rugi', 'minus', 'merah', 'boros', 'defisit'],
            'routes' => ['monitoring.index', 'monitoring.actions'],
            'question' => 'Toko rugi — langkah apa dulu?',
            'answer' => <<<'TXT'
Prioritas saat laba bersih minus:

1. **Cek HPP** — kalau kosong, isi dulu (angka bisa misleading).
2. **Pusat Aksi** — lihat produk bleeder & urgent.
3. **Potong iklan** — produk merah di Analisa Iklan (Stop/Kurangi).
4. **Naikkan harga** — simulasi di Kalkulator Harga.
5. **Review promo** — diskon kebesaran? Cek halaman Promo & Diskon.
6. **Fee Shopee** — take rate naik tiba-tiba? Biasanya karena program promo.

Jangan scale iklan sebelum margin produk sehat.
TXT,
            'link' => ['label' => 'Pusat Aksi', 'route' => 'monitoring.actions'],
        ],

        [
            'id' => 'acos',
            'keywords' => ['acos', 'persentase iklan', 'efisiensi iklan'],
            'question' => 'Apa itu ACOS?',
            'answer' => <<<'TXT'
**ACOS** = persentase biaya iklan dari omzet.

**Rumus:** Spend iklan ÷ Penjualan kotor

Semakin **kecil** semakin hemat. Kalau ACOS 20% artinya Rp20 dari setiap Rp100 omzet habis untuk iklan.

Hubungan dengan ROAS: ROAS = 1 ÷ ACOS (kalau pakai definisi yang sama). ACOS 10% ≈ ROAS 10x.
TXT,
        ],

        [
            'id' => 'take-rate',
            'keywords' => ['take rate', 'fee shopee', 'potongan', 'komisi', 'admin'],
            'question' => 'Apa itu take rate / potongan Shopee?',
            'answer' => <<<'TXT'
**Take rate** = total potongan Shopee ÷ penjualan kotor.

Termasuk: fee admin, layanan, proses, dan program hemat (jika ikut promo).

Take rate **naik** biasanya karena:
- Ikut diskon/promo Shopee
- Kategori fee berubah
- Banyak order kecil (fee minimum)

Cek halaman **Potongan Shopee** untuk breakdown & tren bulanan.
TXT,
            'link' => ['label' => 'Potongan Shopee', 'route' => 'monitoring.shopee'],
        ],

        [
            'id' => 'target-bulan',
            'keywords' => ['target', 'budget', 'on track', 'pace', 'bulanan'],
            'question' => 'Bagaimana set & pantau target bulan?',
            'answer' => <<<'TXT'
Di **Target Bulanan**, isi di awal bulan:
- Target laba bersih (Rp)
- Target penjualan kotor (Rp)
- Target unit terjual
- Maksimal budget iklan (Rp)

Sistem hitung **progress %** & **pace** (seharusnya sudah berapa di tanggal hari ini).

Off-track? Opsi: naikkan conversion, naikkan harga, kurangi iklan boros, atau realistis turunkan target.
TXT,
            'link' => ['label' => 'Target Bulanan', 'route' => 'ceo.targets'],
        ],

        [
            'id' => 'sync-data',
            'keywords' => ['sync', 'sinkron', 'data', 'update', 'refresh', 'kelola data'],
            'question' => 'Seberapa sering sync data Shopee?',
            'answer' => <<<'TXT'
**Minimal 1× sehari** — ideal pagi sebelum lihat laporan.

Di **Kelola Data**:
- **Sync All** — order + produk + iklan sekaligus
- Isi **biaya operasional** bulan berjalan

Data stale = laba & ROAS salah. Kalau AMS/iklan error, cek **Integrasi Shopee** dulu.
TXT,
            'link' => ['label' => 'Kelola Data', 'route' => 'manage.index'],
        ],

        [
            'id' => 'bleeder',
            'keywords' => ['bleeder', 'produk rugi', 'sku rugi', 'merah'],
            'question' => 'Apa itu produk bleeder?',
            'answer' => <<<'TXT'
**Bleeder** = produk yang jual tapi **untung tipis atau minus** setelah HPP, fee, dan iklan.

Tindakan CEO:
1. Buka **Laba per SKU** atau **Analisis Produk**
2. Naikkan harga (Kalkulator Harga) ATAU potong/stop iklan
3. Kalau margin HPP salah — perbaiki HPP dulu
4. Catat keputusan di **Log Keputusan**

Jangan biarkan bleeder scale iklan — makin besar spend, makin rugi.
TXT,
            'link' => ['label' => 'Laba per SKU', 'route' => 'monitoring.matrix'],
        ],

        [
            'id' => 'bcg-star',
            'keywords' => ['bcg', 'star', 'cash cow', 'dog', 'question mark', 'matriks'],
            'question' => 'BCG Star / Dog — strategi iklannya?',
            'answer' => <<<'TXT'
**Star** — laris + trafik bagus → **boleh tambah iklan** pelan, pantau ROAS.

**Cash Cow** — laris, konversi stabil → **pertahankan**, jangan over-spend.

**Question Mark** — trafik ada, konversi jelek → perbaiki listing/harga dulu, iklan hati-hati.

**Dog** — trafik & jual jelek → **kurangi/stop iklan**, pertimbangkan bundling atau delist.

Data trafik dari sync BCG atau import Excel Performa Produk Seller Centre.
TXT,
            'link' => ['label' => 'BCG & Trafik', 'route' => 'monitoring.bcg'],
        ],

        [
            'id' => 'kalkulator',
            'keywords' => ['kalkulator', 'simulasi harga', 'naik harga', 'margin'],
            'question' => 'Kapan pakai Kalkulator Harga?',
            'answer' => <<<'TXT'
Pakai sebelum:
- Naik/turun harga jual
- Scale iklan produk baru
- Ikut promo/diskon

Input: HPP, kemasan, harga jual, fee %, ops %, target untung %.

Output penting: **Set ROAS** & **laba per unit**.

Margin **Bahaya** (merah) = jangan scale iklan — perbaiki harga/HPP dulu.
TXT,
            'link' => ['label' => 'Kalkulator Harga', 'route' => 'ceo.kalkulator'],
        ],

        [
            'id' => 'promo-diskon',
            'keywords' => ['promo', 'diskon', 'flash sale', 'voucher', 'program hemat'],
            'question' => 'Ikut promo Shopee — aman atau rugi?',
            'answer' => <<<'TXT'
Diskon = margin turun. Banyak seller rugi karena diskon kebesaran.

Sebelum ikut:
1. Hitung **laba per unit setelah diskon** (Promo & Diskon / Kalkulator)
2. Cek **take rate** naik berapa % di halaman Potongan Shopee
3. Pastikan volume extra compensates fee + margin loss

Merah setelah diskon? **Jangan ikut** atau naikkan harga dasar.
TXT,
            'link' => ['label' => 'Promo & Diskon', 'route' => 'ceo.promo'],
        ],

        [
            'id' => 'arus-kas',
            'keywords' => ['arus kas', 'cash', 'settlement', 'cair', 'rekening', 'pending'],
            'question' => 'Laba di laporan vs uang di bank — kenapa beda?',
            'answer' => <<<'TXT'
**Laba laporan** = akuntansi accrual (order terjual di periode).

**Arus kas** = uang benar-benar masuk rekening (settlement Shopee).

Shopee hold dana beberapa hari — order hari ini belum tentu cair minggu ini. Normal.

Import **Data Income** dari Seller Centre di halaman Arus Kas untuk cocokkan dengan laporan.
TXT,
            'link' => ['label' => 'Arus Kas', 'route' => 'ceo.settlement'],
        ],

        [
            'id' => 'skor-kesehatan',
            'keywords' => ['skor', 'kesehatan', 'health score', 'score'],
            'question' => 'Apa arti skor kesehatan toko?',
            'answer' => <<<'TXT'
Skor **0–100** gabungan dari:
- Kelengkapan HPP (% produk terisi)
- Margin laba
- Efisiensi iklan (ROAS vs target)

Semakin tinggi = data lebih dipercaya & bisnis lebih sehat.

Skor rendah + HPP kosong → **jangan ambil keputusan iklan dulu**, lengkapi data.
TXT,
            'link' => ['label' => 'Ringkasan Toko', 'route' => 'monitoring.index'],
        ],

        [
            'id' => 'scale-iklan',
            'keywords' => ['scale', 'tambah iklan', 'naikkan budget', 'boroskan'],
            'question' => 'Kapan boleh scale (tambah) iklan?',
            'answer' => <<<'TXT'
Scale aman hanya jika **semua** ini benar:
- Produk kategori **Star** (BCG)
- Laba bersih produk ≥ 0
- ROAS bisnis ≥ target aman
- Label **Scale** / **Boleh scale** di Analisa Iklan
- Margin HPP sudah benar

Scale pelan (+10–20% budget/minggu), pantau ROAS bisnis tiap 3 hari.
TXT,
            'link' => ['label' => 'Analisa Iklan', 'route' => 'ceo.roas'],
        ],

        [
            'id' => 'urgent-aksi',
            'keywords' => ['urgent', 'prioritas', 'pusat aksi', 'to do', 'tindakan'],
            'question' => 'Apa arti urgent di Pusat Aksi?',
            'answer' => <<<'TXT'
**Urgent** = harus dikerjakan **hari ini** — contoh:
- Produk rugi dengan spend iklan tinggi
- HPP critical (<70%)
- Iklan jauh over budget
- Data sync gagal / AMS putus

**Peluang scale** = produk sehat, boleh tambah budget.

Kerjakan dari **atas ke bawah** — sistem sudah urutkan by severity.
TXT,
            'link' => ['label' => 'Pusat Aksi', 'route' => 'monitoring.actions'],
        ],

        [
            'id' => 'margin-kotor-net',
            'keywords' => ['margin kotor', 'margin bersih', 'gross margin', 'net margin'],
            'question' => 'Margin kotor vs margin bersih?',
            'answer' => <<<'TXT'
**Margin kotor** = (Laba kotor ÷ Penjualan kotor) — setelah HPP, sebelum fee & iklan.

**Margin bersih** = (Laba bersih ÷ Penjualan kotor) — setelah semua potongan.

Contoh: Jual Rp100 juta, laba bersih Rp15 juta → margin bersih 15%.

Margin kotor tinggi tapi bersih rendah? Fee atau iklan terlalu besar.
TXT,
            'link' => ['label' => 'Rekap Bulanan', 'route' => 'monitoring.rekap'],
        ],

        [
            'id' => 'analisis-produk',
            'keywords' => ['analisis produk', 'detail sku', 'varian', 'drill'],
            'question' => 'Kapan buka Analisis Produk?',
            'answer' => <<<'TXT'
Buka untuk **1 SKU** yang:
- Spend iklan tertinggi
- Flagged bleeder di Pusat Aksi
- Mau scale tapi ragu marginnya

Isi: laba, iklan, ROAS, BCG, breakdown **varian**, tren 6 bulan, simulasi harga.

3 cek CEO: Margin cukup? Iklan worth it? Varian mana yang jual?
TXT,
            'link' => ['label' => 'Analisis Produk', 'route' => 'monitoring.product-analysis.index'],
        ],

        [
            'id' => 'log-keputusan',
            'keywords' => ['log', 'catat', 'keputusan', 'journal', 'riwayat'],
            'question' => 'Kenapa catat keputusan di Log CEO?',
            'answer' => <<<'TXT'
Supaya 1–3 bulan lagi CEO ingat **kenapa** naik harga, potong iklan, atau stop SKU.

Format singkat: Apa yang dilakukan → Kenapa → Hasil yang diharapkan.

Review bulan depan: keputusan benar atau salah? Belajar untuk produk serupa.
TXT,
            'link' => ['label' => 'Log Keputusan', 'route' => 'ceo.decisions'],
        ],

    ],
];
