<?php
/** Merchant-defined swatch colours on the global colour attribute. */
namespace Veloria\Commerce\Admin;

use Veloria\Commerce\Catalog\Catalog;

defined( 'ABSPATH' ) || exit;

final class ColorTerms {
	public static function register(): void {
		add_action( 'pa_color_add_form_fields', array( self::class, 'add_field' ) );
		add_action( 'pa_color_edit_form_fields', array( self::class, 'edit_field' ) );
		add_action( 'created_pa_color', array( self::class, 'save' ) );
		add_action( 'edited_pa_color', array( self::class, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
	}

	public static function assets(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'pa_color' !== $screen->taxonomy ) return;
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".veloria-term-color").wpColorPicker();});' );
	}

	private static function input( string $value = '' ): void {
		wp_nonce_field( 'veloria_color', 'veloria_color_nonce' );
		printf( '<input type="text" id="veloria-hex" name="veloria_hex" class="veloria-term-color" value="%s"><p class="description">Swatch colour. Leave blank to match the colour name automatically.</p>', esc_attr( $value ) );
	}

	public static function add_field(): void {
		echo '<div class="form-field"><label for="veloria-hex">Swatch colour</label>';
		self::input();
		echo '</div>';
	}

	public static function edit_field( \WP_Term $term ): void {
		echo '<tr class="form-field"><th><label for="veloria-hex">Swatch colour</label></th><td>';
		self::input( (string) get_term_meta( $term->term_id, 'veloria_hex', true ) );
		echo '</td></tr>';
	}

	public static function save( int $term_id ): void {
		$taxonomy = get_taxonomy( 'pa_color' );
		if ( ! $taxonomy || ! current_user_can( $taxonomy->cap->edit_terms ) || ! isset( $_POST['veloria_color_nonce'], $_POST['veloria_hex'] ) || ! is_string( $_POST['veloria_color_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['veloria_color_nonce'] ) ), 'veloria_color' ) || ! is_string( $_POST['veloria_hex'] ) ) return;
		$raw = trim( wp_unslash( $_POST['veloria_hex'] ) );
		$hex = sanitize_hex_color( $raw );
		if ( '' !== $raw && ! $hex ) return;
		if ( $hex ) update_term_meta( $term_id, 'veloria_hex', $hex );
		else delete_term_meta( $term_id, 'veloria_hex' );
		Catalog::bump();
	}
}
