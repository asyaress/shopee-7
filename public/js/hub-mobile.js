(function () {
    'use strict';

    const body = document.body;
    const sidebar = document.getElementById('hubSidebar');
    const toggle = document.getElementById('hubSidebarToggle');
    let backdrop = document.getElementById('hubSidebarBackdrop');

    if (!backdrop && sidebar) {
        backdrop = document.createElement('div');
        backdrop.id = 'hubSidebarBackdrop';
        backdrop.className = 'hub-sidebar-backdrop';
        backdrop.setAttribute('aria-hidden', 'true');
        sidebar.parentElement?.insertBefore(backdrop, sidebar);
    }

    function closeSidebar() {
        body.classList.remove('hub-sidebar-open');
    }

    function openSidebar() {
        if (window.matchMedia('(min-width: 992px)').matches) return;
        body.classList.add('hub-sidebar-open');
    }

    function toggleSidebar() {
        if (body.classList.contains('hub-sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    toggle?.addEventListener('click', toggleSidebar);
    backdrop?.addEventListener('click', closeSidebar);

    document.getElementById('hubBottomMenu')?.addEventListener('click', function (e) {
        e.preventDefault();
        openSidebar();
    });

    sidebar?.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.matchMedia('(max-width: 991.98px)').matches) {
                closeSidebar();
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });

    window.addEventListener('resize', function () {
        if (window.matchMedia('(min-width: 992px)').matches) {
            closeSidebar();
        }
    });

    /** Scroll active monitoring tab into view */
    const subnav = document.querySelector('.mon-subnav--scroll');
    if (subnav) {
        const active = subnav.querySelector('a.active');
        if (active) {
            requestAnimationFrame(function () {
                const left = active.offsetLeft - subnav.offsetWidth / 2 + active.offsetWidth / 2;
                subnav.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
            });
        }
    }

    /** Product search: table + mobile cards */
    const search = document.getElementById('productSearch');
    if (search) {
        search.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('#productTable tbody tr[data-search]').forEach(function (tr) {
                tr.style.display = !q || tr.dataset.search.includes(q) ? '' : 'none';
            });
            document.querySelectorAll('.hub-product-card-v2[data-search]').forEach(function (card) {
                card.style.display = !q || card.dataset.search.includes(q) ? '' : 'none';
            });
        });
    }
})();
