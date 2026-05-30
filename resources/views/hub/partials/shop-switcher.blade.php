@if(count($shopeeShopOptions ?? []) > 0)
<form method="POST" action="{{ route('shop.switch') }}" class="hub-shop-switcher">
    @csrf
    <label class="visually-hidden" for="hubShopSelect">Pilih toko</label>
    <i class="fas fa-store"></i>
    <select name="shop_id" id="hubShopSelect" class="hub-shop-select" onchange="this.form.submit()">
        @foreach($shopeeShopOptions as $id => $label)
            <option value="{{ $id }}" @selected(($activeShopeeShopId ?? 0) == $id)>{{ $label }}</option>
        @endforeach
    </select>
</form>
@endif
