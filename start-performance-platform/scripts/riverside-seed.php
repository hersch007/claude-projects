<?php
/*
 * Riverside demo seeder — run with:  wp eval-file riverside-seed.php --path=<wp root>
 *
 * Builds a believable 60-day history for a fictional "City of Riverside" so the
 * Government Service Core + AI screens have something to show. DEMO ONLY: it
 * wipes every city table and the whole team first, then rebuilds them.
 *
 * Team logins (email + PIN): everyone uses PIN 1234 except the admin (2468).
 */

if ( ! defined( 'ABSPATH' ) ) { echo "Run via wp eval-file.\n"; exit; }
global $wpdb;
$p   = $wpdb->prefix;
$now = current_time( 'timestamp' );

// ── Wipe (demo instance only) ────────────────────────────────────────────────
foreach ( array( 'sp_city_tickets', 'sp_city_ticket_depts', 'sp_city_ticket_notes', 'sp_city_notification_log', 'sp_city_oncall', 'sp_team' ) as $t ) {
    $wpdb->query( "TRUNCATE TABLE {$p}{$t}" );
}
foreach ( $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'sp_ai_city_%'" ) as $o ) delete_option( $o );

// ── City settings ────────────────────────────────────────────────────────────
update_option( 'sp_city_name',              'City of Riverside' );
update_option( 'sp_city_initials',          'RV' );
update_option( 'sp_city_primary_color',     '#1F3A5F' );
update_option( 'sp_city_secondary_color',   '#2E7D6B' );
update_option( 'sp_city_escalate_minutes',  30 );
update_option( 'sp_city_supervisor_email',  'operations@riverside-demo.example' );
update_option( 'sp_setup_complete',         1 );
update_option( 'sp_daily_digest_enabled',   0 );
update_option( 'sp_ai_city_weekly_enabled', 1 );
update_option( 'sp_ai_city_weekly_extra',   '' );

// ── Departments (GSC seeds the four defaults on activation) ──────────────────
if ( function_exists( 'sp_city_create_tables' ) ) sp_city_create_tables();
$depts = $wpdb->get_results( "SELECT id, name FROM {$p}sp_city_departments ORDER BY sort_order" );
$D = array();
foreach ( $depts as $d ) $D[ $d->name ] = (int) $d->id;
$WS = $D['Water/Sewer']; $EL = $D['Electric']; $ST = $D['Streets/Sanitation']; $UB = $D['Utility Billing'];

// ── Team ─────────────────────────────────────────────────────────────────────
$team = array(
    // name, email, core role, city role, pin
    array( 'Dana Whitfield',   'dana.whitfield@riverside-demo.example',  'admin', 'city_admin',        '2468' ),
    array( 'Marcus Bell',      'marcus.bell@riverside-demo.example',     'agent', 'city_supervisor',   '1234' ),
    array( 'Renee Caldwell',   'renee.caldwell@riverside-demo.example',  'agent', 'city_supervisor',   '1234' ),
    array( 'Tyler Nguyen',     'tyler.nguyen@riverside-demo.example',    'agent', 'city_employee',     '1234' ),
    array( 'Luis Herrera',     'luis.herrera@riverside-demo.example',    'agent', 'city_employee',     '1234' ),
    array( 'Brianna Foster',   'brianna.foster@riverside-demo.example',  'agent', 'city_employee',     '1234' ),
    array( 'Sam Okafor',       'sam.okafor@riverside-demo.example',      'agent', 'city_employee',     '1234' ),
    array( 'Gail Peterson',    'gail.peterson@riverside-demo.example',   'agent', 'city_employee',     '1234' ),
    array( 'Kim Alvarez',      'kim.alvarez@riverside-demo.example',     'agent', 'call_center_admin', '1234' ),
    array( 'Jordan Reyes',     'jordan.reyes@riverside-demo.example',    'agent', 'call_center',       '1234' ),
);
$M = array(); $roles = array();
foreach ( $team as $t ) {
    $wpdb->insert( "{$p}sp_team", array(
        'name' => $t[0], 'email' => $t[1], 'role' => $t[2], 'pin' => wp_hash_password( $t[4] ),
        'status' => 'active', 'core_access' => '', 'created_at' => date( 'Y-m-d H:i:s', $now - 120 * DAY_IN_SECONDS ),
        'last_login_at' => date( 'Y-m-d H:i:s', $now - rand( 1, 72 ) * HOUR_IN_SECONDS ),
    ) );
    $M[ $t[0] ] = (int) $wpdb->insert_id;
    $roles[ $M[ $t[0] ] ] = $t[3];
}
update_option( 'sp_city_member_roles', $roles );

// Crew by department (who gets assigned / acknowledges).
$crew = array(
    $WS => array( 'Tyler Nguyen', 'Luis Herrera' ),
    $EL => array( 'Brianna Foster' ),
    $ST => array( 'Sam Okafor', 'Gail Peterson' ),
    $UB => array( 'Renee Caldwell' ),
);
$supervisor_for = array( $WS => 'Marcus Bell', $EL => 'Renee Caldwell', $ST => 'Marcus Bell', $UB => 'Renee Caldwell' );

// ── On-call schedule: this week + next, one person per dept, 24h rotations ──
$week_start = strtotime( 'monday this week 07:00', $now );
$phones = array( 'Tyler Nguyen' => '(555) 014-2201', 'Luis Herrera' => '(555) 014-2202', 'Brianna Foster' => '(555) 014-2203', 'Sam Okafor' => '(555) 014-2204', 'Gail Peterson' => '(555) 014-2205', 'Renee Caldwell' => '(555) 014-2206', 'Marcus Bell' => '(555) 014-2207' );
for ( $day = 0; $day < 14; $day++ ) {
    $start = $week_start + $day * DAY_IN_SECONDS;
    foreach ( array( $WS, $EL, $ST ) as $dept ) {
        $who = $crew[ $dept ][ $day % count( $crew[ $dept ] ) ];
        $wpdb->insert( "{$p}sp_city_oncall", array(
            'team_member_id' => $M[ $who ], 'dept_id' => $dept,
            'start_datetime' => date( 'Y-m-d H:i:s', $start ), 'end_datetime' => date( 'Y-m-d H:i:s', $start + DAY_IN_SECONDS - 1 ),
            'phone' => $phones[ $who ], 'email' => strtolower( str_replace( ' ', '.', $who ) ) . '@riverside-demo.example',
            'notes' => $day % 7 >= 5 ? 'Weekend coverage' : '',
        ) );
    }
}

// ── Ticket content pools ─────────────────────────────────────────────────────
$streets = array( 'Mill Creek Road', 'Sycamore Street', 'Riverbend Drive', 'Old Ferry Road', 'Harbor View Lane', 'Cedar Hollow Court', 'Washington Avenue', 'Depot Street', 'Magnolia Terrace', 'Fairground Road', 'Bluff Street', 'Pecan Grove Circle', 'Church Street', 'Ironworks Way', 'Lakeshore Boulevard', 'Quarry Lane' );
$first = array( 'Angela', 'Derrick', 'Monica', 'Terrance', 'Sheila', 'Brandon', 'Carla', 'Vincent', 'Yolanda', 'Gregory', 'Nina', 'Howard', 'Priya', 'Wesley', 'Loretta', 'Devon', 'Marta', 'Clifford', 'Tasha', 'Raymond' );
$last  = array( 'Hollins', 'Pruitt', 'Escobar', 'Whitaker', 'Boyd', 'Landry', 'McRae', 'Tanaka', 'Sizemore', 'Dorsey', 'Vaughn', 'Ferrell', 'Osei', 'Kirkland', 'Padilla', 'Greer', 'Hubbard', 'Lomax', 'Chandler', 'Ybarra' );

$issues = array(
    $WS => array(
        array( 'emergency', 'Water main break, water shooting up through the pavement and running down the street. Road is starting to buckle.' ),
        array( 'emergency', 'Sewage backing up into the basement through the floor drain. Standing water and strong smell.' ),
        array( 'high',      'No water pressure at all since this morning. Neighbors on both sides have the same problem.' ),
        array( 'high',      'Water running continuously from the meter box at the curb, has been going for two days.' ),
        array( 'high',      'Manhole cover in the road is missing. Somebody put a cone by it but it is a hazard at night.' ),
        array( 'normal',    'Water has a brown tint and a metallic smell when the tap is first turned on.' ),
        array( 'normal',    'Slow leak at the meter, ground around it stays wet even when it has not rained.' ),
        array( 'normal',    'Sewer smell strong near the storm drain at the corner, worse in the afternoons.' ),
        array( 'normal',    'Fire hydrant on the corner is leaking from the base.' ),
        array( 'low',       'Water meter lid is cracked and the box is full of dirt.' ),
        array( 'low',       'Request to have the water meter re-read, bill seems higher than usual.' ),
    ),
    $EL => array(
        array( 'emergency', 'Power line down across the driveway after the storm. Line is on the ground and sparking.' ),
        array( 'emergency', 'Transformer on the pole is smoking and there was a loud bang. Half the block lost power.' ),
        array( 'high',      'Power out to the whole street since about 6 PM. No storm, just went off.' ),
        array( 'high',      'Street light pole is leaning badly after a car hit it. Wires look tight.' ),
        array( 'high',      'Lights flickering and dimming all evening. Worried about appliances.' ),
        array( 'normal',    'Street light out at the intersection. Very dark for the crosswalk.' ),
        array( 'normal',    'Three street lights in a row are out along the park side of the road.' ),
        array( 'normal',    'Tree limbs growing into the power lines behind the house.' ),
        array( 'low',       'Street light stays on all day.' ),
        array( 'low',       'Request to check the meter, the display is blank.' ),
    ),
    $ST => array(
        array( 'high',      'Large pothole in the travel lane, at least a foot across and deep enough to damage tires. Two cars pulled over already.' ),
        array( 'high',      'Tree down across the road blocking both lanes.' ),
        array( 'high',      'Stop sign knocked down at the intersection, laying in the grass.' ),
        array( 'normal',    'Garbage was not picked up on the scheduled day. Bins were out by 6 AM.' ),
        array( 'normal',    'Storm drain clogged with leaves, street floods every time it rains.' ),
        array( 'normal',    'Pothole forming near the curb, getting bigger every week.' ),
        array( 'normal',    'Recycling missed for the second week in a row.' ),
        array( 'normal',    'Dead animal in the road needs to be picked up.' ),
        array( 'normal',    'Sidewalk section is lifted about three inches by tree roots, trip hazard.' ),
        array( 'low',       'Request for a bulk pickup, old couch and a mattress.' ),
        array( 'low',       'Street sign is faded and hard to read.' ),
        array( 'low',       'Grass on the right of way is over two feet tall.' ),
    ),
    $UB => array(
        array( 'high',      'Received a disconnect notice but the bill was paid online last week. Need this cleared before the cutoff date.' ),
        array( 'normal',    'Water bill is triple the normal amount with no change in usage. Requesting a review.' ),
        array( 'normal',    'Moved in on the first and still have not received a bill or account number.' ),
        array( 'normal',    'Auto-pay was charged twice this month.' ),
        array( 'low',       'Need a copy of the last six months of bills for a mortgage application.' ),
        array( 'low',       'Request to change the mailing address on the account.' ),
    ),
);

$note_pool = array(
    'ack'      => array( 'Crew dispatched, en route.', 'Called the resident to confirm the location and let them know we are on the way.', 'Picked this up from the on-call queue.', 'Reviewed with the on-call lead, heading out now.' ),
    'progress' => array( 'On site. Confirmed the issue, starting repair.', 'Isolated the section and set up traffic control. Repair underway.', 'Parts needed from the yard, will return first thing tomorrow.', 'Temporary fix in place, permanent repair scheduled.', 'Contractor notified, waiting on their crew.' ),
    'resolved' => array( 'Repair complete and tested. Area cleaned up.', 'Resolved. Resident notified by phone.', 'Completed. Left a door hanger with the resident.', 'Fixed and verified. No further action needed.' ),
    'public'   => array( 'Thank you for reporting this. A crew has been assigned and will be out shortly.', 'We have completed the repair. Please let us know if the problem returns.', 'Our crew is on site now working on this issue.', 'This has been scheduled. Thank you for your patience.' ),
);

// ── Build tickets: ~48 over the last 60 days, denser in the last 2 weeks ────
mt_srand( 20260908 );
$pick = function( $arr ) { return $arr[ mt_rand( 0, count( $arr ) - 1 ) ]; };
$dept_weights = array( $WS, $WS, $WS, $WS, $EL, $EL, $EL, $ST, $ST, $ST, $ST, $ST, $UB, $UB );
$repeat_addr  = '415 Mill Creek Road'; // chronic water problem: three visits
$specs = array();
for ( $i = 0; $i < 48; $i++ ) {
    // Age: 60% in the last 14 days, rest spread over 60 days.
    $age_days = mt_rand( 0, 9 ) < 6 ? mt_rand( 0, 13 ) + mt_rand( 0, 100 ) / 100 : mt_rand( 14, 59 ) + mt_rand( 0, 100 ) / 100;
    $dept     = $pick( $dept_weights );
    $issue    = $pick( $issues[ $dept ] );
    $specs[]  = array( 'age' => $age_days, 'dept' => $dept, 'priority' => $issue[0], 'desc' => $issue[1] );
}
// Guarantee a few specific stories the analysis can talk about.
$specs[] = array( 'age' => 0.15, 'dept' => $WS, 'priority' => 'emergency', 'desc' => $issues[ $WS ][0][1], 'force_open' => true, 'addr' => '1180 Riverbend Drive' );          // fresh emergency, unacknowledged
$specs[] = array( 'age' => 1.6,  'dept' => $EL, 'priority' => 'high',      'desc' => $issues[ $EL ][2][1], 'force_open' => true, 'no_ack' => true, 'addr' => '77 Depot Street' ); // unacknowledged > 24h
$specs[] = array( 'age' => 2.3,  'dept' => $ST, 'priority' => 'normal',    'desc' => $issues[ $ST ][3][1], 'force_open' => true, 'no_ack' => true );                              // unacknowledged > 24h
$specs[] = array( 'age' => 19,   'dept' => $ST, 'priority' => 'normal',    'desc' => $issues[ $ST ][8][1], 'force_open' => true, 'stuck' => true, 'addr' => '302 Church Street' );  // aging, stuck in progress
$specs[] = array( 'age' => 34,   'dept' => $UB, 'priority' => 'normal',    'desc' => $issues[ $UB ][1][1], 'force_open' => true, 'stuck' => true );                              // aging
foreach ( array( 41, 22, 6 ) as $age ) {                                                                                                                            // repeat address
    $specs[] = array( 'age' => $age, 'dept' => $WS, 'priority' => $age == 6 ? 'high' : 'normal', 'desc' => $age == 6 ? $issues[ $WS ][3][1] : $issues[ $WS ][6][1], 'addr' => $repeat_addr );
}
usort( $specs, function( $a, $b ) { return $b['age'] <=> $a['age']; } ); // oldest first so ticket numbers ascend

$per_day = array();
$counts  = array( 'total' => 0, 'open' => 0, 'resolved' => 0 );
foreach ( $specs as $s ) {
    $created = $now - (int) round( $s['age'] * DAY_IN_SECONDS );
    // Nudge into working hours-ish (6am–9pm) for realism.
    $h = (int) date( 'G', $created ); if ( $h < 6 ) $created += ( 6 - $h + mt_rand( 0, 8 ) ) * HOUR_IN_SECONDS; if ( $h > 21 ) $created -= ( $h - 20 ) * HOUR_IN_SECONDS;
    $daykey  = date( 'Ymd', $created );
    $per_day[ $daykey ] = isset( $per_day[ $daykey ] ) ? $per_day[ $daykey ] + 1 : 1;
    $number  = 'CSR-' . $daykey . '-' . str_pad( $per_day[ $daykey ], 5, '0', STR_PAD_LEFT );

    $dept    = $s['dept'];
    $addr    = isset( $s['addr'] ) ? $s['addr'] : ( mt_rand( 12, 1890 ) . ' ' . $pick( $streets ) );
    $source  = $pick( array( 'public', 'public', 'public', 'call_center', 'call_center', 'staff' ) );
    $rname   = $pick( $first ) . ' ' . $pick( $last );
    $remail  = mt_rand( 0, 3 ) ? strtolower( preg_replace( '/[^a-z]/i', '', $rname ) ) . '@example.com' : '';
    $rphone  = mt_rand( 0, 4 ) ? '(555) 01' . mt_rand( 0, 9 ) . '-' . str_pad( mt_rand( 0, 9999 ), 4, '0', STR_PAD_LEFT ) : '';
    if ( $source === 'staff' ) { $rname = $supervisor_for[ $dept ]; $remail = ''; $rphone = ''; }

    // Lifecycle. Older tickets are mostly resolved; recent ones mostly open.
    $force_open = ! empty( $s['force_open'] );
    $no_ack     = ! empty( $s['no_ack'] );
    $age_h      = $s['age'] * 24;
    $ack_after  = $s['priority'] === 'emergency' ? mt_rand( 10, 45 ) / 60 : ( $s['priority'] === 'high' ? mt_rand( 1, 5 ) : mt_rand( 2, 20 ) ); // hours
    $acked      = ! $no_ack && $age_h > $ack_after;
    $resolve_after = $ack_after + ( $s['priority'] === 'emergency' ? mt_rand( 4, 14 ) : ( $s['priority'] === 'high' ? mt_rand( 8, 60 ) : mt_rand( 24, 168 ) ) );
    $resolved   = ! $force_open && $acked && $age_h > $resolve_after;
    if ( ! $force_open && ! $resolved && $s['age'] > 20 && mt_rand( 0, 3 ) ) { $resolved = true; $resolve_after = min( $resolve_after, $age_h - 2 ); }

    $crew_name  = $pick( $crew[ $dept ] );
    $assigned   = ( $acked || ! empty( $s['stuck'] ) ) ? $M[ $crew_name ] : 0;
    if ( $s['age'] < 0.5 && ! $acked ) $assigned = 0;
    $ack_at     = $acked ? $created + (int) ( $ack_after * HOUR_IN_SECONDS ) : null;
    $res_at     = $resolved ? $created + (int) ( $resolve_after * HOUR_IN_SECONDS ) : null;
    $status     = $resolved ? ( $s['age'] > 25 ? 'closed' : 'resolved' ) : ( ! empty( $s['stuck'] ) ? 'in_progress' : ( $acked ? ( mt_rand( 0, 1 ) ? 'in_progress' : 'acknowledged' ) : 'open' ) );
    $updated    = $res_at ? $res_at : ( $ack_at ? $ack_at + mt_rand( 0, 6 ) * HOUR_IN_SECONDS : $created );

    $wpdb->insert( "{$p}sp_city_tickets", array(
        'ticket_number' => $number, 'source' => $source,
        'reporter_name' => $rname, 'reporter_email' => $remail, 'reporter_phone' => $rphone,
        'address' => $addr, 'description' => $s['desc'], 'priority' => $s['priority'], 'status' => $status,
        'created_at' => date( 'Y-m-d H:i:s', $created ), 'updated_at' => date( 'Y-m-d H:i:s', $updated ),
        'acknowledged_at' => $ack_at ? date( 'Y-m-d H:i:s', $ack_at ) : null, 'acknowledged_by' => $ack_at ? $crew_name : '',
        'assigned_to' => $assigned, 'resolved_at' => $res_at ? date( 'Y-m-d H:i:s', $res_at ) : null, 'photo_ids' => '',
    ) );
    $tid = (int) $wpdb->insert_id;

    // Department link (a few Streets tickets also touch Water/Sewer).
    $dept_ids = array( $dept );
    if ( $dept === $ST && strpos( $s['desc'], 'drain' ) !== false ) $dept_ids[] = $WS;
    foreach ( $dept_ids as $did ) {
        $wpdb->insert( "{$p}sp_city_ticket_depts", array(
            'ticket_id' => $tid, 'dept_id' => $did, 'status' => $resolved ? 'resolved' : ( $acked ? 'acknowledged' : 'open' ),
            'acknowledged_by' => $ack_at ? $crew_name : '', 'acknowledged_at' => $ack_at ? date( 'Y-m-d H:i:s', $ack_at ) : null,
        ) );
    }

    // Notification log: on-call email at creation (always), SMS for emergencies.
    $oncall_name = $crew[ $dept ][0];
    $wpdb->insert( "{$p}sp_city_notification_log", array(
        'ticket_id' => $tid, 'dept_name' => array_search( $dept, $D ), 'recipient_name' => $oncall_name,
        'recipient_email' => strtolower( str_replace( ' ', '.', $oncall_name ) ) . '@riverside-demo.example', 'recipient_phone' => '',
        'channel' => 'email', 'message' => 'New service ticket ' . $number . ' assigned to your department.', 'sent_at' => date( 'Y-m-d H:i:s', $created + 20 ), 'status' => 'sent',
    ) );
    if ( $s['priority'] === 'emergency' ) {
        $wpdb->insert( "{$p}sp_city_notification_log", array(
            'ticket_id' => $tid, 'dept_name' => array_search( $dept, $D ), 'recipient_name' => $oncall_name,
            'recipient_email' => '', 'recipient_phone' => $phones[ $oncall_name ],
            'channel' => 'sms', 'message' => 'EMERGENCY ' . $number . ' ' . $addr, 'sent_at' => date( 'Y-m-d H:i:s', $created + 25 ), 'status' => 'sent',
        ) );
    }

    // Notes.
    if ( $ack_at ) {
        $wpdb->insert( "{$p}sp_city_ticket_notes", array( 'ticket_id' => $tid, 'note' => $pick( $note_pool['ack'] ), 'created_by' => $crew_name, 'created_at' => date( 'Y-m-d H:i:s', $ack_at + 300 ), 'is_public' => 0 ) );
        if ( mt_rand( 0, 1 ) ) $wpdb->insert( "{$p}sp_city_ticket_notes", array( 'ticket_id' => $tid, 'note' => $note_pool['public'][0], 'created_by' => $crew_name, 'created_at' => date( 'Y-m-d H:i:s', $ack_at + 600 ), 'is_public' => 1 ) );
    }
    if ( $status === 'in_progress' || $resolved ) {
        $wpdb->insert( "{$p}sp_city_ticket_notes", array( 'ticket_id' => $tid, 'note' => $pick( $note_pool['progress'] ), 'created_by' => $crew_name, 'created_at' => date( 'Y-m-d H:i:s', ( $ack_at ? $ack_at : $created ) + 3 * HOUR_IN_SECONDS ), 'is_public' => 0 ) );
    }
    if ( $resolved ) {
        $wpdb->insert( "{$p}sp_city_ticket_notes", array( 'ticket_id' => $tid, 'note' => $pick( $note_pool['resolved'] ), 'created_by' => $crew_name, 'created_at' => date( 'Y-m-d H:i:s', $res_at ), 'is_public' => 0 ) );
        $wpdb->insert( "{$p}sp_city_ticket_notes", array( 'ticket_id' => $tid, 'note' => $note_pool['public'][1], 'created_by' => $crew_name, 'created_at' => date( 'Y-m-d H:i:s', $res_at + 120 ), 'is_public' => 1 ) );
    }
    if ( ! empty( $s['stuck'] ) ) {
        $wpdb->insert( "{$p}sp_city_ticket_notes", array( 'ticket_id' => $tid, 'note' => 'Waiting on contractor availability. Following up again next week.', 'created_by' => $supervisor_for[ $dept ], 'created_at' => date( 'Y-m-d H:i:s', $now - 6 * DAY_IN_SECONDS ), 'is_public' => 0 ) );
    }

    $counts['total']++; $resolved ? $counts['resolved']++ : $counts['open']++;
}

echo "Riverside seeded: {$counts['total']} tickets ({$counts['open']} open, {$counts['resolved']} resolved/closed), " . count( $team ) . " team members, " . ( 14 * 3 ) . " on-call shifts.\n";
echo "Logins: dana.whitfield@riverside-demo.example / 2468 (City Admin); marcus.bell@... / 1234 (Supervisor); tyler.nguyen@... / 1234 (Employee); kim.alvarez@... / 1234 (Call Center Admin).\n";
