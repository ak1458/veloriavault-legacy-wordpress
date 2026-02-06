<?php

namespace ADP\BaseVersion\Includes\Core\Cart;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Database\Repository\OrderRepository;
use ADP\BaseVersion\Includes\Database\Repository\OrderRepositoryInterface;
use ADP\BaseVersion\Includes\WC\WcCustomerSessionFacade;
use ADP\Factory;

defined('ABSPATH') or exit;

class CartContext
{
    /**
     * @var CartCustomer
     */
    protected $customer;

    /**
     * @var array
     */
    private $environment;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var WcCustomerSessionFacade
     */
    protected $sessionFacade;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param CartCustomer $customer
     * @param null $deprecated
     */
    public function __construct(CartCustomer $customer, $deprecated = null)
    {
        $this->customer        = $customer;
        $this->context         = adp_context();
        $this->orderRepository = new OrderRepository();

        /** @var WcCustomerSessionFacade $wcSessionFacade */
        $this->sessionFacade = Factory::get("WC_WcCustomerSessionFacade", null);

        $this->environment = array(
            'timestamp'           => current_time('timestamp'),
            'prices_includes_tax' => $this->context->getIsPricesIncludeTax(),
            'tab_enabled'         => $this->context->getIsTaxEnabled(),
            'tax_display_shop'    => $this->context->getTaxDisplayShopMode(),
        );
    }

    public function withContext(Context $context)
    {
        $this->context = $context;

        $this->environment = array(
            'timestamp'           => $this->environment['timestamp'] ?? current_time('timestamp'),
            'prices_includes_tax' => $this->context->getIsPricesIncludeTax(),
            'tab_enabled'         => $this->context->getIsTaxEnabled(),
            'tax_display_shop'    => $this->context->getTaxDisplayShopMode(),
        );
    }

    public function withOrderRepository(OrderRepositoryInterface $orderRepository) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param string $format
     *
     * @return string
     */
    public function datetime($format)
    {
        return gmdate($format, $this->environment['timestamp']);
    }

    /**
     * @return Context
     */
    public function getGlobalContext(): Context
    {
        return $this->context;
    }

    /**
     * @return CartCustomer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @return int
     */
    public function time()
    {
        return $this->environment['timestamp'];
    }

    public function getPriceMode()
    {
        return $this->getOption('discount_for_onsale');
    }

    public function isCombineMultipleDiscounts()
    {
        return $this->getOption('combine_discounts');
    }

    public function isCombineMultipleFees()
    {
        return $this->getOption('combine_fees');
    }

    public function getCustomerId()
    {
        return $this->customer->getId();
    }

    public function getCountOfRuleUsages($ruleId)
    {
        return $this->orderRepository->getCountOfRuleUsages($ruleId);
    }

    public function getCountOfRuleUsagesPerCustomer($ruleId, $customerId)
    {
        return $this->orderRepository->getCountOfRuleUsagesPerCustomer($ruleId, $customerId);
    }

    public function getCountOfRuleUsagesPerCustomerData($ruleId) {
        $data = array();
        //phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (isset($_POST['post_data'])) {
            //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
            parse_str(wp_unslash($_POST['post_data']), $postData);
            $data['customer_email'] = $postData['billing_email'];
            $data['customer_first_name'] = $postData['billing_first_name'];
            $data['customer_last_name'] = $postData['billing_last_name'];
            $data['customer_address_1'] = $postData['billing_address_1'];
            $data['customer_address_2'] = $postData['billing_address_2'];
            $data['customer_city'] = $postData['billing_city'];
            $data['customer_state'] = $postData['billing_state'];
            $data['customer_zip'] = $postData['billing_postcode'];
        } elseif( WC()->cart ) { // loaded when WC cart exists?
            $wcCustomer = WC()->cart->get_customer();
            $data['customer_email'] = $wcCustomer->get_billing_email();
            $data['customer_first_name'] = $wcCustomer->get_billing_first_name();
            $data['customer_last_name'] = $wcCustomer->get_billing_last_name();
            $data['customer_address_1'] = $wcCustomer->get_billing_address_1();
            $data['customer_address_2'] = $wcCustomer->get_billing_address_2();
            $data['customer_city'] = $wcCustomer->get_billing_city();
            $data['customer_state'] = $wcCustomer->get_billing_state();
            $data['customer_zip'] = $wcCustomer->get_shipping_postcode();
        }

        if (!empty($data['customer_email']) ||
            ($data['customer_first_name'] &&
                $data['customer_last_name'] &&
                $data['customer_address_1'] &&
                $data['customer_city'] &&
                $data['customer_state'] &&
                $data['customer_zip'])) {
            return $this->orderRepository->getCountOfRuleUsagesPerCustomerData($ruleId, $data);
        } else {
            return 0;
        }
    }

    public function isTaxEnabled()
    {
        return isset($this->environment['tab_enabled']) ? $this->environment['tab_enabled'] : false;
    }

    public function isPricesIncludesTax()
    {
        return isset($this->environment['prices_includes_tax']) ? $this->environment['prices_includes_tax'] : false;
    }

    public function getTaxDisplayShop()
    {
        return isset($this->environment['tax_display_shop']) ? $this->environment['tax_display_shop'] : '';
    }

    public function getOption($key, $default = false)
    {
        return $this->context->getOption($key);
    }

    /**
     * @param WcCustomerSessionFacade $sessionFacade
     */
    public function withSession($sessionFacade)
    {
        if ($sessionFacade instanceof WcCustomerSessionFacade) {
            $this->sessionFacade = $sessionFacade;
        }
    }

    /**
     * @return WcCustomerSessionFacade
     */
    public function getSession()
    {
        return $this->sessionFacade;
    }
}
