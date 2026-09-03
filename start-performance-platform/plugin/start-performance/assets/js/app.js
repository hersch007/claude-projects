// Date picker — force open via showPicker() to work inside overflow containers
document.addEventListener('click', function(e) {
    var el = e.target;
    if (el.tagName === 'INPUT' && (el.type === 'date' || el.type === 'time') && el.showPicker) {
        try { el.showPicker(); } catch(err) {}
    }
});

// Auto-hide toast after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    var toast = document.querySelector('.sp-toast');
    if (toast) {
        setTimeout(function() {
            toast.style.transition = 'opacity .4s';
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 400);
        }, 3000);
    }

    // ── Collapsible nav groups ────────────────────────────────────────────────
    var nav = document.querySelector('.sp-nav');
    if (!nav) return;

    var activeSection = nav.getAttribute('data-active-section') || '';
    var STORAGE_KEY = 'sp_nav_open';

    function getSavedOpen() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}; } catch(e) { return {}; }
    }
    function saveOpen(state) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch(e) {}
    }

    function setGroupOpen(group, open, animate) {
        var items = group.querySelector('.sp-nav-group-items');
        if (!items) return; // non-collapsible group (e.g. a promoted upsell link) — nothing to toggle
        if (open) {
            group.classList.add('open');
            items.style.maxHeight = '1000px';
        } else {
            if (animate) {
                // measure current height first so transition starts from real height
                items.style.maxHeight = items.scrollHeight + 'px';
                requestAnimationFrame(function() { items.style.maxHeight = '0'; });
            } else {
                items.style.maxHeight = '0';
            }
            group.classList.remove('open');
        }
    }

    var savedOpen = getSavedOpen();
    // ── Mobile sidebar toggle ─────────────────────────────────────────────────
    var sidebar  = document.getElementById('sp-sidebar');
    var overlay  = document.getElementById('sp-overlay');
    var menuBtn  = document.getElementById('sp-menu-toggle');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
    }
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
    }
    if (menuBtn)  menuBtn.addEventListener('click', openSidebar);
    if (overlay)  overlay.addEventListener('click', closeSidebar);
    // Close sidebar when a nav link is tapped
    if (sidebar) {
        sidebar.querySelectorAll('.sp-nav-item').forEach(function(link) {
            link.addEventListener('click', closeSidebar);
        });
    }

    var groups = nav.querySelectorAll('.sp-nav-group');

    groups.forEach(function(group) {
        var key = group.getAttribute('data-section');
        var isActive = activeSection && key === activeSection;
        var defaultOpen = group.hasAttribute('data-nav-default-open');
        // Open if: active section, user previously opened it, or it's a default-open
        // (hero) section the user hasn't explicitly collapsed.
        var shouldOpen = isActive || savedOpen[key] === true || (defaultOpen && savedOpen[key] !== false);
        setGroupOpen(group, shouldOpen);

        var toggle = group.querySelector('.sp-nav-section-toggle');
        if (toggle) {
            toggle.addEventListener('click', function() {
                var nowOpen = group.classList.contains('open');
                setGroupOpen(group, !nowOpen, true);
                var state = getSavedOpen();
                state[key] = !nowOpen;
                saveOpen(state);
            });
        }
    });
});

// ── Global Search ─────────────────────────────────────────────────────────────
(function(){
    var trigger  = document.getElementById('sp-search-trigger');
    var overlay  = document.getElementById('sp-search-overlay');
    var input    = document.getElementById('sp-global-search');
    var results  = document.getElementById('sp-search-results');
    if (!input || !results || !overlay) return;

    function openSearch() {
        overlay.style.display = 'flex';
        input.value = '';
        results.style.display = 'none';
        setTimeout(function(){ input.focus(); }, 50);
    }
    function closeSearch() {
        overlay.style.display = 'none';
        results.style.display = 'none';
    }

    if (trigger) trigger.addEventListener('click', openSearch);

    overlay.addEventListener('click', function(e){
        if (e.target === overlay) closeSearch();
    });

    var ajaxUrl = (window.spData && spData.ajaxUrl) ? spData.ajaxUrl : '';
    // Build search URL from home URL
    var searchBase = window.location.origin + '/sp-app/?sp_ajax=search&q=';
    // Detect from existing links
    var firstNav = document.querySelector('.sp-nav-item');
    if (firstNav) {
        var href = firstNav.getAttribute('href') || '';
        var m = href.match(/^(https?:\/\/[^\/]+)/);
        if (m) searchBase = m[1] + '/sp-app/?sp_ajax=search&q=';
    }

    var typeLinks = {
        contact: '/sp-app/?view=contacts&action=view&id=',
        company: '/sp-app/?view=companies&action=view&id=',
        lead:    '/sp-app/?view=leads&action=view&id='
    };
    var typeLabels = { contact: 'Contact', company: 'Company', lead: 'Lead' };
    var typeColors = { contact: '#3b82f6', company: '#8b5cf6', lead: '#f59e0b' };

    var timer;
    input.addEventListener('input', function(){
        clearTimeout(timer);
        var q = input.value.trim();
        if (q.length < 2) { results.style.display = 'none'; return; }
        timer = setTimeout(function(){
            fetch(searchBase + encodeURIComponent(q), { credentials: 'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    results.innerHTML = '';
                    if (!data.success || !data.data.length) {
                        results.innerHTML = '<div class="sp-search-empty">No results for "' + q + '"</div>';
                        results.style.display = 'block';
                        return;
                    }
                    var groups = {};
                    data.data.forEach(function(r){
                        if (!groups[r.type]) groups[r.type] = [];
                        groups[r.type].push(r);
                    });
                    Object.keys(groups).forEach(function(type){
                        var lbl = document.createElement('div');
                        lbl.className = 'sp-search-group-label';
                        lbl.textContent = typeLabels[type] || type;
                        results.appendChild(lbl);
                        groups[type].forEach(function(r){
                            var a = document.createElement('a');
                            a.className = 'sp-search-item';
                            a.href = typeLinks[type] + r.id;
                            a.innerHTML = '<div class="sp-search-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="'+typeColors[type]+'" stroke-width="2" stroke-linecap="round" width="12" height="12"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div><div style="min-width:0"><div class="sp-search-item-label">'+escHtml(r.label)+'</div><div class="sp-search-item-sub">'+escHtml(r.sub||'')+'</div></div>';
                            results.appendChild(a);
                        });
                    });
                    results.style.display = 'block';
                })
                .catch(function(){});
        }, 250);
    });

    input.addEventListener('keydown', function(e){
        if (e.key === 'Escape') closeSearch();
    });

    document.addEventListener('keydown', function(e){
        if (e.key === 'k' && (e.metaKey || e.ctrlKey)) { e.preventDefault(); openSearch(); }
    });

    function escHtml(s){ return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
})();

// ── FAB + Quick Task Modal ────────────────────────────────────────────────────
(function(){
    var fabBtn  = document.getElementById('sp-fab-btn');
    var fabMenu = document.getElementById('sp-fab-menu');
    var taskBtn = document.getElementById('sp-fab-task-btn');
    var modal   = document.getElementById('sp-task-modal');
    var closeBtn = document.getElementById('sp-task-modal-close');
    var cancelBtn = document.getElementById('sp-task-modal-cancel');

    if (!fabBtn) return;

    var fabOpen = false;
    function setFab(open) {
        fabOpen = open;
        fabBtn.classList.toggle('open', open);
        fabMenu.classList.toggle('open', open);
    }

    fabBtn.addEventListener('click', function(){ setFab(!fabOpen); });

    document.addEventListener('click', function(e){
        if (fabOpen && !document.getElementById('sp-fab-wrap').contains(e.target)) setFab(false);
    });

    if (taskBtn) {
        taskBtn.addEventListener('click', function(){
            setFab(false);
            modal.style.display = 'flex';
            var titleInput = modal.querySelector('input[name="title"]');
            if (titleInput) setTimeout(function(){ titleInput.focus(); }, 50);
        });
    }

    function closeModal() { modal.style.display = 'none'; }
    if (closeBtn)  closeBtn.addEventListener('click',  closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });

    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && modal && modal.style.display !== 'none') closeModal();
    });
})();

// ── Bulk Actions ──────────────────────────────────────────────────────────────
(function(){
    var form = document.getElementById('sp-bulk-form');
    if (!form) return;
    var checkAll = document.getElementById('sp-check-all');
    var bar      = document.getElementById('sp-bulk-bar');
    var counter  = document.getElementById('sp-bulk-n');
    var actionSel = document.getElementById('sp-bulk-action');
    var clearBtn = document.getElementById('sp-bulk-clear');
    var rows = function(){ return Array.prototype.slice.call(form.querySelectorAll('.sp-row-check')); };

    function refresh() {
        var checked = rows().filter(function(c){ return c.checked; });
        if (counter) counter.textContent = checked.length;
        if (bar) bar.style.display = checked.length ? 'flex' : 'none';
        rows().forEach(function(c){
            var tr = c.closest('tr');
            if (tr) tr.classList.toggle('sp-row-selected', c.checked);
        });
        if (checkAll) {
            var all = rows();
            checkAll.checked = all.length > 0 && checked.length === all.length;
            checkAll.indeterminate = checked.length > 0 && checked.length < all.length;
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function(){
            rows().forEach(function(c){ c.checked = checkAll.checked; });
            refresh();
        });
    }
    rows().forEach(function(c){ c.addEventListener('change', refresh); });

    if (clearBtn) {
        clearBtn.addEventListener('click', function(){
            rows().forEach(function(c){ c.checked = false; });
            if (checkAll) checkAll.checked = false;
            refresh();
        });
    }

    form.addEventListener('submit', function(e){
        var checked = rows().filter(function(c){ return c.checked; });
        var action  = actionSel ? actionSel.value : '';
        if (!checked.length) { e.preventDefault(); return; }
        if (!action) { e.preventDefault(); alert('Choose a bulk action first.'); return; }
        if (action === 'delete') {
            if (!confirm('Delete ' + checked.length + ' selected record(s)? This cannot be undone.')) {
                e.preventDefault();
            }
        }
    });

    refresh();
})();

// ── Rich Editor ───────────────────────────────────────────────────────────────
(function(){
    var TOOLBAR = [
        { cmd:'bold',        label:'<b>B</b>',       title:'Bold' },
        { cmd:'italic',      label:'<i>I</i>',       title:'Italic' },
        { cmd:'underline',   label:'<u>U</u>',       title:'Underline' },
        { sep: true },
        { cmd:'h2',          label:'H2',             title:'Heading 2' },
        { cmd:'h3',          label:'H3',             title:'Heading 3' },
        { sep: true },
        { cmd:'insertUnorderedList', label:'&#8226; List',  title:'Bullet List' },
        { cmd:'insertOrderedList',   label:'1. List', title:'Numbered List' },
        { sep: true },
        { cmd:'removeFormat', label:'&#10005;',      title:'Clear Formatting' },
    ];

    function execCmd(cmd, body) {
        body.focus();
        if (cmd === 'h2' || cmd === 'h3') {
            document.execCommand('formatBlock', false, cmd);
        } else {
            document.execCommand(cmd, false, null);
        }
        updateToolbarState(body.closest('.sp-editor-wrap'));
    }

    function updateToolbarState(wrap) {
        if (!wrap) return;
        wrap.querySelectorAll('.sp-editor-btn[data-cmd]').forEach(function(btn) {
            var cmd = btn.getAttribute('data-cmd');
            var active = false;
            try {
                if (cmd === 'h2' || cmd === 'h3') {
                    var block = document.queryCommandValue('formatBlock');
                    active = block.toLowerCase() === cmd;
                } else {
                    active = document.queryCommandState(cmd);
                }
            } catch(e) {}
            btn.classList.toggle('active', active);
        });
    }

    function initEditor(wrap) {
        var textarea = wrap.previousElementSibling;
        if (!textarea || textarea.tagName !== 'TEXTAREA') return;

        // Build toolbar
        var toolbar = document.createElement('div');
        toolbar.className = 'sp-editor-toolbar';
        TOOLBAR.forEach(function(item) {
            if (item.sep) {
                var sep = document.createElement('div');
                sep.className = 'sp-editor-sep';
                toolbar.appendChild(sep);
                return;
            }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'sp-editor-btn';
            btn.setAttribute('data-cmd', item.cmd);
            btn.setAttribute('title', item.title);
            btn.innerHTML = item.label;
            btn.addEventListener('mousedown', function(e) {
                e.preventDefault();
                execCmd(item.cmd, body);
            });
            toolbar.appendChild(btn);
        });

        // Build editable body
        var body = document.createElement('div');
        body.className = 'sp-editor-body';
        body.contentEditable = 'true';
        body.setAttribute('data-placeholder', textarea.placeholder || 'Start typing…');
        body.innerHTML = textarea.value || '';

        wrap.appendChild(toolbar);
        wrap.appendChild(body);

        // Sync to textarea on input
        body.addEventListener('input', function() {
            textarea.value = body.innerHTML;
            updateToolbarState(wrap);
        });
        body.addEventListener('keyup', function() { updateToolbarState(wrap); });
        body.addEventListener('mouseup', function() { updateToolbarState(wrap); });

        // Sync before form submit
        var form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', function() {
                textarea.value = body.innerHTML;
            });
        }

        // Handle tab key — insert spaces instead of leaving field
        body.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                document.execCommand('insertHTML', false, '&nbsp;&nbsp;&nbsp;&nbsp;');
            }
        });
    }

    document.querySelectorAll('.sp-editor-wrap').forEach(initEditor);
})();

// ── Confirm Modal ─────────────────────────────────────────────────────────────
(function(){
    var backdrop, modal, titleEl, msgEl, confirmBtn, _resolve;

    function buildModal() {
        backdrop = document.createElement('div');
        backdrop.className = 'sp-modal-backdrop';
        backdrop.innerHTML =
            '<div class="sp-modal">' +
                '<div class="sp-modal-icon">🗑️</div>' +
                '<div class="sp-modal-title"></div>' +
                '<div class="sp-modal-msg"></div>' +
                '<div class="sp-modal-actions">' +
                    '<button class="sp-btn sp-btn-ghost sp-modal-cancel">Cancel</button>' +
                    '<button class="sp-btn sp-btn-danger sp-modal-confirm">Delete</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(backdrop);

        titleEl    = backdrop.querySelector('.sp-modal-title');
        msgEl      = backdrop.querySelector('.sp-modal-msg');
        confirmBtn = backdrop.querySelector('.sp-modal-confirm');

        backdrop.querySelector('.sp-modal-cancel').addEventListener('click', function(e){ e.stopPropagation(); close(false); });
        confirmBtn.addEventListener('click', function(e){ e.stopPropagation(); close(true); });
        backdrop.addEventListener('click', function(e){ if(e.target===backdrop) close(false); });
        document.addEventListener('keydown', function(e){ if(e.key==='Escape') close(false); });
    }

    function open(msg, opts) {
        if (!backdrop) buildModal();
        opts = opts || {};
        titleEl.textContent    = opts.title    || 'Are you sure?';
        msgEl.textContent      = msg           || '';
        confirmBtn.textContent = opts.confirm  || 'Delete';
        confirmBtn.className   = 'sp-btn sp-modal-confirm ' + (opts.btnClass || 'sp-btn-danger');
        backdrop.querySelector('.sp-modal-icon').textContent = opts.icon || '🗑️';
        backdrop.offsetHeight; // reflow
        backdrop.classList.add('sp-modal-open');
        confirmBtn.focus();
        return new Promise(function(res){ _resolve = res; });
    }

    function close(result) {
        if (!backdrop) return;
        backdrop.classList.remove('sp-modal-open');
        var res = _resolve; _resolve = null;
        if (res) res(result);
    }

    window.spConfirm = open;

    // Intercept all data-sp-confirm links and forms
    document.addEventListener('click', function(e) {
        var el = e.target.closest('[data-sp-confirm]');
        if (!el) return;
        e.preventDefault();
        var msg   = el.getAttribute('data-sp-confirm');
        var title = el.getAttribute('data-sp-confirm-title') || 'Are you sure?';
        var btn   = el.getAttribute('data-sp-confirm-btn')   || 'Delete';
        var icon  = el.getAttribute('data-sp-confirm-icon')  || '🗑️';
        var cls   = el.getAttribute('data-sp-confirm-class') || 'sp-btn-danger';
        open(msg, {title:title, confirm:btn, btnClass:cls, icon:icon}).then(function(ok){
            if (!ok) return;
            if (el.tagName === 'A') {
                window.location.href = el.href;
            } else if (el.tagName === 'BUTTON' || el.tagName === 'INPUT') {
                var form = el.closest('form');
                if (form) { form.submit(); } else { el.click(); }
            }
        });
    });
})();
