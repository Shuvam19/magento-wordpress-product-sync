<?php

add_action('admin_enqueue_scripts', 'magento_sync_enqueue_scripts');

function magento_sync_enqueue_scripts() {
    wp_enqueue_script('magento_sync_script', plugins_url('js/magento_sync.js', __DIR__), array('jquery'), null, true);

    // Pass AJAX URL and nonce to the script
    wp_localize_script('magento_sync_script', 'magentoSyncData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('test_magento_connection_nonce')
    ));

    wp_enqueue_script('cron_sync_script', plugins_url('js/sync-control.js', __DIR__), array('jquery'), null, true);

    wp_localize_script('cron_sync_script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));

}