<?php
/**
 * ProductAccordion.tsx — description split into sections.
 *
 * @package Veloria
 *
 * @var array $args { description: string, extra?: array<int,array{title:string,content:string}> }
 */

defined( 'ABSPATH' ) || exit;

$sections = array_merge( veloria_description_sections( (string) $args['description'] ), (array) ( $args['extra'] ?? array() ) );
if ( ! $sections ) {
	return;
}
?>
<section class="mx-auto max-w-[1320px] px-3 sm:px-6 lg:px-8" x-data="{ open: 0 }">
	<div class="border-t border-[#e6dfd2]">
		<?php foreach ( $sections as $i => $section ) : ?>
			<div class="border-b border-[#e6dfd2]">
				<button type="button" @click="open = open === <?php echo (int) $i; ?> ? null : <?php echo (int) $i; ?>" class="flex w-full items-center justify-between py-5 text-left">
					<span class="text-[15px] font-semibold tracking-[0.02em] text-[#1f1a13]"><?php echo esc_html( $section['title'] ); ?></span>
					<span class="shrink-0 text-[#9b917f] transition-transform duration-300" :class="open === <?php echo (int) $i; ?> ? 'rotate-180' : ''"><?php veloria_icon_e( 'chevron-down', 18 ); ?></span>
				</button>
				<div class="overflow-hidden transition-all duration-300" :class="open === <?php echo (int) $i; ?> ? 'max-h-[2000px] pb-6' : 'max-h-0'"<?php echo 0 === $i ? '' : ' style="max-height:0"'; ?> x-init="$el.style.removeProperty('max-height')">
					<div class="prose prose-sm max-w-none text-[14px] leading-[1.85] text-[#5c564d] [&_a]:text-[#b89a57] [&_a]:transition [&_a:hover]:text-[#8a6e31] [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_li]:mb-1 [&_strong]:text-[#1f1a13] [&_h3]:text-[15px] [&_h3]:font-semibold [&_h3]:text-[#1f1a13] [&_h3]:mb-2 [&_p]:mb-3">
						<?php echo wp_kses_post( $section['content'] ); ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
