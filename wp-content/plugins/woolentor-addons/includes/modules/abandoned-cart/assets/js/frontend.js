;jQuery(document).ready(function($) {
    var checkoutDataTimer;

    // Get data from classic checkout
    function getClassicCheckoutData() {
        var checkoutForm = document.querySelector('form.checkout');
        if (!checkoutForm) return {};
        
        var formData = new FormData(checkoutForm);
        var checkoutData = {};
        
        for (var pair of formData.entries()) {
            if (pair[1] && pair[1].toString().trim() !== '') {
                checkoutData[pair[0]] = pair[1];
            }
        }
        
        return checkoutData;
    }
    
    // Get data from block checkout
    function getBlockCheckoutData() {
        var checkoutData = {};
        
        // Try to get data from WooCommerce store API (if available)
        if (window.wc && window.wc.wcBlocksData && window.wc.wcBlocksData.getSetting) {
            try {
                var storeApi = window.wc.wcBlocksData.getSetting('storeApi');
                if (storeApi && storeApi.billingAddress) {
                    Object.assign(checkoutData, flattenAddress(storeApi.billingAddress, 'billing_'));
                }
                if (storeApi && storeApi.shippingAddress) {
                    Object.assign(checkoutData, flattenAddress(storeApi.shippingAddress, 'shipping_'));
                }
            } catch (e) {
                console.log('WooLentor: Could not access store API data');
            }
        }
        
        // Fallback: Try to extract from input fields (even if they have different structure)
        var blockInputs = document.querySelectorAll(
            '.wc-block-checkout input[type="text"], ' +
            '.wc-block-checkout input[type="email"], ' +
            '.wc-block-checkout input[type="tel"], ' +
            '.wc-block-checkout select, ' +
            '.wc-block-checkout textarea'
        );
        
        blockInputs.forEach(function(input) {
            if (input.value && input.value.trim() !== '') {
                var fieldName = getBlockFieldName(input);
                if (fieldName) {
                    checkoutData[fieldName] = input.value;
                }
            }
        });
        
        // Try to get email from React component state
        var emailField = document.querySelector('.wc-block-checkout input[type="email"]');
        if (emailField && emailField.value) {
            checkoutData['billing_email'] = emailField.value;
        }
        
        return checkoutData;
    }
    
    // Helper function to determine field name from block checkout input
    function getBlockFieldName(input) {
        // Check various attributes and patterns
        if (input.id) {
            // Convert block field IDs to classic field names
            var id = input.id.toLowerCase();
            if (id.includes('email')) return 'billing_email';
            if (id.includes('first') && id.includes('name')) return 'billing_first_name';
            if (id.includes('last') && id.includes('name')) return 'billing_last_name';
            if (id.includes('company')) return 'billing_company';
            if (id.includes('address') && id.includes('1')) return 'billing_address_1';
            if (id.includes('address') && id.includes('2')) return 'billing_address_2';
            if (id.includes('city')) return 'billing_city';
            if (id.includes('postcode') || id.includes('postal')) return 'billing_postcode';
            if (id.includes('phone')) return 'billing_phone';
            if (id.includes('state') || id.includes('province')) return 'billing_state';
            if (id.includes('country')) return 'billing_country';
        }
        
        // Check aria-label
        if (input.getAttribute('aria-label')) {
            var label = input.getAttribute('aria-label').toLowerCase();
            if (label.includes('email')) return 'billing_email';
            if (label.includes('first name')) return 'billing_first_name';
            if (label.includes('last name')) return 'billing_last_name';
            if (label.includes('phone')) return 'billing_phone';
        }
        
        // Check placeholder
        if (input.placeholder) {
            var placeholder = input.placeholder.toLowerCase();
            if (placeholder.includes('email')) return 'billing_email';
            if (placeholder.includes('first name')) return 'billing_first_name';
            if (placeholder.includes('last name')) return 'billing_last_name';
        }
        
        // Check closest label
        var label = input.closest('.wc-block-components-text-input, .wc-block-components-select')?.querySelector('label');
        if (label) {
            var labelText = label.textContent.toLowerCase();
            if (labelText.includes('email')) return 'billing_email';
            if (labelText.includes('first name')) return 'billing_first_name';
            if (labelText.includes('last name')) return 'billing_last_name';
            if (labelText.includes('phone')) return 'billing_phone';
            if (labelText.includes('company')) return 'billing_company';
            if (labelText.includes('address')) return 'billing_address_1';
            if (labelText.includes('city')) return 'billing_city';
            if (labelText.includes('postcode') || labelText.includes('zip')) return 'billing_postcode';
        }
        
        return null;
    }
    
    // Helper function to flatten address object
    function flattenAddress(address, prefix) {
        var flattened = {};
        if (address.first_name) flattened[prefix + 'first_name'] = address.first_name;
        if (address.last_name) flattened[prefix + 'last_name'] = address.last_name;
        if (address.company) flattened[prefix + 'company'] = address.company;
        if (address.address_1) flattened[prefix + 'address_1'] = address.address_1;
        if (address.address_2) flattened[prefix + 'address_2'] = address.address_2;
        if (address.city) flattened[prefix + 'city'] = address.city;
        if (address.state) flattened[prefix + 'state'] = address.state;
        if (address.postcode) flattened[prefix + 'postcode'] = address.postcode;
        if (address.country) flattened[prefix + 'country'] = address.country;
        if (address.email) flattened[prefix + 'email'] = address.email;
        if (address.phone) flattened[prefix + 'phone'] = address.phone;
        return flattened;
    }
    
    // Function to save checkout data
    function saveCheckoutData() {

        var isBlockCheckout = $('.wc-block-checkout').length > 0;

        var checkoutData = {};

        if(isBlockCheckout) {
            checkoutData = getBlockCheckoutData();
        } else {
            checkoutData = getClassicCheckoutData();
        }
        
        // Only send if we have meaningful data
        if(Object.keys(checkoutData).length > 2) {
            $.ajax({
                url: wc_checkout_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'woolentor_save_checkout_data',
                    checkout_data: checkoutData,
                    nonce: woolentorAbancart.nonce
                },
                timeout: 5000,
                success: function(response) {
                    // console.log('WooLentor: Checkout data saved');
                },
                error: function() {
                    // console.log('WooLentor: Failed to save checkout data');
                }
            });
        }
    }
    
    // Debounced save function
    function debouncedSave() {
        clearTimeout(checkoutDataTimer);
        checkoutDataTimer = setTimeout(saveCheckoutData, 2000);
    }
    
    // Bind to checkout field changes
    $(document.body).on('change blur', 'div[class*="checkout"] input, div[class*="checkout"] select, div[class*="checkout"] textarea', debouncedSave);
    
    // Save on checkout update
    $(document.body).on('update_checkout', function() {
        setTimeout(saveCheckoutData, 1000);
    });
    
    // Save before leaving page
    $(window).on('beforeunload', function() {
        saveCheckoutData();
    });
});