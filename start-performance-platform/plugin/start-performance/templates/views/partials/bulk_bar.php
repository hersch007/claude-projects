<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// Expects: $bulk_entity (string), $bulk_statuses (assoc value=>label, may be empty)
$bulk_statuses = isset( $bulk_statuses ) ? $bulk_statuses : array();
?>
<div class="sp-bulk-bar" id="sp-bulk-bar" style="display:none">
    <span class="sp-bulk-count"><span id="sp-bulk-n">0</span> selected</span>
    <select name="bulk_action" id="sp-bulk-action" class="sp-bulk-select">
        <option value="">Bulk actions…</option>
        <?php foreach ( $bulk_statuses as $val => $label ) : ?>
            <option value="status:<?php echo esc_attr( $val ); ?>">Set status: <?php echo esc_html( $label ); ?></option>
        <?php endforeach; ?>
        <option value="delete">Delete selected</option>
    </select>
    <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm" id="sp-bulk-apply">Apply</button>
    <button type="button" class="sp-btn sp-btn-ghost sp-btn-sm" id="sp-bulk-clear">Clear</button>
</div>
