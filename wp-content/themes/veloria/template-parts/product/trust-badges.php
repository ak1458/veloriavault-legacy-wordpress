<?php
/**
 * ProductTrustBadges.tsx
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="mx-auto max-w-[1320px] px-3 sm:px-6 lg:px-8">
	<div class="grid grid-cols-3 gap-x-3 gap-y-6 py-8 sm:grid-cols-4 md:grid-cols-7 md:gap-x-4">
		<?php foreach ( (array) veloria_setting( 'trust_badges', array() ) as $badge ) : ?>
			<div class="flex flex-col items-center text-center">
				<div class="mb-2 h-8 w-8 text-[#b89a57] sm:h-9 sm:w-9"><?php echo veloria_brand_icon( (string) $badge['icon'], 'h-full w-full', 1.2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<span class="text-[11px] font-semibold leading-tight text-[#1f1a13] sm:text-[12px]"><?php echo esc_html( $badge['title'] ); ?></span>
				<?php if ( ! empty( $badge['subtitle'] ) ) : ?>
					<span class="mt-0.5 text-[10px] text-[#6f6658] sm:text-[11px]"><?php echo esc_html( $badge['subtitle'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
