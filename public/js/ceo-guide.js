(function () {
    'use strict';

    var cfg = window.__ceoGuide;
    if (!cfg || !cfg.steps || !cfg.steps.length) return;

    var storageKey = 'ceo_guide_done_' + cfg.pageId;
    var overlay = document.getElementById('ceoGuideOverlay');
    if (!overlay) return;

    var backdrop = overlay.querySelector('.ceo-guide-backdrop');
    var spotlight = overlay.querySelector('.ceo-guide-spotlight');
    var card = overlay.querySelector('.ceo-guide-card');
    var titleEl = overlay.querySelector('[data-ceo-guide-title]');
    var bodyEl = overlay.querySelector('[data-ceo-guide-body]');
    var curEl = overlay.querySelector('[data-ceo-step-cur]');
    var btnNext = overlay.querySelector('[data-ceo-guide-next]');
    var btnPrev = overlay.querySelector('[data-ceo-guide-prev]');
    var btnSkip = overlay.querySelector('[data-ceo-guide-skip]');
    var idx = 0;

    function isDone() {
        try { return localStorage.getItem(storageKey) === '1'; } catch (e) { return false; }
    }

    function markDone() {
        try { localStorage.setItem(storageKey, '1'); } catch (e) {}
    }

    function showStep(i) {
        idx = i;
        var step = cfg.steps[i];
        if (!step) return;

        titleEl.textContent = step.title || '';
        bodyEl.textContent = step.body || '';
        curEl.textContent = String(i + 1);

        btnPrev.hidden = i === 0;
        btnNext.textContent = i >= cfg.steps.length - 1 ? 'Selesai ✓' : 'Lanjut →';

        var target = step.target ? document.querySelector(step.target) : null;
        if (target && spotlight) {
            var r = target.getBoundingClientRect();
            var pad = 8;
            spotlight.hidden = false;
            spotlight.style.top = (r.top - pad + window.scrollY) + 'px';
            spotlight.style.left = (r.left - pad) + 'px';
            spotlight.style.width = (r.width + pad * 2) + 'px';
            spotlight.style.height = (r.height + pad * 2) + 'px';

            var cardRect = card.getBoundingClientRect();
            var below = r.bottom + 16 + cardRect.height < window.innerHeight;
            if (below) {
                card.style.top = (r.bottom + 16 + window.scrollY) + 'px';
                card.style.left = Math.max(16, Math.min(r.left, window.innerWidth - cardRect.width - 16)) + 'px';
                card.style.transform = 'none';
            } else {
                card.style.top = '50%';
                card.style.left = '50%';
                card.style.transform = 'translate(-50%, -50%)';
            }
        } else if (spotlight) {
            spotlight.hidden = true;
            card.style.top = '50%';
            card.style.left = '50%';
            card.style.transform = 'translate(-50%, -50%)';
        }
    }

    function open(force) {
        if (!force && isDone()) return;
        overlay.hidden = false;
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ceo-guide-open');
        showStep(0);
    }

    function close(save) {
        overlay.hidden = true;
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ceo-guide-open');
        if (spotlight) spotlight.hidden = true;
        if (save) markDone();
    }

    btnNext.addEventListener('click', function () {
        if (idx >= cfg.steps.length - 1) close(true);
        else showStep(idx + 1);
    });

    btnPrev.addEventListener('click', function () {
        if (idx > 0) showStep(idx - 1);
    });

    btnSkip.addEventListener('click', function () { close(true); });

    backdrop.addEventListener('click', function () { close(true); });

    document.querySelectorAll('[data-ceo-reopen]').forEach(function (btn) {
        btn.addEventListener('click', function () { open(true); });
    });

    document.addEventListener('keydown', function (e) {
        if (overlay.hidden) return;
        if (e.key === 'Escape') close(true);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { setTimeout(function () { open(false); }, 400); });
    } else {
        setTimeout(function () { open(false); }, 400);
    }
})();
