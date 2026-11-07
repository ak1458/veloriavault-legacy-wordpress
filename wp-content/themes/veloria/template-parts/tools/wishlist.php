<?php
/**
 * Template Name: Wishlist
 *
 * src/app/wishlist/page.tsx (items live in the browser, same storage key).
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;


?>
<div class="min-h-screen bg-[#faf8f5]" x-data="{ add(item) { if (item.type !== 'simple') { window.location.assign(item.href); return; } $store.cart.addItem({ id: item.id, name: item.name, slug: item.slug, href: item.href, price: item.price, image: item.image, category: item.category }); $store.cart.openCart(); }, addAll() { $store.wishlist.items.forEach(i => $store.cart.addItem({ id: i.id, name: i.name, slug: i.slug, href: i.href, price: i.price, image: i.image, category: i.category })); $store.cart.openCart(); } }">
	<div class="relative bg-[#1a1a1a] overflow-hidden">
		<div class="absolute inset-0 opacity-10">
			<div class="absolute inset-0" style="background-image: radial-gradient(circle at 20% 50%, rgba(181,154,92,0.3) 0%, transparent 50%), radial-gradient(circle at 80% 50%, rgba(181,154,92,0.2) 0%, transparent 50%);"></div>
		</div>
		<div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20">
			<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
				<div>
					<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="inline-flex items-center gap-2 text-gray-400 hover:text-[#b59a5c] transition-colors text-sm mb-4"><?php veloria_icon_e( 'arrow-left', 16 ); ?><span>Continue Shopping</span></a>
					<?php if ( empty( $args['hide_heading'] ) ) : ?><h1 class="text-3xl md:text-4xl font-serif text-white tracking-wide flex items-center gap-3"><?php veloria_icon_e( 'heart', 24, 'w-8 h-8 text-[#b59a5c]', array( 'fill' => 'currentColor' ) ); ?>My Wishlist</h1><?php endif; ?>
					<p class="text-gray-400 text-sm mt-2 tracking-wide" x-text="$store.wishlist.items.length + ' ' + ($store.wishlist.items.length === 1 ? 'item' : 'items') + ' saved for later'">0 items saved for later</p>
				</div>
				<button type="button" x-show="$store.wishlist.items.length > 1 &amp;&amp; $store.wishlist.items.every(i =&gt; i.type === 'simple')" x-cloak @click="addAll()" class="flex items-center gap-2 bg-[#b59a5c] text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-[#a08a4f] transition-colors shadow-lg"><?php veloria_icon_e( 'shopping-bag', 14 ); ?>Add All to Cart</button>
			</div>
		</div>
	</div>

	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
		<template x-if="$store.wishlist.items.length === 0">
			<div class="flex flex-col items-center justify-center py-24 px-4 text-center max-w-lg mx-auto animate-slideUp">
				<div class="relative mb-8">
					<div class="w-28 h-28 bg-gradient-to-br from-gray-100 to-gray-50 rounded-full flex items-center justify-center shadow-inner"><?php veloria_icon_e( 'heart', 24, 'w-12 h-12 text-gray-300' ); ?></div>
					<div class="absolute -top-1 -right-1 w-8 h-8 bg-[#b59a5c]/10 rounded-full flex items-center justify-center"><?php veloria_icon_e( 'sparkles', 24, 'w-4 h-4 text-[#b59a5c]' ); ?></div>
				</div>
				<h2 class="font-serif text-2xl mb-3 text-gray-800">Your wishlist is empty</h2>
				<p class="text-gray-600 text-sm mb-8 leading-relaxed max-w-sm">Save the pieces you love to find them later. Your favorites will be waiting right here.</p>
				<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="bg-[#1a1a1a] text-white px-10 py-4 text-xs font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-all duration-300 shadow-sm hover:shadow-lg">Explore Collection</a>
			</div>
		</template>
		<div x-show="$store.wishlist.items.length > 0" x-cloak class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 md:gap-7">
			<template x-for="(item, index) in $store.wishlist.items" :key="item.id">
				<div class="group bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-[0_2px_8px_rgba(0,0,0,0.03)] hover:shadow-[0_8px_30px_rgba(0,0,0,0.08)] transition-all duration-500 flex flex-col h-full veloria-in-y" :style="'animation-delay:' + (index * 0.05) + 's'">
					<div class="relative aspect-square bg-[#f0ede8] overflow-hidden flex-shrink-0">
						<a :href="item.href || ('/product/' + item.slug)"><img :src="item.image || veloriaConfig.placeholder" :alt="item.name" class="absolute inset-0 h-full w-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out" loading="lazy"></a>
						<button type="button" @click="$store.wishlist.remove(item.id)" class="absolute top-3 right-3 w-9 h-9 bg-white/95 backdrop-blur-sm rounded-full flex items-center justify-center shadow-md hover:bg-red-50 hover:text-red-500 transition-all duration-200 opacity-0 group-hover:opacity-100 hover:scale-110" aria-label="Remove from wishlist"><?php veloria_icon_e( 'x', 24, 'w-4 h-4' ); ?></button>
						<div x-show="item.originalPrice" class="absolute top-3 left-3 bg-black text-white text-[9px] font-bold tracking-wider px-2.5 py-1 rounded-full uppercase">Sale</div>
					</div>
					<div class="p-4 md:p-5 flex-1 flex flex-col justify-between">
						<div>
							<p class="text-[9px] md:text-[10px] text-[#b59a5c] uppercase tracking-[0.2em] mb-1.5 font-bold" x-text="item.category"></p>
							<a :href="item.href || ('/product/' + item.slug)"><h3 class="text-sm md:text-base font-medium text-gray-900 line-clamp-2 mb-2 leading-snug hover:text-[#b59a5c] transition-colors" x-text="item.name"></h3></a>
							<div class="flex items-center gap-2 mb-4">
								<span class="font-bold text-base text-gray-900" x-text="$inr(item.price)"></span>
								<span x-show="item.originalPrice" class="text-xs text-gray-500 line-through" x-text="item.originalPrice ? $inr(item.originalPrice) : ''"></span>
							</div>
						</div>
						<button type="button" @click="add(item)" class="w-full bg-[#1a1a1a] text-white py-3 rounded-lg text-xs font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-all duration-300 flex items-center justify-center gap-2 group/btn">
							<?php veloria_icon_e( 'shopping-bag', 24, 'w-3.5 h-3.5 group-hover/btn:scale-110 transition-transform' ); ?><span>Move to Cart</span>
						</button>
					</div>
				</div>
			</template>
		</div>
	</div>
</div>
<?php
