(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('hppForm');
        if (!form) return;

        const cards = Array.from(form.querySelectorAll('[data-product-card]'));
        const saveBar = document.getElementById('hppSaveBar');
        const dirtyCount = document.getElementById('hppDirtyCount');
        const payload = document.getElementById('hppPayload');
        const saveButton = document.getElementById('hppSaveButton');
        const dirtyProducts = new Set();
        let submitting = false;

        const currency = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        });

        function moneyDigits(value) {
            return String(value || '').replace(/\D/g, '').replace(/^0+(?=\d)/, '');
        }

        function formatMoneyInput(input) {
            const digits = moneyDigits(input.value);
            input.value = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        }

        function setPackagingInputMode(input, type, clearValue) {
            if (!input) return;
            if (clearValue) input.value = '';
            const isMoney = type === 'fixed';
            input.dataset.money = isMoney ? 'true' : 'false';
            input.inputMode = isMoney ? 'numeric' : 'decimal';
            if (isMoney) formatMoneyInput(input);
        }

        function numberValue(input) {
            if (!input || input.value === '') return null;
            const raw = input.dataset.money === 'true'
                ? moneyDigits(input.value)
                : input.value.replace(',', '.');
            const value = Number(raw);
            return Number.isFinite(value) ? Math.max(0, value) : null;
        }

        function field(scope, name, variant) {
            return scope.querySelector(
                variant ? `[data-variant-field="${name}"]` : `[data-product-field="${name}"]`
            );
        }

        function packagingCost(price, type, value) {
            const amount = value || 0;
            return type === 'percent' ? (price * Math.min(100, amount)) / 100 : amount;
        }

        function setOpen(card, open) {
            const body = card.querySelector('.hpp-product-body');
            const toggle = card.querySelector('[data-product-toggle]');
            if (!body || !toggle) return;
            body.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            card.classList.toggle('is-open', open);
        }

        function refreshCard(card) {
            const price = Number(card.dataset.price || 0);
            const hppInput = field(card, 'hpp_amount', false);
            const packTypeInput = field(card, 'packaging_type', false);
            const packValueInput = field(card, 'packaging_value', false);
            const hpp = numberValue(hppInput);
            const packType = packTypeInput?.value || 'fixed';
            const packValue = numberValue(packValueInput) || 0;
            setPackagingInputMode(packValueInput, packType, false);
            const pack = packagingCost(price, packType, packValue);
            const total = (hpp || 0) + pack;
            const profit = price - total;
            const margin = price > 0 ? (profit / price) * 100 : null;

            const productUnit = card.querySelector('[data-pack-unit]');
            const productHelp = card.querySelector('[data-pack-help]');
            if (productUnit) productUnit.textContent = packType === 'percent' ? '%' : 'Rp';
            if (productHelp) {
                productHelp.textContent = packType === 'percent'
                    ? 'Persen dari harga jual.'
                    : 'Biaya kemasan per unit.';
            }

            const costEl = card.querySelector('[data-preview-cost]');
            const profitEl = card.querySelector('[data-preview-profit]');
            const marginEl = card.querySelector('[data-preview-margin]');
            if (costEl) costEl.textContent = currency.format(total);
            if (profitEl) {
                profitEl.textContent = currency.format(profit);
                profitEl.classList.toggle('negative', profit < 0);
            }
            if (marginEl) marginEl.textContent = margin === null ? '-' : `${margin.toFixed(1)}%`;

            const headHpp = card.querySelector('[data-head-hpp]');
            if (headHpp) headHpp.textContent = hpp === null ? 'Belum diisi' : currency.format(hpp);

            const variantRows = Array.from(card.querySelectorAll('[data-variant-row]'));
            variantRows.forEach(function (row) {
                const variantPrice = Number(row.dataset.price || price || 0);
                const variantHppInput = field(row, 'hpp_amount', true);
                const variantTypeInput = field(row, 'packaging_type', true);
                const variantValueInput = field(row, 'packaging_value', true);
                const variantHpp = numberValue(variantHppInput);
                const ownPackaging = Boolean(variantTypeInput?.value);
                const effectiveType = ownPackaging ? variantTypeInput.value : packType;
                const effectiveValue = ownPackaging ? (numberValue(variantValueInput) || 0) : packValue;
                const effectiveHpp = variantHpp === null ? (hpp || 0) : variantHpp;
                const effectivePack = packagingCost(variantPrice, effectiveType, effectiveValue);
                const effectiveCost = effectiveHpp + effectivePack;
                const variantProfit = variantPrice - effectiveCost;
                const variantMargin = variantPrice > 0 ? (variantProfit / variantPrice) * 100 : null;

                if (variantValueInput) {
                    setPackagingInputMode(variantValueInput, ownPackaging ? variantTypeInput.value : effectiveType, false);
                    variantValueInput.disabled = !ownPackaging;
                    variantValueInput.placeholder = ownPackaging ? '0' : 'Ikut default';
                }
                const unit = row.querySelector('[data-variant-pack-unit]');
                if (unit) unit.textContent = effectiveType === 'percent' ? '%' : 'Rp';
                const effectiveCostEl = row.querySelector('[data-variant-effective-cost]');
                const variantMarginEl = row.querySelector('[data-variant-margin]');
                if (effectiveCostEl) effectiveCostEl.textContent = currency.format(effectiveCost);
                if (variantMarginEl) {
                    variantMarginEl.textContent = variantMargin === null
                        ? 'Harga belum tersedia'
                        : `Margin ${variantMargin.toFixed(1)}%`;
                    variantMarginEl.classList.toggle('text-danger', variantProfit < 0);
                }
            });

            const complete = variantRows.length
                ? variantRows.every(row => numberValue(field(row, 'hpp_amount', true)) !== null || hpp !== null)
                : hpp !== null;
            const status = card.querySelector('[data-cost-status]');
            if (status) {
                status.classList.toggle('complete', complete);
                status.classList.toggle('missing', !complete);
                status.innerHTML = complete
                    ? '<i class="fas fa-circle-check"></i> Siap'
                    : '<i class="fas fa-triangle-exclamation"></i> Lengkapi';
            }
            card.classList.toggle('is-complete', complete);
            card.classList.toggle('is-missing', !complete);
        }

        function markDirty(card) {
            dirtyProducts.add(String(card.dataset.productId));
            card.classList.add('is-dirty');
            dirtyCount.textContent = String(dirtyProducts.size);
            saveBar.classList.add('show');
            document.body.style.paddingBottom = window.innerWidth < 768 ? '150px' : '88px';
        }

        function productPayload(card) {
            const productType = field(card, 'packaging_type', false)?.value || 'fixed';
            return {
                id: Number(card.dataset.productId),
                hpp_amount: numberValue(field(card, 'hpp_amount', false)),
                packaging_type: productType,
                packaging_value: numberValue(field(card, 'packaging_value', false)),
                variants: Array.from(card.querySelectorAll('[data-variant-row]')).map(function (row) {
                    const variantType = field(row, 'packaging_type', true)?.value || null;
                    return {
                        id: Number(row.dataset.variantId),
                        hpp_amount: numberValue(field(row, 'hpp_amount', true)),
                        packaging_type: variantType,
                        packaging_value: variantType ? numberValue(field(row, 'packaging_value', true)) : null,
                    };
                }),
            };
        }

        cards.forEach(function (card) {
            card.querySelector('[data-product-toggle]')?.addEventListener('click', function () {
                setOpen(card, !card.classList.contains('is-open'));
            });

            card.querySelectorAll('[data-product-field], [data-variant-field]').forEach(function (input) {
                const eventName = input.tagName === 'SELECT' ? 'change' : 'input';
                input.addEventListener(eventName, function () {
                    if (input.dataset.money === 'true') {
                        formatMoneyInput(input);
                    }
                    if (input.matches('[data-product-field="packaging_type"]')) {
                        setPackagingInputMode(field(card, 'packaging_value', false), input.value, true);
                    }
                    if (input.matches('[data-variant-field="packaging_type"]')) {
                        const valueInput = field(input.closest('[data-variant-row]'), 'packaging_value', true);
                        setPackagingInputMode(valueInput, input.value || 'fixed', true);
                    }
                    refreshCard(card);
                    markDirty(card);
                });
            });

            card.querySelector('img')?.addEventListener('error', function () {
                this.style.display = 'none';
            });
            refreshCard(card);
        });

        document.querySelector('[data-expand-all]')?.addEventListener('click', function () {
            cards.forEach(card => setOpen(card, true));
        });
        document.querySelector('[data-collapse-all]')?.addEventListener('click', function () {
            cards.forEach(card => setOpen(card, false));
        });
        document.querySelector('[data-discard-changes]')?.addEventListener('click', function () {
            if (!dirtyProducts.size || window.confirm('Batalkan semua perubahan yang belum disimpan?')) {
                submitting = true;
                window.location.reload();
            }
        });

        window.addEventListener('beforeunload', function (event) {
            if (!submitting && dirtyProducts.size) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            payload.value = JSON.stringify(cards.map(productPayload));
            submitting = true;
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            HTMLFormElement.prototype.submit.call(form);
        });
    });
})();
