<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class Echo64_Transmissions {

    private static ?Echo64_Transmissions $instance = null;

    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    const CONTENT_TYPES = [ 'Essay', 'Deep Dive', 'Manifesto', 'Review', 'Signal Fragment' ];

    private function __construct() {
        add_action( 'pre_get_posts', [ $this, 'transmission_archive_query' ] );
        add_filter( 'the_content',   [ $this, 'inject_content_type_label' ] );
    }

    public function inject_content_type_label( string $content ): string {
        if ( ! is_singular( 'nad_transmission' ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $tags = get_the_terms( get_the_ID(), 'transmission_type' );
        if ( ! $tags || is_wp_error( $tags ) ) return $content;

        $label = null;
        foreach ( $tags as $tag ) {
            if ( in_array( $tag->name, self::CONTENT_TYPES, true ) ) {
                $label = $tag->name;
                break;
            }
        }

        if ( ! $label ) return $content;

        $slug  = sanitize_html_class( strtolower( str_replace( ' ', '-', $label ) ) );
        $badge = sprintf(
            '<div class="e64-transmission-type e64-transmission-type--%s">%s</div>',
            esc_attr( $slug ),
            esc_html( strtoupper( $label ) )
        );

        return $badge . $content;
    }

    public function transmission_archive_query( \WP_Query $query ): void {
        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }
        if ( $query->is_post_type_archive( 'nad_transmission' ) ) {
            $query->set( 'orderby', 'date' );
            $query->set( 'order', 'DESC' );
            if ( get_option( 'echo64_transmissions_unlimited', '0' ) === '1' ) {
                $query->set( 'posts_per_page', -1 );
            }
        }
    }
}
