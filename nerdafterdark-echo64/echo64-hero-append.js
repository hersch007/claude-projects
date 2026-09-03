
/* ── Hero boot sequence ─────────────────────────────────────────────────── */
(function () {
    function initHero() {
        var hero = document.querySelector('.e64-hero');
        if (!hero) return;

        // Staggered boot line reveal
        var lines = hero.querySelectorAll('.e64-boot-line');
        lines.forEach(function (line) {
            var delay = parseInt(line.getAttribute('data-delay') || '0', 10);
            setTimeout(function () {
                line.classList.add('e64-visible');
            }, delay);
        });

        // CTA scroll-to-chat
        var cta = hero.querySelector('#e64-hero-cta');
        if (cta) {
            cta.addEventListener('click', function (e) {
                e.preventDefault();
                var chat = document.getElementById('echo64-chat');
                if (chat) {
                    chat.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    // Trigger open button after scroll
                    setTimeout(function () {
                        var openBtn = chat.querySelector('#echo64-open-btn');
                        if (openBtn && !openBtn.disabled) openBtn.click();
                    }, 600);
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHero);
    } else {
        initHero();
    }
}());
