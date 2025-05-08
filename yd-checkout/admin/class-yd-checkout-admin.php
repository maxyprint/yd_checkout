<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://octonove.com
 * @since      1.0.0
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/admin
 * @author     Octonove <octonoveclientes@gmail.com>
 */
class Yd_Checkout_Admin {

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
	 * The tabs for the settings page.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $tabs    The tabs for the settings page.
	 */
	private $tabs;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Define tabs
		$this->tabs = array(
			'general' => array(
				'label' => __('General', 'yd-checkout'),
				'callback' => array($this, 'render_general_tab')
			),
			'payment' => array(
				'label' => __('Payment Gateways', 'yd-checkout'),
				'callback' => array($this, 'render_payment_tab')
			),
			'location' => array(
				'label' => __('Address & Location', 'yd-checkout'),
				'callback' => array($this, 'render_location_tab')
			),
			'checkout' => array(
				'label' => __('Checkout Flow', 'yd-checkout'),
				'callback' => array($this, 'render_checkout_tab')
			),
			'design' => array(
				'label' => __('Design', 'yd-checkout'),
				'callback' => array($this, 'render_design_tab')
			)
		);

		// Add settings page
		add_action('admin_menu', array($this, 'add_settings_page'));
		
		// Register settings
		add_action('admin_init', array($this, 'register_settings'));
		
		// Register AJAX handlers
		add_action('wp_ajax_yd_checkout_validate_stripe_credentials', array($this, 'validate_stripe_credentials'));
		add_action('wp_ajax_yd_checkout_validate_paypal_credentials', array($this, 'validate_paypal_credentials'));
		add_action('wp_ajax_yd_checkout_validate_here_credentials', array($this, 'validate_here_credentials'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();
		
		// Only load on plugin settings page
		if ($screen && strpos($screen->id, 'yd-checkout-settings') !== false) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/yd-checkout-admin-enhanced.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		
		// Only load on plugin settings page
		if ($screen && strpos($screen->id, 'yd-checkout-settings') !== false) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/yd-checkout-admin-enhanced.js', array( 'jquery' ), $this->version, false );
			
			// Pass variables to script
			wp_localize_script( $this->plugin_name, 'ydCheckoutAdmin', array(
				'nonce' => wp_create_nonce('yd_checkout_admin_nonce'),
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'i18n' => array(
					'validating' => __('Validating...', 'yd-checkout'),
					'success' => __('Validation successful!', 'yd-checkout'),
					'error' => __('Validation failed: ', 'yd-checkout'),
					'connectionError' => __('Connection error. Please try again.', 'yd-checkout'),
				)
			));
		}
	}

	/**
	 * Add the settings page to the WordPress admin menu.
	 *
	 * @since    1.0.0
	 */
	public function add_settings_page() {
		add_options_page(
			__('YDesign Checkout Settings', 'yd-checkout'),
			__('YDesign Checkout', 'yd-checkout'),
			'manage_options',
			'yd-checkout-settings',
			array($this, 'display_settings_page')
		);
	}

	/**
	 * Display the settings page content.
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		// Get current tab
		$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'location';
		
		// Ensure the tab is valid
		if (!isset($this->tabs[$active_tab])) {
			$active_tab = 'location';
		}
		
		?>
		<div class="wrap yd-checkout-admin-wrapper">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			
			<?php
			// Include tabs template
			include plugin_dir_path(__FILE__) . 'partials/yd-checkout-admin-tabs.php';
			
			// Render active tab content
			if (isset($this->tabs[$active_tab]['callback']) && is_callable($this->tabs[$active_tab]['callback'])) {
				call_user_func($this->tabs[$active_tab]['callback']);
			}
			?>
		</div>
		<?php
	}

	/**
	 * Register plugin settings.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		// Register general settings
		$this->register_general_settings();
		
		// Register payment gateway settings
		$this->register_stripe_settings();
		$this->register_paypal_settings();
		
		// Register location settings
		$this->register_location_settings();
		
		// // Register checkout flow settings
		// $this->register_checkout_settings();
		
		// // Register design settings
		// $this->register_design_settings();
	}

	/**
 * PARTE 2: Métodos de registro de configuraciones específicas
 */

	/**
	 * Register general settings.
	 *
	 * @since    1.0.0
	 */
	private function register_general_settings() {
		// Register general settings section
		add_settings_section(
			'yd_checkout_general_section',
			__('General Settings', 'yd-checkout'),
			array($this, 'render_general_section_description'),
			'yd_checkout_general_settings'
		);
		
		// Currency setting
		register_setting('yd_checkout_general_settings', 'yd_checkout_currency');
		add_settings_field(
			'yd_checkout_currency',
			__('Currency', 'yd-checkout'),
			array($this, 'render_currency_field'),
			'yd_checkout_general_settings',
			'yd_checkout_general_section'
		);
		
		// Enable/Disable checkout
		register_setting('yd_checkout_general_settings', 'yd_checkout_enabled');
		add_settings_field(
			'yd_checkout_enabled',
			__('Enable Checkout', 'yd-checkout'),
			array($this, 'render_checkout_enabled_field'),
			'yd_checkout_general_settings',
			'yd_checkout_general_section'
		);
		
		// Require login for checkout
		register_setting('yd_checkout_general_settings', 'yd_checkout_require_login');
		add_settings_field(
			'yd_checkout_require_login',
			__('Require Login', 'yd-checkout'),
			array($this, 'render_require_login_field'),
			'yd_checkout_general_settings',
			'yd_checkout_general_section'
		);
	}
	
	/**
	 * Register Stripe settings.
	 *
	 * @since    1.0.0
	 */
	private function register_stripe_settings() {
		// Register Stripe settings section
		add_settings_section(
			'yd_checkout_stripe_section',
			__('Stripe Settings', 'yd-checkout'),
			array($this, 'render_stripe_section_description'),
			'yd_checkout_stripe_settings'
		);
		
		// Enable/Disable Stripe
		register_setting('yd_checkout_stripe_settings', 'yd_checkout_stripe_enabled');
		add_settings_field(
			'yd_checkout_stripe_enabled',
			__('Enable Stripe', 'yd-checkout'),
			array($this, 'render_stripe_enabled_field'),
			'yd_checkout_stripe_settings',
			'yd_checkout_stripe_section'
		);
		
		// Test mode toggle
		register_setting('yd_checkout_stripe_settings', 'yd_checkout_stripe_test_mode');
		add_settings_field(
			'yd_checkout_stripe_test_mode',
			__('Environment', 'yd-checkout'),
			array($this, 'render_stripe_test_mode_field'),
			'yd_checkout_stripe_settings',
			'yd_checkout_stripe_section'
		);
		
		// Test publishable key
		register_setting('yd_checkout_stripe_settings', 'yd_checkout_stripe_test_publishable_key');
		add_settings_field(
			'yd_checkout_stripe_test_publishable_key',
			__('Test Publishable Key', 'yd-checkout'),
			array($this, 'render_stripe_test_publishable_key_field'),
			'yd_checkout_stripe_settings',
			'yd_checkout_stripe_section'
		);
		
		// Test secret key
		register_setting('yd_checkout_stripe_settings', 'yd_checkout_stripe_test_secret_key');
		add_settings_field(
			'yd_checkout_stripe_test_secret_key',
			__('Test Secret Key', 'yd-checkout'),
			array($this, 'render_stripe_test_secret_key_field'),
			'yd_checkout_stripe_settings',
			'yd_checkout_stripe_section'
		);
		
		// Test webhook secret
		register_setting('yd_checkout_stripe_settings', 'yd_checkout_stripe_test_webhook_secret');
		add_settings_field(
			'yd_checkout_stripe_test_webhook_secret',
			__('Test Webhook Secret', 'yd-checkout'),
			array($this, 'render_stripe_test_webhook_secret_field'),
			'yd_checkout_stripe_settings',
			'yd_checkout_stripe_section'
		);
		
		// Live publishable key
		register_setting('yd_checkout_stripe_settings', 'yd_checkout_stripe_live_publishable_key');
		add_settings_field(
			'yd_checkout_stripe_live_publishable_key',
			__('Live Publishable Key', 'yd-checkout'),
			array($this, 'render_stripe_live_publishable_key_field'),
			'yd_checkout_stripe_settings',
			'yd_checkout_stripe_section'
		);
		
		// Live secret key
		register_setting('yd_checkout_stripe_settings', 'yd_checkout_stripe_live_secret_key');
		add_settings_field(
			'yd_checkout_stripe_live_secret_key',
			__('Live Secret Key', 'yd-checkout'),
			array($this, 'render_stripe_live_secret_key_field'),
			'yd_checkout_stripe_settings',
			'yd_checkout_stripe_section'
		);
		
		// Live webhook secret
		register_setting('yd_checkout_stripe_settings', 'yd_checkout_stripe_live_webhook_secret');
		add_settings_field(
			'yd_checkout_stripe_live_webhook_secret',
			__('Live Webhook Secret', 'yd-checkout'),
			array($this, 'render_stripe_live_webhook_secret_field'),
			'yd_checkout_stripe_settings',
			'yd_checkout_stripe_section'
		);
		
		// Webhook URL (readonly)
		add_settings_field(
			'yd_checkout_stripe_webhook_url',
			__('Webhook URL', 'yd-checkout'),
			array($this, 'render_stripe_webhook_url_field'),
			'yd_checkout_stripe_settings',
			'yd_checkout_stripe_section'
		);
		
		// Debug logging
		register_setting('yd_checkout_stripe_settings', 'yd_checkout_stripe_debug');
		add_settings_field(
			'yd_checkout_stripe_debug',
			__('Debug Log', 'yd-checkout'),
			array($this, 'render_stripe_debug_field'),
			'yd_checkout_stripe_settings',
			'yd_checkout_stripe_section'
		);
	}
	
	/**
	 * Register PayPal settings.
	 *
	 * @since    1.0.0
	 */
	public  function register_paypal_settings() {
		// Register PayPal settings section
		add_settings_section(
			'yd_checkout_paypal_section',
			__('PayPal Settings', 'yd-checkout'),
			array($this, 'render_paypal_section_description'),
			'yd_checkout_paypal_settings'
		);
		
		// Enable/Disable PayPal
		register_setting('yd_checkout_paypal_settings', 'yd_checkout_paypal_enabled');
		add_settings_field(
			'yd_checkout_paypal_enabled',
			__('Enable PayPal', 'yd-checkout'),
			array($this, 'render_paypal_enabled_field'),
			'yd_checkout_paypal_settings',
			'yd_checkout_paypal_section'
		);
		
		// Test mode toggle
		register_setting('yd_checkout_paypal_settings', 'yd_checkout_paypal_test_mode');
		add_settings_field(
			'yd_checkout_paypal_test_mode',
			__('Environment', 'yd-checkout'),
			array($this, 'render_paypal_test_mode_field'),
			'yd_checkout_paypal_settings',
			'yd_checkout_paypal_section'
		);
		
		// Sandbox client ID
		register_setting('yd_checkout_paypal_settings', 'yd_checkout_paypal_sandbox_client_id');
		add_settings_field(
			'yd_checkout_paypal_sandbox_client_id',
			__('Sandbox Client ID', 'yd-checkout'),
			array($this, 'render_paypal_sandbox_client_id_field'),
			'yd_checkout_paypal_settings',
			'yd_checkout_paypal_section'
		);
		
		// Sandbox client secret
		register_setting('yd_checkout_paypal_settings', 'yd_checkout_paypal_sandbox_client_secret');
		add_settings_field(
			'yd_checkout_paypal_sandbox_client_secret',
			__('Sandbox Client Secret', 'yd-checkout'),
			array($this, 'render_paypal_sandbox_client_secret_field'),
			'yd_checkout_paypal_settings',
			'yd_checkout_paypal_section'
		);
		
		// Sandbox webhook ID
		register_setting('yd_checkout_paypal_settings', 'yd_checkout_paypal_sandbox_webhook_id');
		add_settings_field(
			'yd_checkout_paypal_sandbox_webhook_id',
			__('Sandbox Webhook ID', 'yd-checkout'),
			array($this, 'render_paypal_sandbox_webhook_id_field'),
			'yd_checkout_paypal_settings',
			'yd_checkout_paypal_section'
		);
		
		// Live client ID
		register_setting('yd_checkout_paypal_settings', 'yd_checkout_paypal_live_client_id');
		add_settings_field(
			'yd_checkout_paypal_live_client_id',
			__('Live Client ID', 'yd-checkout'),
			array($this, 'render_paypal_live_client_id_field'),
			'yd_checkout_paypal_settings',
			'yd_checkout_paypal_section'
		);
		
		// Live client secret
		register_setting('yd_checkout_paypal_settings', 'yd_checkout_paypal_live_client_secret');
		add_settings_field(
			'yd_checkout_paypal_live_client_secret',
			__('Live Client Secret', 'yd-checkout'),
			array($this, 'render_paypal_live_client_secret_field'),
			'yd_checkout_paypal_settings',
			'yd_checkout_paypal_section'
		);
		
		// Live webhook ID
		register_setting('yd_checkout_paypal_settings', 'yd_checkout_paypal_live_webhook_id');
		add_settings_field(
			'yd_checkout_paypal_live_webhook_id',
			__('Live Webhook ID', 'yd-checkout'),
			array($this, 'render_paypal_live_webhook_id_field'),
			'yd_checkout_paypal_settings',
			'yd_checkout_paypal_section'
		);
		
		// Webhook URL (readonly)
		add_settings_field(
			'yd_checkout_paypal_webhook_url',
			__('Webhook URL', 'yd-checkout'),
			array($this, 'render_paypal_webhook_url_field'),
			'yd_checkout_paypal_settings',
			'yd_checkout_paypal_section'
		);
		
		// Debug logging
		register_setting('yd_checkout_paypal_settings', 'yd_checkout_paypal_debug');
		add_settings_field(
			'yd_checkout_paypal_debug',
			__('Debug Log', 'yd-checkout'),
			array($this, 'render_paypal_debug_field'),
			'yd_checkout_paypal_settings',
			'yd_checkout_paypal_section'
		);
	}
	
	/**
	 * Register location settings.
	 *
	 * @since    1.0.0
	 */
	private function register_location_settings() {
		// Register location settings section
		add_settings_section(
			'yd_checkout_location_section',
			__('Address & Location Settings', 'yd-checkout'),
			array($this, 'render_location_section_description'),
			'yd_checkout_location_settings'
		);
		
		// HERE API key
		register_setting('yd_checkout_location_settings', 'yd_checkout_here_api_key');
		add_settings_field(
			'yd_checkout_here_api_key',
			__('HERE API Key', 'yd-checkout'),
			array($this, 'render_here_api_key_field'),
			'yd_checkout_location_settings',
			'yd_checkout_location_section'
		);
		
		// Enable address autocomplete
		register_setting('yd_checkout_location_settings', 'yd_checkout_enable_address_autocomplete');
		add_settings_field(
			'yd_checkout_enable_address_autocomplete',
			__('Address Autocomplete', 'yd-checkout'),
			array($this, 'render_enable_address_autocomplete_field'),
			'yd_checkout_location_settings',
			'yd_checkout_location_section'
		);
		
		// Default country
		register_setting('yd_checkout_location_settings', 'yd_checkout_default_country');
		add_settings_field(
			'yd_checkout_default_country',
			__('Default Country', 'yd-checkout'),
			array($this, 'render_default_country_field'),
			'yd_checkout_location_settings',
			'yd_checkout_location_section'
		);
	}
	
	/**
	 * Register checkout flow settings.
	 *
	 * @since    1.0.0
	 */
	private function register_checkout_settings() {
		// Register checkout settings section
		add_settings_section(
			'yd_checkout_checkout_section',
			__('Checkout Flow Settings', 'yd-checkout'),
			array($this, 'render_checkout_section_description'),
			'yd_checkout_checkout_settings'
		);
		
		// Allow guest checkout
		register_setting('yd_checkout_checkout_settings', 'yd_checkout_allow_guest_checkout');
		add_settings_field(
			'yd_checkout_allow_guest_checkout',
			__('Guest Checkout', 'yd-checkout'),
			array($this, 'render_allow_guest_checkout_field'),
			'yd_checkout_checkout_settings',
			'yd_checkout_checkout_section'
		);
		
		// Required fields
		register_setting('yd_checkout_checkout_settings', 'yd_checkout_required_fields');
		add_settings_field(
			'yd_checkout_required_fields',
			__('Required Fields', 'yd-checkout'),
			array($this, 'render_required_fields_field'),
			'yd_checkout_checkout_settings',
			'yd_checkout_checkout_section'
		);
		
		// Default address type
		register_setting('yd_checkout_checkout_settings', 'yd_checkout_default_address_type');
		add_settings_field(
			'yd_checkout_default_address_type',
			__('Default Address Type', 'yd-checkout'),
			array($this, 'render_default_address_type_field'),
			'yd_checkout_checkout_settings',
			'yd_checkout_checkout_section'
		);
		
		// Terms and conditions
		register_setting('yd_checkout_checkout_settings', 'yd_checkout_terms_page');
		add_settings_field(
			'yd_checkout_terms_page',
			__('Terms & Conditions Page', 'yd-checkout'),
			array($this, 'render_terms_page_field'),
			'yd_checkout_checkout_settings',
			'yd_checkout_checkout_section'
		);
		
		// Success page
		register_setting('yd_checkout_checkout_settings', 'yd_checkout_success_page');
		add_settings_field(
			'yd_checkout_success_page',
			__('Success Page', 'yd-checkout'),
			array($this, 'render_success_page_field'),
			'yd_checkout_checkout_settings',
			'yd_checkout_checkout_section'
		);
	}
	
	/**
	 * Register design settings.
	 *
	 * @since    1.0.0
	 */
	private function register_design_settings() {
		// Register design settings section
		add_settings_section(
			'yd_checkout_design_section',
			__('Design Settings', 'yd-checkout'),
			array($this, 'render_design_section_description'),
			'yd_checkout_design_settings'
		);
		
		// Color scheme
		register_setting('yd_checkout_design_settings', 'yd_checkout_color_scheme');
		add_settings_field(
			'yd_checkout_color_scheme',
			__('Color Scheme', 'yd-checkout'),
			array($this, 'render_color_scheme_field'),
			'yd_checkout_design_settings',
			'yd_checkout_design_section'
		);
		
		// Primary color
		register_setting('yd_checkout_design_settings', 'yd_checkout_primary_color');
		add_settings_field(
			'yd_checkout_primary_color',
			__('Primary Color', 'yd-checkout'),
			array($this, 'render_primary_color_field'),
			'yd_checkout_design_settings',
			'yd_checkout_design_section'
		);
		
		// Button style
		register_setting('yd_checkout_design_settings', 'yd_checkout_button_style');
		add_settings_field(
			'yd_checkout_button_style',
			__('Button Style', 'yd-checkout'),
			array($this, 'render_button_style_field'),
			'yd_checkout_design_settings',
			'yd_checkout_design_section'
		);
		
		// Custom CSS
		register_setting('yd_checkout_design_settings', 'yd_checkout_custom_css');
		add_settings_field(
			'yd_checkout_custom_css',
			__('Custom CSS', 'yd-checkout'),
			array($this, 'render_custom_css_field'),
			'yd_checkout_design_settings',
			'yd_checkout_design_section'
		);
	}

	/**
 * PARTE 3: Métodos de renderizado de UI y campos básicos
 */

	/**
	 * Render the general tab content.
	 *
	 * @since    1.0.0
	 */
	public function render_general_tab() {
		include plugin_dir_path(__FILE__) . 'partials/yd-checkout-admin-general.php';
	}
	
	/**
	 * Render the payment tab content.
	 *
	 * @since    1.0.0
	 */
	public function render_payment_tab() {
		include plugin_dir_path(__FILE__) . 'partials/yd-checkout-admin-payment.php';
	}
	
	/**
	 * Render the location tab content.
	 *
	 * @since    1.0.0
	 */
	public function render_location_tab() {
		include plugin_dir_path(__FILE__) . 'partials/yd-checkout-admin-location.php';
	}
	
	/**
	 * Render the checkout tab content.
	 *
	 * @since    1.0.0
	 */
	public function render_checkout_tab() {
		include plugin_dir_path(__FILE__) . 'partials/yd-checkout-admin-checkout.php';
	}
	
	/**
	 * Render the design tab content.
	 *
	 * @since    1.0.0
	 */
	public function render_design_tab() {
		include plugin_dir_path(__FILE__) . 'partials/yd-checkout-admin-design.php';
	}
	
	/**
	 * Render general section description.
	 * 
	 * @since    1.0.0
	 */
	public function render_general_section_description() {
		echo '<p>' . __('Configure general settings for the YDesign Checkout system.', 'yd-checkout') . '</p>';
	}
	
	/**
	 * Render Stripe section description.
	 * 
	 * @since    1.0.0
	 */
	public function render_stripe_section_description() {
		echo '<p>' . __('Configure Stripe payment gateway settings for credit card processing.', 'yd-checkout') . '</p>';
	}
	
	/**
	 * Render PayPal section description.
	 * 
	 * @since    1.0.0
	 */
	public function render_paypal_section_description() {
		echo '<p>' . __('Configure PayPal payment gateway settings for PayPal payments.', 'yd-checkout') . '</p>';
	}
	
	/**
	 * Render location section description.
	 * 
	 * @since    1.0.0
	 */
	public function render_location_section_description() {
		echo '<p>' . __('Configure address and location settings for the checkout process.', 'yd-checkout') . '</p>';
	}
	
	/**
	 * Render checkout section description.
	 * 
	 * @since    1.0.0
	 */
	public function render_checkout_section_description() {
		echo '<p>' . __('Configure checkout flow and process settings.', 'yd-checkout') . '</p>';
	}
	
	/**
	 * Render design section description.
	 * 
	 * @since    1.0.0
	 */
	public function render_design_section_description() {
		echo '<p>' . __('Configure design and appearance settings for the checkout form.', 'yd-checkout') . '</p>';
	}
	
	/**
	 * Render currency field.
	 * 
	 * @since    1.0.0
	 */
	public function render_currency_field() {
		$currency = get_option('yd_checkout_currency', 'USD');
		?>
		<select name="yd_checkout_currency">
			<option value="USD" <?php selected('USD', $currency); ?>>USD - US Dollar</option>
			<option value="EUR" <?php selected('EUR', $currency); ?>>EUR - Euro</option>
			<option value="GBP" <?php selected('GBP', $currency); ?>>GBP - British Pound</option>
			<option value="CAD" <?php selected('CAD', $currency); ?>>CAD - Canadian Dollar</option>
			<option value="AUD" <?php selected('AUD', $currency); ?>>AUD - Australian Dollar</option>
			<option value="JPY" <?php selected('JPY', $currency); ?>>JPY - Japanese Yen</option>
		</select>
		<p class="description"><?php _e('Select the currency to use for payments.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render checkout enabled field.
	 * 
	 * @since    1.0.0
	 */
	public function render_checkout_enabled_field() {
		$enabled = get_option('yd_checkout_enabled', 'yes');
		?>
		<label>
			<input type="checkbox" name="yd_checkout_enabled" value="yes" <?php checked('yes', $enabled); ?> />
			<?php _e('Enable YDesign Checkout system', 'yd-checkout'); ?>
		</label>
		<p class="description"><?php _e('Enable or disable the checkout system globally.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render require login field.
	 * 
	 * @since    1.0.0
	 */
	public function render_require_login_field() {
		$require_login = get_option('yd_checkout_require_login', 'no');
		?>
		<label>
			<input type="checkbox" name="yd_checkout_require_login" value="yes" <?php checked('yes', $require_login); ?> />
			<?php _e('Require users to be logged in to checkout', 'yd-checkout'); ?>
		</label>
		<p class="description"><?php _e('If enabled, users must be logged in to access the checkout.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render Stripe enabled field.
	 * 
	 * @since    1.0.0
	 */
	public function render_stripe_enabled_field() {
		$enabled = get_option('yd_checkout_stripe_enabled', 'yes');
		?>
		<label>
			<input type="checkbox" name="yd_checkout_stripe_enabled" value="yes" <?php checked('yes', $enabled); ?> />
			<?php _e('Enable Stripe payment gateway', 'yd-checkout'); ?>
		</label>
		<p class="description"><?php _e('Enable or disable Stripe payment processing.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render Stripe test mode field.
	 * 
	 * @since    1.0.0
	 */
	public function render_stripe_test_mode_field() {
		$test_mode = get_option('yd_checkout_stripe_test_mode', 'yes');
		?>
		<div class="yd-checkout-toggle-container">
			<label class="yd-checkout-toggle">
				<input type="checkbox" name="yd_checkout_stripe_test_mode" value="yes" <?php checked('yes', $test_mode); ?> class="yd-checkout-toggle-checkbox yd-checkout-environment-toggle" data-section="stripe" id="stripe-test-mode-toggle">
				<span class="yd-checkout-toggle-slider"></span>
			</label>
			<span class="yd-checkout-toggle-label">
				<span class="yd-checkout-toggle-on <?php echo $test_mode === 'yes' ? 'active' : ''; ?>"><?php _e('Test', 'yd-checkout'); ?></span>
				<span class="yd-checkout-toggle-off <?php echo $test_mode !== 'yes' ? 'active' : ''; ?>"><?php _e('Live', 'yd-checkout'); ?></span>
			</span>
		</div>
		<p class="description">
			<?php _e('Toggle between test and live environments. Use test mode for development and testing.', 'yd-checkout'); ?>
		</p>
		<?php
	}
	
	/**
	 * Render Stripe test publishable key field.
	 * 
	 * @since    1.0.0
	 */
	public function render_stripe_test_publishable_key_field() {
		$key = get_option('yd_checkout_stripe_test_publishable_key', '');
		?>
		<div class="yd-checkout-stripe-test-field">
			<input type="text" name="yd_checkout_stripe_test_publishable_key" id="yd_checkout_stripe_test_publishable_key" value="<?php echo esc_attr($key); ?>" class="regular-text" />
			<p class="description"><?php _e('Enter your Stripe test publishable key, which starts with "pk_test_".', 'yd-checkout'); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Render Stripe test secret key field.
	 * 
	 * @since    1.0.0
	 */
	public function render_stripe_test_secret_key_field() {
		$key = get_option('yd_checkout_stripe_test_secret_key', '');
		?>
		<div class="yd-checkout-stripe-test-field">
			<input type="password" name="yd_checkout_stripe_test_secret_key" id="yd_checkout_stripe_test_secret_key" value="<?php echo esc_attr($key); ?>" class="regular-text" />
			<p class="description"><?php _e('Enter your Stripe test secret key, which starts with "sk_test_".', 'yd-checkout'); ?></p>
			<button type="button" class="button yd-checkout-validate-credentials" data-gateway="stripe" data-environment="test">
				<?php _e('Validate Test Credentials', 'yd-checkout'); ?>
			</button>
		</div>
		<?php
	}
	
	/**
	 * Render Stripe test webhook secret field.
	 * 
	 * @since    1.0.0
	 */
	public function render_stripe_test_webhook_secret_field() {
		$secret = get_option('yd_checkout_stripe_test_webhook_secret', '');
		?>
		<div class="yd-checkout-stripe-test-field">
			<input type="password" name="yd_checkout_stripe_test_webhook_secret" value="<?php echo esc_attr($secret); ?>" class="regular-text" />
			<p class="description"><?php _e('Enter your Stripe test webhook signing secret, which starts with "whsec_".', 'yd-checkout'); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Render Stripe live publishable key field.
	 * 
	 * @since    1.0.0
	 */
	public function render_stripe_live_publishable_key_field() {
		$key = get_option('yd_checkout_stripe_live_publishable_key', '');
		?>
		<div class="yd-checkout-stripe-live-field">
			<input type="text" name="yd_checkout_stripe_live_publishable_key" id="yd_checkout_stripe_live_publishable_key" value="<?php echo esc_attr($key); ?>" class="regular-text" />
			<p class="description"><?php _e('Enter your Stripe live publishable key, which starts with "pk_live_".', 'yd-checkout'); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Render Stripe live secret key field.
	 * 
	 * @since    1.0.0
	 */
	public function render_stripe_live_secret_key_field() {
		$key = get_option('yd_checkout_stripe_live_secret_key', '');
		?>
		<div class="yd-checkout-stripe-live-field">
			<input type="password" name="yd_checkout_stripe_live_secret_key" id="yd_checkout_stripe_live_secret_key" value="<?php echo esc_attr($key); ?>" class="regular-text" />
			<p class="description"><?php _e('Enter your Stripe live secret key, which starts with "sk_live_".', 'yd-checkout'); ?></p>
			<button type="button" class="button yd-checkout-validate-credentials" data-gateway="stripe" data-environment="live">
				<?php _e('Validate Live Credentials', 'yd-checkout'); ?>
			</button>
		</div>
		<?php
	}

	/**
 * PARTE 4: Campos de configuración avanzados y validación
 */

	/**
	 * Render Stripe live webhook secret field.
	 * 
	 * @since    1.0.0
	 */
	public function render_stripe_live_webhook_secret_field() {
		$secret = get_option('yd_checkout_stripe_live_webhook_secret', '');
		?>
		<div class="yd-checkout-stripe-live-field">
			<input type="password" name="yd_checkout_stripe_live_webhook_secret" value="<?php echo esc_attr($secret); ?>" class="regular-text" />
			<p class="description"><?php _e('Enter your Stripe live webhook signing secret, which starts with "whsec_".', 'yd-checkout'); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Render Stripe webhook URL field.
	 * 
	 * @since    1.0.0
	 */
	public function render_stripe_webhook_url_field() {
		$webhook_url = add_query_arg(
			array(
				'yd-checkout-webhook' => '1',
				'gateway' => 'stripe',
			),
			home_url('/')
		);
		?>
		<input type="text" value="<?php echo esc_url($webhook_url); ?>" class="regular-text" readonly />
		<button type="button" class="button" onclick="copyWebhookUrl(this)">
			<?php _e('Copy', 'yd-checkout'); ?>
		</button>
		<p class="description"><?php _e('Use this URL when setting up webhooks in your Stripe dashboard.', 'yd-checkout'); ?></p>
		<script>
			function copyWebhookUrl(button) {
				const input = button.previousElementSibling;
				input.select();
				document.execCommand('copy');
				
				const originalText = button.textContent;
				button.textContent = '<?php _e('Copied!', 'yd-checkout'); ?>';
				
				setTimeout(() => {
					button.textContent = originalText;
				}, 2000);
			}
		</script>
		<?php
	}
	
	/**
	 * Render Stripe debug field.
	 * 
	 * @since    1.0.0
	 */
	public function render_stripe_debug_field() {
		$debug = get_option('yd_checkout_stripe_debug', 'no');
		?>
		<label>
			<input type="checkbox" name="yd_checkout_stripe_debug" value="yes" <?php checked('yes', $debug); ?> />
			<?php _e('Enable detailed logging for Stripe', 'yd-checkout'); ?>
		</label>
		<p class="description"><?php _e('Enable this to log detailed information about Stripe API interactions for debugging purposes.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render PayPal enabled field.
	 * 
	 * @since    1.0.0
	 */
	public function render_paypal_enabled_field() {
		$enabled = get_option('yd_checkout_paypal_enabled', 'yes');
		?>
		<label>
			<input type="checkbox" name="yd_checkout_paypal_enabled" value="yes" <?php checked('yes', $enabled); ?> />
			<?php _e('Enable PayPal payment gateway', 'yd-checkout'); ?>
		</label>
		<p class="description"><?php _e('Enable or disable PayPal payment processing.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render PayPal test mode field.
	 * 
	 * @since    1.0.0
	 */
	public function render_paypal_test_mode_field() {
		$test_mode = get_option('yd_checkout_paypal_test_mode', 'yes');
		?>
		<div class="yd-checkout-toggle-container">
			<label class="yd-checkout-toggle">
				<input type="checkbox" name="yd_checkout_paypal_test_mode" value="yes" <?php checked('yes', $test_mode); ?> class="yd-checkout-toggle-checkbox yd-checkout-environment-toggle" data-section="paypal" id="paypal-test-mode-toggle">
				<span class="yd-checkout-toggle-slider"></span>
			</label>
			<span class="yd-checkout-toggle-label">
				<span class="yd-checkout-toggle-on <?php echo $test_mode === 'yes' ? 'active' : ''; ?>"><?php _e('Sandbox', 'yd-checkout'); ?></span>
				<span class="yd-checkout-toggle-off <?php echo $test_mode !== 'yes' ? 'active' : ''; ?>"><?php _e('Live', 'yd-checkout'); ?></span>
			</span>
		</div>
		<p class="description">
			<?php _e('Toggle between sandbox and live environments. Use sandbox mode for development and testing.', 'yd-checkout'); ?>
		</p>
		<?php
	}
	
	/**
	 * Render PayPal sandbox client ID field.
	 * 
	 * @since    1.0.0
	 */
	public function render_paypal_sandbox_client_id_field() {
		$client_id = get_option('yd_checkout_paypal_sandbox_client_id', '');
		?>
		<div class="yd-checkout-paypal-test-field">
			<input type="text" name="yd_checkout_paypal_sandbox_client_id" id="yd_checkout_paypal_sandbox_client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text" />
			<p class="description"><?php _e('Enter your PayPal sandbox client ID.', 'yd-checkout'); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Render PayPal sandbox client secret field.
	 * 
	 * @since    1.0.0
	 */
	public function render_paypal_sandbox_client_secret_field() {
		$client_secret = get_option('yd_checkout_paypal_sandbox_client_secret', '');
		?>
		<div class="yd-checkout-paypal-test-field">
			<input type="password" name="yd_checkout_paypal_sandbox_client_secret" id="yd_checkout_paypal_sandbox_client_secret" value="<?php echo esc_attr($client_secret); ?>" class="regular-text" />
			<p class="description"><?php _e('Enter your PayPal sandbox client secret.', 'yd-checkout'); ?></p>
			<button type="button" class="button yd-checkout-validate-credentials" data-gateway="paypal" data-environment="sandbox">
				<?php _e('Validate Sandbox Credentials', 'yd-checkout'); ?>
			</button>
		</div>
		<?php
	}
	
	/**
	 * Render PayPal sandbox webhook ID field.
	 * 
	 * @since    1.0.0
	 */
	public function render_paypal_sandbox_webhook_id_field() {
		$webhook_id = get_option('yd_checkout_paypal_sandbox_webhook_id', '');
		?>
		<div class="yd-checkout-paypal-test-field">
			<input type="text" name="yd_checkout_paypal_sandbox_webhook_id" value="<?php echo esc_attr($webhook_id); ?>" class="regular-text" />
			<p class="description"><?php _e('Enter your PayPal sandbox webhook ID.', 'yd-checkout'); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Render PayPal live client ID field.
	 * 
	 * @since    1.0.0
	 */
	public function render_paypal_live_client_id_field() {
		$client_id = get_option('yd_checkout_paypal_live_client_id', '');
		?>
		<div class="yd-checkout-paypal-live-field">
			<input type="text" name="yd_checkout_paypal_live_client_id" id="yd_checkout_paypal_live_client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text" />
			<p class="description"><?php _e('Enter your PayPal live client ID.', 'yd-checkout'); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Render PayPal live client secret field.
	 * 
	 * @since    1.0.0
	 */
	public function render_paypal_live_client_secret_field() {
		$client_secret = get_option('yd_checkout_paypal_live_client_secret', '');
		?>
		<div class="yd-checkout-paypal-live-field">
			<input type="password" name="yd_checkout_paypal_live_client_secret" id="yd_checkout_paypal_live_client_secret" value="<?php echo esc_attr($client_secret); ?>" class="regular-text" />
			<p class="description"><?php _e('Enter your PayPal live client secret.', 'yd-checkout'); ?></p>
			<button type="button" class="button yd-checkout-validate-credentials" data-gateway="paypal" data-environment="live">
				<?php _e('Validate Live Credentials', 'yd-checkout'); ?>
			</button>
		</div>
		<?php
	}
	
	/**
	 * Render PayPal live webhook ID field.
	 * 
	 * @since    1.0.0
	 */
	public function render_paypal_live_webhook_id_field() {
		$webhook_id = get_option('yd_checkout_paypal_live_webhook_id', '');
		?>
		<div class="yd-checkout-paypal-live-field">
			<input type="text" name="yd_checkout_paypal_live_webhook_id" value="<?php echo esc_attr($webhook_id); ?>" class="regular-text" />
			<p class="description"><?php _e('Enter your PayPal live webhook ID.', 'yd-checkout'); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Render PayPal webhook URL field.
	 * 
	 * @since    1.0.0
	 */
	public function render_paypal_webhook_url_field() {
		$webhook_url = add_query_arg(
			array(
				'yd-checkout-webhook' => '1',
				'gateway' => 'paypal',
			),
			home_url('/')
		);
		?>
		<input type="text" value="<?php echo esc_url($webhook_url); ?>" class="regular-text" readonly />
		<button type="button" class="button" onclick="copyWebhookUrl(this)">
			<?php _e('Copy', 'yd-checkout'); ?>
		</button>
		<p class="description"><?php _e('Use this URL when setting up webhooks in your PayPal dashboard.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render PayPal debug field.
	 * 
	 * @since    1.0.0
	 */
	public function render_paypal_debug_field() {
		$debug = get_option('yd_checkout_paypal_debug', 'no');
		?>
		<label>
			<input type="checkbox" name="yd_checkout_paypal_debug" value="yes" <?php checked('yes', $debug); ?> />
			<?php _e('Enable detailed logging for PayPal', 'yd-checkout'); ?>
		</label>
		<p class="description"><?php _e('Enable this to log detailed information about PayPal API interactions for debugging purposes.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render HERE API key field.
	 * 
	 * @since    1.0.0
	 */
	public function render_here_api_key_field() {
		$api_key = get_option('yd_checkout_here_api_key', '');
		?>
		<input type="text" name="yd_checkout_here_api_key" id="yd_checkout_here_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
		<p class="description"><?php _e('Enter your HERE API key for address autocomplete and geocoding.', 'yd-checkout'); ?></p>
		<button type="button" class="button yd-checkout-validate-credentials" data-gateway="here" data-environment="api">
			<?php _e('Validate API Key', 'yd-checkout'); ?>
		</button>
		<?php
	}
	
	/**
	 * Render enable address autocomplete field.
	 * 
	 * @since    1.0.0
	 */
	public function render_enable_address_autocomplete_field() {
		$enabled = get_option('yd_checkout_enable_address_autocomplete', 'yes');
		?>
		<label>
			<input type="checkbox" name="yd_checkout_enable_address_autocomplete" value="yes" <?php checked('yes', $enabled); ?> />
			<?php _e('Enable address autocomplete on checkout form', 'yd-checkout'); ?>
		</label>
		<p class="description"><?php _e('Use HERE API to provide address autocomplete functionality.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render default country field.
	 * 
	 * @since    1.0.0
	 */
	public function render_default_country_field() {
		$default_country = get_option('yd_checkout_default_country', 'US');
		$countries = array(
			'US' => __('United States', 'yd-checkout'),
			'CA' => __('Canada', 'yd-checkout'),
			'GB' => __('United Kingdom', 'yd-checkout'),
			'DE' => __('Germany', 'yd-checkout'),
			'FR' => __('France', 'yd-checkout'),
			'ES' => __('Spain', 'yd-checkout'),
			'IT' => __('Italy', 'yd-checkout'),
		);
		?>
		<select name="yd_checkout_default_country">
			<?php foreach ($countries as $code => $name): ?>
				<option value="<?php echo esc_attr($code); ?>" <?php selected($code, $default_country); ?>><?php echo esc_html($name); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php _e('Select the default country for address fields.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render allow guest checkout field.
	 * 
	 * @since    1.0.0
	 */
	public function render_allow_guest_checkout_field() {
		$allowed = get_option('yd_checkout_allow_guest_checkout', 'yes');
		?>
		<label>
			<input type="checkbox" name="yd_checkout_allow_guest_checkout" value="yes" <?php checked('yes', $allowed); ?> />
			<?php _e('Allow checkout without creating an account', 'yd-checkout'); ?>
		</label>
		<p class="description"><?php _e('Enable this to allow users to checkout as guests.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render required fields field.
	 * 
	 * @since    1.0.0
	 */
	public function render_required_fields_field() {
		$required_fields = get_option('yd_checkout_required_fields', array(
			'first_name', 'last_name', 'email', 'address_line1', 'city', 'postal_code', 'country'
		));
		
		$available_fields = array(
			'first_name' => __('First Name', 'yd-checkout'),
			'last_name' => __('Last Name', 'yd-checkout'),
			'email' => __('Email', 'yd-checkout'),
			'phone' => __('Phone', 'yd-checkout'),
			'address_line1' => __('Address Line 1', 'yd-checkout'),
			'address_line2' => __('Address Line 2', 'yd-checkout'),
			'city' => __('City', 'yd-checkout'),
			'state' => __('State/Province', 'yd-checkout'),
			'postal_code' => __('Postal Code', 'yd-checkout'),
			'country' => __('Country', 'yd-checkout'),
		);
		
		if (!is_array($required_fields)) {
			$required_fields = array();
		}
		
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php _e('Required Fields', 'yd-checkout'); ?></legend>
			<?php foreach ($available_fields as $field_key => $field_label): ?>
				<label>
					<input type="checkbox" name="yd_checkout_required_fields[]" value="<?php echo esc_attr($field_key); ?>" <?php checked(in_array($field_key, $required_fields), true); ?> />
					<?php echo esc_html($field_label); ?>
				</label><br>
			<?php endforeach; ?>
		</fieldset>
		<p class="description"><?php _e('Select which fields should be required during checkout.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render default address type field.
	 * 
	 * @since    1.0.0
	 */
	public function render_default_address_type_field() {
		$default_type = get_option('yd_checkout_default_address_type', 'shipping');
		?>
		<select name="yd_checkout_default_address_type">
			<option value="shipping" <?php selected('shipping', $default_type); ?>><?php _e('Shipping', 'yd-checkout'); ?></option>
			<option value="billing" <?php selected('billing', $default_type); ?>><?php _e('Billing', 'yd-checkout'); ?></option>
		</select>
		<p class="description"><?php _e('Select the default address type to display first.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render terms page field.
	 * 
	 * @since    1.0.0
	 */
	public function render_terms_page_field() {
		$terms_page_id = get_option('yd_checkout_terms_page', 0);
		wp_dropdown_pages(array(
			'name' => 'yd_checkout_terms_page',
			'selected' => $terms_page_id,
			'show_option_none' => __('Select a page', 'yd-checkout'),
		));
		?>
		<p class="description"><?php _e('Select the page that contains your terms and conditions.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render success page field.
	 * 
	 * @since    1.0.0
	 */
	public function render_success_page_field() {
		$success_page_id = get_option('yd_checkout_success_page', 0);
		wp_dropdown_pages(array(
			'name' => 'yd_checkout_success_page',
			'selected' => $success_page_id,
			'show_option_none' => __('Select a page', 'yd-checkout'),
		));
		?>
		<p class="description"><?php _e('Select the page to redirect to after successful checkout.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render color scheme field.
	 * 
	 * @since    1.0.0
	 */
	public function render_color_scheme_field() {
		$color_scheme = get_option('yd_checkout_color_scheme', 'default');
		?>
		<select name="yd_checkout_color_scheme">
			<option value="default" <?php selected('default', $color_scheme); ?>><?php _e('Default (Theme Colors)', 'yd-checkout'); ?></option>
			<option value="light" <?php selected('light', $color_scheme); ?>><?php _e('Light', 'yd-checkout'); ?></option>
			<option value="dark" <?php selected('dark', $color_scheme); ?>><?php _e('Dark', 'yd-checkout'); ?></option>
			<option value="custom" <?php selected('custom', $color_scheme); ?>><?php _e('Custom', 'yd-checkout'); ?></option>
		</select>
		<p class="description"><?php _e('Select the color scheme for the checkout form.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render primary color field.
	 * 
	 * @since    1.0.0
	 */
	public function render_primary_color_field() {
		$primary_color = get_option('yd_checkout_primary_color', '#4285f4');
		?>
		<input type="color" name="yd_checkout_primary_color" value="<?php echo esc_attr($primary_color); ?>" />
		<p class="description"><?php _e('Select the primary color for buttons and accents.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render button style field.
	 * 
	 * @since    1.0.0
	 */
	public function render_button_style_field() {
		$button_style = get_option('yd_checkout_button_style', 'default');
		?>
		<select name="yd_checkout_button_style">
			<option value="default" <?php selected('default', $button_style); ?>><?php _e('Default', 'yd-checkout'); ?></option>
			<option value="rounded" <?php selected('rounded', $button_style); ?>><?php _e('Rounded', 'yd-checkout'); ?></option>
			<option value="pill" <?php selected('pill', $button_style); ?>><?php _e('Pill', 'yd-checkout'); ?></option>
			<option value="square" <?php selected('square', $button_style); ?>><?php _e('Square', 'yd-checkout'); ?></option>
		</select>
		<p class="description"><?php _e('Select the button style for the checkout form.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
	 * Render custom CSS field.
	 * 
	 * @since    1.0.0
	 */
	public function render_custom_css_field() {
		$custom_css = get_option('yd_checkout_custom_css', '');
		?>
		<textarea name="yd_checkout_custom_css" rows="8" cols="50" class="large-text code"><?php echo esc_textarea($custom_css); ?></textarea>
		<p class="description"><?php _e('Enter custom CSS to customize the checkout form appearance.', 'yd-checkout'); ?></p>
		<?php
	}
	
	/**
 * AJAX handler for validating Stripe credentials.
 * 
 * @since    1.0.0
 */
	public function validate_stripe_credentials() {
		// Check nonce
		check_ajax_referer('yd_checkout_admin_nonce', 'nonce');
		
		// Check permissions
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to do this.', 'yd-checkout')));
			return;
		}
		
		// Get the environment and credentials
		$environment = isset($_POST['environment']) ? sanitize_text_field($_POST['environment']) : 'test';
		$credentials = isset($_POST['credentials']) ? json_decode(stripslashes($_POST['credentials']), true) : array();
		
		// Sanitize credentials
		$publishable_key = isset($credentials['publishable_key']) ? sanitize_text_field($credentials['publishable_key']) : '';
		$secret_key = isset($credentials['secret_key']) ? sanitize_text_field($credentials['secret_key']) : '';
		
		// Basic validation
		if (empty($publishable_key) || empty($secret_key)) {
			wp_send_json_error(array('message' => __('Please enter both Publishable Key and Secret Key.', 'yd-checkout')));
			return;
		}
		
		// Validate key format
		$publishable_prefix = $environment === 'test' ? 'pk_test_' : 'pk_live_';
		$secret_prefix = $environment === 'test' ? 'sk_test_' : 'sk_live_';
		
		if (strpos($publishable_key, $publishable_prefix) !== 0) {
			wp_send_json_error(array('message' => sprintf(__('Publishable Key should start with "%s".', 'yd-checkout'), $publishable_prefix)));
			return;
		}
		
		if (strpos($secret_key, $secret_prefix) !== 0) {
			wp_send_json_error(array('message' => sprintf(__('Secret Key should start with "%s".', 'yd-checkout'), $secret_prefix)));
			return;
		}
		
		// Actual validation with Stripe API
		$validation_result = $this->validate_stripe_api_keys($secret_key);
		
		if (is_wp_error($validation_result)) {
			wp_send_json_error(array('message' => $validation_result->get_error_message()));
			return;
		}
		
		wp_send_json_success(array('message' => __('Stripe credentials validated successfully!', 'yd-checkout')));
	}
	

	/**
	 * Validate Stripe API keys by making a test request to the Stripe API.
	 * 
	 * @since    1.0.0
	 * @param    string    $secret_key    The Stripe secret key to validate.
	 * @return   true|WP_Error            True on success, WP_Error on failure.
	 */
	private function validate_stripe_api_keys($secret_key) {
		// Make a request to the Stripe API to validate the keys
		$response = wp_remote_get(
			'https://api.stripe.com/v1/account',
			array(
				'method'      => 'GET',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.1',
				'headers'     => array(
					'Authorization' => 'Bearer ' . $secret_key,
				),
			)
		);
		
		// Check for errors in the response
		if (is_wp_error($response)) {
			return new WP_Error('stripe_api_error', $response->get_error_message());
		}
		
		$response_code = wp_remote_retrieve_response_code($response);
		
		// Check if the request was successful (HTTP 200)
		if ($response_code !== 200) {
			$body = json_decode(wp_remote_retrieve_body($response), true);
			$error_message = isset($body['error']['message']) 
				? $body['error']['message'] 
				: sprintf(__('Invalid credentials. HTTP response code: %s', 'yd-checkout'), $response_code);
			
			return new WP_Error('stripe_invalid_key', $error_message);
		}
		
		// Keys are valid
		return true;
	}

	public function validate_paypal_credentials() {
		// Check nonce
		check_ajax_referer('yd_checkout_admin_nonce', 'nonce');
		
		// Check permissions
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to do this.', 'yd-checkout')));
			return;
		}
		
		// Get the environment and credentials
		$environment = isset($_POST['environment']) ? sanitize_text_field($_POST['environment']) : 'sandbox';
		$credentials = isset($_POST['credentials']) ? json_decode(stripslashes($_POST['credentials']), true) : array();

		// Sanitize credentials
		$client_id = isset($credentials['client_id']) ? sanitize_text_field($credentials['client_id']) : '';
		$client_secret = isset($credentials['client_secret']) ? sanitize_text_field($credentials['client_secret']) : '';

		// Basic validation
		if (empty($client_id) || empty($client_secret)) {
			wp_send_json_error(array('message' => __('Please enter both Client ID and Client Secret.', 'yd-checkout')));
			return;
		}
		
		// Determine API URL based on environment
		$api_url = ($environment === 'sandbox') 
			? 'https://api-m.sandbox.paypal.com/v1/oauth2/token' 
			: 'https://api-m.paypal.com/v1/oauth2/token';
		
		// Make request to get access token to validate credentials
		$response = wp_remote_post(
			$api_url,
			array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.1',
				'headers' => array(
					'Accept' => 'application/json',
					'Accept-Language' => 'en_US',
					'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
				),
				'body' => 'grant_type=client_credentials',
			)
		);
		
		// Check for connection errors
		if (is_wp_error($response)) {
			wp_send_json_error(array('message' => $response->get_error_message()));
			return;
		}
		
		// Check response code
		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = json_decode(wp_remote_retrieve_body($response), true);
		
		if ($response_code !== 200) {
			$error_message = isset($response_body['error_description']) 
				? $response_body['error_description'] 
				: __('Invalid credentials or API error.', 'yd-checkout');
			
			wp_send_json_error(array('message' => $error_message));
			return;
		}
		
		// Check if we got an access token
		if (empty($response_body['access_token'])) {
			wp_send_json_error(array('message' => __('API response did not include an access token.', 'yd-checkout')));
			return;
		}
		
		// Credentials are valid
		wp_send_json_success(array('message' => __('PayPal credentials validated successfully!', 'yd-checkout')));
	}
	
	/**
	 * AJAX handler for validating HERE API credentials.
	 * 
	 * @since    1.0.0
	 */
	public function validate_here_credentials() {
		// Check nonce
		check_ajax_referer('yd_checkout_admin_nonce', 'nonce');
		
		// Check permissions
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to do this.', 'yd-checkout')));
			return;
		}
		
		// Get the credentials
		$credentials = isset($_POST['credentials']) ? json_decode(stripslashes($_POST['credentials']),true) : array();
		
		// Sanitize credentials
		$api_key = isset($credentials['api_key']) ? sanitize_text_field($credentials['api_key']) : '';
		
		// Basic validation
		if (empty($api_key)) {
			wp_send_json_error(array('message' => __('Please enter an API Key.', 'yd-checkout')));
			return;
		}
		
		// Validate the HERE API key
		$validation_result = $this->validate_here_api_key($api_key);
		
		if (is_wp_error($validation_result)) {
			wp_send_json_error(array('message' => $validation_result->get_error_message()));
			return;
		}
		
		wp_send_json_success(array('message' => __('HERE API key validated successfully!', 'yd-checkout')));
	}

		/**
	 * Validate HERE API key by making a test request to the HERE API.
	 * 
	 * @since    1.0.0
	 * @param    string    $api_key    The HERE API key to validate.
	 * @return   true|WP_Error         True on success, WP_Error on failure.
	 */
	private function validate_here_api_key($api_key) {
		// If no API key provided, return error
		if (empty($api_key)) {
			return new WP_Error('missing_api_key', __('API key is required.', 'yd-checkout'));
		}
		
		// Make a simple request to the HERE autocomplete API with a test query
		$test_query = 'test';
		$url = add_query_arg(
			array(
				'apiKey' => $api_key,
				'q' => $test_query,
				'limit' => 1,
			),
			'https://autocomplete.search.hereapi.com/v1/autocomplete'
		);
		
		$response = wp_remote_get($url, array(
			'timeout' => 15,
			'headers' => array(
				'Accept' => 'application/json',
			)
		));
		
		// Check for request errors
		if (is_wp_error($response)) {
			return new WP_Error('api_connection_error', $response->get_error_message());
		}
		
		// Check response code
		$response_code = wp_remote_retrieve_response_code($response);
		if ($response_code !== 200) {
			$body = json_decode(wp_remote_retrieve_body($response), true);
			$error_message = isset($body['error']) && isset($body['error_description']) 
				? $body['error_description'] 
				: sprintf(__('Invalid API key. HTTP response code: %s', 'yd-checkout'), $response_code);
			
			return new WP_Error('invalid_api_key', $error_message);
		}
		
		// Try to parse the response
		$body = json_decode(wp_remote_retrieve_body($response), true);
		if (!$body || !isset($body['items'])) {
			return new WP_Error('invalid_response', __('Invalid response from HERE API.', 'yd-checkout'));
		}
		
		// API key is valid
		return true;
	}
}