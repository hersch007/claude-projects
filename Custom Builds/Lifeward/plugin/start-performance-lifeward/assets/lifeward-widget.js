/**
 * Drives the Lifeward landing page funnel. Thin renderer only — every reply,
 * button set, and form is decided server-side (see includes/rest-endpoints.php);
 * this file just posts the visitor's choice and renders whatever comes back.
 */
(function () {
    'use strict';

    var app = document.getElementById('sp-lifeward-app');
    if (!app || typeof SP_LIFEWARD === 'undefined') return;

    var bubble  = document.getElementById('sp-lifeward-bubble');
    var btnsBox = document.getElementById('sp-lifeward-buttons');
    var fldsBox = document.getElementById('sp-lifeward-fields');
    var errBox  = document.getElementById('sp-lifeward-error');
    var token   = '';
    var busy    = false;

    function setBubble(text, loading) {
        bubble.textContent = text;
        bubble.classList.toggle('sp-lifeward-loading', !!loading);
    }

    function clearError() { errBox.textContent = ''; }
    function showError(msg) { errBox.textContent = msg; }

    function setBusy(v) {
        busy = v;
        app.querySelectorAll('button, input, select').forEach(function (el) { el.disabled = v; });
    }

    function postStep(step, payload) {
        if (busy) return;
        setBusy(true);
        clearError();
        setBubble(bubble.textContent, true);

        var body = Object.assign({ token: token, step: step }, payload || {});

        fetch(SP_LIFEWARD.restUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': SP_LIFEWARD.nonce },
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
                if (res.data.token) token = res.data.token;
                render(res.data);
            })
            .catch(function () {
                setBusy(false);
                showError('Connection problem — please try again.');
                setBubble(bubble.textContent, false);
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
                el.className = 'sp-lifeward-btn' + (i === 0 ? ' sp-lifeward-btn-primary' : '');
                el.textContent = b.label;
                el.addEventListener('click', function () { postStep(b.id); });
                btnsBox.appendChild(el);
            });
        }

        if (data.fields) renderFields(data.fields);
    }

    function field(labelText, inputEl) {
        var wrap = document.createElement('div');
        wrap.className = 'sp-lifeward-field';
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

    function submitButton(text) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'sp-lifeward-btn sp-lifeward-btn-primary';
        b.style.marginTop = '4px';
        b.textContent = text;
        return b;
    }

    function renderFields(fields) {
        var form = document.createElement('div');

        if (fields.type === 'email_form') {
            var email = textInput('email', 'email', 'you@example.com');
            form.appendChild(field('Email address', email));
            var go = submitButton('Send it to me');
            go.addEventListener('click', function () {
                if (!email.value.trim()) { showError('Please enter an email address.'); return; }
                postStep('submit_email', { email: email.value.trim() });
            });
            form.appendChild(go);
        }

        if (fields.type === 'contact_form') {
            var name = textInput('name', 'text', 'Your name');
            var phone = textInput('phone', 'tel', '(555) 555-5555');
            form.appendChild(field('Name', name));
            form.appendChild(field('Phone number', phone));
            var goC = submitButton("I'm ready — call me");
            goC.addEventListener('click', function () {
                if (!name.value.trim() || !phone.value.trim()) { showError('Please fill in both fields.'); return; }
                postStep('submit_call_now', { name: name.value.trim(), phone: phone.value.trim() });
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
                postStep('submit_schedule', { name: sName.value.trim(), phone: sPhone.value.trim(), slot: select.value });
            });
            form.appendChild(goS);
        }

        fldsBox.appendChild(form);
    }

    var initialButtons = btnsBox.querySelectorAll('button[data-step]');
    initialButtons.forEach(function (b) {
        b.addEventListener('click', function () { postStep(b.getAttribute('data-step')); });
    });
})();
