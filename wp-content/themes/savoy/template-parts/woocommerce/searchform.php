<?php
    global $nm_theme_options;
?>
<div class="nm-header-search-holder">
    <div id="nm-header-search">
        <a href="#" id="nm-header-search-close" class="nm-font nm-font-close2"></a>

        <div class="nm-header-search-wrap">
            <div class="nm-header-search-form-wrap">
                <form id="nm-header-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <a id="nm-header-search-clear-button" class="button border">
                        <i class="nm-font-close2"></i>
                        <span><?php esc_html_e( 'Clear', 'woocommerce' ); ?></span>
                    </a>
                    <i class="nm-font nm-font-search"></i>
                    <input type="text" id="nm-header-search-input" autocomplete="off" value="" name="s" placeholder="<?php esc_attr_e( 'Search products', 'woocommerce' ); ?>&hellip;" />
                    <input type="hidden" name="post_type" value="product" />
                </form>
            </div>

            <?php
                if ( strlen( $nm_theme_options['shop_search_keywords'] ) > 1 ) :

                $search_keywords = explode( ',', $nm_theme_options['shop_search_keywords'] );
            ?>
            <div id="nm-search-keywords" class="show">
                <strong class="nm-search-keywords-title"><?php echo esc_html( $nm_theme_options['shop_search_keywords_title'] ); ?></strong>
                <ul class="nm-search-keywords-list">
                <?php
                    foreach ( $search_keywords as $search_keyword ) {
                        printf(
                            '<li><a href="%s" class="button border"><i class="nm-font-search"></i>%s</a></li>',
                            esc_url( home_url( '?s=' . $search_keyword . '&post_type=product' ) ),
                            esc_html( $search_keyword )
                        );
                    }
                ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php
                if ( $nm_theme_options['shop_search_suggestions'] ) :

                // Column class
                $columns_class = apply_filters( 'nm_search_suggestions_product_columns_class', 'block-grid-single-row xsmall-block-grid-2 small-block-grid-4 medium-block-grid-5 large-block-grid-6');
            ?>
            <div id="nm-search-suggestions-notice">
                <span class="txt-press-enter"><?php printf( esc_html__( 'press %sEnter%s to search', 'nm-framework' ), '<u>', '</u>' ); ?></span>
                <span class="txt-has-results"><?php esc_html_e( 'Search results', 'woocommerce' ); ?>:</span>
                <span class="txt-no-results"><?php esc_html_e( 'No products found.', 'woocommerce' ); ?></span>
            </div>

            <div id="nm-search-suggestions">
                <div class="nm-search-suggestions-inner">
                    <ul id="nm-search-suggestions-product-list" class="<?php echo esc_attr( $columns_class ); ?>"></ul>
                </div>
            </div>
            <?php else : ?>
            <div id="nm-header-search-notice"><span><?php printf( esc_html__( 'press %sEnter%s to search', 'nm-framework' ), '<u>', '</u>' ); ?></span></div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="nm-page-overlay nm-header-search-overlay"></div>
</div>
