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
        // Convertiamo gli array in stringhe con virgole
        $product_ids = implode(',', $products);
        $quantities_str = '';

        // Creiamo la stringa delle quantità nell'ordine corretto
        foreach ($products as $product_id) {
            $quantities_str .= ($quantities_str ? ',' : '') . ($quantities[$product_id] ?? 1);
        }

        // Genera l'URL base senza nonce
        $display_url = home_url('/?') . "add_package={$product_ids}&quantities={$quantities_str}";

        // Genera l'URL completo con nonce (per uso interno)
        $actual_url = wp_nonce_url($display_url, 'add_package_to_cart');

        // Salva l'URL completo come meta del post
        update_post_meta($post_id, '_package_complete_url', $actual_url);

        // Restituisci solo l'URL di visualizzazione
        return $display_url;
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
        $products = get_post_meta($post_id, '_add_bulk_cart_packages_products', true);
        $quantities = get_post_meta($post_id, '_add_bulk_cart_packages_quantities', true);

        if (!empty($products) && is_array($products)) {
            // Creazione della stringa delle quantità
            $quantities_str = isset($quantities) && is_array($quantities) ? implode(',', $quantities) : '';

            // Genera il nonce
            $nonce = wp_create_nonce('add_package_to_cart');

            // Costruzione della URL senza codifica delle virgole
            $url = home_url("/?add_package=" . implode(',', $products) . "&quantities=" . $quantities_str . "&_wpnonce=" . $nonce);

            echo '<input type="text" value="' . esc_url($url) . '" readonly style="width: 100%; font-size: 14px;">';
        } else {
            echo esc_html__('No products selected', 'add-bulk-cart-packages');
        }
    }
}
add_action('manage_package_posts_custom_column', 'add_bulk_cart_packages_custom_column_content', 10, 2);