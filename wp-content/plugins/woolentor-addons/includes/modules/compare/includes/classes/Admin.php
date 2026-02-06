<?php
namespace EverCompare;
use WooLentor\Traits\Singleton;

/**
 * Admin handlers class
 */
class Admin {
    use Singleton;
    
    /**
     * Initialize the class
     */
    private function __construct() {
        $this->includes();
        $this->init();

        // Add a post display state for special EverCompare page.
        add_filter( 'display_post_states', [ $this, 'add_display_post_states' ], 10, 2 );
    }

    /**
     * Add a post display state for special EverCompare page in the page list table.
     *
     * @param array   $post_states An array of post display states.
     * @param \WP_Post $post  The current post object.
     */
    public function add_display_post_states( $post_states, $post ){
        if ( (int)woolentor_get_option( 'compare_page', 'ever_compare_table_settings_tabs' ) === $post->ID ) {
            $post_states['evercompare_page_for_compare_table'] = __( 'EverCompare', 'ever-compare' );
        }
        return $post_states;
    }

    /**
     * Load Required files
     *
     * @return void
     */
    private function includes(){
        require_once( __DIR__. '/Admin/Fields.php' );
    }

    /**
     * Initialize
     */
    public function init(){
        \Woolentor\Modules\Compare\Admin\Fields::instance();
    }

}