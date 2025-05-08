<?php
/**
 * Admin settings tabs template
 *
 * @link       https://octonove.com
 * @since      1.0.0
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Available variables:
 * 
 * $active_tab - The current active tab
 * $tabs - Array of tabs with 'id', 'label', and optional 'callback'
 */

// Default tabs if not provided
if (!isset($tabs) || empty($tabs)) {
    $tabs = array(
        array(
            'id' => 'location',
            'label' => __('Address & Location', 'yd-checkout'),
        )
    );
}

// Set default active tab if not provided
if (!isset($active_tab)) {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'location';
}
?>

<div class="yd-checkout-admin-tabs">
    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab): ?>
            <a href="?page=yd-checkout-settings&tab=<?php echo esc_attr($tab['id']); ?>" 
               class="nav-tab <?php echo $active_tab === $tab['id'] ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab['label']); ?>
            </a>
        <?php endforeach; ?>
    </h2>
</div>