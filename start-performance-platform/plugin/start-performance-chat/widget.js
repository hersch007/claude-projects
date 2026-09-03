/* Start Performance — Chat Core widget. Self-contained, no dependencies.
   Embed:  <script src="https://APP-INSTANCE/wp-content/plugins/start-performance-chat/widget.js"
                   data-sp-chat="PUBLIC_KEY" async></script>
   Optional data-* attrs: data-accent, data-title, data-launch. */
(function () {
  var s = document.currentScript || document.querySelector('script[data-sp-chat]');
  if (!s || window.__spChatLoaded) return;
  window.__spChatLoaded = true;

  var KEY    = s.getAttribute('data-sp-chat') || '';
  var ACCENT = s.getAttribute('data-accent')  || '#CC1F1F';
  var HEAD   = s.getAttribute('data-head')    || '#2563eb';
  var TITLE  = s.getAttribute('data-title')   || 'Chat with us';
  var LAUNCH = s.getAttribute('data-launch')  || 'Chat';
  var API    = (s.src || '').split('/wp-content/')[0] + '/wp-json/sp-chat/v1';

  var token = null, started = false, sending = false;

  var css =
    '.spc-btn{position:fixed;bottom:20px;right:20px;z-index:2147483000;display:flex;align-items:center;gap:8px;background:' + ACCENT + ';color:#fff;border:none;border-radius:28px;padding:12px 18px;font:600 14px/1 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;cursor:pointer;box-shadow:0 6px 20px rgba(0,0,0,.18)}' +
    '.spc-btn svg{width:18px;height:18px}' +
    '.spc-panel{position:fixed;bottom:20px;right:20px;z-index:2147483000;width:360px;max-width:calc(100vw - 32px);height:520px;max-height:calc(100vh - 40px);background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.22);display:none;flex-direction:column;overflow:hidden;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}' +
    '.spc-open .spc-panel{display:flex}.spc-open .spc-btn{display:none}' +
    '.spc-head{background:' + ACCENT + ';color:#fff;padding:14px 16px;display:flex;align-items:center;justify-content:space-between}' +
    '.spc-head b{font-size:15px;font-weight:600}.spc-x{background:none;border:none;color:#fff;font-size:20px;line-height:1;cursor:pointer;opacity:.9}' +
    '.spc-body{flex:1;overflow-y:auto;padding:14px;background:#f7f8fa;display:flex;flex-direction:column;gap:10px}' +
    '.spc-msg{max-width:80%;padding:9px 12px;border-radius:14px;font-size:14px;line-height:1.5;white-space:pre-wrap;word-wrap:break-word}' +
    '.spc-a{align-self:flex-start;background:#fff;color:#1e293b;border:1px solid #e5e7eb;border-bottom-left-radius:4px}' +
    '.spc-u{align-self:flex-end;background:' + ACCENT + ';color:#fff;border-bottom-right-radius:4px}' +
    '.spc-typing{align-self:flex-start;display:flex;gap:4px;padding:12px}' +
    '.spc-typing i{width:7px;height:7px;border-radius:50%;background:#c7ccd4;display:inline-block;animation:spcb 1s infinite}' +
    '.spc-typing i:nth-child(2){animation-delay:.15s}.spc-typing i:nth-child(3){animation-delay:.3s}' +
    '@keyframes spcb{0%,60%,100%{opacity:.3}30%{opacity:1}}' +
    '.spc-foot{border-top:1px solid #e5e7eb;padding:10px;display:flex;gap:8px;align-items:flex-end;background:#fff}' +
    '.spc-foot textarea{flex:1;resize:none;border:1px solid #d1d5db;border-radius:10px;padding:9px 11px;font:14px system-ui;max-height:90px;outline:none}' +
    '.spc-foot textarea:focus{border-color:' + ACCENT + '}' +
    '.spc-send{background:' + ACCENT + ';color:#fff;border:none;border-radius:10px;width:38px;height:38px;cursor:pointer;flex:0 0 auto;font-size:16px}' +
    '.spc-send:disabled{opacity:.5;cursor:default}' +
    '.spc-esc{text-align:center;padding:6px 0 2px}.spc-esc button{background:none;border:none;color:' + ACCENT + ';font-size:12px;cursor:pointer;text-decoration:underline}' +
    '.spc-escform{display:flex;flex-direction:column;gap:6px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:10px;margin-top:2px}' +
    '.spc-escform input{border:1px solid #d1d5db;border-radius:8px;padding:7px 9px;font:13px system-ui;outline:none}' +
    '.spc-escform button{background:' + ACCENT + ';color:#fff;border:none;border-radius:8px;padding:8px;font:600 13px system-ui;cursor:pointer}' +
    '.spc-a strong,.spc-b{font-weight:700;color:' + HEAD + '}' +
    '.spc-ul{margin:5px 0;padding-left:18px}.spc-ul li{margin:3px 0}' +
    '.spc-a .spc-sp{height:7px}' +
    '.spc-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:2px}' +
    '.spc-chip{background:#fff;border:1px solid ' + ACCENT + ';color:#334155;border-radius:16px;padding:7px 12px;font-size:13px;cursor:pointer;line-height:1}' +
    '.spc-chip:hover{background:' + ACCENT + ';color:#fff}';

  var style = document.createElement('style'); style.textContent = css; document.head.appendChild(style);

  var root = document.createElement('div'); root.className = 'spc';
  root.innerHTML =
    '<button class="spc-btn" aria-label="Open chat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg><span>' + esc(LAUNCH) + '</span></button>' +
    '<div class="spc-panel" role="dialog" aria-label="' + esc(TITLE) + '">' +
      '<div class="spc-head"><b>' + esc(TITLE) + '</b><button class="spc-x" aria-label="Close">×</button></div>' +
      '<div class="spc-body"></div>' +
      '<div class="spc-esc"><button type="button">Talk to a human</button></div>' +
      '<div class="spc-foot"><textarea rows="1" placeholder="Type your message…"></textarea><button class="spc-send" aria-label="Send">↑</button></div>' +
    '</div>';
  document.body.appendChild(root);

  var btn   = root.querySelector('.spc-btn');
  var panel = root.querySelector('.spc-panel');
  var body  = root.querySelector('.spc-body');
  var ta    = root.querySelector('.spc-foot textarea');
  var send  = root.querySelector('.spc-send');

  btn.addEventListener('click', open);
  root.querySelector('.spc-x').addEventListener('click', function () { root.classList.remove('spc-open'); });
  root.querySelector('.spc-esc button').addEventListener('click', showEscalate);
  send.addEventListener('click', doSend);
  ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); doSend(); } });

  function esc(t){ var d=document.createElement('div'); d.textContent=t==null?'':String(t); return d.innerHTML; }
  function post(path, data){ return fetch(API + '/' + path, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) }).then(function(r){ return r.json(); }); }
  // Render a safe subset of markdown (escape first, then **bold**, - bullets, ## headings).
  function md(text){
    var e = esc(text);
    e = e.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
    var lines = e.split('\n'), html = '', inList = false;
    lines.forEach(function(ln){
      var b = ln.match(/^\s*[-*]\s+(.*)/);
      if (b) { if (!inList) { html += '<ul class="spc-ul">'; inList = true; } html += '<li>' + b[1] + '</li>'; return; }
      if (inList) { html += '</ul>'; inList = false; }
      var h = ln.match(/^\s*#{1,3}\s+(.*)/);
      if (h) { html += '<div><span class="spc-b">' + h[1] + '</span></div>'; return; }
      if (ln.trim() === '') { html += '<div class="spc-sp"></div>'; return; }
      html += '<div>' + ln + '</div>';
    });
    if (inList) html += '</ul>';
    return html;
  }
  function addMsg(role, text){ var m=document.createElement('div'); m.className='spc-msg '+(role==='user'?'spc-u':'spc-a'); if(role==='user'){ m.textContent=text; } else { m.innerHTML=md(text); } body.appendChild(m); body.scrollTop=body.scrollHeight; return m; }
  function typing(on){ var t=body.querySelector('.spc-typing'); if(on){ if(!t){ t=document.createElement('div'); t.className='spc-typing'; t.innerHTML='<i></i><i></i><i></i>'; body.appendChild(t);} } else if(t){ t.remove(); } body.scrollTop=body.scrollHeight; }

  function open(){
    root.classList.add('spc-open'); ta.focus();
    if (started) return; started = true;
    typing(true);
    post('start', { key: KEY }).then(function(d){ typing(false); addMsg('assistant', d && d.ok ? d.reply : (d && d.message) || 'Chat is unavailable right now.'); if (d && d.ok) { token = d.token; renderChips(d.chips); } })
      .catch(function(){ typing(false); addMsg('assistant', 'Could not connect. Please try again later.'); });
  }

  function renderChips(chips){
    if (!chips || !chips.length) return;
    var c = document.createElement('div'); c.className = 'spc-chips';
    chips.forEach(function(ch){
      var b = document.createElement('button'); b.type = 'button'; b.className = 'spc-chip';
      b.textContent = ch.label || ch.message;
      b.addEventListener('click', function(){ sendText(ch.message || ch.label); });
      c.appendChild(b);
    });
    body.appendChild(c); body.scrollTop = body.scrollHeight;
  }

  function sendText(text){
    if (!text || sending || !token) return;
    var chips = body.querySelector('.spc-chips'); if (chips) chips.remove();
    addMsg('user', text); sending = true; send.disabled = true; typing(true);
    post('send', { key: KEY, token: token, message: text }).then(function(d){ typing(false); sending=false; send.disabled=false; addMsg('assistant', d && d.ok ? d.reply : (d && d.message) || 'Sorry, something went wrong.'); ta.focus(); })
      .catch(function(){ typing(false); sending=false; send.disabled=false; addMsg('assistant', 'Connection error. Please try again.'); });
  }

  function doSend(){ var text = ta.value.trim(); if (!text) return; ta.value=''; sendText(text); }

  function showEscalate(){
    if (!token || body.querySelector('.spc-escform')) return;
    var f = document.createElement('div'); f.className='spc-escform';
    f.innerHTML = '<input type="email" placeholder="Your email" /><input type="text" placeholder="Anything you’d like to add (optional)" /><button type="button">Send to the team</button>';
    body.appendChild(f); body.scrollTop = body.scrollHeight;
    var email = f.children[0], note = f.children[1], go = f.children[2];
    email.focus();
    go.addEventListener('click', function(){
      go.disabled = true;
      post('escalate', { key: KEY, token: token, email: email.value.trim(), note: note.value.trim() })
        .then(function(d){ f.remove(); addMsg('assistant', d && d.ok ? d.reply : 'Thanks — we’ll be in touch.'); })
        .catch(function(){ go.disabled=false; });
    });
  }
})();
