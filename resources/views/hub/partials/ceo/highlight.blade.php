@if(!empty($ceoGuide['highlights']))
<div id="ceoGuideOverlay" class="ceo-guide-overlay" hidden aria-hidden="true">
    <div class="ceo-guide-backdrop"></div>
    <div class="ceo-guide-spotlight" hidden></div>
    <div class="ceo-guide-card" role="dialog" aria-labelledby="ceoGuideTitle" aria-modal="true">
        <div class="ceo-guide-progress"><span data-ceo-step-cur>1</span> / <span data-ceo-step-total>{{ count($ceoGuide['highlights']) }}</span></div>
        <h2 id="ceoGuideTitle" class="ceo-guide-title" data-ceo-guide-title></h2>
        <p class="ceo-guide-body" data-ceo-guide-body></p>
        <div class="ceo-guide-actions">
            <button type="button" class="ceo-guide-skip" data-ceo-guide-skip>Lewati</button>
            <button type="button" class="hub-btn hub-btn-sm hub-btn-outline" data-ceo-guide-prev hidden>← Sebelumnya</button>
            <button type="button" class="hub-btn hub-btn-sm hub-btn-primary" data-ceo-guide-next>Lanjut →</button>
        </div>
    </div>
</div>
<script>
window.__ceoGuide = @json([
    'pageId' => $ceoGuide['id'],
    'steps' => $ceoGuide['highlights'],
]);
</script>
@endif
