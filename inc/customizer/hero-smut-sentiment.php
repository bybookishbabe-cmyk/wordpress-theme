<?php
/**
 * Customizer Settings: Homepage Hero
 * Converted from: sections/hero-smut-sentiment.liquid schema.
 *
 * @package ByBookishBabeShopifyPort
 */

function bbb_hero_customizer_settings(WP_Customize_Manager $wp_customize): void {
	$wp_customize->add_section(
		'bbb_hero',
		array(
			'title'    => __('Hero Section', 'bybookishbabe-shopify-port'),
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'hero_heading',
		array(
			'default'           => 'smut meets sentiment',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'hero_heading',
		array(
			'label'   => __('Main heading', 'bybookishbabe-shopify-port'),
			'section' => 'bbb_hero',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'hero_mini_text',
		array(
			'default'           => 'for soft hearts with sinful taste.',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'hero_mini_text',
		array(
			'label'   => __('Mini line', 'bybookishbabe-shopify-port'),
			'section' => 'bbb_hero',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'hero_subtitle',
		array(
			'default'           => 'morally gray men delivered every sunday 🖤',
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control(
		'hero_subtitle',
		array(
			'label'   => __('Subtitle', 'bybookishbabe-shopify-port'),
			'section' => 'bbb_hero',
			'type'    => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'hero_primary_label',
		array(
			'default'           => 'explore library',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'hero_primary_label',
		array(
			'label'   => __('Primary button label', 'bybookishbabe-shopify-port'),
			'section' => 'bbb_hero',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'hero_primary_link',
		array(
			'default'           => '/library/',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		'hero_primary_link',
		array(
			'label'   => __('Primary button link', 'bybookishbabe-shopify-port'),
			'section' => 'bbb_hero',
			'type'    => 'url',
		)
	);

	$wp_customize->add_setting(
		'hero_secondary_label',
		array(
			'default'           => 'join the society',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'hero_secondary_label',
		array(
			'label'   => __('Secondary button label', 'bybookishbabe-shopify-port'),
			'section' => 'bbb_hero',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'hero_secondary_link',
		array(
			'default'           => 'https://thesmutandsentimentsociety.substack.com/subscribe',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		'hero_secondary_link',
		array(
			'label'   => __('Secondary button link', 'bybookishbabe-shopify-port'),
			'section' => 'bbb_hero',
			'type'    => 'url',
		)
	);
}
add_action('customize_register', 'bbb_hero_customizer_settings');
