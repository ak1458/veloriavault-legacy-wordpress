<?php
/**
 * src/app/not-found.tsx
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'veloria_elementor_location' ) ) {
	ob_start(); $builder = veloria_elementor_location( 'single' ); $content = ob_get_clean();
	if ( $builder ) { get_header(); echo $content; get_footer(); return; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

get_header();
?>
<div class="min-h-screen bg-[#faf8f5] flex flex-col items-center justify-center px-4">
	<div class="text-center max-w-md">
		<h1 class="text-6xl font-serif text-[#b59a5c] mb-4">404</h1>
		<h2 class="text-2xl font-medium text-gray-900 mb-4">Page Not Found</h2>
		<p class="text-gray-600 mb-8">The page you&apos;re looking for doesn&apos;t exist or has been moved.</p>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-block bg-[#1a1a1a] text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-colors">Return Home</a>
	</div>
</div>
<?php
get_footer();
