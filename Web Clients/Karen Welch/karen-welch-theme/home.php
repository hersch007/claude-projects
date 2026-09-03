<?php get_header(); ?>
<main id="main">

<section class="page-hero page-hero-soft">
  <div class="wrap">
    <div class="page-hero-copy">
      <p class="eyebrow">Insights &amp; reflections</p>
      <h1>From the journal.</h1>
      <p class="lead">Thoughts on therapy, IFS, anxiety, and finding your way back to yourself.</p>
    </div>
  </div>
</section>

<section class="section wrap">
  <?php if ( have_posts() ) : ?>
    <div class="blog-grid">
      <?php while ( have_posts() ) : the_post(); ?>
        <a href="<?php the_permalink(); ?>" class="post-card">
          <?php if ( has_post_thumbnail() ) : ?>
            <div class="post-thumbnail"><?php the_post_thumbnail( 'medium_large' ); ?></div>
          <?php endif; ?>
          <div class="post-card-body">
            <p class="post-card-meta"><?php echo get_the_date( 'F j, Y' ); ?></p>
            <h3><?php the_title(); ?></h3>
            <p><?php the_excerpt(); ?></p>
            <span class="read-more">Read more &rarr;</span>
          </div>
        </a>
      <?php endwhile; ?>
    </div>

    <div style="margin-top:60px;text-align:center;">
      <?php the_posts_pagination( [
        'mid_size'  => 2,
        'prev_text' => '&larr; Older',
        'next_text' => 'Newer &rarr;',
      ] ); ?>
    </div>

  <?php else : ?>
    <p style="text-align:center;font-size:1.1rem;color:var(--deep);padding:80px 0;">
      No posts yet. Check back soon.
    </p>
  <?php endif; ?>
</section>

<section class="contact-band">
  <div class="wrap">
    <p class="eyebrow">Now accepting new clients</p>
    <h2>Ready to take the first step?</h2>
    <p>Online IFS therapy for adults throughout Massachusetts.</p>
    <a class="button light" href="/contact/">Schedule a free consultation <span>&#x2197;</span></a>
  </div>
</section>

</main>
<?php get_footer(); ?>
