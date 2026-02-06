<?php

namespace ADP\BaseVersion\Includes;

use ADP\BaseVersion\Includes\Advertising\DiscountMessage;
use ADP\BaseVersion\Includes\Compatibility\AnyFeedsCmp;
use ADP\BaseVersion\Includes\CustomizerExtensions\CustomizerExtensions;
use ADP\BaseVersion\Includes\Database\Repository\OrderRepository;
use ADP\BaseVersion\Includes\LoadStrategies\AdminAjax;
use ADP\BaseVersion\Includes\LoadStrategies\AdminCommon;
use ADP\BaseVersion\Includes\LoadStrategies\ClientCommon;
use ADP\BaseVersion\Includes\LoadStrategies\CustomizePreview;
use ADP\BaseVersion\Includes\LoadStrategies\LoadStrategy;
use ADP\BaseVersion\Includes\LoadStrategies\PhpUnit;
use ADP\BaseVersion\Includes\LoadStrategies\RestApi;
use ADP\BaseVersion\Includes\LoadStrategies\WpCron;
use ADP\BaseVersion\Includes\ProductExtensions\ProductExtensions;
use ADP\Factory;

defined('ABSPATH') or exit;

class Loader
{
    public function __construct()
    {
        $this->define();
        add_action('init', array($this, 'initPlugin'));
        add_action( 'before_woocommerce_init', function() {
            if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables',
                    WC_ADP_PLUGIN_PATH . WC_ADP_PLUGIN_FILE, true );

                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks',
                    WC_ADP_PLUGIN_PATH . WC_ADP_PLUGIN_FILE, true );
            }
        } );
    }

    protected function define()
    {
        define('WC_ADP_PLUGIN_TEMPLATES_PATH', WC_ADP_PLUGIN_PATH . 'BaseVersion/templates/');
        define('WC_ADP_PLUGIN_VIEWS_PATH', WC_ADP_PLUGIN_PATH . 'BaseVersion/views/');
    }

    public function initPlugin()
    {
        // must load langs ASAP
        // as Wordpress loads default transations on first usage __()
        $this->loadTextDomain();

        if ( ! $this->checkRequirements()) {
            return;
        }

        $context = adp_context(); // do not remove! Required for correct initialization
        $this->load($context);
    }

    protected function loadTextDomain()
    {
        // do nothing if loaded free version , it reads translations from Wordpress.org
        if(!defined('WC_ADP_PRO_VERSION_PATH'))
            return;

        $pricingDomain = 'advanced-dynamic-pricing-for-woocommerce';
        add_filter('load_textdomain_mofile', function ($moFile, $domain) use ($pricingDomain) {
            if ($domain !== $pricingDomain) {
                return $moFile;
            }
            $path = WP_PLUGIN_DIR . '/' . trim(basename(dirname(dirname(dirname(__FILE__)))) . '/languages/', '/');
            $plugin_file = $path . '/' . substr($moFile, strrpos($moFile, '/') + 1);
            return file_exists($plugin_file) ? $plugin_file : $moFile;
        }, 10, 2);
        load_plugin_textdomain($pricingDomain, false, basename(dirname(dirname(dirname(__FILE__)))) . '/languages/');
    }

    /**
     * @param Context $context
     */
    protected function load($context)
    {
        add_filter( 'woocommerce_hidden_order_itemmeta', function ( $keys ) {
            $keys[] = '_wdp_initial_cost';
            $keys[] = '_wdp_initial_tax';
            $keys[] = '_wdp_rules';
            $keys[] = '_wdp_free_shipping';
            $keys[] = '_wdp_cart_item_key';

            return $keys;
        }, 10, 1 );

        $strategy = $this->selectLoadStrategy($context);
        $strategy->start();

        /**
         * @var CustomizerExtensions $customizer
         * @var DiscountMessage $discountMessage
         */
        $customizer      = Factory::get("CustomizerExtensions_CustomizerExtensions");
        $discountMessage = Factory::get("Advertising_DiscountMessage", $customizer);

        $discountMessage->setThemeOptionsEmail($customizer);
        $discountMessage->setThemeOptionsEditOrder($customizer);

        $productExtensions = new ProductExtensions();
        $productExtensions->replaceWcProductFactory();

        $anyFeedsCmp = new AnyFeedsCmp();
        if ($anyFeedsCmp->isActive()) {
            $anyFeedsCmp->updateContext(adp_context());
        }

        wp_enqueue_style('wdp_cart-summary', WC_ADP_PLUGIN_URL . "/BaseVersion/" . 'assets/css/cart-summary.css', array(), WC_ADP_VERSION);
    }

    public function checkRequirements()
    {
        $state = true;
        if (version_compare(phpversion(), WC_ADP_MIN_PHP_VERSION, '<')) {
            add_action('admin_notices', function () {
                /* translators: A message about the need for a specific php version*/
                echo '<div class="notice notice-error is-dismissible"><p>' . sprintf(esc_html__('Advanced Dynamic Pricing for WooCommerce requires PHP version %s or later.',
                        'advanced-dynamic-pricing-for-woocommerce'), esc_html(WC_ADP_MIN_PHP_VERSION)) . '</p></div>';
            });
            $state = false;
        } elseif ( ! class_exists('WooCommerce')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Advanced Dynamic Pricing for WooCommerce requires active WooCommerce!',
                        'advanced-dynamic-pricing-for-woocommerce') . '</p></div>';
            });
            $state = false;
        } elseif (version_compare(WC_VERSION, WC_ADP_MIN_WC_VERSION, '<')) {
            add_action('admin_notices', function () {
                /* translators: A message about the need for a specific php version*/
                echo '<div class="notice notice-error is-dismissible"><p>' . sprintf(esc_html__('Advanced Dynamic Pricing for WooCommerce requires WooCommerce version %s or later.',
                        'advanced-dynamic-pricing-for-woocommerce'), esc_html(WC_ADP_MIN_WC_VERSION)) . '</p></div>';
            });
            $state = false;
        }

        return $state;
    }

    /**
     * @param Context $context
     *
     * @return LoadStrategy
     */
    protected function selectLoadStrategy($context)
    {
        if ($context->is($context::CUSTOMIZER)) {
            $strategy = Factory::get("LoadStrategies_CustomizePreview");
            /** @var $strategy CustomizePreview */
        } elseif ($context->is($context::WP_CRON)) {
            $strategy = Factory::get("LoadStrategies_WpCron");
            /** @var $strategy WpCron */
        } elseif ($context->is($context::REST_API)) {
            $strategy = Factory::get("LoadStrategies_RestApi");
            /** @var $strategy RestApi */
        } elseif ($context->is($context::AJAX)) {
            $strategy = Factory::get("LoadStrategies_AdminAjax");
            /** @var $strategy AdminAjax */
        } elseif ($context->is($context::ADMIN)) {
            $strategy = Factory::get("LoadStrategies_AdminCommon");
            /** @var $strategy AdminCommon */
        } elseif ($context->is($context::PHPUNIT)) {
            $strategy = Factory::get("LoadStrategies_PhpUnit");
            /** @var $strategy PhpUnit */
        } else {
            $strategy = Factory::get("LoadStrategies_ClientCommon");
            /** @var $strategy ClientCommon */
        }

        return $strategy;
    }
}
