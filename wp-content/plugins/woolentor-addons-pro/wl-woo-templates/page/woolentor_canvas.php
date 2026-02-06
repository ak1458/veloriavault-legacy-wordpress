<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
		<title><?php echo wp_get_document_title(); ?></title>
	<?php endif; ?>
	<?php wp_head(); ?>
	<?php
	// Keep the following line after `wp_head()` call, to ensure it's not overridden by another templates.
	?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
</head>
<body <?php body_class(); ?>>
	<?php $width = apply_filters( 'woolentor_builder_template_width', 1200 ); ?>
	<div class="woolentor-page-template" style="margin:0 auto; max-width:<?php echo $width ? $width.'px; padding: 0 15px;' : '100%;'; ?>">
		<?php
			while ( have_posts() ) { 
				the_post();
				do_action('woolentor/builder/content');
			}
		?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>