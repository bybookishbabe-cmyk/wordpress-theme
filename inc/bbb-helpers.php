<?php
/**
 * Shared helpers for the Shopify to WordPress conversion.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_get_field(string $key, $post_id = null, $default = null) {
	$post_id = null === $post_id ? get_the_ID() : (int) $post_id;
	$is_url_field = str_contains($key, 'url') || str_contains($key, 'link');

	if ('bbb_book' === get_post_type($post_id)) {
		if ('cover' === $key && function_exists('bbb_get_book_cover_url')) {
			$cover_url = bbb_get_book_cover_url($post_id);
			if ('' !== $cover_url) {
				return $cover_url;
			}
		}

		$bbb_map = array(
			'author'                      => '_bbb_author',
			'cover'                       => '_bbb_cover_url',
			'amazon_link'                 => '_bbb_amazon_url',
			'audible_link'                => '_bbb_audible_url',
			'bookshop_link'               => '_bbb_bookshop_url',
			'libby_link'                  => '_bbb_libby_url',
			'kindle_unlimited_link'       => '_bbb_ku_url',
			'ku_link'                     => '_bbb_ku_url',
			'spice_level'                 => '_bbb_spice',
			'book_spice_level'            => '_bbb_spice',
			'starter_pack'                => '_bbb_starter_pack',
			'top_shelf'                   => '_bbb_top_shelf',
			'hide_from_library'           => '_bbb_hide_from_library',
			'is_private'                  => '_bbb_private_shelf',
			'read_as_standalone'          => '_bbb_standalone',
			'standalone'                  => '_bbb_standalone',
			'on_kindle_unlimited'         => '_bbb_ku',
			'why_i_loved_it'              => '_bbb_why',
			'mini_note'                   => '_bbb_mini_note',
			'featured_in_newsletter_date' => '_bbb_newsletter_date',
		);

		if (isset($bbb_map[$key])) {
			$value = get_post_meta($post_id, $bbb_map[$key], true);
			if ('' !== $value && null !== $value) {
				return $is_url_field && function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($value) : $value;
			}
		}
	}

	if (function_exists('get_field')) {
		$value = get_field($key, $post_id);
		if (null !== $value && '' !== $value && false !== $value) {
			return $is_url_field && function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($value) : $value;
		}
	}

	$raw = get_post_meta($post_id, $key, true);
	if ('' !== $raw) {
		return $is_url_field && function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($raw) : $raw;
	}

	$legacy = get_post_meta($post_id, '_' . $key, true);
	if ('' !== $legacy) {
		return $is_url_field && function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($legacy) : $legacy;
	}

	return $default;
}

function bbb_normalize_url_value($value): string {
	if (is_array($value)) {
		foreach (array('url', 'href', 'link') as $key) {
			if (!empty($value[$key]) && is_scalar($value[$key])) {
				return trim((string) $value[$key]);
			}
		}

		return '';
	}

	if (!is_scalar($value)) {
		return '';
	}

	$url = trim((string) $value);
	if ('' === $url) {
		return '';
	}

	$decoded = json_decode($url, true);
	if (is_array($decoded)) {
		return bbb_normalize_url_value($decoded);
	}

	if (
		(strlen($url) >= 2)
		&& (('"' === $url[0] && '"' === substr($url, -1)) || ("'" === $url[0] && "'" === substr($url, -1)))
	) {
		$url = substr($url, 1, -1);
	}

	if (preg_match('/url:\s*(https?:\/\/.+)$/i', $url, $matches)) {
		return trim((string) $matches[1]);
	}

	return trim($url);
}

function bbb_is_sss_member(): bool {
	if (!is_user_logged_in()) {
		return false;
	}

	$user = wp_get_current_user();

	return in_array('sss_member', (array) $user->roles, true)
		|| in_array('society', (array) $user->roles, true)
		|| in_array('paid', (array) $user->roles, true)
		|| in_array('society_member', (array) $user->roles, true)
		|| (function_exists('bbb_user_is_society') && bbb_user_is_society(get_current_user_id()))
		|| '1' === get_user_meta(get_current_user_id(), 'bbb_society_member', true)
		|| '1' === get_user_meta(get_current_user_id(), '_bbb_society_member_active', true)
		|| (function_exists('wc_memberships_is_user_active_member')
			&& wc_memberships_is_user_active_member(get_current_user_id(), 'smut-sentiment-society'));
}

function bbb_resolve_page_url(string $slug): string {
	$page = get_page_by_path($slug);

	return $page ? get_permalink($page) : home_url('/' . trim($slug, '/') . '/');
}

function bbb_require_sss_member(): void {
	if (bbb_is_sss_member()) {
		return;
	}

	wp_safe_redirect(bbb_resolve_page_url('join'));
	exit;
}

function bbb_book_cover_value_to_url($value, string $size = 'large'): string {
	if (is_array($value)) {
		foreach (array('url', 'src') as $key) {
			if (!empty($value[$key]) && is_scalar($value[$key])) {
				return trim((string) $value[$key]);
			}
		}

		foreach (array('ID', 'id') as $key) {
			if (!empty($value[$key])) {
				$url = wp_get_attachment_image_url((int) $value[$key], $size);
				if ($url) {
					return (string) $url;
				}
			}
		}

		return '';
	}

	if (is_numeric($value)) {
		$url = wp_get_attachment_image_url((int) $value, $size);
		if ($url) {
			return (string) $url;
		}
	}

	return is_scalar($value) ? trim((string) $value) : '';
}

function bbb_is_site_logo_url(string $url): bool {
	$url = trim($url);
	if ('' === $url) {
		return false;
	}

	$logo_urls = array();
	if (function_exists('bbb_logo_url')) {
		$logo_urls[] = bbb_logo_url();
	}

	$custom_logo_id = (int) get_theme_mod('custom_logo');
	if ($custom_logo_id) {
		$logo_urls[] = (string) wp_get_attachment_image_url($custom_logo_id, 'full');
	}

	foreach (array_filter($logo_urls) as $logo_url) {
		if (untrailingslashit($url) === untrailingslashit((string) $logo_url)) {
			return true;
		}
	}

	$path = strtolower((string) wp_parse_url($url, PHP_URL_PATH));

	return str_contains($path, '/bybookishbabe') && preg_match('/\.(?:png|jpe?g|webp|gif|svg)$/', $path);
}

function bbb_get_book_cover_url(int $post_id, string $size = 'large'): string {
	$candidates = array(
		get_post_meta($post_id, '_bbb_cover_attachment_id', true),
		get_post_meta($post_id, '_thumbnail_id', true),
		get_post_meta($post_id, '_bbb_cover_url', true),
		get_post_meta($post_id, 'sss_cover_url', true),
		get_post_meta($post_id, 'cover', true),
		get_post_meta($post_id, '_thumbnail_external_url', true),
	);

	if (function_exists('get_field')) {
		$candidates[] = get_field('cover', $post_id);
		$candidates[] = get_field('sss_cover_url', $post_id);
	}

	foreach ($candidates as $candidate) {
		$url = bbb_book_cover_value_to_url($candidate, $size);
		if ('' !== $url && !bbb_is_site_logo_url($url)) {
			return $url;
		}
	}

	$thumbnail = (string) (get_the_post_thumbnail_url($post_id, $size) ?: '');

	return bbb_is_site_logo_url($thumbnail) ? '' : $thumbnail;
}

function bbb_get_book_author(int $post_id): string {
	if ('bbb_book' === get_post_type($post_id)) {
		return bbb_bookish_proper_name((string) get_post_meta($post_id, '_bbb_author', true));
	}

	return bbb_bookish_proper_name((string) bbb_get_field('author', $post_id, ''));
}

function bbb_get_book_shelf_name(int $post_id): string {
	foreach (array('bbb_shelf', 'sss_shelf') as $taxonomy) {
		$terms = get_the_terms($post_id, $taxonomy);

		if ($terms && !is_wp_error($terms)) {
			return bbb_bookish_proper_name((string) $terms[0]->name);
		}
	}

	foreach (array('_bbb_shelf_name', 'sss_shelf', 'shelf') as $meta_key) {
		$shelf = trim((string) get_post_meta($post_id, $meta_key, true));

		if ('' !== $shelf) {
			return bbb_bookish_proper_name($shelf);
		}
	}

	return '';
}

function bbb_book_cover_alt(string $title, string $author = '', string $shelf = ''): string {
	$title  = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($title) : trim(wp_strip_all_tags($title));
	$author = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($author) : trim(wp_strip_all_tags($author));
	$shelf  = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($shelf) : trim(wp_strip_all_tags($shelf));

	if ('' === $title) {
		return 'book cover';
	}

	$alt = $title;

	if ('' !== $author) {
		$alt .= ' by ' . $author;
	}

	if ('' !== $shelf) {
		$alt .= ' – ' . $shelf;
	}

	return $alt . ' book cover';
}

function bbb_bookish_proper_name(string $value): string {
	$value = trim(wp_strip_all_tags($value));
	if ('' === $value) {
		return '';
	}

	if (preg_match('/^[A-Z0-9]{2,}(?:\.[A-Z0-9]+)*\.?$/', $value)) {
		return $value;
	}

	if (preg_match('/[A-Z]/', $value) && !preg_match('/^[A-Z\s[:punct:]\d]+$/', $value)) {
		return bbb_bookish_normalize_name_initials($value);
	}

	return bbb_bookish_normalize_name_initials(bbb_bookish_title_case($value, false));
}

function bbb_bookish_book_title(string $value): string {
	$value = trim(wp_strip_all_tags($value));
	if ('' === $value) {
		return '';
	}

	if (preg_match('/[A-Z]/', $value) && !preg_match('/^[A-Z\s[:punct:]\d]+$/', $value)) {
		return $value;
	}

	return bbb_bookish_title_case($value, true);
}

function bbb_bookish_title_case(string $value, bool $lower_small_words = true): string {
	$small_words = array('a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'from', 'in', 'nor', 'of', 'on', 'or', 'the', 'to', 'up', 'via', 'with');
	$parts       = preg_split('/(\s+)/u', $value, -1, PREG_SPLIT_DELIM_CAPTURE);
	if (!is_array($parts)) {
		return $value;
	}

	$word_indexes = array();
	foreach ($parts as $index => $part) {
		if ('' !== trim($part)) {
			$word_indexes[] = $index;
		}
	}

	$last_word_index = $word_indexes ? end($word_indexes) : -1;
	foreach ($parts as $index => $part) {
		if ('' === trim($part)) {
			continue;
		}

		$leading  = '';
		$trailing = '';
		$core     = $part;

		if (preg_match('/^([\'"“‘(\[{]*)(.*?)([\'"”’)\]},.!?:;]*)$/u', $part, $matches)) {
			$leading  = $matches[1];
			$core     = $matches[2];
			$trailing = $matches[3];
		}

		$core_lower = function_exists('mb_strtolower') ? mb_strtolower($core, 'UTF-8') : strtolower($core);
		if ($lower_small_words && $index !== $word_indexes[0] && $index !== $last_word_index && in_array($core_lower, $small_words, true)) {
			$core = $core_lower;
		} elseif ('.' === $trailing && preg_match('/^(?:[a-z]\.)+[a-z]$/i', $core)) {
			$core = strtoupper($core);
		} elseif (preg_match('/^(?:[a-z]\.)+$/i', $core)) {
			$core = strtoupper($core);
		} else {
			$core = preg_replace_callback(
				'/(^|[-’\'])([[:alpha:]])/u',
				static function (array $matches): string {
					$letter = function_exists('mb_strtoupper') ? mb_strtoupper($matches[2], 'UTF-8') : strtoupper($matches[2]);
					return $matches[1] . $letter;
				},
				$core_lower
			) ?: $core;

			$core = preg_replace_callback("/([’'])(D|Ll|M|Re|S|T|Ve)\b/u", static function (array $matches): string {
				return $matches[1] . strtolower($matches[2]);
			}, $core) ?: $core;
		}

		$parts[$index] = $leading . $core . $trailing;
	}

	return implode('', $parts);
}

function bbb_bookish_normalize_name_initials(string $value): string {
	$value = preg_replace_callback(
		'/\b([A-Z]{2,3}|[A-Z](?=\s+[A-Z][[:alpha:]]))\b/u',
		static function (array $matches): string {
			return implode('.', str_split($matches[1])) . '.';
		},
		$value
	) ?: $value;

	$known_initials = array(
		'Hd' => 'H.D.',
		'Hm' => 'H.M.',
		'Jd' => 'J.D.',
		'Jt' => 'J.T.',
		'Lj' => 'L.J.',
		'Sc' => 'S.C.',
	);

	return preg_replace_callback(
		'/\b(' . implode('|', array_keys($known_initials)) . ')(?=\s+[A-Z][[:alpha:]])/u',
		static function (array $matches) use ($known_initials): string {
			return $known_initials[$matches[1]] ?? $matches[1];
		},
		$value
	) ?: $value;
}

function bbb_bookish_public_book_title($title, $post_id = 0) {
	if (is_admin() || !is_scalar($title)) {
		return $title;
	}

	$post = $post_id ? get_post((int) $post_id) : null;
	if (!$post instanceof WP_Post || !in_array($post->post_type, array('bbb_book', 'sss_book'), true)) {
		return $title;
	}

	return bbb_bookish_book_title((string) $title);
}
add_filter('the_title', 'bbb_bookish_public_book_title', 20, 2);
add_filter('single_post_title', 'bbb_bookish_public_book_title', 20, 2);

function bbb_book_is_hidden(int $post_id): bool {
	if ('bbb_book' === get_post_type($post_id)) {
		return bbb_truthy(get_post_meta($post_id, '_bbb_hide_from_library', true))
			|| bbb_content_is_hidden_from_public_browsing($post_id);
	}

	return (bool) bbb_get_field('hide_from_library', $post_id, false)
		|| bbb_content_is_hidden_from_public_browsing($post_id);
}

function bbb_book_is_private(int $post_id): bool {
	if ('bbb_book' === get_post_type($post_id)) {
		return function_exists('bbb_is_book_private') ? bbb_is_book_private($post_id) : bbb_truthy(get_post_meta($post_id, '_bbb_private_shelf', true));
	}

	return (bool) bbb_get_field('is_private', $post_id, false);
}

function bbb_truthy($value): bool {
	if (is_bool($value)) {
		return $value;
	}

	if (is_numeric($value)) {
		return 1 === (int) $value;
	}

	$normalized = strtolower(trim((string) $value));

	return in_array($normalized, array('1', 'true', 'yes', 'on'), true);
}

function bbb_book_newsletter_is_unlocked(int $post_id): bool {
	$featured_date = 'bbb_book' === get_post_type($post_id)
		? (string) get_post_meta($post_id, '_bbb_newsletter_date', true)
		: (string) bbb_get_field('featured_in_newsletter_date', $post_id, '');
	if ('' === trim($featured_date)) {
		return true;
	}

	$date = substr(trim($featured_date), 0, 10);
	if (preg_match('/^\d{8}$/', $date)) {
		$date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
	}

	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		return true;
	}

	try {
		$timezone = new DateTimeZone('America/Los_Angeles');
		$unlock   = new DateTimeImmutable($date . ' 10:00:00', $timezone);
		$now      = new DateTimeImmutable('now', $timezone);

		return $unlock <= $now;
	} catch (Exception $exception) {
		return true;
	}
}

function bbb_content_reveal_date_is_unlocked(int $post_id): bool {
	$reveal_date = (string) get_post_meta($post_id, '_bbb_reveal_date', true);
	if ('' === trim($reveal_date)) {
		$reveal_date = (string) bbb_get_field('reveal_date', $post_id, '');
	}
	if ('' === trim($reveal_date)) {
		return true;
	}

	$date = substr(trim($reveal_date), 0, 10);
	if (preg_match('/^\d{8}$/', $date)) {
		$date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
	}

	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		return true;
	}

	try {
		$timezone = new DateTimeZone('America/Los_Angeles');
		$unlock   = new DateTimeImmutable($date . ' 10:00:00', $timezone);
		$now      = new DateTimeImmutable('now', $timezone);

		return $unlock <= $now;
	} catch (Exception $exception) {
		return true;
	}
}

function bbb_content_is_hidden_from_public_browsing(int $post_id): bool {
	if (apply_filters('bbb_show_all_imported_books', false, $post_id)) {
		return false;
	}

	if (bbb_truthy(get_post_meta($post_id, '_bbb_hidden_from_public_browsing', true))) {
		return true;
	}

	if ('bbb_book' === get_post_type($post_id) && bbb_truthy(get_post_meta($post_id, '_bbb_hide_from_library', true))) {
		return true;
	}

	return !bbb_content_reveal_date_is_unlocked($post_id);
}

function bbb_content_is_publicly_discoverable(int $post_id): bool {
	return 'publish' === get_post_status($post_id) && !bbb_content_is_hidden_from_public_browsing($post_id);
}

function bbb_book_is_publicly_visible(int $post_id): bool {
	if (apply_filters('bbb_show_all_imported_books', false, $post_id)) {
		return 'publish' === get_post_status($post_id);
	}

	return !bbb_content_is_hidden_from_public_browsing($post_id)
		&& !bbb_truthy(bbb_get_field('hide_from_library', $post_id, false))
		&& !bbb_truthy(bbb_get_field('is_private', $post_id, false))
		&& bbb_book_newsletter_is_unlocked($post_id);
}

function bbb_render_hidden_from_public_browsing_meta_box(WP_Post $post): void {
	wp_nonce_field('bbb_save_hidden_from_public_browsing', 'bbb_hidden_from_public_browsing_nonce');
	$is_hidden = bbb_truthy(get_post_meta($post->ID, '_bbb_hidden_from_public_browsing', true));
	$reveal_date = (string) get_post_meta($post->ID, '_bbb_reveal_date', true);
	?>
	<p>
		<label>
			<input type="checkbox" name="bbb_hidden_from_public_browsing" value="1" <?php checked($is_hidden); ?>>
			<?php esc_html_e('Hide from public browsing', 'bybookishbabe-shopify-port'); ?>
		</label>
	</p>
	<p class="description"><?php esc_html_e('The direct URL still works, but this page stays out of public grids, related sections, archives, search, and SEO indexing.', 'bybookishbabe-shopify-port'); ?></p>
	<p>
		<label for="bbb_reveal_date"><strong><?php esc_html_e('Reveal date', 'bybookishbabe-shopify-port'); ?></strong></label>
		<input id="bbb_reveal_date" name="bbb_reveal_date" type="date" value="<?php echo esc_attr(substr($reveal_date, 0, 10)); ?>">
	</p>
	<p class="description"><?php esc_html_e('Optional. If set, public browsing unlocks at 10:00 AM Pacific on this date.', 'bybookishbabe-shopify-port'); ?></p>
	<?php
}

function bbb_save_hidden_from_public_browsing_meta(int $post_id): void {
	if (!isset($_POST['bbb_hidden_from_public_browsing_nonce']) || !wp_verify_nonce((string) wp_unslash($_POST['bbb_hidden_from_public_browsing_nonce']), 'bbb_save_hidden_from_public_browsing')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	if (!empty($_POST['bbb_hidden_from_public_browsing'])) {
		update_post_meta($post_id, '_bbb_hidden_from_public_browsing', '1');
	} else {
		delete_post_meta($post_id, '_bbb_hidden_from_public_browsing');
	}

	$reveal_date = isset($_POST['bbb_reveal_date']) ? sanitize_text_field((string) wp_unslash($_POST['bbb_reveal_date'])) : '';
	if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $reveal_date)) {
		update_post_meta($post_id, '_bbb_reveal_date', $reveal_date);
	} else {
		delete_post_meta($post_id, '_bbb_reveal_date');
	}
}

function bbb_hidden_content_robots(array $robots): array {
	if (!is_singular(array('bbb_book', 'sss_series', 'bbb_boyfriend'))) {
		return $robots;
	}

	$post_id = (int) get_queried_object_id();
	if ($post_id > 0 && bbb_content_is_hidden_from_public_browsing($post_id)) {
		unset($robots['index'], $robots['follow']);
		$robots['noindex'] = 'noindex';
		$robots['nofollow'] = 'nofollow';
	}

	return $robots;
}
add_filter('rank_math/frontend/robots', 'bbb_hidden_content_robots', 100);

add_filter(
	'wp_robots',
	static function (array $robots): array {
		if (!is_singular(array('bbb_book', 'sss_series', 'bbb_boyfriend'))) {
			return $robots;
		}

		$post_id = (int) get_queried_object_id();
		if ($post_id > 0 && bbb_content_is_hidden_from_public_browsing($post_id)) {
			unset($robots['index'], $robots['follow']);
			$robots['noindex'] = true;
			$robots['nofollow'] = true;
		}

		return $robots;
	},
	100
);

function bbb_hidden_content_ids_for_query(array $post_types): array {
	static $cache = array();

	$post_types = array_values(array_intersect(array_unique($post_types), array('bbb_book', 'sss_series', 'bbb_boyfriend')));
	$post_types = array_values(array_filter($post_types, 'post_type_exists'));
	if (!$post_types) {
		return array();
	}

	$cache_key = implode(',', $post_types);
	if (isset($cache[$cache_key])) {
		return $cache[$cache_key];
	}

	$today = wp_date('Y-m-d', null, new DateTimeZone('America/Los_Angeles'));
	$meta_query = array(
		'relation' => 'OR',
		array(
			'key'     => '_bbb_hidden_from_public_browsing',
			'value'   => '1',
			'compare' => '=',
		),
		array(
			'key'     => '_bbb_reveal_date',
			'value'   => $today,
			'compare' => '>',
			'type'    => 'DATE',
		),
	);

	if (in_array('bbb_book', $post_types, true)) {
		$meta_query[] = array(
			'key'     => '_bbb_hide_from_library',
			'value'   => '1',
			'compare' => '=',
		);
	}

	$ids = get_posts(
		array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => true,
			'meta_query'             => $meta_query,
		)
	);

	$cache[$cache_key] = array_values(array_filter(array_map('absint', $ids)));
	return $cache[$cache_key];
}

add_action(
	'pre_get_posts',
	static function (WP_Query $query): void {
		if (is_admin() || !$query->is_main_query() || is_singular()) {
			return;
		}

		$post_type = $query->get('post_type');
		$post_types = is_array($post_type) ? $post_type : array($post_type ?: 'post');
		if ($query->is_search() || $query->is_post_type_archive() || $query->is_tax()) {
			if (in_array('any', $post_types, true) || $query->is_search()) {
				$post_types = array_merge($post_types, array('bbb_book', 'sss_series', 'bbb_boyfriend'));
			}
			$hidden_ids = bbb_hidden_content_ids_for_query(array_map('strval', $post_types));
			if (!$hidden_ids) {
				return;
			}

			$existing = array_filter(array_map('absint', (array) $query->get('post__not_in')));
			$query->set('post__not_in', array_values(array_unique(array_merge($existing, $hidden_ids))));
		}
	},
	20
);

function bbb_get_all_books_json(bool $include_private = true): array {
	$cache_version = function_exists('sss_library_cache_version') ? sss_library_cache_version() : (string) get_option('bbb_library_cache_version', '1');
	$cache_key     = 'bbb_books_json_' . md5($cache_version . '|' . ($include_private ? 'private' : 'public'));
	$cached        = get_transient($cache_key);

	if (is_array($cached)) {
		return $cached;
	}

	$post_types = array_values(
		array_filter(
			array('sss_book', 'bbb_book'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);

	$books = get_posts(
		array(
			'post_type'        => $post_types ?: 'sss_book',
			'numberposts'      => -1,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'suppress_filters' => false,
		)
	);

	$out = array();
	foreach ($books as $book) {
		if (bbb_book_is_hidden($book->ID)) {
			continue;
		}

		$is_private = bbb_book_is_private($book->ID);
		if (!$include_private && $is_private) {
			continue;
		}

		$is_bbb      = 'bbb_book' === $book->post_type;
		$shelf_terms = get_the_terms($book->ID, $is_bbb ? 'bbb_shelf' : 'sss_shelf');
		$trope_terms = get_the_terms($book->ID, $is_bbb ? 'bbb_trope' : 'sss_trope');

		$out[] = array(
			'id'           => $book->ID,
			'title'        => bbb_bookish_book_title((string) $book->post_title),
			'slug'         => $book->post_name,
			'author'       => bbb_get_book_author($book->ID),
			'cover_url'    => bbb_get_book_cover_url($book->ID),
			'shelf'        => $shelf_terms && !is_wp_error($shelf_terms) ? $shelf_terms[0]->slug : '',
			'shelf_name'   => $shelf_terms && !is_wp_error($shelf_terms) ? $shelf_terms[0]->name : '',
			'tropes'       => $trope_terms && !is_wp_error($trope_terms) ? wp_list_pluck($trope_terms, 'name') : array(),
			'spice_level'  => (int) bbb_get_field('spice_level', $book->ID, bbb_get_field('book_spice_level', $book->ID, 0)),
			'series_handle' => (string) bbb_get_field('series_handle', $book->ID, ''),
			'series_number' => (string) bbb_get_field('series_number', $book->ID, ''),
			'standalone'    => bbb_truthy(bbb_get_field('read_as_standalone', $book->ID, bbb_get_field('standalone', $book->ID, false))),
			'is_private'   => $is_private,
			'starter_pack' => function_exists('sss_book_is_starter_pack') ? sss_book_is_starter_pack($book->ID) : (bool) bbb_get_field('starter_pack', $book->ID, false),
			'top_shelf'    => function_exists('sss_book_is_top_shelf') ? sss_book_is_top_shelf($book->ID) : (bool) bbb_get_field('top_shelf', $book->ID, false),
			'on_ku'        => bbb_truthy(bbb_get_field('on_kindle_unlimited', $book->ID, false)),
			'why'          => (string) bbb_get_field('why_i_loved_it', $book->ID, ''),
			'mini'         => (string) bbb_get_field('mini_note', $book->ID, ''),
		);
	}

	set_transient($cache_key, $out, 30 * MINUTE_IN_SECONDS);

	return $out;
}

function bbb_get_public_books_query(array $args = array()): WP_Query {
	$post_types = array_values(
		array_filter(
			array('sss_book', 'bbb_book'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);

	$defaults = array(
		'post_type'      => $post_types ?: 'sss_book',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	return new WP_Query(array_replace_recursive($defaults, $args));
}

function bbb_render_section(string $name, array $args = array()): void {
	$file = get_theme_file_path('inc/sections/' . $name . '.php');
	if (file_exists($file)) {
		require $file;
	}
}

function bbb_render_component(string $name, array $args = array()): void {
	$file = get_theme_file_path('inc/components/' . $name . '.php');
	if (file_exists($file)) {
		require $file;
	}
}
