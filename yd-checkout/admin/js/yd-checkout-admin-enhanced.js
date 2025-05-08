/**
 * YDesign Checkout Admin JavaScript
 *
 * Handles admin UI interactions for the YDesign Checkout plugin settings.
 *
 * @package    Yd_Checkout
 * @subpackage Yd_Checkout/admin/js
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all admin functionality
        initEnvironmentToggles();
        initCredentialValidation();
        initFieldVisibility();
        initCopyButtons();
    });

    /**
     * Initialize environment toggle switches
     */
    function initEnvironmentToggles() {
        const toggles = document.querySelectorAll('.yd-checkout-environment-toggle');
        
        toggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const section = this.dataset.section;
                const isTestMode = this.checked;
                
                // Update toggle labels
                const container = this.closest('.yd-checkout-toggle-container');
                if (container) {
                    const onLabel = container.querySelector('.yd-checkout-toggle-on');
                    const offLabel = container.querySelector('.yd-checkout-toggle-off');
                    
                    if (onLabel) onLabel.classList.toggle('active', isTestMode);
                    if (offLabel) offLabel.classList.toggle('active', !isTestMode);
                }
                
                // Show/hide appropriate fields based on environment
                updateFieldVisibility(section, isTestMode);
            });
        });
    }

    /**
     * Initialize API credential validation
     */
    function initCredentialValidation() {
        const validateButtons = document.querySelectorAll('.yd-checkout-validate-credentials');
        
        validateButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const gateway = this.dataset.gateway;
                const environment = this.dataset.environment;
                
                // Create validation message container if it doesn't exist
                let messageContainer = this.nextElementSibling;
                if (!messageContainer || !messageContainer.classList.contains('validation-message')) {
                    messageContainer = document.createElement('span');
                    messageContainer.className = 'validation-message';
                    this.parentNode.insertBefore(messageContainer, this.nextSibling);
                }
                
                // Show validating message
                messageContainer.textContent = ydCheckoutAdmin.i18n.validating;
                messageContainer.className = 'validation-message validating';
                
                // Get credentials based on gateway and environment
                const credentials = getCredentials(gateway, environment);
                
                // Validate credentials
                validateCredentials(gateway, environment, credentials, messageContainer);
            });
        });
    }

    /**
     * Initialize field visibility based on environment toggle state
     */
    function initFieldVisibility() {
        // Initial field visibility for Stripe
        const stripeToggle = document.getElementById('stripe-test-mode-toggle');
        if (stripeToggle) {
            updateFieldVisibility('stripe', stripeToggle.checked);
        }
        
        // Initial field visibility for PayPal
        const paypalToggle = document.getElementById('paypal-test-mode-toggle');
        if (paypalToggle) {
            updateFieldVisibility('paypal', paypalToggle.checked);
        }
    }
    
    /**
     * Initialize copy buttons for webhook URLs
     */
    function initCopyButtons() {
        const copyButtons = document.querySelectorAll('.yd-checkout-copy-button');
        
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                if (input) {
                    input.select();
                    document.execCommand('copy');
                    
                    // Show copied message
                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                }
            });
        });
    }

    /**
     * Update field visibility based on environment
     * 
     * @param {string} section The settings section (stripe, paypal)
     * @param {boolean} isTestMode Whether test mode is enabled
     */
    function updateFieldVisibility(section, isTestMode) {
        const testFields = document.querySelectorAll(`.yd-checkout-${section}-test-field`);
        const liveFields = document.querySelectorAll(`.yd-checkout-${section}-live-field`);
        
        testFields.forEach(field => {
            field.style.display = isTestMode ? 'block' : 'none';
        });
        
        liveFields.forEach(field => {
            field.style.display = isTestMode ? 'none' : 'block';
        });
    }

    /**
     * Get credentials based on gateway and environment
     * 
     * @param {string} gateway The payment gateway (stripe, paypal, here)
     * @param {string} environment The environment (test, live, sandbox, api)
     * @return {object} The credentials
     */
    function getCredentials(gateway, environment) {
        const credentials = {};
        
        if (gateway === 'stripe') {
            if (environment === 'test') {
                credentials.publishable_key = document.getElementById('yd_checkout_stripe_test_publishable_key').value;
                credentials.secret_key = document.getElementById('yd_checkout_stripe_test_secret_key').value;
            } else if (environment === 'live') {
                credentials.publishable_key = document.getElementById('yd_checkout_stripe_live_publishable_key').value;
                credentials.secret_key = document.getElementById('yd_checkout_stripe_live_secret_key').value;
            }
        } else if (gateway === 'paypal') {
            if (environment === 'sandbox') {
                credentials.client_id = document.getElementById('yd_checkout_paypal_sandbox_client_id').value;
                credentials.client_secret = document.getElementById('yd_checkout_paypal_sandbox_client_secret').value;
            } else if (environment === 'live') {
                credentials.client_id = document.getElementById('yd_checkout_paypal_live_client_id').value;
                credentials.client_secret = document.getElementById('yd_checkout_paypal_live_client_secret').value;
            }
        } else if (gateway === 'here') {
            credentials.api_key = document.getElementById('yd_checkout_here_api_key').value;
        }
        
        return credentials;
    }

    /**
     * Validate credentials with AJAX
     * 
     * @param {string} gateway The payment gateway (stripe, paypal, here)
     * @param {string} environment The environment (test, live, sandbox, api)
     * @param {object} credentials The credentials to validate
     * @param {HTMLElement} messageContainer Element to display validation message
     */
    function validateCredentials(gateway, environment, credentials, messageContainer) {
        // Prepare form data
        const formData = new FormData();
        formData.append('action', `yd_checkout_validate_${gateway}_credentials`);
        formData.append('nonce', ydCheckoutAdmin.nonce);
        formData.append('environment', environment);
        formData.append('credentials', JSON.stringify(credentials));
        
        // Send AJAX request
        fetch(ydCheckoutAdmin.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageContainer.textContent = ydCheckoutAdmin.i18n.success;
                messageContainer.className = 'validation-message success';
            } else {
                messageContainer.textContent = ydCheckoutAdmin.i18n.error + data.data.message;
                messageContainer.className = 'validation-message error';
            }
        })
        .catch(error => {
            messageContainer.textContent = ydCheckoutAdmin.i18n.connectionError;
            messageContainer.className = 'validation-message error';
            console.error('Validation error:', error);
        });
    }
})();