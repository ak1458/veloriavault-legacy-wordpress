<?php
/**
 * Checkout Order Receipt Template (theme override).
 *
 * The storefront's payment card (templates/order-pay.php) already shows the
 * amount, so the default order-details list is omitted. The receipt action
 * still fires, which is where the Razorpay plugin prints its payment form.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.2.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_receipt_' . $order->get_payment_method(), $order->get_id() );
?>
<div class="clear"></div>
