{{-- @include('hub.partials.page-hero', ['title' => '', 'subtitle' => '', 'icon' => 'fa-chart-line', 'actions' => '']) --}}
<div class="report-hero mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1>
                @if(!empty($icon))<i class="fas {{ $icon }} me-2"></i>@endif
                {{ $title ?? 'Halaman' }}
            </h1>
            @if(!empty($subtitle))
                <div class="report-hero-meta"><span>{{ $subtitle }}</span></div>
            @endif
            @if(!empty($meta) && is_array($meta))
                <div class="report-hero-meta mt-1">
                    @foreach($meta as $m)
                        <span><i class="fas {{ $m['icon'] ?? 'fa-circle' }}"></i> {{ $m['text'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>
        @if(!empty($actions))
            <div class="hub-btn-group">{!! $actions !!}</div>
        @endif
    </div>
</div>
