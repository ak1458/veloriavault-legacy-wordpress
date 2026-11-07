<?php
declare( strict_types=1 );

const ABSPATH = '/workspace/';
define( 'VELORIA_THEME_DIR', dirname( __DIR__ ) . '/wp-content/themes/veloria' );
define( 'VELORIA_THEME_URI', 'https://store.test/wp-content/themes/veloria' );
define( 'VELORIA_THEME_VERSION', '1.0.0' );

$fixture_root = sys_get_temp_dir() . '/veloria-resolver-' . getmypid();
$upload_dir   = $fixture_root . '/uploads/2026/01';
mkdir( $upload_dir, 0777, true );
file_put_contents( $upload_dir . '/existing.jpg', 'fixture' );
define( 'WP_CONTENT_DIR', $fixture_root );

function add_action( string $hook, callable $callback, int $priority = 10 ): void {}
function home_url( string $path = '/' ): string { return 'https://store.test' . $path; }
function content_url( string $path = '/' ): string { return 'https://store.test/wp-content' . $path; }

require dirname( __DIR__ ) . '/wp-content/themes/veloria/inc/assets.php';

function expect_same( string $label, string $expected, string $actual ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $label . "\nExpected: " . $expected . "\nActual:   " . $actual . "\n" );
		exit( 1 );
	}
}

$placeholder = VELORIA_THEME_URI . '/public/images/bag-placeholder.svg';
$existing    = content_url( '/uploads/2026/01/existing.jpg' );

expect_same( 'existing theme asset', VELORIA_THEME_URI . '/public/images/covers/shop.png', veloria_theme_asset( 'images/covers/shop.png' ) );
expect_same( 'missing theme asset uses placeholder', $placeholder, veloria_theme_asset( 'missing.png' ) );
expect_same( 'theme traversal is rejected', $placeholder, veloria_theme_asset( '../README.md' ) );
expect_same( 'invalid custom fallback uses placeholder', $placeholder, veloria_theme_asset( 'missing.png', '../README.md' ) );
expect_same( 'existing relative upload', $existing, veloria_asset_url( '/wp-content/uploads/2026/01/existing.jpg' ) );
expect_same( 'missing relative upload uses fallback', VELORIA_THEME_URI . '/public/images/covers/about.png', veloria_asset_url( '/wp-content/uploads/2026/01/missing.jpg', 'images/covers/about.png' ) );
expect_same( 'existing same-origin absolute upload', $existing, veloria_asset_url( $existing ) );
expect_same( 'missing same-origin absolute upload uses fallback', VELORIA_THEME_URI . '/public/images/covers/about.png', veloria_asset_url( 'https://store.test/wp-content/uploads/2026/01/missing.jpg', 'images/covers/about.png' ) );
expect_same( 'external absolute URL passes through', 'https://cdn.example.test/bag.jpg', veloria_asset_url( 'https://cdn.example.test/bag.jpg' ) );
expect_same( 'upload traversal is rejected', $placeholder, veloria_asset_url( '/wp-content/uploads/../../wp-config.php' ) );

echo "asset resolver contract passed\n";
