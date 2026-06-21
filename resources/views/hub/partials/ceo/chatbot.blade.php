@php
    $chatBootstrapUrl = route('monitoring.chatbot.bootstrap');
    $chatAskUrl = route('monitoring.chatbot.ask');
@endphp
<div id="ceoChatRoot" class="ceo-chat-root" aria-hidden="true" data-chat-bootstrap="{{ $chatBootstrapUrl }}">
    <div class="ceo-chat-backdrop" data-ceo-chat-close></div>

    <div class="ceo-chat-panel" role="dialog" aria-labelledby="ceoChatTitle" aria-modal="true">
        <header class="ceo-chat-header">
            <div class="ceo-chat-brand">
                <span class="ceo-chat-avatar" id="ceoChatAvatar"><i class="fas fa-headset"></i></span>
                <div>
                    <div id="ceoChatTitle" class="ceo-chat-name">Asisten CEO</div>
                    <div class="ceo-chat-sub" id="ceoChatSub">Memuat…</div>
                </div>
            </div>
            <button type="button" class="ceo-chat-close" data-ceo-chat-close aria-label="Tutup chat">
                <i class="fas fa-times"></i>
            </button>
        </header>

        <div class="ceo-chat-messages" id="ceoChatMessages" aria-live="polite">
            <div class="ceo-chat-loading" id="ceoChatLoading">
                <div class="ceo-chat-loading-avatar"></div>
                <div class="ceo-chat-loading-lines">
                    <span></span><span></span><span></span>
                </div>
                <p class="ceo-chat-loading-text" id="ceoChatLoadingText">Menyiapkan asisten…</p>
            </div>
        </div>

        <div class="ceo-chat-starters ceo-chat-starters--hidden" id="ceoChatStarters">
            <div class="ceo-chat-starters-label">Pertanyaan populer</div>
            <div class="ceo-chat-chips" id="ceoChatChips"></div>
        </div>

        <footer class="ceo-chat-footer">
            <form id="ceoChatForm" class="ceo-chat-form" autocomplete="off">
                <input type="text" id="ceoChatInput" class="ceo-chat-input"
                    placeholder="Ketik pertanyaan…"
                    maxlength="240" aria-label="Pertanyaan" disabled>
                <button type="submit" class="ceo-chat-send" id="ceoChatSend" aria-label="Kirim" disabled>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </footer>
    </div>

    <button type="button" class="ceo-chat-fab" id="ceoChatFab" aria-label="Buka asisten CEO">
        <i class="fas fa-comments"></i>
        <span class="ceo-chat-fab-label">Bantuan</span>
    </button>
</div>

<script>
window.__ceoChatEndpoints = @json(['bootstrap' => $chatBootstrapUrl, 'ask' => $chatAskUrl]);
</script>
