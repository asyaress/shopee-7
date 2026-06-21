/**
 * CEO Chatbot — overlay Q&A preset, lazy bootstrap, friendly UX
 */
(function () {
    'use strict';

    const endpoints = window.__ceoChatEndpoints;
    const root = document.getElementById('ceoChatRoot');
    const fab = document.getElementById('ceoChatFab');
    const messages = document.getElementById('ceoChatMessages');
    const loadingEl = document.getElementById('ceoChatLoading');
    const loadingText = document.getElementById('ceoChatLoadingText');
    const chipsEl = document.getElementById('ceoChatChips');
    const form = document.getElementById('ceoChatForm');
    const input = document.getElementById('ceoChatInput');
    const sendBtn = document.getElementById('ceoChatSend');
    const starters = document.getElementById('ceoChatStarters');
    const titleEl = document.getElementById('ceoChatTitle');
    const subEl = document.getElementById('ceoChatSub');
    const avatarEl = document.getElementById('ceoChatAvatar');

    if (!root || !fab || !messages || !endpoints?.bootstrap) return;

    let cfg = null;
    let opened = false;
    let welcomed = false;
    let bootstrapping = false;
    let answering = false;
    let bootstrapPromise = null;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function pick(arr, fallback) {
        if (!arr?.length) return fallback;
        return arr[Math.floor(Math.random() * arr.length)];
    }

    function openChat() {
        opened = true;
        root.classList.add('ceo-chat-open');
        root.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ceo-chat-active');
        ensureBootstrap().then(() => {
            if (!welcomed) {
                welcomed = true;
                showWelcome();
            }
            setTimeout(() => input?.focus(), 200);
        });
    }

    function closeChat() {
        opened = false;
        root.classList.remove('ceo-chat-open');
        root.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ceo-chat-active');
    }

    function scrollBottom() {
        requestAnimationFrame(() => {
            messages.scrollTop = messages.scrollHeight;
        });
    }

    function formatText(text) {
        if (!text) return '';
        let html = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\n/g, '<br>');
        return html;
    }

    function appendBubble(role, html, extra, animate) {
        const wrap = document.createElement('div');
        wrap.className = 'ceo-chat-msg ceo-chat-msg--' + role + (animate ? ' ceo-chat-msg--in' : '');
        const bubble = document.createElement('div');
        bubble.className = 'ceo-chat-bubble';
        bubble.innerHTML = html;
        wrap.appendChild(bubble);
        if (extra) wrap.appendChild(extra);
        messages.appendChild(wrap);
        scrollBottom();
        if (animate) {
            requestAnimationFrame(() => wrap.classList.add('ceo-chat-msg--visible'));
        }
        return wrap;
    }

    function userMessage(text) {
        appendBubble('user', formatText(text), null, true);
        if (starters) starters.classList.add('ceo-chat-starters--dim');
    }

    function showTyping(label) {
        const wrap = appendBubble('bot', '', null, true);
        const bubble = wrap.querySelector('.ceo-chat-bubble');
        bubble.innerHTML =
            '<span class="ceo-chat-thinking-label">' + formatText(label || 'Sedang menyiapkan…') + '</span>' +
            '<span class="ceo-chat-typing"><span></span><span></span><span></span></span>';
        wrap.classList.add('ceo-chat-msg--typing');
        scrollBottom();
        return wrap;
    }

    function botMessage(text, link, opts) {
        opts = opts || {};
        const thinking = opts.thinking || pick(cfg?.personality?.thinking, 'Sebentar ya…');
        const ack = opts.ack;
        const delay = Math.min(1800, 500 + (text?.length || 0) * 6);

        return new Promise((resolve) => {
            const typing = showTyping(thinking);
            avatarEl?.classList.add('ceo-chat-avatar--pulse');

            setTimeout(() => {
                typing.remove();
                avatarEl?.classList.remove('ceo-chat-avatar--pulse');

                let body = '';
                if (ack) {
                    body += '<p class="ceo-chat-ack">' + formatText(ack) + '</p>';
                }
                body += formatText(text);

                const extra = document.createElement('div');
                extra.className = 'ceo-chat-msg-actions';
                if (link?.url) {
                    const a = document.createElement('a');
                    a.href = link.url;
                    a.className = 'ceo-chat-link-btn';
                    a.innerHTML = '<i class="fas fa-arrow-right"></i> ' + (link.label || 'Buka halaman');
                    extra.appendChild(a);
                }

                appendBubble('bot', body, link?.url ? extra : null, true);
                resolve();
            }, delay);
        });
    }

    function setLoading(active, text) {
        if (!loadingEl) return;
        loadingEl.style.display = active ? 'flex' : 'none';
        if (text && loadingText) loadingText.textContent = text;
    }

    function enableInput() {
        if (input) {
            input.disabled = false;
            input.placeholder = cfg?.bot?.placeholder || 'Ketik pertanyaan…';
        }
        if (sendBtn) sendBtn.disabled = false;
        if (starters) starters.classList.remove('ceo-chat-starters--hidden');
    }

    function ensureBootstrap() {
        if (cfg) return Promise.resolve(cfg);
        if (bootstrapPromise) return bootstrapPromise;

        bootstrapping = true;
        setLoading(true, cfg?.bot?.loading || 'Menyiapkan asisten…');
        const dataTimer = setTimeout(() => {
            if (loadingText) loadingText.textContent = 'Membaca ringkasan toko…';
        }, 700);

        bootstrapPromise = fetch(endpoints.bootstrap, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((r) => {
                if (!r.ok) throw new Error('bootstrap failed');
                return r.json();
            })
            .then((data) => {
                clearTimeout(dataTimer);
                cfg = data;
                if (titleEl) titleEl.textContent = cfg.bot?.name || 'Asisten CEO';
                if (subEl) subEl.textContent = cfg.bot?.subtitle || 'Panduan Shopee Profit Hub';
                buildChips();
                enableInput();
                setLoading(false);
                bootstrapping = false;
                return cfg;
            })
            .catch(() => {
                clearTimeout(dataTimer);
                bootstrapping = false;
                setLoading(false);
                cfg = { bot: {}, personality: {}, faqs: [], quick_starters: [] };
                enableInput();
                appendBubble('bot', formatText('Maaf, asisten belum bisa dimuat. Coba refresh halaman ya.'), null, true);
                return cfg;
            });

        return bootstrapPromise;
    }

    function showWelcome() {
        const snap = cfg?.snapshot;
        let welcome = cfg?.bot?.welcome || 'Halo! Ada yang bisa dibantu?';

        if (sessionStorage.getItem('ceo_chat_welcomed')) {
            welcome = cfg?.bot?.welcome_back || welcome;
        } else {
            sessionStorage.setItem('ceo_chat_welcomed', '1');
        }

        botMessage(welcome).then(() => {
            if (snap && typeof snap.net_profit === 'number') {
                const profitWord = snap.profit_positive ? 'positif' : 'perlu perhatian';
                let hint =
                    '📌 **Ringkas bulan ini:** laba bersih **Rp ' +
                    new Intl.NumberFormat('id-ID').format(snap.net_profit) +
                    '** (' + profitWord + '), skor **' + snap.health_score + '/100**.';
                if (snap.urgent_count > 0) {
                    hint += ' Ada **' + snap.urgent_count + ' urgent** — tanya "Apa arti urgent?" ya.';
                }
                appendBubble('bot', formatText(hint), null, true);
            }
        });
    }

    function buildChips() {
        if (!chipsEl) return;
        chipsEl.innerHTML = '';
        const list = cfg?.quick_starters || [];
        list.forEach((q) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ceo-chat-chip';
            btn.textContent = q;
            btn.addEventListener('click', () => ask(q));
            chipsEl.appendChild(btn);
        });
    }

    function addFollowUpChips(questions) {
        if (!questions?.length || !chipsEl) return;
        questions.forEach((q) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ceo-chat-chip ceo-chat-chip--follow';
            btn.textContent = '→ ' + q;
            btn.addEventListener('click', () => ask(q));
            chipsEl.appendChild(btn);
        });
        if (starters) starters.classList.remove('ceo-chat-starters--dim');
    }

    function addSuggestionChips(questions) {
        if (!questions?.length) return;
        questions.forEach((q) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ceo-chat-chip ceo-chat-chip--suggest';
            btn.textContent = q;
            btn.addEventListener('click', () => ask(q));
            chipsEl?.appendChild(btn);
        });
    }

    function ask(question) {
        if (answering || !question?.trim()) return;
        answering = true;
        if (sendBtn) sendBtn.classList.add('ceo-chat-send--loading');
        userMessage(question);

        fetch(endpoints.ask, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ question }),
        })
            .then((r) => r.json())
            .then((res) => {
                if (!res.ok) throw new Error('ask failed');
                return botMessage(res.answer, res.link, {
                    ack: res.ack,
                    thinking: res.thinking,
                }).then(() => {
                    if (res.matched && res.follow_ups?.length) {
                        addFollowUpChips(res.follow_ups);
                    }
                    if (!res.matched) {
                        if (res.suggestions?.length) addSuggestionChips(res.suggestions);
                        if (res.snapshot_hint) {
                            appendBubble('bot', formatText(res.snapshot_hint), null, true);
                        }
                    }
                    const thanks = pick(cfg?.personality?.thanks, '');
                    if (thanks && res.matched && Math.random() > 0.6) {
                        setTimeout(() => appendBubble('bot', formatText(thanks), null, true), 400);
                    }
                });
            })
            .catch(() => {
                return botMessage(
                    cfg?.bot?.fallback || 'Maaf, ada gangguan koneksi. Coba lagi sebentar ya.',
                    null,
                    { ack: 'Ups, sepertinya ada kendala teknis.' }
                );
            })
            .finally(() => {
                answering = false;
                sendBtn?.classList.remove('ceo-chat-send--loading');
            });
    }

    fab.addEventListener('click', () => (opened ? closeChat() : openChat()));

    root.querySelectorAll('[data-ceo-chat-close]').forEach((el) => {
        el.addEventListener('click', closeChat);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && opened) closeChat();
    });

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        const val = (input?.value || '').trim();
        if (!val || bootstrapping) return;
        input.value = '';
        ensureBootstrap().then(() => ask(val));
    });

    window.CeoChat = {
        open: openChat,
        close: closeChat,
        ask: (q) => ensureBootstrap().then(() => ask(q)),
    };
})();
