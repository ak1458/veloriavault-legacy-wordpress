<?php
/**
 * Fields.
 */

namespace WLPF\Admin;

/**
 * Class.
 */
class Fields {

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'woolentor_pro_product_filter_fields', array( $this, 'setting_fields' ) );
    }

    /**
     * Setting fields.
     */
    public function setting_fields() {
        $fields = array(
            array(
                'id'    => 'enable',
                'name'   => esc_html__( 'Enable / Disable', 'woolentor-pro' ),
                'desc'    => esc_html__( 'You can enable / disable product filter from here.', 'woolentor-pro' ),
                'type'    => 'checkbox',
                'default' => 'off',
                'class'   => 'woolentor-action-field-left',
            ),
            array(
                'id'   => 'filters',
                'name'  => esc_html__( 'Filters', 'woolentor-pro' ),
                'type'   => 'repeater',
                'title_field' => 'filter_unique_id', // For save if title_pattern is not set or excution fail
                'title_pattern' => 'ID# {{filter_unique_id}} - {{filter_label}}',
                'unique_id' => 'filter_unique_id',
                'fields' => $this->filter_fields(),
                'update_fields' => [
                    [
                        'repeater_id' => 'groups', // ID of the field to update
                        'field_id' => 'group_filters', // ID of the field to update
                        'type' => 'repeater',
                        'value_key' => 'filter_unique_id', // Repeater item field to use as option value
                        'label_key' => 'filter_label' // Repeater item field to use as option label
                    ]
                ],
                'options' => [
                    'button_label' => esc_html__( 'Add Filter', 'woolentor-pro' ),
                ],
            ),
            array(
                'id'   => 'groups',
                'name'  => esc_html__( 'Groups', 'woolentor-pro' ),
                'type'   => 'repeater',
                'title_field' => 'group_unique_id',
                'unique_id' => 'group_unique_id', // For save if title_pattern is not set or excution fail
                'title_pattern' => 'ID# {{group_unique_id}} - {{group_label}}',
                'fields' => $this->group_fields(),
                'options' => [
                    'button_label' => esc_html__( 'Add Filter Group', 'woolentor-pro' ),
                ],
            ),
            array(
                'id'      => 'general_settings_title',
                'heading'  => esc_html__( 'General Settings', 'woolentor-pro' ),
                'type'      => 'title',
                'size'      => 'margin_0 regular',
                'class'     => 'element_section_title_area',
            ),
            array(
                'id'    => 'ajax_filter',
                'name'   => esc_html__( 'Ajax filter', 'woolentor-pro' ),
                'type'    => 'checkbox',
                'default' => 'on',
                'class'   => 'woolentor-action-field-left',
            ),
            array(
                'id'      => 'add_ajax_query_args_to_url',
                'name'     => esc_html__( 'Add ajax query arguments to URL', 'woolentor-pro' ),
                'type'      => 'checkbox',
                'default'   => 'on',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'ajax_filter', 'operator'=>'==', 'value'=>'on' ),
            ),
            array(
                'id'      => 'time_to_take_ajax_action',
                'name'     => esc_html__( 'Time to take ajax action (ms)', 'woolentor-pro' ),
                'type'      => 'number',
                'default'   => '500',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'ajax_filter', 'operator'=>'==', 'value'=>'on' ),
            ),
            array(
                'id'      => 'time_to_take_none_ajax_action',
                'name'     => esc_html__( 'Time to take none ajax action (ms)', 'woolentor-pro' ),
                'type'      => 'number',
                'default'   => '1000',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'ajax_filter', 'operator'=>'==', 'value'=>'0' ),
            ),
            array(
                'id'    => 'show_filter_arguments',
                'name'   => esc_html__( 'Show filter arguments', 'woolentor-pro' ),
                'type'    => 'checkbox',
                'default' => 'off',
                'class'   => 'woolentor-action-field-left wlpf-show-filter-arguments',
            ),
            array(
                'id'        => 'query_args_prefix',
                'name'       => esc_html__( 'Query arguments prefix', 'woolentor-pro' ),
                'type'        => 'text',
                'placeholder' => 'wlpf_',
                'default'     => 'wlpf_',
                'class'       => 'woolentor-action-field-left',
            ),
            array(
                'id'      => 'taxonomy_list_in_page',
                'name'     => esc_html__( 'Show all taxonomy list in taxonomy page', 'woolentor-pro' ),
                'desc'      => esc_html__('If enable this option then all terms list show in taxonomy page. ( Ex: product-category/category-name/ )','woolentor-pro'),
                'type'    => 'checkbox',
                'default' => 'off',
                'class'     => 'woolentor-action-field-left',
            ),
            array(
                'id'      => 'default_shop_and_product_archive_title',
                'heading'  => esc_html__( 'Default Shop & Product Archive', 'woolentor-pro' ),
                'type'      => 'title',
                'size'      => 'margin_0 regular',
                'class'     => 'element_section_title_area',
            ),
            array(
                'id'        => 'products_wrapper_selector',
                'name'       => esc_html__( 'Products wrapper selector', 'woolentor-pro' ),
                'type'        => 'text',
                'placeholder' => '.wlpf-products-wrap',
                'default'     => '.wlpf-products-wrap',
                'class'       => 'woolentor-action-field-left',
            ),
        );

        return $fields;
    }

    /**
     * Filter fields.
     */
    public function filter_fields() {
        $fields = array(
            array(
                'id'  => 'filter_shortcode',
                'name' => esc_html__( 'Shortcode', 'woolentor-pro' ),
                'type'  => 'text',
                'class' => 'woolentor-action-field-left wlpf-filter-shortcode wlpf-dynamic-shortcode',
                'generate_value' => [
                    'show_key' => 'filter_shortcode',
                    'pattern' => '[wlpf_filter id="{{filter_unique_id}}"]',
                    'copyable' => true,
                    'success_msg' => esc_html__('Shortcode copied!', 'woolentor-pro'),
                ],
            ),
            array(
                'id'  => 'filter_unique_id',
                'name' => esc_html__( 'Unique ID', 'woolentor-pro' ),
                'type'  => 'hidden',
                'class' => 'woolentor-action-field-left wlpf-filter-unique-id wlpf-dynamic-unique-id',
            ),
            array(
                'id'  => 'filter_label',
                'name' => esc_html__( 'Label', 'woolentor-pro' ),
                'type'  => 'text',
                'class' => 'woolentor-action-field-left wlpf-filter-label wlpf-dynamic-label',
            ),
            array(
                'id'    => 'filter_element',
                'name'   => esc_html__( 'Element', 'woolentor-pro' ),
                'type'    => 'select',
                'options' => array(
                    'taxonomy'  => esc_html__( 'Taxonomy', 'woolentor-pro' ),
                    'attribute' => esc_html__( 'Attribute', 'woolentor-pro' ),
                    'author'    => esc_html__( 'Author (vendor)', 'woolentor-pro' ),
                    'price'     => esc_html__( 'Price range', 'woolentor-pro' ),
                    'search'    => esc_html__( 'Search input', 'woolentor-pro' ),
                    'sorting'   => esc_html__( 'Sorting', 'woolentor-pro' ),
                ),
                'default' => 'taxonomy',
                'class'   => 'woolentor-action-field-left',
            ),
            array(
                'id'      => 'filter_taxonomy_options',
                'heading'  => esc_html__( 'Taxonomy options', 'woolentor-pro' ),
                'type'      => 'title',
                'size'      => 'margin_0 regular',
                'class'     => 'element_section_title_area',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'taxonomy' ),
            ),
            array(
                'id'      => 'filter_attribute_options',
                'heading'  => esc_html__( 'Attribute options', 'woolentor-pro' ),
                'type'      => 'title',
                'size'      => 'margin_0 regular',
                'class'     => 'element_section_title_area',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'attribute' ),
            ),
            array(
                'id'      => 'filter_author_options',
                'heading'  => esc_html__( 'Author (vendor) options', 'woolentor-pro' ),
                'type'      => 'title',
                'size'      => 'margin_0 regular',
                'class'     => 'element_section_title_area',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'author' ),
            ),
            array(
                'id'      => 'filter_price_options',
                'heading'  => esc_html__( 'Price options', 'woolentor-pro' ),
                'type'      => 'title',
                'size'      => 'margin_0 regular',
                'class'     => 'element_section_title_area',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'price' ),
            ),
            array(
                'id'      => 'filter_search_options',
                'heading'  => esc_html__( 'Search options', 'woolentor-pro' ),
                'type'      => 'title',
                'size'      => 'margin_0 regular',
                'class'     => 'element_section_title_area',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'search' ),
            ),
            array(
                'id'      => 'filter_sorting_options',
                'heading'  => esc_html__( 'Sorting options', 'woolentor-pro' ),
                'type'      => 'title',
                'size'      => 'margin_0 regular',
                'class'     => 'element_section_title_area',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'sorting' ),
            ),
            array(
                'id'      => 'filter_taxonomy',
                'name'     => esc_html__( 'Taxonomy', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => wlpf_get_product_taxonomies( 'product' ),
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'taxonomy' ),
            ),
            array(
                'id'        => 'filter_taxonomy_terms_include',
                'name'       => esc_html__( 'Terms inlcudes', 'woolentor-pro' ),
                'type'        => 'text',
                'placeholder' => esc_html__( 'Comma separated IDs', 'woolentor-pro' ),
                'class'       => 'woolentor-action-field-left',
                'condition'   => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'taxonomy' ),
            ),
            array(
                'id'        => 'filter_taxonomy_terms_exclude',
                'name'       => esc_html__( 'Terms exlcudes', 'woolentor-pro' ),
                'type'        => 'text',
                'placeholder' => esc_html__( 'Comma separated IDs', 'woolentor-pro' ),
                'class'       => 'woolentor-action-field-left',
                'condition'   => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'taxonomy' ),
            ),
            array(
                'id'       => 'filter_attribute',
                'name'      => esc_html__( 'Attribute', 'woolentor-pro' ),
                'type'       => 'select',
                'options'    => wlpf_get_product_attributes(),
                'class'      => 'woolentor-action-field-left',
                'condition'  => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'attribute' ),
            ),
            array(
                'id'        => 'filter_attribute_terms_include',
                'name'       => esc_html__( 'Terms inlcudes', 'woolentor-pro' ),
                'type'        => 'text',
                'placeholder' => esc_html__( 'Comma separated IDs', 'woolentor-pro' ),
                'class'       => 'woolentor-action-field-left',
                'condition'   => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'attribute' ),
            ),
            array(
                'id'        => 'filter_attribute_terms_exclude',
                'name'       => esc_html__( 'Terms exlcudes', 'woolentor-pro' ),
                'type'        => 'text',
                'placeholder' => esc_html__( 'Comma separated IDs', 'woolentor-pro' ),
                'class'       => 'woolentor-action-field-left',
                'condition'   => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'attribute' ),
            ),
            array(
                'id'      => 'filter_terms_operator',
                'name'     => esc_html__( 'Terms operator', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'in'     => esc_html__( 'IN', 'woolentor-pro' ),
                    'not_in' => esc_html__( 'NOT IN', 'woolentor-pro' ),
                    'and'    => esc_html__( 'AND', 'woolentor-pro' ),
                ),
                'default'   => 'in',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'any', 'value'=>'taxonomy,attribute' ),
            ),
            array(
                'id'        => 'filter_authors_include',
                'name'       => esc_html__( 'Author inlcudes', 'woolentor-pro' ),
                'type'        => 'text',
                'placeholder' => esc_html__( 'Comma separated IDs', 'woolentor-pro' ),
                'class'       => 'woolentor-action-field-left',
                'condition'   => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'author' ),
            ),
            array(
                'id'        => 'filter_authors_exclude',
                'name'       => esc_html__( 'Author exlcudes', 'woolentor-pro' ),
                'type'        => 'text',
                'placeholder' => esc_html__( 'Comma separated IDs', 'woolentor-pro' ),
                'class'       => 'woolentor-action-field-left',
                'condition'   => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'author' ),
            ),
            array(
                'id'      => 'filter_sortings_include',
                'name'     => esc_html__( 'Sortings inlcudes', 'woolentor-pro' ),
                'type'      => 'multiselect',
                'options'   => wlpf_get_sorting_options(),
                'default'   => wlpf_get_sorting_options( 'key' ),
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'sorting' ),
            ),
            array(
                'id'      => 'filter_orderby',
                'name'     => esc_html__( 'Orderby', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => wlpf_get_terms_orderby_options(),
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'any', 'value'=>'taxonomy,attribute' ),
            ),
            array(
                'id'      => 'filter_author_orderby',
                'name'     => esc_html__( 'Orderby', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => wlpf_get_author_orderby_options(),
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'author' ),
            ),
            array(
                'id'      => 'filter_order',
                'name'     => esc_html__( 'Order', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'asc'  => esc_html__( 'Ascending', 'woolentor-pro' ),
                    'desc' => esc_html__( 'Descending', 'woolentor-pro' ),
                ),
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'any', 'value'=>'taxonomy,attribute,author' ),
            ),
            array(
                'id'      => 'filter_children_terms',
                'name'     => esc_html__( 'Show children terms', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default'   => 'on',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'taxonomy' ),
            ),
            array(
                'id'      => 'filter_terms_hierarchy',
                'name'     => esc_html__( 'Terms hierarchy', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default'   => 'on',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element|filter_children_terms', 'operator'=>'==|==', 'value'=>'taxonomy|on' ),
            ),
            array(
                'id'      => 'filter_terms_collapsible',
                'name'     => esc_html__( 'Children terms collapsible', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default'   => 'on',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element|filter_children_terms|filter_terms_hierarchy', 'operator'=>'==|==|==', 'value'=>'taxonomy|on|on' )
            ),
            array(
                'id'      => 'filter_terms_collapsed_by_default',
                'name'     => esc_html__( 'Children terms collapsed by default', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default'   => 'on',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element|filter_children_terms|filter_terms_hierarchy|filter_terms_collapsible', 'operator'=>'==|==|==|==', 'value'=>'taxonomy|on|on|on' )
            ),
            array(
                'id'      => 'filter_hide_empty_terms',
                'name'     => esc_html__( 'Hide empty terms', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default'   => 'on',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'any', 'value'=>'taxonomy,attribute' ),
            ),
            array(
                'id'      => 'filter_with_children_terms',
                'name'     => esc_html__( 'Filter with children terms', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default'   => 'on',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'taxonomy' ),
            ),
            array(
                'id'      => 'filter_field_type',
                'name'     => esc_html__( 'Field type', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'checkbox' => esc_html__( 'Checkbox', 'woolentor-pro' ),
                    'radio'    => esc_html__( 'Radio', 'woolentor-pro' ),
                    'select'   => esc_html__( 'Select', 'woolentor-pro' ),
                ),
                'default'   => 'checkbox',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'any', 'value'=>'taxonomy,attribute,author' ),
            ),
            array(
                'id'      => 'filter_sorting_field_type',
                'name'     => esc_html__( 'Field type', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'radio'  => esc_html__( 'Radio', 'woolentor-pro' ),
                    'select' => esc_html__( 'Select', 'woolentor-pro' ),
                ),
                'default'   => 'radio',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'sorting' ),
            ),
            array(
                'id'        => 'filter_select_placeholder',
                'name'       => esc_html__( 'Select placeholder', 'woolentor-pro' ),
                'type'        => 'text',
                'placeholder' => esc_html__( 'Choose an option', 'woolentor-pro' ),
                'default'     => esc_html__( 'Choose an option', 'woolentor-pro' ),
                'class'       => 'woolentor-action-field-left',
                'condition'   => array( 'key'=>'filter_element|filter_field_type', 'operator'=>'any|==', 'value'=>'taxonomy,attribute,author|select' ),
            ),
            array(
                'id'        => 'filter_sorting_select_placeholder',
                'name'       => esc_html__( 'Select placeholder', 'woolentor-pro' ),
                'type'        => 'text',
                'placeholder' => esc_html__( 'Choose an option', 'woolentor-pro' ),
                'default'     => esc_html__( 'Choose an option', 'woolentor-pro' ),
                'class'       => 'woolentor-action-field-left',
                'condition'   => array( 'key'=>'filter_element|filter_sorting_field_type', 'operator'=>'any|==', 'value'=>'sorting|select' ),
            ),
            array(
                'id'      => 'filter_terms_name',
                'name'     => esc_html__( 'Show terms name', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default'   => 'on',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element|filter_field_type', 'operator'=>'any|any', 'value'=>'taxonomy,attribute|checkbox,radio' ),
            ),
            array(
                'id'      => 'filter_terms_count',
                'name'     => esc_html__( 'Show products count with terms name', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default'   => 'off',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'any', 'value'=>'taxonomy,attribute' ),
            ),
            array(
                'id'      => 'filter_authors_count',
                'name'     => esc_html__( 'Show products count with authors name', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default'   => 'off',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'author' ),
            ),
            array(
                'id'      => 'filter_search_placeholder',
                'name'     => esc_html__( 'Placeholder', 'woolentor-pro' ),
                'type'      => 'text',
                'default'   => esc_html__( 'Search keyword', 'woolentor-pro' ),
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element', 'operator'=>'==', 'value'=>'search' ),
            ),
            array(
                'id'    => 'filter_apply_action',
                'name'   => esc_html__( 'Apply', 'woolentor-pro' ),
                'type'    => 'select',
                'options' => array(
                    'auto'   => esc_html__( 'Auto', 'woolentor-pro' ),
                    'button' => esc_html__( 'Button click', 'woolentor-pro' ),
                ),
                'default' => 'auto',
                'class'   => 'woolentor-action-field-left',
            ),
            array(
                'id'      => 'filter_apply_action_button_txt',
                'name'     => esc_html__( 'Apply button text', 'woolentor-pro' ),
                'type'      => 'text',
                'default'   => esc_html__( 'Apply', 'woolentor-pro' ),
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_apply_action', 'operator'=>'==', 'value'=>'button' ),
            ),
            array(
                'id'    => 'filter_clear_action',
                'name'   => esc_html__( 'Clear', 'woolentor-pro' ),
                'type'    => 'select',
                'options' => array(
                    'none'   => esc_html__( 'Default', 'woolentor-pro' ),
                    'button' => esc_html__( 'Button click', 'woolentor-pro' ),
                ),
                'default' => 'auto',
                'class'   => 'woolentor-action-field-left',
            ),
            array(
                'id'      => 'filter_clear_action_button_txt',
                'name'     => esc_html__( 'Clear button text', 'woolentor-pro' ),
                'type'      => 'text',
                'default'   => esc_html__( 'Clear', 'woolentor-pro' ),
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_clear_action', 'operator'=>'==', 'value'=>'button' ),
            ),
            array(
                'id'      => 'filter_max_height',
                'name'     => esc_html__( 'Maximum height (px)', 'woolentor-pro' ),
                'type'      => 'number',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element|filter_field_type', 'operator'=>'any|any', 'value'=>'taxonomy,attribute,author|checkbox,radio' ),
            ),
            array(
                'id'      => 'filter_sorting_max_height',
                'name'     => esc_html__( 'Maximum height (px)', 'woolentor-pro' ),
                'type'      => 'number',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element|filter_sorting_field_type', 'operator'=>'any|==', 'value'=>'sorting|radio' ),
            ),
            array(
                'id'    => 'filter_collapsible',
                'name'   => esc_html__( 'Collapsible', 'woolentor-pro' ),
                'type'    => 'select',
                'options' => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default' => 'on',
                'class'   => 'woolentor-action-field-left',
            ),
            array(
                'id'      => 'filter_collapsed_by_default',
                'name'     => esc_html__( 'Collapsed by default', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default'   => 'off',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_collapsible', 'operator'=>'==', 'value'=>'on' ),
            ),
            array(
                'id'      => 'filter_terms_type',
                'name'     => esc_html__( 'Terms type', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    ''      => esc_html__( 'Default', 'woolentor-pro' ),
                    'color' => esc_html__( 'Color', 'woolentor-pro' ),
                    'image' => esc_html__( 'Image', 'woolentor-pro' ),
                ),
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'filter_element|filter_field_type', 'operator'=>'any|any', 'value'=>'taxonomy,attribute|checkbox,radio' )
            )
        );

        return $fields;
    }

    /**
     * Group fields.
     */
    public function group_fields() {
        $fields = array(
            array(
                'id'  => 'group_shortcode',
                'name' => esc_html__( 'Shortcode', 'woolentor-pro' ),
                'type'  => 'text',
                'class' => 'woolentor-action-field-left wlpf-group-shortcode wlpf-dynamic-shortcode',
                'generate_value' => [
                    'show_key' => 'group_shortcode',
                    'pattern' => '[wlpf_group id="{{group_unique_id}}"]',
                    'copyable' => true,
                    'success_msg' => esc_html__('Shortcode copied!', 'woolentor-pro'),
                ],
            ),
            array(
                'id'  => 'group_unique_id',
                'name' => esc_html__( 'Unique ID', 'woolentor-pro' ),
                'type'  => 'hidden',
                'class' => 'woolentor-action-field-left wlpf-group-unique-id wlpf-dynamic-unique-id',
            ),
            array(
                'id'  => 'group_label',
                'name' => esc_html__( 'Label', 'woolentor-pro' ),
                'type'  => 'text',
                'class' => 'woolentor-action-field-left wlpf-group-label wlpf-dynamic-label',
            ),
            array(
                'id'    => 'group_filters',
                'name'   => esc_html__( 'Filters', 'woolentor-pro' ),
                'type'    => 'multiselect',
                'options' => wlpf_get_filters_list(),
                'convertnumber' => true,
                'class'   => 'woolentor-action-field-left wlpf-group-filters',
            ),
            array(
                'id'    => 'group_filters_label',
                'name'   => esc_html__( 'Filters label', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default'   => 'on',
                'class'   => 'woolentor-action-field-left',
            ),
            array(
                'id'    => 'group_apply_action',
                'name'   => esc_html__( 'Apply', 'woolentor-pro' ),
                'type'    => 'select',
                'options' => array(
                    'auto'       => esc_html__( 'Auto', 'woolentor-pro' ),
                    'button'     => esc_html__( 'Button click', 'woolentor-pro' ),
                    'individual' => esc_html__( 'Individual', 'woolentor-pro' ),
                ),
                'default' => 'button',
                'class'   => 'woolentor-action-field-left',
            ),
            array(
                'id'      => 'group_apply_action_button_txt',
                'name'     => esc_html__( 'Apply button text', 'woolentor-pro' ),
                'type'      => 'text',
                'default'   => esc_html__( 'Apply All', 'woolentor-pro' ),
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'group_apply_action', 'operator'=>'==', 'value'=>'button' ),
            ),
            array(
                'id'      => 'group_apply_action_button_pos',
                'name'     => esc_html__( 'Apply button position', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'top'    => esc_html__( 'Top', 'woolentor-pro' ),
                    'bottom' => esc_html__( 'Bottom', 'woolentor-pro' ),
                    'both'   => esc_html__( 'Top & Bottom', 'woolentor-pro' ),
                ),
                'default'   => 'bottom',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'group_apply_action', 'operator'=>'==', 'value'=>'button' ),
            ),
            array(
                'id'    => 'group_clear_action',
                'name'   => esc_html__( 'Clear', 'woolentor-pro' ),
                'type'    => 'select',
                'options' => array(
                    'none'       => esc_html__( 'Default', 'woolentor-pro' ),
                    'button'     => esc_html__( 'Button click', 'woolentor-pro' ),
                    'individual' => esc_html__( 'Individual', 'woolentor-pro' ),
                ),
                'default' => 'button',
                'class'   => 'woolentor-action-field-left',
            ),
            array(
                'id'      => 'group_clear_action_button_txt',
                'name'     => esc_html__( 'Clear button text', 'woolentor-pro' ),
                'type'      => 'text',
                'default'   => esc_html__( 'Clear All', 'woolentor-pro' ),
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'group_clear_action', 'operator'=>'==', 'value'=>'button' ),
            ),
            array(
                'id'      => 'group_clear_action_button_pos',
                'name'     => esc_html__( 'Clear button position', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'top'    => esc_html__( 'Top', 'woolentor-pro' ),
                    'bottom' => esc_html__( 'Bottom', 'woolentor-pro' ),
                    'both'   => esc_html__( 'Top & Bottom', 'woolentor-pro' ),
                ),
                'default'   => 'bottom',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'group_clear_action', 'operator'=>'==', 'value'=>'button' ),
            ),
            array(
                'id'  => 'group_max_height',
                'name' => esc_html__( 'Maximum height (px)', 'woolentor-pro' ),
                'type'  => 'number',
                'class' => 'woolentor-action-field-left',
            ),
            array(
                'id'    => 'group_collapsible',
                'name'   => esc_html__( 'Collapsible', 'woolentor-pro' ),
                'type'    => 'select',
                'options' => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default' => 'on',
                'class'   => 'woolentor-action-field-left',
            ),
            array(
                'id'      => 'group_collapsed_by_default',
                'name'     => esc_html__( 'Collapsed by default', 'woolentor-pro' ),
                'type'      => 'select',
                'options'   => array(
                    'on'  => esc_html__( 'Yes', 'woolentor-pro' ),
                    'off' => esc_html__( 'No', 'woolentor-pro' ),
                ),
                'default'   => 'off',
                'class'     => 'woolentor-action-field-left',
                'condition' => array( 'key'=>'group_collapsible', 'operator'=>'==', 'value'=>'on' ),
            )
        );

        return $fields;
    }

}