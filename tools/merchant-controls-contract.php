<?php
/** Behaviour checks for merchant controls; no network or real database. */
define( 'ABSPATH', '/fixture/' );
define( 'MINUTE_IN_SECONDS', 60 );
$GLOBALS['meta'] = array();
$GLOBALS['options'] = array();
$GLOBALS['status'] = array( 1 => 'auto-draft', 2 => 'publish', 3 => 'publish' );
function get_post_status( $id ) { return $GLOBALS['status'][$id] ?? false; }
function get_post_meta( $id, $key, $single = false ) { return $GLOBALS['meta'][$id][$key] ?? ''; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][$key] ?? $default; }
function get_current_screen() { return (object) array( 'id' => 'shop_coupon' ); }
function current_user_can( $cap, ...$args ) { return $GLOBALS['can_manage'] ?? true; }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function wp_timezone() { return new DateTimeZone( $GLOBALS['timezone'] ?? 'Asia/Kolkata' ); }
function get_transient( $key ) { return false; }
function set_transient( $key, $value, $ttl ) { return true; }
function sanitize_hex_color( $value ) { return preg_match( '/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i', $value ) ? $value : null; }
function get_term_meta( $id, $key, $single = false ) { return $GLOBALS['term_meta'][$id][$key] ?? ''; }
function get_taxonomy( $name ) { return (object) array( 'cap' => (object) array( 'edit_terms' => 'manage_product_terms' ) ); }
function wp_unslash( $value ) { return stripslashes( $value ); }
function sanitize_text_field( $value ) { return strip_tags( $value ); }
function wp_verify_nonce( $value, $action ) { return 'valid' === $value; }
function update_term_meta( $id, $key, $value ) { $GLOBALS['term_meta'][$id][$key] = $value; }
function delete_term_meta( $id, $key ) { unset( $GLOBALS['term_meta'][$id][$key] ); }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['options'][$key] = $value; }
function wp_next_scheduled( $hook ) { if ( 'woocommerce_scheduled_sales' !== $hook ) throw new RuntimeException( 'Wrong WooCommerce event' ); return time() + 3600; }
function add_action( $hook, $callback, $priority = 10 ) { $GLOBALS['registered_actions'][$hook] = $callback; }
function get_template_directory() { return $GLOBALS['theme_dir']; }
function get_stylesheet_directory() { return $GLOBALS['child_dir']; }
class WC_Coupon {
    public function __construct( public int $id ) {}
    public function get_discount_type() { return $GLOBALS['coupon_type'] ?? 'percent'; }
    public function __call( $name, $args ) { return 'get_exclude_sale_items' === $name && ! empty( $GLOBALS['exclude_sale'] ); }
}
$root = __DIR__ . '/../wp-content/plugins/veloria-commerce/src/';
require $root . 'Settings.php';
require $root . 'Pricing/CouponRepository.php';
require $root . 'Pricing/DiscountEngine.php';
require $root . 'Catalog/Catalog.php';
require $root . 'Admin/Notices.php';
require $root . 'Admin/HealthChecks.php';
require $root . 'Admin/ColorTerms.php';
require __DIR__ . '/../wp-content/themes/veloria/inc/template-helpers.php';
use Veloria\Commerce\Pricing\CouponRepository;
use Veloria\Commerce\Pricing\DiscountEngine;
use Veloria\Commerce\Admin\Notices;
use Veloria\Commerce\Admin\HealthChecks;
use Veloria\Commerce\Admin\ColorTerms;
use Veloria\Commerce\Catalog\Catalog;
use Veloria\Commerce\Settings;
$passed = 0;
function check( $label, $ok ) { if ( ! $ok ) throw new RuntimeException( $label ); $GLOBALS['passed']++; }
check( 'New unsaved coupon opts in', 'yes' === CouponRepository::engine_field_value( 0 ) );
check( 'Auto draft opts in', 'yes' === CouponRepository::engine_field_value( 1 ) );
$GLOBALS['meta'][2]['_veloria_engine'] = 'no';
check( 'Existing opted-out coupon stays off', 'no' === CouponRepository::engine_field_value( 2 ) );
check( 'Legacy coupon without opt-in stays off', '' === CouponRepository::engine_field_value( 3 ) );
$GLOBALS['post'] = (object) array( 'ID' => 2 );
$GLOBALS['meta'][2]['_veloria_engine'] = 'yes'; $GLOBALS['exclude_sale'] = true;
ob_start(); CouponRepository::unsupported_notice(); $notice = ob_get_clean();
check( 'Unsupported restrictions warn after save', str_contains( $notice, 'unavailable in the storefront' ) );
$GLOBALS['can_manage'] = false;
ob_start(); CouponRepository::unsupported_notice(); $notice = ob_get_clean();
check( 'Coupon warning checks manager capability', '' === $notice );
$GLOBALS['can_manage'] = true; $GLOBALS['exclude_sale'] = false;
ob_start(); CouponRepository::unsupported_notice(); $notice = ob_get_clean();
check( 'Supported coupon has no warning', '' === $notice );
check( 'Default advertised copy matches rules', array() === Notices::offer_copy_problems( Settings::defaults() ) );
$settings = Settings::defaults(); $settings['prepaid_percent'] = 7.5;
check( 'Changing prepaid percent flags stale copy', count( Notices::offer_copy_problems( $settings ) ) >= 2 );
$settings = Settings::defaults(); $settings['tier_rules'][0]['percent'] = 12;
check( 'Changing tier percent flags stale copy', count( Notices::offer_copy_problems( $settings ) ) >= 2 );
$product = array( 'name' => 'Bag', 'slug' => 'bag', 'attributes' => array( array( 'name' => 'Color', 'slug' => 'pa_color', 'terms' => array( array( 'id' => 9, 'name' => 'Wine', 'slug' => 'wine' ) ) ) ) );
$GLOBALS['term_meta'][9]['veloria_hex'] = '#883355';
check( 'Saved term hex overrides name heuristic', '#883355' === veloria_swatches( $product )[0]['hex'] );
$_POST = array( 'veloria_color_nonce' => 'bad', 'veloria_hex' => '#123456' ); ColorTerms::save( 9 );
check( 'Invalid nonce cannot change term', '#883355' === $GLOBALS['term_meta'][9]['veloria_hex'] );
$_POST['veloria_color_nonce'] = 'valid'; $_POST['veloria_hex'] = 'javascript:bad'; ColorTerms::save( 9 );
check( 'Invalid hex preserves previous colour', '#883355' === $GLOBALS['term_meta'][9]['veloria_hex'] );
$_POST['veloria_hex'] = ''; ColorTerms::save( 9 );
check( 'Clearing term restores fallback', '#cccccc' === veloria_swatches( $product )[0]['hex'] );
$_POST['veloria_hex'] = '#aabbcc'; $GLOBALS['can_manage'] = false; ColorTerms::save( 9 );
check( 'Term writes require capability', ! isset( $GLOBALS['term_meta'][9]['veloria_hex'] ) );
$GLOBALS['can_manage'] = true; ColorTerms::save( 9 );
check( 'Valid authorized colour persists', '#aabbcc' === $GLOBALS['term_meta'][9]['veloria_hex'] );
$variants = array(
    array( 'id' => 1, 'leadImage' => 'parent.jpg', 'stock_status' => 'instock', 'has_own_image' => false ),
    array( 'id' => 2, 'leadImage' => 'blue.jpg', 'stock_status' => 'outofstock', 'has_own_image' => true ),
    array( 'id' => 3, 'leadImage' => 'red.jpg', 'stock_status' => 'instock', 'has_own_image' => true ),
);
check( 'Own in-stock image wins over inherited lead image', 3 === Catalog::default_variation_id( $variants, 'parent.jpg' ) );
check( 'Stock wins if own images are sold out', 1 === Catalog::default_variation_id( array_slice( $variants, 0, 2 ), 'parent.jpg' ) );
check( 'Empty variations are safe', null === Catalog::default_variation_id( array(), '' ) );
check( 'Non-colour label uses attribute name', 'Size' === Catalog::selector_label( array( 'attributes' => array( array( 'name' => 'Size', 'variation' => true ) ) ) ) );
check( 'Combined attributes get a combined label', 'Color / Size' === Catalog::selector_label( array( 'attributes' => array( array( 'name' => 'Color', 'variation' => true ), array( 'name' => 'Size', 'variation' => true ) ) ) ) );
check( 'Old WooCommerce fails health check', str_contains( implode( ' ', HealthChecks::runtime_problems( '11.0.9', 100000, 100100, 100001 ) ), '11.1 or newer' ) );
check( 'Missed actual run detected despite future schedule', str_contains( implode( ' ', HealthChecks::runtime_problems( '11.2.1', 1, 200001, 200000 ) ), 'no verified' ) );
check( 'Unscheduled sales detected', str_contains( implode( ' ', HealthChecks::runtime_problems( '11.2.1', 100000, 0, 100001 ) ), 'missing' ) );
check( 'Healthy sales execution passes', array() === HealthChecks::runtime_problems( '11.2.1', 100000, 100100, 100001 ) );
check( 'Override version parsed', '9.8.0' === HealthChecks::template_version( "/**\n * @version 9.8.0\n */" ) );
HealthChecks::register();
check( 'Tracks actual WooCommerce event rather than function name', isset( $GLOBALS['registered_actions']['woocommerce_scheduled_sales'] ) && ! isset( $GLOBALS['registered_actions']['wc_scheduled_sales'] ) );
$fixture = sys_get_temp_dir() . '/veloria-health-' . getmypid();
define( 'WC_ABSPATH', $fixture . '/wc/' ); define( 'WC_VERSION', '11.2.1' );
$GLOBALS['theme_dir'] = $fixture . '/theme'; $GLOBALS['child_dir'] = $fixture . '/child';
foreach ( array( '/wc/templates/checkout', '/theme/woocommerce/checkout', '/child/woocommerce/checkout' ) as $path ) mkdir( $fixture . $path, 0777, true );
file_put_contents( WC_ABSPATH . 'templates/checkout/example.php', '<?php /** @version 10.0.0 */' );
file_put_contents( $GLOBALS['theme_dir'] . '/woocommerce/checkout/example.php', '<?php /** @version 9.0.0 */' );
$GLOBALS['options']['veloria_sales_last_run'] = time();
check( 'Outdated override fails preflight', str_contains( implode( ' ', HealthChecks::problems() ), 'Outdated WooCommerce override: checkout/example.php' ) );
file_put_contents( $GLOBALS['child_dir'] . '/woocommerce/checkout/example.php', '<?php /** @version 10.0.0 */' );
check( 'Current child override takes precedence', array() === HealthChecks::problems() );
foreach ( json_decode( file_get_contents( __DIR__ . '/../wp-content/plugins/veloria-commerce/tests/parity/coupon-date-vectors.json' ), true, 512, JSON_THROW_ON_ERROR ) as $vector ) {
    $GLOBALS['timezone'] = $vector['timezone'] ?? 'Asia/Kolkata';
    $coupon = array( 'isActive' => true ) + $vector['coupon'];
    $now = (int) ( new DateTimeImmutable( $vector['now'] ) )->format( 'U' ) * 1000 + ( $vector['millis'] ?? 0 );
    check( $vector['label'], $vector['error'] === DiscountEngine::coupon_validation_error( $coupon, 1000, 1, $now ) );
}
echo "$passed merchant-control contracts passed\n";
