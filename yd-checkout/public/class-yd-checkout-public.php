<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://octonove.com
 * @since      1.0.0
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for enqueuing 
 * public-facing styles and JavaScript.
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/public
 */
class Yd_Checkout_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Register shortcode
        add_shortcode('yd_checkout', array($this, 'render_checkout'));

        // Register AJAX handlers
        $this->register_ajax_handlers();
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        // Order processing
        add_action('wp_ajax_yd_checkout_process_order', array($this, 'ajax_process_order'));
        add_action('wp_ajax_nopriv_yd_checkout_process_order', array($this, 'ajax_process_order'));
        
        add_action('wp_ajax_yd_checkout_get_addresses_by_type', array($this, 'ajax_get_addresses_by_type'));
        add_action('wp_ajax_nopriv_yd_checkout_get_addresses_by_type', array($this, 'ajax_get_addresses_by_type'));
        add_action('wp_ajax_yd_checkout_save_address', array($this, 'ajax_save_address'));
        add_action('wp_ajax_yd_checkout_update_address', array($this, 'ajax_update_address'));
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Main styles
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/yd-checkout.css', array(), $this->version, 'all');

        // Check if we're on the order-received endpoint and ensure WooCommerce styles are loaded
        if ($this->is_order_received_page()) {
            // If WooCommerce hasn't already enqueued its styles, do it manually
            if (function_exists('WC') && !wp_style_is('woocommerce-general', 'enqueued')) {
                wp_enqueue_style('woocommerce-general');
            }
            
            if (function_exists('WC') && !wp_style_is('woocommerce-layout', 'enqueued')) {
                wp_enqueue_style('woocommerce-layout');
            }
            
            // Also load WooCommerce's checkout-specific styles
            if (function_exists('WC') && !wp_style_is('woocommerce-checkout', 'enqueued')) {
                wp_enqueue_style('woocommerce-checkout');
            }
            
            // Force our checkout page to include WooCommerce's "checkout" body class for styling
            add_filter('body_class', function($classes) {
                $classes[] = 'woocommerce-checkout';
                $classes[] = 'woocommerce-order-received';
                return $classes;
            });
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Check if we're on the checkout page
        if (!$this->is_checkout_page()) {
            return;
        }
        
        // Main script
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/yd-checkout.js', array(), $this->version, true);
        
        // Localize script with data needed for JavaScript
        global $yd_checkout;
        $here_api = null;
        if (isset($yd_checkout) && method_exists($yd_checkout, 'get_here_api')) {
            $here_api = $yd_checkout->get_here_api();
        }
        
        wp_localize_script($this->plugin_name, 'ydCheckoutSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yd_checkout_nonce'),
            'hereApiKey' => $here_api ? $here_api->get_api_key() : '',
            'i18n' => array(
                'addressSaved' => __('Address saved successfully.', 'yd-checkout'),
                'addressDeleted' => __('Address deleted successfully.', 'yd-checkout'),
                'defaultAddressSet' => __('Default address updated.', 'yd-checkout'),
                'errorOccurred' => __('An error occurred. Please try again.', 'yd-checkout'),
            )
        ));
        
        // Ensure gateway scripts are enqueued
        if (function_exists('WC') && $this->is_checkout_page()) {
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            
            if (!empty($available_gateways)) {
                foreach ($available_gateways as $gateway_id => $gateway) {
                    // Let each gateway enqueue its scripts
                    if (method_exists($gateway, 'payment_scripts')) {
                        $gateway->payment_scripts();
                    }
                }
            }
        }
        
    }

    /**
     * Check if the current page is a checkout page.
     *
     * @since    1.0.0
     * @return   boolean    True if the current page is a checkout page.
     */
    private function is_checkout_page() {
        global $post;
        
        // Check if the current page/post has the checkout shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'yd_checkout')) {
            return true;
        }
        
        // Check if it's a specific page ID
        $checkout_page_id = get_option('yd_checkout_page_id');
        if ($checkout_page_id && is_page($checkout_page_id)) {
            return true;
        }
        
        return false;
    }

    /**
     * Render the checkout form.
     *
     * @since    1.0.0
     * @param    array     $atts       Shortcode attributes.
     * @param    string    $content    Shortcode content.
     * @return   string                HTML output.
     */
    public function render_checkout($atts, $content = null) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'template' => 'default',
        ), $atts, 'yd_checkout');
        
        // Start output buffering
        ob_start();

        if ($this->is_order_received_page()) {
            // Let WooCommerce handle displaying the order confirmation
            if (function_exists('wc_get_template')) {
                global $wp;
                $order_id  = absint($wp->query_vars['order-received']);
                $order_key = isset($_GET['key']) ? wc_clean($_GET['key']) : '';
                
                // Get the order
                $order = wc_get_order($order_id);
                
                if ($order && $order->get_order_key() === $order_key) {
                    // Display the standard WooCommerce order received template
                    wc_get_template('checkout/thankyou.php', array('order' => $order));
                } else {
                    // Invalid order
                    echo '<p>' . esc_html__('Invalid order.', 'yd-checkout') . '</p>';
                }
            } else {
                echo '<p>' . esc_html__('WooCommerce is not active.', 'yd-checkout') . '</p>';
            }
            
            return ob_get_clean();
        }
        
        // Check if user is logged in if required
        if (get_option('yd_checkout_require_login', 'no') === 'yes' && !is_user_logged_in()) {
            // Display login message or form
            $this->render_login_required_message();
        } else {
            // Include the checkout template
            $template_path = 'partials/yd-checkout-public-display.php';
            if ($atts['template'] !== 'default') {
                $custom_template = 'partials/yd-checkout-' . sanitize_file_name($atts['template']) . '.php';
                if (file_exists(plugin_dir_path(__FILE__) . $custom_template)) {
                    $template_path = $custom_template;
                }
            }
            
            include(plugin_dir_path(__FILE__) . $template_path);
        }
        
        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Render the login required message.
     *
     * @since    1.0.0
     */
    private function render_login_required_message() {
        ?>
        <div class="yd-checkout-login-required">
            <h2><?php esc_html_e('Login Required', 'yd-checkout'); ?></h2>
            <p><?php esc_html_e('Please log in to continue with checkout.', 'yd-checkout'); ?></p>
            
            <?php
            // Display login form
            if (function_exists('woocommerce_login_form')) {
                woocommerce_login_form();
            } else {
                wp_login_form(array(
                    'redirect' => get_permalink(),
                ));
            }
            ?>
            
            <p class="yd-checkout-register-link">
                <?php esc_html_e('Don\'t have an account?', 'yd-checkout'); ?> 
                <a href="<?php echo esc_url(wp_registration_url()); ?>"><?php esc_html_e('Register here', 'yd-checkout'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * AJAX handler for getting addresses by type
     */
    public function ajax_get_addresses_by_type() {
        // Check nonce
        check_ajax_referer('yd_checkout_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to view addresses.', 'yd-checkout')));
            return;
        }
        
        // Get address type
        $address_type = isset($_POST['address_type']) 
            ? sanitize_text_field($_POST['address_type']) 
            : 'shipping';
        
        // Validate address type
        if (!in_array($address_type, ['shipping', 'billing'])) {
            wp_send_json_error(array('message' => __('Invalid address type.', 'yd-checkout')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Initialize the address model
        $address_model = new Yd_Checkout_Address();
        
        // Get addresses by type
        $addresses = $address_model->get_by_type($user_id, $address_type);
        
        // Format addresses for frontend
        $formatted_addresses = array();
        
        foreach ($addresses as $address) {
            // Extract street and house number parts
            $parts = [];
            if (method_exists($address_model, 'extract_street_parts')) {
                $parts = $address_model->extract_street_parts($address->address_line1);
            } else {
                // Fallback extraction
                $parts = [
                    'street' => $address->address_line1,
                    'house_number' => ''
                ];
                
                // Try to extract house number based on common patterns
                if (preg_match('/^(.*)\s+(\d+\s*[a-zA-Z]*)$/', $address->address_line1, $matches)) {
                    $parts['street'] = trim($matches[1]);
                    $parts['house_number'] = trim($matches[2]);
                }
            }
            
            // Create a comprehensive data object with ALL fields needed for editing
            $formatted_addresses[] = array(
                'id' => $address->id,
                'name' => $address->address_name,
                'first_name' => $address->first_name,
                'last_name' => $address->last_name,
                'address_line1' => $address->address_line1,
                'address_line2' => isset($address->address_line2) ? $address->address_line2 : '',
                'street' => $parts['street'],
                'house_number' => $parts['house_number'],
                'city' => $address->city,
                'postal_code' => $address->postal_code,
                'country' => $address->country,
                'is_default' => (bool) $address->is_default,
                'formatted' => sprintf(
                    '%s, %s %s, %s', 
                    $address->address_line1,
                    $address->city,
                    $address->postal_code,
                    $address->country
                )
            );
        }
        
        wp_send_json_success(array(
            'addresses' => $formatted_addresses,
            'count' => count($formatted_addresses)
        ));
    }
    /**
     * AJAX handler for processing an order.
     */
    public function ajax_process_order() {
        // Check nonce
        check_ajax_referer('yd_checkout_nonce', 'nonce');
        
        error_log('YDSN Processing order'); 
        error_log(print_r($_POST, true));

        // Initialize error array
        $errors = array();
        
        // Get form data
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
        $shipping_address_id = isset($_POST['shipping_address_id']) ? intval($_POST['shipping_address_id']) : 0;
        $use_new_shipping_address = empty($shipping_address_id) && isset($_POST['address_first_name']);
        $different_billing = isset($_POST['different_billing_address']) && $_POST['different_billing_address'] === '1';
        $billing_address_id = isset($_POST['billing_address_id']) ? intval($_POST['billing_address_id']) : 0;
        $use_new_billing_address = $different_billing && empty($billing_address_id) && isset($_POST['billing_address_first_name']);
        
        // Validate payment method
        if (empty($payment_method)) {
            $errors[] = __('Please select a payment method.', 'yd-checkout');
        }
        
        // Validate shipping address
        if (!$shipping_address_id && !$use_new_shipping_address) {
            $errors[] = __('Please select or enter a shipping address.', 'yd-checkout');
        }
        
        // Validate billing address if different billing is selected
        if ($different_billing && !$billing_address_id && !$use_new_billing_address) {
            $errors[] = __('Please select or enter a billing address.', 'yd-checkout');
        }
        
        // If there are errors, return them
        if (!empty($errors)) {
            wp_send_json_error(array('message' => implode(' ', $errors)));
            return;
        }
        
        // If WooCommerce is active, process the order through WooCommerce
        if (function_exists('WC')) {
            $this->process_woocommerce_order($payment_method);
        } else {
            wp_send_json_error(array('message' => __('WooCommerce is required for checkout processing.', 'yd-checkout')));
        }
    }

    /**
     * Process order through WooCommerce
     * 
     * @param string $payment_method The selected payment method
     */
    private function process_woocommerce_order($payment_method) {

        error_log('YDSN Woocommerce Processing order');
        // Get WooCommerce checkout fields
            $checkout_fields = WC()->session->get('checkout_fields');
        
        if (empty($checkout_fields)) {
            // Fall back to preparing from POST data
            $checkout_fields = $this->prepare_checkout_fields_from_post();
        }
        
        // Set all fields in WooCommerce session again to ensure they're fresh
        WC()->session->set('checkout_fields', $checkout_fields);
        
        // Set payment method
        WC()->session->set('chosen_payment_method', $payment_method);
        
        // Process payment with the chosen gateway
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        
        // Check if the chosen gateway is available
        if (!isset($available_gateways[$payment_method])) {
            wp_send_json_error(array('message' => __('Selected payment method is not available.', 'yd-checkout')));
            return;
        }
        
        // Get gateway
        $gateway = $available_gateways[$payment_method];
        
        // Get order details
        $order_id = WC()->checkout->create_order($checkout_fields);
        
        if (is_wp_error($order_id)) {
            wp_send_json_error(array('message' => $order_id->get_error_message()));
            return;
        }
        
        $order = wc_get_order($order_id);
        
        // Set payment method
        $order->set_payment_method($gateway);
        
        // Apply gateway payment processing
        $result = $gateway->process_payment($order_id);
        
        if (isset($result['result']) && $result['result'] === 'success') {
            // Payment successful, return success response
            wp_send_json_success($result);
        } else {
            // Payment failed, return error
            $error_message = isset($result['messages']) ? $result['messages'] : __('Payment processing failed.', 'yd-checkout');
            wp_send_json_error(array('message' => $error_message));
        }
    }

    /**
     * Check if current page is the order-received endpoint.
     *
     * @since    1.0.1
     * @return   bool    True if it's the order-received endpoint.
     */
    private function is_order_received_page() {
        global $wp;
        return !empty($wp->query_vars['order-received']);
    }

    /**
     * AJAX handler for creating a PayPal order.
     */
    public function ajax_create_paypal_order() {
        // For this phase, we're focusing on simplification. The implementation
        // details of the PayPal checkout will be handled in a later phase.
        wp_send_json_error(array('message' => 'PayPal integration will be implemented in Phase 3.'));
    }

    /**
     * AJAX handler for capturing a PayPal order after approval.
     */
    public function ajax_capture_paypal_order() {
        // For this phase, we're focusing on simplification. The implementation
        // details of the PayPal checkout will be handled in a later phase.
        wp_send_json_error(array('message' => 'PayPal integration will be implemented in Phase 3.'));
    }

    /**
     * Helper method to save a new address for a user
     * 
     * @param array $address_data The address data
     * @param string $type The address type (shipping or billing)
     * @return int|false The new address ID or false on failure
     */
    private function save_new_address($address_data, $type = 'shipping') {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_id = get_current_user_id();
        
        // Add user ID to address data
        $address_data['user_id'] = $user_id;
        
        // Set default flag
        $address_data['is_default'] = isset($_POST[$type === 'shipping' ? 'default_address' : 'default_billing_address']) 
            && $_POST[$type === 'shipping' ? 'default_address' : 'default_billing_address'] === '1' ? 1 : 0;
        
        // Set address name
        $address_data['address_name'] = isset($_POST[$type === 'shipping' ? 'address_name' : 'billing_address_name']) 
            && !empty($_POST[$type === 'shipping' ? 'address_name' : 'billing_address_name']) 
            ? sanitize_text_field($_POST[$type === 'shipping' ? 'address_name' : 'billing_address_name']) 
            : sprintf(
                __('%s %s\'s %s Address', 'yd-checkout'),
                $address_data['first_name'],
                $address_data['last_name'],
                $type === 'billing' ? 'Billing' : ''
            );
        
        // Set address type
        $address_data['address_type'] = $type;
        
        // Save address
        $address_model = new Yd_Checkout_Address();
        $address_id = $address_model->create($address_data);
        
        return $address_id;
    }

    /**
     * AJAX handler for saving a new address
     */
    public function ajax_save_address() {
        // Check nonce
        check_ajax_referer('yd_checkout_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to save addresses.', 'yd-checkout')));
            return;
        }
        
        // Get address type
        $address_type = isset($_POST['address_type']) 
            ? sanitize_text_field($_POST['address_type']) 
            : 'shipping';
        
        // Validate address type
        if (!in_array($address_type, ['shipping', 'billing'])) {
            wp_send_json_error(array('message' => __('Invalid address type.', 'yd-checkout')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Build address data from $_POST
        $address_data = array(
            'user_id'      => $user_id,
            'address_type' => $address_type,
            'first_name'   => isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '',
            'last_name'    => isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '',
            'address_line1' => (isset($_POST['street']) && isset($_POST['house_number'])) 
                ? sanitize_text_field($_POST['street'] . ' ' . $_POST['house_number']) 
                : (isset($_POST['street']) ? sanitize_text_field($_POST['street']) : ''),
            'city'         => isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '',
            'postal_code'  => isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '',
            'country'      => isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '', // This should now be an ISO code
            'is_default'   => isset($_POST['is_default']) && $_POST['is_default'] ? 1 : 0,
            'address_name' => isset($_POST['name']) && !empty($_POST['name']) 
                ? sanitize_text_field($_POST['name']) 
                : sprintf(
                    __('%s %s\'s %s Address', 'yd-checkout'),
                    isset($_POST['first_name']) ? $_POST['first_name'] : '',
                    isset($_POST['last_name']) ? $_POST['last_name'] : '',
                    $address_type === 'billing' ? 'Billing' : 'Shipping'
                )
        );
        
        // Additional data we might need for frontend display
        $address_data['street'] = isset($_POST['street']) ? sanitize_text_field($_POST['street']) : '';
        $address_data['house_number'] = isset($_POST['house_number']) ? sanitize_text_field($_POST['house_number']) : '';
        
        // For convenience, also store the country name if we have it
        if (!empty($address_data['country'])) {
            $country_name = Yd_Checkout_Helpers::get_country_name($address_data['country']);
            if ($country_name) {
                $address_data['country_name'] = $country_name;
            }
        }
        
        // Validate required fields
        $required_fields = array('first_name', 'last_name', 'address_line1', 'city', 'postal_code', 'country');
        $missing_fields = array();
        
        foreach ($required_fields as $field) {
            if (empty($address_data[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'yd-checkout')));
            return;
        }
        
        // Initialize the address model
        $address_model = new Yd_Checkout_Address();
        
        // If this is default, unset other defaults
        if ($address_data['is_default']) {
            $address_model->unset_defaults($user_id, $address_type);
        }
        
        // Create the address
        $address_id = $address_model->create($address_data);
        
        if ($address_id) {
            wp_send_json_success(array(
                'message' => __('Address saved successfully.', 'yd-checkout'),
                'address_id' => $address_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to save address.', 'yd-checkout')));
        }
    }

    /**
     * AJAX handler for updating an existing address
     */
    public function ajax_update_address() {
        // Check nonce
        check_ajax_referer('yd_checkout_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to update addresses.', 'yd-checkout')));
            return;
        }
        
        // Get address ID
        $address_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$address_id) {
            wp_send_json_error(array('message' => __('No address specified.', 'yd-checkout')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Get address type
        $address_type = isset($_POST['address_type']) 
            ? sanitize_text_field($_POST['address_type']) 
            : 'shipping';
        
        // Validate address type
        if (!in_array($address_type, ['shipping', 'billing'])) {
            wp_send_json_error(array('message' => __('Invalid address type.', 'yd-checkout')));
            return;
        }
        
        // Initialize the address model
        $address_model = new Yd_Checkout_Address();
        
        // Verify the address belongs to this user
        $address = $address_model->get_by_id($address_id);
        
        if (!$address || $address->user_id != $user_id) {
            wp_send_json_error(array('message' => __('You do not have permission to update this address.', 'yd-checkout')));
            return;
        }
        
        // Build address data from $_POST
        $address_data = array(
            'id'           => $address_id,
            'user_id'      => $user_id,
            'address_type' => $address_type,
            'first_name'   => isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '',
            'last_name'    => isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '',
            'address_line1' => (isset($_POST['street']) && isset($_POST['house_number'])) 
                ? sanitize_text_field($_POST['street'] . ' ' . $_POST['house_number']) 
                : (isset($_POST['street']) ? sanitize_text_field($_POST['street']) : ''),
            'city'         => isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '',
            'postal_code'  => isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '',
            'country'      => isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '',
            'is_default'   => isset($_POST['is_default']) && $_POST['is_default'] ? 1 : 0,
            'address_name' => isset($_POST['name']) && !empty($_POST['name']) 
                ? sanitize_text_field($_POST['name']) 
                : $address->address_name
        );
        
        // Additional data we might need for frontend display
        $address_data['street'] = isset($_POST['street']) ? sanitize_text_field($_POST['street']) : '';
        $address_data['house_number'] = isset($_POST['house_number']) ? sanitize_text_field($_POST['house_number']) : '';
        
        // Validate required fields
        $required_fields = array('first_name', 'last_name', 'address_line1', 'city', 'postal_code', 'country');
        $missing_fields = array();
        
        foreach ($required_fields as $field) {
            if (empty($address_data[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'yd-checkout')));
            return;
        }
        
        // If this is default, unset other defaults
        if ($address_data['is_default']) {
            $address_model->unset_defaults($user_id, $address_type);
        }
        
        // Update the address
        $success = $address_model->update($address_data);
        
        if ($success) {
            wp_send_json_success(array(
                'message' => __('Address updated successfully.', 'yd-checkout'),
                'address_id' => $address_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update address.', 'yd-checkout')));
        }
    }
    
}