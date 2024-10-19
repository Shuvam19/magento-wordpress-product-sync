<div class="wrap">
    <h1>Magento Sync Control</h1>
    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
        <tr>
            <th scope="col">Sync Job</th>
            <th scope="col">Next Run (UTC)</th>
            <th scope="col">Status</th>
            <th scope="col">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php display_magento_sync_jobs(); ?>
        </tbody>
    </table>
</div>


<?php

function display_magento_sync_jobs()
{
    $pausedCronJobs = get_option('wp_crontrol_paused', []);


    $status = in_array('magento_sync_cron_job', $pausedCronJobs) ? 'Paused' : 'Active';

    $next_run = wp_next_scheduled('magento_sync_cron_job');
    if ($next_run) {
        $next_run = date('Y-m-d H:i:s', $next_run);
    } else {
        $next_run = 'Not scheduled';
    }

    echo '<tr>';
    echo '<td>Magento Sync</td>';
    echo '<td>' . $next_run . '</td>';
    echo '<td>' . $status . '</td>';
    echo '<td>';
    if ($status == 'Paused' || $status == 'Inactive') {
        echo '<button class="button start-sync" data-action="start">Start Sync</button>';
    } else {
        echo '<button class="button stop-sync" data-action="stop">Stop Sync</button>';
    }
    echo '</td>';
    echo '</tr>';
}

//do_action('magento_sync_cron_job');