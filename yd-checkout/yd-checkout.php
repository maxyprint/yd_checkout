<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://octonove.com
 * @since             1.0.0
 * @package           Yd_Checkout
 *
 * @wordpress-plugin
 * Plugin Name:       YDesign Checkout
 * Plugin URI:        https://octonove.com
 * Description:       Custom checkout system for YDesign
 * Version:           1.0.2
 * Author:            Octonove
 * Author URI:        https://octonove.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       yd-checkout
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'YD_CHECKOUT_VERSION', '1.0.2' );

/**
 * Define plugin path and URL constants for easier access
 */
define( 'YD_CHECKOUT_PATH', plugin_dir_path( __FILE__ ) );
define( 'YD_CHECKOUT_URL', plugin_dir_url( __FILE__ ) );

define('WP_ERROR_LOG_FILE', YD_CHECKOUT_PATH . 'error-log.log');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', WP_ERROR_LOG_FILE);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-yd-checkout-activator.php
 */
function activate_yd_checkout() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-yd-checkout-activator.php';
	Yd_Checkout_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-yd-checkout-deactivator.php
 */
function deactivate_yd_checkout() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-yd-checkout-deactivator.php';
	Yd_Checkout_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_yd_checkout' );
register_deactivation_hook( __FILE__, 'deactivate_yd_checkout' );

/**
 * Check if WooCommerce is active.
 * 
 * @return bool True if WooCommerce is active, false otherwise.
 */
function yd_checkout_is_woocommerce_active() {
    $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
    return in_array('woocommerce/woocommerce.php', $active_plugins);
}

/**
 * Define WooCommerce integration constants.
 */
if (yd_checkout_is_woocommerce_active()) {
    define('YD_CHECKOUT_WC_INTEGRATION', true);
    
    // Include WooCommerce compatibility function if needed
    if (!function_exists('wc_get_logger')) {
        function yd_checkout_wc_compatibility() {
            // Load WooCommerce compatibility layer
            require_once plugin_dir_path(__FILE__) . 'includes/woocommerce/yd-checkout-wc-compatibility.php';
        }
        add_action('plugins_loaded', 'yd_checkout_wc_compatibility', 5);
    }
} else {
    define('YD_CHECKOUT_WC_INTEGRATION', false);
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-yd-checkout.php';

/**
 * Global plugin instance.
 * 
 * @since 1.0.0
 * @var Yd_Checkout
 */
global $yd_checkout;

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_yd_checkout() {
    global $yd_checkout;
    
    // Initialize the plugin
    $yd_checkout = new Yd_Checkout();
    $yd_checkout->run();
    
    // Handle webhook requests if applicable
    if (isset($_GET['yd-checkout-webhook']) && $_GET['yd-checkout-webhook'] === '1') {
        $yd_checkout->handle_webhook();
    }
}
run_yd_checkout();

/**
 * Helper function to get the plugin instance.
 * 
 * @since 1.0.0
 * @return Yd_Checkout The plugin instance.
 */
function yd_checkout() {
    global $yd_checkout;
    return $yd_checkout;
}

/**
 * Include test payment functionality.
 */
require_once plugin_dir_path(__FILE__) . 'includes/test.php';