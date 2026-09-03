<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Echo64_Log — Admin conversation log + stats dashboard (#11 + #12)
 */
class Echo64_Log {

    private static ?Echo64_Log $instance = null;

    public static function instance(): self {
        if ( self::$instance === null ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_echo64_delete_session', [ $this, 'ajax_delete_session' ] );
    }

    // ── Stats ─────────────────────────────────────────────────────────────

    public function get_stats(): array {
        global $wpdb;
        $t = $wpdb->prefix . 'echo64_conversations';

        $total_msgs     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}" );
        $total_sessions = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$t}" );
        $avg_msgs       = $total_sessions > 0 ? round( $total_msgs / $total_sessions, 1 ) : 0;

        $active_sessions = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT session_id) FROM {$t}
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        // Messages per day — last 14 days
        $daily = $wpdb->get_results(
            "SELECT DATE(created_at) as day, COUNT(*) as cnt
             FROM {$t}
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            ARRAY_A
        );

        // Build a full 14-day array (fill gaps with 0)
        $days_map = [];
        for ( $i = 13; $i >= 0; $i-- ) {
            $d = date( 'Y-m-d', strtotime( "-{$i} days" ) );
            $days_map[ $d ] = 0;
        }
        foreach ( $daily ?: [] as $row ) {
            if ( isset( $days_map[ $row['day'] ] ) ) {
                $days_map[ $row['day'] ] = (int) $row['cnt'];
            }
        }

        // Top keywords from user messages (simple word frequency, stopwords excluded)
        $user_text = $wpdb->get_col(
            "SELECT content FROM {$t}
             WHERE role = 'user'
               AND content NOT LIKE '[%]'
             ORDER BY id DESC
             LIMIT 500"
        );

        $keywords = $this->extract_keywords( $user_text ?: [] );

        return compact( 'total_msgs', 'total_sessions', 'avg_msgs', 'active_sessions', 'days_map', 'keywords' );
    }

    private function extract_keywords( array $messages ): array {
        $stopwords = [
            'the','a','an','and','or','but','in','on','at','to','for','of','with',
            'is','it','its','this','that','was','are','be','been','have','has','had',
            'do','did','does','will','would','could','should','can','may','might',
            'i','you','he','she','we','they','me','him','her','us','them','my','your',
            'his','our','their','what','which','who','how','when','where','why',
            'not','no','so','if','as','up','out','about','than','more','just','get',
            'from','by','like','think','know','think','really','also','echo','64',
            'new','one','two','some','any','all','there','been','here',
        ];

        $freq = [];
        foreach ( $messages as $msg ) {
            $words = preg_split( '/[\s\W]+/u', strtolower( $msg ), -1, PREG_SPLIT_NO_EMPTY );
            foreach ( $words as $w ) {
                if ( strlen( $w ) < 3 ) continue;
                if ( in_array( $w, $stopwords, true ) ) continue;
                $freq[ $w ] = ( $freq[ $w ] ?? 0 ) + 1;
            }
        }

        arsort( $freq );
        return array_slice( $freq, 0, 24, true );
    }

    // ── Conversation Log ──────────────────────────────────────────────────

    public function get_sessions( int $page = 1, int $per_page = 15 ): array {
        global $wpdb;
        $t      = $wpdb->prefix . 'echo64_conversations';
        $offset = ( $page - 1 ) * $per_page;

        $total = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$t}" );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    session_id,
                    MIN(created_at)                        AS started,
                    MAX(created_at)                        AS last_active,
                    COUNT(*)                               AS msg_count,
                    SUM(role = 'user')                     AS user_count,
                    MAX(CASE WHEN role='user' AND content NOT LIKE '[%]'
                             THEN content END)             AS last_user_msg
                 FROM {$t}
                 GROUP BY session_id
                 ORDER BY last_active DESC
                 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        return [
            'sessions'   => $rows ?: [],
            'total'      => $total,
            'per_page'   => $per_page,
            'page'       => $page,
            'page_count' => (int) ceil( $total / $per_page ),
        ];
    }

    public function get_session_messages( string $session_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT role, content, created_at
                 FROM {$wpdb->prefix}echo64_conversations
                 WHERE session_id = %s
                 ORDER BY id ASC",
                $session_id
            ),
            ARRAY_A
        ) ?: [];
    }

    public function ajax_delete_session(): void {
        check_ajax_referer( 'echo64_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        $sid = sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) );
        if ( empty( $sid ) ) wp_send_json_error();

        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'echo64_conversations', [ 'session_id' => $sid ], [ '%s' ] );
        wp_send_json_success();
    }

    // ── Render: Stats Dashboard ───────────────────────────────────────────

    public function render_stats(): void {
        $s   = $this->get_stats();
        $max = max( array_values( $s['days_map'] ) ?: [1] );
        $max = max( $max, 1 );
        ?>
        <div class="echo64-stats-grid">
            <?php $this->stat_card( 'Total Messages',   (string) number_format_i18n( $s['total_msgs'] ),     'cyan' ); ?>
            <?php $this->stat_card( 'Total Sessions',   (string) number_format_i18n( $s['total_sessions'] ), 'purple' ); ?>
            <?php $this->stat_card( 'Active (7 days)',  (string) number_format_i18n( $s['active_sessions'] ),'green' ); ?>
            <?php $this->stat_card( 'Avg Msgs/Session', (string) $s['avg_msgs'],                              'amber' ); ?>
        </div>

        <div class="echo64-admin-card">
            <h3 class="echo64-card-title"><span class="echo64-card-icon">📈</span> Messages — Last 14 Days</h3>
            <div class="echo64-chart">
                <?php foreach ( $s['days_map'] as $day => $cnt ) :
                    $pct   = $max > 0 ? round( ( $cnt / $max ) * 100 ) : 0;
                    $label = date( 'M j', strtotime( $day ) );
                ?>
                <div class="echo64-bar-col">
                    <span class="echo64-bar-count"><?php echo $cnt ?: ''; ?></span>
                    <div class="echo64-bar" style="height:<?php echo $pct; ?>%"></div>
                    <span class="echo64-bar-label"><?php echo esc_html( $label ); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ( ! empty( $s['keywords'] ) ) : ?>
        <div class="echo64-admin-card">
            <h3 class="echo64-card-title"><span class="echo64-card-icon">💬</span> Top Topics (from visitor messages)</h3>
            <div class="echo64-keyword-cloud">
                <?php
                $max_kw = max( array_values( $s['keywords'] ) );
                foreach ( $s['keywords'] as $word => $count ) :
                    $size = round( 12 + ( $count / $max_kw ) * 14 );
                ?>
                <span class="echo64-keyword" style="font-size:<?php echo $size; ?>px"
                      title="<?php echo esc_attr( $count . ' mentions' ); ?>">
                    <?php echo esc_html( $word ); ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php
    }

    private function stat_card( string $label, string $value, string $color ): void {
        ?>
        <div class="echo64-stat-card echo64-stat-card--<?php echo esc_attr( $color ); ?>">
            <span class="echo64-stat-value"><?php echo esc_html( $value ); ?></span>
            <span class="echo64-stat-label"><?php echo esc_html( $label ); ?></span>
        </div>
        <?php
    }

    // ── Render: Conversation Log ──────────────────────────────────────────

    public function render_log(): void {
        $page = max( 1, (int) ( $_GET['log_page'] ?? 1 ) );
        $data = $this->get_sessions( $page );
        $nonce = wp_create_nonce( 'echo64_admin_nonce' );
        ?>
        <?php if ( empty( $data['sessions'] ) ) : ?>
            <p style="color:#888">No conversations recorded yet.</p>
        <?php else : ?>
        <div class="echo64-admin-card" style="padding:0;overflow:hidden;">
            <table class="widefat echo64-log-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Session', 'echo64-chatbot' ); ?></th>
                        <th><?php esc_html_e( 'Started', 'echo64-chatbot' ); ?></th>
                        <th><?php esc_html_e( 'Last Active', 'echo64-chatbot' ); ?></th>
                        <th><?php esc_html_e( 'Messages', 'echo64-chatbot' ); ?></th>
                        <th><?php esc_html_e( 'Last Message', 'echo64-chatbot' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'echo64-chatbot' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $data['sessions'] as $s ) :
                    $sid_short = substr( $s['session_id'], 0, 8 ) . '…';
                    $preview   = wp_trim_words( $s['last_user_msg'] ?? '', 10, '…' );
                ?>
                    <tr class="echo64-log-row" data-session="<?php echo esc_attr( $s['session_id'] ); ?>">
                        <td><code class="echo64-sid"><?php echo esc_html( $sid_short ); ?></code></td>
                        <td><?php echo esc_html( wp_date( 'M j Y g:i a', strtotime( $s['started'] ) ) ); ?></td>
                        <td><?php echo esc_html( wp_date( 'M j g:i a',  strtotime( $s['last_active'] ) ) ); ?></td>
                        <td><?php echo (int) $s['msg_count']; ?></td>
                        <td class="echo64-msg-preview"><?php echo esc_html( $preview ); ?></td>
                        <td>
                            <button class="button button-small echo64-view-session"
                                    data-session="<?php echo esc_attr( $s['session_id'] ); ?>">
                                View
                            </button>
                            <button class="button button-small echo64-delete-session"
                                    data-session="<?php echo esc_attr( $s['session_id'] ); ?>"
                                    data-nonce="<?php echo esc_attr( $nonce ); ?>"
                                    style="color:#c00">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr class="echo64-session-detail" id="detail-<?php echo esc_attr( $s['session_id'] ); ?>" hidden>
                        <td colspan="6" class="echo64-session-detail-cell">
                            <div class="echo64-session-msgs">
                                <?php foreach ( $this->get_session_messages( $s['session_id'] ) as $msg ) : ?>
                                <div class="echo64-log-msg echo64-log-msg--<?php echo esc_attr( $msg['role'] ); ?>">
                                    <span class="echo64-log-role"><?php echo esc_html( $msg['role'] ); ?></span>
                                    <span class="echo64-log-time"><?php echo esc_html( wp_date( 'g:i a', strtotime( $msg['created_at'] ) ) ); ?></span>
                                    <p><?php echo esc_html( $msg['content'] ); ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ( $data['page_count'] > 1 ) : ?>
        <div class="echo64-pagination">
            <?php for ( $i = 1; $i <= $data['page_count']; $i++ ) :
                $url = add_query_arg( [ 'page' => 'echo64-conversations', 'log_page' => $i ], admin_url( 'admin.php' ) );
                $cls = $i === $page ? 'button button-primary' : 'button';
            ?>
                <a href="<?php echo esc_url( $url ); ?>" class="<?php echo $cls; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <script>
        jQuery(function($){
            $('.echo64-view-session').on('click', function(){
                const sid = $(this).data('session');
                const $row = $('#detail-' + sid);
                $row.prop('hidden', !$row.prop('hidden'));
                $(this).text($row.prop('hidden') ? 'View' : 'Hide');
            });
            $('.echo64-delete-session').on('click', function(){
                if (!confirm('Delete this entire conversation? This cannot be undone.')) return;
                const $btn = $(this), sid = $btn.data('session'), nonce = $btn.data('nonce');
                $.post(ajaxurl, { action:'echo64_delete_session', nonce, session_id: sid })
                 .done(res => { if (res.success) $btn.closest('tr').next().addBack().fadeOut(300, function(){ $(this).remove(); }); });
            });
        });
        </script>
        <?php endif; ?>
        <?php
    }
}
