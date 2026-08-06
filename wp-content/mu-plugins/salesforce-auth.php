<?php
/**
 * Salesforce JWT Bearer auth.
 *
 * Provides spark_sf_get_token() — returns a valid access token, cached in a
 * WP transient so Salesforce is only called when the token is missing or expired.
 *
 * Config: define these constants in wp-config.php
 *   SPARK_SF_CONSUMER_KEY   — Connected App consumer key from Salesforce
 *   SPARK_SF_USERNAME       — Salesforce integration user email
 *   SPARK_SF_KEY_PATH       — absolute path to server.key (default: /srv/www/secrets/server.key)
 *   SPARK_SF_AUTH_URL       — token endpoint (default: https://test.salesforce.com/services/oauth2/token)
 */

defined('ABSPATH') || exit;

/**
 * Returns ['token' => '...', 'instance_url' => 'https://...my.salesforce.com']
 * or false on failure. Both values are cached together.
 */
function spark_sf_get_credentials(): array|false {
    $cached = get_transient('spark_sf_credentials');
    if ($cached !== false) {
        return $cached;
    }

    $creds = _spark_sf_fetch_token();
    if ($creds === false) {
        return false;
    }

    // Salesforce tokens last 1 hour; cache for 55 min to give a safety margin
    set_transient('spark_sf_credentials', $creds, 55 * MINUTE_IN_SECONDS);
    return $creds;
}

function spark_sf_get_token(): string|false {
    $creds = spark_sf_get_credentials();
    return $creds ? $creds['token'] : false;
}

function _spark_sf_fetch_token(): array|false {
    $consumer_key = defined('SPARK_SF_CONSUMER_KEY') ? SPARK_SF_CONSUMER_KEY : '';
    $username     = defined('SPARK_SF_USERNAME')     ? SPARK_SF_USERNAME     : '';
    $key_path     = defined('SPARK_SF_KEY_PATH')     ? SPARK_SF_KEY_PATH     : '/srv/www/secrets/server.key';
    $auth_url     = defined('SPARK_SF_AUTH_URL')     ? SPARK_SF_AUTH_URL     : 'https://test.salesforce.com/services/oauth2/token';

    if (empty($consumer_key) || empty($username)) {
        error_log('[Spark SF] SPARK_SF_CONSUMER_KEY or SPARK_SF_USERNAME not defined in wp-config.php');
        return false;
    }

    $private_key = @file_get_contents($key_path);
    if ($private_key === false) {
        error_log('[Spark SF] Could not read private key at: ' . $key_path);
        return false;
    }

    $jwt = _spark_sf_build_jwt($consumer_key, $username, $auth_url, $private_key);
    if ($jwt === false) {
        return false;
    }

    $response = wp_remote_post($auth_url, [
        'body' => [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        error_log('[Spark SF] Token request failed: ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body['access_token'])) {
        error_log('[Spark SF] No access_token in response: ' . wp_remote_retrieve_body($response));
        return false;
    }

    return [
        'token'        => $body['access_token'],
        'instance_url' => rtrim($body['instance_url'], '/'),
    ];
}

function _spark_sf_build_jwt(string $consumer_key, string $username, string $auth_url, string $private_key): string|false {
    $header  = _spark_sf_base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claims  = _spark_sf_base64url(json_encode([
        'iss' => $consumer_key,
        'sub' => $username,
        'aud' => $auth_url,
        'exp' => time() + 180,  // 3 minutes — Salesforce requires exp within 5 min
    ]));

    $signing_input = $header . '.' . $claims;

    $key_resource = openssl_pkey_get_private($private_key);
    if ($key_resource === false) {
        error_log('[Spark SF] Could not parse private key: ' . openssl_error_string());
        return false;
    }

    $signature = '';
    $ok = openssl_sign($signing_input, $signature, $key_resource, OPENSSL_ALGO_SHA256);
    if (!$ok) {
        error_log('[Spark SF] openssl_sign failed: ' . openssl_error_string());
        return false;
    }

    return $signing_input . '.' . _spark_sf_base64url($signature);
}

function _spark_sf_base64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
