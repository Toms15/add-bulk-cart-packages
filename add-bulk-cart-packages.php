<?php
/**
 * Plugin Name: Add bulk cart packages
 * Description: Create packages of products and generate URLs to add them to the shopping cart with specific quantities
 * Version: 1.1.0
 * Author: Tommaso Costantini
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: add-bulk-cart-packages
 */

namespace Toms15\ABCP;

if (!defined('ABSPATH')) {
    exit; // Blocca l'accesso diretto
}

// Includi i file principali
require_once plugin_dir_path(__FILE__) . 'includes/custom-post-type.php';
require_once plugin_dir_path(__FILE__) . 'includes/metaboxes.php';
require_once plugin_dir_path(__FILE__) . 'includes/process-cart.php';

// Attiva il CPT quando il plugin è attivato
function plugin_activate() {
    register_cpt();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\\plugin_activate');

// Disattiva il plugin e ripristina i permalink
function plugin_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\\plugin_deactivate');

function check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(esc_html__('WooCommerce must be active to use this plugin.', 'add-bulk-cart-packages'));
    }
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\\check_woocommerce');

function admin_notice() {
    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-error"><p>' .
             wp_kses_post(__('<strong>Warning:</strong> The "Add Bulk Cart Packages" plugin requires WooCommerce to function.', 'add-bulk-cart-packages')) .
             '</p></div>';
    }
}
add_action('admin_notices', __NAMESPACE__ . '\\admin_notice');

function check_woocommerce_admin() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__)); // Disattiva il plugin
    }
}
add_action('admin_init', __NAMESPACE__ . '\\check_woocommerce_admin');

function enqueue_admin_scripts($hook) {
    // Assicurati di caricare lo script solo nelle pagine pertinenti
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    wp_enqueue_script(
        'add-bulk-cart-packages-script',
        plugin_dir_url(__FILE__) . 'assets/add-bulk-cart-packages.js', // Usa plugin_dir_url se è un plugin
        array('jquery'),
        '1.0',
        true
    );
}
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_scripts');