<?php
add_action('wp_ajax_test_magento_connection', 'test_magento_connection');

function test_magento_connection() {
    check_ajax_referer('test_magento_connection_nonce', 'security');

    // Get the API credentials from the database
    $api_url = get_option('magento_sync_api_url');
    $api_username = get_option('magento_sync_api_username');
    $api_password = get_option('magento_sync_api_password');

    if (!$api_url || !$api_username || !$api_password) {
        wp_send_json_error('Please provide valid API credentials.');
    }

    // Prepare the API URL for getting the token
    $token_url = rtrim($api_url, '/') . '/rest/V1/integration/admin/token';

    // Prepare the payload
    $payload = json_encode([
        'username' => $api_username,
        'password' => $api_password,
    ]);

    // Make the cURL request to Magento API
    $response = wp_remote_post($token_url, [
        'body' => $payload,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error('Failed to connect to the Magento API.');
    }

    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    if (isset($response_data['message'])) {
        wp_send_json_error('Error: ' . $response_data['message']);
    }

    update_option('magento_sync_api_token', $response_data);

    // Also store the current time for expiration checks
    update_option('magento_sync_token_time', time());

    // If successful, the response will contain a token
    if (isset($response_data) && !empty($response_data)) {
        wp_send_json_success('Connection successful! Token: ' . $response_data);
    } else {
        wp_send_json_error('Failed to retrieve a token.');
    }
}