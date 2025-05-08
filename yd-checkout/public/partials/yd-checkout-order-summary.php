<?php
/**
 * Order summary template
 *
 * Displays the order summary with cart items and totals directly from WooCommerce.
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get cart data from WooCommerce
$cart_items = array();
$subtotal = 0;
$shipping = 0;
$total = 0;
// Check if WooCommerce is active and cart is available
if (function_exists('WC') && WC()->cart) {
    $cart = WC()->cart;
    
    if (!$cart->is_empty()) {
        // Get cart items
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            
            // Apply product filters for title and price
            $product_title = apply_filters('woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key);
            $product_price = apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($product), $cart_item, $cart_item_key);
            $product_subtotal = apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($product, $cart_item['quantity']), $cart_item, $cart_item_key);
            
            // Get product image with proper filters
            $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $product->get_image(), $cart_item, $cart_item_key);
            
            // If product has no image, use a placeholder
            if (empty($thumbnail)) {
                $thumbnail = wc_placeholder_img();
            }
            
            // Get item data
            $item_data = array(
                'id' => $product->get_id(),
                'title' => $product_title,
                'price' => $cart_item['line_subtotal'] / $cart_item['quantity'],
                'quantity' => $cart_item['quantity'],
                'total_price' => $cart_item['line_subtotal'],
                'image' => $thumbnail,
                'formatted_price' => $product_price,
                'formatted_subtotal' => $product_subtotal
            );
            
            // Apply item filters
            $item_data = apply_filters('yd_checkout_cart_item', $item_data, $cart_item, $cart_item_key);
            
            $cart_items[] = $item_data;
        }
        
        // Get totals with proper WooCommerce filters
        $subtotal = $cart->get_cart_subtotal();
        $shipping_total = $cart->get_shipping_total();
        $shipping = WC()->cart->get_cart_shipping_total();
        $total = $cart->get_total();
        
        // Apply filters to allow other plugins to modify totals
        $subtotal = apply_filters('yd_checkout_subtotal', $subtotal);
        $shipping = apply_filters('yd_checkout_shipping', $shipping);
        $total = apply_filters('yd_checkout_total', $total);
    }
}

// Use sample data if cart is empty (for testing/preview)
if (empty($cart_items)) {
    $cart_items = array(
        array(
            'id' => 1,
            'title' => 'Sample Product',
            'price' => 20.00,
            'quantity' => 1,
            'total_price' => 20.00,
            'image' => '<img src="' . plugin_dir_url(dirname(dirname(__FILE__))) . 'public/images/product-placeholder.png" alt="Sample Product">',
            'formatted_price' => '€20,00',
            'formatted_subtotal' => '€20,00'
        )
    );
    
    $subtotal = '<span class="amount">€20,00</span>';
    $shipping = '<span class="amount">€5,00</span>';
    $total = '<span class="amount">€25,00</span>';
}
?>

<div class="yd-order-summary">
    <h2><?php esc_html_e('order summary', 'yd-checkout'); ?></h2>
    
    <div class="yd-order-items">
        <?php foreach ($cart_items as $item): ?>
            <div class="yd-order-item">
                <div class="yd-order-item-image">
                    <?php echo $item['image']; ?>
                </div>
                <div class="yd-order-item-details">
                    <h3 class="yd-order-item-title"><?php echo wp_kses_post($item['title']); ?></h3>
                    <div class="yd-order-item-price">
                        <?php 
                        /* Display quantity and unit price */
                        printf(
                            esc_html__('%1$dx %2$s', 'yd-checkout'),
                            esc_html($item['quantity']),
                            isset($item['formatted_price']) ? wp_kses_post($item['formatted_price']) : wc_price($item['price'])
                        );
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="yd-order-totals">
        <div class="yd-order-subtotal yd-order-row">
            <span class="yd-order-label"><?php esc_html_e('Subtotal', 'yd-checkout'); ?></span>
            <span class="yd-order-value">
                <?php echo wp_kses_post($subtotal); ?>
            </span>
        </div>
        
        <div class="yd-order-shipping yd-order-row">
            <span class="yd-order-label"><?php esc_html_e('Shipping', 'yd-checkout'); ?></span>
            <span class="yd-order-value">
                <?php echo wp_kses_post($shipping); ?>
            </span>
        </div>
        
        <div class="yd-order-divider"></div>
        
        <div class="yd-order-total yd-order-row">
            <span class="yd-order-label"><?php esc_html_e('Total', 'yd-checkout'); ?></span>
            <span class="yd-order-value yd-order-total-value">
                <?php echo wp_kses_post($total); ?>
            </span>
        </div>
    </div>
</div>