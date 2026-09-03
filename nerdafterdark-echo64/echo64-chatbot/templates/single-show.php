<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) : the_post();
    $post_id  = get_the_ID();
    $network  = get_post_meta( $post_id, '_echo64_network', true );
    $year     = get_post_meta( $post_id, '_echo64_year', true );
    $verdict  = get_the_content();
    $question = Echo64_Shows_CPT::build_question( get_the_title() );
    $chat_url = esc_url( home_url( '/echo-64/' ) . '?e64q=' . urlencode( $question ) );

    $related = get_posts( [
        'post_type'      => Echo64_Shows_CPT::POST_TYPE,
        'posts_per_page' => 3,
        'orderby'        => 'rand',
        'post__not_in'   => [ $post_id ],
    ] );
?>

<div class="e64-show-single echo64-page">

    <div class="e64-show-single-header">
        <a class="e64-show-back" href="<?php echo esc_url( home_url( '/cancelled-shows/' ) ); ?>">&larr; ALL CANCELLED SHOWS</a>
        <div class="e64-shows-eyebrow"><?php echo esc_html( $network ); ?> &middot; <?php echo esc_html( $year ); ?></div>
        <h1 class="e64-show-single-title"><?php the_title(); ?></h1>
        <div class="e64-show-single-verdict"><?php echo wp_kses_post( wpautop( $verdict ) ); ?></div>
    </div>

    <a class="e64-show-single-cta" href="<?php echo $chat_url; ?>">
        ASK ECHO-64 ABOUT <?php echo esc_html( mb_strtoupper( get_the_title() ) ); ?> &rarr;
    </a>

    <?php if ( $related ) : ?>
    <div class="e64-show-related">
        <div class="e64-shows-eyebrow">ALSO CANCELLED</div>
        <div class="e64-show-related-grid">
            <?php foreach ( $related as $r ) :
                $r_network = get_post_meta( $r->ID, '_echo64_network', true );
                $r_year    = get_post_meta( $r->ID, '_echo64_year', true );
            ?>
            <a class="e64-show-card" href="<?php echo esc_url( get_permalink( $r ) ); ?>">
                <div class="e64-show-title"><?php echo esc_html( $r->post_title ); ?></div>
                <div class="e64-show-meta"><?php echo esc_html( $r_network ); ?> &middot; <?php echo esc_html( $r_year ); ?></div>
                <p class="e64-show-verdict"><?php echo esc_html( wp_strip_all_tags( $r->post_content ) ); ?></p>
                <span class="e64-show-cta">ASK ECHO-64 &rarr;</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php
endwhile;

get_footer();
