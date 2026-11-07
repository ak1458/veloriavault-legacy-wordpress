<?php
/**
 * MobileBottomNav.tsx
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

$path      = untrailingslashit( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH ) ) ?: '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
$is_active = static function ( string $href ) use ( $path ): bool {
	if ( '/shop' === $href ) {
		return '/shop' === $path || str_starts_with( $path, '/product' );
	}
	return $path === $href;
};
$items = array(
	array( '/shop', 'Shop', 'shopping-bag', null ),
	array( '/wishlist', 'Wishlist', 'heart', '$store.wishlist.items.length' ),
	array( '/cart', 'Cart', 'shopping-cart', '$store.cart.totalItems' ),
	array( '/account', 'Account', 'user', null ),
);
?>
<nav x-data class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50 lg:hidden pb-safe" aria-label="Mobile navigation">
	<div class="flex items-center justify-around h-16">
		<?php foreach ( $items as list( $href, $label, $icon, $badge ) ) : ?>
			<?php $active = $is_active( $href ); ?>
			<a href="<?php echo esc_url( home_url( $href ) ); ?>" class="flex flex-col items-center justify-center w-full h-full relative transition-colors <?php echo $active ? 'text-[#b59a5c]' : 'text-gray-500'; ?>">
				<div class="relative">
					<?php veloria_icon_e( $icon, 22, '', array( 'stroke-width' => $active ? 2.5 : 2 ) ); ?>
					<?php if ( $badge ) : ?>
						<span x-show="<?php echo esc_attr( $badge ); ?> > 0" x-cloak class="absolute -top-2 -right-2 w-4 h-4 bg-[#b59a5c] text-white text-[10px] font-bold flex items-center justify-center rounded-full" x-text="<?php echo esc_attr( $badge ); ?> > 9 ? '9+' : <?php echo esc_attr( $badge ); ?>"></span>
					<?php endif; ?>
				</div>
				<span class="text-[10px] mt-1 font-medium <?php echo $active ? 'text-[#856b35]' : ''; ?>"><?php echo esc_html( $label ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</nav>
