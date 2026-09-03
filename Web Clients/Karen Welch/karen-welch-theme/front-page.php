<?php get_header(); ?>
<main id="main">

<section class="hero wrap">
  <div class="hero-copy">
    <p class="eyebrow">Online therapy for adults in Massachusetts</p>
    <h1>Make room for a life beyond <em>worry.</em></h1>
    <p class="lead">You may look capable and put together on the outside—while overthinking, self-doubt, or the pressure to get everything right leaves you exhausted inside.</p>
    <p>Therapy can be a place to slow down, understand what is happening within you, and begin moving forward with more clarity and self-trust.</p>
    <div class="actions">
      <a class="button" href="/contact/">Let's talk <span>&#x2197;</span></a>
      <a href="/how-i-help/" class="text-link">Explore how I help &rarr;</a>
    </div>
    <?php if ( get_theme_mod( 'kwb_accepting', '1' ) ) : ?>
    <p class="availability"><span></span> <?php echo esc_html( get_theme_mod( 'kwb_accepting_text', 'Now welcoming new clients' ) ); ?></p>
    <?php endif; ?>
  </div>
  <div class="hero-visual">
    <div class="portrait-orbit" aria-hidden="true"></div>
    <figure class="portrait-frame">
      <img src="https://karenwelchtherapist.com/karen-welch-buttars.avif" alt="Karen Welch Buttars, licensed mental health counselor">
    </figure>
    <blockquote class="hero-quote">
      <span class="quote-mark" aria-hidden="true">&ldquo;</span>
      <p>You don&rsquo;t have to figure it all out alone.</p>
      <cite>Karen Welch Buttars, LMHC</cite>
    </blockquote>
  </div>
</section>

<section class="stats" aria-label="Credentials">
  <div><strong>26+</strong><span>Years of experience</span></div>
  <div><strong>IFS</strong><span>Certified since 2020</span></div>
  <div><strong>MA</strong><span>Licensed in Massachusetts</span></div>
  <div><strong>100%</strong><span>Online for adults</span></div>
</section>

<section class="section wrap care-section">
  <p class="eyebrow">A place to begin</p>
  <h2>Care that sees the whole <em>you.</em></h2>
  <p class="intro">Whether you are navigating anxiety, burnout, relationship strain, or a season of change, we&rsquo;ll meet what is happening with curiosity rather than judgment.</p>
  <div class="card-grid">
    <a href="/how-i-help/" class="card">
      <span>01</span>
      <h3>How I help</h3>
      <p>Support for anxiety, overthinking, perfectionism, people pleasing, burnout, conflict, and life transitions.</p>
      <strong>Learn more &rarr;</strong>
    </a>
    <a href="/approach/" class="card">
      <span>02</span>
      <h3>My approach</h3>
      <p>A compassionate, IFS-informed way to understand the parts of you that work so hard to protect you.</p>
      <strong>Learn more &rarr;</strong>
    </a>
    <a href="/about/" class="card">
      <span>03</span>
      <h3>Meet Karen</h3>
      <p>More than 26 years of experience, grounded in warmth, respect, collaboration, and room to be yourself.</p>
      <strong>Learn more &rarr;</strong>
    </a>
  </div>
</section>

<section class="details home-details">
  <div class="wrap split">
    <div>
      <p class="eyebrow">Practical details</p>
      <h2>Clear information from the start.</h2>
    </div>
    <div>
      <p>Secure online therapy for adults in Massachusetts. Sessions are $175. Karen works with BCBS of Massachusetts and Aetna.</p>
      <a href="/fees/" class="text-link">View fees &amp; insurance &rarr;</a>
    </div>
  </div>
</section>

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
