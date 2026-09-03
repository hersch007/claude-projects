/**
 * Thin renderer for the Lifeward funnel. All copy and branching is decided by
 * api.php; this file just posts the visitor's choice and renders the response.
 */
(function () {
    'use strict';

    var app = document.getElementById('lw-app');
    if (!app) return;

    var bubble  = document.getElementById('lw-bubble');
    var btnsBox = document.getElementById('lw-buttons');
    var fldsBox = document.getElementById('lw-fields');
    var errBox  = document.getElementById('lw-error');
    var token   = sessionStorage.getItem( 'lw_session_token' ) || '';
    var busy    = false;

    var modalOverlay = document.getElementById('lw-modal-overlay');
    var modalNext    = document.getElementById('lw-modal-next');
    var modalClose   = document.getElementById('lw-modal-close');

    function showCallNowModal(onNext) {
        if (!modalOverlay) { onNext(); return; }
        modalOverlay.classList.remove('lw-hidden');
        function cleanup() {
            modalOverlay.classList.add('lw-hidden');
            modalNext.removeEventListener('click', onNextClick);
            modalClose.removeEventListener('click', onCloseClick);
            modalOverlay.removeEventListener('click', onOverlayClick);
        }
        function onNextClick() { cleanup(); onNext(); }
        function onCloseClick() { cleanup(); }
        function onOverlayClick(e) { if (e.target === modalOverlay) cleanup(); }
        modalNext.addEventListener('click', onNextClick);
        modalClose.addEventListener('click', onCloseClick);
        modalOverlay.addEventListener('click', onOverlayClick);
    }

    function handleButtonClick(id) {
        if (id === 'call_now') { showCallNowModal(function () { postStep('call_now'); }); return; }
        postStep(id);
    }

    function setBubble(text, loading) {
        bubble.classList.remove('lw-hidden');
        var by = document.querySelector('.lw-bubble-by');
        if (by) by.classList.remove('lw-hidden');
        bubble.textContent = text;
        bubble.classList.toggle('lw-loading', !!loading);
    }

    function clearError() { errBox.textContent = ''; }
    function showError(msg) { errBox.textContent = msg; }

    function setBusy(v) {
        busy = v;
        app.querySelectorAll('button, input, select').forEach(function (el) { el.disabled = v; });
    }

    function postStep(step, payload, onDone) {
        if (busy) return;
        setBusy(true);
        clearError();
        setBubble(bubble.textContent, true);

        var body = Object.assign({ token: token, step: step }, payload || {});

        fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (res) {
                setBusy(false);
                if (!res.ok || !res.data.ok) {
                    showError((res.data && res.data.message) || 'Something went wrong — please try again.');
                    setBubble(bubble.textContent, false);
                    return;
                }
                if (res.data.token) { token = res.data.token; sessionStorage.setItem('lw_session_token', token); }
                render(res.data);
                if (onDone) onDone(res.data);
            })
            .catch(function () {
                setBusy(false);
                showError('Connection problem — please try again.');
                setBubble(bubble.textContent, false);
            });
    }

    function resetToFreshLanding() {
        sessionStorage.removeItem('lw_session_token');
        window.location.href = window.location.pathname;
    }

    function showTrackedModal(overlayId, doneId, populate) {
        var overlay = document.getElementById(overlayId);
        var doneBtn = document.getElementById(doneId);
        if (!overlay || !doneBtn) { resetToFreshLanding(); return; }

        populate();

        overlay.classList.remove('lw-hidden');
        function onDoneClick() {
            overlay.classList.add('lw-hidden');
            doneBtn.removeEventListener('click', onDoneClick);
            resetToFreshLanding();
        }
        doneBtn.addEventListener('click', onDoneClick);
    }

    function showCallMadeModal(payload) {
        showTrackedModal('lw-callmade-overlay', 'lw-callmade-done', function () {
            document.getElementById('lw-callmade-name').textContent = payload.name;
            document.getElementById('lw-callmade-phone').textContent = payload.phone;
            document.getElementById('lw-callmade-time').textContent = new Date().toLocaleString();
            var msgRow = document.getElementById('lw-callmade-message-row');
            if (payload.message) {
                document.getElementById('lw-callmade-message').textContent = payload.message;
                msgRow.style.display = '';
            } else {
                msgRow.style.display = 'none';
            }
        });
    }

    function formatClockTime(time24) {
        var parts = time24.split(':');
        var h = parseInt(parts[0], 10);
        var m = parts[1];
        var ampm = h >= 12 ? 'PM' : 'AM';
        var h12 = h % 12 === 0 ? 12 : h % 12;
        return h12 + ':' + m + ' ' + ampm + ' ET';
    }

    function showScheduledModal(payload, whenLabel) {
        showTrackedModal('lw-scheduled-overlay', 'lw-scheduled-done', function () {
            document.getElementById('lw-scheduled-name').textContent = payload.name;
            document.getElementById('lw-scheduled-phone').textContent = payload.phone;
            document.getElementById('lw-scheduled-when').textContent = whenLabel;
            var msgRow = document.getElementById('lw-scheduled-message-row');
            if (payload.message) {
                document.getElementById('lw-scheduled-message').textContent = payload.message;
                msgRow.style.display = '';
            } else {
                msgRow.style.display = 'none';
            }
        });
    }

    function showInfoSentModal(payload) {
        showTrackedModal('lw-infosent-overlay', 'lw-infosent-done', function () {
            document.getElementById('lw-infosent-name').textContent = payload.name;
            document.getElementById('lw-infosent-email').textContent = payload.email;
            var row = document.getElementById('lw-infosent-products-row');
            if (payload.productLabels && payload.productLabels.length) {
                document.getElementById('lw-infosent-products').textContent = payload.productLabels.join(', ');
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function render(data) {
        setBubble(data.reply || '', false);
        btnsBox.innerHTML = '';
        fldsBox.innerHTML = '';
        if (data.done) return;

        if (Array.isArray(data.buttons) && data.buttons.length) {
            data.buttons.forEach(function (b, i) {
                var el = document.createElement('button');
                el.type = 'button';
                el.className = 'lw-btn' + (i === 0 ? ' lw-btn-primary' : '');
                el.textContent = b.label;
                el.addEventListener('click', function () { handleButtonClick(b.id); });
                btnsBox.appendChild(el);
            });
        }

        if (data.fields) renderFields(data.fields);
    }

    function field(labelText, inputEl) {
        var wrap = document.createElement('div');
        wrap.className = 'lw-field';
        var label = document.createElement('label');
        label.textContent = labelText;
        wrap.appendChild(label);
        wrap.appendChild(inputEl);
        return wrap;
    }

    function textInput(name, type, placeholder) {
        var i = document.createElement('input');
        i.type = type || 'text';
        i.name = name;
        if (placeholder) i.placeholder = placeholder;
        return i;
    }

    function textareaInput(name, placeholder) {
        var t = document.createElement('textarea');
        t.name = name;
        t.rows = 3;
        if (placeholder) t.placeholder = placeholder;
        return t;
    }

    function submitButton(text) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'lw-btn lw-btn-primary';
        b.style.marginTop = '4px';
        b.textContent = text;
        return b;
    }

    function renderFields(fields) {
        var form = document.createElement('div');

        if (fields.type === 'info_form') {
            var iName = textInput('name', 'text', 'Your name');
            var iEmail = textInput('email', 'email', 'you@example.com');
            form.appendChild(field('Name', iName));
            form.appendChild(field('Email address', iEmail));

            var wrap = document.createElement('div');
            wrap.className = 'lw-field';
            var label = document.createElement('label');
            label.textContent = 'Which products are you interested in?';
            wrap.appendChild(label);

            var checks = [];
            (fields.products || []).forEach(function (p) {
                var row = document.createElement('div');
                row.className = 'lw-checkrow';
                var cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.value = p.id;
                cb.id = 'lw-prod-' + p.id;
                checks.push(cb);
                var cbLabel = document.createElement('label');
                cbLabel.setAttribute('for', cb.id);
                cbLabel.textContent = p.label;
                var learnMore = document.createElement('a');
                learnMore.href = p.url;
                learnMore.target = '_blank';
                learnMore.rel = 'noopener';
                learnMore.className = 'lw-learnmore';
                learnMore.textContent = 'learn more';
                row.appendChild(cb);
                row.appendChild(cbLabel);
                row.appendChild(learnMore);
                wrap.appendChild(row);
            });
            form.appendChild(wrap);

            var goI = submitButton('Send it to me');
            goI.addEventListener('click', function () {
                if (!iName.value.trim() || !iEmail.value.trim()) { showError('Please enter your name and email address.'); return; }
                var products = checks.filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
                var productLabels = (fields.products || []).filter(function (p) { return products.indexOf(p.id) !== -1; }).map(function (p) { return p.label; });
                var payload = { name: iName.value.trim(), email: iEmail.value.trim(), products: products };
                postStep('submit_info_request', payload, function (data) {
                    if (data.done) showInfoSentModal({ name: payload.name, email: payload.email, productLabels: productLabels });
                });
            });
            form.appendChild(goI);
        }

        if (fields.type === 'contact_form') {
            var name = textInput('name', 'text', 'Your name');
            var phone = textInput('phone', 'tel', '(555) 555-5555');
            var msg = textareaInput('message', "Anything you'd like our rep to know? (optional)");
            form.appendChild(field('Name', name));
            form.appendChild(field('Phone number', phone));
            form.appendChild(field('Message for our rep (optional)', msg));
            var goC = submitButton("I'm ready — call me");
            goC.addEventListener('click', function () {
                if (!name.value.trim() || !phone.value.trim()) { showError('Please fill in your name and phone number.'); return; }
                var payload = { name: name.value.trim(), phone: phone.value.trim(), message: msg.value.trim() };
                postStep('submit_call_now', payload, function (data) {
                    if (data.done) showCallMadeModal(payload);
                });
            });
            form.appendChild(goC);
        }

        if (fields.type === 'schedule_form') {
            var sName = textInput('name', 'text', 'Your name');
            var sPhone = textInput('phone', 'tel', '(555) 555-5555');
            var select = document.createElement('select');
            (fields.slots || []).forEach(function (s) {
                var opt = document.createElement('option');
                opt.value = s.value;
                opt.textContent = s.label;
                select.appendChild(opt);
            });
            form.appendChild(field('Name', sName));
            form.appendChild(field('Phone number', sPhone));
            form.appendChild(field('Preferred time (Eastern)', select));
            var goS = submitButton('Confirm callback time');
            goS.addEventListener('click', function () {
                if (!sName.value.trim() || !sPhone.value.trim() || !select.value) { showError('Please fill in every field.'); return; }
                var payload = { name: sName.value.trim(), phone: sPhone.value.trim(), slot: select.value };
                var whenLabel = select.options[select.selectedIndex].text;
                postStep('submit_schedule', payload, function (data) {
                    if (data.done) showScheduledModal(payload, whenLabel);
                });
            });
            form.appendChild(goS);
        }

        if (fields.type === 'specific_time_form') {
            var tName = textInput('name', 'text', 'Your name');
            var tPhone = textInput('phone', 'tel', '(555) 555-5555');
            var tDay = textInput('day', 'date', '');
            var tTime = textInput('time', 'time', '');
            var tMsg = textareaInput('message', "Anything you'd like our rep to know? (optional)");
            form.appendChild(field('Name', tName));
            form.appendChild(field('Phone number', tPhone));
            form.appendChild(field('Day', tDay));
            form.appendChild(field('Time (Eastern)', tTime));
            form.appendChild(field('Message for our rep (optional)', tMsg));
            var goT = submitButton('Confirm callback time');
            goT.addEventListener('click', function () {
                if (!tName.value.trim() || !tPhone.value.trim() || !tDay.value || !tTime.value) { showError('Please fill in your name, phone, day, and time.'); return; }
                var payload = { name: tName.value.trim(), phone: tPhone.value.trim(), day: tDay.value, time: tTime.value, message: tMsg.value.trim() };
                var whenLabel = tDay.value + ' at ' + formatClockTime(tTime.value);
                postStep('submit_specific_time', payload, function (data) {
                    if (data.done) showScheduledModal(payload, whenLabel);
                });
            });
            form.appendChild(goT);
        }

        fldsBox.appendChild(form);
    }

    btnsBox.querySelectorAll('button[data-step]').forEach(function (b) {
        b.addEventListener('click', function () { handleButtonClick(b.getAttribute('data-step')); });
    });

    // Exposed so the chat widget (chat-widget.js) can hand off into the exact
    // same funnel/modal logic when Rachel suggests one of the main buttons.
    window.lwHandleButtonClick = handleButtonClick;
})();
