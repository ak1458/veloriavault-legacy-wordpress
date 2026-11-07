<?php
/**
 * LegacyHomePage.tsx — features row.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

$features = (array) veloria_setting( 'home_features', array() );
?>
<section class="py-12 lg:py-16">
	<div class="container mx-auto px-4 sm:px-6 lg:px-8">
		<div class="grid grid-cols-3 gap-2 md:gap-6 lg:gap-12">
			<?php foreach ( $features as $feature ) : ?>
				<?php $title = $feature['title']; $description = $feature['description']; $icon = $feature['icon']; ?>
				<article class="flex flex-col items-center text-center p-2 md:p-6">
					<div class="w-8 h-8 md:w-12 md:h-12 lg:w-14 lg:h-14 mb-2 md:mb-4 text-[#b59a5c]">
						<?php echo veloria_brand_icon( $icon, 'w-full h-full', 1.5 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<h2 class="text-[11px] md:text-sm lg:text-base font-semibold text-gray-900 mb-1 md:mb-2 tracking-tight leading-tight"><?php echo esc_html( $title ); ?></h2>
					<p class="text-[9px] md:text-xs lg:text-sm text-gray-600 leading-relaxed max-w-[120px] md:max-w-[200px]"><?php echo esc_html( $description ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
