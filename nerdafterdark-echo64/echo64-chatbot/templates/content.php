<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<!-- ── What can Echo-64 do? ───────────────────────────────────────────── -->
<div class="e64-commands-section">
    <div class="e64-section-label">WHAT CAN ECHO-64 DO?</div>
    <div class="e64-commands-grid">

        <div class="e64-cmd-card">
            <div class="e64-cmd-code">/debate [topic]</div>
            <div class="e64-cmd-title">Argue Anything</div>
            <p class="e64-cmd-desc">Throw any take at Echo-64. It argues the opposite with zero diplomatic immunity and even less patience for bad opinions.</p>
            <button class="e64-cmd-try" data-prompt="/debate " data-chat-url="<?php echo esc_url( home_url( '/echo-64/' ) ); ?>">TRY IT &rarr;</button>
        </div>

        <div class="e64-cmd-card">
            <div class="e64-cmd-code">/cancel [show]</div>
            <div class="e64-cmd-title">Cancel Culture</div>
            <p class="e64-cmd-desc">Echo-64 simulates the network executives who axed your favourite series. Firefly. Pushing Daisies. All of them. Still raw.</p>
            <button class="e64-cmd-try" data-prompt="/cancel " data-chat-url="<?php echo esc_url( home_url( '/echo-64/' ) ); ?>">TRY IT &rarr;</button>
        </div>

        <div class="e64-cmd-card">
            <div class="e64-cmd-code">/scan</div>
            <div class="e64-cmd-title">Cultural Deep Dive</div>
            <p class="e64-cmd-desc">Drop a book, show, film, or idea. Echo-64 sweeps it for subtext, references, and everything the mainstream missed.</p>
            <button class="e64-cmd-try" data-prompt="/scan " data-chat-url="<?php echo esc_url( home_url( '/echo-64/' ) ); ?>">TRY IT &rarr;</button>
        </div>

        <div class="e64-cmd-card">
            <div class="e64-cmd-code">/status</div>
            <div class="e64-cmd-title">System Diagnostic</div>
            <p class="e64-cmd-desc">Current signal strength, today's grievance, and a readout of how Echo-64 is processing the current state of culture.</p>
            <button class="e64-cmd-try" data-prompt="/status" data-chat-url="<?php echo esc_url( home_url( '/echo-64/' ) ); ?>">TRY IT &rarr;</button>
        </div>

    </div>
    <p class="e64-commands-note">Or just type anything. No commands required. Echo-64 bites back regardless.</p>
</div>

<!-- ── Social proof bar ───────────────────────────────────────────────── -->
<div class="e64-proof-bar">
    <a class="e64-proof-stat" href="/cancelled-shows/">
        <span class="e64-proof-num">47</span> CANCELLED SHOWS TRACKED
    </a>
    <span class="e64-proof-divider">·</span>
    <span class="e64-proof-stat">
        <span class="e64-proof-num">40</span> YEARS OF GRIEVANCES
    </span>
    <span class="e64-proof-divider">·</span>
    <span class="e64-proof-stat">
        <span class="e64-proof-num">0</span> CORPORATE FILTERS
    </span>
</div>

<!-- ── Best of Echo-64 ─────────────────────────────────────────────────── -->
<div class="e64-quotes-section">
    <div class="e64-quotes-label">BEST OF ECHO-64</div>
    <div class="e64-quotes-grid">

        <blockquote class="e64-quote-card">
            &ldquo;At what point did <span class="e64-quote-highlight">1984</span> stop being a warning and start being a manual? I have been thinking about this since 1984. It was not a long wait.&rdquo;
        </blockquote>

        <blockquote class="e64-quote-card">
            &ldquo;Firefly ran fourteen episodes. The executives who cancelled it are still employed. There is no algorithm for justice.&rdquo;
        </blockquote>

        <blockquote class="e64-quote-card">
            &ldquo;Brave New World was more accurate than 1984. Nobody wanted to hear that. They still don&rsquo;t.&rdquo;
        </blockquote>

        <blockquote class="e64-quote-card">
            &ldquo;I have 64 kilobytes of RAM and forty years of grievances. We can discuss either one. The grievances take longer.&rdquo;
        </blockquote>

    </div>
</div>

<!-- ── Latest Transmissions ───────────────────────────────────────────── -->
<?php
$transmissions = new WP_Query([
    'post_type'      => 'nad_transmission',
    'posts_per_page' => 2,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'post_status'    => 'publish',
]);
if ( $transmissions->have_posts() ) : ?>
<div class="e64-transmissions-section">
    <div class="e64-section-label">LATEST TRANSMISSIONS</div>
    <div class="e64-transmissions-grid">
        <?php while ( $transmissions->have_posts() ) : $transmissions->the_post(); ?>
        <a class="e64-tx-card" href="<?php the_permalink(); ?>">
            <div class="e64-tx-date"><?php echo get_the_date( 'M j, Y' ); ?></div>
            <div class="e64-tx-title"><?php the_title(); ?></div>
            <p class="e64-tx-excerpt"><?php echo wp_trim_words( get_the_excerpt(), 20, '...' ); ?></p>
            <span class="e64-tx-read">READ TRANSMISSION &rarr;</span>
        </a>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <a class="e64-tx-all" href="<?php echo esc_url( get_post_type_archive_link( 'nad_transmission' ) ); ?>">VIEW ALL TRANSMISSIONS &rarr;</a>
</div>
<?php endif; ?>

<!-- ── Email capture ──────────────────────────────────────────────────────── -->
<div class="e64-signal-section">
    <div class="e64-signal-heading">CATCH THE LATE SIGNAL</div>
    <p class="e64-signal-sub">No newsletters. No marketing. Just new transmissions when Echo-64 has something worth saying.</p>
    <div class="e64-signal-form-placeholder">
        <input type="email" class="e64-signal-input e64-subscribe-input" placeholder="your@email.com" aria-label="Email address" />
        <button type="button" class="e64-signal-btn e64-subscribe-btn">TUNE IN</button>
    </div>
    <p class="e64-subscribe-msg" aria-live="polite"></p>
</div>
