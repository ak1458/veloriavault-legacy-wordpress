<?php
/** Native Elementor starter documents. Explicit CLI apply, backups and no silent overwrite. */
defined( 'ABSPATH' ) || exit;

class Veloria_Elementor_Seed {
	private static int $sequence = 0;
	private bool $apply = false;
	private bool $replace = false;

	private static function id(): string { return substr( md5( 'veloria-builder-v1-' . ++self::$sequence ), 0, 8 ); }
	private static function widget( string $type, array $settings = array(), array $children = array() ): array {
		return array( 'id' => self::id(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => $children, 'isInner' => false );
	}
	private static function box( string $name, array $children, array $settings = array() ): array {
		return array( 'id' => self::id(), 'elType' => 'container', 'isInner' => false, 'settings' => array_merge( array( '_title' => $name, 'content_width' => 'boxed', 'boxed_width' => array( 'unit' => 'px', 'size' => 1200 ), 'flex_direction' => 'column', 'gap' => array( 'unit' => 'px', 'size' => 24, 'column' => '24', 'row' => '24' ), 'padding' => array( 'unit' => 'px', 'top' => '48', 'right' => '24', 'bottom' => '48', 'left' => '24', 'isLinked' => false ) ), $settings ), 'elements' => $children );
	}
	private static function heading( string $text, string $tag = 'h2', string $color = '#1f1a13', int $size = 36 ): array {
		return self::widget( 'heading', array( 'title' => $text, 'header_size' => $tag, 'title_color' => $color, 'typography_typography' => 'custom', 'typography_font_family' => 'Playfair Display', 'typography_font_size' => array( 'unit' => 'px', 'size' => $size ), 'typography_font_size_mobile' => array( 'unit' => 'px', 'size' => min( $size, 30 ) ), 'typography_font_weight' => '400' ) );
	}
	private static function text( string $html, string $color = '#5c564d' ): array { return self::widget( 'text-editor', array( 'editor' => $html, 'text_color' => $color, 'typography_typography' => 'custom', 'typography_font_family' => 'Lato', 'typography_font_size' => array( 'unit' => 'px', 'size' => 16 ), 'typography_line_height' => array( 'unit' => 'em', 'size' => 1.8 ) ) ); }
	private static function button( string $text, string $url ): array { return self::widget( 'button', array( 'text' => $text, 'link' => array( 'url' => $url ), 'background_color' => '#1a1a1a', 'button_text_color' => '#ffffff', 'typography_typography' => 'custom', 'typography_font_family' => 'Lato', 'typography_font_size' => array( 'unit' => 'px', 'size' => 13 ), 'typography_text_transform' => 'uppercase' ) ); }
	private static function image( string $url ): array { return self::widget( 'image', array( 'image' => array( 'id' => 0, 'url' => $url ), 'image_size' => 'full', 'width' => array( 'unit' => '%', 'size' => 100 ) ) ); }
	private static function row( string $name, array $children ): array { return self::box( $name, $children, array( 'flex_direction' => 'row', 'flex_direction_tablet' => 'column', 'flex_align_items' => 'center' ) ); }
	private static function column( string $name, array $children ): array { return self::box( $name, $children, array( 'content_width' => 'full', 'width' => array( 'unit' => '%', 'size' => 50 ), 'width_tablet' => array( 'unit' => '%', 'size' => 100 ), 'padding' => array( 'unit' => 'px', 'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'isLinked' => true ) ) ); }

	/**
	 * Preview or install editable starter pages and theme locations.
	 *
	 * ## OPTIONS
	 * [--apply]
	 * : Save documents. Without this flag, report only.
	 * [--replace]
	 * : Explicitly replace previous Veloria builder documents, preserving a backup.
	 *
	 * ## EXAMPLES
	 *     wp veloria-builder seed
	 *     wp veloria-builder seed --apply
	 */
	public function seed( array $args, array $flags ): void {
		if ( ! class_exists( '\Elementor\Plugin' ) ) { \WP_CLI::error( 'Activate Elementor first. No data changed.' ); }
		$this->apply = isset( $flags['apply'] );
		$this->replace = isset( $flags['replace'] );
		self::$sequence = 0;
		$this->page( 'home', 'Home', $this->home(), true );
		$this->page( 'about', 'About Us', $this->about() );
		$this->page( 'contact-us', 'Contact Us', $this->contact() );
		foreach ( array( 'track-order' => 'Track Your Order', 'wishlist' => 'My Wishlist', 'login' => 'Welcome Back', 'register' => 'Create Your Account' ) as $slug => $title ) {
			$this->page( $slug, $title, array( self::box( $title, array( self::heading( $title, 'h1' ), self::widget( 'veloria-customer-tool', array( 'tool' => $slug ) ) ) ) ) );
		}
		$this->page( 'cart', 'Cart', array( self::box( 'Shopping bag', array( self::heading( 'Your Shopping Bag', 'h1' ), self::widget( 'woocommerce-cart' ) ) ) ) );
		$this->page( 'checkout', 'Checkout', array( self::box( 'Secure checkout', array( self::heading( 'Secure Checkout', 'h1' ), self::widget( 'woocommerce-checkout-page' ) ) ) ) );
		$this->page( 'account', 'My Account', array( self::box( 'Customer account', array( self::heading( 'My Account', 'h1' ), self::widget( 'woocommerce-my-account' ) ) ) ) );
		foreach ( array( 'privacy-policy' => 'Privacy Policy', 'shipping-policy' => 'Shipping Policy', 'refund-returns' => 'Return Policy', 'cancellation-refund' => 'Cancellation and Refunds', 'warranty-policy' => 'Warranty Policy', 'terms-conditions' => 'Terms and Conditions' ) as $slug => $title ) {
			$this->page( $slug, $title, $this->policy( $slug, $title ) );
		}
		$this->template( 'veloria-builder-header', 'Veloria Site Header', 'header', $this->header(), array( array( 'type' => 'include', 'name' => 'general' ) ) );
		$this->template( 'veloria-builder-footer', 'Veloria Site Footer', 'footer', $this->footer(), array( array( 'type' => 'include', 'name' => 'general' ) ) );
		$this->template( 'veloria-builder-product', 'Veloria Product', 'product', $this->product(), array( array( 'type' => 'include', 'name' => 'woocommerce', 'sub_name' => 'product' ) ) );
		$this->template( 'veloria-builder-404', 'Veloria Page Not Found', 'error-404', array( self::box( 'Page not found', array( self::heading( '404', 'h1', '#b59a5c', 72 ), self::heading( 'Page Not Found' ), self::text( '<p>The page you are looking for could not be found. Explore our latest collection.</p>' ), self::button( 'Continue shopping', home_url( '/shop' ) ) ) ) ), array( array( 'type' => 'include', 'name' => 'singular', 'sub_name' => 'not_found404' ) ) );
		$this->template( 'veloria-builder-catalog', 'Veloria Product Catalog', 'product-archive', array( self::box( 'Shop heading', array( self::heading( 'Shop the Collection', 'h1' ), self::text( '<p>Timeless leather goods for the modern minimalist.</p>' ), self::widget( 'wc-archive-products', array( 'columns' => 4, 'columns_tablet' => 2, 'columns_mobile' => 2, 'paginate' => 'yes', 'allow_order' => 'yes', 'show_result_count' => 'yes' ) ) ) ) ), array( array( 'type' => 'include', 'name' => 'woocommerce', 'sub_name' => 'product_archive' ) ) );
		if ( $this->apply ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
			if ( class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_cache()->regenerate();
		}
		\WP_CLI::success( $this->apply ? 'Builder documents saved. Test pages and editor on staging before release.' : 'Dry run only. Add --apply to save. Existing builder documents are skipped.' );
	}

	private function supported( array $elements ): bool {
		foreach ( $elements as $element ) {
			if ( isset( $element['widgetType'] ) && ! \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $element['widgetType'] ) ) { \WP_CLI::warning( 'Unavailable widget ' . $element['widgetType'] . '; this document is skipped. Activate a legitimately licensed Elementor plan with these features.' ); return false; }
			if ( ! $this->supported( $element['elements'] ?? array() ) ) { return false; }
		}
		return true;
	}

	/** Every policy heading and paragraph is a separate editable widget. */
	private function policy( string $slug, string $title ): array {
		$content = file_get_contents( VELORIA_COMMERCE_DIR . 'content/policies/' . $slug . '.html' );
		$doc = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8" ?><body>' . $content . '</body>' );
		libxml_clear_errors(); libxml_use_internal_errors( $previous );
		$widgets = array( self::heading( $title, 'h1' ) );
		foreach ( $doc->getElementsByTagName( 'body' )->item( 0 )->childNodes as $node ) {
			if ( ! $node instanceof DOMElement ) continue;
			$widgets[] = in_array( $node->tagName, array( 'h2', 'h3' ), true ) ? self::heading( $node->textContent, $node->tagName, '#1f1a13', 26 ) : self::text( (string) $doc->saveHTML( $node ) );
		}
		return array( self::box( $title, $widgets ) );
	}

	private function page( string $slug, string $title, array $elements, bool $front = false ): void {
		if ( ! $this->supported( $elements ) ) { return; }
		$id = $front ? (int) get_option( 'page_on_front' ) : 0;
		$page = $id ? get_post( $id ) : get_page_by_path( $slug );
		if ( $page && 'yes' === get_post_meta( $page->ID, '_veloria_elementor_enabled', true ) && ! $this->replace ) { \WP_CLI::log( 'Skip existing builder page: ' . $slug ); return; }
		\WP_CLI::log( ( $this->apply ? 'Save page: ' : 'Would save page: ' ) . $slug );
		if ( ! $this->apply ) { return; }
		$id = $page ? (int) $page->ID : wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug ), true );
		if ( is_wp_error( $id ) ) { \WP_CLI::error( $id->get_error_message() ); }
		$this->save( $id, $elements, 'wp-page' );
		update_post_meta( $id, '_veloria_elementor_enabled', 'yes' );
		if ( $front ) { update_option( 'show_on_front', 'page' ); update_option( 'page_on_front', $id ); }
	}

	private function template( string $slug, string $title, string $type, array $elements, array $conditions ): void {
		$manager = \Elementor\Plugin::$instance->documents;
		if ( ! $manager->get_document_type( $type, false ) || ! $this->supported( $elements ) ) { \WP_CLI::warning( 'Theme Builder ' . $type . ' unavailable; existing PHP fallback retained.' ); return; }
		$posts = get_posts( array( 'post_type' => 'elementor_library', 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1 ) );
		if ( $posts && ! $this->replace ) { \WP_CLI::log( 'Skip existing builder template: ' . $slug ); return; }
		\WP_CLI::log( ( $this->apply ? 'Save template: ' : 'Would save template: ' ) . $slug );
		if ( ! $this->apply ) { return; }
		$id = $posts ? (int) $posts[0]->ID : wp_insert_post( array( 'post_type' => 'elementor_library', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug ), true );
		if ( is_wp_error( $id ) ) { \WP_CLI::error( $id->get_error_message() ); }
		$this->save( $id, $elements, $type );
		\ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->save_conditions( $id, $conditions );
	}

	private function save( int $id, array $elements, string $type ): void {
		$backup = array( 'saved_at' => gmdate( 'c' ), 'content' => get_post_field( 'post_content', $id ), 'meta' => array() );
		foreach ( array( '_elementor_data', '_elementor_edit_mode', '_elementor_template_type', '_elementor_page_settings', '_elementor_conditions', '_veloria_elementor_enabled', '_wp_page_template' ) as $key ) { $backup['meta'][ $key ] = get_post_meta( $id, $key, true ); }
		// Meta writes unslash their input; slash it so the backed-up Elementor JSON survives intact.
		add_post_meta( $id, '_veloria_builder_backup', wp_slash( $backup ) );
		update_post_meta( $id, '_elementor_template_type', $type );
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
		update_post_meta( $id, '_elementor_page_settings', array( 'hide_title' => 'yes' ) );
		update_post_meta( $id, '_wp_page_template', 'default' );
		update_post_meta( $id, '_veloria_builder_seed_version', 1 );
		if ( 'elementor_library' === get_post_type( $id ) ) { wp_set_object_terms( $id, $type, 'elementor_library_type' ); }
		delete_post_meta( $id, '_elementor_css' );
		clean_post_cache( $id );
	}

	private function home(): array {
		$gallery = array();
		foreach ( array( 'Ultra_realistic_luxury_202603301304.png', 'Ultra_realistic_luxury_202603301304 (1).png', 'Ultra_realistic_luxury_202603301309.png', 'Ultra_realistic_luxury_202603301309 (1).png' ) as $file ) { $gallery[] = array( 'id' => 0, 'url' => veloria_theme_asset( 'images/hero/' . $file ) ); }
		$hero = self::box( 'Hero — edit headline, copy and slideshow separately', array( self::heading( 'LUXURY<br><em>locked in leather</em>', 'h1', '#ffffff', 72 ), self::text( '<p>Each piece holds more than design. It is a possession. A presence. A promise. Handcrafted for the modern minimalist.</p>', '#ffffff' ), self::button( 'Explore the collection', home_url( '/shop' ) ) ), array( 'min_height' => array( 'unit' => 'vh', 'size' => 78 ), 'flex_justify_content' => 'center', 'background_background' => 'slideshow', 'background_slideshow_gallery' => $gallery, 'background_slideshow_slide_duration' => 6000, 'background_slideshow_transition_duration' => 1200, 'background_overlay_background' => 'classic', 'background_overlay_color' => '#000000', 'background_overlay_opacity' => array( 'unit' => 'px', 'size' => 0.35 ) ) );
		$features = array();
		foreach ( array( array( 'Genuine Leather', 'Carefully selected natural hides with durability, flexibility, and a refined finish.' ), array( 'Designed With Intent', 'Every curve, cut, and contour is shaped with purpose so beauty never fights function.' ), array( 'Hand Crafted In India', 'Built to move through daily life while holding shape, softness, and structure.' ) ) as $feature ) { $features[] = self::column( $feature[0], array( self::heading( $feature[0], 'h3', '#1f1a13', 22 ), self::text( '<p>' . esc_html( $feature[1] ) . '</p>' ) ) ); }
		$spotlight = (array) veloria_setting( 'spotlight', array() );
		$spot_copy = array( self::heading( (string) ( $spotlight['eyebrow'] ?? 'Designed For Everyday Elegance' ), 'h3', '#b59a5c', 18 ), self::heading( (string) ( $spotlight['title'] ?? 'Donna Tote' ) ) );
		foreach ( (array) ( $spotlight['paragraphs'] ?? array() ) as $paragraph ) { $spot_copy[] = self::text( '<p>' . esc_html( $paragraph ) . '</p>' ); }
		$spot_copy[] = self::button( 'View details', home_url( '/shop?search=Donna' ) );
		$tabs = array(); $children = array();
		foreach ( (array) ( veloria_setting( 'most_loved', array() )['tabs'] ?? array() ) as $tab ) {
			$tabs[] = array( '_id' => self::id(), 'tab_title' => $tab['label'] );
			$children[] = self::box( $tab['label'] . ' products', array( self::widget( 'veloria-products', array( 'category' => $tab['categorySlug'], 'count' => $tab['maxItems'] ?? 8 ) ), self::button( 'View all ' . $tab['label'], home_url( $tab['viewAllHref'] ?? '/shop' ) ) ), array( 'content_width' => 'full' ) );
		}
		$most_loved = (array) veloria_setting( 'most_loved', array() );
		return array( $hero, self::box( 'Current discounts — follows store rules', array( self::widget( 'veloria-offers' ) ) ), self::row( 'Craft and materials', $features ), self::row( 'Customer stories', array( self::column( 'Review copy', array( self::heading( 'What Our Customers Say', 'h3', '#b59a5c', 18 ), self::heading( 'Quiet luxury, built to last.' ), self::widget( 'veloria-reviews', array( 'count' => 3 ) ) ) ), self::column( 'Review editorial image', array( self::image( veloria_asset_url( '/wp-content/uploads/2026/01/Bag-3-5-scaled.jpg', 'images/covers/about.png' ) ) ) ) ) ), self::row( 'Donna spotlight', array( self::column( 'Spotlight image', array( self::image( veloria_asset_url( (string) ( $spotlight['image'] ?? '' ), 'images/covers/about.png' ) ) ) ), self::column( 'Spotlight editorial', $spot_copy ) ) ), self::box( 'Hot sellers', array( self::heading( (string) veloria_setting( 'hot_seller_label', 'Hot Seller' ), 'h3', '#b59a5c', 18 ), self::heading( (string) veloria_setting( 'hot_seller_title', '' ) ), self::widget( 'veloria-products', array( 'product_ids' => array_map( 'strval', (array) veloria_setting( 'hot_seller_ids', array() ) ), 'count' => 4 ) ) ) ), self::box( 'Most loved styles', array( self::heading( (string) ( $most_loved['eyebrow'] ?? 'Most Loved Styles' ), 'h3', '#b59a5c', 18 ), self::heading( (string) ( $most_loved['title'] ?? 'Choose the silhouette that fits her rhythm.' ) ), self::widget( 'nested-tabs', array( 'tabs' => $tabs ), $children ) ) ), self::box( 'Instagram', array( self::heading( '@veloriavault' ), self::text( '<p>Follow us on Instagram for the latest updates and behind-the-scenes.</p>' ), self::button( 'Follow us', 'https://www.instagram.com/veloriavault/' ) ) ), self::row( 'Store promises', array( self::column( 'Replacements', array( self::heading( 'Hassle-Free Replacements', 'h3', '#1f1a13', 20 ), self::text( '<p>Contact support for easy replacements.</p>' ) ) ), self::column( 'Shipping', array( self::heading( 'Shipping', 'h3', '#1f1a13', 20 ), self::text( '<p>See current delivery and shipping terms at checkout.</p>' ), self::button( 'Shipping policy', home_url( '/shipping-policy' ) ) ) ), self::column( 'Checkout', array( self::heading( 'Secure Checkout', 'h3', '#1f1a13', 20 ), self::text( '<p>Protected payment flow and verified transactions.</p>' ) ) ) ) ) );
	}

	private function about(): array {
		return array( self::box( 'About cover', array( self::heading( 'ABOUT US', 'h1', '#ffffff', 56 ) ), array( 'background_background' => 'classic', 'background_image' => array( 'url' => veloria_theme_asset( 'images/covers/about.png' ), 'id' => 0 ), 'background_position' => 'center center', 'background_size' => 'cover', 'min_height' => array( 'unit' => 'px', 'size' => 360 ), 'flex_justify_content' => 'center' ) ), self::box( 'Introduction', array( self::text( '<p>Our collections reflect a commitment to luxury that endures—they are statements of elegance, strength, and individuality.</p>' ) ) ), self::row( 'Our journey', array( self::column( 'Craftsmanship image', array( self::image( veloria_asset_url( '/wp-content/uploads/2026/01/Bag-3-5-scaled.jpg', 'images/covers/about.png' ) ) ) ), self::column( 'Journey copy', array( self::heading( 'Where Our Journey Began' ), self::text( '<p>After studying at leather institute and working in the fashion and export industry, I took a break to focus on my family. But even during that time, my eye never left the world of leather. I kept researching, observing, and studying the evolution of style, materials, and global trends.</p><p>Veloria Vault was born from years of experience in design, fashion, and export. We saw a gap between quality and style—between tradition and modern life—and we set out to fill it.</p><p><em>That’s how Veloria Vault was born.</em></p><p>We looked at what worked, what didn’t, and what women were still looking for: handbags that perfectly balance everyday functionality with elegant design.</p>' ) ) ) ) ), self::row( 'Genuine leather', array( self::column( 'Leather copy', array( self::heading( 'The Art Of Genuine Leather' ), self::text( '<p>Our passion for leather, design, and quiet luxury is what motivates us at Veloria Vault. Our bags are pieces that you’ll want to carry every day and still love years from now.</p><p>The handbags feel classic and are long-lasting because they are made with attention to detail, care, and purpose. We are committed to making bags that go beyond the current trends and elevate your everyday fashion.</p><p>Our brand exudes elegance through fine craftsmanship, attention to detail, and the way each piece blends into everyday life. These timeless, functional designs can easily transition from day to night, from work to weekend.</p>' ) ) ), self::column( 'Leather editorial image', array( self::image( veloria_asset_url( '/wp-content/uploads/2026/01/Bag-16-4-scaled.jpg', 'images/covers/about.png' ) ) ) ) ) ), self::box( 'Our values', array( self::heading( 'Our Values' ), self::text( '<p>What drives us to create exceptional leather goods</p>' ), self::row( 'Value cards', array_map( static fn( $value ) => self::column( $value[0], array( self::heading( $value[0], 'h3', '#b59a5c', 24 ), self::text( '<p>' . $value[1] . '</p>' ) ) ), array( array( 'Quality', 'Premium materials and meticulous craftsmanship in every piece' ), array( 'Timeless', 'Designs that transcend trends and last for generations' ), array( 'Functional', 'Practical elegance that fits your everyday life' ), array( 'Authentic', 'Genuine leather with honest, transparent sourcing' ) ) ) ) ) ), self::box( 'Explore the collection', array( self::heading( 'Find Your Everyday Companion' ), self::button( 'Shop the collection', home_url( '/shop' ) ) ) ) );
	}

	private function contact(): array {
		$email = (string) veloria_setting( 'support_email', 'care@veloriavault.com' ); $phone = (string) veloria_setting( 'support_phone', '' );
		return array( self::box( 'Contact cover', array( self::heading( 'CONTACT US', 'h1', '#ffffff', 56 ), self::text( '<p>We’d love to hear from you. Reach out for any questions, support, or just to say hello.</p>', '#ffffff' ) ), array( 'background_background' => 'classic', 'background_image' => array( 'url' => veloria_theme_asset( 'images/covers/contact us.png' ), 'id' => 0 ), 'background_size' => 'cover', 'min_height' => array( 'unit' => 'px', 'size' => 360 ), 'flex_justify_content' => 'center' ) ), self::row( 'Contact methods', array( self::column( 'Email', array( self::heading( 'Email Us', 'h3' ), self::button( $email, 'mailto:' . $email ) ) ), self::column( 'Telephone', array( self::heading( 'Call Us', 'h3' ), self::button( $phone, 'tel:' . preg_replace( '/[^+0-9]/', '', $phone ) ) ) ) ) ), self::row( 'Contact and support', array( self::column( 'Message form', array( self::heading( 'Send Us a Message' ), self::text( '<p>Fill out the form below and we’ll get back to you within 24 hours.</p>' ), self::widget( 'veloria-contact' ) ) ), self::column( 'Support image and copy', array( self::image( veloria_theme_asset( 'images/covers/about.png' ) ), self::heading( 'Why Choose Veloria Vault?', 'h3' ), self::text( '<ul><li>Premium genuine leather craftsmanship</li><li>Hassle-free replacement policy</li><li>Secure checkout &amp; customer support</li></ul>' ) ) ) ) ) );
	}

	private function menu(): int {
		$menu = wp_get_nav_menu_object( 'Veloria Main Navigation' );
		if ( $menu ) { return (int) $menu->term_id; }
		if ( ! $this->apply ) { return 0; }
		$id = wp_create_nav_menu( 'Veloria Main Navigation' );
		if ( is_wp_error( $id ) ) { \WP_CLI::error( $id->get_error_message() ); }
		foreach ( array( 'Shop' => '/shop', 'Tote Bags' => '/product-category/tote-bag', 'Sling Bags' => '/product-category/sling-bag', 'About' => '/about', 'Contact' => '/contact-us' ) as $label => $url ) { wp_update_nav_menu_item( $id, 0, array( 'menu-item-title' => $label, 'menu-item-url' => home_url( $url ), 'menu-item-status' => 'publish' ) ); }
		$locations = get_theme_mod( 'nav_menu_locations', array() ); $locations['primary'] = $id; set_theme_mod( 'nav_menu_locations', $locations );
		return (int) $id;
	}

	private function header(): array {
		$nav = self::widget( 'nav-menu', array( 'menu' => (string) $this->menu(), 'layout' => 'horizontal', 'align_items' => 'center', 'dropdown' => 'tablet', 'color_menu_item' => '#1a1a1a', 'color_menu_item_hover' => '#b59a5c' ) );
		return array( self::box( 'Header offers', array( self::widget( 'veloria-offers' ) ), array( 'padding' => array( 'unit' => 'px', 'top' => '8', 'right' => '24', 'bottom' => '8', 'left' => '24', 'isLinked' => false ), 'background_background' => 'classic', 'background_color' => '#faf8f5' ) ), self::row( 'Brand and store tools', array( self::column( 'Brand logo', array( self::widget( 'image', array( 'image' => array( 'url' => veloria_theme_asset( 'logo.svg' ), 'id' => 0 ), 'link_to' => 'custom', 'link' => array( 'url' => home_url( '/' ) ), 'width' => array( 'unit' => 'px', 'size' => 196 ) ) ) ) ), self::column( 'Store actions', array( self::widget( 'veloria-store-actions' ) ) ) ) ), self::box( 'Main navigation — edit links in Appearance → Menus', array( $nav ), array( 'padding' => array( 'unit' => 'px', 'top' => '0', 'right' => '24', 'bottom' => '12', 'left' => '24', 'isLinked' => false ) ) ) );
	}

	private function footer(): array {
		$links = array();
		foreach ( veloria_footer_links() as $link ) { $links[] = self::button( $link['label'], home_url( $link['href'] ) ); }
		$collections = array(); foreach ( veloria_footer_collections() as $link ) { $collections[] = self::button( $link['label'], home_url( $link['href'] ) ); }
		return array( self::box( 'Footer', array( self::row( 'Footer columns', array( self::column( 'Brand and social', array( self::heading( 'VELORIA VAULT', 'h3', '#ffffff', 26 ), self::text( '<p>Timeless leather goods for the modern minimalist. Handcrafted with precision and designed to last a lifetime.</p>', '#dddddd' ), self::button( 'Instagram', 'https://www.instagram.com/veloriavault/' ), self::button( 'Facebook', 'https://www.facebook.com/p/Veloria-Vault-61568488662553/' ) ) ), self::column( 'Collections', array_merge( array( self::heading( 'Collections', 'h3', '#b59a5c', 22 ) ), $collections ) ), self::column( 'Important links', array_merge( array( self::heading( 'Important Links', 'h3', '#b59a5c', 22 ) ), $links ) ), self::column( 'Newsletter', array( self::heading( 'Newsletter', 'h3', '#b59a5c', 22 ), self::text( '<p>Subscribe for early access to new drops and exclusive offers.</p>', '#dddddd' ), self::widget( 'veloria-newsletter' ) ) ) ) ), self::text( '<p>© ' . wp_date( 'Y' ) . ' Veloria Vault. All rights reserved.</p>', '#aaaaaa' ), self::widget( 'veloria-chat' ) ), array( 'background_background' => 'classic', 'background_color' => '#1a1a1a' ) ) );
	}

	private function product(): array {
		return array( self::row( 'Product details', array( self::column( 'Product gallery', array( self::widget( 'woocommerce-product-images' ) ) ), self::column( 'Product information', array( self::widget( 'woocommerce-product-title' ), self::widget( 'woocommerce-product-rating' ), self::widget( 'woocommerce-product-price' ), self::widget( 'woocommerce-product-short-description' ), self::widget( 'woocommerce-product-add-to-cart' ), self::widget( 'veloria-offers' ), self::widget( 'woocommerce-product-meta' ) ) ) ) ), self::box( 'Description and reviews', array( self::widget( 'woocommerce-product-data-tabs' ) ) ) );
	}
}
