<?php

/**
 * The core plugin class
 *
 * @since      1.0.0
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 */
class Yd_Checkout {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Yd_Checkout_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * HERE API integration instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Yd_Checkout_Here_API    $here_api    HERE API integration instance.
     */
    protected $here_api;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        if (defined('YD_CHECKOUT_VERSION')) {
            $this->version = YD_CHECKOUT_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'yd-checkout';

        $this->load_dependencies();
        $this->register_woocommerce_hooks(); 
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        
        // Initialize HERE API
        $this->initialize_here_api();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once YD_CHECKOUT_PATH . 'includes/class-yd-checkout-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once YD_CHECKOUT_PATH . 'includes/class-yd-checkout-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once YD_CHECKOUT_PATH . 'admin/class-yd-checkout-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once YD_CHECKOUT_PATH . 'public/class-yd-checkout-public.php';

        /**
         * The class responsible for address data management.
         */
        require_once YD_CHECKOUT_PATH . 'includes/models/class-yd-checkout-address.php';

        /**
         * HERE API integration.
         */
        require_once YD_CHECKOUT_PATH . 'includes/integrations/class-yd-checkout-here-api.php';

        /**
         * Helper functions for the plugin.
         */
        require_once YD_CHECKOUT_PATH . 'includes/class-yd-checkout-helpers.php';

        $this->loader = new Yd_Checkout_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale() {
        $plugin_i18n = new Yd_Checkout_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     */
    private function define_admin_hooks() {
        $plugin_admin = new Yd_Checkout_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_settings_page');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     */
    private function define_public_hooks() {
        $plugin_public = new Yd_Checkout_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Initialize HERE API integration
     */
    private function initialize_here_api() {
        $this->here_api = new Yd_Checkout_Here_API();
    }

    /**
     * Register WooCommerce integration hooks.
     *
     * @since    1.0.1
     * @access   private
     */
    private function register_woocommerce_hooks() {
        // Only register these hooks if WooCommerce is active
        if (!yd_checkout_is_woocommerce_active()) {
            return;
        }
        
        // Add filter to identify our page as a checkout page
        add_filter('woocommerce_is_checkout', array($this, 'is_yd_checkout_page'));
        
        // Add filter to ensure our checkout fields are recognized
        add_filter('woocommerce_checkout_posted_data', array($this, 'filter_checkout_posted_data'));
        
        // Add filter to handle country code normalization
        add_filter('woocommerce_checkout_update_order_review_data', array($this, 'normalize_country_codes'));
    }

    /**
     * Check if current page is our custom checkout page.
     *
     * @since    1.0.1
     * @param    bool    $is_checkout    The current status.
     * @return   bool                    True if it's our checkout page.
     */
    public function is_yd_checkout_page($is_checkout) {
        global $wp;
        
        // Check if this is an order-received endpoint
        if (!empty($wp->query_vars['order-received'])) {
            // This is a WooCommerce order confirmation page
            // Return the current value so WooCommerce handles it normally
            return $is_checkout;
        }
        
        // Get the ID of our checkout page from settings
        $checkout_page_id = get_option('yd_checkout_page_id');
        
        if ($checkout_page_id && is_page($checkout_page_id)) {
            return true;
        }
        
        // Also check for shortcode presence in the current page
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'yd_checkout')) {
            return true;
        }
        
        return $is_checkout;
    }

    /**
     * Filter checkout posted data to ensure compatibility with WooCommerce.
     *
     * @since    1.0.1
     * @param    array    $data    The posted checkout data.
     * @return   array             The modified checkout data.
     */
    public function filter_checkout_posted_data($data) {
        // Get data from YD Checkout session if available
        $yd_fields = WC()->session->get('checkout_fields');
        
        if (is_array($yd_fields) && !empty($yd_fields)) {
            $data = array_merge($data, $yd_fields);
        }
        
        return $data;
    }

    /**
     * Normalize country codes for payment gateways.
     *
     * @since    1.0.1
     * @param    array    $data    The posted checkout data.
     * @return   array             The modified checkout data with normalized country codes.
     */
    public function normalize_country_codes($data) {
        // Convert full country names to ISO codes if needed
        if (!empty($data['billing_country']) && strlen($data['billing_country']) > 2) {
            // Use WooCommerce's built-in country list to convert
            $country_code = $this->get_country_code($data['billing_country']);
            if ($country_code) {
                $data['billing_country'] = $country_code;
            }
        }
        
        if (!empty($data['shipping_country']) && strlen($data['shipping_country']) > 2) {
            $country_code = $this->get_country_code($data['shipping_country']);
            if ($country_code) {
                $data['shipping_country'] = $country_code;
            }
        }
        
        return $data;
    }

    /**
     * Helper method to get country code from country name.
     *
     * @since    1.0.1
     * @param    string    $country_name    The country name.
     * @return   string                     The country code or empty string if not found.
     */
    private function get_country_code($country_name) {
        if (!function_exists('WC')) {
            return '';
        }
        
        $countries = WC()->countries->get_countries();
        $country_name = trim($country_name);
        
        // Direct match
        $code = array_search($country_name, $countries);
        if ($code) {
            return $code;
        }
        
        // Loose match - case insensitive
        foreach ($countries as $code => $name) {
            if (strtolower($name) === strtolower($country_name)) {
                return $code;
            }
        }
        
        return '';
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Yd_Checkout_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Get the HERE API integration instance.
     *
     * @return    Yd_Checkout_Here_API    The HERE API integration instance.
     */
    public function get_here_api() {
        return $this->here_api;
    }

    /**
     * Handle webhook requests.
     */
    public function handle_webhook() {
        // Check if this is a webhook request
        if (!isset($_GET['yd-checkout-webhook']) || $_GET['yd-checkout-webhook'] !== '1') {
            return;
        }
        
        // Get the gateway
        $gateway_id = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : '';
        
        // WooCommerce gateway handling
        if (function_exists('WC') && !empty($gateway_id)) {
            $payment_gateways = WC()->payment_gateways->payment_gateways();
            if (isset($payment_gateways[$gateway_id])) {
                $gateway = $payment_gateways[$gateway_id];
                
                // Check if the gateway has a webhook handler method
                if (method_exists($gateway, 'handle_webhook')) {
                    $gateway->handle_webhook();
                    exit;
                }
            }
        }
        
        // Default response if no handler found
        status_header(400);
        echo 'No valid webhook handler found';
        exit;
    }
}