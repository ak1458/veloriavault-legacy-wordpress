<?php
/** Structured merchant controls; no JSON editing required. */
namespace Veloria\Commerce\Admin;
defined( 'ABSPATH' ) || exit;

final class ClientFields {
	public static function schema( string $key ): array {
		$text = array( 'type' => 'text' );
		$url = array( 'type' => 'url' );
		$lines = array( 'type' => 'lines' );
		$object = static fn( $fields ) => array( 'type' => 'object', 'fields' => $fields );
		$list = static fn( $row ) => array( 'type' => 'list', 'row' => $row );
		$schemas = array(
			'hero_slides' => $list( $object( array( 'image' => array( 'label' => 'Photo', 'type' => 'media' ), 'alt' => array( 'label' => 'Image description' ) + $text ) ) ),
			'home_features' => $list( $object( array( 'title' => array( 'label' => 'Title' ) + $text, 'description' => array( 'label' => 'Description' ) + $text, 'icon' => array( 'label' => 'Icon', 'type' => 'select', 'options' => array( 'leather', 'design', 'hammer' ) ) ) ) ),
			'home_policies' => $list( $object( array( 'title' => array( 'label' => 'Title' ) + $text, 'description' => array( 'label' => 'Description' ) + $text, 'icon' => array( 'label' => 'Icon', 'type' => 'select', 'options' => array( 'refresh-cw', 'truck', 'shield-check', 'heart', 'package' ) ) ) ) ),
			'tier_rules' => $list( $object( array( 'min_qty' => array( 'label' => 'Minimum items', 'type' => 'integer', 'min' => 1, 'max' => 1000 ), 'percent' => array( 'label' => 'Discount (%)', 'type' => 'number', 'min' => 0, 'max' => 100 ) ) ) ),
			'product_promo' => $object( array( 'eyebrow' => array( 'label' => 'Section label' ) + $text, 'items' => array( 'label' => 'Offers' ) + $list( $object( array( 'title' => array( 'label' => 'Title' ) + $text, 'description' => array( 'label' => 'Description' ) + $text ) ) ) ) ),
			'trust_badges' => $list( $object( array( 'icon' => array( 'label' => 'Icon', 'type' => 'select', 'options' => array( 'leather', 'craft', 'secure', 'ethical', 'packaging', 'warranty', 'returns' ) ), 'title' => array( 'label' => 'Title' ) + $text, 'subtitle' => array( 'label' => 'Subtitle' ) + $text ) ) ),
			'policy_links' => $object( array( 'returnExchange' => array( 'label' => 'Return policy link' ) + $url, 'warranty' => array( 'label' => 'Warranty policy link' ) + $url ) ),
			'most_loved' => $object( array(
				'eyebrow' => array( 'label' => 'Section label' ) + $text, 'title' => array( 'label' => 'Heading' ) + $text, 'label_color' => array( 'label' => 'Label color', 'type' => 'color' ),
				'tabs' => array( 'label' => 'Collection tabs' ) + $list( $object( array(
					'slug' => array( 'label' => 'Tab reference', 'type' => 'slug' ), 'label' => array( 'label' => 'Tab title' ) + $text,
					'categorySlug' => array( 'label' => 'Category', 'type' => 'category' ), 'allowedColors' => array( 'label' => 'Colors (one per line; blank for all)' ) + $lines,
					'productIds' => array( 'label' => 'Featured products', 'type' => 'products' ), 'maxItems' => array( 'label' => 'Maximum products', 'type' => 'integer', 'min' => 1, 'max' => 50 ),
					'viewAllHref' => array( 'label' => 'View all link' ) + $url,
				) ) ),
			) ),
			'spotlight' => $object( array( 'eyebrow' => array( 'label' => 'Section label' ) + $text, 'title' => array( 'label' => 'Heading' ) + $text, 'image' => array( 'label' => 'Photo', 'type' => 'media' ), 'search_terms' => array( 'label' => 'Product search terms (one alternative per line)', 'type' => 'terms' ), 'paragraphs' => array( 'label' => 'Paragraphs (one per line)' ) + $lines ) ),
		);
		return $schemas[ $key ] ?? array();
	}

	public static function render( string $key, $value ): bool {
		$schema = self::schema( $key );
		if ( ! $schema ) return false;
		self::node( $schema, $value, 'veloria[' . $key . ']' );
		return true;
	}

	private static function node( array $schema, $value, string $name, int $depth = 0 ): void {
		$type = $schema['type'];
		if ( 'object' === $type ) {
			echo '<div class="veloria-fields">';
			foreach ( $schema['fields'] as $key => $field ) {
				$group = in_array( $field['type'], array( 'object', 'list' ), true );
				echo $group ? '<fieldset class="veloria-field"><legend>' . esc_html( $field['label'] ) . '</legend>' : '<label class="veloria-field"><span>' . esc_html( $field['label'] ) . '</span>';
				self::node( $field, is_array( $value ) ? ( $value[ $key ] ?? null ) : null, $name . '[' . $key . ']', $depth + 1 );
				echo $group ? '</fieldset>' : '</label>';
			}
			echo '</div>';
		} elseif ( 'list' === $type ) {
			$rows = is_array( $value ) ? array_values( $value ) : array();
			$token = '__ROW' . $depth . '__';
			printf( '<div class="veloria-repeater" data-next="%d" data-token="%s"><input type="hidden" name="%s[__present]" value="1"><div class="veloria-rows">', count( $rows ), esc_attr( $token ), esc_attr( $name ) );
			foreach ( $rows as $i => $row ) self::row( $schema['row'], $row, $name . '[' . $i . ']', $depth + 1 );
			echo '</div><template>';
			self::row( $schema['row'], null, $name . '[' . $token . ']', $depth + 1 );
			echo '</template><button type="button" class="button veloria-add">Add item</button></div>';
		} elseif ( in_array( $type, array( 'products', 'category', 'select' ), true ) ) {
			$multi = 'products' === $type;
			if ( $multi ) printf( '<input type="hidden" name="%s[]" value="">', esc_attr( $name ) );
			printf( '<select name="%s" %s>', esc_attr( $name . ( $multi ? '[]' : '' ) ), $multi ? 'multiple class="wc-product-search" data-placeholder="Search products by name…" data-action="woocommerce_json_search_products_and_variations" data-security="' . esc_attr( wp_create_nonce( 'search-products' ) ) . '"' : '' );
			if ( ! $multi ) echo '<option value="">Choose…</option>';
			$options = array();
			if ( $multi ) {
				foreach ( wc_get_products( array( 'limit' => 200, 'status' => array( 'publish', 'private', 'draft' ), 'orderby' => 'title', 'order' => 'ASC' ) ) as $product ) $options[ $product->get_id() ] = $product->get_name();
			} elseif ( 'category' === $type ) {
				$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
				if ( ! is_wp_error( $terms ) ) foreach ( $terms as $term ) $options[ $term->slug ] = $term->name;
			} else foreach ( $schema['options'] as $option ) $options[ $option ] = ucfirst( $option );
			foreach ( $options as $id => $label ) printf( '<option value="%s" %s>%s</option>', esc_attr( (string) $id ), selected( in_array( (string) $id, array_map( 'strval', (array) $value ), true ), true, false ), esc_html( $label ) );
			echo '</select>';
		} elseif ( in_array( $type, array( 'lines', 'terms' ), true ) ) {
			if ( 'terms' === $type ) $value = array_map( static fn( $row ) => implode( ', ', (array) $row ), (array) $value );
			printf( '<textarea rows="3" name="%s">%s</textarea>', esc_attr( $name ), esc_textarea( implode( "\n", (array) $value ) ) );
		} else {
			$kind = in_array( $type, array( 'integer', 'number' ), true ) ? 'number' : ( 'color' === $type ? 'color' : 'text' );
			$extra = isset( $schema['min'] ) ? ' min="' . esc_attr( (string) $schema['min'] ) . '" max="' . esc_attr( (string) $schema['max'] ) . '" step="' . ( 'integer' === $type ? '1' : 'any' ) . '"' : '';
			printf( '<input type="%s" name="%s" value="%s"%s>', esc_attr( $kind ), esc_attr( $name ), esc_attr( is_scalar( $value ) ? (string) $value : '' ), $extra );
			if ( 'media' === $type ) echo '<button type="button" class="button veloria-media">Choose photo</button>';
		}
	}

	private static function row( array $schema, $value, string $name, int $depth ): void {
		echo '<div class="veloria-row">';
		self::node( $schema, $value, $name, $depth );
		echo '<div class="veloria-row-actions"><button type="button" class="button veloria-up" aria-label="Move item up">↑</button> <button type="button" class="button veloria-down" aria-label="Move item down">↓</button> <button type="button" class="button-link-delete veloria-remove">Remove item</button></div></div>';
	}

	public static function sanitize( string $key, $value ) {
		try {
			$clean = self::clean( self::schema( $key ), $value );
			if ( 'hero_slides' === $key && ( ! $clean || array_filter( $clean, static fn( $row ) => '' === $row['image'] ) ) ) throw new \InvalidArgumentException( 'Choose at least one hero photo; remove empty rows.' );
			return $clean;
		}
		catch ( \InvalidArgumentException $error ) { return new \WP_Error( 'veloria_field', $error->getMessage() ); }
	}

	private static function clean( array $schema, $value ) {
		$type = $schema['type'] ?? '';
		if ( 'object' === $type ) {
			if ( ! is_array( $value ) ) throw new \InvalidArgumentException( 'Invalid section fields.' );
			$out = array();
			foreach ( $schema['fields'] as $key => $field ) $out[ $key ] = self::clean( $field, $value[ $key ] ?? ( in_array( $field['type'], array( 'list', 'products', 'object' ), true ) ? array() : '' ) );
			return $out;
		}
		if ( 'list' === $type ) {
			if ( ! is_array( $value ) ) throw new \InvalidArgumentException( 'Invalid item list.' );
			unset( $value['__present'] );
			if ( count( $value ) > 30 ) throw new \InvalidArgumentException( 'Use at most 30 items per section.' );
			return array_map( static fn( $row ) => self::clean( $schema['row'], $row ), array_values( $value ) );
		}
		if ( 'products' === $type ) {
			if ( ! is_array( $value ) ) throw new \InvalidArgumentException( 'Choose products from the list.' );
			return array_values( array_unique( array_filter( array_map( 'absint', array_filter( $value, 'is_scalar' ) ) ) ) );
		}
		if ( ! is_scalar( $value ) && null !== $value ) throw new \InvalidArgumentException( 'A field contains an invalid value.' );
		$value = (string) $value;
		if ( in_array( $type, array( 'integer', 'number' ), true ) ) {
			if ( ! is_numeric( $value ) || (float) $value < $schema['min'] || (float) $value > $schema['max'] || ( 'integer' === $type && (float) $value !== (float) (int) $value ) ) throw new \InvalidArgumentException( 'Check the quantity or percentage range.' );
			return 'integer' === $type ? (int) $value : (float) $value;
		}
		if ( 'color' === $type ) {
			$color = sanitize_hex_color( $value );
			if ( ! $color ) throw new \InvalidArgumentException( 'Choose a valid color.' );
			return $color;
		}
		if ( 'select' === $type ) {
			if ( ! in_array( $value, $schema['options'], true ) ) throw new \InvalidArgumentException( 'Choose a valid option.' );
			return $value;
		}
		if ( in_array( $type, array( 'url', 'media' ), true ) ) {
			if ( '' === $value ) return '';
			if ( str_starts_with( $value, '/' ) && ! str_starts_with( $value, '//' ) ) return esc_url_raw( $value );
			$url = esc_url_raw( $value, array( 'https', 'http' ) );
			if ( ! $url || ! preg_match( '#^https?://#i', $url ) ) throw new \InvalidArgumentException( 'Use a website link or a /page link.' );
			return $url;
		}
		if ( in_array( $type, array( 'slug', 'category' ), true ) ) return sanitize_title( $value );
		if ( in_array( $type, array( 'lines', 'terms' ), true ) ) {
			$lines = array_values( array_filter( array_map( 'sanitize_text_field', preg_split( '/\r?\n/', $value ) ?: array() ), static fn( $line ) => '' !== $line ) );
			return 'terms' === $type ? array_map( static fn( $line ) => array_values( array_filter( array_map( 'trim', explode( ',', $line ) ) ) ), $lines ) : $lines;
		}
		return sanitize_text_field( $value );
	}
}
