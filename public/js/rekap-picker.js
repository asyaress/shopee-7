(function () {
    'use strict';

    const form = document.getElementById('rekapCompareForm');
    if (!form) return;

    const grid = form.querySelector('.rekap-month-grid');
    const countEl = form.querySelector('[data-rekap-count]');

    function updateCount() {
        if (!countEl || !grid) return;
        const n = grid.querySelectorAll('input[type=checkbox]:checked').length;
        countEl.textContent = n + ' bulan dipilih';
    }

    function setChecked(keys) {
        if (!grid) return;
        grid.querySelectorAll('input[type=checkbox]').forEach(function (cb) {
            cb.checked = keys.includes(cb.value);
        });
        updateCount();
    }

    function lastN(n) {
        const all = Array.from(grid.querySelectorAll('input[type=checkbox]')).map(function (cb) {
            return cb.value;
        });
        return all.slice(-n);
    }

    form.querySelector('[data-rekap-select="last3"]')?.addEventListener('click', function () {
        setChecked(lastN(3));
    });

    form.querySelector('[data-rekap-select="last6"]')?.addEventListener('click', function () {
        setChecked(lastN(6));
    });

    form.querySelector('[data-rekap-clear]')?.addEventListener('click', function () {
        setChecked([]);
    });

    grid?.addEventListener('change', updateCount);

    form.addEventListener('submit', function (e) {
        const n = grid.querySelectorAll('input[type=checkbox]:checked').length;
        if (n < 2) {
            e.preventDefault();
            alert('Pilih minimal 2 bulan untuk mode bandingkan.');
        }
    });

    updateCount();
})();
