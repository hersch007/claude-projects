<?php get_header(); ?>
<main id="main">

<?php while ( have_posts() ) : the_post(); ?>

<section class="page-hero page-hero-soft">
  <div class="wrap">
    <div class="page-hero-copy">
      <p class="eyebrow"><?php echo get_the_date( 'F j, Y' ); ?></p>
      <h1><?php the_title(); ?></h1>
    </div>
  </div>
</section>

<div class="wrap single-post">
  <?php if ( has_post_thumbnail() ) : ?>
    <div style="margin-bottom:48px;border-radius:16px;overflow:hidden;">
      <?php the_post_thumbnail( 'large', [ 'style' => 'width:100%;height:auto;display:block;' ] ); ?>
    </div>
  <?php endif; ?>

  <div class="post-body">
    <?php the_content(); ?>
  </div>

  <div style="margin-top:64px;padding-top:40px;border-top:1px solid var(--taupe);">
    <a href="<?php echo get_permalink( get_option( 'page_for_posts' ) ); ?>" class="text-link">&larr; Back to journal</a>
  </div>
</div>

<?php endwhile; ?>

<section class="contact-band" style="margin-top:80px;">
  <div class="wrap">
    <p class="eyebrow">Now accepting new clients</p>
    <h2>You deserve a space where you don&rsquo;t have to hold everything together.</h2>
    <p>Online IFS therapy for adults throughout Massachusetts.</p>
    <a class="button light" href="/contact/">Schedule a free consultation <span>&#x2197;</span></a>
  </div>
</section>

</main>
<?php get_footer(); ?>
