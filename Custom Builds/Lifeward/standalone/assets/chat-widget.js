/**
 * Floating "ask me anything" chat — companion to the guided funnel in app.js.
 * Shares the same session token (sessionStorage key lw_session_token) so a
 * lead captured here and a lead captured through the buttons are one record.
 */
(function () {
    'use strict';

    var launcher = document.getElementById('lw-chat-launcher');
    var panel    = document.getElementById('lw-chat-panel');
    var closeBtn = document.getElementById('lw-chat-close');
    var openLink = document.getElementById('lw-chat-open');
    var thread   = document.getElementById('lw-chat-thread');
    var input    = document.getElementById('lw-chat-input');
    var sendBtn  = document.getElementById('lw-chat-send');

    if (!launcher || !panel) return;

    var busy = false;

    function openPanel() {
        panel.hidden = false;
        launcher.classList.add('lw-chat-launcher-open');
        input.focus();
    }
    function closePanel() {
        panel.hidden = true;
        launcher.classList.remove('lw-chat-launcher-open');
    }
    function togglePanel() { panel.hidden ? openPanel() : closePanel(); }

    launcher.addEventListener('click', togglePanel);
    launcher.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); togglePanel(); } });
    closeBtn.addEventListener('click', closePanel);
    if (openLink) openLink.addEventListener('click', openPanel);

    /** The 4 real Lifeward products Rachel can mention — matched longest-alias-first
     *  so "ReWalk Personal Exoskeleton" isn't cut short by the plain "ReWalk" match. */
    var PRODUCT_LINK_PATTERN = /(ReWalk Personal Exoskeleton|ReWalk|ReStore Exo-Suit|ReStore|MYOLYN FES Cycling|MYOLYN|AlterG Anti-Gravity Systems|AlterG)/gi;
    function productUrlFor(name) {
        var n = name.toLowerCase();
        if (n.indexOf('rewalk') === 0)  return 'https://golifeward.com/products/rewalkpersonal-exoskeleton/';
        if (n.indexOf('restore') === 0) return 'https://golifeward.com/products/restore-exo-suit/';
        if (n.indexOf('myolyn') === 0)  return 'https://golifeward.com/products/myolyn-fes-cycling/';
        if (n.indexOf('alterg') === 0)  return 'https://golifeward.com/products/alterg-anti-gravity-systems/';
        return null;
    }

    /** Turns recognized product names into real links, as DOM nodes (never raw
     *  HTML) so nothing from the model's own text can be injected as markup. */
    function linkifyProducts(text) {
        var frag = document.createDocumentFragment();
        var lastIndex = 0, m;
        PRODUCT_LINK_PATTERN.lastIndex = 0;
        while ((m = PRODUCT_LINK_PATTERN.exec(text)) !== null) {
            if (m.index > lastIndex) frag.appendChild(document.createTextNode(text.slice(lastIndex, m.index)));
            var a = document.createElement('a');
            a.href = productUrlFor(m[0]);
            a.target = '_blank';
            a.rel = 'noopener';
            a.className = 'lw-chat-link';
            a.textContent = m[0];
            frag.appendChild(a);
            lastIndex = PRODUCT_LINK_PATTERN.lastIndex;
        }
        frag.appendChild(document.createTextNode(text.slice(lastIndex)));
        return frag;
    }

    function addMessage(text, role) {
        var el = document.createElement('div');
        el.className = 'lw-chat-msg lw-chat-msg-' + role;
        if (role.indexOf('assistant') === 0) {
            el.appendChild(linkifyProducts(text));
        } else {
            el.textContent = text;
        }
        thread.appendChild(el);
        thread.scrollTop = thread.scrollHeight;
        return el;
    }

    /** Strips the light markdown Rachel sometimes uses (bold, bullet dashes) since
     *  chat bubbles render as plain text/links, not a markdown renderer. */
    function cleanMarkdown(text) {
        return String(text || '')
            .replace(/\*\*(.*?)\*\*/g, '$1')
            .replace(/^[-*]\s+/gm, '• ');
    }

    /** Splits a reply into small, readable chat-bubble-sized chunks: prefer the
     *  model's own paragraph breaks, otherwise one sentence per bubble so even a
     *  short 2-3 sentence reply reads as a few quick beats, not one dense block. */
    function splitIntoChunks(text) {
        text = cleanMarkdown(text).trim();
        if (!text) return [];
        var paras = text.split(/\n\s*\n+/).map(function (s) { return s.trim(); }).filter(Boolean);
        if (paras.length > 1) return paras;
        var sentences = (text.match(/[^.!?]+[.!?]+(\s+|$)/g) || [text]).map(function (s) { return s.trim(); }).filter(Boolean);
        return sentences.length ? sentences : [text];
    }

    function addAssistantReply(text, onDone) {
        var chunks = splitIntoChunks(text);
        var i = 0;
        function next() {
            if (i >= chunks.length) { if (onDone) onDone(); return; }
            addMessage(chunks[i], 'assistant');
            i++;
            setTimeout(next, 350);
        }
        next();
    }

    function addButtonsRow(defs) {
        var wrap = document.createElement('div');
        wrap.className = 'lw-chat-buttons';
        defs.forEach(function (b, i) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'lw-btn' + (i === 0 ? ' lw-btn-primary' : ' lw-btn-teal');
            btn.textContent = b.label;
            btn.addEventListener('click', function () {
                closePanel();
                if (window.lwHandleButtonClick) window.lwHandleButtonClick(b.id);
            });
            wrap.appendChild(btn);
        });
        thread.appendChild(wrap);
        thread.scrollTop = thread.scrollHeight;
    }

    function send() {
        var text = input.value.trim();
        if (!text || busy) return;
        busy = true;
        input.value = '';
        sendBtn.disabled = true;
        addMessage(text, 'user');
        var typing = addMessage('...', 'assistant lw-chat-typing');

        fetch('chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: sessionStorage.getItem('lw_session_token') || '', message: text })
        })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (res) {
                busy = false;
                sendBtn.disabled = false;
                typing.remove();
                if (!res.ok || !res.data.ok) {
                    addMessage((res.data && res.data.message) || 'Something went wrong — please try again.', 'assistant');
                    return;
                }
                if (res.data.token) sessionStorage.setItem('lw_session_token', res.data.token);
                addAssistantReply(res.data.reply, function () {
                    if (Array.isArray(res.data.buttons) && res.data.buttons.length) addButtonsRow(res.data.buttons);
                });
            })
            .catch(function () {
                busy = false;
                sendBtn.disabled = false;
                typing.remove();
                addMessage('Connection problem — please try again.', 'assistant');
            });
    }

    sendBtn.addEventListener('click', send);
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') send(); });

    setTimeout(openPanel, 6000);
})();
