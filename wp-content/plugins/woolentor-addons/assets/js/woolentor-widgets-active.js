;(function($){
"use strict";

    /**
     * Senitize HTML
     */
    var woolentorSanitizeHTML = function (str) {
        if( str ){
            return str.replace(/[&<>"']/g, function (c) {
                switch (c) {
                    case '&': return '&amp;';
                    case '<': return '&lt;';
                    case '>': return '&gt;';
                    case '"': return '&quot;';
                    case "'": return '&#39;';
                    default: return c;
                }
            });
        }else{
            return '';
        }
    }

    /**
     * Sanitize Object
     */
    var woolentorSanitizeObject = function (inputObj) {
        const sanitizedObj = {};
    
        for (let key in inputObj) {
            if (inputObj.hasOwnProperty(key)) {
                let value = inputObj[key];
    
                // Sanitize based on the value type
                if (typeof value === 'string') {
                    // Sanitize strings to prevent injection
                    sanitizedObj[key] = woolentorSanitizeHTML(value);
                } else if (typeof value === 'number') {
                    // Ensure numbers are valid (you could also set limits if needed)
                    sanitizedObj[key] = Number.isFinite(value) ? value : 0;
                } else if (typeof value === 'boolean') {
                    // Keep boolean values as they are
                    sanitizedObj[key] = value;
                } else {
                    // Handle other types if needed (e.g., arrays, objects)
                    sanitizedObj[key] = value;
                }
            }
        }
    
        return sanitizedObj;
    }

   /* 
    * Product Slider 
    */
    var WidgetProductSliderHandler = function ($scope, $) {

        var slider_elem = $scope.find('.product-slider').eq(0);

        if (slider_elem.length > 0) {

            slider_elem[0].style.display='block';

            var settings = woolentorSanitizeObject(slider_elem.data('settings'));
            var arrows = settings['arrows'];
            var dots = settings['dots'];
            var autoplay = settings['autoplay'];
            var infinite = settings.hasOwnProperty('infinite') ? settings['infinite'] : true;
            var rtl = settings['rtl'];
            var autoplay_speed = parseInt(settings['autoplay_speed']) || 3000;
            var animation_speed = parseInt(settings['animation_speed']) || 300;
            var fade = settings['fade'];
            var pause_on_hover = settings['pause_on_hover'];
            var display_columns = parseInt(settings['product_items']) || 4;
            var scroll_columns = parseInt(settings['scroll_columns']) || 4;
            var tablet_width = parseInt(settings['tablet_width']) || 800;
            var tablet_display_columns = parseInt(settings['tablet_display_columns']) || 2;
            var tablet_scroll_columns = parseInt(settings['tablet_scroll_columns']) || 2;
            var mobile_width = parseInt(settings['mobile_width']) || 480;
            var mobile_display_columns = parseInt(settings['mobile_display_columns']) || 1;
            var mobile_scroll_columns = parseInt(settings['mobile_scroll_columns']) || 1;

            slider_elem.not('.slick-initialized').slick({
                arrows: arrows,
                prevArrow: '<button type="button" class="slick-prev"><i class="fa fa-angle-left"></i></button>',
                nextArrow: '<button type="button" class="slick-next"><i class="fa fa-angle-right"></i></button>',
                dots: dots,
                infinite: infinite,
                autoplay: autoplay,
                autoplaySpeed: autoplay_speed,
                speed: animation_speed,
                fade: false,
                pauseOnHover: pause_on_hover,
                slidesToShow: display_columns,
                slidesToScroll: scroll_columns,
                rtl: rtl,
                responsive: [
                    {
                        breakpoint: tablet_width,
                        settings: {
                            slidesToShow: tablet_display_columns,
                            slidesToScroll: tablet_scroll_columns
                        }
                    },
                    {
                        breakpoint: mobile_width,
                        settings: {
                            slidesToShow: mobile_display_columns,
                            slidesToScroll: mobile_scroll_columns
                        }
                    }
                ]
            });
        };
    };

    /*
    * Custom Tab
    */
    function woolentor_tabs( $tabmenus, $tabpane ){
        $tabmenus.on('click', 'a', function(e){
            e.preventDefault();
            var $this = $(this),
                $target = $this.attr('href');
            $this.addClass('htactive').parent().siblings().children('a').removeClass('htactive');
            $( $tabpane + $target ).addClass('htactive').siblings().removeClass('htactive');

            // slick refresh
            if( $('.slick-slider').length > 0 ){
                var $id = $this.attr('href');
                $( $id ).find('.slick-slider').slick('refresh');
            }

        });
    }

    /* 
    * Universal product 
    */
    function productImageThumbnailsSlider( $slider ){
        $slider.slick({
            dots: true,
            arrows: true,
            prevArrow: '<button class="slick-prev"><i class="sli sli-arrow-left"></i></button>',
            nextArrow: '<button class="slick-next"><i class="sli sli-arrow-right"></i></button>',
        });
    }
    if( $(".ht-product-image-slider").length > 0 ) {
        productImageThumbnailsSlider( $(".ht-product-image-slider") );
    }

    var WidgetThumbnaisImagesHandler = function thumbnailsimagescontroller(){
        woolentor_tabs( $(".ht-product-cus-tab-links"), '.ht-product-cus-tab-pane' );
        woolentor_tabs( $(".ht-tab-menus"), '.ht-tab-pane' );

        // Countdown
        var finalTime, daysTime, hours, minutes, second;
        $('.ht-product-countdown').each(function() {
            var $this = $(this), finalDate = $(this).data('countdown');
            var customlavel = $(this).data('customlavel');
            $this.countdown(finalDate, function(event) {
                $this.html(event.strftime('<div class="cd-single"><div class="cd-single-inner"><h3>%D</h3><p>'+woolentorSanitizeHTML(customlavel.daytxt)+'</p></div></div><div class="cd-single"><div class="cd-single-inner"><h3>%H</h3><p>'+woolentorSanitizeHTML(customlavel.hourtxt)+'</p></div></div><div class="cd-single"><div class="cd-single-inner"><h3>%M</h3><p>'+woolentorSanitizeHTML(customlavel.minutestxt)+'</p></div></div><div class="cd-single"><div class="cd-single-inner"><h3>%S</h3><p>'+woolentorSanitizeHTML(customlavel.secondstxt)+'</p></div></div>'));
            });
        });

    }

    /*
    * Tool Tip
    */
    function woolentor_tool_tips(element, content) {
        if ( content == 'html' ) {
            var tipText = element.text();
        } else {
            var tipText = element.attr('title');
        }
        element.on('mouseover', function() {
            if ( $('.woolentor-tip').length == 0 ) {
                element.before('<span class="woolentor-tip">' + woolentorSanitizeHTML(tipText) + '</span>');
                $('.woolentor-tip').css('transition', 'all 0.5s ease 0s');
                $('.woolentor-tip').css('margin-left', 0);
            }
        });
        element.on('mouseleave', function() {
            $('.woolentor-tip').remove();
        });
    }

    /*
    * Tooltip Render
    */
    var WidgetWoolentorTooltipHandler = function woolentor_tool_tip(){
        $('a.woolentor-compare').each(function() {
            woolentor_tool_tips( $(this), 'title' );
        });
        $('.woolentor-cart a.add_to_cart_button,.woolentor-cart a.added_to_cart,.woolentor-cart a.button').each(function() {
            woolentor_tool_tips( $(this), 'html');
        });
        $('a.woolentor-quick-checkout-button').each(function() {
            woolentor_tool_tips( $(this), 'title' );
        });
    }

    /*
    * Product Tab
    */
    var  WidgetProducttabsHandler = woolentor_tabs( $(".ht-tab-menus"),'.ht-tab-pane' );

    /*
    * Single Product Video Gallery tab
    */
    var WidgetProductVideoGallery = function thumbnailsvideogallery(){
        woolentor_tabs( $(".woolentor-product-video-tabs"), '.video-cus-tab-pane' );
    }

    /**
     * WoolentorAccordion
     */
    var WoolentorAccordion = function ( $scope, $ ){
        var accordion_elem = $scope.find('.htwoolentor-faq').eq(0);

        var data_opt = accordion_elem.data('settings');

        if ( accordion_elem.length > 0 ) {
            var $id = accordion_elem.attr('id');
            new Accordion('#' + $id, {
                duration: 500,
                showItem: data_opt.showitem,
                elementClass: 'htwoolentor-faq-card',
                questionClass: 'htwoolentor-faq-head',
                answerClass: 'htwoolentor-faq-body',
            });
        }
        
    };


    /**
     * WoolentorOnePageSlider
     */
    var WoolentorOnePageSlider = function ( $scope, $ ){

        var slider_elem = $scope.find('.ht-full-slider-area').eq(0);

        if ( slider_elem.length > 0 ) {

            /* Jarallax active  */
            $('.ht-parallax-active').jarallax({
                speed: 0.4,
            });
            
            $('#ht-nav').onePageNav({
                currentClass: 'current',
                changeHash: false,
                scrollSpeed: 750,
                scrollThreshold: 0.5,
                filter: '',
                easing: 'swing',
            });
            
            /*------ Wow Active ----*/
            new WOW().init();

            /*---------------------
            Video popup
            --------------------- */
            $('.ht-video-popup').magnificPopup({
                type: 'iframe',
                mainClass: 'mfp-fade',
                removalDelay: 160,
                preloader: false,
                zoom: {
                    enabled: true,
                }
            });
    
        }

    };

    /**
     * LoadMore Product Ajax Action handeler
     * @param {String} selectorBtn // LoadMore Button Selector
     * @param {String} loadMoreWrapper // LoadMore Enable Track Class
     */
    var WooLentorLoadMore = function( selectorBtn, loadMoreWrapper ){

        selectorBtn.on('click', function(e) {
            e.preventDefault();
    
            const $button = selectorBtn;
            const $loader = $button.siblings('.woolentor-ajax-loader');
            const $grid = $('#' + $button.data('grid-id'));
            const currentPage = parseInt($button.data('page'));
            const maxPages = parseInt($button.data('max-pages'));
            const dataLayout = $grid.attr('data-show-layout');
    
            if (currentPage > maxPages) {
                return;
            }
    
            $button.hide();
            $loader.show();
    
           let settings = loadMoreWrapper.attr( 'data-wl-widget-settings' );
    
            // Prepare AJAX data
            const ajaxData = {
                action: 'woolentor_load_more_products',
                nonce: typeof woolentor_addons !== 'undefined' ? woolentor_addons.ajax_nonce : '',
                page: currentPage,
                settings: settings,
                viewlayout: typeof dataLayout === 'undefined' ? '' : dataLayout
            };
    
            // AJAX request to load more products
            $.ajax({
                url: typeof woolentor_addons !== 'undefined' ? woolentor_addons.woolentorajaxurl : '',
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    if (response.success && response.data.html) {

                        // Append new products
                        const $newProducts = $(response.data.html);
                        $grid.append($newProducts);
                            
                        // Update page counter
                        $button.data('page', currentPage+1);
    
                        // Show button if more pages available
                        if (currentPage < maxPages) {
                            $button.show();
                        } else {
                            $button.text($button.data('complete-loadtxt')).prop('disabled', true).show();
                        }
                    }
                    $loader.hide();
                },
                error: function(xhr, status, error) {
                    $loader.hide();
                    $button.show();
                    console.log("Status:", status, "Error:", error);
                }
            });
        });

    }

    var WooLentorInfiniteScroll = function(selectorBtn, productLoadWrapper ){

        let isLoading = false;
        const $loader = selectorBtn.find('.woolentor-ajax-loader');
        const $grid = $('#' + selectorBtn.data('grid-id'));
        const paginationArea = productLoadWrapper.find('.woolentor-pagination-infinite');

        function loadMoreOnScroll() {
            if (isLoading) return;

            // Calculate trigger point based on product grid bottom position
            const gridOffset = $grid.offset().top;
            const gridHeight = $grid.outerHeight();
            const gridBottom = gridOffset + gridHeight;
            const scrollTop = $(window).scrollTop();
            const windowHeight = $(window).height();
            const triggerPoint = gridBottom - windowHeight - 100; // 100px before grid end

            if (scrollTop >= triggerPoint) {
                const currentPage = parseInt(selectorBtn.data('page'));
                const maxPages = parseInt(selectorBtn.data('max-pages'));

                if (currentPage > maxPages) {
                    $(window).off('scroll', loadMoreOnScroll);
                    return;
                }

                paginationArea.css('margin-top', '30px');
                isLoading = true;
                $loader.show();

                let settings = productLoadWrapper.attr( 'data-wl-widget-settings' );
                const dataLayout = $grid.attr('data-show-layout');

                // AJAX request to load more products
                $.ajax({
                    url: typeof woolentor_addons !== 'undefined' ? woolentor_addons.woolentorajaxurl : '',
                    type: 'POST',
                    data: {
                        action: 'woolentor_load_more_products',
                        nonce: typeof woolentor_addons !== 'undefined' ? woolentor_addons.ajax_nonce : '',
                        page: currentPage,
                        settings: settings,
                        viewlayout: typeof dataLayout === 'undefined' ? '' : dataLayout
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            // Append new products
                            const $newProducts = $(response.data.html);
                            $grid.append($newProducts);

                            // Update page counter
                            selectorBtn.data('page', currentPage + 1);

                            // Check if we've reached the last page
                            if (currentPage > maxPages) {
                                $(window).off('scroll', loadMoreOnScroll);
                                selectorBtn.remove();
                            }
                        }
                    },
                    complete: function() {
                        $loader.hide();
                        isLoading = false;
                        paginationArea.css('margin-top', '0');
                    },
                    error: function() {
                        $loader.hide();
                        isLoading = false;
                    }
                });
            }
        }

        // Bind scroll event
        $(window).on('scroll', loadMoreOnScroll);

    }

    /**
     * Quantaty Manager
     */
    var WooLentorQtnManager = function(){
        $(document).on('click', '.woolentor-qty-minus', function(e) {
            e.preventDefault();
            const $input = $(this).siblings('.woolentor-qty-input');
            const $qtnSelector = $(this).parent('.woolentor-quantity-selector').siblings('.add_to_cart_button');
            const currentVal = parseInt($input.val()) || 1;
            const minVal = parseInt($input.attr('min')) || 1;

            if (currentVal > minVal) {
                $input.val(currentVal - 1);
                $qtnSelector.attr('data-quantity', currentVal - 1);
                $input.trigger('change');
            }
        });

        $(document).on('click', '.woolentor-qty-plus', function(e) {
            e.preventDefault();
            const $input = $(this).siblings('.woolentor-qty-input');
            const $qtnSelector = $(this).parent('.woolentor-quantity-selector').siblings('.add_to_cart_button');
            const currentVal = parseInt($input.val()) || 1;
            const maxVal = parseInt($input.attr('max')) || 999;

            if (currentVal < maxVal) {
                $input.val(currentVal + 1);
                $qtnSelector.attr('data-quantity', currentVal + 1);
                $input.trigger('change');
            }
        });
    }

    /**
     * Grid and View Mode Manager
     */
    var WooLentorViewModeManager = function($selector, $style = 'modern'){
        $(document).on('click', '.woolentor-layout-btn', function(e){
            e.preventDefault();

            const $this = $(this);
            const layout = $this.data('layout');
            const $gridContainer = $this.closest('.woolentor-product-grid, .woolentor-filters-enabled').find($selector);

            // Update active button state
            $this.siblings().removeClass('woolentor-active');
            $this.addClass('woolentor-active');

            // Update grid container layout classes
            if ($gridContainer.length > 0) {
                // Remove existing layout classes from container
                $gridContainer.removeClass('woolentor-layout-grid woolentor-layout-list');

                // Add new layout class to container
                $gridContainer.addClass('woolentor-layout-' + layout);
                $gridContainer.attr('data-show-layout', layout);

                // Update product card classes
                const $productCards = $gridContainer.find('.woolentor-product-card');
                $productCards.removeClass('woolentor-grid-card woolentor-list-card');

                if (layout === 'grid') {
                    if($style === 'editorial'){
                        $productCards.removeClass('woolentor-editorial-list-card');
                        $productCards.addClass('woolentor-editorial-grid-card');
                    }else if($style === 'magazine'){
                        $productCards.removeClass('woolentor-magazine-list-card');
                        $productCards.addClass('woolentor-magazine-grid-card');
                    }else{
                        $productCards.addClass('woolentor-grid-card');
                    }
                } else if (layout === 'list') {
                    if($style === 'editorial'){
                        $productCards.removeClass('woolentor-editorial-grid-card');
                        $productCards.addClass('woolentor-editorial-list-card');
                    }else if($style === 'magazine'){
                        $productCards.removeClass('woolentor-magazine-grid-card');
                        $productCards.addClass('woolentor-magazine-list-card');
                    }else{
                        $productCards.addClass('woolentor-list-card');
                    }
                }
            }
        });
    }

    /**
     * New Product Grid
     * @param {*} $scope
     * @param {*} $
     */
    var WoolentorProductGridModern = function ( $scope, $ ){
        // Selector
        let loadMoreWrapper = $scope.find('.woolentor-ajax-enabled').eq(0);
        let loadMoreButton = $scope.find('.woolentor-load-more-btn').eq(0);
        let infiniteScroll = $scope.find('.woolentor-infinite-scroll').eq(0);
        let layoutList = $scope.find('.woolentor-layout-list').eq(0);

        // LoadMore Button
        if (loadMoreButton.length > 0) {
            WooLentorLoadMore(loadMoreButton, loadMoreWrapper);
        }

        // Infinite Scroll
        if (infiniteScroll.length > 0) {
            WooLentorInfiniteScroll(infiniteScroll, loadMoreWrapper);
        }

        // Quantity selector - using event delegation to handle dynamically loaded products
        if(layoutList.length > 0){
            WooLentorQtnManager();
        }

        // View Manager
        WooLentorViewModeManager('.woolentor-product-grid-modern');

    }

    /**
     * New Product Grid - Editorial Style
     * @param {*} $scope
     * @param {*} $
     */
    var WoolentorProductGridEditorial = function ($scope, $){
        // Selector
        let loadMoreWrapper = $scope.find('.woolentor-ajax-enabled').eq(0);
        let loadMoreButton = $scope.find('.woolentor-load-more-btn').eq(0);
        let infiniteScroll = $scope.find('.woolentor-infinite-scroll').eq(0);

        // LoadMore Button
        if (loadMoreButton.length > 0) {
            WooLentorLoadMore(loadMoreButton, loadMoreWrapper);
        }

        // Infinite Scroll
        if (infiniteScroll.length > 0) {
            WooLentorInfiniteScroll(infiniteScroll, loadMoreWrapper);
        }

        // View Manager
        WooLentorViewModeManager('.woolentor-product-grid-editorial','editorial');
    }

    /**
     * New Product Grid - Magazine Style
     * @param {*} $scope
     * @param {*} $
     */
    var WoolentorProductGridMagazine = function ($scope, $){
        // Selector
        let loadMoreWrapper = $scope.find('.woolentor-ajax-enabled').eq(0);
        let loadMoreButton = $scope.find('.woolentor-load-more-btn').eq(0);
        let infiniteScroll = $scope.find('.woolentor-infinite-scroll').eq(0);

        // LoadMore Button
        if (loadMoreButton.length > 0) {
            WooLentorLoadMore(loadMoreButton, loadMoreWrapper);
        }

        // Infinite Scroll
        if (infiniteScroll.length > 0) {
            WooLentorInfiniteScroll(infiniteScroll, loadMoreWrapper);
        }

        // View Manager
        WooLentorViewModeManager('.woolentor-product-grid-magazine','magazine');
    }

    /*
    * Run this code under Elementor.
    */
    $(window).on('elementor/frontend/init', function () {

        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-product-tab.default', WidgetProductSliderHandler);
        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-product-tab.default', WidgetProducttabsHandler);

        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-universal-product.default', WidgetProductSliderHandler);
        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-universal-product.default', WidgetWoolentorTooltipHandler);
        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-universal-product.default', WidgetThumbnaisImagesHandler);

        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-cross-sell-product-custom.default', WidgetProductSliderHandler);
        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-cross-sell-product-custom.default', WidgetWoolentorTooltipHandler);
        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-cross-sell-product-custom.default', WidgetThumbnaisImagesHandler);

        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-upsell-product-custom.default', WidgetProductSliderHandler);
        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-upsell-product-custom.default', WidgetWoolentorTooltipHandler);
        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-upsell-product-custom.default', WidgetThumbnaisImagesHandler);

        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-related-product-custom.default', WidgetProductSliderHandler);
        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-related-product-custom.default', WidgetWoolentorTooltipHandler);
        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-related-product-custom.default', WidgetThumbnaisImagesHandler);

        elementorFrontend.hooks.addAction( 'frontend/element_ready/wl-product-video-gallery.default', WidgetProductVideoGallery );

        elementorFrontend.hooks.addAction( 'frontend/element_ready/wl-brand-logo.default', WidgetProductSliderHandler );
        elementorFrontend.hooks.addAction( 'frontend/element_ready/wl-faq.default', WoolentorAccordion );

        elementorFrontend.hooks.addAction( 'frontend/element_ready/wl-category-grid.default', WidgetProductSliderHandler );
        elementorFrontend.hooks.addAction( 'frontend/element_ready/wl-testimonial.default', WidgetProductSliderHandler );
        elementorFrontend.hooks.addAction( 'frontend/element_ready/wl-product-grid.default', WidgetProductSliderHandler );
        elementorFrontend.hooks.addAction( 'frontend/element_ready/wl-recently-viewed-products.default', WidgetProductSliderHandler );
        elementorFrontend.hooks.addAction( 'frontend/element_ready/wl-onepage-slider.default', WoolentorOnePageSlider );

        elementorFrontend.hooks.addAction( 'frontend/element_ready/wl-customer-veview.default', WidgetProductSliderHandler );

        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-product-grid-modern.default', WoolentorProductGridModern );
        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-product-grid-luxury.default', WoolentorProductGridModern );
        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-product-grid-editorial.default', WoolentorProductGridEditorial );
        elementorFrontend.hooks.addAction( 'frontend/element_ready/woolentor-product-grid-magazine.default', WoolentorProductGridMagazine );

    });


})(jQuery);