<?php
/*
 * Template Name: Fees & Insurance
 */
get_header();
$eyebrow  = get_field('hero_eyebrow')   ?: 'Fees & insurance';
$h1       = get_field('hero_heading')   ?: 'Clear information from the start.';
$lead     = get_field('hero_lead')      ?: 'Individual therapy is offered 100% by secure video for adults located in Massachusetts. Karen does not provide couples or family therapy.';
$inteyeb  = get_field('intro_eyebrow')  ?: 'Practical details';
$inth     = get_field('intro_heading')  ?: 'A simple path forward.';
$intb     = get_field('intro_body')     ?: 'Begin with a free 15-minute consultation to ask questions and see whether working together feels right.';
$rate     = get_field('session_rate')   ?: '$175';
$snote    = get_field('session_note')   ?: 'Telehealth · Adults 18+';
$ins      = get_field('insurance')      ?: 'BCBS of Massachusetts & Aetna';
$inote    = get_field('insurance_note') ?: 'For other insurance plans, an itemized receipt can be provided for possible out-of-network reimbursement. Please verify benefits with your insurer.';
$feyeb    = get_field('faqs_eyebrow')   ?: 'Common questions';
$fh       = get_field('faqs_heading')   ?: 'A little more clarity before you begin.';
$f1q      = get_field('faq_1_question') ?: 'Do you offer a consultation?';
$f1a      = get_field('faq_1_answer')   ?: 'Yes. A free 15-minute consultation gives us time to briefly connect, answer questions, and see whether working together feels right.';
$f2q      = get_field('faq_2_question') ?: 'What happens in the first session?';
$f2a      = get_field('faq_2_answer')   ?: "We'll talk about what brings you to therapy, your history, current life, and what you hope may change.";
$f3q      = get_field('faq_3_question') ?: 'Do you accept insurance?';
$f3a      = get_field('faq_3_answer')   ?: 'Karen works with BCBS of Massachusetts and Aetna. For other plans, an itemized receipt may support out-of-network reimbursement.';
$f4q      = get_field('faq_4_question') ?: 'Where are sessions available?';
$f4a      = get_field('faq_4_answer')   ?: 'Sessions are online for adults who are physically located in Massachusetts at the time of the appointment.';
?>
<main id="main">

<section class="page-hero page-hero-soft page-hero-image" style="--hero-image:url(https://karenwelchtherapist.com/hero-fees.png)">
  <div class="wrap">
    <div class="page-hero-copy">
      <p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
      <h1><?php echo esc_html( $h1 ); ?></h1>
      <p class="lead"><?php echo esc_html( $lead ); ?></p>
    </div>
  </div>
</section>

<section class="section wrap">
  <div class="split">
    <div>
      <p class="eyebrow"><?php echo esc_html( $inteyeb ); ?></p>
      <h2><?php echo esc_html( $inth ); ?></h2>
      <p><?php echo esc_html( $intb ); ?></p>
      <a class="button" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Let&rsquo;s talk <span>&#x2197;</span></a>
    </div>
    <div class="fee-card">
      <span>Individual therapy session</span>
      <strong><?php echo esc_html( $rate ); ?></strong>
      <p><?php echo esc_html( $snote ); ?></p>
      <hr>
      <span>Insurance</span>
      <h3><?php echo esc_html( $ins ); ?></h3>
      <p><?php echo esc_html( $inote ); ?></p>
    </div>
  </div>
</section>

<section class="details">
  <div class="section wrap">
    <p class="eyebrow"><?php echo esc_html( $feyeb ); ?></p>
    <h2><?php echo esc_html( $fh ); ?></h2>
    <div class="faqs">
      <details><summary><?php echo esc_html( $f1q ); ?><i>+</i></summary><p><?php echo esc_html( $f1a ); ?></p></details>
      <details><summary><?php echo esc_html( $f2q ); ?><i>+</i></summary><p><?php echo esc_html( $f2a ); ?></p></details>
      <details><summary><?php echo esc_html( $f3q ); ?><i>+</i></summary><p><?php echo esc_html( $f3a ); ?></p></details>
      <details><summary><?php echo esc_html( $f4q ); ?><i>+</i></summary><p><?php echo esc_html( $f4a ); ?></p></details>
    </div>
    <div class="resources-cta">
      <p>Looking for more information about IFS therapy and mental health?</p>
      <a class="button" href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">Resources &amp; Reading <span>&#x2197;</span></a>
    </div>
  </div>
</section>

<?php kwb_contact_band(); ?>
</main>
<?php get_footer(); ?>
