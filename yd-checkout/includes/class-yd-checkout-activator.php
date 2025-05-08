<?php

/**
 * Fired during plugin activation
 *
 * @link       https://octonove.com
 * @since      1.0.0
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/includes
 * @author     Octonove <octonoveclientes@gmail.com>
 */
class Yd_Checkout_Activator {

	/**
	 * Create the necessary database tables on plugin activation.
	 *
	 * Creates the addresses table for storing user shipping addresses.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		
        Yd_Checkout_Address::create_table();
	}
}