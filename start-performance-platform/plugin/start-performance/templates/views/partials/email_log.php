<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// Required: $email_record_type (string), $email_record_id (int), $emails (array)
global $wpdb;
$member = sp_get_current_team_member();
?>
<div class="sp-tab-pane">
    <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-inline-form">
        <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
        <input type="hidden" name="sp_type" value="email_log">
        <input type="hidden" name="sp_id" value="0">
        <input type="hidden" name="record_type" value="<?php echo esc_attr( $email_record_type ); ?>">
        <input type="hidden" name="record_id" value="<?php echo esc_attr( $email_record_id ); ?>">
        <input type="text" name="subject" placeholder="Subject…" required style="flex:1">
        <div class="sp-inline-form-footer">
            <div class="sp-field-inline">
                <label>Direction</label>
                <select name="direction">
                    <option value="sent">Sent</option>
                    <option value="received">Received</option>
                </select>
            </div>
            <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm">Log Email</button>
        </div>
        <textarea name="body" rows="2" placeholder="Optional body or notes…" style="margin-top:8px;width:100%;box-sizing:border-box;border:1px solid #e2e8f0;border-radius:6px;padding:8px 10px;font-size:13px;resize:vertical"></textarea>
    </form>

    <?php if ( empty( $emails ) ) : ?>
        <p class="sp-empty">No emails logged yet.</p>
    <?php else : foreach ( $emails as $em ) : ?>
        <div class="sp-email-item">
            <div class="sp-email-dir sp-email-dir-<?php echo esc_attr( $em->direction ); ?>">
                <?php echo $em->direction === 'received' ? '↙ In' : '↗ Out'; ?>
            </div>
            <div class="sp-email-body">
                <div class="sp-email-subject"><?php echo esc_html( $em->subject ?: '(no subject)' ); ?></div>
                <?php if ( $em->body ) : ?>
                <div class="sp-email-preview"><?php echo esc_html( wp_trim_words( $em->body, 15 ) ); ?></div>
                <?php endif; ?>
                <div class="sp-note-meta">
                    <?php echo esc_html( $em->author ?: 'System' ); ?> &middot;
                    <?php echo esc_html( date( 'M j, Y g:ia', strtotime( $em->logged_at ) ) ); ?>
                    <a href="<?php echo esc_url( sp_delete_url( 'email_log', $em->id ) ); ?>" data-sp-confirm="Delete this email log?" class="sp-note-delete">×</a>
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>
