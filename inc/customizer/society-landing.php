<?php
/**
 * Customizer settings for the Society landing monthly hub.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_society_landing_customizer_settings(WP_Customize_Manager $wp_customize): void {
	$wp_customize->add_section(
		'bbb_society_landing',
		array(
			'title'    => __('Society Landing', 'bybookishbabe-shopify-port'),
			'priority' => 31,
		)
	);

	$text_settings = array(
		'bbb_society_month_kicker' => array(
			'label'   => __('Monthly hub kicker', 'bybookishbabe-shopify-port'),
			'default' => 'monthly theme',
			'type'    => 'text',
		),
		'bbb_society_month_title' => array(
			'label'   => __('Monthly hub title', 'bybookishbabe-shopify-port'),
			'default' => 'burn for me',
			'type'    => 'text',
		),
		'bbb_society_month_text' => array(
			'label'   => __('Monthly hub text', 'bybookishbabe-shopify-port'),
			'default' => 'dark romance month with mafia, obsession, enemies to lovers, and the member tools that keep the whole reading life in one place.',
			'type'    => 'textarea',
		),
	);

	foreach ($text_settings as $setting_id => $setting) {
		$wp_customize->add_setting(
			$setting_id,
			array(
				'default'           => $setting['default'],
				'sanitize_callback' => 'textarea' === $setting['type'] ? 'sanitize_textarea_field' : 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			$setting_id,
			array(
				'label'   => $setting['label'],
				'section' => 'bbb_society_landing',
				'type'    => $setting['type'],
			)
		);
	}

	$link_defaults = array(
		1 => array('label' => 'monthly theme', 'url' => '/monthly-theme/'),
		2 => array('label' => 'track your reads', 'url' => '/member-library/'),
		3 => array('label' => 'member dashboard', 'url' => '/member-dashboard/'),
		4 => array('label' => 'my bookshelf', 'url' => '/my-bookshelf/'),
		5 => array('label' => 'printables', 'url' => '/kindle-inserts/'),
		6 => array('label' => 'quote library', 'url' => '/quote-wall/'),
	);

	foreach ($link_defaults as $index => $defaults) {
		$wp_customize->add_setting(
			"bbb_society_month_link_{$index}_label",
			array(
				'default'           => $defaults['label'],
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			"bbb_society_month_link_{$index}_label",
			array(
				'label'   => sprintf(__('Hub link %d label', 'bybookishbabe-shopify-port'), $index),
				'section' => 'bbb_society_landing',
				'type'    => 'text',
			)
		);

		$wp_customize->add_setting(
			"bbb_society_month_link_{$index}_url",
			array(
				'default'           => $defaults['url'],
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			"bbb_society_month_link_{$index}_url",
			array(
				'label'   => sprintf(__('Hub link %d URL or path', 'bybookishbabe-shopify-port'), $index),
				'section' => 'bbb_society_landing',
				'type'    => 'text',
			)
		);
	}
}
add_action('customize_register', 'bbb_society_landing_customizer_settings');
