<?php

// Fetch the stores using the utility class
$magento_api = new MagentoAPI();

$stores = $magento_api->request('/rest/V1/store/websites');

if ($stores && !empty($stores)) {
    echo '<h3>Select Store to Sync</h3>';
    echo '<form method="POST" action="options.php">';
    settings_fields('magento_sync_store_settings');

    foreach ($stores as $store) {
        $store_code = $store->id;
        $store_name = $store->name;
        $checked = (get_option('magento_sync_store') == $store_code) ? 'checked' : '';
        echo '<label>';
        echo '<input type="radio" name="magento_sync_store" value="' . esc_attr($store_code) . '" ' . $checked . ' />';
        echo esc_html($store_name);
        echo '</label><br>';
    }

    submit_button();
    echo '</form>';
} else {
    echo '<p>No stores found. Please check your Magento API credentials.</p>';
}