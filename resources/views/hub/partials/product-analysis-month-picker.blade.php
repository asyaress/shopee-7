@php
    $mc = $month_compare ?? [];
    $compareMonths = $mc['selected'] ?? [];
    $available = $mc['available_months'] ?? [];
    $preserve = request()->except(['compare']);
@endphp

<div class="rekap-picker-card hub-card mb-3">
    <div class="hub-card-body">
        <h3 class="h6 mb-2"><i class="fas fa-calendar-alt me-1"></i> Bandingkan tren bulan</h3>
        <form method="GET"
              action="{{ route('monitoring.product-analysis.show', $product) }}"
              class="rekap-picker-form"
              data-month-compare-form>
            @foreach($preserve as $key => $val)
                @if(is_array($val))
                    @foreach($val as $v)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endif
            @endforeach
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
            <p class="rekap-picker-hint mb-0 mt-2">Default: 6 bulan terakhir. Pilih bulan lain untuk melihat omzet, iklan, dan ROAS per periode.</p>
        </form>
    </div>
</div>
