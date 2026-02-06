<?php
/**
 * Result Count
 *
 * Shows text: Showing x - x of x results.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/result-count.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     9.9.0
 NM: Modified - See comments */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// NM
global $nm_theme_options;
$nm_is_infinite_load = ( is_woocommerce() && $nm_theme_options['shop_infinite_load'] !== '0' ) ? true : false;
?>
<p class="woocommerce-result-count" role="alert" aria-relevant="all" <?php echo ( empty( $orderedby ) || 1 === intval( $total ) ) ? '' : 'data-is-sorted-by="true"'; ?>>
	<?php
	// phpcs:disable WordPress.Security
	if ( 1 === intval( $total ) ) {
		_e( 'Showing the single result', 'woocommerce' );
	} elseif ( $total <= $per_page || -1 === $per_page ) {
		$orderedby_placeholder = empty( $orderedby ) ? '%2$s' : '<span class="screen-reader-text">%2$s</span>';
		/* translators: 1: total results 2: sorted by */
		printf( _n( 'Showing all %1$d result', 'Showing all %1$d results', $total, 'woocommerce' ) . $orderedby_placeholder, $total, esc_html( $orderedby ) );
	} else {
        $first                 = ( $nm_is_infinite_load ) ? 1 : ( $per_page * $current ) - $per_page + 1; // NM: Always show "1-X" when infinite load is enabled
		$last                  = min( $total, $per_page * $current );
		$orderedby_placeholder = empty( $orderedby ) ? '%4$s' : '<span class="screen-reader-text">%4$s</span>';
		
        // NM: Added conditional
		if ( $total === $last ) {
            /* translators: 1: total results 2: sorted by */
            printf( _n( 'Showing all %1$d result', 'Showing all %1$d results', $total, 'woocommerce' ), $total, esc_html( $orderedby ) );
		} else {
        /* translators: 1: first result 2: last result 3: total results 4: sorted by */
		printf( _nx( 'Showing %1$d&ndash;%2$d of %3$d result', 'Showing %1$d&ndash;%2$d of %3$d results', $total, 'with first and last result', 'woocommerce' ) . $orderedby_placeholder, $first, $last, $total, esc_html( $orderedby ) );
        }
	}
	// phpcs:enable WordPress.Security
	?>
</p>
