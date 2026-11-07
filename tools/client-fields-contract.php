<?php
/** Invalid merchant input must never silently corrupt structured settings. */
define( 'ABSPATH', '/fixture/' );
class WP_Error { public function __construct( public string $code, public string $message ) {} }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_text_field( $v ) { return strip_tags( $v ); }
function sanitize_title( $v ) { return strtolower( preg_replace( '/[^a-z0-9-]/i', '-', $v ) ); }
function sanitize_hex_color( $v ) { return preg_match( '/^#[a-f0-9]{6}$/i', $v ) ? $v : null; }
function esc_url_raw( $v, $protocols = null ) { return preg_match( '#^(?:https?://|/(?!/))#i', $v ) ? $v : ''; }
require __DIR__ . '/../wp-content/plugins/veloria-commerce/src/Admin/ClientFields.php';
use Veloria\Commerce\Admin\ClientFields;
$passed = 0;
function check( $name, $ok ) { if ( ! $ok ) throw new RuntimeException( $name ); $GLOBALS['passed']++; }
$tiers = ClientFields::sanitize( 'tier_rules', array( '__present' => 1, array( 'min_qty' => '2', 'percent' => '15' ) ) );
check( 'Quantities and percentages are typed', $tiers === array( array( 'min_qty' => 2, 'percent' => 15.0 ) ) );
check( 'Negative discounts rejected', ClientFields::sanitize( 'tier_rules', array( array( 'min_qty' => '1', 'percent' => '-5' ) ) ) instanceof WP_Error );
check( 'Over-100 discounts rejected', ClientFields::sanitize( 'tier_rules', array( array( 'min_qty' => '1', 'percent' => '101' ) ) ) instanceof WP_Error );
check( 'Fractional quantity rejected', ClientFields::sanitize( 'tier_rules', array( array( 'min_qty' => '1.5', 'percent' => '10' ) ) ) instanceof WP_Error );
check( 'Removing all rows saves an empty list', ClientFields::sanitize( 'tier_rules', array( '__present' => 1 ) ) === array() );
check( 'Unsafe policy link rejected', ClientFields::sanitize( 'policy_links', array( 'returnExchange' => 'javascript:alert(1)', 'warranty' => '/warranty' ) ) instanceof WP_Error );
check( 'Scheme-relative external link rejected', ClientFields::sanitize( 'policy_links', array( 'returnExchange' => '//external.test', 'warranty' => '/warranty' ) ) instanceof WP_Error );
check( 'Malformed nested values rejected', ClientFields::sanitize( 'trust_badges', array( array( 'icon' => 'secure', 'title' => array( 'bad' ), 'subtitle' => '' ) ) ) instanceof WP_Error );
check( 'Unknown icon rejected', ClientFields::sanitize( 'trust_badges', array( array( 'icon' => 'script', 'title' => 'Safe', 'subtitle' => '' ) ) ) instanceof WP_Error );
$links = ClientFields::sanitize( 'policy_links', array( 'returnExchange' => '/refund-returns', 'warranty' => 'https://store.test/warranty' ) );
check( 'Relative and HTTP links accepted', is_array( $links ) && '/refund-returns' === $links['returnExchange'] );
check( 'Hero requires at least one photo', ClientFields::sanitize( 'hero_slides', array( '__present' => 1 ) ) instanceof WP_Error );
check( 'Hero rejects unsafe media links', ClientFields::sanitize( 'hero_slides', array( array( 'image' => 'javascript:bad', 'alt' => '' ) ) ) instanceof WP_Error );
$hero = ClientFields::sanitize( 'hero_slides', array( array( 'image' => '/images/hero/default.png', 'alt' => 'Leather bag' ), array( 'image' => 'https://store.test/photo.jpg', 'alt' => 'Second bag' ) ) );
check( 'Hero preserves merchant order and descriptions', is_array( $hero ) && 2 === count( $hero ) && 'Second bag' === $hero[1]['alt'] );
$policies = ClientFields::sanitize( 'home_policies', array( array( 'title' => 'Shipping', 'description' => '{shipping}.', 'icon' => 'truck' ) ) );
check( 'Shipping policy token survives saving', is_array( $policies ) && '{shipping}.' === $policies[0]['description'] );
check( 'Feature icons are validated', ClientFields::sanitize( 'home_features', array( array( 'title' => 'Bad', 'description' => '', 'icon' => 'unknown' ) ) ) instanceof WP_Error );
$tabs = ClientFields::sanitize( 'most_loved', array( 'eyebrow' => '', 'title' => '', 'label_color' => '#abcdef', 'tabs' => array( array( 'slug' => 'bags', 'label' => 'Bags', 'categorySlug' => 'bags', 'allowedColors' => '', 'productIds' => array(), 'maxItems' => '8', 'viewAllHref' => '/shop' ) ) ) );
check( 'Blank colour filter persists as all colours', is_array( $tabs ) && array() === $tabs['tabs'][0]['allowedColors'] );
echo "$passed merchant field contracts passed\n";
