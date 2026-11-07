<?php
/**
 * Template Name: Policy page
 *
 * Privacy, terms, shipping, returns, cancellation, warranty
 * (src/app/(pages)/* + StaticPage.tsx). Content is edited in WordPress.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

if ( veloria_elementor_render_page() ) { return; }

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<div class="min-h-screen bg-white">
		<div class="bg-[#1a1a1a] py-16 md:py-20">
			<div class="container mx-auto px-4 sm:px-6 lg:px-8">
				<h1 class="text-3xl md:text-4xl lg:text-5xl font-serif font-medium text-white"><?php the_title(); ?></h1>
			</div>
		</div>
		<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
			<?php if ( '' !== trim( get_the_content() ) ) : ?>
				<div class="prose prose-lg max-w-4xl mx-auto prose-headings:font-serif prose-a:text-[#b59a5c] hover:prose-a:text-[#a08a4f] text-gray-700 space-y-6">
					<?php the_content(); ?>
				</div>
			<?php else : ?>
				<div class="max-w-4xl mx-auto">
					<p class="text-gray-600 mb-6">We&apos;re currently updating our <?php echo esc_html( strtolower( get_the_title() ) ); ?>. Please check back soon or contact our support team for assistance.</p>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-flex items-center px-6 py-3 bg-[#1a1a1a] text-white text-sm font-medium hover:bg-[#b59a5c] transition-colors">Return to Home</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
endwhile;
get_footer();
