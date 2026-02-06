<?php

namespace ADP\BaseVersion\Includes;

use ADP\BaseVersion\Includes\AdminExtensions\AdminNotice;
use ADP\BaseVersion\Includes\AdminExtensions\AdminPage;
use ADP\BaseVersion\Includes\Database\Database;

defined('ABSPATH') or exit;

class PluginActions
{
    /**
     * @var string
     */
    protected $pluginFileFullPath;

    /**
     * @param string|null $pluginFileFullPath
     */
    public function __construct($pluginFileFullPath)
    {
        $this->pluginFileFullPath = $pluginFileFullPath;
    }

    /**
     *  Only a static class method or function can be used in an uninstall hook.
     */
    public function register()
    {
        if ($this->pluginFileFullPath && file_exists($this->pluginFileFullPath)) {
            register_activation_hook($this->pluginFileFullPath, array($this, 'install'));
            register_deactivation_hook($this->pluginFileFullPath, array($this, 'deactivate'));

            add_filter(
                'plugin_action_links_' . plugin_basename(WC_ADP_PLUGIN_PATH . WC_ADP_PLUGIN_FILE),
                array($this, 'pluginActionLinks')
            );
        }
    }

    public function pluginActionLinks($actions)
    {
        $actionsList = [
            [
                'https://algolplus.freshdesk.com/support/tickets/new',
                __("Contact support", 'advanced-dynamic-pricing-for-woocommerce'),
                __('Support', 'advanced-dynamic-pricing-for-woocommerce')
            ],
            [
                'https://docs.algolplus.com/category/algol_pricing/',
                __('Plugin documentation', 'advanced-dynamic-pricing-for-woocommerce'),
                __('Docs', 'advanced-dynamic-pricing-for-woocommerce')
            ],
            [
                admin_url('admin.php?page=' . AdminPage::SLUG),
                __('Change the plugin settings', 'advanced-dynamic-pricing-for-woocommerce'),
                __('Settings', 'advanced-dynamic-pricing-for-woocommerce')
            ],
        ];

        foreach ($actionsList as $action) {
            array_unshift($actions,
                sprintf(
                    '<a target="_blank" href=%s title="%s">%s</a>',
                    $action[0], $action[1], $action[2]
                )
            );
        }

        if (!defined('WC_ADP_PRO_VERSION_PATH')) {
            $goToProLink = sprintf(
                '<a target="_blank" style="font-weight: bold" href=%s title="%s">%s</a>',
                'https://algolplus.com/plugins/downloads/advanced-dynamic-pricing-woocommerce-pro/',
                __('Upgrade to Advanced Dynamic Pricing Pro', 'advanced-dynamic-pricing-for-woocommerce'),
                __('Go to Pro', 'advanced-dynamic-pricing-for-woocommerce')
            );

            $actions[] = $goToProLink;
        }

        return $actions;
    }

    public function singleInstall()
    {
        Database::createDatabase();
        do_action('wdp_install');
    }

    /** @param boolean $networkWide */
    public function install($networkWide)
    {
        global $wpdb;

        if (is_multisite() && $networkWide) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                $this->singleInstall();
                restore_current_blog();
            }
        } else {
            $this->singleInstall();
        }
    }

    public function deactivate()
    {
        AdminNotice::cleanUp();
    }

    /**
     * Method required for tests
     */
    public function uninstall()
    {
        $file = WC_ADP_PLUGIN_PATH . 'uninstall.php';
        if (file_exists($file)) {
            include_once $file;
        }
    }
}
