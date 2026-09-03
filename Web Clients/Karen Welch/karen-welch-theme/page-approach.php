<?php
/*
 * Template Name: My Approach
 */
get_header();
$eyebrow  = get_field('hero_eyebrow')    ?: 'A compassionate way inward';
$h1       = get_field('hero_heading')    ?: 'Every part of you has a purpose.';
$lead     = get_field('hero_lead')       ?: 'Internal Family Systems therapy offers a respectful way to understand what is happening inside—without judging or trying to eliminate parts of yourself.';
$selfmark = get_field('self_mark')       ?: 'Curiosity creates room for change.';
$heading  = get_field('approach_heading') ?: 'A gentler way to understand yourself.';
$body     = get_field('approach_body')   ?: '<p>My approach is grounded in Internal Family Systems (IFS), a compassionate and effective therapy that helps you understand the different parts of yourself that shape your thoughts, emotions, and behaviors.</p><p>Instead of judging the parts that create anxiety, perfectionism, or self-criticism, we\'ll explore them with curiosity. As we understand what they are trying to protect, healing and lasting change become possible.</p><ul class="checks"><li>Slow down and gain clarity</li><li>Build greater self-trust</li><li>Respond with calm and confidence</li></ul>';
$ceyebrow = get_field('cards_eyebrow')   ?: 'What the work can feel like';
$cheading = get_field('cards_heading')   ?: 'Gentle, collaborative, and at your pace.';
$c1t      = get_field('card_1_title')   ?: 'Notice';
$c1b      = get_field('card_1_body')    ?: 'Recognize the thoughts, feelings, and patterns asking for attention.';
$c2t      = get_field('card_2_title')   ?: 'Understand';
$c2b      = get_field('card_2_body')    ?: 'Explore what each part is trying to protect, with compassion instead of criticism.';
$c3t      = get_field('card_3_title')   ?: 'Choose';
$c3b      = get_field('card_3_body')    ?: 'Create more space to respond from calm, clarity, confidence, and connection.';
?>
<main id="main">

<section class="page-hero page-hero-soft page-hero-image" style="--hero-image:url(https://karenwelchtherapist.com/hero-approach.png)">
  <div class="wrap">
    <div class="page-hero-copy">
      <p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
      <h1><?php echo esc_html( $h1 ); ?></h1>
      <p class="lead"><?php echo esc_html( $lead ); ?></p>
    </div>
  </div>
</section>

<section class="section wrap split">
  <div class="self-mark"><span>Self</span><p><?php echo esc_html( $selfmark ); ?></p></div>
  <div>
    <h2><?php echo esc_html( $heading ); ?></h2>
    <?php echo wp_kses_post( $body ); ?>
  </div>
</section>

<section class="details">
  <div class="section wrap">
    <p class="eyebrow"><?php echo esc_html( $ceyebrow ); ?></p>
    <h2><?php echo esc_html( $cheading ); ?></h2>
    <div class="card-grid">
      <div class="card"><span>01</span><h3><?php echo esc_html( $c1t ); ?></h3><p><?php echo esc_html( $c1b ); ?></p></div>
      <div class="card"><span>02</span><h3><?php echo esc_html( $c2t ); ?></h3><p><?php echo esc_html( $c2b ); ?></p></div>
      <div class="card"><span>03</span><h3><?php echo esc_html( $c3t ); ?></h3><p><?php echo esc_html( $c3b ); ?></p></div>
    </div>
  </div>
</section>

<?php kwb_contact_band(); ?>
</main>
<?php get_footer(); ?>
