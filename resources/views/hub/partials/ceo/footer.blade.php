@php $g = $ceoGuide ?? null; @endphp
@if($g && (!empty($g['glossary']) || !empty($g['formulas'])))
<div class="ceo-guide-footer mt-3" data-ceo="guides">
    @if(!empty($g['glossary']))
    <details class="ceo-details mb-2">
        <summary>Apa arti istilah di halaman ini?</summary>
        <div class="ceo-glossary">
            @foreach($g['glossary'] as $item)
            <div class="ceo-glossary-item">
                <div class="ceo-glossary-term">{{ $item['term'] }}</div>
                <div class="ceo-glossary-plain">{{ $item['plain'] }}</div>
                @if(!empty($item['formula']))
                <code class="ceo-glossary-formula">{{ $item['formula'] }}</code>
                @endif
            </div>
            @endforeach
        </div>
    </details>
    @endif
    @if(!empty($g['formulas']))
    <details class="ceo-details">
        <summary>Rumus perhitungan</summary>
        <div class="ceo-formula-list">
            @foreach($g['formulas'] as $f)
            <div class="ceo-formula-row">
                <span>{{ $f['label'] }}</span>
                <code>{{ $f['formula'] }}</code>
            </div>
            @endforeach
        </div>
    </details>
    @endif
</div>
@endif
