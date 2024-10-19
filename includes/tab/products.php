<?php

$magento_api = new MagentoAPI();

// Get all the attributes from the website
$searchCriteria = [
    'pageSize' => 1000,
];
$attributes = $magento_api->request('/rest/V1/products/attributes?'.build_search_params($searchCriteria));
//Give user change to multiselect the attributes and save that to one database

if ($attributes && !empty($attributes)) {
    echo '<h3>Select Attributes to Sync</h3>';
    echo '<form method="POST" action="options.php">';
    settings_fields('magento_sync_attribute_settings');

    $saved_attributes = get_option('magento_sync_attributes', []);

    foreach ($attributes->items as $attribute) {
        $attribute_code = $attribute->attribute_code;
        if (!isset($attribute->default_frontend_label)) {
            continue;
        }
        $attribute_label = $attribute->default_frontend_label;
        $checked = in_array($attribute_code, $saved_attributes) ? 'checked' : '';
        echo '<label>';
        echo '<input type="checkbox" name="magento_sync_attributes[]" value="' . esc_attr($attribute_code) . '" ' . $checked . ' />';
        echo '<strong>' . esc_html($attribute_label) . '</strong> ( ' . esc_html($attribute_code) . ' )';
        echo '</label><br>';
    }

    submit_button();
    echo '</form>';
} else {
    echo '<p>No attributes found. Please check your Magento API credentials.</p>';
}