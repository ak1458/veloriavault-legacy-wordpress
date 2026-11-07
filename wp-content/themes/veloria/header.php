<?php
/**
 * Document head + site header (src/app/layout.tsx, PremiumHeader.tsx).
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body id="top" <?php body_class(); ?> style="font-family: var(--font-lato), var(--font-sans), sans-serif">
<?php wp_body_open(); ?>
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-white focus:text-black focus:rounded focus:shadow-lg">Skip to main content</a>
<?php if ( ! veloria_elementor_location( 'header' ) ) { get_template_part( 'template-parts/header/site-header' ); } ?>
<main id="main-content">
