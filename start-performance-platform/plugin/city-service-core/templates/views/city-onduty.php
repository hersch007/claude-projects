<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$now_shifts     = sp_city_get_oncall_now();
$upcoming       = sp_city_get_oncall_upcoming( 72 );
$depts          = sp_city_get_departments();
$city_name      = get_option( 'sp_city_name', get_bloginfo( 'name' ) );
$current_time   = current_time( 'timestamp' );
?>
<div class="sp-page-header">
    <div>
        <h1>On-Duty Board</h1>
        <div style="font-size:13px;color:var(--sp-muted)"><?php echo esc_html( $city_name ); ?> &mdash; <?php echo date( 'l, F j Y g:ia', $current_time ); ?></div>
    </div>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-oncall&action=new' ) ); ?>" class="sp-btn sp-btn-primary">+ Assign Shift</a>
</div>

<!-- On Duty NOW -->
<div class="sp-card" style="margin-bottom:20px">
    <div class="sp-card-header" style="background:var(--sp-surface-2,#f8fafc);border-bottom:1px solid var(--sp-border)">
        <h2 style="display:flex;align-items:center;gap:8px">
            <span style="display:inline-block;width:10px;height:10px;background:#22c55e;border-radius:50%;box-shadow:0 0 0 3px rgba(34,197,94,.25)"></span>
            On Duty Right Now
        </h2>
    </div>

    <?php if ( empty( $now_shifts ) ) : ?>
        <div style="padding:32px;text-align:center;color:var(--sp-muted)">
            <div style="font-size:32px;margin-bottom:8px">&#128338;</div>
            <div style="font-weight:600;margin-bottom:4px">No one is currently on call</div>
            <div style="font-size:13px">Incoming tickets will escalate to the supervisor.</div>
        </div>
    <?php else : ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1px;background:var(--sp-border)">
        <?php foreach ( $now_shifts as $s ) : ?>
            <div style="background:var(--sp-surface);padding:20px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                    <span style="display:inline-block;width:12px;height:12px;background:<?php echo esc_attr( $s->dept_color ); ?>;border-radius:50%"></span>
                    <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:<?php echo esc_attr( $s->dept_color ); ?>"><?php echo esc_html( $s->dept_name ); ?></span>
                </div>
                <div style="font-size:18px;font-weight:700;margin-bottom:4px"><?php echo esc_html( $s->member_name ?: 'Unassigned' ); ?></div>
                <?php if ( $s->phone ) : ?>
                    <div style="font-size:13px;color:var(--sp-muted)"><?php echo esc_html( $s->phone ); ?></div>
                <?php endif; ?>
                <?php if ( $s->email ) : ?>
                    <div style="font-size:13px;color:var(--sp-muted)"><?php echo esc_html( $s->email ); ?></div>
                <?php endif; ?>
                <div style="margin-top:10px;font-size:11px;color:var(--sp-muted);border-top:1px solid var(--sp-border);padding-top:8px">
                    Shift ends <?php echo esc_html( date( 'D M j g:ia', strtotime( $s->end_datetime ) ) ); ?>
                </div>
                <?php if ( $s->notes ) : ?>
                    <div style="margin-top:6px;font-size:12px;font-style:italic;color:var(--sp-muted)"><?php echo esc_html( $s->notes ); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Department Coverage Overview -->
<div class="sp-card" style="margin-bottom:20px">
    <div class="sp-card-header"><h2>Department Coverage</h2></div>
    <table class="sp-table">
        <thead><tr><th>Department</th><th>Status</th><th>On Call</th><th>Contact</th></tr></thead>
        <tbody>
        <?php
        $now_by_dept = array();
        foreach ( $now_shifts as $s ) {
            $now_by_dept[ $s->dept_id ][] = $s;
        }
        foreach ( $depts as $d ) :
            $covered = isset( $now_by_dept[ $d->id ] );
        ?>
        <tr>
            <td>
                <span style="display:inline-block;width:10px;height:10px;background:<?php echo esc_attr( $d->color ); ?>;border-radius:50%;margin-right:8px"></span>
                <strong><?php echo esc_html( $d->name ); ?></strong>
            </td>
            <td>
                <?php if ( $covered ) : ?>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#16a34a">
                        <span style="display:inline-block;width:7px;height:7px;background:#22c55e;border-radius:50%"></span> Covered
                    </span>
                <?php else : ?>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#dc2626">
                        <span style="display:inline-block;width:7px;height:7px;background:#ef4444;border-radius:50%"></span> Uncovered
                    </span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ( $covered ) :
                    $names = array_map( function($s){ return $s->member_name; }, $now_by_dept[ $d->id ] );
                    echo esc_html( implode( ', ', $names ) );
                else : echo '—'; endif; ?>
            </td>
            <td class="sp-muted" style="font-size:12px">
                <?php if ( $covered ) :
                    $phones = array_filter( array_map( function($s){ return $s->phone; }, $now_by_dept[ $d->id ] ) );
                    echo esc_html( implode( ', ', $phones ) ?: '—' );
                else : echo '—'; endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Upcoming Shifts -->
<?php if ( ! empty( $upcoming ) ) : ?>
<div class="sp-card">
    <div class="sp-card-header"><h2>Upcoming Shifts (next 72 hours)</h2></div>
    <table class="sp-table">
        <thead><tr><th>Department</th><th>Who</th><th>Shift Start</th><th>Shift End</th><th>Phone</th><th></th></tr></thead>
        <tbody>
        <?php foreach ( $upcoming as $s ) : ?>
        <tr>
            <td>
                <span style="display:inline-block;width:8px;height:8px;background:<?php echo esc_attr( $s->dept_color ); ?>;border-radius:50%;margin-right:6px"></span>
                <?php echo esc_html( $s->dept_name ); ?>
            </td>
            <td style="font-weight:600"><?php echo esc_html( $s->member_name ?: '—' ); ?></td>
            <td class="sp-muted"><?php echo esc_html( date( 'D M j, g:ia', strtotime( $s->start_datetime ) ) ); ?></td>
            <td class="sp-muted"><?php echo esc_html( date( 'D M j, g:ia', strtotime( $s->end_datetime ) ) ); ?></td>
            <td class="sp-muted"><?php echo esc_html( $s->phone ?: '—' ); ?></td>
            <td class="sp-actions">
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-oncall&action=edit&id=' . $s->id ) ); ?>">Edit</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
