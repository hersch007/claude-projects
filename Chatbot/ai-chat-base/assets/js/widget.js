(function () {
    'use strict';

    var cfg       = window.aichatData || {};
    var ajaxUrl   = cfg.ajaxUrl   || '';
    var nonce     = cfg.nonce     || '';
    var sessionId = cfg.sessionId || '';
    var botName   = cfg.botName   || 'Bot';
    var greeting  = cfg.greeting  || "Hey! I'm here to help. What can I do for you today?";
    var chips     = cfg.chips     || [];

    var history     = [];
    var pendingPhoto = null;
    var nudgeShown  = false;

    /* ── DOM refs ── */
    var launcher    = document.getElementById('ac-launcher');
    var panel       = document.getElementById('ac-panel');
    var closeBtn    = document.getElementById('ac-close');
    var body        = document.getElementById('ac-body');
    var input       = document.getElementById('ac-input');
    var sendBtn     = document.getElementById('ac-send');
    var photoBtn    = document.getElementById('ac-photo-btn');
    var photoInput  = document.getElementById('ac-photo-input');
    var nudge       = document.getElementById('ac-nudge');
    var nudgeClose  = document.getElementById('ac-nudge-close');

    if ( ! launcher ) return;

    /* ── Timestamps ── */
    function formatTimeAgo(ts) {
        var diff = Math.floor((Date.now() - ts) / 1000);
        if (diff < 60)   return 'Just now';
        if (diff < 120)  return '1 min ago';
        if (diff < 3600) return Math.floor(diff / 60) + ' mins ago';
        var d = new Date(ts), h = d.getHours(), m = d.getMinutes();
        return (h % 12 || 12) + ':' + (m < 10 ? '0' : '') + m + (h < 12 ? ' am' : ' pm');
    }
    setInterval(function () {
        body.querySelectorAll('.ac-timestamp[data-ts]').forEach(function (el) {
            el.textContent = formatTimeAgo(parseInt(el.getAttribute('data-ts'), 10));
        });
    }, 30000);

    /* ── Panel open / close ── */
    launcher.addEventListener('click', function () {
        panel.classList.add('open');
        input.focus();
        if ( nudge ) nudge.style.display = 'none';
        if ( body.childElementCount === 0 ) showStartScreen();
    });

    closeBtn.addEventListener('click', function () {
        panel.classList.remove('open');
    });

    /* ── Proactive nudge ── */
    if ( nudge && ! sessionStorage.getItem('ac_nudge_shown') ) {
        setTimeout(triggerNudge, 45000);
        window.addEventListener('scroll', function onScroll() {
            if ((window.scrollY + window.innerHeight) / document.body.scrollHeight >= 0.6) {
                triggerNudge();
                window.removeEventListener('scroll', onScroll);
            }
        });
    }
    function triggerNudge() {
        if (nudgeShown || sessionStorage.getItem('ac_nudge_shown')) return;
        nudgeShown = true;
        sessionStorage.setItem('ac_nudge_shown', '1');
        nudge.style.display = 'block';
    }
    if (nudgeClose) nudgeClose.addEventListener('click', function () { nudge.style.display = 'none'; });

    /* ── Bubble renderer ── */
    function appendMessage(text, role) {
        var row    = document.createElement('div');
        row.className = 'ac-row ' + role;

        var bubble = document.createElement('div');
        bubble.className = 'ac-bubble';

        if (role === 'ai') {
            bubble.innerHTML = linkifyPhones(escapeHtml(text).replace(/\n/g, '<br>'));
        } else {
            bubble.textContent = text;
        }

        var ts = document.createElement('div');
        ts.className = 'ac-timestamp';
        ts.setAttribute('data-ts', Date.now());
        ts.textContent = 'Just now';

        row.appendChild(bubble);
        row.appendChild(ts);
        body.appendChild(row);
        body.scrollTop = body.scrollHeight;
        return bubble;
    }

    function escapeHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function linkifyPhones(text) {
        return text.replace(/(\(?\d{3}\)?[\s.\-]?\d{3}[\s.\-]?\d{4})/g, function (m) {
            var digits = m.replace(/\D/g, '');
            return '<a href="tel:' + digits + '">' + m + '</a>';
        });
    }

    /* ── Typing indicator ── */
    function showTyping() {
        var row = document.createElement('div');
        row.className = 'ac-row ai';
        row.id = 'ac-typing-row';
        var bubble = document.createElement('div');
        bubble.className = 'ac-bubble ac-typing-bubble';
        for (var i = 0; i < 3; i++) {
            var dot = document.createElement('span');
            dot.className = 'ac-typing-dot';
            bubble.appendChild(dot);
        }
        row.appendChild(bubble);
        body.appendChild(row);
        body.scrollTop = body.scrollHeight;
    }
    function hideTyping() {
        var row = document.getElementById('ac-typing-row');
        if (row) row.remove();
    }

    /* ── Start screen ── */
    function showStartScreen() {
        var wrap = document.createElement('div');
        wrap.id = 'ac-start';

        var greetRow = document.createElement('div');
        greetRow.className = 'ac-row ai';
        var greetBubble = document.createElement('div');
        greetBubble.className = 'ac-bubble';
        greetBubble.innerHTML = linkifyPhones(escapeHtml(greeting));
        greetRow.appendChild(greetBubble);
        wrap.appendChild(greetRow);

        if (chips.length) {
            var chipGrid = document.createElement('div');
            chipGrid.className = 'ac-chips';
            chips.forEach(function (label) {
                var btn = document.createElement('button');
                btn.className = 'ac-chip';
                btn.textContent = label;
                btn.addEventListener('click', function () {
                    wrap.remove();
                    sendMessage(label, true);
                });
                chipGrid.appendChild(btn);
            });
            wrap.appendChild(chipGrid);
        }

        body.appendChild(wrap);
    }

    /* ── Send message ── */
    function sendMessage(forcedText, fromChip) {
        var text = fromChip ? forcedText : input.value.trim();
        if ( ! text && ! pendingPhoto ) return;

        var startScreen = document.getElementById('ac-start');
        if (startScreen) startScreen.remove();

        if ( ! fromChip ) input.value = '';
        autoResizeInput();

        appendMessage(text || '[Photo attached]', 'user');
        history.push({ role: 'user', content: text || '[Photo attached]' });

        showTyping();

        var formData = new FormData();
        formData.append('action',  'aichat_message');
        formData.append('nonce',   nonce);
        formData.append('session', sessionId);
        formData.append('history', JSON.stringify(history));

        if (pendingPhoto) {
            formData.append('image_data', pendingPhoto.base64);
            formData.append('image_type', pendingPhoto.mimeType);
            clearPendingPhoto();
        }

        fetch(ajaxUrl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                hideTyping();
                if (data.success && data.data && data.data.reply) {
                    var reply = data.data.reply;
                    appendMessage(reply, 'ai');
                    history.push({ role: 'assistant', content: reply });
                } else {
                    appendMessage('Something went wrong. Please try again.', 'ai');
                }
            })
            .catch(function () {
                hideTyping();
                appendMessage('Connection error. Please try again.', 'ai');
            });
    }

    sendBtn.addEventListener('click', function () { sendMessage(); });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && ! e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    input.addEventListener('input', autoResizeInput);

    function autoResizeInput() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 100) + 'px';
    }

    /* ── Photo upload ── */
    photoBtn.addEventListener('click', function () { photoInput.click(); });

    photoInput.addEventListener('change', function () {
        var file = photoInput.files[0];
        if (!file) return;
        photoInput.value = '';

        var reader = new FileReader();
        reader.onload = function (e) {
            var dataUrl   = e.target.result;
            var mimeType  = file.type || 'image/jpeg';
            var base64    = dataUrl.split(',')[1];

            var existing = document.getElementById('ac-photo-preview');
            if (existing) existing.remove();

            var preview = document.createElement('div');
            preview.id = 'ac-photo-preview';
            var img = document.createElement('img');
            img.src = dataUrl;
            var removeBtn = document.createElement('button');
            removeBtn.id = 'ac-photo-remove';
            removeBtn.textContent = '✕ Remove photo';
            removeBtn.addEventListener('click', clearPendingPhoto);

            preview.appendChild(img);
            preview.appendChild(removeBtn);

            var inputWrap = document.getElementById('ac-input-wrap');
            panel.insertBefore(preview, inputWrap);

            pendingPhoto = { base64: base64, mimeType: mimeType };
            input.placeholder = 'What are you seeing in this photo?';
            setTimeout(function () { input.focus(); }, 100);
        };
        reader.readAsDataURL(file);
    });

    function clearPendingPhoto() {
        pendingPhoto = null;
        var prev = document.getElementById('ac-photo-preview');
        if (prev) prev.remove();
        input.placeholder = 'Type your message...';
    }

})();
