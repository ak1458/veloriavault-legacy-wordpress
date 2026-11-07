<?php
/**
 * Lucide icons (same paths as lucide-react on the headless site).
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inline SVG icon.
 *
 * @param array<string,string|int|float> $attrs Extra attributes (e.g. fill, stroke-width, x-bind…).
 */
function veloria_icon( string $name, int $size = 24, string $class = '', array $attrs = array() ): string {
	static $icons = null;
	if ( null === $icons ) {
		$icons = require VELORIA_THEME_DIR . '/inc/icons-data.php';
	}
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	$defaults = array(
		'xmlns'           => 'http://www.w3.org/2000/svg',
		'width'           => $size,
		'height'          => $size,
		'viewBox'         => '0 0 24 24',
		'fill'            => 'none',
		'stroke'          => 'currentColor',
		'stroke-width'    => 2,
		'stroke-linecap'  => 'round',
		'stroke-linejoin' => 'round',
		'aria-hidden'     => 'true',
	);
	$attrs = array_merge( $defaults, $attrs );
	$out   = '<svg class="lucide lucide-' . esc_attr( $name ) . ( '' !== $class ? ' ' . esc_attr( $class ) : '' ) . '"';
	foreach ( $attrs as $key => $value ) {
		$out .= ' ' . esc_attr( (string) $key ) . '="' . esc_attr( (string) $value ) . '"';
	}
	return $out . '>' . $icons[ $name ] . '</svg>';
}

function veloria_icon_e( string $name, int $size = 24, string $class = '', array $attrs = array() ): void {
	echo veloria_icon( $name, $size, $class, $attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts and trusted path data.
}

/** Custom brand icons from LegacyHomePage.tsx / ProductTrustBadges.tsx. */
function veloria_brand_icon( string $name, string $class = '', float $stroke = 1.5 ): string {
	$paths = array(
		'leather'   => '<path d="M12 2c-2 0-3 2-3 2s-1 1-3 1-2 2-2 4 1 5 1 5-1 3 1 4 4-1 6-1 4 2 6 1 1-4 1-4 1-3 1-5-2-4-2-4-1-1-3-1-1-2-3-2z"/><path d="M12 6v12" stroke-dasharray="1 3"/>',
		'design'    => '<circle cx="12" cy="12" r="9" stroke-opacity="0.4"/><path d="M12 3l-6 16"/><path d="M12 3l6 16"/><path d="M9 13h6"/><circle cx="12" cy="4" r="1.5"/><path d="M12 1v1.5"/>',
		'hammer'    => '<path d="M14 10L6 18a1.5 1.5 0 0 0 2 2l8-8"/><path d="M12 8l4-4a1 1 0 0 1 1.4 0l1.6 1.6a1 1 0 0 1 0 1.4L15 11"/><path d="M14 10l1-1"/><path d="M5 5l1.5 1.5" opacity="0.6"/><path d="M9 4v1.5" opacity="0.6"/><path d="M4 9h1.5" opacity="0.6"/>',
		'craft'     => '<path d="M14 10L6 18a1.5 1.5 0 0 0 2 2l8-8"/><path d="M12 8l4-4a1 1 0 0 1 1.4 0l1.6 1.6a1 1 0 0 1 0 1.4L15 11"/><path d="M14 10l1-1"/>',
		'secure'    => '<path d="M12 2l8 4v6c0 5.5-3.84 8.5-8 10-4.16-1.5-8-4.5-8-10V6l8-4z"/><path d="M9 12l2 2 4-4"/>',
		'ethical'   => '<circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 0 0 20"/><path d="M2 12h20"/><path d="M12 2c2.76 2.76 4 6 4 10s-1.24 7.24-4 10"/><path d="M12 2c-2.76 2.76-4 6-4 10s1.24 7.24 4 10"/>',
		'packaging' => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M12 12l8-4.5"/><path d="M12 12v9"/><path d="M12 12L4 7.5"/>',
		'warranty'  => '<path d="M12 2l8 4v6c0 5.5-3.84 8.5-8 10-4.16-1.5-8-4.5-8-10V6l8-4z"/><path d="M12 8v4"/><circle cx="12" cy="16" r="0.5" fill="currentColor"/>',
		'returns'   => '<path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 1 1 0 8h-1"/>',
	);
	$inner = $paths[ $name ] ?? $paths['secure'];
	return sprintf(
		'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%s" stroke-linecap="round" stroke-linejoin="round" class="%s" aria-hidden="true">%s</svg>',
		esc_attr( (string) $stroke ),
		esc_attr( $class ),
		$inner
	);
}
