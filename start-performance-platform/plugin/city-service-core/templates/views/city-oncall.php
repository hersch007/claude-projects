<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$action = sanitize_key( isset( $_GET['action'] ) ? $_GET['action'] : 'list' );
$id     = (int) ( isset( $_GET['id'] ) ? $_GET['id'] : 0 );
$depts  = sp_city_get_departments();
$team   = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_team WHERE status = 'active' ORDER BY name" );

// ── Dept edit form ─────────────────────────────────────────────────────────────
if ( $action === 'edit-dept' || $action === 'add-dept' ) {
    $dept = $action === 'edit-dept' && $id
        ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_city_departments WHERE id = %d", $id ) )
        : null;
    ?>
    <div class="sp-page-header">
        <h1><?php echo $dept ? 'Edit Department' : 'Add Department'; ?></h1>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=settings' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="city_dept">
            <input type="hidden" name="sp_id" value="<?php echo $dept ? (int) $dept->id : 0; ?>">
            <div class="sp-form-row">
                <div class="sp-field"><label>Department Name</label><input type="text" name="dept_name" value="<?php echo esc_attr( $dept ? $dept->name : '' ); ?>" required></div>
                <div class="sp-field"><label>Color</label><input type="color" name="dept_color" value="<?php echo esc_attr( $dept ? $dept->color : '#3B82F6' ); ?>"></div>
                <div class="sp-field"><label>Sort Order</label><input type="number" name="dept_sort" value="<?php echo esc_attr( $dept ? $dept->sort_order : 10 ); ?>" min="0"></div>
            </div>
            <div class="sp-field">
                <label style="display:flex;align-items:center;gap:8px">
                    <input type="checkbox" name="dept_active" value="1" <?php checked( $dept ? $dept->active : 1, 1 ); ?>>
                    Active (visible in ticket form)
                </label>
            </div>
            <div class="sp-form-actions"><button type="submit" class="sp-btn sp-btn-primary">Save Department</button></div>
        </form>
    </div>
    <?php return;
}

// ── Shift edit / new form ──────────────────────────────────────────────────────
if ( $action === 'new' || $action === 'edit' ) {
    $shift = $action === 'edit' && $id
        ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_city_oncall WHERE id = %d", $id ) )
        : null;

    // Default: start now, end in 8 hours
    $default_start = date( 'Y-m-d\TH:i', current_time( 'timestamp' ) );
    $default_end   = date( 'Y-m-d\TH:i', current_time( 'timestamp' ) + 8 * 3600 );
    ?>
    <div class="sp-page-header">
        <h1><?php echo $shift ? 'Edit Shift' : 'Assign On-Call Shift'; ?></h1>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-oncall' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="city_oncall">
            <input type="hidden" name="sp_id" value="<?php echo $shift ? (int) $shift->id : 0; ?>">

            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Team Member</label>
                    <select name="team_member_id" required>
                        <option value="">— Select —</option>
                        <?php foreach ( $team as $tm ) : ?>
                            <option value="<?php echo esc_attr( $tm->id ); ?>" <?php selected( $shift ? $shift->team_member_id : 0, $tm->id ); ?>><?php echo esc_html( $tm->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sp-field">
                    <label>Department</label>
                    <select name="dept_id" required>
                        <option value="">— Select —</option>
                        <?php foreach ( $depts as $d ) : ?>
                            <option value="<?php echo esc_attr( $d->id ); ?>" <?php selected( $shift ? $shift->dept_id : 0, $d->id ); ?>>
                                <?php echo esc_html( $d->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Shift Start</label>
                    <input type="datetime-local" name="start_datetime"
                        value="<?php echo esc_attr( $shift ? date( 'Y-m-d\TH:i', strtotime( $shift->start_datetime ) ) : $default_start ); ?>" required>
                </div>
                <div class="sp-field">
                    <label>Shift End</label>
                    <input type="datetime-local" name="end_datetime"
                        value="<?php echo esc_attr( $shift ? date( 'Y-m-d\TH:i', strtotime( $shift->end_datetime ) ) : $default_end ); ?>" required>
                </div>
            </div>

            <div class="sp-form-row">
                <div class="sp-field">
                    <label>SMS Phone for This Shift</label>
                    <input type="tel" name="phone" value="<?php echo esc_attr( $shift ? $shift->phone : '' ); ?>" placeholder="+15555550100">
                    <span class="sp-hint">Overrides team member's default phone for notifications during this shift.</span>
                </div>
                <div class="sp-field">
                    <label>Email for This Shift</label>
                    <input type="email" name="email" value="<?php echo esc_attr( $shift ? $shift->email : '' ); ?>">
                </div>
            </div>

            <div class="sp-field">
                <label>Notes (optional)</label>
                <input type="text" name="notes" value="<?php echo esc_attr( $shift ? $shift->notes : '' ); ?>" placeholder="e.g. covering for John, limited availability after 10pm">
            </div>

            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Save Shift</button>
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-oncall' ) ); ?>" class="sp-btn sp-btn-ghost">Cancel</a>
                <?php if ( $shift && function_exists( 'sp_is_admin_member' ) && sp_is_admin_member() ) : ?>
                    <a href="<?php echo esc_url( sp_delete_url( 'city_oncall', $shift->id ) ); ?>" data-sp-confirm="Remove this shift?" class="sp-btn sp-btn-ghost sp-danger">Delete</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php return;
}

// ── Schedule list view ─────────────────────────────────────────────────────────
// Show a week-view grid + upcoming list

$week_start = isset( $_GET['week'] ) ? sanitize_text_field( $_GET['week'] ) : date( 'Y-m-d', strtotime( 'monday this week', current_time( 'timestamp' ) ) );
$week_ts    = strtotime( $week_start );
$week_end   = date( 'Y-m-d 23:59:59', $week_ts + 6 * 86400 );
$week_label = date( 'M j', $week_ts ) . ' – ' . date( 'M j, Y', $week_ts + 6 * 86400 );
$prev_week  = date( 'Y-m-d', $week_ts - 7 * 86400 );
$next_week  = date( 'Y-m-d', $week_ts + 7 * 86400 );

$week_shifts = $wpdb->get_results( $wpdb->prepare(
    "SELECT o.*, d.name AS dept_name, d.color AS dept_color, tm.name AS member_name
     FROM {$wpdb->prefix}sp_city_oncall o
     LEFT JOIN {$wpdb->prefix}sp_city_departments d ON d.id = o.dept_id
     LEFT JOIN {$wpdb->prefix}sp_team tm ON tm.id = o.team_member_id
     WHERE o.start_datetime <= %s AND o.end_datetime >= %s
     ORDER BY o.start_datetime, d.sort_order",
    $week_end, $week_start
) );

// Index by day
$by_day = array();
for ( $i = 0; $i < 7; $i++ ) {
    $by_day[ date( 'Y-m-d', $week_ts + $i * 86400 ) ] = array();
}
foreach ( $week_shifts as $s ) {
    // Add to every day it covers within the week
    for ( $i = 0; $i < 7; $i++ ) {
        $day_ts  = $week_ts + $i * 86400;
        $day_str = date( 'Y-m-d', $day_ts );
        $day_end = date( 'Y-m-d 23:59:59', $day_ts );
        if ( $s->start_datetime <= $day_end && $s->end_datetime >= $day_str ) {
            $by_day[ $day_str ][] = $s;
        }
    }
}
$today = date( 'Y-m-d', current_time( 'timestamp' ) );
?>
<div class="sp-page-header">
    <h1>On-Call Schedule</h1>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-oncall&action=new' ) ); ?>" class="sp-btn sp-btn-primary">+ Assign Shift</a>
</div>

<?php if ( isset( $_GET['saved'] ) ) : ?>
    <div class="sp-alert sp-alert-success" style="margin-bottom:16px">Shift saved.</div>
<?php endif; ?>

<!-- Week navigation -->
<div class="sp-card" style="margin-bottom:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--sp-border)">
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-oncall&week=' . $prev_week ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">&larr; Prev Week</a>
        <h2 style="margin:0;font-size:16px"><?php echo esc_html( $week_label ); ?></h2>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-oncall&week=' . $next_week ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Next Week &rarr;</a>
    </div>

    <div style="display:grid;grid-template-columns:repeat(7,1fr);min-height:200px">
    <?php for ( $i = 0; $i < 7; $i++ ) :
        $day_ts  = $week_ts + $i * 86400;
        $day_str = date( 'Y-m-d', $day_ts );
        $is_today = $day_str === $today;
        $day_shifts = $by_day[ $day_str ];
    ?>
        <div style="border-right:<?php echo $i < 6 ? '1px solid var(--sp-border)' : 'none'; ?>;padding:12px;<?php echo $is_today ? 'background:rgba(59,130,246,.06)' : ''; ?>">
            <div style="font-size:12px;font-weight:<?php echo $is_today ? '700' : '600'; ?>;color:<?php echo $is_today ? 'var(--sp-accent)' : 'var(--sp-muted)'; ?>;margin-bottom:8px">
                <?php echo date( 'D', $day_ts ); ?><br>
                <span style="font-size:18px;color:<?php echo $is_today ? 'var(--sp-accent)' : 'var(--sp-text)'; ?>"><?php echo date( 'j', $day_ts ); ?></span>
            </div>
            <?php if ( empty( $day_shifts ) ) : ?>
                <div style="font-size:11px;color:var(--sp-muted);font-style:italic">No shifts</div>
            <?php else : ?>
                <?php foreach ( $day_shifts as $s ) : ?>
                <div style="background:<?php echo esc_attr( $s->dept_color ); ?>22;border-left:3px solid <?php echo esc_attr( $s->dept_color ); ?>;border-radius:4px;padding:5px 7px;margin-bottom:5px;font-size:11px">
                    <div style="font-weight:700;color:<?php echo esc_attr( $s->dept_color ); ?>"><?php echo esc_html( $s->dept_name ); ?></div>
                    <div style="font-weight:500"><?php echo esc_html( $s->member_name ?: '—' ); ?></div>
                    <div style="color:var(--sp-muted)"><?php echo date( 'g:ia', strtotime( $s->start_datetime ) ); ?>&ndash;<?php echo date( 'g:ia', strtotime( $s->end_datetime ) ); ?></div>
                    <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-oncall&action=edit&id=' . $s->id ) ); ?>" style="color:var(--sp-accent);text-decoration:none">edit</a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-oncall&action=new&date=' . $day_str ) ); ?>" style="font-size:11px;color:var(--sp-accent);text-decoration:none;display:block;margin-top:4px">+ add</a>
        </div>
    <?php endfor; ?>
    </div>
</div>

<!-- All upcoming shifts list -->
<div class="sp-card">
    <div class="sp-card-header"><h2>All Upcoming Shifts</h2></div>
    <?php
    $all_upcoming = $wpdb->get_results( $wpdb->prepare(
        "SELECT o.*, d.name AS dept_name, d.color AS dept_color, tm.name AS member_name
         FROM {$wpdb->prefix}sp_city_oncall o
         LEFT JOIN {$wpdb->prefix}sp_city_departments d ON d.id = o.dept_id
         LEFT JOIN {$wpdb->prefix}sp_team tm ON tm.id = o.team_member_id
         WHERE o.end_datetime >= %s
         ORDER BY o.start_datetime, d.sort_order
         LIMIT 50",
        current_time( 'mysql' )
    ) );
    ?>
    <?php if ( empty( $all_upcoming ) ) : ?>
        <p class="sp-empty">No upcoming shifts scheduled.</p>
    <?php else : ?>
    <table class="sp-table">
        <thead><tr><th>Who</th><th>Department</th><th>Shift Start</th><th>Shift End</th><th>Phone</th><th>Notes</th><th></th></tr></thead>
        <tbody>
        <?php foreach ( $all_upcoming as $s ) :
            $is_now = $s->start_datetime <= current_time( 'mysql' ) && $s->end_datetime >= current_time( 'mysql' );
        ?>
        <tr <?php if ( $is_now ) echo 'style="background:rgba(34,197,94,.07)"'; ?>>
            <td style="font-weight:600">
                <?php if ( $is_now ) : ?>
                    <span style="display:inline-block;width:7px;height:7px;background:#22c55e;border-radius:50%;margin-right:5px"></span>
                <?php endif; ?>
                <?php echo esc_html( $s->member_name ?: '—' ); ?>
            </td>
            <td>
                <span style="display:inline-block;width:8px;height:8px;background:<?php echo esc_attr( $s->dept_color ); ?>;border-radius:50%;margin-right:6px"></span>
                <?php echo esc_html( $s->dept_name ); ?>
            </td>
            <td class="sp-muted"><?php echo esc_html( date( 'D M j, g:ia', strtotime( $s->start_datetime ) ) ); ?></td>
            <td class="sp-muted"><?php echo esc_html( date( 'D M j, g:ia', strtotime( $s->end_datetime ) ) ); ?></td>
            <td class="sp-muted"><?php echo esc_html( $s->phone ?: '—' ); ?></td>
            <td class="sp-muted" style="font-size:12px"><?php echo esc_html( $s->notes ?: '—' ); ?></td>
            <td class="sp-actions">
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-oncall&action=edit&id=' . $s->id ) ); ?>">Edit</a>
                <a href="<?php echo esc_url( sp_delete_url( 'city_oncall', $s->id ) ); ?>" data-sp-confirm="Remove this shift?" class="sp-danger">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
