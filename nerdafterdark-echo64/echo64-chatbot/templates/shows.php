<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$home = home_url( '/' );

// Pull shows from the Cancelled Show CPT (each has its own SEO-friendly page).
$show_posts = get_posts( [
    'post_type'      => Echo64_Shows_CPT::POST_TYPE,
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
] );

$shows = [];
foreach ( $show_posts as $sp ) {
    $shows[] = [
        'title'     => $sp->post_title,
        'network'   => get_post_meta( $sp->ID, '_echo64_network', true ),
        'year'      => get_post_meta( $sp->ID, '_echo64_year', true ),
        'verdict'   => wp_trim_words( wp_strip_all_tags( $sp->post_content ), 20, '…' ),
        'permalink' => get_permalink( $sp ),
    ];
}

// Build network kill counts
$network_counts = array_count_values( array_column( $shows, 'network' ) );
$networks = array_keys( $network_counts );
sort( $networks );
?>

<div class="e64-shows-page">

    <div class="e64-shows-header">
        <div class="e64-shows-eyebrow">ECHO-64 BUFFER &middot; <?php echo count( $shows ); ?> ENTRIES</div>
        <h1 class="e64-shows-title">THE CANCELLED SHOWS BUFFER</h1>
        <p class="e64-shows-intro">
            Every show here deserved more time. Every network made the wrong call.
            Click any show to read the verdict and argue with Echo-64 about it.
        </p>
    </div>

    <div class="e64-shows-toolbar">
        <div class="e64-shows-search-wrap">
            <input type="search" id="e64-show-search" class="e64-show-search" placeholder="SEARCH SHOWS…" autocomplete="off" spellcheck="false" />
        </div>
        <button class="e64-random-btn" id="e64-random-btn">&#x2685; RANDOM SHOW</button>
    </div>

    <div class="e64-shows-filters" role="group" aria-label="Filter by network">
        <button class="e64-filter-btn is-active" data-network="all">ALL <span class="e64-filter-count"><?php echo count( $shows ); ?></span></button>
        <?php foreach ( $networks as $net ) : ?>
        <button class="e64-filter-btn" data-network="<?php echo esc_attr( $net ); ?>">
            <?php echo esc_html( $net ); ?> <span class="e64-filter-count"><?php echo $network_counts[ $net ]; ?></span>
        </button>
        <?php endforeach; ?>
    </div>
    <p class="e64-shows-count"><span id="e64-visible-count"><?php echo count( $shows ); ?></span> SHOWS</p>

    <div class="e64-shows-grid" id="e64-shows-grid">
        <?php foreach ( $shows as $i => $show ) :
            $num = str_pad( $i + 1, 2, '0', STR_PAD_LEFT );
        ?>
        <a class="e64-show-card" href="<?php echo esc_url( $show['permalink'] ); ?>"
           data-network="<?php echo esc_attr( $show['network'] ); ?>"
           data-title="<?php echo esc_attr( strtolower( $show['title'] ) ); ?>">
            <div class="e64-show-num"><?php echo $num; ?></div>
            <div class="e64-show-title"><?php echo esc_html( $show['title'] ); ?></div>
            <div class="e64-show-meta"><?php echo esc_html( $show['network'] ); ?> &middot; <?php echo esc_html( $show['year'] ); ?></div>
            <p class="e64-show-verdict"><?php echo esc_html( $show['verdict'] ); ?></p>
            <span class="e64-show-cta">READ THE VERDICT &rarr;</span>
        </a>
        <?php endforeach; ?>
    </div>

    <script>
    (function() {
        var btns   = document.querySelectorAll('.e64-filter-btn');
        var cards  = document.querySelectorAll('#e64-shows-grid .e64-show-card');
        var count  = document.getElementById('e64-visible-count');
        var search = document.getElementById('e64-show-search');
        var randBtn= document.getElementById('e64-random-btn');

        var activeNetwork = 'all';
        var searchQuery   = '';

        function applyFilters() {
            var visible = 0;
            cards.forEach(function(card) {
                var netMatch   = activeNetwork === 'all' || card.dataset.network === activeNetwork;
                var titleMatch = !searchQuery || card.dataset.title.indexOf(searchQuery) !== -1;
                var show = netMatch && titleMatch;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            count.textContent = visible;
        }

        btns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                activeNetwork = this.dataset.network;
                btns.forEach(function(b) { b.classList.remove('is-active'); });
                this.classList.add('is-active');
                applyFilters();
            });
        });

        search.addEventListener('input', function() {
            searchQuery = this.value.trim().toLowerCase();
            applyFilters();
        });

        randBtn.addEventListener('click', function() {
            var visible = Array.from(cards).filter(function(c) { return c.style.display !== 'none'; });
            if (!visible.length) return;
            var pick = visible[Math.floor(Math.random() * visible.length)];
            window.location.href = pick.href;
        });
    })();
    </script>

</div>
