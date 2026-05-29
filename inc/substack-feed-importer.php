<?php
/**
 * Imports the latest Substack issue into the Newsletter Issue CPT.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_substack_feed_url(): string {
	$url = (string) get_option('bbb_substack_feed_url', 'https://thesmutandsentimentsociety.substack.com/feed');
	$url = trim($url);

	return (string) apply_filters('bbb_substack_feed_url', '' !== $url ? $url : 'https://thesmutandsentimentsociety.substack.com/feed');
}

function bbb_substack_subscribe_url(): string {
	return (string) apply_filters('bbb_substack_subscribe_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
}

function bbb_substack_issue_slug_from_url(string $url, string $title): string {
	$path = (string) wp_parse_url($url, PHP_URL_PATH);
	$slug = $path ? basename(untrailingslashit($path)) : '';
	$slug = sanitize_title($slug ?: $title);

	return '' !== $slug ? $slug : 'latest-society-dispatch';
}

function bbb_substack_issue_first_image(string $html): string {
	if (!preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
		return '';
	}

	return esc_url_raw((string) html_entity_decode($matches[1], ENT_QUOTES));
}

function bbb_substack_issue_pull_quote(string $html): string {
	if (preg_match('/<blockquote[^>]*>(.*?)<\/blockquote>/is', $html, $matches)) {
		return trim(wp_strip_all_tags((string) $matches[1]));
	}

	return '';
}

function bbb_substack_issue_subtitle(string $html, string $description): string {
	$candidates = array();
	if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $matches)) {
		$candidates = $matches[1];
	}
	$candidates[] = $description;

	foreach ($candidates as $candidate) {
		$text = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $candidate)) ?: '');
		if (strlen($text) >= 24) {
			return wp_trim_words($text, 24, '');
		}
	}

	return '';
}

function bbb_substack_find_issue_by_url(string $url): ?WP_Post {
	if ('' === $url || !post_type_exists('newsletter_issue')) {
		return null;
	}

	$matches = get_posts(
		array(
			'post_type'      => 'newsletter_issue',
			'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
			'posts_per_page' => 1,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'   => '_bbb_newsletter_url',
					'value' => $url,
				),
				array(
					'key'   => 'issue_url',
					'value' => $url,
				),
			),
		)
	);

	return !empty($matches[0]) && $matches[0] instanceof WP_Post ? $matches[0] : null;
}

function bbb_substack_find_book_for_issue_url(string $url): ?WP_Post {
	if ('' === $url) {
		return null;
	}

	$post_types = array_values(
		array_filter(
			array('bbb_book', 'sss_book'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);
	if (!$post_types) {
		return null;
	}

	$books = get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
			'posts_per_page' => 1,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'   => '_bbb_newsletter_url',
					'value' => $url,
				),
				array(
					'key'   => 'newsletter_url',
					'value' => $url,
				),
			),
		)
	);

	return !empty($books[0]) && $books[0] instanceof WP_Post ? $books[0] : null;
}

function bbb_substack_feed_cache_lifetime(int $seconds): int {
	return 15 * MINUTE_IN_SECONDS;
}

function bbb_substack_item_local_date_parts(int $timestamp): array {
	if ($timestamp <= 0) {
		$timestamp = time();
	}

	try {
		$date = (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone('America/Los_Angeles'));
	} catch (Exception $e) {
		return array(wp_date('Y-m-d', $timestamp), (int) wp_date('N', $timestamp));
	}

	return array($date->format('Y-m-d'), (int) $date->format('N'));
}

function bbb_substack_import_feed_item($item) {
	$title = sanitize_text_field((string) $item->get_title());
	$url = esc_url_raw((string) $item->get_permalink());
	$content = (string) $item->get_content();
	$description = (string) $item->get_description();
	$date_ts = (int) $item->get_date('U');
	[$publish_date, $publish_weekday] = bbb_substack_item_local_date_parts($date_ts);
	$slug = bbb_substack_issue_slug_from_url($url, $title);
	$existing = bbb_substack_find_issue_by_url($url);
	if (!$existing instanceof WP_Post) {
		$existing = get_page_by_path($slug, OBJECT, 'newsletter_issue');
	}

	$native_content = wp_kses_post($content ?: $description);
	$excerpt = wp_trim_words(wp_strip_all_tags($description ?: $native_content), 44, '');
	$subtitle = bbb_substack_issue_subtitle($native_content, $description);
	$pull_quote = bbb_substack_issue_pull_quote($native_content);
	$preview_url = '';
	$enclosure = $item->get_enclosure();
	if ($enclosure && $enclosure->get_link()) {
		$preview_url = esc_url_raw((string) $enclosure->get_link());
	}
	if ('' === $preview_url) {
		$preview_url = bbb_substack_issue_first_image($native_content);
	}

	$postarr = array(
		'post_type'    => 'newsletter_issue',
		'post_status'  => 'publish',
		'post_title'   => '' !== $title ? $title : 'latest society dispatch',
		'post_name'    => $slug,
		'post_content' => $native_content,
		'post_excerpt' => $excerpt,
	);
	if ($date_ts > 0) {
		$postarr['post_date'] = get_date_from_gmt(gmdate('Y-m-d H:i:s', $date_ts));
		$postarr['post_date_gmt'] = gmdate('Y-m-d H:i:s', $date_ts);
	}
	if ($existing instanceof WP_Post) {
		$postarr['ID'] = $existing->ID;
		$post_id = wp_update_post($postarr, true);
	} else {
		$post_id = wp_insert_post($postarr, true);
	}

	if (is_wp_error($post_id)) {
		return $post_id;
	}

	$post_id = (int) $post_id;
	foreach (array('_issue_publish_date', 'publish_date') as $meta_key) {
		update_post_meta($post_id, $meta_key, $publish_date);
	}
	foreach (array('_bbb_newsletter_url', 'issue_url') as $meta_key) {
		update_post_meta($post_id, $meta_key, $url);
	}
	update_post_meta($post_id, '_issue_publish_weekday', (string) $publish_weekday);
	update_post_meta($post_id, '_issue_subtitle', $subtitle);
	update_post_meta($post_id, '_issue_excerpt', $excerpt);
	update_post_meta($post_id, '_issue_import_source', 'substack_rss');
	update_post_meta($post_id, '_issue_imported_at', current_time('mysql'));
	if ('' !== $pull_quote) {
		update_post_meta($post_id, '_issue_pull_quote', $pull_quote);
	}
	if ('' !== $preview_url) {
		update_post_meta($post_id, '_issue_preview_url', $preview_url);
		update_post_meta($post_id, '_issue_preview_alt', $title);
	}

	$book = bbb_substack_find_book_for_issue_url($url);
	if ($book instanceof WP_Post) {
		update_post_meta($post_id, '_issue_book_id', $book->ID);
		update_post_meta($post_id, '_issue_library_book_id', $book->ID);
		update_post_meta($post_id, '_issue_book_handle', $book->post_name);
	}

	return get_post($post_id);
}

function bbb_substack_import_latest_issue(bool $force = false) {
	if (!post_type_exists('newsletter_issue')) {
		return new WP_Error('bbb_substack_no_cpt', 'Newsletter Issue post type is not registered.');
	}

	if (!$force && get_transient('bbb_substack_latest_import_lock')) {
		return null;
	}
	set_transient('bbb_substack_latest_import_lock', '1', 5 * MINUTE_IN_SECONDS);

	require_once ABSPATH . WPINC . '/feed.php';

	add_filter('wp_feed_cache_transient_lifetime', 'bbb_substack_feed_cache_lifetime');
	$feed = fetch_feed(bbb_substack_feed_url());
	remove_filter('wp_feed_cache_transient_lifetime', 'bbb_substack_feed_cache_lifetime');
	if (is_wp_error($feed)) {
		delete_transient('bbb_substack_latest_import_lock');
		update_option('bbb_substack_last_import_error', $feed->get_error_message(), false);
		return $feed;
	}

	$items = $feed->get_items(0, 10);
	if (!$items) {
		delete_transient('bbb_substack_latest_import_lock');
		return null;
	}

	$latest = null;
	foreach ($items as $item) {
		$imported = bbb_substack_import_feed_item($item);
		if (is_wp_error($imported)) {
			delete_transient('bbb_substack_latest_import_lock');
			update_option('bbb_substack_last_import_error', $imported->get_error_message(), false);
			return $imported;
		}
		if (!$latest instanceof WP_Post && $imported instanceof WP_Post) {
			$latest = $imported;
		}
	}

	delete_option('bbb_substack_last_import_error');
	if ($latest instanceof WP_Post) {
		update_option('bbb_substack_last_imported_issue_id', $latest->ID, false);
	}
	delete_transient('bbb_substack_latest_import_lock');

	return $latest;
}

function bbb_substack_schedule_import(): void {
	if (!wp_next_scheduled('bbb_substack_import_latest_issue')) {
		wp_schedule_event(time() + 15 * MINUTE_IN_SECONDS, 'hourly', 'bbb_substack_import_latest_issue');
	}
}
add_action('init', 'bbb_substack_schedule_import');
add_action('bbb_substack_import_latest_issue', 'bbb_substack_import_latest_issue');

function bbb_substack_import_on_admin_request(): void {
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to import newsletter issues.', 'bybookishbabe-shopify-port'));
	}
	check_admin_referer('bbb_substack_import_latest');

	$result = bbb_substack_import_latest_issue(true);
	$args = array('post_type' => 'newsletter_issue');
	if ($result instanceof WP_Post) {
		$args['bbb_substack_imported'] = '1';
	} elseif (is_wp_error($result)) {
		$args['bbb_substack_error'] = rawurlencode($result->get_error_message());
	} else {
		$args['bbb_substack_imported'] = '0';
	}

	wp_safe_redirect(add_query_arg($args, admin_url('edit.php')));
	exit;
}
add_action('admin_post_bbb_substack_import_latest', 'bbb_substack_import_on_admin_request');

function bbb_substack_newsletter_admin_notice(): void {
	$screen = get_current_screen();
	if (!$screen || 'edit-newsletter_issue' !== $screen->id) {
		return;
	}

	$import_url = wp_nonce_url(admin_url('admin-post.php?action=bbb_substack_import_latest'), 'bbb_substack_import_latest');
	$error = isset($_GET['bbb_substack_error']) ? sanitize_text_field(wp_unslash($_GET['bbb_substack_error'])) : '';
	?>
	<div class="notice notice-info">
		<p>
			<?php esc_html_e('Substack RSS sync imports the latest issue into this shelf.', 'bybookishbabe-shopify-port'); ?>
			<a class="button button-secondary" href="<?php echo esc_url($import_url); ?>"><?php esc_html_e('Import latest from Substack', 'bybookishbabe-shopify-port'); ?></a>
		</p>
		<?php if ('' !== $error) : ?>
			<p><strong><?php esc_html_e('Last import error:', 'bybookishbabe-shopify-port'); ?></strong> <?php echo esc_html($error); ?></p>
		<?php endif; ?>
	</div>
	<?php
}
add_action('admin_notices', 'bbb_substack_newsletter_admin_notice');
