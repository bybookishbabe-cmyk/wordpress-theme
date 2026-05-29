<?php
/**
 * Single Easy Digital Downloads product.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$shop_css_path = get_theme_file_path('assets/css/shop-page.css');
wp_enqueue_style('bbb-shop-page', get_template_directory_uri() . '/assets/css/shop-page.css', array('bbb-base'), file_exists($shop_css_path) ? (string) filemtime($shop_css_path) : wp_get_theme()->get('Version'));
$shop_cart_js_path = get_theme_file_path('assets/js/shop-edd-cart.js');
wp_enqueue_script('bbb-shop-edd-cart', get_template_directory_uri() . '/assets/js/shop-edd-cart.js', array(), file_exists($shop_cart_js_path) ? (string) filemtime($shop_cart_js_path) : wp_get_theme()->get('Version'), true);

get_header();

if (have_posts()) {
	while (have_posts()) {
		the_post();
		get_template_part('template-parts/shop-product-single');
	}
}

get_footer();
