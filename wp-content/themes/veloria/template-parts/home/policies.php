<?php
/**
 * LegacyHomePage.tsx — policy strip.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

$policies = (array) veloria_setting( 'home_policies', array() );
?>
<section class="py-16 lg:py-20 border-t border-gray-100 bg-white">
	<div class="container mx-auto px-4 sm:px-6 lg:px-8">
		<div class="grid grid-cols-3 gap-4 md:gap-8 lg:gap-12">
			<?php foreach ( $policies as $policy ) : ?>
				<?php $title = $policy['title']; $description = str_replace( '{shipping}', veloria_shipping_promise(), $policy['description'] ); $icon = $policy['icon']; ?>
				<article class="text-center">
					<div class="w-12 h-12 mx-auto mb-4 text-[#b59a5c]"><?php veloria_icon_e( $icon, 24, 'w-full h-full', array( 'stroke-width' => 1.5 ) ); ?></div>
					<h3 class="text-sm font-semibold text-gray-900 mb-2 uppercase tracking-wide"><?php echo esc_html( $title ); ?></h3>
					<p class="text-sm text-gray-500"><?php echo esc_html( $description ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
