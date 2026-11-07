<?php
/**
 * Template Name: Contact
 *
 * src/app/contact-us/page.tsx (the form now actually emails support).
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

if ( veloria_elementor_render_page() ) { return; }

$email = (string) veloria_setting( 'support_email', 'care@veloriavault.com' );
$phone = (string) veloria_setting( 'support_phone', '+91-7376326666' );
$tel   = '+' . preg_replace( '/\D/', '', $phone );
$input = 'w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-[#b59a5c] transition-colors';

get_header();
?>
<div class="min-h-screen bg-white">
	<div class="relative bg-[#1a1a1a] h-[300px] md:h-[400px] overflow-hidden">
		<div class="absolute inset-0">
			<img src="<?php echo esc_url( veloria_sized_asset_url( 'images/covers/contact us.png', '1536x1536' ) ); ?>" srcset="<?php echo esc_attr( veloria_theme_srcset( 'images/covers/contact us.png' ) ); ?>" sizes="100vw" alt="" class="w-full h-full object-cover object-center lg:object-[center_40%]" style="opacity: 0.6" fetchpriority="high">
			<div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-black/30"></div>
		</div>
		<div class="absolute inset-0 flex flex-col items-center justify-center z-10 px-4">
			<h1 class="text-4xl md:text-5xl lg:text-6xl font-serif font-medium text-white text-center">CONTACT US</h1>
			<p class="text-gray-300 text-center mt-4 max-w-2xl mx-auto text-sm md:text-base">We&apos;d love to hear from you. Reach out for any questions, support, or just to say hello.</p>
		</div>
	</div>

	<section class="py-16 md:py-20 bg-white">
		<div class="container mx-auto px-4 sm:px-6 lg:px-8">
			<div class="grid grid-cols-2 gap-4 md:gap-6 max-w-xl mx-auto">
				<div class="bg-[#fafafa] rounded-xl p-4 sm:p-6 text-center hover:shadow-lg transition-all duration-300 flex flex-col items-center">
					<div class="w-12 h-12 sm:w-14 sm:h-14 bg-[#1a1a1a] rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4"><?php veloria_icon_e( 'mail', 24, 'w-5 h-5 sm:w-6 sm:h-6 text-[#b59a5c]' ); ?></div>
					<h3 class="text-base sm:text-lg font-serif text-gray-900 mb-2 sm:mb-3">Email Us</h3>
					<a href="mailto:<?php echo esc_attr( $email ); ?>" class="text-sm sm:text-base text-gray-600 hover:text-[#b59a5c] transition-colors break-all"><?php echo esc_html( $email ); ?></a>
				</div>
				<div class="bg-[#fafafa] rounded-xl p-4 sm:p-6 text-center hover:shadow-lg transition-all duration-300 flex flex-col items-center">
					<div class="w-12 h-12 sm:w-14 sm:h-14 bg-[#1a1a1a] rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4"><?php veloria_icon_e( 'phone', 24, 'w-5 h-5 sm:w-6 sm:h-6 text-[#b59a5c]' ); ?></div>
					<h3 class="text-base sm:text-lg font-serif text-gray-900 mb-2 sm:mb-3">Call Us</h3>
					<a href="tel:<?php echo esc_attr( $tel ); ?>" class="inline-flex items-center justify-center px-6 py-2.5 bg-black text-white text-[10px] sm:text-xs font-bold tracking-[0.15em] uppercase rounded-full hover:bg-[#b59a5c] transition-all duration-300">Call Now</a>
				</div>
			</div>
		</div>
	</section>

	<section class="py-16 md:py-24 bg-[#fafafa]">
		<div class="container mx-auto px-4 sm:px-6 lg:px-8">
			<div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-start">
				<div class="bg-white rounded-lg p-8 md:p-10 shadow-sm" x-data="contact()">
					<h2 class="text-2xl md:text-3xl font-serif text-gray-900 mb-2">Send Us a Message</h2>
					<p class="text-gray-500 mb-8">Fill out the form below and we&apos;ll get back to you within 24 hours.</p>
					<template x-if="submitted">
						<div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
							<div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4"><?php veloria_icon_e( 'send', 24, 'w-6 h-6 text-green-600' ); ?></div>
							<h3 class="text-lg font-medium text-green-800 mb-2">Message Sent!</h3>
							<p class="text-green-600 mb-4">Thank you for reaching out. We&apos;ll get back to you soon.</p>
							<button type="button" @click="submitted = false" class="text-[#b59a5c] hover:text-[#a08a4f] font-medium">Send another message</button>
						</div>
					</template>
					<form x-show="!submitted" @submit.prevent="submit()" class="space-y-6">
						<div class="grid sm:grid-cols-2 gap-6">
							<div><label for="name" class="block text-sm font-medium text-gray-700 mb-2">Your Name</label><input type="text" id="name" required x-model="form.name" class="<?php echo esc_attr( $input ); ?>" placeholder="John Doe"></div>
							<div><label for="email" class="block text-sm font-medium text-gray-700 mb-2">Your Email</label><input type="email" id="email" required x-model="form.email" class="<?php echo esc_attr( $input ); ?>" placeholder="john@example.com"></div>
						</div>
						<div>
							<label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
							<select id="subject" required x-model="form.subject" class="<?php echo esc_attr( $input ); ?>">
								<option value="">Select a subject</option>
								<option value="general">General Inquiry</option>
								<option value="order">Order Status</option>
								<option value="return">Returns &amp; Exchanges</option>
								<option value="wholesale">Wholesale Inquiry</option>
								<option value="other">Other</option>
							</select>
						</div>
						<div><label for="message" class="block text-sm font-medium text-gray-700 mb-2">Your Message</label><textarea id="message" required rows="5" x-model="form.message" class="<?php echo esc_attr( $input ); ?> resize-none" placeholder="How can we help you?"></textarea></div>
						<p x-show="error" x-cloak class="text-sm text-red-600" x-text="error"></p>
						<button type="submit" :disabled="submitting" class="w-full py-4 bg-black text-white font-medium tracking-wider uppercase rounded-lg hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" x-text="submitting ? 'Sending...' : 'Send Message'">Send Message</button>
					</form>
				</div>
				<div class="space-y-8">
					<div class="relative aspect-[4/3] rounded-lg overflow-hidden">
						<img src="<?php echo esc_url( veloria_sized_asset_url( 'images/covers/about.png', 'medium_large' ) ); ?>" alt="Veloria Vault Collection" sizes="(min-width: 1024px) 40vw, 100vw" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
					</div>
					<div class="bg-[#1a1a1a] text-white rounded-lg p-8">
						<h3 class="text-xl font-serif mb-4">Why Choose Veloria Vault?</h3>
						<ul class="space-y-3 text-gray-300">
						<?php foreach ( array( 'Premium genuine leather craftsmanship', 'Hassle-free replacement policy', veloria_shipping_promise(), 'Secure checkout & customer support' ) as $point ) : ?>
								<li class="flex items-start gap-3"><span class="text-[#b59a5c] mt-1">✓</span><span><?php echo esc_html( $point ); ?></span></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php $legal = veloria_legal(); ?>
					<?php if ( isset( $legal['legal_name'] ) || isset( $legal['grievance_name'] ) ) : ?>
						<div class="border border-gray-200 rounded-lg p-8 text-sm text-gray-600 space-y-3">
							<?php if ( isset( $legal['legal_name'] ) ) : ?>
								<h3 class="text-lg font-serif text-gray-900">Seller information</h3>
								<p><?php echo esc_html( $legal['legal_name'] ); ?><?php echo isset( $legal['legal_address'] ) ? '<br>' . esc_html( $legal['legal_address'] ) : ''; ?></p>
								<?php if ( isset( $legal['gstin'] ) ) : ?><p>GSTIN: <?php echo esc_html( $legal['gstin'] ); ?></p><?php endif; ?>
							<?php endif; ?>
							<?php if ( isset( $legal['grievance_name'] ) ) : ?>
								<h3 class="text-lg font-serif text-gray-900 pt-2">Grievance officer</h3>
								<p><?php echo esc_html( $legal['grievance_name'] . ( isset( $legal['grievance_designation'] ) ? ', ' . $legal['grievance_designation'] : '' ) ); ?></p>
								<?php if ( isset( $legal['grievance_email'] ) ) : ?><p>Email: <a class="underline" href="mailto:<?php echo esc_attr( $legal['grievance_email'] ); ?>"><?php echo esc_html( $legal['grievance_email'] ); ?></a></p><?php endif; ?>
								<?php if ( isset( $legal['grievance_phone'] ) ) : ?><p>Phone: <?php echo esc_html( $legal['grievance_phone'] ); ?></p><?php endif; ?>
								<p class="text-xs text-gray-500">Complaints are acknowledged within 48 hours and resolved within one month.</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
</div>
<?php
get_footer();
