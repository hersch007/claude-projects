<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$core_id = sanitize_key( isset( $_GET['core'] ) ? $_GET['core'] : '' );
$slots   = function_exists( 'sp_get_core_slots' ) ? sp_get_core_slots() : array();
$slot    = isset( $slots[ $core_id ] ) ? $slots[ $core_id ] : null;

if ( ! $slot ) {
    echo '<p class="sp-empty">Module not found.</p>';
    return;
}

$tagline  = ! empty( $slot['tagline'] )  ? $slot['tagline']  : $slot['label'];
$features = ! empty( $slot['features'] ) ? $slot['features'] : array();
?>
<style>
.sp-upsell-wrap {
    max-width: 700px;
    margin: 0 auto;
}
.sp-upsell-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0f2744 100%);
    border-radius: 18px;
    padding: 48px 48px 40px;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 0;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}
.sp-upsell-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 260px; height: 260px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(59,130,246,.18) 0%, transparent 70%);
    pointer-events: none;
}
.sp-upsell-hero::after {
    content: '';
    position: absolute;
    bottom: -40px; left: 30%;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(99,102,241,.12) 0%, transparent 70%);
    pointer-events: none;
}
.sp-upsell-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #60a5fa;
    background: rgba(59,130,246,.15);
    border: 1px solid rgba(59,130,246,.3);
    border-radius: 99px;
    padding: 4px 12px;
    margin-bottom: 18px;
}
.sp-upsell-title {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1.15;
    margin: 0 0 10px;
    color: #fff;
}
.sp-upsell-tagline {
    font-size: 1.05rem;
    font-weight: 600;
    color: #93c5fd;
    margin: 0 0 10px;
}
.sp-upsell-desc {
    font-size: .88rem;
    color: #94a3b8;
    line-height: 1.65;
    margin: 0;
    max-width: 520px;
}
.sp-upsell-features {
    background: #1e293b;
    border: 1px solid #334155;
    border-top: none;
    padding: 32px 48px;
    border-bottom-left-radius: 18px;
    border-bottom-right-radius: 18px;
    margin-bottom: 24px;
}
.sp-upsell-features-heading {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #64748b;
    margin: 0 0 20px;
}
.sp-upsell-feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.sp-upsell-feature-item {
    display: flex;
    align-items: flex-start;
    gap: 13px;
}
.sp-upsell-check {
    flex-shrink: 0;
    width: 22px; height: 22px;
    border-radius: 6px;
    background: linear-gradient(135deg, #16a34a, #15803d);
    display: flex; align-items: center; justify-content: center;
    margin-top: 1px;
    box-shadow: 0 2px 6px rgba(22,163,74,.35);
}
.sp-upsell-feature-text strong {
    display: block;
    font-size: .88rem;
    font-weight: 700;
    color: #f1f5f9;
    margin-bottom: 1px;
}
.sp-upsell-feature-text span {
    font-size: .8rem;
    color: #64748b;
    line-height: 1.4;
}
.sp-upsell-divider {
    border: none;
    border-top: 1px solid #334155;
    margin: 28px 0;
}
.sp-upsell-cta {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border: 1px solid #334155;
    border-radius: 14px;
    padding: 28px 32px;
    color: #fff;
}
.sp-upsell-cta-rocket {
    font-size: 1.5rem;
    margin-bottom: 10px;
    display: block;
}
.sp-upsell-cta-headline {
    font-size: 1rem;
    font-weight: 700;
    color: #f1f5f9;
    margin: 0 0 8px;
}
.sp-upsell-cta-body {
    font-size: .85rem;
    color: #94a3b8;
    line-height: 1.6;
    margin: 0 0 6px;
}
.sp-upsell-cta-body strong {
    color: #e2e8f0;
}
.sp-upsell-cta-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 20px;
}
.sp-upsell-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff !important;
    font-size: .85rem;
    font-weight: 700;
    padding: 11px 22px;
    border-radius: 8px;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(37,99,235,.4);
    transition: opacity .15s;
}
.sp-upsell-btn-primary:hover { opacity: .9; }
.sp-upsell-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.15);
    color: #cbd5e1 !important;
    font-size: .85rem;
    font-weight: 600;
    padding: 11px 22px;
    border-radius: 8px;
    text-decoration: none;
    transition: background .15s;
}
.sp-upsell-btn-secondary:hover { background: rgba(255,255,255,.1); }
</style>

<div class="sp-upsell-wrap">

    <div class="sp-upsell-hero">
        <div class="sp-upsell-eyebrow">
            <svg viewBox="0 0 20 20" fill="currentColor" width="11" height="11"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            Performance Core
        </div>
        <div style="display:flex;align-items:flex-start;gap:18px;">
            <div style="flex-shrink:0;width:54px;height:54px;border-radius:14px;background:rgba(59,130,246,.18);border:1px solid rgba(59,130,246,.3);display:flex;align-items:center;justify-content:center;">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="26" height="26" style="color:#60a5fa;">
                    <?php echo $slot['icon']; ?>
                </svg>
            </div>
            <div>
                <h1 class="sp-upsell-title"><?php echo esc_html( $slot['label'] ); ?></h1>
                <p class="sp-upsell-tagline"><?php echo esc_html( $tagline ); ?></p>
                <p class="sp-upsell-desc"><?php echo esc_html( $slot['description'] ); ?></p>
            </div>
        </div>
    </div>

    <div class="sp-upsell-features">
        <?php if ( ! empty( $features ) ) : ?>
        <div class="sp-upsell-features-heading">What's Included</div>
        <ul class="sp-upsell-feature-list">
            <?php foreach ( $features as $feature ) :
                $parts = explode( '|', $feature, 2 );
                $name  = $parts[0];
                $desc  = $parts[1] ?? '';
            ?>
            <li class="sp-upsell-feature-item">
                <span class="sp-upsell-check">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><path d="M5 13l4 4L19 7"/></svg>
                </span>
                <div class="sp-upsell-feature-text">
                    <strong><?php echo esc_html( $name ); ?></strong>
                    <?php if ( $desc ) : ?><span><?php echo esc_html( $desc ); ?></span><?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <hr class="sp-upsell-divider">

        <div class="sp-upsell-cta">
            <span class="sp-upsell-cta-rocket">🚀</span>
            <p class="sp-upsell-cta-headline">Ready to level up your performance?</p>
            <p class="sp-upsell-cta-body">
                This powerful module isn't activated on your account yet — but it can be in just a few minutes.
            </p>
            <p class="sp-upsell-cta-body">
                <strong>Your Start Performance rep is ready to get this turned on for you today</strong> and show you exactly how much it will help your team.
            </p>
            <p class="sp-upsell-cta-body">Tap the button below and let's make it happen!</p>
            <div class="sp-upsell-cta-actions">
                <a href="tel:8005414938" class="sp-upsell-btn-primary">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                    Call 800-541-4938
                </a>
                <a href="mailto:RBStart@StartPerformance.com" class="sp-upsell-btn-secondary">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                    Talk to My Success Manager
                </a>
            </div>
        </div>
    </div>

</div>
