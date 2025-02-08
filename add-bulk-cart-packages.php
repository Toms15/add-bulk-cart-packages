<?php
/**
 * Plugin Name: Add bulk cart packages
 * Description: Crea pacchetti di prodotti e genera URL per aggiungerli al carrello con quantità specifiche.
 * Version: 1.0.0
 * Author: Tommaso Costantini
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: add-bulk-cart-packages
 */

if (!defined('ABSPATH')) {
    exit; // Blocca l'accesso diretto
}

// Definisci il percorso del plugin
define('ADD_BULK_CART_PACKAGES_DIR', plugin_dir_path(__FILE__));

// Includi i file principali
require_once ADD_BULK_CART_PACKAGES_DIR . 'includes/custom-post-type.php';
require_once ADD_BULK_CART_PACKAGES_DIR . 'includes/metaboxes.php';
require_once ADD_BULK_CART_PACKAGES_DIR . 'includes/process-cart.php';

// Attiva il CPT quando il plugin è attivato
function add_bulk_cart_packges_activate() {
    add_bulk_cart_packges_register_cpt();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'add_bulk_cart_packges_activate');

// Disattiva il plugin e ripristina i permalink
function add_bulk_cart_packges_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'add_bulk_cart_packges_deactivate');

function add_bulk_cart_packges_activation_check() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(esc_html__('WooCommerce must be active to use this plugin.', 'add-bulk-cart-packages'));
    }
}
register_activation_hook(__FILE__, 'add_bulk_cart_packges_activation_check');

function add_bulk_cart_packges_admin_notice() {
    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-error"><p>' .
             wp_kses_post(__('<strong>Warning:</strong> The "Add Bulk Cart Packages" plugin requires WooCommerce to function.', 'add-bulk-cart-packages')) .
             '</p></div>';
    }
}
add_action('admin_notices', 'add_bulk_cart_packges_admin_notice');

function add_bulk_cart_packges_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__)); // Disattiva il plugin
    }
}
add_action('admin_init', 'add_bulk_cart_packges_check_woocommerce');