<?php
namespace Toms15\ABCP;

function add_metabox() {
    add_meta_box(
        'package_metabox',
        __('Select products and coupon', 'add-bulk-cart-packages'),
        __NAMESPACE__ . '\\render_metabox',
        'abcp_package',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', __NAMESPACE__ . '\\add_metabox');

function get_available_coupons() {
    $args = array(
        'posts_per_page'   => -1,
        'orderby'          => 'title',
        'order'            => 'asc',
        'post_type'        => 'shop_coupon',
        'post_status'      => 'publish',
    );

    return get_posts($args);
}

function render_metabox($post) {
    wp_nonce_field('add_bulk_cart_packages_nonce_action', 'add_bulk_cart_packages_nonce');

    $selected_products = get_post_meta($post->ID, '_add_bulk_cart_packages_products', true);
    $selected_quantities = get_post_meta($post->ID, '_add_bulk_cart_packages_quantities', true);
    $selected_coupon = get_post_meta($post->ID, '_add_bulk_cart_packages_coupon', true);

    // Aggiungi il campo select per i coupon
    $coupons = get_available_coupons();
    echo '<div style="margin-bottom: 20px;">';
    echo '<label for="add_bulk_cart_packages_coupon" style="display: block; margin-bottom: 5px;">' . esc_html__('Select Coupon:', 'add-bulk-cart-packages') . '</label>';
    echo '<select id="add_bulk_cart_packages_coupon" name="add_bulk_cart_packages_coupon" style="width: 300px;">';
    echo '<option value="">' . esc_html__('No coupon', 'add-bulk-cart-packages') . '</option>';

    foreach ($coupons as $coupon) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr($coupon->post_title),
            selected($coupon->post_title, $selected_coupon, false),
            esc_html($coupon->post_title)
        );
    }
    echo '</select>';
    echo '</div>';

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1
    );
    $products = get_posts($args);

    echo '<div id="woo-bulk-repeater">';

    if (!empty($selected_products) && is_array($selected_products)) {
        foreach ($selected_products as $product_id) {
            $quantity = isset($selected_quantities[$product_id]) ? intval($selected_quantities[$product_id]) : 1;
            call_user_func(__NAMESPACE__ . '\\render_repeater_row', $products, $product_id, $quantity);
        }
    } else {
        call_user_func(__NAMESPACE__ . '\\render_repeater_row', $products);
    }

    echo '</div>';
    echo '<button type="button" id="woo-bulk-add-row" class="button">' . esc_html__('Add new product', 'add-bulk-cart-packages') . '</button>';
}

function render_repeater_row($products, $selected_product = '', $quantity = 1) {
    echo '<div class="woo-bulk-row" style="margin-bottom: 10px; display: flex; align-items: center;">';

    echo '<select name="add_bulk_cart_packages_products[]" style="margin-right: 10px;">';
    echo '<option value="">' . esc_html__('Select a product', 'add-bulk-cart-packages') . '</option>';

    foreach ($products as $product) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr($product->ID),
            selected($product->ID, $selected_product, false),
            esc_html($product->post_title)
        );
    }
    echo '</select>';

    printf(
        '<input type="number" name="add_bulk_cart_packages_quantities[]" value="%d" min="1" style="width: 60px; margin-right: 10px;">',
        esc_attr($quantity)
    );

    echo '<button type="button" class="button woo-bulk-remove-row">' . esc_html__('Remove', 'add-bulk-cart-packages') . '</button>';
    echo '</div>';
}

function save_metabox($post_id) {
    if (!isset($_POST['add_bulk_cart_packages_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['add_bulk_cart_packages_nonce'])),
            'add_bulk_cart_packages_nonce_action'
        )) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['add_bulk_cart_packages_products']) && is_array($_POST['add_bulk_cart_packages_products'])) {
        $products = array_map('absint', wp_unslash($_POST['add_bulk_cart_packages_products']));
        $quantities = isset($_POST['add_bulk_cart_packages_quantities'])
            ? array_map('absint', wp_unslash($_POST['add_bulk_cart_packages_quantities']))
            : [];

        $quantities_assoc = [];
        foreach ($products as $index => $product_id) {
            $quantities_assoc[$product_id] = isset($quantities[$index]) ? $quantities[$index] : 1;
        }

        update_post_meta($post_id, '_add_bulk_cart_packages_products', $products);
        update_post_meta($post_id, '_add_bulk_cart_packages_quantities', $quantities_assoc);

        if (isset($_POST['add_bulk_cart_packages_coupon'])) {
            $coupon = sanitize_text_field(wp_unslash($_POST['add_bulk_cart_packages_coupon']));
            update_post_meta($post_id, '_add_bulk_cart_packages_coupon', $coupon);
        } else {
            delete_post_meta($post_id, '_add_bulk_cart_packages_coupon');
        }
    } else {
        delete_post_meta($post_id, '_add_bulk_cart_packages_products');
        delete_post_meta($post_id, '_add_bulk_cart_packages_quantities');
        delete_post_meta($post_id, '_add_bulk_cart_packages_coupon');
    }
}
add_action('save_post', __NAMESPACE__ . '\\save_metabox');

function add_url_metabox() {
    add_meta_box(
        'package_url_metabox',
        __('Package URL', 'add-bulk-cart-packages'),
        __NAMESPACE__ . '\\render_url_metabox',
        'abcp_package',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', __NAMESPACE__ . '\\add_url_metabox');

function render_url_metabox($post) {
    $products = get_post_meta($post->ID, '_add_bulk_cart_packages_products', true);
    $quantities = get_post_meta($post->ID, '_add_bulk_cart_packages_quantities', true);
    $selected_coupon = get_post_meta($post->ID, '_add_bulk_cart_packages_coupon', true);

    if (!empty($products) && is_array($products)) {
        $product_ids = implode(',', $products);
        $quantities_str = isset($quantities) && is_array($quantities) ? implode(',', $quantities) : '';

        // Genera il nonce
        $nonce = wp_create_nonce('add_package_to_cart');

        // Costruisce la URL con il coupon prima del nonce
        $url = home_url("/?add_package={$product_ids}&quantities={$quantities_str}");
        if (!empty($selected_coupon)) {
            $url .= "&coupon=" . urlencode($selected_coupon);
        }
        $url .= "&_wpnonce={$nonce}";

        echo '<input type="text" value="' . esc_url($url) . '" readonly style="width: 100%; font-size: 14px;">';
        echo '<p>' . esc_html__('Copy this URL and use it to add products to your cart.', 'add-bulk-cart-packages') . '</p>';
    } else {
        echo '<p>' . esc_html__('Select at least one product to generate the URL.', 'add-bulk-cart-packages') . '</p>';
    }
}