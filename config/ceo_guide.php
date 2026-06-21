<?php

/**
 * Panduan CEO — teks awam, rumus, highlight per halaman.
 */
return [

    'shared_glossary' => [
        ['term' => 'Penjualan kotor', 'plain' => 'Total uang masuk dari order sebelum Shopee potong fee.', 'formula' => 'Σ harga jual × qty order'],
        ['term' => 'HPP / COGS', 'plain' => 'Biaya pokok barang + kemasan per unit yang terjual.', 'formula' => '(HPP + kemasan) × qty terjual'],
        ['term' => 'Laba kotor', 'plain' => 'Sisa uang setelah HPP, sebelum fee Shopee & iklan.', 'formula' => 'Penjualan kotor − COGS'],
        ['term' => 'Laba bersih', 'plain' => 'Uang benar-benar tersisa setelah semua potongan & biaya.', 'formula' => 'Kotor − fee − iklan − operasional'],
        ['term' => 'Fee Shopee', 'plain' => 'Potongan platform (admin, layanan, proses).', 'formula' => 'Admin + layanan + proses'],
        ['term' => 'ROAS', 'plain' => 'Berapa rupiah omzet didapat per Rp1 iklan. Semakin besar semakin bagus.', 'formula' => 'Omzet ÷ Biaya iklan'],
        ['term' => 'ACOS', 'plain' => 'Persentase iklan dari omzet. Semakin kecil semakin hemat.', 'formula' => 'Biaya iklan ÷ Omzet'],
        ['term' => 'Margin', 'plain' => 'Persentase laba dari omzet.', 'formula' => 'Laba ÷ Penjualan kotor'],
    ],

    'shared_formulas' => [
        ['label' => 'Laba kotor', 'formula' => 'Penjualan kotor − COGS'],
        ['label' => 'Laba bersih', 'formula' => 'Kotor − fee Shopee − iklan − operasional'],
        ['label' => 'Margin bersih', 'formula' => 'Laba bersih ÷ Penjualan kotor'],
        ['label' => 'ROAS', 'formula' => 'Omzet ÷ Spend iklan'],
        ['label' => 'ACOS', 'formula' => 'Spend iklan ÷ Omzet'],
    ],

    'pages' => [

        'monitoring.index' => [
            'id' => 'ringkasan',
            'icon' => 'fa-gauge-high',
            'title' => 'Ringkasan Toko',
            'subtitle' => 'Cek sehat tidaknya toko dalam 1 layar — laba, iklan, target.',
            'action' => [
                'severity' => 'info',
                'title' => 'Mulai dari sini',
                'headline' => 'Lihat laba bersih dulu. Merah? Buka Pusat Aksi. Hijau? Cek apakah target bulan tercapai.',
                'steps' => ['Pilih periode di filter atas.', 'Klik kartu biru/oranye untuk detail.', 'Urgent? Langsung ke Pusat Aksi.'],
                'cta' => ['label' => 'Pusat Aksi', 'route' => 'monitoring.actions'],
            ],
            'highlights' => [
                ['target' => '[data-ceo="hero"]', 'title' => 'Ini Ringkasan Toko', 'body' => 'Halaman utama CEO. Satu tempat lihat untung/rugi, iklan, dan target bulan ini.'],
                ['target' => '[data-ceo="alerts"]', 'title' => 'Alert kuning/merah', 'body' => 'Kalau ada peringatan, klik link-nya — sistem arahkan ke halaman perbaikan.'],
                ['target' => '[data-ceo="main-kpi"]', 'title' => 'Angka penting', 'body' => 'Laba bersih = uang benar-benar tersisa. Itu angka paling penting.'],
                ['title' => 'Kartu pintasan', 'body' => 'Klik kartu untuk masuk detail: laba, iklan, analisis produk.'],
            ],
            'formulas' => [
                ['label' => 'Skor kesehatan', 'formula' => 'Gabungan kelengkapan HPP + margin + efisiensi iklan'],
            ],
        ],

        'monitoring.actions' => [
            'id' => 'pusat-aksi',
            'icon' => 'fa-bolt',
            'title' => 'Pusat Aksi',
            'subtitle' => 'Daftar tugas yang harus CEO kerjakan hari ini — urut prioritas.',
            'action' => [
                'severity' => 'warning',
                'title' => 'Kerjakan dari atas',
                'headline' => 'Item urgent = harus hari ini. Scale = boleh tambah iklan. Bleeder = produk yang bikin rugi.',
                'steps' => ['Urutkan dari merah ke hijau.', 'Klik produk untuk detail.', 'HPP belum lengkap? Isi dulu sebelum ikuti saran iklan.'],
                'cta' => ['label' => 'Input HPP', 'route' => 'hpp.index'],
            ],
            'highlights' => [
                ['title' => 'Apa ini?', 'body' => 'To-do list otomatis dari data toko. CEO tidak perlu cari masalah sendiri.'],
                ['title' => 'Urgent', 'body' => 'Masalah serius — rugi, data hilang, atau iklan boros. Kerjakan duluan.'],
                ['title' => 'Bleeder', 'body' => 'Produk jual tapi untungnya tipis atau minus. Perlu naik harga atau potong iklan.'],
            ],
        ],

        'ceo.targets' => [
            'id' => 'target-bulanan',
            'icon' => 'fa-bullseye',
            'title' => 'Target Bulan Ini',
            'subtitle' => 'Tetapkan target laba & budget iklan — lalu pantau apakah on-track.',
            'action' => [
                'severity' => 'info',
                'title' => 'Isi target di awal bulan',
                'headline' => 'Tanpa target, sulit tahu toko jalan bagus atau tidak.',
                'steps' => ['Isi target laba bersih & penjualan kotor.', 'Set budget iklan maksimal.', 'Simpan — progress otomatis dihitung.'],
            ],
            'highlights' => [
                ['title' => 'Target laba', 'body' => 'Berapa rupiah untung bersih yang CEO inginkan bulan ini.'],
                ['title' => 'Pace / on-track', 'body' => 'Bandingkan progress hari ini vs seharusnya sudah berapa.'],
                ['title' => 'Budget iklan', 'body' => 'Batas maksimal spend iklan bulan ini — jangan lewati.'],
            ],
            'formulas' => [
                ['label' => 'Progress laba', 'formula' => 'Laba actual ÷ Target laba'],
                ['label' => 'Pace harian', 'formula' => 'Target ÷ jumlah hari bulan'],
            ],
        ],

        'monitoring.profit' => [
            'id' => 'laba-detail',
            'icon' => 'fa-chart-pie',
            'title' => 'Laba Detail',
            'subtitle' => 'Laporan untung/rugi lengkap — dari omzet sampai sisa uang di tangan.',
            'action' => [
                'severity' => 'info',
                'title' => 'Baca dari atas ke bawah',
                'headline' => 'Alur: Kotor → potong HPP → potong fee → potong iklan → sisa = laba bersih.',
                'steps' => ['Cek laba bersih di KPI atas.', 'Scroll tabel produk — cari yang merah.', 'Export Excel jika perlu laporan ke tim.'],
            ],
            'highlights' => [
                ['title' => 'P&L lengkap', 'body' => 'Profit & Loss — semua pemasukan dan pengeluaran toko per periode.'],
                ['title' => 'Skor kesehatan', 'body' => 'Semakin tinggi semakin bagus — data lengkap & margin sehat.'],
                ['title' => 'Per produk', 'body' => 'Lihat SKU mana yang untung dan mana yang rugi.'],
            ],
        ],

        'monitoring.rekap' => [
            'id' => 'rekap-bulanan',
            'icon' => 'fa-table',
            'title' => 'Rekap Bulanan',
            'subtitle' => 'Bandingkan performa toko bulan ke bulan — seperti Excel rekap.',
            'action' => [
                'severity' => 'info',
                'title' => 'Scroll ke kanan',
                'headline' => 'Setiap kolom = 1 bulan. Bandingkan ROAS, margin, dan laba antar bulan.',
                'steps' => ['Cari bulan terbaik — apa yang beda?', 'Bulan jelek? Cek iklan & promo saat itu.'],
            ],
            'highlights' => [
                ['title' => 'Grid 12 bulan', 'body' => 'Semua angka penting dalam satu tabel — mudah bandingkan tren.'],
                ['title' => 'Best seller', 'body' => 'Produk terlaris per periode — fokuskan stok & iklan di sini.'],
            ],
        ],

        'monitoring.revenue' => [
            'id' => 'pendapatan',
            'icon' => 'fa-coins',
            'title' => 'Pendapatan',
            'subtitle' => 'Uang masuk dari penjualan — kotor vs bersih setelah fee.',
            'action' => [
                'severity' => 'info',
                'title' => 'Kotor ≠ bersih',
                'headline' => 'Penjualan kotor belum termasuk potongan Shopee. Net = yang benar-benar masuk.',
                'steps' => ['Lihat grafik tren — naik atau turun?', 'Net jauh di bawah kotor? Fee terlalu besar — cek promo.'],
            ],
            'highlights' => [
                ['title' => 'Penjualan kotor', 'body' => 'Total harga jual sebelum Shopee potong apapun.'],
                ['title' => 'Pendapatan net', 'body' => 'Setelah fee platform — lebih dekat ke uang di rekening.'],
            ],
            'formulas' => [
                ['label' => 'Net pendapatan', 'formula' => 'Kotor − fee Shopee'],
            ],
        ],

        'monitoring.shopee' => [
            'id' => 'potongan-shopee',
            'icon' => 'fa-percent',
            'title' => 'Potongan Shopee',
            'subtitle' => 'Berapa persen omzet diambil Shopee — admin, layanan, proses.',
            'action' => [
                'severity' => 'info',
                'title' => 'Pantau take rate',
                'headline' => 'Take rate tinggi = Shopee ambil banyak. Biasanya karena promo/diskon.',
                'steps' => ['Lihat komposisi fee — admin vs layanan.', 'Take rate naik tiba-tiba? Cek promo aktif.'],
            ],
            'highlights' => [
                ['title' => 'Fee admin', 'body' => 'Komisi dasar Shopee per transaksi.'],
                ['title' => 'Take rate', 'body' => 'Total fee ÷ omzet — persentase yang Shopee ambil.'],
            ],
            'formulas' => [
                ['label' => 'Take rate', 'formula' => 'Total fee ÷ Penjualan kotor'],
            ],
        ],

        'ceo.settlement' => [
            'id' => 'arus-kas',
            'icon' => 'fa-wallet',
            'title' => 'Arus Kas',
            'subtitle' => 'Uang masuk & keluar dari rekening — beda dengan laba di laporan.',
            'action' => [
                'severity' => 'info',
                'title' => 'Laba ≠ cash',
                'headline' => 'Laba di laporan bisa beda dengan uang di bank — Shopee cairkan belum tentu hari itu.',
                'steps' => ['Import settlement dari Shopee Seller Centre.', 'Bandingkan dengan laba laporan.'],
            ],
            'highlights' => [
                ['title' => 'Settlement', 'body' => 'Uang yang Shopee transfer ke rekening toko.'],
                ['title' => 'Timing', 'body' => 'Order hari ini belum tentu cair minggu ini — normal di Shopee.'],
            ],
        ],

        'monitoring.ads' => [
            'id' => 'iklan',
            'icon' => 'fa-bullhorn',
            'title' => 'Iklan Shopee',
            'subtitle' => 'Berapa uang keluar untuk iklan & apakah worth it.',
            'action' => [
                'severity' => 'warning',
                'title' => 'Cek ROAS bisnis',
                'headline' => 'ROAS bisnis = omzet order nyata ÷ iklan. Ini yang menentukan untung/rugi iklan.',
                'steps' => ['Lihat rekomendasi di banner.', 'ROAS jelek? Buka Analisa Iklan.', 'Budget habis? Naikkan di Target Bulanan.'],
                'cta' => ['label' => 'Analisa Iklan', 'route' => 'ceo.roas'],
            ],
            'highlights' => [
                ['title' => 'Spend iklan', 'body' => 'Total biaya iklan periode ini dari Shopee Ads.'],
                ['title' => 'ROAS bisnis', 'body' => 'Omzet order (termasuk organik) dibagi iklan — lebih akurat dari angka Shopee saja.'],
                ['title' => 'Budget', 'body' => 'Batas iklan bulan ini — % terpakai harus dijaga.'],
            ],
            'formulas' => [
                ['label' => 'ROAS Shopee (GMV)', 'formula' => 'GMV iklan ÷ Spend'],
                ['label' => 'ROAS bisnis', 'formula' => 'Penjualan kotor ÷ Spend iklan'],
            ],
        ],

        'ceo.roas' => [
            'id' => 'analisa-iklan',
            'icon' => 'fa-chart-line',
            'title' => 'Analisa Iklan',
            'subtitle' => 'Angka Set ROAS + aksi per produk — input langsung di Shopee Ads.',
            'action' => null,
            'highlights' => [
                ['target' => '[data-ceo="main-kpi"]', 'title' => 'Set ROAS', 'body' => 'Angka besar = yang CEO input di dashboard Shopee Ads. Ini yang paling penting.'],
                ['target' => '[data-ceo="action"]', 'title' => 'Banner aksi', 'body' => 'Hijau = lanjut. Kuning = hati-hati. Merah = potong iklan sekarang.'],
                ['target' => '[data-ceo="products"]', 'title' => 'Kartu produk', 'body' => 'Stop / Kurangi / Scale — ikuti label, jangan tebak.'],
            ],
            'glossary' => [
                ['term' => 'Set ROAS', 'plain' => 'Angka target yang CEO ketik di Shopee Ads agar iklan tidak boros.', 'formula' => 'Target ROAS ÷ 70%'],
                ['term' => 'ROAS Shopee', 'plain' => 'Angka di dashboard Shopee — omzet atribusi iklan ÷ spend.', 'formula' => 'GMV iklan ÷ Spend'],
            ],
            'formulas' => [
                ['label' => 'Target ROAS', 'formula' => '1 ÷ Target ACOS'],
                ['label' => 'Set ROAS Shopee', 'formula' => 'Target ROAS ÷ 70%'],
                ['label' => 'ROAS impas', 'formula' => '1 ÷ (Laba kotor ÷ Kotor)'],
            ],
        ],

        'monitoring.bcg' => [
            'id' => 'bcg-trafik',
            'icon' => 'fa-chart-scatter',
            'title' => 'BCG & Trafik',
            'subtitle' => 'Produk dibagi Star / Cash Cow / Question / Dog — plus data kunjungan.',
            'action' => [
                'severity' => 'info',
                'title' => 'Fokus di Star',
                'headline' => 'Star = laris & untung → boleh tambah iklan. Dog = jelek → kurangi atau stop.',
                'steps' => ['Import/sync data trafik dari Shopee.', 'Set target unit per produk.', 'Star + margin bagus = prioritas iklan.'],
            ],
            'highlights' => [
                ['title' => 'Matriks BCG', 'body' => 'Star (bintang), Cash Cow (sapi), Question (?), Dog (anjing) — strategi iklan beda tiap kategori.'],
                ['title' => 'Trafik', 'body' => 'Berapa orang lihat produk — tanpa trafik, iklan sia-sia.'],
            ],
        ],

        'monitoring.matrix' => [
            'id' => 'laba-per-sku',
            'icon' => 'fa-th',
            'title' => 'Laba per SKU',
            'subtitle' => 'Tabel semua produk — untung/rugi per item, urut dari yang paling boros.',
            'action' => [
                'severity' => 'info',
                'title' => 'Cari baris merah',
                'headline' => 'Produk rugi = naikkan harga, turunkan iklan, atau stop jual.',
                'steps' => ['Sort by laba bersih.', 'Klik produk untuk analisis detail.', 'HPP kosong? Isi dulu — angka bisa salah.'],
                'cta' => ['label' => 'Analisis Produk', 'route' => 'monitoring.product-analysis.index'],
            ],
            'highlights' => [
                ['title' => 'Matrix SKU', 'body' => 'Semua produk dalam satu tabel — laba, margin, iklan per item.'],
            ],
        ],

        'monitoring.product-analysis.index' => [
            'id' => 'analisis-produk-pilih',
            'icon' => 'fa-microscope',
            'title' => 'Analisis Produk',
            'subtitle' => 'Pilih 1 produk — lihat untung/rugi, iklan, varian, dan rekomendasi.',
            'action' => [
                'severity' => 'info',
                'title' => 'Pilih produk dulu',
                'headline' => 'Mulai dari produk dengan spend iklan tertinggi atau yang flagged bleeder.',
                'steps' => ['Ketik nama produk di pencarian.', 'Klik untuk buka detail lengkap.'],
            ],
            'highlights' => [
                ['title' => 'Drill-down', 'body' => 'Satu produk, semua angka — keuangan, iklan, BCG, varian.'],
            ],
        ],

        'monitoring.product-analysis.show' => [
            'id' => 'analisis-produk-detail',
            'icon' => 'fa-microscope',
            'title' => 'Detail Produk',
            'subtitle' => 'Semua angka 1 produk — keuangan, iklan, varian, tren.',
            'action' => [
                'severity' => 'info',
                'title' => '3 hal yang dicek',
                'headline' => 'Margin cukup? Iklan worth it? Varian mana yang jual?',
                'steps' => ['Laba bersih merah → Kalkulator Harga.', 'Iklan boros → Analisa Iklan.', 'Varian jelek → fokus stok di varian terbaik.'],
                'cta' => ['label' => 'Kalkulator', 'route' => 'ceo.kalkulator'],
            ],
            'highlights' => [
                ['title' => 'Ringkasan produk', 'body' => 'Laba, margin, iklan — khusus produk ini saja.'],
                ['title' => 'Rekomendasi', 'body' => 'Saran otomatis: naik harga, potong iklan, atau scale.'],
            ],
        ],

        'ceo.kalkulator' => [
            'id' => 'kalkulator-harga',
            'icon' => 'fa-calculator',
            'title' => 'Kalkulator Harga',
            'subtitle' => 'Simulasi: kalau harga/HPP begini, Set ROAS berapa & untung per unit berapa.',
            'action' => null,
            'highlights' => [
                ['target' => '[data-ceo="main-kpi"]', 'title' => 'Set ROAS hasil simulasi', 'body' => 'Angka besar = input di Shopee Ads setelah yakin harga & HPP benar.'],
                ['target' => '[data-ceo="action"]', 'title' => 'Margin Bahaya?', 'body' => 'Merah = jangan scale iklan dulu — perbaiki harga atau HPP.'],
            ],
            'formulas' => [
                ['label' => 'Margin profit', 'formula' => '(Harga jual − HPP − kemasan) ÷ Harga jual'],
                ['label' => 'Target ACOS', 'formula' => 'Margin − Fee% − Ops% − Target laba%'],
                ['label' => 'Set ROAS', 'formula' => 'Target ROAS ÷ buffer 70%'],
            ],
        ],

        'ceo.promo' => [
            'id' => 'promo-diskon',
            'icon' => 'fa-tags',
            'title' => 'Promo & Diskon',
            'subtitle' => 'Simulasi diskon — lihat dampak ke margin sebelum ikut promo Shopee.',
            'action' => [
                'severity' => 'warning',
                'title' => 'Diskon = margin turun',
                'headline' => 'Sebelum ikut promo, hitung dulu — banyak seller rugi karena diskon kebesaran.',
                'steps' => ['Masukkan harga normal & % diskon.', 'Cek laba per unit setelah diskon.', 'Merah? Jangan ikut promo atau naikkan harga dasar.'],
            ],
            'highlights' => [
                ['title' => 'Simulasi promo', 'body' => 'Cek untung per unit jika harga diskon X%.'],
            ],
            'formulas' => [
                ['label' => 'Harga setelah diskon', 'formula' => 'Harga jual × (1 − diskon%)'],
                ['label' => 'Margin setelah diskon', 'formula' => '(Harga diskon − COGS) ÷ Harga diskon'],
            ],
        ],

        'ceo.decisions' => [
            'id' => 'log-keputusan',
            'icon' => 'fa-clipboard-list',
            'title' => 'Log Keputusan',
            'subtitle' => 'Catat keputusan CEO — naik harga, potong iklan, ikut promo — supaya bisa dievaluasi.',
            'action' => [
                'severity' => 'info',
                'title' => 'Catat setiap keputusan besar',
                'headline' => '3 bulan lagi, CEO lupa kenapa naik harga — log ini jawabannya.',
                'steps' => ['Setelah putus di Pusat Aksi, catat di sini.', 'Tulis alasan singkat & tanggal.', 'Review bulan depan — keputusan benar atau salah?'],
            ],
            'highlights' => [
                ['title' => 'Journal CEO', 'body' => 'Buku catatan keputusan bisnis — optional tapi sangat membantu.'],
            ],
        ],

        'manage.index' => [
            'id' => 'kelola-data',
            'icon' => 'fa-database',
            'title' => 'Kelola Data',
            'subtitle' => 'Sync order, produk, iklan dari Shopee + set biaya operasional bulanan.',
            'action' => [
                'severity' => 'info',
                'title' => 'Sync rutin',
                'headline' => 'Data stale = laporan salah. Sync minimal 1× sehari.',
                'steps' => ['Klik Sync All di awal hari.', 'Isi biaya operasional bulan ini.', 'AMS error? Cek Integrasi Shopee.'],
                'cta' => ['label' => 'Integrasi', 'route' => 'shopee.index'],
            ],
            'highlights' => [
                ['title' => 'Sync', 'body' => 'Tarik data terbaru dari Shopee API — order, produk, iklan.'],
                ['title' => 'Operasional', 'body' => 'Biaya gaji, sewa, dll per bulan — masuk hitungan laba bersih.'],
            ],
        ],

        'hpp.index' => [
            'id' => 'input-hpp',
            'icon' => 'fa-tags',
            'title' => 'Input HPP',
            'subtitle' => 'Isi biaya pokok tiap produk — tanpa ini, semua laporan laba bisa salah.',
            'action' => [
                'severity' => 'danger',
                'title' => 'Wajib lengkap 70%+',
                'headline' => 'HPP kosong = laba palsu. Isi dulu sebelum percaya angka iklan & ROAS.',
                'steps' => ['Filter produk HPP kosong.', 'Isi HPP + kemasan per varian.', 'Simpan — skor kelengkapan naik otomatis.'],
            ],
            'highlights' => [
                ['title' => 'HPP = biaya barang', 'body' => 'Berapa modal per unit — bahan baku + kemasan.'],
                ['title' => 'Kenapa penting?', 'body' => 'Tanpa HPP, sistem tidak tahu untung atau rugi per produk.'],
            ],
            'formulas' => [
                ['label' => 'COGS per unit', 'formula' => 'HPP + biaya kemasan'],
                ['label' => 'Laba per unit', 'formula' => 'Harga jual − COGS − fee − iklan'],
            ],
        ],
    ],
];
