<?php
/**
 * OfferStrip.tsx
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

$offers = array();
foreach ( veloria_offer_tiers() as $tier ) {
	$offers[] = array( 'big' => veloria_format_number( $tier['percent'] ) . '% Off', 'small' => $tier['label'] );
}
$prepaid  = (string) veloria_setting( 'prepaid_offer_text', '' );
$offers[] = array(
	'big'   => '+' . veloria_format_number( (float) veloria_setting( 'prepaid_percent', 5 ) ) . '% Off',
	'small' => (string) preg_replace( '/^Extra \d+(\.\d+)?% off on /i', 'On ', $prepaid ),
);
?>
<section aria-label="Offers" class="border-y border-[#e9e2d5] bg-white">
	<div class="mx-auto grid max-w-[1320px] divide-x divide-[#ede7db] px-2 sm:px-6" style="grid-template-columns: repeat(<?php echo (int) count( $offers ); ?>, minmax(0, 1fr));">
		<?php foreach ( $offers as $offer ) : ?>
			<div class="px-2 py-4 text-center sm:py-5">
				<p class="font-serif text-lg text-[#856b35] sm:text-2xl"><?php echo esc_html( $offer['big'] ); ?></p>
				<p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#1f1a13] sm:text-xs"><?php echo esc_html( $offer['small'] ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
	<p class="pb-3 text-center text-[11px] text-[#7a7264]">Applied automatically at checkout. No code needed.</p>
</section>
