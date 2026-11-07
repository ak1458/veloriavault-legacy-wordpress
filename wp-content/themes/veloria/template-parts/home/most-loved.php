<?php
/**
 * LegacyHomePage.tsx + LegacyHomeTabs.tsx — "Most loved styles".
 *
 * @package Veloria
 *
 * @var array $args { config: array, tabs: array }
 */

defined( 'ABSPATH' ) || exit;

$config = $args['config'];
$tabs   = $args['tabs'];
?>
<section id="most-loved-styles" class="py-16 lg:py-24">
	<div class="container mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center mb-12">
			<p class="text-[11px] font-semibold tracking-[0.2em] uppercase mb-3" style="color: <?php echo esc_attr( (string) ( $config['label_color'] ?? '#856b35' ) ); ?>"><?php echo esc_html( (string) ( $config['eyebrow'] ?? '' ) ); ?></p>
			<h2 class="text-2xl lg:text-3xl font-serif font-medium text-gray-900"><?php echo esc_html( (string) ( $config['title'] ?? '' ) ); ?></h2>
		</div>

		<div class="space-y-6 lg:space-y-8" x-data="homeTabs('<?php echo esc_js( $tabs[0]['slug'] ); ?>')">
			<div class="overflow-x-auto scrollbar-hide" style="scrollbar-width: none; -ms-overflow-style: none">
				<div class="flex min-w-max items-center justify-start gap-2 border-b border-gray-200 px-2 sm:px-4 md:min-w-0 md:justify-center md:gap-8 md:px-0">
					<?php foreach ( $tabs as $t => $tab ) : ?>
						<?php list( $initial, $bind ) = veloria_toggle( "active === '" . esc_js( $tab['slug'] ) . "'", 'text-black', 'text-gray-600 hover:text-black', 0 === $t ); ?>
						<button type="button" @click="active = '<?php echo esc_js( $tab['slug'] ); ?>'"
							class="relative px-4 md:px-6 py-3 text-xs md:text-sm font-medium tracking-wider uppercase transition-all duration-300 whitespace-nowrap <?php echo esc_attr( $initial ); ?>"<?php echo $bind; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							style="font-family: var(--font-lato), sans-serif">
							<?php echo esc_html( $tab['label'] ); ?>
							<span x-show="active === '<?php echo esc_js( $tab['slug'] ); ?>'"<?php echo $t > 0 ? ' x-cloak' : ''; ?> class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#b59a5c]" x-transition.opacity.duration.300ms></span>
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="relative">
				<button type="button" @click="scroll('left')" class="absolute -left-4 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white shadow-lg rounded-full flex items-center justify-center hover:bg-gray-50 transition-colors hidden lg:flex" aria-label="Scroll left"><?php veloria_icon_e( 'chevron-left', 20 ); ?></button>
				<button type="button" @click="scroll('right')" class="absolute -right-4 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white shadow-lg rounded-full flex items-center justify-center hover:bg-gray-50 transition-colors hidden lg:flex" aria-label="Scroll right"><?php veloria_icon_e( 'chevron-right', 20 ); ?></button>
				<?php foreach ( $tabs as $t => $tab ) : ?>
					<div x-show="active === '<?php echo esc_js( $tab['slug'] ); ?>'"<?php echo $t > 0 ? ' x-cloak' : ''; ?> x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
						x-ref="row-<?php echo esc_attr( $tab['slug'] ); ?>"
						class="flex gap-4 md:gap-5 overflow-x-auto scrollbar-hide pb-4 snap-x snap-mandatory" style="scrollbar-width: none; -ms-overflow-style: none">
						<?php foreach ( array_slice( $tab['products'], 0, 12 ) as $i => $product ) : ?>
							<div class="w-[78vw] max-w-[240px] flex-shrink-0 snap-start sm:w-[280px] lg:w-[300px] veloria-in-x" style="animation-delay: <?php echo esc_attr( (string) min( $i * 0.02, 0.12 ) ); ?>s">
								<?php get_template_part( 'template-parts/product/card-premium', null, array( 'product' => $product, 'eager' => $i < 4 && 0 === $t, 'show_wishlist' => true ) ); ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="text-center pt-4">
				<?php foreach ( $tabs as $t => $tab ) : ?>
					<a x-show="active === '<?php echo esc_js( $tab['slug'] ); ?>'"<?php echo $t > 0 ? ' x-cloak' : ''; ?> href="<?php echo esc_url( veloria_url( $tab['viewAllHref'] ) ); ?>" class="inline-flex items-center px-8 py-3 border-2 border-black text-black text-xs font-bold tracking-[0.2em] uppercase hover:bg-black hover:text-white transition-all duration-300" style="font-family: var(--font-lato), sans-serif">Explore <?php echo esc_html( $tab['label'] ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
