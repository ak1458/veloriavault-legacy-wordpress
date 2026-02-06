(function($) {
	
	'use strict';
	
	if (!$.nmThemeExtensions)
		$.nmThemeExtensions = {};
	
	function NmTheme() {
		// Initialize scripts
		this.init();
	};
	
	
	NmTheme.prototype = {
	
		/**
		 *	Initialize
		 */
		init: function() {
			var self = this;
            
            // CSS Classes
            self.classHeaderFixed = 'header-on-scroll';
            self.classMobileMenuOpen = 'mobile-menu-open';
            self.classSearchOpen = 'header-search-open';
            self.classCartPanelOpen = 'cart-panel-open';

            // Page elements
            self.$window = $(window);
            self.$document = $(document);
            self.$html = $('html');
            self.$body = $('body');

            // Page includes element
            self.$pageIncludes = $('#nm-page-includes');

            // Page overlay
            self.$pageOverlay = $('#nm-page-overlay');

            // Header
            self.$topBar = $('#nm-top-bar');
            self.$header = $('#nm-header');
            self.$headerPlaceholder = $('#nm-header-placeholder');
            self.headerScrollTolerance = 0;

            // Mobile menu
            self.$mobileMenu = $('#nm-mobile-menu');
            self.$mobileMenuScroller = self.$mobileMenu.children('.nm-mobile-menu-scroll');
            self.$mobileMenuLi = self.$mobileMenu.find('ul li.menu-item');

            // Cart panel
            self.$cartPanel = $('#nm-cart-panel');
            self.cartPanelAnimSpeed = 250;

            // Slide panels animation speed
            self.panelsAnimSpeed = 200;

            // Shop
            self.$shopWrap = $('#nm-shop');
            self.isShop = (self.$shopWrap.length) ? true : false;
            self.shopCustomSelect = (nm_wp_vars.shopCustomSelect != '0') ? true : false;

            // Search
            self.searchEnabled = (nm_wp_vars.shopSearch !== '0') ? true : false;
            
            // Browser check
            self.isChromium = !!window.chrome; // Chromium engine (multiple browsers)
            self.isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
            
            /* Page-load transition */
            if (nm_wp_vars.pageLoadTransition != '0') {
                self.isIos = navigator.userAgent.match(/iPad/i) || navigator.userAgent.match(/iPhone/i);
                if (!self.isIos) {
                    self.$window.on('beforeunload', function(e) {
                        $('#nm-page-load-overlay').addClass('nm-loader'); // Show preloader animation
                        self.$html.removeClass('nm-page-loaded');
                    });
                }
                // Hide page-load overlay - Note: Using the "pageshow" event so the overlay is hidden when the browser "back" button is used (only seems to be needed in Safari though)
                if ('onpagehide' in window) {
                    window.addEventListener('pageshow', function() {
                        setTimeout(function() { self.$html.addClass('nm-page-loaded'); }, 150);
                    }, false);
                } else {
                    setTimeout(function() { self.$html.addClass('nm-page-loaded'); }, 150);
                }
            }
            
			// Remove the CSS transition preload class
			self.$body.removeClass('nm-preload');
			
            // Check for touch device (modernizr)
			self.isTouch = (self.$html.hasClass('touch')) ? true : false;
			
            // Add "has-hover" class - Makes it possible to add :hover selectors for non-touch devices only
            if (self.isTouch) {
                if (nm_wp_vars.touchHover != '0') { self.$html.addClass('has-hover'); }
            } else {
                self.$html.addClass('has-hover');
            }
            
			// Fixed header
			self.headerIsFixed = (self.$body.hasClass('header-fixed')) ? true : false;
			
			// History (browser "back" button): Init
			var enablePushState = (nm_wp_vars.pushStateMobile != '0' || self.$html.hasClass('no-touch')) ? true : false;
            if (enablePushState && self.$html.hasClass('history')) {
				self.hasPushState = true;
				window.history.replaceState({nmShop: true}, '', window.location.href);
			} else {
				self.hasPushState = false;
			}
			
            // Scrollbar
            self.setScrollbarWidth();
            
			// Init header
			self.headerCheckPlaceholderHeight(); // Make sure the header and header-placeholder has the same height
			if (self.headerIsFixed) {
				self.headerSetScrollTolerance();
				self.mobileMenuPrep();
			}
            
            // Init top bar
            self.TopBarInitCycles();
            
            
            // Init cart panel
			if (self.$cartPanel.length) {
                self.cartPanelPrep();
            }
			
            // Init cart shipping meter
            if (self.$cartPanel.length && nm_wp_vars.cartShippingMeter !== '0') {
                self.cartShippingMeterInit();
            }
            
			// Check for old IE browser (IE10 or below)
			var ua = window.navigator.userAgent,
            	msie = ua.indexOf('MSIE ');
			if (msie > 0) {
				self.$html.addClass('nm-old-ie');
			}
            
            // Shop - Infinite load: Snapback cache
            //if (nm_wp_vars.infloadSnapbackCache != '0' && ! self.isTouch && ! self.isFirefox) {
            if (nm_wp_vars.infloadSnapbackCache != '0' && ! self.isTouch) {
                self.shopInfloadSnapbackCache();
            }
            
			// Load extension scripts
			self.loadExtension();
			
			self.bind();
			self.initPageIncludes();
            
			
			// "Add to cart" redirect: Show cart panel
			if (self.$body.hasClass('nm-added-to-cart')) {
				self.$body.removeClass('nm-added-to-cart')
				
                self.$window.on('load', function() {
					// Is cart panel enabled?
					if (self.$cartPanel.length) {
                        // Show cart panel
                        self.cartPanelShow(true, true); // Args: showLoader, addingToCart
                        // Hide cart panel "loader" overlay
                        setTimeout(function() { self.cartPanelHideLoader(); }, 1000);
                    }
				});
			}
		},
		
		
		/**
		 *	Extensions: Load scripts
		 */
		loadExtension: function() {
			var self = this;
            
			// Extension: Shop
			if ($.nmThemeExtensions.shop) {
				$.nmThemeExtensions.shop.call(self);
			}
            
            // Extension: Search
            if (self.searchEnabled && $.nmThemeExtensions.search) {
                $.nmThemeExtensions.search.call(self);
            }
				
			// Extension: Shop - Single product
			if ($.nmThemeExtensions.singleProduct) {
				$.nmThemeExtensions.singleProduct.call(self);
			}
				
			// Extension: Shop - Cart
			if ($.nmThemeExtensions.cart) {
				$.nmThemeExtensions.cart.call(self);
			}
			
			// Extension: Shop - Checkout
			if ($.nmThemeExtensions.checkout) {
				$.nmThemeExtensions.checkout.call(self);
			}
            
            // Extension: Blog
			if ($.nmThemeExtensions.blog) {
				$.nmThemeExtensions.blog.call(self);
			}
		},
		
        
		/**
		 *  Helper: Calculate scrollbar width
		 */
		setScrollbarWidth: function() {
			// From Magnific Popup v1.0.0
			var self = this,
				scrollDiv = document.createElement('div');
			scrollDiv.style.cssText = 'width: 99px; height: 99px; overflow: scroll; position: absolute; top: -9999px;';
			document.body.appendChild(scrollDiv);
			self.scrollbarWidth = scrollDiv.offsetWidth - scrollDiv.clientWidth;
			document.body.removeChild(scrollDiv);
			// /Magnific Popup
		},
        
        
        /**
		 *	Helper: Is page vertically scrollable?
		 */
        pageIsScrollable: function() {
            return document.body.scrollHeight > document.body.clientHeight;
            //jQuery alt: return self.$body.height() > self.$window.height();
        },
        
        
        /**
		 *  Helper: Get parameter from current page URL
		 */
        urlGetParameter: function(param) {
            var url = decodeURIComponent(window.location.search.substring(1)),
                urlVars = url.split('&'),
                paramName, i;

            for (i = 0; i < urlVars.length; i++) {
                paramName = urlVars[i].split('=');
                if (paramName[0] === param) {
                    return paramName[1] === undefined ? true : paramName[1];
                }
            }
        },
		
		
		/**
		 *  Helper: Add/update a key-value pair in the URL query parameters 
		 */
		updateUrlParameter: function(/*uri*/url, key, value) {
			// Remove #hash before operating on the uri
			/*var i = uri.indexOf('#'),
				hash = i === -1 ? '' : uri.substr(i);
			uri = (i === -1) ? uri : uri.substr(0, i);
			
			var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i"),
				separator = (uri.indexOf('?') !== -1) ? "&" : "?";
			
			if (uri.match(re)) {
				uri = uri.replace(re, '$1' + key + "=" + value + '$2');
			} else {
				uri = uri + separator + key + "=" + value;
			}
			
			return uri + hash; // Append #hash*/
            var newUrl = new URL(url);
            newUrl.searchParams.set(key, value);
            newUrl.searchParams.delete('_'); // Remove "_=(timestamp)" query added by jQuery when Ajax cache is disabled to avoid 301 redirect
            
            return newUrl.href;
		},
		
		
		/**
		 *	Helper: Set browser history "pushState" (AJAX url)
		 */
		setPushState: function(pageUrl) {
			var self = this;
			
			// Set browser "pushState"
			if (self.hasPushState) {
				window.history.pushState({nmShop: true}, '', pageUrl);
			}
		},
		
        
        /**
		 *	Top bar: Init cycles
		 */
		TopBarInitCycles: function() {
            var self = this,
                $topBarCycles = self.$topBar.find('.nm-top-bar-cycles');
            
            if ($topBarCycles.length) {
                setInterval(function () {
                    var $topBarActiveCycle = $topBarCycles.find('.active'),

                    $topBarNextCycle = $topBarActiveCycle.next().length ? $topBarActiveCycle.next() : $topBarCycles.children().first();
                    
                    $topBarActiveCycle.addClass('hide');
                    setTimeout(function() {
                        $topBarActiveCycle.removeClass('active hide');
                        $topBarNextCycle.addClass('active');
                    }, 250);
                }, nm_wp_vars.topBarCycleInterval);
            }
        },
		
        
		/**
		 *	Header: Check/set placeholder height
		 */
		headerCheckPlaceholderHeight: function() {
			var self = this;
			
            if (nm_wp_vars.headerPlaceholderSetHeight == 0) {
                console.log('NM: Header placeholder height NOT set');
                return false;
            }
            
			// Make sure the header is not fixed/floated
			if (self.$body.hasClass(self.classHeaderFixed)) {
				return;
			}
			
            var headerHeight = Math.round(self.$header.innerHeight()),
				headerPlaceholderHeight = Math.round(parseInt(self.$headerPlaceholder.css('height')));
            
            // Is there a height difference of more than 1 pixel between the header and header-placeholder?
            if (Math.abs(headerHeight - headerPlaceholderHeight) > 1) {
                self.$headerPlaceholder.css('height', headerHeight+'px');
			}
		},
		
		
		/**
		 *	Header: Set scroll tolerance
		 */
		headerSetScrollTolerance: function() {
			var self = this;
			
			self.headerScrollTolerance = (self.$topBar.length && self.$topBar.is(':visible')) ? self.$topBar.outerHeight(true) : 0;
		},
        
        
        /**
		 *	Header: Fix/stick on scroll
		 */
        headerFixOnScroll: function() {
            var self = this;
            
            self.$window.on('scroll.nmheader', function() {
                if (self.$body.hasClass('nm-scroll-lock')) {
                    return;
                }
                
                var currentPageScroll = (self.$body.hasClass('nm-scroll-lock')) ? Math.abs(self.$body.offset().top) : document.documentElement.scrollTop; // Offset is used when page-scroll is "locked"   
                
                //if (self.$document.scrollTop() > self.headerScrollTolerance || Math.abs(self.$body.offset().top) > self.headerScrollTolerance) {
                if (currentPageScroll > self.headerScrollTolerance) {
                    if (!self.$body.hasClass(self.classHeaderFixed)) {
                        self.$body.addClass(self.classHeaderFixed);
                    }
                } else {
                    if (self.$body.hasClass(self.classHeaderFixed)) {
                        self.$body.removeClass(self.classHeaderFixed);
                    }
                }
            });
        },
		
		
        /**
		 *	Header: Fix/stick on scroll-up
		 */
        headerFixOnScrollUp: function(self) {
            var self = this;
            
            // Variables to track scrolling
            let lastScrollTop = 0;
            let totalScrollDistance = 0;
            let isSticky = false;
            let scrollAnimationTimer;
            const initialThreshold = (Math.ceil(self.$header.outerHeight(true)) * 2); // Initial scroll threshold (pixels)
            const scrollUpThreshold = 50; // Scroll up distance threshold (pixels)
            
            window.addEventListener('scroll', () => {
                // Get current scroll position
                // Note: .offset().top is needed when page scrolling is "locked"
                const currentScrollTop = document.documentElement.scrollTop || Math.abs(self.$body.offset().top);
                
                // Check if page scroll is "locked", and prevent negative scroll values
                if (self.$body.hasClass('nm-scroll-lock') || currentScrollTop < 0) {
                    return;
                }

                // Calculate scroll distance for this event
                const scrollDelta = Math.abs(currentScrollTop - lastScrollTop);

                // Determine scroll direction
                const isScrollingUp = currentScrollTop < lastScrollTop;
                
                //console.log('Is Scrolling Up:', isScrollingUp);
                
                // Reset totalScrollDistance when scrolling down
                if (! isScrollingUp) {
                    totalScrollDistance = 0;
                } else {
                    // Accumulate scroll distance when scrolling up
                    totalScrollDistance += scrollDelta;
                }
                
                //console.log('Current Scroll:', currentScrollTop, 'Last Scroll:', lastScrollTop, 'Total Distance:', totalScrollDistance);

                // Check if scrolling up, total scroll distance > 100 pixels, and scroll position > 200 pixels
                if (isScrollingUp && totalScrollDistance > scrollUpThreshold && currentScrollTop > initialThreshold) {
                    if (! isSticky) {
                        self.$body.addClass(self.classHeaderFixed);
                        clearTimeout(scrollAnimationTimer);
                        scrollAnimationTimer = setTimeout(function() {
                            self.$header.addClass('animate-in');
                        }, 50);
                        
                        isSticky = true;
                    }
                } else {
                    if (isSticky) {
                        self.$body.removeClass(self.classHeaderFixed);
                        self.$header.removeClass('animate-in');
                        
                        isSticky = false;
                    }
                }

                // Update last scroll position
                lastScrollTop = currentScrollTop;
                
                //console.log('Updated Last Scroll:', lastScrollTop);
            });
        },
        
        
		/**
		 *	Bind scripts
		 */
		bind: function() {
			var self = this;
			
            
			/* Bind: Window resize */
			var timer = null;
            self.$window.on('resize', function() {
				if (timer) { clearTimeout(timer); }
				timer = setTimeout(function() {
					// Make sure the header and header-placeholder has the same height
					self.headerCheckPlaceholderHeight();
																	
					if (self.headerIsFixed) {
						self.headerSetScrollTolerance();
						self.mobileMenuPrep();
					}
				}, 250);
			});
            
            
            /* Mobile menu: Hide menu on Desktop using media query matching */
            if (! self.$body.hasClass('mobile-menu-desktop')) { // Make sure mobile menu is disabled for desktop
                var _hideMobileMenu = function(mediaQuery) {
                    if (mediaQuery.matches && self.$body.hasClass(self.classMobileMenuOpen)) {
                        self.mobileMenuClose();
                    }
                };
                
                var breakpointMobileMenu = window.matchMedia('(min-width: 992px)');
                
                // Use "addEventListener" when available ("addListener" is deprecated)
                try {
                    breakpointMobileMenu.addEventListener('change', _hideMobileMenu);
                } catch(err1) {
                    try {
                        breakpointMobileMenu.addListener(_hideMobileMenu);
                    } catch(err2) {
                        console.error('NM: Media query matching - ' + err2);
                    }
                }
            }
            
            
            /* Bind: Mobile "orientationchange" event */
            if (self.isTouch) {
                self.$window.on('orientationchange', function() {
                    self.$body.addClass('touch-orientation-change');
                    setTimeout(function() { 
                        self.$body.removeClass('touch-orientation-change');
                    }, 500);
                });
            }
            
			
			/* Bind: Window scroll (Fixed header) */
			if (self.headerIsFixed) {
                if (self.$body.hasClass('header-fixed-on-scroll-up')) {
                    self.headerFixOnScrollUp();
                } else {
                    self.headerFixOnScroll();
                }
                
				self.$window.trigger('scroll');
			}
			
			
			/* Bind: Menus - Sub-menu hover (set position and "bridge" height) */
			var $topMenuItems = $('#nm-top-menu').children('.menu-item'),
				$mainMenuItems = $('#nm-main-menu-ul').children('.menu-item'),
                $secondaryMenuItems = $('#nm-right-menu-ul').children('.menu-item'),
                $menuItems = $().add($topMenuItems).add($mainMenuItems).add($secondaryMenuItems);
            
            $menuItems.on('mouseenter', function() {
                var $menuItem = $(this),
                    $subMenu = $menuItem.children('.sub-menu');
                
                if ($subMenu.length) {
                    // Sub-menu: Set position/offset (prevents menu from being positioned outside the browser window)
                    var windowWidth = self.$window.innerWidth(),
                        subMenuOffset = $subMenu.offset().left,
                        subMenuWidth = $subMenu.width(),
                        subMenuGap = windowWidth - (subMenuOffset + subMenuWidth);
                    if (subMenuGap < 0) {
                        $subMenu.css('left', (subMenuGap-33)+'px');
                    }
                    
                    // Header sub-menus: Set "bridge" height (prevents menu from closing when hovering outside its parent <li> element)
                    if (! $menuItem.hasClass('bridge-height-set')) {
                        var $headerMenuContainer = $menuItem.closest('nav');
                        if ($headerMenuContainer.length) {
                            $menuItem.addClass('bridge-height-set');
                            var menuBridgeHeight = Math.ceil(($headerMenuContainer.height() - $menuItem.height()) / 2);
                            $subMenu.children('.nm-sub-menu-bridge').css('height', (menuBridgeHeight + 1) + 'px');
                        }
                    }
                }
            }).on('mouseleave', function() {
                // Reset sub-menu position
                var $subMenu = $(this).children('.sub-menu');
                if ($subMenu.length) {
                    $subMenu.css('left', '');
                }
            });
			
            
            /* Bind: Header - Shop links */
            if (! self.isShop) {
                self.$header.on('click.nmHeaderShopRedirect', '.shop-redirect-link > a', function(e) {
                    e.preventDefault();
                    var url = $(this).attr('href');
                    window.location.href = url + '#shop';
                });
            }
			
            
			/* Bind: Mobile menu - Header button */
            self.$header.on('click.nmMobileMenuToggle', '.nm-mobile-menu-button', function(e) {
				e.preventDefault();
                
				if (!self.$body.hasClass(self.classMobileMenuOpen)) {
					self.mobileMenuOpen();
				} else {
					self.mobileMenuClose();
				}
			});
			
            /* Bind: Mobile menu - Panel close button */
			$('#nm-mobile-menu-close-button').on('click', function(e) {
				e.preventDefault();
				self.mobileMenuClose();
			});
            
			/* Function: Mobile menu - Toggle sub-menu */
			var _mobileMenuToggleSub = function($menu, $subMenu) {
                $menu.toggleClass('active');
				$subMenu.toggleClass('open');
			};
			
			/* Bind: Mobile menu list elements */
			self.$mobileMenuLi.on('click.nmMenuToggle', function(e) {
                e.stopPropagation(); // Prevent click event on parent menu link
                
                var $this = $(this);
                
                // Prevent closing menu when clicking outside a menu-item in an open menu "panel"
                if (self.$body.hasClass('mobile-menu-panels') && ! $(e.target).is('li, a, .nm-menu-toggle')) {
                    return;
                }
                
                self.$document.trigger('nm_mobile_menu_toggle', [e, this]);
                
				var $thisSubMenu = $this.children('.sub-menu');
                
                if ($thisSubMenu.length) {
                    // Prevent toggle when "nm-notoggle" class is added -and- the "plus" icon wasn't clicked
                    if ($this.hasClass('nm-notoggle') && ! $(e.target).hasClass('nm-menu-toggle')) { return; }
                    
                    e.preventDefault();
                    _mobileMenuToggleSub($this, $thisSubMenu);
				}
			});
            
            /* Bind: Mobile menu "back" button */
            $('.nm-mobile-sub-menu-back-button').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $menu = $(this).closest('.menu-item'),
					$thisSubMenu = $menu.children('.sub-menu');
                
                $menu.addClass('hide-panel');
                
                setTimeout(function() {
                    _mobileMenuToggleSub($menu, $thisSubMenu);
                    $menu.removeClass('hide-panel');
                }, 250);
            });
			
			
			/* Bind: Cart panel */
			if (self.$cartPanel.length) {
				self.cartPanelBind();
			}
			
			
			/* Bind: Login/register popup */
			if (self.$pageIncludes.hasClass('login-popup')) {
                self.loginRegisterNonceValuesUpdated = false;
                
				$('#nm-menu-account-btn').on('click.nmLoginShowPopup', function(e) {
					e.preventDefault();
                    self.loginRegisterPopupOpen();
				});
			}
			
			
			/* Bind: Page overlay */
            self.$body.on('click', '.nm-page-overlay', function() {
                var $pageOverlay = $(this);
				self.pageOverlayClick($pageOverlay);
			});
            
            
            /* Bind: Shop attributes */
			if (nm_wp_vars.shopAttsSwapImage == '1') {
                // Enable on touch?
                var shopAttsSwapImageOnTouch = (self.isTouch && nm_wp_vars.shopAttsSwapImageOnTouch == '1') ? true : false;
                
                if ( shopAttsSwapImageOnTouch ) {
                    /* Bind: Shop attribute links */
                    self.$body.on('click', '.nm-shop-loop-attribute-link',/* { passive: true }, */function(e) {
                        var $link = $(this);
                        if (! $link.hasClass('selected')) {
                            e.preventDefault();
                            $link.parent().children('.selected').removeClass('selected');
                            $link.addClass('selected');
                            self.shopAttsSwapImage($link);
                        }
                    });
                } else {
                    /* Bind: Shop attribute links */
                    self.$body.on('mouseenter', '.nm-shop-loop-attribute-link', function() {
                        var $link = $(this);
                        self.shopAttsSwapImage($link);
                    });

                    if (nm_wp_vars.shopAttsSwapImageRevert == '1') {
                        /* Bind: Shop attributes container */
                        self.$body.on('mouseleave.nmShopImageRevert', '.nm-shop-loop-attributes', function() {
                            var $attsWrap = $(this);
                            self.shopAttsSwapImageRevert($attsWrap, false);
                        });

                        /* Bind: Page load */
                        self.$window.on('beforeunload', function(e) {
                            // Unbind "mouseleave.nmShopImageRevert" event when attribute link is clicked (page loads)
                            self.$body.off('mouseleave.nmShopImageRevert');
                        });
                    }
                }
            }
		},
		
        
        /**
		 *	Page scroll: Lock
		 */
		pageScrollLock: function() {
            var self = this;
            
            // Only lock on touch screens
            if (self.isTouch) {
                self.pageScrollOffset = window.pageYOffset;

                self.$body.addClass('nm-scroll-lock').css({position: 'fixed', top: '-'+self.pageScrollOffset+'px', width: '100%', overflow: 'hidden'});
            }
        },
        
        
        /**
		 *	Page scroll: Unlock
		 */
		pageScrollUnlock: function() {
            var self = this;
            
            if (self.isTouch) {
                self.$body.css({position: '', top: '', width: '', overflow: ''});
                
                // Using a timeout to make sure scroll is fully re-enabled when removing class (used when checking scroll position for "sticky" header)
                setTimeout(function() {
                    self.$body.removeClass('nm-scroll-lock');
                }, 100);
                
                window.scrollTo(0, self.pageScrollOffset);
            }
        },
        
        
        /**
		 *	Page overlay: Show
		 */
		pageOverlayShow: function() {
            var self = this;
            
            self.$pageOverlay.addClass('show');
		},
        
        
        /**
		 *	Page overlay: Click
		 */
		pageOverlayClick: function($pageOverlay) {
            var self = this,
                hideOverlay = true,
                delay = 0;
            
            $pageOverlay = ($pageOverlay) ? $pageOverlay : self.$pageOverlay;
            
            // Mobile menu
            if (self.$body.hasClass(self.classMobileMenuOpen)) {
                self.mobileMenuClose();
                hideOverlay = false;
            // Header search
            } else if (self.$body.hasClass(self.classSearchOpen)) {
                self.headerSearchTogglePanel();
                hideOverlay = false;
            // Cart panel
            } else if (self.$body.hasClass(self.classCartPanelOpen)) {
                self.cartPanelHide();
                hideOverlay = false;
            // Shop popup panel
            } else if (self.$body.hasClass('shop-filters-popup-open')) {
                self.shopFiltersPopupHide();
                hideOverlay = false;
            }
            
            if (hideOverlay) {
                // A delay is needed when suggestions are removed from header search panel
                setTimeout(function() {
                    // Trigger "nm_page_overlay_hide" event
                    self.$body.trigger('nm_page_overlay_hide');

                    $pageOverlay.addClass('fade-out');
                    setTimeout(function() {
                        $pageOverlay.removeClass().addClass('nm-page-overlay'); // Remove all additional classes from page-overlay element
                    //}, self.panelsAnimSpeed);
                    }, 250);
                }, delay);
            }
		},
        
		
		/**
		 *	Mobile menu: Prepare (add CSS)
		 */
		mobileMenuPrep: function() {
			var self = this,
				windowHeight = self.$window.height() - self.$header.outerHeight(true);
			
			self.$mobileMenuScroller.css({'max-height': windowHeight+'px', 'margin-right': '-'+self.scrollbarWidth+'px'});
		},
        
        
        /**
		 *	Mobile menu: Open
		 */
		mobileMenuOpen: function(hideOverlay) {
            var self = this,
                headerPosition = self.$header.outerHeight(true);
            
            self.$mobileMenuScroller.css('margin-top', headerPosition+'px');
            
            self.pageScrollLock();
            
            self.$body.addClass(self.classMobileMenuOpen);
        },
        
        
        /**
		 *	Mobile menu: Close
		 */
		mobileMenuClose: function() {
            var self = this;
            
            self.$body.addClass('mobile-menu-closing');
            self.$body.removeClass(self.classMobileMenuOpen);
            
            setTimeout(function() {
                self.$body.removeClass('mobile-menu-closing');
            }, self.cartPanelAnimSpeed);
            
            // Hide open menus (first level only)
            /*setTimeout(function() {
                $('#nm-mobile-menu-main-ul').children('.active').removeClass('active').children('ul').removeClass('open');
                $('#nm-mobile-menu-secondary-ul').children('.active').removeClass('active').children('ul').removeClass('open');
            }, 250);*/
            
            self.pageScrollUnlock();
        },
        
        
        /**
		 * Login/register popup: Open
		 */
		loginRegisterPopupOpen: function() {
            var self = this;
            
            // Checkout page fix: Make sure the login form is visible
            $('#nm-login-wrap').children('.login').css('display', '');
            
            $.magnificPopup.open({
                mainClass: 'nm-login-popup nm-mfp-fade-in',
                //alignTop: true,
                closeMarkup: '<a class="mfp-close nm-font nm-font-close2"></a>',
                removalDelay: 180,
                closeOnBgClick: false,
                items: {
                    src: '#nm-login-popup-wrap',
                    type: 'inline'
                },
                callbacks: {
                    open: function() {
                        if (self.loginRegisterNonceValuesUpdated) {
                            return;
                        }
                        self.loginRegisterNonceValuesUpdated = true;
                        
                        // Update popup "nonce" input values so the form can be submitted on cached pages
                        $.ajax({
                            type: 'POST',
                            //url: nm_wp_vars.ajaxUrl,
                            //data: { action: 'nm_ajax_login_get_nonce_fields' },
                            url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'nm_ajax_login_get_nonces'),
                            dataType: 'json',
                            cache: false,
                            headers: {'cache-control': 'no-cache'},
                            success: function(noncesJson) {
                                $('#woocommerce-login-nonce').attr('value', noncesJson.login);
                                $('#woocommerce-register-nonce').attr('value', noncesJson.register);
                            }
                        });
                    },
                    close: function() {
                        // Make sure the login form is displayed when the modal is re-opened
                        $('#nm-login-wrap').addClass('inline fade-in slide-up');
                        $('#nm-register-wrap').removeClass('inline fade-in slide-up');
                    }
                }
            });
        },
        
        
		/**
		 *	Cart panel: Prepare
		 */
		cartPanelPrep: function() {
			var self = this;
            
            // Cart panel: Set Ajax state
            self.cartPanelAjax = null;
            
            if (nm_wp_vars.cartPanelQtyArrows != '0') {
                // Cart panel: Bind quantity-input buttons
                self.quantityInputsBindButtons(self.$cartPanel);

                // Cart panel - Quantity inputs: Bind "blur" event
                self.$cartPanel.on('blur', 'input.qty', function() {
                    var $quantityInput = $(this),
                        currentVal = parseFloat($quantityInput.val()),
                        max	= parseFloat($quantityInput.attr('max'));

                    // Validate input values
                    if (currentVal === '' || currentVal === 'NaN') { currentVal = 0; }
                    if (max === 'NaN') { max = ''; }

                    // Make sure the value is not higher than the max value
                    if (currentVal > max) { 
                        $quantityInput.val(max);
                        currentVal = max;
                    };

                    // Is the quantity value more than 0?
                    if (currentVal > 0) {
                        self.cartPanelUpdate($quantityInput);
                    }
                });

                // Cart panel - Quantity inputs: Bind "nm_qty_change" event
                self.$document.on('nm_qty_change', function(event, quantityInput) {
                    // Is the cart-panel open?
                    if (self.$body.hasClass(self.classCartPanelOpen)) {
                        self.cartPanelUpdate($(quantityInput));
                    }
                });
            }
		},
        
        
		/**
		 *	Cart panel: Bind
		 */
		cartPanelBind: function() {
			var self = this;
			
			// Touch event handling
			if (self.isTouch) {
				//if (self.headerIsFixed) { // Allow page overlay "touchmove" event if header is not fixed/floating
                // Bind: Page overlay "touchmove" event
                /*self.$pageOverlay.on('touchmove', function(e) {
                    e.preventDefault(); // Prevent default touch event
                });*/
				//}
				
				// Bind: Cart panel "touchmove" event
				self.$cartPanel.on('touchmove', function(e) {				
					e.stopPropagation(); // Prevent event propagation (bubbling)
				});
			}
			
			/* Bind: "Cart" buttons */
			$('#nm-menu-cart-btn, #nm-mobile-menu-cart-btn').on('click.nmAtc', function(e) {
				e.preventDefault();										
				
				// Close the mobile menu first					
				if (self.$body.hasClass(self.classMobileMenuOpen)) {
					var $this = $(this);
                    self.mobileMenuClose();
                    //self.$pageOverlay.removeClass('nm-mobile-menu-overlay'); // Remove mobile menu class from page-overlay element
				    $this.trigger('click'); // Trigger this function again
				} else {
				    self.cartPanelShow();
                }
			});
			
			/* Bind: "Close" button */
			$('#nm-cart-panel-close').on('click.nmCartPanelClose', function(e) {
				e.preventDefault();
                self.cartPanelHide();
			});
            
            /* Bind: "Continue shopping" button */
			self.$cartPanel.on('click.nmCartPanelClose', '#nm-cart-panel-continue', function(e) {
				e.preventDefault();
                self.cartPanelHide();
			});
		},
        
        
		/**
		 *	Cart panel: Show
		 */
		cartPanelShow: function(showLoader, addingToCart) {
			var self = this;
            
            // Show cart panel on add-to-cart?
            if (addingToCart && nm_wp_vars.cartPanelShowOnAtc == '0') {
                self.shopShowNotices();
                return;
            }
            
			if (showLoader) {
                self.cartPanelShowLoader();
			}
			
            self.pageScrollLock();
            
            self.$body.addClass('cart-panel-opening '+self.classCartPanelOpen);
            
            setTimeout(function() {
                self.$body.removeClass('cart-panel-opening');
            }, self.cartPanelAnimSpeed);
		},
        
        
        /**
		 *	Cart panel: Hide
		 */
		cartPanelHide: function() {
			var self = this;
			
            self.$body.addClass('cart-panel-closing');
            self.$body.removeClass(self.classCartPanelOpen);
            
            setTimeout(function() {
                self.$body.removeClass('cart-panel-closing');
            }, self.cartPanelAnimSpeed);
            
            self.pageScrollUnlock();
		},
		
        
        /**
		 *	Cart panel: Show loader
		 */
		cartPanelShowLoader: function() {
            var self = this;
            self.$cartPanel.addClass('loading');
		},
        
		
		/**
		 *	Cart panel: Hide loader
		 */
		cartPanelHideLoader: function() {
            var self = this;
            self.$cartPanel.removeClass('loading');
		},
		
		
        /**
		 *	Cart panel: Update quantity
         *
         *  Note: Based on the "quantity_update" function in "../woocommerce/assets/js/frontend/cart.js"
		 */
        cartPanelUpdate: function($quantityInput) {
            var self = this;
            
            // Is an Ajax request already running?
            if (self.cartPanelAjax) {
                self.cartPanelAjax.abort(); // Abort current Ajax request
            }
            
            // Show thumbnail loader
            $quantityInput.closest('li').addClass('loading');
            
            var $cartForm = $('#nm-cart-panel-form'), // The "#nm-cart-panel-form" element is placed in the "../footer.php" file
                $cartFormNonce = $cartForm.find('#_wpnonce'),
                data = {};
            
            if ( ! $cartFormNonce.length ) {
                console.log( 'NM - cartPanelUpdate: Nonce field not found.' );
                return;
            }
            
            data['nm_cart_panel_update'] = '1';
			data['update_cart'] = '1';
            data[$quantityInput.attr('name')] = $quantityInput.val();
            data['_wpnonce'] = $cartFormNonce.val();
            
			// Make call to actual form post URL.
			self.cartPanelAjax = $.ajax({
				type:     'POST',
				url:      $cartForm.attr('action'),
                data:     data,
				dataType: 'html',
				error: function(XMLHttpRequest, textStatus, errorThrown) {
				    console.log('NM: AJAX error - cartPanelUpdate() - ' + errorThrown);
                    
                    // Hide any visible thumbnail loaders (no need to hide on "success" since the cart panel is replaced)
                    $('#nm-cart-panel .cart_list').children('.loading').removeClass('loading');
                },
                success:  function(response) {
                    // Replace cart fragments
                    $(document.body).trigger('wc_fragment_refresh').trigger('updated_cart_totals');
				},
				complete: function() {
                    self.cartPanelAjax = null; // Reset Ajax state
                }
			});
        },
        
        
        /**
         *	Cart shipping meter: Save progress
         */
        cartShippingMeterSaveProgress: function($cartShippingMeter) {
            var shippingMeterProgress = ($cartShippingMeter) ? $cartShippingMeter.data('progress') : 0;
            localStorage.setItem('nmThemeShippingMeterProgress', shippingMeterProgress);
        },
        
        
        /**
         *	Cart shipping meter: Get progress
         */
        cartShippingMeterGetProgress: function($cartShippingMeter) {
            var shippingMeterProgress = localStorage.getItem('nmThemeShippingMeterProgress');
            shippingMeterProgress = (shippingMeterProgress) ? shippingMeterProgress : 0;
            return shippingMeterProgress;
        },
        
        
        /**
         *	Cart shipping meter: Set progress
         */
        cartShippingMeterSetProgress: function($cartShippingMeter) {
            var self = this,
                shippingMeterProgress = $cartShippingMeter.data('progress'),
                shippingMeterOldProgress = self.cartShippingMeterGetProgress();
            
            $cartShippingMeter.css('width', shippingMeterOldProgress+'%');
            setTimeout(function() {
                $cartShippingMeter.addClass('transition-on');
                $cartShippingMeter.css('width', shippingMeterProgress+'%');
                
                // Wait until animation is complete before updating progress (in case animation is restarted when "added_to_cart" event is run more than once)
                setTimeout(function() {
                    $cartShippingMeter.attr('data-progress', shippingMeterProgress);
                    self.cartShippingMeterSaveProgress($cartShippingMeter);
                }, 400);
            }, 100);
        },
        
        
        /**
         *	Cart shipping meter: Init
         */
        cartShippingMeterInit: function() {
            var self = this,
                $cartShippingMeter;
            
            self.cartShippingMeterSaveProgress(null);

            // Bind MutationObserver for cart panel to update shipping meter progress after it has updated in DOM
            var cartShippingMeterObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    $cartShippingMeter = $(mutation.target).find('.nm-cart-shipping-meter-bar-progress');
                    
                    if ($cartShippingMeter.length) {
                        self.cartShippingMeterSetProgress($cartShippingMeter);
                    } else {
                        self.cartShippingMeterSaveProgress(null); // Reset progress to 0 when meter is removed
                    }
                });
            });
            cartShippingMeterObserver.observe(self.$cartPanel[0], {
                attributes: false,
                childList: true,
                characterData: false
            });
        },
        
        
        /**
		 *	Shop: Replace fragments
		 */
        shopReplaceFragments: function(fragments) {
            var $fragment;
            $.each(fragments, function(selector, fragment) {
                $fragment = $(fragment);
                if ($fragment.length) {
                    $(selector).replaceWith($fragment);
                }
            });
        },
        
        
        /**
         *  Shop - Attributes: Swap product thumbnail
         */
        shopAttsSwapImage: function($link) {
            var attributeImgSrc = $link.data('attr-src');
			
            if (attributeImgSrc) {
                var $productWrap = $link.closest('.product'),
                    $productThumb = $productWrap.find('.attachment-woocommerce_thumbnail').first();
				
				// Is the thumbnail a "picture" tag?
				if ($productThumb[0].tagName == 'PICTURE') {
					// Change attribute values for "picture" tag's child elements ("source" and "img")
					var $pictureChild;
					$productThumb.children().each(function() {
						$pictureChild = $(this);
						if ($pictureChild[0].hasAttribute('src')) {
							$pictureChild.attr('src', attributeImgSrc);
						}
						if ($pictureChild[0].hasAttribute('srcset')) {
							$pictureChild.attr('srcset', attributeImgSrc);
						}
					});
				} else {
					// Change attribute values for standard "img" tag
					$productThumb.attr('src', attributeImgSrc);
					$productThumb.attr('srcset', attributeImgSrc);
				}
				
                $productWrap.addClass('nm-attr-image-set');
            } else {
                this.shopAttsSwapImageRevert($link, true);
            }
        },
        
        
        /**
         *  Shop - Attributes: Revert swapped product thumbnail
         */
        shopAttsSwapImageRevert: function($this, isAttrLink) {
            var $productWrap = $this.closest('.product');

            if ($productWrap.hasClass('nm-attr-image-set')) {
                var $attrWrap = (isAttrLink) ? $this.closest('.nm-shop-loop-attributes') : $this,
                    productThumbSrc = $attrWrap.data('thumb-src');

                if (productThumbSrc) {
                    var $productThumb = $attrWrap.closest('.product').find('.attachment-woocommerce_thumbnail').first(),
                        productThumbSrcset = $attrWrap.data('thumb-srcset');
					
					// Is the thumbnail a "picture" tag?
					if ($productThumb[0].tagName == 'PICTURE') {
						// Revert attribute values for "picture" tag's child elements ("source" and "img")
						var $pictureChild;
						$productThumb.children().each(function() {
							$pictureChild = $(this);
							if ($pictureChild[0].hasAttribute('src')) {
								$pictureChild.attr('src', productThumbSrc);
							}
							if ($pictureChild[0].hasAttribute('srcset')) {
								$pictureChild.attr('srcset', productThumbSrcset);
							}
						});
					} else {
						// Revert attribute values for standard "img" tag
						$productThumb.attr('src', productThumbSrc);
						$productThumb.attr('srcset', productThumbSrcset);
					}
					
                    $productWrap.removeClass('nm-attr-image-set');
                }
            }
        },
        
        
        /**
		 *	Shop - Infinite load: Snapback cache for browser "back" button
		 */
        shopInfloadSnapbackCache: function() {
            var self = this;
            
            /* Bind: Track page loads when cache is saved
             * - Note: Run on every page (place above conditional below) */
            self.$window.on('beforeunload', function() {
                var pageViews = sessionStorage.getItem('pageCacheViews');
                
                if (pageViews) {
                    // Only update page-view count if cache is saved
                    var pageCache = sessionStorage.getItem('pageCache');
                    
                    if (pageCache && pageCache !== '{}') {
                        pageViews = parseInt(pageViews) + 1;
                        sessionStorage.setItem('pageCacheViews', pageViews);
                    }
                } else {
                    sessionStorage.setItem('pageCacheViews', 1);
                }
            });
            
            // Only need to run code below on shop catalog
            if (! $('#nm-shop-browse-wrap').length) {
                return false;
            }
            
            //console.log('NM: Snapback cache ACTIVE');
            
            var snapbackCache = SnapbackCache({bodySelector: '#nm-shop-browse-wrap'}),
                snapbackCacheLinks = nm_wp_vars.infloadSnapbackCacheLinks; // Comma separated list of Shop links that can be used to generate cache
            
            /* Bind: Product list links */
            self.$body.on('click', '#nm-shop-browse-wrap a', function() {
                var $link = $(this);
                
                if ($('#nm-shop-browse-wrap').hasClass('products-loaded') && $link.is(snapbackCacheLinks)) {
                    snapbackCache.cachePage();
                }
            });
        },
        
        
        /**
		 *	Quantity inputs: Bind buttons
		 */
		quantityInputsBindButtons: function($container) {
			var self = this,
                clickThrottle,
                clickThrottleTimeout = nm_wp_vars.cartPanelQtyThrottleTimeout;
            
			/* 
			 *	Bind buttons click event
			 *	Note: Modified code from WooCommerce core (v2.2.6)
			 */
			$container.off('click.nmQty').on('click.nmQty', '.nm-qty-plus, .nm-qty-minus', function() {
				if (clickThrottle) { clearTimeout(clickThrottle); }
                
                // Get elements and values
				var $this		= $(this),
					$qty		= $this.closest('.quantity').find('.qty'),
					currentVal	= parseFloat($qty.val()),
					max			= parseFloat($qty.attr('max')),
					min			= parseFloat($qty.attr('min')),
					step		= $qty.attr('step');
				
				// Format values
				if (!currentVal || currentVal === '' || currentVal === 'NaN') currentVal = 0;
				if (max === '' || max === 'NaN') max = '';
				if (min === '' || min === 'NaN') min = 0;
				if (step === 'any' || step === '' || step === undefined || parseFloat(step) === 'NaN') step = 1;
                
				// Change the value
				if ($this.hasClass('nm-qty-plus')) {
					if (max && (max == currentVal || currentVal > max)) {
						$qty.val(max);
					} else {
						$qty.val(currentVal + parseFloat(step));
                        clickThrottle = setTimeout(function() { self.quantityInputsTriggerEvents($qty); }, clickThrottleTimeout);
					}
				} else {
					if (min && (min == currentVal || currentVal < min)) {
						$qty.val(min);
					} else if (currentVal > 0) {
						$qty.val(currentVal - parseFloat(step));
                        clickThrottle = setTimeout(function() { self.quantityInputsTriggerEvents($qty); }, clickThrottleTimeout);
					}
				}
			});
		},
        
        
        /**
		 *    Quantity inputs: Trigger events
		 */
        quantityInputsTriggerEvents: function($qty) {
            var self = this;
            
            // Trigger quantity input "change" event
            $qty.trigger('change');

            // Trigger custom event
            self.$document.trigger('nm_qty_change', $qty);
        },
        
        
		/**
		 *	Initialize "page includes" elements
		 */
		initPageIncludes: function() {
			var self = this;
			
            /* VC element: Row - Full height */
            if (self.$pageIncludes.hasClass('row-full-height')) {
                var _rowSetFullHeight = function() {
                    var $row = $('.nm-row-full-height:first');

                    if ($row.length) {
                        var windowHeight = self.$window.height(),
                            rowOffsetTop = $row.offset().top,
                            rowFullHeight;
                        
                        // Set/calculate Row's viewpoint height (vh)
                        windowHeight > rowOffsetTop && (rowFullHeight = 100 - rowOffsetTop / (windowHeight / 100), $row.css('min-height', rowFullHeight+'vh'));
                    }
                }
                
                _rowSetFullHeight(); // Init
                
                /* Bind: Window "resize" event for changing Row height */
                var rowResizeTimer = null;
                self.$window.on('resize.nmRow', function() {
                    if (rowResizeTimer) { clearTimeout(rowResizeTimer); }
                    rowResizeTimer = setTimeout(function() { _rowSetFullHeight(); }, 250);
                });
            }
            
			/* VC element: Row - Video (YouTube) background */
			var rowVideoHide = (self.isTouch && nm_wp_vars.rowVideoOnTouch == 0) ? true : false; // Show video on touch?
            if (!rowVideoHide && self.$pageIncludes.hasClass('video-background')) {
				$('.nm-row-video').each(function() {
					var $row = $(this),
						youtubeUrl = $row.data('video-url');
					
					if (youtubeUrl) {
						var youtubeId = vcExtractYoutubeId(youtubeUrl); // Note: function located in: "nm-js_composer_front(.min).js"
						
						if (youtubeId) {
							insertYoutubeVideoAsBackground($row, youtubeId); // Note: function located in: "nm-js_composer_front(.min).js"
						}
					}
				});
			}
			
            self.$window.on('load', function() {
				
				/* Element: Banner */
				if (self.$pageIncludes.hasClass('banner')) {
                    self.elementBanner($('.nm-banner'));
				}
				
				/* Element: Banner slider */
				if (self.$pageIncludes.hasClass('banner-slider')) {
                    $('.nm-banner-slider').each(function() {
                        self.elementBannerSlider($(this));
                    });
				}
                
                /* Element: Product slider */
				if (self.$pageIncludes.hasClass('product-slider')) {
                    $('.nm-product-slider').each(function() {
                        self.elementProductSlider($(this));
                    });
				}
				
                /* Element: Product reviews slider */
				if (self.$pageIncludes.hasClass('product-reviews-slider')) {
                    $('.nm-product-reviews-slider').each(function() {
                        self.elementProductReviewsSlider($(this));
                    });
				}
                
				/* Element: Post slider */
				if (self.$pageIncludes.hasClass('post-slider')) {
                    $('.nm-post-slider').each(function() {
                        self.elementPostSlider($(this));
                    });
				}
				
				/* WP element: Gallery - popup */
                if (nm_wp_vars.wpGalleryPopup != '0' && self.$pageIncludes.hasClass('wp-gallery')) {
					$('.gallery').each(function() {
						$(this).magnificPopup({
							mainClass: 'nm-wp-gallery-popup nm-mfp-fade-in',
							closeMarkup: '<a class="mfp-close nm-font nm-font-close2"></a>',
							removalDelay: 180,
							delegate: '.gallery-icon > a', // Gallery item selector
							type: 'image',
							gallery: {
								enabled: true,
								arrowMarkup: '<a title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir% nm-font nm-font-angle-right"></a>'
							},
                            image: {
                                titleSrc: function(item) {
                                    // Get title from caption element
                                    var title = item.el.parent().next('.wp-caption-text').text();
                                    return title || '';
                                }
                            },
							closeBtnInside: false
						});
					});
				}
			
			});
			
			
			/* Element: Product categories */
			if (self.$pageIncludes.hasClass('product_categories')) {
                var self = this,
                    $categories = $('.nm-product-categories');

                self.elementProductCategoriesBindLinks($categories);

                if (self.$pageIncludes.hasClass('product_categories_masonry')) {
                    self.$window.on('load', function() {
                        for (var i = 0; i < $categories.length; i++) {
                            self.elementProductCategories($($categories[i]));
                        }
                    });
                }
			}
			
			/* Element: Lightbox */
            if (self.$pageIncludes.hasClass('lightbox')) {
                $('.nm-lightbox').each(function() {
                    self.elementLightbox($(this));
                });
			}
            
            /* Element: Elementor - Tabs */
            if (self.$pageIncludes.hasClass('elementor-tabs')) {
                $('.nm-elementor-tabs').each(function() {
                    self.elementElementorTabs($(this));
                });
            }
            
            /* Element: Product brands */
            var $brandsContainer = $('#brands_a_z');
            if ($brandsContainer.length) {
                // Set minimum height on main container to prevent scrollbar from being removed when filtering
                $brandsContainer.css('min-height', $brandsContainer.height() + 'px');
                
                /* Bind: Brands character index */
                $brandsContainer.children('.brands_index').on('click', 'a', function(e) {
                    e.preventDefault();                    
                    
                    var $indexButton = $(this),
                        $indexButtonLi = $indexButton.parent(),
                        targetHash = $indexButton.attr('href');
                    
                    // Brands: Remove previous "current-index" classes
                    $indexButton.closest('.brands_index').children('.current-index').removeClass('current-index');
                    $brandsContainer.children('.current-index').removeClass('current-index');
                    
                    $indexButtonLi.addClass('current-index');
                    
                    if (targetHash === '#all') {
                        $brandsContainer.removeClass('is-filtered');
                    } else {
                        $brandsContainer.addClass('is-filtered');
                        $(targetHash).parent('.nm-brands-wrapper').addClass('current-index');
                    }
                });
                
                /* Bind: Brands "to top" buttons */
                $brandsContainer.on('click', '.top', function(e) {
                    e.preventDefault();
                    window.scrollTo({top: 0, behavior: 'smooth'});
                });
            }
		},
        
        
        /**
		 *	Element: Banner
		 */
        elementBanner: function($banners) {
            var self = this;
            
            /* Bind: Banner shop links (AJAX) */
            if (self.isShop && self.filtersEnableAjax) {
                $banners.find('.nm-banner-shop-link').on('click.nmBannerAjax', function(e) {
                    e.preventDefault();
                    var shopUrl = $(this).attr('href');
                    if (shopUrl) {
                        self.shopExternalGetPage($(this).attr('href')); // Smooth-scroll to top, then load shop page
                    }
                });
            }
        },
        
        
        /**
		 *	Element: Banner - Add text animation class
		 */
        elementBannerAddAnimClass: function($slider, currentSlide) {
            // Make sure the slide has changed
            if ($slider.slideIndex != currentSlide) {
                $slider.slideIndex = currentSlide;

                // Remove animation class from previous banner
                if ($slider.$bannerContent) {
                    $slider.$bannerContent.removeClass($slider.bannerAnimation);
                }

                var $slideActive = ($slider.isSlick) ? $slider.find('.slick-track .slick-active') : $slider.children('.flickity-viewport').children('.flickity-slider').children('.is-selected'); // Note: Don't use "currentSlide" index to find the active element (Slick slider's "infinite" setting clones slides)
                $slider.$bannerContent = $slideActive.find('.nm-banner-text-inner');

                if ($slider.$bannerContent.length) {
                    $slider.bannerAnimation = $slider.$bannerContent.data('animate');
                    $slider.$bannerContent.addClass($slider.bannerAnimation);
                }
            }
        },
        
        
        /**
		 *	Element: Banner slider
		 */
        elementBannerSlider: function($slider) {
            var self = this;
            
            $slider.isSlick = ($slider.hasClass('plugin-slick')) ? true : false;

            // Wrap slider's banner elements in a "div" element
            $slider.children().wrap('<div class="nm-banner-slide"></div>');

            if ($slider.isSlick) {
                var slickOptions = {
                    arrows: false,
                    prevArrow: '<a class="slick-prev"><i class="nm-font nm-font-angle-thin-left"></i></a>',
                    nextArrow: '<a class="slick-next"><i class="nm-font nm-font-angle-thin-right"></i></a>',
                    dots: false,
                    edgeFriction: 0,
                    infinite: false,
                    pauseOnHover: true,
                    speed: 350,
                    touchThreshold: 30
                };
                slickOptions = $.extend(slickOptions, $slider.data()); // Extend default slider settings with data attribute settings

                // Slick slider: Event - Init
                $slider.on('init', function() {
                    self.$document.trigger('banner-slider-loaded');
                    self.elementBannerAddAnimClass($slider, 0);
                });

                // Slick slider: Event - After slide change
                $slider.on('afterChange', function(event, slick, currentSlide) {
                    self.elementBannerAddAnimClass($slider, currentSlide);
                });

                // Slick slider: Event - After position/size changes
                $slider.on('setPosition', function(event, slick) {
                    var $slider = slick.$slider,
                        $currentSlide = $(slick.$slides[slick.currentSlide]);
                    self.elementBannerSliderToggleLayoutClass($slider, $currentSlide);
                });

                // Slick slider: Initialize
                $slider.slick(slickOptions);
            } else {
                var sliderOptions = $.extend({}, $slider.data('options')), // Extend default slider options with data attribute options
                    sliderInstance;

                // Flickity: Single event - Initial slide select
                $slider.one('select.flickity', function() {
                    self.$document.trigger('banner-slider-loaded');
                    self.elementBannerAddAnimClass($slider, 0);
                });

                // Flickity: Event - Slide settled at end position
                $slider.on('settle.flickity', function() {
                    var currentSlide = sliderInstance.selectedIndex;
                    self.elementBannerAddAnimClass($slider, currentSlide);
                });

                // Flickity: Initialize
                $slider.flickity(sliderOptions);
                sliderInstance = $slider.data('flickity'); // Get slider instance

                // Flickity: Event: Slide select (keep below .flickity initialization)
                $slider.on('select.flickity', function() {
                    var $slider = $(this),
                        $currentSlide = (sliderInstance) ? $(sliderInstance.selectedElement) : $slider.find('.is-selected'); // In case the instance isn't available
                    self.elementBannerSliderToggleLayoutClass($slider, $currentSlide);
                });
                $slider.trigger('select.flickity'); // Trigger initial event

                // Flickity: Banner text "parallax" effect
                if ($slider.hasClass('has-text-parallax')) {
                    var $text = $slider.find('.nm-banner-text'),
                        x;
                    // Flickity: Event - Triggered when the slider moves
                    $slider.on('scroll.flickity', function(event, progress) {
                        sliderInstance.slides.forEach(function(slide, i) {
                            // Fix for "wrapAround" Flickity option - https://github.com/metafizzy/flickity/issues/468 - Note: This doesn't work with two slides
                            /*if (0 === i) {
                                x = Math.abs(sliderInstance.x) > sliderInstance.slidesWidth ? (sliderInstance.slidesWidth + sliderInstance.x + sliderInstance.slides[sliderInstance.slides.length - 1].outerWidth + slide.target) : slide.target + sliderInstance.x;
                            } else if (i === sliderInstance.slides.length - 1) {
                                x = Math.abs(sliderInstance.x) + sliderInstance.slides[i].outerWidth < sliderInstance.slidesWidth ? (slide.target - sliderInstance.slidesWidth + sliderInstance.x - sliderInstance.slides[i].outerWidth) : slide.target + sliderInstance.x;
                            } else {
                                x = slide.target + sliderInstance.x;
                            }

                            $text[i].style.transform = 'translate3d(' + x * (1/3) + 'px,0,0)';*/
                            // Note: Works with 2 slides, but not with the "wrapAround" option
                            x = (slide.target + sliderInstance.x) * 1/3;
                            $text[i].style.transform = 'translate3d(' + x + 'px,0,0)';
                        });
                    });
                }
            }
        },
        
        
        /**
		 *	Element: Banner slider - Toggle layout class
		 */
        elementBannerSliderToggleLayoutClass: function($slider, $currentSlide) {
            var $currentBanner = $currentSlide.children('.nm-banner');

            // Is the alternative text layout showing?
            if ($currentBanner.hasClass('alt-mobile-layout')) {
                if ($currentBanner.children('.nm-banner-content').css('position') != 'absolute') { // Content container has static/relative position when the alt. layout is showing
                    $slider.addClass('alt-mobile-layout-showing');
                } else {
                    $slider.removeClass('alt-mobile-layout-showing');
                }
            } else {
                $slider.removeClass('alt-mobile-layout-showing');
            }
        },
        
        
        /**
		 *	Element: Product slider
		 */
        elementProductSlider: function($sliderWrap) {
            var $slider = $sliderWrap.find('.nm-products:first'),
                sliderOptions = {
                    adaptiveHeight: true, // NOTE: Doesn't work with multiple slides
                    arrows: false,
                    prevArrow: '<a class="slick-prev"><i class="nm-font nm-font-angle-thin-left"></i></a>',
                    nextArrow: '<a class="slick-next"><i class="nm-font nm-font-angle-thin-right"></i></a>',
                    dots: true,
                    edgeFriction: 0,
                    infinite: false,
                    speed: 350,
                    touchThreshold: 30,
                    slidesToShow: 4,
                    slidesToScroll: 4,
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 3,
                                slidesToScroll: 3
                            }
                        },
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 2
                            }
                        },
                        {
                            breakpoint: 518,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1
                            }
                        }
                    ]
                };

            // Extend default slider settings with data attribute settings
            sliderOptions = $.extend(sliderOptions, $sliderWrap.data());

            // Responsive columns
            var colMobile = $sliderWrap.data('slides-to-show-mobile'),
                col_1024 = (parseInt(sliderOptions.slidesToShow) == 2) ? 2 : 3,
                col_768 = (parseInt(colMobile) > 2) ? colMobile : 2,
                col_518 = colMobile;

            // Set responsive columns
            sliderOptions.responsive[0].settings.slidesToShow = col_1024;
            sliderOptions.responsive[0].settings.slidesToScroll = col_1024;
            sliderOptions.responsive[1].settings.slidesToShow = col_768;
            sliderOptions.responsive[1].settings.slidesToScroll = col_768;
            sliderOptions.responsive[2].settings.slidesToShow = col_518;
            sliderOptions.responsive[2].settings.slidesToScroll = col_518;

            $slider.slick(sliderOptions);
        },
        
        
        /**
		 *	Element: Product reviews slider
		 */
        elementProductReviewsSlider: function($sliderWrap) {
            var self = this,
                $slider = $sliderWrap.find('.nm-product-reviews-ul'),
                sliderOptions = {
                    adaptiveHeight: true, // NOTE: Doesn't work with multiple slides
                    arrows: false,
                    prevArrow: '<a class="slick-prev"><i class="nm-font nm-font-angle-thin-left"></i></a>',
                    nextArrow: '<a class="slick-next"><i class="nm-font nm-font-angle-thin-right"></i></a>',
                    dots: true,
                    edgeFriction: 0,
                    infinite: false,
                    speed: 350,
                    touchThreshold: 30,
                    slidesToShow: 4,
                    slidesToScroll: 4,
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 3,
                                slidesToScroll: 3
                            }
                        },
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 2
                            }
                        },
                        {
                            breakpoint: 518,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1
                            }
                        }
                    ]
                };

            // Extend default slider settings with data attribute settings
            sliderOptions = $.extend(sliderOptions, $sliderWrap.data());
            
            if (sliderOptions.slidesToShow == 2) {
                // Max. two columns
                sliderOptions.responsive[0].settings.slidesToShow = 2;
                sliderOptions.responsive[0].settings.slidesToScroll = 2;
            }
            
            /* Function: Set slider height based on tallest slide */
            var _sliderSetHeight = function(slider) {
                // Make sure slider is visible (Elementor can trigger resize when dragging)
                if (! $(slider).is(':visible')) { return; }
                
                var activeSlides = [],
                    tallestSlide = 0;

                // Short timeout in order to get correct active slides
                setTimeout(function() {
                    $('.slick-track .slick-active', slider).each(function(item) {
                        activeSlides[item] = $(this).outerHeight();
                    });
                    
                    activeSlides.forEach(function(item) {
                        if (item > tallestSlide) {
                            tallestSlide = item;
                        }
                    });
                    
                    $('.slick-list', slider).css('height', Math.ceil(tallestSlide)+'px');
                }, 10);
            };
            
            // Bind slider init/change/resize events
            $slider.on('init', function(slick) {
                _sliderSetHeight(this);
            });
            $slider.on('beforeChange', function(slick, currentSlide, nextSlide) {
                _sliderSetHeight(this);
            });
            var sliderResizeTimer = null;
            self.$window.on('resize.reviewsSlider', function() {
                if (sliderResizeTimer) { clearTimeout(sliderResizeTimer); }
                sliderResizeTimer = setTimeout(function() { _sliderSetHeight($slider[0]); }, 250);
            });
            
            // Init slider
            $slider.slick(sliderOptions);
        },
        
        
        /**
		 *	Element: Post slider
		 */
        elementPostSlider: function($slider) {
            var sliderOptions = {
                    adaptiveHeight: true, // NOTE: Doesn't work with multiple slides
                    arrows: false,
                    prevArrow: '<a class="slick-prev"><i class="nm-font nm-font-angle-thin-left"></i></a>',
                    nextArrow: '<a class="slick-next"><i class="nm-font nm-font-angle-thin-right"></i></a>',
                    dots: true,
                    edgeFriction: 0,
                    infinite: false,
                    pauseOnHover: true,
                    speed: 350,
                    touchThreshold: 30,
                    slidesToShow: 4,
                    slidesToScroll: 4,
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 3,
                                slidesToScroll: 3
                            }
                        },
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 2
                            }
                        },
                        {
                            breakpoint: 518,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1
                            }
                        }
                    ]
                };

            // Extend default slider settings with data attribute settings
            sliderOptions = $.extend(sliderOptions, $slider.data());

            if (sliderOptions.slidesToShow == 2) {
                // Max. two columns
                sliderOptions.responsive[0].settings.slidesToShow = 2;
                sliderOptions.responsive[0].settings.slidesToScroll = 2;
            }

            $slider.slick(sliderOptions);
        },
        
        
        /**
		 *	Element: Product categories
		 */
        elementProductCategories: function($categories) {
            if ($categories.hasClass('masonry-enabled')) {
                var $categoriesUl = $categories.children('.woocommerce').children('ul');

                // Initialize Masonry
                $categoriesUl.masonry({
                    itemSelector: '.product-category',
                    gutter: 0,
                    //horizontalOrder: true,
                    initLayout: false // Disable initial layout
                });

                // Masonry event: "layoutComplete"
                $categoriesUl.masonry('on', 'layoutComplete', function() {
                    $categoriesUl.closest('.nm-product-categories').removeClass('nm-loader'); // Hide preloader
                    $categoriesUl.addClass('show');
                });

                // Trigger initial layout
                $categoriesUl.masonry();
            }
        },
        
        
        /**
		 *	Element: Product categories - Bind shop links
		 */
        elementProductCategoriesBindLinks: function($categories) {
            var self = this;
            
            if (self.isShop && self.filtersEnableAjax) {
                $categories.find('.product-category a').on('click', function(e) {
                    e.preventDefault();
                    // Load shop category page
                    self.shopExternalGetPage($(this).attr('href'));
                });
            }
        },
        
        
        /**
		 *	Element: Lightbox
		 */
        elementLightbox: function($lightbox) {
            $lightbox.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $this = $(this),
                    type = $this.data('mfp-type'),
                    lightboxOptions = {
                        mainClass: 'nm-wp-gallery-popup nm-mfp-zoom-in',
                        closeMarkup: '<a class="mfp-close nm-font nm-font-close2"></a>',
                        removalDelay: 180,
                        type: type,
                        closeBtnInside: false,
                        image: {
                            titleSrc: 'data-mfp-title'
                        }
                    };
                
                lightboxOptions.closeOnContentClick = (type == 'inline') ? false : true; // Disable "closeOnContentClick" for inline/HTML lightboxes
                
                $this.magnificPopup(lightboxOptions).magnificPopup('open');
            });
        },
        
        
        /**
		 *	Element: Elementor - Tabs
		 */
        elementElementorTabs: function($tabs) {
            var $tab, $tabActive;

            $tabs.children('.nm-elementor-tabs-wrapper').children('.nm-elementor-tab').on('click', function(e) {
                e.preventDefault();

                $tab = $(this);
                
                if ($tab.hasClass('nm-elementor-active')) { return; }
                
                $tabActive = $tab.closest('.nm-elementor-tabs-wrapper').children('.nm-elementor-active');
                
                // Change tab "active" class
                $tabActive.removeClass('nm-elementor-active');
                $tab.addClass('nm-elementor-active');

                // Change content "active" class
                $('#'+$tabActive.attr('aria-controls')).removeClass('nm-elementor-active');
                $('#'+$tab.attr('aria-controls')).addClass('nm-elementor-active');
            });
        }
        
	};
	
	
	// Add core script to $.nmTheme so it can be extended
	$.nmTheme = NmTheme.prototype;
    
    
    /**
     *  Document ready (".ready()" doesn't work with nested ".load()" functions in jQuery 3.0+)
     *
     *  Source: http://stackoverflow.com/questions/9899372/pure-javascript-equivalent-to-jquerys-ready-how-to-call-a-function-when-the/9899701#9899701
     */
    $.nmReady = function(fn) {
        // See if DOM is already available
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            // Call on next available tick
            setTimeout(fn, 1);
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    };
    
    
    $.nmReady(function() {
		// Initialize script
		$.nmThemeInstance = new NmTheme();
	});
	
})(jQuery);
