@extends('layouts.hub')

@section('title', 'Laba per SKU')

@section('content')
@php $q = request()->query(); $heroExtra = '<span class="small text-muted">'.e($shop['label'] ?? '').' · '.e($meta['period_label'] ?? '').'</span>'; @endphp
@include('hub.partials.ceo.shell-open')

    @include('hub.partials.hub-zone-nav')
    @include('hub.partials.monitoring-filter')

    @php
        $blocks = [
            'stars' => ['label' => 'Stars', 'desc' => 'Laba bagus · iklan efisien', 'class' => 'tier-star'],
            'maintain' => ['label' => 'Maintain', 'desc' => 'Pertahankan', 'class' => 'tier-maintain'],
            'fix_price' => ['label' => 'Perbaiki harga', 'desc' => 'Margin tipis, volume tinggi', 'class' => 'tier-fix'],
            'bleeders' => ['label' => 'Bleeders', 'desc' => 'Rugi — prioritaskan tindakan', 'class' => 'tier-bleeder'],
        ];
    @endphp

    <div class="mon-matrix-grid">
        @foreach($blocks as $key => $meta)
        <div class="mon-matrix-col {{ $meta['class'] }}">
            <h3>{{ $meta['label'] }} <span class="badge bg-secondary">{{ count($quadrants[$key] ?? []) }}</span></h3>
            <p class="small text-muted">{{ $meta['desc'] }}</p>
            <ul class="mon-matrix-list">
                @foreach(array_slice($quadrants[$key] ?? [], 0, 12) as $p)
                <li>
                    <a href="{{ route('monitoring.product-analysis.show', ['product' => $p['product_id']] + $q) }}">{{ $p['name'] }}</a>
                    @include('hub.partials.product-shopee-links', ['links' => $p['links'] ?? []])
                    <span class="{{ ($p['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($p['net_profit'] ?? 0, true) }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>

    @include('hub.partials.chart-panel', [
        'id' => 'matrixPolar',
        'title' => 'Distribusi SKU',
        'subtitle' => 'Polar area — Star, Maintain, Perbaiki harga, Bleeder',
        'size' => 'default',
    ])

@include('hub.partials.ceo.shell-close')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const q = @json($quadrants ?? []);
    HubCharts.renderPreset('matrixPolar', 'bcg_mix', {
        labels: ['Stars', 'Maintain', 'Perbaiki harga', 'Bleeders'],
        data: [
            (q.stars || []).length,
            (q.maintain || []).length,
            (q.fix_price || []).length,
            (q.bleeders || []).length,
        ],
    });
});
</script>
@endpush
