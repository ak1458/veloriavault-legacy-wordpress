<?php
/**
 * Template Name: Track order
 *
 * src/app/track-order/page.tsx — guest tracking with cancel/return/invoice.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

$email = (string) veloria_setting( 'support_email', 'care@veloriavault.com' );
$phone = (string) veloria_setting( 'support_phone', '+91-7376326666' );
$steps = array( array( 'package', 'Order Placed' ), array( 'loader-2', 'Processing' ), array( 'truck', 'Shipped' ), array( 'map-pin', 'Out for Delivery' ), array( 'check-circle-2', 'Delivered' ) );
$input = 'w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-[#b59a5c] transition-colors';


?>
<div class="min-h-screen bg-[#faf8f5] pt-24 pb-16" x-data="trackOrder()">
	<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center mb-12">
			<?php if ( empty( $args['hide_heading'] ) ) : ?><h1 class="text-3xl md:text-4xl font-serif font-medium text-gray-900 mb-4">Track Your Order</h1><?php endif; ?>
			<p class="text-gray-600 max-w-lg mx-auto">Sign in for account orders, or verify your guest order email with a one-time code.</p>
		</div>

		<div x-show="!order" class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm max-w-md mx-auto animate-slideUp">
			<form @submit.prevent="track()" class="space-y-6">
				<div>
					<label for="track-order-id" class="block text-xs font-bold text-gray-500 uppercase mb-2">Order Number *</label>
					<input id="track-order-id" type="text" x-model="orderId" placeholder="e.g., 9452" class="<?php echo esc_attr( $input ); ?>" required>
					<p class="text-xs text-gray-500 mt-1">Found in your order confirmation email</p>
				</div>
				<div>
					<label for="track-email" class="block text-xs font-bold text-gray-500 uppercase mb-2">Email Address *</label>
					<input id="track-email" type="email" x-model="email" @input="differentEmail()" placeholder="email@example.com" class="<?php echo esc_attr( $input ); ?>" required>
					<p class="text-xs text-gray-500 mt-1">The email used when placing the order</p>
				</div>
				<div class="space-y-3 border-t border-gray-100 pt-4">
					<button type="button" @click="requestCode()" :disabled="verifying || loading || !email || !orderId" class="text-sm font-medium text-[#b59a5c] disabled:opacity-50" x-text="otpStep === 'code' ? 'Resend guest email code' : 'Verify guest order email'"></button>
					<p x-show="info" x-cloak class="text-sm text-gray-600" x-text="info"></p>
					<div x-show="otpStep === 'code'" x-cloak class="space-y-3">
						<label for="track-code" class="block text-xs font-bold text-gray-500 uppercase">6-digit email code</label>
						<input id="track-code" type="text" x-model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" class="<?php echo esc_attr( $input ); ?>">
						<button type="button" @click="verifyCode()" :disabled="verifying || code.length !== 6" class="w-full rounded-lg border border-[#b59a5c] py-3 text-sm font-bold text-[#b59a5c] disabled:opacity-50">Verify &amp; Track</button>
					</div>
				</div>
				<div x-show="error" x-cloak class="flex items-center gap-2 p-4 bg-red-50 border border-red-200 rounded-lg">
					<?php veloria_icon_e( 'alert-circle', 24, 'w-5 h-5 text-red-500 flex-shrink-0' ); ?>
					<p class="text-sm text-red-600" x-text="error"></p>
				</div>
				<button type="submit" :disabled="loading" class="w-full flex items-center justify-center space-x-2 bg-[#1a1a1a] text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-colors disabled:opacity-50 rounded-lg">
					<span x-show="loading" class="flex items-center space-x-2"><?php veloria_icon_e( 'loader-2', 24, 'w-5 h-5 animate-spin' ); ?><span>Searching...</span></span>
					<span x-show="!loading" class="flex items-center space-x-2"><?php veloria_icon_e( 'search', 24, 'w-5 h-5' ); ?><span>Track Order</span></span>
				</button>
			</form>
			<div class="mt-6 pt-6 border-t border-gray-100 text-center">
				<a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="inline-flex flex-col items-center group">
					<span class="text-sm text-gray-500 mb-1 group-hover:text-gray-700">Have an account?</span>
					<span class="inline-flex items-center text-[#856b35] hover:text-[#6d5729] font-medium">View all your orders<?php veloria_icon_e( 'chevron-right', 24, 'w-4 h-4' ); ?></span>
				</a>
			</div>
		</div>

		<template x-if="order">
			<div class="space-y-6 animate-slideUp">
				<button type="button" @click="reset()" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">← Track another order</button>

				<div class="bg-white p-6 md:p-8 rounded-2xl border border-gray-100 shadow-sm">
					<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
						<div>
							<p class="text-sm text-gray-500 mb-1" x-text="'Order #' + order.number"></p>
							<h2 class="text-2xl font-serif font-medium text-gray-900 break-words [overflow-wrap:anywhere]" x-text="order.billing.firstName + ' ' + order.billing.lastName"></h2>
						</div>
						<span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium" :class="statusColor(order.status)" x-text="capitalize(order.status)"></span>
					</div>
					<div class="grid grid-cols-1 min-[400px]:grid-cols-2 md:grid-cols-4 gap-4 text-sm">
						<div class="min-w-0"><p class="text-gray-500 mb-1">Order Date</p><p class="font-medium" x-text="dateShort(order.dateCreated)"></p></div>
						<div class="min-w-0"><p class="text-gray-500 mb-1" x-text="order.codDue ? 'Due on Delivery' : (order.amountCaptured ? 'Amount Paid' : 'Order Total')">Amount Paid</p><p class="font-medium" x-text="inr(order.codDue || order.amountPaid)"></p></div>
						<div class="min-w-0"><p class="text-gray-500 mb-1">Email</p><p class="font-medium break-words [overflow-wrap:anywhere]" x-text="order.billing.email"></p></div>
						<div class="min-w-0"><p class="text-gray-500 mb-1">Phone</p><p class="font-medium break-words" x-text="order.billing.phone"></p></div>
					</div>
				</div>

				<div class="bg-white p-6 md:p-8 rounded-2xl border border-gray-100 shadow-sm">
					<h3 class="font-serif text-lg text-gray-900 mb-4">Manage Order</h3>
					<p x-show="order.refundProgress" class="mb-4 p-3 rounded-lg text-sm border bg-amber-50 border-amber-200 text-amber-800" role="status" x-text="order.refundProgress"></p>
					<p x-show="orderStateStale" role="status" class="mb-4 text-sm text-red-700">Could not refresh this order. Reload its details before another action.</p>
					<div x-show="actionMsg" class="mb-4 p-3 rounded-lg text-sm border" :class="actionMsg && actionMsg.type === 'ok' ? 'bg-green-50 border-green-200 text-green-700' : (actionMsg && actionMsg.type === 'info' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-red-50 border-red-200 text-red-600')" role="status" x-text="actionMsg ? actionMsg.text : ''"></div>
					<div class="flex flex-col sm:flex-row sm:flex-wrap gap-3">
						<button type="button" x-show="order.canCancel" @click="cancel()" :disabled="actionLoading || orderStateStale" class="inline-flex items-center justify-center gap-2 min-h-[48px] w-full sm:w-auto px-4 py-2.5 rounded-lg border border-red-200 text-red-600 text-sm font-bold uppercase tracking-wider hover:bg-red-50 transition-colors disabled:opacity-50">
							<span x-show="actionLoading"><?php veloria_icon_e( 'loader-2', 16, 'animate-spin' ); ?></span><span x-show="!actionLoading"><?php veloria_icon_e( 'xcircle', 16 ); ?></span>
							<span x-text="cancellationLabel(order)">Cancel order</span>
						</button>
						<button type="button" x-show="order.canReturn" @click="showReturn = !showReturn" :disabled="actionLoading || orderStateStale" class="inline-flex items-center justify-center gap-2 min-h-[48px] w-full sm:w-auto px-4 py-2.5 rounded-lg border border-gray-200 text-gray-700 text-sm font-bold uppercase tracking-wider hover:bg-gray-50 transition-colors disabled:opacity-50"><?php veloria_icon_e( 'refresh-cw', 16 ); ?> Request Return</button>
						<button type="button" @click="invoice()" :disabled="actionLoading" class="inline-flex items-center justify-center gap-2 min-h-[48px] w-full sm:w-auto px-4 py-2.5 rounded-lg border border-gray-200 text-gray-700 text-sm font-bold uppercase tracking-wider hover:bg-gray-50 transition-colors disabled:opacity-50"><?php veloria_icon_e( 'file-text', 16 ); ?> Download Invoice</button>
					</div>
					<form x-show="showReturn" @submit.prevent="submitReturn()" class="mt-5 border-t border-gray-100 pt-5 space-y-3">
						<label for="track-return-reason" class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Reason for return</label>
						<textarea id="track-return-reason" x-model="returnReason" rows="3" minlength="5" required placeholder="Tell us what went wrong (damaged, wrong item, not as described, etc.)" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-[#b59a5c] resize-none text-sm"></textarea>
						<button type="submit" :disabled="actionLoading || returnReason.trim().length < 5" class="inline-flex items-center gap-2 bg-[#1a1a1a] text-white px-5 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider hover:bg-[#b59a5c] transition-colors disabled:opacity-50">
							<span x-show="actionLoading"><?php veloria_icon_e( 'loader-2', 16, 'animate-spin' ); ?></span>Submit Return Request
						</button>
					</form>
					<p class="text-xs text-gray-600 mt-4">Within 48 hours of order placement, eligible cancellations proceed after courier checks; staff help may be needed. Later requests need approval before dispatch. Online refunds go to the original payment method; bank timing varies. Unpaid COD has no refund. Returns may be requested within 7 days of verified delivery and require inspection.</p>
				</div>

				<div class="bg-white p-6 md:p-8 rounded-2xl border border-gray-100 shadow-sm">
					<h3 class="font-serif text-lg text-gray-900 mb-6">Order Progress</h3>
					<div class="relative">
						<div class="absolute top-5 left-0 right-0 h-1 bg-gray-200 rounded-full hidden md:block"><div class="h-full bg-[#b59a5c] rounded-full transition-all duration-500" :style="'width:' + ((statusStep(order.status) - 1) / <?php echo (int) ( count( $steps ) - 1 ); ?> * 100) + '%'"></div></div>
						<div class="grid grid-cols-2 md:grid-cols-5 gap-4">
							<?php foreach ( $steps as $index => list( $icon, $label ) ) : ?>
								<div class="flex flex-col items-center text-center">
									<div class="w-10 h-10 rounded-full flex items-center justify-center mb-2 transition-colors" :class="<?php echo (int) $index; ?> < statusStep(order.status) ? 'bg-[#b59a5c] text-white' : 'bg-gray-200 text-gray-400'">
										<span :class="'<?php echo esc_js( $icon ); ?>' === 'loader-2' && <?php echo (int) $index; ?> === statusStep(order.status) - 1 ? 'animate-spin' : ''" class="inline-flex"><?php veloria_icon_e( $icon, 24, 'w-5 h-5' ); ?></span>
									</div>
									<p class="text-xs font-medium" :class="<?php echo (int) $index; ?> < statusStep(order.status) ? 'text-gray-900' : 'text-gray-400'"><?php echo esc_html( $label ); ?></p>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<div class="bg-white p-6 md:p-8 rounded-2xl border border-gray-100 shadow-sm">
					<h3 class="font-serif text-lg text-gray-900 mb-6">Order Items</h3>
					<div class="space-y-4">
						<template x-for="(item, index) in order.lineItems" :key="index">
							<div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
								<div class="w-16 h-16 bg-white rounded-lg flex items-center justify-center flex-shrink-0"><?php veloria_icon_e( 'package', 24, 'w-8 h-8 text-gray-400' ); ?></div>
								<div class="flex-1 min-w-0"><p class="font-medium text-gray-900 break-words" x-text="item.name"></p><p class="text-sm text-gray-500" x-text="'Qty: ' + item.quantity"></p></div>
								<p class="font-medium" x-text="inr(item.total)"></p>
							</div>
						</template>
					</div>
					<template x-if="order.shippingLines.length > 0">
						<div class="mt-6 pt-6 border-t border-gray-200"><div class="flex justify-between text-sm"><span class="text-gray-500" x-text="order.shippingLines[0].methodTitle"></span><span x-text="inr(order.shippingLines[0].total)"></span></div></div>
					</template>
					<div class="mt-4 pt-4 border-t border-gray-200">
						<div class="flex justify-between items-center"><span class="font-serif text-lg" x-text="order.codDue ? 'Due on Delivery' : (order.amountCaptured ? 'Amount Paid' : 'Order Total')">Order Total</span><span class="font-serif text-xl font-medium text-[#b59a5c]" x-text="inr(order.codDue || order.amountPaid)"></span></div>
						<p x-show="order.paymentMethod" class="text-xs text-gray-400 text-right mt-1" x-text="(order.isCod ? 'Pay on delivery: ' : (order.amountCaptured ? 'Paid via ' : 'Payment method: ')) + order.paymentMethod"></p>
						<p x-show="parseFloat(order.amountRefunded || 0) > 0" class="text-xs text-green-700 text-right mt-1" x-text="'Refunded: ' + inr(order.amountRefunded)"></p>
					</div>
				</div>

				<div class="bg-white p-6 md:p-8 rounded-2xl border border-gray-100 shadow-sm">
					<h3 class="font-serif text-lg text-gray-900 mb-4">Shipping Address</h3>
					<div class="text-sm text-gray-600 break-words [overflow-wrap:anywhere]">
						<p class="font-medium text-gray-900" x-text="order.shipping.firstName + ' ' + order.shipping.lastName"></p>
						<p x-text="order.shipping.address"></p>
						<p x-show="order.shipping.address2" x-text="order.shipping.address2"></p>
						<p x-text="order.shipping.city + ', ' + order.shipping.state + ' ' + order.shipping.postcode"></p>
						<p>India</p>
					</div>
				</div>

				<div class="bg-[#1a1a1a] text-white p-6 rounded-2xl">
					<h3 class="font-medium mb-2">Need Help?</h3>
					<p class="text-sm text-gray-300 mb-4">If you have any questions about your order, contact our support team.</p>
					<div class="flex flex-wrap gap-4">
						<a href="tel:<?php echo esc_attr( '+' . preg_replace( '/\D/', '', $phone ) ); ?>" class="text-sm text-[#b59a5c] hover:text-[#a08a4f]">Call: <?php echo esc_html( str_replace( '-', ' ', $phone ) ); ?></a>
						<span class="text-gray-600 hidden sm:inline">|</span>
						<a href="mailto:<?php echo esc_attr( $email ); ?>" class="text-sm text-[#b59a5c] hover:text-[#a08a4f]">Email: <?php echo esc_html( $email ); ?></a>
					</div>
				</div>
			</div>
		</template>
	</div>
</div>
<?php
