<?php
/**
 * PremiumFooter.tsx
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="bg-[#1a1a1a] pb-16 text-white lg:pb-0">
	<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
			<div class="lg:col-span-1">
				<h3 class="text-xl font-serif font-semibold tracking-wide mb-4">VELORIA VAULT</h3>
				<p class="text-gray-400 text-sm leading-relaxed mb-6"><?php echo esc_html( veloria_setting( 'footer_tagline' ) ); ?></p>
				<div class="flex items-center space-x-4">
					<?php foreach ( array( 'instagram' => 'Instagram', 'facebook' => 'Facebook' ) as $network => $label ) : ?>
						<?php if ( veloria_setting( $network . '_url' ) ) : ?>
							<a href="<?php echo esc_url( veloria_setting( $network . '_url' ) ); ?>" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-[#b59a5c] transition-colors duration-300" aria-label="<?php echo esc_attr( $label ); ?>"><?php veloria_icon_e( $network, 18 ); ?></a>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="grid grid-cols-2 gap-8 lg:col-span-2">
				<div>
					<h4 class="text-sm font-semibold tracking-wider uppercase mb-4 text-[#b59a5c]">Collections</h4>
					<ul class="space-y-3">
						<?php foreach ( veloria_footer_collections() as $link ) : ?>
							<li><a href="<?php echo esc_url( veloria_url( $link['href'] ) ); ?>" class="text-gray-400 text-sm hover:text-white transition-colors duration-200"><?php echo esc_html( $link['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div>
					<h4 class="text-sm font-semibold tracking-wider uppercase mb-4 text-[#b59a5c]">Important Links</h4>
					<ul class="space-y-3">
						<?php foreach ( veloria_footer_links() as $link ) : ?>
							<li><a href="<?php echo esc_url( veloria_url( $link['href'] ) ); ?>" class="text-gray-400 text-sm hover:text-white transition-colors duration-200"><?php echo esc_html( $link['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>

			<div x-data="newsletter()">
				<h4 class="text-sm font-semibold tracking-wider uppercase mb-4 text-[#b59a5c]">Newsletter</h4>
				<p class="text-gray-400 text-sm mb-4">Subscribe for early access to new drops and exclusive offers.</p>
				<form class="flex flex-col gap-3" @submit.prevent="submit()">
					<input type="email" id="newsletter-email" placeholder="Enter your email" x-model="email" :disabled="loading" class="px-4 py-3 bg-white/10 border border-white/20 rounded-none text-white text-sm placeholder:text-gray-500 focus:outline-none focus:border-[#b59a5c] transition-colors disabled:opacity-50">
					<button type="submit" :disabled="loading" class="px-4 py-3 bg-[#856b35] text-white text-sm font-medium hover:bg-[#6d5729] transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
						<template x-if="loading"><span class="flex items-center gap-2"><?php veloria_icon_e( 'loader-2', 16, 'animate-spin' ); ?><span>Subscribing...</span></span></template>
						<template x-if="!loading"><span>Subscribe</span></template>
					</button>
					<div x-show="message" x-cloak class="flex items-center gap-2 text-xs" :class="message && message.type === 'success' ? 'text-green-400' : 'text-red-400'">
						<span x-show="message && message.type === 'success'"><?php veloria_icon_e( 'check-circle', 14 ); ?></span>
						<span x-text="message ? message.text : ''"></span>
					</div>
				</form>
			</div>
		</div>
	</div>

	<div class="border-t border-white/10">
		<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
			<div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
				<p class="text-gray-400 text-sm">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> Veloria Vault. All rights reserved.<?php $legal = veloria_legal(); if ( isset( $legal['legal_name'] ) ) { echo '<br>' . esc_html( 'Sold by ' . $legal['legal_name'] . ( isset( $legal['gstin'] ) ? ' · GSTIN ' . $legal['gstin'] : '' ) ); } ?></p>
				<button type="button" onclick="veloriaScrollTop()" class="flex items-center space-x-2 text-gray-400 hover:text-white transition-colors text-sm">
					<span>Back to top</span>
					<?php veloria_icon_e( 'arrow-up', 16 ); ?>
				</button>
			</div>
		</div>
	</div>
</footer>
