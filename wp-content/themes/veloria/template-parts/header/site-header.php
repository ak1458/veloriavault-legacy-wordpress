<?php
/**
 * Announcement bar + sticky header + mobile menu + cart drawer
 * (PremiumHeader.tsx, AnnouncementBar.tsx, CartDrawer.tsx).
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

$messages = array_values( (array) veloria_setting( 'announcement_messages', array() ) );
$custom   = (int) get_theme_mod( 'custom_logo' );
$file     = $custom ? get_attached_file( $custom ) : '';
// Appearance → Customize → Site Identity; a logo whose upload is missing (live attachment 84) falls back to the theme logo.
$logo     = ( $file && file_exists( $file ) ? wp_get_attachment_image_url( $custom, 'medium' ) : '' ) ?: veloria_theme_asset( 'logo.svg' );
$nav      = veloria_nav_items();
?>
<div x-data="header()" @keydown.escape.window="dismiss()">
	<div x-data="announcement(<?php echo (int) count( $messages ); ?>)" class="bg-black px-4 py-2 text-center text-[10px] font-medium uppercase tracking-[0.12em] text-white sm:text-[11px] sm:tracking-[0.2em]" role="region" aria-label="Current offers">
		<span class="hidden sm:inline">
			<?php foreach ( $messages as $i => $message ) : ?>
				<span><?php if ( $i > 0 ) : ?><span class="mx-3 text-[#b59a5c]" aria-hidden="true">•</span><?php endif; ?><?php echo esc_html( $message ); ?></span>
			<?php endforeach; ?>
		</span>
		<span class="sm:hidden" aria-live="polite">
			<?php foreach ( $messages as $i => $message ) : ?>
				<span x-show="index === <?php echo (int) $i; ?>"<?php echo $i > 0 ? ' x-cloak' : ''; ?>><?php echo esc_html( $message ); ?></span>
			<?php endforeach; ?>
		</span>
	</div>

	<header class="sticky top-0 z-50 bg-[#faf8f5] border-b border-gray-200">
		<div class="hidden lg:block">
			<div class="container mx-auto px-4 sm:px-6">
				<div class="flex items-center justify-between py-4">
					<div class="flex-1"></div>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="block">
						<img src="<?php echo esc_url( $logo ); ?>" alt="Veloria Vault" width="196" height="48" fetchpriority="high" class="h-12 w-auto transition-all duration-300">
					</a>
					<div class="flex-1 flex items-center justify-end space-x-4">
						<a href="<?php echo esc_url( home_url( '/track-order' ) ); ?>" class="text-xs tracking-wider text-gray-600 hover:text-[#b59a5c] cursor-pointer transition-colors font-bold">TRACK ORDER</a>
						<a href="<?php echo esc_url( home_url( '/account' ) ); ?>" class="text-xs tracking-wider text-gray-600 hover:text-black cursor-pointer transition-colors font-bold">LOGIN / REGISTER</a>
						<a href="<?php echo esc_url( home_url( '/wishlist' ) ); ?>" class="p-2 text-gray-800 hover:text-black transition-colors relative" aria-label="Wishlist">
							<?php veloria_icon_e( 'heart', 20 ); ?>
							<span x-show="$store.wishlist.items.length > 0" x-cloak class="absolute top-0 right-0 w-4 h-4 bg-[#b59a5c] text-white text-[9px] font-bold flex items-center justify-center rounded-full" x-text="$store.wishlist.items.length > 9 ? '9+' : $store.wishlist.items.length"></span>
						</a>
						<button type="button" @click="$store.cart.openCart()" class="flex items-center space-x-1 p-2 text-gray-800 hover:text-black transition-colors relative" aria-label="Cart ₹0" x-bind:aria-label="'Cart ' + $inr($store.cart.subtotal)">
							<?php veloria_icon_e( 'shopping-bag', 20 ); ?>
							<span x-show="$store.cart.totalItems > 0" x-cloak class="absolute top-0 right-0 w-4 h-4 bg-[#b59a5c] text-white text-[9px] font-bold flex items-center justify-center rounded-full" x-text="$store.cart.totalItems > 9 ? '9+' : $store.cart.totalItems"></span>
							<span class="text-sm" x-text="$inr($store.cart.subtotal)">₹0</span>
						</button>
					</div>
				</div>

				<nav class="flex items-center justify-center space-x-10 pb-3">
					<?php foreach ( $nav as $item ) : ?>
						<?php $has_menu = ! empty( $item['columns'] ); $key = esc_js( $item['label'] ); ?>
						<div class="relative group" @mouseenter="hovered = <?php echo $has_menu ? "'" . $key . "'" : 'null'; ?>" @mouseleave="hovered = null"<?php echo $has_menu ? ' @focusout="if (!$el.contains($event.relatedTarget)) hovered = null" @keydown.escape="hovered = null"' : ''; ?>>
							<?php if ( $has_menu ) : ?>
								<?php // A real button: click/tap/Enter opens (hover already does on desktop); Escape, focus leaving or mouse leaving close it. No "#" in the URL, no scroll jump. ?>
								<button type="button" @click="hovered = '<?php echo $key; ?>'" aria-haspopup="true" :aria-expanded="(hovered === '<?php echo $key; ?>').toString()" class="flex items-center gap-1 text-[11px] font-bold tracking-[0.35em] text-gray-800 hover:text-[#b59a5c] transition-colors relative py-1">
									<?php echo esc_html( $item['label'] ); ?>
									<?php veloria_icon_e( 'chevron-down', 12, 'group-hover:rotate-180 transition-transform duration-200' ); ?>
									<span class="absolute left-0 bottom-0 w-0 h-[1px] bg-[#b59a5c] transition-all duration-300 group-hover:w-full"></span>
								</button>
							<?php else : ?>
								<a href="<?php echo esc_url( veloria_url( $item['href'] ) ); ?>" class="flex items-center gap-1 text-[11px] font-bold tracking-[0.35em] text-gray-800 hover:text-[#b59a5c] transition-colors relative py-1">
									<?php echo esc_html( $item['label'] ); ?>
									<span class="absolute left-0 bottom-0 w-0 h-[1px] bg-[#b59a5c] transition-all duration-300 group-hover:w-full"></span>
								</a>
							<?php endif; ?>
							<?php if ( ! empty( $item['columns'] ) ) : ?>
								<?php $cols = max( 1, min( 4, count( $item['columns'] ) ) ); ?>
								<div x-show="hovered === '<?php echo esc_js( $item['label'] ); ?>'" x-cloak
									x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2.5" x-transition:enter-end="opacity-100 translate-y-0"
									style="width: <?php echo (int) ( 70 + 160 * $cols ); ?>px; grid-template-columns: repeat(<?php echo (int) $cols; ?>, minmax(0, 1fr));"
									class="absolute left-1/2 -translate-x-1/2 top-full mt-2 bg-white shadow-xl rounded-xl p-6 grid gap-6 z-50 border border-gray-100 before:content-[''] before:absolute before:inset-x-0 before:-top-4 before:h-4 before:bg-transparent">
									<?php foreach ( $item['columns'] as $column ) : ?>
										<div class="space-y-3">
											<h4 class="font-serif text-[12px] font-bold uppercase tracking-wider text-gray-900 border-b border-gray-100 pb-1.5 market-header"><?php echo esc_html( $column['title'] ); ?></h4>
											<ul class="space-y-2">
												<?php foreach ( $column['items'] as $sub ) : ?>
													<li><a href="<?php echo esc_url( veloria_url( $sub['href'] ) ); ?>" class="text-[11px] font-medium text-gray-600 hover:text-[#b59a5c] transition-colors block py-0.5 tracking-wider"><?php echo esc_html( $sub['label'] ); ?></a></li>
												<?php endforeach; ?>
											</ul>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</nav>
			</div>
		</div>

		<div class="lg:hidden">
			<div class="container mx-auto px-4 py-3">
				<div class="grid grid-cols-[44px_1fr_72px] items-center gap-2">
					<button type="button" @click="toggle()" class="p-2 -ml-2 text-gray-800 hover:text-black transition-colors" :aria-label="mobileOpen ? 'Close menu' : 'Open menu'" :aria-expanded="mobileOpen.toString()">
						<span x-show="!mobileOpen"><?php veloria_icon_e( 'menu', 24 ); ?></span>
						<span x-show="mobileOpen" x-cloak><?php veloria_icon_e( 'x', 24 ); ?></span>
					</button>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex justify-center">
						<img src="<?php echo esc_url( $logo ); ?>" alt="Veloria Vault" width="130" height="40" class="h-10 w-auto">
					</a>
					<div class="flex items-center justify-end space-x-1">
						<a href="<?php echo esc_url( home_url( '/wishlist' ) ); ?>" class="p-2 text-gray-800 hover:text-black transition-colors relative" aria-label="Wishlist">
							<?php veloria_icon_e( 'heart', 20 ); ?>
							<span x-show="$store.wishlist.items.length > 0" x-cloak class="absolute top-0 right-0 w-4 h-4 bg-[#b59a5c] text-white text-[9px] font-bold flex items-center justify-center rounded-full" x-text="$store.wishlist.items.length > 9 ? '9+' : $store.wishlist.items.length"></span>
						</a>
						<button type="button" @click="$store.cart.openCart()" class="p-2 text-gray-800 hover:text-black transition-colors relative" aria-label="Cart">
							<?php veloria_icon_e( 'shopping-bag', 20 ); ?>
							<span x-show="$store.cart.totalItems > 0" x-cloak class="absolute top-0 right-0 w-4 h-4 bg-[#b59a5c] text-white text-[9px] font-bold flex items-center justify-center rounded-full" x-text="$store.cart.totalItems > 9 ? '9+' : $store.cart.totalItems"></span>
						</button>
					</div>
				</div>
			</div>
		</div>
	</header>

	<template x-if="mobileOpen">
		<div>
			<div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 lg:hidden" @click="dismiss()" aria-hidden="true"></div>
			<div class="fixed top-0 left-0 bottom-0 w-[80%] max-w-[320px] bg-[#faf8f5] z-50 lg:hidden shadow-2xl" role="dialog" aria-modal="true" aria-label="Mobile navigation menu">
				<div class="flex flex-col h-full">
					<div class="flex items-center justify-between p-4 border-b border-gray-200">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" @click="close()">
							<img src="<?php echo esc_url( $logo ); ?>" alt="Veloria Vault" width="130" height="40" class="h-10 w-auto">
						</a>
						<button type="button" @click="dismiss()" class="p-2 text-gray-800 hover:text-black" aria-label="Close menu"><?php veloria_icon_e( 'x', 24 ); ?></button>
					</div>
					<nav class="flex-1 overflow-y-auto py-6">
						<ul class="space-y-1 px-4">
							<?php foreach ( $nav as $item ) : ?>
								<li class="border-b border-gray-100 last:border-0">
									<?php if ( ! empty( $item['columns'] ) ) : ?>
										<div class="py-2">
											<button type="button" @click="expanded = expanded === '<?php echo esc_js( $item['label'] ); ?>' ? null : '<?php echo esc_js( $item['label'] ); ?>'" class="flex items-center justify-between w-full py-2 text-base font-medium text-gray-800 hover:text-[#b59a5c] tracking-wider">
												<span><?php echo esc_html( $item['label'] ); ?></span>
												<span class="transition-transform duration-200" :class="expanded === '<?php echo esc_js( $item['label'] ); ?>' ? 'rotate-180' : ''"><?php veloria_icon_e( 'chevron-down', 16 ); ?></span>
											</button>
											<div x-show="expanded === '<?php echo esc_js( $item['label'] ); ?>'" x-collapse.duration.200ms x-cloak class="overflow-hidden pl-4 space-y-4 pb-2 pt-1">
												<?php foreach ( $item['columns'] as $column ) : ?>
													<div class="space-y-1.5">
														<p class="text-xs font-bold text-gray-400 uppercase tracking-wider"><?php echo esc_html( $column['title'] ); ?></p>
														<ul class="space-y-1 pl-2 border-l border-gray-100">
															<?php foreach ( $column['items'] as $sub ) : ?>
																<li><a href="<?php echo esc_url( veloria_url( $sub['href'] ) ); ?>" @click="close()" class="block py-1 text-sm text-gray-600 hover:text-[#b59a5c]"><?php echo esc_html( $sub['label'] ); ?></a></li>
															<?php endforeach; ?>
														</ul>
													</div>
												<?php endforeach; ?>
											</div>
										</div>
									<?php else : ?>
										<a href="<?php echo esc_url( veloria_url( $item['href'] ) ); ?>" @click="close()" class="block py-3 text-base font-medium text-gray-800 hover:text-[#b59a5c] transition-colors tracking-wider"><?php echo esc_html( $item['label'] ); ?></a>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
						<div class="mt-8 px-4">
							<p class="text-xs text-gray-400 uppercase tracking-wider mb-3">Account</p>
							<ul class="space-y-1">
								<li><a href="<?php echo esc_url( home_url( '/track-order' ) ); ?>" @click="close()" class="flex items-center space-x-3 py-2 text-gray-600 hover:text-black font-bold"><?php veloria_icon_e( 'package', 18 ); ?><span>Track Your Order</span></a></li>
								<li><a href="<?php echo esc_url( home_url( '/account' ) ); ?>" @click="close()" class="flex items-center space-x-3 py-2 text-gray-600 hover:text-black"><?php veloria_icon_e( 'user', 18 ); ?><span>Login / Register</span></a></li>
								<li><a href="<?php echo esc_url( home_url( '/wishlist' ) ); ?>" @click="close()" class="flex items-center space-x-3 py-2 text-gray-600 hover:text-black"><?php veloria_icon_e( 'heart', 18 ); ?><span x-text="'Wishlist (' + $store.wishlist.items.length + ')'">Wishlist (0)</span></a></li>
								<li><a href="<?php echo esc_url( home_url( '/cart' ) ); ?>" @click="close()" class="flex items-center space-x-3 py-2 text-gray-600 hover:text-black"><?php veloria_icon_e( 'shopping-bag', 18 ); ?><span x-text="'Cart (' + $store.cart.totalItems + ')'">Cart (0)</span></a></li>
							</ul>
						</div>
					</nav>
					<div class="p-4 border-t border-gray-200 bg-gray-50">
						<p class="text-xs text-gray-500 text-center">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> Veloria Vault</p>
					</div>
				</div>
			</div>
		</div>
	</template>

	<?php get_template_part( 'template-parts/cart/drawer' ); ?>
</div>
