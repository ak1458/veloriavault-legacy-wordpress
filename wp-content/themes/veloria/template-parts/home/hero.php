<?php
/**
 * PremiumHero.tsx — crossfading slider with GSAP headline entrance.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

$slides = apply_filters(
	'veloria_hero_slides',
	(array) veloria_setting( 'hero_slides', array() )
);
$count = count( $slides );

// Phone-sized and desktop-sized copies; only the first slide loads with the page,
// the rest once the page has finished loading (heroSlider adds .is-ready).
$css_url = static fn( string $url ): string => str_replace( array( '"', "'", '(', ')', '\\' ), array( '%22', '%27', '%28', '%29', '' ), esc_url_raw( $url ) );
$hero_css = '';
foreach ( array_values( $slides ) as $i => $slide ) {
	$mobile   = $css_url( veloria_sized_asset_url( (string) $slide['image'], 'medium_large' ) );
	$desktop  = $css_url( veloria_sized_asset_url( (string) $slide['image'], '1536x1536' ) );
	$selector = 0 === $i ? '.veloria-hero-0' : '.veloria-hero.is-ready .veloria-hero-' . $i;
	$hero_css .= "{$selector}{background-image:url('{$mobile}')}@media (min-width:768px){{$selector}{background-image:url('{$desktop}')}}";
	if ( 0 === $i ) {
		printf( '<link rel="preload" as="image" href="%s" media="(max-width: 767px)" fetchpriority="high">', esc_url( $mobile ) );
		printf( '<link rel="preload" as="image" href="%s" media="(min-width: 768px)" fetchpriority="high">', esc_url( $desktop ) );
	}
}
?>
<style><?php echo $hero_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URLs sanitized above. ?></style>
<section x-data="heroSlider(<?php echo (int) $count; ?>)" @mouseenter="autoplay = false" @mouseleave="autoplay = true"
	class="veloria-hero relative flex min-h-[76svh] w-full items-end overflow-hidden bg-[#1a1a1a] px-0 pb-24 pt-24 sm:min-h-[80vh] sm:pb-28 sm:pt-28 lg:h-[85vh] lg:items-center lg:pb-0 lg:pt-0">
	<div class="absolute inset-0">
		<?php foreach ( array_values( $slides ) as $i => $slide ) : ?>
			<div class="veloria-hero-<?php echo (int) $i; ?> absolute inset-0 transition-[opacity,transform] [transition-duration:1200ms,6000ms] [transition-timing-function:ease-in-out,ease-out]"
				:style="current === <?php echo (int) $i; ?> ? { opacity: 1, transform: 'scale(1)', zIndex: 1 } : { opacity: 0, transform: 'scale(1.05)', zIndex: 0 }"
				style="background-size: cover; background-position: center center; <?php echo 0 === $i ? 'opacity:1; transform:scale(1); z-index:1;' : 'opacity:0; transform:scale(1.05); z-index:0;'; ?>"
				role="img" aria-label="<?php echo esc_attr( $slide['alt'] ); ?>">
				<div class="absolute inset-0 bg-black/40 lg:bg-black/30"></div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="hidden lg:block absolute inset-y-0 left-4 right-4 z-20 pointer-events-none">
		<div class="container mx-auto h-full relative">
			<button type="button" @click="prev()" class="absolute left-0 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all pointer-events-auto" aria-label="Previous slide"><?php veloria_icon_e( 'chevron-left', 24 ); ?></button>
			<button type="button" @click="next()" class="absolute right-0 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all pointer-events-auto" aria-label="Next slide"><?php veloria_icon_e( 'chevron-right', 24 ); ?></button>
		</div>
	</div>

	<div class="absolute bottom-6 left-1/2 z-20 flex -translate-x-1/2 items-center sm:bottom-8">
		<?php for ( $i = 0; $i < $count; $i++ ) : ?>
			<?php list( $initial, $bind ) = veloria_toggle( 'current === ' . (int) $i, 'w-8 h-2 bg-white rounded-full', 'w-2 h-2 bg-white/50 group-hover:bg-white/80 rounded-full', 0 === $i ); ?>
			<?php // The button is a 24px tap target; the visible dot is the inner span. ?>
			<button type="button" @click="goTo(<?php echo (int) $i; ?>)" class="group flex h-6 min-w-6 items-center justify-center"
				aria-label="Go to slide <?php echo (int) ( $i + 1 ); ?>">
				<span class="relative block transition-all duration-300 <?php echo esc_attr( $initial ); ?>"<?php echo $bind; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<template x-if="current === <?php echo (int) $i; ?> && autoplay">
						<span class="absolute inset-0 bg-[#b59a5c] rounded-full origin-left animate-[veloria-progress_6s_linear_forwards]"></span>
					</template>
				</span>
			</button>
		<?php endfor; ?>
	</div>

	<div class="absolute bottom-8 right-8 z-20 hidden lg:block">
		<span class="text-white/80 text-sm font-light tracking-widest" x-text="counter()">01 / <?php echo esc_html( str_pad( (string) $count, 2, '0', STR_PAD_LEFT ) ); ?></span>
	</div>

	<div class="container relative z-10 mx-auto px-5 sm:px-6 lg:px-8">
		<div class="mx-0 max-w-[20rem] text-left text-white sm:max-w-xl lg:max-w-2xl">
			<h1 x-ref="title" class="mb-3 font-serif text-[2.35rem] leading-[1.05] tracking-tight sm:text-5xl lg:mb-6 lg:text-7xl">
				<span class="block">LUXURY</span>
				<span class="block italic font-light">locked in leather</span>
			</h1>
			<p x-ref="subtitle" class="max-w-[18rem] text-sm leading-relaxed font-light text-gray-100 sm:max-w-sm sm:text-base lg:hidden">Each piece holds more than design. It is a possession. A presence. A promise.</p>
			<div class="hidden lg:block">
				<p class="text-base lg:text-lg text-gray-200 max-w-lg mb-8 leading-relaxed font-light tracking-wide">Each piece holds more than design. It is a possession. A presence. A promise. Handcrafted for the modern minimalist.</p>
			</div>
		</div>
	</div>
</section>
