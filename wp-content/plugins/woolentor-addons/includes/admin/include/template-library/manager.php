<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woolentor_Template_Library_Manager{

    // Remote URLs
    const REST_ROUTE_URL = 'https://library.shoplentor.com/wp-json/woolentor';
    const BACKUP_REST_ROUTE_URL = 'https://library-backup.shoplentor.com/wp-json/woolentor';

    // Transient Key (kept for backward compatibility and migration)
    const TRANSIENT_KEYES = [
        'template'  => 'woolentor_template_info',
        'gutenberg' => 'woolentor_gutenberg_template_info',
        'pattern'   => 'woolentor_gutenberg_patterns_info'
    ];

    // API Endpoint
    const API_ENDPOINT = [
        'template'      => 'v1/templates',
        'singletemplate'=> 'v1/templates/%s',
        'gutenberg'     => 'v1/gutenbergtemplates',
        'pattern'       => 'v1/gutenbergpatterns'
    ];

    // Cache directory name (inside woolentor-addons folder)
    const PLUGIN_DIR = 'woolentor-addons';
    const CACHE_DIR = 'cache';

    // Cache file names
    const CACHE_FILES = [
        'template'  => 'templates.json',
        'gutenberg' => 'gutenberg-templates.json',
        'pattern'   => 'patterns.json'
    ];

    // Cache meta file
    const CACHE_META_FILE = 'cache-meta.json';

    // Cache expiry time (1 month)
    const CACHE_EXPIRY = MONTH_IN_SECONDS;

    private static $_instance = null;

    /**
     * Class Instance
     */
    public static function instance(){
        if( is_null( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Initialize WP_Filesystem
     *
     * @return bool
     */
    private static function init_filesystem() {
        global $wp_filesystem;

        if ( ! $wp_filesystem || ! is_object( $wp_filesystem ) ) {
            if ( ! function_exists( 'WP_Filesystem' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            $upload_dir = wp_upload_dir();
            WP_Filesystem( false, $upload_dir['basedir'], true );
        }

        return ( $wp_filesystem && is_object( $wp_filesystem ) );
    }

    /**
     * Get plugin directory path (woolentor-addons)
     *
     * @return string
     */
    public static function get_plugin_dir() {
        $upload_dir = wp_upload_dir();
        return trailingslashit( $upload_dir['basedir'] ) . self::PLUGIN_DIR;
    }

    /**
     * Get cache directory path (woolentor-addons/cache)
     *
     * @return string
     */
    public static function get_cache_dir() {
        return trailingslashit( self::get_plugin_dir() ) . self::CACHE_DIR;
    }

    /**
     * Get cache file path by type
     *
     * @param string $type - template|gutenberg|pattern
     * @return string
     */
    public static function get_cache_file_path( $type ) {
        if ( ! isset( self::CACHE_FILES[ $type ] ) ) {
            return '';
        }
        return trailingslashit( self::get_cache_dir() ) . self::CACHE_FILES[ $type ];
    }

    /**
     * Get cache meta file path
     *
     * @return string
     */
    public static function get_cache_meta_path() {
        return trailingslashit( self::get_cache_dir() ) . self::CACHE_META_FILE;
    }

    /**
     * Create cache directory if not exists
     * Creates woolentor-addons/cache/ structure
     *
     * @return bool
     */
    public static function create_cache_dir() {
        global $wp_filesystem;

        if ( ! self::init_filesystem() ) {
            return false;
        }

        $plugin_dir = self::get_plugin_dir();
        $cache_dir = self::get_cache_dir();

        // Create parent plugin directory if not exists (woolentor-addons)
        if ( ! $wp_filesystem->exists( $plugin_dir ) ) {
            wp_mkdir_p( $plugin_dir );

            // Create index.php for security in parent folder
            $wp_filesystem->put_contents( trailingslashit( $plugin_dir ) . 'index.php', '<?php // Silence is golden', FS_CHMOD_FILE );
        }

        // Create cache directory if not exists (woolentor-addons/cache)
        if ( ! $wp_filesystem->exists( $cache_dir ) ) {
            $created = wp_mkdir_p( $cache_dir );

            if ( $created ) {
                // Create .htaccess for security
                $htaccess_content = "Options -Indexes\n<Files *.json>\n    Order Allow,Deny\n    Allow from all\n</Files>";
                $wp_filesystem->put_contents( trailingslashit( $cache_dir ) . '.htaccess', $htaccess_content, FS_CHMOD_FILE );

                // Create index.php for security
                $wp_filesystem->put_contents( trailingslashit( $cache_dir ) . 'index.php', '<?php // Silence is golden', FS_CHMOD_FILE );
            }
        }

        return $wp_filesystem->is_dir( $cache_dir ) && $wp_filesystem->is_writable( $cache_dir );
    }

    /**
     * Save data to cache file
     *
     * @param string $type - template|gutenberg|pattern
     * @param array $data
     * @return bool
     */
    public static function save_cache_file( $type, $data ) {
        global $wp_filesystem;

        if ( ! self::create_cache_dir() ) {
            return false;
        }

        $file_path = self::get_cache_file_path( $type );

        if ( empty( $file_path ) ) {
            return false;
        }

        $json_data = wp_json_encode( $data );
        $saved = $wp_filesystem->put_contents( $file_path, $json_data, FS_CHMOD_FILE );

        if ( $saved ) {
            // Update cache meta
            self::update_cache_meta( $type, strlen( $json_data ) );
            return true;
        }

        return false;
    }

    /**
     * Read data from cache file
     *
     * @param string $type - template|gutenberg|pattern
     * @return array|null
     */
    public static function get_cache_file( $type ) {
        global $wp_filesystem;

        if ( ! self::init_filesystem() ) {
            return null;
        }

        $file_path = self::get_cache_file_path( $type );

        if ( empty( $file_path ) || ! $wp_filesystem->exists( $file_path ) ) {
            return null;
        }

        $content = $wp_filesystem->get_contents( $file_path );

        if ( $content === false ) {
            return null;
        }

        return json_decode( $content, true );
    }

    /**
     * Update cache meta information
     *
     * @param string $type
     * @param int $size
     * @return void
     */
    public static function update_cache_meta( $type, $size = 0 ) {
        global $wp_filesystem;

        if ( ! self::init_filesystem() ) {
            return;
        }

        $meta_path = self::get_cache_meta_path();
        $meta = [];

        if ( $wp_filesystem->exists( $meta_path ) ) {
            $meta_content = $wp_filesystem->get_contents( $meta_path );
            if ( $meta_content !== false ) {
                $meta = json_decode( $meta_content, true ) ?: [];
            }
        }

        $meta[ $type ] = [
            'timestamp' => time(),
            'version'   => defined( 'WOOLENTOR_VERSION' ) ? WOOLENTOR_VERSION : '1.0.0',
            'size'      => $size
        ];

        $wp_filesystem->put_contents( $meta_path, wp_json_encode( $meta ), FS_CHMOD_FILE );
    }

    /**
     * Check if cache is valid (not expired)
     *
     * @param string $type
     * @param int $expiry - default WEEK_IN_SECONDS
     * @return bool
     */
    public static function is_cache_valid( $type, $expiry = null ) {
        global $wp_filesystem;

        if ( ! self::init_filesystem() ) {
            return false;
        }

        if ( $expiry === null ) {
            $expiry = self::CACHE_EXPIRY;
        }

        $file_path = self::get_cache_file_path( $type );

        if ( empty( $file_path ) || ! $wp_filesystem->exists( $file_path ) ) {
            return false;
        }

        $meta_path = self::get_cache_meta_path();

        if ( ! $wp_filesystem->exists( $meta_path ) ) {
            return false;
        }

        $meta_content = $wp_filesystem->get_contents( $meta_path );
        if ( $meta_content === false ) {
            return false;
        }

        $meta = json_decode( $meta_content, true );

        if ( ! isset( $meta[ $type ]['timestamp'] ) ) {
            return false;
        }

        return ( time() - $meta[ $type ]['timestamp'] ) < $expiry;
    }

    /**
     * Clear cache files
     *
     * @param string|null $type - specific type or null for all
     * @return bool
     */
    public static function clear_cache( $type = null ) {
        global $wp_filesystem;

        if ( ! self::init_filesystem() ) {
            return false;
        }

        $cache_dir = self::get_cache_dir();

        if ( ! $wp_filesystem->is_dir( $cache_dir ) ) {
            return true;
        }

        if ( $type !== null ) {
            // Clear specific cache
            $file_path = self::get_cache_file_path( $type );
            if ( ! empty( $file_path ) && $wp_filesystem->exists( $file_path ) ) {
                wp_delete_file( $file_path );
            }
        } else {
            // Clear all cache files
            foreach ( self::CACHE_FILES as $cache_type => $filename ) {
                $file_path = trailingslashit( $cache_dir ) . $filename;
                if ( $wp_filesystem->exists( $file_path ) ) {
                    wp_delete_file( $file_path );
                }
            }
            // Clear meta file
            $meta_path = self::get_cache_meta_path();
            if ( $wp_filesystem->exists( $meta_path ) ) {
                wp_delete_file( $meta_path );
            }
        }

        return true;
    }

    /**
     * Migrate from transient to file cache and cleanup old transients
     *
     * @return void
     */
    public static function migrate_from_transient() {
        // Delete old transients
        foreach ( self::TRANSIENT_KEYES as $type => $transient_key ) {
            delete_transient( $transient_key );
        }
    }

    /**
     * Get Template Endpoint with backup support
     */
    public static function get_api_endpoint(){
        if( is_plugin_active('woolentor-addons-pro/woolentor_addons_pro.php') && function_exists('woolentor_pro_template_endpoint') ){
            return woolentor_pro_template_endpoint();
        }
        return self::get_remote_urls('template');
    }

    /**
     * Get Template API with backup support
     * @todo We will remove in Future
     */
    public static function get_api_templateapi(){
        if( is_plugin_active('woolentor-addons-pro/woolentor_addons_pro.php') && function_exists('woolentor_pro_template_url') ){
            return woolentor_pro_template_url();
        }
        return self::get_remote_urls('singletemplate');
    }

    /**
     * Delete cache data (both file and transient for backward compatibility)
     *
     * @return void
     */
    public static function delete_transient_cache_data(){
        if ( get_option( 'woolentor_delete_data_fetch_cache', false ) ) {
            // Clear file cache
            self::clear_cache();

            // Clear old transients (backward compatibility)
            self::migrate_from_transient();

            delete_option('woolentor_delete_data_fetch_cache');
        }
    }

    /**
     * Get Remote URL (single URL for backward compatibility)
     *
     * @param [type] $name
     */
    public static function get_remote_url( $name ){
        return sprintf('%s/%s', self::REST_ROUTE_URL, self::API_ENDPOINT[$name]);
    }

    /**
     * Get Remote URLs (primary and backup)
     *
     * @param [type] $name
     * @return array
     */
    public static function get_remote_urls( $name ){
        $primary_url = sprintf('%s/%s', self::REST_ROUTE_URL, self::API_ENDPOINT[$name]);
        $backup_url = sprintf('%s/%s', self::BACKUP_REST_ROUTE_URL, self::API_ENDPOINT[$name]);

        return [
            'primary' => $primary_url,
            'backup'  => $backup_url
        ];
    }

    /**
     * Set data to file cache with backup server support
     *
     * @param string|array $url
     * @param string $type - template|gutenberg|pattern
     * @param boolean $force_update
     */
    public static function set_templates_info( $url = '', $type = '', $force_update = false ) {
        // Check if cache is valid
        if ( ! $force_update && self::is_cache_valid( $type ) ) {
            return;
        }

        // Fetch from remote
        $info = self::get_content_remote_request_with_backup( $url );

        if ( ! empty( $info ) ) {
            // Save to file cache
            self::save_cache_file( $type, $info );
        }
    }

    /**
     * Get Remote Template List with file cache support
     *
     * @param string $type
     * @param array|null $endpoint
     * @param boolean $force_update
     * @return array
     */
    public static function get_template_remote_data( $type, $endpoint = null, $force_update = false ){

        if( $endpoint === null ){
            $endpoint = self::get_remote_urls( $type );
        }

        // Check file cache first
        if ( ! $force_update && self::is_cache_valid( $type ) ) {
            $cached_data = self::get_cache_file( $type );
            if ( $cached_data !== null ) {
                return $cached_data;
            }
        }

        // Fallback: If file cache directory is not writable, fetch directly without caching
        if ( ! self::create_cache_dir() ) {
            $info = self::get_content_remote_request_with_backup( $endpoint );
            return $info ?: [];
        }

        // Fetch fresh data and save to cache
        self::set_templates_info( $endpoint, $type, true );

        // Return cached data
        $data = self::get_cache_file( $type );

        return $data ?: [];
    }

    /**
     * Get Template List with backup support
     *
     * @param boolean $force_update
     */
    public static function get_templates_info($force_update = false) {
        return self::get_template_remote_data('template', self::get_api_endpoint(), $force_update);
    }

    /**
     * Get Gutenberg Template List with backup support
     *
     * @param boolean $force_update
     */
    public static function get_gutenberg_templates_info($force_update = false) {
        return self::get_template_remote_data('gutenberg', self::get_remote_urls('gutenberg'), $force_update);
    }

    /**
     * Get Gutenberg Patterns list with backup support
     *
     * @param boolean $force_update
     */
    public static function get_gutenberg_patterns_info($force_update = false) {
        return self::get_template_remote_data('pattern', self::get_remote_urls('pattern'), $force_update);
    }

    /**
     * Get Template content by Template ID with backup support
     *
     * @param [type] $type template | gutenberg | pattern
     * @param [type] $template_id
     */
    public static function get_template_data( $type, $template_id ){
        $template_urls = self::get_remote_urls($type);
        $templateurl_primary = sprintf( '%s/%s', $template_urls['primary'], $template_id);
        $templateurl_backup = sprintf( '%s/%s', $template_urls['backup'], $template_id);

        $response_data = self::get_content_remote_request_with_backup([
            'primary' => $templateurl_primary,
            'backup'  => $templateurl_backup
        ]);

        return $response_data;
    }

    /**
     * Handle remote request with backup server support
     *
     * @param string|array $request_url
     * @return array
     */
    public static function get_content_remote_request_with_backup( $request_url ){
        global $wp_version;

        $urls = [];

        // Handle different URL formats
        if( is_string( $request_url ) ){
            // Single URL - create backup URL
            $urls['primary'] = $request_url;
            $urls['backup'] = str_replace( self::REST_ROUTE_URL, self::BACKUP_REST_ROUTE_URL, $request_url );
        } elseif( is_array( $request_url ) ){
            // Array of URLs
            $urls = $request_url;
        } else {
            return [];
        }

        // Try primary URL first
        if( isset( $urls['primary'] ) ){
            $response = self::get_content_remote_request( $urls['primary'] );
            if( !empty( $response ) ){
                return $response;
            }
        }

        // Try backup URL if primary fails
        if( isset( $urls['backup'] ) ){
            $response = self::get_content_remote_request( $urls['backup'] );
            if( !empty( $response ) ){
                return $response;
            }
        }

        // If both fail, return empty array
        return [];
    }

    /**
     * Handle single remote request
     *
     * @param [type] $request_url
     */
    public static function get_content_remote_request( $request_url ){
        global $wp_version;

        $response = wp_remote_get(
			$request_url,
			array(
				'timeout'    => 25,
				'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url()
			)
		);

        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return [];
        }

        $result = json_decode( wp_remote_retrieve_body( $response ), true );
        return $result;
    }
}
