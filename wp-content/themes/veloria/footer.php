<?php
/**
 * Site footer, mobile bottom nav, chat widget (PremiumFooter.tsx,
 * MobileBottomNav.tsx, AgenticChatWidget.tsx).
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;
?>
</main>
<?php
if ( ! veloria_elementor_location( 'footer' ) ) {
	get_template_part( 'template-parts/footer/site-footer' );
	get_template_part( 'template-parts/footer/mobile-nav' );
	get_template_part( 'template-parts/chat/widget' );
}
wp_footer();
?>
</body>
</html>
