<?php
/**
 * The template for displaying product content in the quickview-product.php template
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?>
<div class="woolentor-quickview-content-builder woolentor-quickview-content-area woocommerce single-product">
	<div id="product-<?php the_ID(); ?>" <?php post_class('product'); ?> >
        <?php do_action( 'woolentor_quick_view_content' ); ?>
	</div>
</div>