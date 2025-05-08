/**
 * YDesign Checkout - Test Payment JavaScript
 * 
 * Handles test payment buttons for PayPal and Apple Pay
 */

const YDCheckoutTest = (function() {
    'use strict';

    /**
     * Initialize PayPal button
     * 
     * @param {string} paymentId Unique ID for this payment
     * @param {object} options Payment options (amount, currency, etc.)
     */
    function initPayPal(paymentId, options) {
        // Check if PayPal is already loaded
        if (typeof paypal === 'undefined') {
            loadPayPalScript(paymentId, options);
        } else {
            renderPayPalButton(paymentId, options);
        }
    }

    /**
     * Load PayPal SDK script
     * 
     * @param {string} paymentId Unique ID for this payment
     * @param {object} options Payment options
     */
    function loadPayPalScript(paymentId, options) {
        if (!window.ydCheckoutPayPal || !window.ydCheckoutPayPal.clientId) {
            updateStatus(paymentId, 'paypal', 'PayPal client ID not configured', 'error');
            return;
        }

        const script = document.createElement('script');
        script.src = `https://www.paypal.com/sdk/js?client-id=${window.ydCheckoutPayPal.clientId}&currency=${options.currency}`;
        script.async = true;
        
        script.onload = function() {
            renderPayPalButton(paymentId, options);
        };
        
        script.onerror = function() {
            updateStatus(paymentId, 'paypal', 'Failed to load PayPal SDK', 'error');
        };
        
        document.body.appendChild(script);
    }

    /**
     * Render PayPal button
     * 
     * @param {string} paymentId Unique ID for this payment
     * @param {object} options Payment options
     */
    function renderPayPalButton(paymentId, options) {
        const buttonContainer = document.getElementById(`paypal-button-container-${paymentId}`);
        if (!buttonContainer) return;

        updateStatus(paymentId, 'paypal', 'Initializing PayPal...', 'info');

        paypal.Buttons({
            style: {
                layout: 'vertical',
                color: 'blue',
                shape: 'rect',
                label: 'paypal'
            },
            
            // Set up the transaction
            createOrder: function(data, actions) {
                updateStatus(paymentId, 'paypal', 'Creating PayPal order...', 'info');
                
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: options.amount,
                            currency_code: options.currency
                        },
                        description: options.description
                    }]
                });
            },
            
            // Finalize the transaction
            onApprove: function(data, actions) {
                updateStatus(paymentId, 'paypal', 'Processing payment...', 'info');
                
                return actions.order.capture().then(function(orderData) {
                    // Capture the funds from the transaction
                    const transaction = orderData.purchase_units[0].payments.captures[0];
                    
                    // Process on our server
                    processPayPalPayment(paymentId, orderData.id, options);
                });
            },
            
            // Handle errors
            onError: function(err) {
                updateStatus(paymentId, 'paypal', 'PayPal error: ' + err.message, 'error');
            }
        }).render(buttonContainer);
    }

    /**
     * Process PayPal payment on server
     * 
     * @param {string} paymentId Unique ID for this payment
     * @param {string} orderId PayPal order ID
     * @param {object} options Payment options
     */
    function processPayPalPayment(paymentId, orderId, options) {
        const formData = new FormData();
        formData.append('action', 'yd_test_paypal_payment');
        formData.append('nonce', window.ydCheckoutSettings.nonce);
        formData.append('order_id', orderId);
        formData.append('amount', options.amount);
        formData.append('currency', options.currency);
        formData.append('description', options.description);
        
        fetch(window.ydCheckoutSettings.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatus(paymentId, 'paypal', 'Payment successful! Order ID: ' + orderId, 'success');
                
                // Redirect if needed
                if (options.redirect) {
                    setTimeout(() => {
                        window.location.href = options.redirect;
                    }, 2000);
                }
            } else {
                updateStatus(paymentId, 'paypal', data.data.message || 'Error processing payment', 'error');
            }
        })
        .catch(error => {
            updateStatus(paymentId, 'paypal', 'Error: ' + error.message, 'error');
        });
    }

    /**
     * Initialize Apple Pay button
     * 
     * @param {string} paymentId Unique ID for this payment
     * @param {object} options Payment options (amount, currency, etc.)
     */
    function initApplePay(paymentId, options) {
        const button = document.getElementById(`applepay-button-${paymentId}`);
        if (!button) return;

        // Check if Apple Pay is supported
        if (!window.ApplePaySession || !ApplePaySession.canMakePayments()) {
            updateStatus(paymentId, 'applepay', 'Apple Pay is not supported on this device/browser', 'error');
            button.disabled = true;
            button.classList.add('disabled');
            return;
        }

        // Check if Stripe is configured
        if (!window.ydCheckoutStripe || !window.ydCheckoutStripe.publishableKey) {
            updateStatus(paymentId, 'applepay', 'Stripe publishable key not configured', 'error');
            return;
        }

        // Load Stripe if not already loaded
        if (typeof Stripe === 'undefined') {
            loadStripeScript(paymentId, options);
        } else {
            setupApplePayButton(paymentId, options);
        }
    }

    /**
     * Load Stripe.js script
     * 
     * @param {string} paymentId Unique ID for this payment
     * @param {object} options Payment options
     */
    function loadStripeScript(paymentId, options) {
        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.async = true;
        
        script.onload = function() {
            setupApplePayButton(paymentId, options);
        };
        
        script.onerror = function() {
            updateStatus(paymentId, 'applepay', 'Failed to load Stripe.js', 'error');
        };
        
        document.body.appendChild(script);
    }

    /**
     * Setup Apple Pay button
     * 
     * @param {string} paymentId Unique ID for this payment
     * @param {object} options Payment options
     */
    function setupApplePayButton(paymentId, options) {
        const button = document.getElementById(`applepay-button-${paymentId}`);
        if (!button) return;

        updateStatus(paymentId, 'applepay', 'Apple Pay is available', 'info');

        // Initialize Stripe
        const stripe = Stripe(window.ydCheckoutStripe.publishableKey);

        // Add click event to button
        button.addEventListener('click', function() {
            startApplePaySession(paymentId, options, stripe);
        });
    }

    /**
     * Start Apple Pay session
     * 
     * @param {string} paymentId Unique ID for this payment
     * @param {object} options Payment options
     * @param {object} stripe Stripe instance
     */
    function startApplePaySession(paymentId, options, stripe) {
        updateStatus(paymentId, 'applepay', 'Starting Apple Pay session...', 'info');

        // Format amount as cents for Stripe
        const amount = Math.round(parseFloat(options.amount) * 100);

        // Create payment request
        const paymentRequest = stripe.paymentRequest({
            country: 'DE',  // Change to your country
            currency: options.currency.toLowerCase(),
            total: {
                label: options.description,
                amount: amount,
            },
            requestPayerName: true,
            requestPayerEmail: true,
        });

        // Create Apple Pay session
        const session = new ApplePaySession(3, {
            countryCode: 'DE',  // Change to your country
            currencyCode: options.currency,
            supportedNetworks: ['visa', 'mastercard', 'amex'],
            merchantCapabilities: ['supports3DS'],
            total: {
                label: options.description,
                amount: options.amount,
            }
        });

        // Handle Apple Pay session events
        session.onvalidatemerchant = function(event) {
            // Normally you would validate with your server here
            // This is a simplified test implementation
            const validationURL = event.validationURL;
            
            // For testing, we'll just complete without validation
            setTimeout(() => {
                session.completeMerchantValidation({});
            }, 500);
        };

        session.onpaymentauthorized = function(event) {
            const payment = event.payment;
            
            // Process payment with Stripe
            processApplePayPayment(paymentId, payment, options);
            
            // Complete payment
            session.completePayment(ApplePaySession.STATUS_SUCCESS);
        };

        session.oncancel = function(event) {
            updateStatus(paymentId, 'applepay', 'Payment cancelled', 'info');
        };

        // Start the session
        session.begin();
    }

    /**
     * Process Apple Pay payment on server
     * 
     * @param {string} paymentId Unique ID for this payment
     * @param {object} payment Apple Pay payment object
     * @param {object} options Payment options
     */
    function processApplePayPayment(paymentId, payment, options) {
        // Create token from payment data - in a real implementation
        // In this test version, we'll just simulate successful processing
        
        const formData = new FormData();
        formData.append('action', 'yd_test_applepay_payment');
        formData.append('nonce', window.ydCheckoutSettings.nonce);
        formData.append('payment_token', 'test_token_' + Date.now());
        formData.append('amount', options.amount);
        formData.append('currency', options.currency);
        formData.append('description', options.description);
        
        fetch(window.ydCheckoutSettings.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatus(paymentId, 'applepay', 'Payment successful!', 'success');
                
                // Redirect if needed
                if (options.redirect) {
                    setTimeout(() => {
                        window.location.href = options.redirect;
                    }, 2000);
                }
            } else {
                updateStatus(paymentId, 'applepay', data.data.message || 'Error processing payment', 'error');
            }
        })
        .catch(error => {
            updateStatus(paymentId, 'applepay', 'Error: ' + error.message, 'error');
        });
    }

    /**
     * Update status message
     * 
     * @param {string} paymentId Unique ID for this payment
     * @param {string} type Payment type (paypal, applepay)
     * @param {string} message Status message
     * @param {string} status Status type (info, success, error)
     */
    function updateStatus(paymentId, type, message, status) {
        const statusContainer = document.getElementById(`${type}-status-${paymentId}`);
        if (!statusContainer) return;
        
        statusContainer.innerHTML = message;
        statusContainer.className = 'yd-test-payment-status';
        statusContainer.classList.add(`yd-test-payment-status-${status}`);
    }

    // Public API
    return {
        initPayPal: initPayPal,
        initApplePay: initApplePay
    };
})();