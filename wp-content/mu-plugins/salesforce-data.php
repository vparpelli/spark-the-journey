<?php
/**
 * Salesforce data layer for the Spark dashboard.
 *
 * Provides spark_sf_get_dashboard_data($sf_contact_id) which returns all the
 * fields needed by template-page1.php. Returns an array on success, false on failure.
 */

defined('ABSPATH') || exit;

function spark_sf_get_contact_id_by_email(string $email): string|false {
    $creds = spark_sf_get_credentials();
    if ($creds === false) {
        return false;
    }

    $soql   = "SELECT Id FROM Contact WHERE Email = '" . addslashes($email) . "' LIMIT 1";
    $result = _spark_sf_soql($creds, $soql);

    if ($result === false || empty($result['records'])) {
        error_log('[Spark SF] No Contact found for email: ' . $email);
        return false;
    }

    return $result['records'][0]['Id'];
}

function spark_sf_get_dashboard_data(string $sf_contact_id): array|false {
    $creds = spark_sf_get_credentials();
    if ($creds === false) {
        return false;
    }

    $contact = _spark_sf_query_contact($creds, $sf_contact_id);
    if ($contact === false) {
        return false;
    }

    $events = _spark_sf_query_events($creds, $sf_contact_id);

    return [
        // Identity
        'contact_type'          => $contact['SYSTEM_Contact_Record_Type__c'] ?? '',

        // Progress Report stats
        'last_pair_meetup'      => $contact['Last_Mentee_Meeting_Date__c']    ?? null,
        'last_spark_checkin'    => $contact['Most_Recent_Check_in_Form__c']   ?? null,
        'checkin_this_semester' => $contact['Check_in_call_this_semester__c'] ?? null,
        'last_progress_note'    => $contact['Last_Case_Note_Update__c']       ?? null,

        // Quick Links — personalized URLs stored on the Contact
        'schedule_call_url'     => $contact['Scheduling_Link__c']                  ?? '#',
        'rsvp_url'              => $contact['Personalized_Link_Event_RSVP__c']     ?? '#',
        'checkin_url'           => $contact['Personalized_Link_Check_In_Call__c']  ?? '#',
        'emergency_funds_url'   => $contact['Personalized_Link_EFR_Form__c']       ?? '#',

        // LearnUpon
        'lu_user_id'            => $contact['LearnUponP__LU_User_ID__c']           ?? null,

        // Upcoming Events
        'events'                => $events,
    ];
}

function _spark_sf_query_contact(array $creds, string $contact_id): array|false {
    $fields = implode(',', [
        'Id',
        'FirstName',
        'LastName',
        'SYSTEM_Contact_Record_Type__c',
        'Last_Mentee_Meeting_Date__c',
        'Most_Recent_Check_in_Form__c',
        'Check_in_call_this_semester__c',
        'Last_Case_Note_Update__c',
        'Scheduling_Link__c',
        'Personalized_Link_Event_RSVP__c',
        'Personalized_Link_Check_In_Call__c',
        'Personalized_Link_EFR_Form__c',
        'LearnUponP__LU_User_ID__c',
    ]);

    $soql   = 'SELECT ' . $fields . ' FROM Contact WHERE Id = \'' . addslashes($contact_id) . '\' LIMIT 1';
    $result = _spark_sf_soql($creds, $soql);

    if ($result === false || empty($result['records'])) {
        error_log('[Spark SF] Contact not found or query failed for ID: ' . $contact_id);
        return false;
    }

    return $result['records'][0];
}

function _spark_sf_query_events(array $creds, string $contact_id): array {
    $soql = "SELECT CampaignId, Campaign.Name, Campaign.StartDate, Campaign.Description
             FROM CampaignMember
             WHERE ContactId = '" . addslashes($contact_id) . "'
             AND Campaign.StartDate >= TODAY
             ORDER BY Campaign.StartDate ASC
             LIMIT 4";

    $result = _spark_sf_soql($creds, $soql);
    if ($result === false || empty($result['records'])) {
        return [];
    }

    return array_map(fn($r) => [
        'name'        => $r['Campaign']['Name']        ?? '',
        'date'        => $r['Campaign']['StartDate']   ?? '',
        'description' => $r['Campaign']['Description'] ?? '',
    ], $result['records']);
}

function _spark_sf_soql(array $creds, string $soql): array|false {
    $url = $creds['instance_url'] . '/services/data/v60.0/query?q=' . rawurlencode($soql);

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $creds['token'],
            'Content-Type'  => 'application/json',
        ],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        error_log('[Spark SF] SOQL request failed: ' . $response->get_error_message());
        return false;
    }

    $status = wp_remote_retrieve_response_code($response);
    $body   = json_decode(wp_remote_retrieve_body($response), true);

    if ($status === 401) {
        delete_transient('spark_sf_credentials');
        error_log('[Spark SF] 401 on SOQL — token cleared, will retry on next request');
        return false;
    }

    if ($status !== 200) {
        error_log('[Spark SF] SOQL error ' . $status . ': ' . wp_remote_retrieve_body($response));
        return false;
    }

    return $body;
}
