@php $filters = $filters ?? []; @endphp
<details class="report-filter-card" open>
    <summary class="report-filter-summary">
        <span class="report-filter-summary-left">
            <i class="fas fa-sliders-h"></i>
            <span>Filter periode</span>
        </span>
        <span class="report-filter-summary-hint">Klik untuk ubah tanggal & status</span>
    </summary>
    <form method="GET" class="hub-filter-bar report-filter-form">
        <div class="filter-item">
            <label class="hub-form-label">Mulai</label>
            <input type="date" name="start" class="hub-form-control" value="{{ $filters['start'] ?? '' }}">
        </div>
        <div class="filter-item">
            <label class="hub-form-label">Akhir</label>
            <input type="date" name="end" class="hub-form-control" value="{{ $filters['end'] ?? '' }}">
        </div>
        <div class="filter-item">
            <label class="hub-form-label">Status</label>
            <select name="status" class="hub-form-select">
                @foreach(['completed','in_progress','pending','cancelled','all'] as $st)
                    <option value="{{ $st }}" @selected(($filters['status'] ?? 'completed') === $st)>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-item">
            <label class="hub-form-label">Kanal</label>
            <select name="jenis" class="hub-form-select">
                <option value="shopee" @selected(($filters['jenis'] ?? 'shopee') === 'shopee')>Shopee</option>
                <option value="all" @selected(($filters['jenis'] ?? '') === 'all')>Semua</option>
            </select>
        </div>
        <div class="filter-item filter-item--action">
            <button type="submit" class="hub-btn hub-btn-primary report-filter-submit">
                <i class="fas fa-sync-alt"></i> Terapkan
            </button>
        </div>
    </form>
</details>
