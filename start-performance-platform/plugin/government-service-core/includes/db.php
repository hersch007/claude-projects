<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function sp_city_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Departments (Water/Sewer, Electric, Streets/Sanitation, Utility Billing + custom)
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_city_departments (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  slug varchar(50) NOT NULL DEFAULT '',
  name varchar(100) NOT NULL DEFAULT '',
  color varchar(7) NOT NULL DEFAULT '#3B82F6',
  active tinyint(1) NOT NULL DEFAULT 1,
  sort_order int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY slug (slug)
) $charset;" );

    // Main tickets table
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_city_tickets (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  ticket_number varchar(30) NOT NULL DEFAULT '',
  source varchar(10) NOT NULL DEFAULT 'public',
  reporter_name varchar(150) NOT NULL DEFAULT '',
  reporter_email varchar(150) NOT NULL DEFAULT '',
  reporter_phone varchar(30) NOT NULL DEFAULT '',
  address text NOT NULL DEFAULT '',
  description text,
  priority varchar(20) NOT NULL DEFAULT 'normal',
  status varchar(20) NOT NULL DEFAULT 'open',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  acknowledged_at datetime DEFAULT NULL,
  acknowledged_by varchar(100) NOT NULL DEFAULT '',
  assigned_to bigint(20) unsigned NOT NULL DEFAULT 0,
  resolved_at datetime DEFAULT NULL,
  photo_ids text,
  PRIMARY KEY  (id),
  KEY status (status),
  KEY priority (priority),
  KEY ticket_number (ticket_number),
  KEY created_at (created_at),
  KEY assigned_to (assigned_to)
) $charset;" );

    // Which departments a ticket touches
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_city_ticket_depts (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  ticket_id bigint(20) unsigned NOT NULL DEFAULT 0,
  dept_id bigint(20) unsigned NOT NULL DEFAULT 0,
  status varchar(20) NOT NULL DEFAULT 'open',
  acknowledged_by varchar(100) NOT NULL DEFAULT '',
  acknowledged_at datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY ticket_id (ticket_id),
  KEY dept_id (dept_id)
) $charset;" );

    // On-call schedule
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_city_oncall (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  team_member_id bigint(20) unsigned NOT NULL DEFAULT 0,
  dept_id bigint(20) unsigned NOT NULL DEFAULT 0,
  start_datetime datetime NOT NULL,
  end_datetime datetime NOT NULL,
  phone varchar(30) NOT NULL DEFAULT '',
  email varchar(150) NOT NULL DEFAULT '',
  notes varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY  (id),
  KEY team_member_id (team_member_id),
  KEY dept_id (dept_id),
  KEY start_datetime (start_datetime),
  KEY end_datetime (end_datetime)
) $charset;" );

    // Ticket notes / history
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_city_ticket_notes (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  ticket_id bigint(20) unsigned NOT NULL DEFAULT 0,
  note text NOT NULL,
  created_by varchar(100) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  is_public tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  KEY ticket_id (ticket_id)
) $charset;" );

    // Notification audit log
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_city_notification_log (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  ticket_id bigint(20) unsigned NOT NULL DEFAULT 0,
  dept_name varchar(100) NOT NULL DEFAULT '',
  recipient_name varchar(150) NOT NULL DEFAULT '',
  recipient_email varchar(150) NOT NULL DEFAULT '',
  recipient_phone varchar(30) NOT NULL DEFAULT '',
  channel varchar(10) NOT NULL DEFAULT 'email',
  message text,
  sent_at datetime NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'sent',
  PRIMARY KEY  (id),
  KEY ticket_id (ticket_id),
  KEY sent_at (sent_at)
) $charset;" );

    // Seed default departments if table is empty
    $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_city_departments" );
    if ( $count === 0 ) {
        $defaults = array(
            array( 'water-sewer',        'Water/Sewer',        '#3B82F6', 1 ),
            array( 'electric',           'Electric',           '#F59E0B', 2 ),
            array( 'streets-sanitation', 'Streets/Sanitation', '#10B981', 3 ),
            array( 'utility-billing',    'Utility Billing',    '#8B5CF6', 4 ),
        );
        foreach ( $defaults as list( $slug, $name, $color, $order ) ) {
            $wpdb->insert( $wpdb->prefix . 'sp_city_departments', array(
                'slug'       => $slug,
                'name'       => $name,
                'color'      => $color,
                'active'     => 1,
                'sort_order' => $order,
            ) );
        }
    } else {
        // One-time migration: rename old default departments to Clinton's actual departments.
        // Runs whenever the old names are still present (safe to run repeatedly).
        $renames = array(
            'Water'       => array( 'slug' => 'water-sewer',        'name' => 'Water/Sewer',        'color' => '#3B82F6', 'sort_order' => 1 ),
            'Electric'    => array( 'slug' => 'electric',           'name' => 'Electric',           'color' => '#F59E0B', 'sort_order' => 2 ),
            'Maintenance' => array( 'slug' => 'streets-sanitation', 'name' => 'Streets/Sanitation', 'color' => '#10B981', 'sort_order' => 3 ),
            'Garbage'     => array( 'slug' => 'utility-billing',    'name' => 'Utility Billing',    'color' => '#8B5CF6', 'sort_order' => 4 ),
        );
        foreach ( $renames as $old_name => $data ) {
            $wpdb->update(
                $wpdb->prefix . 'sp_city_departments',
                $data,
                array( 'name' => $old_name )
            );
        }
        // Merge old Sewer dept into Water/Sewer: reassign its ticket links then delete it.
        $sewer_id      = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}sp_city_departments WHERE name = 'Sewer'" );
        $water_sewer_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}sp_city_departments WHERE slug = 'water-sewer'" );
        if ( $sewer_id && $water_sewer_id ) {
            // Move any ticket links from Sewer to Water/Sewer (skip if already linked to avoid duplicates)
            $linked = $wpdb->get_col( $wpdb->prepare(
                "SELECT ticket_id FROM {$wpdb->prefix}sp_city_ticket_depts WHERE dept_id = %d", $sewer_id
            ) );
            foreach ( $linked as $tid ) {
                $already = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}sp_city_ticket_depts WHERE ticket_id = %d AND dept_id = %d",
                    $tid, $water_sewer_id
                ) );
                if ( ! $already ) {
                    $wpdb->update(
                        $wpdb->prefix . 'sp_city_ticket_depts',
                        array( 'dept_id' => $water_sewer_id ),
                        array( 'ticket_id' => $tid, 'dept_id' => $sewer_id )
                    );
                } else {
                    $wpdb->delete( $wpdb->prefix . 'sp_city_ticket_depts', array( 'ticket_id' => $tid, 'dept_id' => $sewer_id ) );
                }
            }
            $wpdb->delete( $wpdb->prefix . 'sp_city_departments', array( 'id' => $sewer_id ) );
        }
    }
}
