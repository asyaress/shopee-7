@if(!empty($links['product']) || !empty($links['ads']))
<span class="shopee-links ms-1">
    @if(!empty($links['product']))
    <a href="{{ $links['product'] }}" target="_blank" rel="noopener" class="shopee-link" title="Produk Shopee"><i class="fas fa-store"></i></a>
    @endif
    @if(!empty($links['ads']))
    <a href="{{ $links['ads'] }}" target="_blank" rel="noopener" class="shopee-link shopee-link-ads" title="Iklan Shopee"><i class="fas fa-bullhorn"></i></a>
    @endif
</span>
@endif
