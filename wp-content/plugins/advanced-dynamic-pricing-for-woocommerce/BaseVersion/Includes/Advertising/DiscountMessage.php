<?php

namespace ADP\BaseVersion\Includes\Advertising;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\Coupon\CouponCart;
use ADP\BaseVersion\Includes\CustomizerExtensions\CustomizerExtensions;
use ADP\BaseVersion\Includes\Database\Repository\OrderRepository;
use ADP\BaseVersion\Includes\Database\Repository\OrderRepositoryInterface;
use ADP\BaseVersion\Includes\TemplateLoader;
use ADP\BaseVersion\Includes\WC\WcAdpMergedCouponHelper;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;
use ADP\BaseVersion\Includes\WC\WcCustomerSessionFacade;

defined('ABSPATH') or exit;

class DiscountMessage
{
    const PANEL_KEY = 'discount_message';

    const CONTEXT_CART = 'cart';
    const CONTEXT_BLOCK_CART = 'block-cart';
    const CONTEXT_MINI_CART = 'mini-cart';
    const CONTEXT_CHECKOUT = 'checkout';
    const CONTEXT_BLOCK_CHECKOUT = 'block-checkout';
    const CONTEXT_EDIT_ORDER = 'edit-order';

    protected $amountSavedLabel;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var CustomizerExtensions
     */
    protected $customizer;

    /**
     * @param CustomizerExtensions $customizer
     */
    public function __construct($customizer)
    {
        $this->context          = adp_context();
        $this->orderRepository  = new OrderRepository();
        $this->amountSavedLabel = __("Amount Saved", 'advanced-dynamic-pricing-for-woocommerce');
        $this->customizer       = $customizer;
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function withOrderRepository(OrderRepositoryInterface $repository)
    {
        $this->orderRepository = $repository;
    }

    /**
     * @param CustomizerExtensions $customizer
     */
    public function setThemeOptionsEmail($customizer)
    {
        return;
    }

    /**
     * @param CustomizerExtensions $customizer
     */
    public function setThemeOptionsEditOrder($customizer)
    {
        // wait until filling get_theme_mod()
        add_action('wp_loaded', function () use ($customizer) {
            $contexts = array(
                self::CONTEXT_EDIT_ORDER => array($this, 'outputEditOrderAmountSaved'),
            );

            $this->installMessageHooks($customizer, $contexts);
        });
    }

    /**
     * @param CustomizerExtensions $customizer
     */
    public function setThemeOptions($customizer)
    {
        // wait until filling get_theme_mod()
        add_action('wp_loaded', function () use ($customizer) {
            $contexts = array(
                self::CONTEXT_CART      => array($this, 'outputCartAmountSaved'),
                self::CONTEXT_BLOCK_CART => array($this, 'outputBlockCartAmountSaved'),
                self::CONTEXT_MINI_CART => array($this, 'outputMiniCartAmountSaved'),
                self::CONTEXT_CHECKOUT  => array($this, 'outputCheckoutAmountSaved'),
                self::CONTEXT_BLOCK_CHECKOUT => array($this, 'outputBlockCheckoutAmountSaved')
            );

            $this->installMessageHooks($customizer, $contexts);
        });
    }

    /**
     * @param CustomizerExtensions $customizer
     * @param array $contexts
     *
     */
    protected function installMessageHooks($customizer, $contexts)
    {
        $themeOptions = $customizer->getThemeOptions()->advertisingThemeProperties;

        if ( $amountSavedLabel = $themeOptions->global->amountSavedLabel ) {
            //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
            $this->amountSavedLabel = _x( $amountSavedLabel,
                "theme option 'amount saved label",
                'advanced-dynamic-pricing-for-woocommerce'
            );
        }

        $shortcodeBlockCartHooksMapping = array(
            'woocommerce_cart_totals_before_shipping' => 'render_block_woocommerce/cart-order-summary-subtotal-block',
            'woocommerce_cart_totals_before_order_total' => 'render_block_woocommerce/cart-order-summary-taxes-block',
            'woocommerce_cart_totals_after_order_total' => 'render_block_woocommerce/cart-order-summary-block',
        );

        $shortcodeBlockCheckoutHooksMapping = array(
            'woocommerce_review_order_before_cart_contents' => 'render_block_woocommerce/checkout-order-summary-cart-items-block',
            'woocommerce_review_order_after_cart_contents' => 'render_block_woocommerce/checkout-order-summary-coupon-form-block',
            'woocommerce_review_order_before_shipping' => 'render_block_woocommerce/checkout-order-summary-subtotal-block',
            'woocommerce_review_order_before_order_total' => 'render_block_woocommerce/checkout-order-summary-taxes-block',
            'woocommerce_review_order_after_order_total' => 'render_block_woocommerce/checkout-order-summary-block',
        );

        foreach ($contexts as $context => $callback) {

            if ( $context === self::CONTEXT_CART ) {
                $enable = $this->context->getOption('is_enable_cart_amount_saved');
                $position = $themeOptions->cart->positionAmountSavedAction;
            } elseif ($context === self::CONTEXT_BLOCK_CART) {
                $enable   = $this->context->getOption('is_enable_cart_amount_saved');
                $position = $shortcodeBlockCartHooksMapping[$themeOptions->cart->positionAmountSavedAction];
            } elseif ( $context === self::CONTEXT_MINI_CART ) {
                $enable = $this->context->getOption('is_enable_minicart_amount_saved');
                $position = $themeOptions->miniCart->positionAmountSavedAction;
            } elseif ( $context === self::CONTEXT_CHECKOUT ) {
                $enable = $this->context->getOption('is_enable_checkout_amount_saved');
                $position = $themeOptions->checkout->positionAmountSavedAction;
            } elseif ( $context === self::CONTEXT_BLOCK_CHECKOUT ) {
                $enable = $this->context->getOption('is_enable_checkout_amount_saved');
                $position = $shortcodeBlockCheckoutHooksMapping[$themeOptions->checkout->positionAmountSavedAction];
            } elseif ( $context === self::CONTEXT_EDIT_ORDER ) {
                $enable = $this->context->getOption('is_enable_backend_order_amount_saved');
                $position = "woocommerce_admin_order_totals_after_tax";
            } else {
                continue;
            }

            if ($enable) {
                if (has_action("wdp_{$context}_discount_message_install")) {
                    do_action(
                        "wdp_{$context}_discount_message_install",
                        $this,
                        $position
                    );
                } else {
                    add_action($position, $callback, 10);
                }
            }
        }
    }

    public function getOption($option, $default = false)
    {
        return $this->context->getOption($option);
    }

    public function outputCartAmountSaved()
    {
        $includeTax   = 'incl' === $this->context->getTaxDisplayCartMode();
        $amount_saved = $this->getAmountSaved($includeTax);

        if ($amount_saved > 0) {
            $this->outputAmountSaved(self::CONTEXT_CART, $amount_saved);
        }
    }

    public function outputBlockCartAmountSaved($blockContent)
    {
        $includeTax = 'incl' === $this->context->getTaxDisplayCartMode();
        $amount_saved = $this->getAmountSaved($includeTax);
        $afterTotals = false;
        if (doing_filter('render_block_woocommerce/cart-order-summary-block')) {
            $afterTotals = true;
        }

        if ($amount_saved > 0) {
            ob_start();
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $blockContent;
            $this->outputAmountSaved(self::CONTEXT_BLOCK_CART, $amount_saved, '',
                array('afterTotals' => $afterTotals));
            $blockContent = ob_get_clean();
        }

        return $blockContent;
    }

    public function outputMiniCartAmountSaved()
    {
        $includeTax  = 'incl' === $this->context->getTaxDisplayCartMode();
        $amountSaved = $this->getAmountSaved($includeTax);

        if ($amountSaved > 0) {
            $this->outputAmountSaved(self::CONTEXT_MINI_CART, $amountSaved);
        }
    }

    public function outputCheckoutAmountSaved()
    {
        $includeTax  = 'incl' === $this->context->getTaxDisplayCartMode();
        $amountSaved = $this->getAmountSaved($includeTax);

        if ($amountSaved > 0) {
            $this->outputAmountSaved(self::CONTEXT_CHECKOUT, $amountSaved);
        }
    }

    public function outputBlockCheckoutAmountSaved($blockContent)
    {
        $includeTax  = 'incl' === $this->context->getTaxDisplayCartMode();
        $amountSaved = $this->getAmountSaved($includeTax);
        $afterTotals = false;
        $cartContentsHook = false;
        if (doing_filter('render_block_woocommerce/checkout-order-summary-block')) {
            $afterTotals = true;
        } elseif (doing_filter('render_block_woocommerce/checkout-order-summary-cart-items-block') ||
            doing_filter('render_block_woocommerce/checkout-order-summary-coupon-form-block')) {
            $cartContentsHook = true;
        }

        if ($amountSaved > 0) {
            ob_start();
            if ($cartContentsHook) {
                $this->outputAmountSaved(self::CONTEXT_BLOCK_CHECKOUT, $amountSaved, '',
                    array('afterTotals' => $afterTotals));
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $blockContent;
            } else {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $blockContent;
                $this->outputAmountSaved(self::CONTEXT_BLOCK_CHECKOUT, $amountSaved, '',
                    array('afterTotals' => $afterTotals));
            }
            $blockContent = ob_get_clean();
        }

        return $blockContent;
    }

    public function outputEditOrderAmountSaved($orderId)
    {
        $amountSaved = $this->getAmountSavedOrder($orderId);
        $order = \wc_get_order($orderId);
        $currency = $order->get_currency();

        if ($amountSaved > 0) {
            $this->outputAmountSaved(self::CONTEXT_EDIT_ORDER, $amountSaved, $currency);
        }
    }

    /**
     * @param int $orderId
     *
     * @return float
     */
    public function getAmountSavedOrder($orderId)
    {
        $rules = $this->orderRepository->getAppliedRulesForOrder($orderId);

        $saved = floatval(0);

        foreach ($rules as $row) {
            $order = $row['order'];
            $rule = $row['rule'];
            $saved += floatval($order->amount + $order->extra + $order->giftedAmount);
        }

        return (float)$saved;
    }

    public function outputAmountSaved($context, $amountSaved, $currency = '', $additionalArgs = array())
    {
        switch ($context) {
            case self::CONTEXT_CART:
                $template = 'cart-totals.php';
                break;
            case self::CONTEXT_BLOCK_CART:
                $template = 'block-cart-totals.php';
                break;
            case self::CONTEXT_MINI_CART:
                $template = 'mini-cart.php';
                break;
            case self::CONTEXT_CHECKOUT:
                $template = 'cart-totals-checkout.php';
                break;
            case self::CONTEXT_BLOCK_CHECKOUT:
                $template = 'block-cart-totals-checkout.php';
                break;
            case self::CONTEXT_EDIT_ORDER:
                $template = 'edit-order.php';
                break;
            default:
                $template = null;
                break;
        }

        if (is_null($template)) {
            return;
        }

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo TemplateLoader::wdpGetTemplate($template, array(

            'amount_saved'   => $amountSaved,
            'title'          => $this->amountSavedLabel,
            'currency'       => $currency,
            'additionalArgs' => $additionalArgs
        ), 'amount-saved');
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function getAmountSaved($includeTax)
    {
        $cartItems    = WC()->cart->cart_contents;
        $wcSessionFacade = new WcCustomerSessionFacade(WC()->session);

        $amountSaved = floatval(0);

        foreach ($cartItems as $cartItemKey => $cartItem) {
            $facade = new WcCartItemFacade($this->context, $cartItem, $cartItemKey);

            if ($includeTax) {
                $original = ($facade->getOriginalPriceWithoutTax() + $facade->getOriginalPriceTax()) * $facade->getQty();
                $current  = $facade->getSubtotal() + $facade->getExactSubtotalTax();
            } else {
                $original = $facade->getOriginalPriceWithoutTax() * $facade->getQty();
                $current  = $facade->getSubtotal();
            }

            $amountSaved += $original - $current;
        }

        foreach (WC()->cart->get_coupons() as $wcCoupon) {
            $code = $wcCoupon->get_code();

            if ( $this->context->isUseMergedCoupons() ) {
                $mergedCoupon = WcAdpMergedCouponHelper::loadOfCoupon($wcCoupon);

                if ($mergedCoupon->hasAdpPart() || $this->context->getOption('add_all_coupons_to_amount_saved')) {
                    $amountSaved += WC()->cart->get_coupon_discount_amount($code, !$includeTax);
                }
            } else {
                $adpData = $wcCoupon->get_meta('adp', true, 'edit');
                $coupon  = isset($adpData['parts']) ? reset($adpData['parts']) : null;

                if ($coupon || $this->context->getOption('add_all_coupons_to_amount_saved')) {
                    /** @var $coupon CouponCart */
                    $amountSaved += WC()->cart->get_coupon_discount_amount($code, ! $includeTax);
                }
            }
        }

        foreach ($wcSessionFacade->getFees() as $fee) {
            foreach (WC()->cart->get_fees() as $cartFee) {
                if ($fee->getName() === $cartFee->name) {
                    if ($includeTax) {
                        $amountSaved -= $cartFee->total + $cartFee->tax;
                    } else {
                        $amountSaved -= $cartFee->total;
                    }
                }
            }
        }

        return floatval(apply_filters('wdp_amount_saved', $amountSaved, $cartItems));
    }

}
