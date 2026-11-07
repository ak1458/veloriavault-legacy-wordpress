<?php
/**
 * InstagramFeed.tsx. Six branded tiles (the live behaviour); switch on
 * Veloria → "Live Instagram photos" to render real posts from the existing
 * veloria/v1/instagram-feed endpoint instead.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

$labels = array( 'Quiet luxury details', 'Daily carry essentials', 'Premium leather craftsmanship', 'Structured silhouettes', 'Made for everyday elegance', 'View the latest drops' );
$posts  = array();
if ( veloria_setting( 'instagram_live' ) ) {
	$response = rest_do_request( new WP_REST_Request( 'GET', '/veloria/v1/instagram-feed' ) );
	$data     = $response->is_error() ? array() : (array) $response->get_data();
	$posts    = ! empty( $data['success'] ) && is_array( $data['posts'] ?? null ) ? array_slice( $data['posts'], 0, 6 ) : array();
}
$profile = (string) veloria_setting( 'instagram_url' );
if ( '' === $profile ) return;
?>
<section class="py-16 lg:py-24 bg-white">
	<div class="container mx-auto px-4 sm:px-6 lg:px-8">
		<div class="text-center mb-8">
			<a href="<?php echo esc_url( $profile ); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center space-x-3 mb-4 group">
				<?php veloria_icon_e( 'instagram', 24, 'w-6 h-6 text-[#b59a5c] group-hover:scale-110 transition-transform' ); ?>
				<span class="text-lg font-medium text-gray-900">@veloriavault</span>
			</a>
			<p class="text-gray-500 text-sm">Follow us on Instagram for the latest updates and behind-the-scenes</p>
		</div>
		<div id="instagram-feed-container" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
			<?php if ( $posts ) : ?>
				<?php foreach ( $posts as $post ) : ?>
					<a href="<?php echo esc_url( (string) $post['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="group relative aspect-square overflow-hidden bg-gradient-to-br from-[#faf8f5] via-white to-[#f7f1e6] block" title="<?php echo esc_attr( (string) ( $post['caption'] ?? '' ) ); ?>">
						<img src="<?php echo esc_url( (string) $post['imageUrl'] ); ?>" alt="<?php echo esc_attr( (string) ( $post['caption'] ?: 'Veloria Vault Instagram Post' ) ); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy" referrerpolicy="no-referrer">
						<div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center"><?php veloria_icon_e( 'instagram', 24, 'w-8 h-8 text-white' ); ?></div>
					</a>
				<?php endforeach; ?>
			<?php else : ?>
				<?php foreach ( $labels as $label ) : ?>
					<a href="<?php echo esc_url( $profile ); ?>" target="_blank" rel="noopener noreferrer" class="group aspect-square rounded-sm border border-[#b59a5c]/15 bg-gradient-to-br from-[#faf8f5] via-white to-[#f7f1e6] p-5 flex flex-col items-center justify-center text-center transition-transform duration-300 hover:-translate-y-1">
						<?php veloria_icon_e( 'instagram', 24, 'w-7 h-7 text-[#b59a5c] mb-3 group-hover:scale-110 transition-transform' ); ?>
						<span class="text-[11px] font-semibold tracking-[0.18em] uppercase text-gray-800">Veloria Vault</span>
						<span class="mt-2 text-xs text-gray-500 leading-relaxed"><?php echo esc_html( $label ); ?></span>
					</a>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="text-center mt-8">
			<a href="<?php echo esc_url( $profile ); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-8 py-3 border-2 border-black text-black text-xs font-bold tracking-[0.2em] uppercase hover:bg-black hover:text-white transition-all duration-300">Follow Us</a>
		</div>
	</div>
</section>
