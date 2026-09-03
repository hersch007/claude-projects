<?php
/*
 * Template Name: Blank Canvas
 */
get_header();

$has_hero = has_post_thumbnail();
$hero_url = $has_hero ? get_the_post_thumbnail_url( null, 'full' ) : '';
?>
<main id="main">

<?php if ( $has_hero ) : ?>
<section class="page-hero page-hero-soft page-hero-image" style="--hero-image:url(<?php echo esc_url( $hero_url ); ?>)">
  <div class="wrap">
    <div class="page-hero-copy">
      <p class="eyebrow"><?php echo esc_html( get_field('hero_eyebrow') ?: '' ); ?></p>
      <h1><?php the_title(); ?></h1>
      <?php if ( $lead = get_field('hero_lead') ) : ?>
      <p class="lead"><?php echo esc_html( $lead ); ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="section wrap">
<?php
if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        the_content();
    }
}
?>
</div>

</main>
<?php get_footer(); ?>
