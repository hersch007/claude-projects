<?php get_header(); ?>
<main id="main">

<section class="page-hero page-hero-soft">
  <div class="wrap">
    <div class="page-hero-copy">
      <h1><?php the_title(); ?></h1>
    </div>
  </div>
</section>

<div class="wrap page-content">
  <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <div class="page-body"><?php the_content(); ?></div>
  <?php endwhile; endif; ?>
</div>

<section class="contact-band">
  <div class="wrap">
    <p class="eyebrow">Now accepting new clients</p>
    <h2>You deserve a space where you don&rsquo;t have to hold everything together.</h2>
    <p>Online IFS therapy for adults throughout Massachusetts.</p>
    <a class="button light" href="/contact/">Schedule a free consultation <span>&#x2197;</span></a>
    <small>Reaching out doesn&rsquo;t commit you to therapy. You can share what brings you here and ask any questions through the secure portal.</small>
  </div>
</section>

</main>
<?php get_footer(); ?>
