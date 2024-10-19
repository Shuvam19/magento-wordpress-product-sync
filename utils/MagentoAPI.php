<?php

class MagentoAPI {
    private $api_url;
    private $token;

    public function __construct() {
        $this->api_url = get_option('magento_sync_api_url');
        $this->token = $this->getToken();
    }

    // Get token or renew if expired
    private function getToken() {
        $saved_token = get_option('magento_sync_api_token');
        $token_time = get_option('magento_sync_token_time');

        // Check if token is older than 4 hours (Magento tokens expire after 4 hours)
        if (time() - $token_time > 14400) {
            // Token expired, generate a new one
            return $this->generateToken();
        }

        return $saved_token;
    }

    // Generate a new token
    private function generateToken() {
        $api_username = get_option('magento_sync_api_username');
        $api_password = get_option('magento_sync_api_password');

        $token_url = rtrim($this->api_url, '/') . '/rest/V1/integration/admin/token';
        $payload = json_encode([
            'username' => $api_username,
            'password' => $api_password,
        ]);

        $response = wp_remote_post($token_url, [
            'body' => $payload,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);
        $new_token = json_decode($response_body, true);

        if (isset($new_token['message'])) {
            return false;
        }

        // Save the new token and update the timestamp
        update_option('magento_sync_api_token', $new_token);
        update_option('magento_sync_token_time', time());

        return $new_token;
    }

    // Make API requests using the stored token
    public function request($endpoint, $method = 'GET', $body = []) {
        $url = rtrim($this->api_url, '/') . $endpoint;

        $this->token = $this->getToken();

        $response = wp_remote_request($url, [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ],
            'body' => !empty($body) ? json_encode($body) : null,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        if (isset($new_token['message']) && $new_token['message'] == 'The consumer isn\'t authorized to access %resources.') {
            // Token is not authorized, generate a new one
            $this->generateToken();
            // Retry the request
            return $this->request($endpoint, $method, $body);
        }

        $response_body = wp_remote_retrieve_body($response);
        return json_decode($response_body);
    }
}