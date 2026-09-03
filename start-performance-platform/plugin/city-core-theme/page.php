<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<div class="cc-hero">
    <h1><?php the_title(); ?></h1>
</div>

<main class="cc-main">
    <div class="cc-content-card">
        <?php the_content(); ?>
    </div>
</main>

<?php endwhile; ?>

<?php get_footer(); ?>
