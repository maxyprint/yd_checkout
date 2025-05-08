<?php

/**
 * The HERE API integration class
 *
 * @link       https://octonove.com
 * @since      1.0.0
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/includes/integrations
 */

/**
 * The HERE API integration class.
 *
 * This class handles interaction with the HERE Maps API for address autocomplete,
 * geocoding, and location services.
 *
 * @since      1.0.0
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/includes/integrations
 * @author     Octonove <octonoveclientes@gmail.com>
 */
class Yd_Checkout_Here_API {

	/**
	 * The HERE API key.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $api_key    The HERE API key.
	 */
	private $api_key;

	/**
	 * The base URL for HERE API autocomplete.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $autocomplete_url    The base URL for HERE API autocomplete.
	 */
	private $autocomplete_url = 'https://autocomplete.search.hereapi.com/v1/autocomplete';

	/**
	 * The base URL for HERE API geocoding.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $geocode_url    The base URL for HERE API geocoding.
	 */
	private $geocode_url = 'https://geocode.search.hereapi.com/v1/geocode';

	/**
	 * The base URL for HERE API reverse geocoding.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $reverse_geocode_url    The base URL for HERE API reverse geocoding.
	 */
	private $reverse_geocode_url = 'https://revgeocode.search.hereapi.com/v1/revgeocode';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->api_key = get_option('yd_checkout_here_api_key', '');
		
		// Register AJAX endpoints
		add_action('wp_ajax_yd_checkout_address_autocomplete', array($this, 'handle_autocomplete_ajax'));
		add_action('wp_ajax_nopriv_yd_checkout_address_autocomplete', array($this, 'handle_autocomplete_ajax'));
		
		add_action('wp_ajax_yd_checkout_address_geocode', array($this, 'handle_geocode_ajax'));
		add_action('wp_ajax_nopriv_yd_checkout_address_geocode', array($this, 'handle_geocode_ajax'));
	}

	/**
	 * Check if the HERE API integration is properly configured.
	 *
	 * @since     1.0.0
	 * @return    boolean    True if API key is set, false otherwise.
	 */
	public function is_configured() {
		return !empty($this->api_key);
	}

	/**
	 * Get the HERE API key.
	 *
	 * @since     1.0.0
	 * @return    string    The HERE API key.
	 */
	public function get_api_key() {
		return $this->api_key;
	}

	/**
	 * Handle the AJAX request for address autocomplete.
	 *
	 * @since    1.0.0
	 */
	public function handle_autocomplete_ajax() {
		// Check nonce
		check_ajax_referer('yd_checkout_nonce', 'nonce');
		
		// Get query parameter
		$query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
		
		if (empty($query)) {
			wp_send_json_error(array('message' => 'Query parameter is required'));
			return;
		}
		
		// Get autocomplete results
		$results = $this->get_autocomplete_results($query);
		
		if (is_wp_error($results)) {
			wp_send_json_error(array('message' => $results->get_error_message()));
			return;
		}
		
		wp_send_json_success($results);
	}

	/**
	 * Handle the AJAX request for address geocoding.
	 *
	 * @since    1.0.0
	 */
	public function handle_geocode_ajax() {
		// Check nonce
		check_ajax_referer('yd_checkout_nonce', 'nonce');
		
		// Get address parameter
		$address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
		
		if (empty($address)) {
			wp_send_json_error(array('message' => 'Address parameter is required'));
			return;
		}
		
		// Get geocode results
		$results = $this->geocode_address($address);
		
		if (is_wp_error($results)) {
			wp_send_json_error(array('message' => $results->get_error_message()));
			return;
		}
		
		wp_send_json_success($results);
	}

	/**
	 * Get autocomplete results for an address query.
	 *
	 * @since     1.0.0
	 * @param     string    $query    The address query.
	 * @param     array     $args     Optional. Additional arguments for the API request.
	 * @return    array|WP_Error      Autocomplete results or WP_Error on failure.
	 */
	public function get_autocomplete_results($query, $args = array()) {
		if (!$this->is_configured()) {
			return new WP_Error('api_not_configured', __('HERE API is not configured', 'yd-checkout'));
		}
		
		// Default arguments
		$default_args = array(
			'limit' => 5,
			'types' => 'address',
			'lang' => 'en-US'
		);
		
		// Merge default arguments with provided arguments
		$args = wp_parse_args($args, $default_args);
		
		// Build API URL
		$url = add_query_arg(
			array(
				'apiKey' => $this->api_key,
				'q' => urlencode($query),
				'limit' => $args['limit'],
				'types' => $args['types'],
				'lang' => $args['lang']
			),
			$this->autocomplete_url
		);
		
		// Make API request
		$response = wp_remote_get($url);
		
		// Check for errors
		if (is_wp_error($response)) {
			return $response;
		}
		
		// Get response body
		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);
		
		// Check if response is valid
		if (empty($data) || !isset($data['items'])) {
			return new WP_Error(
				'invalid_response',
				__('Invalid response from HERE API', 'yd-checkout')
			);
		}
		
		return $data;
	}

	/**
	 * Geocode an address.
	 *
	 * @since     1.0.0
	 * @param     string    $address    The address to geocode.
	 * @param     array     $args       Optional. Additional arguments for the API request.
	 * @return    array|WP_Error        Geocode results or WP_Error on failure.
	 */
	public function geocode_address($address, $args = array()) {
		if (!$this->is_configured()) {
			return new WP_Error('api_not_configured', __('HERE API is not configured', 'yd-checkout'));
		}
		
		// Basic validation
		if (empty($address)) {
			return new WP_Error('empty_address', __('Address cannot be empty', 'yd-checkout'));
		}
		
		// Default arguments
		$default_args = array(
			'limit' => 1,
			'lang' => 'en-US',
			'resultType' => 'houseNumber,street,postalCode'
		);
		
		// Merge default arguments with provided arguments
		$args = wp_parse_args($args, $default_args);
		
		// Log the geocoding attempt if debug is enabled
		$this->log("Geocoding address: {$address}", 'info');
		
		// Build API URL
		$url = add_query_arg(
			array(
				'apiKey' => $this->api_key,
				'q' => urlencode($address),
				'limit' => $args['limit'],
				'lang' => $args['lang'],
				'resultType' => $args['resultType']
			),
			$this->geocode_url
		);
		
		// Make API request with increased timeout for reliability
		$response = wp_remote_get($url, array(
			'timeout' => 15,
			'headers' => array(
				'Accept' => 'application/json',
			)
		));
		
		// Check for connection errors
		if (is_wp_error($response)) {
			$this->log('Geocoding connection error: ' . $response->get_error_message(), 'error');
			return new WP_Error(
				'connection_error',
				sprintf(__('Could not connect to HERE API: %s', 'yd-checkout'), $response->get_error_message())
			);
		}
		
		// Check response code
		$response_code = wp_remote_retrieve_response_code($response);
		if ($response_code !== 200) {
			$body = json_decode(wp_remote_retrieve_body($response), true);
			$error_message = isset($body['error_description']) ? $body['error_description'] : '';
			
			$this->log("Geocoding error - HTTP {$response_code}: {$error_message}", 'error');
			
			return new WP_Error(
				'invalid_response',
				sprintf(__('HERE API error (HTTP %d): %s', 'yd-checkout'), $response_code, $error_message)
			);
		}
		
		// Get response body
		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);
		
		// Check if response is valid and contains items
		if (empty($data) || !isset($data['items']) || empty($data['items'])) {
			$this->log('Geocoding returned no results for: ' . $address, 'warning');
			return new WP_Error(
				'no_results',
				__('No address matches found. Please check the address and try again.', 'yd-checkout')
			);
		}
		
		// Log success
		$this->log('Geocoding successful - found ' . count($data['items']) . ' result(s)', 'info');
		
		return $data;
	}

	/**
	 * Log a message for debugging purposes.
	 *
	 * @since     1.0.0
	 * @access    private
	 * @param     string    $message    The message to log.
	 * @param     string    $level      The log level (info, warning, error).
	 * @return    void
	 */
	private function log($message, $level = 'info') {
		// Check if logging is enabled
		$enable_logging = get_option('yd_checkout_here_api_debug', 'no') === 'yes';
		
		if (!$enable_logging && $level !== 'error') {
			return;
		}
		
		// Format log message
		$log_entry = sprintf(
			'[%s] [%s] %s',
			date('Y-m-d H:i:s'),
			strtoupper($level),
			$message
		);
		
		// Define log file path
		$log_dir = WP_CONTENT_DIR . '/logs/';
		$log_file = $log_dir . 'yd-checkout-here-api.log';
		
		// Create logs directory if it doesn't exist
		if (!file_exists($log_dir)) {
			wp_mkdir_p($log_dir);
		}
		
		// Write to log file
		error_log($log_entry . PHP_EOL, 3, $log_file);
	}


	
	/**
	 * Reverse geocode coordinates.
	 *
	 * @since     1.0.0
	 * @param     float     $latitude     The latitude coordinate.
	 * @param     float     $longitude    The longitude coordinate.
	 * @param     array     $args         Optional. Additional arguments for the API request.
	 * @return    array|WP_Error          Reverse geocode results or WP_Error on failure.
	 */
	public function reverse_geocode($latitude, $longitude, $args = array()) {
		if (!$this->is_configured()) {
			return new WP_Error('api_not_configured', __('HERE API is not configured', 'yd-checkout'));
		}
		
		// Validate coordinates
		$latitude = floatval($latitude);
		$longitude = floatval($longitude);
		
		if (empty($latitude) || empty($longitude)) {
			return new WP_Error('invalid_coordinates', __('Invalid coordinates', 'yd-checkout'));
		}
		
		// Default arguments
		$default_args = array(
			'limit' => 1,
			'lang' => 'en-US'
		);
		
		// Merge default arguments with provided arguments
		$args = wp_parse_args($args, $default_args);
		
		// Build API URL
		$url = add_query_arg(
			array(
				'apiKey' => $this->api_key,
				'at' => $latitude . ',' . $longitude,
				'limit' => $args['limit'],
				'lang' => $args['lang']
			),
			$this->reverse_geocode_url
		);
		
		// Make API request
		$response = wp_remote_get($url);
		
		// Check for errors
		if (is_wp_error($response)) {
			return $response;
		}
		
		// Get response body
		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);
		
		// Check if response is valid
		if (empty($data) || !isset($data['items']) || empty($data['items'])) {
			return new WP_Error(
				'invalid_response',
				__('Invalid response from HERE API or no results found', 'yd-checkout')
			);
		}
		
		return $data;
	}

	/**
	 * Format address components from HERE API response.
	 *
	 * @since     1.0.0
	 * @param     array     $address_data    Address data from HERE API.
	 * @return    array                      Formatted address components.
	 */
	public function format_address_components($address_data) {
        if (empty($address_data) || !isset($address_data['address'])) {
            return array();
        }
        
        $address = $address_data['address'];
        
        // Extract house number from street if it contains both
        $street = isset($address['street']) ? $address['street'] : '';
        $house_number = isset($address['houseNumber']) ? $address['houseNumber'] : '';
        
        // If street contains house number and no house number is set
        if (!empty($street) && empty($house_number)) {
            // Try to extract house number from street
            if (preg_match('/^(.*?)\s+(\d+.*)$/', $street, $matches)) {
                $street = $matches[1];
                $house_number = $matches[2];
            }
        }
        
        // Get country code (uppercase for ISO standards)
        $country_code = isset($address['countryCode']) ? strtoupper($address['countryCode']) : '';
        
        // Build formatted address components
        $formatted = array(
            'street' => $street,
            'house_number' => $house_number,
            'postal_code' => isset($address['postalCode']) ? $address['postalCode'] : '',
            'city' => isset($address['city']) ? $address['city'] : '',
            'state' => isset($address['state']) ? $address['state'] : '',
            'country' => $country_code, // Use the country code instead of name
            'country_name' => isset($address['countryName']) ? $address['countryName'] : '',
            'formatted_address' => isset($address_data['title']) ? $address_data['title'] : ''
        );
        
        return $formatted;
    }

	/**
	 * Register HERE API settings fields.
	 *
	 * @since     1.0.0
	 * @return    array    Settings fields for HERE API.
	 */
	public function get_settings_fields() {
		return array(
			array(
				'name' => 'yd_checkout_here_api_key',
				'label' => __('HERE API Key', 'yd-checkout'),
				'desc' => __('Enter your HERE API key for address autocomplete and geocoding', 'yd-checkout'),
				'type' => 'text',
				'default' => ''
			),
			array(
				'name' => 'yd_checkout_here_api_enable_autocomplete',
				'label' => __('Enable Address Autocomplete', 'yd-checkout'),
				'desc' => __('Enable address autocomplete on checkout form', 'yd-checkout'),
				'type' => 'checkbox',
				'default' => 'on'
			)
		);
	}

	/**
	 * Verify if an address is valid using HERE API geocoding.
	 *
	 * @since     1.0.0
	 * @param     array     $address_data    Address data to verify.
	 * @param     array     $args            Optional. Additional verification arguments.
	 * @return    array|WP_Error             Verified address data on success, or WP_Error on failure.
	 */
	public function verify_address($address_data, $args = array()) {
		// Check if the API is configured
		if (!$this->is_configured()) {
			return new WP_Error('api_not_configured', __('HERE API is not configured', 'yd-checkout'));
		}
		
		// Check if address data is provided
		if (empty($address_data)) {
			return new WP_Error('empty_address', __('Address data is empty', 'yd-checkout'));
		}
		
		// Default arguments
		$default_args = array(
			'require_house_number' => false,        // Whether house number is required for validation
			'require_postal_code' => true,          // Whether postal code is required
			'verification_level' => 'standard',     // 'relaxed', 'standard', or 'strict'
			'include_confidence' => true,           // Include confidence score in result
			'include_suggestions' => true,          // Include address suggestions on low confidence
			'min_confidence' => 0.7                 // Minimum confidence score to consider valid (0.0-1.0)
		);
		
		// Parse arguments
		$args = wp_parse_args($args, $default_args);
		
		// Build address string from components
		$address_parts = array();
		
		// Add house number and street
		if (!empty($address_data['street'])) {
			$street = $address_data['street'];
			
			if (!empty($address_data['house_number'])) {
				// Different countries have different formats for house number placement
				// This is a simple approach; might need adjustment for specific countries
				$street .= ' ' . $address_data['house_number'];
			} elseif ($args['require_house_number']) {
				return new WP_Error('missing_house_number', __('House number is required', 'yd-checkout'));
			}
			
			$address_parts[] = $street;
		} elseif (!empty($address_data['address_line1'])) {
			$address_parts[] = $address_data['address_line1'];
			
			// Include address line 2 if available
			if (!empty($address_data['address_line2'])) {
				$address_parts[] = $address_data['address_line2'];
			}
		} else {
			return new WP_Error('missing_street', __('Street address is required', 'yd-checkout'));
		}
		
		// Add city
		if (!empty($address_data['city'])) {
			$address_parts[] = $address_data['city'];
		} else {
			return new WP_Error('missing_city', __('City is required', 'yd-checkout'));
		}
		
		// Add postal code
		if (!empty($address_data['postal_code'])) {
			$address_parts[] = $address_data['postal_code'];
		} elseif (!empty($address_data['postcode'])) {
			$address_parts[] = $address_data['postcode'];
		} elseif ($args['require_postal_code']) {
			return new WP_Error('missing_postal_code', __('Postal code is required', 'yd-checkout'));
		}
		
		// Add country
		if (!empty($address_data['country'])) {
			$address_parts[] = $address_data['country'];
		} else {
			return new WP_Error('missing_country', __('Country is required', 'yd-checkout'));
		}
		
		// Create address string
		$address_string = implode(', ', array_filter($address_parts));
		
		// Geocode the address
		$geocode_args = array(
			'limit' => 3, // Get multiple results for comparison if needed
			'resultType' => 'houseNumber,street,postalCode'
		);
		
		$geocode_result = $this->geocode_address($address_string, $geocode_args);
		
		// Handle geocoding errors
		if (is_wp_error($geocode_result)) {
			return $geocode_result;
		}
		
		// If we got here, we have at least one result
		$items = $geocode_result['items'];
		$best_match = $items[0]; // Assume first result is best match
		
		// Calculate confidence score based on verification level
		$confidence_score = $this->calculate_confidence_score($best_match, $address_data, $args['verification_level']);
		
		// Create result array
		$result = array(
			'verified' => $confidence_score >= $args['min_confidence'],
			'formatted_address' => $best_match['title'],
			'geocoded_address' => $this->format_address_components($best_match),
			'input_address' => $address_data,
		);
		
		// Add confidence score if requested
		if ($args['include_confidence']) {
			$result['confidence'] = $confidence_score;
		}
		
		// Add suggestions if confidence is low and suggestions are requested
		if (!$result['verified'] && $args['include_suggestions'] && count($items) > 1) {
			$suggestions = array();
			
			// Skip the first item (already used as best match)
			for ($i = 1; $i < count($items); $i++) {
				$suggestions[] = array(
					'formatted_address' => $items[$i]['title'],
					'address' => $this->format_address_components($items[$i])
				);
			}
			
			$result['suggestions'] = $suggestions;
		}
		
		return $result;
	}

	/**
	 * Calculate confidence score for address verification.
	 *
	 * @since     1.0.0
	 * @access    private
	 * @param     array     $geocoded_data    Geocoded address data from HERE API.
	 * @param     array     $input_data       User-provided address data.
	 * @param     string    $level            Verification level ('relaxed', 'standard', or 'strict').
	 * @return    float                       Confidence score between 0 and 1.
	 */
	private function calculate_confidence_score($geocoded_data, $input_data, $level = 'standard') {
		// Component weights vary based on verification level
		$weights = array(
			'relaxed' => array(
				'street' => 0.3,
				'house_number' => 0.1,
				'postal_code' => 0.25,
				'city' => 0.25,
				'state' => 0.05,
				'country' => 0.05
			),
			'standard' => array(
				'street' => 0.25,
				'house_number' => 0.15,
				'postal_code' => 0.25,
				'city' => 0.2,
				'state' => 0.05,
				'country' => 0.1
			),
			'strict' => array(
				'street' => 0.25,
				'house_number' => 0.2,
				'postal_code' => 0.25,
				'city' => 0.15,
				'state' => 0.05,
				'country' => 0.1
			)
		);
		
		// Use standard level if specified level is not defined
		if (!isset($weights[$level])) {
			$level = 'standard';
		}
		
		// Get formatted address components from geocoded result
		$geocoded_components = $this->format_address_components($geocoded_data);
		
		// Initialize score and applied weights
		$score = 0;
		$applied_weight_total = 0;
		
		// Compare street name
		if (!empty($input_data['street']) && !empty($geocoded_components['street'])) {
			$street_similarity = $this->calculate_string_similarity(
				$this->normalize_string($input_data['street']),
				$this->normalize_string($geocoded_components['street'])
			);
			$score += $street_similarity * $weights[$level]['street'];
			$applied_weight_total += $weights[$level]['street'];
		} elseif (!empty($input_data['address_line1']) && !empty($geocoded_components['street'])) {
			// Try to extract street from address_line1
			$address_parts = explode(' ', $input_data['address_line1']);
			$potential_street = implode(' ', array_slice($address_parts, 0, count($address_parts) - 1));
			
			$street_similarity = $this->calculate_string_similarity(
				$this->normalize_string($potential_street),
				$this->normalize_string($geocoded_components['street'])
			);
			$score += $street_similarity * $weights[$level]['street'];
			$applied_weight_total += $weights[$level]['street'];
		}
		
		// Compare house number
		if (!empty($input_data['house_number']) && !empty($geocoded_components['house_number'])) {
			$house_similarity = $this->calculate_string_similarity(
				$this->normalize_string($input_data['house_number']),
				$this->normalize_string($geocoded_components['house_number'])
			);
			$score += $house_similarity * $weights[$level]['house_number'];
			$applied_weight_total += $weights[$level]['house_number'];
		}
		
		// Compare postal code
		if (!empty($input_data['postal_code']) && !empty($geocoded_components['postal_code'])) {
			$postal_similarity = $this->calculate_string_similarity(
				$this->normalize_string($input_data['postal_code']),
				$this->normalize_string($geocoded_components['postal_code'])
			);
			$score += $postal_similarity * $weights[$level]['postal_code'];
			$applied_weight_total += $weights[$level]['postal_code'];
		} elseif (!empty($input_data['postcode']) && !empty($geocoded_components['postal_code'])) {
			$postal_similarity = $this->calculate_string_similarity(
				$this->normalize_string($input_data['postcode']),
				$this->normalize_string($geocoded_components['postal_code'])
			);
			$score += $postal_similarity * $weights[$level]['postal_code'];
			$applied_weight_total += $weights[$level]['postal_code'];
		}
		
		// Compare city
		if (!empty($input_data['city']) && !empty($geocoded_components['city'])) {
			$city_similarity = $this->calculate_string_similarity(
				$this->normalize_string($input_data['city']),
				$this->normalize_string($geocoded_components['city'])
			);
			$score += $city_similarity * $weights[$level]['city'];
			$applied_weight_total += $weights[$level]['city'];
		}
		
		// Compare state/province
		if (!empty($input_data['state']) && !empty($geocoded_components['state'])) {
			$state_similarity = $this->calculate_string_similarity(
				$this->normalize_string($input_data['state']),
				$this->normalize_string($geocoded_components['state'])
			);
			$score += $state_similarity * $weights[$level]['state'];
			$applied_weight_total += $weights[$level]['state'];
		}
		
		// Compare country
		if (!empty($input_data['country']) && !empty($geocoded_components['country'])) {
			$country_similarity = $this->calculate_string_similarity(
				$this->normalize_string($input_data['country']),
				$this->normalize_string($geocoded_components['country'])
			);
			$score += $country_similarity * $weights[$level]['country'];
			$applied_weight_total += $weights[$level]['country'];
		} elseif (!empty($input_data['country']) && !empty($geocoded_components['country_code'])) {
			// Try matching country name with country code
			$country_codes = $this->get_country_codes();
			$country_name = strtoupper($input_data['country']);
			
			// Check if provided country matches the geocoded country code
			if (isset($country_codes[$country_name]) && $country_codes[$country_name] === $geocoded_components['country_code']) {
				$score += 1.0 * $weights[$level]['country'];
			} else {
				$score += 0.0 * $weights[$level]['country'];
			}
			$applied_weight_total += $weights[$level]['country'];
		}
		
		// Normalize the score based on applied weights
		if ($applied_weight_total > 0) {
			$normalized_score = $score / $applied_weight_total;
		} else {
			$normalized_score = 0;
		}
		
		return $normalized_score;
	}


	/**
	 * Calculate similarity between two strings.
	 *
	 * @since     1.0.0
	 * @access    private
	 * @param     string    $str1    First string to compare.
	 * @param     string    $str2    Second string to compare.
	 * @return    float               Similarity score between 0 and 1.
	 */
	private function calculate_string_similarity($str1, $str2) {
		// If either string is empty, return 0
		if (empty($str1) || empty($str2)) {
			return 0;
		}
		
		// If strings are identical, return 1
		if ($str1 === $str2) {
			return 1;
		}
		
		// Calculate Levenshtein distance
		$lev_distance = levenshtein($str1, $str2);
		
		// Get the max string length
		$max_length = max(strlen($str1), strlen($str2));
		
		// Calculate similarity as inverse of normalized distance
		$similarity = 1 - ($lev_distance / $max_length);
		
		// Ensure the result is between 0 and 1
		return max(0, min(1, $similarity));
	}

	/**
	 * Normalize a string for comparison.
	 *
	 * @since     1.0.0
	 * @access    private
	 * @param     string    $string    String to normalize.
	 * @return    string               Normalized string.
	 */
	private function normalize_string($string) {
		// Convert to lowercase
		$string = strtolower($string);
		
		// Remove extra whitespace
		$string = preg_replace('/\s+/', ' ', trim($string));
		
		// Remove special characters but keep spaces
		$string = preg_replace('/[^\p{L}\p{N}\s]/u', '', $string);
		
		// Remove common words that don't add meaning for address matching
		$stop_words = array('street', 'st', 'avenue', 'ave', 'road', 'rd', 'boulevard', 'blvd', 'lane', 'ln', 'drive', 'dr');
		$words = explode(' ', $string);
		$filtered_words = array();
		
		foreach ($words as $word) {
			if (!in_array($word, $stop_words)) {
				$filtered_words[] = $word;
			}
		}
		
		// Return the normalized string
		return implode(' ', $filtered_words);
	}

	/**
	 * Get country codes for matching country names.
	 *
	 * @since     1.0.0
	 * @access    private
	 * @return    array    Associative array of country names to country codes.
	 */
	private function get_country_codes() {
		// Return a mapping of country names to ISO country codes
		// This is a simplified list - a complete list would be much longer
		return array(
			'UNITED STATES' => 'US',
			'USA' => 'US',
			'UNITED KINGDOM' => 'GB',
			'UK' => 'GB',
			'CANADA' => 'CA',
			'GERMANY' => 'DE',
			'FRANCE' => 'FR',
			'AUSTRALIA' => 'AU',
			'SPAIN' => 'ES',
			'ITALY' => 'IT',
			'NETHERLANDS' => 'NL',
			'SWEDEN' => 'SE',
			'NORWAY' => 'NO',
			'DENMARK' => 'DK',
			'FINLAND' => 'FI',
			'JAPAN' => 'JP',
			'CHINA' => 'CN',
			'INDIA' => 'IN',
			'BRAZIL' => 'BR',
			'MEXICO' => 'MX'
		);
	}


	/**
	 * Enqueue scripts and styles for HERE API integration.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if (!$this->is_configured()) {
			return;
		}
		
		$enable_autocomplete = get_option('yd_checkout_here_api_enable_autocomplete', 'on') === 'on';
		
		if (!$enable_autocomplete) {
			return;
		}
		
		// Localize script with HERE API key and settings
		wp_localize_script(
			'yd-checkout-public',
			'ydCheckoutHere',
			array(
				'apiKey' => $this->api_key,
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('yd_checkout_nonce')
			)
		);
	}
}