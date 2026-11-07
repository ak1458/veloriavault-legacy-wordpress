<?php
/**
 * Template Name: About
 *
 * src/app/(pages)/about/page.tsx
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

if ( veloria_elementor_render_page() ) { return; }

$values = array(
	array( 'Quality', 'Premium materials and meticulous craftsmanship in every piece' ),
	array( 'Timeless', 'Designs that transcend trends and last for generations' ),
	array( 'Functional', 'Practical elegance that fits your everyday life' ),
	array( 'Authentic', 'Genuine leather with honest, transparent sourcing' ),
);

get_header();
?>
<div class="min-h-screen bg-white">
	<div class="relative bg-[#1a1a1a] h-[300px] md:h-[400px] overflow-hidden">
		<div class="absolute inset-0">
			<img src="<?php echo esc_url( veloria_sized_asset_url( 'images/covers/about.png', '1536x1536' ) ); ?>" srcset="<?php echo esc_attr( veloria_theme_srcset( 'images/covers/about.png' ) ); ?>" sizes="100vw" alt="" class="w-full h-full object-cover object-center lg:object-[center_30%]" style="opacity: 0.6" fetchpriority="high">
			<div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-black/30"></div>
		</div>
		<div class="absolute inset-0 flex items-center justify-center z-10 px-4">
			<h1 class="text-4xl md:text-5xl lg:text-6xl font-serif font-medium text-white text-center">ABOUT US</h1>
		</div>
	</div>

	<section class="py-16 md:py-24 bg-white">
		<div class="container mx-auto px-4 sm:px-6 lg:px-8">
			<div class="max-w-3xl mx-auto text-center">
				<p class="text-lg md:text-xl text-gray-600 leading-relaxed font-light">Our collections reflect a commitment to luxury that endures—they are statements of elegance, strength, and individuality.</p>
				<div class="w-16 h-0.5 bg-[#b59a5c] mx-auto mt-8"></div>
			</div>
		</div>
	</section>

	<section class="py-16 md:py-24 bg-[#fafafa]">
		<div class="container mx-auto px-4 sm:px-6 lg:px-8">
			<div class="grid md:grid-cols-2 gap-12 lg:gap-16 items-center">
				<div class="relative aspect-[4/5] overflow-hidden rounded-lg">
					<img src="<?php echo esc_url( veloria_sized_asset_url( '/wp-content/uploads/2026/01/Bag-3-5-scaled.jpg', 'large', 'images/covers/about.png' ) ); ?>" alt="Veloria Vault Craftsmanship" sizes="(min-width: 1024px) 42vw, 100vw" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
				</div>
				<div class="space-y-6">
					<h2 class="text-3xl md:text-4xl font-serif text-gray-900">Where Our Journey Began</h2>
					<div class="space-y-4 text-gray-600 leading-relaxed">
						<p>After studying at leather institute and working in the fashion and export industry, I took a break to focus on my family. But even during that time, my eye never left the world of leather. I kept researching, observing, and studying the evolution of style, materials, and global trends.</p>
						<p>Veloria Vault was born from years of experience in design, fashion, and export. We saw a gap between quality and style—between tradition and modern life—and we set out to fill it.</p>
						<p class="italic text-gray-800">&ldquo;That&apos;s how Veloria Vault was born.&rdquo;</p>
						<p>We looked at what worked, what didn&apos;t, and what women were still looking for: handbags that perfectly balance everyday functionality with elegant design.</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="py-16 md:py-24 bg-white">
		<div class="container mx-auto px-4 sm:px-6 lg:px-8">
			<div class="grid md:grid-cols-2 gap-12 lg:gap-16 items-center">
				<div class="space-y-6 order-2 md:order-1">
					<h2 class="text-3xl md:text-4xl font-serif text-gray-900">The Art Of Genuine Leather</h2>
					<div class="space-y-4 text-gray-600 leading-relaxed">
						<p>Our passion for leather, design, and quiet luxury is what motivates us at Veloria Vault. Our bags are pieces that you&apos;ll want to carry every day and still love years from now.</p>
						<p>The handbags feel classic and are long-lasting because they are made with attention to detail, care, and purpose. We are committed to making bags that go beyond the current trends and elevate your everyday fashion.</p>
						<p>Our brand exudes elegance through fine craftsmanship, attention to detail, and the way each piece blends into everyday life. These timeless, functional designs can easily transition from day to night, from work to weekend.</p>
					</div>
				</div>
				<div class="relative aspect-[4/5] overflow-hidden rounded-lg order-1 md:order-2">
					<img src="<?php echo esc_url( veloria_sized_asset_url( '/wp-content/uploads/2026/01/Bag-16-4-scaled.jpg', 'large', 'images/hero/Ultra_realistic_luxury_202603301304.png' ) ); ?>" alt="Veloria Vault Genuine Leather" sizes="(min-width: 1024px) 42vw, 100vw" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
				</div>
			</div>
		</div>
	</section>

	<section class="py-16 md:py-24 bg-[#1a1a1a] text-white">
		<div class="container mx-auto px-4 sm:px-6 lg:px-8">
			<div class="text-center mb-10 md:mb-12">
				<h2 class="text-3xl md:text-4xl font-serif mb-4">Our Values</h2>
				<p class="text-gray-400 max-w-2xl mx-auto">What drives us to create exceptional leather goods</p>
			</div>
			<div class="grid grid-cols-2 gap-x-4 gap-y-6 sm:gap-8 lg:grid-cols-4">
				<?php foreach ( $values as list( $title, $desc ) ) : ?>
					<div class="text-center px-2 py-3 sm:p-6">
						<h3 class="mb-2 font-serif text-lg text-[#b59a5c] sm:mb-3 sm:text-xl"><?php echo esc_html( $title ); ?></h3>
						<p class="text-xs leading-relaxed text-gray-400 sm:text-sm"><?php echo esc_html( $desc ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="py-16 md:py-24 bg-white">
		<div class="container mx-auto px-4 sm:px-6 lg:px-8">
			<div class="max-w-4xl mx-auto text-center">
				<blockquote class="text-2xl md:text-3xl font-serif text-gray-800 leading-relaxed italic">&ldquo;We create leather handbags for women who respect and value simplicity, style, and quality. For women who leave their mark without chasing fads. For confident women who lead with quiet assurance.&rdquo;</blockquote>
				<div class="mt-8 flex justify-center"><div class="w-16 h-0.5 bg-[#b59a5c]"></div></div>
			</div>
		</div>
	</section>
</div>
<?php
get_footer();
