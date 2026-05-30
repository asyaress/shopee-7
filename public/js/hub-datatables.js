/**
 * Shopee Profit Hub — DataTables terpusat
 */
(function (global) {
    'use strict';

    const langId = {
        emptyTable: 'Tidak ada data',
        info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
        infoEmpty: 'Menampilkan 0 data',
        infoFiltered: '(disaring dari _MAX_ total)',
        lengthMenu: 'Tampilkan _MENU_',
        loadingRecords: 'Memuat…',
        processing: 'Memproses…',
        search: 'Cari:',
        zeroRecords: 'Tidak ada data yang cocok',
        paginate: {
            first: '« Awal',
            last: 'Akhir »',
            next: 'Berikutnya ›',
            previous: '‹ Sebelumnya',
        },
    };

    const defaults = {
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        autoWidth: false,
        language: langId,
        dom: '<"hub-dt-top"lf>rt<"hub-dt-bottom"ip>',
        drawCallback: function () {
            const paginate = document.querySelector('.hub-dt-wrap .dataTables_paginate')
                || document.querySelector('.hub-main .dataTables_paginate');
            if (!paginate) return;
            paginate.querySelectorAll('.page-link, .paginate_button').forEach(function (el) {
                const t = el.textContent.trim();
                if (t === 'Previous' || t === '‹') {
                    el.textContent = '‹ Sebelumnya';
                }
                if (t === 'Next' || t === '›') {
                    el.textContent = 'Berikutnya ›';
                }
            });
        },
    };

    function init(selector, options) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
            console.warn('[HubDataTable] jQuery DataTables tidak dimuat');
            return null;
        }
        const $el = jQuery(selector);
        if (!$el.length) return null;
        if (jQuery.fn.DataTable.isDataTable(selector)) {
            return $el.DataTable();
        }
        const wrap = $el.closest('.hub-card-body, .hub-dt-wrap');
        if (wrap.length && !wrap.hasClass('hub-dt-wrap')) {
            wrap.addClass('hub-dt-wrap');
        }
        return $el.DataTable(jQuery.extend(true, {}, defaults, options || {}));
    }

    global.HubDataTable = { init: init, defaults: defaults, langId: langId };
})(typeof window !== 'undefined' ? window : this);
