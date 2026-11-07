<?php
/**
 * /account/return/{orderId} (src/app/account/return/[orderId]/page.tsx).
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

global $wp;
$order_id = absint( $wp->query_vars['return'] ?? 0 );

get_header();
?>
<div class="min-h-screen bg-[#fbfaf7] py-12 px-4 sm:px-6 lg:px-8 pt-24" x-data="returnForm(<?php echo (int) $order_id; ?>)">
	<div class="max-w-xl mx-auto">
		<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
			<template x-if="done">
				<div class="text-center py-6">
					<?php veloria_icon_e( 'check-circle-2', 24, 'w-14 h-14 text-green-500 mx-auto mb-4' ); ?>
					<h1 class="text-2xl font-serif mb-2">Return Request Submitted</h1>
					<p class="text-gray-600 mb-6">We&apos;ve received your request for order #<?php echo (int) $order_id; ?>. Our team will review it and email you the next steps within 1–2 business days.</p>
					<a href="<?php echo esc_url( home_url( '/account' ) ); ?>" class="inline-block bg-[#1a1a1a] text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-colors rounded">Back to My Account</a>
				</div>
			</template>
			<template x-if="!done">
				<div>
					<div class="flex items-center gap-2 mb-2"><?php veloria_icon_e( 'refresh-cw', 24, 'w-5 h-5 text-[#b59a5c]' ); ?><h1 class="text-2xl font-serif">Request a Return</h1></div>
					<p class="text-gray-500 text-sm mb-6">Order #<?php echo (int) $order_id; ?></p>
					<div class="bg-[#fcf8e8] border border-[#f0e6c8] p-4 rounded-lg mb-6"><p class="text-sm text-[#8a6d3b]/90">Returns can be requested within 7 days of delivery. The product must be unused and in original packaging.</p></div>
					<div x-show="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg"><p class="text-sm text-red-600" x-text="error"></p></div>
					<form @submit.prevent="submit()" class="space-y-4">
						<div>
							<label for="return-reason" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Reason for return</label>
							<textarea id="return-reason" x-model="reason" rows="4" minlength="5" required placeholder="Tell us what went wrong (wrong item, damaged, not as described, etc.)" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-[#b59a5c] resize-none"></textarea>
						</div>
						<button type="submit" :disabled="loading || reason.trim().length < 5" class="w-full bg-[#1a1a1a] text-white py-3 text-sm font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-colors disabled:opacity-50 rounded">
							<span x-show="loading"><?php veloria_icon_e( 'loader-2', 24, 'w-5 h-5 animate-spin mx-auto' ); ?></span>
							<span x-show="!loading">Submit Return Request</span>
						</button>
					</form>
					<p class="text-xs text-gray-400 mt-4 text-center">Need help? Email <a class="underline" href="mailto:<?php echo esc_attr( (string) veloria_setting( 'support_email' ) ); ?>"><?php echo esc_html( (string) veloria_setting( 'support_email' ) ); ?></a></p>
				</div>
			</template>
		</div>
	</div>
</div>
<?php
get_footer();
