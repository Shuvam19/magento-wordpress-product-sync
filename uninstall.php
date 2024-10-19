<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Remove plugin settings
delete_option('magento_sync_store');

// Clear scheduled cron job
wp_clear_scheduled_hook('magento_sync_cron');