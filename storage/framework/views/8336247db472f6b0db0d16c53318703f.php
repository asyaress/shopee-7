<?php $__env->startSection('title', 'Detail Produk - Toedjoe Order System'); ?>

<?php $__env->startSection('content'); ?>
<style>
/* =========================
   GLOBAL RESPONSIVE TWEAKS
========================= */
.table-responsive{
    -webkit-overflow-scrolling: touch;
}
.table td, .table th{
    vertical-align: middle;
}

/* ✅ Bigger touch targets on mobile/tablet */
.money-input{
    font-size:1.05rem;
    padding:.65rem .75rem;
}
@media (max-width: 991.98px){
    .money-input{ font-size:1.15rem; padding:.8rem .9rem; }
}

/* ✅ Select inside input-group that looks like "chip" (Rp / %) */
.input-group-unit{
    max-width: 84px;
    flex: 0 0 84px;
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
}
.unit-input{
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
}
.input-group .btn{
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
}

/* =========================
   PREVIEW KALKULASI
========================= */
.cost-preview{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:12px;
}
.cost-preview .preview-title{
    font-weight:700;
    margin-bottom:10px;
    display:flex; align-items:center; flex-wrap:wrap;
}
.cost-preview .preview-grid{ display:grid; gap:8px; }
.cost-preview .preview-row{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
}
.cost-preview .label{ color:#6b7280; font-weight:600; }
.cost-preview b{ white-space:nowrap; }
.cost-preview .preview-note{ font-size:.85rem; color:#6b7280; }
@media (max-width:575.98px){
    .cost-preview{ padding:10px; }
    .cost-preview .label{ font-size:.9rem; }
}

/* =========================
   VARIANT COST UI
========================= */
.variant-cost{ min-width:300px; }
.variant-cost .vc-stack{ display:flex; flex-direction:column; gap:6px; }

/* =========================
   ✅ MOBILE/TABLET: TABLE -> CARD STACK
========================= */
@media (max-width: 991.98px){
    table.table-variants thead{ display:none; }
    table.table-variants,
    table.table-variants tbody,
    table.table-variants tr,
    table.table-variants td{
        display:block;
        width:100%;
    }
    table.table-variants tr.variant-row{
        background:#fff;
        border:1px solid #e5e7eb;
        border-radius:14px;
        padding:12px;
        margin-bottom:12px;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
    }
    table.table-variants td{
        padding:8px 0;
        border:none;
    }
    table.table-variants td::before{
        content: attr(data-label);
        display:block;
        font-size:.85rem;
        color:#6b7280;
        font-weight:700;
        margin-bottom:6px;
    }
}

/* Helper badge */
.badge-soft{
    background:#f3f4f6;
    border:1px solid #e5e7eb;
    color:#111827;
}
</style>

<div class="report-shell hub-form-page">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-box',
        'title' => $product->name,
        'subtitle' => ($product->category ?: 'Produk') . ' · ' . ($product->external_platform === 'shopee' ? 'Shopee' : 'Internal'),
        'meta' => [
            ['icon' => 'fa-tag', 'text' => $product->is_active ? 'Aktif' : 'Nonaktif'],
        ],
        'actions' => '<a href="' . route('products.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-arrow-left"></i></a>'
            . ($product->external_platform !== 'shopee' ? '<a href="' . route('products.edit', $product) . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-edit"></i> Edit</a>' : ''),
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <?php
        $hasVariants = $product->variants && $product->variants->count() > 0;

        // ✅ Default HPP (ONLY Rupiah)
        $hppVal = old('hpp_amount', $product->hpp_amount);
        $hppDisplay = $hppVal !== null && $hppVal !== '' ? number_format((float)$hppVal, 0, ',', '.') : '';

        // ✅ Default Packaging (Rp / %)
        $packType = old('packaging_type', $product->packaging_type ?? 'fixed');
        $packVal  = old('packaging_value', $product->packaging_value);

        $packFixedDisplay   = ($packType === 'fixed' && $packVal !== null && $packVal !== '') ? number_format((float)$packVal, 0, ',', '.') : '';
        $packPercentDisplay = ($packType === 'percent' && $packVal !== null && $packVal !== '') ? (float)$packVal : '';

        $basePrice = (float)($product->base_price ?? 0);
    ?>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="hub-card">
                <div class="card-header" style="background:#f8f9fa; border-bottom:1px solid #dee2e6;">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Produk</h6>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tr>
                                <th style="width:180px;">Harga</th>
                                <td>
                                    <?php if($product->base_price): ?>
                                        <span class="fw-bold" style="color: var(--primary-red);">
                                            Rp <?php echo e(number_format($product->base_price, 0, ',', '.')); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Harga Custom</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Unit</th>
                                <td><span class="badge bg-secondary"><?php echo e($product->unit); ?></span></td>
                            </tr>
                            <tr>
                                <th>Dibuat</th>
                                <td><?php echo e($product->created_at->format('d M Y H:i')); ?></td>
                            </tr>
                            <tr>
                                <th>Diupdate</th>
                                <td><?php echo e($product->updated_at->format('d M Y H:i')); ?></td>
                            </tr>
                        </table>
                    </div>

                    <?php if($product->description): ?>
                        <hr>
                        <h6 class="mb-2">Deskripsi</h6>
                        <p class="text-muted mb-0"><?php echo e($product->description); ?></p>
                    <?php endif; ?>

                    <hr>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h6 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>Biaya Internal (Editable)
                        </h6>
                        <?php if($product->external_platform === 'shopee'): ?>
                            <span class="badge badge-soft">
                                Shopee read-only, tapi biaya internal boleh diisi
                            </span>
                        <?php endif; ?>
                    </div>

                    <form class="mt-3 cost-form" method="POST" action="<?php echo e(route('products.update-costs', $product)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>

                        <div class="row g-3">
                            
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Default HPP (Rp / unit)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input
                                        type="text"
                                        class="form-control money-input rupiah-display"
                                        id="hpp_amount_display"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        enterkeyhint="done"
                                        autocomplete="off"
                                        placeholder="10.000"
                                        value="<?php echo e($hppDisplay); ?>"
                                    >
                                    <button type="button" class="btn btn-outline-secondary" id="hpp_clear_btn" title="Clear">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="hpp_amount" id="hpp_amount" value="<?php echo e($hppVal); ?>">
                                <small class="text-muted">Kosongkan jika tidak ingin pakai default.</small>
                                <small class="text-muted d-block mt-1" id="hpp_estimate_line">Estimasi HPP: -</small>
                            </div>

                            
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Default Packaging</label>

                                <div class="input-group">
                                    <select class="form-select input-group-unit" name="packaging_type" id="packaging_type">
                                        <option value="fixed" <?php echo e($packType === 'fixed' ? 'selected' : ''); ?>>Rp</option>
                                        <option value="percent" <?php echo e($packType === 'percent' ? 'selected' : ''); ?>>%</option>
                                    </select>

                                    
                                    <input
                                        type="text"
                                        class="form-control money-input unit-input rupiah-display <?php echo e($packType === 'percent' ? 'd-none' : ''); ?>"
                                        id="packaging_fixed_display"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        enterkeyhint="done"
                                        autocomplete="off"
                                        placeholder="2.000"
                                        value="<?php echo e($packFixedDisplay); ?>"
                                    >

                                    
                                    <input
                                        type="number"
                                        class="form-control money-input unit-input <?php echo e($packType === 'fixed' ? 'd-none' : ''); ?>"
                                        id="packaging_percent_input"
                                        min="0" max="100" step="0.01"
                                        placeholder="1"
                                        value="<?php echo e($packPercentDisplay); ?>"
                                    >
                                </div>

                                <input type="hidden" name="packaging_value" id="packaging_value" value="<?php echo e($packVal); ?>">
                                <small class="text-muted d-block mt-1" id="packaging_help">Packaging per unit (Rp) atau % dari harga.</small>
                                <small class="text-muted d-block mt-1" id="packaging_estimate_line">Estimasi packaging: -</small>
                            </div>
                        </div>

                        
                        <div class="cost-preview mt-3">
                            <div class="preview-title">
                                <i class="fas fa-chart-line me-2"></i>Kalkulasi Otomatis (Default)
                                <span class="text-muted ms-2 small">(live)</span>
                            </div>

                            <div class="preview-grid">
                                <div class="preview-row">
                                    <span class="label">Harga Jual</span>
                                    <b id="pv_price">-</b>
                                </div>
                                <div class="preview-row">
                                    <span class="label">HPP</span>
                                    <b id="pv_hpp">-</b>
                                </div>
                                <div class="preview-row">
                                    <span class="label">Packaging</span>
                                    <b id="pv_pack">-</b>
                                </div>
                                <div class="preview-row">
                                    <span class="label">Total Biaya</span>
                                    <b id="pv_total_cost">-</b>
                                </div>

                                <hr class="my-2">

                                <div class="preview-row">
                                    <span class="label">Estimasi Laba</span>
                                    <b id="pv_profit">-</b>
                                </div>
                                <div class="preview-row">
                                    <span class="label">Margin</span>
                                    <b id="pv_margin">-</b>
                                </div>

                                <div class="preview-note" id="pv_note"></div>
                            </div>
                        </div>

                        
                        <?php if($hasVariants): ?>
                            <hr class="my-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <h6 class="mb-0">
                                    <i class="fas fa-layer-group me-2"></i>Biaya per Variant (Opsional)
                                </h6>
                                <small class="text-muted">Kosong = ikut produk</small>
                            </div>

                            <div class="table-responsive mt-2">
                                <table class="table table-hover mb-0 table-variants">
                                    <thead>
                                        <tr>
                                            <th>Nama</th>
                                            <th>Model ID</th>
                                            <th>SKU</th>
                                            <th>Harga</th>
                                            <th>Stok</th>
                                            <th style="min-width:240px;">Override HPP</th>
                                            <th style="min-width:360px;">Override Packaging</th>
                                            <th>Laba</th>
                                            <th>Margin</th>
                                            <th>Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php $__currentLoopData = $product->variants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $vPrice = (float)($v->price ?? 0);

                                            $vHpp = old("variants.$v->id.hpp_amount", $v->hpp_amount);
                                            $vHppDisplay = ($vHpp !== null && $vHpp !== '') ? number_format((float)$vHpp, 0, ',', '.') : '';

                                            $vPackType = old("variants.$v->id.packaging_type", $v->packaging_type);
                                            $vPackVal  = old("variants.$v->id.packaging_value", $v->packaging_value);

                                            $vPackFixedDisplay   = ($vPackType === 'fixed' && $vPackVal !== null && $vPackVal !== '') ? number_format((float)$vPackVal, 0, ',', '.') : '';
                                            $vPackPercentDisplay = ($vPackType === 'percent' && $vPackVal !== null && $vPackVal !== '') ? (float)$vPackVal : '';
                                        ?>

                                        <tr class="variant-row"
                                            data-variant-id="<?php echo e($v->id); ?>"
                                            data-variant-price="<?php echo e($vPrice); ?>">

                                            <td data-label="Nama"><?php echo e($v->name); ?></td>
                                            <td data-label="Model ID"><?php echo e($v->external_model_id); ?></td>
                                            <td data-label="SKU"><?php echo e($v->sku ?? '-'); ?></td>

                                            <td data-label="Harga">
                                                <?php if($v->price !== null): ?>
                                                    Rp <?php echo e(number_format($v->price, 0, ',', '.')); ?>

                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <td data-label="Stok"><?php echo e($v->stock ?? '-'); ?></td>

                                            
                                            <td data-label="Override HPP">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="text"
                                                           class="form-control money-input rupiah-display v-hpp-display"
                                                           inputmode="numeric"
                                                           pattern="[0-9]*"
                                                           enterkeyhint="done"
                                                           autocomplete="off"
                                                           placeholder="Default"
                                                           value="<?php echo e($vHppDisplay); ?>">
                                                </div>
                                                <input type="hidden"
                                                       class="v-hpp-hidden"
                                                       name="variants[<?php echo e($v->id); ?>][hpp_amount]"
                                                       value="<?php echo e($vHpp); ?>">
                                                <small class="text-muted d-block mt-1">Kosong = default</small>
                                            </td>

                                            
                                            <td data-label="Override Packaging" class="variant-cost">
                                                <div class="vc-stack">
                                                    <div class="input-group input-group-sm">
                                                        <select class="form-select input-group-unit v-pack-type"
                                                                name="variants[<?php echo e($v->id); ?>][packaging_type]"
                                                                aria-label="Packaging type">
                                                            <option value="" <?php echo e(($vPackType === null || $vPackType === '') ? 'selected' : ''); ?>></option>
                                                            <option value="fixed" <?php echo e($vPackType === 'fixed' ? 'selected' : ''); ?>>Rp</option>
                                                            <option value="percent" <?php echo e($vPackType === 'percent' ? 'selected' : ''); ?>>%</option>
                                                        </select>

                                                        <input type="hidden"
                                                               class="v-pack-hidden"
                                                               name="variants[<?php echo e($v->id); ?>][packaging_value]"
                                                               value="<?php echo e($vPackVal); ?>">

                                                        
                                                        <input type="text"
                                                               class="form-control money-input unit-input rupiah-display v-pack-fixed-display"
                                                               inputmode="numeric"
                                                               pattern="[0-9]*"
                                                               enterkeyhint="done"
                                                               autocomplete="off"
                                                               placeholder="Default"
                                                               value="<?php echo e($vPackFixedDisplay); ?>">

                                                        
                                                        <input type="number"
                                                               class="form-control money-input unit-input d-none v-pack-percent-input"
                                                               min="0" max="100" step="0.01"
                                                               placeholder="Default"
                                                               value="<?php echo e($vPackPercentDisplay); ?>">
                                                    </div>

                                                    <small class="text-muted v-pack-est">Estimasi: -</small>
                                                    <small class="text-muted">Kosong di select = ikut produk.</small>
                                                </div>
                                            </td>

                                            <td data-label="Laba"><b class="v-profit">-</b></td>
                                            <td data-label="Margin"><b class="v-margin">-</b></td>
                                            <td data-label="Update"><small class="text-muted"><?php echo e($v->updated_at->diffForHumans()); ?></small></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>

                            <small class="text-muted d-block mt-2">
                                Catatan: Laba/Margin dihitung berdasarkan harga variant. Jika harga variant kosong, hasil jadi '-'.
                            </small>
                        <?php endif; ?>

                        <div class="mt-3">
                            <button class="hub-btn hub-btn-primary w-100 w-sm-auto">
                                <i class="fas fa-save me-2"></i>Simpan Biaya
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="hub-card">
                <div class="card-header" style="background:#f8f9fa; border-bottom:1px solid #dee2e6;">
                    <h6 class="mb-0"><i class="fas fa-link me-2"></i>Data External (Shopee)</h6>
                </div>
                <div class="card-body">
                    <?php if($product->external_platform === 'shopee'): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <th style="width:180px;">Shop ID</th>
                                    <td><?php echo e($product->external_shop_id ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Item ID</th>
                                    <td><?php echo e($product->external_item_id ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>SKU</th>
                                    <td><?php echo e($product->external_sku ?? '-'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <small class="text-muted">
                            Produk ini disync dari Shopee dan bersifat <b>read-only</b> untuk data produk utama.
                            Biaya internal (HPP/Packaging) tetap bisa diisi di panel ini.
                        </small>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-0">Produk internal (bukan dari Shopee).</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="hub-card">
                <div class="card-header" style="background:#f8f9fa; border-bottom:1px solid #dee2e6;">
                    <h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Pesanan terkait (internal)</h6>
                </div>
                <div class="card-body">
                    <?php if($product->orders && $product->orders->count() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $product->orders->take(20); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><a href="<?php echo e(route('orders.show', $o)); ?>"><?php echo e($o->order_number); ?></a></td>
                                            <td><?php echo e($o->customer_name); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo e($o->status); ?></span></td>
                                            <td><?php echo e(optional($o->order_date)->format('d M Y')); ?></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">Belum ada pesanan internal yang terhubung via kolom <code>product_id</code>.</div>
                    <?php endif; ?>
                    <small class="text-muted d-block mt-2">Catatan: order Shopee biasanya terhubung via <code>order_items.product_id</code> setelah sync produk.</small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
  'use strict';

  const DEBUG = true;
  const NS = '[COST_UI]';
  const log  = (...a) => DEBUG && console.log(NS, ...a);

  /* =========================
     KILL MASKS (best-effort)
  ========================= */
  function killExternalMasks(){
    const targets = [
      ...document.querySelectorAll('.rupiah-display'),
      ...document.querySelectorAll('.v-hpp-display'),
      ...document.querySelectorAll('.v-pack-fixed-display')
    ];

    targets.forEach(el => {
      if (el.autoNumeric) try { el.autoNumeric.remove(); } catch(e){}
      if (el.cleave) try { el.cleave.destroy(); } catch(e){}
      if (el.inputmask) try { el.inputmask.remove(); } catch(e){}
      ['autonumeric', 'cleave', 'inputmask', 'mask'].forEach(attr => {
        el.removeAttribute(`data-${attr}`);
      });
      log('Killed mask on', el.id || el.className);
    });
  }

  /* =========================
     FORMAT HELPERS
  ========================= */
  const nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
  function rupiah(n){ return 'Rp ' + nf.format(Math.round(n || 0)); }

  function clampPct(x){
    const v = isNaN(x) ? 0 : x;
    return Math.min(100, Math.max(0, v));
  }

  function normPercentString(val){
    const raw = (val ?? '').toString().trim().replace(',', '.');
    const num = clampPct(parseFloat(raw || '0'));
    const fixed = Math.round(num * 100) / 100;
    return String(fixed);
  }

  function onlyDigits(str){ return (str || '').toString().replace(/\D/g,''); }
  function formatDotsFromDigits(d){
    const x = onlyDigits(d);
    return x ? x.replace(/\B(?=(\d{3})+(?!\d))/g,'.') : '';
  }

  function stripInvisibles(s){
    return (s ?? '').toString().replace(/[\u200E\u200F\u202A-\u202E\u2066-\u2069]/g, '');
  }

  // ✅ parser DISPLAY (user typing)
  function rupiahDigitsFromDisplay(raw){
    let s = stripInvisibles((raw ?? '').toString());
    s = s.replace(/\u00A0/g, ' ').trim();
    s = s.replace(/,\-$/, '').replace(/\-$/, '');
    return s.replace(/\D/g, '');
  }

  // ✅ parser STORED/DB (misal "1000000.00") -> buang desimal
  function rupiahDigitsFromStored(raw){
    let s = stripInvisibles((raw ?? '').toString());
    s = s.replace(/\u00A0/g, ' ').trim();
    s = s.replace(/,\-$/, '').replace(/\-$/, '');

    if (/^\d+(?:[.,]\d+)?$/.test(s)){
      return s.split(/[.,]/)[0] || '';
    }
    return s.replace(/\D/g, '');
  }

  function toIntRupiah(raw){
    const d = rupiahDigitsFromStored(raw);
    return d ? parseInt(d, 10) : 0;
  }

  /**
   * ✅ parse angka dari attribute / string "uang" apa pun:
   * - "10.000" -> 10000
   * - "Rp 10.000" -> 10000
   * - "10000.00" -> 10000
   * - "1,234,567.89" -> 1234567
   */
  function toIntMoneyAny(raw){
    let s = stripInvisibles((raw ?? '').toString());
    s = s.replace(/\u00A0/g, ' ').trim();
    s = s.replace(/[^\d.,]/g, '');

    // id-ID thousand: 1.234.567 or 1.234.567,89
    if (/^\d{1,3}(\.\d{3})+(?:,\d+)?$/.test(s)){
      const intPart = s.split(',')[0];
      const digits = intPart.replace(/\./g, '');
      return digits ? parseInt(digits, 10) : 0;
    }

    // en-US thousand: 1,234,567 or 1,234,567.89
    if (/^\d{1,3}(,\d{3})+(?:\.\d+)?$/.test(s)){
      const intPart = s.split('.')[0];
      const digits = intPart.replace(/,/g, '');
      return digits ? parseInt(digits, 10) : 0;
    }

    // plain number with optional decimals: 1000000.00 / 1000000,00 / 1000000
    if (/^\d+(?:[.,]\d+)?$/.test(s)){
      const intPart = s.split(/[.,]/)[0] || '';
      return intPart ? parseInt(intPart, 10) : 0;
    }

    const d = s.replace(/\D/g, '');
    return d ? parseInt(d, 10) : 0;
  }

  /**
   * ✅ FORMAT LIVE + STORE TRUTH
   */
  function formatRupiahLive(inputEl, label){
    const before = inputEl.value;
    const digits = rupiahDigitsFromDisplay(before);
    const formatted = formatDotsFromDigits(digits);

    inputEl.value = formatted;
    inputEl.setAttribute('data-digits', digits);

    try { inputEl.setSelectionRange(formatted.length, formatted.length); } catch(e) {}

    log('formatRupiahLive', { label, before, digits, formatted });
    return digits;
  }

  /* =========================
     ✅ SMART BACKSPACE/DELETE
  ========================= */
  function stripLeadingZeros(d){
    return (d || '').toString().replace(/^0+(?=\d)/, '');
  }

  function digitsBeforeCaret(formatted, pos){
    const s = (formatted || '').slice(0, Math.max(0, pos));
    return (s.match(/\d/g) || []).length;
  }

  function caretPosFromDigitCount(formatted, digitCount){
    if (digitCount <= 0) return 0;
    let cnt = 0;
    for (let i = 0; i < formatted.length; i++){
      if (/\d/.test(formatted[i])) cnt++;
      if (cnt === digitCount) return i + 1;
    }
    return formatted.length;
  }

  function applyDigitsToEl(el, digits, caretDigitCount){
    const clean = stripLeadingZeros(digits);
    const formatted = formatDotsFromDigits(clean);

    el.value = formatted;
    el.setAttribute('data-digits', clean);

    const pos = caretPosFromDigitCount(formatted, caretDigitCount);
    try { el.setSelectionRange(pos, pos); } catch(e) {}

    return clean;
  }

  function smartDeleteOnEl(el, direction){
    const formatted = el.value || '';
    let digits = el.getAttribute('data-digits');
    if (digits == null) digits = rupiahDigitsFromDisplay(formatted);
    digits = stripLeadingZeros(digits);

    const start = el.selectionStart ?? formatted.length;
    const end   = el.selectionEnd ?? formatted.length;

    const left  = digitsBeforeCaret(formatted, start);
    const right = digitsBeforeCaret(formatted, end);

    let newDigits = digits;
    let caretDigits = left;

    if (start !== end){
      newDigits = digits.slice(0, left) + digits.slice(right);
      caretDigits = left;
    } else if (direction === 'backward'){
      if (left > 0){
        newDigits = digits.slice(0, left - 1) + digits.slice(left);
        caretDigits = left - 1;
      }
    } else {
      if (left < digits.length){
        newDigits = digits.slice(0, left) + digits.slice(left + 1);
        caretDigits = left;
      }
    }

    return { newDigits, caretDigits };
  }

  function attachSmartRupiahDelete(el, label, onDigits){
    if (!el) return;

    el.__smartDelHandled = false;

    el.addEventListener('keydown', function(e){
      if (e.isComposing) return;
      if (e.key !== 'Backspace' && e.key !== 'Delete') return;

      const dir = (e.key === 'Backspace') ? 'backward' : 'forward';
      const { newDigits, caretDigits } = smartDeleteOnEl(el, dir);

      e.preventDefault();
      e.stopImmediatePropagation();
      e.stopPropagation();

      const digits = applyDigitsToEl(el, newDigits, caretDigits);
      el.__smartDelHandled = true;

      log('smartDelete keydown', { label, key: e.key, digits });
      onDigits?.(digits);
    }, true);

    el.addEventListener('beforeinput', function(e){
      if (e.isComposing) return;

      if (el.__smartDelHandled){
        el.__smartDelHandled = false;
        return;
      }

      if (e.inputType !== 'deleteContentBackward' && e.inputType !== 'deleteContentForward') return;

      const dir = (e.inputType === 'deleteContentBackward') ? 'backward' : 'forward';
      const { newDigits, caretDigits } = smartDeleteOnEl(el, dir);

      e.preventDefault();
      e.stopImmediatePropagation();
      e.stopPropagation();

      const digits = applyDigitsToEl(el, newDigits, caretDigits);

      log('smartDelete beforeinput', { label, inputType: e.inputType, digits });
      onDigits?.(digits);
    }, true);
  }

  function disableBrowserValidation(form){
    if (form) form.noValidate = true;
    document.querySelectorAll('input.rupiah-display').forEach(el => {
      if (el.hasAttribute('pattern')) el.removeAttribute('pattern');
      el.setAttribute('inputmode', 'numeric');
      if (el.type !== 'text' && el.type !== 'tel') el.type = 'tel';
    });
    log('Browser validation disabled');
  }

  const PRODUCT_PRICE = Number(<?php echo json_encode((float)($product->base_price ?? 0), 15, 512) ?>) || 0;

  /* =========================
     ELEMENTS
  ========================= */
  const form = document.querySelector('form.cost-form');

  const hppDisplay  = document.getElementById('hpp_amount_display');
  const hppHidden   = document.getElementById('hpp_amount');
  const hppClearBtn = document.getElementById('hpp_clear_btn');

  const packType         = document.getElementById('packaging_type');
  const packHidden       = document.getElementById('packaging_value');
  const packFixedDisplay = document.getElementById('packaging_fixed_display');
  const packPercentInput = document.getElementById('packaging_percent_input');
  const packHelp         = document.getElementById('packaging_help');

  const pvPrice  = document.getElementById('pv_price');
  const pvHpp    = document.getElementById('pv_hpp');
  const pvPack   = document.getElementById('pv_pack');
  const pvTotal  = document.getElementById('pv_total_cost');
  const pvProfit = document.getElementById('pv_profit');
  const pvMargin = document.getElementById('pv_margin');

  /* =========================
     CALC
  ========================= */
  function calcPackagingCost(price, type, value){
    if (type === 'percent'){
      const pct = clampPct(parseFloat((value || '0').toString().replace(',', '.')));
      return Math.round((price * pct) / 100);
    }
    return toIntRupiah(value);
  }

  function packagingLabel(price, type, value){
    if (type === 'percent'){
      const pct = clampPct(parseFloat((value || '0').toString().replace(',', '.')));
      const cost = calcPackagingCost(price, 'percent', pct);
      return `${rupiah(cost)} (${pct}%)`;
    }
    return rupiah(calcPackagingCost(price, 'fixed', value));
  }

  function getDefaultHpp(){ return hppHidden ? toIntRupiah(hppHidden.value) : 0; }
  function getDefaultPackagingType(){ return packType ? packType.value : 'fixed'; }
  function getDefaultPackagingValue(){ return packHidden ? packHidden.value : ''; }

  function applyDefaultPackagingMode(){
    if (!packType || !packFixedDisplay || !packPercentInput) return;

    if (packType.value === 'percent'){
      packFixedDisplay.classList.add('d-none');
      packPercentInput.classList.remove('d-none');
      if (packHelp) packHelp.textContent = 'Isi persen. Contoh: 1 berarti 1% dari harga.';
    } else {
      packPercentInput.classList.add('d-none');
      packFixedDisplay.classList.remove('d-none');
      if (packHelp) packHelp.textContent = 'Isi biaya packaging per unit dalam rupiah.';
    }
  }

  function refreshDefaultPreview(){
    const price = PRODUCT_PRICE || 0;
    const hpp   = getDefaultHpp();
    const pType = getDefaultPackagingType();
    const pVal  = getDefaultPackagingValue() || '0';
    const pack  = calcPackagingCost(price, pType, pVal);

    const total  = hpp + pack;
    const profit = price - total;
    const margin = price > 0 ? (profit / price) * 100 : 0;

    if (pvPrice) pvPrice.textContent = price > 0 ? rupiah(price) : '-';
    if (pvHpp) pvHpp.textContent = rupiah(hpp);
    if (pvPack) pvPack.textContent = packagingLabel(price, pType, pVal);
    if (pvTotal) pvTotal.textContent = rupiah(total);

    if (pvProfit){
      pvProfit.textContent = rupiah(profit);
      pvProfit.classList.remove('text-success','text-danger');
      pvProfit.classList.add(profit >= 0 ? 'text-success' : 'text-danger');
    }

    if (pvMargin) pvMargin.textContent = price > 0 ? `${margin.toFixed(1)}%` : '-';
  }

function updateVariantPackagingEstimate(row, price, effType, effVal){
  // ✅ class estimasi kamu
  const el = row.querySelector('.v-pack-est');
  if (!el) return;

  const txt = packagingLabel(price || 0, effType, effVal || '0');
  el.textContent = `Estimasi: ${txt}`;
}

  function refreshVariantRow(row){
    // ✅ parse harga variant aman (tidak pakai Number()!)
    const priceRaw = row.getAttribute('data-variant-price');
    const price = toIntMoneyAny(priceRaw);

    const defHpp = getDefaultHpp();
    const hppHiddenV = row.querySelector('.v-hpp-hidden');
    const hpp = (hppHiddenV && hppHiddenV.value !== '') ? toIntRupiah(hppHiddenV.value) : defHpp;

    const vTypeSel   = row.querySelector('.v-pack-type');
    const vValHidden = row.querySelector('.v-pack-hidden');

    let effType, effVal;

    // ✅ kalau select kosong => ikut default produk
    if (vTypeSel && vTypeSel.value !== ''){
      effType = vTypeSel.value;
      effVal  = (vValHidden ? vValHidden.value : '') || '0';
    } else {
      effType = getDefaultPackagingType();
      effVal  = getDefaultPackagingValue() || '0';
    }

    const packCost = calcPackagingCost(price, effType, effVal);
    const total = hpp + packCost;
    const profit = price - total;
    const margin = price > 0 ? (profit / price) * 100 : 0;

    // ✅ UPDATE Estimasi packaging di row variant (ini yang bikin sebelumnya tetap "-")
    updateVariantPackagingEstimate(row, price, effType, effVal);

    const elProfit = row.querySelector('.v-profit');
    const elMargin = row.querySelector('.v-margin');

    if (elProfit){
      elProfit.textContent = rupiah(profit);
      elProfit.classList.remove('text-success','text-danger');
      elProfit.classList.add(profit >= 0 ? 'text-success' : 'text-danger');
    }
    if (elMargin){
      elMargin.textContent = price > 0 ? `${margin.toFixed(1)}%` : '-';
    }
  }

  function refreshAll(){
    refreshDefaultPreview();
    document.querySelectorAll('tr.variant-row').forEach(refreshVariantRow);
  }

  /* =========================
     VARIANT BINDING
  ========================= */
  function bindVariantRow(row){
    const variantId = row.getAttribute('data-variant-id');
    const hppDisplayV = row.querySelector('.v-hpp-display');
    const hppHiddenV  = row.querySelector('.v-hpp-hidden');

    // ✅ init dari hidden (stored/DB)
    if (hppDisplayV && hppHiddenV && hppHiddenV.value !== null && hppHiddenV.value !== ''){
      const d = rupiahDigitsFromStored(hppHiddenV.value);
      hppHiddenV.value = d;
      hppDisplayV.value = formatDotsFromDigits(d);
      hppDisplayV.setAttribute('data-digits', d);
    }

    if (hppDisplayV && hppHiddenV){
      hppDisplayV.addEventListener('input', function(e){
        const digits = formatRupiahLive(e.target, `vHPP:${variantId}`);
        hppHiddenV.value = digits ? digits : '';
        refreshAll();

        e.stopImmediatePropagation();
        e.stopPropagation();
      }, true);

      hppDisplayV.addEventListener('blur', function(e){
        setTimeout(() => {
          const digits = formatRupiahLive(hppDisplayV, `vHPP_BLUR:${variantId}`);
          hppHiddenV.value = digits ? digits : '';
          refreshAll();
        }, 0);

        e.stopImmediatePropagation();
        e.stopPropagation();
      }, true);

      attachSmartRupiahDelete(hppDisplayV, `vHPP:${variantId}`, (digits) => {
        hppHiddenV.value = digits ? digits : '';
        refreshAll();
      });
    }

    const packTypeSel   = row.querySelector('.v-pack-type');
    const packHiddenV   = row.querySelector('.v-pack-hidden');
    const fixedDisplayV = row.querySelector('.v-pack-fixed-display');
    const percentInputV = row.querySelector('.v-pack-percent-input');

    function applyVariantPackagingUI(){
      if (!packTypeSel || !fixedDisplayV || !percentInputV || !packHiddenV) return;

      const chosen  = packTypeSel.value;
      const defType = getDefaultPackagingType();
      const showType = (chosen === '') ? defType : chosen;

      if (showType === 'percent'){
        fixedDisplayV.classList.add('d-none');
        percentInputV.classList.remove('d-none');
      } else {
        percentInputV.classList.add('d-none');
        fixedDisplayV.classList.remove('d-none');
      }

      if (chosen === ''){
        packHiddenV.value = '';
        fixedDisplayV.disabled = true;
        percentInputV.disabled = true;
        fixedDisplayV.value = '';
        percentInputV.value = '';
      } else {
        fixedDisplayV.disabled = (chosen !== 'fixed');
        percentInputV.disabled = (chosen !== 'percent');
      }
    }

    // ✅ init packaging fixed dari hidden (stored/DB)
    if (packTypeSel && packHiddenV && fixedDisplayV){
      if (packTypeSel.value === 'fixed' && packHiddenV.value !== null && packHiddenV.value !== ''){
        const d = rupiahDigitsFromStored(packHiddenV.value);
        packHiddenV.value = d;
        fixedDisplayV.value = formatDotsFromDigits(d);
        fixedDisplayV.setAttribute('data-digits', d);
      }
    }

    fixedDisplayV?.addEventListener('input', function(e){
      if (!packTypeSel || packTypeSel.value !== 'fixed') return;

      const digits = formatRupiahLive(e.target, `vPACK_FIXED:${variantId}`);
      if (packHiddenV) packHiddenV.value = digits ? digits : '';
      refreshAll();

      e.stopImmediatePropagation();
      e.stopPropagation();
    }, true);

    fixedDisplayV?.addEventListener('blur', function(e){
      if (!packTypeSel || packTypeSel.value !== 'fixed') return;

      setTimeout(() => {
        const digits = formatRupiahLive(fixedDisplayV, `vPACK_FIXED_BLUR:${variantId}`);
        if (packHiddenV) packHiddenV.value = digits ? digits : '';
        refreshAll();
      }, 0);

      e.stopImmediatePropagation();
      e.stopPropagation();
    }, true);

    attachSmartRupiahDelete(fixedDisplayV, `vPACK_FIXED:${variantId}`, (digits) => {
      if (packHiddenV && (!packTypeSel || packTypeSel.value === 'fixed')){
        packHiddenV.value = digits ? digits : '';
      }
      refreshAll();
    });

    percentInputV?.addEventListener('input', function(){
      if (!packTypeSel || packTypeSel.value !== 'percent') return;

      const v = this.value ? normPercentString(this.value) : '';
      this.value = v;
      if (packHiddenV) packHiddenV.value = v ? v : '';
      refreshAll();
    });

    packTypeSel?.addEventListener('change', function(){
      if (packHiddenV) packHiddenV.value = '';
      if (fixedDisplayV) fixedDisplayV.value = '';
      if (percentInputV) percentInputV.value = '';
      applyVariantPackagingUI();
      refreshAll();
    });

    row._applyVariantPackagingUI = applyVariantPackagingUI;
    applyVariantPackagingUI();
  }

  /* =========================
     DEFAULT INPUTS BINDING
  ========================= */
  if (hppDisplay && hppHidden){
    if (hppHidden.value !== null && hppHidden.value !== ''){
      const d = rupiahDigitsFromStored(hppHidden.value);
      hppHidden.value = d;
      hppDisplay.value = formatDotsFromDigits(d);
      hppDisplay.setAttribute('data-digits', d);
    }

    hppDisplay.addEventListener('input', function(e){
      const digits = formatRupiahLive(e.target, 'defaultHPP');
      hppHidden.value = digits ? digits : '';
      refreshAll();

      e.stopImmediatePropagation();
      e.stopPropagation();
    }, true);

    hppDisplay.addEventListener('blur', function(e){
      setTimeout(() => {
        const digits = formatRupiahLive(hppDisplay, 'defaultHPP_BLUR');
        hppHidden.value = digits ? digits : '';
        refreshAll();
      }, 0);

      e.stopImmediatePropagation();
      e.stopPropagation();
    }, true);

    attachSmartRupiahDelete(hppDisplay, 'defaultHPP', (digits) => {
      hppHidden.value = digits ? digits : '';
      refreshAll();
    });
  }

  hppClearBtn?.addEventListener('click', function(){
    if (hppDisplay) {
      hppDisplay.value = '';
      hppDisplay.removeAttribute('data-digits');
    }
    if (hppHidden) hppHidden.value = '';
    refreshAll();
  });

  packType?.addEventListener('change', function(){
    if (packHidden) packHidden.value = '';
    if (packFixedDisplay) {
      packFixedDisplay.value = '';
      packFixedDisplay.removeAttribute('data-digits');
    }
    if (packPercentInput) packPercentInput.value = '';
    applyDefaultPackagingMode();
    refreshAll();
  });

  // ✅ init default pack fixed dari hidden (stored/DB) kalau mode fixed
  if (packType && packHidden && packFixedDisplay){
    if (packType.value === 'fixed' && packHidden.value !== null && packHidden.value !== ''){
      const d = rupiahDigitsFromStored(packHidden.value);
      packHidden.value = d;
      packFixedDisplay.value = formatDotsFromDigits(d);
      packFixedDisplay.setAttribute('data-digits', d);
    }
  }

  packFixedDisplay?.addEventListener('input', function(e){
    const digits = formatRupiahLive(e.target, 'defaultPACK_FIXED');
    if (packHidden) packHidden.value = digits ? digits : '';
    refreshAll();

    e.stopImmediatePropagation();
    e.stopPropagation();
  }, true);

  packFixedDisplay?.addEventListener('blur', function(e){
    setTimeout(() => {
      const digits = formatRupiahLive(packFixedDisplay, 'defaultPACK_FIXED_BLUR');
      if (packHidden) packHidden.value = digits ? digits : '';
      refreshAll();
    }, 0);

    e.stopImmediatePropagation();
    e.stopPropagation();
  }, true);

  attachSmartRupiahDelete(packFixedDisplay, 'defaultPACK_FIXED', (digits) => {
    if (packHidden && (!packType || packType.value === 'fixed')){
      packHidden.value = digits ? digits : '';
    }
    refreshAll();
  });

  packPercentInput?.addEventListener('input', function(){
    const v = this.value ? normPercentString(this.value) : '';
    this.value = v;
    if (packHidden) packHidden.value = v ? v : '';
    refreshAll();
  });

  /* =========================
     ✅ SUBMIT - USE DATA ATTRIBUTE (TRUTH)
  ========================= */
  function sanitizeAllFromDisplays(){
    console.group('🔍 SANITIZE - READ FROM DATA-DIGITS');

    if (hppHidden && hppDisplay){
      const truth = hppDisplay.getAttribute('data-digits') || '';
      console.log('Default HPP:', { displayValue: hppDisplay.value, dataDigits: truth, hiddenBefore: hppHidden.value });
      hppHidden.value = truth;
    }

    if (packType && packHidden){
      if (packType.value === 'percent'){
        const v = (packPercentInput && packPercentInput.value !== '') ? normPercentString(packPercentInput.value) : '';
        packHidden.value = v ? v : '';
      } else if (packFixedDisplay) {
        const truth = packFixedDisplay.getAttribute('data-digits') || '';
        packHidden.value = truth;
      }
      console.log('Default PACK:', { type: packType.value, value: packHidden.value });
    }

    document.querySelectorAll('tr.variant-row').forEach(row => {
      const vid = row.getAttribute('data-variant-id');

      const vHppDisplay = row.querySelector('.v-hpp-display');
      const vHppHidden  = row.querySelector('.v-hpp-hidden');

      if (vHppDisplay && vHppHidden){
        const truth = vHppDisplay.getAttribute('data-digits') || '';
        console.log(`Variant ${vid} HPP:`, { displayValue: vHppDisplay.value, dataDigits: truth, hiddenBefore: vHppHidden.value });
        vHppHidden.value = truth;
      }

      const vTypeSel = row.querySelector('.v-pack-type');
      const vHidden  = row.querySelector('.v-pack-hidden');
      const vFixed   = row.querySelector('.v-pack-fixed-display');
      const vPct     = row.querySelector('.v-pack-percent-input');

      if (vTypeSel && vHidden){
        if (vTypeSel.value === ''){
          vHidden.value = '';
        } else if (vTypeSel.value === 'percent'){
          const v = (vPct && vPct.value !== '') ? normPercentString(vPct.value) : '';
          vHidden.value = v ? v : '';
        } else if (vFixed) {
          const truth = vFixed.getAttribute('data-digits') || '';
          vHidden.value = truth;
        }
      }
    });

    console.groupEnd();
  }

  if (form){
    form.addEventListener('submit', function(e){
      e.preventDefault();
      e.stopImmediatePropagation();

      console.log('🚀 SUBMIT TRIGGERED');

      const recompute = (el) => {
        if (!el) return;
        const digits = rupiahDigitsFromDisplay(el.value);
        el.setAttribute('data-digits', digits);
        el.value = formatDotsFromDigits(digits);
        return digits;
      };

      recompute(hppDisplay);
      recompute(packFixedDisplay);

      document.querySelectorAll('tr.variant-row').forEach(row => {
        recompute(row.querySelector('.v-hpp-display'));
        recompute(row.querySelector('.v-pack-fixed-display'));
      });

      sanitizeAllFromDisplays();

      const fd = new FormData(form);
      console.log('📦 PAYLOAD hpp_amount:', fd.get('hpp_amount'));
      console.log('📦 PAYLOAD variants hpp_amount:', [...fd.entries()].filter(([k]) => k.includes('[hpp_amount]')));
      console.log('📦 PAYLOAD variants packaging_value:', [...fd.entries()].filter(([k]) => k.includes('[packaging_value]')));

      HTMLFormElement.prototype.submit.call(form);
    }, true);
  }

  /* =========================
     INIT
  ========================= */
  killExternalMasks();
  disableBrowserValidation(form);
  applyDefaultPackagingMode();
  document.querySelectorAll('tr.variant-row').forEach(bindVariantRow);
  refreshAll();

  window.__COST_DEBUG__ = {
    dump(){
      const rows = Array.from(document.querySelectorAll('tr.variant-row')).map(r => ({
        id: r.getAttribute('data-variant-id'),
        variantPriceRaw: r.getAttribute('data-variant-price'),
        variantPriceParsed: toIntMoneyAny(r.getAttribute('data-variant-price')),
        packType: r.querySelector('.v-pack-type')?.value ?? null,
        packHidden: r.querySelector('.v-pack-hidden')?.value ?? null,
        packEstimateText:
          r.querySelector('.v-pack-estimate')?.textContent ??
          r.querySelector('.v-pack-estimasi')?.textContent ??
          r.querySelector('.v-pack-preview')?.textContent ??
          r.querySelector('.v-pack-cost')?.textContent ??
          r.querySelector('[data-pack-estimate]')?.textContent ??
          r.querySelector('[data-role="pack-estimate"]')?.textContent ??
          null
      }));
      console.table(rows);
      console.log('Default HPP:', {
        display: hppDisplay?.value,
        dataDigits: hppDisplay?.getAttribute('data-digits'),
        hidden: hppHidden?.value
      });
    }
  };

  log('Ready. Run: __COST_DEBUG__.dump()');
})();
</script>

<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\products\show.blade.php ENDPATH**/ ?>