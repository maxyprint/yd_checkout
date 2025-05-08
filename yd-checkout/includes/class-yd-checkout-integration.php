<?php
/**
 * Updates to integrate payment gateways into the main plugin class.
 *
 * This file contains code for integrating the payment gateways into the
 * Yd_Checkout class. These changes should be applied to class-yd-checkout.php.
 *
 * @link       https://octonove.com
 * @since      1.0.0
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/includes
 */

/**
 * Add the following property to the Yd_Checkout class:
 *
 * /**
 *  * Available payment gateways.
 *  *
 *  * @since    1.0.0
 *  * @access   protected
 *  * @var      array    $gateways    Available payment gateways.
 *  *\/
 * protected $gateways;
 */

/**
 * Add the following method to the load_dependencies() method in class-yd-checkout.php
 * right before $this->loader = new Yd_Checkout_Loader();
 */
// Load payment gateways
$this->load_gateways();

/**
 * Add the following methods to the Yd_Checkout class:
 */

/**
 * Load payment gateways.
 *
 * @since    1.0.0
 * @access   private
 */
private function load_gateways() {
    // Create gateways directory if it doesn't exist
    $gateways_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways';
    if ( ! file_exists( $gateways_dir ) ) {
        wp_mkdir_p( $gateways_dir );
    }
    
    // Include gateway files
    require_once $gateways_dir . '/class-yd-checkout-gateway.php';
    require_once $gateways_dir . '/class-yd-checkout-stripe.php';
    require_once $gateways_dir . '/class-yd-checkout-paypal.php';
    
    // Initialize gateways
    $this->gateways = array(
        'stripe' => new Yd_Checkout_Stripe(),
        'paypal' => new Yd_Checkout_PayPal(),
    );
    
    // Filter gateways
    $this->gateways = apply_filters( 'yd_checkout_payment_gateways', $this->gateways );
}

/**
 * Get available payment gateways.
 *
 * @since     1.0.0
 * @return    array    Available payment gateways.
 */
public function get_available_gateways() {
    $available_gateways = array();
    
    if ( ! empty( $this->gateways ) ) {
        foreach ( $this->gateways as $gateway_id => $gateway ) {
            if ( $gateway->is_enabled() ) {
                $available_gateways[ $gateway_id ] = $gateway;
            }
        }
    }
    
    return $available_gateways;
}

/**
 * Get a specific payment gateway.
 *
 * @since     1.0.0
 * @param     string    $gateway_id    The gateway ID.
 * @return    object|null               The gateway object or null if not found.
 */
public function get_gateway( $gateway_id ) {
    return isset( $this->gateways[ $gateway_id ] ) ? $this->gateways[ $gateway_id ] : null;
}

/**
 * Process payment with a specific gateway.
 *
 * @since     1.0.0
 * @param     string              $gateway_id    The gateway ID.
 * @param     Yd_Checkout_Order   $order         The order to process.
 * @param     array               $data          Additional data for payment processing.
 * @return    array                              Result of payment processing.
 */
public function process_payment( $gateway_id, $order, $data = array() ) {
    $gateway = $this->get_gateway( $gateway_id );
    
    if ( ! $gateway ) {
        return array(
            'result'  => 'error',
            'message' => __( 'Invalid payment method.', 'yd-checkout' ),
        );
    }
    
    // Validate payment fields
    $validation = $gateway->validate_payment_fields( $data );
    
    if ( is_wp_error( $validation ) ) {
        return array(
            'result'  => 'error',
            'message' => $validation->get_error_message(),
        );
    }
    
    // Process payment
    return $gateway->process_payment( $order, $data );
}

/**
 * Handle webhook requests.
 *
 * @since     1.0.0
 * @return    void
 */
public function handle_webhook() {
    // Check if this is a webhook request
    if ( ! isset( $_GET['yd-checkout-webhook'] ) || $_GET['yd-checkout-webhook'] !== '1' ) {
        return;
    }
    
    // Get the gateway
    $gateway_id = isset( $_GET['gateway'] ) ? sanitize_text_field( $_GET['gateway'] ) : '';
    $gateway = $this->get_gateway( $gateway_id );
    
    if ( ! $gateway ) {
        status_header( 400 );
        exit( 'Invalid gateway' );
    }
    
    // Handle webhook
    $gateway->handle_webhook( $_REQUEST );
    exit;
}

/**
 * Register webhooks with payment gateways.
 *
 * @since     1.0.0
 * @return    array    Results of webhook registration.
 */
public function register_webhooks() {
    $results = array();
    
    if ( ! empty( $this->gateways ) ) {
        foreach ( $this->gateways as $gateway_id => $gateway ) {
            if ( $gateway->is_enabled() ) {
                $results[ $gateway_id ] = $gateway->register_webhook();
            }
        }
    }
    
    return $results;
}