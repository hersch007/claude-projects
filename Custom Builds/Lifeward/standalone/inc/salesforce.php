<?php
/**
 * Salesforce sync — MOCK IMPLEMENTATION.
 *
 * There is no real Salesforce integration wired up here (or anywhere else in the
 * Start Performance platform this app was spun out of). Every lead-capture branch
 * calls lw_salesforce_sync_lead() so there's one consistent seam to fill in once
 * real credentials exist, instead of scattering API calls through the funnel logic.
 *
 * To go live:
 *   1. Create a Salesforce Connected App and choose an auth flow — JWT Bearer
 *      (server-to-server, no user login) is the usual choice for this kind of
 *      backend sync.
 *   2. Store the instance URL + client id/secret (or JWT private key) somewhere
 *      safe — e.g. add fields to inc/config.php's settings and edit them via
 *      admin/settings.php, the same way the call-center webhook URL works.
 *   3. Replace the body of lw_salesforce_request() below with a real
 *      cURL/file_get_contents call to
 *      https://login.salesforce.com/services/oauth2/token for the access token,
 *      then to https://{instance}/services/data/vXX.0/sobjects/Lead for the
 *      actual create/update.
 *   4. Flip lw_salesforce_live_mode() to read a real "live" setting instead of
 *      always returning false.
 *   5. Map Lead/Task fields to whatever your org's Lead/Event objects expect
 *      (field API names are org-specific).
 *
 * Until then, every call is logged to the local activity table and returns a
 * synthetic salesforce_id so the funnel and admin dashboard behave exactly as
 * they will once this is real.
 */

function lw_salesforce_live_mode() {
    return false; // never claims success in live mode without real credentials
}

/**
 * @param int    $lead_id Local leads.id this sync is for (0 if not yet created).
 * @param array  $contact array('name' => ..., 'email' => ..., 'phone' => ...)
 * @param string $status  e.g. 'hot', 'scheduled', 'info-requested', 'lost'
 * @param array  $extra   Free-form extra fields (e.g. 'slot' => ISO datetime string)
 * @return array('ok' => bool, 'mock' => bool, 'salesforce_id' => string)
 */
function lw_salesforce_sync_lead( $lead_id, $contact, $status, $extra = array() ) {
    $status_labels = array(
        'hot'            => 'Working - Contacted',
        'scheduled'      => 'Working - Callback Scheduled',
        'info-requested' => 'Nurturing',
        'lost'           => 'Closed - Not Converted',
    );

    $request = array(
        'object' => 'Lead',
        'fields' => array(
            'LastName'   => $contact['name'] !== '' ? $contact['name'] : 'Unknown',
            'Email'      => $contact['email'],
            'Phone'      => $contact['phone'],
            'LeadSource' => 'Lifeward Website',
            'Status'     => isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : 'Open',
        ),
        'extra' => $extra,
    );

    if ( lw_salesforce_live_mode() ) {
        return lw_salesforce_request( $request );
    }

    $mock_id  = 'SF-MOCK-' . strtoupper( substr( bin2hex( random_bytes( 6 ) ), 0, 10 ) );
    $response = array( 'ok' => true, 'mock' => true, 'salesforce_id' => $mock_id );

    if ( $lead_id > 0 ) {
        lw_log_activity( $lead_id, 'salesforce_sync', array( 'request' => $request, 'response' => $response, 'mode' => 'mock' ) );
        lw_db()->prepare( 'UPDATE leads SET salesforce_id = ? WHERE id = ?' )->execute( array( $mock_id, $lead_id ) );
    }

    return $response;
}

/** Placeholder for the real API call — see the file header for what's needed first. */
function lw_salesforce_request( $request ) {
    return array( 'ok' => false, 'mock' => false, 'error' => 'Salesforce is not configured yet.' );
}
