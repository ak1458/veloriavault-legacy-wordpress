;(function($) {
    'use strict';

    /**
     * Google Address Autocomplete Handler
     */
    const WoolentorAddressAutocomplete = {

        /**
         * Settings from PHP
         */
        settings: {},

        /**
         * Autocomplete instances
         */
        autocompleteInstances: {},

        /**
         * Initialize the autocomplete functionality
         */
        init: function() {
            this.settings = window.woolentorGoogleAutocomplete || {};
            this.initAutocomplete();
            this.bindEvents();
        },

        /**
         * Initialize autocomplete on target fields
         */
        initAutocomplete: function() {
            const self = this;
            const targets = this.getTargetFields();

            targets.forEach(function(fieldId) {
                const field = document.getElementById(fieldId);
                if (field && !self.autocompleteInstances[fieldId]) {
                    self.setupAutocomplete(field, fieldId);
                }
            });
        },

        /**
         * Get target field IDs based on settings
         *
         * @return {Array} Array of field IDs
         */
        getTargetFields: function() {
            const fields = [];
            const target = this.settings.targetFields || 'both';

            if (target === 'both' || target === 'billing') {
                fields.push('billing_address_1');
            }
            if (target === 'both' || target === 'shipping') {
                fields.push('shipping_address_1');
            }

            return fields;
        },

        /**
         * Setup autocomplete on a specific field
         *
         * @param {HTMLElement} field The input field element
         * @param {string} fieldId The field ID
         */
        setupAutocomplete: function(field, fieldId) {
            const self = this;

            // Autocomplete options
            const options = {
                types: ['address'],
                fields: ['address_components', 'formatted_address','icon']
            };

            // Add country restrictions if set
            if (this.settings.countryRestrictions && this.settings.countryRestrictions.length > 0) {
                options.componentRestrictions = {
                    country: this.settings.countryRestrictions
                };
            }

            // Create autocomplete instance
            const autocomplete = new google.maps.places.Autocomplete(field, options);

            // Store instance
            this.autocompleteInstances[fieldId] = autocomplete;

            // Listen for place selection
            autocomplete.addListener('place_changed', function() {
                self.fillInAddress(autocomplete, fieldId);
            });

            // Prevent form submission on Enter key in autocomplete field
            field.addEventListener('keydown', function(e) {
                if (e.keyCode === 13) {
                    const pacContainer = document.querySelector('.pac-container');
                    if (pacContainer && pacContainer.style.display !== 'none') {
                        e.preventDefault();
                    }
                }
            });
        },

        /**
         * Fill in address fields when a place is selected
         *
         * @param {google.maps.places.Autocomplete} autocomplete The autocomplete instance
         * @param {string} fieldId The source field ID
         */
        fillInAddress: function(autocomplete, fieldId) {
            const place = autocomplete.getPlace();
            const prefix = fieldId.replace('_address_1', '');

            if (!place || !place.address_components) {
                return;
            }

            // Reset fields first
            this.resetFields(prefix);

            // Component type to format mapping
            const componentMapping = {
                street_number: 'short_name',
                route: 'long_name',
                locality: 'long_name',
                sublocality_level_1: 'long_name',
                administrative_area_level_1: 'short_name',
                administrative_area_level_2: 'long_name',
                country: 'short_name',
                postal_code: 'short_name',
                postal_code_suffix: 'short_name'
            };

            // Extract address components
            const addressData = {};
            place.address_components.forEach(function(component) {
                const type = component.types[0];
                if (componentMapping[type]) {
                    addressData[type] = component[componentMapping[type]];
                }
                // Also store long_name for country (needed for country dropdown)
                if (type === 'country') {
                    addressData['country_long'] = component['long_name'];
                }
            });

            // Build street address
            const streetNumber = addressData.street_number || '';
            const route = addressData.route || '';
            let address1 = '';

            if (streetNumber && route) {
                address1 = streetNumber + ' ' + route;
            } else if (route) {
                address1 = route;
            } else if (streetNumber) {
                address1 = streetNumber;
            }

            // Get city (try locality first, then sublocality, then administrative_area_level_2)
            const city = addressData.locality ||
                        addressData.sublocality_level_1 ||
                        addressData.administrative_area_level_2 ||
                        '';

            // Get postal code (with suffix if available)
            let postalCode = addressData.postal_code || '';
            if (addressData.postal_code_suffix) {
                postalCode += '-' + addressData.postal_code_suffix;
            }

            // Fill in the fields
            this.setFieldValue(prefix + '_address_1', address1);
            this.setFieldValue(prefix + '_city', city);
            this.setFieldValue(prefix + '_postcode', postalCode);

            // Handle state/province field
            const stateValue = addressData.administrative_area_level_1 || '';
            this.setStateField(prefix + '_state', stateValue);

            // Handle country field (must be set before state for proper state dropdown population)
            const countryValue = addressData.country || '';
            this.setCountryField(prefix + '_country', countryValue);

            // Trigger WooCommerce checkout update
            $(document.body).trigger('update_checkout');
        },

        /**
         * Set a field value and trigger change event
         *
         * @param {string} fieldId The field ID
         * @param {string} value The value to set
         */
        setFieldValue: function(fieldId, value) {
            const field = document.getElementById(fieldId);
            if (field) {
                $(field).val(value).trigger('change');
            }
        },

        /**
         * Set country field value (handles select2 dropdowns)
         *
         * @param {string} fieldId The field ID
         * @param {string} countryCode The country code
         */
        setCountryField: function(fieldId, countryCode) {
            const field = document.getElementById(fieldId);
            if (!field) return;

            const $field = $(field);
            const upperCode = countryCode.toUpperCase();

            // Check if option exists
            if ($field.find('option[value="' + upperCode + '"]').length > 0) {
                $field.val(upperCode).trigger('change');
            }
        },

        /**
         * Set state field value (handles both text and select fields)
         *
         * @param {string} fieldId The field ID
         * @param {string} stateValue The state value/code
         */
        setStateField: function(fieldId, stateValue) {
            const field = document.getElementById(fieldId);
            if (!field) return;

            const $field = $(field);

            // If it's a select field
            if (field.tagName === 'SELECT') {
                // Try to find matching option by value or text
                let found = false;

                $field.find('option').each(function() {
                    const $option = $(this);
                    const optVal = $option.val().toUpperCase();
                    const optText = $option.text().toUpperCase();
                    const searchVal = stateValue.toUpperCase();

                    if (optVal === searchVal || optText === searchVal) {
                        $field.val($option.val()).trigger('change');
                        found = true;
                        return false;
                    }
                });

                // If not found and has options, try partial match
                if (!found) {
                    $field.find('option').each(function() {
                        const $option = $(this);
                        const optText = $option.text().toUpperCase();
                        const searchVal = stateValue.toUpperCase();

                        if (optText.indexOf(searchVal) !== -1 || searchVal.indexOf(optText) !== -1) {
                            $field.val($option.val()).trigger('change');
                            return false;
                        }
                    });
                }
            } else {
                // Text input
                $field.val(stateValue).trigger('change');
            }
        },

        /**
         * Reset address fields
         *
         * @param {string} prefix Field prefix (billing or shipping)
         */
        resetFields: function(prefix) {
            const fieldsToReset = ['_address_2', '_city', '_state', '_postcode'];
            const self = this;

            fieldsToReset.forEach(function(suffix) {
                self.setFieldValue(prefix + suffix, '');
            });
        },

        /**
         * Bind events for dynamic checkout updates
         */
        bindEvents: function() {
            const self = this;

            // Re-initialize on checkout update (for dynamic fields)
            $(document.body).on('updated_checkout', function() {
                self.initAutocomplete();
            });

            // Re-initialize when ship to different address is toggled
            $(document.body).on('change', '#ship-to-different-address-checkbox', function() {
                setTimeout(function() {
                    self.initAutocomplete();
                }, 100);
            });

            // Handle WooCommerce Blocks checkout
            if (typeof wp !== 'undefined' && wp.data) {
                // Block checkout support - initialize on store changes
                const unsubscribe = wp.data.subscribe(function() {
                    self.initBlockCheckoutAutocomplete();
                });
            }
        },

        /**
         * Initialize autocomplete for Block Checkout
         */
        initBlockCheckoutAutocomplete: function() {
            const self = this;
            const target = this.settings.targetFields || 'both';

            // Block checkout uses different field IDs
            const blockFields = [];

            if (target === 'both' || target === 'billing') {
                blockFields.push('billing-address_1');
            }
            if (target === 'both' || target === 'shipping') {
                blockFields.push('shipping-address_1');
            }

            blockFields.forEach(function(fieldId) {
                const field = document.getElementById(fieldId);
                if (field && !self.autocompleteInstances[fieldId]) {
                    self.setupBlockAutocomplete(field, fieldId);
                }
            });
        },

        /**
         * Setup autocomplete for Block Checkout fields
         *
         * @param {HTMLElement} field The input field element
         * @param {string} fieldId The field ID
         */
        setupBlockAutocomplete: function(field, fieldId) {
            const self = this;

            const options = {
                types: ['address'],
                fields: ['address_components', 'formatted_address','icon']
            };

            if (this.settings.countryRestrictions && this.settings.countryRestrictions.length > 0) {
                options.componentRestrictions = {
                    country: this.settings.countryRestrictions
                };
            }

            const autocomplete = new google.maps.places.Autocomplete(field, options);
            this.autocompleteInstances[fieldId] = autocomplete;

            autocomplete.addListener('place_changed', function() {
                self.fillInBlockAddress(autocomplete, fieldId);
            });
        },

        /**
         * Fill in Block Checkout address fields
         *
         * @param {google.maps.places.Autocomplete} autocomplete The autocomplete instance
         * @param {string} fieldId The source field ID
         */
        fillInBlockAddress: function(autocomplete, fieldId) {
            const place = autocomplete.getPlace();
            const prefix = fieldId.replace('-address_1', '');

            if (!place || !place.address_components) {
                return;
            }

            const componentMapping = {
                street_number: 'short_name',
                route: 'long_name',
                locality: 'long_name',
                administrative_area_level_1: 'short_name',
                country: 'short_name',
                postal_code: 'short_name'
            };

            const addressData = {};
            place.address_components.forEach(function(component) {
                const type = component.types[0];
                if (componentMapping[type]) {
                    addressData[type] = component[componentMapping[type]];
                }
            });

            const streetNumber = addressData.street_number || '';
            const route = addressData.route || '';
            const address1 = streetNumber ? streetNumber + ' ' + route : route;

            // Block checkout field IDs use different format
            this.setBlockFieldValue(prefix + '-address_1', address1);
            this.setBlockFieldValue(prefix + '-city', addressData.locality || '');
            this.setBlockFieldValue(prefix + '-state', addressData.administrative_area_level_1 || '');
            this.setBlockFieldValue(prefix + '-postcode', addressData.postal_code || '');
            this.setBlockFieldValue(prefix + '-country', addressData.country || '');
        },

        /**
         * Set Block Checkout field value
         *
         * @param {string} fieldId The field ID
         * @param {string} value The value to set
         */
        setBlockFieldValue: function(fieldId, value) {
            const field = document.getElementById(fieldId);
            if (!field) {
                return;
            }

            try {
                const tagName = field.tagName.toUpperCase();

                if (tagName === 'SELECT') {
                    // For SELECT elements, find and select the matching option
                    const upperValue = value.toUpperCase();
                    let optionFound = false;

                    for (let i = 0; i < field.options.length; i++) {
                        const option = field.options[i];
                        if (option.value.toUpperCase() === upperValue ||
                            option.text.toUpperCase() === upperValue) {
                            field.selectedIndex = i;
                            optionFound = true;
                            break;
                        }
                    }

                    if (optionFound) {
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                } else if (tagName === 'INPUT' || tagName === 'TEXTAREA') {
                    // For INPUT/TEXTAREA elements, use native value setter for React compatibility
                    const descriptor = Object.getOwnPropertyDescriptor(
                        tagName === 'TEXTAREA' ? window.HTMLTextAreaElement.prototype : window.HTMLInputElement.prototype,
                        'value'
                    );

                    if (descriptor && descriptor.set) {
                        descriptor.set.call(field, value);
                    } else {
                        field.value = value;
                    }

                    // Dispatch events for React to pick up the change
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));

                    // Also try focus/blur to trigger validation
                    field.dispatchEvent(new Event('blur', { bubbles: true }));
                }
            } catch (error) {
                // Fallback: try simple value assignment
                console.warn('WoolentorAddressAutocomplete: Error setting field value', fieldId, error);
                try {
                    field.value = value;
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                } catch (e) {
                    // Silent fail
                }
            }
        }
    };

    // Initialize when document is ready and Google API is loaded
    $(document).ready(function() {
        // Check if Google Maps API is loaded
        const checkGoogleApi = function() {
            if (typeof google !== 'undefined' && google.maps && google.maps.places) {
                WoolentorAddressAutocomplete.init();
            } else {
                // Retry after a short delay
                setTimeout(checkGoogleApi, 100);
            }
        };

        checkGoogleApi();
    });

})(jQuery);
