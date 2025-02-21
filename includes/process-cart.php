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

        // Pulisci il carrello prima di aggiungere i nuovi prodotti
        WC()->cart->empty_cart();

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

        // Aggiungi i prodotti al carrello
        $all_products_added = true;
        foreach ($products as $index => $product_id) {
            $quantity = $quantities[$index] ?? 1;
            $product = wc_get_product($product_id);
            if ($product && $product->is_purchasable() && $product->is_in_stock()) {
                $added = WC()->cart->add_to_cart($product_id, $quantity);
                if (!$added) {
                    $all_products_added = false;
                    // Translators: %s is the product name.
                    $error_message = sprintf(
                        /* translators: %s is the product name. */
                        __('Error adding %s to cart.', 'add-bulk-cart-packages'),
                        $product->get_name()
                    );
                    wc_add_notice($error_message, 'error');
                }
            } else {
                $all_products_added = false;
                if ($product) {
                    // Translators: %s is the product name.
                    $error_message = sprintf(
                        /* translators: %s is the product name. */
                        __('%s is not available for purchase.', 'add-bulk-cart-packages'),
                        $product->get_name()
                    );
                    wc_add_notice($error_message, 'error');
                }
            }
        }

        // Gestisci il coupon se presente
        if (isset($_GET['coupon']) && !empty($_GET['coupon'])) {
            $coupon_code = sanitize_text_field(wp_unslash($_GET['coupon']));

            // Verifica se il coupon esiste
            $coupon = new \WC_Coupon($coupon_code);

            if ($coupon->get_id()) {
                // Verifica se il coupon è valido
                if ($coupon->is_valid()) {
                    $applied = WC()->cart->apply_coupon($coupon_code);
                    if ($applied) {
                        // Translators: %s is the coupon code.
                        $success_message = sprintf(
                            /* translators: %s is the coupon code. */
                            __('Coupon "%s" applied successfully.', 'add-bulk-cart-packages'),
                            $coupon_code
                        );
                        wc_add_notice($success_message, 'success');
                    } else {
                        // Translators: %s is the coupon code.
                        $error_message = sprintf(
                            /* translators: %s is the coupon code. */
                            __('Error applying coupon "%s".', 'add-bulk-cart-packages'),
                            $coupon_code
                        );
                        wc_add_notice($error_message, 'error');
                    }
                } else {
                    // Translators: %s is the coupon code.
                    $error_message = sprintf(
                        /* translators: %s is the coupon code. */
                        __('Coupon "%s" is not valid.', 'add-bulk-cart-packages'),
                        $coupon_code
                    );
                    wc_add_notice($error_message, 'error');
                }
            } else {
                // Translators: %s is the coupon code.
                $error_message = sprintf(
                /* translators: %s is the coupon code. */
                    __('Coupon "%s" does not exist.', 'add-bulk-cart-packages'),
                    $coupon_code
                );
                wc_add_notice($error_message, 'error');

            }
        }

        // Ricalcola i totali
        WC()->cart->calculate_totals();

        if ($all_products_added) {
            wc_add_notice(__('Products added to cart successfully.', 'add-bulk-cart-packages'), 'success');
        }

        wp_safe_redirect(wc_get_cart_url());
        exit;
    }
}
add_action('init', __NAMESPACE__ . '\\process_bulk_add_to_cart');