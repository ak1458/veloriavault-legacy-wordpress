<?php
namespace WoolentorPro\Modules\StoreVacation\Frontend;
use WooLentorPro\Traits\Singleton;
use \Woolentor\Modules\StoreVacation\Frontend as FrontendBase;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Manage_Notice {
    use Singleton;

    public $notice_data = [];

    /**
     * Class Constructor __construct
     */
    public function __construct(){
        add_action('init', [$this, 'init_hooks']);
        add_filter('woolentor_vacation_access_allowed', [$this, 'check_vacation_status'], 10, 1);
    }

    /**
     * Initialize hooks
     */
    public function init_hooks(){
        add_filter('woolentor_vacation_notice_content', [$this, 'render_vacation_popup'], 10, 2);
        add_action('wp_head', [$this, 'add_vacation_meta_tags']);
    }

    /**
     * Check vacation status including pro features
     */
    public function check_vacation_status($status){

        // Check Category Specific Vacation
        if($this->check_category_vacation_notice($status)){
            return true;
        }

        if($status && $this->check_access_permission($status)){
            return $status;
        }

        // Check Multiple Schedules
        if($this->check_multiple_schedules($status)){
            return true;
        }

        // Check Access Permission
        if( $this->check_access_permission($status) ){
            return true;
        }
    
        return $status;
    }

    /**
     * Check Multiple Schedules
     * @param mixed $status
     * @return mixed
     */
    public function check_multiple_schedules($status){
        $schedules = woolentor_get_option('multiple_schedules', 'woolentor_store_vacation_settings', []);
        if(!empty($schedules)){
            foreach($schedules as $schedule){
                if($this->is_schedule_active($schedule)){
                    return true;
                }
            }
        }else{
            return $status;
        }
    }

    /**
     * Check if current page is category page
     */
    public function check_category_vacation_notice($status){
        global $wp_query;
        if( $wp_query && isset($wp_query->queried_object) ){
            $queried_object = $wp_query->queried_object;
    
            // Check if current page is category page
            if( isset($queried_object->taxonomy) && $queried_object->taxonomy === 'product_cat' ){
                if( $this->check_category_vacation($status, $queried_object->term_id) ){
                    return true;
                }
            }
    
            // Check if single product
            if( is_singular('product') ){
                $product = wc_get_product( get_the_ID() );
                if( $product ){
                    $product_cats = $product->get_category_ids();
                    foreach( $product_cats as $cat_id ){
                        if( $this->check_category_vacation($status, $cat_id) ){
                            return true;
                        }
                    }
                }
            }
    
            // Check for shop/archive pages
            if( is_shop() || is_product_taxonomy() ){
                global $post;
                if( isset($post->ID) ){
                    $product = wc_get_product($post->ID);
                    if( $product ){
                        $product_cats = $product->get_category_ids();
                        foreach( $product_cats as $cat_id ){
                            if( $this->check_category_vacation($status, $cat_id) ){
                                return true;
                            }
                        }
                    }
                }
            }
        }else{
            return $status;
        }
    }

    /**
     * Check category vacation status
     */
    public function check_category_vacation($status, $category_id){
        $category_schedules = woolentor_get_option('category_specific_vacation', 'woolentor_store_vacation_settings', []);
        
        if(!empty($category_schedules)){
            foreach($category_schedules as $schedule){
                if($schedule['category'] == $category_id && $this->is_schedule_active($schedule)){
                    return true;
                }
            }
        }else{
            return $status;
        }
    }

    /**
     * Check if schedule is active
     */
    private function is_schedule_active($schedule){
        if(empty($schedule['start_date']) || empty($schedule['end_date'])){
            return false;
        }

        $is_active = false;

        $current_time = current_time('timestamp');
        $start_date = strtotime($schedule['start_date']);
        $end_date = strtotime($schedule['end_date']);

        // Check recurring
        if(!empty($schedule['recurring']) && $schedule['recurring'] != 'none'){
            switch($schedule['recurring']){
                case 'weekly':
                    $start_day = date('w', $start_date);
                    $current_day = date('w', $current_time);
                    $is_active = $start_day == $current_day;
                    add_filter('woolentor_vacation_notice_message',function($message) use ($schedule){
                        $message = $schedule['message'];
                        $this->notice_data = $schedule;
                        return $message;
                    },10,1);
                    return $is_active;

                case 'monthly':
                    $start_day = date('j', $start_date);
                    $current_day = date('j', $current_time);
                    $is_active = $start_day == $current_day;
                    add_filter('woolentor_vacation_notice_message',function($message) use ($schedule){
                        $message = $schedule['message'];
                        $this->notice_data = $schedule;
                        return $message;
                    },10,1);
                    return $is_active;

                case 'yearly':
                    $start_month_day = date('md', $start_date);
                    $current_month_day = date('md', $current_time);
                    $is_active = $start_month_day == $current_month_day;
                    add_filter('woolentor_vacation_notice_message',function($message) use ($schedule){
                        $message = $schedule['message'];
                        $this->notice_data = $schedule;
                        return $message;
                    },10,1);
                    return $is_active;

                default:
                    $is_active = true;
                    return $is_active;
            }
        }

        $is_active = ($current_time >= $start_date && $current_time <= $end_date);

        if( $is_active ){
            add_filter('woolentor_vacation_notice_message',function($message) use ($schedule){
                $message = $schedule['message'];
                $this->notice_data = $schedule;
                return $message;
            },10,1);
        }

        return $is_active;
    }

    /**
     * Check access permission
     */
    public function check_access_permission($allowed){

        // Check user roles
        $allowed_roles = woolentor_get_option('allowed_user_roles', 'woolentor_store_vacation_settings', []);
        if(!empty($allowed_roles)){
            $user = wp_get_current_user();
            $user_roles = (array) $user->roles;
            if(array_intersect($allowed_roles, $user_roles)){
                return true;
            }else{
                return false;
            }
        }

        // Check IP
        $allowed_ips = woolentor_get_option('allowed_ips', 'woolentor_store_vacation_settings', '');
        if(!empty($allowed_ips)){
            $allowed_ips = array_map('trim', explode(',', $allowed_ips));
            $user_ip = $this->get_user_ip();
            if(in_array($user_ip, $allowed_ips)){
                return true;
            }else{
                return false;
            }
        }

        return $allowed;
    }

    /**
     * Render vacation popup
     */
    public function render_vacation_popup($notice_html, $message ){
        if(!FrontendBase::is_vacation_active()){
            return;
        }

        $notice_style = woolentor_get_option('notice_style', 'woolentor_store_vacation_settings', 'banner');
        
        if($notice_style == 'popup'){
            ob_start();
            echo '<div class="woolentor-vacation-popup">';
            echo '<div class="woolentor-vacation-popup-content">';
            echo '<button class="woolentor-popup-close">&times;</button>';
            echo wp_kses_post($message);
            if(woolentor_get_option('show_countdown', 'woolentor_store_vacation_settings') == 'on'){
                $this->render_countdown_timer();
            }
            echo '</div>';
            echo '</div>';
            $notice_html = ob_get_clean();
        }else if( $notice_style == 'floating' ){
            $float_position = woolentor_get_option('floating_position', 'woolentor_store_vacation_settings', 'bottom-right');
            $position_class = 'floating-notice ' . $float_position;
            ob_start();
            ?>
                <div class="woolentor-store-vacation-notice <?php echo esc_attr($position_class); ?>">
                    <span class="notice-close">&times;</span>
                    <?php echo wp_kses_post($message); ?>
                </div>
            <?php
            $notice_html = ob_get_clean();

        }else{
            return $notice_html;    
        }

        return $notice_html;

    }

    /**
     * Render countdown timer
     */
    private function render_countdown_timer(){
        $start_date = apply_filters('woolentor_vacation_start_date', woolentor_get_option('vacation_start_date', 'woolentor_store_vacation_settings'));
        $end_date = apply_filters('woolentor_vacation_end_date', woolentor_get_option('vacation_end_date', 'woolentor_store_vacation_settings'));

        if(!empty($this->notice_data)){
            $start_date = $this->notice_data['start_date'];
            $end_date = $this->notice_data['end_date'];
        }

        if(empty($start_date) || empty($end_date)){
            return;
        }

        echo '<div class="woolentor-vacation-countdown" data-end-date="' . esc_attr($end_date) . '" style="display: none;">';
        echo '<div class="woolentor-vacation-countdown-item">';
        echo '<div class="woolentor-vacation-countdown-number woolentor-days">0</div>';
        echo '<div class="woolentor-vacation-countdown-label">' . esc_html__('Days', 'woolentor') . '</div>';
        echo '</div>';
        echo '<div class="woolentor-vacation-countdown-item">';
        echo '<div class="woolentor-vacation-countdown-number woolentor-hours">0</div>';
        echo '<div class="woolentor-vacation-countdown-label">' . esc_html__('Hours', 'woolentor') . '</div>';
        echo '</div>';
        echo '<div class="woolentor-vacation-countdown-item">';
        echo '<div class="woolentor-vacation-countdown-number woolentor-minutes">0</div>';
        echo '<div class="woolentor-vacation-countdown-label">' . esc_html__('Minutes', 'woolentor') . '</div>';
        echo '</div>';
        echo '<div class="woolentor-vacation-countdown-item">';
        echo '<div class="woolentor-vacation-countdown-number woolentor-seconds">0</div>';
        echo '<div class="woolentor-vacation-countdown-label">' . esc_html__('Seconds', 'woolentor') . '</div>';
        echo '</div>';
        echo '</div>';

    }

    /**
     * Get user IP
     */
    private function get_user_ip(){
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * Add vacation meta tags
     */
    public function add_vacation_meta_tags(){
        if(!FrontendBase::is_vacation_active()){
            return;
        }

        $meta_title = woolentor_get_option('vacation_meta_title', 'woolentor_store_vacation_settings');
        $meta_description = woolentor_get_option('vacation_meta_description', 'woolentor_store_vacation_settings');

        if(!empty($meta_title)){
            echo '<meta name="title" content="' . esc_attr($meta_title) . '" />';
        }

        if(!empty($meta_description)){
            echo '<meta name="description" content="' . esc_attr($meta_description) . '" />';
        }
    }

    


}