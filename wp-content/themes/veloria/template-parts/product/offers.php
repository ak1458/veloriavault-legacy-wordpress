<?php
/**
 * ProductOffers.tsx
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

$promo = (array) veloria_setting( 'product_promo', array() );
?>
<section class="mx-auto max-w-[1320px] px-3 sm:px-6 lg:px-8" x-data="{ expanded: null }">
	<div class="border border-[#e9e2d5] bg-white">
		<div class="flex items-center gap-2.5 border-b border-[#ede7db] px-5 py-3.5">
			<?php veloria_icon_e( 'tag', 15, 'text-[#b89a57]' ); ?>
			<span class="text-[13px] font-semibold uppercase tracking-[0.06em] text-[#1f1a13]"><?php echo esc_html( (string) ( $promo['eyebrow'] ?? '' ) ); ?></span>
		</div>
		<div class="divide-y divide-[#f0ebe1]">
			<?php foreach ( array_values( (array) ( $promo['items'] ?? array() ) ) as $i => $item ) : ?>
				<button type="button" @click="expanded = expanded === <?php echo (int) $i; ?> ? null : <?php echo (int) $i; ?>" class="flex w-full items-start gap-3 px-5 py-3.5 text-left transition hover:bg-[#faf8f4]">
					<span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center"><span class="h-1.5 w-1.5 rounded-full bg-[#856b35]"></span></span>
					<div class="flex-1 min-w-0">
						<span class="text-[14px] font-medium text-[#1f1a13]"><?php echo esc_html( $item['title'] ); ?></span>
						<p x-show="expanded === <?php echo (int) $i; ?>" x-cloak class="mt-1 text-[13px] leading-relaxed text-[#7a7264]"><?php echo esc_html( $item['description'] ); ?></p>
					</div>
					<span class="mt-1.5 shrink-0 text-[#6f6658] transition-transform" :class="expanded === <?php echo (int) $i; ?> ? 'rotate-90' : ''"><?php veloria_icon_e( 'chevron-right', 14 ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
	</div>
</section>
