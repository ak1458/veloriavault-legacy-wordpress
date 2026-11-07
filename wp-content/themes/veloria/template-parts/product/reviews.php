<?php
/**
 * ProductReviewSection.tsx + WriteReviewModal.tsx.
 *
 * @package Veloria
 *
 * @var array $args { product: array, bundle: array }
 */

defined( 'ABSPATH' ) || exit;

$product = $args['product'];
$bundle  = $args['bundle'];
$reviews = $bundle['reviews'];
$count   = (int) $bundle['reviewCount'];
$average = (float) $bundle['averageRating'];

$stars = static function ( int $rating, int $size ): string {
	$out = '<div class="flex items-center gap-0.5">';
	for ( $s = 1; $s <= 5; $s++ ) {
		$out .= veloria_icon( 'star', $size, $s <= $rating ? 'text-[#e0b31c]' : 'text-[#d9cfbf]', array( 'fill' => $s <= $rating ? '#e0b31c' : 'none' ) );
	}
	return $out . '</div>';
};
?>
<section id="reviews" class="mx-auto max-w-[1320px] px-3 pb-10 sm:px-6 lg:px-8" x-data="reviewSection()">
	<div class="border-t border-[#e6dfd2] pt-10">
		<div class="mb-8 flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
			<h2 class="font-serif text-[1.5rem] text-[#1f1a13] sm:text-[1.8rem]">Customer Reviews</h2>
			<button type="button" @click="openModal()" class="shrink-0 border border-[#1f1a13] px-6 py-2.5 text-[13px] font-medium uppercase tracking-[0.04em] text-[#1f1a13] transition hover:bg-[#1f1a13] hover:text-white">Write a review</button>
		</div>

		<?php if ( $count > 0 ) : ?>
			<div class="mb-8 grid gap-8 sm:grid-cols-[auto_1fr_auto]">
				<div class="flex flex-col items-center gap-1">
					<span class="text-[2.5rem] font-semibold leading-none text-[#1f1a13]"><?php echo esc_html( number_format( $average, 1 ) ); ?></span>
					<?php echo $stars( (int) round( $average ), 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="mt-1 text-[13px] text-[#6f6658]"><?php echo (int) $count; ?> review<?php echo 1 !== $count ? 's' : ''; ?></span>
				</div>
				<div class="flex flex-col justify-center gap-1.5">
					<?php foreach ( array( 5, 4, 3, 2, 1 ) as $s ) : ?>
						<?php $pct = $count > 0 ? ( $bundle['histogram'][ $s ] / $count ) * 100 : 0; ?>
						<div class="flex items-center gap-2 text-[13px]">
							<span class="w-4 text-right text-[#5c564d]"><?php echo (int) $s; ?></span>
							<?php veloria_icon_e( 'star', 11, 'text-[#e0b31c]', array( 'fill' => '#e0b31c' ) ); ?>
							<div class="h-2 flex-1 overflow-hidden bg-[#f0ebe1]"><div class="h-full bg-[#856b35] transition-all duration-500" style="width: <?php echo esc_attr( (string) $pct ); ?>%"></div></div>
							<span class="w-7 text-right text-[#6f6658]"><?php echo (int) $bundle['histogram'][ $s ]; ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( $bundle['media'] ) : ?>
				<div class="mb-8">
					<div class="mb-3 flex items-center gap-2">
						<?php veloria_icon_e( 'camera', 14, 'text-[#b89a57]' ); ?>
						<span class="text-[13px] font-medium text-[#1f1a13]">Customer Photos</span>
					</div>
					<div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
						<?php foreach ( array_slice( $bundle['media'], 0, 20 ) as $media ) : ?>
							<button type="button" @click="lightbox = <?php echo veloria_json_attr( array( 'type' => $media['type'], 'url' => $media['url'], 'alt' => $media['alt'] ) ); ?>" class="relative h-20 w-20 shrink-0 overflow-hidden border border-[#e9e2d5] transition hover:border-[#b89a57]">
								<?php if ( 'image' === $media['type'] ) : ?>
									<img src="<?php echo esc_url( $media['thumbnail'] ?: $media['url'] ); ?>" alt="<?php echo esc_attr( $media['alt'] ); ?>" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
								<?php else : ?>
									<div class="flex h-full w-full items-center justify-center bg-[#f0ebe1] text-[10px] text-[#6f6658]">▶ Video</div>
								<?php endif; ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<div>
				<?php foreach ( $reviews as $i => $review ) : ?>
					<div class="border-b border-[#f0ebe1] py-5 last:border-0"<?php echo $i >= 3 ? ' x-show="showAll" x-cloak' : ''; ?>>
						<div class="mb-2 flex items-start justify-between gap-4">
							<div>
								<div class="mb-1 flex items-center gap-2">
									<div class="flex h-7 w-7 items-center justify-center rounded-full bg-[#f0ebe1] text-[12px] font-semibold text-[#5c564d]"><?php echo esc_html( strtoupper( mb_substr( $review['reviewer'], 0, 1 ) ) ); ?></div>
									<span class="text-[14px] font-medium text-[#1f1a13]"><?php echo esc_html( $review['reviewer'] ); ?></span>
									<?php if ( $review['verified'] ) : ?>
										<span class="text-[11px] font-medium text-green-600">✓ Verified</span>
									<?php endif; ?>
								</div>
								<?php echo $stars( (int) $review['rating'], 12 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<span class="shrink-0 text-[12px] text-[#6f6658]"><?php echo esc_html( $review['date_label'] ); ?></span>
						</div>
						<div class="text-[14px] leading-relaxed text-[#5c564d]"><?php echo wp_kses_post( $review['review'] ); ?></div>
						<?php if ( $review['media'] ) : ?>
							<div class="mt-3 flex gap-2">
								<?php foreach ( $review['media'] as $media ) : ?>
									<div class="relative h-16 w-16 overflow-hidden border border-[#e9e2d5]">
										<?php if ( 'image' === $media['type'] ) : ?>
											<img src="<?php echo esc_url( $media['thumbnail'] ?: $media['url'] ); ?>" alt="<?php echo esc_attr( $media['alt'] ); ?>" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
										<?php else : ?>
											<div class="flex h-full w-full items-center justify-center bg-[#f0ebe1] text-[9px] text-[#6f6658]">▶</div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( count( $reviews ) > 3 ) : ?>
				<button type="button" @click="showAll = !showAll" class="mt-4 flex items-center gap-1.5 text-[14px] font-medium text-[#b89a57] transition hover:text-[#8a6e31]">
					<span x-show="showAll" class="flex items-center gap-1.5" x-cloak>Show fewer <?php veloria_icon_e( 'chevron-up', 16 ); ?></span>
					<span x-show="!showAll" class="flex items-center gap-1.5">Show all <?php echo (int) count( $reviews ); ?> reviews <?php veloria_icon_e( 'chevron-down', 16 ); ?></span>
				</button>
			<?php endif; ?>
		<?php else : ?>
			<div class="py-8 text-center"><p class="mb-4 text-[14px] text-[#6f6658]">No reviews yet. Be the first to share your experience!</p></div>
		<?php endif; ?>
	</div>

	<template x-if="lightbox">
		<div class="fixed inset-0 z-[75] flex items-center justify-center bg-[rgba(18,15,11,0.9)] p-4" @click="lightbox = null">
			<div class="relative max-h-[85vh] max-w-[85vw]" @click.stop>
				<template x-if="lightbox.type === 'image'"><img :src="lightbox.url" :alt="lightbox.alt" class="max-h-[85vh] max-w-full object-contain"></template>
				<template x-if="lightbox.type !== 'image'"><video :src="lightbox.url" controls autoplay class="max-h-[85vh] max-w-full"></video></template>
				<button type="button" @click="lightbox = null" class="absolute -right-3 -top-3 flex h-8 w-8 items-center justify-center rounded-full bg-white text-[#1f1a13] shadow" aria-label="Close">✕</button>
			</div>
		</div>
	</template>

	<?php get_template_part( 'template-parts/product/review-modal', null, array( 'product' => $product ) ); ?>
</section>
