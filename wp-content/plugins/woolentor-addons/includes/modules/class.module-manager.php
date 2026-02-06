<?php  
use WooLentor\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woolentor_Module_Manager{
    use Singleton;

    /**
     * Constructor
     */
    public function __construct(){
        if( is_admin() ){
            $this->include_under_admin();
        }

        $this->module_manager();
    }

    /**
     * [include_under_admin] Nessary File Required if admin page.
     * @return [void]
     */
    public function include_under_admin(){

        // Post Duplicator
        if( !is_plugin_active('ht-mega-for-elementor/htmega_addons_elementor.php') ){
            if( woolentor_get_option( 'postduplicator', 'woolentor_others_tabs', 'off' ) === 'on' ){
                require_once ( WOOLENTOR_ADDONS_PL_PATH.'includes/modules/post-duplicator/class.post-duplicator.php' );
            }
        }

    }

    /**
     * Module Manager
     * @return void
     */
    public function module_manager(){
        $module_list = $this->module_list();

        foreach($module_list as $module_key => $module){

            $is_enable   = woolentor_get_option( $module['option']['key'], $module['option']['section'], $module['option']['default'] ) == 'on';
            $module_path = ($module['is_pro'] == true) ? WOOLENTOR_ADDONS_PL_PATH_PRO : WOOLENTOR_ADDONS_PL_PATH;
            $module_file = $module_path.'includes/modules/'.$module['slug'].'/'.$module_key.'.php';

            // Check Plugin If Already Activated Then Module Should Not Load Again
            if( !empty( $module['check_exist_class'] ) && class_exists( $module['check_exist_class'] ) ){
                continue;
            }

            if( $module['manage_setting'] && file_exists($module_file) ){
                require_once $module_file;
                if( method_exists($module['main_class'], 'instance')){
                    $module['main_class']::instance( $is_enable );
                }else{
                    if( method_exists($module['main_class'],'get_instance')){
                        $module['main_class']::get_instance( $is_enable );
                    }
                }
            }else{
                if( $is_enable && file_exists($module_file)){
                    require_once $module_file;
                }else{
                    /**
                     * @todo Need to delete in future.
                     */
                    if( $is_enable && $module_key === 'size-chart' ){
                        require_once $module_path.'includes/modules/'.$module['slug'].'/class.size-chart.php';
                    }
                }
            }
            
        }

    }

    /**
     * Free Module List
     */
    private function module_list(){

        $module_list = [
            'rename_label' => [
                'slug'   =>'rename-label',
                'title'  => esc_html('Rename Label'),
                'option' => [
                    'key'     => 'enablerenamelabel',
                    'section' => 'woolentor_rename_label_tabs',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\RenameLabel\Rename_label',
                'is_pro'     => false,
                'manage_setting' => true
            ],
            'ajax-search' => [
                'slug'   =>'ajax-search',
                'title'  => esc_html('AJAX Search Widget'),
                'option' => [
                    'key'     => 'ajaxsearch',
                    'section' => 'woolentor_others_tabs',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => false,
                'manage_setting' => false
            ],
            'sales-notification' => [
                'slug'   =>'sales-notification',
                'title'  => esc_html('Sales Notification'),
                'option' => [
                    'key'     => 'enableresalenotification',
                    'section' => 'woolentor_sales_notification_tabs',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => false,
                'manage_setting' => false
            ],
            'ajax_add_to_cart' => [
                'slug'   =>'single-product-ajax-add-to-cart',
                'title'  => esc_html('Single Product AJAX Add To Cart'),
                'option' => [
                    'key'     => 'ajaxcart_singleproduct',
                    'section' => 'woolentor_others_tabs',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => false,
                'manage_setting' => false
            ],
            'wishlist' => [
                'slug'   =>'wishlist',
                'title'  => esc_html('Wishlist'),
                'option' => [
                    'key'     => 'wishlist',
                    'section' => 'woolentor_others_tabs',
                    'default' => 'off'
                ],
                'main_class' => 'Woolentor_WishSuite_Base',
                'check_exist_class' => 'WishSuite_Base',
                'is_pro'     => false,
                'manage_setting' => true
            ],
            'compare' => [
                'slug'   =>'compare',
                'title'  => esc_html('Compare'),
                'option' => [
                    'key'     => 'compare',
                    'section' => 'woolentor_others_tabs',
                    'default' => 'off'
                ],
                'main_class' => 'Woolentor_Ever_Compare',
                'check_exist_class' => 'Ever_Compare',
                'is_pro'     => false,
                'manage_setting' => true
            ],
            'shopify-like-checkout' => [
                'slug'   =>'shopify-like-checkout',
                'title'  => esc_html('Shopify Style Checkout'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_shopify_checkout_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => false,
                'manage_setting' => false
            ],
            'variation-swatch' => [
                'slug'   =>'variation-swatch',
                'title'  => esc_html('Variation Swatches'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_swatch_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => false,
                'manage_setting' => false
            ],
            'popup-builder' => [
                'slug'   =>'popup-builder',
                'title'  => esc_html('Popup Builder'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_popup_builder_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => false,
                'manage_setting' => false
            ],
            'flash-sale' => [
                'slug'   =>'flash-sale',
                'title'  => esc_html('Flash Sale Countdown'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_flash_sale_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => false,
                'manage_setting' => false
            ],
            'backorder' => [
                'slug'   =>'backorder',
                'title'  => esc_html('Backorder'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_backorder_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => false,
                'manage_setting' => false
            ],
            'quickview' => [
                'slug'   =>'quickview',
                'title'  => esc_html('Quick View'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_quickview_settings',
                    'default' => 'on'
                ],
                'main_class' => '\Woolentor\Modules\QuickView\Quick_View',
                'is_pro'     => false,
                'manage_setting' => true
            ],
            'currency-switcher' => [
                'slug'   =>'currency-switcher',
                'title'  => esc_html('Currency Switcher'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_currency_switcher',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\CurrencySwitcher\Currency_Switcher',
                'is_pro'     => false,
                'manage_setting' => true
            ],
            'badges' => [
                'slug'   =>'badges',
                'title'  => esc_html('Product Badges'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_badges_settings',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\Badges\Product_Badges',
                'is_pro'     => false,
                'manage_setting' => true
            ],
            'advanced-coupon' => [
                'slug'   =>'advanced-coupon',
                'title'  => esc_html('Advanced Coupon'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_advanced_coupon_settings',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\AdvancedCoupon\Advanced_Coupon',
                'is_pro'     => false,
                'manage_setting' => true
            ],
            'cart-reserve-time' => [
                'slug'   =>'cart-reserve-time',
                'title'  => esc_html('Cart Reserved Timer'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_cart_reserve_timer_settings',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\CartReserveTime\Cart_Reserve_Time',
                'is_pro'     => false,
                'manage_setting' => true
            ],
            'sales-report-email' => [
                'slug'   =>'sales-report-email',
                'title'  => esc_html('Sales Report Email'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_email_reports_settings',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\EmailReports\Sales_Email_Reports',
                'is_pro'     => false,
                'manage_setting' => true
            ],
            'smart-cross-sell-popup' => [
                'slug'   =>'smart-cross-sell-popup',
                'title'  => esc_html('Smart Cross-sell Popup'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_smart_cross_sell_popup_settings',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\Smart_Cross_Sell_Popup\Smart_Cross_Sell_Popup',
                'is_pro'     => false,
                'manage_setting' => true
            ],
            'store-vacation' => [
                'slug'   =>'store-vacation',
                'title'  => esc_html('Store Vacation'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_store_vacation_settings',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\StoreVacation\Store_Vacation',
                'is_pro'     => false,
                'manage_setting' => true
            ],

            'abandoned-cart' => [
                'slug'   =>'abandoned-cart',
                'title'  => esc_html('Abandoned Cart'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_abandoned_cart_settings',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\AbandonedCart\Abandoned_Cart',
                'is_pro'     => false,
                'manage_setting' => true
            ]

        ];

        $module_list = apply_filters('woolentor_module_list', $module_list);
        $final_module_list = $module_list;

        // Support For Previous version
        if( is_plugin_active('woolentor-addons-pro/woolentor_addons_pro.php') && defined( "WOOLENTOR_VERSION_PRO" ) ){
            if ( version_compare( WOOLENTOR_VERSION_PRO, '2.5.1', '<=' ) ) {
                $final_module_list = array_merge($module_list, $this->pro_module_list());
            }
        }

        return $final_module_list;

    }

    /**
     * Pro Module List
     * @return mixed
     */
    private function pro_module_list(){
        $pro_module_list = [
            
            'partial-payment' => [
                'slug'   =>'partial-payment',
                'title'  => esc_html('Partial Payment'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_partial_payment_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'pre-orders' => [
                'slug'   =>'pre-orders',
                'title'  => esc_html('Pre Orders'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_pre_order_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'gtm-conversion-tracking' => [
                'slug'   =>'gtm-conversion-tracking',
                'title'  => esc_html('GTM Conversion Tracking'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_gtm_convertion_tracking_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'size-chart' => [
                'slug'   =>'size-chart',
                'title'  => esc_html('Size Chart'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_size_chart_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'email-customizer' => [
                'slug'   =>'email-customizer',
                'title'  => esc_html('Email Customizer'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_email_customizer_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'email-automation' => [
                'slug'   =>'email-automation',
                'title'  => esc_html('Email Automation'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_email_automation_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'order-bump' => [
                'slug'   =>'order-bump',
                'title'  => esc_html('Order Bump'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_order_bump_settings',
                    'default' => 'off'
                ],
                'main_class' => '',
                'is_pro'     => true,
                'manage_setting' => false
            ],
            'product-filter' => [
                'slug'   =>'product-filter',
                'title'  => esc_html('Product Filter'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_product_filter_settings',
                    'default' => 'off'
                ],
                'main_class' => 'Woolentor_Product_Filter',
                'is_pro'     => true,
                'manage_setting' => true
            ],
            'side-mini-cart' => [
                'slug'   =>'side-mini-cart',
                'title'  => esc_html('Side Mini Cart'),
                'option' => [
                    'key'     => 'mini_side_cart',
                    'section' => 'woolentor_others_tabs',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\SideMiniCart\Side_Mini_Cart',
                'is_pro'     => true,
                'manage_setting' => true
            ],
            'quick-checkout' => [
                'slug'   =>'quick-checkout',
                'title'  => esc_html('Quick Checkout'),
                'option' => [
                    'key'     => 'enable',
                    'section' => 'woolentor_quick_checkout_settings',
                    'default' => 'off'
                ],
                'main_class' => '\Woolentor\Modules\QuickCheckout\Quick_Checkout',
                'is_pro'     => true,
                'manage_setting' => true
            ]

        ];

        return apply_filters('woolentor_pro_module_list', $pro_module_list);
    }

    /**
     * [deactivate] Deactivated
     * Uses : $this->deactivate( 'ever-compare/ever-compare.php' );
     * @return [void]
     */
    public function deactivate( $slug ){
        if( is_plugin_active( $slug ) ){
            return deactivate_plugins( $slug );
        }
    }


}

Woolentor_Module_Manager::instance();