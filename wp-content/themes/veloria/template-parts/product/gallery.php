<?php
/**
 * Product gallery: desktop thumbnails rail + main image, mobile swipe
 * gallery (ProductDetails.tsx). Rendered by Alpine from the selected colour's
 * images; $args['initial'] is printed for the first paint.
 *
 * @package Veloria
 *
 * @var array $args { initial: array }
 */

defined( 'ABSPATH' ) || exit;

$first = $args['initial'][0] ?? null;
$many  = count( (array) $args['initial'] ) > 1;
// Server-rendered first photo: discovered by the browser before any JS runs
// (fast LCP) and hidden by Alpine once its own gallery has rendered.
$first_img = static function ( string $sizes ) use ( $first ): void {
	if ( ! $first ) {
		return;
	}
	printf(
		'<img src="%s"%s sizes="%s" alt="%s" class="absolute inset-0 h-full w-full object-contain" fetchpriority="high" x-show="false">',
		esc_url( $first['src'] ),
		! empty( $first['srcset'] ) ? ' srcset="' . esc_attr( $first['srcset'] ) . '"' : '',
		esc_attr( $sizes ),
		esc_attr( $first['alt'] ?? '' )
	);
};
?>
<div class="hidden lg:grid lg:grid-cols-[108px_minmax(0,1fr)] lg:gap-4">
	<div class="flex flex-col items-center gap-4" x-show="gallery.length > 1">
		<button type="button" @click="thumbsIndex = Math.max(0, resolvedThumbsIndex - 1)" :disabled="resolvedThumbsIndex === 0" class="text-[#9b917f] transition hover:text-[#241d14] disabled:cursor-not-allowed disabled:opacity-35" aria-label="Previous thumbnails"><?php veloria_icon_e( 'chevron-up', 18 ); ?></button>
		<div class="flex w-full flex-col gap-3">
			<template x-for="thumb in visibleThumbs" :key="thumb.index + '-' + thumb.image.src">
				<button type="button" @click="selectImage(thumb.index)" class="relative aspect-square overflow-hidden border border-[#e9e2d5] transition"
					:class="thumb.index === activeIndex ? 'opacity-100 shadow-[0_0_0_1px_#b7924e_inset]' : 'opacity-40 hover:opacity-100'" :aria-label="'Show image ' + (thumb.index + 1)">
					<img :src="thumb.image.thumbnail || thumb.image.src" :alt="thumb.image.alt" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
				</button>
			</template>
		</div>
		<button type="button" @click="thumbsIndex = Math.min(maxThumbIndex, resolvedThumbsIndex + 1)" :disabled="resolvedThumbsIndex >= maxThumbIndex" class="text-[#9b917f] transition hover:text-[#241d14] disabled:cursor-not-allowed disabled:opacity-35" aria-label="More thumbnails"><?php veloria_icon_e( 'chevron-down', 18 ); ?></button>
	</div>
	<div class="relative aspect-square overflow-hidden" :class="gallery.length > 1 ? '' : 'col-span-2'">
		<?php $first_img( '(min-width: 1280px) 760px, (min-width: 1024px) 58vw, 100vw' ); ?>
		<template x-if="currentImage">
			<button type="button" @click="zoomOpen = true" class="relative block h-full w-full cursor-zoom-in" aria-label="Open image preview">
				<img :src="currentImage.src" :srcset="currentImage.srcset || null" :alt="currentImage.alt" sizes="(min-width: 1280px) 760px, (min-width: 1024px) 58vw, 100vw" class="absolute inset-0 h-full w-full object-contain" fetchpriority="high">
				<span class="absolute bottom-4 left-4 flex h-14 w-14 items-center justify-center rounded-full bg-white text-[#6f6143] shadow-[0_12px_25px_rgba(0,0,0,0.08)]"><?php veloria_icon_e( 'maximize-2', 18 ); ?></span>
			</button>
		</template>
		<?php if ( $first ) : ?>
			<noscript><img src="<?php echo esc_url( $first['src'] ); ?>" alt="<?php echo esc_attr( $first['alt'] ); ?>" class="absolute inset-0 h-full w-full object-contain"></noscript>
		<?php endif; ?>
		<div x-show="!currentImage" class="aspect-square bg-[#f1ece3]"></div>
	</div>
</div>

<div class="lg:hidden">
	<div class="relative -mx-1 overflow-hidden sm:mx-0">
		<?php // aspect-[1.08/1] reserves the photo height before Alpine renders the slides (no layout shift). ?>
		<div x-ref="mobileGallery" @scroll.passive="onMobileScroll()" class="flex aspect-[1.08/1] snap-x snap-mandatory overflow-x-auto scrollbar-hide">
			<?php if ( $first ) : ?>
				<div class="relative aspect-[1.08/1] min-w-full overflow-hidden" x-show="false"><?php $first_img( '100vw' ); ?></div>
			<?php endif; ?>
			<template x-for="(image, index) in gallery" :key="image.src + '-' + index">
				<div class="relative aspect-[1.08/1] min-w-full snap-center overflow-hidden">
					<img :src="image.src" :srcset="image.srcset || null" :alt="image.alt" sizes="100vw" class="absolute inset-0 h-full w-full object-contain" :loading="index === 0 ? 'eager' : 'lazy'">
				</div>
			</template>
		</div>
		<template x-if="gallery.length > 1">
			<div>
				<button type="button" @click="prevImage(true)" class="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/88 text-[#746a5a] shadow-[0_10px_25px_rgba(0,0,0,0.08)]" aria-label="Previous image"><?php veloria_icon_e( 'chevron-left', 18 ); ?></button>
				<button type="button" @click="nextImage(true)" class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/88 text-[#746a5a] shadow-[0_10px_25px_rgba(0,0,0,0.08)]" aria-label="Next image"><?php veloria_icon_e( 'chevron-right', 18 ); ?></button>
			</div>
		</template>
		<button type="button" @click="zoomOpen = true" class="absolute bottom-4 left-4 flex h-14 w-14 items-center justify-center rounded-full bg-white text-[#6f6143] shadow-[0_12px_25px_rgba(0,0,0,0.08)]" aria-label="Open image preview"><?php veloria_icon_e( 'maximize-2', 18 ); ?></button>
	</div>
	<div x-show="gallery.length > 1" class="mt-2 flex gap-2 overflow-x-auto pb-1 scrollbar-hide <?php echo $many ? 'min-h-[86px]' : ''; ?>"<?php echo $many ? '' : ' style="display:none"'; ?>>
		<template x-for="(image, index) in gallery" :key="image.src + '-thumb-' + index">
			<button type="button" @click="selectImage(index, true)" class="relative h-[82px] w-[82px] shrink-0 overflow-hidden border border-[#e8e0d3] transition" :class="activeIndex === index ? 'opacity-100 shadow-[0_0_0_1px_#b7924e_inset]' : 'opacity-45'" :aria-label="'Show image ' + (index + 1)">
				<img :src="image.thumbnail || image.src" :alt="image.alt" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
			</button>
		</template>
	</div>
</div>
