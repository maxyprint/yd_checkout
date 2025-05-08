<?php
/**
 * YDesign Checkout - Test Payment Shortcodes
 *
 * Test implementation of payment buttons for various payment gateways.
 *
 * @package     Yd_Checkout
 * @subpackage  Yd_Checkout/includes
 * @since       1.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to handle test payment shortcodes
 *
 * @since 1.0.0
 */
class Yd_Checkout_Test_Payments {

    /**
     * Initialize the class and register shortcodes
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode('yd_test_paypal', array($this, 'render_paypal_button'));
        add_shortcode('yd_test_applepay', array($this, 'render_applepay_button'));
        
        // Add necessary scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue necessary scripts for payment buttons
     */
    public function enqueue_scripts() {
        // Only enqueue on pages where our shortcodes are used
        global $post;
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'yd_test_paypal') || 
            has_shortcode($post->post_content, 'yd_test_applepay')
        )) {
            // Enqueue main checkout script which contains our payment functions
            wp_enqueue_script('yd-checkout-script', YD_CHECKOUT_URL . 'public/js/yd-checkout.js', array('jquery'), YD_CHECKOUT_VERSION, true);
            
            // Get settings for payment gateways
            $settings = $this->get_payment_settings();
            
            // Pass settings to script
            wp_localize_script('yd-checkout-script', 'ydCheckoutSettings', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yd_checkout_nonce'),
            ));
            
            // Add Stripe settings if needed
            if (has_shortcode($post->post_content, 'yd_test_applepay')) {
                wp_localize_script('yd-checkout-script', 'ydCheckoutStripe', array(
                    'publishableKey' => $settings['stripe_publishable_key'],
                ));
            }
            
            // Add PayPal settings if needed
            if (has_shortcode($post->post_content, 'yd_test_paypal')) {
                wp_localize_script('yd-checkout-script', 'ydCheckoutPayPal', array(
                    'clientId' => $settings['paypal_client_id'],
                ));
            }
            
            // Enqueue additional test script
            wp_enqueue_script('yd-checkout-test', YD_CHECKOUT_URL . 'includes/js/yd-checkout-test.js', array('jquery', 'yd-checkout-script'), YD_CHECKOUT_VERSION, true);
            
            // Enqueue styles
            wp_enqueue_style('yd-checkout-test', YD_CHECKOUT_URL . 'includes/css/yd-checkout-test.css', array(), YD_CHECKOUT_VERSION);
        }
    }

    /**
     * Get payment settings from WordPress options
     *
     * @return array Settings for payment gateways
     */
    private function get_payment_settings() {
        $settings = array();
        
        // Check if we're in test mode
        $stripe_test_mode = get_option('yd_checkout_stripe_test_mode', 'yes');
        $paypal_test_mode = get_option('yd_checkout_paypal_test_mode', 'yes');
        
        // Get Stripe keys based on test mode
        if ($stripe_test_mode === 'yes') {
            $settings['stripe_publishable_key'] = get_option('yd_checkout_stripe_test_publishable_key', '');
            $settings['stripe_secret_key'] = get_option('yd_checkout_stripe_test_secret_key', '');
        } else {
            $settings['stripe_publishable_key'] = get_option('yd_checkout_stripe_live_publishable_key', '');
            $settings['stripe_secret_key'] = get_option('yd_checkout_stripe_live_secret_key', '');
        }
        
        // Get PayPal keys based on test mode
        if ($paypal_test_mode === 'yes') {
            $settings['paypal_client_id'] = get_option('yd_checkout_paypal_sandbox_client_id', '');
            $settings['paypal_client_secret'] = get_option('yd_checkout_paypal_sandbox_client_secret', '');
        } else {
            $settings['paypal_client_id'] = get_option('yd_checkout_paypal_live_client_id', '');
            $settings['paypal_client_secret'] = get_option('yd_checkout_paypal_live_client_secret', '');
        }
        
        return $settings;
    }

    /**
     * Render PayPal test button shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_paypal_button($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'amount' => '10.00',
                'currency' => 'EUR',
                'description' => 'Test Payment',
                'redirect' => '',
            ),
            $atts,
            'yd_test_paypal'
        );
        
        // Get settings
        $settings = $this->get_payment_settings();
        
        // Check if PayPal is configured
        if (empty($settings['paypal_client_id'])) {
            return '<div class="yd-test-payment-error">PayPal client ID is not configured. Please check plugin settings.</div>';
        }
        
        // Create unique payment ID
        $payment_id = 'test_' . uniqid();
        
        // Create test checkout container
        $output = '<div class="yd-test-payment yd-test-paypal" data-payment-id="' . esc_attr($payment_id) . '">';
        $output .= '<div class="yd-test-payment-header">';
        $output .= '<h3>Test PayPal Payment</h3>';
        $output .= '<div class="yd-test-payment-details">';
        $output .= '<p><strong>Amount:</strong> ' . esc_html($atts['amount']) . ' ' . esc_html($atts['currency']) . '</p>';
        $output .= '<p><strong>Description:</strong> ' . esc_html($atts['description']) . '</p>';
        $output .= '</div>';
        $output .= '</div>';
        
        // PayPal buttons container
        $output .= '<div id="paypal-button-container-' . esc_attr($payment_id) . '" class="yd-test-paypal-buttons"></div>';
        
        // Status container
        $output .= '<div class="yd-test-payment-status" id="paypal-status-' . esc_attr($payment_id) . '"></div>';
        
        // Add data attributes for JavaScript
        $output .= '<script type="text/javascript">';
        $output .= 'document.addEventListener("DOMContentLoaded", function() {';
        $output .= '  if (typeof YDCheckoutTest !== "undefined") {';
        $output .= '    YDCheckoutTest.initPayPal("' . esc_attr($payment_id) . '", {';
        $output .= '      amount: "' . esc_js($atts['amount']) . '",';
        $output .= '      currency: "' . esc_js($atts['currency']) . '",';
        $output .= '      description: "' . esc_js($atts['description']) . '",';
        $output .= '      redirect: "' . esc_js($atts['redirect']) . '"';
        $output .= '    });';
        $output .= '  }';
        $output .= '});';
        $output .= '</script>';
        
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Render Apple Pay test button shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_applepay_button($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'amount' => '10.00',
                'currency' => 'EUR',
                'description' => 'Test Payment',
                'redirect' => '',
            ),
            $atts,
            'yd_test_applepay'
        );
        
        // Get settings
        $settings = $this->get_payment_settings();
        
        // Check if Stripe is configured
        if (empty($settings['stripe_publishable_key'])) {
            return '<div class="yd-test-payment-error">Stripe publishable key is not configured. Please check plugin settings.</div>';
        }
        
        // Create unique payment ID
        $payment_id = 'test_' . uniqid();
        
        // Create test checkout container
        $output = '<div class="yd-test-payment yd-test-applepay" data-payment-id="' . esc_attr($payment_id) . '">';
        $output .= '<div class="yd-test-payment-header">';
        $output .= '<h3>Test Apple Pay Payment</h3>';
        $output .= '<div class="yd-test-payment-details">';
        $output .= '<p><strong>Amount:</strong> ' . esc_html($atts['amount']) . ' ' . esc_html($atts['currency']) . '</p>';
        $output .= '<p><strong>Description:</strong> ' . esc_html($atts['description']) . '</p>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Apple Pay container
        $output .= '<div id="applepay-button-container-' . esc_attr($payment_id) . '" class="yd-test-applepay-button">';
        $output .= '<button id="applepay-button-' . esc_attr($payment_id) . '" class="yd-applepay-button">Pay with Apple Pay</button>';
        $output .= '</div>';
        
        // Status container
        $output .= '<div class="yd-test-payment-status" id="applepay-status-' . esc_attr($payment_id) . '"></div>';
        
        // Add data attributes for JavaScript
        $output .= '<script type="text/javascript">';
        $output .= 'document.addEventListener("DOMContentLoaded", function() {';
        $output .= '  if (typeof YDCheckoutTest !== "undefined") {';
        $output .= '    YDCheckoutTest.initApplePay("' . esc_attr($payment_id) . '", {';
        $output .= '      amount: "' . esc_js($atts['amount']) . '",';
        $output .= '      currency: "' . esc_js($atts['currency']) . '",';
        $output .= '      description: "' . esc_js($atts['description']) . '",';
        $output .= '      redirect: "' . esc_js($atts['redirect']) . '"';
        $output .= '    });';
        $output .= '  }';
        $output .= '});';
        $output .= '</script>';
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Process AJAX test payment for PayPal
     * This would normally be registered as an AJAX endpoint
     */
    public function process_test_paypal_payment() {
        // Check nonce for security
        check_ajax_referer('yd_checkout_nonce', 'nonce');
        
        // Get PayPal order ID from request
        $order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';
        
        if (empty($order_id)) {
            wp_send_json_error(array('message' => 'Missing order ID'));
            return;
        }
        
        // Get settings
        $settings = $this->get_payment_settings();
        
        // Here you would normally capture the PayPal payment using the PayPal API
        // For this test implementation, we'll just return success
        
        wp_send_json_success(array(
            'message' => 'Payment processed successfully!',
            'order_id' => $order_id,
            'test_mode' => get_option('yd_checkout_paypal_test_mode', 'yes')
        ));
    }
    
    /**
     * Process AJAX test payment for Apple Pay
     * This would normally be registered as an AJAX endpoint
     */
    public function process_test_applepay_payment() {
        // Check nonce for security
        check_ajax_referer('yd_checkout_nonce', 'nonce');
        
        // Get payment token from request
        $payment_token = isset($_POST['payment_token']) ? sanitize_text_field($_POST['payment_token']) : '';
        
        if (empty($payment_token)) {
            wp_send_json_error(array('message' => 'Missing payment token'));
            return;
        }
        
        // Get settings
        $settings = $this->get_payment_settings();
        
        // Here you would normally process the Apple Pay payment using the Stripe API
        // For this test implementation, we'll just return success
        
        wp_send_json_success(array(
            'message' => 'Payment processed successfully!',
            'payment_token' => $payment_token,
            'test_mode' => get_option('yd_checkout_stripe_test_mode', 'yes')
        ));
    }
}

// Initialize the class
$yd_checkout_test_payments = new Yd_Checkout_Test_Payments();

// Register AJAX handlers
add_action('wp_ajax_yd_test_paypal_payment', array($yd_checkout_test_payments, 'process_test_paypal_payment'));
add_action('wp_ajax_nopriv_yd_test_paypal_payment', array($yd_checkout_test_payments, 'process_test_paypal_payment'));

add_action('wp_ajax_yd_test_applepay_payment', array($yd_checkout_test_payments, 'process_test_applepay_payment'));
add_action('wp_ajax_nopriv_yd_test_applepay_payment', array($yd_checkout_test_payments, 'process_test_applepay_payment'));