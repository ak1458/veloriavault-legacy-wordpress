(function($) {	
	
	'use strict';
	
	// Extend core script
	$.extend($.nmTheme, {
		
		/**
		 *	Initialize
		 */
		search_init: function() {
			var self = this;
            
            self.headerSearch = (nm_wp_vars.shopSearchHeader !== '0') ? true : false;
            self.searchCurrentQuery = '';
            
            if (self.headerSearch) {
                // Suggestions
                if (nm_wp_vars.searchSuggestions !== '0') {
                    self.instantSuggestions = (nm_wp_vars.searchSuggestionsInstant !== '0') ? true : false;
                    if (self.instantSuggestions) {
                        self.instantSuggestionsPreloaderTo = null;
                        
                        self.instantSuggestionsLoad(); // Load product data
                    }
                    
                    self.suggestions = true;
                    self.suggestionsAjax = null;
                    self.suggestionsCacheEnabled = true;
                    self.suggestionsCache = {};
                    self.suggestionsMaxResults = nm_wp_vars.searchSuggestionsMax;
                } else {
                    self.suggestions = false;
                }
                
                self.$searchPanel = $('#nm-header-search');
                
                // Hide search-panel scrollbar
                //self.$searchPanel.css('margin-right', '-'+self.scrollbarWidth+'px');
                
                self.$searchBtn = $('#nm-menu-search-btn');
                self.$searchInput = $('#nm-header-search-input');
                self.$searchNotice = $('#nm-header-search-notice');
                self.$searchKeywords = $('#nm-search-keywords');
                
                self.headerSearchBind();
            }
            
            self.shopSearch = (! self.headerSearch && nm_wp_vars.shopSearch !== '0') ? true : false;
            
            if (self.isShop && self.shopSearch) {
                self.$searchBtn = $('#nm-shop-search-btn');
                self.$searchPanel = $('#nm-shop-search');
                self.$searchInput = $('#nm-shop-search-input');
                self.$searchNotice = $('#nm-shop-search-notice');
                self.searchAjax = null;
                self.currentSearch = '';
                
                self.shopSearchBind();
            }
		},
		
		
		/**
		 *	Search: Validate input string
		 */
		searchValidateInput: function(searchKey, enterPress) {
			// Make sure the search string has at least one character (not just whitespace) and minimum allowed characters are entered
			/*ORG: if ((/\S/.test(searchKey)) && searchKey.length > (nm_wp_vars.shopSearchMinChar - 1)) {
				return true;
			} else {
				return false;
			}*/
            var self = this,
                searchQueryNoWhitespace = searchKey.replace(/ /g,'');
            
            // Has the search-text (without whitespace) changed?
            if (! enterPress && self.searchCurrentQuery == searchQueryNoWhitespace) {
                return 'unchanged';
            }
            
			// Make sure the search string has at least one character (not just whitespace) and minimum allowed characters are entered
            if (searchQueryNoWhitespace.length > 0 && searchKey.length > (nm_wp_vars.shopSearchMinChar - 1)) {
				self.searchCurrentQuery = searchQueryNoWhitespace;
                
                return 'valid';
			} else {
				return 'invalid';
			}
		},
        
        
        /**
		 *	Search: Show notice
		 */
		searchShowNotice: function() {
			var self = this;
            self.$searchNotice.addClass('show');
		},
		
		
		/**
		 *	Search: Hide notice
		 */
		searchHideNotice: function() {
			var self = this;
            self.$searchNotice.removeClass('show');
		},
        
        
        /**
		 *	Header search: Bind
		 */
		headerSearchBind: function() {
			var self = this;
			
            /* Bind: Menu button/link */
            self.$searchBtn.on('click', function(e) {
                e.preventDefault();
                self.headerSearchTogglePanel();
            });

            /* Bind: Panel "close" button */
            $('#nm-header-search-close').on('click', function(e) {
                e.preventDefault();
                self.headerSearchTogglePanel();
            });
            
            /* Bind: Search input "input" event */
            var searchInputTo = null;
            self.$searchInput.on('input', function() {
                var $input = $(this);
                
                var searchKey = $input.val(),
                    validSearch = self.searchValidateInput(searchKey);
                
                if (validSearch === 'valid') {
                    self.headerSearchKeywordsHide();
                    
                    if (self.suggestions) {
                        self.suggestionsGet(searchKey);
                    } else {
                        self.searchShowNotice();
                    }
                } else if (validSearch === 'invalid') {
                    self.headerSearchKeywordsShow();
                    
                    if (self.suggestions) {
                        self.suggestionsHide();   
                    } else {
                        self.searchHideNotice();
                    }
                }
            }).trigger('input');
			
			/* Bind: Search input "keypress" event - "ENTER" press */
            self.$searchInput.on('keypress', function(e) {
                var $input = $(this),
                    keyCode = (e.keyCode ? e.keyCode : e.which);
                
                if (keyCode == '13') {
                    // Prevent default form submit
                    e.preventDefault();
                    
                    // Cancel suggestion AJAX
                    if (self.suggestionsAjax) {
                        self.suggestionsAjax.abort();
                    }
                    
                    self.headerSearchStaticSearch($input);
                }
			});
            
            /* Bind: Document "keydown" event - "ESC" press */
            self.$document.on('keydown', function(e) {
                var keyCode = (e.keyCode ? e.keyCode : e.which);
                if (keyCode == '27' && self.$body.hasClass(self.classSearchOpen)) {
                    self.headerSearchTogglePanel();
                }
            });
            
            /* Bind: Clear search button */
            $('#nm-header-search-clear-button').on('click', function(e) {
                e.preventDefault();
                self.$searchInput.val('').trigger('input');
            });
            
            /* Bind: Keyword buttons - Show search suggestions */
            if (self.suggestions) {
                self.$searchKeywords.on('click', '.button', function(e) {
                    e.preventDefault();
                    var keyword = $(this).text();
                    self.$searchInput.val(keyword).focus().trigger('input');
                });
            }
		},
        
        
        /**
		 *	Header search: Toggle panel
		 */
		headerSearchTogglePanel: function() {
            var self = this,
                toggleDelay = 0;
            
            // Remove search suggestions before hiding search panel
            if (self.suggestions && self.$body.hasClass(self.classSearchOpen)) {
                if ($('#nm-search-suggestions-notice').hasClass('show')) { // Make sure suggestions are showing
                    self.suggestionsHide();
                    toggleDelay = 100;
                }
            }
            
            // Hide mobile menu
            if (self.$body.hasClass(self.classMobileMenuOpen)) {
                self.mobileMenuClose();
            }
            
            setTimeout(function() {
                if (! self.$body.hasClass(self.classSearchOpen)) {
                    self.$body.addClass(self.classSearchOpen);
                    
                    // "Focus" search input
                    $('#nm-header-search-input').trigger('focus');
                    
                    self.pageScrollLock()
                } else {
                    self.$body.addClass('header-search-closing');

                    setTimeout(function() {
                        self.$body.removeClass('header-search-closing '+self.classSearchOpen);
                        
                        $('#nm-header-search-input').val(''); // Empty input value
                        
                        self.headerSearchKeywordsShow();
                    }, self.panelsAnimSpeed + 150);
                    
                    self.pageScrollUnlock();
                }

                // Hide search notice
                self.searchHideNotice();
            }, toggleDelay);
		},
        
        
        /**
         *  Header search: Hide keywords
         */
        headerSearchKeywordsHide: function() {
            var self = this;
            self.$searchKeywords.addClass('hide').removeClass('show');
        },
        
        
        /**
         *  Header search: Show keywords
         */
        headerSearchKeywordsShow: function() {
            var self = this;
            
            self.$searchKeywords.removeClass('hide');
            setTimeout(function() {
                self.$searchKeywords.addClass('show');
            }, 50);
        },
        
        
        /**
         *  Header search: Do static search
         */
        headerSearchStaticSearch: function($input) {
            var self = this,
                searchKey = $input.val(),
                validSearch = self.searchValidateInput(searchKey, true);
            
            // Make sure search is valid
            if (validSearch === 'valid') {
                self.searchHideNotice();
                
                if (self.suggestions) {
                    $('#nm-header-search-form').addClass('nm-loader');
                    $('.nm-header-search-wrap').addClass('redirecting');
                }
                
                /*Can be used to do AJAX search: if (self.isShop && self.filtersEnableAjax) {
                    self.headerSearchTogglePanel();
                    self.shopSearchAjaxSearch(searchKey);
                } else {*/
                var searchUrl = nm_wp_vars.shopSearchUrl.toString().replace('%%nmsearchkey%%', encodeURIComponent(searchKey));
                window.location.href = searchUrl;
                //}
            }
        },
        
        
        /**
		 *	Shop search: Bind
		 */
		shopSearchBind: function() {
			var self = this;
            
            /* Bind: Panel "close" button */
            $('#nm-shop-search-close').on('click', function(e) {
                e.preventDefault();
                self.$searchBtn.trigger('click');
            });
            
            
            /* Bind: Search input "input" event */
            $('#nm-shop-search-input').on('input', function() {
                var validSearch = self.searchValidateInput($(this).val());

                //if (validSearch) {
                if (validSearch === 'valid') {
                    self.searchShowNotice();
                //} else {
                } else if (validSearch === 'invalid') {
                    self.searchHideNotice();
                }
            }).trigger('input');
            
			
            if (self.filtersEnableAjax) {
                /* Bind: Input "keypress" event - "ENTER" press */
                self.$searchInput.on('keypress', function(e) {
                    var $input = $(this),
                        searchKey = $input.val(),
                        keyCode = (e.keyCode ? e.keyCode : e.which);
                    
                    if (keyCode == '13') {
                        // Prevent default form submit
                        e.preventDefault();
                        
                        //ORG: var validSearch = self.searchValidateInput(searchKey);
                        var validSearch = self.searchValidateInput(searchKey, true);

                        // Make sure search is valid and unique
                        //ORG: if (validSearch && self.currentSearch !== s) {
                        if (validSearch === 'valid' && self.currentSearch !== searchKey) {

                            if ($input.hasClass('nm-mobile-menu-search')) {
                                self.mobileMenuClose();
                                setTimeout(function() {
                                    $('#nm-mobile-menu-shop-search-input').val(''); // Empty input value
                                    self.shopSearchAjaxSearch(searchKey);
                                }, self.panelsAnimSpeed);
                            } else {
                                if (nm_wp_vars.shopSearchAutoClose != '0') {
                                    // Close search-panel
                                    self.$searchBtn.trigger('click');
                                } else {
                                    self.searchHideNotice();
                                }

                                setTimeout(function() {
                                    self.shopSearchAjaxSearch(searchKey);
                                }, self.filterPanelSlideSpeed);
                            }

                        } else {
                            self.currentSearch = searchKey;
                        }
                    }
                });
            }
		},
        
        
        /**
		 *	Shop search: Toggle panel
		 */
		shopSearchTogglePanel: function() {
			var self = this,
				$searchInput = $('#nm-shop-search-input');
			
			self.$searchPanel.slideToggle(200, function() {
				self.$searchPanel.toggleClass('fade-in');
												
				if (self.$searchPanel.hasClass('fade-in')) {
					// "Focus" search input
					$searchInput.trigger('focus');
				} else {
					// Empty input value
					$searchInput.val('');
				}
				
				self.filterPanelSliding = false;
			});
			
			// Hide search notice
			self.searchHideNotice();
		},
		
		
		/**
		 *	Shop search: Perform AJAX search
		 */
		shopSearchAjaxSearch: function(searchKey) {
			var self = this;
			
			// Blur input to hide virtual mobile keyboards
			self.$searchInput.blur();
            
            if (self.shopSidebarLayout == 'popup' && nm_wp_vars.shopFiltersPopupAutoClose !== '0') {
                 // Hide filters popup
                self.shopFiltersPopupHide();
            }
            
            // Show 'loader' overlay
            self.shopShowLoader();
			
			self.currentSearch = searchKey;
			
            // Create search URL
            var searchUrl = nm_wp_vars.searchUrl.toString().replace('%%nmsearchkey%%', encodeURIComponent(searchKey)); // Note: Encoding the search string with "encodeURIComponent" to avoid breaking the AJAX url
            
			self.searchAjax = $.ajax({
				url: searchUrl,
                data: {
					shop_load: 'search',
					post_type: 'product',
                    shop_filters_layout: self.shopSidebarLayout
				},
				dataType: 'html',
				// Note: Disabling this to avoid the "_(random number)" query-string in pagination links
				//cache: false,
				//headers: {'cache-control': 'no-cache'},
				method: 'GET',
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					console.log('NM: AJAX error - shopSearchAjaxSearch() - ' + errorThrown);
					
					// Hide 'loader' overlay
					self.shopHideLoader();
					
					self.searchAjax = null;
				},
				success: function(data) {
					// Update shop content
					self.shopUpdateContent(data);
					
					self.searchAjax = null;
				}
			});
		},
        
        
        /**
         * Suggestions: Get suggestions
         */
        suggestionsGet: function(searchKey) {
            var self = this;
            
            // Show instant suggestions?
            if (self.instantSuggestions) {
                self.$searchPanel.addClass('doing-search');
                        
                self.instantSuggestionsGet(searchKey);
                
                clearTimeout(self.instantSuggestionsPreloaderTo);
                self.instantSuggestionsPreloaderTo = setTimeout(function() {
                    self.$searchPanel.removeClass('doing-search');
                }, 800);
                
                return;
            }
            
            // Is an AJAX request already running?
            if (self.suggestionsAjax) {
                self.suggestionsAjax.abort();
            }
            
            // Get form elements
            var $searchForm = $('#nm-header-search-form'),
                $searchSuggestions = $('#nm-search-suggestions');
            
            var cacheKey = self.suggestionsEscapeQuery(searchKey);
            
            // Has the search results been cached?
            if (self.suggestionsCacheEnabled && self.suggestionsCache[cacheKey]) {
                self.suggestionsShow(self.suggestionsCache[cacheKey], null, $searchSuggestions);
            } else {
                $searchForm.addClass('nm-loader');
                
                // Add class if suggestions are showing
                if ($searchSuggestions.children('.nm-search-suggestions-inner').children().length) {
                    $searchSuggestions.addClass('doing-search');
                }
                
                // Use WooCommerce AJAX endpoint URL
                var ajaxUrl = (typeof wc_add_to_cart_params !== 'undefined') ? wc_add_to_cart_params.wc_ajax_url : nm_wp_vars.woocommerceAjaxUrl;
                ajaxUrl = ajaxUrl.toString().replace('%%endpoint%%', 'nm_shop_search');

                // Get search results via AJAX
                self.suggestionsAjax = $.ajax({
                    type: 'POST',
                    url: ajaxUrl,
                    data: {
                        s: searchKey
                    },
                    dataType: 'html',
                    success: function(response) {
                        // Cache results
                        if (self.suggestionsCacheEnabled) {
                            self.suggestionsCacheResults(searchKey, response);
                        }
                        
                        self.suggestionsShow(response, null, $searchSuggestions);
                    },
                    complete: function() {
                        $searchForm.removeClass('nm-loader');

                        self.suggestionsAjax = null; // Reset AJAX state
                    }
                });
            }
        },
        
        
        /**
		 *	Suggestions: Make search-query URL safe (so it can be used as an object key)
		 */
        suggestionsEscapeQuery: function(searchQuery) {
            //var escapedQuery = $.param({s: searchQuery});
            var escapedQuery = encodeURIComponent(searchQuery);
            return escapedQuery;
        },
        
        
        /**
		 *	Suggestions: Cache results
		 */
        suggestionsCacheResults: function(searchKey, response) {
            var self = this,
                cacheKey = self.suggestionsEscapeQuery(searchKey);
            
            self.suggestionsCache[cacheKey] = response;
        },
        
        
        /**
		 *	Suggestions: Show
		 */
		suggestionsShow: function(productsHTML, resultCount, $searchSuggestions) {
            var self = this,
                $productList = $('#nm-search-suggestions-product-list');
            
            $productList.html(productsHTML);
            $searchSuggestions.removeClass('doing-search');
            $searchSuggestions.addClass('show');
            
            resultCount = (resultCount) ? resultCount : $productList.children().length;
            
            // Show notice
            if (resultCount == 0) {
                var noticeTxtClass = 'no-results';
            } else {
                var noticeTxtClass = (resultCount == self.suggestionsMaxResults) ? 'press-enter' : 'has-results';
            }
            $('#nm-search-suggestions-notice').removeClass().addClass('show ' + noticeTxtClass);
            
            self.$searchPanel.addClass('has-suggestions');
        },
        
        
        /**
		 *	Suggestions: Hide
		 */
		suggestionsHide: function() {
            var self = this;
            
            // Is an AJAX request running?
            if (self.suggestionsAjax) {
                self.suggestionsAjax.abort();
            }
            
            $('#nm-search-suggestions-product-list').html('');
            $('#nm-search-suggestions').removeClass();
            $('#nm-search-suggestions-notice').removeClass();
            
            self.$searchPanel.removeClass('has-suggestions');
            
            self.searchCurrentQuery = '';
        },
        
        
        /**
		 *	Instant suggestions: Load product data
		 */
        instantSuggestionsLoad: function() {
            var self = this;
            
            self.productDataJSON = null;
            
            // Use WooCommerce AJAX endpoint URL
            var ajaxUrl = (typeof wc_add_to_cart_params !== 'undefined') ? wc_add_to_cart_params.wc_ajax_url : nm_wp_vars.woocommerceAjaxUrl;
            ajaxUrl = ajaxUrl.toString().replace('%%endpoint%%', 'nm_suggestions_product_data');
            
            // Get product data via AJAX
            $.ajax({
                type: 'post',
                url: ajaxUrl,
                dataType: 'json',
                error: function(XMLHttpRequest, textStatus, errorThrown) {
					console.log('NM: AJAX error - instantSuggestionsLoad() - ' + errorThrown);
				},
                success: function(response) {
                    self.productDataJSON = response;
                }
            });
        },
        
        
        /**
		 *	Instant suggestions: Search product data
		 */
        instantSuggestionsSearchData: function(searchKey) {
            // Converting accent characters so searching "ãbç" will match "abc" (product title is also converted in "../includes/woocommerce/search-suggestions-php")
            // - Source: https://stackoverflow.com/questions/990904/remove-accents-diacritics-in-a-string-in-javascript
            if (searchKey.normalize) { // .normalize() not supported in IE
                searchKey = searchKey.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }
            
            var self = this,
                product,
                regexKeyword = new RegExp(searchKey, "i"),
                regexSKU = new RegExp("^" + searchKey + "$"), // Find exact match
                titleMatches,
                matchCount = 0,
                matches = [];
            
            // Title search: Create array from each search key/word
            var titleSearchKeysArray = searchKey.split(' ');
            titleSearchKeysArray = titleSearchKeysArray.slice(0, 3); // Limit search to three words
            
            for (var key in self.productDataJSON) {
                if (self.productDataJSON.hasOwnProperty(key)) {
                    product = self.productDataJSON[key];
                    
                    // Check if each search word has a match with the product title
                    titleMatches = 0;
                    for (var i = 0; i < titleSearchKeysArray.length; i++) {
                        if (product['title'].match(new RegExp(titleSearchKeysArray[i], 'i'))) {
                            titleMatches++;
                        }
                    }
                    
                    //if (product['title'].search(new RegExp(regexKeyword)) != -1) { // Search by title
                    if (titleMatches > 0 && titleMatches == titleSearchKeysArray.length) { // Search by title (if all search words match)
                        matchCount++;
                        matches.push(product);
                    } else if (product['categories'] && product['categories'].search(new RegExp(regexKeyword)) != -1) { // Search by Categories
                        matchCount++;
                        matches.push(product);
                    } else if (product['tags'] && product['tags'].search(new RegExp(regexKeyword)) != -1) { // Search by Tags
                        matchCount++;
                        matches.push(product);
                    } else if (product['sku'] && regexSKU.test(product['sku'])) { // Search by SKU (exact match)
                        matchCount++;
                        matches.push(product);
                    }
                    
                    // Break loop when max results are reached
                    if (matchCount == self.suggestionsMaxResults) { 
                        break;
                    }
                }
            }
            
            return matches;
        },
        
        
        /**
		 *	Instant suggestions: Get suggestions from cached product data
		 */
        instantSuggestionsGet: function(searchKey) {
            var self = this,
                searchResults = self.instantSuggestionsSearchData(searchKey),
                productsHTML = '';
            
            if (searchResults.length > 0) {
                for (var i = 0; i < searchResults.length; i++) {
                    productsHTML += searchResults[i]['product_html'];
                }
                
                self.suggestionsShow(productsHTML, searchResults.length, $('#nm-search-suggestions'));
            } else {
                self.suggestionsShow('', 0, $('#nm-search-suggestions'));
            }
        }
		
	});
	
	// Add extension so it can be called from $.nmThemeExtensions
	$.nmThemeExtensions.search = $.nmTheme.search_init;
	
})(jQuery);
