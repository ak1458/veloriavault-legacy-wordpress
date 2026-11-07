<?php
/** Small dynamic widgets; all editorial copy/layout uses native Elementor widgets. */
defined( 'ABSPATH' ) || exit;

abstract class Veloria_Elementor_Widget extends \Elementor\Widget_Base {
	public function get_categories(): array { return array( 'general' ); }
	public function get_icon(): string { return 'eicon-woocommerce'; }
	protected function is_dynamic_content(): bool { return true; }
	protected function start_controls(): void { $this->start_controls_section( 'content', array( 'label' => __( 'Content', 'veloria' ) ) ); }
}

class Veloria_Elementor_Products extends Veloria_Elementor_Widget {
	public function get_name(): string { return 'veloria-products'; }
	public function get_title(): string { return 'Veloria Product Grid'; }
	protected function register_controls(): void {
		$this->start_controls();
		$options = array();
		foreach ( wc_get_products( array( 'limit' => 250, 'status' => 'publish', 'type' => array( 'simple', 'variable' ) ) ) as $product ) { $options[ $product->get_id() ] = $product->get_name(); }
		$this->add_control( 'product_ids', array( 'label' => 'Products (leave empty for category)', 'type' => \Elementor\Controls_Manager::SELECT2, 'multiple' => true, 'options' => $options ) );
		$categories = array( '' => 'All categories' );
		foreach ( (array) get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $term ) { if ( $term instanceof WP_Term ) { $categories[ $term->slug ] = $term->name; } }
		$this->add_control( 'category', array( 'label' => 'Category', 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $categories, 'default' => '' ) );
		$this->add_control( 'count', array( 'label' => 'Products to show', 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 1, 'max' => 48, 'default' => 4 ) );
		$this->add_responsive_control( 'columns', array( 'label' => 'Columns', 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 1, 'max' => 6, 'default' => 4, 'tablet_default' => 2, 'mobile_default' => 2, 'selectors' => array( '{{WRAPPER}} .veloria-builder-products ul.products' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ) ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$s = $this->get_settings_for_display();
		$ids = array_values( array_filter( array_map( 'absint', (array) ( $s['product_ids'] ?? array() ) ) ) );
		$limit = max( 1, min( 48, (int) ( $s['count'] ?? 4 ) ) );
		$columns = max( 1, min( 6, (int) ( $s['columns'] ?? 4 ) ) );
		// The native product loop retains wishlist, swatch, pricing and other
		// extension hooks and avoids nested Store API dispatch in Elementor.
		$filter = $ids ? ' ids="' . implode( ',', $ids ) . '" orderby="post__in"' : ' category="' . esc_attr( sanitize_title( (string) ( $s['category'] ?? '' ) ) ) . '"';
		echo '<div class="veloria-builder-products">';
		echo do_shortcode( '[products limit="' . $limit . '" columns="' . $columns . '"' . $filter . ']' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}
}

class Veloria_Elementor_Reviews extends Veloria_Elementor_Widget {
	public function get_name(): string { return 'veloria-reviews'; }
	public function get_title(): string { return 'Veloria Customer Reviews'; }
	protected function register_controls(): void {
		$this->start_controls();
		$this->add_control( 'count', array( 'label' => 'Reviews to show', 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 1, 'max' => 20, 'default' => 5 ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$reviews = veloria_catalog()->reviews( 0, max( 1, min( 20, (int) $this->get_settings_for_display( 'count' ) ) ) );
		foreach ( $reviews as $review ) {
			echo '<figure style="padding:20px 0;border-bottom:1px solid #e9e2d5"><blockquote style="border-left:3px solid #b59a5c;padding-left:18px;font-size:18px">' . wp_kses_post( $review['review'] ) . '</blockquote><figcaption style="margin-top:12px">' . esc_html( $review['reviewer'] ) . ' <span style="color:#b59a5c" aria-label="' . esc_attr( (string) $review['rating'] ) . ' out of 5">' . esc_html( str_repeat( '★', max( 0, min( 5, (int) $review['rating'] ) ) ) ) . '</span></figcaption></figure>';
		}
		if ( ! $reviews ) { echo '<p>Customer reviews will appear here after approval.</p>'; }
	}
}

class Veloria_Elementor_Contact extends Veloria_Elementor_Widget {
	public function get_name(): string { return 'veloria-contact'; }
	public function get_title(): string { return 'Veloria Contact Form'; }
	protected function register_controls(): void {
		$this->start_controls();
		$this->add_control( 'button_text', array( 'label' => 'Submit button', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Send Message' ) );
		$this->add_control( 'success_text', array( 'label' => 'Success message', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Thank you for reaching out. We will get back to you soon.' ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$s = $this->get_settings_for_display();
		$id = 'veloria-contact-' . $this->get_id();
		?>
		<div x-data="contact()"><p x-show="submitted" x-cloak role="status"><?php echo esc_html( $s['success_text'] ); ?></p>
		<form x-show="!submitted" @submit.prevent="submit()" class="space-y-5">
		<?php foreach ( array( 'name' => 'Your Name', 'email' => 'Your Email', 'subject' => 'Subject', 'message' => 'Your Message' ) as $key => $label ) : ?>
		<div><label for="<?php echo esc_attr( $id . '-' . $key ); ?>" class="block mb-2"><?php echo esc_html( $label ); ?></label>
		<?php if ( 'message' === $key ) : ?><textarea required id="<?php echo esc_attr( $id . '-' . $key ); ?>" x-model="form.message" rows="5" class="w-full border border-gray-200 rounded-lg p-3"></textarea>
		<?php else : ?><input required id="<?php echo esc_attr( $id . '-' . $key ); ?>" type="<?php echo 'email' === $key ? 'email' : 'text'; ?>" x-model="form.<?php echo esc_attr( $key ); ?>" class="w-full border border-gray-200 rounded-lg p-3">
		<?php endif; ?></div>
		<?php endforeach; ?>
		<p x-show="error" x-cloak x-text="error" role="alert" class="text-red-600"></p><button type="submit" :disabled="submitting" class="w-full bg-black text-white rounded-lg p-4"><?php echo esc_html( $s['button_text'] ); ?></button>
		</form></div>
		<?php
	}
}

class Veloria_Elementor_Newsletter extends Veloria_Elementor_Widget {
	public function get_name(): string { return 'veloria-newsletter'; }
	public function get_title(): string { return 'Veloria Newsletter Form'; }
	protected function register_controls(): void {
		$this->start_controls();
		$this->add_control( 'button_text', array( 'label' => 'Button text', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Subscribe' ) );
		$this->end_controls_section();
	}
	protected function render(): void { ?>
		<form x-data="newsletter()" @submit.prevent="submit()" class="space-y-3"><label class="sr-only" for="newsletter-<?php echo esc_attr( $this->get_id() ); ?>">Email</label><input id="newsletter-<?php echo esc_attr( $this->get_id() ); ?>" type="email" required x-model="email" :disabled="loading" placeholder="Email address" class="w-full p-3 border border-gray-300 text-black"><button type="submit" :disabled="loading" class="w-full bg-[#b59a5c] text-white p-3"><?php echo esc_html( $this->get_settings_for_display( 'button_text' ) ); ?></button><p x-show="message" x-cloak x-text="message ? message.text : ''" role="status"></p></form>
	<?php }
}

class Veloria_Elementor_Store_Actions extends Veloria_Elementor_Widget {
	public function get_name(): string { return 'veloria-store-actions'; }
	public function get_title(): string { return 'Veloria Store Actions'; }
	protected function register_controls(): void {
		$this->start_controls();
		foreach ( array( 'track' => 'Track order', 'account' => 'Account', 'wishlist' => 'Wishlist', 'cart' => 'Cart' ) as $key => $label ) { $this->add_control( $key . '_label', array( 'label' => $label . ' label', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => $label ) ); }
		$this->end_controls_section();
	}
	protected function render(): void {
		$s = $this->get_settings_for_display();
		echo '<nav aria-label="Store tools" style="display:flex;flex-wrap:wrap;gap:18px;font-size:13px" x-data="{}">';
		foreach ( array( 'track' => '/track-order', 'account' => '/account', 'wishlist' => '/wishlist', 'cart' => '/cart' ) as $key => $url ) { echo '<a href="' . esc_url( home_url( $url ) ) . '">' . esc_html( $s[ $key . '_label' ] ) . ( 'cart' === $key ? ' (<span x-text="$store.cart.totalItems">0</span>)' : '' ) . '</a>'; }
		echo '</nav>';
	}
}

class Veloria_Elementor_Offers extends Veloria_Elementor_Widget {
	public function get_name(): string { return 'veloria-offers'; }
	public function get_title(): string { return 'Veloria Current Offers'; }
	protected function register_controls(): void {
		$this->start_controls();
		$this->add_control( 'note', array( 'label' => 'Offer note', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Applied automatically at checkout. No code needed.' ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		echo '<div style="display:flex;flex-wrap:wrap;justify-content:space-around;gap:18px;text-align:center">';
		foreach ( veloria_offer_tiers() as $tier ) { echo '<div><strong style="font-size:24px;color:#b59a5c">' . esc_html( veloria_format_number( $tier['percent'] ) ) . '% Off</strong><p>' . esc_html( $tier['label'] ) . '</p></div>'; }
		echo '<div><strong style="font-size:24px;color:#b59a5c">+' . esc_html( veloria_format_number( (float) veloria_setting( 'prepaid_percent', 5 ) ) ) . '% Off</strong><p>' . esc_html( (string) veloria_setting( 'prepaid_offer_text', '' ) ) . '</p></div></div><p style="text-align:center;margin-top:16px;font-size:12px">' . esc_html( $this->get_settings_for_display( 'note' ) ) . '</p>';
	}
}

class Veloria_Elementor_Chat extends Veloria_Elementor_Widget {
	public function get_name(): string { return 'veloria-chat'; }
	public function get_title(): string { return 'Veloria Shopping Assistant'; }
	protected function register_controls(): void {
		$this->start_controls();
		$this->add_control( 'help', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => 'Shopping assistant uses the existing Veloria product finder and order tools. Remove this widget to hide the floating assistant.' ) );
		$this->end_controls_section();
	}
	protected function render(): void { get_template_part( 'template-parts/chat/widget' ); }
}

class Veloria_Elementor_Customer_Tool extends Veloria_Elementor_Widget {
	public function get_name(): string { return 'veloria-customer-tool'; }
	public function get_title(): string { return 'Veloria Customer Form'; }
	protected function register_controls(): void {
		$this->start_controls();
		$this->add_control( 'tool', array( 'label' => 'Customer form', 'type' => \Elementor\Controls_Manager::SELECT, 'options' => array( 'track-order' => 'Track order and guest email verification', 'wishlist' => 'Saved wishlist', 'login' => 'Password and email-code sign in', 'register' => 'Customer registration' ), 'default' => 'track-order' ) );
		$this->add_control( 'help', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => 'Place editable headings, copy and images around this form. Form validation and order permissions remain controlled by the store.' ) );
		$this->end_controls_section();
	}
	protected function render(): void {
		$tool = (string) $this->get_settings_for_display( 'tool' );
		if ( ! in_array( $tool, array( 'track-order', 'wishlist', 'login', 'register' ), true ) ) return;
		get_template_part( 'template-parts/tools/' . $tool, null, array( 'hide_heading' => true ) );
	}
}
