<?php

// AJAX actions for starting and stopping sync
add_action('wp_ajax_start_sync', 'start_magento_sync');
add_action('wp_ajax_stop_sync', 'stop_magento_sync');

function start_magento_sync()
{
    if (!wp_next_scheduled('magento_sync_cron_job')) {
        wp_schedule_event(time(), 'every_minute', 'magento_sync_cron_job');
    }
    $pausedCronJobs = get_option('wp_crontrol_paused',[]);
    $pausedCronJobs = array_diff($pausedCronJobs, ['magento_sync_cron_job']);
    update_option('wp_crontrol_paused', $pausedCronJobs);
    wp_send_json_success('Sync started.');
}

function stop_magento_sync()
{
    $timestamp = wp_next_scheduled('magento_sync_cron_job');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'magento_sync_cron_job');
    }
    $pausedCronJobs = get_option('wp_crontrol_paused',[]);
    $pausedCronJobs[] = 'magento_sync_cron_job';
    update_option('wp_crontrol_paused', $pausedCronJobs);
    wp_send_json_success('Sync stopped.');
}