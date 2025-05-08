/**
 * YDesign Checkout - Main JavaScript
 * 
 * A consolidated JavaScript file that handles checkout functionality:
 * - Form handling and validation
 * - Address management
 * - Payment processing (Stripe & PayPal)
 */

const YDCheckout = (function() {
    'use strict';

    /**
     * Core module - handles initialization and common functionality
     */
    const core = {
        // DOM elements
        elements: {
            checkoutForm: null,
            submitButton: null,
            noticeContainer: null
        },

        // Configuration
        config: {
            ajaxUrl: '',
            nonce: '',
            i18n: {}
        },

        /**
         * Initialize the checkout
         * @param {Object} config - Configuration options
         */
        init: function(config) {
            // Merge configuration
            if (config) {
                this.config = {...this.config, ...config};
            }

            // Cache DOM elements
            this.cacheElements();

            // Initialize submodules
            address.init();
            payment.init();

            // Bind events
            this.bindEvents();

            console.log('YDesign Checkout initialized');
        },

        /**
         * Cache commonly used DOM elements
         */
        cacheElements: function() {
            this.elements.checkoutForm = document.getElementById('yd-checkout-form');
            this.elements.submitButton = document.querySelector('.yd-checkout-submit-btn');
            this.elements.noticeContainer = document.createElement('div');
            this.elements.noticeContainer.className = 'yd-checkout-notices';
            
            // Add notice container to the top of the form
            if (this.elements.checkoutForm) {
                this.elements.checkoutForm.prepend(this.elements.noticeContainer);
            }
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            if (this.elements.checkoutForm) {
                this.elements.checkoutForm.addEventListener('submit', this.handleFormSubmit.bind(this));
            }
        },

        /**
         * Handle form submission
         * @param {Event} event - The submit event
         */
        handleFormSubmit: function(event) {
            event.preventDefault();
            
            // Clear existing notices
            this.clearNotices();
            
            // Validate form
            if (!this.validateForm()) {
                return;
            }
            
            // Disable submit button
            this.setSubmitButtonState(false);
            
            // Get selected payment method
            const paymentMethod = payment.getSelectedMethod();
            
            // Process payment based on method
            switch(paymentMethod) {
                case 'stripe':
                    payment.processStripePayment();
                    break;
                case 'paypal':
                    payment.processPayPalPayment();
                    break;
                default:
                    // For other payment methods, submit the form directly
                    this.processOrder();
                    break;
            }
        },
        
        /**
         * Process the order using AJAX
         */
        processOrder: function() {
            const formData = new FormData(this.elements.checkoutForm);
            formData.append('action', 'yd_checkout_process_order');
            formData.append('nonce', this.config.nonce);
            
            // Check if different billing address is used
            const differentBilling = document.getElementById('different-billing') && document.getElementById('different-billing').checked;
            
            // If billing address is the same as shipping, don't send billing fields
            if (!differentBilling) {
                // Remove billing address fields from formData
                for (const pair of Array.from(formData.entries())) {
                    const key = pair[0];
                    if (key.startsWith('billing_address_') || key === 'billing_address_id') {
                        formData.delete(key);
                    }
                }
            }
            
            fetch(this.config.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Handle successful order
                    if (data.data.redirect) {
                        window.location.href = data.data.redirect;
                    } else {
                        this.showNotice('Order processed successfully!', 'success');
                    }
                } else {
                    // Handle error
                    this.showNotice(data.data.message || 'Error processing order.', 'error');
                    this.setSubmitButtonState(true);
                }
            })
            .catch(error => {
                this.showNotice('Error: ' + error.message, 'error');
                this.setSubmitButtonState(true);
            });
        },
        
        /**
         * Validate the checkout form
         * @returns {boolean} Whether the form is valid
         */
        validateForm: function() {
            let isValid = true;
            
            // Check if a payment method is selected
            if (!payment.validatePaymentMethod()) {
                isValid = false;
            }
            
            // Check address selection/input
            if (!address.validateAddresses()) {
                isValid = false;
            }
            
            return isValid;
        },
        
        /**
         * Show a notice message
         * @param {string} message - The message to display
         * @param {string} type - The notice type (success, error, info)
         */
        showNotice: function(message, type = 'info') {
            const notice = document.createElement('div');
            notice.className = `yd-checkout-notice yd-checkout-notice-${type}`;
            notice.textContent = message;
            
            this.elements.noticeContainer.appendChild(notice);
            
            // Scroll to notice
            this.elements.noticeContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Remove notice after a delay if it's a success message
            if (type === 'success') {
                setTimeout(() => {
                    notice.remove();
                }, 5000);
            }
        },
        
        /**
         * Clear all notices
         */
        clearNotices: function() {
            this.elements.noticeContainer.innerHTML = '';
        },
        
        /**
         * Set submit button state
         * @param {boolean} isEnabled - Whether the button should be enabled
         */
        setSubmitButtonState: function(isEnabled) {
            if (this.elements.submitButton) {
                this.elements.submitButton.disabled = !isEnabled;
                this.elements.submitButton.textContent = isEnabled ? 'Place Order' : 'Processing...';
            }
        }
    };

    /**
     * Address module - handles address management
     */
    const address = {
        // DOM elements
        elements: {
            shippingAddressGrid: null,
            billingAddressGrid: null,
            shippingAddressForm: null,
            billingAddressForm: null,
            addShippingAddressBtn: null,
            addBillingAddressBtn: null,
            saveShippingAddressBtn: null,
            saveBillingAddressBtn: null,
            updateShippingAddressBtn: null,
            updateBillingAddressBtn: null,
            cancelAddressBtns: null,
            differentBillingCheckbox: null,
            billingAddressSection: null,
            addressSearchField: null,
            editingShippingAddressId: null,
            editingBillingAddressId: null,
            shippingFormTitle: null,
            billingFormTitle: null
        },

        // State
        state: {
            hereApiKey: '',
            hereApiEnabled: false,
            isLoggedIn: false,
            billingAddressesLoaded: false,
            editingAddress: false,
            editingBillingAddress: false,
            shippingAddresses: [],
            billingAddresses: []
        },
        
        /**
         * Initialize address functionality
         */
        init: function() {
            // Cache elements
            this.cacheElements();
            
            // Set initial state
            this.state.hereApiKey = window.ydCheckoutSettings?.hereApiKey || '';
            this.state.hereApiEnabled = !!this.state.hereApiKey;
            this.state.isLoggedIn = document.body.classList.contains('logged-in');
            
            // Bind events
            this.bindEvents();
            
            // Initial setup for billing fields - make sure they're not required if hidden
            if (this.elements.differentBillingCheckbox && !this.elements.differentBillingCheckbox.checked) {
                this.toggleBillingFieldsRequired(false);
            }
            
            // Load saved addresses if user is logged in
            if (this.state.isLoggedIn) {
                this.loadAddressesByType('shipping');
            }
            
            // Initialize HERE API address search if enabled
            if (this.state.hereApiEnabled && this.elements.addressSearchField) {
                this.setupAddressSearch();
            }
        },
        
        /**
         * Cache commonly used DOM elements
         */
        cacheElements: function() {
            // Address grids
            this.elements.shippingAddressGrid = document.getElementById('shipping-addresses-grid');
            this.elements.billingAddressGrid = document.getElementById('billing-addresses-grid');
            
            // Address forms
            this.elements.shippingAddressForm = document.getElementById('yd-checkout-address-form');
            this.elements.billingAddressForm = document.getElementById('billing-address-form');
            
            // Add address buttons
            this.elements.addShippingAddressBtn = document.getElementById('yd-add-shipping-address');
            this.elements.addBillingAddressBtn = document.getElementById('yd-add-billing-address');
            
            // Save/update address buttons
            this.elements.saveShippingAddressBtn = document.getElementById('yd-save-shipping-address');
            this.elements.saveBillingAddressBtn = document.getElementById('yd-save-billing-address');
            this.elements.updateShippingAddressBtn = document.getElementById('yd-update-shipping-address');
            this.elements.updateBillingAddressBtn = document.getElementById('yd-update-billing-address');
            
            // Cancel buttons
            this.elements.cancelAddressBtns = document.querySelectorAll('.yd-cancel-address-btn');
            
            // Billing checkbox and section
            this.elements.differentBillingCheckbox = document.getElementById('different-billing');
            this.elements.billingAddressSection = document.getElementById('billing-address-section');
            
            // Address search field
            this.elements.addressSearchField = document.getElementById('yd-address-search');
            
            // Hidden address ID fields
            this.elements.editingShippingAddressId = document.getElementById('yd-editing-address-id');
            this.elements.editingBillingAddressId = document.getElementById('yd-editing-billing-address-id');
            
            // Form titles
            this.elements.shippingFormTitle = document.getElementById('address-form-title');
            this.elements.billingFormTitle = document.getElementById('billing-form-title');
        },
        
        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Toggle billing address section
            if (this.elements.differentBillingCheckbox && this.elements.billingAddressSection) {
                this.elements.differentBillingCheckbox.addEventListener('change', this.toggleBillingAddressSection.bind(this));
            }
            
            // Add new address buttons
            if (this.elements.addShippingAddressBtn) {
                this.elements.addShippingAddressBtn.addEventListener('click', () => {
                    this.showAddressForm('shipping', 'new');
                });
            }
            
            if (this.elements.addBillingAddressBtn) {
                this.elements.addBillingAddressBtn.addEventListener('click', () => {
                    this.showAddressForm('billing', 'new');
                });
            }
            
            // Save address buttons
            if (this.elements.saveShippingAddressBtn) {
                this.elements.saveShippingAddressBtn.addEventListener('click', () => {
                    this.saveAddress('shipping');
                });
            }
            
            if (this.elements.saveBillingAddressBtn) {
                this.elements.saveBillingAddressBtn.addEventListener('click', () => {
                    this.saveAddress('billing');
                });
            }
            
            // Update address buttons
            if (this.elements.updateShippingAddressBtn) {
                this.elements.updateShippingAddressBtn.addEventListener('click', () => {
                    this.updateAddress('shipping');
                });
            }
            
            if (this.elements.updateBillingAddressBtn) {
                this.elements.updateBillingAddressBtn.addEventListener('click', () => {
                    this.updateAddress('billing');
                });
            }
            
            // Cancel buttons
            this.elements.cancelAddressBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const isShipping = e.target.closest('#yd-checkout-address-form');
                    this.cancelAddressForm(isShipping ? 'shipping' : 'billing');
                });
            });
        },
        
        /**
         * Toggle billing address section visibility
         */
        toggleBillingAddressSection: function() {
            const isChecked = this.elements.differentBillingCheckbox.checked;
            
            if (isChecked) {
                this.elements.billingAddressSection.classList.remove('yd-hidden');
                this.toggleBillingFieldsRequired(true); // Add required attributes
                
                // Load billing addresses if not already loaded
                if (this.state.isLoggedIn && !this.state.billingAddressesLoaded) {
                    this.loadAddressesByType('billing');
                    this.state.billingAddressesLoaded = true;
                }
            } else {
                this.elements.billingAddressSection.classList.add('yd-hidden');
                this.toggleBillingFieldsRequired(false); // Remove required attributes
            }
        },

        /**
         * Toggle required attribute on billing address fields
         * @param {boolean} isRequired - Whether fields should be required
         */
        toggleBillingFieldsRequired: function(isRequired) {
            // Get all required input fields in the billing section
            const billingFields = document.querySelectorAll('#billing-address-section input[required], #billing-address-form input[required]');
            
            // Toggle the required attribute
            billingFields.forEach(field => {
                if (isRequired) {
                    field.setAttribute('required', '');
                } else {
                    field.removeAttribute('required');
                }
            });
        },
        
        /**
         * Show address form for adding new or editing
         * @param {string} type - The address type (shipping or billing)
         * @param {string} mode - 'new' or 'edit'
         * @param {Object} addressData - Address data when editing
         */
        showAddressForm: function(type, mode, addressData = null) {
            const form = type === 'shipping' ? this.elements.shippingAddressForm : this.elements.billingAddressForm;
            const formTitle = type === 'shipping' ? this.elements.shippingFormTitle : this.elements.billingFormTitle;
            const saveBtn = type === 'shipping' ? this.elements.saveShippingAddressBtn : this.elements.saveBillingAddressBtn;
            const updateBtn = type === 'shipping' ? this.elements.updateShippingAddressBtn : this.elements.updateBillingAddressBtn;
            const editingIdField = type === 'shipping' ? this.elements.editingShippingAddressId : this.elements.editingBillingAddressId;
            
            if (!form) return;
            
            // Reset form
            form.reset && form.reset();
            
            // Show form
            form.classList.remove('yd-hidden');
            
            if (mode === 'new') {
                // Set title
                formTitle.textContent = type === 'shipping' 
                    ? 'Add New Address' 
                    : 'Add New Billing Address';
                
                // Show save button, hide update button
                saveBtn.style.display = 'block';
                updateBtn.style.display = 'none';
                
                // Clear editing ID
                editingIdField.value = '';
                
                // Update state
                if (type === 'shipping') {
                    this.state.editingAddress = false;
                } else {
                    this.state.editingBillingAddress = false;
                }
            } else if (mode === 'edit' && addressData) {
                // Set title
                formTitle.textContent = type === 'shipping' 
                    ? 'Edit Address' 
                    : 'Edit Billing Address';
                
                formTitle.style.display = 'none';
                    
                // Hide save button, show update button
                saveBtn.style.display = 'none';
                updateBtn.style.display = 'block';
                
                // Set editing ID
                editingIdField.value = addressData.id;
                
                // Fill form with address data
                this.fillAddressForm(type, addressData);
                
                // Update state
                if (type === 'shipping') {
                    this.state.editingAddress = true;
                } else {
                    this.state.editingBillingAddress = true;
                }
            }
            
            // Scroll to form
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
        
        /**
         * Fill address form with data
         * @param {string} type - The address type (shipping or billing)
         * @param {Object} addressData - The address data
         */
        fillAddressForm: function(type, addressData) {
            if (!addressData) {
                console.error('No address data provided for filling the form');
                return;
            }
            
            // For debugging - log what we received
            console.log('Filling form with address data:', addressData);
            
            const prefix = type === 'shipping' ? 'yd-address-' : 'billing-address-';
            
            // Helper function to safely set field values
            const setFieldValue = (fieldId, value) => {
                const field = document.getElementById(prefix + fieldId);
                if (field) {
                    if (fieldId === 'country' && field.tagName === 'SELECT') {
                        // For country select field
                        for (let i = 0; i < field.options.length; i++) {
                            if (field.options[i].value === value) {
                                field.selectedIndex = i;
                                break;
                            }
                        }
                    } else {
                        // For normal text inputs
                        field.value = value || '';
                    }
                } else {
                    console.warn(`Field ${prefix + fieldId} not found in form`);
                }
            };
            
            // Basic fields - set safely
            setFieldValue('first-name', addressData.first_name);
            setFieldValue('last-name', addressData.last_name);
            setFieldValue('street', addressData.street);
            setFieldValue('house-number', addressData.house_number);
            setFieldValue('postal-code', addressData.postal_code);
            setFieldValue('city', addressData.city);
            setFieldValue('country', addressData.country);
            setFieldValue('name', addressData.name || addressData.address_name);
            
            // Default checkbox
            const defaultCheckbox = document.getElementById(type === 'shipping' ? 'default-address' : 'default-billing-address');
            if (defaultCheckbox) {
                defaultCheckbox.checked = !!addressData.is_default;
            }
        },        
        
        /**
         * Cancel address form
         * @param {string} type - The address type (shipping or billing)
         */
        cancelAddressForm: function(type) {
            const form = type === 'shipping' ? this.elements.shippingAddressForm : this.elements.billingAddressForm;
            
            if (form) {
                // Hide form
                form.classList.add('yd-hidden');
                
                // Reset form if it's a proper form element
                if (form.reset) {
                    form.reset();
                }
            }
        },
        
        /**
         * Save new address
         * @param {string} type - The address type (shipping or billing)
         */
        saveAddress: function(type) {
            // Only save if user is logged in
            if (!this.state.isLoggedIn) {
                // Just hide the form for non-logged in users
                this.cancelAddressForm(type);
                return;
            }
            
            const form = type === 'shipping' ? this.elements.shippingAddressForm : this.elements.billingAddressForm;
            const prefix = type === 'shipping' ? 'yd-address-' : 'billing-address-';
            
            // Validate form
            if (!this.validateAddressForm(type)) {
                return;
            }
            
            // Get form data
            const addressData = {
                first_name: document.getElementById(prefix + 'first-name').value,
                last_name: document.getElementById(prefix + 'last-name').value,
                street: document.getElementById(prefix + 'street').value,
                house_number: document.getElementById(prefix + 'house-number').value,
                postal_code: document.getElementById(prefix + 'postal-code').value,
                city: document.getElementById(prefix + 'city').value,
                country: document.getElementById(prefix + 'country').value,
                name: document.getElementById(prefix + 'name')?.value || '',
                is_default: document.getElementById(type === 'shipping' ? 'default-address' : 'default-billing-address')?.checked || false,
                address_type: type
            };
            
            // Submit to server
            this.submitAddressToServer('save', type, addressData);
        },
        
        /**
         * Update existing address
         * @param {string} type - The address type (shipping or billing)
         */
        updateAddress: function(type) {
            // Only update if user is logged in
            if (!this.state.isLoggedIn) {
                // Just hide the form for non-logged in users
                this.cancelAddressForm(type);
                return;
            }
            
            const editingIdField = type === 'shipping' ? this.elements.editingShippingAddressId : this.elements.editingBillingAddressId;
            const addressId = editingIdField.value;
            
            if (!addressId) {
                YDCheckout.core.showNotice('No address selected for update.', 'error');
                return;
            }
            
            // Validate form
            if (!this.validateAddressForm(type)) {
                return;
            }
            
            const prefix = type === 'shipping' ? 'yd-address-' : 'billing-address-';
            
            // Get form data
            const addressData = {
                id: addressId,
                first_name: document.getElementById(prefix + 'first-name').value,
                last_name: document.getElementById(prefix + 'last-name').value,
                street: document.getElementById(prefix + 'street').value,
                house_number: document.getElementById(prefix + 'house-number').value,
                postal_code: document.getElementById(prefix + 'postal-code').value,
                city: document.getElementById(prefix + 'city').value,
                country: document.getElementById(prefix + 'country').value,
                name: document.getElementById(prefix + 'name')?.value || '',
                is_default: document.getElementById(type === 'shipping' ? 'default-address' : 'default-billing-address')?.checked || false,
                address_type: type
            };
            
            // Submit to server
            this.submitAddressToServer('update', type, addressData);
        },
        
        /**
         * Submit address to server (save or update)
         * @param {string} action - 'save' or 'update'
         * @param {string} type - The address type (shipping or billing)
         * @param {Object} addressData - The address data
         */
        submitAddressToServer: function(action, type, addressData) {
            // Create form data for submission
            const formData = new FormData();
            formData.append('action', action === 'save' ? 'yd_checkout_save_address' : 'yd_checkout_update_address');
            formData.append('nonce', YDCheckout.core.config.nonce);
            formData.append('address_type', type);
            
            // Add all address data
            Object.keys(addressData).forEach(key => {
                formData.append(key, addressData[key]);
            });
            
            // Submit via fetch
            fetch(YDCheckout.core.config.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    YDCheckout.core.showNotice(data.data.message || 'Address saved successfully.', 'success');
                    
                    // Reload addresses
                    this.loadAddressesByType(type);
                    
                    // Hide form
                    this.cancelAddressForm(type);
                } else {
                    // Show error
                    YDCheckout.core.showNotice(data.data.message || 'Error saving address.', 'error');
                }
            })
            .catch(error => {
                YDCheckout.core.showNotice('Error: ' + error.message, 'error');
            });
        },
        
        /**
         * Load addresses by type
         * @param {string} type - The address type (shipping or billing)
         */
        loadAddressesByType: function(type) {
            const container = type === 'shipping' ? this.elements.shippingAddressGrid : this.elements.billingAddressGrid;
            
            if (!container) return;
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', YDCheckout.core.config.ajaxUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success && response.data.addresses) {
                            // Save addresses to state
                            if (type === 'shipping') {
                                this.state.shippingAddresses = response.data.addresses;
                            } else {
                                this.state.billingAddresses = response.data.addresses;
                            }
                            
                            // Render address cards
                            this.renderAddressCards(response.data.addresses, type, container);
                        } else {
                            // Show empty state with add button only
                            this.renderEmptyAddressState(type, container);
                        }
                    } catch (e) {
                        console.error('Error parsing address data:', e);
                        this.renderEmptyAddressState(type, container);
                    }
                } else {
                    this.renderEmptyAddressState(type, container);
                }
            }.bind(this);
            
            xhr.onerror = function() {
                this.renderEmptyAddressState(type, container);
            }.bind(this);
            
            xhr.send('action=yd_checkout_get_addresses_by_type&nonce=' + YDCheckout.core.config.nonce + '&address_type=' + type);
        },
        
        /**
         * Render address cards in the grid
         * @param {Array} addresses - The addresses to render
         * @param {string} type - The address type (shipping or billing)
         * @param {HTMLElement} container - The container element
         */
        renderAddressCards: function(addresses, type, container) {
            // Clear existing content except the add new button
            const addNewBtn = container.querySelector('.yd-address-add-new');
            container.innerHTML = '';
            
            if (addresses.length === 0) {
                // Just show the add new button
                container.appendChild(addNewBtn);
                return;
            }
            
            // Render each address card
            addresses.forEach(address => {
                const card = this.createAddressCard(address, type);
                container.appendChild(card);
            });
            
            // Add the add new button back
            container.appendChild(addNewBtn);
            
            // Attach event listeners
            this.attachAddressCardListeners(container, type);
        },
        
        /**
         * Create address card element
         * @param {Object} address - The address data
         * @param {string} type - The address type
         * @returns {HTMLElement} The address card element
         */
        createAddressCard: function(address, type) {
            const card = document.createElement('div');
            card.className = 'yd-checkout-address-card';
            card.dataset.addressId = address.id;
            
            if (address.is_default) {
                card.classList.add('yd-address-selected');
            }
            
            // Create formatted address summary
            const formattedAddress = typeof address.formatted === 'string' ? address.formatted : 
                `${address.street || ''} ${address.house_number || ''}, ${address.postal_code || ''} ${address.city || ''}, ${address.country || ''}`;
            
            card.innerHTML = `
                <div class="yd-address-card-content">
                    <h3 class="yd-address-name">${address.name || 'Address ' + address.id}</h3>
                    <p class="yd-address-summary">${formattedAddress}</p>
                    <input type="radio" name="${type}_address_id" id="${type}_address_${address.id}" 
                        value="${address.id}" class="yd-address-radio" ${address.is_default ? 'checked' : ''}>
                </div>
            `;
            
            return card;
        },
        
        /**
         * Render empty address state
         * @param {string} type - The address type
         * @param {HTMLElement} container - The container element
         */
        renderEmptyAddressState: function(type, container) {
            // Clear all existing content
            container.innerHTML = '';
            
            // Create and add the "add new" button
            const addNewBtn = document.createElement('div');
            addNewBtn.className = 'yd-checkout-address-card yd-address-add-new';
            addNewBtn.id = type === 'shipping' ? 'yd-add-shipping-address' : 'yd-add-billing-address';
            addNewBtn.innerHTML = `
                <div class="yd-address-card-content">
                    <span class="yd-address-add-icon">+</span>
                    <span class="yd-address-add-text">add new address</span>
                </div>
            `;
            
            container.appendChild(addNewBtn);
            
            // Re-attach event listener
            addNewBtn.addEventListener('click', () => {
                this.showAddressForm(type, 'new');
            });
        },
        
        /**
         * Attach event listeners to address cards
         * @param {HTMLElement} container - The container element
         * @param {string} type - The address type
         */
        attachAddressCardListeners: function(container, type) {
            // Card selection
            const cards = container.querySelectorAll('.yd-checkout-address-card:not(.yd-address-add-new)');
            cards.forEach(card => {
                card.addEventListener('click', (e) => {
                    // Select the address card
                    const radio = card.querySelector('.yd-address-radio');
                    if (radio) {
                        radio.checked = true;
                        
                        // Update visual selection
                        cards.forEach(c => c.classList.remove('yd-address-selected'));
                        card.classList.add('yd-address-selected');
                        
                        // Fill form fields with this address data for checkout
                        const addressId = card.dataset.addressId;
                        const addresses = type === 'shipping' ? this.state.shippingAddresses : this.state.billingAddresses;
                        const addressData = addresses.find(a => a.id.toString() === addressId);
                        
                        if (addressData) {
                            this.fillCheckoutFields(type, addressData);
                            this.showAddressForm(type, 'edit', addressData);
                        }
                    }
                });
            });
            // Select first card
            cards[0].click();

            // "Add new" button
            const addNewBtn = container.querySelector('.yd-address-add-new');
            if (addNewBtn) {
                addNewBtn.addEventListener('click', () => {
                    this.showAddressForm(type, 'new');
                });
            }
        },
        
        /**
         * Fill checkout fields with address data
         * @param {string} type - The address type
         * @param {Object} addressData - The address data
         */
        fillCheckoutFields: function(type, addressData) {
            // This just ensures the address ID is set for checkout processing
            // Actual form filling happens when user edits the address
        },
        
        /**
         * Setup address search with HERE API
         */
        setupAddressSearch: function() {
            if (!this.state.hereApiEnabled || !this.elements.addressSearchField) return;
            
            // Autocomplete results container
            const autocompleteResults = document.createElement('div');
            autocompleteResults.classList.add('yd-address-autocomplete-results');
            autocompleteResults.style.display = 'none';
            this.elements.addressSearchField.parentNode.appendChild(autocompleteResults);
            
            // Setup event listeners
            let searchTimeout = null;
            let lastQuery = '';
            
            this.elements.addressSearchField.addEventListener('input', function() {
                
                const query = this.elements.addressSearchField.value.trim();
                
                // Minimum query length
                if (query.length < 3 || query === lastQuery) return;
                
                lastQuery = query;
                
                // Clear previous timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                // Debounced search
                searchTimeout = setTimeout(() => {
                    this.searchAddress(query, autocompleteResults);
                }, 300);
            }.bind(this));
            
            // Handle clicks outside autocomplete
            document.addEventListener('click', function(e) {
                if (!autocompleteResults.contains(e.target) && e.target !== this.elements.addressSearchField) {
                    autocompleteResults.style.display = 'none';
                }
            }.bind(this));
            
            // Also setup for billing address search if it exists
            const billingAddressSearch = document.getElementById('billing-address-search');
            if (billingAddressSearch) {
                this.setupAddressSearchField(billingAddressSearch, {
                    firstNameField: 'billing-address-first-name',
                    lastNameField: 'billing-address-last-name',
                    streetField: 'billing-address-street',
                    houseNumberField: 'billing-address-house-number',
                    postalCodeField: 'billing-address-postal-code',
                    cityField: 'billing-address-city',
                    countryField: 'billing-address-country'
                });
            }
        },
        
        /**
         * Setup address search for a specific field
         * @param {HTMLElement} searchField - The search field element
         * @param {Object} fieldMapping - Mapping of address fields to element IDs
         */
        setupAddressSearchField: function(searchField, fieldMapping) {
            if (!this.state.hereApiEnabled || !searchField) return;
            
            // Autocomplete results container
            const autocompleteResults = document.createElement('div');
            autocompleteResults.classList.add('yd-address-autocomplete-results');
            autocompleteResults.style.display = 'none';
            searchField.parentNode.appendChild(autocompleteResults);
            
            // Setup event listeners
            let searchTimeout = null;
            let lastQuery = '';
            
            searchField.addEventListener('input', function() {
                const query = this.value.trim();
                
                // Minimum query length
                if (query.length < 3 || query === lastQuery) return;
                
                lastQuery = query;
                
                // Clear previous timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                // Debounced search
                searchTimeout = setTimeout(() => {
                    this.searchAddress(query, autocompleteResults, fieldMapping);
                }, 300);
            }.bind(this));
            
            // Handle clicks outside autocomplete
            document.addEventListener('click', function(e) {
                if (!autocompleteResults.contains(e.target) && e.target !== searchField) {
                    autocompleteResults.style.display = 'none';
                }
            });
        },
        
        /**
         * ISO 3166-1 alpha-3 to alpha-2 country code mapping
         * @type {Object}
         */
        countryCodeMap: {
            'AFG': 'AF', 'ALA': 'AX', 'ALB': 'AL', 'DZA': 'DZ', 'ASM': 'AS', 'AND': 'AD', 'AGO': 'AO', 
            'AIA': 'AI', 'ATA': 'AQ', 'ATG': 'AG', 'ARG': 'AR', 'ARM': 'AM', 'ABW': 'AW', 'AUS': 'AU', 
            'AUT': 'AT', 'AZE': 'AZ', 'BHS': 'BS', 'BHR': 'BH', 'BGD': 'BD', 'BRB': 'BB', 'BLR': 'BY', 
            'BEL': 'BE', 'BLZ': 'BZ', 'BEN': 'BJ', 'BMU': 'BM', 'BTN': 'BT', 'BOL': 'BO', 'BIH': 'BA', 
            'BWA': 'BW', 'BVT': 'BV', 'BRA': 'BR', 'VGB': 'VG', 'IOT': 'IO', 'BRN': 'BN', 'BGR': 'BG', 
            'BFA': 'BF', 'BDI': 'BI', 'KHM': 'KH', 'CMR': 'CM', 'CAN': 'CA', 'CPV': 'CV', 'CYM': 'KY', 
            'CAF': 'CF', 'TCD': 'TD', 'CHL': 'CL', 'CHN': 'CN', 'HKG': 'HK', 'MAC': 'MO', 'CXR': 'CX', 
            'CCK': 'CC', 'COL': 'CO', 'COM': 'KM', 'COG': 'CG', 'COD': 'CD', 'COK': 'CK', 'CRI': 'CR', 
            'CIV': 'CI', 'HRV': 'HR', 'CUB': 'CU', 'CYP': 'CY', 'CZE': 'CZ', 'DNK': 'DK', 'DJI': 'DJ', 
            'DMA': 'DM', 'DOM': 'DO', 'ECU': 'EC', 'EGY': 'EG', 'SLV': 'SV', 'GNQ': 'GQ', 'ERI': 'ER', 
            'EST': 'EE', 'ETH': 'ET', 'FLK': 'FK', 'FRO': 'FO', 'FJI': 'FJ', 'FIN': 'FI', 'FRA': 'FR', 
            'GUF': 'GF', 'PYF': 'PF', 'ATF': 'TF', 'GAB': 'GA', 'GMB': 'GM', 'GEO': 'GE', 'DEU': 'DE', 
            'GHA': 'GH', 'GIB': 'GI', 'GRC': 'GR', 'GRL': 'GL', 'GRD': 'GD', 'GLP': 'GP', 'GUM': 'GU', 
            'GTM': 'GT', 'GGY': 'GG', 'GIN': 'GN', 'GNB': 'GW', 'GUY': 'GY', 'HTI': 'HT', 'HMD': 'HM', 
            'VAT': 'VA', 'HND': 'HN', 'HUN': 'HU', 'ISL': 'IS', 'IND': 'IN', 'IDN': 'ID', 'IRN': 'IR', 
            'IRQ': 'IQ', 'IRL': 'IE', 'IMN': 'IM', 'ISR': 'IL', 'ITA': 'IT', 'JAM': 'JM', 'JPN': 'JP', 
            'JEY': 'JE', 'JOR': 'JO', 'KAZ': 'KZ', 'KEN': 'KE', 'KIR': 'KI', 'PRK': 'KP', 'KOR': 'KR', 
            'KWT': 'KW', 'KGZ': 'KG', 'LAO': 'LA', 'LVA': 'LV', 'LBN': 'LB', 'LSO': 'LS', 'LBR': 'LR', 
            'LBY': 'LY', 'LIE': 'LI', 'LTU': 'LT', 'LUX': 'LU', 'MKD': 'MK', 'MDG': 'MG', 'MWI': 'MW', 
            'MYS': 'MY', 'MDV': 'MV', 'MLI': 'ML', 'MLT': 'MT', 'MHL': 'MH', 'MTQ': 'MQ', 'MRT': 'MR', 
            'MUS': 'MU', 'MYT': 'YT', 'MEX': 'MX', 'FSM': 'FM', 'MDA': 'MD', 'MCO': 'MC', 'MNG': 'MN', 
            'MNE': 'ME', 'MSR': 'MS', 'MAR': 'MA', 'MOZ': 'MZ', 'MMR': 'MM', 'NAM': 'NA', 'NRU': 'NR', 
            'NPL': 'NP', 'NLD': 'NL', 'ANT': 'AN', 'NCL': 'NC', 'NZL': 'NZ', 'NIC': 'NI', 'NER': 'NE', 
            'NGA': 'NG', 'NIU': 'NU', 'NFK': 'NF', 'MNP': 'MP', 'NOR': 'NO', 'OMN': 'OM', 'PAK': 'PK', 
            'PLW': 'PW', 'PSE': 'PS', 'PAN': 'PA', 'PNG': 'PG', 'PRY': 'PY', 'PER': 'PE', 'PHL': 'PH', 
            'PCN': 'PN', 'POL': 'PL', 'PRT': 'PT', 'PRI': 'PR', 'QAT': 'QA', 'REU': 'RE', 'ROU': 'RO', 
            'RUS': 'RU', 'RWA': 'RW', 'BLM': 'BL', 'SHN': 'SH', 'KNA': 'KN', 'LCA': 'LC', 'MAF': 'MF', 
            'SPM': 'PM', 'VCT': 'VC', 'WSM': 'WS', 'SMR': 'SM', 'STP': 'ST', 'SAU': 'SA', 'SEN': 'SN', 
            'SRB': 'RS', 'SYC': 'SC', 'SLE': 'SL', 'SGP': 'SG', 'SVK': 'SK', 'SVN': 'SI', 'SLB': 'SB', 
            'SOM': 'SO', 'ZAF': 'ZA', 'SGS': 'GS', 'SSD': 'SS', 'ESP': 'ES', 'LKA': 'LK', 'SDN': 'SD', 
            'SUR': 'SR', 'SJM': 'SJ', 'SWZ': 'SZ', 'SWE': 'SE', 'CHE': 'CH', 'SYR': 'SY', 'TWN': 'TW', 
            'TJK': 'TJ', 'TZA': 'TZ', 'THA': 'TH', 'TLS': 'TL', 'TGO': 'TG', 'TKL': 'TK', 'TON': 'TO', 
            'TTO': 'TT', 'TUN': 'TN', 'TUR': 'TR', 'TKM': 'TM', 'TCA': 'TC', 'TUV': 'TV', 'UGA': 'UG', 
            'UKR': 'UA', 'ARE': 'AE', 'GBR': 'GB', 'USA': 'US', 'UMI': 'UM', 'URY': 'UY', 'UZB': 'UZ', 
            'VUT': 'VU', 'VEN': 'VE', 'VNM': 'VN', 'VIR': 'VI', 'WLF': 'WF', 'ESH': 'EH', 'YEM': 'YE', 
            'ZMB': 'ZM', 'ZWE': 'ZW'
        },

        /**
         * Convert ISO3 country code to ISO2
         * @param {string} iso3Code - The ISO3 country code
         * @returns {string} The ISO2 country code, or original code if not found
         */
        convertIso3ToIso2: function(iso3Code) {
            if (!iso3Code) return '';
            
            // If already ISO2, return as is
            if (iso3Code.length === 2) {
                return iso3Code;
            }
            
            // Convert to uppercase for consistent lookup
            const code = iso3Code.toUpperCase();
            
            // Return the mapped ISO2 code or the original if not found
            return this.countryCodeMap[code] || iso3Code;
        },
        
        /**
         * Search addresses using HERE API
         * @param {string} query - The search query
         * @param {HTMLElement} resultsContainer - The container for displaying results
         * @param {Object} fieldMapping - Optional mapping of address fields to element IDs
         */
        searchAddress: function(query, resultsContainer, fieldMapping = null) {
            if (!query || !this.state.hereApiKey) return;
            
            const url = `https://autocomplete.search.hereapi.com/v1/autocomplete?apiKey=${this.state.hereApiKey}&q=${encodeURIComponent(query)}&limit=5`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    console.log('HERE API Response:', data);  // Log the API response for debugging
                    this.displayAutocompleteResults(data.items || [], resultsContainer, fieldMapping);
                })
                .catch(error => {
                    console.error('Address search error:', error);
                });
        },
        
        /**
         * Display autocomplete results
         * @param {Array} results - Autocomplete results
         * @param {HTMLElement} resultsContainer - The container for displaying results
         * @param {Object} fieldMapping - Optional mapping of address fields to element IDs
         */
        displayAutocompleteResults: function(results, resultsContainer, fieldMapping = null) {
            // Clear previous results
            resultsContainer.innerHTML = '';
            
            if (results.length === 0) {
                resultsContainer.style.display = 'none';
                return;
            }
            
            // Default field mapping if not provided
            if (!fieldMapping) {
                fieldMapping = {
                    firstNameField: 'yd-address-first-name',
                    lastNameField: 'yd-address-last-name',
                    streetField: 'yd-address-street',
                    houseNumberField: 'yd-address-house-number',
                    postalCodeField: 'yd-address-postal-code',
                    cityField: 'yd-address-city',
                    countryField: 'yd-address-country'
                };
            }
            
            // Create result items
            results.forEach(result => {
                const item = document.createElement('div');
                item.classList.add('yd-address-autocomplete-item');
                item.textContent = result.title;
                
                // Click event to select address
                item.addEventListener('click', () => {
                    this.selectAutocompleteAddress(result, fieldMapping);
                    resultsContainer.style.display = 'none';
                });
                
                resultsContainer.appendChild(item);
            });
            
            // Show results
            resultsContainer.style.display = 'block';
        },
        
        /**
         * Select an address from autocomplete
         * @param {Object} addressData - Selected address data
         * @param {Object} fieldMapping - Mapping of address fields to element IDs
         */
        selectAutocompleteAddress: function(addressData, fieldMapping) {
            if (!addressData || !addressData.address) return;
            
            const address = addressData.address;
            
            // Populate form fields
            const fields = {
                street: document.getElementById(fieldMapping.streetField),
                houseNumber: document.getElementById(fieldMapping.houseNumberField),
                postalCode: document.getElementById(fieldMapping.postalCodeField),
                city: document.getElementById(fieldMapping.cityField),
                country: document.getElementById(fieldMapping.countryField)
            };
            
            if (fields.street) {
                fields.street.value = address.street || '';
            }
            
            if (fields.houseNumber) {
                fields.houseNumber.value = address.houseNumber || '';
            }
            
            if (fields.postalCode) {
                fields.postalCode.value = address.postalCode || '';
            }
            
            if (fields.city) {
                fields.city.value = address.city || '';
            }
            
            // Updated country handling with ISO3 to ISO2 conversion
            if (fields.country && address.countryCode) {
                const countrySelect = fields.country;
                // Convert ISO3 to ISO2 if needed
                const countryCode = this.convertIso3ToIso2(address.countryCode);
                
                console.log('HERE API returned country code:', address.countryCode);
                console.log('Converted to ISO2:', countryCode);
                
                // Find and select the option with this value
                let optionFound = false;
                for (let i = 0; i < countrySelect.options.length; i++) {
                    if (countrySelect.options[i].value === countryCode) {
                        countrySelect.selectedIndex = i;
                        optionFound = true;
                        break;
                    }
                }
                
                // If no matching option was found, log a warning
                if (!optionFound) {
                    console.warn('No matching country option found for code:', countryCode);
                }
            }
        },
        
        /**
         * Validate addresses before checkout
         * @returns {boolean} Whether the addresses are valid
         */
        validateAddresses: function() {
            let isValid = true;
            
            // Check shipping address
            const selectedShippingAddress = document.querySelector('input[name="shipping_address_id"]:checked');
            const isShippingAddressFormVisible = this.elements.shippingAddressForm && !this.elements.shippingAddressForm.classList.contains('yd-hidden');
            
            if (!selectedShippingAddress && !isShippingAddressFormVisible) {
                YDCheckout.core.showNotice('Please select a shipping address.', 'error');
                isValid = false;
            } else if (isShippingAddressFormVisible) {
                // Validate shipping address form
                if (!this.validateAddressForm('shipping')) {
                    isValid = false;
                }
            }
            
            // Only check billing address if different from shipping is checked
            const differentBilling = this.elements.differentBillingCheckbox && this.elements.differentBillingCheckbox.checked;
            
            if (differentBilling) {
                const selectedBillingAddress = document.querySelector('input[name="billing_address_id"]:checked');
                const isBillingAddressFormVisible = this.elements.billingAddressForm && !this.elements.billingAddressForm.classList.contains('yd-hidden');
                
                if (!selectedBillingAddress && !isBillingAddressFormVisible) {
                    YDCheckout.core.showNotice('Please select a billing address.', 'error');
                    isValid = false;
                } else if (isBillingAddressFormVisible) {
                    // Validate billing address form
                    if (!this.validateAddressForm('billing')) {
                        isValid = false;
                    }
                }
            }
            
            return isValid;
        },
        
        /**
         * Validate address form fields
         * @param {string} type - The address type (shipping or billing)
         * @returns {boolean} Whether the form is valid
         */
        validateAddressForm: function(type) {
            const form = type === 'shipping' ? this.elements.shippingAddressForm : this.elements.billingAddressForm;
            let isValid = true;
            
            if (!form || form.classList.contains('yd-hidden')) return true;
            
            // Only validate visible forms
            // Check required fields
            const requiredFields = form.querySelectorAll('input[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('yd-field-error');
                    isValid = false;
                } else {
                    field.classList.remove('yd-field-error');
                }
            });
            
            if (!isValid) {
                YDCheckout.core.showNotice(`Please fill in all required ${type} address fields.`, 'error');
            }
            
            return isValid;
        }
    };

    /**
     * Payment module - handles payment methods and processing
     */
    const payment = {
        // DOM elements
        elements: {
            paymentMethodsContainer: null,
            paymentMethods: null,
            paymentDescriptions: null,
            stripeContainer: null,
            paypalContainer: null
        },

        // Payment state
        state: {
            stripeLoaded: false,
            paypalLoaded: false,
            stripe: null,
            elements: null,
            paymentElement: null,
            stripePaymentMethodId: null,
            paypalOrderId: null
        },

        // Configuration
        config: {
            stripePublishableKey: '',
            paypalClientId: ''
        },
        
        /**
         * Initialize payment functionality
         */
        init: function() {
            // Cache elements
            this.cacheElements();
            
            // Set configuration from global vars
            if (window.ydCheckoutStripe) {
                this.config.stripePublishableKey = window.ydCheckoutStripe.publishableKey || '';
            }
            
            if (window.ydCheckoutPayPal) {
                this.config.paypalClientId = window.ydCheckoutPayPal.clientId || '';
            }
            
            // Bind events
            this.bindEvents();
            
            // Display the initial selected payment method
            this.showSelectedPaymentMethod();
        },
        
        /**
         * Cache commonly used DOM elements
         */
        cacheElements: function() {
            this.elements.paymentMethodsContainer = document.querySelector('.yd-checkout-payment-methods');
            this.elements.paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            this.elements.paymentDescriptions = document.querySelectorAll('.yd-checkout-payment-description');
            this.elements.stripeContainer = document.getElementById('payment_fields_stripe');
            this.elements.paypalContainer = document.getElementById('payment_fields_paypal');
        },
        
        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Payment method selection
            this.elements.paymentMethods.forEach(method => {
                method.addEventListener('change', this.handlePaymentMethodChange.bind(this));
            });

            // Document-level event for payment method selection
            document.addEventListener('payment_method_selected', this.handlePaymentMethodEvent.bind(this));
            
            // Initial method display
            this.showSelectedPaymentMethod();
        },
        
        /**
         * Handle payment method change
         * @param {Event} event - Change event
         */
        handlePaymentMethodChange: function(event) {
            const methodId = event.target.value;
            
            // Trigger custom event
            const customEvent = new CustomEvent('payment_method_selected', {
                detail: {
                    methodId: methodId
                }
            });
            
            document.dispatchEvent(customEvent);
            
            // Show selected method description and fields
            this.showSelectedPaymentMethod();
        },
        
        /**
         * Handle payment method custom event
         * @param {CustomEvent} event - Custom event
         */
        handlePaymentMethodEvent: function(event) {
            const methodId = event.detail.methodId;
            
            // Initialize specific payment method
            switch (methodId) {
                case 'stripe':
                    this.initStripe();
                    break;
                case 'paypal':
                    this.initPayPal();
                    break;
            }
        },
        
        /**
         * Show selected payment method details
         */
        showSelectedPaymentMethod: function() {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (selectedMethod) {
                // Hide all descriptions and fields
                this.elements.paymentDescriptions.forEach(desc => {
                    desc.style.display = 'none';
                });
                
                const paymentFields = document.querySelectorAll('.yd-checkout-payment-method-fields');
                paymentFields.forEach(field => {
                    field.style.display = 'none';
                });

                const allActionerButtons = document.querySelectorAll('.yd-checkout-payment-method');
                allActionerButtons.forEach(button => {
                    button.classList.remove('selected');
                });
                
                // Show selected method description and fields
                const descriptionId = 'payment_description_' + selectedMethod.value;
                const fieldsId = 'payment_fields_' + selectedMethod.value;
                
                const description = document.getElementById(descriptionId);
                const fields = document.getElementById(fieldsId);
                const actionerButton = document.querySelector(`.yd-checkout-payment-method.payment_${selectedMethod.value}`);
                
                if (description) description.style.display = 'block';
                if (fields) fields.style.display = 'block';
                if(actionerButton) actionerButton.classList.add('selected');
                
            }
        },
        
        /**
         * Get the selected payment method
         * @returns {string} The selected payment method ID
         */
        getSelectedMethod: function() {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            return selectedMethod ? selectedMethod.value : '';
        },
        
        /**
         * Validate payment method selection
         * @returns {boolean} Whether a payment method is selected
         */
        validatePaymentMethod: function() {
            const selectedMethod = this.getSelectedMethod();
            
            if (!selectedMethod) {
                YDCheckout.core.showNotice('Please select a payment method.', 'error');
                return false;
            }
            
            return true;
        },
        
        /**
         * Initialize Stripe
         */
        initStripe: function() {
            // Skip if already loaded or no Stripe key
            if (this.state.stripeLoaded || !this.config.stripePublishableKey) return;
            
            // Check if Stripe is already loaded
            if (window.Stripe) {
                this.initializeStripeElements();
            } else {
                // Load Stripe.js
                const script = document.createElement('script');
                script.src = 'https://js.stripe.com/v3/';
                script.async = true;
                
                script.onload = this.initializeStripeElements.bind(this);
                script.onerror = () => {
                    console.error('Failed to load Stripe.js');
                };
                
                document.body.appendChild(script);
            }
        },
        
        /**
         * Initialize Stripe Elements
         */
        initializeStripeElements: function() {
            // Initialize Stripe
            this.state.stripe = Stripe(this.config.stripePublishableKey);
            
            // Create Elements instance
            this.state.elements = this.state.stripe.elements();
            
            // Create Payment Element
            this.state.paymentElement = this.state.elements.create('payment', {
                layout: {
                    type: 'tabs',
                    defaultCollapsed: false
                }
            });
            
            // Create container for Payment Element if needed
            if (!document.getElementById('stripe-payment-element')) {
                const container = document.createElement('div');
                container.id = 'stripe-payment-element';
                
                if (this.elements.stripeContainer) {
                    this.elements.stripeContainer.appendChild(container);
                } else {
                    // Create container if it doesn't exist
                    const paymentMethodContainer = document.querySelector('.yd-checkout-payment-description[id="payment_description_stripe"]');
                    if (paymentMethodContainer) {
                        const newContainer = document.createElement('div');
                        newContainer.id = 'payment_fields_stripe';
                        newContainer.className = 'yd-checkout-payment-method-fields';
                        newContainer.innerHTML = '<div id="stripe-payment-element"></div>';
                        paymentMethodContainer.after(newContainer);
                    }
                }
            }
            
            // Mount Payment Element
            try {
                this.state.paymentElement.mount('#stripe-payment-element');
                this.state.stripeLoaded = true;
            } catch (error) {
                console.error('Failed to mount Stripe Element:', error);
            }
            
            // Add hidden inputs for payment processing if they don't exist
            this.ensureHiddenInputs([
                { id: 'stripe-payment-method-id', name: 'stripe_payment_method_id' },
                { id: 'stripe-payment-intent-id', name: 'stripe_payment_intent_id' }
            ]);
        },
        
        /**
         * Process Stripe payment
         */
        processStripePayment: function() {
            if (!this.state.stripe || !this.state.elements) {
                YDCheckout.core.showNotice('Stripe is not properly initialized.', 'error');
                YDCheckout.core.setSubmitButtonState(true);
                return;
            }
            
            // Create payment method
            this.state.stripe.createPaymentMethod({
                type: 'card',
                card: this.state.paymentElement,
                billing_details: this.getBillingDetails()
            })
            .then(result => {
                if (result.error) {
                    throw result.error;
                }
                
                // Set payment method ID
                document.getElementById('stripe-payment-method-id').value = result.paymentMethod.id;
                this.state.stripePaymentMethodId = result.paymentMethod.id;
                
                // Process order
                YDCheckout.core.processOrder();
            })
            .catch(error => {
                YDCheckout.core.showNotice('Payment error: ' + error.message, 'error');
                YDCheckout.core.setSubmitButtonState(true);
            });
        },
        
        /**
         * Get billing details from the form
         * @returns {Object} Billing details for Stripe
         */
        getBillingDetails: function() {
            // Check if different billing is checked
            const differentBilling = document.getElementById('different-billing') && document.getElementById('different-billing').checked;
            const prefix = differentBilling ? 'billing-address-' : 'yd-address-';
            
            // Get values from form fields
            const firstName = document.getElementById(prefix + 'first-name')?.value || '';
            const lastName = document.getElementById(prefix + 'last-name')?.value || '';
            const street = document.getElementById(prefix + 'street')?.value || '';
            const houseNumber = document.getElementById(prefix + 'house-number')?.value || '';
            const city = document.getElementById(prefix + 'city')?.value || '';
            const postalCode = document.getElementById(prefix + 'postal-code')?.value || '';
            const country = document.getElementById(prefix + 'country')?.value || '';
            
            return {
                name: firstName + ' ' + lastName,
                address: {
                    line1: street + ' ' + houseNumber,
                    city: city,
                    postal_code: postalCode,
                    country: country
                }
            };
        },
        
        /**
         * Initialize PayPal
         */
        initPayPal: function() {
            // Skip if already loaded or no PayPal client ID
            if (this.state.paypalLoaded || !this.config.paypalClientId) return;
            
            // Check if PayPal is already loaded
            if (window.paypal) {
                this.renderPayPalButtons();
            } else {
                // Load PayPal SDK
                const script = document.createElement('script');
                script.src = `https://www.paypal.com/sdk/js?client-id=${this.config.paypalClientId}&currency=EUR&intent=capture`;
                script.async = true;
                
                script.onload = this.renderPayPalButtons.bind(this);
                script.onerror = () => {
                    console.error('Failed to load PayPal SDK');
                };
                
                document.body.appendChild(script);
            }
        },
        
        /**
         * Render PayPal buttons
         */
        renderPayPalButtons: function() {
            // Create container for PayPal buttons if needed
            if (!document.getElementById('paypal-buttons')) {
                const container = document.createElement('div');
                container.id = 'paypal-buttons';
                
                if (this.elements.paypalContainer) {
                    this.elements.paypalContainer.appendChild(container);
                } else {
                    // Create container if it doesn't exist
                    const paymentMethodContainer = document.querySelector('.yd-checkout-payment-description[id="payment_description_paypal"]');
                    if (paymentMethodContainer) {
                        const newContainer = document.createElement('div');
                        newContainer.id = 'payment_fields_paypal';
                        newContainer.className = 'yd-checkout-payment-method-fields';
                        newContainer.innerHTML = '<div id="paypal-buttons"></div>';
                        paymentMethodContainer.after(newContainer);
                    }
                }
            }
            
            // Render buttons
            window.paypal.Buttons({
                style: {
                    layout: 'vertical',
                    color: 'blue',
                    shape: 'rect',
                    label: 'paypal'
                },
                
                // Create order
                createOrder: function(data, actions) {
                    return this.createPayPalOrder();
                }.bind(this),
                
                // Approve order
                onApprove: function(data, actions) {
                    this.capturePayPalOrder(data.orderID);
                }.bind(this),
                
                // Handle errors
                onError: function(err) {
                    YDCheckout.core.showNotice('PayPal error: ' + err.message, 'error');
                }
            }).render('#paypal-buttons');
            
            this.state.paypalLoaded = true;
            
            // Add hidden input for PayPal order ID
            this.ensureHiddenInputs([
                { id: 'paypal-order-id', name: 'paypal_order_id' }
            ]);
        },
        
        /**
         * Create PayPal order
         * @returns {Promise} Promise resolving to the PayPal order ID
         */
        createPayPalOrder: function() {
            // Get form data
            const formData = new FormData(YDCheckout.core.elements.checkoutForm);
            formData.append('action', 'yd_checkout_create_paypal_order');
            formData.append('nonce', YDCheckout.core.config.nonce);
            
            return fetch(YDCheckout.core.config.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(function(orderData) {
                if (orderData.success && orderData.data && orderData.data.paypal_order_id) {
                    // Store order ID
                    document.getElementById('paypal-order-id').value = orderData.data.paypal_order_id;
                    this.state.paypalOrderId = orderData.data.paypal_order_id;
                    
                    return orderData.data.paypal_order_id;
                } else {
                    const errorMessage = orderData.data && orderData.data.message 
                        ? orderData.data.message 
                        : 'Failed to create PayPal order.';
                    
                    throw new Error(errorMessage);
                }
            }.bind(this));
        },
        
        /**
         * Process PayPal payment
         */
        processPayPalPayment: function() {
            // Check if we have a PayPal order ID
            const paypalOrderId = document.getElementById('paypal-order-id')?.value || this.state.paypalOrderId;
            
            if (!paypalOrderId) {
                YDCheckout.core.showNotice('Please complete PayPal checkout first.', 'error');
                YDCheckout.core.setSubmitButtonState(true);
                return;
            }
            
            // Capture the payment
            this.capturePayPalOrder(paypalOrderId);
        },
        
        /**
         * Capture PayPal order after approval
         * @param {string} orderId - PayPal order ID
         */
        capturePayPalOrder: function(orderId) {
            // Get form data
            const formData = new FormData(YDCheckout.core.elements.checkoutForm);
            formData.append('action', 'yd_checkout_capture_paypal_order');
            formData.append('nonce', YDCheckout.core.config.nonce);
            formData.append('paypal_order_id', orderId);
            
            fetch(YDCheckout.core.config.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(function(orderData) {
                if (orderData.success) {
                    // Show success message
                    YDCheckout.core.showNotice('Payment successful!', 'success');
                    
                    // Redirect to thank you page if provided
                    if (orderData.data && orderData.data.redirect) {
                        window.location.href = orderData.data.redirect;
                    }
                } else {
                    const errorMessage = orderData.data && orderData.data.message 
                        ? orderData.data.message 
                        : 'Failed to process payment.';
                    
                    throw new Error(errorMessage);
                }
            })
            .catch(function(error) {
                YDCheckout.core.showNotice('Payment error: ' + error.message, 'error');
                YDCheckout.core.setSubmitButtonState(true);
            });
        },
        
        /**
         * Ensure hidden inputs exist for payment processing
         * @param {Array} inputs - Array of input objects with id and name properties
         */
        ensureHiddenInputs: function(inputs) {
            inputs.forEach(input => {
                let hiddenInput = document.getElementById(input.id);
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.id = input.id;
                    hiddenInput.name = input.name;
                    YDCheckout.core.elements.checkoutForm.appendChild(hiddenInput);
                }
            });
        }
    };

    // Public API
    return {
        core: core,
        address: address,
        payment: payment,
        
        /**
         * Initialize the checkout
         * @param {Object} config - Configuration options
         */
        init: function(config) {
            this.core.init(config);
        }
    };
})();

// Initialize checkout when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    YDCheckout.init({
        ajaxUrl: window.ydCheckoutSettings?.ajaxUrl || '',
        nonce: window.ydCheckoutSettings?.nonce || '',
        i18n: window.ydCheckoutSettings?.i18n || {}
    });
});