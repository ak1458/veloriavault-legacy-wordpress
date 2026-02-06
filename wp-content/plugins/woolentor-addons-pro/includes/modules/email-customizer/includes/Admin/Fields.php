<?php
/**
 * Fields.
 */

namespace Woolentor_Email_Customizer\Admin;

/**
 * Fields class.
 */
class Fields {

	/**
     * Fields constructor.
     */
    public function __construct() {
        // Element tabs admin fields.
        add_filter( 'woolentor_elements_tabs_admin_fields_vue', array( $this, 'email_admin_fields' ), 99, 1 );

        // Template builder.
        if ( did_action( 'elementor/loaded' ) ) {
            add_filter( 'woolentor_template_menu_tabs', array( $this, 'email_template_menu_navs' ) );
            add_filter( 'woolentor_template_types', array( $this, 'email_template_type' ) );
        }
    }

    /**
     * Email admin fields.
     */
    public function email_admin_fields( $fields = array() ) {
        $email_fields = array(
            array(
                'id'      => 'email_widget_heading',
                'heading'  => esc_html__( 'Email', 'woolentor-pro' ),
                'type'      => 'title',
                'class'     => 'woolentor_heading_style_two'
            ),
            array(
                'id'  => 'wl_email_heading',
                'name' => esc_html__( 'Heading', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_image',
                'name' => esc_html__( 'Image', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_text_editor',
                'name' => esc_html__( 'Text Editor', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_video',
                'name' => esc_html__( 'Video', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_button',
                'name' => esc_html__( 'Button', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_divider',
                'name' => esc_html__( 'Divider', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_spacer',
                'name' => esc_html__( 'Spacer', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_nav_menu',
                'name' => esc_html__( 'Nav Menu', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_social_icons',
                'name' => esc_html__( 'Social Icons', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_products',
                'name' => esc_html__( 'Products', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_order_details',
                'name' => esc_html__( 'Order Details', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_order_note',
                'name' => esc_html__( 'Order Note', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_downloads',
                'name' => esc_html__( 'Downloads', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_billing_address',
                'name' => esc_html__( 'Billing Address', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_shipping_address',
                'name' => esc_html__( 'Shipping Address', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
            array(
                'id'  => 'wl_email_customer_note',
                'name' => esc_html__( 'Customer Note', 'woolentor-pro' ),
                'type'  => 'element',
                'default' => 'on'
            ),
        );

        $fields = array_merge( $fields, $email_fields );

        return $fields;
    }

    /**
     * Email template menu navs.
     */
    public function email_template_menu_navs( $navs ) {
        $emails = woolentor_wc_get_emails( 'title' );

        if ( is_array( $emails ) && ! empty( $emails ) ) {
            foreach ( $emails as $email_id => $email_title ) {
                $email_id = sanitize_text_field( $email_id );
                $email_title = sanitize_text_field( $email_title );

                $email_key = 'email_' . $email_id;
                $email_label = ucwords( $email_title );

                $submenu[ $email_key ] = array(
                    'label' => $email_label,
                );
            }
        } else {
            $submenu = array();
        }

        if ( ! empty( $submenu ) ) {
            $navs['emails'] = array(
                'label' => esc_html__( 'Emails', 'woolentor-pro' ),
                'submenu' => $submenu,
            );
        }

        return $navs;
    }

    /**
     * Email template type.
     */
    public function email_template_type( $types ) {
        $emails = woolentor_wc_get_emails( 'title' );

        if ( is_array( $emails ) && ! empty( $emails ) ) {
            foreach ( $emails as $email_id => $email_title ) {
                $email_id = sanitize_text_field( $email_id );
                $email_title = sanitize_text_field( $email_title );

                $email_key = 'email_' . $email_id;
                $email_label = ucwords( sprintf( esc_html__('Email %1$s','woolentor-pro'), $email_title ) );

                $types[ $email_key ] = array(
                    'label' => $email_label,
                    'optionkey' => $email_key,
                );
            }
        }

        return $types;
    }

}