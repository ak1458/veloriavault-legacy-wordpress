<?php
// Batch WP_Error conversion (WP 7.1 connectors fatal) and anonymous /wp/v2/users hiding.
define( 'ABSPATH', __DIR__ );
class WP_Error { public function __construct( public string $code = '', public string $message = '' ) {} }
class WP_REST_Response { public function __construct( public $data = null, public int $status = 200 ) {} }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function rest_convert_error_to_response( $e ) { return new WP_REST_Response( array( 'code' => $e->code ), 403 ); }
function is_user_logged_in() { return $GLOBALS['logged_in']; }
require dirname( __DIR__ ) . '/wp-content/plugins/veloria-commerce/src/Store/StoreApiExtension.php';
require dirname( __DIR__ ) . '/wp-content/plugins/veloria-commerce/src/Security/Headers.php';
use Veloria\Commerce\Store\StoreApiExtension;
use Veloria\Commerce\Security\Headers;

$count = 0;
function check( bool $ok, string $name ) { global $count; if ( ! $ok ) { fwrite( STDERR, "FAIL $name\n" ); exit( 1 ); } $count++; }

$converted = StoreApiExtension::batch_error_response( new WP_Error( 'veloria_checkout_unsupported', 'x' ) );
check( $converted instanceof WP_REST_Response && 403 === $converted->status, 'batch WP_Error becomes a REST response' );
$ok = new WP_REST_Response( array( 'a' => 1 ) );
check( StoreApiExtension::batch_error_response( $ok ) === $ok, 'normal REST response untouched' );

$endpoints = array( '/wp/v2/users' => 1, '/wp/v2/users/(?P<id>[\d]+)' => 1, '/wp/v2/users/me' => 1, '/wp/v2/posts' => 1 );
$GLOBALS['logged_in'] = false;
$anon = Headers::hide_users_endpoints( $endpoints );
check( ! isset( $anon['/wp/v2/users'], $anon['/wp/v2/users/(?P<id>[\d]+)'] ) && isset( $anon['/wp/v2/posts'] ), 'anonymous users listing hidden, other routes kept' );
$GLOBALS['logged_in'] = true;
check( Headers::hide_users_endpoints( $endpoints ) === $endpoints, 'logged-in editors keep users endpoints' );

echo "$count REST hardening checks passed\n";
