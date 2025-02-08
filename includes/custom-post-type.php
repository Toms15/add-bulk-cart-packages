<?php
// Registra il Custom Post Type "Pacchetti"
function add_bulk_cart_packages_register_cpt() {
    $labels = array(
        'name'          => 'Packages',
        'singular_name' => 'Package',
        'menu_name'     => 'Packages',
        'all_items'     => 'Packages',
        'add_new'       => 'New package',
        'add_new_item'  => 'New package',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'supports'           => array('title'),
        'show_in_menu'       => 'woocommerce',
        'menu_icon'          => 'dashicons-cart',
    );

    register_post_type('package', $args);
}

add_action('init', 'add_bulk_cart_packages_register_cpt');

function add_bulk_cart_packages_generate_url($post_id) {
    $products = get_post_meta($post_id, '_add_bulk_cart_packages_products', true);
    $quantities = get_post_meta($post_id, '_add_bulk_cart_packages_quantities', true);

    if (!empty($products) && is_array($products)) {
        $product_ids = implode(',', $products);
        $quantities_str = isset($quantities) && is_array($quantities) ? implode(',', $quantities) : '';

        return home_url("?add_package={$product_ids}&quantities={$quantities_str}");
    }

    return '';
}

function add_bulk_cart_packages_add_custom_column($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['bulk_url'] = __('Package URL', 'add-bulk-cart-packages');
        }
    }
    return $new_columns;
}
add_filter('manage_package_posts_columns', 'add_bulk_cart_packages_add_custom_column');

function add_bulk_cart_packages_custom_column_content($column, $post_id) {
    if ($column === 'bulk_url') {
        $url = add_bulk_cart_packages_generate_url($post_id);

        if (!empty($url)) {
            echo '<input type="text" value="' . esc_url($url) . '" readonly style="width: 100%; font-size: 14px;" onclick="this.select();">';
        } else {
            echo 'Nessun prodotto selezionato';
        }
    }
}
add_action('manage_package_posts_custom_column', 'add_bulk_cart_packages_custom_column_content', 10, 2);