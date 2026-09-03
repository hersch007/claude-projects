<?php get_header(); ?>

<div class="cc-hero">
    <h1><?php wp_title( '', true ); ?></h1>
</div>

<main class="cc-main">
    <div class="cc-content-card">
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
            <?php the_content(); ?>
        <?php endwhile; else : ?>
            <p>No content found.</p>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
