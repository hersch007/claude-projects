(function () {
    "use strict";

    var launcher    = document.getElementById("la-launcher");
    var panel       = document.getElementById("la-panel");
    var closeBtn    = document.getElementById("la-close");
    var body        = document.getElementById("la-body");
    var input       = document.getElementById("la-input");
    var sendBtn     = document.getElementById("la-send");
    var dot         = document.getElementById("la-dot");
    var photoBtn    = document.getElementById("la-photo-btn");
    var photoInput  = document.getElementById("la-photo-input");

    var history          = [];
    var isLoading        = false;
    var started          = false;
    var pendingPhoto     = null; // { base64, mimeType, previewEl }
    var messageCount     = 0;
    var suppressPlanBtns = false; // true during quote flow to prevent recursive buttons

    var optionMeta = {
        "New Customer":              { icon: "🌱", sub: "Quotes, lawn evaluations, weeds, mosquitoes, and service recommendations." },
        "Existing Customer":         { icon: "🛠", sub: "Billing, account help, scheduling, service issues, and support." },
        "Lawn Question":             { icon: "?",  sub: "Ask about weeds, brown patches, grass types, bugs, or lawn problems." },
        "Weed Control":              { icon: "♣",  sub: "Help with broadleaf weeds, crabgrass, nutsedge, and more." },
        "Fertilization":             { icon: "▦",  sub: "Improve color, thickness, and seasonal lawn health." },
        "Mosquito Control":          { icon: "✣",  sub: "Reduce mosquitoes around patios, shade, shrubs, and standing water." },
        "Fire Ant Control":          { icon: "●",  sub: "Help with active mounds and recurring fire ant pressure." },
        "Get Pricing":               { icon: "$",  sub: "Start a custom quote request with Lawn Ace." },
        "Billing Question":          { icon: "$",  sub: "Payment, account, portal, and billing help." },
        "Schedule Service":          { icon: "◷",  sub: "Scheduling, rescheduling, or upcoming service questions." },
        "Service Issue":             { icon: "!",  sub: "Report weeds, lawn concerns, or a service problem." },
        "Account Help":              { icon: "☎",  sub: "Login, customer info, support, or account questions." },
        "Weeds":                     { icon: "♣",  sub: "Identify likely weed issues and next steps." },
        "Brown Patches":             { icon: "◌",  sub: "Narrow down stress, fungus, insects, or watering issues." },
        "Mosquitoes":                { icon: "✣",  sub: "Find likely breeding and resting areas." },
        "Grass Type Help":           { icon: "🌿", sub: "Bermuda, Zoysia, Fescue, Centipede, St. Augustine, and more." },
        "Other Lawn Question":       { icon: "?",  sub: "Ask Lawnie a general lawn care question." },
        "Essential Plan — $29/mo":   { icon: "🌱", sub: "Weed control + fertilization. Click to get your exact quote." },
        "Grow Plan — $39/mo":        { icon: "⭐", sub: "Most popular — guaranteed weed-free lawn year-round." },
        "Pro Plan — $49/mo":         { icon: "🛡", sub: "Complete protection — adds insect + fire ant control." }
    };

    /* ── Quote flow ─────────────────────────────────────────────────── */

    var quoteFlow = { active: false, plan: null, step: 0, data: {} };

    var quoteSteps = [
        { key: "name",    prompt: "What’s your full name?" },
        { key: "address", prompt: "Full street address for the lawn?" },
        { key: "email",   prompt: "Best email for follow-up?" },
        { key: "phone",   prompt: "And a phone number?" }
    ];

    function startQuoteFlow(planLabel) {
        quoteFlow.active = true;
        quoteFlow.plan   = planLabel;
        quoteFlow.step   = 0;
        quoteFlow.data   = {};
        suppressPlanBtns = true;
        appendMessage("ai", "Great choice. Just four quick questions and we’ll have your exact " + planLabel + " quote ready — no visit needed.\n\nWhat’s your full name?");
        suppressPlanBtns = false;
        input.focus();
    }

    function advanceQuoteFlow(text) {
        var stepDef = quoteSteps[quoteFlow.step];
        quoteFlow.data[stepDef.key] = text;
        quoteFlow.step++;

        // Show user bubble
        var userRow       = document.createElement("div");
        userRow.className = "la-row user";
        var userBubble       = document.createElement("div");
        userBubble.className = "la-bubble user";
        userBubble.textContent = text;
        var userTs       = document.createElement("div");
        userTs.className = "la-timestamp";
        userTs.setAttribute("data-ts", Date.now());
        userTs.textContent = "Just now";
        userRow.appendChild(userBubble);
        userRow.appendChild(userTs);
        body.appendChild(userRow);
        body.scrollTop = body.scrollHeight;

        if (quoteFlow.step < quoteSteps.length) {
            setTimeout(function () {
                suppressPlanBtns = true;
                appendMessage("ai", quoteSteps[quoteFlow.step].prompt);
                suppressPlanBtns = false;
            }, 350);
            return;
        }

        // All 4 collected — submit to AI for confirmation + lead capture
        quoteFlow.active = false;
        var d = quoteFlow.data;
        var submitMsg = "I'd like a quote for the " + quoteFlow.plan + ". " +
            "Name: " + d.name + ". Address: " + d.address + ". " +
            "Email: " + d.email + ". Phone: " + d.phone + ".";
        history.push({ role: "user", content: submitMsg });

        setLoading(true);
        var typingRow = createTypingIndicator();
        body.appendChild(typingRow);
        body.scrollTop = body.scrollHeight;

        var fd = new FormData();
        fd.append("action",     "lawnace_chat");
        fd.append("nonce",      window.lawnaceChat.nonce);
        fd.append("session_id", window.lawnaceChat.sessionId || ("session_" + Date.now()));
        fd.append("messages",   JSON.stringify(history));

        fetch(window.lawnaceChat.ajaxUrl, { method: "POST", credentials: "same-origin", body: fd })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var tr = document.getElementById("la-typing-row");
                if (tr) tr.remove();
                var reply = (data && data.success && data.data && data.data.reply)
                    ? data.data.reply
                    : "You’re all set, " + d.name + ". Someone from our team will follow up with your exact quote — no visit needed.";
                appendMessage("ai", reply);
                history.push({ role: "assistant", content: reply });
                if (data && data.data && data.data.session_id) {
                    window.lawnaceChat.sessionId = data.data.session_id;
                }
                playPing();
            })
            .catch(function () {
                var tr = document.getElementById("la-typing-row");
                if (tr) tr.remove();
                appendMessage("ai", "You’re all set, " + d.name + ". Someone from our team will follow up with your exact quote — no visit needed.");
            })
            .finally(function () { setLoading(false); input.focus(); });
    }

    /* ── Utilities ──────────────────────────────────────────────────── */

    function escapeHtml(text) {
        return String(text || "")
            .replace(/&/g,  "&amp;")
            .replace(/</g,  "&lt;")
            .replace(/>/g,  "&gt;")
            .replace(/"/g,  "&quot;")
            .replace(/'/g,  "&#039;");
    }

    function clearBody() {
        body.innerHTML = "";
    }

    /* ── Sound notification ─────────────────────────────────────────── */

    function playPing() {
        try {
            var ctx  = new (window.AudioContext || window.webkitAudioContext)();
            var osc  = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = "sine";
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(660, ctx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.18, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.5);
        } catch (e) {}
    }

    /* ── Typing indicator ───────────────────────────────────────────── */

    function createTypingIndicator() {
        var row        = document.createElement("div");
        row.className  = "la-row ai";
        row.id         = "la-typing-row";
        var bubble     = document.createElement("div");
        bubble.className = "la-bubble ai la-typing-indicator";
        bubble.innerHTML =
            '<span class="la-typing-dot"></span>' +
            '<span class="la-typing-dot"></span>' +
            '<span class="la-typing-dot"></span>';
        row.appendChild(bubble);
        return row;
    }

    /* ── Rating buttons ─────────────────────────────────────────────── */

    function addRatingButtons(bubbleEl, messageIndex) {
        var row        = document.createElement("div");
        row.className  = "la-rating-row";

        var up   = document.createElement("button");
        var down = document.createElement("button");
        up.className   = "la-rating-btn";
        down.className = "la-rating-btn";
        up.innerHTML   = "👍";
        down.innerHTML = "👎";

        function submitRating(val) {
            up.classList.add("rated");
            down.classList.add("rated");
            up.disabled   = true;
            down.disabled = true;
            row.innerHTML = '<span class="la-rating-thanks">Thanks for the feedback!</span>';

            if (!window.lawnaceChat) return;
            var fd = new FormData();
            fd.append("action",     "lawnace_rate");
            fd.append("nonce",      window.lawnaceChat.nonce);
            fd.append("session_id", window.lawnaceChat.sessionId || "");
            fd.append("rating",     val);
            fetch(window.lawnaceChat.ajaxUrl, {
                method: "POST", credentials: "same-origin", body: fd
            }).catch(function () {});
        }

        up.addEventListener("click",   function () { submitRating(1);  });
        down.addEventListener("click", function () { submitRating(-1); });

        row.appendChild(up);
        row.appendChild(down);
        bubbleEl.appendChild(row);
    }

    /* ── Photo upload ───────────────────────────────────────────────── */

    function clearPendingPhoto() {
        pendingPhoto = null;
        var prev = document.getElementById("la-photo-preview");
        if (prev) prev.remove();
        input.placeholder = "Type your message...";
    }

    photoBtn.addEventListener("click", function () {
        photoInput.value = "";
        photoInput.click();
    });

    photoInput.addEventListener("change", function () {
        var file = photoInput.files[0];
        if (!file) return;

        if (file.size > 4 * 1024 * 1024) {
            alert("Photo must be under 4MB. Please choose a smaller image.");
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            var dataUrl  = e.target.result;
            var base64   = dataUrl.split(",")[1];
            var mimeType = file.type || "image/jpeg";

            // Show preview above input
            var prev     = document.createElement("div");
            prev.className = "la-photo-preview";
            prev.id      = "la-photo-preview";

            var img      = document.createElement("img");
            img.src      = dataUrl;
            prev.appendChild(img);

            var rmBtn    = document.createElement("button");
            rmBtn.className = "la-photo-remove";
            rmBtn.innerHTML = "×";
            rmBtn.title  = "Remove photo";
            rmBtn.addEventListener("click", clearPendingPhoto);
            prev.appendChild(rmBtn);

            // Insert before input wrap
            var wrap = document.getElementById("la-input-wrap");
            wrap.parentNode.insertBefore(prev, wrap);

            pendingPhoto = { base64: base64, mimeType: mimeType };

            // Focus input and change placeholder
            input.placeholder = "What are you seeing in this photo?";
            setTimeout(function () { input.focus(); }, 100);
        };
        reader.readAsDataURL(file);
    });

    /* ── Timestamps ─────────────────────────────────────────────────── */

    function formatTimeAgo(ts) {
        var diff = Math.floor((Date.now() - ts) / 1000);
        if (diff < 60)  return "Just now";
        if (diff < 120) return "1 min ago";
        if (diff < 3600) return Math.floor(diff / 60) + " mins ago";
        var d = new Date(ts);
        var h = d.getHours(), m = d.getMinutes();
        return (h % 12 || 12) + ":" + (m < 10 ? "0" : "") + m + (h < 12 ? " am" : " pm");
    }

    function updateAllTimestamps() {
        var stamps = body.querySelectorAll(".la-timestamp[data-ts]");
        stamps.forEach(function (el) {
            el.textContent = formatTimeAgo(parseInt(el.getAttribute("data-ts"), 10));
        });
    }

    setInterval(updateAllTimestamps, 30000);

    /* ── Chat bubble renderer ───────────────────────────────────────── */

    function createChoice(label, subOverride) {
        var meta = optionMeta[label] || { icon: "➜", sub: subOverride || "" };
        var btn  = document.createElement("button");
        btn.className = "la-choice";
        btn.type      = "button";
        btn.innerHTML =
            '<span class="la-choice-icon">' + escapeHtml(meta.icon) + '</span>' +
            '<span>' +
                '<span class="la-choice-main">' + escapeHtml(label) + '</span>' +
                '<span class="la-choice-sub">'  + escapeHtml(subOverride || meta.sub || "") + '</span>' +
            '</span>' +
            '<span class="la-choice-arrow">›</span>';
        return btn;
    }

    function linkifyPhones(html) {
        return html.replace(/(\(?\d{3}\)?[\s\-]?\d{3}[\s\-]\d{4})/g, function (match) {
            var digits = match.replace(/\D/g, "");
            return '<a href="tel:' + digits + '" style="color:inherit;font-weight:500;">' + match + '</a>';
        });
    }

    function linkifyUrls(html) {
        return html.replace(/(https?:\/\/[^\s<"]+)/g, function (match, url, offset, str) {
            // Skip URLs already inside an HTML attribute (preceded by =" or =')
            var before = str.slice(Math.max(0, offset - 6), offset);
            if (/=["']$/.test(before)) return match;
            var isBillPay = match.indexOf('lawngateway.com') !== -1;
            if (isBillPay) {
                return '<a href="' + match + '" target="_blank" rel="noopener" class="la-pay-btn">Pay My Bill</a>';
            }
            return '<a href="' + match + '" target="_blank" rel="noopener" style="color:var(--la-purple);text-decoration:underline;">' + match + '</a>';
        });
    }

    function stylePlanNames(html) {
        var plans = ["Essential Plan", "Grow Plan", "Pro Plan"];
        plans.forEach(function (name) {
            var re = new RegExp("\\b(" + name + ")\\b", "g");
            html = html.replace(re, '<strong style="color:#7C3AED;font-weight:700;">$1</strong>');
        });
        return html;
    }

    function parseAiText(text) {
        var raw     = String(text || "");
        var options = [];

        raw = raw.replace(/\[OPTION:\s*([^\]]+)\]/g, function (_, opt) {
            options.push(opt.trim()); return "";
        });
        raw = raw.replace(/\*\*\[([^\]]+)\]\*\*/g, function (_, opt) {
            options.push(opt.trim()); return "";
        });
        raw = raw.replace(/^\s*\[([^\]]+)\]\s*$/gm, function (_, opt) {
            options.push(opt.trim()); return "";
        });
        raw = raw.replace(/\[LEAD_CAPTURED:[^\]]+\]/g, "").trim();
        raw = raw.replace(/\*\*([^*]+)\*\*/g, "$1");

        // Extract markdown links before escapeHtml to avoid double-processing
        var linkSlots = [];
        raw = raw.replace(/\[([^\]]+)\]\((https?:\/\/[^)]+)\)/g, function (_, linkText, url) {
            var isBillPay = url.indexOf('lawngateway.com') !== -1;
            var anchor = isBillPay
                ? '<a href="' + url + '" target="_blank" rel="noopener" class="la-pay-btn">Pay My Bill</a>'
                : '<a href="' + url + '" target="_blank" rel="noopener" style="color:var(--la-purple);text-decoration:underline;">' + linkText + '</a>';
            linkSlots.push(anchor);
            return '\x02LINK' + (linkSlots.length - 1) + '\x03';
        });

        // Auto-inject plan buttons if response mentions pricing and no plan options yet
        var hasPlanPricing = /\$29|\$39|\$49/i.test(raw) &&
            /(essential|grow|pro)\s*(plan)?/i.test(raw);
        var alreadyHasPlanBtn = options.some(function (o) {
            return ["Essential Plan", "Grow Plan", "Pro Plan"].some(function (k) { return o.indexOf(k) !== -1; });
        });
        if (hasPlanPricing && !alreadyHasPlanBtn && !suppressPlanBtns) {
            options = options.concat([
                "Essential Plan — $29/mo",
                "Grow Plan — $39/mo",
                "Pro Plan — $49/mo"
            ]);
        }

        // Split on double newlines → paragraphs; single newline → <br>
        var paras = escapeHtml(raw).split(/\n{2,}/);
        var html  = paras.map(function (p) {
            return "<p>" + p.replace(/\n/g, "<br>") + "</p>";
        }).join("");
        // Restore markdown link placeholders (safe HTML, skip escaping)
        linkSlots.forEach(function (anchor, i) {
            html = html.replace('\x02LINK' + i + '\x03', anchor);
        });
        html = linkifyPhones(html);
        html = linkifyUrls(html);
        html = stylePlanNames(html);

        return { html: html, options: options };
    }

    var planButtonKeys = ["Essential Plan", "Grow Plan", "Pro Plan"];

    function isPlanOption(text) {
        return planButtonKeys.some(function (k) { return text.indexOf(k) !== -1; });
    }

    function createOptionButton(optionText) {
        var meta = optionMeta[optionText] || { icon: "➜", sub: "" };
        var btn  = document.createElement("button");
        btn.className = "la-option";
        btn.type      = "button";
        btn.innerHTML =
            '<span class="la-option-icon">' + escapeHtml(meta.icon) + '</span>' +
            '<span>' +
                '<span class="la-option-main">' + escapeHtml(optionText) + '</span>' +
                (meta.sub ? '<span class="la-option-sub">' + escapeHtml(meta.sub) + '</span>' : '') +
            '</span>' +
            '<span class="la-option-arrow">›</span>';
        if (isPlanOption(optionText)) {
            btn.addEventListener("click", function () { startQuoteFlow(optionText); });
        } else {
            btn.addEventListener("click", function () { sendMessage(optionText); });
        }
        return btn;
    }

    function appendMessage(role, text, photoDataUrl) {
        var row       = document.createElement("div");
        row.className = "la-row " + (role === "user" ? "user" : "ai");

        var bubble       = document.createElement("div");
        bubble.className = "la-bubble " + (role === "user" ? "user" : "ai");

        if (role === "user") {
            if (photoDataUrl) {
                var img      = document.createElement("img");
                img.src      = photoDataUrl;
                img.className = "la-msg-photo";
                bubble.appendChild(img);
            }
            if (text) {
                var textNode = document.createElement("span");
                textNode.innerHTML = escapeHtml(text).replace(/\n/g, "<br>");
                bubble.appendChild(textNode);
            }
        } else {
            var parsed = parseAiText(text);
            bubble.innerHTML = parsed.html;

            if (parsed.options.length) {
                var wrap       = document.createElement("div");
                wrap.className = "la-options";
                parsed.options.forEach(function (opt) {
                    wrap.appendChild(createOptionButton(opt));
                });
                bubble.appendChild(wrap);
            }

            // Add rating buttons after AI response (not for typing indicator)
            if (text && text.length > 10) {
                messageCount++;
                addRatingButtons(bubble, messageCount);
            }
        }

        // Timestamp
        var ts       = document.createElement("div");
        ts.className = "la-timestamp";
        ts.setAttribute("data-ts", Date.now());
        ts.textContent = "Just now";
        row.appendChild(bubble);
        row.appendChild(ts);

        body.appendChild(row);

        if (role === "user") {
            body.scrollTop = body.scrollHeight;
        } else {
            // Wait for full render (option buttons add height after initial paint)
            setTimeout(function () {
                var rowH  = row.offsetHeight;
                var viewH = body.clientHeight;
                if (rowH < viewH) {
                    // Short message: bottom-align so buttons are in view
                    body.scrollTop = row.offsetTop + rowH - viewH + 8;
                } else {
                    // Long message: top-align so user reads from start
                    body.scrollTop = row.offsetTop - 8;
                }
            }, 100);
        }

        return bubble;
    }

    /* ── Start screen ───────────────────────────────────────────────── */

    function showStartScreen() {
        clearBody();

        var disclaimer       = document.createElement("div");
        disclaimer.className = "la-disclaimer";
        disclaimer.textContent = "Conversations may be recorded for quality and service purposes.";
        body.appendChild(disclaimer);

        var row       = document.createElement("div");
        row.className = "la-row ai";

        var bubble       = document.createElement("div");
        bubble.className = "la-bubble ai";
        bubble.innerHTML = "Hey, I'm Lawnie — Lawn Ace's virtual assistant. What can I help you with today?";

        var chipRow       = document.createElement("div");
        chipRow.className = "la-chip-row";
        chipRow.id        = "la-start-chips";

        ["New Customer", "Existing Customer", "Lawn Question"].forEach(function (label) {
            var chip       = document.createElement("button");
            chip.className = "la-chip";
            chip.type      = "button";
            chip.textContent = label;
            chip.addEventListener("click", function () {
                var chips = document.getElementById("la-start-chips");
                if (chips) chips.remove();
                handleRoute(label);
            });
            chipRow.appendChild(chip);
        });

        bubble.appendChild(chipRow);
        row.appendChild(bubble);
        body.appendChild(row);
    }

    function handleRoute(label) {
        if (label === "New Customer") {
            showRouteScreen("New Customer", "What can we help you with?", [
                "Weed Control", "Fertilization", "Mosquito Control", "Fire Ant Control", "Get Pricing"
            ]);
            return;
        }
        if (label === "Existing Customer") {
            showRouteScreen("Existing Customer", "What do you need help with?", [
                "Billing Question", "Schedule Service", "Service Issue", "Account Help"
            ]);
            return;
        }
        if (label === "Lawn Question") {
            showRouteScreen("Lawn Question", "What are you dealing with?", [
                "Weeds", "Brown Patches", "Mosquitoes", "Grass Type Help", "Other Lawn Question"
            ]);
            return;
        }
        sendMessage(label);
    }

    function showRouteScreen(eyebrow, title, labels) {
        clearBody();
        var card       = document.createElement("div");
        card.className = "la-start-card";
        card.innerHTML =
            '<div class="la-start-eyebrow">' + escapeHtml(eyebrow) + '</div>' +
            '<div class="la-start-title">'   + escapeHtml(title)   + '</div>' +
            '<div class="la-start-sub">Pick one and Lawnie will take it from there.</div>' +
            '<div class="la-choice-grid" id="la-route-choices"></div>';
        body.appendChild(card);

        var grid = document.getElementById("la-route-choices");
        labels.forEach(function (label) {
            var btn = createChoice(label);
            btn.addEventListener("click", function () {
                clearBody();
                sendMessage(label);
            });
            grid.appendChild(btn);
        });
    }

    /* ── Loading state ──────────────────────────────────────────────── */

    function setLoading(loading) {
        isLoading        = loading;
        sendBtn.disabled = loading;
        photoBtn.disabled = loading;
    }

    /* ── Chat open/close ────────────────────────────────────────────── */

    function openChat() {
        panel.classList.add("open");
        if (dot) dot.style.display = "none";
        if (!started) {
            started = true;
            showStartScreen();
        }
        setTimeout(function () { input.focus(); }, 100);
    }

    function closeChat() {
        panel.classList.remove("open");
    }

    /* ── Send message ───────────────────────────────────────────────── */

    function sendMessage(forcedText) {
        if (isLoading) return;

        var text  = (forcedText || input.value || "").trim();
        var photo = pendingPhoto;

        // Route through quote flow if active
        if (quoteFlow.active && !forcedText) {
            if (!text) return;
            input.value        = "";
            input.style.height = "auto";
            advanceQuoteFlow(text);
            return;
        }

        if (!text && !photo) return;

        input.value        = "";
        input.style.height = "auto";

        // Render user bubble
        appendMessage("user", text, photo ? "data:" + photo.mimeType + ";base64," + photo.base64 : null);

        // Add to history (text description for context)
        var historyText = photo ? (text ? "[Photo attached] " + text : "[Photo of lawn attached]") : text;
        history.push({ role: "user", content: historyText });

        clearPendingPhoto();

        if (!window.lawnaceChat || !window.lawnaceChat.ajaxUrl || !window.lawnaceChat.nonce) {
            appendMessage("ai", "The chat is almost ready, but the backend is not active yet. Please call 706-364-2338.");
            return;
        }

        setLoading(true);

        // Show typing indicator
        var typingRow = createTypingIndicator();
        body.appendChild(typingRow);
        body.scrollTop = body.scrollHeight;

        var formData = new FormData();
        formData.append("action",     "lawnace_chat");
        formData.append("nonce",      window.lawnaceChat.nonce);
        formData.append("session_id", window.lawnaceChat.sessionId || ("session_" + Date.now()));
        formData.append("messages",   JSON.stringify(history));

        if (photo) {
            formData.append("image_data",  photo.base64);
            formData.append("image_type",  photo.mimeType);
        }

        fetch(window.lawnaceChat.ajaxUrl, {
            method: "POST", credentials: "same-origin", body: formData
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            var tr = document.getElementById("la-typing-row");
            if (tr) tr.remove();

            if (data && data.success && data.data && data.data.reply) {
                appendMessage("ai", data.data.reply);
                history.push({ role: "assistant", content: data.data.reply });
                if (data.data.session_id) window.lawnaceChat.sessionId = data.data.session_id;
                playPing();
            } else {
                appendMessage("ai", (data && data.data) ? data.data : "Something went wrong. Please call 706-364-2338.");
            }
        })
        .catch(function () {
            var tr = document.getElementById("la-typing-row");
            if (tr) tr.remove();
            appendMessage("ai", "Connection issue. Please call 706-364-2338 if this continues.");
        })
        .finally(function () {
            setLoading(false);
            input.focus();
        });
    }

    /* ── Proactive trigger ──────────────────────────────────────────── */

    (function () {
        var nudge     = document.getElementById("la-nudge");
        var nudgeClose = document.getElementById("la-nudge-close");
        if (!nudge) return;

        var storageKey = "la_nudge_shown";
        var triggered  = false;

        function showNudge() {
            if (triggered) return;
            if (panel.classList.contains("open")) return;
            if (sessionStorage.getItem(storageKey)) return;
            triggered = true;
            nudge.style.display = "block";
            sessionStorage.setItem(storageKey, "1");
        }

        function dismissNudge() {
            nudge.style.display = "none";
        }

        // Timer trigger — 45 seconds
        var nudgeTimer = setTimeout(showNudge, 45000);

        // Scroll trigger — 60% down the page
        function onScroll() {
            var scrolled = (window.scrollY + window.innerHeight) / document.documentElement.scrollHeight;
            if (scrolled >= 0.6) {
                showNudge();
                window.removeEventListener("scroll", onScroll);
            }
        }
        window.addEventListener("scroll", onScroll, { passive: true });

        // Clicking nudge body opens chat
        nudge.addEventListener("click", function (e) {
            if (e.target === nudgeClose || nudgeClose.contains(e.target)) return;
            dismissNudge();
            openChat();
        });

        // X button dismisses
        nudgeClose.addEventListener("click", function (e) {
            e.stopPropagation();
            dismissNudge();
        });

        // Cancel nudge if user opens chat themselves
        launcher.addEventListener("click", function () {
            clearTimeout(nudgeTimer);
            dismissNudge();
        });
    })();

    /* ── Event listeners ────────────────────────────────────────────── */

    launcher.addEventListener("click", function () {
        panel.classList.contains("open") ? closeChat() : openChat();
    });

    closeBtn.addEventListener("click", closeChat);
    sendBtn.addEventListener("click",  function () { sendMessage(); });

    input.addEventListener("keydown", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    input.addEventListener("input", function () {
        this.style.height = "auto";
        this.style.height = Math.min(this.scrollHeight, 76) + "px";
    });

})();
