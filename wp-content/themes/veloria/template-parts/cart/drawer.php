<?php
/**
 * Slide-out cart (CartDrawer.tsx).
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;
?>
<div x-show="$store.cart.isOpen" x-cloak>
	<div x-show="$store.cart.isOpen" x-transition.opacity.duration.300ms class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[60]" @click="$store.cart.closeCart()"></div>
	<div x-show="$store.cart.isOpen"
		x-transition:enter="transition-transform ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
		x-transition:leave="transition-transform ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
		class="fixed top-0 right-0 h-[100dvh] w-full max-w-md bg-[#faf8f5] z-[60] shadow-2xl flex flex-col" role="dialog" aria-modal="true" aria-label="Shopping cart">
		<div class="flex items-center justify-between p-6 border-b border-gray-200">
			<div class="flex items-center space-x-2">
				<?php veloria_icon_e( 'shopping-bag', 24, 'w-5 h-5 text-[#b59a5c]' ); ?>
				<h2 class="font-serif text-xl tracking-wide text-[#1a1a1a]">Shopping Cart</h2>
			</div>
			<button type="button" @click="$store.cart.closeCart()" class="p-2 -mr-2 text-gray-600 hover:text-black transition-colors" aria-label="Close cart"><?php veloria_icon_e( 'x', 24 ); ?></button>
		</div>

		<div class="flex-1 overflow-y-auto px-6 py-4">
			<p x-show="$store.cart.error" x-cloak class="mb-3 text-xs text-red-500" x-text="$store.cart.error"></p>
			<template x-if="$store.cart.items.length === 0">
				<div class="flex flex-col items-center justify-center h-full text-center">
					<div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
						<?php veloria_icon_e( 'shopping-bag', 24, 'w-8 h-8 text-gray-400' ); ?>
					</div>
					<p class="font-serif text-lg mb-2 text-gray-800">Your cart is empty</p>
					<p class="text-sm text-gray-500 mb-6">Looks like you haven&apos;t added anything yet.</p>
					<button type="button" @click="$store.cart.closeCart()" class="bg-[#1a1a1a] text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-colors">Continue Shopping</button>
				</div>
			</template>
			<div class="space-y-4" x-show="$store.cart.items.length > 0">
				<template x-for="item in $store.cart.items" :key="item.id">
					<div class="flex gap-4 bg-white p-3 rounded-lg shadow-sm border border-gray-100">
						<div class="relative w-20 h-20 bg-gray-50 rounded overflow-hidden flex-shrink-0">
							<img :src="item.image || veloriaConfig.placeholder" :alt="item.name" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
						</div>
						<div class="flex-1 min-w-0 flex flex-col justify-between">
							<div>
								<p class="text-[10px] text-gray-600 uppercase tracking-wider" x-text="item.category"></p>
								<h3 class="font-medium text-sm text-gray-800 truncate hover:text-[#b59a5c]">
									<a :href="item.href" @click="$store.cart.closeCart()" x-text="item.name"></a>
								</h3>
								<p class="font-semibold text-sm mt-1" x-text="$inr(item.price)"></p>
							</div>
							<div class="flex items-center justify-between mt-2">
								<div class="flex items-center border border-gray-200 rounded">
									<button type="button" @click="$store.cart.updateQuantity(item.id, item.quantity - 1)" class="w-7 h-7 flex items-center justify-center hover:bg-gray-50 transition-colors" aria-label="Decrease quantity"><?php veloria_icon_e( 'minus', 24, 'w-3 h-3 text-gray-600' ); ?></button>
									<span class="w-6 text-center text-xs font-medium" x-text="item.quantity"></span>
									<button type="button" @click="$store.cart.updateQuantity(item.id, item.quantity + 1)" class="w-7 h-7 flex items-center justify-center hover:bg-gray-50 transition-colors" aria-label="Increase quantity"><?php veloria_icon_e( 'plus', 24, 'w-3 h-3 text-gray-600' ); ?></button>
								</div>
								<button type="button" @click="$store.cart.removeItem(item.id)" class="text-gray-400 hover:text-red-500 transition-colors" aria-label="Remove item"><?php veloria_icon_e( 'trash-2', 24, 'w-4 h-4' ); ?></button>
							</div>
						</div>
					</div>
				</template>
			</div>
		</div>

		<div x-show="$store.cart.items.length > 0" class="border-t border-gray-200 p-6 pb-24 lg:pb-6 space-y-4 bg-white shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
			<div class="flex justify-between items-center">
				<span class="text-gray-600 font-medium">Subtotal</span>
				<span class="text-lg font-bold text-[#1a1a1a]" x-text="$inr($store.cart.subtotal)"></span>
			</div>
			<p class="text-[11px] text-gray-400 text-center">Shipping and taxes calculated at checkout</p>
			<div class="grid grid-cols-2 gap-3">
				<a href="<?php echo esc_url( home_url( '/cart' ) ); ?>" @click="$store.cart.closeCart()" class="flex items-center justify-center border border-[#1a1a1a] text-[#1a1a1a] py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-50 transition-colors text-center">View Cart</a>
				<a href="<?php echo esc_url( home_url( '/checkout' ) ); ?>" class="flex items-center justify-center bg-[#1a1a1a] text-white py-3 text-xs font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-colors text-center">Checkout</a>
			</div>
		</div>
	</div>
</div>
