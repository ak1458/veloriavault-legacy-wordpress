<?php
/** Elementor handoff. Existing PHP templates remain the fallback. */
defined( 'ABSPATH' ) || exit;
add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );

add_action( 'elementor/theme/register_locations', static function ( $manager ): void {
	$manager->register_all_core_location();
} );

// The migration never activates inherited theme-builder conditions implicitly.
// Editors can opt a template in by saving it, or install the supplied templates.
add_filter( 'elementor/theme/conditions/cache/regenerate/query_args', static function ( array $args ): array {
	$args['meta_query'][] = array( 'key' => '_veloria_builder_seed_version', 'compare' => 'EXISTS' );
	return $args;
} );

add_action( 'after_setup_theme', static function (): void {
	register_nav_menus( array( 'primary' => __( 'Header menu', 'veloria' ), 'footer' => __( 'Footer: Collections', 'veloria' ), 'footer-links' => __( 'Footer: Important links', 'veloria' ) ) );
	add_theme_support( 'custom-logo' );
} );

/** Old Woodmart Elementor data stays dormant until deliberately migrated or saved. */
function veloria_elementor_has_page( int $post_id = 0 ): bool {
	$post_id = $post_id ?: (int) get_queried_object_id();
	if ( ! class_exists( '\Elementor\Plugin' ) || ! $post_id ) { return false; }
	$plugin = \Elementor\Plugin::$instance;
	if ( isset( $plugin->preview ) && $plugin->preview->is_preview_mode() && current_user_can( 'edit_post', $post_id ) ) { return true; }
	if ( 'yes' !== get_post_meta( $post_id, '_veloria_elementor_enabled', true ) ) { return false; }
	$document = $plugin->documents->get( $post_id );
	return $document && $document->is_built_with_elementor();
}

/**
 * Pages built with Elementor before the migration (headless-era home, policies)
 * would otherwise be rendered by Elementor itself: its `elementor_*` page
 * templates replace the theme template and its builder flag replaces
 * the_content. On the public site, hide both for pages that were not opted in,
 * so the theme templates render. Editor, admin, REST and previews are untouched.
 */
add_filter(
	'get_post_metadata',
	static function ( $value, $object_id, $meta_key, $single ) {
		static $reading = false;
		if ( $reading || ! in_array( $meta_key, array( '_elementor_edit_mode', '_wp_page_template' ), true ) ) { return $value; }
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) || isset( $_GET['elementor-preview'] ) ) { return $value; } // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( get_post_type( (int) $object_id ), array( 'page', 'post', 'product' ), true ) ) { return $value; }
		$reading = true;
		$enabled  = 'yes' === get_post_meta( (int) $object_id, '_veloria_elementor_enabled', true );
		$template = '_wp_page_template' === $meta_key ? (string) get_post_meta( (int) $object_id, '_wp_page_template', true ) : '';
		$reading  = false;
		if ( $enabled ) { return $value; }
		if ( '_elementor_edit_mode' === $meta_key || str_starts_with( $template, 'elementor_' ) ) {
			return $single ? '' : array();
		}
		return $value;
	},
	10,
	4
);

function veloria_elementor_location( string $location ): bool {
	return function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( $location );
}

/** Called before a page's legacy data loaders/output, including commerce pages. */
function veloria_elementor_render_page(): bool {
	if ( ! veloria_elementor_has_page() ) { return false; }
	get_header();
	while ( have_posts() ) { the_post(); the_content(); }
	get_footer();
	return true;
}

/** A user saving in Elementor explicitly chooses the new layout. */
add_action( 'elementor/document/after_save', static function ( $document ): void {
	$id = (int) $document->get_main_id();
	if ( $id && 'page' === get_post_type( $id ) && current_user_can( 'edit_post', $id ) ) {
		update_post_meta( $id, '_veloria_elementor_enabled', 'yes' );
	}
	if ( $id && 'elementor_library' === get_post_type( $id ) && current_user_can( 'edit_post', $id ) ) {
		update_post_meta( $id, '_veloria_builder_seed_version', 1 );
		if ( class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_cache()->regenerate();
	}
} );

add_action( 'elementor/widgets/register', static function ( $manager ): void {
	require_once VELORIA_THEME_DIR . '/inc/elementor-widgets.php';
	foreach ( array( 'Products', 'Reviews', 'Contact', 'Newsletter', 'Store_Actions', 'Offers', 'Chat', 'Customer_Tool' ) as $name ) {
		$class = 'Veloria_Elementor_' . $name;
		$manager->register( new $class() );
	}
} );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once VELORIA_THEME_DIR . '/inc/elementor-seed.php';
	\WP_CLI::add_command( 'veloria-builder', 'Veloria_Elementor_Seed' );
}
