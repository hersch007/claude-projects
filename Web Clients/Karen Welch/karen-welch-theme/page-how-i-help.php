<?php
/*
 * Template Name: How I Help
 */
get_header();
$eyebrow = get_field('hero_eyebrow') ?: 'How I help';
$h1      = get_field('hero_heading') ?: "You're capable. You're caring. And you're tired.";
$lead    = get_field('hero_lead')    ?: 'Maybe you are the person everyone depends on, yet internally you feel overwhelmed, self-critical, or disconnected from yourself.';
$i1t     = get_field('issue_1_title') ?: 'Anxiety & overthinking';
$i1b     = get_field('issue_1_body')  ?: "Understand worry with curiosity rather than self-criticism. We'll explore the protective parts that rely on constant analysis, helping you find more calm, clarity, and confidence.";
$i2t     = get_field('issue_2_title') ?: 'Perfectionism';
$i2b     = get_field('issue_2_body')  ?: 'Loosen the pressure to perform, get everything exactly right, or earn approval. Therapy can help you build a steadier sense of worth and make room for "good enough."';
$i3t     = get_field('issue_3_title') ?: 'People pleasing';
$i3b     = get_field('issue_3_body')  ?: "Explore the part of you that works hard to keep the peace and care for everyone else. Together, we can build healthier boundaries without losing your kindness.";
$i4t     = get_field('issue_4_title') ?: 'Burnout, conflict & change';
$i4b     = get_field('issue_4_body')  ?: 'When your inner system feels overloaded or pulled in different directions, we can make room for understanding, balance, and a more grounded way forward.';
$qh      = get_field('quote_heading') ?: 'Change that feels lasting, not forced.';
$qb      = get_field('quote_body')    ?: "You may be navigating a relationship challenge, burnout, a major transition, or simply a season of feeling stuck. Whatever brings you to therapy, we'll meet it with curiosity rather than judgment.";
?>
<main id="main">

<section class="page-hero page-hero-soft page-hero-image" style="--hero-image:url(https://karenwelchtherapist.com/hero-how-i-help.png)">
  <div class="wrap">
    <div class="page-hero-copy">
      <p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
      <h1><?php echo esc_html( $h1 ); ?></h1>
      <p class="lead"><?php echo esc_html( $lead ); ?></p>
    </div>
  </div>
</section>

<section class="section wrap issue-list">
  <article>
    <span class="number">01</span>
    <h2><?php echo esc_html( $i1t ); ?></h2>
    <p><?php echo esc_html( $i1b ); ?></p>
  </article>
  <article>
    <span class="number">02</span>
    <h2><?php echo esc_html( $i2t ); ?></h2>
    <p><?php echo esc_html( $i2b ); ?></p>
    <?php
    $btn2_label = get_field('issue_2_btn_label') ?: 'FAQ & Resources';
    $btn2_url   = get_field('issue_2_btn_url')   ?: '/resources/';
    if ( str_starts_with( $btn2_url, '/' ) ) $btn2_url = home_url( $btn2_url );
    ?>
    <a class="button small" href="<?php echo esc_url( $btn2_url ); ?>"><?php echo esc_html( $btn2_label ); ?> <span>&#x2197;</span></a>
  </article>
  <article>
    <span class="number">03</span>
    <h2><?php echo esc_html( $i3t ); ?></h2>
    <p><?php echo esc_html( $i3b ); ?></p>
    <?php
    $btn3_label = get_field('issue_3_btn_label') ?: 'FAQ & Resources';
    $btn3_url   = get_field('issue_3_btn_url')   ?: '/resources/';
    if ( str_starts_with( $btn3_url, '/' ) ) $btn3_url = home_url( $btn3_url );
    ?>
    <a class="button small" href="<?php echo esc_url( $btn3_url ); ?>"><?php echo esc_html( $btn3_label ); ?> <span>&#x2197;</span></a>
  </article>
  <article>
    <span class="number">04</span>
    <h2><?php echo esc_html( $i4t ); ?></h2>
    <p><?php echo esc_html( $i4b ); ?></p>
  </article>
</section>

<section class="quote-section">
  <div class="wrap">
    <h2><?php echo esc_html( $qh ); ?></h2>
    <p><?php echo esc_html( $qb ); ?></p>
  </div>
</section>

<?php kwb_contact_band(); ?>
</main>
<?php get_footer(); ?>
