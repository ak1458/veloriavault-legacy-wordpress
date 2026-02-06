<?php
/**
 * Email Footer for Abandoned Cart Recovery
 *
 * @package WooLentor\Templates\Emails
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$site_name = get_bloginfo( 'name' );
$current_year = date( 'Y' );
?>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px; background-color: #f8f9fa; border-top: 1px solid #e9ecef;" bgcolor="#f8f9fa">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="text-align: center; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.5; color: #666666;" align="center">
                                        <div style="margin-bottom: 15px;">
                                            <strong style="color: #333333;"><?php echo esc_html( $site_name ); ?></strong>
                                        </div>
                                        <div style="margin-bottom: 15px;">
                                            <a href="<?php echo esc_url( home_url() ); ?>" style="color: #007cba; text-decoration: none;"><?php echo esc_html( home_url() ); ?></a>
                                        </div>
                                        <div style="margin-bottom: 15px; font-size: 12px; color: #999999;">
                                            <?php printf( __( '&copy; %s %s. All rights reserved.', 'woolentor' ), $current_year, esc_html( $site_name ) ); ?>
                                        </div>
                                        <?php if( get_privacy_policy_url() ): ?>
                                        <div style="margin-bottom: 10px; font-size: 12px;">
                                            <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" style="color: #666666; text-decoration: none;"><?php _e( 'Privacy Policy', 'woolentor' ); ?></a>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>