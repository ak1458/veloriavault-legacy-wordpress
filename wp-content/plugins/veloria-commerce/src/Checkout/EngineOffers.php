<?php
/** Engine offer controls mounted through classic WooCommerce hooks. */
namespace Veloria\Commerce\Checkout;

use Veloria\Commerce\Pricing\CartPricing;
use Veloria\Commerce\Pricing\CouponRepository;
use Veloria\Commerce\Settings;

defined( 'ABSPATH' ) || exit;

final class EngineOffers {
	public static function register(): void {
		add_action( 'wp_loaded', array( self::class, 'handle' ), 30 );
		add_action( 'woocommerce_before_cart', array( self::class, 'render' ), 20 );
		add_action( 'woocommerce_before_checkout_form', array( self::class, 'render' ), 15 );
		add_action( 'wp', static function (): void {
			if ( CouponRepository::uses_engine() ) {
				remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
			}
		} );
		add_filter( 'woocommerce_coupons_enabled', static function ( $enabled ) {
			// Only hide the native coupon field while rendering the classic cart.
			// Engine holds/counting still use WooCommerce's coupon APIs directly.
			return CouponRepository::uses_engine() && ! wp_doing_ajax() && is_cart() ? false : $enabled;
		} );
	}

	public static function handle(): void {
		if ( ! isset( $_POST['veloria_offer_nonce'] ) || ! CouponRepository::uses_engine() ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['veloria_offer_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'veloria_offer' ) || ! WC()->cart || ! WC()->session ) {
			wc_add_notice( 'Your offer request expired. Please refresh the page.', 'error' );
			return;
		}
		$codes = CouponRepository::session_codes();
		if ( isset( $_POST['veloria_remove_offer'] ) ) {
			$remove = strtoupper( sanitize_text_field( wp_unslash( $_POST['veloria_remove_offer'] ) ) );
			CouponRepository::set_session_codes( array_values( array_diff( $codes, array( $remove ) ) ) );
		} else {
			$code = strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['veloria_offer_code'] ?? '' ) ) ) );
			$items = CartPricing::cart_items();
			$subtotal = array_sum( array_map( static fn( $item ) => $item['price'] * $item['quantity'], $items ) );
			$check = CartPricing::engine()->validate_coupon( $code, $subtotal, (int) array_sum( array_column( $items, 'quantity' ) ), CouponRepository::engine_coupons(), $codes );
			$error = ! $check['valid'] ? (string) $check['error'] : CouponRepository::validate_native( new \WC_Coupon( $code ) );
			if ( in_array( $code, $codes, true ) ) {
				$error = 'This offer is already applied.';
			}
			if ( '' !== $error ) {
				wc_add_notice( $error, 'error' );
			} else {
				CouponRepository::set_session_codes( array_merge( $codes, array( $code ) ) );
				wc_add_notice( 'Offer applied.', 'success' );
			}
		}
		WC()->cart->calculate_totals();
		$target = isset( $_POST['veloria_offer_checkout'] ) ? wc_get_checkout_url() : wc_get_cart_url();
		wp_safe_redirect( $target );
		exit;
	}

	public static function render(): void {
		if ( ! CartPricing::engine_enabled() ) {
			return;
		}
		?>
		<section class="veloria-engine-offers woocommerce-info" aria-label="Discounts and offers">
			<p><strong><?php esc_html_e( 'Automatic offers', 'veloria-commerce' ); ?></strong>
			<?php foreach ( (array) Settings::get( 'tier_rules', array() ) as $rule ) : ?>
				<?php echo esc_html( sprintf( '%s+ items: %s%% off. ', $rule['min_qty'], $rule['percent'] ) ); ?>
			<?php endforeach; ?>
			<?php echo esc_html( sprintf( 'Eligible online payments: extra %s%%. Combined discounts up to %s%%.', Settings::get( 'prepaid_percent', 5 ), Settings::get( 'max_discount_percent', 35 ) ) ); ?></p>
			<?php if ( CouponRepository::uses_engine() && ( CouponRepository::has_active_coupons() || CouponRepository::session_codes() ) ) : ?>
				<form method="post" class="veloria-offer-form">
					<?php wp_nonce_field( 'veloria_offer', 'veloria_offer_nonce' ); ?>
					<?php if ( is_checkout() ) : ?><input type="hidden" name="veloria_offer_checkout" value="1"><?php endif; ?>
					<label for="veloria-offer-code"><?php esc_html_e( 'Offer code', 'veloria-commerce' ); ?></label>
					<input id="veloria-offer-code" type="text" name="veloria_offer_code" autocomplete="off">
					<button class="button" type="submit"><?php esc_html_e( 'Apply offer', 'veloria-commerce' ); ?></button>
					<?php foreach ( CouponRepository::session_codes() as $code ) : ?>
						<button class="button" type="submit" name="veloria_remove_offer" value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( sprintf( 'Remove %s', $code ) ); ?></button>
					<?php endforeach; ?>
				</form>
			<?php endif; ?>
		</section>
		<?php
	}
}
