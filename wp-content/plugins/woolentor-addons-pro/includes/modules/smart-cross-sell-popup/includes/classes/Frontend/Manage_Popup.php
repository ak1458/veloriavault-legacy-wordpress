<?php
namespace WoolentorPro\Modules\Smart_Cross_Sell_Popup\Frontend;
use WooLentorPro\Traits\Singleton;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Manage_Popup {
    use Singleton;

    /**
     * Class __construct
     */
    public function __construct(){
        // Add Pro Triggers
        add_action('init', [$this, 'register_pro_triggers']);

        // Add Pro Product Sources
        add_filter('woolentor_cross_sell_products', [$this, 'get_pro_products'], 10, 2);
    }

    /**
     * Register Pro Triggers
     */
    public function register_pro_triggers(){
        // Exit Intent
        add_action('wp_footer', [$this, 'exit_intent_trigger']);

        // Time Delay
        add_action('wp_footer', [$this, 'time_delay_trigger']);

        // Scroll Position
        add_action('wp_footer', [$this, 'scroll_trigger']);

        // Cart Total
        add_action('woocommerce_cart_updated', [$this, 'cart_total_trigger']);

        // Before Checkout
        add_action('template_redirect', [$this, 'before_checkout_trigger']);
    }

    /**
     * Exit Intent Trigger
     */
    public function exit_intent_trigger(){
        $settings = woolentor_smart_cross_sell_get_settings();
        if($settings['trigger_type'] != 'exit_intent') return;

        ?>
        <script>
            ;jQuery(document).ready(function($){
                var showExitPopup = true,
                    productId = '<?php echo (int) \WC()->session->get('woolentor_last_added_product'); ?>';
                
                $(document).on('mouseleave', function(e){
                    // if(e.clientY < 0 && showExitPopup){
                    if(e.clientY < 0){
                        showExitPopup = false;
                        WoolentorCrossSellPopup.requestPopup(productId);
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Time Delay Trigger
     */
    public function time_delay_trigger(){
        $settings = woolentor_smart_cross_sell_get_settings();
        if($settings['trigger_type'] != 'time_delay') return;

        $delay = isset($settings['time_delay']) ? absint($settings['time_delay']) : 30;
        ?>
        <script>
            ;jQuery(document).ready(function($){

                var productId = '<?php echo (int) \WC()->session->get('woolentor_last_added_product'); ?>';

                setTimeout(function(){
                    WoolentorCrossSellPopup.requestPopup(productId);
                }, <?php echo $delay * 1000; ?>);

            });
        </script>
        <?php
    }

    /**
     * Scroll Trigger
     */
    public function scroll_trigger(){
        $settings = woolentor_smart_cross_sell_get_settings();
        if($settings['trigger_type'] != 'scroll') return;

        $scroll_percent = isset($settings['scroll_percent']) ? absint($settings['scroll_percent']) : 50;
        ?>
        <script>
            ;jQuery(document).ready(function($){
                var showScrollPopup = true;
                
                $(window).scroll(function(){
                    if(!showScrollPopup) return;

                    var scrollPercent = 100 * $(window).scrollTop() / ($(document).height() - $(window).height()),
                        productId = '<?php echo (int) \WC()->session->get('woolentor_last_added_product'); ?>';
                    
                    if(scrollPercent > <?php echo $scroll_percent; ?>){
                        showScrollPopup = false;
                        WoolentorCrossSellPopup.requestPopup(productId);
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Cart Total Trigger
     */
    public function cart_total_trigger(){
        $settings = woolentor_smart_cross_sell_get_settings();
        if($settings['trigger_type'] != 'cart_total') return;

        $min_amount = isset($settings['min_cart_amount']) ? floatval($settings['min_cart_amount']) : 0;
        $cart_total_amount = floatval(\WC()->cart->get_cart_contents_total());
        
        if( \WC()->cart && $cart_total_amount >= $min_amount ){
            \WC()->session->set('woolentor_cross_sell_popup', true);
        }else{
            \WC()->session->set('woolentor_cross_sell_popup', false);
        }

    }

    /**
     * Before Checkout Trigger
     */
    public function before_checkout_trigger(){
        $settings = woolentor_smart_cross_sell_get_settings();
        if($settings['trigger_type'] != 'checkout') return;

        if(is_checkout() && !is_wc_endpoint_url()){
            \WC()->session->set('woolentor_cross_sell_popup', true);
        }else{
            \WC()->session->set('woolentor_cross_sell_popup', false);
        }
    }

    /**
     * Get Pro Products
     */
    public function get_pro_products($products, $product_id){
        $settings = woolentor_smart_cross_sell_get_settings();
        $source = isset($settings['product_source']) ? $settings['product_source'] : 'cross_sells';
        $limit = isset($settings['product_limit']) ? absint($settings['product_limit']) : 4;

        switch($source){
            case 'up_sells':
                return $this->get_upsell_products($limit, $product_id);

            case 'related':
                return $this->get_related_products($limit, $product_id);

            case 'custom':
                return $this->get_custom_products($limit);

            case 'category':
                return $this->get_category_products($limit);

            default:
                return $products;
        }
    }

    /**
     * Get Upsell Products
     */
    private function get_upsell_products($limit, $product_id){
        $products = [];

        $product = wc_get_product($product_id);
        if($product){
            $upsells = $product->get_upsell_ids();
            foreach($upsells as $upsell_id){
                if(count($products) >= $limit) break;

                $upsell_product = wc_get_product($upsell_id);
                if($upsell_product && !isset($products[$upsell_id])){
                    $products[$upsell_id] = $upsell_product;
                }
            }
        }

        return $products;
    }

    /**
     * Get Related Products
     */
    private function get_related_products($limit, $product_id){
        $products = [];

        $product = wc_get_product($product_id);
        if($product){
            $related = wc_get_related_products($product->get_id(), $limit);
            foreach($related as $related_id){
                if(count($products) >= $limit) break;

                $related_product = wc_get_product($related_id);
                if($related_product && !isset($products[$related_id])){
                    $products[$related_id] = $related_product;
                }
            }
        }

        return $products;
    }

    /**
     * Get Custom Products
     */
    private function get_custom_products($limit){
        $settings = woolentor_smart_cross_sell_get_settings();
        $products = [];

        if(isset($settings['custom_products']) && is_array($settings['custom_products'])){
            foreach($settings['custom_products'] as $product_id){
                if(count($products) >= $limit) break;

                $product = wc_get_product($product_id);
                if($product){
                    $products[$product_id] = $product;
                }
            }
        }

        return $products;
    }

    /**
     * Get Category Products
     */
    private function get_category_products($limit){
        $settings = woolentor_smart_cross_sell_get_settings();
        $products = [];

        if(isset($settings['product_categories']) && is_array($settings['product_categories'])){
            $args = [
                'post_type' => 'product',
                'posts_per_page' => $limit,
                'tax_query' => [
                    [
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $settings['product_categories']
                    ]
                ]
            ];

            $query = new \WP_Query($args);
            
            if($query->have_posts()){
                while($query->have_posts()){
                    $query->the_post();
                    $product = wc_get_product(get_the_ID());
                    if($product){
                        $products[$product->get_id()] = $product;
                    }
                }
                wp_reset_postdata();
            }
        }

        return $products;
    }


}