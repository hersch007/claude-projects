<?php get_header(); ?>
<main id="main">
  <div class="wrap page-content">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
      <h1><?php the_title(); ?></h1>
      <div class="page-body"><?php the_content(); ?></div>
    <?php endwhile; endif; ?>
  </div>
</main>
<?php get_footer(); ?>
