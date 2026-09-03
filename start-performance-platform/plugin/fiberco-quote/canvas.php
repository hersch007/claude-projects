<?php
/**
 * Bare, full-bleed template for the FiberCo funnel — NO theme header/footer.
 * Keeps wp_head()/wp_footer() so the funnel styles + FiberCo chat widget still load.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo esc_html( get_the_title() ); ?> &mdash; FiberCo</title>
<style>
	html,body{margin:0!important;padding:0!important;background:#f0f4ff;-webkit-text-size-adjust:100%}
	body::before,body::after{display:none!important}
	img{max-width:100%;height:auto}
	/* Neutralize the shortcode's viewport-breakout since the canvas is already full-bleed */
	#fcq-wrap{width:100%!important;max-width:100%!important;margin-left:0!important}
	/* Center the funnel content within the window + side gutters. The hero/nav
	   backgrounds stay full-bleed; only their inner content is capped + centered. */
	.fcq-topbar-inner,.fcq-inner{max-width:1200px!important;margin-left:auto!important;margin-right:auto!important}
	.fcq-inner{padding-left:clamp(20px,5vw,56px)!important;padding-right:clamp(20px,5vw,56px)!important}
	.fcq-hero>*:not(.fcq-hero-wave){padding-left:clamp(20px,5vw,56px);padding-right:clamp(20px,5vw,56px);box-sizing:border-box}
	/* ── Spacing polish: more padding on cards + breathing room between title/content/fields ── */
	.fcq-card,.fcq-addr-card{padding:clamp(32px,4vw,60px) clamp(28px,5vw,72px)!important}
	/* Progress bar now sits directly above the active card (trust row is above it) */
	.fcq-progress-wrap{padding:clamp(24px,3vw,36px) clamp(28px,4vw,60px)!important;margin:clamp(8px,2vw,18px) auto clamp(22px,3vw,34px)!important}
	.fcq-trust-row{margin:clamp(30px,4vw,48px) auto clamp(8px,2vw,16px)!important;padding:22px 32px!important}
	.fcq-panel-head{margin-bottom:clamp(30px,4vw,44px)!important}
	.fcq-panel-head h2{margin-bottom:14px!important}
	.fcq-addr-head{margin-bottom:clamp(32px,4vw,46px)!important}
	.fcq-addr-title{margin-bottom:16px!important}
	.fcq-addr-sub,.fcq-panel-head p{line-height:1.75!important}
	.fcq-addr-field{margin-bottom:clamp(28px,3vw,36px)!important}
	.fcq-addr-label,.fcq-field label{margin-bottom:12px!important;display:block}
	.fcq-field{gap:12px!important}
	.fcq-field-row{margin-bottom:clamp(22px,2.5vw,30px)!important}
	.fcq-section-title{margin:6px 0 22px!important;padding-bottom:14px!important}
	.fcq-addr-biz-row,.fcq-addr-check-row{margin:10px 0 clamp(26px,3vw,34px)!important}
	.fcq-btn-row{margin-top:clamp(30px,4vw,42px)!important}
	.fcq-addr-cta{margin-top:26px!important}
	#fcq-summary-bar,.fcq-pay-summary{padding:clamp(22px,3vw,28px) clamp(24px,3vw,30px)!important;margin-bottom:clamp(24px,3vw,28px)!important}
	#fcq-plans-grid{gap:18px!important;margin-bottom:clamp(26px,3vw,32px)!important}
	/* ── Fix centering broken by the snippet's high-specificity #fcq-wrap *{margin:0} reset ── */
	.fcq-addr-sub,.fcq-panel-head p,.fcq-hero p,.fcq-hero-stats,.fcq-addr-icon{margin-left:auto!important;margin-right:auto!important}
	.fcq-addr-head,.fcq-panel-head{text-align:center!important}
	/* ── Align the utility bar content to the same 1200px container so links never run
	   off the right edge / under the scrollbar (keeps the bar background full-width) ── */
	.fcq-utilbar{padding-left:max(20px,calc((100% - 1200px)/2))!important;padding-right:max(20px,calc((100% - 1200px)/2))!important}
	/* ── Buttons: more generous padding + gaps ── */
	.fcq-btn{padding:18px 46px!important}
	.fcq-btn-row{gap:16px!important}
	.fcq-upsell-btns{gap:16px!important;margin-top:12px!important;row-gap:12px!important}
	.fcq-addr-btn{padding:20px 40px!important}
	/* ── Upsell card: center the shield icon (margin-reset fix), space features, pad ── */
	.fcq-upsell-icon-wrap,.fcq-confirm-lottie{margin-left:auto!important;margin-right:auto!important}
	#fcq-upsell-card{padding:clamp(32px,4vw,52px)!important}
	.fcq-upsell-features{margin:0 auto 32px!important}
	.fcq-upsell-features li{padding:14px 0!important}
	.fcq-upsell-title{margin-bottom:10px!important}
	.fcq-upsell-sub{margin-bottom:30px!important}
	.fcq-upsell-price{margin-bottom:28px!important}
	/* ── Confirmation: center + space + pad ── */
	.fcq-confirm-shell{margin-left:auto!important;margin-right:auto!important;padding:clamp(30px,4vw,44px) clamp(24px,4vw,44px)!important}
	.fcq-confirm-sub{margin-left:auto!important;margin-right:auto!important;margin-bottom:30px!important;line-height:1.85!important}
	.fcq-confirm-order{margin:0 0 20px!important;padding:24px 28px!important}
	.fcq-confirm-grid{gap:16px!important;margin-bottom:20px!important}
	.fcq-confirm-cell{padding:18px 22px!important}
	.fcq-confirm-items li{padding:15px 24px!important}
	.fcq-confirm-items h4{padding:16px 24px!important}
</style>
<?php wp_head(); ?>
</head>
<body <?php body_class( 'fiberco-funnel-canvas' ); ?>>
<?php
while ( have_posts() ) {
	the_post();
	the_content();
}
wp_footer();
?>
</body>
</html>
