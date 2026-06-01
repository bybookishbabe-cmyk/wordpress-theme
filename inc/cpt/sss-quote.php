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
					'name'               => __('Quotes', 'bybookishbabe-shopify-port'),
					'singular_name'      => __('Quote', 'bybookishbabe-shopify-port'),
					'add_new'            => __('Add New', 'bybookishbabe-shopify-port'),
					'add_new_item'       => __('Add New Quote', 'bybookishbabe-shopify-port'),
					'edit_item'          => __('Edit Quote', 'bybookishbabe-shopify-port'),
					'new_item'           => __('New Quote', 'bybookishbabe-shopify-port'),
					'view_item'          => __('View Quote', 'bybookishbabe-shopify-port'),
					'search_items'       => __('Search Quotes', 'bybookishbabe-shopify-port'),
					'not_found'          => __('No quotes found.', 'bybookishbabe-shopify-port'),
					'not_found_in_trash' => __('No quotes found in Trash.', 'bybookishbabe-shopify-port'),
					'all_items'          => __('All Quotes', 'bybookishbabe-shopify-port'),
					'menu_name'          => __('Quotes', 'bybookishbabe-shopify-port'),
					'name_admin_bar'     => __('Quote', 'bybookishbabe-shopify-port'),
				),
				'public'       => true,
				'show_in_menu' => false,
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
					'name'               => __('Quotes', 'bybookishbabe-shopify-port'),
					'singular_name'      => __('Quote', 'bybookishbabe-shopify-port'),
					'add_new'            => __('Add New', 'bybookishbabe-shopify-port'),
					'add_new_item'       => __('Add New Quote', 'bybookishbabe-shopify-port'),
					'edit_item'          => __('Edit Quote', 'bybookishbabe-shopify-port'),
					'new_item'           => __('New Quote', 'bybookishbabe-shopify-port'),
					'view_item'          => __('View Quote', 'bybookishbabe-shopify-port'),
					'search_items'       => __('Search Quotes', 'bybookishbabe-shopify-port'),
					'not_found'          => __('No quotes found.', 'bybookishbabe-shopify-port'),
					'not_found_in_trash' => __('No quotes found in Trash.', 'bybookishbabe-shopify-port'),
					'all_items'          => __('All Quotes', 'bybookishbabe-shopify-port'),
					'menu_name'          => __('Quotes', 'bybookishbabe-shopify-port'),
					'name_admin_bar'     => __('Quote', 'bybookishbabe-shopify-port'),
				),
				'public'       => true,
				'show_in_menu' => true,
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

if (!function_exists('bbb_quote_book_post_types')) {
	function bbb_quote_book_post_types(): array {
		return array_values(
			array_filter(
				array('bbb_book', 'sss_book'),
				static function (string $post_type): bool {
					return post_type_exists($post_type);
				}
			)
		);
	}
}

if (!function_exists('bbb_quote_admin_books')) {
	function bbb_quote_admin_books(): array {
		$post_types = bbb_quote_book_post_types();
		if (!$post_types) {
			return array();
		}

		return get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => array('publish', 'draft', 'pending', 'private'),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
	}
}

function bbb_add_quote_details_meta_box(): void {
	foreach (bbb_quote_post_types() as $post_type) {
		add_meta_box(
			'bbb_quote_details',
			__('Quote details', 'bybookishbabe-shopify-port'),
			'bbb_render_quote_details_meta_box',
			$post_type,
			'normal',
			'high'
		);
	}
}
add_action('add_meta_boxes', 'bbb_add_quote_details_meta_box');

function bbb_render_quote_details_meta_box(WP_Post $post): void {
	wp_nonce_field('bbb_save_quote_details', 'bbb_quote_details_nonce');

	$quote_text = trim((string) get_post_meta($post->ID, '_quote_text', true));
	if ('' === $quote_text) {
		$quote_text = trim((string) get_post_meta($post->ID, 'quote_text', true));
	}
	if ('' === $quote_text) {
		$quote_text = trim((string) get_post_meta($post->ID, 'quote', true));
	}
	if ('' === $quote_text) {
		$quote_text = trim((string) get_post_meta($post->ID, '_bbb_quote', true));
	}

	$selected_book_id = max(
		(int) get_post_meta($post->ID, '_quote_book_id', true),
		(int) get_post_meta($post->ID, '_quote_library_book_id', true),
		(int) get_post_meta($post->ID, 'book_id', true),
		(int) get_post_meta($post->ID, 'library_book_id', true)
	);
	$books = bbb_quote_admin_books();
	?>
	<style>
		.bbb-quote-fields { display: grid; gap: 14px; }
		.bbb-quote-fields__row { display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 12px; align-items: start; }
		.bbb-quote-fields__row label { font-weight: 600; padding-top: 7px; }
		.bbb-quote-fields textarea,
		.bbb-quote-fields select { width: 100%; }
		.bbb-quote-fields textarea { min-height: 110px; }
		.bbb-quote-fields__help { margin: 6px 0 0; color: #646970; font-size: 12px; }
	</style>
	<div class="bbb-quote-fields">
		<div class="bbb-quote-fields__row">
			<label for="bbb_quote_text"><?php esc_html_e('Quote text', 'bybookishbabe-shopify-port'); ?></label>
			<div>
				<textarea id="bbb_quote_text" name="bbb_quote_text"><?php echo esc_textarea($quote_text); ?></textarea>
				<p class="bbb-quote-fields__help"><?php esc_html_e('This is the line shown on the quote wall.', 'bybookishbabe-shopify-port'); ?></p>
			</div>
		</div>

		<div class="bbb-quote-fields__row">
			<label for="bbb_quote_book_id"><?php esc_html_e('Linked book', 'bybookishbabe-shopify-port'); ?></label>
			<div>
				<select id="bbb_quote_book_id" name="bbb_quote_book_id">
					<option value="0"><?php esc_html_e('No linked book', 'bybookishbabe-shopify-port'); ?></option>
					<?php foreach ($books as $book) : ?>
						<?php if ($book instanceof WP_Post) : ?>
							<option value="<?php echo esc_attr((string) $book->ID); ?>" <?php selected($selected_book_id, $book->ID); ?>>
								<?php echo esc_html(get_the_title($book) . ' (' . $book->post_type . ')'); ?>
							</option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<p class="bbb-quote-fields__help"><?php esc_html_e('When linked, the quote wall can open the book modal and search can match the book, author, shelf, and tropes.', 'bybookishbabe-shopify-port'); ?></p>
			</div>
		</div>
	</div>
	<?php
}

function bbb_save_quote_details(int $post_id): void {
	if (!isset($_POST['bbb_quote_details_nonce']) || !wp_verify_nonce((string) $_POST['bbb_quote_details_nonce'], 'bbb_save_quote_details')) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}
	if (!in_array(get_post_type($post_id), bbb_quote_post_types(), true)) {
		return;
	}

	$quote_text = isset($_POST['bbb_quote_text']) ? sanitize_textarea_field((string) wp_unslash($_POST['bbb_quote_text'])) : '';
	if ('' !== trim($quote_text)) {
		update_post_meta($post_id, '_quote_text', $quote_text);
		update_post_meta($post_id, 'quote_text', $quote_text);
	} else {
		delete_post_meta($post_id, '_quote_text');
		delete_post_meta($post_id, 'quote_text');
	}

	$book_id = isset($_POST['bbb_quote_book_id']) ? (int) $_POST['bbb_quote_book_id'] : 0;
	if ($book_id > 0 && in_array(get_post_type($book_id), bbb_quote_book_post_types(), true)) {
		update_post_meta($post_id, '_quote_book_id', $book_id);
		update_post_meta($post_id, '_quote_library_book_id', $book_id);
		update_post_meta($post_id, 'book_id', $book_id);
		update_post_meta($post_id, 'library_book_id', $book_id);
		update_post_meta($post_id, '_quote_book_handle', get_post_field('post_name', $book_id));
		update_post_meta($post_id, '_quote_book_title', get_the_title($book_id));
	} else {
		foreach (array('_quote_book_id', '_quote_library_book_id', 'book_id', 'library_book_id', '_quote_book_handle', '_quote_book_title') as $key) {
			delete_post_meta($post_id, $key);
		}
	}
}
add_action('save_post', 'bbb_save_quote_details');

if (!function_exists('bbb_quote_wall_book')) {
	function bbb_quote_wall_book(WP_Post $quote): ?WP_Post {
		$book = function_exists('get_field') ? get_field('book', $quote->ID) : null;
		if ($book instanceof WP_Post) {
			return $book;
		}
		if (is_array($book) && isset($book['ID']) && is_numeric($book['ID'])) {
			$book_post = get_post((int) $book['ID']);
			if ($book_post instanceof WP_Post) {
				return $book_post;
			}
		}
		if (is_numeric($book)) {
			$book_post = get_post((int) $book);
			if ($book_post instanceof WP_Post) {
				return $book_post;
			}
		}

		$book_id = max(
			(int) get_post_meta($quote->ID, '_quote_book_id', true),
			(int) get_post_meta($quote->ID, '_quote_library_book_id', true),
			(int) get_post_meta($quote->ID, 'book_id', true),
			(int) get_post_meta($quote->ID, 'library_book_id', true)
		);
		if ($book_id > 0) {
			$book_post = get_post($book_id);
			if ($book_post instanceof WP_Post) {
				return $book_post;
			}
		}

		$handle = (string) get_post_meta($quote->ID, '_quote_book_handle', true);
		$handle = '' !== $handle ? $handle : (string) get_post_meta($quote->ID, 'book_handle', true);
		$handle = '' !== $handle ? $handle : (string) get_post_meta($quote->ID, '_bbb_book_handle', true);
		if ('' !== $handle) {
			foreach (bbb_quote_book_post_types() as $post_type) {
				$book_post = get_page_by_path($handle, OBJECT, $post_type);
				if ($book_post instanceof WP_Post) {
					return $book_post;
				}
			}
		}

		return null;
	}
}

if (!function_exists('bbb_quote_permalink_target')) {
	function bbb_quote_permalink_target(WP_Post $quote): string {
		$book = bbb_quote_wall_book($quote);
		if (!$book instanceof WP_Post || !in_array(get_post_type($book), bbb_quote_book_post_types(), true)) {
			return '';
		}

		$permalink = get_permalink($book);

		return $permalink ? (string) $permalink : '';
	}
}

add_action(
	'template_redirect',
	static function (): void {
		if (!is_singular(bbb_quote_post_types())) {
			return;
		}

		$quote = get_queried_object();
		if (!$quote instanceof WP_Post) {
			return;
		}

		$target = bbb_quote_permalink_target($quote);
		if ('' === $target) {
			return;
		}

		wp_safe_redirect($target, 301);
		exit;
	},
	-1000
);

add_filter(
	'rank_math/frontend/robots',
	static function (array $robots): array {
		if (!is_singular(bbb_quote_post_types())) {
			return $robots;
		}

		unset($robots['index'], $robots['follow']);
		$robots['noindex']  = 'noindex';
		$robots['nofollow'] = 'nofollow';

		return $robots;
	},
	999
);

add_filter(
	'wp_robots',
	static function (array $robots): array {
		if (!is_singular(bbb_quote_post_types())) {
			return $robots;
		}

		unset($robots['index'], $robots['follow']);
		$robots['noindex']  = true;
		$robots['nofollow'] = true;

		return $robots;
	},
	999
);

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
