<?php
/**
 * Class Yd_Checkout_Address
 * 
 * Handles address data operations for the YDesign Checkout
 * 
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/includes/models
 */
class Yd_Checkout_Address {
    
    /**
     * Table name
     * 
     * @var string
     */
    private $table_name;
    
    /**
     * Initialize the class
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'yd_checkout_addresses';
    }
    
    /**
     * Create a new address
     * 
     * @param array $data Address data
     * @return int|false The address ID or false on failure
     */
    public function create($data) {
        global $wpdb;
        
        // Make sure we have required fields
        $required = array('user_id', 'address_type', 'first_name', 'last_name', 'address_line1', 'city', 'postal_code', 'country');
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        
        // Set default values for optional fields
        $data = wp_parse_args($data, array(
            'address_line2' => '',
            'is_default' => 0,
            'address_name' => sprintf('%s %s\'s Address', $data['first_name'], $data['last_name'])
        ));
        
        // Format data for database
        $insert_data = array(
            'user_id' => $data['user_id'],
            'address_type' => $data['address_type'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'],
            'city' => $data['city'],
            'postal_code' => $data['postal_code'],
            'country' => $data['country'],
            'is_default' => $data['is_default'] ? 1 : 0,
            'address_name' => $data['address_name'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        // Format for database
        $format = array(
            '%d', // user_id
            '%s', // address_type
            '%s', // first_name
            '%s', // last_name
            '%s', // address_line1
            '%s', // address_line2
            '%s', // city
            '%s', // postal_code
            '%s', // country
            '%d', // is_default
            '%s', // address_name
            '%s', // created_at
            '%s'  // updated_at
        );
        
        // Insert data
        $result = $wpdb->insert(
            $this->table_name,
            $insert_data,
            $format
        );
        
        if ($result === false) {
            return false;
        }
        
        // Return the new address ID
        return $wpdb->insert_id;
    }
    
    /**
     * Update an existing address
     * 
     * @param array $data Address data with ID
     * @return bool True on success, false on failure
     */
    public function update($data) {
        global $wpdb;
        
        // Make sure we have an ID
        if (!isset($data['id']) || empty($data['id'])) {
            return false;
        }
        
        // Make sure we have required fields
        $required = array('user_id', 'address_type', 'first_name', 'last_name', 'address_line1', 'city', 'postal_code', 'country');
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        
        // Get the existing address to merge with updates
        $address = $this->get_by_id($data['id']);
        
        if (!$address) {
            return false;
        }
        
        // Verify the address belongs to this user
        if ($address->user_id != $data['user_id']) {
            return false;
        }
        
        // Format data for database
        $update_data = array(
            'address_type' => $data['address_type'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'address_line1' => $data['address_line1'],
            'address_line2' => isset($data['address_line2']) ? $data['address_line2'] : $address->address_line2,
            'city' => $data['city'],
            'postal_code' => $data['postal_code'],
            'country' => $data['country'],
            'is_default' => isset($data['is_default']) ? ($data['is_default'] ? 1 : 0) : $address->is_default,
            'address_name' => isset($data['address_name']) ? $data['address_name'] : $address->address_name,
            'updated_at' => current_time('mysql')
        );
        
        // Format for database
        $format = array(
            '%s', // address_type
            '%s', // first_name
            '%s', // last_name
            '%s', // address_line1
            '%s', // address_line2
            '%s', // city
            '%s', // postal_code
            '%s', // country
            '%d', // is_default
            '%s', // address_name
            '%s'  // updated_at
        );
        
        // Update data
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $data['id']),
            $format,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete an address
     * 
     * @param int $id Address ID
     * @param int $user_id User ID (for security)
     * @return bool True on success, false on failure
     */
    public function delete($id, $user_id) {
        global $wpdb;
        
        // Get the address first to verify ownership
        $address = $this->get_by_id($id);
        
        if (!$address || $address->user_id != $user_id) {
            return false;
        }
        
        // Delete the address
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get a single address by ID
     * 
     * @param int $id Address ID
     * @return object|false Address object or false if not found
     */
    public function get_by_id($id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        );
        
        $address = $wpdb->get_row($query);
        
        return $address;
    }
    
    /**
     * Get addresses by user ID and type
     * 
     * @param int $user_id User ID
     * @param string $type Address type (shipping or billing)
     * @return array Array of address objects
     */
    public function get_by_type($user_id, $type = 'shipping') {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND address_type = %s ORDER BY is_default DESC, id DESC",
            $user_id,
            $type
        );
        
        $addresses = $wpdb->get_results($query);
        
        // Add additional data for frontend display
        foreach ($addresses as $key => $address) {
            // Extract street and house number from address_line1
            $parts = $this->extract_street_parts($address->address_line1);
            
            // Map database fields to what JavaScript expects
            $addresses[$key]->street = $parts['street'];
            $addresses[$key]->house_number = $parts['house_number'];
            $addresses[$key]->first_name = $address->first_name;
            $addresses[$key]->last_name = $address->last_name;
            $addresses[$key]->postal_code = $address->postal_code;
            $addresses[$key]->city = $address->city;
            $addresses[$key]->country = $address->country;
            $addresses[$key]->is_default = (bool)$address->is_default;
            $addresses[$key]->name = $address->address_name;
            
            // Format the address for display
            $addresses[$key]->formatted = sprintf(
                '%s, %s %s, %s',
                $address->address_line1,
                $address->city,
                $address->postal_code,
                $address->country
            );
        }
        
        return $addresses ?: array();
    }
    
    /**
     * Get the default address for a user by type
     * 
     * @param int $user_id User ID
     * @param string $type Address type (shipping or billing)
     * @return object|false Address object or false if not found
     */
    public function get_default($user_id, $type = 'shipping') {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND address_type = %s AND is_default = 1",
            $user_id,
            $type
        );
        
        $address = $wpdb->get_row($query);
        
        // If no default, get the most recent
        if (!$address) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE user_id = %d AND address_type = %s ORDER BY id DESC LIMIT 1",
                $user_id,
                $type
            );
            
            $address = $wpdb->get_row($query);
        }
        
        if ($address) {
            // Extract street and house number from address_line1
            $parts = $this->extract_street_parts($address->address_line1);
            $address->street = $parts['street'];
            $address->house_number = $parts['house_number'];
            
            // Format the address for display
            $address->formatted = sprintf(
                '%s, %s %s, %s',
                $address->address_line1,
                $address->city,
                $address->postal_code,
                $address->country
            );
        }
        
        return $address;
    }
    
    /**
     * Set an address as default
     * 
     * @param int $id Address ID
     * @param int $user_id User ID (for security)
     * @return bool True on success, false on failure
     */
    public function set_default($id, $user_id) {
        global $wpdb;
        
        // Get the address first to verify ownership and get type
        $address = $this->get_by_id($id);
        
        if (!$address || $address->user_id != $user_id) {
            return false;
        }
        
        // Unset all other defaults of this type for this user
        $this->unset_defaults($user_id, $address->address_type);
        
        // Set this address as default
        $result = $wpdb->update(
            $this->table_name,
            array('is_default' => 1),
            array('id' => $id),
            array('%d'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Unset all default addresses for a user of a specific type
     * 
     * @param int $user_id User ID
     * @param string $type Address type (shipping or billing)
     * @return bool True on success, false on failure
     */
    public function unset_defaults($user_id, $type) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_name,
            array('is_default' => 0),
            array(
                'user_id' => $user_id,
                'address_type' => $type,
                'is_default' => 1
            ),
            array('%d'),
            array('%d', '%s', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Extract street and house number from address line
     * 
     * @param string $address_line Address line
     * @return array Array with street and house_number keys
    */
    public function extract_street_parts($address_line) {
        $result = array(
            'street' => $address_line,
            'house_number' => ''
        );
        
        // Try to extract house number based on common patterns
        if (preg_match('/^(.*)\s+(\d+\s*[a-zA-Z]*)$/', $address_line, $matches)) {
            $result['street'] = trim($matches[1]);
            $result['house_number'] = trim($matches[2]);
        }
        
        return $result;
    }
    
    /**
     * Create the database table on plugin activation
     * 
     * @return bool True on success, false on failure
    */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'yd_checkout_addresses';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            address_type varchar(20) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            address_line1 varchar(255) NOT NULL,
            address_line2 varchar(255) DEFAULT '',
            city varchar(100) NOT NULL,
            postal_code varchar(20) NOT NULL,
            country varchar(100) NOT NULL,
            is_default tinyint(1) NOT NULL DEFAULT 0,
            address_name varchar(100) DEFAULT '',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id_type (user_id, address_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql);
        
        return true;
    }
}