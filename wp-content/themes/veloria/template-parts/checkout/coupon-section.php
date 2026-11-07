<?php
/**
 * CouponSection.tsx. The code box appears only while at least one engine
 * coupon is active (none are today, as on the headless site).
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
	<h3 class="font-serif text-lg text-gray-800 mb-4">Discounts &amp; Offers</h3>
	<div class="p-4 bg-gradient-to-r from-[#b59a5c]/10 to-[#b59a5c]/5 border border-[#b59a5c]/20 rounded-lg space-y-2">
		<div class="flex items-center gap-2 mb-1">
			<?php veloria_icon_e( 'sparkles', 24, 'w-4 h-4 text-[#b59a5c]' ); ?>
			<span class="text-sm font-medium text-gray-800">Applied automatically</span>
		</div>
		<ul class="text-sm text-gray-600 space-y-1.5 ml-6 list-disc">
			<?php foreach ( veloria_offer_tiers() as $tier ) : ?>
				<li><?php echo esc_html( $tier['label'] ); ?>: <span class="text-[#b59a5c] font-medium"><?php echo esc_html( veloria_format_number( $tier['percent'] ) ); ?>% Off</span></li>
			<?php endforeach; ?>
			<li><?php echo esc_html( (string) veloria_setting( 'prepaid_offer_text' ) ); ?></li>
		</ul>
	</div>

	<template x-if="hasActiveCoupons">
		<div>
			<div class="flex gap-2 mt-4 mb-2">
				<div class="relative flex-1">
					<?php veloria_icon_e( 'tag', 24, 'absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400' ); ?>
					<input type="text" x-model="couponInput" @input="couponInput = couponInput.toUpperCase()" @keydown.enter.prevent="applyCoupon()" placeholder="Enter coupon code" aria-label="Coupon code" :disabled="applying" class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-[#b59a5c] uppercase">
				</div>
				<button type="button" @click="applyCoupon()" :disabled="applying || !couponInput.trim()" class="px-4 py-2.5 bg-[#1a1a1a] text-white text-sm font-medium rounded-lg hover:bg-[#b59a5c] transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
					<span x-show="applying"><?php veloria_icon_e( 'loader-2', 24, 'w-4 h-4 animate-spin' ); ?></span>
					<span x-show="!applying">Apply</span>
				</button>
			</div>
			<p x-show="couponError" class="text-xs text-red-500 mb-2" x-text="couponError"></p>
			<div x-show="appliedCodes.length > 0" class="space-y-2 mt-3">
				<template x-for="code in appliedCodes" :key="code">
					<div class="flex items-center justify-between p-3 bg-green-50 border border-green-100 rounded-lg">
						<div class="flex items-center gap-2">
							<?php veloria_icon_e( 'check', 24, 'w-4 h-4 text-green-600' ); ?>
							<div>
								<span class="text-sm font-medium text-gray-800" x-text="code"></span>
								<p x-show="couponAmount(code) !== null" class="text-xs text-green-600" x-text="'-' + $inr(couponAmount(code))"></p>
							</div>
						</div>
						<button type="button" @click="removeCoupon(code)" :aria-label="'Remove coupon ' + code" class="p-1 text-gray-400 hover:text-red-500 transition-colors"><?php veloria_icon_e( 'x', 24, 'w-4 h-4' ); ?></button>
					</div>
				</template>
			</div>
			<p x-show="calculation && calculation.isCapped" class="text-xs text-gray-500 mt-3">Total savings are capped at <?php echo esc_html( veloria_format_number( (float) veloria_setting( 'max_discount_percent', 35 ) ) ); ?>% of the order value.</p>
		</div>
	</template>
</div>
