<?php
/**
 * ACF options for the Society Hero / Newsletter CTA section.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'acf/init',
	static function (): void {
		if (!function_exists('acf_add_local_field_group')) {
			return;
		}

		if (function_exists('acf_add_options_sub_page')) {
			acf_add_options_sub_page(
				array(
					'page_title'  => __('Society Hero', 'bybookishbabe-shopify-port'),
					'menu_title'  => __('Society Hero', 'bybookishbabe-shopify-port'),
					'parent_slug' => 'themes.php',
					'menu_slug'   => 'society-hero-options',
					'capability'  => 'edit_theme_options',
					'post_id'     => 'option',
				)
			);
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_bbb_society_hero_options',
				'title'    => __('Society Hero Settings', 'bybookishbabe-shopify-port'),
				'fields'   => array(
					array(
						'key'           => 'field_bbb_sh_kicker',
						'label'         => __('Kicker', 'bybookishbabe-shopify-port'),
						'name'          => 'sh_kicker',
						'type'          => 'text',
						'default_value' => 'for the bookaholics who love romance',
					),
					array(
						'key'           => 'field_bbb_sh_title',
						'label'         => __('Title', 'bybookishbabe-shopify-port'),
						'name'          => 'sh_title',
						'type'          => 'text',
						'default_value' => 'the smut & sentiment society',
					),
					array(
						'key'           => 'field_bbb_sh_subtitle',
						'label'         => __('Subtitle', 'bybookishbabe-shopify-port'),
						'name'          => 'sh_subtitle',
						'type'          => 'text',
						'default_value' => "weekly letters, obsessive recs, and reader-core you pretend you're not addicted to.",
					),
					array(
						'key'           => 'field_bbb_sh_society_title',
						'label'         => __('Society box title', 'bybookishbabe-shopify-port'),
						'name'          => 'sh_society_title',
						'type'          => 'text',
						'default_value' => 'inside the society',
					),
					array(
						'key'           => 'field_bbb_sh_society_text',
						'label'         => __('Society box text', 'bybookishbabe-shopify-port'),
						'name'          => 'sh_society_text',
						'type'          => 'textarea',
						'rows'          => 3,
						'default_value' => 'the archive. reading lists. the fictional men problem. a tasteful amount of chaos.',
					),
					array(
						'key'           => 'field_bbb_sh_society_url',
						'label'         => __('Society page URL', 'bybookishbabe-shopify-port'),
						'name'          => 'sh_society_url',
						'type'          => 'url',
						'default_value' => '/pages/smut-sentiment-society',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => 'society-hero-options',
						),
					),
				),
			)
		);
	}
);
