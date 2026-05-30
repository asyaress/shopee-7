@extends('layouts.hub')

@section('title', 'HPP & Packaging — Shopee Profit Hub')

@section('content')
<div class="report-shell hub-form-page">
  @include('hub.partials.page-hero', [
      'icon' => 'fa-calculator',
      'title' => 'Editor HPP & Packaging',
      'subtitle' => 'Kalkulasi laba live per produk & varian',
      'actions' => '<a href="' . route('manage.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-database"></i> Kelola Data</a>'
          . '<a href="' . route('products.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-box"></i> Produk</a>',
  ])

  <div class="report-filter-card mb-3">
    <form class="row g-2 align-items-end" method="GET" action="{{ route('products.costs') }}">
      <div class="col-md-5">
        <label class="hub-form-label">Cari</label>
        <input type="text" class="hub-form-control" name="search" value="{{ request('search') }}" placeholder="Nama produk...">
      </div>
      <div class="col-md-4">
        <label class="hub-form-label">Kategori</label>
        <select class="hub-form-select hub-form-control" name="category">
          <option value="">Semua Kategori</option>
          @foreach($categories as $cat)
            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <button class="hub-btn hub-btn-primary w-100" type="submit"><i class="fas fa-search me-1"></i> Filter</button>
      </div>
    </form>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="row g-4">
    <div class="col-12">
      @forelse($products as $product)
        @php
          $hasVariants = $product->variants && $product->variants->count() > 0;

          $hppVal = $product->hpp_amount; // jangan pakai old() biar nggak bentrok antar form
          $hppDisplay = ($hppVal !== null && $hppVal !== '') ? number_format((float)$hppVal, 0, ',', '.') : '';

          $packType = $product->packaging_type ?? 'fixed';
          $packVal  = $product->packaging_value;

          $packFixedDisplay   = ($packType === 'fixed' && $packVal !== null && $packVal !== '') ? number_format((float)$packVal, 0, ',', '.') : '';
          $packPercentDisplay = ($packType === 'percent' && $packVal !== null && $packVal !== '') ? (float)$packVal : '';

          $basePrice = (float)($product->base_price ?? 0);
        @endphp

        <div class="hub-card cost-scope mb-4"
             data-product-id="{{ $product->id }}"
             data-product-price="{{ $basePrice }}">

          <div class="card-header" style="background:#f8f9fa; border-bottom:1px solid #dee2e6;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
              <div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <h6 class="mb-0" style="font-weight:800;">
                    <i class="fas fa-box me-2"></i>{{ $product->name }}
                  </h6>

                  @if($product->category)
                    <span class="badge bg-info">{{ $product->category }}</span>
                  @endif

                  @if($product->external_platform === 'shopee')
                    <span class="badge bg-warning text-dark">Shopee (Synced)</span>
                  @else
                    <span class="badge bg-secondary">Internal</span>
                  @endif

                  @if($product->is_active)
                    <span class="badge bg-success">Aktif</span>
                  @else
                    <span class="badge bg-danger">Tidak Aktif</span>
                  @endif
                </div>

                <div class="text-muted mt-1">
                  Harga:
                  @if($product->base_price)
                    <b style="color: var(--primary-red);">Rp {{ number_format($product->base_price, 0, ',', '.') }}</b>
                  @else
                    <span class="text-muted">Harga Custom</span>
                  @endif
                  <span class="mx-2">•</span>
                  <a href="{{ route('products.show', $product) }}" class="text-decoration-none">Detail</a>
                </div>
              </div>

              <div class="d-flex gap-2">
                <button class="hub-btn hub-btn-sm hub-btn-outline" type="button" data-toggle-variants>
                  <i class="fas fa-layer-group me-1"></i>Toggle Variants
                </button>
              </div>
            </div>
          </div>

          <div class="card-body">
            <form class="cost-form" method="POST" action="{{ route('products.update-costs', $product) }}">
              @csrf
              @method('PATCH')

              <div class="row g-3">
                {{-- DEFAULT HPP --}}
                <div class="col-12 col-md-6">
                  <label class="form-label fw-semibold">Default HPP (Rp / unit)</label>
                  <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input
                      type="text"
                      class="form-control money-input rupiah-display p-hpp-display"
                      inputmode="numeric"
                      autocomplete="off"
                      placeholder="10.000"
                      value="{{ $hppDisplay }}"
                    >
                    <button type="button" class="btn btn-outline-secondary p-hpp-clear" title="Clear">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  <input type="hidden" name="hpp_amount" class="p-hpp-hidden" value="{{ $hppVal }}">
                  <small class="text-muted">Kosongkan jika tidak ingin pakai default.</small>
                  <small class="text-muted d-block mt-1 p-hpp-est-line">Estimasi HPP: -</small>
                </div>

                {{-- DEFAULT PACKAGING --}}
                <div class="col-12 col-md-6">
                  <label class="form-label fw-semibold">Default Packaging</label>
                  <div class="input-group">
                    <select class="form-select input-group-unit p-pack-type" name="packaging_type">
                      <option value="fixed" {{ $packType==='fixed' ? 'selected' : '' }}>Rp</option>
                      <option value="percent" {{ $packType==='percent' ? 'selected' : '' }}>%</option>
                    </select>

                    <input
                      type="text"
                      class="form-control money-input unit-input rupiah-display p-pack-fixed {{ $packType==='percent' ? 'd-none' : '' }}"
                      inputmode="numeric"
                      autocomplete="off"
                      placeholder="2.000"
                      value="{{ $packFixedDisplay }}"
                    >

                    <input
                      type="number"
                      class="form-control money-input unit-input p-pack-percent {{ $packType==='fixed' ? 'd-none' : '' }}"
                      min="0" max="100" step="0.01"
                      placeholder="1"
                      value="{{ $packPercentDisplay }}"
                    >
                  </div>

                  <input type="hidden" name="packaging_value" class="p-pack-hidden" value="{{ $packVal }}">
                  <small class="text-muted d-block mt-1 p-pack-help">Packaging per unit (Rp) atau % dari harga.</small>
                  <small class="text-muted d-block mt-1 p-pack-est-line">Estimasi packaging: -</small>
                </div>
              </div>

              {{-- PREVIEW --}}
              <div class="cost-preview mt-3">
                <div class="preview-title">
                  <i class="fas fa-chart-line me-2"></i>Kalkulasi Otomatis (Default)
                  <span class="text-muted ms-2 small">(live)</span>
                </div>

                <div class="preview-grid">
                  <div class="preview-row">
                    <span class="label">Harga Jual</span>
                    <b class="pv-price">-</b>
                  </div>
                  <div class="preview-row">
                    <span class="label">HPP</span>
                    <b class="pv-hpp">-</b>
                  </div>
                  <div class="preview-row">
                    <span class="label">Packaging</span>
                    <b class="pv-pack">-</b>
                  </div>
                  <div class="preview-row">
                    <span class="label">Total Biaya</span>
                    <b class="pv-total">-</b>
                  </div>

                  <hr class="my-2">

                  <div class="preview-row">
                    <span class="label">Estimasi Laba</span>
                    <b class="pv-profit">-</b>
                  </div>
                  <div class="preview-row">
                    <span class="label">Margin</span>
                    <b class="pv-margin">-</b>
                  </div>
                </div>
              </div>

              {{-- VARIANTS --}}
              @if($hasVariants)
                <hr class="my-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                  <h6 class="mb-0">
                    <i class="fas fa-layer-group me-2"></i>Biaya per Variant (Opsional)
                  </h6>
                  <small class="text-muted">Kosong = ikut produk</small>
                </div>

                <div class="table-responsive mt-2 variants-wrap">
                  <table class="table table-hover mb-0 table-variants">
                    <thead>
                      <tr>
                        <th>Nama</th>
                        <th>Harga</th>
                        <th style="min-width:240px;">Override HPP</th>
                        <th style="min-width:360px;">Override Packaging</th>
                        <th>Laba</th>
                        <th>Margin</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($product->variants as $v)
                        @php
                          $vPrice = $v->price; // bisa null
                          $vPriceFloat = $v->price !== null ? (float)$v->price : null;

                          $vHpp = $v->hpp_amount;
                          $vHppDisplay = ($vHpp !== null && $vHpp !== '') ? number_format((float)$vHpp, 0, ',', '.') : '';

                          $vPackType = $v->packaging_type; // null = inherit
                          $vPackVal  = $v->packaging_value;

                          $vPackFixedDisplay   = ($vPackType === 'fixed' && $vPackVal !== null && $vPackVal !== '') ? number_format((float)$vPackVal, 0, ',', '.') : '';
                          $vPackPercentDisplay = ($vPackType === 'percent' && $vPackVal !== null && $vPackVal !== '') ? (float)$vPackVal : '';
                        @endphp

                        <tr class="variant-row"
                            data-variant-id="{{ $v->id }}"
                            data-variant-price="{{ $vPriceFloat !== null ? $vPriceFloat : '' }}">

                          <td data-label="Nama">{{ $v->name }}</td>

                          <td data-label="Harga">
                            @if($vPrice !== null)
                              Rp {{ number_format($vPrice, 0, ',', '.') }}
                            @else
                              <span class="text-muted">-</span>
                            @endif
                          </td>

                          {{-- Override HPP --}}
                          <td data-label="Override HPP">
                            <div class="input-group input-group-sm">
                              <span class="input-group-text">Rp</span>
                              <input type="text"
                                     class="form-control money-input rupiah-display v-hpp-display"
                                     inputmode="numeric"
                                     autocomplete="off"
                                     placeholder="Default"
                                     value="{{ $vHppDisplay }}">
                            </div>
                            <input type="hidden"
                                   class="v-hpp-hidden"
                                   name="variants[{{ $v->id }}][hpp_amount]"
                                   value="{{ $vHpp }}">
                            <small class="text-muted d-block mt-1">Kosong = default</small>
                          </td>

                          {{-- Override Packaging --}}
                          <td data-label="Override Packaging" class="variant-cost">
                            <div class="vc-stack">
                              <div class="input-group input-group-sm">
                                <select class="form-select input-group-unit v-pack-type"
                                        name="variants[{{ $v->id }}][packaging_type]">
                                  <option value="" {{ ($vPackType === null || $vPackType === '') ? 'selected' : '' }}> </option>
                                  <option value="fixed" {{ $vPackType === 'fixed' ? 'selected' : '' }}>Rp</option>
                                  <option value="percent" {{ $vPackType === 'percent' ? 'selected' : '' }}>%</option>
                                </select>

                                <input type="hidden"
                                       class="v-pack-hidden"
                                       name="variants[{{ $v->id }}][packaging_value]"
                                       value="{{ $vPackVal }}">

                                <input type="text"
                                       class="form-control money-input unit-input rupiah-display v-pack-fixed-display"
                                       inputmode="numeric"
                                       autocomplete="off"
                                       placeholder="Default"
                                       value="{{ $vPackFixedDisplay }}">

                                <input type="number"
                                       class="form-control money-input unit-input d-none v-pack-percent-input"
                                       min="0" max="100" step="0.01"
                                       placeholder="Default"
                                       value="{{ $vPackPercentDisplay }}">
                              </div>

                              <small class="text-muted v-pack-est">Estimasi: -</small>
                              <small class="text-muted">Kosong di select = ikut produk.</small>
                            </div>
                          </td>

                          <td data-label="Laba"><b class="v-profit">-</b></td>
                          <td data-label="Margin"><b class="v-margin">-</b></td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>

                  <small class="text-muted d-block mt-2">
                    Catatan: Laba/Margin dihitung berdasarkan harga variant. Jika harga variant kosong, hasil jadi '-'.
                  </small>
                </div>
              @endif

              <div class="mt-3">
                <button class="hub-btn hub-btn-primary w-100 w-sm-auto">
                  <i class="fas fa-save me-2"></i>Simpan Biaya (Produk Ini)
                </button>
              </div>
            </form>
          </div>
        </div>
      @empty
        <div class="alert alert-secondary mb-0">Tidak ada produk.</div>
      @endforelse
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  'use strict';

  const DEBUG = false;
  const NS = '[COSTS_PAGE]';
  const log  = (...a) => DEBUG && console.log(NS, ...a);

  /* =========================
     HELPERS
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

  // DISPLAY parser
  function rupiahDigitsFromDisplay(raw){
    let s = stripInvisibles((raw ?? '').toString());
    s = s.replace(/\u00A0/g, ' ').trim();
    s = s.replace(/,\-$/, '').replace(/\-$/, '');
    return s.replace(/\D/g, '');
  }

  // STORED parser (1000000.00 -> 1000000)
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

  function toIntMoneyAny(raw){
    let s = stripInvisibles((raw ?? '').toString());
    s = s.replace(/\u00A0/g, ' ').trim();
    s = s.replace(/[^\d.,]/g, '');

    if (/^\d{1,3}(\.\d{3})+(?:,\d+)?$/.test(s)){
      const intPart = s.split(',')[0];
      const digits = intPart.replace(/\./g, '');
      return digits ? parseInt(digits, 10) : 0;
    }
    if (/^\d{1,3}(,\d{3})+(?:\.\d+)?$/.test(s)){
      const intPart = s.split('.')[0];
      const digits = intPart.replace(/,/g, '');
      return digits ? parseInt(digits, 10) : 0;
    }
    if (/^\d+(?:[.,]\d+)?$/.test(s)){
      const intPart = s.split(/[.,]/)[0] || '';
      return intPart ? parseInt(intPart, 10) : 0;
    }
    const d = s.replace(/\D/g, '');
    return d ? parseInt(d, 10) : 0;
  }

  function formatRupiahLive(inputEl){
    const digits = rupiahDigitsFromDisplay(inputEl.value);
    const formatted = formatDotsFromDigits(digits);
    inputEl.value = formatted;
    inputEl.setAttribute('data-digits', digits);
    try { inputEl.setSelectionRange(formatted.length, formatted.length); } catch(e) {}
    return digits;
  }

  /* =========================
     SMART BACKSPACE/DELETE (Rp)
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

  function attachSmartRupiahDelete(el, onDigits){
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
      onDigits?.(digits);
    }, true);

    el.addEventListener('beforeinput', function(e){
      if (e.isComposing) return;
      if (el.__smartDelHandled){ el.__smartDelHandled = false; return; }

      if (e.inputType !== 'deleteContentBackward' && e.inputType !== 'deleteContentForward') return;

      const dir = (e.inputType === 'deleteContentBackward') ? 'backward' : 'forward';
      const { newDigits, caretDigits } = smartDeleteOnEl(el, dir);

      e.preventDefault();
      e.stopImmediatePropagation();
      e.stopPropagation();

      const digits = applyDigitsToEl(el, newDigits, caretDigits);
      onDigits?.(digits);
    }, true);
  }

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

  function disableBrowserValidation(form){
    if (form) form.noValidate = true;
    form.querySelectorAll('input.rupiah-display').forEach(el => {
      if (el.hasAttribute('pattern')) el.removeAttribute('pattern');
      el.setAttribute('inputmode', 'numeric');
      if (el.type !== 'text' && el.type !== 'tel') el.type = 'tel';
    });
  }

  /* =========================
     PER SCOPE INIT
  ========================= */
  function initCostScope(scope){
    const form = scope.querySelector('form.cost-form');
    if (!form) return;

    disableBrowserValidation(form);

    const PRODUCT_PRICE = toIntMoneyAny(scope.getAttribute('data-product-price'));

    const hppDisplay  = scope.querySelector('.p-hpp-display');
    const hppHidden   = scope.querySelector('.p-hpp-hidden');
    const hppClearBtn = scope.querySelector('.p-hpp-clear');

    const packType    = scope.querySelector('.p-pack-type');
    const packHidden  = scope.querySelector('.p-pack-hidden');
    const packFixed   = scope.querySelector('.p-pack-fixed');
    const packPercent = scope.querySelector('.p-pack-percent');
    const packHelp    = scope.querySelector('.p-pack-help');

    const elHppEstLine  = scope.querySelector('.p-hpp-est-line');
    const elPackEstLine = scope.querySelector('.p-pack-est-line');

    const pvPrice  = scope.querySelector('.pv-price');
    const pvHpp    = scope.querySelector('.pv-hpp');
    const pvPack   = scope.querySelector('.pv-pack');
    const pvTotal  = scope.querySelector('.pv-total');
    const pvProfit = scope.querySelector('.pv-profit');
    const pvMargin = scope.querySelector('.pv-margin');

    // toggle variants show/hide
    scope.querySelector('[data-toggle-variants]')?.addEventListener('click', () => {
      const wrap = scope.querySelector('.variants-wrap');
      if (wrap) wrap.classList.toggle('d-none');
    });

    function getDefaultHpp(){
      return hppHidden ? toIntRupiah(hppHidden.value) : 0;
    }
    function getDefaultPackagingType(){
      return packType ? packType.value : 'fixed';
    }
    function getDefaultPackagingValue(){
      return packHidden ? packHidden.value : '';
    }

    function applyDefaultPackagingMode(){
      if (!packType || !packFixed || !packPercent) return;
      if (packType.value === 'percent'){
        packFixed.classList.add('d-none');
        packPercent.classList.remove('d-none');
        if (packHelp) packHelp.textContent = 'Isi persen. Contoh: 1 berarti 1% dari harga.';
      } else {
        packPercent.classList.add('d-none');
        packFixed.classList.remove('d-none');
        if (packHelp) packHelp.textContent = 'Isi biaya packaging per unit dalam rupiah.';
      }
    }

    function refreshDefaultPreview(){
      const price = PRODUCT_PRICE || 0;
      const hpp   = getDefaultHpp();
      const pType = getDefaultPackagingType();
      const pVal  = (getDefaultPackagingValue() || '0');

      const pack = calcPackagingCost(price, pType, pVal);
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

      if (elHppEstLine) elHppEstLine.textContent = `Estimasi HPP: ${hpp ? rupiah(hpp) : '-'}`;
      if (elPackEstLine) elPackEstLine.textContent = `Estimasi packaging: ${pack || pVal ? packagingLabel(price, pType, pVal) : '-'}`;
    }

    function updateVariantPackagingEstimate(row, price, effType, effVal){
      const el = row.querySelector('.v-pack-est');
      if (!el) return;
      const txt = packagingLabel(price || 0, effType, effVal || '0');
      el.textContent = `Estimasi: ${txt}`;
    }

    function refreshVariantRow(row){
      const priceRaw = row.getAttribute('data-variant-price');
      const price = toIntMoneyAny(priceRaw);

      const defHpp = getDefaultHpp();
      const hppHiddenV = row.querySelector('.v-hpp-hidden');
      const hpp = (hppHiddenV && hppHiddenV.value !== '') ? toIntRupiah(hppHiddenV.value) : defHpp;

      const vTypeSel   = row.querySelector('.v-pack-type');
      const vValHidden = row.querySelector('.v-pack-hidden');

      let effType, effVal;
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
      scope.querySelectorAll('tr.variant-row').forEach(refreshVariantRow);
    }

    /* ===== INIT normalize default from hidden ===== */
    if (hppDisplay && hppHidden && hppHidden.value !== null && hppHidden.value !== ''){
      const d = rupiahDigitsFromStored(hppHidden.value);
      hppHidden.value = d;
      hppDisplay.value = formatDotsFromDigits(d);
      hppDisplay.setAttribute('data-digits', d);
    }

    if (packType && packHidden && packFixed){
      if (packType.value === 'fixed' && packHidden.value !== null && packHidden.value !== ''){
        const d = rupiahDigitsFromStored(packHidden.value);
        packHidden.value = d;
        packFixed.value = formatDotsFromDigits(d);
        packFixed.setAttribute('data-digits', d);
      }
    }

    /* ===== default bindings ===== */
    if (hppDisplay && hppHidden){
      hppDisplay.addEventListener('input', function(e){
        const digits = formatRupiahLive(e.target);
        hppHidden.value = digits ? digits : '';
        refreshAll();
        e.stopImmediatePropagation(); e.stopPropagation();
      }, true);

      hppDisplay.addEventListener('blur', function(){
        setTimeout(() => {
          const digits = formatRupiahLive(hppDisplay);
          hppHidden.value = digits ? digits : '';
          refreshAll();
        }, 0);
      }, true);

      attachSmartRupiahDelete(hppDisplay, (digits) => {
        hppHidden.value = digits ? digits : '';
        refreshAll();
      });
    }

    hppClearBtn?.addEventListener('click', function(){
      if (hppDisplay){ hppDisplay.value = ''; hppDisplay.removeAttribute('data-digits'); }
      if (hppHidden) hppHidden.value = '';
      refreshAll();
    });

    packType?.addEventListener('change', function(){
      if (packHidden) packHidden.value = '';
      if (packFixed){ packFixed.value = ''; packFixed.removeAttribute('data-digits'); }
      if (packPercent) packPercent.value = '';
      applyDefaultPackagingMode();
      refreshAll();
    });

    packFixed?.addEventListener('input', function(e){
      const digits = formatRupiahLive(e.target);
      if (packHidden) packHidden.value = digits ? digits : '';
      refreshAll();
      e.stopImmediatePropagation(); e.stopPropagation();
    }, true);

    packFixed?.addEventListener('blur', function(){
      setTimeout(() => {
        const digits = formatRupiahLive(packFixed);
        if (packHidden) packHidden.value = digits ? digits : '';
        refreshAll();
      }, 0);
    }, true);

    attachSmartRupiahDelete(packFixed, (digits) => {
      if (packHidden && (!packType || packType.value === 'fixed')){
        packHidden.value = digits ? digits : '';
      }
      refreshAll();
    });

    packPercent?.addEventListener('input', function(){
      const v = this.value ? normPercentString(this.value) : '';
      this.value = v;
      if (packHidden) packHidden.value = v ? v : '';
      refreshAll();
    });

    /* ===== variants init + bindings ===== */
    scope.querySelectorAll('tr.variant-row').forEach(row => {
      const variantId = row.getAttribute('data-variant-id');

      const hppDisplayV = row.querySelector('.v-hpp-display');
      const hppHiddenV  = row.querySelector('.v-hpp-hidden');

      if (hppDisplayV && hppHiddenV && hppHiddenV.value !== null && hppHiddenV.value !== ''){
        const d = rupiahDigitsFromStored(hppHiddenV.value);
        hppHiddenV.value = d;
        hppDisplayV.value = formatDotsFromDigits(d);
        hppDisplayV.setAttribute('data-digits', d);
      }

      if (hppDisplayV && hppHiddenV){
        hppDisplayV.addEventListener('input', function(e){
          const digits = formatRupiahLive(e.target);
          hppHiddenV.value = digits ? digits : '';
          refreshAll();
          e.stopImmediatePropagation(); e.stopPropagation();
        }, true);

        hppDisplayV.addEventListener('blur', function(){
          setTimeout(() => {
            const digits = formatRupiahLive(hppDisplayV);
            hppHiddenV.value = digits ? digits : '';
            refreshAll();
          }, 0);
        }, true);

        attachSmartRupiahDelete(hppDisplayV, (digits) => {
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
        const digits = formatRupiahLive(e.target);
        if (packHiddenV) packHiddenV.value = digits ? digits : '';
        refreshAll();
        e.stopImmediatePropagation(); e.stopPropagation();
      }, true);

      fixedDisplayV?.addEventListener('blur', function(){
        if (!packTypeSel || packTypeSel.value !== 'fixed') return;
        setTimeout(() => {
          const digits = formatRupiahLive(fixedDisplayV);
          if (packHiddenV) packHiddenV.value = digits ? digits : '';
          refreshAll();
        }, 0);
      }, true);

      attachSmartRupiahDelete(fixedDisplayV, (digits) => {
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

      applyVariantPackagingUI();
    });

    /* ===== submit sanitize (per form) ===== */
    form.addEventListener('submit', function(e){
      e.preventDefault();
      e.stopImmediatePropagation();

      const recompute = (el) => {
        if (!el) return;
        const digits = rupiahDigitsFromDisplay(el.value);
        el.setAttribute('data-digits', digits);
        el.value = formatDotsFromDigits(digits);
        return digits;
      };

      recompute(hppDisplay);
      recompute(packFixed);
      scope.querySelectorAll('.v-hpp-display, .v-pack-fixed-display').forEach(recompute);

      // sanitize default
      if (hppHidden && hppDisplay){
        hppHidden.value = hppDisplay.getAttribute('data-digits') || '';
      }

      if (packType && packHidden){
        if (packType.value === 'percent'){
          const v = (packPercent && packPercent.value !== '') ? normPercentString(packPercent.value) : '';
          packHidden.value = v ? v : '';
        } else {
          packHidden.value = packFixed?.getAttribute('data-digits') || '';
        }
      }

      // sanitize variants
      scope.querySelectorAll('tr.variant-row').forEach(row => {
        const vHppDisplay = row.querySelector('.v-hpp-display');
        const vHppHidden  = row.querySelector('.v-hpp-hidden');
        if (vHppDisplay && vHppHidden){
          vHppHidden.value = vHppDisplay.getAttribute('data-digits') || '';
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
          } else {
            vHidden.value = vFixed?.getAttribute('data-digits') || '';
          }
        }
      });

      HTMLFormElement.prototype.submit.call(form);
    }, true);

    applyDefaultPackagingMode();
    refreshAll();
  }

  // init all product sections
  document.querySelectorAll('.cost-scope').forEach(initCostScope);
})();
</script>
@endpush
