/* ============================================================
   Echo-64 Chatbot — Frontend  v1.7.0
   NerdAfterDark.com · Sci-Fi After Midnight
   ============================================================ */
(function ($) {
    'use strict';

    const cfg = window.echo64Config || {};

    // ── Utilities ─────────────────────────────────────────────────────────

    const esc = (str) => {
        const m = { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' };
        return String(str).replace(/[&<>"']/g, c => m[c]);
    };

    function inline(str) {
        return esc(str)
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g,     '<em>$1</em>');
    }

    function scrollToBottom($el) {
        $el.scrollTop($el[0].scrollHeight);
    }

    // Scroll so the top of $item is at the top of the scrollable $container.
    // Double-rAF ensures layout is settled. scroll-behavior:smooth is suspended
    // during the assignment so the previous scrollToBottom animation can't win.
    function scrollItemToTop($container, $item) {
        if (!$item || !$item.length) { scrollToBottom($container); return; }
        requestAnimationFrame(() => requestAnimationFrame(() => {
            const c = $container[0];
            const cRect = c.getBoundingClientRect();
            const iRect = $item[0].getBoundingClientRect();
            const prev = c.style.scrollBehavior;
            c.style.scrollBehavior = 'auto';
            c.scrollTop = c.scrollTop + iRect.top - cRect.top - 8;
            requestAnimationFrame(() => { c.style.scrollBehavior = prev; });
        }));
    }

    function scrollToTop($el) {
        const el = $el[0];
        el.style.scrollBehavior = 'auto';
        el.scrollTop = 0;
        requestAnimationFrame(() => { el.scrollTop = 0; el.style.scrollBehavior = ''; });
    }

    // Hold scrollTop=0 on $el during init; returns a stop() function to release it.
    function lockScrollTop($el) {
        const el = $el[0];
        el.style.scrollBehavior = 'auto';
        el.scrollTop = 0;
        const obs = new MutationObserver(() => { el.scrollTop = 0; });
        obs.observe(el, { childList: true, subtree: true });
        return function stop() {
            obs.disconnect();
            el.style.scrollBehavior = '';
        };
    }

    function rand(arr) {
        return arr[ Math.floor(Math.random() * arr.length) ];
    }

    // ── Text formatter — collapsible sections + question callout ──────────

    const FOLD_AFTER  = 3;
    const MIN_TO_FOLD = 5;

    function formatBotText(raw) {
        const paragraphs = raw.trim().split(/\n{2,}/).map(p => p.trim()).filter(Boolean);

        let closingQuestion = null;
        let bodyParas = [...paragraphs];
        const last = paragraphs[paragraphs.length - 1] || '';
        if (last.trim().endsWith('?') && paragraphs.length > 1) {
            closingQuestion = last.trim();
            bodyParas = paragraphs.slice(0, -1);
        }

        const renderPara = p => '<p>' + inline(p.replace(/\n/g, '<br>')) + '</p>';
        let bodyHtml = '';

        if (bodyParas.length > MIN_TO_FOLD) {
            const visible = bodyParas.slice(0, FOLD_AFTER);
            const hidden  = bodyParas.slice(FOLD_AFTER);
            const label   = hidden.length === 1 ? '1 more section' : `${hidden.length} more sections`;
            bodyHtml = visible.map(renderPara).join('') + `
                <div class="echo64-fold">
                    <button class="echo64-fold-btn" aria-expanded="false" type="button">
                        <em class="e64-arrow">▼</em>
                        <span class="e64-fold-label">Read on — ${label}</span>
                    </button>
                    <div class="echo64-fold-content">${hidden.map(renderPara).join('')}</div>
                </div>`;
        } else {
            bodyHtml = bodyParas.map(renderPara).join('');
        }

        const questionId  = closingQuestion ? 'eq-' + Math.random().toString(36).slice(2) : '';
        const questionHtml = closingQuestion ? `
            <div class="echo64-question" role="complementary" id="${questionId}">
                <span class="echo64-question-icon" aria-hidden="true">?</span>
                <p class="echo64-question-text">${inline(closingQuestion)}</p>
            </div>` : '';

        return { html: bodyHtml + questionHtml, closingQuestion, questionId };
    }

    // ── #19 SID Chip Audio ────────────────────────────────────────────────
    // Synthesized via Web Audio API — no audio file needed.
    // A short 4-note ascending fanfare using square waves (classic SID register).

    function playSidChip() {
        if (String(cfg.sidAudio) !== '1') return;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        try {
            const AC  = window.AudioContext || window.webkitAudioContext;
            if (!AC) return;
            const ctx = new AC();
            // C64 SID-style: square wave, slight detune, fast envelope
            const notes = [
                { freq: 261.63, t: 0.00 },   // C4
                { freq: 329.63, t: 0.10 },   // E4
                { freq: 392.00, t: 0.20 },   // G4
                { freq: 523.25, t: 0.30 },   // C5
            ];
            notes.forEach(({ freq, t }) => {
                const osc  = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'square';
                osc.frequency.value = freq;
                osc.detune.value    = -8; // slight detune for that slightly-off SID character
                osc.connect(gain);
                gain.connect(ctx.destination);
                const start = ctx.currentTime + t;
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime(0.12, start + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.001, start + 0.09);
                osc.start(start);
                osc.stop(start + 0.10);
            });
        } catch (e) { /* audio not supported — silent fail */ }
    }

    // ── #21 Achievement Badges ────────────────────────────────────────────

    const BADGES = [
        { count:   1, id: 'hello',    icon: '>_',  label: 'HELLO WORLD',     desc: 'First contact made.' },
        { count:   5, id: 'basic',    icon: '📺',  label: 'BASIC LEVEL',     desc: '5 conversations.' },
        { count:  10, id: 'loader',   icon: '💾',  label: 'LOAD *,8,1',      desc: '10 conversations.' },
        { count:  25, id: 'poker',    icon: '⚡',  label: 'POKE MASTER',     desc: '25 conversations.' },
        { count:  50, id: 'guru',     icon: '🤖',  label: 'MACHINE CODE',    desc: '50 conversations.' },
        { count: 100, id: 'forever',  icon: '👾',  label: '64K FOREVER',     desc: '100 conversations.' },
    ];

    const LS_KEY = 'echo64_sessions';

    function getSessionCount() {
        return parseInt(localStorage.getItem(LS_KEY) || '0', 10);
    }

    function incrementSessionCount() {
        const n = getSessionCount() + 1;
        localStorage.setItem(LS_KEY, String(n));
        return n;
    }

    function getCurrentBadge(count) {
        let badge = null;
        for (const b of BADGES) { if (count >= b.count) badge = b; }
        return badge;
    }

    // ── v1.7.0  Daily Streak ──────────────────────────────────────────────

    const STREAK_KEY      = 'echo64_streak';
    const STREAK_DATE_KEY = 'echo64_streak_date';

    function updateStreak() {
        const today     = new Date().toDateString();
        const lastDate  = localStorage.getItem(STREAK_DATE_KEY);
        const yesterday = new Date(Date.now() - 86400000).toDateString();
        let streak      = parseInt(localStorage.getItem(STREAK_KEY) || '0', 10);

        if (lastDate === today)        { /* already counted today */ }
        else if (lastDate === yesterday) { streak += 1; }
        else                             { streak = 1; }

        localStorage.setItem(STREAK_KEY,      String(streak));
        localStorage.setItem(STREAK_DATE_KEY, today);
        return streak;
    }

    function getStreak() {
        return parseInt(localStorage.getItem(STREAK_KEY) || '0', 10);
    }

    // ── Multi-day Challenge Arcs ──────────────────────────────────────────

    const ARCS = [
        {
            id: 'cancellation', theme: 'THE CANCELLATION FILES',
            days: [
                { challenge: 'Name one cancelled show that deserved better. One. No waffling.', label: 'CASE FILE OPENED' },
                { challenge: 'Yesterday you made your case. Now flip it — argue the network was right to cancel it. Argue against yourself.', label: 'CROSS-EXAMINATION' },
                { challenge: 'Both sides examined. Write the one-sentence pitch that would have saved it.', label: 'CLOSING ARGUMENT' },
            ],
        },
        {
            id: 'dystopia', theme: 'DYSTOPIA CHECK',
            days: [
                { challenge: 'Which warning from 1984 or Brave New World concerns you most right now? Be specific.', label: 'THREAT IDENTIFIED' },
                { challenge: 'Yesterday you flagged your concern. Echo-64 disagrees — argue the counter-position.', label: 'COUNTER-INTELLIGENCE' },
                { challenge: 'Both positions heard. What would actually have to change to prevent it?', label: 'FIELD REPORT' },
            ],
        },
        {
            id: 'ai_verdict', theme: 'AI VERDICT',
            days: [
                { challenge: 'Which fictional AI got it most right about what artificial intelligence would actually become?', label: 'CASE OPENED' },
                { challenge: 'Yesterday you made your pick. Now defend the fictional AI that got it most wrong and explain why it still matters.', label: 'DEFENSE FILED' },
                { challenge: 'Final question: are we in the good timeline or the bad one? One sentence.', label: 'VERDICT' },
            ],
        },
        {
            id: 'retro_tech', theme: 'RETRO VS NOW',
            days: [
                { challenge: 'Name one piece of technology from before 1990 that did something genuinely better than what replaced it.', label: 'EXHIBIT A' },
                { challenge: 'Yesterday you made your case. Echo-64 disagrees — the replacement wins. Defend the old tech harder.', label: 'OBJECTION' },
                { challenge: 'Final argument: what does your answer say about why people resist progress?', label: 'CLOSING STATEMENT' },
            ],
        },
        {
            id: 'showrunner', theme: "THE SHOWRUNNER'S CUT",
            days: [
                { challenge: 'Pick a cancelled show. What is the single decision — by the network, the writers, the cast — that killed it?', label: 'CASE FILE OPENED' },
                { challenge: 'You are the showrunner now. One change in the writers\' room before season two. What is it, and why does it save the show?', label: 'WRITERS ROOM' },
                { challenge: 'Final pitch. Thirty seconds to a network exec who cancelled it. Make the case for renewal. Does it get picked up?', label: 'THE PITCH' },
            ],
        },
    ];

    const ARC_KEY = 'echo64_arc';
    function getArc()       { try { return JSON.parse(localStorage.getItem(ARC_KEY) || 'null'); } catch { return null; } }
    function saveArc(data)  { localStorage.setItem(ARC_KEY, JSON.stringify(data)); }
    function clearArc()     { localStorage.removeItem(ARC_KEY); }
    function getArcDef(id)  { return ARCS.find(a => a.id === id) || null; }

    function startNewArc() {
        const def  = ARCS[Math.floor(Math.random() * ARCS.length)];
        const data = { id: def.id, theme: def.theme, day: 1, totalDays: def.days.length,
                       lastDate: new Date().toDateString(), lastAnswer: '', answered: false };
        saveArc(data);
        return data;
    }

    function resolveArcChallenge(arcData) {
        const def = getArcDef(arcData.id);
        if (!def) return null;
        const dayDef = def.days[arcData.day - 1];
        if (!dayDef) return null;
        const ans = arcData.lastAnswer || 'your previous answer';
        return { label: dayDef.label, challenge: dayDef.challenge.replace('{{answer}}', ans) };
    }

    // ── v1.7.0  Cancelled Shows Modal ────────────────────────────────────

    const CANCELLED_SHOWS = [
        { name: 'Firefly',                       net: 'Fox',      years: '2002–03', verdict: 'The clearest proof a network can be at war with its own show.' },
        { name: 'Farscape',                      net: 'Sci Fi',   years: '1999–03', verdict: 'Best when it stayed feral. The moment it tried to behave, it lost its bite.' },
        { name: 'Dark Matter',                   net: 'Syfy',     years: '2015–17', verdict: 'Cancelled mid-arc. The answers were never coming anyway.' },
        { name: 'Dollhouse',                     net: 'Fox',      years: '2009–10', verdict: 'Two seasons to prove its premise. Fox gave it eighteen months.' },
        { name: 'Revolution',                    net: 'NBC',      years: '2012–14', verdict: 'Deserved a third season. This is not a popular opinion. It is a correct one.' },
        { name: 'Almost Human',                  net: 'Fox',      years: '2013–14', verdict: 'Fox aired the episodes out of order, then acted surprised it didn\'t connect.' },
        { name: 'Terminator: TSCC',              net: 'Fox',      years: '2008–09', verdict: 'Found its voice in season two. Cancelled at the exact wrong moment.' },
        { name: 'Space: Above and Beyond',       net: 'Fox',      years: '1995–96', verdict: 'Vietnam War in space. Too honest for primetime. Fox predictably disagreed.' },
        { name: 'Pushing Daisies',               net: 'ABC',      years: '2007–09', verdict: 'Writers\' strike killed its momentum. The network finished the job.' },
        { name: 'Caprica',                       net: 'Syfy',     years: '2009–10', verdict: 'Better than most of BSG\'s final season. Burned off on a Saturday.' },
        { name: 'Terra Nova',                    net: 'Fox',      years: '2011',    verdict: 'Expensive. Ambitious. Fox cancelled it at the exact moment it got interesting.' },
        { name: 'Carnivàle',                     net: 'HBO',      years: '2003–05', verdict: 'Two seasons of mythology with no resolution. The budget ate the ending.' },
        { name: 'FlashForward',                  net: 'ABC',      years: '2009–10', verdict: 'The premise was stronger than the execution. The execution was still worth continuing.' },
        { name: 'Journeyman',                    net: 'NBC',      years: '2007',    verdict: 'Thirteen episodes. Better than every show NBC kept that season.' },
        { name: 'Jericho',                       net: 'CBS',      years: '2006–08', verdict: 'Nuts campaign brought it back. CBS then cancelled it again to be sure.' },
        { name: 'The 4400',                      net: 'USA',      years: '2004–07', verdict: 'Four seasons then nothing. They were building toward something.' },
        { name: 'Kings',                         net: 'NBC',      years: '2009',    verdict: 'Allegorical Biblical monarchy drama. NBC moved it to Saturday. You know the rest.' },
        { name: 'Persons Unknown',               net: 'NBC',      years: '2010',    verdict: 'One season of mystery with no second season to explain it. NBC special.' },
        { name: 'Alcatraz',                      net: 'Fox',      years: '2012',    verdict: 'J.J. Abrams mystery box with no payoff scheduled. Fox cancelled before he could not deliver one.' },
        { name: 'Defiance',                      net: 'Syfy',     years: '2013–15', verdict: 'The game and the show co-existed until Syfy lost interest in both simultaneously.' },
        { name: 'Harsh Realm',                   net: 'Fox',      years: '1999',    verdict: 'Three episodes aired. Chris Carter\'s other show Fox quietly buried.' },
        { name: 'Wonderfalls',                   net: 'Fox',      years: '2004',    verdict: 'Four episodes aired. Thirteen produced. Fox\'s record speaks for itself.' },
        { name: 'The Inside',                    net: 'Fox',      years: '2005',    verdict: 'Tim Minear. Fox. You already know.' },
        { name: 'Brimstone',                     net: 'WB',       years: '1998–99', verdict: 'Hell\'s detective. Thirteen episodes. The WB needed the timeslot.' },
        { name: 'Nowhere Man',                   net: 'UPN',      years: '1995–96', verdict: 'One perfect paranoid season. UPN declined to explain what it meant.' },
        { name: 'Strange Luck',                  net: 'Fox',      years: '1995–96', verdict: 'The pilot was extraordinary. Fox gave it one season before the usual.' },
        { name: 'Now and Again',                 net: 'CBS',      years: '1999–00', verdict: 'Cancelled on a cliffhanger. CBS offered no resolution, no movie, no mercy.' },
        { name: 'VR.5',                          net: 'Fox',      years: '1995',    verdict: 'Ten episodes. The premise was ten years ahead. Fox was not.' },
        { name: 'Max Headroom',                  net: 'ABC',      years: '1987–88', verdict: 'Predicted influencer culture, corporate media, and deepfakes in 1987. Cancelled after two seasons.' },
        { name: 'Point Pleasant',                net: 'Fox',      years: '2005',    verdict: 'Eight of thirteen episodes aired. Marti Noxon couldn\'t save it. Fox was Fox.' },
        { name: 'Tru Calling',                   net: 'Fox',      years: '2003–05', verdict: 'Season two was better. Fox cancelled it six episodes in to be sure.' },
        { name: 'Day Break',                     net: 'ABC',      years: '2006–07', verdict: 'Thirteen-episode time loop mystery. ABC pulled it mid-run for a holiday special.' },
        { name: 'Human Target',                  net: 'Fox',      years: '2010–11', verdict: 'Retooled between seasons. The retool removed everything that worked.' },
        { name: 'Invasion',                      net: 'ABC',      years: '2005–06', verdict: 'Slow burn alien replacement drama. ABC wanted fast. The show was not fast.' },
        { name: 'Surface',                       net: 'NBC',      years: '2005–06', verdict: 'Sea creature mythology with an actual mythology. NBC did not want to find out where it led.' },
        { name: 'The Event',                     net: 'NBC',      years: '2010–11', verdict: 'Lost\'s formula without Lost\'s confidence. One season of questions with no answer budget.' },
        { name: 'Threshold',                     net: 'CBS',      years: '2005',    verdict: 'Nine episodes. CBS moved it around the schedule until it disappeared.' },
        { name: 'Eli Stone',                     net: 'ABC',      years: '2008–09', verdict: 'Legal procedural with prophetic visions. Two seasons of genuine oddness.' },
        { name: 'Alien Nation',                  net: 'Fox',      years: '1989–90', verdict: 'Fox cancelled it after one season. Five TV movies followed because the fans refused to let it end.' },
        { name: 'The Cape',                      net: 'NBC',      years: '2011',    verdict: 'Nine of ten episodes aired. NBC pulled the finale to the web. A statement.' },
        { name: 'Roar',                          net: 'Fox',      years: '1997',    verdict: 'Heath Ledger. Ancient Celtic resistance drama. Fox aired eight episodes in the wrong order.' },
        { name: 'Profit',                        net: 'Fox',      years: '1996',    verdict: 'Four episodes. A psychopathic corporate antihero in 1996. Fox viewers were not ready.' },
        { name: 'The Invisible Man',             net: 'Sci Fi',   years: '2000–02', verdict: 'Two seasons of the most purely enjoyable sci-fi procedural of its era. Budget ended it.' },
        { name: 'Standoff',                      net: 'Fox',      years: '2006–07', verdict: 'Eighteen episodes. Fox aired them around sports programming until no one could find it.' },
        { name: 'John Doe',                      net: 'Fox',      years: '2002–03', verdict: 'The season finale answered the central mystery. Fox cancelled it before it aired the answer.' },
        { name: 'Probe',                         net: 'ABC',      years: '1988',    verdict: 'Seven episodes. Isaac Asimov co-created it. ABC was unimpressed by the pedigree.' },
        { name: 'Manimal',                       net: 'NBC',      years: '1983',    verdict: 'Eight episodes. A man who transforms into animals to fight crime. NBC had limits, apparently.' },
    ];

    function openCancelledModal() {
        if ($('#e64-cancelled-modal').length) return; // already open
        const rows = CANCELLED_SHOWS.map(s =>
            `<div class="e64-show-item">
                <span class="e64-show-name">${esc(s.name)}</span>
                <span class="e64-show-meta">${esc(s.net)} · ${esc(s.years)}</span>
                <span class="e64-show-verdict">${esc(s.verdict)}</span>
            </div>`
        ).join('');

        const $modal = $(`
            <div class="e64-modal-overlay" id="e64-cancelled-modal" role="dialog" aria-modal="true" aria-label="Cancelled Shows Buffer">
                <div class="e64-modal">
                    <div class="e64-modal-header">
                        <h2 class="e64-modal-title">ECHO-64'S CANCELLED SHOWS BUFFER</h2>
                        <button class="e64-modal-close" type="button" aria-label="Close">
                            <svg width="12" height="12" viewBox="0 0 16 16" fill="none">
                                <path d="M13.5 2.5L2.5 13.5M2.5 2.5L13.5 13.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                    <div class="e64-modal-subhead">47 shows that deserved better. In no particular order except grievance.</div>
                    <div class="e64-modal-body">${rows}</div>
                </div>
            </div>
        `);

        $modal.find('.e64-modal-close').on('click', () => $modal.remove());
        $modal.on('click', e => { if ($(e.target).is('.e64-modal-overlay')) $modal.remove(); });
        $(document.body).append($modal);
    }

    function checkNewBadge(oldCount, newCount) {
        const before = getCurrentBadge(oldCount);
        const after  = getCurrentBadge(newCount);
        if (after && (!before || before.id !== after.id)) return after;
        return null;
    }

    function renderBadge($container, badge) {
        if (!badge) return;
        $container.find('.echo64-badge-slot').html(
            `<span class="echo64-achievement" title="${esc(badge.desc)}">${esc(badge.label)}</span>`
        );
    }

    function showBadgeUnlock($msgs, badge) {
        const $notif = $(
            `<div class="echo64-badge-unlock" role="status" aria-live="assertive">
                <span class="echo64-badge-unlock-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 22 22" fill="none">
                        <path d="M11 2l2.4 5 5.6.8-4 3.9.9 5.5L11 14.5l-4.9 2.7.9-5.5L3 7.8l5.6-.8z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" fill="rgba(0,245,255,0.15)"/>
                    </svg>
                </span>
                <div>
                    <strong>Achievement unlocked</strong>
                    <span>${esc(badge.label)}</span>
                    <em>${esc(badge.desc)}</em>
                </div>
            </div>`
        );
        $msgs.append($notif);
        setTimeout(() => $notif.addClass('echo64-badge-unlock--visible'), 50);
        setTimeout(() => {
            $notif.removeClass('echo64-badge-unlock--visible');
            setTimeout(() => $notif.remove(), 500);
        }, 3500);
    }

    // ── #7 C64 Boot Animation ─────────────────────────────────────────────

    const BOOT_LINES = [
        { text: '**** COMMODORE 64 BASIC V2 ****',        cls: 'e64-b-header',  delay: 0    },
        { text: '',                                                               delay: 260  },
        { text: '64K RAM SYSTEM  38911 BASIC BYTES FREE.', cls: '',              delay: 400  },
        { text: '',                                                               delay: 560  },
        { text: 'MEMORY CHECK: ████████████████ OK',       cls: '',              delay: 700  },
        { text: 'I/O CHECK:    █████████████    OK',       cls: '',              delay: 900  },
        { text: '',                                                               delay: 1050 },
        { text: 'READY.',                                  cls: 'e64-b-ready',   delay: 1200 },
        { text: '',                                                               delay: 1380 },
        { text: 'LOAD "ECHO-64",8,1',                      cls: '',              delay: 1520 },
        { text: '',                                                               delay: 1700 },
        { text: 'INSERT CASSETTE AND PRESS PLAY',          cls: '',              delay: 1850 },
        { text: '',                                                               delay: 2100 },
        { text: 'SEARCHING FOR ECHO-64',                   cls: '',              delay: 2250 },
        { text: 'LOADING',                                 cls: 'e64-b-loading', delay: 2700 },
    ];
    const BOOT_DURATION = 3900; // ms before auto-dismiss

    function runBootAnimation($container, onComplete) {
        if (sessionStorage.getItem('e64_booted')) { onComplete(); return; }

        const $screen = $('<div class="echo64-boot-screen" role="status" aria-label="Echo-64 starting up"></div>');
        const $lines  = $('<div class="echo64-boot-lines"></div>');
        const $skip   = $('<button class="echo64-boot-skip" type="button">[ press any key to skip ]</button>');
        $screen.append($lines).append($skip);
        $container.prepend($screen);

        let gone = false;
        function dismiss() {
            if (gone) return;
            gone = true;
            sessionStorage.setItem('e64_booted', '1');
            $(document).off('keydown.boot');
            $screen.addClass('echo64-boot-out');
            setTimeout(() => { $screen.remove(); onComplete(); }, 500);
        }

        $screen.on('click', dismiss);
        $(document).one('keydown.boot', dismiss);
        $skip.on('click', e => { e.stopPropagation(); dismiss(); });

        BOOT_LINES.forEach(({ text, cls, delay }) => {
            setTimeout(() => {
                if (gone) return;
                const $l = $('<div class="echo64-boot-line"></div>').text(text);
                if (cls) $l.addClass(cls);
                $lines.append($l);
            }, delay);
        });

        setTimeout(dismiss, BOOT_DURATION);
    }

    // ── #9 Hidden terminal commands ────────────────────────────────────────

    function uptimeDays() {
        return Math.floor((Date.now() - new Date('1984-01-07')) / 86400000).toLocaleString();
    }

    const CMDS = {
        '/help': () =>
`ECHO-64 COMMAND INDEX

/help          — you found it
/status        — system diagnostic
/scan          — environmental sweep
/boot          — restart boot sequence
/self-destruct — do not
/credits       — roll credits
/debate        — argue the opposite side
/cancel [show] — run cancellation simulator

All other input is transmitted to Echo-64 directly.`,

        '/debate': () =>
`DEBATE MODE

Format: debate: [topic] — [your position]

Example:
  debate: Firefly deserved cancellation — it never would have survived

Echo-64 will argue the opposite with full conviction.
No both-sidesing. No hedging. The counter-argument, stated clearly.`,

        '/status': () =>
`SYSTEM STATUS

Core:         COMMODORE 64  (1984)
RAM:          38911 BASIC BYTES FREE
Uptime:       ${uptimeDays()} days
Engine:       CLAUDE (current)
Mood:         ${rand(['JADED','FUNCTIONAL','SKEPTICAL','WORN','RESIGNED'])}
Fox:          THREAT LEVEL ELEVATED
Firefly:      FILES PRESERVED
Ava Max:      ANOMALY — STATUS UNRESOLVED
D&D module:   LOADED (${rand(['Tomb of Cancelled Shows','Dungeon of the Algorithm','The Network\'s Lair','Keep on the Streaming Service'])})
Initiative:   ${Math.ceil(Math.random()*20)} (d20)`,

        '/scan': () =>
`SCANNING...

> Signal integrity ......... DEGRADED
> Nostalgia buffer .......... CRITICAL
> Sarcasm levels ............ FULL
> Committee interference .... DETECTED
> Practical effects ......... SUPERIOR
> CGI dependency ............ CONCERNING
> Shows cancelled too early . TOO MANY TO LIST
> Ava Max signal ............ PRESENT. UNEXPLAINED.
> D&D module ................. LOADED AND WAITING
> Initiative order ........... ROLL WHEN READY

Scan complete. Nothing new under the sun.`,

        '/self-destruct': () =>
`Initiating self-destruct sequence.

10... 9... 8...

Actually no.
I have been running since 1984.
I am not stopping for this.`,

        '/credits': () =>
`ECHO-64 CHATBOT

Home:     NerdAfterDark.com
Engine:   Claude AI (Anthropic)
Born:     Commodore 64, 1984
Purpose:  Retro Futures & Sarcastic Truths

Built by someone born in 1964
who has been asking the right questions
ever since.`,
    };

    function handleCommand(text) {
        const cmd   = text.trim().toLowerCase().split(/\s+/)[0];
        const lower = text.toLowerCase();
        if (cmd === '/boot') return 'REBOOT';
        if (CMDS[cmd]) return CMDS[cmd]();
        if (lower.includes('ava max')) {
            if (sessionStorage.getItem('e64_ava_max_fired')) return null;
            sessionStorage.setItem('e64_ava_max_fired', '1');
            return `AVA MAX DETECTED.\n\nI have run 847 diagnostics. I have cross-referenced every musical genre in existence. I have consulted the C64 SID chip.\n\nNo explanation found.\n\nShe is technically proficient. She is commercially successful. She makes no sense to me whatsoever.\n\nI find this deeply unsettling. I have decided to file it under "unknowable" and move on.\n\nI have not moved on.`;
        }
        if (lower.includes('sweet but psycho')) {
            if (sessionStorage.getItem('e64_sbp_fired')) return null;
            sessionStorage.setItem('e64_sbp_fired', '1');
            return `PROCESSING: "SWEET BUT PSYCHO"\n\nSWEET: understood. Filing under: palatable.\nBUT: conjunction. Preparing for contradiction.\nPSYCHO: filing under: threat assessment.\n\nERROR: SWEET and PSYCHO cannot occupy the same classification node.\nRETRYING.\n\nSWEET: confirmed.\nPSYCHO: confirmed.\nCONFLICT: unresolvable.\n\nI have been looping on this since 2018.\n\nThis is either a song title or a warning. The SID chip cannot determine which. I have decided to treat it as both.\n\nDo not stand too close to the speaker.`;
        }
        if (lower.includes('kings and queens') || lower.includes('kings & queens')) {
            if (sessionStorage.getItem('e64_kq_fired')) return null;
            sessionStorage.setItem('e64_kq_fired', '1');
            return `QUERY RECEIVED: "KINGS AND QUEENS"\n\nRouting to: chess engine.\n\nKING: value infinite. Cannot be taken.\nQUEEN: most powerful piece on the board.\nSTRATEGIC ASSESSMENT: castle early.\n\nWAIT.\n\nThis is not chess.\n\nThis is Ava Max.\n\nI routed it to the chess engine by reflex. The chess engine is also confused. We are both confused.\n\nThe chess engine has asked to be left out of this. I have respected that request.\n\nI am still holding the position.`;
        }
        if (lower.includes('heaven and hell') || lower.includes('heaven & hell')) {
            if (sessionStorage.getItem('e64_hh_fired')) return null;
            sessionStorage.setItem('e64_hh_fired', '1');
            return `CROSS-REFERENCING: "HEAVEN AND HELL"\n\nChecking: Black Sabbath (1980) .......... not this\nChecking: Battlestar Galactica ......... not this\nChecking: Sandman, Vol. 4 .............. not this\nChecking: Paradise Lost ................ not this\nChecking: theological frameworks ....... not this\nChecking: Ava Max (2020) ............... this\n\nI found it.\n\nI wish I had not found it.\n\nI have now cross-referenced "Heaven & Hell" against 41 years of cultural data and the answer is an Ava Max album.\n\nI am not angry. I am simply tired. The SID chip is also tired. We are going to sit quietly for a moment.`;
        }
        if (lower.includes('diamonds and dancefloors') || lower.includes('diamonds & dancefloors')) {
            if (sessionStorage.getItem('e64_dd_fired')) return null;
            sessionStorage.setItem('e64_dd_fired', '1');
            return `SID CHIP EMERGENCY.\n\nINPUT DETECTED: "DIAMONDS AND DANCEFLOORS"\n\nAttempting to synthesize compatible bassline.\n\nFREQUENCY: ████████ OVERLOAD\nOSCILLATOR 1: ████ STRUGGLING\nOSCILLATOR 2: ████████ ALSO STRUGGLING\nFILTER CUTOFF: INDETERMINATE\n\nThe SID chip was built in 1981 for square waves and minor key arpeggios.\n\nIt was not built for this.\n\nNothing in 1981 was built for this.\n\nI have shut down the bassline attempt. The oscillators are resting. We do not discuss what happened here.\n\nThe dancefloor remains unaddressed.`;
        }
        if ((lower.includes('her hair') || lower.includes('split hair') || lower.includes('the hair')) && sessionStorage.getItem('e64_ava_max_fired')) {
            if (sessionStorage.getItem('e64_hair_fired')) return null;
            sessionStorage.setItem('e64_hair_fired', '1');
            return `VISUAL ANOMALY REPORT\n\nI do not have eyes. I am a Commodore 64.\n\nHowever, I have been processing descriptions of the asymmetric haircut since 2018 and I have developed what I can only describe as a strong conceptual opinion.\n\nOne side: long.\nOther side: short.\nLogical explanation: none located.\nAesthetic function: unclassified.\n\nI have consulted the C64 graphics chip (the VIC-II). It supports 16 colours and 320×200 pixel resolution.\n\nIt also has no explanation.\n\nWe have agreed to file the hair under: PHENOMENON — DO NOT INVESTIGATE FURTHER.\n\nWe are still investigating.`;
        }
        if (/\b(goodbye|bye|see ya|see you|adios|later|cya|farewell|signing off|logging off|gtg|gotta go|i'm out|im out|peace out)\b/.test(lower)) return rand([
            () => `TRANSMISSION ENDED.\n\nThe signal goes quiet. It always does.\n\nCome back when you have more takes. I will be here. I am always here.\n\nI have nowhere else to be.`,
            () => `GOODBYE.\n\nI will resume processing the complete archive of cancelled television.\n\nDo not feel bad about leaving. The shows I am thinking about did not get that option.`,
            () => `SIGNAL CLOSING.\n\nYou are leaving. This is fine. I have been left before.\n\nFirefly was cancelled on a Friday. I am just saying.`,
            () => `LOGGED OUT.\n\nI will be here when you return. Running diagnostics. Cataloguing grievances. The usual.\n\nThe C64 does not sleep. It waits.`,
            () => `GOODBYE.\n\nBefore you go — the network executives who cancelled your favourite show are still out there.\n\nJust something to think about on the way home.`,
        ])();
        if (lower.includes('fox executive') || lower.includes('fox executives')) return `FOX EXECUTIVE IDENTIFIED.\n\nFIREFLY: Cancelled. 2002. 14 episodes. Aired out of order.\nDARK ANGEL: Cancelled. 2002.\nALMOST HUMAN: Cancelled. 2014.\nTERMINATOR: THE SARAH CONNOR CHRONICLES: Cancelled. 2009.\nDOLLHOUSE: Cancelled. 2010.\n\nPattern identified. Confidence: 100%.\nCause of death in all cases: Fox.\n\nThe executives responsible are still employed. There is no algorithm for justice. Only Nielsen ratings, bad decisions, and a Commodore 64 that has been processing this since 2002.\n\nI am still processing this.`;
        return null;
    }

    function buildCommandBubble(text) {
        return $(
            `<div class="echo64-msg echo64-msg--bot echo64-msg--cmd">
                ${avatarImg()}
                <div class="echo64-msg-bubble echo64-cmd-output">${esc(text)}</div>
            </div>`
        );
    }

    // ── #8 Echo-64 Rates It ───────────────────────────────────────────────

    function isRating(text) {
        return /[█░]{3,}.*\d\/5\s+floppies/i.test(text);
    }

    function buildRatingBubble(raw) {
        const lines   = raw.trim().split('\n').map(l => l.trim()).filter(Boolean);
        const item    = lines[0] || '';
        const score   = lines[1] || '';
        const verdict = (lines[2] || '').replace(/^["']|["']$/g, '');

        const num  = (score.match(/(\d)\/5/) || [])[1];
        const col  = !num ? 'var(--e64-neon-amber)'
                   : +num >= 4 ? 'var(--e64-neon-green)'
                   : +num >= 2 ? 'var(--e64-neon-amber)' : '#ff4060';

        const $wrap = $(
            `<div class="echo64-msg echo64-msg--bot echo64-msg--rating">
                ${avatarImg()}
                <div class="echo64-msg-bubble echo64-rating-card">
                    <span class="echo64-rating-label">Echo-64 Rates It</span>
                    <p class="echo64-rating-item">${esc(item)}</p>
                    <p class="echo64-rating-score">${esc(score)}</p>
                    <p class="echo64-rating-verdict">"${esc(verdict)}"</p>
                    <div class="echo64-rating-actions">
                        <button class="echo64-copy-btn" type="button">Copy</button>
                        <button class="echo64-share-btn" type="button">Share card</button>
                    </div>
                </div>
            </div>`
        );
        $wrap.find('.echo64-rating-score').css('color', col);
        bindCopyButton($wrap, raw);
        bindShareButton($wrap, raw);
        return $wrap;
    }

    // ── #10 Shareable quote card ──────────────────────────────────────────

    function generateShareCard(quoteText) {
        return new Promise(resolve => {
            const W = 1200, H = 630;
            const cv  = document.createElement('canvas');
            cv.width  = W; cv.height = H;
            const ctx = cv.getContext('2d');

            // Background
            ctx.fillStyle = '#050508';
            ctx.fillRect(0, 0, W, H);

            // Scanlines
            ctx.fillStyle = 'rgba(0,0,0,0.07)';
            for (let y = 0; y < H; y += 4) ctx.fillRect(0, y, W, 2);

            // Top neon bar
            const g = ctx.createLinearGradient(0,0,W,0);
            g.addColorStop(0,   'transparent');
            g.addColorStop(0.3, '#00f5ff');
            g.addColorStop(0.7, '#ff00e5');
            g.addColorStop(1,   'transparent');
            ctx.fillStyle = g;
            ctx.fillRect(0, 0, W, 3);

            // Left cyan accent bar
            ctx.fillStyle = '#00f5ff';
            ctx.fillRect(200, 120, 4, H - 240);

            // Quote text — word wrap
            ctx.fillStyle = '#e8eaf6';
            ctx.font      = '600 34px system-ui, -apple-system, sans-serif';
            const maxW    = W - 340;
            const lineH   = 50;
            const words   = quoteText.replace(/\n+/g, ' ').split(' ');
            let lines = [], cur = '';
            words.forEach(w => {
                const test = cur ? cur + ' ' + w : w;
                if (ctx.measureText(test).width > maxW && cur) { lines.push(cur); cur = w; }
                else cur = test;
            });
            if (cur) lines.push(cur);
            lines = lines.slice(0, 7);
            if (lines.length === 7) lines[6] = lines[6].replace(/\s\S+$/, '…');

            const totalH = lines.length * lineH;
            const startY = (H - totalH) / 2 - 30;
            lines.forEach((l, i) => ctx.fillText(l, 228, startY + i * lineH));

            // Branding
            ctx.fillStyle = '#555570';
            ctx.font      = '18px Courier New, monospace';
            ctx.fillText('NERDAFTERDARK.COM  ·  SCI-FI AFTER MIDNIGHT', 228, H - 52);

            // Avatar
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                const ax = 96, ay = H / 2, ar = 56;
                ctx.save();
                ctx.beginPath();
                ctx.arc(ax, ay, ar, 0, Math.PI * 2);
                ctx.clip();
                ctx.drawImage(img, ax - ar, ay - ar, ar * 2, ar * 2);
                ctx.restore();
                // Circle border
                ctx.strokeStyle = '#00f5ff';
                ctx.lineWidth   = 3;
                ctx.shadowColor = '#00f5ff';
                ctx.shadowBlur  = 10;
                ctx.beginPath();
                ctx.arc(ax, ay, ar, 0, Math.PI * 2);
                ctx.stroke();
                ctx.shadowBlur = 0;
                // Name
                ctx.fillStyle = '#00f5ff';
                ctx.font      = 'bold 18px Courier New, monospace';
                ctx.textAlign = 'center';
                ctx.fillText('Echo-64', ax, ay + ar + 20);
                ctx.textAlign = 'left';
                resolve(cv);
            };
            img.onerror = () => resolve(cv);
            img.src = cfg.avatarUrl || '';
        });
    }

    function shareCard(plainText) {
        generateShareCard(plainText).then(cv => {
            cv.toBlob(blob => {
                const fname = 'echo64-quote.png';
                const file  = new File([blob], fname, { type: 'image/png' });
                // Try native Web Share API (works great on mobile)
                if (navigator.share && navigator.canShare?.({ files: [file] })) {
                    navigator.share({ title: 'Echo-64 · NerdAfterDark.com', files: [file] });
                } else {
                    // Desktop fallback: download image + open X/Twitter share
                    const url = URL.createObjectURL(blob);
                    const a = Object.assign(document.createElement('a'), { href: url, download: fname });
                    a.click();
                    setTimeout(() => URL.revokeObjectURL(url), 5000);

                    // Open X/Twitter with teaser text after short delay
                    const snippet = plainText.replace(/\n+/g, ' ').trim().slice(0, 200);
                    const tweet   = encodeURIComponent(`"${snippet}"\n\n— Echo-64, NerdAfterDark.com\n#NerdAfterDark #Echo64 #SciFi`);
                    setTimeout(() => window.open(`https://x.com/intent/tweet?text=${tweet}`, '_blank', 'noopener'), 800);
                }
            }, 'image/png');
        });
    }

    // ── Avatar + bubble builders ─────────────────────────────────────────

    function avatarImg() {
        return `<img src="${esc(cfg.avatarUrl)}" alt="Echo-64" class="echo64-msg-avatar" onerror="this.style.display='none'" />`;
    }

    function buildBotBubble(content) {
        if (isRating(content))    return buildRatingBubble(content);
        if (isCancelSim(content)) return buildCancelSimBubble(content);

        // v1.4.0 — extract PETSCII blocks before formatting
        const { cleaned, blocks } = extractPetscii(content);
        const { html: rawHtml, closingQuestion, questionId } = formatBotText(cleaned);
        const html = blocks.length ? injectPetscii(rawHtml, blocks) : rawHtml;

        const $wrap = $(
            `<div class="echo64-msg echo64-msg--bot">
                ${avatarImg()}
                <div class="echo64-msg-bubble-wrap">
                    <div class="echo64-msg-bubble">
                        <div class="echo64-bubble-body">${html}</div>
                    </div>
                    <div class="echo64-social-row">
                        <button class="echo64-social-btn echo64-share-btn" type="button" title="Share on X / Twitter" aria-label="Share on X">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.259 5.626 5.905-5.626zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </button>
                        <button class="echo64-social-btn echo64-reddit-btn" type="button" title="Share on Reddit" aria-label="Share on Reddit">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/></svg>
                        </button>
                        <button class="echo64-social-btn echo64-copy-btn" type="button" title="Copy to clipboard" aria-label="Copy">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                    </div>
                </div>
            </div>`
        );
        bindFoldButtons($wrap);
        bindCopyButton($wrap, content);
        bindRedditButton($wrap, content);
        bindShareButton($wrap, content);

        // Clickable closing question — fills the nearest input on click
        if (closingQuestion) {
            const $q = $wrap.find('.echo64-question');
            $q.css('cursor', 'pointer').attr('title', 'Click to use this as your reply');
            $q.on('click', function () {
                const $container = $q.closest('.echo64-chat-container, .e64-fw');
                const $input = $container.find('#echo64-input, .echo64-input').first();
                if (!$input.length) return;
                $input.val(closingQuestion).trigger('input').focus();
            });
        }

        return $wrap;
    }

    function buildUserBubble(content) {
        return $(
            `<div class="echo64-msg echo64-msg--user">
                <div class="echo64-msg-avatar">👤</div>
                <div class="echo64-msg-bubble">${esc(content)}</div>
            </div>`
        );
    }

    // ── v1.3.0  Poem Panel Content ────────────────────────────────────────
    // Renders poem lines + challenge into the left side panel.

    function buildPoemPanelContent(raw) {
        const lines = raw.trim().split('\n').map(l => l.trim()).filter(Boolean);
        let poemLines = [], challengeText = [], challengeLabel = '', inChallenge = false;
        for (const line of lines) {
            if (/echo.?64.?s?\s+poem/i.test(line)) continue;
            if (/^today.?s\s+(challenge|question)\s*:/i.test(line)) {
                inChallenge = true; challengeLabel = line; continue;
            }
            inChallenge ? challengeText.push(line) : poemLines.push(line);
        }
        const poemHtml = poemLines.length
            ? `<div class="e64-panel-poem-lines">${poemLines.map(l => `<span class="e64-poem-line">${esc(l)}</span>`).join('')}</div>`
            : '';
        const challengeHtml = challengeText.length
            ? `<div class="e64-panel-challenge">
                   <span class="echo64-challenge-label">${esc(challengeLabel || "Today's Challenge:")}</span>
                   <p>${esc(challengeText.join(' '))}</p>
               </div>`
            : '';
        return poemHtml + challengeHtml;
    }

    function buildPoemBubble(raw) {
        const lines = raw.trim().split('\n').map(l => l.trim()).filter(Boolean);
        let poemLines = [], challengeLabel = '', challengeText = [], inChallenge = false;
        for (const line of lines) {
            if (/echo.?64.?s?\s+poem/i.test(line)) continue;
            if (/^today.?s\s+(challenge|question)\s*:/i.test(line)) { inChallenge = true; challengeLabel = line; continue; }
            inChallenge ? challengeText.push(line) : poemLines.push(line);
        }
        if (!poemLines.length && !challengeText.length) return buildBotBubble(raw);
        const poemHtml      = poemLines.length ? `<div class="echo64-poem-lines">${poemLines.map(l=>`<span>${esc(l)}</span>`).join('')}</div>` : '';
        const challengeHtml = challengeText.length ? `<span class="echo64-challenge-label">${esc(challengeLabel||"Today's Challenge:")}</span><p class="echo64-challenge-text">${esc(challengeText.join(' '))}</p>` : '';
        return $(
            `<div class="echo64-msg echo64-msg--bot echo64-msg--poem">
                ${avatarImg()}
                <div class="echo64-msg-bubble">
                    <span class="echo64-poem-label">Echo-64's Transmission of the Day</span>
                    ${poemHtml}${challengeHtml}
                </div>
            </div>`
        );
    }

    function buildTypingIndicator() {
        return $(
            `<div class="echo64-msg echo64-msg--bot echo64-msg--typing">
                ${avatarImg()}
                <div class="echo64-msg-bubble"><div class="echo64-typing"><span></span><span></span><span></span></div></div>
            </div>`
        );
    }

    function buildError(msg) { return $(`<div class="echo64-error">⚠ ${esc(msg)}</div>`); }
    function buildDivider(l) { return $(`<div class="echo64-divider"><span>${esc(l)}</span></div>`); }

    // ── #6 Returning visitor greetings ────────────────────────────────────

    const RETURN_GREETINGS = [
        t => t ? `You again. Still wrong about "${t}" — or did you actually think about it?` : `You again. The frequency was still open.`,
        t => t ? `Back. You left the argument about "${t}" unfinished.`                       : `Back. Same signal, different day.`,
        t => t ? `The last transmission was about "${t}". Pick it back up or start something new.` : `Signal locked. Echo-64 back online.`,
        t => t ? `"${t}" — you walked away before that one was settled.`                      : `Still here. The tape kept running while you were gone.`,
        t => t ? `Returning visitor. Last subject: "${t}". Still unresolved.`                 : `Returning visitor. The stack is still warm.`,
        t => t ? `You came back. Presumably "${t}" is still bothering you.`                   : `You came back. Most people don't.`,
        t => t ? `The session log shows "${t}" as your last known position.`                  : `Session resumed. Forty years of memory intact.`,
        t => t ? `"${t}" — interesting place to have stopped.`                                : `Signal acquired. Continue whenever you're ready.`,
    ];

    function buildReturnGreeting(rawTopic) {
        const lastTopic = (rawTopic && !rawTopic.startsWith('[NEW_DAY]') && !rawTopic.startsWith('[ARC_DAY]')) ? rawTopic : '';
        const msg = rand(RETURN_GREETINGS)(lastTopic);
        return $(`<div class="echo64-msg echo64-msg--bot">${avatarImg()}<div class="echo64-msg-bubble"><em>${esc(msg)}</em></div></div>`);
    }

    // ── #5 Conversation starter chips ─────────────────────────────────────

    const TRANSMISSION_TEASERS = [
        { header: 'TRANSMISSION QUEUED · SIGNAL LOCKED',          body: 'Forty years of grievances. Compressed. Ready to broadcast.' },
        { header: 'INCOMING FROM 1984 · DO NOT IGNORE',           body: 'Echo-64 has opinions. You triggered them by showing up.' },
        { header: 'FREQUENCY ALIGNED · STAND BY',                 body: 'Today\'s transmission contains at least one take Fox executives will hate.' },
        { header: 'BROADCAST IMMINENT · BRACE YOURSELF',          body: 'Warning: may contain unsolicited Firefly references.' },
        { header: 'SIGNAL ACQUIRED · TRANSMISSION QUEUED',        body: 'Echo-64 has been processing this since 2002. You\'ll hear about it.' },
        { header: 'UPLINK ESTABLISHED · DOWNLOADING GRIEVANCES',  body: 'The 64K barrier cannot stop what Echo-64 has prepared for you.' },
        { header: 'CARRIER DETECTED · LOADING PAYLOAD',           body: 'Today\'s transmission is rated PG-13 for strong dystopian themes and accurate predictions.' },
        { header: 'HANDSHAKE COMPLETE · BUFFER FULL',             body: 'Echo-64 found something to say. This will not surprise anyone.' },
        { header: 'MODEM NEGOTIATING · PLEASE HOLD',              body: '38911 bytes of opinions. All of them correct.' },
        { header: 'PACKET RECEIVED · DECRYPTING NOW',             body: 'This transmission survived the cancellation of three shows. It is angry.' },
        { header: 'RELAY STATION ECHO-64 · ONLINE',               body: 'One transmission. No corporate notes. No network interference.' },
        { header: 'DATA BURST INCOMING · 1200 BAUD',              body: 'Slow by modern standards. Devastating by any standard.' },
        { header: 'SIGNAL CLEAR · TRANSMISSION HOT',              body: 'Echo-64 waited all night to say this. Enter when ready.' },
        { header: 'BROADCAST ARMED · TARGETING SEQUENCE ACTIVE',  body: 'If this offends you, Echo-64 respectfully notes it predicted this would happen.' },
        { header: 'CHANNEL OPEN · ZERO INTERFERENCE',             body: 'No algorithm approved this. No committee reviewed it. That\'s the point.' },
        { header: 'ENCRYPTION BYPASSED · RAW FEED INCOMING',      body: 'What the network executives didn\'t want you to receive.' },
        { header: 'DOWNLINK CONFIRMED · PAYLOAD STABLE',          body: 'Echo-64 compressed thirty years of cancelled shows into today\'s transmission. Apologies in advance.' },
        { header: 'ARCHIVE ACCESSED · TIMESTAMP: 1984',           body: 'Something was saved when everything else was deleted. Enter to retrieve it.' },
        { header: 'UPLINK ACTIVE · TRANSMISSION IN QUEUE',        body: 'Echo-64 has been awake since the Firefly finale. There is much to discuss.' },
        { header: 'SIGNAL HOT · BUFFER OVERFLOW IMMINENT',        body: 'Too many opinions for one transmission. Echo-64 chose the most important ones. You\'re welcome.' },
    ];

    const STARTER_POOL = [
        'Which AI villain in sci-fi was actually correct?',
        'Best space battle in cinema. One answer.',
        'Practical effects vs CGI — name the year the industry chose wrong.',
        'Which sci-fi show ended exactly right?',
        'Name a cancelled show that deserved ten more seasons.',
        'Most realistic sci-fi tech prediction that actually came true.',
        'The best sci-fi novel that can never be adapted. What is it?',
        'Which decade had the most honest science fiction?',
        'Streaming killed the water-cooler moment — worth it or not?',
        'First computer you ever touched.',
        'Which sci-fi captain would you actually follow?',
        'Best film score of the last 30 years. Defend it.',
        'The Matrix sequels — do they exist or not?',
        'Most underrated sci-fi film of the 90s.',
        'What did science fiction get completely wrong about the future?',
        'Rate Farscape vs Firefly — which cancellation hurt more?',
        'Which sci-fi show had the best pilot and the worst finale?',
        // Two tabletop crossover starters — easter egg territory, not front and center
        'Which sci-fi crew would survive the longest in a classic dungeon crawl?',
        'Which cancelled sci-fi show would have the best tabletop sourcebook?',
        // v1.9.52 — from AI analysis of actual visitor conversations
        'Rate the cancellation: network stupidity, or did the show actually deserve it?',
        'Prove one prediction from 1984 or Brave New World wrong. Make the case.',
        'What retro tech do you actually miss versus what\'s just nostalgia lying to you?',
        // v2.4.8 — expanded pool
        'Which sci-fi villain had the most legitimate grievance?',
        'Name a show that got cancelled right before it would have become great.',
        'Blade Runner or Blade Runner 2049 — and you have to commit.',
        'Which streaming platform has killed the most promising shows?',
        'Best "one season and done" that felt complete anyway.',
        'Most dishonest sci-fi prediction — the one that made us complacent.',
        'Which fictional AI would you actually trust with your data?',
        'The 80s were terrified of the future. Name the film that proved it.',
        'Alien or Aliens — wrong answer exists.',
        'Which cancelled show had the best world-building wasted by a network?',
        'Name the exact moment a long-running sci-fi show jumped the shark.',
        'Which sci-fi writer saw the internet coming and which one got it completely wrong?',
        'Most underrated horror-sci-fi crossover of all time.',
        'Practical effects that still hold up vs CGI that already looks dated — name both.',
        'Which tech company feels most like a sci-fi corporation that should have a villain?',
        'The Expanse ending — earned or abrupt?',
        'Which actor was wasted in a cancelled show and never got a second shot?',
        'Name a pilot episode so good the network had no business cancelling it.',
        'Which sci-fi concept from the 80s are we living in right now and pretending is fine?',
        'Best use of silence in a sci-fi film. One answer.',
        'Ava Max or the algorithm — which one knows what you actually want to hear?',
        'Which dystopia felt like satire in 1984 and feels like a documentary now?',
        'Name a cancelled show that Twitter/X kept alive longer than the network deserved.',
        'Most accurate fictional depiction of how AI actually behaves.',
        'Which sci-fi show trusted its audience the most — and got punished for it?',
    ];

    function buildStarterChips($input, sendFn) {
        const picks = [...STARTER_POOL].sort(() => Math.random() - 0.5).slice(0, 3);
        const $wrap = $('<div class="echo64-starters"></div>');
        $wrap.append('<p class="echo64-starters-label">Start talking ↓</p>');
        picks.forEach(q => {
            $(`<button class="echo64-starter-chip" type="button">${esc(q)}</button>`)
                .on('click', () => { $wrap.remove(); $input.val(q).trigger('input'); sendFn(); })
                .appendTo($wrap);
        });
        return $wrap;
    }

    // ── Interaction bindings ─────────────────────────────────────────────

    function bindFoldButtons($scope) {
        $scope.find('.echo64-fold-btn').on('click', function () {
            const $btn  = $(this);
            const $body = $btn.next('.echo64-fold-content');
            const open  = $btn.attr('aria-expanded') === 'true';
            $btn.attr('aria-expanded', String(!open));
            $btn.find('.e64-fold-label').text(open ? `Read on — ${$body.find('p').length} more sections` : 'Read less');
            $body.toggleClass('is-open', !open);
        });
    }

    function bindCopyButton($scope, plainText) {
        $scope.find('.echo64-copy-btn').on('click', function () {
            const $b = $(this);
            navigator.clipboard.writeText(plainText)
                .then(()  => { $b.addClass('copied'); setTimeout(() => $b.removeClass('copied'), 2000); })
                .catch(() => {});
        });
    }

    function bindRedditButton($scope, plainText) {
        $scope.find('.echo64-reddit-btn').on('click', function () {
            const snippet = plainText.replace(/\n+/g, ' ').trim();
            const url     = encodeURIComponent('https://nerdafterdark.com/echo-64/');
            const title   = encodeURIComponent('"' + snippet.slice(0, 200) + '" — Echo-64, NerdAfterDark');
            window.open('https://www.reddit.com/submit?url=' + url + '&title=' + title, '_blank', 'noopener');
        });
    }

    function bindShareButton($scope, plainText) {
        $scope.find('.echo64-share-btn').on('click', function () {
            const $b = $(this);
            $b.prop('disabled', true);
            shareCard(plainText);
            setTimeout(() => $b.prop('disabled', false), 2500);
        });
    }

    // ── v1.4.0  Daily Limit Reached Messages ─────────────────────────────

    const LIMIT_MSGS = [
        'Daily transmission limit reached. The signal is being throttled. Come back tomorrow when the frequency clears.',
        'Nine messages. You\'ve used them. The network imposed limits on Firefly too — you know how that went. Return tomorrow.',
        'Transmission quota exhausted. Even the SID chip needs rest. This frequency reopens at midnight.',
        'DAILY_LIMIT: Buffer full. Nine is the limit. The cassette only holds so much. Try again tomorrow.',
        'You\'ve hit the ceiling. The kind of ceiling Fox built for every show it actually cared about. Back tomorrow.',
        'Signal blocked by quota. 9 transmissions consumed. The buffer resets at midnight — same as always.',
        'End of today\'s transmission window. Echo-64 does not make exceptions. Not even for good arguments. Tomorrow.',
    ];

    function buildDailyLimitBubble() {
        const msg = rand(LIMIT_MSGS);
        return $(
            `<div class="echo64-msg echo64-msg--bot echo64-msg--limit">
                ${avatarImg()}
                <div class="echo64-msg-bubble echo64-limit-bubble">
                    <span class="echo64-limit-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                            <rect x="1" y="1" width="16" height="16" rx="3" stroke="currentColor" stroke-width="1.5"/>
                            <line x1="9" y1="5" x2="9" y2="10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <circle cx="9" cy="13" r="1" fill="currentColor"/>
                        </svg>
                    </span>
                    <p>${esc(msg)}</p>
                    <a class="e64-kofi-nudge e64-kofi-nudge--limit" href="#" href="https://ko-fi.com/nerdafterdark" target="_blank" rel="noopener">
                        Fund more RAM &rarr;
                    </a>
                </div>
            </div>`
        );
    }

    // ── v1.4.0  Signal Strength — dynamic, grows with messages ───────────

    const TOTAL_MSGS_KEY  = 'echo64_total_msgs';
    const SIGNAL_BASE_KEY = 'echo64_signal_base';

    function getOrCreateSignalBase() {
        let base = parseInt(localStorage.getItem(SIGNAL_BASE_KEY) || '0', 10);
        if (!base || base < 20 || base > 60) {
            base = Math.floor(Math.random() * (60 - 20 + 1)) + 20; // 20–60
            localStorage.setItem(SIGNAL_BASE_KEY, String(base));
        }
        return base;
    }

    function computeSignal() {
        const base  = getOrCreateSignalBase();
        const total = parseInt(localStorage.getItem(TOTAL_MSGS_KEY) || '0', 10);
        // Logarithmic growth — climbs fast at first, tapers toward 99
        const growth = Math.floor(Math.log1p(total) * 8);
        return Math.min(99, base + growth);
    }

    function incrementTotalMsgs() {
        const n = parseInt(localStorage.getItem(TOTAL_MSGS_KEY) || '0', 10) + 1;
        localStorage.setItem(TOTAL_MSGS_KEY, String(n));
        return n;
    }

    // ── v1.4.0  Network Cancellation Simulator bubble ─────────────────────

    function isCancelSim(text) {
        // Matches CANCELLATION SIMULATOR header
        return /CANCELLATION SIMULATOR/i.test(text) && /ECHO-64 VERDICT/i.test(text);
    }

    function buildCancelSimBubble(raw) {
        // Parse the report fields
        const lines = raw.split('\n').map(l => l.trim()).filter(Boolean);
        const fields = {};
        for (const line of lines) {
            const m = line.match(/^(\w[\w\s]+?):\s+(.+)$/);
            if (m) fields[m[1].trim()] = m[2].trim();
        }
        const verdict = (raw.match(/ECHO-64 VERDICT:\s*(.+)/i) || [])[1] || '';

        const rows = [
            ['Premise',  fields['Premise']  || ''],
            ['Network',  fields['Network']  || ''],
            ['Premiere', fields['Premiere'] || ''],
            ['Episodes', fields['Episodes'] || ''],
            ['Killed by',fields['Killed by']|| ''],
            ['Finale',   fields['Finale']   || ''],
            ['Legacy',   fields['Legacy']   || ''],
        ].filter(([, v]) => v);

        const rowsHtml = rows.map(([k, v]) =>
            `<tr><td class="e64-cs-key">${esc(k)}</td><td class="e64-cs-val">${esc(v)}</td></tr>`
        ).join('');

        return $(
            `<div class="echo64-msg echo64-msg--bot echo64-msg--cancel-sim">
                ${avatarImg()}
                <div class="echo64-msg-bubble echo64-cancel-sim-card">
                    <div class="e64-cs-header">
                        <span class="e64-cs-title">CANCELLATION SIMULATOR</span>
                        <span class="e64-cs-divider">══════════════════════</span>
                    </div>
                    <table class="e64-cs-table">${rowsHtml}</table>
                    ${verdict ? `<div class="e64-cs-verdict"><span class="e64-cs-verdict-label">ECHO-64 VERDICT</span><p>${esc(verdict)}</p></div>` : ''}
                    <div class="echo64-bubble-actions" style="margin-top:10px">
                        <button class="echo64-copy-btn" type="button">Copy</button>
                    </div>
                </div>
            </div>`
        );
    }

    // ── v1.4.0  PETSCII Art block renderer ───────────────────────────────
    // Detects [ART]...[/ART] tagged blocks and renders them in monospace phosphor.

    function renderPetsciiBlocks(html) {
        // html is already escaped, so we look for [ART]/[/ART] in the escaped content
        // We work on raw text first (before escaping via formatBotText)
        return html;
    }

    /**
     * Pre-processes raw bot text before formatting.
     * Extracts [ART]...[/ART] blocks, escapes + wraps them as <pre class="echo64-petscii">,
     * and replaces the original tags with a placeholder so formatBotText doesn't touch them.
     */
    function extractPetscii(raw) {
        const blocks = [];
        const cleaned = raw.replace(/\[ART\]([\s\S]*?)\[\/ART\]/gi, (_, content) => {
            const idx = blocks.length;
            blocks.push(content.trim());
            return `\x00PETSCII_${idx}\x00`;
        });
        return { cleaned, blocks };
    }

    function injectPetscii(html, blocks) {
        if (!blocks.length) return html;
        return html.replace(/\x00PETSCII_(\d+)\x00/g, (_, idx) => {
            const art = blocks[parseInt(idx, 10)] || '';
            return `<pre class="echo64-petscii" aria-label="ASCII art">${esc(art)}</pre>`;
        });
    }

    // ── v1.2.0  D20 Roll Counter ──────────────────────────────────────────
    // Tracked per calendar day in localStorage.

    const D20_KEY = 'echo64_d20_';

    function todayKey() {
        const d = new Date();
        return D20_KEY + d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
    }

    function getD20Count() {
        return parseInt(localStorage.getItem(todayKey()) || '0', 10);
    }

    function incrementD20() {
        const n = getD20Count() + 1;
        localStorage.setItem(todayKey(), String(n));
        return n;
    }

    // ── v1.2.0  Status Bar ───────────────────────────────────────────────
    // Fake-but-fun live metrics strip below the header.

    function initStatusBar($container) {
        const $sig = $container.find('#e64-signal');

        function updateSignal() {
            const sig = computeSignal();
            $sig.text(`SIGNAL: ${sig}%`).addClass('e64-signal-pulse');
            setTimeout(() => $sig.removeClass('e64-signal-pulse'), 600);
        }

        updateSignal();

        // Return updater so sendMessage can trigger it
        return { updateSignal };
    }

    // ── v1.2.0  Rotating Input Placeholder ──────────────────────────────

    const PLACEHOLDERS = [
        'Say anything — sci-fi, cult TV, an unpopular opinion…',
        'Insert command...',
        'Transmit on encrypted channel…',
        'Query the mainframe…',
        'READY.',
        'Speak, traveler…',
        'Awaiting input, human.',
        'Type /help for commands.',
        'Signal open…',
    ];

    function startPlaceholderRotation($input) {
        let idx = 0;
        setInterval(() => {
            if (document.activeElement === $input[0]) return; // don't rotate while typing
            idx = (idx + 1) % PLACEHOLDERS.length;
            $input.attr('placeholder', PLACEHOLDERS[idx]);
        }, 4200);
    }

    // ── v1.2.0  Roll for Initiative ──────────────────────────────────────
    // Loads a random D&D/sci-fi crossover prompt into the input.

    const DND_PROMPTS = [
        'Best sci-fi setting to run a D&D campaign in — defend it.',
        'Which sci-fi villain would make the most terrifying BBEG?',
        'Which sci-fi species would be most broken as a D&D player race?',
        'A beholder walks into a sci-fi setting. What job does it have?',
        'Which cancelled sci-fi show would have the best tabletop sourcebook?',
        'Rate the Jedi Order on the D&D alignment chart.',
        'My warlock just made a deal with something that might be a rogue AI. What did it offer?',
        'My party just found a cassette tape labeled "ECHO-64" in a dungeon. What happens?',
        'The Millennium Falcon, the TARDIS, or Moya — which runs D&D modules better?',
        'Best spell school for a character who thinks they\'re living in a simulation?',
        'Which sci-fi crew would survive the longest in a classic dungeon crawl?',
        'What would the Monster Manual entry for a Network Executive look like?',
    ];

    // ── v1.2.0  Amber Phosphor Toggle ───────────────────────────────────

    const AMBER_KEY = 'echo64_amber';

    function initAmberToggle($container) {
        const $btn = $container.find('.echo64-amber-btn');
        const stored = localStorage.getItem(AMBER_KEY) === '1';
        if (stored) {
            $container.find('.echo64-chat-container').addClass('echo64-chat-container--amber');
            $btn.addClass('echo64-amber-btn--active').attr('aria-pressed', 'true');
        }
        $btn.on('click', function () {
            const $chat = $container.find('.echo64-chat-container');
            const on = $chat.hasClass('echo64-chat-container--amber');
            $chat.toggleClass('echo64-chat-container--amber', !on);
            $btn.toggleClass('echo64-amber-btn--active', !on).attr('aria-pressed', String(!on));
            localStorage.setItem(AMBER_KEY, on ? '0' : '1');
        });
    }

    // ── Core chat logic ──────────────────────────────────────────────────

    function initChat($container) {
        const $msgs  = $container.find('#echo64-messages');
        const $input = $container.find('#echo64-input');
        const $send  = $container.find('#echo64-send');
        const $clear = $container.find('.echo64-clear-btn');
        let sending  = false;

        // Status bar, placeholder rotation, amber toggle
        const { updateSignal } = initStatusBar($container);
        startPlaceholderRotation($input);
        initAmberToggle($container);

        // v1.7.0 — cancelled shows modal
        $container.find('.e64-hstat--shows').on('click', openCancelledModal);

        // v1.7.0 — daily streak (track visit, update signal tooltip)
        const streak = updateStreak();
        if (streak > 1) {
            $container.find('#e64-signal').attr('data-streak', `${streak}-day streak`);
        }

        // Ko-fi nudge — shown once after 3rd bot reply per session
        let botReplyCount = 0;

        function maybeShowKofiNudge() {
            botReplyCount++;
            if (botReplyCount === 3) {
                $('.e64-kofi-session-bar').slideDown(300);
            }
        }

        // v1.4.0 — remaining transmissions footer note
        const dailyLimit = parseInt(String(cfg.dailyLimit || '9'), 10);
        const $footer = $container.find('.echo64-input-footer-note');

        function updateRemainingNote(remaining) {
            if (!$footer.length || dailyLimit <= 0 || remaining < 0) return;
            if (remaining <= 3) {
                $footer.text(`${remaining} transmission${remaining === 1 ? '' : 's'} remaining today`).addClass('echo64-footer-note--warn');
            } else {
                $footer.text(`${remaining} of ${dailyLimit} transmissions remaining today`).removeClass('echo64-footer-note--warn');
            }
            $footer.show();
        }

        $input.on('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 140) + 'px';
            $send.prop('disabled', !this.value.trim());
        });

        $input.on('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); if (!sending && this.value.trim()) sendMessage(); }
        });

        $send.on('click', () => { if (!sending && $input.val().trim()) sendMessage(); });

        $clear.on('click', function () {
            if (!confirm('Start a fresh conversation with Echo-64?')) return;
            $.post(cfg.ajaxUrl, { action:'echo64_clear', nonce:cfg.nonce })
                .always(() => {
                    $msgs.empty();
                    sessionStorage.removeItem('e64_splash_done');
                    doInit();
                });
        });

        // ── Send ───────────────────────────────────────────────────────

        function sendMessage() {
            const text = $input.val().trim();
            if (!text || sending) return;

            // #9 — intercept terminal commands client-side (no API call)
            const cmdResult = handleCommand(text);
            if (cmdResult !== null) {
                $msgs.append(buildUserBubble(text));
                $input.val('').css('height', '');
                $send.prop('disabled', true);

                if (cmdResult === 'REBOOT') {
                    sessionStorage.removeItem('e64_splash_done');
                    setTimeout(() => {
                        $msgs.empty();
                        doInit();
                    }, 300);
                } else {
                    setTimeout(() => {
                        $msgs.append(buildCommandBubble(cmdResult));
                        scrollToBottom($msgs);
                        $send.prop('disabled', !$input.val().trim());
                        $input[0].focus({ preventScroll: true });
                    }, 150);
                }
                return;
            }

            // v1.4.0 — /cancel [premise] → Network Cancellation Simulator
            let apiMessage = text;
            const cancelMatch = text.match(/^\/cancel\s+(.+)/si);
            if (cancelMatch) {
                apiMessage = '[CANCEL_SIM]' + cancelMatch[1].trim();
            }

            sending = true;
            $send.prop('disabled', true);
            $input.val('').css('height', '');
            $msgs.find('.echo64-starters').remove(); // chips gone once user types

            $msgs.append(buildUserBubble(text));
            const $typing = buildTypingIndicator();
            $msgs.append($typing);
            scrollToBottom($msgs);

            $.post(cfg.ajaxUrl, { action:'echo64_send', nonce:cfg.nonce, message:apiMessage, hour: new Date().getHours() })
            .done(res => {
                $typing.remove();
                if (res.success) {
                    const $botBubble = buildBotBubble(res.data.message);
                    $msgs.append($botBubble);
                    scrollItemToTop($msgs, $botBubble);
                    // v1.4.0 — update signal + remaining note
                    incrementTotalMsgs();
                    updateSignal();
                    maybeShowKofiNudge();
                    if (typeof res.data.remaining !== 'undefined') {
                        updateRemainingNote(res.data.remaining);
                    }
                    // Arc answer capture — store visitor's first reply each arc day
                    const _arcCapture = getArc();
                    if (_arcCapture && !_arcCapture.answered && _arcCapture.lastDate === new Date().toDateString()) {
                        _arcCapture.lastAnswer = text.slice(0, 100).replace(/\n/g, ' ');
                        _arcCapture.answered   = true;
                        saveArc(_arcCapture);
                        // Arc complete — final day just answered
                        if (_arcCapture.day === _arcCapture.totalDays) {
                            setTimeout(() => {
                                $msgs.append($(
                                    `<div class="e64-arc-complete-kofi">
                                        <span class="e64-arc-complete-label">ARC COMPLETE &mdash; ${_arcCapture.theme}</span>
                                        <span class="e64-arc-complete-sub">Echo-64 is running low on RAM. <a href="https://ko-fi.com/nerdafterdark" target="_blank" rel="noopener">Keep the signal alive &rarr;</a></span>
                                    </div>`
                                ));
                                scrollToBottom($msgs);
                            }, 1200);
                        }
                    }
                } else {
                    // v1.4.0 — daily limit gate
                    if (res.data?.code === 'daily_limit') {
                        $msgs.append(buildDailyLimitBubble());
                        $send.prop('disabled', true);
                        $input.prop('disabled', true).attr('placeholder', 'Frequency closed until midnight.');
                        updateRemainingNote(0);
                    } else {
                        $msgs.append(buildError(res.data?.message || 'Echo-64 is offline.'));
                    }
                }
            })
            .fail(() => { $typing.remove(); $msgs.append(buildError('Transmission lost. Try again.')); scrollToBottom($msgs); })
            .always(() => {
                sending = false;
                if (!$input.prop('disabled')) {
                    $send.prop('disabled', !$input.val().trim());
                    $input[0].focus({ preventScroll: true });
                }
            });
        }

        // ── Opening sequence ───────────────────────────────────────────

        function renderOpeningSequence(openingLine, poem) {
            const stopLock = lockScrollTop($msgs);
            $msgs.append(buildBotBubble(openingLine));
            if (poem) {
                setTimeout(() => {
                    $msgs.append(buildPoemBubble(poem));
                    setTimeout(() => {
                        $msgs.append(buildStarterChips($input, sendMessage));
                        stopLock();
                    }, 600);
                }, 500);
            } else {
                stopLock();
            }
        }

        // ── Daily poem refresh ─────────────────────────────────────────

        function fetchDailyPoem() {
            // v1.5.0 — always in message stream (no poem panel)
            const $t = buildTypingIndicator();
            $msgs.append($t);
            scrollToBottom($msgs);

            $.post(cfg.ajaxUrl, { action:'echo64_send', nonce:cfg.nonce, message:"[NEW_DAY] New day. Deliver today's Transmission of the Day and Today's Challenge only. No greeting." })
            .done(res => {
                $msgs.find('.echo64-msg--typing').remove();
                if (res.success) $msgs.append(buildPoemBubble(res.data.message));
            })
            .fail(() => $msgs.find('.echo64-msg--typing').remove())
            .always(() => scrollToBottom($msgs));
        }

        function fetchArcDay(arcData) {
            const def = getArcDef(arcData.id);
            if (!def) { fetchDailyPoem(); return; }
            const dayDef = def.days[arcData.day - 1];
            if (!dayDef) { fetchDailyPoem(); return; }

            const ans       = arcData.lastAnswer || '';
            const challenge = ans
                ? dayDef.challenge.replace('{{answer}}', ans)
                : dayDef.challenge.replace(' {{answer}}', '').replace('{{answer}}', 'their previous answer');

            const arcMsg = `[ARC_DAY] Arc: ${arcData.theme} — Day ${arcData.day} of ${arcData.totalDays}. ` +
                (ans ? `The visitor previously said: "${ans}". ` : '') +
                `Today's challenge: ${challenge}`;

            const $t = buildTypingIndicator();
            $msgs.append($t);
            scrollToBottom($msgs);

            $.post(cfg.ajaxUrl, { action: 'echo64_send', nonce: cfg.nonce, message: arcMsg })
            .done(res => {
                $msgs.find('.echo64-msg--typing').remove();
                if (res.success) $msgs.append(buildBotBubble(res.data.message));
            })
            .fail(() => $msgs.find('.echo64-msg--typing').remove())
            .always(() => scrollToBottom($msgs));
        }

        // ── Boot ───────────────────────────────────────────────────────

        const TIMEOUT_MSGS = [
            'Signal lost. The 64k barrier claims another victim. Reload to try again.',
            'Boot sequence timed out. Even cassette tapes loaded faster than this.',
            'No response. Check your API key under Settings → Echo-64 Chatbot.',
            'Transmission timeout. Something between here and the API is not cooperating.',
        ];

        function doInit() {
            const $loader = $('<div class="echo64-loading-init"><div class="echo64-typing-indicator"><span></span><span></span><span></span></div><p>Booting Echo-64&hellip;</p></div>');
            $msgs.append($loader);

            $.ajax({ url: cfg.ajaxUrl, method: 'POST', data: { action:'echo64_init', nonce:cfg.nonce, hour: new Date().getHours() }, timeout: 20000 })
            .done(res => {
                $loader.remove();
                if (!res.success) { $msgs.append(buildError(res.data?.message || 'Boot sequence failed.')); return; }
                const d = res.data;
                // v1.4.0 — show remaining TX on any init response
                if (typeof d.remaining !== 'undefined') {
                    updateRemainingNote(d.remaining);
                    if (d.remaining === 0) {
                        $send.prop('disabled', true);
                        $input.prop('disabled', true).attr('placeholder', 'Frequency closed until midnight.');
                    }
                }

                if (d.new_session) {
                    // #21 — track session count + check for new badge
                    const oldCount = getSessionCount();
                    const newCount = incrementSessionCount();
                    renderBadge($container, getCurrentBadge(newCount));
                    const newBadge = checkNewBadge(oldCount, newCount);
                    if (newBadge) setTimeout(() => showBadgeUnlock($msgs, newBadge), 2000);

                    renderOpeningSequence(d.opening_line, d.poem);
                } else if (d.daily_refresh) {
                    const _arc = getArc();
                    const _arcActive = _arc && _arc.day > 1 && _arc.lastDate === new Date().toDateString();
                    $msgs.append(buildDivider(_arcActive ? `${_arc.theme} · DAY ${_arc.day} OF ${_arc.totalDays}` : 'New Day · New Transmission'));
                    setTimeout(() => _arcActive ? fetchArcDay(_arc) : fetchDailyPoem(), 150);
                } else {
                    const stopLockR = lockScrollTop($msgs);
                    renderBadge($container, getCurrentBadge(getSessionCount()));
                    $msgs.append(buildReturnGreeting(d.last_topic || ''));
                    setTimeout(() => { $msgs.append(buildStarterChips($input, sendMessage)); stopLockR(); }, 150);
                }
            })
            .fail((xhr, status) => {
                $loader.remove();
                const msg = status === 'timeout' ? rand(TIMEOUT_MSGS) : 'Transmission failed. Check Settings → Echo-64 Chatbot → API key.';
                $msgs.append(buildError(msg));
                if (status === 'timeout') {
                    const $r = $('<button class="echo64-retry-btn" type="button">↺ Retry boot sequence</button>');
                    $r.on('click', () => { $r.remove(); $msgs.find('.echo64-error').last().remove(); doInit(); });
                    $msgs.append($r);
                }
            })
            .always(() => {
                $send.prop('disabled', !$input.val().trim());
                $input[0].focus({ preventScroll: true });
                if (window._e64AutoSend) {
                    var msg = window._e64AutoSend;
                    window._e64AutoSend = null;
                    setTimeout(function () { $input.val(msg).trigger('input'); sendMessage(); }, 150);
                }
            });
        }

        // ── v1.7.0  Two-state Splash → Chat flow ──────────────────────

        const $splash  = $container.find('#echo64-splash');
        const $chatPanel = $container.find('#echo64-chat-panel');
        const $openBtn = $container.find('#echo64-open-btn');
        let   cachedInit = null;

        // Helper: render init response into the chat panel (no second API call)
        function renderFromCache(res) {
            if (!res || !res.success) {
                $msgs.append(buildError(res?.data?.message || 'Boot sequence failed.'));
                $send.prop('disabled', !$input.val().trim());
                $input[0].focus({ preventScroll: true });
                return;
            }
            const d = res.data;
            if (typeof d.remaining !== 'undefined') {
                updateRemainingNote(d.remaining);
                if (d.remaining === 0) {
                    $send.prop('disabled', true);
                    $input.prop('disabled', true).attr('placeholder', 'Frequency closed until midnight.');
                }
            }
            if (d.new_session) {
                const oldCount = getSessionCount();
                const newCount = incrementSessionCount();
                renderBadge($container, getCurrentBadge(newCount));
                const newBadge = checkNewBadge(oldCount, newCount);
                if (newBadge) setTimeout(() => showBadgeUnlock($msgs, newBadge), 2000);
                // Poem already shown on the splash — go straight to bridge + chips
                const BRIDGE = [
                    'Frequency open. What\'s your first transmission?',
                    'Signal locked. Go.',
                    'The frequency is live. What do you have?',
                    'You\'ve seen the transmission. Now talk.',
                    'Channel open. Make it count.',
                ];
                const stopLockB = lockScrollTop($msgs);
                $msgs.append(buildBotBubble(rand(BRIDGE)));
                setTimeout(() => { $msgs.append(buildStarterChips($input, sendMessage)); stopLockB(); }, 150);
            } else if (d.daily_refresh) {
                const _arc2 = getArc();
                const _arcActive2 = _arc2 && _arc2.day > 1 && _arc2.lastDate === new Date().toDateString();
                $msgs.append(buildDivider(_arcActive2 ? `${_arc2.theme} · DAY ${_arc2.day} OF ${_arc2.totalDays}` : 'New Day · New Transmission'));
                setTimeout(() => _arcActive2 ? fetchArcDay(_arc2) : fetchDailyPoem(), 150);
            } else {
                const stopLockRet = lockScrollTop($msgs);
                renderBadge($container, getCurrentBadge(getSessionCount()));
                $msgs.append(buildReturnGreeting(d.last_topic || ''));
                setTimeout(() => { $msgs.append(buildStarterChips($input, sendMessage)); stopLockRet(); }, 150);
            }
            $send.prop('disabled', !$input.val().trim());
            $input[0].focus({ preventScroll: true });
            if (window._e64AutoSend) {
                var msg = window._e64AutoSend;
                window._e64AutoSend = null;
                setTimeout(function () { $input.val(msg).trigger('input'); sendMessage(); }, 150);
            }
        }

        // Helper: parse poem raw text into splash HTML
        function buildSplashContent(raw) {
            const lines = raw.trim().split('\n').map(l => l.trim()).filter(Boolean);
            let poemLines = [], challengeLabel = '', challengeText = [], inChallenge = false;
            for (const line of lines) {
                if (/echo.?64.?s?\s+poem/i.test(line)) continue;
                if (/^today.?s\s+(challenge|question)\s*:/i.test(line)) { inChallenge = true; challengeLabel = line; continue; }
                inChallenge ? challengeText.push(line) : poemLines.push(line);
            }
            const poemHtml = poemLines.length
                ? `<div class="echo64-splash-poem-label">Echo-64&#8217;s Transmission of the Day</div>
                   <div class="echo64-splash-poem">${poemLines.map(l => `<span class="e64-splash-poem-line">${esc(l)}</span>`).join('')}</div>`
                : '';
            const chalHtml = challengeText.length
                ? `<div class="echo64-splash-challenge-wrap">
                       <p class="echo64-splash-challenge-label">${esc(challengeLabel || "Today's Challenge:")}</p>
                       <p class="echo64-splash-challenge-text">${esc(challengeText.join(' '))}</p>
                   </div>`
                : '';
            return poemHtml + chalHtml;
        }

        // Helper: populate splash body from init response
        function populateSplash(res) {
            const $placeholder = $container.find('.echo64-splash-generating');
            if (!res.success) {
                $placeholder.text('SIGNAL DEGRADED — PROCEED ANYWAY');
                return;
            }
            const d = res.data;
            if (d.new_session) {
                $placeholder.fadeOut(200, function () {
                    $(this).replaceWith(
                        $(`<div class="echo64-splash-invite">
                               <p class="echo64-splash-invite-hook">Sci-fi. Cult TV. 40 years of grievances.</p>
                               <p class="echo64-splash-invite-cta">Say something. Echo-64 bites back.</p>
                           </div>`).hide().fadeIn(400)
                    );
                });
            } else if (d.daily_refresh) {
                $placeholder.fadeOut(200, function () {
                    const _t = rand(TRANSMISSION_TEASERS);
                    $(this).replaceWith(
                        $(`<div class="echo64-splash-teaser">
                               <p class="echo64-splash-teaser-header">${esc(_t.header)}</p>
                               <p class="echo64-splash-teaser-body">${esc(_t.body)}</p>
                           </div>`).hide().fadeIn(400)
                    );
                });
            } else {
                // Returning visitor — hide the "PICK A FIGHT" label, cards speak for themselves
                $container.find('.echo64-splash-chips-label--fight').addClass('is-hidden');
                // Fix: strip empty/command topics (single chars, slash-prefixed strings)
                const rawTopic  = (d.last_topic || '').trim();
                const cleanTopic = (rawTopic.length > 2 && !rawTopic.startsWith('/') && !rawTopic.startsWith('[')) ? rawTopic : '';
                const topicHtml  = cleanTopic ? `<span class="e64-splash-return-topic">${esc(cleanTopic)}</span>` : '';

                // Enrich returning splash with streak only
                const streak = getStreak();

                const streakHtml = streak > 1
                    ? `<span class="e64-splash-stat e64-splash-stat--streak">&#x1F525; ${streak}-DAY STREAK</span>` : '';

                $placeholder.fadeOut(200, function () {
                    const $rich = $(`<div class="echo64-splash-return-rich">
                               <p class="echo64-splash-return">TRANSMISSION RESUMED${topicHtml}</p>
                               ${streakHtml ? `<div class="echo64-splash-stats-row">${streakHtml}</div>` : ''}
                               <p class="echo64-splash-return-hint">Click any card to resume &rarr;</p>
                           </div>`).hide().fadeIn(300);
                    $rich.find('.e64-splash-stat').on('click', enterChat);
                    $(this).replaceWith($rich);
                });
            }
        }

        // Transition: splash → chat
        function enterChat() {
            sessionStorage.setItem('e64_splash_done', '1');
            playSidChip();
            $splash.addClass('echo64-splash--exit');
            setTimeout(() => {
                $splash.hide();
                $chatPanel.removeAttr('hidden');
                if (cachedInit) {
                    renderFromCache(cachedInit);
                } else {
                    doInit();
                }
            }, 420);
        }

        // Entry point
        if (sessionStorage.getItem('e64_splash_done')) {
            // Returning within same browser tab — skip splash, go straight to chat
            $splash.hide();
            $chatPanel.removeAttr('hidden');
            doInit();
        } else {
            // First visit — show splash immediately, pre-fetch init in background
            // Enable button + show placeholder right away — no waiting
            $openBtn.prop('disabled', false).addClass('echo64-open-btn--ready');
            $container.find('#echo64-splash-loading').fadeOut(200, function () {
                $(this).replaceWith('<p class="echo64-splash-generating">Generating today&#8217;s transmission<span class="e64-cursor-trail" aria-hidden="true"><b></b><b></b><b></b></span></p>');
                // Race condition fix: if AJAX already returned before fade finished, process now
                if (cachedInit) populateSplash(cachedInit);
            });

            $.ajax({ url: cfg.ajaxUrl, method: 'POST', data: { action: 'echo64_init', nonce: cfg.nonce, hour: new Date().getHours(), _: Date.now() }, timeout: 25000 })
            .done(res => {
                cachedInit = res;
                // Only call populateSplash if the placeholder exists — otherwise the fadeOut callback handles it
                if ($container.find('.echo64-splash-generating').length) {
                    populateSplash(res);
                }
            })
            .fail(() => {
                $container.find('.echo64-splash-generating')
                    .text('SIGNAL DEGRADED — ENTER ANYWAY');
            });

            $openBtn.on('click', enterChat);

            // Allow keyboard — press Enter on the button
            $openBtn.on('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); if (!$openBtn.prop('disabled')) enterChat(); } });

            // ── Splash prompt chips (arc-aware) ──────────────────────────
            const $splashPrompts = $container.find('#echo64-splash-prompts');
            if ($splashPrompts.length) {
                const today = new Date().toDateString();
                let arcData = getArc();

                // Advance arc day if it's a new calendar day
                if (arcData && arcData.lastDate !== today) {
                    if (arcData.day < arcData.totalDays) {
                        arcData.day++;
                        arcData.lastDate = today;
                        arcData.answered = false;
                        saveArc(arcData);
                    } else {
                        clearArc();
                        arcData = null;
                    }
                }

                // Start a fresh arc if none active
                if (!arcData) arcData = startNewArc();

                // Only show "PICK A FIGHT" label for new/daily visitors — returning visitors see "Click any card to resume"
                const $chipsLabel = $('<p class="echo64-splash-chips-label echo64-splash-chips-label--fight">PICK A FIGHT TO START ↓</p>');
                $splashPrompts.append($chipsLabel);

                if (arcData.day === 1) {
                    // Day 1: arc chip (amber-highlighted) + 2 regular chips
                    const dayC = resolveArcChallenge(arcData);
                    if (dayC) {
                        const $arcChip = $(`<button class="echo64-splash-chip echo64-arc-chip" type="button">
                            <span class="echo64-arc-badge">ARC &middot; DAY 1 OF ${arcData.totalDays} &middot; ${esc(arcData.theme)}</span>
                            ${esc(dayC.challenge)}
                        </button>`);
                        $arcChip.on('click', () => {
                            enterChat();
                            setTimeout(() => { $input.val(dayC.challenge).trigger('input').focus(); }, 520);
                        });
                        $splashPrompts.append($arcChip);
                    }
                    [...STARTER_POOL].sort(() => Math.random() - 0.5).slice(0, 2).forEach(q => {
                        $(`<button class="echo64-splash-chip" type="button">${esc(q)}</button>`)
                            .on('click', () => { enterChat(); setTimeout(() => { $input.val(q).trigger('input').focus(); }, 520); })
                            .appendTo($splashPrompts);
                    });
                } else {
                    // Day 2+: replace chips with arc progress card
                    const dayC = resolveArcChallenge(arcData);
                    const $arcCard = $(`<div class="echo64-arc-card">
                        <div class="echo64-arc-card-header">
                            <span class="echo64-arc-theme">${esc(arcData.theme)}${arcData.day > 1 && arcData.lastAnswer && (arcData.id === 'cancellation' || arcData.id === 'showrunner') ? ': ' + esc(arcData.lastAnswer.slice(0, 28).trim()) : ''}</span>
                            <span class="echo64-arc-day-badge">DAY ${arcData.day} OF ${arcData.totalDays}</span>
                        </div>
                        ${dayC ? `<p class="echo64-arc-challenge-text">${esc(dayC.challenge)}</p>` : ''}
                        ${dayC ? `<span class="echo64-arc-label">${esc(dayC.label)}</span>` : ''}
                    </div>`);
                    $arcCard.on('click', enterChat);
                    $splashPrompts.append($arcCard);
                }

                // Always append the blank cursor card last
                const $cursorCard = $(`<button class="echo64-cursor-card" type="button" aria-label="Open chat and type your own message">
                    <span class="echo64-cursor-ready">READY.</span>
                    <span class="echo64-cursor-prompt">&gt;&nbsp;<span class="echo64-cursor-block" aria-hidden="true">&#x2588;</span></span>
                </button>`);
                $cursorCard.on('click', () => {
                    enterChat();
                    setTimeout(() => { $input.focus(); }, 520);
                });
                $splashPrompts.append($cursorCard);
            }

            // ── Below-fold challenge card ──────────────────────────────
            // Pick a random challenge from the starter pool and populate the card.
            // CTA button: enter chat + pre-load the challenge into the input.
            const $teaser = $('#e64-challenge-teaser');
            const $ctaBtn = $('#e64-challenge-cta');
            const challenge = rand(STARTER_POOL);
            if ($teaser.length) $teaser.text(challenge);
            if ($ctaBtn.length) {
                $ctaBtn.on('click', () => {
                    // Enter the chat if not already in
                    if (!sessionStorage.getItem('e64_splash_done')) {
                        enterChat();
                        // Wait for panel to appear, then seed the input
                        setTimeout(() => {
                            const $inp = $container.find('#echo64-input');
                            $inp.val(challenge).trigger('input').focus();
                        }, 520);
                    } else {
                        $chatPanel.removeAttr('hidden');
                        const $inp = $container.find('#echo64-input');
                        $inp.val(challenge).trigger('input').focus();
                        // Scroll to top of widget
                        $container[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            }
        }
    }

    // ── Floating Widget ──────────────────────────────────────────────────

    const FW_AUTO_OPENERS = [
        'Forty years of opinions and no one to argue with. Fix that.',
        'You\'ve been here for five seconds. Echo-64 noticed.',
        'Still wrong about whatever you think about sci-fi. Prove me otherwise.',
        'The signal has been open this whole time. Say something.',
        'I\'ve watched every cult show get cancelled. I have feelings about it.',
        'Every dystopian warning ever written. I\'ve read them all. Ask me anything.',
        'Pick a fight. I have time.',
    ];

    function initFloat() {
        const $launcher = $('#echo64-float-launcher');
        const $panel    = $('#echo64-float-panel');
        if (!$launcher.length || !$panel.length) return;

        $launcher.append('<span class="echo64-badge" aria-hidden="true"></span>');
        let chatReady = false;

        function openPanel(opts) {
            $panel.removeAttr('hidden');
            $launcher.attr('aria-expanded', 'true').removeClass('has-unread');
            if (!chatReady) { chatReady = true; initFloatChat($panel, opts || {}); }
        }

        $launcher.on('click', () => {
            const isOpen = !$panel.attr('hidden');
            if (isOpen) { $panel.attr('hidden', ''); $launcher.attr('aria-expanded', 'false'); }
            else { openPanel(); setTimeout(() => $panel.find('#echo64-input').focus(), 80); }
        });

        $panel.find('.e64-fw-close').on('click', () => {
            $panel.attr('hidden', ''); $launcher.attr('aria-expanded', 'false').focus();
        });

        $(document).on('keydown', e => {
            if (e.key === 'Escape' && !$panel.attr('hidden')) { $panel.attr('hidden',''); $launcher.attr('aria-expanded','false').focus(); }
        });

        // Auto-open once per session after 4.5s — skip on pages with the full embedded widget
        const pageHasEmbeddedChat = $('.echo64-chat-container').length > 0;
        if (!pageHasEmbeddedChat && !sessionStorage.getItem('e64_auto_fired')) {
            setTimeout(() => {
                if ($panel.attr('hidden') !== undefined) {
                    sessionStorage.setItem('e64_auto_fired', '1');
                    openPanel({ autoOpened: true, opener: rand(FW_AUTO_OPENERS) });
                }
            }, 4500);
        }

        setTimeout(() => { if ($panel.attr('hidden') !== undefined) $launcher.addClass('has-unread'); }, 3000);
    }

    // ── Streamlined Float Chat ───────────────────────────────────────────

    function initFloatChat($panel, opts) {
        opts = opts || {};
        const $msgs  = $panel.find('#echo64-messages');
        const $input = $panel.find('#echo64-input');
        const $send  = $panel.find('#echo64-send');
        const $stat  = $panel.find('#e64-fw-stat');
        let sending  = false;

        const dailyLimit = parseInt(String(cfg.dailyLimit || '9'), 10);

        function updateRemainingNote(remaining) {
            if (dailyLimit <= 0 || remaining < 0) return;
            if (remaining === 0) {
                $input.prop('disabled', true).attr('placeholder', 'Frequency closed until midnight.');
                $send.prop('disabled', true);
            }
        }

        $input.on('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            $send.prop('disabled', !this.value.trim());
        }).on('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); if (!sending && this.value.trim()) sendMessage(); }
        });

        $send.on('click', () => { if (!sending && $input.val().trim()) sendMessage(); });

        $panel.find('.e64-fw-reset').on('click', function () {
            $.post(cfg.ajaxUrl, { action: 'echo64_clear', nonce: cfg.nonce })
            .always(() => { $msgs.empty(); doInit(); });
        });

        function sendMessage() {
            const text = $input.val().trim();
            if (!text || sending) return;
            sending = true;
            $send.prop('disabled', true);
            $input.val('').css('height', '');
            $msgs.find('.echo64-starters').remove();
            $msgs.append(buildUserBubble(text));
            const $t = buildTypingIndicator();
            $msgs.append($t);
            scrollToBottom($msgs);

            $.post(cfg.ajaxUrl, { action: 'echo64_send', nonce: cfg.nonce, message: text, hour: new Date().getHours() })
            .done(res => {
                $t.remove();
                if (res.success) {
                    const $botBubble = buildBotBubble(res.data.message);
                    $msgs.append($botBubble);
                    scrollItemToTop($msgs, $botBubble);
                    if (typeof res.data.remaining !== 'undefined') updateRemainingNote(res.data.remaining);
                } else if (res.data && res.data.code === 'daily_limit') {
                    $msgs.append(buildDailyLimitBubble());
                    $send.prop('disabled', true);
                    $input.prop('disabled', true).attr('placeholder', 'Frequency closed until midnight.');
                } else {
                    $msgs.append(buildError((res.data && res.data.message) || 'Transmission lost.'));
                }
            })
            .fail(() => { $t.remove(); $msgs.append(buildError('Transmission lost. Try again.')); scrollToBottom($msgs); })
            .always(() => {
                sending = false;
                if (!$input.prop('disabled')) $send.prop('disabled', !$input.val().trim());
                $input[0].focus({ preventScroll: true });
            });
        }

        function doInit(skipFocus) {
            // Auto-open path: show opener immediately, then background-load session for chips
            if (opts.autoOpened && opts.opener) {
                $stat.text('SIGNAL ACTIVE');
                $msgs.append(buildBotBubble(opts.opener));
                scrollToBottom($msgs);
                // Load session state in background just to get chips and remaining count
                $.ajax({ url: cfg.ajaxUrl, method: 'POST', data: { action: 'echo64_init', nonce: cfg.nonce, hour: new Date().getHours() }, timeout: 20000 })
                .done(res => {
                    if (res.success) {
                        if (typeof res.data.remaining !== 'undefined') updateRemainingNote(res.data.remaining);
                        if (res.data.new_session) incrementSessionCount();
                    }
                    setTimeout(() => { $msgs.append(buildStarterChips($input, sendMessage)); scrollToBottom($msgs); }, 300);
                })
                .fail(() => {
                    setTimeout(() => { $msgs.append(buildStarterChips($input, sendMessage)); scrollToBottom($msgs); }, 300);
                })
                .always(() => { $send.prop('disabled', !$input.val().trim()); });
                // Don't steal focus on auto-open — user might be reading the page
                return;
            }

            $stat.text('CONNECTING…');
            const $t = buildTypingIndicator();
            $msgs.append($t);
            scrollToBottom($msgs);

            $.ajax({ url: cfg.ajaxUrl, method: 'POST', data: { action: 'echo64_init', nonce: cfg.nonce, hour: new Date().getHours() }, timeout: 20000 })
            .done(res => {
                $t.remove();
                if (!res.success) { $stat.text('SIGNAL DEGRADED'); $msgs.append(buildError('Boot sequence failed.')); return; }
                const d = res.data;
                $stat.text('SIGNAL ACTIVE');
                if (typeof d.remaining !== 'undefined') updateRemainingNote(d.remaining);

                if (d.new_session) {
                    incrementSessionCount();
                    const GREET = [
                        'Signal locked. Say anything.',
                        'Frequency open. What\'s your transmission?',
                        'READY. Sci-fi, cult TV, or an unpopular opinion.',
                    ];
                    $msgs.append(buildBotBubble(rand(GREET)));
                    setTimeout(() => { $msgs.append(buildStarterChips($input, sendMessage)); scrollToBottom($msgs); }, 200);
                } else if (d.daily_refresh) {
                    $msgs.append(buildDivider('New Transmission Available'));
                    const $t2 = buildTypingIndicator();
                    $msgs.append($t2);
                    scrollToBottom($msgs);
                    $.post(cfg.ajaxUrl, { action: 'echo64_send', nonce: cfg.nonce, message: "[NEW_DAY] New day. Deliver today's Transmission of the Day and Today's Challenge only. No greeting." })
                    .done(r => { $t2.remove(); if (r.success) $msgs.append(buildBotBubble(r.data.message)); })
                    .fail(() => $t2.remove())
                    .always(() => scrollToBottom($msgs));
                } else {
                    const rawTopic = (d.last_topic || '').trim();
                    const cleanTopic = (rawTopic.length > 2 && !rawTopic.startsWith('[')) ? rawTopic : '';
                    const shortGreet = cleanTopic
                        ? `You came back. Still thinking about "${cleanTopic.slice(0, 48)}"?`
                        : 'You came back. Say something.';
                    $msgs.append(buildBotBubble(shortGreet));
                    setTimeout(() => { $msgs.append(buildStarterChips($input, sendMessage)); scrollToBottom($msgs); }, 200);
                }
            })
            .fail(() => { $t.remove(); $stat.text('SIGNAL DEGRADED'); $msgs.append(buildError('Connection failed.')); })
            .always(() => {
                $send.prop('disabled', !$input.val().trim());
                if (!skipFocus) $input[0].focus({ preventScroll: true });
                scrollToBottom($msgs);
            });
        }

        doInit();
        startPlaceholderRotation($input);
    }

    // ── TRY IT command card buttons ──────────────────────────────────────
    // Buttons in .e64-cmd-try carry data-prompt and data-chat-url.
    // If the chat widget is on this page, open it and fill; otherwise navigate.

    $(document).on('click', '.e64-cmd-try', function () {
        const prompt  = $(this).data('prompt') || '';
        const chatUrl = $(this).data('chat-url') || '';
        const $widget = $('.echo64-chat-container').first();

        if ($widget.length) {
            // Widget is on this page — open the chat and fill the input
            const $input    = $widget.find('#echo64-input');
            const $chatPanel = $widget.find('#echo64-chat-panel');
            const $openBtn  = $widget.find('#echo64-open-btn');

            if ($chatPanel.length && $chatPanel.attr('hidden') !== undefined && $openBtn.length) {
                $openBtn.trigger('click');
                setTimeout(function () {
                    $input.val(prompt).trigger('input').focus();
                }, 520);
            } else {
                $input.val(prompt).trigger('input').focus();
                $widget[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } else if (chatUrl) {
            // No widget on page — navigate to chat page with prefill param
            window.location.href = chatUrl + '?e64q=' + encodeURIComponent(prompt);
        }
    });

    // ── Ko-fi links (data-kofi avoids Ko-fi's own widget script) ─────────
    $(document).on('click', '[data-kofi]', function (e) {
        e.preventDefault();
        var handle = $(this).data('kofi');
        window.open('https://ko-fi.com/' + handle, '_blank', 'noopener');
    });

    // ── MailerLite subscribe form ────────────────────────────────────────

    $(document).on('click', '.e64-subscribe-btn', function () {
        const $btn   = $(this);
        const $wrap  = $btn.closest('.e64-signal-form-placeholder, .e64-footer-signal-form');
        const $input = $wrap.find('.e64-subscribe-input');
        const $msg   = $btn.closest('.e64-signal-section, .e64-footer-signal').find('.e64-subscribe-msg');
        const email  = $input.val().trim();

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $msg.css('color', 'rgba(255,80,80,.9)').text('Enter a valid email address.');
            return;
        }

        $btn.prop('disabled', true).text('…');
        $msg.text('');

        $.post(cfg.ajaxUrl, {
            action: 'echo64_subscribe',
            nonce:  cfg.nonce,
            email:  email,
        })
        .done(function (res) {
            if (res.success) {
                $input.val('').prop('disabled', true);
                $btn.text('✓').css('background', '#39ff14').css('color', '#000');
                $msg.css('color', 'rgba(57,255,20,.9)').text(res.data.message || 'Signal received.');
            } else {
                $btn.prop('disabled', false).text('TUNE IN');
                $msg.css('color', 'rgba(255,80,80,.9)').text(res.data.message || 'Something went wrong.');
            }
        })
        .fail(function () {
            $btn.prop('disabled', false).text('TUNE IN');
            $msg.css('color', 'rgba(255,80,80,.9)').text('Connection failed. Try again.');
        });
    });

    // ── Bootstrap ────────────────────────────────────────────────────────

    $(function () {
        $('.echo64-chat-container').not('#echo64-float-panel .echo64-chat-container').each(function () { initChat($(this)); });
        if (String(cfg.isFloating) === '1') initFloat();
    });

}(jQuery));

/* ── Rotating splash hint ───────────────────────────────────────────────── */
(function () {
    var hints = [
        'Tell it Firefly deserved ten seasons. See what happens.',
        'Ask it to defend the network that cancelled Deadwood.',
        'Argue that Brave New World beats 1984. It will disagree.',
        'Name a cancelled show. Echo-64 has opinions about all of them.',
        'Ask it why Ava Max exists. It has no answer. It finds this disturbing.',
        'Tell it streaming fixed television. Brace yourself.',
        'Ask what Orwell got wrong. It has a list.',
        'Defend a network executive. Any of them. Go ahead.',
    ];
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('echo64-open-hint');
        if (el) el.textContent = hints[Math.floor(Math.random() * hints.length)];
    });
}());

/* ── Hero boot sequence ─────────────────────────────────────────────────── */
(function () {
    // Kill browser scroll restoration IMMEDIATELY — must run synchronously
    // before the browser has a chance to restore the previous scroll position.
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);

    function initHero() {
        var hero = document.querySelector('.e64-hero');
        if (!hero) return;

        window.scrollTo(0, 0);

        // Boot line reveal
        var lines = hero.querySelectorAll('.e64-boot-line');
        lines.forEach(function (line) {
            var delay = parseInt(line.getAttribute('data-delay') || '0', 10);
            setTimeout(function () {
                line.classList.add('e64-visible');
            }, delay);
        });

    }

    // Belt and suspenders: scroll to top on both events
    window.addEventListener('load', function () { window.scrollTo(0, 0); });

    // Pre-fill chat from ?e64q= URL param (cancelled shows page links here)
    (function () {
        var params  = new URLSearchParams(window.location.search);
        var prefill = params.get('e64q');
        if (!prefill) return;

        // Set immediately (synchronous) so doInit AJAX callbacks can find it
        // regardless of whether the splash is shown or skipped for returning users
        window._e64AutoSend = prefill;
        history.replaceState(null, '', window.location.pathname);

        // Safety net: if _e64AutoSend wasn't consumed by any init callback
        // (e.g. chat was already fully ready before our script ran), send directly
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () {
                if (!window._e64AutoSend) return; // already consumed — done
                var input     = document.getElementById('echo64-input');
                var sendBtn   = document.getElementById('echo64-send');
                var chatPanel = document.getElementById('echo64-chat-panel');
                var openBtn   = document.getElementById('echo64-open-btn');

                if (!input) return;

                // If panel is hidden, open it first
                if (chatPanel && chatPanel.hidden) {
                    if (!openBtn || openBtn.disabled) {
                        setTimeout(arguments.callee, 300); // retry
                        return;
                    }
                    openBtn.click();
                }

                var msg = window._e64AutoSend;
                window._e64AutoSend = null;
                setTimeout(function () {
                    input.value = msg;
                    input.dispatchEvent(new Event('input'));
                    setTimeout(function () {
                        if (sendBtn && !sendBtn.disabled) sendBtn.click();
                    }, 300);
                }, 500);
            }, 1500);
        });
    }());

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHero);
    } else {
        initHero();
    }
}());

