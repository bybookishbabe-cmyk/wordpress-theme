<!doctype html>
<html class="js" <?php language_attributes(); ?>>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<meta name="theme-color" content="">
	<?php if (!defined('WPSEO_VERSION') && !defined('RANK_MATH_VERSION') && is_singular()) : ?>
		<link rel="canonical" href="<?php echo esc_url(get_permalink()); ?>">
	<?php endif; ?>
	<?php if (has_site_icon()) : ?>
		<link rel="icon" type="image/png" href="<?php echo esc_url(get_site_icon_url(32)); ?>">
	<?php endif; ?>
	<?php if (!defined('WPSEO_VERSION') && !defined('RANK_MATH_VERSION') && is_singular()) : ?>
		<meta name="description" content="<?php echo esc_attr(get_the_excerpt()); ?>">
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class('gradient'); ?>>
<?php wp_body_open(); ?>
<a class="skip-to-content-link button visually-hidden" href="#MainContent">
	<?php esc_html_e('Skip to content', 'bybookishbabe-shopify-port'); ?>
</a>
<?php get_template_part('template-parts/header'); ?>
<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
