<?php

function kwb_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ] );
    add_theme_support( 'automatic-feed-links' );

    register_nav_menus( [
        'primary' => 'Primary Navigation',
    ] );
}
add_action( 'after_setup_theme', 'kwb_theme_setup' );

function kwb_enqueue_assets() {
    wp_enqueue_style( 'kwb-style', get_stylesheet_uri(), [], wp_get_theme()->get( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'kwb_enqueue_assets' );

function kwb_customizer( $wp_customize ) {

    $wp_customize->add_section( 'kwb_practice', [
        'title'    => 'Practice Settings',
        'priority' => 30,
    ] );

    // Tagline
    $wp_customize->add_setting( 'kwb_tagline', [
        'default'           => 'LMHC · IFS Certified Therapist',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'kwb_tagline', [
        'label'   => 'Header tagline',
        'section' => 'kwb_practice',
        'type'    => 'text',
    ] );

    // Availability toggle
    $wp_customize->add_setting( 'kwb_accepting', [
        'default'           => '1',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'kwb_accepting', [
        'label'   => 'Show "Now welcoming new clients" dot',
        'section' => 'kwb_practice',
        'type'    => 'checkbox',
    ] );

    // Availability text
    $wp_customize->add_setting( 'kwb_accepting_text', [
        'default'           => 'Now welcoming new clients',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'kwb_accepting_text', [
        'label'   => 'Availability text',
        'section' => 'kwb_practice',
        'type'    => 'text',
    ] );

    // Phone number
    $wp_customize->add_setting( 'kwb_phone', [
        'default'           => '617-230-3180',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'kwb_phone', [
        'label'   => 'Phone number',
        'section' => 'kwb_practice',
        'type'    => 'text',
    ] );

    // Button text
    $wp_customize->add_setting( 'kwb_button_text', [
        'default'           => "Let's talk",
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'kwb_button_text', [
        'label'   => 'Header button text',
        'section' => 'kwb_practice',
        'type'    => 'text',
    ] );

    // Contact band (appears at the bottom of every interior page)
    $wp_customize->add_setting( 'kwb_band_eyebrow', [ 'default' => 'Now accepting new clients', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
    $wp_customize->add_control( 'kwb_band_eyebrow', [ 'label' => 'Contact band — Eyebrow', 'section' => 'kwb_practice', 'type' => 'text' ] );

    $wp_customize->add_setting( 'kwb_band_heading', [ 'default' => "You deserve a space where you don’t have to hold everything together.", 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
    $wp_customize->add_control( 'kwb_band_heading', [ 'label' => 'Contact band — Heading', 'section' => 'kwb_practice', 'type' => 'text' ] );

    $wp_customize->add_setting( 'kwb_band_body', [ 'default' => 'Online IFS therapy for adults throughout Massachusetts.', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
    $wp_customize->add_control( 'kwb_band_body', [ 'label' => 'Contact band — Body', 'section' => 'kwb_practice', 'type' => 'text' ] );

    $wp_customize->add_setting( 'kwb_band_button', [ 'default' => 'Schedule a free consultation', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
    $wp_customize->add_control( 'kwb_band_button', [ 'label' => 'Contact band — Button text', 'section' => 'kwb_practice', 'type' => 'text' ] );

    $wp_customize->add_setting( 'kwb_band_fine', [ 'default' => "Reaching out doesn’t commit you to therapy. You can share what brings you here and ask any questions through the secure portal.", 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
    $wp_customize->add_control( 'kwb_band_fine', [ 'label' => 'Contact band — Fine print', 'section' => 'kwb_practice', 'type' => 'text' ] );
}
add_action( 'customize_register', 'kwb_customizer' );

// Helper: render the shared contact band using Customizer values
function kwb_contact_band() {
    $eyebrow = get_theme_mod( 'kwb_band_eyebrow', 'Now accepting new clients' );
    $heading = get_theme_mod( 'kwb_band_heading', "You deserve a space where you don’t have to hold everything together." );
    $body    = get_theme_mod( 'kwb_band_body',    'Online IFS therapy for adults throughout Massachusetts.' );
    $button  = get_theme_mod( 'kwb_band_button',  'Schedule a free consultation' );
    $fine    = get_theme_mod( 'kwb_band_fine',    "Reaching out doesn’t commit you to therapy. You can share what brings you here and ask any questions through the secure portal." );
    ?>
    <section class="contact-band">
      <div class="wrap">
        <p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
        <h2><?php echo esc_html( $heading ); ?></h2>
        <p><?php echo esc_html( $body ); ?></p>
        <a class="button light" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo esc_html( $button ); ?> <span>&#x2197;</span></a>
        <small><?php echo esc_html( $fine ); ?></small>
      </div>
    </section>
    <?php
}

function kwb_excerpt_length( $length ) {
    return 28;
}
add_filter( 'excerpt_length', 'kwb_excerpt_length' );

function kwb_excerpt_more( $more ) {
    return '…';
}
add_filter( 'excerpt_more', 'kwb_excerpt_more' );

// ACF local field group definitions — editable page content for Karen
function kwb_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

    // ── How I Help ─────────────────────────────────────────────────────────
    acf_add_local_field_group( [
        'key'      => 'group_kwb_how_i_help',
        'title'    => 'How I Help — Page Content',
        'location' => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-how-i-help.php' ] ] ],
        'fields'   => [
            // Hero
            [ 'key' => 'field_kwb_hih_eyebrow', 'name' => 'hero_eyebrow',  'label' => 'Hero — Eyebrow',   'type' => 'text',     'default_value' => 'How I help' ],
            [ 'key' => 'field_kwb_hih_h1',      'name' => 'hero_heading',  'label' => 'Hero — Heading',   'type' => 'text',     'default_value' => "You're capable. You're caring. And you're tired." ],
            [ 'key' => 'field_kwb_hih_lead',    'name' => 'hero_lead',     'label' => 'Hero — Lead text', 'type' => 'textarea', 'default_value' => 'Maybe you are the person everyone depends on, yet internally you feel overwhelmed, self-critical, or disconnected from yourself.' ],
            // Issue list
            [ 'key' => 'field_kwb_h1_title',   'name' => 'issue_1_title',  'label' => 'Issue 1 — Title',  'type' => 'text',     'default_value' => 'Anxiety & overthinking' ],
            [ 'key' => 'field_kwb_h1_body',    'name' => 'issue_1_body',   'label' => 'Issue 1 — Body',   'type' => 'textarea', 'default_value' => "Understand worry with curiosity rather than self-criticism. We'll explore the protective parts that rely on constant analysis, helping you find more calm, clarity, and confidence." ],
            [ 'key' => 'field_kwb_h2_title',   'name' => 'issue_2_title',  'label' => 'Issue 2 — Title',  'type' => 'text',     'default_value' => 'Perfectionism' ],
            [ 'key' => 'field_kwb_h2_body',    'name' => 'issue_2_body',   'label' => 'Issue 2 — Body',   'type' => 'textarea', 'default_value' => 'Loosen the pressure to perform, get everything exactly right, or earn approval. Therapy can help you build a steadier sense of worth and make room for "good enough."' ],
            [ 'key' => 'field_kwb_h3_title',   'name' => 'issue_3_title',  'label' => 'Issue 3 — Title',  'type' => 'text',     'default_value' => 'People pleasing' ],
            [ 'key' => 'field_kwb_h3_body',    'name' => 'issue_3_body',   'label' => 'Issue 3 — Body',   'type' => 'textarea', 'default_value' => "Explore the part of you that works hard to keep the peace and care for everyone else. Together, we can build healthier boundaries without losing your kindness." ],
            [ 'key' => 'field_kwb_h4_title',   'name' => 'issue_4_title',  'label' => 'Issue 4 — Title',  'type' => 'text',     'default_value' => 'Burnout, conflict & change' ],
            [ 'key' => 'field_kwb_h4_body',    'name' => 'issue_4_body',   'label' => 'Issue 4 — Body',   'type' => 'textarea', 'default_value' => 'When your inner system feels overloaded or pulled in different directions, we can make room for understanding, balance, and a more grounded way forward.' ],
            // Quote section
            [ 'key' => 'field_kwb_h2_btn_label', 'name' => 'issue_2_btn_label', 'label' => 'Issue 2 — Button label', 'type' => 'text', 'default_value' => 'FAQ & Resources' ],
            [ 'key' => 'field_kwb_h2_btn_url',   'name' => 'issue_2_btn_url',   'label' => 'Issue 2 — Button URL',   'type' => 'text', 'default_value' => '/resources/' ],
            [ 'key' => 'field_kwb_h3_btn_label', 'name' => 'issue_3_btn_label', 'label' => 'Issue 3 — Button label', 'type' => 'text', 'default_value' => 'FAQ & Resources' ],
            [ 'key' => 'field_kwb_h3_btn_url',   'name' => 'issue_3_btn_url',   'label' => 'Issue 3 — Button URL',   'type' => 'text', 'default_value' => '/resources/' ],
            [ 'key' => 'field_kwb_hq_heading', 'name' => 'quote_heading',  'label' => 'Quote — Heading',  'type' => 'text',     'default_value' => 'Change that feels lasting, not forced.' ],
            [ 'key' => 'field_kwb_hq_body',    'name' => 'quote_body',     'label' => 'Quote — Body',     'type' => 'textarea', 'default_value' => "You may be navigating a relationship challenge, burnout, a major transition, or simply a season of feeling stuck. Whatever brings you to therapy, we'll meet it with curiosity rather than judgment." ],
        ],
    ] );

    // ── My Approach ────────────────────────────────────────────────────────
    acf_add_local_field_group( [
        'key'      => 'group_kwb_approach',
        'title'    => 'My Approach — Page Content',
        'location' => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-approach.php' ] ] ],
        'fields'   => [
            // Hero
            [ 'key' => 'field_kwb_ap_eyebrow', 'name' => 'hero_eyebrow',     'label' => 'Hero — Eyebrow',        'type' => 'text',     'default_value' => 'A compassionate way inward' ],
            [ 'key' => 'field_kwb_ap_h1',      'name' => 'hero_heading',     'label' => 'Hero — Heading',        'type' => 'text',     'default_value' => 'Every part of you has a purpose.' ],
            [ 'key' => 'field_kwb_ap_lead',    'name' => 'hero_lead',        'label' => 'Hero — Lead text',     'type' => 'textarea', 'default_value' => 'Internal Family Systems therapy offers a respectful way to understand what is happening inside—without judging or trying to eliminate parts of yourself.' ],
            // Split section
            [ 'key' => 'field_kwb_ap_sm',      'name' => 'self_mark',        'label' => 'Self mark tagline',    'type' => 'text',     'default_value' => 'Curiosity creates room for change.' ],
            [ 'key' => 'field_kwb_ap_heading', 'name' => 'approach_heading', 'label' => 'Intro — Heading',      'type' => 'text',     'default_value' => 'A gentler way to understand yourself.' ],
            [ 'key' => 'field_kwb_ap_body',    'name' => 'approach_body',    'label' => 'Intro — Body',         'type' => 'wysiwyg',  'default_value' => '<p>My approach is grounded in Internal Family Systems (IFS), a compassionate and effective therapy that helps you understand the different parts of yourself that shape your thoughts, emotions, and behaviors.</p><p>Instead of judging the parts that create anxiety, perfectionism, or self-criticism, we\'ll explore them with curiosity. As we understand what they are trying to protect, healing and lasting change become possible.</p><ul><li>Slow down and gain clarity</li><li>Build greater self-trust</li><li>Respond with calm and confidence</li></ul>', 'tabs' => 'visual' ],
            // Cards section
            [ 'key' => 'field_kwb_ap_ceyebrow','name' => 'cards_eyebrow',    'label' => 'Cards — Eyebrow',      'type' => 'text',     'default_value' => 'What the work can feel like' ],
            [ 'key' => 'field_kwb_ap_ch',      'name' => 'cards_heading',    'label' => 'Cards — Heading',      'type' => 'text',     'default_value' => 'Gentle, collaborative, and at your pace.' ],
            [ 'key' => 'field_kwb_ap_c1t',    'name' => 'card_1_title',     'label' => 'Card 1 — Title',       'type' => 'text',     'default_value' => 'Notice' ],
            [ 'key' => 'field_kwb_ap_c1b',    'name' => 'card_1_body',      'label' => 'Card 1 — Body',        'type' => 'textarea', 'default_value' => 'Recognize the thoughts, feelings, and patterns asking for attention.' ],
            [ 'key' => 'field_kwb_ap_c2t',    'name' => 'card_2_title',     'label' => 'Card 2 — Title',       'type' => 'text',     'default_value' => 'Understand' ],
            [ 'key' => 'field_kwb_ap_c2b',    'name' => 'card_2_body',      'label' => 'Card 2 — Body',        'type' => 'textarea', 'default_value' => 'Explore what each part is trying to protect, with compassion instead of criticism.' ],
            [ 'key' => 'field_kwb_ap_c3t',    'name' => 'card_3_title',     'label' => 'Card 3 — Title',       'type' => 'text',     'default_value' => 'Choose' ],
            [ 'key' => 'field_kwb_ap_c3b',    'name' => 'card_3_body',      'label' => 'Card 3 — Body',        'type' => 'textarea', 'default_value' => 'Create more space to respond from calm, clarity, confidence, and connection.' ],
        ],
    ] );

    // ── About Karen ────────────────────────────────────────────────────────
    acf_add_local_field_group( [
        'key'      => 'group_kwb_about',
        'title'    => 'About Karen — Page Content',
        'location' => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-about.php' ] ] ],
        'fields'   => [
            // Hero
            [ 'key' => 'field_kwb_ab_eyebrow', 'name' => 'hero_eyebrow',    'label' => 'Hero — Eyebrow',    'type' => 'text',    'default_value' => 'Meet Karen' ],
            [ 'key' => 'field_kwb_ab_h1',      'name' => 'hero_heading',    'label' => 'Hero — Heading',    'type' => 'text',    'default_value' => 'Experience, warmth, and room to be yourself.' ],
            [ 'key' => 'field_kwb_ab_lead',    'name' => 'hero_lead',       'label' => 'Hero — Lead text',  'type' => 'textarea','default_value' => 'A respectful, down-to-earth, and collaborative space for honest conversation and meaningful change.' ],
            // Bio section
            [ 'key' => 'field_kwb_ab_tagline', 'name' => 'portrait_tagline','label' => 'Portrait tagline',  'type' => 'text',    'default_value' => 'Respectful · Gentle · Collaborative' ],
            [ 'key' => 'field_kwb_ab_bio',     'name' => 'bio',             'label' => 'Bio',               'type' => 'wysiwyg', 'default_value' => "<p>I'm a Licensed Mental Health Counselor and Certified IFS Therapist with more than 26 years of experience.</p><p>I knew I wanted to be a therapist as early as high school—it felt like a natural fit. My style is warm, down-to-earth, respectful, and collaborative. I want therapy to feel like a place where you can be honest about what is difficult and discover strengths you may have lost sight of.</p><p>I earned my master's degree in counseling from Rhode Island College. Outside of work, I enjoy kayaking, crafting, house projects, and volunteering in my community.</p>", 'tabs' => 'visual' ],
            [ 'key' => 'field_kwb_ab_cred1',   'name' => 'credential_1',    'label' => 'Credential 1',      'type' => 'text',    'default_value' => 'LMHC since 2002' ],
            [ 'key' => 'field_kwb_ab_cred2',   'name' => 'credential_2',    'label' => 'Credential 2',      'type' => 'text',    'default_value' => 'IFS certified since 2020' ],
            [ 'key' => 'field_kwb_ab_cred3',   'name' => 'credential_3',    'label' => 'Credential 3',      'type' => 'text',    'default_value' => '26+ years' ],
            // Quote section
            [ 'key' => 'field_kwb_ab_qh',      'name' => 'quote_heading',   'label' => 'Quote — Heading',   'type' => 'text',    'default_value' => 'A relationship built on trust.' ],
            [ 'key' => 'field_kwb_ab_qb',      'name' => 'quote_body',      'label' => 'Quote — Body',      'type' => 'wysiwyg', 'default_value' => "<p>The first appointment is a chance for us to get to know one another. We'll talk about what is bringing you to therapy, your history, relationships, current life, and hopes for our work.</p><p>By the end, we can both decide whether working together feels like a good fit.</p>", 'tabs' => 'visual' ],
        ],
    ] );

    // ── Fees & Insurance ───────────────────────────────────────────────────
    acf_add_local_field_group( [
        'key'      => 'group_kwb_fees',
        'title'    => 'Fees & Insurance — Page Content',
        'location' => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-fees.php' ] ] ],
        'fields'   => [
            // Hero
            [ 'key' => 'field_kwb_fe_eyebrow', 'name' => 'hero_eyebrow',    'label' => 'Hero — Eyebrow',        'type' => 'text',     'default_value' => 'Fees & insurance' ],
            [ 'key' => 'field_kwb_fe_h1',      'name' => 'hero_heading',    'label' => 'Hero — Heading',        'type' => 'text',     'default_value' => 'Clear information from the start.' ],
            [ 'key' => 'field_kwb_fe_lead',    'name' => 'hero_lead',       'label' => 'Hero — Lead text',     'type' => 'textarea', 'default_value' => 'Individual therapy is offered 100% by secure video for adults located in Massachusetts. Karen does not provide couples or family therapy.' ],
            // Practical details
            [ 'key' => 'field_kwb_fe_inteyeb', 'name' => 'intro_eyebrow',   'label' => 'Details — Eyebrow',    'type' => 'text',     'default_value' => 'Practical details' ],
            [ 'key' => 'field_kwb_fe_inth',    'name' => 'intro_heading',   'label' => 'Details — Heading',    'type' => 'text',     'default_value' => 'A simple path forward.' ],
            [ 'key' => 'field_kwb_fe_intb',    'name' => 'intro_body',      'label' => 'Details — Body',       'type' => 'textarea', 'default_value' => 'Begin with a free 15-minute consultation to ask questions and see whether working together feels right.' ],
            [ 'key' => 'field_kwb_fe_rate',    'name' => 'session_rate',    'label' => 'Session rate',         'type' => 'text',     'default_value' => '$175' ],
            [ 'key' => 'field_kwb_fe_snote',   'name' => 'session_note',    'label' => 'Session note',         'type' => 'text',     'default_value' => 'Telehealth · Adults 18+' ],
            [ 'key' => 'field_kwb_fe_ins',     'name' => 'insurance',       'label' => 'Insurance accepted',   'type' => 'text',     'default_value' => 'BCBS of Massachusetts & Aetna' ],
            [ 'key' => 'field_kwb_fe_inote',   'name' => 'insurance_note',  'label' => 'Insurance note',       'type' => 'textarea', 'default_value' => 'For other insurance plans, an itemized receipt can be provided for possible out-of-network reimbursement. Please verify benefits with your insurer.' ],
            // FAQs
            [ 'key' => 'field_kwb_fe_feyeb',   'name' => 'faqs_eyebrow',    'label' => 'FAQs — Eyebrow',       'type' => 'text',     'default_value' => 'Common questions' ],
            [ 'key' => 'field_kwb_fe_fh',      'name' => 'faqs_heading',    'label' => 'FAQs — Heading',       'type' => 'text',     'default_value' => 'A little more clarity before you begin.' ],
            [ 'key' => 'field_kwb_fe_f1q',     'name' => 'faq_1_question',  'label' => 'FAQ 1 — Question',     'type' => 'text',     'default_value' => 'Do you offer a consultation?' ],
            [ 'key' => 'field_kwb_fe_f1a',     'name' => 'faq_1_answer',    'label' => 'FAQ 1 — Answer',       'type' => 'textarea', 'default_value' => 'Yes. A free 15-minute consultation gives us time to briefly connect, answer questions, and see whether working together feels right.' ],
            [ 'key' => 'field_kwb_fe_f2q',     'name' => 'faq_2_question',  'label' => 'FAQ 2 — Question',     'type' => 'text',     'default_value' => 'What happens in the first session?' ],
            [ 'key' => 'field_kwb_fe_f2a',     'name' => 'faq_2_answer',    'label' => 'FAQ 2 — Answer',       'type' => 'textarea', 'default_value' => "We'll talk about what brings you to therapy, your history, current life, and what you hope may change." ],
            [ 'key' => 'field_kwb_fe_f3q',     'name' => 'faq_3_question',  'label' => 'FAQ 3 — Question',     'type' => 'text',     'default_value' => 'Do you accept insurance?' ],
            [ 'key' => 'field_kwb_fe_f3a',     'name' => 'faq_3_answer',    'label' => 'FAQ 3 — Answer',       'type' => 'textarea', 'default_value' => 'Karen works with BCBS of Massachusetts and Aetna. For other plans, an itemized receipt may support out-of-network reimbursement.' ],
            [ 'key' => 'field_kwb_fe_f4q',     'name' => 'faq_4_question',  'label' => 'FAQ 4 — Question',     'type' => 'text',     'default_value' => 'Where are sessions available?' ],
            [ 'key' => 'field_kwb_fe_f4a',     'name' => 'faq_4_answer',    'label' => 'FAQ 4 — Answer',       'type' => 'textarea', 'default_value' => 'Sessions are online for adults who are physically located in Massachusetts at the time of the appointment.' ],
        ],
    ] );

    // ── Blank Canvas (featured-image hero optional) ────────────────────────
    acf_add_local_field_group( [
        'key'      => 'group_kwb_blank',
        'title'    => 'Page Hero (optional)',
        'location' => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-blank.php' ] ] ],
        'fields'   => [
            [ 'key' => 'field_kwb_bl_eyebrow', 'name' => 'hero_eyebrow', 'label' => 'Hero — Eyebrow (small text above title)', 'type' => 'text',     'default_value' => '' ],
            [ 'key' => 'field_kwb_bl_lead',    'name' => 'hero_lead',    'label' => 'Hero — Lead text (below title)',           'type' => 'textarea', 'default_value' => '' ],
        ],
        'instruction_placement' => 'label',
        'description' => 'Set a Featured Image in the sidebar to enable the hero banner. The page title becomes the h1 automatically.',
    ] );
}
add_action( 'acf/init', 'kwb_acf_fields' );

// Pre-fill ACF fields with their default values so Karen sees the current content on first edit
add_filter( 'acf/load_value', function( $value, $post_id, $field ) {
    if ( ( $value === null || $value === '' ) && ! empty( $field['default_value'] ) ) {
        return $field['default_value'];
    }
    return $value;
}, 10, 3 );
