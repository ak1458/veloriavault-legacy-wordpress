<div class="nm-cart-panel-holder">
    <div id="nm-cart-panel">
        <div id="nm-cart-panel-loader">
            <span class="nm-loader"><?php esc_html_e( 'Updating', 'woocommerce' ); ?><em>&hellip;</em></span>
        </div>

        <div class="nm-cart-panel-header">
            <div class="nm-cart-panel-header-inner">
                <a href="#" id="nm-cart-panel-close">
                    <span class="nm-cart-panel-title"><?php esc_html_e( 'Cart', 'woocommerce' ); ?></span>
                    <span class="nm-cart-panel-close-title"><i class="nm-font-close2"></i></span>
                </a>
            </div>
        </div>

        <div class="widget_shopping_cart_content">
            <?php woocommerce_mini_cart(); ?>
        </div>
    </div>
    
    <div class="nm-page-overlay nm-cart-panel-overlay"></div>
</div>
