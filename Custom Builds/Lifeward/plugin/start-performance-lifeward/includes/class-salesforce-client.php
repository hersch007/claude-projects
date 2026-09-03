<?php
/**
 * Salesforce sync client — MOCK IMPLEMENTATION.
 *
 * There is no existing Salesforce integration anywhere in the Start Performance
 * platform (the only CRM wired up today is HubSpot, for SMTI only — see
 * plugin/start-performance-smti-sales/start-performance-smti-sales.php). This
 * class exists so every lead-capture branch on the Lifeward landing page has a
 * single, consistent seam to call, and so wiring in real Salesforce is a matter
 * of filling in sp_lifeward_salesforce_request() below rather than hunting
 * through the funnel logic.
 *
 * To go live:
 *   1. Create a Salesforce Connected App (or use an existing one) and choose an
 *      auth flow — JWT Bearer (server-to-server, no user login) is the usual
 *      choice for this kind of backend sync.
 *   2. Store instance URL + client id/secret (or JWT private key) via
 *      update_option(), the same way the HubSpot integration stores its
 *      Private App Token — see sp_lifeward_settings_section() in
 *      includes/settings.php for where the admin fields would go.
 *   3. Replace the body of sp_lifeward_salesforce_request() with a real
 *      wp_remote_post() to https://login.salesforce.com/services/oauth2/token
 *      for the access token, then to
 *      https://{instance}/services/data/vXX.0/sobjects/Lead for the actual
 *      create/update, mirroring sp_smti_hubspot_request() in the HubSpot
 *      plugin (wp_remote_request() + wp_remote_post(), Bearer auth header).
 *   4. Flip sp_lifeward_salesforce_live_mode() to read a real "live" toggle
 *      instead of always returning false.
 *   5. Map Lead/Task fields to whatever your org's Lead/Event objects expect
 *      (field API names are org-specific — there's nothing to copy from this
 *      codebase for that part).
 *
 * Until then, every call below is logged to the Core System's own sp_activity
 * table (record_type = 'lead') and returns a synthetic salesforce_id so the
 * funnel and admin dashboard behave exactly as they will once this is real.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SP_Lifeward_Salesforce_Client {

    /** Always false today — see file header. Never claims success in live mode without real credentials. */
    public static function live_mode_enabled() {
        return false;
    }

    /**
     * @param int    $lead_id Core sp_leads.id this sync is for (0 if not yet created).
     * @param array  $contact array( 'name' => ..., 'email' => ..., 'phone' => ... )
     * @param string $status  e.g. 'hot', 'scheduled', 'info-requested', 'lost'
     * @param array  $extra   Free-form extra fields (e.g. 'slot' => ISO datetime string)
     * @return array( 'ok' => bool, 'mock' => bool, 'salesforce_id' => string )
     */
    public static function sync_lead( $lead_id, $contact, $status, $extra = array() ) {
        $request = array(
            'object' => 'Lead',
            'fields' => array(
                'LastName'  => $contact['name'] !== '' ? $contact['name'] : 'Unknown',
                'Email'     => $contact['email'],
                'Phone'     => $contact['phone'],
                'LeadSource'=> 'Lifeward Website',
                'Status'    => sp_lifeward_salesforce_status_label( $status ),
            ),
            'extra' => $extra,
        );

        if ( self::live_mode_enabled() ) {
            return sp_lifeward_salesforce_request( $request );
        }

        $mock_id = 'SF-MOCK-' . strtoupper( wp_generate_password( 10, false ) );
        $response = array( 'ok' => true, 'mock' => true, 'salesforce_id' => $mock_id );

        if ( $lead_id > 0 ) {
            global $wpdb;
            $wpdb->insert( $wpdb->prefix . 'sp_activity', array(
                'record_type' => 'lead',
                'record_id'   => (int) $lead_id,
                'action'      => 'salesforce_sync',
                'detail'      => wp_json_encode( array( 'request' => $request, 'response' => $response, 'mode' => 'mock' ) ),
                'created_by'  => 0,
                'created_at'  => current_time( 'mysql' ),
            ) );
        }

        return $response;
    }
}

function sp_lifeward_salesforce_status_label( $status ) {
    $map = array(
        'hot'            => 'Working - Contacted',
        'scheduled'      => 'Working - Callback Scheduled',
        'info-requested' => 'Nurturing',
        'lost'           => 'Closed - Not Converted',
    );
    return isset( $map[ $status ] ) ? $map[ $status ] : 'Open';
}

/**
 * Placeholder for the real API call — see the file header for what needs to be
 * filled in here before self::live_mode_enabled() can ever return true.
 */
function sp_lifeward_salesforce_request( $request ) {
    return new WP_Error( 'sf_not_configured', 'Salesforce is not configured yet.' );
}
