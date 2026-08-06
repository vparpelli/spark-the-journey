<?php
/**
 * Security hardening — loaded automatically as a must-use plugin.
 *
 * Fixes:
 *   - REST API user enumeration (vuln 11772): block unauthenticated access to /wp/v2/users
 *   - XML-RPC methods restricted (backup for apache-level block)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Block unauthenticated access to the core WP users REST endpoint.
// Our spark-hub/v1 routes are unaffected — only wp/v2/users is restricted.
add_filter( 'rest_endpoints', function ( $endpoints ) {
    if ( ! is_user_logged_in() ) {
        unset( $endpoints['/wp/v2/users'] );
        unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    }
    return $endpoints;
} );

// Belt-and-suspenders: disable XML-RPC entirely.
add_filter( 'xmlrpc_enabled', '__return_false' );
