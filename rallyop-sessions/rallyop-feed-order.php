<?php
if (!defined('ABSPATH')) { exit; }

// Game ids increase as results are recorded, so sorting descending keeps the
// latest result at the top even if another script renders the cards later.
add_action('wp_footer', function () {
    if (!is_front_page() || !is_user_logged_in()) { return; }
    ?>
    <script id="rallyop-feed-newest-first">
    (function () {
        function sortFeed() {
            var feed = document.querySelector('[data-rop-feed]');
            if (!feed) return;
            Array.from(feed.querySelectorAll('[data-game]'))
                .sort(function (a, b) { return Number(b.dataset.game) - Number(a.dataset.game); })
                .forEach(function (card) { feed.appendChild(card); });
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', sortFeed);
        else sortFeed();
        setTimeout(sortFeed, 950);
        setTimeout(sortFeed, 1600);
    })();
    </script>
    <?php
}, 146);
