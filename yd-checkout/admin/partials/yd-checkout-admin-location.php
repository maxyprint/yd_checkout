<?php
/**
 * Admin location and address settings template
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
?>

<form method="post" action="options.php" class="yd-checkout-admin-form">
    <?php
    settings_fields('yd_checkout_location_settings');
    do_settings_sections('yd_checkout_location_settings');
    submit_button(__('Save Location Settings', 'yd-checkout'));
    ?>
</form>