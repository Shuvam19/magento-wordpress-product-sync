<?php
/*
Plugin Name: Magento Store Product Sync
Description: Sync products from a Magento store to WordPress.
Version: 1.0
Author: Teammjay Teammjay
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_filter( 'http_request_timeout', function( $timeout ) {
    return 30; // Set to 30 seconds
});

require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';


require_once plugin_dir_path(__FILE__) . 'includes/crons/product-sync.php';


require_once plugin_dir_path(__FILE__) . 'includes/scripts.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax.php';




require_once plugin_dir_path(__FILE__) . 'utils/MagentoAPI.php';





