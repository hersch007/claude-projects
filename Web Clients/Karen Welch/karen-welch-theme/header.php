<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<a class="skip" href="#main">Skip to content</a>
<header>
  <div class="nav wrap">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand">
      <strong>Karen Welch Buttars</strong>
      <span><?php echo esc_html( get_theme_mod( 'kwb_tagline', 'LMHC · IFS Certified Therapist' ) ); ?></span>
    </a>
    <?php
    wp_nav_menu( [
      'theme_location' => 'primary',
      'container'      => 'nav',
      'container_attrs'=> [ 'aria-label' => 'Primary navigation' ],
      'depth'          => 1,
      'fallback_cb'    => 'kwb_fallback_nav',
    ] );
    ?>
    <a class="button small"
       href="/contact/"
       data-spwidget-scope-id="8bb13639-a65a-41be-8513-47d286734364"
       data-spwidget-scope-uri="karenwelch"
       data-spwidget-application-id="7c72cb9f9a9b913654bb89d6c7b4e71a77911b30192051da35384b4d0c6d505b"
       data-spwidget-channel="embedded_widget"
       data-spwidget-type="Contact form"
       data-spwidget-contact=""
       data-spwidget-scope-global=""
       data-spwidget-autobind="">
      <?php echo esc_html( get_theme_mod( 'kwb_button_text', "Let's talk" ) ); ?> <span>&#x2197;</span>
    </a>
  </div>
</header>

<?php
function kwb_fallback_nav() {
    echo '<nav aria-label="Primary navigation"><ul>';
    echo '<li><a href="/how-i-help/">How I help</a></li>';
    echo '<li><a href="/approach/">My approach</a></li>';
    echo '<li><a href="/about/">About</a></li>';
    echo '<li><a href="/fees/">Fees &amp; insurance</a></li>';
    echo '<li><a href="/blog/">Blog</a></li>';
    echo '</ul></nav>';
}
?>
