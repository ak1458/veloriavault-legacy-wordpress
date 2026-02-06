<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

$uniqClass = 'woolentorblock-' . $settings['blockUniqId'];
$areaClasses = array($uniqClass, 'woolentor-order-bump-area');

!empty($settings['className']) ? $areaClasses[] = esc_attr($settings['className']) : '';

echo '<div class="' . implode(' ', $areaClasses) . '">';
    if ( empty( $settings['selectedOrderBump'] ) ) {
        echo esc_html__('Please Select Order Bump', 'woolentor-pro');
    } else {
        $shortcode_attributes = [
            'id' => $settings['selectedOrderBump'],
            'block' => $block
        ];
        echo woolentor_do_shortcode('woolentor_order_bump', $shortcode_attributes);
    }
echo '</div>';