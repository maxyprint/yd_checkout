<?php
/**
 * Main Checkout Form Template
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/public/partials
 */

// Security check
if (!defined('WPINC')) {
    die;
}

// User and cart context
$current_user = wp_get_current_user();
$is_logged_in = $current_user->exists();
$first_name = $is_logged_in ? $current_user->first_name : 'Guest';

// Check if HERE API is configured
global $yd_checkout;
$here_api = null;
if (isset($yd_checkout) && method_exists($yd_checkout, 'get_here_api')) {
    $here_api = $yd_checkout->get_here_api();
}
$here_api_enabled = $here_api && $here_api->is_configured();
?>

<div class="yd-checkout-container">
    <!-- Greeting -->
    <div class="yd-checkout-header">
        <h1>Hello <?php echo esc_html($first_name); ?>!</h1>
    </div>
    
    <div class="yd-checkout-content">
        <!-- Main Checkout Form -->
        <div class="yd-checkout-main">
            <form id="yd-checkout-form" method="post">
                <?php wp_nonce_field('yd_checkout_process', 'yd_checkout_nonce'); ?>
                
                <!-- Shipping Address Section -->
                <div class="yd-checkout-section yd-checkout-address-section" id="shipping-address-section">   
                    <!-- Address Grid - Two Column Layout -->
                    <div class="yd-checkout-address-grid" id="shipping-addresses-grid">
                        <?php if ($is_logged_in): ?>
                            <div class="yd-address-loading">Loading your addresses...</div>
                        <?php else: ?>
                            <!-- Sample Address for Non-Logged In Users -->
                            <div class="yd-checkout-address-card yd-address-selected" data-address-id="1">
                                <div class="yd-address-card-content">
                                    <h3 class="yd-address-name">my address</h3>
                                    <p class="yd-address-summary">your address 12, 97453 Town</p>
                                    <input type="radio" name="shipping_address_id" id="address_1" value="1" checked class="yd-address-radio">
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Add New Address Card - Always visible as the last item -->
                        <div class="yd-checkout-address-card yd-address-add-new" id="yd-add-shipping-address">
                            <div class="yd-address-card-content">
                                <span class="yd-address-add-icon">+</span>
                                <span class="yd-address-add-text">add new address</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Address Form (initially hidden unless editing) -->
                    <div id="yd-checkout-address-form" class="yd-hidden">
                        <div class="yd-checkout-form-title">
                            <h3 id="address-form-title">Add New Address</h3>
                        </div>
                        
                        <?php if ($here_api_enabled): ?>
                        <div class="yd-checkout-form-row">
                            <div class="yd-checkout-form-field yd-checkout-full-width">
                                <input type="text" 
                                    id="yd-address-search" 
                                    name="address_search" 
                                    placeholder="<?php esc_attr_e('Here-API Search Adress', 'yd-checkout'); ?>" 
                                    class="yd-checkout-address-search">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="yd-checkout-form-row yd-checkout-form-two-col">
                            <div class="yd-checkout-form-field">
                                <input type="text" 
                                    id="yd-address-first-name" 
                                    name="address_first_name" 
                                    placeholder="<?php esc_attr_e('Vorname', 'yd-checkout'); ?>" 
                                    value="<?php echo $is_logged_in ? esc_attr($current_user->first_name) : ''; ?>" 
                                    required>
                            </div>
                            <div class="yd-checkout-form-field">
                                <input type="text" 
                                    id="yd-address-last-name" 
                                    name="address_last_name" 
                                    placeholder="<?php esc_attr_e('Name', 'yd-checkout'); ?>" 
                                    value="<?php echo $is_logged_in ? esc_attr($current_user->last_name) : ''; ?>" 
                                    required>
                            </div>
                        </div>
                        
                        <div class="yd-checkout-form-row yd-checkout-form-two-col">
                            <div class="yd-checkout-form-field">
                                <input type="text" 
                                    id="yd-address-street" 
                                    name="address_street" 
                                    placeholder="<?php esc_attr_e('Straße', 'yd-checkout'); ?>" 
                                    required>
                            </div>
                            <div class="yd-checkout-form-field">
                                <input type="text" 
                                    id="yd-address-house-number" 
                                    name="address_house_number" 
                                    placeholder="<?php esc_attr_e('Hausnummer', 'yd-checkout'); ?>" 
                                    required>
                            </div>
                        </div>
                        
                        <div class="yd-checkout-form-row yd-checkout-form-two-col">
                            <div class="yd-checkout-form-field">
                                <input type="text" 
                                    id="yd-address-postal-code" 
                                    name="address_postal_code" 
                                    placeholder="<?php esc_attr_e('Postleitzahl', 'yd-checkout'); ?>" 
                                    required>
                            </div>
                            <div class="yd-checkout-form-field">
                                <input type="text" 
                                    id="yd-address-city" 
                                    name="address_city" 
                                    placeholder="<?php esc_attr_e('Ort', 'yd-checkout'); ?>" 
                                    required>
                            </div>
                        </div>
                        
                        <div class="yd-checkout-form-row">
                            <div class="yd-checkout-form-field yd-checkout-full-width">
                                <select id="yd-address-country" name="address_country" required>
                                    <option value=""><?php esc_html_e('Select a country', 'yd-checkout'); ?></option>
                                    <?php 
                                    $countries = Yd_Checkout_Helpers::get_countries();
                                    $default_country = get_option('yd_checkout_default_country', 'US');
                                    
                                    foreach ($countries as $code => $name) {
                                        echo '<option value="' . esc_attr($code) . '" ' . selected($code, $default_country, false) . '>' . esc_html($name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <?php if ($is_logged_in): ?>
                        <div class="yd-checkout-form-row">
                            <div class="yd-checkout-form-field yd-checkout-full-width">
                                <input type="text" 
                                    id="yd-address-name" 
                                    name="address_name" 
                                    placeholder="<?php esc_attr_e('Address name (e.g. Home, Work)', 'yd-checkout'); ?>"
                                    required>
                            </div>
                        </div>
                        
                        <div class="yd-checkout-form-row">
                            <div class="yd-checkout-form-field yd-checkout-full-width">
                                <div class="yd-checkout-checkbox">
                                    <input type="checkbox" id="default-address" name="default_address" value="1">
                                    <label for="default-address">
                                        <?php esc_html_e('Set as default shipping address', 'yd-checkout'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="yd-checkout-form-actions">
                            <button type="button" class="yd-button yd-button-secondary yd-cancel-address-btn">
                                <?php esc_html_e('Cancel', 'yd-checkout'); ?>
                            </button>
                            
                            <button type="button" class="yd-button yd-button-primary yd-save-address-btn" id="yd-save-shipping-address">
                                <?php esc_html_e('Save Address', 'yd-checkout'); ?>
                            </button>
                            
                            <button type="button" class="yd-button yd-button-primary yd-update-address-btn" id="yd-update-shipping-address" style="display: none;">
                                <?php esc_html_e('Update Address', 'yd-checkout'); ?>
                            </button>
                            
                            <input type="hidden" id="yd-editing-address-id" name="editing_address_id" value="">
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method Section -->
                <div class="yd-checkout-section yd-checkout-payment-section">
                    <h2>payment method</h2>
                    
                    <div class="yd-checkout-payment-methods-wrapper">                        
                        <div class="yd-checkout-payment-methods">
                            <?php
                            // Get available payment gateways
                            $available_gateways = array();
                            if (function_exists('WC')) {
                                $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
                            }
                            
                            if (empty($available_gateways)) {
                                echo '<div class="yd-checkout-notice yd-checkout-error">';
                                echo '<p>' . esc_html__('No payment methods available.', 'yd-checkout') . '</p>';
                                echo '</div>';
                            } else {
                                $default_gateway = '';
                                foreach ($available_gateways as $gateway_id => $gateway) {
                                    if (empty($default_gateway)) {
                                        $default_gateway = $gateway_id;
                                    }
                                    
                                    // Get icon or use default
                                    $icon_url = plugin_dir_url(dirname(dirname(__FILE__))) . 'public/assets/payment/' . $gateway_id . '.png';
                                    if (!file_exists(plugin_dir_path(dirname(dirname(__FILE__))) . 'public/assets/payment/' . $gateway_id . '.png')) {
                                        $icon_url = plugin_dir_url(dirname(dirname(__FILE__))) . 'public/assets/payment/default.png';
                                    }
                                    ?>
                                    <div class="yd-checkout-payment-method payment_<?php echo esc_attr($gateway_id); ?>">
                                        <input type="radio" 
                                               id="payment_<?php echo esc_attr($gateway_id); ?>" 
                                               name="payment_method" 
                                               value="<?php echo esc_attr($gateway_id); ?>"
                                               <?php checked($gateway_id === $default_gateway); ?>>
                                        <label for="payment_<?php echo esc_attr($gateway_id); ?>" class="yd-payment-method-label">
                                            <img src="<?php echo esc_url($icon_url); ?>" 
                                                 alt="<?php echo esc_attr($gateway->get_title()); ?>"
                                                 class="yd-payment-method-icon">
                                        </label>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        
                        <div class="yd-checkout-payment-descriptions">
                            <?php foreach ($available_gateways as $gateway_id => $gateway): ?>
                                <div id="payment_fields_<?php echo esc_attr($gateway_id); ?>" class="yd-checkout-payment-method-fields" style="display: none;">
                                    <?php 
                                    // Call the payment_fields method to render gateway-specific fields
                                    if (method_exists($gateway, 'payment_fields')) $gateway->payment_fields();
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Billing Address Toggle -->
                <div class="yd-checkout-billing-toggle">
                    <label class="yd-checkout-checkbox">
                        <input type="checkbox" id="different-billing" name="different_billing_address" value="1">
                        <span>different billing address</span>
                    </label>
                </div>
                
                <!-- Billing Address Section (initially hidden) -->
                <div class="yd-checkout-section yd-checkout-address-section yd-hidden" id="billing-address-section">
                    
                    <!-- Billing Address Grid - Two Column Layout -->
                    <div class="yd-checkout-address-grid" id="billing-addresses-grid">
                        <?php if ($is_logged_in): ?>
                            <div class="yd-address-loading">Loading your addresses...</div>
                        <?php endif; ?>
                        
                        <!-- Add New Billing Address Card - Always visible as the last item -->
                        <div class="yd-checkout-address-card yd-address-add-new" id="yd-add-billing-address">
                            <div class="yd-address-card-content">
                                <span class="yd-address-add-icon">+</span>
                                <span class="yd-address-add-text">add new address</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Billing Address Form (initially hidden) -->
                    <div id="billing-address-form" class="yd-hidden">
                        <div class="yd-checkout-form-title">
                            <h3 id="billing-form-title">Add New Billing Address</h3>
                        </div>
                        
                        <?php if ($here_api_enabled): ?>
                        <div class="yd-checkout-form-row">
                            <div class="yd-checkout-form-field yd-checkout-full-width">
                                <input type="text" 
                                    id="billing-address-search" 
                                    name="billing_address_search" 
                                    placeholder="<?php esc_attr_e('Here-API Search Adress', 'yd-checkout'); ?>" 
                                    class="yd-checkout-address-search">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="yd-checkout-form-row yd-checkout-form-two-col">
                            <div class="yd-checkout-form-field">
                                <input type="text" 
                                    id="billing-address-first-name" 
                                    name="billing_address_first_name" 
                                    placeholder="<?php esc_attr_e('Vorname', 'yd-checkout'); ?>" 
                                    value="<?php echo $is_logged_in ? esc_attr($current_user->first_name) : ''; ?>" 
                                    required>
                            </div>
                            <div class="yd-checkout-form-field">
                                <input type="text" 
                                    id="billing-address-last-name" 
                                    name="billing_address_last_name" 
                                    placeholder="<?php esc_attr_e('Name', 'yd-checkout'); ?>" 
                                    value="<?php echo $is_logged_in ? esc_attr($current_user->last_name) : ''; ?>" 
                                    required>
                            </div>
                        </div>
                        
                        <div class="yd-checkout-form-row yd-checkout-form-two-col">
                            <div class="yd-checkout-form-field">
                                <input type="text" 
                                    id="billing-address-street" 
                                    name="billing_address_street" 
                                    placeholder="<?php esc_attr_e('Straße', 'yd-checkout'); ?>" 
                                    required>
                            </div>
                            <div class="yd-checkout-form-field">
                                <input type="text" 
                                    id="billing-address-house-number" 
                                    name="billing_address_house_number" 
                                    placeholder="<?php esc_attr_e('Hausnummer', 'yd-checkout'); ?>" 
                                    required>
                            </div>
                        </div>
                        
                        <div class="yd-checkout-form-row yd-checkout-form-two-col">
                            <div class="yd-checkout-form-field">
                                <input type="text" 
                                    id="billing-address-postal-code" 
                                    name="billing_address_postal_code" 
                                    placeholder="<?php esc_attr_e('Postleitzahl', 'yd-checkout'); ?>" 
                                    required>
                            </div>
                            <div class="yd-checkout-form-field">
                                <input type="text" 
                                    id="billing-address-city" 
                                    name="billing_address_city" 
                                    placeholder="<?php esc_attr_e('Ort', 'yd-checkout'); ?>" 
                                    required>
                            </div>
                        </div>
                        
                        <div class="yd-checkout-form-row">
                            <div class="yd-checkout-form-field yd-checkout-full-width">
                                <select id="billing-address-country" name="billing_address_country" required>
                                    <option value=""><?php esc_html_e('Select a country', 'yd-checkout'); ?></option>
                                    <?php 
                                    $countries = Yd_Checkout_Helpers::get_countries();
                                    $default_country = get_option('yd_checkout_default_country', 'US');
                                    
                                    foreach ($countries as $code => $name) {
                                        echo '<option value="' . esc_attr($code) . '" ' . selected($code, $default_country, false) . '>' . esc_html($name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <?php if ($is_logged_in): ?>
                        <div class="yd-checkout-form-row">
                            <div class="yd-checkout-form-field yd-checkout-full-width">
                                <input type="text" 
                                    id="billing-address-name" 
                                    name="billing_address_name" 
                                    placeholder="<?php esc_attr_e('Address name (e.g. Home, Work)', 'yd-checkout'); ?>"
                                    required>
                            </div>
                        </div>
                        
                        <div class="yd-checkout-form-row">
                            <div class="yd-checkout-form-field yd-checkout-full-width">
                                <div class="yd-checkout-checkbox">
                                    <input type="checkbox" id="default-billing-address" name="default_billing_address" value="1">
                                    <label for="default-billing-address">
                                        <?php esc_html_e('Set as default billing address', 'yd-checkout'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="yd-checkout-form-actions">
                            <button type="button" class="yd-button yd-button-secondary yd-cancel-address-btn">
                                <?php esc_html_e('Cancel', 'yd-checkout'); ?>
                            </button>
                            
                            <button type="button" class="yd-button yd-button-primary yd-save-address-btn" id="yd-save-billing-address">
                                <?php esc_html_e('Save Address', 'yd-checkout'); ?>
                            </button>
                            
                            <button type="button" class="yd-button yd-button-primary yd-update-address-btn" id="yd-update-billing-address" style="display: none;">
                                <?php esc_html_e('Update Address', 'yd-checkout'); ?>
                            </button>
                            
                            <input type="hidden" id="yd-editing-billing-address-id" name="editing_billing_address_id" value="">
                        </div>
                    </div>
                </div>
                
                <div class="yd-checkout-submit">
                    <button type="submit" class="yd-checkout-submit-btn">Place Order</button>
                </div>
            </form>
        </div>
        
        <!-- Order Summary Sidebar -->
        <div class="yd-checkout-sidebar">
            <?php include plugin_dir_path(__FILE__) . 'yd-checkout-order-summary.php'; ?>
        </div>
    </div>
</div>