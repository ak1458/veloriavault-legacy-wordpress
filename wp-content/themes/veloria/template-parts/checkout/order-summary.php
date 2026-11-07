<?php
/**
 * OrderSummary.tsx — renders inside the checkout() Alpine component.
 *
 * @package Veloria
 *
 * @var array $args { show_toggle: bool }
 */

defined( 'ABSPATH' ) || exit;

$cod_fee = (float) veloria_setting( 'cod_fee', 149 );
?>
<div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
	<h3 class="font-serif text-lg text-gray-800 mb-6 border-b border-gray-100 pb-3">Order Summary</h3>
	<div class="space-y-4 max-h-[300px] overflow-y-auto mb-6 pr-2 scrollbar-style">
		<template x-for="item in items" :key="item.id">
			<div class="flex items-center gap-4">
				<div class="relative w-16 h-16 bg-gray-50 rounded border flex-shrink-0">
					<img :src="item.image || veloriaConfig.placeholder" :alt="item.name" class="absolute inset-0 h-full w-full object-cover rounded" loading="lazy">
					<span class="absolute -top-1 -right-1 bg-gray-900 text-white w-4 h-4 rounded-full text-center flex items-center justify-center text-[10px] font-bold" x-text="item.quantity"></span>
				</div>
				<div class="flex-1 min-w-0">
					<h4 class="text-xs font-medium text-gray-800 truncate mb-1" x-text="item.name"></h4>
					<p class="text-xs text-gray-400 uppercase" x-text="item.category"></p>
				</div>
				<p class="font-bold text-xs text-gray-800" x-text="$inr(item.price * item.quantity)"></p>
			</div>
		</template>
	</div>

	<?php if ( ! empty( $args['show_toggle'] ) ) : ?>
		<div class="mb-4 p-3 bg-gray-50 rounded-lg">
			<p class="text-xs font-medium text-gray-600 mb-2">Payment Method</p>
			<div class="flex flex-col gap-2 sm:flex-row">
				<button type="button" @click="setPrepaid(true)" class="flex-1 py-2 px-3 text-xs font-medium rounded transition-colors" :class="isPrepaid ? 'bg-[#b59a5c] text-white' : 'bg-white text-gray-600 border border-gray-200'">
					Prepaid<span class="block text-[10px] opacity-80">+<?php echo esc_html( veloria_format_number( (float) veloria_setting( 'prepaid_percent', 5 ) ) ); ?>% extra off</span>
				</button>
				<button type="button" @click="setPrepaid(false)" class="flex-1 py-2 px-3 text-xs font-medium rounded transition-colors" :class="!isPrepaid ? 'bg-[#b59a5c] text-white' : 'bg-white text-gray-600 border border-gray-200'">
					Cash on Delivery<span class="block text-[10px] opacity-80">+<?php echo esc_html( veloria_inr( $cod_fee ) ); ?> fee</span>
				</button>
			</div>
		</div>
	<?php endif; ?>

	<div class="space-y-2 pt-4 border-t border-gray-100 text-sm">
		<div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span class="font-semibold" x-text="$inr(subtotal)"></span></div>
		<template x-for="(saving, idx) in (calculation ? calculation.savingsBreakdown : [])" :key="idx">
			<div class="flex justify-between text-green-600"><span class="text-xs" x-text="saving.label"></span><span class="font-medium text-xs" x-text="'-' + $inr(saving.amount)"></span></div>
		</template>
		<div class="flex justify-between">
			<span class="text-gray-500 flex items-center gap-1"><?php veloria_icon_e( 'truck', 14 ); ?> Shipping</span>
			<span x-show="!calculation || calculation.shippingCost === 0" class="font-bold text-green-600 text-xs">FREE</span>
			<span x-show="calculation && calculation.shippingCost > 0" x-cloak class="font-semibold" x-text="calculation ? $inr(calculation.shippingCost) : ''"></span>
		</div>
		<div x-show="!isPrepaid" x-cloak class="flex justify-between text-amber-600">
			<span class="text-xs flex items-center gap-1"><?php veloria_icon_e( 'alert-circle', 12 ); ?> COD Fee</span>
			<span class="font-medium text-xs">+<?php echo esc_html( veloria_inr( $cod_fee ) ); ?></span>
		</div>
		<div x-show="totalSavings > 0" x-cloak class="pt-2 border-t border-dashed border-gray-200">
			<div class="flex justify-between text-green-600"><span class="text-xs font-medium">Total Savings</span><span class="font-bold text-xs" x-text="$inr(totalSavings)"></span></div>
		</div>
		<div class="border-t border-gray-100 pt-3 flex justify-between text-base font-bold text-gray-900">
			<span>Total Amount</span><span x-text="$inr(finalTotal || subtotal)"></span>
		</div>
	</div>
</div>
