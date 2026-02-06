== Changelog ==
= 3.13.0 = Released on 04 September 2025

* New: support for WooCommerce 10.2
* Update: YITH plugin framework

= 3.12.0 = Released on 11 August 2025

* New: support for WooCommerce 10.1
* Fix: increase inactive cross width
* Fix: fix add to cart button selector in the frontend JS
* Fix: prevent issue in product tabs when the value of a colorpicker attribute is an image
* Fix: display custom values on product attribute tab
* Dev: filter add_to_cart_selector_loop
* Dev: filter yith_wccl_available_variations_transient
* Update: YITH plugin framework

= 3.11.0 = Released on 24 June 2025

* New: support for WooCommerce 10.0
* Update: YITH plugin framework

= 3.10.0 = Released on 13 May 2025

* New: support for WooCommerce 9.9
* Update: YITH plugin framework

= 3.9.0 = Released on 15 April 2025

* New: support for WordPress 6.8
* Update: YITH plugin framework

= 3.8.0 = Released on 25 March 2025

* New: support for WooCommerce 9.8
* Update: YITH plugin framework

= 3.7.0 = Released on 24 February 2025

* New: support for WooCommerce 9.7
* Update: YITH plugin framework

= 3.6.0 = Released on 29 January 2025

* New: support for WooCommerce 9.6
* Update: YITH plugin framework

= 3.5.0 = Released on 10 December 2024

* New: support for WooCommerce 9.5
* Update: YITH plugin framework

= 3.4.0 = Released on 13 November 2024

* New: support for WordPress 6.7
* New: support for WooCommerce 9.4
* Update: YITH plugin framework

= 3.3.0 = Released on 12 September 2024

* New: support for WooCommerce 9.3
* Update: YITH plugin framework

= 3.2.0 = Released on 20 August 2024

* New: support for WooCommerce 9.2
* Update: YITH plugin framework

= 3.1.0 = Released on 16 July 2024

* New: support for WordPress 6.6
* New: support for WooCommerce 9.1
* Update: YITH plugin framework

= 3.0.0 = Released on 26 June 2024

* New: support for WooCommerce 9.0
* New: edit attributes on cart page
* New: show specific attributes on archive pages
* Update: YITH plugin framework
* Fix: Changed CSS for the bi-color swatch in additional information of the product
* Dev: filter yith_wccl_enable_label_in_loop

= 2.11.0 = Released on 29 May 2024

* New: support for WooCommerce 8.9
* Update: YITH plugin framework

= 2.10.0 = Released on 27 March 2024

* New: support for WooCommerce 8.8
* Update: YITH plugin framework

= 2.9.0 = Released on 27 March 2024

* New: support for WooCommerce 8.7
* New: support for WordPress 6.5
* Update: YITH plugin framework
* Tweak reload product images with Woodmart theme
* Dev: added load product template action from YITH Add-ons

= 2.8.1 = Released on 21 February 2024

* Fix: undefined label when you choose a specific attribute

= 2.8.0 = Released on 20 February 2024

* New: support for WooCommerce 8.6
* Update: YITH plugin framework
* Fix: compatibility issue with Product Bundle - Variation Bundles plugin
* Dev: added data-value to variations in additional information tab

= 2.7.0 = Released on 14 January 2024

* New: support for WooCommerce 8.5
* Update: YITH plugin framework
* Dev: filter yith_wccl_wrapper_price_shop_js

= 2.6.0 = Released on 14 December 2023

* New: support for WooCommerce 8.4
* Update: YITH plugin framework
* Dev: filter yith_wccl_attribute_separator

= 2.5.1 = Released on 23 November 2023

* Fix: Show attributes when the variations are loaded via Ajax

= 2.5.0 = Released on 14 November 2023

* New: support for WooCommerce 8.3
* New: support for WordPress 6.4
* Fix create new attribute from the product section
* Fix remove attribute on init when a specific variation is not available
* Fix prevent to hide parent variations if the hide parent product option is not enabled
* Fix: support for Variation Gallery with Avada template
* Update: YITH plugin framework

= 2.4.0 = Released on 12 September 2023

* New: support for WooCommerce 8.2
* Tweak: change JS for prevent errors with windows.parent
* Update: YITH plugin framework

= 2.3.0 = Released on 12 September 2023

* New: support for WooCommerce 8.1
* Fix: avoid conflict with Gift Cards. All variations are shown although they are disabled
* Fix: get correct attribute selector name

= 2.2.0 = Released on 03 August 2023

* New: support for WooCommerce 8.0
* New: support for WordPress 6.3
* Fix: remove hidden variations from the loop
* Fix: show translated attributes on translated product with WPML
* Dev: filter yith_wccl_change_label_on_selected_attribute

* Update: YITH plugin framework
* Fix admin style problem when you create or edit a product
* Fix attribute display on variations
* Dev: removed old code for gift cards

= 2.1.0 = Released on 14 July 2023

* New: support for WooCommerce 7.9
* Update: YITH plugin framework
* Fix admin style problem when you create or edit a product
* Fix attribute display on variations
* Dev: removed old code for gift cards

= 2.0.0 = Released on 28 June 2023

* New: support for WooCommerce 7.8
* New: settings panel
* New: Attributes layout on frontend
* New: Blur and cross an attribute when variation doesn't have stock
* New: Option to set color swatches size
* New: Option to set color swatches border radius
* New: Option to set options border radius
* New: Attributes style tab on edit product page
* New: Possibility to override default term configuration on edit product page
* New: Image swatch type option for colorpickers
* New: Possibility to set a tooltip image in all attributes
* New: Image swatch type option for colorpickers.
* New: Allow to add tooltip image in all attributes.
* Dev: filter yith_wccl_require_class
* Dev: filter yith_wccl_before_save_custom_product_terms
* Dev: filter yith_wccl_product_tabs
* Dev: filter yith_wccl_image_hover_even_selected
* Dev: filter yith_wccl_get_product
* Dev: filter yith_wccl_custom_css
* Dev: filter yith_wccl_get_term_id
* Dev: filter yith_wccl_custom_attr_product
* Dev: filter yith_wccl_form_field_args
* Dev: filter yith_wccl_form_field_html
* Dev: filter yith_wccl_gel_fields_type
* Dev: action yith_wccl_attribute_save
* Dev: action yith_wccl_after_save_colorpicker_attribute
* Dev: action yith_wccl_after_save_image_attribute
* Dev: action yith_wccl_after_save_label_attribute
* Update: YITH plugin framework

= 1.36.0 = Released on 08 May 2023

* New: support for WooCommerce 7.7
* New: support for WooCommerce HPOS feature
* Update: YITH plugin framework

= 1.35.0 = Released on 11 April 2023

* New: support for WooCommerce 7.6
* Update: YITH plugin framework

= 1.34.0 = Released on 21 March 2023

* New: support for WooCommerce 7.5
* New: support for WordPress 6.2
* Tweak: Control check for prevent error if product does not exist.
* Update: YITH plugin framework

= 1.33.0 = Released on 14 February 2023

* New: support for WooCommerce 7.4
* Update: YITH plugin framework

= 1.32.0 = Released on 29 December 2022

* New: support for WooCommerce 7.3
* Update: YITH plugin framework

= 1.31.0 = Released on 15 December 2022

* New: support for WooCommerce 7.2
* Update: YITH plugin framework

== 1.30.1 Released on 11 November 2022

* Update: YITH plugin framework
* Fix: patched security vulnerability

= 1.30.0 = Released on 07 November 2022

* New: support for WooCommerce 7.1
* New: support for WordPress 6.1
* Update: YITH plugin framework

= 1.29.0 = Released on 06 October 2022

* New: support for WooCommerce 7.0
* Update: YITH plugin framework

= 1.28.0 = Released on 31 August 2022

* New: support for WooCommerce 6.9
* Update: YITH plugin framework

= 1.27.0 = Released on 09 August 2022

* New: support for WooCommerce 6.8
* Update: YITH plugin framework
* Update: language files

= 1.26.0 = Released on 12 July 2022

* New: support for WooCommerce 6.7
* Update: YITH plugin framework

= 1.25.0 = Released on 16 June 2022

* New: support for WooCommerce 6.6
* Update: YITH plugin framework

= 1.24.0 = Released on 04 May 2022

* New: support for WooCommerce 6.5
* New: support for WordPress 6.0
* Update: YITH plugin framework
* Dev : filter yith_wccl_variation_attributes

= 1.23.0 = Released on 06 April 2022

* New: support for WooCommerce 6.4
* Update: YITH plugin framework
* Fix : variation order by menu_order

= 1.22.0 = Released on 15 March 2022

* New: support for WooCommerce 6.3
* Update: YITH plugin framework

= 1.21.0 = Released on 14 February 2022

* New: support for WooCommerce 6.2
* Update: YITH plugin framework

= 1.20.0 = Released on 18 January 2022

* New: support for WooCommerce 6.1
* New: support for WordPress 5.9
* Update: YITH plugin framework

= 1.19.0 = Released on 16 December 2021

* New: support for WooCommerce 6.0
* Update: YITH plugin framework
* Fix: select2 style on frontend

= 1.18.0 = Released on 09 November 2021

* New: support for WooCommerce 5.9
* Update: YITH plugin framework
* Fix: missing attribute type for alt image

= 1.17.0 = Released on 18 October 2021

* New: support for WooCommerce 5.8
* Update: YITH plugin framework
* Fix: tooltip.replace is not a function

= 1.16.1 = Released on 27 Sep 2021

* Update: YITH plugin framework
* Fix: debug info feature removed for all logged in users

= 1.16.0 = Released on 10 Sep 2021

* New: support for WooCommerce 5.7
* New: French translation
* Update: YITH plugin framework

= 1.15.3 = Released on 10 Aug 2021

* New: support for WooCommerce 5.6
* Update: YITH plugin framework

= 1.15.2 = Released on 14 July 2021

* New: support for WooCommerce 5.5
* New: support for WordPress 5.8
* Update: YITH plugin framework
* Dev: new filter 'yith_wccl_check_for_custom_types' to show custom fields on all attributes

= 1.15.1 = Released on 08 Jun 2021

* New: support for WooCommerce 5.4
* Update: YITH plugin framework
* Fix: add to cart issue on archive pages using cyrillic attributes

= 1.15.0 = Released on 12 May 2021

* New: support for WooCommerce 5.3
* New: added German translation
* New: Astra theme compatibility
* Update: YITH plugin framework
* Update: language files

= 1.14.1 = Released on 17 April 2021

* New: support for WooCommerce 5.2
* Update: YITH plugin framework
* Fix: Clear plugin cache on attribute update
* Dev: new parameter is available for the filter 'yith_wccl_empty_option_loop_label'
* Dev: new filter 'yith_wccl_set_srcset_on_loop_image'

= 1.14.0 = Released on 10 March 2021

* New: support for WordPress 5.7
* New: support for WooCommerce 5.1
* Update: YITH plugin framework
* Fix: now is possible to set 0 as value for product attributes
* Dev: new filter 'yith_wccl_force_disable_add_to_cart'

= 1.13.0 = Released on 09 February 2021

* New: product attributes meta key handler
* Update: YITH plugin framework

= 1.12.2 = Released on 03 February 2021

* New: support for WooCommerce 5.0
* Update: YITH plugin framework
* Update: language files

= 1.12.1 = Released on 12 January 2021

* New: Support for WooCommerce 4.9
* Update: Plugin framework
* Update: Language files

= 1.12.0 = Released on 09 December 2020

* New: Support for WooCommerce 4.8
* New: Uncode theme compatibility
* Update: Plugin framework
* Update: Language files
* Fix: Compatibility issue with YITH Proteo theme
* Fix: Issue on export meta gallery using Bulk Product Editing
* Tweak: Cached variations gallery to improve loading speed

= 1.11.3 = Released on 04 November 2020

* New: Support for WordPress 5.6
* New: Support for WooCommerce 4.7
* Update: Plugin framework
* Update: Language files

= 1.11.2 = Released on 12 October 2020

* New: Support for WooCommerce 4.6
* Update: Plugin framework
* Update: Language files
* Fix: Show single variations also on WooCommerce shortcode
* Fix: Double check that variation exists before process it

= 1.11.1 = Released on 18 September 2020

* New: Support for WooCommerce 4.5
* Update: Plugin framework
* Update: Language files
* Tweak: Improved YITH WooCommerce Wishlist compatibility

= 1.11.0 = Released on 20 August 2020

* New: Support for WooCommerce 4.4.1
* New: Support for WordPress 5.5
* New: Support for Woocommerce Variations Update in Cart by makewebbetter
* Update: Plugin framework
* Update: Language files
* Fix: JavaScript error for no-latin charset
* Fix: wrong "Hide in archive pages?" variable product meta key

= 1.10.3 = Released on 01 June 2020

* New: Support for WooCommerce 4.2
* Update: Plugin framework
* Update: Language files

= 1.10.2 = Released on 24 April 2020

* New: Support for WooCommerce 4.1
* Update: Plugin framework
* Tweak: Improved Flatsome theme support

= 1.10.1 = Released on 16 March 2020

* Fix: Issue on multi colorpicker
* Fix: Prevent save empty variation gallery
* Fix: Double check image srcset attribute when changing image in archive pages

= 1.10.0 = Released on 09 March 2020

* New: Support for WooCommerce 4.0
* New: Support for WordPress 5.4
* Update: Plugin framework
* Update: Language files
* Fix: Missing woocommerce_add_to_cart_validation filter in AJAX add to cart for archive pages

= 1.9.4 = Released on 05 February 2020

* New: Support for WooCommerce 3.9.1
* New: Added option to show or hide a single product variation from archive pages
* New: Added option to hide or show variable product from loop
* Update: Plugin framework
* Update: Spanish language
* Fix: No products shown on brands archive page
* Dev: New filter 'yith_wccl_available_variations_loop'
* Tweak: Using transient on archive pages for better performance

= 1.9.3 = Released on 20 December 2019

* New: Support for WooCommerce 3.9
* New: Support for WordPress 5.3.2
* Update: Plugin framework
* Update: Dutch language
* Fix: Reset term relationship on variable attribute changes
* Fix: Gallery issue with themes that use global post instead of product
* Fix: Show variations in loop also on tax archives and attributes filter
* Fix: Single variation visibility on tax archive pages

= 1.9.2 = Released on 29 November 2019

* Fix: Multi-colorpicker issue admin side

= 1.9.1 = Released on 29 November 2019

* Update: Italian language
* Update: Notice handler
* Update: Plugin framework
* Fix: Not possible to edit manually the color attribute on single product page in admin view
* Fix: Typo css class
* Dev: New parameter for the filter 'yith_wccl_skip_form_variable_loop'
* Dev: Added jQuery trigger adding_to_cart to yith_wccl_add_cart function

= 1.9.0 = Released on 13 November 2019

* New: import/export variation galleries with WooCommerce import/export tools
* New: show single variations on WooCommerce archive pages

= 1.8.14 = Released on 05 November 2019

* New: Import gallery images for single variation using WP ALL IMPORT
* Update: Plugin framework
* Dev: new filter 'yith_wccl_custom_html_for_attributes_in_custom_tab'

= 1.8.13 = Released on 30 October 2019

* Update: Plugin framework

= 1.8.12 = Released on 25 October 2019

* New: Support for WooCommerce 3.8
* New: Support for WordPress 5.3
* Update: Plugin framework
* Fix: Compatibility issue with YITH WooCommerce Role Based Prices
* Dev: new filter 'yith_wccl_set_plugin_compatibility_selectors'

= 1.8.11 = Released on 01 August 2019

* New: Support to WooCommerce 3.7.0 RC1
* New: Support to WordPress 5.2.2
* New: New plugin admin panel style
* Update: Plugin Core

= 1.8.10 = Released on 29 May 2019

* Tweak:  get value of default language term if translated value is missing
* Update: plugin fw 3.2.1
* Fix: check on $sitepress variable
* Fix: js error missing variation on loop
* Fix: removed WP main query setup from gallery code
* Dev: new js trigger 'yith_wccl_reset_loop_form'

= 1.8.9 = Released on 03 April 2019

* New: Support to WooCommerce 3.6.0 RC1
* New: Support to WordPress 5.1
* New: Support to Eola theme (by Mikado Themes)
* Update: Plugin Core
* Update: Spanish translation
* Fix: Get parent variations gallery for WPML translated products.
* Dev: New filter "yith_wccl_use_parent_gallery_for_translated_products"

= 1.8.8 = Released on 20 February 2019

* Update: Plugin Core
* Fix: get_attribute method called on null
* Fix: Check id param before loading variation gallery

= 1.8.7 = Released on 02 February 2019

* Fix: Image changes in archive pages if variations are loaded using AJAX

= 1.8.6 = Released on 31 January 2019

* New: Support to WooCommerce 3.5.4
* Update: Plugin Core
* Update: Italian translation
* Update: Dutch translation
* Fix: Cannot redeclare function 'yith_wccl_premium_install_woocommerce_admin_notice'

= 1.8.5 = Released on 10 December 2018

* New: Support to WooCommerce 3.5.2
* New: Support to WordPress 5.0.0
* Update: Plugin core

= 1.8.4 = Released on 29 November 2018

* Update: Plugin core
* Fix: JavaScript error if attribute description is missing
* Tweak: Use transients to speed up loading time in archive pages
* Dev: New filter "yith_wccl_enable_handle_variation_gallery" to enable/disable variation gallery

= 1.8.3 = Released on 31 October 2018

* Update: Dutch translation
* Fix: Removed outdated plugin transient
* Fix: Product attributes disappear on save

= 1.8.2 = Released on 25 October 2018

* New: Support to WooCommerce 3.5.0
* Update: Plugin Core
* Update Languages files
* Fix: Error can't use function return value in write context
* Fix: Image size for variations image in archive pages

= 1.8.1 = Released on 02 October 2018

* Fix: JavaScript error for custom product attributes

= 1.8.0 = Released on 27 September 2018

* New: Support to WooCommerce 3.4.5
* New: Support to WordPress 4.9.8
* New: Add different images gallery per variation
* Update: Spanish language
* Update: Plugin Core
* Fix: Compatibility with Flatsome Infinite Scrolling
* Dev: Refactoring plugin frontend javascrip

= 1.7.0 = Released on 17 May 2018

* New: Support to WooCommerce 3.4 RC1
* New: Support to WordPress 4.9.6 RC2
* Update: Italian language
* Update: Dutch language
* Update: Plugin core
* Fix: Product image doesn't change on select variation if "Change product image on hover" is enabled
* Dev: New filter 'yith_wccl_image_selector' to filter product image selectors
* Dev: Added arguments to the filter yith_wccl_html_form_in_loop

= 1.6.0 = Released on 06 February 2018

* New: Support to WooCommerce 3.3.0
* New: Support to WordPress 4.9.3
* New: Compatibility with YITH WooCommerce Request a Quote Premium
* Update: Plugin core
* Update: Language files
* Dev: New filter 'yith_wccl_empty_option_loop_label'

= 1.5.0 = Released on 10 October 2017

* New: Support to WooCommerce 3.2.0 RC2
* Update: Plugin core
* Tweak: Use of transients to increase plugin speed and performance
* Dev: Added filter yith_wccl_skip_form_variable_loop to skip adding the variation form in the loop for specific products or under specific conditions

= 1.4.0 = Released on 11 September 2017

* New: Support to WooCommerce 3.1.2
* New: Support to WordPress 4.8.1
* New: Compatibility with Flatsome Quick View
* New: Compatibility with WooCommerce Ajax Filter
* New: Dutch translate ( thanks to Boaz van der Zeep )
* New: Option to enable AJAX handle for variations form in archive shop pages
* Update: Plugin core
* Update: Languages file
* Fix: "Add New Attribute" popup not showing in post-new.php pages
* Fix: Compatibility issue with WP version older then 4.5
* Fix: Closing key tag missing for wpml-config.xml

= 1.3.0 = Released on 13 March 2017

* New: Support to WooCommerce 2.7.0 RC1
* New: Support Ajax variations on single product page
* Update: Plugin core
* Fix: YITH Infinite Scrolling compatibility issue
* Dev: Add filter yith_wccl_create_custom_attributes_term_attr

= 1.2.1 = Released on 17 November 2016

* New: Support to WP-Rocket LazyLoad
* Fix: Notice undefined index on class YITH_WCCL_Frontend

= 1.2.0 = Released on 06 October 2016

* New: Change product image on hover (only for one attirbute)
* New: Option to show custom attributes style also on "Additional Information" Tab
* New: Compatibility with WooCommerce Products Filter
* New: Compatibility with YITH Composite Products For WooCommerce
* Update: Language files.
* Update: Core plugin.
* Fix: Reset attribute type on plugin deactivation.

= 1.1.0 = Released on 25 July 2016

* New: Spanish translation
* New: Compatibility with WooCommerce Quick View by WooThemes
* Update: Language files
* Update: Core plugin
* Fix: Description and default variations on archive pages

= 1.0.9 = Released on 07 June 2016

* New: Italian translation
* Update: Language files
* Update: Core plugin
* Fix: Default variation on single product pages for products with only one attribute

= 1.0.8 = Released on 23 May 2016

* New: Compatibility with YITH WooCommerce Added to Cart Popup
* New: Set dual color such as blue-white (half box blue and half box white)
* New: Show a preview of the attribute image in the tooltip (available only for image attributes)
* New: Support to WordPress 4.5.2
* New: Support to WooCommerce 2.6 Beta2
* Update: Updated textdomain from yith-wccl to yith-woocommerce-color-label-variations
* Update: Language files
* Update: Core plugin
* Fix: Variations now work with Owl Carousel 2 when infinite loop option is set
* Fix: Clicking on selected attribute before selecting another one is no longer necessary

= 1.0.7 = Released on 14 December 2015

* New: Compatibility with WooThumbs Awesome Product Imagery plugin
* New: Compatibility with YITH WooCommerce Gift Card

= 1.0.6 = Released on 09 December 2015

* New: Compatibility with WooCommerce Thumbnail Input Quantities plugin
* New: Compatibility with Wordpress 4.4
* Update: Plugin Core
* Fix: Change product image in loop when variation is selected

= 1.0.5 = Released on 18 September 2015

* Fix: Add to cart variation out-of-stock in shop page

= 1.0.4 = Released on 17 September 2015

* New: Blur effect for product attributes. Activate it on plugin settings page
* New: Compatibility with YITH Infinite Scrolling
* New: Out of stock label in shop if selected variation is out of stock
* New: ITA Translation
* Update: Core plugin
* Fix: Default variation on shop page
* Fix: Replace fragments after add to cart action

= 1.0.3 = Released on 12 August 2015

* New: Compatibility with WooCommerce 2.4
* New: WP 4.2.4 compatibility
* New: Option for choose the form position in archive shop page
* Update: Core plugin
* Fix: Multiple view cart on shop page

= 1.0.2 = Released on 26 June 2015

* New: Ajax Navigation compatibility
* Fix: Minor bugs

= 1.0.1 = Released on 23 June 2015

* Update: plugin core
* Fix: minor bugs
* Fix: js error

= 1.0.0 =

* Initial release
