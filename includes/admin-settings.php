<?php
// Add admin menu
add_action('admin_menu', 'magento_sync_menu');
function magento_sync_menu() {
    add_menu_page(
        'Magento Sync Settings',
        'Magento Sync',
        'manage_options',
        'magento-sync-settings',
        'magento_sync_settings_page',
        'dashicons-update',
        20
    );
}

function magento_sync_settings_page() {
    $tab = $_GET['tab'] ?? 'general';

    $tabs = [
        'general' => 'General',
        'stores' => 'Stores',
        'products' => 'Products',
        'sync' => 'Sync',
    ];

    echo '<div class="wrap">';
    echo '<h1>Magento Sync Settings</h1>';

    echo '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $key => $label) {
        $active_class = ($tab === $key) ? 'nav-tab-active' : '';
        echo '<a href="?page=magento-sync-settings&tab=' . $key . '" class="nav-tab ' . $active_class . '">' . $label . '</a>';
    }
    echo '</h2>';

    switch ($tab) {
        case 'products':
            include 'tab/products.php';
            break;
        case 'stores':
            include 'tab/stores.php';
            break;
        case 'sync':
            include 'tab/sync.php';
            break;
        default:
            include 'tab/general.php';
            break;
    }
}


add_action('admin_init', 'magento_sync_register_settings');
add_action('admin_init', 'magento_sync_store_settings');
add_action('admin_init', 'magento_sync_products_settings');

function magento_sync_register_settings(): void
{
    // General Settings
    register_setting('magento_sync_general_settings', 'magento_sync_api_url');
    register_setting('magento_sync_general_settings', 'magento_sync_api_username');
    register_setting('magento_sync_general_settings', 'magento_sync_api_password');

    add_settings_section('magento_sync_general_section', 'Magento API Credentials', null, 'magento_sync_general_settings');

    // API URL Field
    add_settings_field(
        'magento_sync_api_url_field',
        'API URL',
        'magento_sync_api_url_field_render',
        'magento_sync_general_settings',
        'magento_sync_general_section'
    );

    // API Username Field
    add_settings_field(
        'magento_sync_api_username_field',
        'API Username',
        'magento_sync_api_username_field_render',
        'magento_sync_general_settings',
        'magento_sync_general_section'
    );

    // API Password Field
    add_settings_field(
        'magento_sync_api_password_field',
        'API Password',
        'magento_sync_api_password_field_render',
        'magento_sync_general_settings',
        'magento_sync_general_section'
    );
}

function magento_sync_store_settings(): void
{
    // Store Settings
    register_setting('magento_sync_store_settings', 'magento_sync_store');

    add_settings_section('magento_sync_store_section', 'Select Store', null, 'magento_sync_store_settings');
}


function magento_sync_products_settings(): void
{
    register_setting('magento_sync_attribute_settings', 'magento_sync_attributes', [
        'type' => 'array',
        'sanitize_callback' => 'magento_sanitize_attributes',
        'default' => [],
    ]);

    // Add a settings section if needed
    add_settings_section(
        'magento_sync_section',
        'Magento Sync Settings',
        null,
        'magento_sync_attributes_page'
    );
}

function magento_sanitize_attributes($input): array
{
    return is_array($input) ? array_map('sanitize_text_field', $input) : [];
}

// Render API URL Field
function magento_sync_api_url_field_render(): void
{
    $value = get_option('magento_sync_api_url', '');
    echo '<input type="text" name="magento_sync_api_url" value="' . esc_attr($value) . '" class="regular-text">';
}

// Render API Username Field
function magento_sync_api_username_field_render(): void
{
    $value = get_option('magento_sync_api_username', '');
    echo '<input type="text" name="magento_sync_api_username" value="' . esc_attr($value) . '" class="regular-text">';
}

// Render API Password Field
function magento_sync_api_password_field_render(): void
{
    $value = get_option('magento_sync_api_password', '');
    echo '<input type="password" name="magento_sync_api_password" value="' . esc_attr($value) . '" class="regular-text">';
}