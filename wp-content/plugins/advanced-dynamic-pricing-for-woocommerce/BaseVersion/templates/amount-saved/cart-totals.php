<?php
defined('ABSPATH') or exit;

/**
 * @var $title string
 * @var $amount_saved float
 */
?>
<tr class="order-total adp-discount">
    <th><?php echo esc_html($title); ?></th>
    <td data-title="<?php echo esc_attr($title); ?>"><?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo wc_price($amount_saved); ?></td>
</tr>
