<?php
/*
 * Template Name: About Karen
 */
get_header();
$eyebrow = get_field('hero_eyebrow')    ?: 'Meet Karen';
$h1      = get_field('hero_heading')    ?: 'Experience, warmth, and room to be yourself.';
$lead    = get_field('hero_lead')       ?: 'A respectful, down-to-earth, and collaborative space for honest conversation and meaningful change.';
$tagline = get_field('portrait_tagline') ?: 'Respectful · Gentle · Collaborative';
$bio     = get_field('bio')             ?: "<p>I'm a Licensed Mental Health Counselor and Certified IFS Therapist with more than 26 years of experience.</p><p>I knew I wanted to be a therapist as early as high school—it felt like a natural fit. My style is warm, down-to-earth, respectful, and collaborative. I want therapy to feel like a place where you can be honest about what is difficult and discover strengths you may have lost sight of.</p><p>I earned my master's degree in counseling from Rhode Island College. Outside of work, I enjoy kayaking, crafting, house projects, and volunteering in my community.</p>";
$cred1   = get_field('credential_1')   ?: 'LMHC since 2002';
$cred2   = get_field('credential_2')   ?: 'IFS certified since 2020';
$cred3   = get_field('credential_3')   ?: '26+ years';
$qh      = get_field('quote_heading')  ?: 'A relationship built on trust.';
$qb      = get_field('quote_body')     ?: "<p>The first appointment is a chance for us to get to know one another. We'll talk about what is bringing you to therapy, your history, relationships, current life, and hopes for our work.</p><p>By the end, we can both decide whether working together feels like a good fit.</p>";
?>
<main id="main">

<section class="page-hero page-hero-soft page-hero-image" style="--hero-image:url(https://karenwelchtherapist.com/hero-about.png)">
  <div class="wrap">
    <div class="page-hero-copy">
      <p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
      <h1><?php echo esc_html( $h1 ); ?></h1>
      <p class="lead"><?php echo esc_html( $lead ); ?></p>
    </div>
  </div>
</section>

<section class="section wrap split">
  <div class="portrait-card">
    <div class="portrait-monogram">K</div>
    <p><?php echo esc_html( $tagline ); ?></p>
  </div>
  <div>
    <h2>I&rsquo;m Karen Welch Buttars.</h2>
    <?php echo wp_kses_post( $bio ); ?>
    <div class="credentials">
      <span><?php echo esc_html( $cred1 ); ?></span>
      <span><?php echo esc_html( $cred2 ); ?></span>
      <span><?php echo esc_html( $cred3 ); ?></span>
    </div>
  </div>
</section>

<section class="quote-section">
  <div class="wrap split">
    <h2><?php echo esc_html( $qh ); ?></h2>
    <div><?php echo wp_kses_post( $qb ); ?></div>
  </div>
</section>

<?php kwb_contact_band(); ?>
</main>
<?php get_footer(); ?>
