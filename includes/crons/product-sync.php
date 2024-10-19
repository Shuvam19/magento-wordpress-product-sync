<?php

add_filter('cron_schedules', 'magento_sync_products_schedule');

// add logging function which will add some string inside one file
//create a function for this
function log_this($message)
{
    $file = plugin_dir_path(__DIR__) . '/log.txt';
    $current = file_get_contents($file);
    $current .= $message . "\n";
    file_put_contents($file, $current);
}

function magento_sync_products_schedule($schedules)
{
    // Add a 1-minute interval for testing (you can adjust as needed)
    $schedules['every_minute'] = array(
        'interval' => 60,  // Interval in seconds
        'display' => esc_html__('Every Minute'),
    );
    return $schedules;
}

// Hook the cron job to our sync function
add_action('magento_sync_cron_job', 'magento_sync_single_product');

function magento_sync_single_product(): void
{
    // Get all Magento products
    $magento_api = new MagentoAPI();

    $website_id = get_option('magento_sync_store');
    $products = getRequest($magento_api, $website_id);


    if (!$products || !isset($products->items)) {
        return; // No products to sync
    }

    foreach ($products->items as $product) {
        $product_id = $product->id;

        magento_sync_create_woocommerce_product($product);

        update_option('last_synced_magento_id', $product_id);
    }
}

/**
 * @param MagentoAPI $magento_api
 * @param mixed $website_id
 * @return false|mixed
 */
function getRequest(MagentoAPI $magento_api, mixed $website_id): mixed
{
    $lastProduct = get_option('last_synced_magento_id');
    $params = [
        'filterGroups' => [
            [
                'filters' => [
                    ['field' => 'website_id', 'value' => $website_id, 'condition_type' => 'eq'],
                ]
            ]
        ],
        'sortOrders' => [
            ['field' => 'entity_id', 'direction' => 'ASC']
        ],
        'pageSize' => 1,
        'currentPage' => 1
    ];
    // I want to ads this param if lastProduct is set
    if (isset($lastProduct)) {
        $params['filterGroups'][1]['filters'][] = ['field' => 'entity_id', 'value' => $lastProduct, 'condition_type' => 'gt'];
    }
//    $products = $magento_api->request('/rest/V1/products?searchCriteria[filter_groups][0][filters][0][field]=website_id&searchCriteria[filter_groups][0][filters][0][value]=' . $website_id);
    return $magento_api->request('/rest/V1/products?' . build_search_params($params));
}

function magento_sync_create_woocommerce_product($magento_product): void
{
//    $attributesToSync = ['is_recurring', 'gift_message_available', 'naya_call_for_price_enable', 'msrp', 'msrp_display_actual_price_type', 'tax_class_id', 'comcosapnotes', 'news_to_date', 'operatingsystem', 'android_version', 'screen_size_tablets', 'news_from_date', ''];
    $attributesToSync = get_option('magento_sync_attributes', []);

    $product = new WC_Product();
    $convertedProductAttributes = getcustomAttributes($magento_product);


    // Set the product details
    $product->set_name($magento_product->name);
    $product->set_regular_price($magento_product->price);
    $product->set_description($convertedProductAttributes['description']);
    $product->set_sku($magento_product->sku);
    $product->set_weight($magento_product->weight);
    $product->set_short_description($convertedProductAttributes['short_description']);


    //add new attribute is_reccuring from $convertedProductAttributes object into the product
    $attributes = [];
    foreach ($convertedProductAttributes as $key => $value) {
        if (!in_array($key, $attributesToSync)) continue;
        if (gettype($value) != 'string') continue;
        // create a WC_Product_Attribute object
        $attribute = new WC_Product_Attribute();
        $attribute->set_id($key);
        $attribute->set_name($key);
        $attribute->set_options([$value]);
        $attribute->set_visible(true);
        $attribute->set_variation(false);
        $attributes[] = $attribute;
    }
    $product->set_attributes($attributes);

    // Need to sync the categories also.
    if (isset($convertedProductAttributes['category_ids']) && count($convertedProductAttributes['category_ids']) > 0) {
        $categories = [];
        foreach ($convertedProductAttributes['category_ids'] as $category_id) {
            // Get category name from Magento
            $termid = create_category($category_id);
            $categories[] = $termid;
        }
        $product->set_category_ids($categories);
    }

    $images = sync_images_to_wordpress($magento_product->media_gallery_entries);
    if (isset($images['image_ids'])) $product->set_gallery_image_ids($images['image_ids']);
    if (isset($images['feature_image'])) $product->set_image_id($images['feature_image']);

    $product_id = $product->save();

    update_post_meta($product_id, '_yoast_wpseo_title', $convertedProductAttributes['meta_title']);
    update_post_meta($product_id, '_yoast_wpseo_metadesc', $convertedProductAttributes['meta_']);
//    update_post_meta($product_id, '_yoast_wpseo_metakeywords', $meta_title);

    // Set product meta to keep track of sync status
    update_post_meta($product_id, '_magento_product_id', $magento_product->id);
}

function create_category($category_id)
{
    $magento_api = new MagentoAPI();
    $category = $magento_api->request('/rest/V1/categories/' . $category_id);
    $categoryAttributes = getcustomAttributes($category);

    $parentId = 0;
    if ($category->parent_id > 1) {
        $parentId = create_category($category->parent_id);
    }
    $term = term_exists($category->name, 'product_cat');
    if ($term) {
        return $term['term_id'];
    }
    $termData = wp_insert_term($category->name, 'product_cat', [
//        'description' => $categoryAttributes['description'],
        'slug' => $categoryAttributes['url_key'],
        'parent' => $parentId
    ]);
    return $termData['term_id'];
}

function build_search_params(array $params): string
{
    $query = [];
    $filterGroupIndex = 0;
    $filterIndex = 0;
    $sortOrderIndex = 0;

    if (isset($params['filterGroups'])) {
        foreach ($params['filterGroups'] as $filterGroup) {
            foreach ($filterGroup['filters'] as $filter) {
                $query[] = "searchCriteria[filterGroups][$filterGroupIndex][filters][$filterIndex][field]=" . $filter['field'];
                $query[] = "searchCriteria[filterGroups][$filterGroupIndex][filters][$filterIndex][value]=" . $filter['value'];
                if (isset($filter['condition_type'])) {
                    $query[] = "searchCriteria[filterGroups][$filterGroupIndex][filters][$filterIndex][condition_type]=" . $filter['condition_type'];
                }
                $filterIndex++;
            }
            $filterGroupIndex++;
        }
    }

    if (isset($params['sortOrders'])) {
        foreach ($params['sortOrders'] as $sortOrder) {
            $query[] = "searchCriteria[sortOrders][$sortOrderIndex][field]=" . $sortOrder['field'];
            $query[] = "searchCriteria[sortOrders][$sortOrderIndex][direction]=" . $sortOrder['direction'];
            $sortOrderIndex++;
        }
    }

    if (isset($params['pageSize'])) {
        $query[] = "searchCriteria[pageSize]=" . $params['pageSize'];
    }

    if (isset($params['currentPage'])) {
        $query[] = "searchCriteria[currentPage]=" . $params['currentPage'];
    }

    return implode('&', $query);
}

function getcustomAttributes($magento_product)
{
    // custom_attributes":[{"attribute_code":"is_recurring","value":"0"},
    // convert custom_attributes to array by attribute_code and value
    $custom_attributes = [];
    foreach ($magento_product->custom_attributes as $attribute) {
        $custom_attributes[$attribute->attribute_code] = $attribute->value;
    }

    return $custom_attributes;
}

function sync_images_to_wordpress($media_gallery_entries)
{
    $image_ids = [];
    $upload_dir = wp_upload_dir();
    $featureImage = 0;

    foreach ($media_gallery_entries as $media) {
        $magentoUrl = get_option('magento_sync_api_url');
        $image_url = $magentoUrl . '/media/catalog/product' . $media->file;
        $image_data = file_get_contents($image_url);

        if (isset($image_data)) {
            $filename = basename($media->file);
            $file_path = $upload_dir['path'] . '/' . $filename;
            file_put_contents($file_path, $image_data);
            $filetype = wp_check_filetype($filename, null);
            $attachment_data = [
                'post_mime_type' => $filetype['type'],
                'post_title' => sanitize_file_name($filename),
                'post_content' => '',
                'post_status' => 'inherit',
            ];

            $attachment_id = wp_insert_attachment($attachment_data, $file_path);

//            $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $file_path);

//            wp_update_attachment_metadata($attachment_id, $attachment_metadata);

            $image_ids[] = $attachment_id;


            if (in_array('image', $media->types)) {
                $featureImage = $attachment_id;
            }
        }
    }
    if (count($image_ids) > 0 && $featureImage == 0) {
        $featureImage = $image_ids[0];
    }
    return ['image_ids' => $image_ids, 'feature_image' => $featureImage];
}