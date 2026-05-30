@php $filters = $filters ?? []; @endphp
<div class="report-filter-card">
    <div class="filter-title"><i class="fas fa-sliders-h me-1"></i> Parameter Laporan</div>
    <form method="GET" class="hub-filter-bar">
        <div class="filter-item">
            <label class="hub-form-label">Tanggal mulai</label>
            <input type="date" name="start" class="hub-form-control" value="{{ $filters['start'] ?? '' }}">
        </div>
        <div class="filter-item">
            <label class="hub-form-label">Tanggal akhir</label>
            <input type="date" name="end" class="hub-form-control" value="{{ $filters['end'] ?? '' }}">
        </div>
        <div class="filter-item">
            <label class="hub-form-label">Status pesanan</label>
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
        <div class="filter-item" style="flex:0 0 auto;align-self:flex-end;">
            <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-search"></i> Tampilkan</button>
        </div>
    </form>
</div>
