<?php get_header(); ?>
<main id="main">
  <div class="wrap error-page">
    <h1>404</h1>
    <p>That page doesn&rsquo;t exist. Let&rsquo;s get you back on track.</p>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button">Go home &rarr;</a>
  </div>
</main>
<?php get_footer(); ?>
