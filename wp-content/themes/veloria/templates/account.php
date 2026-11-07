<?php
/**
 * /account (src/app/account/page.tsx).
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

$tabs = array(
	array( 'orders', 'My Orders', 'package', "tab = 'orders'" ),
	array( 'profile', 'Profile Details', 'user', "tab = 'profile'" ),
	array( 'track', 'Track Order', 'truck', 'openTrackTab()' ),
	array( 'returns', 'Returns & Refunds', 'refresh-cw', "tab = 'returns'" ),
	array( 'support', 'Support', 'help-circle', "tab = 'support'" ),
);
$steps = array( array( 'package', 'Order Placed', 1 ), array( 'truck', 'Shipped', 2 ), array( 'map-pin', 'Out for Delivery', 3 ), array( 'check-circle-2', 'Delivered', 4 ) );

get_header();
?>
<div x-data="account()">
	<template x-if="loading">
		<div class="min-h-screen bg-[#faf8f5] flex items-center justify-center"><?php veloria_icon_e( 'loader-2', 24, 'w-8 h-8 animate-spin text-[#b59a5c]' ); ?></div>
	</template>

	<div x-show="!loading" x-cloak class="min-h-screen bg-[#faf8f5] pt-24 pb-16">
		<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
			<div class="mb-8">
				<h1 class="text-3xl font-serif text-gray-900 mb-2">My Account</h1>
				<p class="text-gray-500" x-text="'Welcome back, ' + greetingName"></p>
				<p x-show="error" class="mt-2 text-sm text-red-500" x-text="error"></p>
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
				<div class="lg:col-span-1">
					<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden sticky top-24">
						<div class="p-6 border-b border-gray-100 text-center flex flex-col items-center">
							<div class="w-20 h-20 bg-[#fbf9f4] rounded-full flex items-center justify-center mb-4 border border-[#eee7d5]"><span class="text-3xl font-serif text-[#b59a5c]" x-text="initial"></span></div>
							<h2 class="font-medium text-gray-900 text-lg break-all" x-text="displayName"></h2>
							<p class="text-sm text-gray-500 break-all" x-text="displayEmail"></p>
						</div>
						<nav class="p-3 space-y-1">
							<?php foreach ( $tabs as list( $key, $label, $icon, $action ) ) : ?>
								<button type="button" @click="<?php echo esc_attr( $action ); ?>" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition-colors" :class="tab === '<?php echo esc_js( $key ); ?>' ? 'bg-[#fbf9f4] text-[#b59a5c]' : 'text-gray-600 hover:bg-gray-50'">
									<?php veloria_icon_e( $icon, 18 ); ?><?php echo esc_html( $label ); ?>
								</button>
							<?php endforeach; ?>
							<div class="pt-2 mt-2 border-t border-gray-100">
								<button type="button" @click="logout()" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors"><?php veloria_icon_e( 'log-out', 18 ); ?>Logout</button>
							</div>
						</nav>
					</div>
				</div>

				<div class="lg:col-span-3">
					<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 lg:p-8 min-h-[500px]">

						<div x-show="tab === 'orders'" class="animate-fadeIn">
							<div class="flex items-center justify-between mb-8"><h2 class="text-xl font-serif text-gray-900 border-b-2 border-[#b59a5c] pb-1 inline-block">Order History</h2></div>
							<p class="mb-6 text-sm text-gray-600">Within 48 hours of order placement, eligible cancellations proceed after courier checks; staff review may be needed. Later requests need staff approval before dispatch. Online refunds go to the original payment method; your provider or bank determines when they appear. Unpaid COD has no refund.</p>
							<template x-if="orders.length === 0">
								<div class="text-center py-16 bg-gray-50 border border-gray-100 rounded-xl">
									<?php veloria_icon_e( 'package', 24, 'w-16 h-16 text-gray-300 mx-auto mb-4' ); ?>
									<h3 class="font-serif text-lg text-gray-800 mb-2">No orders found</h3>
									<p class="text-gray-500 mb-6 max-w-sm mx-auto">When you place an order, it will appear here along with its status.</p>
									<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="inline-block bg-[#1a1a1a] text-white px-8 py-3 text-sm font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-colors rounded">Start Shopping</a>
								</div>
							</template>
							<div x-show="orders.length > 0" class="space-y-6">
								<template x-for="order in orders" :key="order.id">
									<div class="border border-gray-100 rounded-xl p-5 hover:shadow-md transition-shadow relative overflow-hidden">
										<div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 pb-4 border-b border-gray-50">
											<div class="mb-2 sm:mb-0">
												<p class="text-sm text-gray-500 mb-1">Placed on <span class="font-medium text-gray-800" x-text="dateLong(order.dateCreated)"></span></p>
												<span class="text-base font-bold text-gray-900" x-text="'Order #' + order.number"></span>
											</div>
											<div class="flex flex-col sm:items-end gap-2">
												<span class="px-3 py-1 rounded text-xs font-bold tracking-wider uppercase border" :class="statusColor(order.status)" x-text="order.status"></span>
												<span class="font-bold text-gray-900 text-lg" x-text="inr(order.codDue || order.amountPaid)"></span>
												<span x-show="order.paymentMethod" class="text-[11px] text-gray-400" x-text="(order.isCod ? 'Pay on delivery: ' : (order.amountCaptured ? 'Paid via ' : 'Payment method: ')) + order.paymentMethod"></span>
											</div>
										</div>
										<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
											<div class="min-w-0">
												<p class="text-sm text-gray-600 break-words" x-text="'Includes: ' + order.lineItems.map(i => i.name).join(', ')"></p>
												<p x-show="order.refundProgress" class="mt-2 text-xs text-amber-800" role="status" x-text="order.refundProgress"></p>
											</div>
											<div class="flex flex-wrap gap-x-5 gap-y-2">
												<button type="button" @click="track(order)" class="text-xs font-bold text-[#b59a5c] uppercase tracking-wider hover:text-black flex items-center gap-1 min-h-[44px]"><?php veloria_icon_e( 'truck', 14 ); ?> Track</button>
												<button type="button" @click="invoice(order)" :disabled="invoiceId === order.id" class="text-xs font-bold text-gray-500 uppercase tracking-wider hover:text-black flex items-center gap-1 min-h-[44px] disabled:opacity-50"><?php veloria_icon_e( 'file-text', 14 ); ?> Invoice</button>
												<a x-show="order.canReturn && !ordersStateStale" :href="returnUrl(order)" class="text-xs font-bold text-gray-500 uppercase tracking-wider hover:text-red-500 flex items-center gap-1 min-h-[44px]"><?php veloria_icon_e( 'refresh-cw', 14 ); ?> Request Return</a>
												<button type="button" x-show="order.canCancel" @click="cancel(order)" :disabled="cancellingId === order.id || ordersStateStale" class="text-xs font-bold text-gray-500 uppercase tracking-wider hover:text-red-600 flex items-center gap-1 min-h-[44px] disabled:opacity-50">
													<span x-show="cancellingId === order.id"><?php veloria_icon_e( 'loader-2', 14, 'animate-spin' ); ?></span>
													<span x-show="cancellingId !== order.id"><?php veloria_icon_e( 'refresh-cw', 14 ); ?></span>
													<span x-text="cancellationLabel(order)">Cancel order</span>
												</button>
											</div>
										</div>
									</div>
								</template>
							</div>
						</div>

						<div x-show="tab === 'profile'" x-cloak class="animate-fadeIn">
							<h2 class="text-xl font-serif text-gray-900 border-b-2 border-[#b59a5c] pb-1 mb-8 inline-block">Profile Details</h2>
							<template x-if="customer">
								<div class="max-w-md space-y-6">
									<div class="grid grid-cols-2 gap-6">
										<div><label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">First Name</label><div class="p-3 bg-gray-50 border border-gray-200 rounded text-gray-800" x-text="customer.firstName"></div></div>
										<div><label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Last Name</label><div class="p-3 bg-gray-50 border border-gray-200 rounded text-gray-800" x-text="customer.lastName"></div></div>
									</div>
									<div><label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email Address</label><div class="p-3 bg-gray-50 border border-gray-200 rounded text-gray-800" x-text="customer.email"></div></div>
								</div>
							</template>
							<template x-if="!customer">
								<div class="max-w-md space-y-4">
									<div><label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email Address</label><div class="p-3 bg-gray-50 border border-gray-200 rounded text-gray-800 break-all" x-text="sessionEmail"></div></div>
									<p class="text-sm text-gray-500">You&apos;re signed in with a one-time email code. Create a password-protected account to save your addresses and manage your profile.</p>
									<a href="<?php echo esc_url( home_url( '/register' ) ); ?>" class="inline-block bg-black text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-colors rounded">Create an Account</a>
								</div>
							</template>
						</div>

						<div x-show="tab === 'track'" x-cloak class="animate-fadeIn">
							<div class="flex items-center justify-between mb-8"><h2 class="text-xl font-serif text-gray-900 border-b-2 border-[#b59a5c] pb-1 inline-block">Track Package</h2></div>
							<template x-if="!trackingOrder">
								<div class="text-center py-16 bg-gray-50 border border-gray-100 rounded-xl">
									<?php veloria_icon_e( 'truck', 24, 'w-16 h-16 text-gray-300 mx-auto mb-4' ); ?>
									<h3 class="font-serif text-lg text-gray-800 mb-2">No active orders to track</h3>
									<p class="text-gray-500 mb-6 max-w-sm mx-auto">Place an order first to track its journey to you.</p>
									<button type="button" @click="tab = 'orders'" class="bg-[#1a1a1a] text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-colors rounded">View Order History</button>
								</div>
							</template>
							<template x-if="trackingOrder">
								<div class="relative">
									<div x-show="orders.length > 1" class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100 min-w-0">
										<span class="text-sm font-medium text-gray-600">Currently Tracking:</span>
										<select aria-label="Order to track" class="w-full sm:w-auto min-w-0 max-w-full bg-white border border-gray-200 text-sm font-bold text-gray-900 rounded p-2 focus:ring-[#b59a5c] focus:outline-none" :value="trackingOrder.id" @change="selectTracked($event.target.value)">
											<template x-for="o in orders" :key="o.id"><option :value="o.id" :selected="o.id === trackingOrder.id" x-text="'Order #' + o.number + ' - ' + dateShort(o.dateCreated)"></option></template>
										</select>
									</div>
									<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative min-h-[300px]">
										<template x-if="searching">
											<div class="absolute inset-0 bg-white z-20 flex flex-col items-center justify-center p-8">
												<div class="w-full max-w-sm px-8 relative h-20 flex items-center">
													<div class="absolute top-1/2 left-8 right-8 h-[2px] bg-gray-200 -translate-y-1/2 rounded-full overflow-hidden"><div class="w-full h-full bg-[#b59a5c] origin-left animate-[veloria-progress_2.5s_linear_forwards]"></div></div>
													<div class="absolute z-10 text-[#1a1a1a] drop-shadow-lg -translate-x-1/2 animate-[veloria-truck_2.5s_ease-in-out_forwards]" style="left:2rem"><?php veloria_icon_e( 'truck', 40, 'fill-white', array( 'stroke-width' => 1.5 ) ); ?></div>
												</div>
												<p class="mt-4 font-serif text-lg text-gray-800 animate-pulse">Contacting logistics partner...</p>
											</div>
										</template>
										<template x-if="!searching">
											<div class="h-full animate-fadeIn">
												<div class="bg-[#fbfcfa] border-b border-gray-100 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
													<div>
														<p class="text-sm text-gray-500 mb-1" x-text="'Order #' + trackingOrder.number"></p>
														<h2 class="text-2xl font-serif text-gray-900 flex items-center gap-2 capitalize">
															<span x-text="liveTracking && !liveTracking.fallback ? liveTracking.status : trackingOrder.status"></span>
															<span x-show="trackingOrder.status === 'completed' || ((liveTracking && liveTracking.status) || '').toLowerCase() === 'delivered'" class="text-green-500"><?php veloria_icon_e( 'check-circle-2', 24 ); ?></span>
														</h2>
														<p x-show="liveTracking && liveTracking.awb" class="text-xs text-gray-400 mt-1" x-text="'AWB: ' + (liveTracking ? liveTracking.awb : '')"></p>
													</div>
													<div class="bg-white px-5 py-3 rounded-lg border border-gray-100 shadow-sm text-center md:text-right">
														<p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Expected Delivery</p>
														<p class="font-bold text-[#b59a5c] text-lg" x-text="liveTracking && liveTracking.etaDate ? dateShort(liveTracking.etaDate) : 'Calculating…'"></p>
													</div>
												</div>
												<div class="p-6 md:p-8">
													<div class="relative max-w-2xl mx-auto mb-8 mt-4">
														<div aria-hidden="true" class="hidden sm:block absolute top-5 left-8 right-8 h-1 bg-gray-100 rounded-full"><div class="h-full bg-[#b59a5c] rounded-full transition-all duration-1000" :style="'width:' + ((statusStep(trackingOrder.status) - 1) / 3 * 100) + '%'"></div></div>
														<div class="relative grid grid-cols-2 sm:grid-cols-4 gap-x-2 gap-y-5">
															<?php foreach ( $steps as list( $icon, $label, $n ) ) : ?>
																<div class="flex flex-col items-center">
																	<div class="w-10 h-10 rounded-full flex items-center justify-center border-4 border-white shadow-sm z-10 transition-colors duration-500" :class="statusStep(trackingOrder.status) >= <?php echo (int) $n; ?> ? 'bg-[#b59a5c] text-white' : 'bg-gray-200 text-gray-400'"><?php veloria_icon_e( $icon, 18 ); ?></div>
																<p class="mt-3 text-xs font-bold uppercase tracking-wider text-center w-full max-w-20 break-words leading-tight" :class="statusStep(trackingOrder.status) >= <?php echo (int) $n; ?> ? 'text-gray-900' : 'text-gray-400'"><?php echo esc_html( $label ); ?></p>
																</div>
															<?php endforeach; ?>
														</div>
													</div>
													<template x-if="liveTracking && !liveTracking.fallback && liveTracking.checkpoints.length > 0">
														<div class="mt-4 border-t border-gray-100 pt-6">
															<h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Shipment Activity</h4>
															<ol class="space-y-4">
																<template x-for="(c, i) in liveTracking.checkpoints" :key="i">
																	<li class="flex gap-3">
																		<div class="mt-1 w-2 h-2 rounded-full flex-shrink-0" :class="i === 0 ? 'bg-[#b59a5c]' : 'bg-gray-300'"></div>
																		<div><p class="text-sm text-gray-800" x-text="c.activity"></p><p class="text-xs text-gray-400" x-text="(c.location ? c.location + ' · ' : '') + c.date"></p></div>
																	</li>
																</template>
															</ol>
														</div>
													</template>
													<p x-show="liveTracking && liveTracking.fallback" class="mt-4 text-xs text-gray-400 text-center">Live courier tracking will appear here once your order ships.</p>
												</div>
											</div>
										</template>
									</div>
								</div>
							</template>
						</div>

						<div x-show="tab === 'returns'" x-cloak class="animate-fadeIn">
							<div class="flex items-center justify-between mb-8"><h2 class="text-xl font-serif text-gray-900 border-b-2 border-[#b59a5c] pb-1 inline-block">Returns &amp; Refunds</h2></div>
							<div class="bg-[#fcf8e8] border border-[#f0e6c8] p-4 rounded-lg mb-8">
								<h3 class="font-bold text-[#8a6d3b] mb-1">Our Return Policy</h3>
								<p class="text-sm text-[#8a6d3b]/80">You can request a return within 7 days of verified delivery. The product must be unused and in original packaging. Refund approval follows receipt and inspection.</p>
							</div>
							<p x-show="ordersStateStale" role="status" class="mb-4 text-sm text-red-700">Return details could not be refreshed. Reload your orders to see the latest status.</p>
							<template x-for="o in returnOrders" :key="o.id">
								<article class="mb-4 p-5 bg-white border border-gray-200 rounded-xl break-words">
									<p class="font-bold text-gray-900" x-text="'Order #' + o.number"></p>
									<p class="mt-2 text-sm font-semibold" x-text="returnStatus(o)"></p>
									<p x-show="o.returnRequestedAt" class="mt-2 text-sm text-gray-600" x-text="o.returnRequestedAt ? 'Requested: ' + dateLong(o.returnRequestedAt) : ''"></p>
									<p x-show="o.returnRequestReason" class="mt-2 text-sm text-gray-600" x-text="o.returnRequestReason"></p>
									<p x-show="o.refundProgress" class="mt-2 text-sm text-gray-700" x-text="o.refundProgress"></p>
									<p x-show="Number(o.amountRefunded) > 0" class="mt-2 text-sm text-gray-700" x-text="'Refund recorded: ' + inr(o.amountRefunded)"></p>
								</article>
							</template>
							<div x-show="ordersLoaded && !ordersStateStale && returnOrders.length === 0" class="text-center py-16 bg-gray-50 border border-gray-100 rounded-xl">
								<?php veloria_icon_e( 'refresh-cw', 24, 'w-16 h-16 text-gray-300 mx-auto mb-4' ); ?>
								<h3 class="font-serif text-lg text-gray-800 mb-2">No Active Returns</h3>
								<p class="text-gray-500 mb-6 max-w-sm mx-auto">If you have recently submitted a return ticket, its status will be displayed here.</p>
							</div>
						</div>

						<div x-show="tab === 'support'" x-cloak class="animate-fadeIn">
							<h2 class="text-xl font-serif text-gray-900 border-b-2 border-[#b59a5c] pb-1 mb-8 inline-block">Support Helpdesk</h2>
							<div class="space-y-4">
								<a href="<?php echo esc_url( home_url( '/contact-us' ) ); ?>" class="block p-6 border border-gray-200 rounded-xl hover:border-[#b59a5c] transition-colors group">
									<h3 class="font-bold text-gray-900 group-hover:text-[#b59a5c] mb-2 flex items-center justify-between">Contact Us<?php veloria_icon_e( 'chevron-right', 16 ); ?></h3>
									<p class="text-sm text-gray-500">Reach out directly via email or phone.</p>
								</a>
								<div class="block p-6 border border-gray-200 rounded-xl hover:border-[#b59a5c] transition-colors group cursor-pointer">
									<h3 class="font-bold text-gray-900 group-hover:text-[#b59a5c] mb-2 flex items-center justify-between">FAQ<?php veloria_icon_e( 'chevron-right', 16 ); ?></h3>
									<p class="text-sm text-gray-500">Read our frequently asked questions about shipping, care, and quality.</p>
								</div>
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
get_footer();
