@php
    $mode = $rekap_mode ?? 'detail';
    $selectedMonth = $rekap_selected_month ?? null;
    $compareMonths = $rekap_compare_months ?? [];
    $available = $rekap['available_months'] ?? [];
    $status = $filters['status'] ?? 'completed';
    $defaultMonth = $selectedMonth ?? now()->format('Y-m');
@endphp

<div class="rekap-picker-card hub-card mb-3">
    <div class="hub-card-body">
        <div class="rekap-mode-tabs" role="tablist">
            <a href="{{ route('monitoring.rekap', ['mode' => 'detail', 'month' => $selectedMonth, 'status' => $status]) }}"
               class="rekap-mode-tab {{ $mode === 'detail' ? 'is-active' : '' }}"
               role="tab" aria-selected="{{ $mode === 'detail' ? 'true' : 'false' }}">
                <i class="far fa-calendar"></i> Detail 1 Bulan
            </a>
            <a href="{{ route('monitoring.rekap', ['mode' => 'compare', 'compare' => $compareMonths, 'status' => $status]) }}"
               class="rekap-mode-tab {{ $mode === 'compare' ? 'is-active' : '' }}"
               role="tab" aria-selected="{{ $mode === 'compare' ? 'true' : 'false' }}">
                <i class="fas fa-columns"></i> Bandingkan Bulan
            </a>
        </div>

        @if($mode === 'detail')
        <form method="GET" action="{{ route('monitoring.rekap') }}" class="rekap-picker-form">
            <input type="hidden" name="mode" value="detail">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="rekap-picker-row">
                <div class="rekap-picker-field">
                    <label class="hub-form-label" for="rekapMonth">Pilih bulan</label>
                    <select name="month" id="rekapMonth" class="hub-form-select" required>
                        <option value="" disabled {{ !$selectedMonth ? 'selected' : '' }}>— Pilih bulan —</option>
                        @foreach($available as $opt)
                        <option value="{{ $opt['key'] }}" @selected($selectedMonth === $opt['key'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="hub-btn hub-btn-primary rekap-picker-submit">
                    <i class="fas fa-search"></i> Lihat Detail
                </button>
            </div>
            <p class="rekap-picker-hint mb-0">Pilih bulan dulu — detail KPI, metrik, dan best seller akan muncul di bawah.</p>
        </form>
        @else
        <form method="GET" action="{{ route('monitoring.rekap') }}" class="rekap-picker-form" id="rekapCompareForm">
            <input type="hidden" name="mode" value="compare">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="rekap-picker-toolbar">
                <span class="rekap-picker-label">Centang bulan yang ingin dibandingkan (min. 2):</span>
                <div class="rekap-picker-actions">
                    <button type="button" class="rekap-chip-btn" data-rekap-select="last3">3 bulan terakhir</button>
                    <button type="button" class="rekap-chip-btn" data-rekap-select="last6">6 bulan</button>
                    <button type="button" class="rekap-chip-btn" data-rekap-clear>Bersihkan</button>
                </div>
            </div>
            <div class="rekap-month-grid">
                @foreach($available as $opt)
                <label class="rekap-month-chip">
                    <input type="checkbox" name="compare[]" value="{{ $opt['key'] }}"
                        @checked(in_array($opt['key'], $compareMonths, true))>
                    <span>{{ $opt['short'] }}</span>
                </label>
                @endforeach
            </div>
            <div class="rekap-picker-row mt-2">
                <button type="submit" class="hub-btn hub-btn-primary rekap-picker-submit">
                    <i class="fas fa-chart-bar"></i> Bandingkan
                </button>
                <span class="rekap-compare-count" data-rekap-count>{{ count($compareMonths) }} bulan dipilih</span>
            </div>
        </form>
        @endif
    </div>
</div>
