<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class NAD_Transmissions {

    private static ?NAD_Transmissions $instance = null;

    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init',            [ $this, 'register_post_type_hook' ] );
        add_action( 'init',            [ $this, 'register_taxonomy' ] );
        add_action( 'add_meta_boxes',  [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post',       [ $this, 'save_meta' ] );
        add_filter( 'manage_nad_transmission_posts_columns',       [ $this, 'custom_columns' ] );
        add_action( 'manage_nad_transmission_posts_custom_column', [ $this, 'custom_column_values' ], 10, 2 );
    }

    // ── Post Type ─────────────────────────────────────────────────────────

    public function register_post_type_hook(): void {
        self::register_post_type();
    }

    public static function register_post_type(): void {
        register_post_type( 'nad_transmission', [
            'labels' => [
                'name'               => 'Transmissions',
                'singular_name'      => 'Transmission',
                'add_new'            => 'New Transmission',
                'add_new_item'       => 'Add New Transmission',
                'edit_item'          => 'Edit Transmission',
                'view_item'          => 'View Transmission',
                'all_items'          => 'All Transmissions',
                'search_items'       => 'Search Transmissions',
                'not_found'          => 'No transmissions found.',
                'not_found_in_trash' => 'No transmissions in the trash.',
            ],
            'public'            => true,
            'publicly_queryable'=> true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_in_rest'      => true,   // Gutenberg support
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'transmissions', 'with_front' => false ],
            'capability_type'   => 'post',
            'has_archive'       => true,
            'hierarchical'      => false,
            'menu_position'     => 5,
            'menu_icon'         => 'dashicons-rss',
            'supports'          => [ 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ],
        ] );
    }

    // ── Taxonomy: Transmission Type ───────────────────────────────────────

    public function register_taxonomy(): void {
        register_taxonomy( 'transmission_type', 'nad_transmission', [
            'labels' => [
                'name'          => 'Transmission Types',
                'singular_name' => 'Transmission Type',
                'search_items'  => 'Search Types',
                'all_items'     => 'All Types',
                'edit_item'     => 'Edit Type',
                'update_item'   => 'Update Type',
                'add_new_item'  => 'Add New Type',
                'new_item_name' => 'New Type',
                'menu_name'     => 'Types',
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'transmission-type' ],
        ] );

        // Seed default terms if not already present
        $default_types = [ 'Essay', 'Manifesto', 'Review', 'Deep Dive', 'Signal Fragment' ];
        foreach ( $default_types as $type ) {
            if ( ! term_exists( $type, 'transmission_type' ) ) {
                wp_insert_term( $type, 'transmission_type' );
            }
        }
    }

    // ── Meta Boxes ────────────────────────────────────────────────────────

    public function add_meta_boxes(): void {
        add_meta_box(
            'nad_transmission_meta',
            '📡 Transmission Details',
            [ $this, 'render_meta_box' ],
            'nad_transmission',
            'side',
            'high'
        );
    }

    public function render_meta_box( WP_Post $post ): void {
        wp_nonce_field( 'nad_transmission_meta', 'nad_transmission_nonce' );

        $signal_strength = get_post_meta( $post->ID, '_nad_signal_strength', true ) ?: 85;
        $echo64_verdict  = get_post_meta( $post->ID, '_nad_echo64_verdict',  true ) ?: '';
        ?>
        <style>
            .nad-meta-row { margin-bottom: 14px; }
            .nad-meta-row label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; }
            .nad-meta-row input[type=range] { width: 100%; }
            .nad-meta-row textarea { width: 100%; height: 80px; font-size: 12px; }
            .nad-signal-display { font-family: monospace; font-size: 18px; color: #00b4cc; font-weight: bold; }
        </style>

        <div class="nad-meta-row">
            <label>Signal Strength</label>
            <input type="range" name="nad_signal_strength" min="1" max="100"
                   value="<?php echo esc_attr( $signal_strength ); ?>"
                   oninput="document.getElementById('nad-signal-val').textContent = this.value + '%'">
            <div class="nad-signal-display"><span id="nad-signal-val"><?php echo esc_html( $signal_strength ); ?>%</span></div>
            <p style="font-size:11px;color:#666;margin-top:4px;">How strong is this transmission? 100% = manifesto-level clarity.</p>
        </div>

        <div class="nad-meta-row">
            <label>Echo-64 Verdict</label>
            <textarea name="nad_echo64_verdict" placeholder="One line from Echo-64 on this transmission..."><?php echo esc_textarea( $echo64_verdict ); ?></textarea>
            <p style="font-size:11px;color:#666;margin-top:4px;">Optional. Shows as a pull-quote in the post footer.</p>
        </div>
        <?php
    }

    public function save_meta( int $post_id ): void {
        if ( ! isset( $_POST['nad_transmission_nonce'] )
             || ! wp_verify_nonce( $_POST['nad_transmission_nonce'], 'nad_transmission_meta' )
             || defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE
             || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['nad_signal_strength'] ) ) {
            $strength = max( 1, min( 100, (int) $_POST['nad_signal_strength'] ) );
            update_post_meta( $post_id, '_nad_signal_strength', $strength );
        }

        if ( isset( $_POST['nad_echo64_verdict'] ) ) {
            update_post_meta( $post_id, '_nad_echo64_verdict',
                sanitize_textarea_field( wp_unslash( $_POST['nad_echo64_verdict'] ) ) );
        }
    }

    // ── Admin Columns ─────────────────────────────────────────────────────

    public function custom_columns( array $columns ): array {
        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( $key === 'title' ) {
                $new['signal_strength'] = '📡 Signal';
                $new['transmission_type'] = 'Type';
            }
        }
        return $new;
    }

    public function custom_column_values( string $column, int $post_id ): void {
        if ( $column === 'signal_strength' ) {
            $strength = get_post_meta( $post_id, '_nad_signal_strength', true ) ?: '—';
            echo $strength !== '—' ? esc_html( $strength ) . '%' : '—';
        }
    }

}
