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
	<?php
	$bbb_header_share_request = (string) ($_SERVER['REQUEST_URI'] ?? '');
	$bbb_header_share_slug = '';
	$bbb_header_share_aliases = array(
		'sss-printable-kindle-inserts' => 'sss-printable-kindle-inserts',
		'kindle-inserts'               => 'kindle-inserts',
		'books-like-fourth-wing'       => 'books-like-fourth-wing',
	);
	foreach ($bbb_header_share_aliases as $bbb_header_share_needle => $bbb_header_share_card_slug) {
		if (str_contains($bbb_header_share_request, $bbb_header_share_needle)) {
			$bbb_header_share_slug = $bbb_header_share_needle;
			break;
		}
	}
	if (isset($bbb_header_share_aliases[$bbb_header_share_slug])) {
		$bbb_header_share_image = get_theme_file_uri('assets/seo/share-cards/' . $bbb_header_share_aliases[$bbb_header_share_slug] . '.png');
		ob_start(
			static function (string $html) use ($bbb_header_share_image): string {
				return str_replace('https://bybookishbabe.com/wp-content/uploads/2026/05/bybookishbabe.png', $bbb_header_share_image, $html);
			}
		);
	}
	if (function_exists('bbb_social_share_start_head_buffer')) {
		bbb_social_share_start_head_buffer();
	}
	wp_head();
	if (function_exists('bbb_social_share_flush_head_buffer')) {
		bbb_social_share_flush_head_buffer();
	}
	if (isset($bbb_header_share_aliases[$bbb_header_share_slug]) && ob_get_level() > 0) {
		ob_end_flush();
	}
	?>
</head>
<body <?php body_class('gradient'); ?>>
<?php wp_body_open(); ?>
<?php if (function_exists('bbb_render_pwa_promo')) : ?>
	<?php bbb_render_pwa_promo('header'); ?>
<?php endif; ?>
<a class="skip-to-content-link button visually-hidden" href="#MainContent">
	<?php esc_html_e('Skip to content', 'bybookishbabe-shopify-port'); ?>
</a>
<?php get_template_part('template-parts/header'); ?>
<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
