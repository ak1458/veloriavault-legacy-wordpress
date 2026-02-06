<?php
namespace Woolentor\Modules\AbandonedCart;
use WooLentor\Traits\Singleton;
use Woolentor\Modules\AbandonedCart\Config\Config;

class Dynamic_Scheduler {
    use Singleton;

    /**
     * Constructor
     */
    private function __construct() {
        // Add custom cron intervals
        add_filter( 'cron_schedules', [$this, 'add_custom_schedules'] );
        
        // Monitor setting changes to update schedule
        add_action( 'update_option_woolentor_abandoned_cart_settings', [ $this, 'update_schedule' ], 10, 3 );
    }

    /**
     * Add custom schedules
     */
    public function add_custom_schedules($schedules) {
        // Get minutes from settings
        $minutes = Config::get_abandoned_time();
        $minutes = max(5, $minutes); // Minimum 5 minutes

        // Add custom schedule
        $schedules['woolentor_abandoned_cart_custom_minutes'] = [
            'interval' => $minutes * MINUTE_IN_SECONDS,
            'display' => sprintf(__('Every %d minutes', 'woolentor'), $minutes)
        ];

        // Add custom schedule for email processing
        $schedules['woolentor_email_processing'] = [
            'interval' => 1 * MINUTE_IN_SECONDS,
            'display' => __('Every 1 minute', 'woolentor')
        ];

        return $schedules;
    }

    /**
     * Get optimal cron interval based on abandoned time setting
     */
    public function get_optimal_cron_interval( $abandoned_time_minutes ) {
        return $abandoned_time_minutes < 60 ? 'woolentor_abandoned_cart_custom_minutes' : 'hourly';
    }

    /**
     * Schedule events with dynamic intervals
     */
    public function schedule_events() {
        $abandoned_time = Config::get_abandoned_time();
        $optimal_interval = $this->get_optimal_cron_interval( $abandoned_time );

        $this->clear_all_schedules();

        // Schedule abandoned cart check with optimal interval
        if ( ! wp_next_scheduled( 'woolentor_abandoned_cart_check' ) ) {
            wp_schedule_event( time(), $optimal_interval, 'woolentor_abandoned_cart_check' );
        }

        // Cleanup can remain daily
        if ( ! wp_next_scheduled( 'woolentor_abandoned_cart_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'woolentor_abandoned_cart_cleanup' );
        }

        // Schedule email processing
        if ( ! wp_next_scheduled( 'woolentor_process_scheduled_emails' ) ) {
            wp_schedule_event( time(), 'woolentor_email_processing', 'woolentor_process_scheduled_emails' );
        }

    }

    /**
     * Update schedule when settings change
     */
    public function update_schedule( $old_value, $value, $option ) {

        if( $value['enable'] === 'on' ) {

            if( ($value['abandoned_time'] !== $old_value['abandoned_time']) ){

                $abandoned_time = Config::get_abandoned_time();
                $optimal_interval = $this->get_optimal_cron_interval( $abandoned_time );
                
                // Clear current schedule
                wp_clear_scheduled_hook( 'woolentor_abandoned_cart_check' );
                
                // Schedule with new interval
                wp_schedule_event( time(), $optimal_interval, 'woolentor_abandoned_cart_check' );
            }

            // If the module is enabled and the abandoned time is the same as the old value, clear the schedule
            if( !isset( $old_value['enable'] ) || $old_value['enable'] === 'off' || $old_value['enable'] === '' ){
                $this->schedule_events();
            }

            // If next scheduled event is not found, schedule it
            if( !wp_next_scheduled( 'woolentor_abandoned_cart_check' ) || !wp_next_scheduled( 'woolentor_abandoned_cart_cleanup' ) || !wp_next_scheduled( 'woolentor_process_scheduled_emails' ) ){
                $this->schedule_events();
            }

        }else{
            $this->clear_all_schedules();
        }
    }

    /**
     * Clear all scheduled events
     */
    public function clear_all_schedules() {
        wp_clear_scheduled_hook( 'woolentor_abandoned_cart_check' );
        wp_clear_scheduled_hook( 'woolentor_abandoned_cart_cleanup' );
        wp_clear_scheduled_hook( 'woolentor_process_scheduled_emails' );
    }


}