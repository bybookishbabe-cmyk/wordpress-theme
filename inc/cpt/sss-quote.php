<?php
/**
 * SSS Quote custom post type.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'init',
	static function (): void {
		register_post_type(
			'sss_quote',
			array(
				'labels'       => array(
					'name'          => __('Quotes', 'bybookishbabe-shopify-port'),
					'singular_name' => __('Quote', 'bybookishbabe-shopify-port'),
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-format-quote',
				'supports'     => array('title', 'editor', 'custom-fields'),
				'has_archive'  => 'quotes',
				'rewrite'      => array('slug' => 'quotes'),
			)
		);

		register_post_type(
			'bbb_quote',
			array(
				'labels'       => array(
					'name'          => __('Quotes', 'bybookishbabe-shopify-port'),
					'singular_name' => __('Quote', 'bybookishbabe-shopify-port'),
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-format-quote',
				'supports'     => array('title', 'editor', 'custom-fields'),
				'has_archive'  => false,
				'rewrite'      => false,
			)
		);
	}
);

if (!function_exists('bbb_quote_post_types')) {
	function bbb_quote_post_types(): array {
		return array_values(
			array_filter(
				array('sss_quote', 'bbb_quote'),
				static function (string $post_type): bool {
					return post_type_exists($post_type);
				}
			)
		);
	}
}

if (!function_exists('bbb_quote_export_entries')) {
	function bbb_quote_export_entries(int $limit = -1): array {
		$path = get_theme_file_path('firstpass/migration/exports/metaobjects/sss_quote.json');
		if (!is_readable($path)) {
			return array();
		}

		$payload = json_decode((string) file_get_contents($path), true);
		if (!is_array($payload) || empty($payload['entries']) || !is_array($payload['entries'])) {
			return array();
		}

		$out = array();
		foreach ($payload['entries'] as $entry) {
			if (!is_array($entry)) {
				continue;
			}

			$fields = array();
			foreach ((array) ($entry['fields'] ?? array()) as $field) {
				if (is_array($field) && !empty($field['key'])) {
					$fields[(string) $field['key']] = $field;
				}
			}

			$quote = trim((string) ($fields['quote']['jsonValue'] ?? $fields['quote']['value'] ?? $entry['displayName'] ?? ''));
			if ('' === $quote) {
				continue;
			}

			$book = is_array($fields['library_book']['reference'] ?? null) ? $fields['library_book']['reference'] : array();
			$out[] = array(
				'text'        => $quote,
				'book_title'  => (string) ($book['displayName'] ?? ''),
				'book_handle' => (string) ($book['handle'] ?? ''),
			);

			if ($limit > 0 && count($out) >= $limit) {
				break;
			}
		}

		return $out;
	}
}
