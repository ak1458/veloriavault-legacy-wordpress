<?php
/** Preserve Veloria customer tools through native WooCommerce extension hooks. */
defined( 'ABSPATH' ) || exit;

function veloria_native_wishlist(): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! apply_filters( 'veloria_show_builtin_wishlist', true, $product ) ) return;
	$item = array( 'id' => $product->get_id(), 'name' => $product->get_name(), 'slug' => $product->get_slug(), 'href' => $product->get_permalink(), 'price' => (float) $product->get_price(), 'image' => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) ?: '', 'type' => $product->get_type(), 'category' => wp_strip_all_tags( wc_get_product_category_list( $product->get_id() ) ) );
	printf( '<div x-data="{ item: %s }" class="veloria-native-wishlist"><button type="button" class="button" @click.prevent="$store.wishlist.toggle(item)" :aria-pressed="$store.wishlist.has(item.id)" x-text="$store.wishlist.has(item.id) ? \'Saved to wishlist\' : \'Save to wishlist\'">Save to wishlist</button></div>', veloria_json_attr( $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'woocommerce_after_shop_loop_item', 'veloria_native_wishlist', 15 );
add_action( 'woocommerce_after_add_to_cart_form', 'veloria_native_wishlist', 15 );

add_filter( 'woocommerce_my_account_my_orders_actions', static function ( array $actions, $order ): array {
	if ( \Veloria\Commerce\Orders\OrderService::is_returnable( $order )['ok'] ) $actions['veloria-return'] = array( 'url' => home_url( '/account/return/' . $order->get_id() ), 'name' => __( 'Request return', 'veloria' ) );
	$order_actions = \Veloria\Commerce\Orders\OrderService::actions( $order );
	if ( $order_actions['canCancel'] ) $actions['veloria-cancel'] = array( 'url' => $order->get_view_order_url() . '#veloria-order-tools', 'name' => 'review' === $order_actions['cancelMode'] ? __( 'Request cancellation review', 'veloria' ) : __( 'Manage cancellation', 'veloria' ) );
	return $actions;
}, 20, 2 );

add_action( 'woocommerce_order_details_after_order_table', static function ( $order ): void {
	if ( ! $order instanceof WC_Order ) return;
	$customer = (int) $order->get_customer_id();
	$owned = $customer > 0 ? get_current_user_id() === $customer : \Veloria\Commerce\Orders\OrderService::owns( $order, (string) \Veloria\Commerce\Auth\Session::otp_email() );
	if ( ! $owned ) return;
	$actions = \Veloria\Commerce\Orders\OrderService::actions( $order );
	echo '<div id="veloria-order-tools" x-data="nativeOrderTools(' . (int) $order->get_id() . ', ' . esc_attr( wp_json_encode( $order->get_billing_email() ) ) . ', ' . esc_attr( wp_json_encode( $order->get_status() ) ) . ', ' . esc_attr( wp_json_encode( $actions['cancelMode'] ) ) . ')" class="veloria-native-order-tools"><h2>Manage this order</h2>';
	if ( $actions['refundProgress'] ) echo '<p class="veloria-order-progress" role="status">' . esc_html( $actions['refundProgress'] ) . '</p>';
	echo '<p x-show="message" x-text="message" role="status"></p><div class="veloria-order-actions"><button type="button" class="button" :disabled="busy" @click="invoice()">Download invoice</button>';
	if ( $actions['canReturn'] ) echo '<a class="button" href="' . esc_url( home_url( '/account/return/' . $order->get_id() ) ) . '">Request return</a>';
	if ( $actions['canCancel'] ) echo '<button type="button" class="button veloria-cancel-action" x-show="canCancel" :disabled="busy || stateStale" @click="cancel()" x-text="cancelLabel">' . esc_html( 'review' === $actions['cancelMode'] ? 'Request cancellation review' : 'Cancel order' ) . '</button>';
	echo '</div><p>Within 48 hours of order placement, eligible cancellations proceed after courier checks. Later requests need staff approval before dispatch. Online refunds go to the original payment method; bank timing varies. Unpaid COD has no refund. Returns may be requested within 7 days of verified delivery and require inspection.</p></div>';
} );

// Keep existing bookmarked shop filters working with the native archive query.
add_action( 'pre_get_posts', static function ( $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'product' ) ) return;
	if ( isset( $_GET['search'] ) && is_string( $_GET['search'] ) ) $query->set( 's', sanitize_text_field( wp_unslash( $_GET['search'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['category'] ) && is_string( $_GET['category'] ) ) $query->set( 'product_cat', sanitize_title( wp_unslash( $_GET['category'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
} );
