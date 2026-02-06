<?php
namespace WoolentorOptions\Api;

use WP_REST_Controller;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;
use WoolentorOptions\SanitizeTrail\Sanitize_Trait;

if (!class_exists('\WoolentorOptions\Admin\Options_Field')) {
    // require_once WOOLENTOROPT_INCLUDES . '/classes/Admin/Options_field.php';
    require_once dirname(__DIR__) . '/Admin/Options_field.php';
}

// Load Pro Field functionality if available
if (function_exists('woolentor_is_pro') && woolentor_is_pro() && defined("WOOLENTOR_ADDONS_PL_PATH_PRO") && file_exists(WOOLENTOR_ADDONS_PL_PATH_PRO . 'includes/admin/admin_fields.php')) {
    require_once WOOLENTOR_ADDONS_PL_PATH_PRO . 'includes/admin/admin_fields.php';
}

/**
 * REST_API Handler for WooLentor Options
 */
class Settings extends WP_REST_Controller {

    use Sanitize_Trait;

    /**
     * API namespace
     * @var string
     */
    protected $namespace;
    
    /**
     * API endpoint base
     * @var string
     */
    protected $rest_base;
    
    /**
     * Plugin slug
     * @var string
     */
    protected $slug;
    
    /**
     * Error handling object
     * @var WP_Error
     */
    protected $errors;

    /**
     * All registered settings.
     * @var array
     */
    protected $settings;

    /**
     * Constructor initializes important properties
     */
    public function __construct() {
        $this->slug      = 'woolentor_';
        $this->namespace = 'woolentoropt/v1';
        $this->rest_base = 'settings';
        $this->settings  = \WoolentorOptions\Admin\Options_Field::instance()->get_registered_settings();
        $this->errors    = new WP_Error();

        add_filter($this->slug . '_settings_sanitize', [$this, 'sanitize_settings'], 10, 3);
    }

    /**
     * Register API routes
     * 
     * @return void
     */
    public function register_routes() {
        // Main settings endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_items'],
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'create_items'],
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => $this->get_collection_params(),
                ]
            ]
        );

        // Dependent element settings endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/dependelement',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'depend_element_settings'],
                    'permission_callback' => [$this, 'permissions_check'],
                ]
            ]
        );

        // Group settings endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/group',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'group_settings'],
                    'permission_callback' => [$this, 'permissions_check'],
                ]
            ]
        );
    }

    /**
     * Check permissions for API requests
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function permissions_check($request) {
        if (!current_user_can('manage_options')) {
            return new WP_Error('rest_forbidden', 'WOOLENTOR OPT: Permission Denied.', ['status' => 401]);
        }

        return true;
    }

    /**
     * Verify nonce from request
     * 
     * @param string $nonce Nonce to verify
     * @return bool|WP_Error
     */
    protected function verify_nonce($nonce) {
        if (!wp_verify_nonce($nonce, 'woolentor_verifynonce')) {
            return new WP_Error('rest_forbidden', __('Nonce not verified.'), ['status' => 403]);
        }
        return true;
    }

    /**
     * Get collection parameters
     * 
     * @return array
     */
    public function get_collection_params() {
        return [];
    }

    /**
     * Get settings items
     * 
     * @param \WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_items($request) {
        $nonce         = $request->get_param('nonce');
        $nonce_check   = $this->verify_nonce($nonce);

        if (is_wp_error($nonce_check)) {
            return $nonce_check;
        }

        $section = !empty($request['section']) ? sanitize_text_field($request['section']) : '';
        if (empty($section)) {
            return rest_ensure_response([]);
        }

        $items = $this->get_options_value($section);
        return rest_ensure_response($items);
    }

    /**
     * Get options values for a section
     * 
     * @param string $section Section identifier
     * @return array
     */
    public function get_options_value($section) {
        $registered_settings = !empty($section) && isset($this->settings[$section]) ? $this->settings[$section] : [];
        return woolentor_opt_get_options_value_by_section($section, $registered_settings);
    }

    /**
     * Create or update settings
     * 
     * @param \WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function create_items($request) {
        $nonce_check = $this->verify_nonce($request['settings']['verifynonce']);
        if (is_wp_error($nonce_check)) {
            return $nonce_check;
        }

        $section        = !empty($request['section']) ? sanitize_text_field($request['section']) : '';
        $sub_section    = !empty($request['subsection']) ? sanitize_text_field($request['subsection']) : '';
        $settings_received = !empty($request['settings']) ? woolentor_opt_data_clean($request['settings']) : '';
        $settings_reset    = !empty($request['reset']) ? rest_sanitize_boolean($request['reset']) : '';

        // Handle reset action
        if ($settings_reset) {
            $option_name = !empty($sub_section) ? $sub_section : $section;
            $reseted     = delete_option($option_name);
            return rest_ensure_response($reseted);
        }

        // Validate required parameters
        if (empty($section) || empty($settings_received)) {
            return new WP_Error('missing_parameters', __('Required parameters are missing.'), ['status' => 400]);
        }

        // Get registered settings for the section/subsection
        $registered_settings = !empty($sub_section) 
            ? $this->settings[$section][$this->get_section_index($section, $sub_section)]['setting_fields'] 
            : $this->settings[$section];
        
        // Get existing data from the database
        $option_name    = !empty($sub_section) ? $sub_section : $section;
        $existing_data  = get_option($option_name, []);
        $existing_data  = is_array( $existing_data ) ? $existing_data : [];

        // Process the settings
        $processed_data = $this->process_settings($registered_settings, $settings_received, $existing_data);
        
        // If there were errors
        if (is_wp_error($processed_data)) {
            return new WP_REST_Response($processed_data, 422);
        }

        // Save the data
        update_option($option_name, $processed_data);
        return rest_ensure_response($processed_data);
    }

    /**
     * Process settings data, applying validation and sanitization
     * 
     * @param array $registered_settings The registered settings configuration
     * @param array $input_data The data being submitted
     * @param array $existing_data Existing data, if any
     * @return array|WP_Error Processed data or error
     */
    protected function process_settings($registered_settings, $input_data, $existing_data = []) {
        if (!is_array($registered_settings) || empty($registered_settings)) {
            return $existing_data;
        }
        
        foreach ($registered_settings as $setting) {
            // Skip if setting doesn't meet criteria for processing
            if (!$this->should_process_setting($setting, $input_data)) {
                continue;
            }

            $sanitized_value = $this->sanitize_setting_value($setting, $input_data[$setting['id']]);
            
            // Skip empty checkbox values
            if ($setting['type'] === 'checkbox' && $sanitized_value === false) {
                continue;
            }

            // Add sanitized value if no errors
            if (!is_wp_error($sanitized_value)) {
                $existing_data[$setting['id']] = $sanitized_value;
            }
        }

        // Return errors if any occurred during processing
        if (!empty($this->errors->get_error_codes())) {
            return $this->errors;
        }

        return $existing_data;
    }

    /**
     * Determine if a setting should be processed
     * 
     * @param array $setting Setting configuration
     * @param array $input_data Input data being processed
     * @return bool
     */
    protected function should_process_setting($setting, $input_data) {
        // Must have a type
        if (empty($setting['type'])) {
            return false;
        }
        
        // Skip non-data field types
        if (in_array($setting['type'], ['html', 'title'], true)) {
            return false;
        }
        
        // Skip pro fields
        if (isset($setting['is_pro']) && $setting['is_pro']) {
            return false;
        }
        
        // Must exist in submitted data
        if (!array_key_exists($setting['id'], $input_data)) {
            return false;
        }
        
        return true;
    }

    /**
     * Sanitize a single setting value
     * 
     * @param array $setting Setting configuration
     * @param mixed $value Value to sanitize
     * @return mixed Sanitized value
     */
    protected function sanitize_setting_value($setting, $value) {
        $sanitized = apply_filters(
            $this->slug . '_settings_sanitize', 
            $value, 
            $this->errors, 
            $setting
        );
        
        return apply_filters(
            $this->slug . '_settings_sanitize_' . $setting['id'], 
            $sanitized, 
            $this->errors, 
            $setting
        );
    }

    /**
     * Element dependency settings handler
     * 
     * @param \WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function depend_element_settings($request) {
        $nonce_check = $this->verify_nonce($request['settings']['verifynonce']);
        if (is_wp_error($nonce_check)) {
            return $nonce_check;
        }

        $settings_received = !empty($request['settings']) ? woolentor_opt_data_clean($request['settings']) : '';
        $section           = !empty($request['section']) ? sanitize_text_field($request['section']) : '';
        $detect_id         = !empty($settings_received['detectId']) ? sanitize_text_field($settings_received['detectId']) : '';

        // Get the registered settings and existing data
        $registered_settings = !empty($section) 
            ? $this->settings[$section][$this->get_section_index($section, $section, 'parent_id', $detect_id)]['setting_fields'] 
            : [];
        
        $existing_data = get_option($section, []);

        // Process the settings
        $processed_data = $this->process_settings($registered_settings, $settings_received, $existing_data);
        
        // If there were errors
        if (is_wp_error($processed_data)) {
            return new WP_REST_Response($processed_data, 422);
        }

        // Save the data
        update_option($section, $processed_data);
        return rest_ensure_response($processed_data);
    }

    /**
     * Group settings handler
     * 
     * @param \WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function group_settings($request) {
        $nonce_check = $this->verify_nonce($request['settings']['verifynonce']);
        if (is_wp_error($nonce_check)) {
            return $nonce_check;
        }

        $settings_received = !empty($request['settings']) ? woolentor_opt_data_clean($request['settings']) : '';
        $section = !empty($request['group']) ? sanitize_text_field($request['group']) : '';
        $parentId = !empty($request['parentId']) ? sanitize_text_field($request['parentId']) : '';
        $section_index = $this->get_section_index($parentId, $section, 'id');

        // Get the setting group and initialize the data to save
        $registered_setting_group = $this->settings[$parentId][$section_index]['setting_tabs'];
        $data_to_save = [];

        // Process each group
        if (is_array($registered_setting_group) && !empty($registered_setting_group)) {
            foreach ($registered_setting_group as $group) {
                if (!$this->is_valid_group($group)) {
                    continue;
                }

                $group_data = $this->process_group_settings($group, $settings_received);
                
                if (!empty($group_data)) {
                    $data_to_save[$group['setting_group']] = $group_data;
                }
            }
        }

        return rest_ensure_response($data_to_save);
    }

    /**
     * Check if a group configuration is valid for processing
     * 
     * @param array $group Group configuration
     * @return bool
     */
    protected function is_valid_group($group) {
        return isset($group['fields']) && is_array($group['fields']) && !empty($group['fields']);
    }

    /**
     * Process group settings
     * 
     * @param array $group Group configuration
     * @param array $settings_received Submitted settings
     * @return array Processed settings
     */
    protected function process_group_settings($group, $settings_received) {
        if (!isset($settings_received[$group['setting_group']])) {
            return [];
        }
        
        $existing_data = is_array(get_option($group['setting_group'], [])) 
            ? get_option($group['setting_group'], []) 
            : [];
            
        $processed_data = $this->process_settings(
            $group['fields'], 
            $settings_received[$group['setting_group']], 
            $existing_data
        );
        
        if (!is_wp_error($processed_data)) {
            update_option($group['setting_group'], $processed_data);
            return $processed_data;
        }
        
        return [];
    }

    /**
     * Find section index by specified criteria
     * 
     * @param string $section Section to look in
     * @param string $find_section Section to find
     * @param string $find_key Key to match
     * @param string $field_id Optional field ID to match
     * @return int
     */
    public function get_section_index($section, $find_section, $find_key = 'section', $field_id = '') {
        if (!isset($this->settings[$section])) {
            return -1;
        }

        foreach ($this->settings[$section] as $index => $field) {
            if (!empty($field_id)) {
                if (isset($field[$find_key]) && $field[$find_key] === $find_section && $field['id'] == $field_id) {
                    return $index;
                }
            } else {
                if (isset($field[$find_key]) && $field[$find_key] === $find_section) {
                    return $index;
                }
            }
        }

        return -1;
    }

    /**
     * Main sanitize callback for settings
     * 
     * @param mixed $setting_value The value to sanitize
     * @param WP_Error $errors Error object for logging issues
     * @param array $setting The setting configuration
     * @return mixed
     */
    public function sanitize_settings($setting_value, $errors, $setting) {
        if (!empty($setting['sanitize_callback']) && is_callable($setting['sanitize_callback'])) {
            return call_user_func($setting['sanitize_callback'], $setting_value);
        } else {
            return $this->default_sanitizer($setting_value, $errors, $setting);
        }
    }

    /**
     * Default sanitizer based on setting type
     * 
     * @param mixed $setting_value The value to sanitize
     * @param WP_Error $errors Error object for logging issues
     * @param array $setting The setting configuration
     * @return mixed
     */
    public function default_sanitizer($setting_value, $errors, $setting) {
        switch ($setting['type']) {
            case 'text':
            case 'radio':
            case 'select':
                return $this->sanitize_text_field($setting_value, $errors, $setting);

            case 'textarea':
                return $this->sanitize_textarea_field($setting_value, $errors, $setting);

            case 'checkbox':
            case 'switcher':
                return $this->sanitize_checkbox_field($setting_value, $errors, $setting);
            
            case 'element':
                return $this->sanitize_element_field($setting_value, $errors, $setting);

            case 'multiselect':
            case 'multicheckbox':
                return $this->sanitize_multiple_field($setting_value, $errors, $setting);

            case 'file':
                return $this->sanitize_file_field($setting_value, $errors, $setting);
            
            case 'repeater':
                return $this->sanitize_repeater_field($setting_value, $errors, $setting);

            case 'shortable':
                return $this->sanitize_shortable_field($setting_value, $errors, $setting);

            case 'dimensions':
                return $this->sanitize_dimensions_field($setting_value, $errors, $setting);
                
            case 'multitext':
                return $this->sanitize_multitext_field($setting_value, $errors, $setting);
            
            default:
                return sanitize_text_field($setting_value);
        }
    }
}