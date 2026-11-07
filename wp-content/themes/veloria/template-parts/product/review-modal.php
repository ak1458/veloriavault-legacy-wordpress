<?php
/**
 * WriteReviewModal.tsx
 *
 * @package Veloria
 *
 * @var array $args { product: array }
 */

defined( 'ABSPATH' ) || exit;

$product = $args['product'];
?>
<template x-if="modalOpen">
	<div class="fixed inset-0 z-[80] flex items-center justify-center bg-[rgba(18,15,11,0.7)] px-4 backdrop-blur-sm" x-data="reviewModal(<?php echo (int) $product['id']; ?>)">
		<div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl sm:p-8">
			<button type="button" @click="modalOpen = false" class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center text-[#9b917f] transition hover:text-[#1f1a13]" aria-label="Close"><?php veloria_icon_e( 'x', 20 ); ?></button>

			<template x-if="submitted">
				<div class="py-12 text-center">
					<div class="mb-4 text-4xl">✓</div>
					<h3 class="mb-2 font-serif text-xl text-[#1f1a13]">Thank you!</h3>
					<p class="text-[14px] text-[#7a7264]">Your review has been submitted and is pending approval.</p>
					<button type="button" @click="modalOpen = false" class="mt-6 bg-[#856b35] px-8 py-3 text-[13px] font-medium uppercase tracking-[0.04em] text-white transition hover:bg-[#a98a48]">Close</button>
				</div>
			</template>

			<template x-if="!submitted">
				<div>
					<h3 class="mb-1 font-serif text-xl text-[#1f1a13]">Write a Review</h3>
					<p class="mb-6 text-[13px] text-[#6f6658]">for <?php echo esc_html( $product['name'] ); ?></p>

					<div class="mb-5">
						<label class="mb-2 block text-[13px] font-medium text-[#1f1a13]">Your Rating <span class="text-red-400">*</span></label>
						<div class="flex items-center gap-1">
							<template x-for="star in [1,2,3,4,5]" :key="star">
								<button type="button" @click="rating = star" @mouseenter="hover = star" @mouseleave="hover = 0" class="p-0.5 transition" :aria-label="star + ' star'">
									<?php veloria_icon_e( 'star', 22, '', array( ':fill' => "star <= displayRating ? '#e0b31c' : 'none'", ':class' => "star <= displayRating ? 'text-[#e0b31c]' : 'text-[#d9cfbf]'" ) ); ?>
								</button>
							</template>
						</div>
					</div>

					<div class="mb-4 grid gap-4 sm:grid-cols-2">
						<div>
							<label for="review-name" class="mb-1.5 block text-[13px] font-medium text-[#1f1a13]">Name <span class="text-red-400">*</span></label>
							<input id="review-name" type="text" x-model="name" class="h-11 w-full border border-[#e0d9cc] bg-white px-3 text-[14px] text-[#1f1a13] outline-none transition focus:border-[#b89a57]" placeholder="Your name">
						</div>
						<div>
							<label for="review-email" class="mb-1.5 block text-[13px] font-medium text-[#1f1a13]">Email <span class="text-red-400">*</span></label>
							<input id="review-email" type="email" x-model="email" class="h-11 w-full border border-[#e0d9cc] bg-white px-3 text-[14px] text-[#1f1a13] outline-none transition focus:border-[#b89a57]" placeholder="Your email">
						</div>
					</div>

					<div class="mb-4">
						<label for="review-text" class="mb-1.5 block text-[13px] font-medium text-[#1f1a13]">Your Review <span class="text-red-400">*</span></label>
						<textarea id="review-text" x-model="review" rows="4" class="w-full border border-[#e0d9cc] bg-white p-3 text-[14px] text-[#1f1a13] outline-none transition focus:border-[#b89a57]" placeholder="Share your experience with this product..."></textarea>
					</div>
					<input type="text" x-model="website" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

					<div class="mb-6">
						<label class="mb-2 block text-[13px] font-medium text-[#1f1a13]">Photos (optional, max 5)</label>
						<div class="flex flex-wrap items-center gap-3">
							<template x-for="img in images" :key="img.preview">
								<div class="relative">
									<div class="h-16 w-16 overflow-hidden border border-[#e0d9cc]">
										<img :src="img.preview" alt="Upload preview" class="h-full w-full object-cover">
										<div x-show="img.uploading" class="absolute inset-0 flex items-center justify-center bg-white/70"><?php veloria_icon_e( 'loader-2', 16, 'animate-spin text-[#b89a57]' ); ?></div>
									</div>
									<button type="button" @click="removeImage(img.preview)" class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#1f1a13] text-white" aria-label="Remove image"><?php veloria_icon_e( 'trash-2', 10 ); ?></button>
								</div>
							</template>
							<button type="button" x-show="images.length < 5" @click="$refs.file.click()" class="flex h-16 w-16 items-center justify-center border-2 border-dashed border-[#dad3c5] text-[#6f6658] transition hover:border-[#b89a57] hover:text-[#b89a57]"><?php veloria_icon_e( 'image-plus', 20 ); ?></button>
							<input x-ref="file" type="file" accept="image/*" multiple class="hidden" @change="upload($event.target.files); $event.target.value = ''">
						</div>
					</div>

					<p x-show="error" class="mb-4 text-[13px] text-red-500" x-text="error"></p>

					<button type="button" @click="submit()" :disabled="submitting || !rating" class="w-full bg-[#856b35] py-3.5 text-[13px] font-medium uppercase tracking-[0.04em] text-white transition hover:bg-[#a98a48] disabled:cursor-not-allowed disabled:opacity-50">
						<span x-show="submitting" class="flex justify-center"><?php veloria_icon_e( 'loader-2', 16, 'mx-auto animate-spin' ); ?></span>
						<span x-show="!submitting">Submit Review</span>
					</button>
				</div>
			</template>
		</div>
	</div>
</template>
