<?php
/**
 * SSS Trope taxonomy.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'init',
	static function (): void {
		register_taxonomy(
			'sss_trope',
			'sss_book',
			array(
				'label'        => 'Tropes',
				'hierarchical' => false,
				'show_in_rest' => true,
				'rewrite'      => array('slug' => 'trope'),
			)
		);

		foreach (array('_trope_bg', '_trope_text') as $meta_key) {
			register_term_meta(
				'sss_trope',
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_hex_color',
					'auth_callback'     => static function (): bool {
						return current_user_can('manage_categories');
					},
				)
			);
		}
	}
);

add_action('created_sss_trope', 'sss_populate_trope_color_meta', 10, 2);
add_action('edited_sss_trope', 'sss_populate_trope_color_meta', 10, 2);

function sss_populate_trope_color_meta(int $term_id, int $tt_id): void {
	unset($tt_id);

	$term = get_term($term_id, 'sss_trope');
	if (!$term || is_wp_error($term)) {
		return;
	}

	$colors = function_exists('sss_get_trope_colors') ? sss_get_trope_colors($term->slug) : array('#f3bfd5', '#4b112d');

	if (!get_term_meta($term_id, '_trope_bg', true)) {
		update_term_meta($term_id, '_trope_bg', $colors[0]);
	}

	if (!get_term_meta($term_id, '_trope_text', true)) {
		update_term_meta($term_id, '_trope_text', $colors[1]);
	}
}
