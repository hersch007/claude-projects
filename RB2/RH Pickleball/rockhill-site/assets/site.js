/* Rock Hill Pickleball - injects the shared header + footer into every page.
   (Mockup convenience. In GeneratePress you'll use the static markup in
   /partials instead - this file just keeps the 6 preview pages in sync.) */
(function(){
  var NAV = [
    {href:"index.html",          label:"Home",            key:"home"},
    {href:"courts.html",         label:"Courts",          key:"courts"},
    {href:"find-players.html",   label:"Find Players",    key:"find"},
    {href:"getting-started.html",label:"Getting Started", key:"start"},
    {href:"about.html",          label:"About",           key:"about"},
    {href:"scheduler.html",      label:"Scheduler",       key:"scheduler", cta:true}
  ];
  var current = document.body.getAttribute("data-page") || "";

  // favicon (mockup only - in WordPress set this via Customize → Site Identity → Site Icon)
  var fav = document.createElement("link");
  fav.rel = "icon"; fav.type = "image/png";
  fav.href = "assets/cropped-rock-hill-york-county-pickleball-club-logo.png";
  document.head.appendChild(fav);

  var links = NAV.map(function(n){
    var cls = (n.cta ? "rh-cta " : "") + (n.key===current ? "rh-active" : "");
    return '<a href="'+n.href+'"'+(cls.trim()?' class="'+cls.trim()+'"':'')+'>'+n.label+'</a>';
  }).join("");

  var headerHTML =
    '<header class="rh-header">'+
      '<div class="rh-header-inner">'+
        '<a class="rh-logo" href="index.html">'+
          '<img src="assets/cropped-rock-hill-york-county-pickleball-club-logo.png" alt="Rock Hill Pickleball Club">'+
        '</a>'+
        '<button class="rh-burger" aria-label="Menu"><span></span><span></span><span></span></button>'+
        '<nav class="rh-nav">'+links+'</nav>'+
      '</div>'+
    '</header>';

  var footerHTML =
    '<footer class="rh-footer">'+
      '<div class="rh-footer-inner">'+
        '<div>'+
          '<img class="rh-foot-logo" src="assets/cropped-rock-hill-york-county-pickleball-club-logo.png" alt="Rock Hill Pickleball Club">'+
          '<p>The honest, local guide to pickleball in Rock Hill &amp; York County - where to play, how to start, and where the community lives.</p>'+
          '<span class="rh-foot-tag">A free community resource</span>'+
        '</div>'+
        '<div>'+
          '<h4>Explore</h4>'+
          '<ul>'+
            '<li><a href="courts.html">Courts Guide</a></li>'+
            '<li><a href="find-players.html">Find Players</a></li>'+
            '<li><a href="getting-started.html">Getting Started</a></li>'+
            '<li><a href="scheduler.html">Tournament Scheduler</a></li>'+
          '</ul>'+
        '</div>'+
        '<div>'+
          '<h4>Community</h4>'+
          '<ul>'+
            '<li><a href="https://app.courtreserve.com/Online/Portal/Index/16310" target="_blank" rel="noopener">CourtReserve</a></li>'+
            '<li><a href="#">Group.Me</a></li>'+
            '<li><span class="rh-foot-soon">Facebook (coming soon)</span></li>'+
            '<li><span class="rh-foot-soon">Instagram (coming soon)</span></li>'+
          '</ul>'+
        '</div>'+
        '<div>'+
          '<h4>Get in touch</h4>'+
          '<ul>'+
            '<li><a href="about.html">About us</a></li>'+
            '<li><a href="about.html#contact">Contact / suggest an update</a></li>'+
          '</ul>'+
          '<p style="margin-top:10px">[Add your club email]</p>'+
        '</div>'+
      '</div>'+
      '<div class="rh-footer-bottom">'+
        '<span>&copy; 2026 Rock Hill Pickleball · A community resource for Rock Hill &amp; York County.</span>'+
        '<span>Built by local players.</span>'+
      '</div>'+
    '</footer>';

  var hSlot = document.getElementById("rh-header-slot");
  var fSlot = document.getElementById("rh-footer-slot");
  if(hSlot) hSlot.outerHTML = headerHTML;
  if(fSlot) fSlot.outerHTML = footerHTML;

  // hamburger toggle
  var header = document.querySelector(".rh-header");
  var burger = document.querySelector(".rh-burger");
  if(burger && header){
    burger.addEventListener("click", function(){ header.classList.toggle("rh-open"); });
    header.querySelectorAll(".rh-nav a").forEach(function(a){
      a.addEventListener("click", function(){ header.classList.remove("rh-open"); });
    });
  }
})();

/* Scroll reveal — fade content up as it enters the viewport */
(function(){
  if(!("IntersectionObserver" in window)) return; // no support: content stays visible (class never added)
  var items = [];
  document.querySelectorAll(".rh-section > .rh-container, .rh-statsband > .rh-container").forEach(function(cont){
    Array.prototype.forEach.call(cont.children, function(child){
      // expand grids/steps so their cards cascade individually; otherwise reveal the block
      if(child.classList.contains("rh-grid") || child.classList.contains("rh-steps")){
        Array.prototype.forEach.call(child.children, function(g){ items.push(g); });
      } else {
        items.push(child);
      }
    });
  });
  if(!items.length) return;
  items.forEach(function(el){ el.classList.add("rh-reveal"); });

  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if(reduce){ items.forEach(function(el){ el.classList.add("rh-in"); }); return; }

  // gentle stagger among reveal-siblings
  items.forEach(function(el){
    var sibs = Array.prototype.filter.call(el.parentNode.children, function(c){ return c.classList.contains("rh-reveal"); });
    el.style.transitionDelay = Math.min(sibs.indexOf(el), 5) * 70 + "ms";
  });

  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if(e.isIntersecting){ e.target.classList.add("rh-in"); io.unobserve(e.target); }
    });
  }, { rootMargin: "0px 0px -8% 0px", threshold: 0.08 });
  items.forEach(function(el){ io.observe(el); });
})();
