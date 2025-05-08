<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://octonove.com
 * @since      1.0.0
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/includes
 * @author     Octonove <octonoveclientes@gmail.com>
 */
class Yd_Checkout_Deactivator {

	/**
	 * Plugin deactivation actions.
	 *
	 * Optional cleanup actions to perform when the plugin is deactivated.
	 * Currently does not remove the database tables to preserve user data.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// We don't remove database tables by default to preserve user data
		// If a complete uninstall is desired, use uninstall.php
	}

}