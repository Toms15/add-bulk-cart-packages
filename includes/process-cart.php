<?php
namespace Toms15\ABCP;

function process_bulk_add_to_cart() {
    if (isset($_GET['add_package']) && isset($_GET['_wpnonce'])) {
        // Verifica il nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'add_package_to_cart')) {
            wc_add_notice(__('Security check failed.', 'add-bulk-cart-packages'), 'error');
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }

        if (!WC()->session) WC()->session = new WC_Session_Handler();
        if (!WC()->cart) WC()->cart = new WC_Cart();

        // Unslash e sanitizza add_package
        $products = array_map(
            'absint',
            explode(',', sanitize_text_field(wp_unslash($_GET['add_package'])))
        );

        // Unslash e sanitizza quantities se presente
        $quantities = isset($_GET['quantities'])
            ? array_map(
                'absint',
                explode(',', sanitize_text_field(wp_unslash($_GET['quantities'])))
            )
            : array_fill(0, count($products), 1);

        foreach ($products as $index => $product_id) {
            $quantity = $quantities[$index] ?? 1;
            $product = wc_get_product($product_id);
            if ($product && $product->is_purchasable() && $product->is_in_stock()) {
                WC()->cart->add_to_cart($product_id, $quantity);
            }
        }

        WC()->cart->calculate_totals();
        wp_safe_redirect(wc_get_cart_url());
        exit;
    }
}
add_action('init', __NAMESPACE__ . '\\process_bulk_add_to_cart');