<?php
/**
 * CustomerReviewsSection.tsx — auto-sliding testimonials.
 *
 * @package Veloria
 *
 * @var array $args { reviews: array }
 */

defined( 'ABSPATH' ) || exit;

$reviews = $args['reviews'] ?: array(
	array( 'id' => 0, 'reviewer' => 'Veloria Customer', 'rating' => 5, 'review' => 'Luxurious quality and exceptional craftsmanship. Experience the vault.' ),
);
$count = count( $reviews );
$image = veloria_sized_asset_url( '/wp-content/uploads/2026/01/Bag-3-5-scaled.jpg', 'large', 'images/covers/about.png' );
?>
<section class="bg-white py-14 sm:py-16 lg:py-24" x-data="reviewsSlider(<?php echo (int) $count; ?>)">
	<div class="container mx-auto px-4 sm:px-6 lg:px-8">
		<div class="grid grid-cols-1 items-center gap-8 lg:grid-cols-2 lg:gap-16">
			<div class="order-2 flex flex-col justify-center min-h-[260px] sm:min-h-[320px] lg:order-1 lg:min-h-[350px]">
				<p class="text-[11px] font-semibold tracking-[0.2em] text-[#856b35] uppercase mb-3">What Our Customers Say</p>
				<h2 class="mb-5 font-serif text-[2rem] leading-tight text-gray-900 sm:text-4xl">Quiet luxury, built to last.</h2>
				<div class="flex min-h-[150px] flex-col justify-start sm:min-h-[170px] lg:min-h-[140px]">
					<?php foreach ( $reviews as $i => $review ) : ?>
						<div x-show="index === <?php echo (int) $i; ?>"<?php echo $i > 0 ? ' x-cloak' : ''; ?> x-transition.opacity.duration.500ms>
							<blockquote class="mb-5 border-l-4 border-[#b59a5c] pl-4 text-base leading-relaxed text-gray-600 italic transition-opacity duration-500 sm:pl-6 sm:text-lg lg:text-xl">&ldquo;<?php echo wp_kses_post( $review['review'] ); ?>&rdquo;</blockquote>
							<div class="flex flex-wrap items-center gap-2 sm:gap-3">
								<p class="text-sm font-medium text-gray-900"><?php echo esc_html( $review['reviewer'] ); ?></p>
								<span class="text-[#b59a5c]">|</span>
								<div class="flex items-center gap-0.5">
									<?php for ( $s = 1; $s <= 5; $s++ ) : ?>
										<?php veloria_icon_e( 'star', 24, $s <= (int) $review['rating'] ? 'w-4 h-4 fill-[#b59a5c] text-[#b59a5c]' : 'w-4 h-4 fill-gray-200 text-gray-200' ); ?>
									<?php endfor; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="mt-6 flex items-center sm:mt-8">
					<?php for ( $i = 0; $i < $count; $i++ ) : ?>
						<?php // 24px tap target around the visible dot. ?>
						<button type="button" @click="index = <?php echo (int) $i; ?>" class="group flex h-6 min-w-6 items-center justify-center" aria-label="Go to review <?php echo (int) ( $i + 1 ); ?>">
							<span class="block h-1.5 rounded-full transition-all duration-300" :class="index === <?php echo (int) $i; ?> ? 'w-8 bg-[#b59a5c]' : 'w-1.5 bg-gray-300 group-hover:bg-gray-400'"></span>
						</button>
					<?php endfor; ?>
				</div>
			</div>
			<div class="order-1 lg:order-2">
				<div class="relative mx-auto aspect-[4/5] w-full max-w-[420px] overflow-hidden rounded-lg lg:max-w-none">
					<img src="<?php echo esc_url( $image ); ?>" alt="Veloria Vault customer review editorial" sizes="(min-width: 1024px) 40vw, 100vw" class="absolute inset-0 h-full w-full object-cover transition-opacity duration-500" loading="lazy">
				</div>
			</div>
		</div>
	</div>
</section>
