<?php
/**
 * Media kit helpers.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_media_kit_slug(): string {
	return 'work-with-me';
}

function bbb_is_media_kit_route(): bool {
	return function_exists('bbb_current_route_slug') && bbb_media_kit_slug() === bbb_current_route_slug();
}

function bbb_media_kit_supabase_count(string $table, array $query = array()): ?int {
	if (!function_exists('bbb_reader_supabase_config')) {
		return null;
	}

	$config = bbb_reader_supabase_config();
	if (empty($config['url']) || empty($config['key'])) {
		return null;
	}

	$query = array_merge(array('select' => 'id', 'limit' => '1'), $query);
	$url   = rtrim((string) $config['url'], '/') . '/rest/v1/' . ltrim($table, '/') . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 12,
			'headers' => array(
				'apikey'        => (string) $config['key'],
				'Authorization' => 'Bearer ' . (string) $config['key'],
				'Accept'        => 'application/json',
				'Prefer'        => 'count=exact',
				'Range'         => '0-0',
			),
		)
	);

	if (is_wp_error($response)) {
		return null;
	}

	$content_range = (string) wp_remote_retrieve_header($response, 'content-range');
	if (preg_match('#/([0-9]+|\*)$#', $content_range, $matches) && '*' !== $matches[1]) {
		return max(0, (int) $matches[1]);
	}

	$body = json_decode((string) wp_remote_retrieve_body($response), true);
	return is_array($body) ? count($body) : null;
}

function bbb_media_kit_count_posts(array $post_types): int {
	$total = 0;
	foreach ($post_types as $post_type) {
		if (!post_type_exists((string) $post_type)) {
			continue;
		}

		$counts = wp_count_posts((string) $post_type);
		if (isset($counts->publish)) {
			$total += (int) $counts->publish;
		}
	}

	return $total;
}

function bbb_media_kit_manual_stats(): array {
	$defaults = array(
		'instagram_followers' => '',
		'tiktok_followers'    => '',
		'threads_followers'   => '',
		'avg_open_rate'       => '',
		'monthly_pageviews'   => '',
		'last_updated'        => '',
	);

	$saved = get_option('bbb_media_kit_manual_stats', array());
	return array_merge($defaults, is_array($saved) ? $saved : array());
}

function bbb_media_kit_stats(bool $force_refresh = false): array {
	$cache_key = 'bbb_media_kit_stats_v1';
	if (!$force_refresh) {
		$cached = get_transient($cache_key);
		if (is_array($cached)) {
			return $cached;
		}
	}

	$manual = bbb_media_kit_manual_stats();
	$stats  = array(
		'total_subscribers' => bbb_media_kit_supabase_count('bookshelf_subscribers'),
		'society_members'   => bbb_media_kit_supabase_count('bookshelf_subscribers', array('access_tier' => 'eq.society')),
		'saved_books'       => bbb_media_kit_supabase_count('bookshelf_saved_books', array('is_active' => 'eq.true')),
		'read_marks'        => bbb_media_kit_supabase_count('bookshelf_book_statuses', array('status' => 'eq.read')),
		'library_books'     => bbb_media_kit_count_posts(array('bbb_book', 'sss_book')),
		'newsletter_issues' => bbb_media_kit_count_posts(array('newsletter_issue')),
		'manual'            => $manual,
		'generated_at'      => current_time('timestamp'),
	);

	set_transient($cache_key, $stats, 6 * HOUR_IN_SECONDS);
	return $stats;
}

function bbb_media_kit_number($value, string $fallback = 'updating'): string {
	if (is_string($value) && '' !== trim($value)) {
		return trim($value);
	}

	if (!is_numeric($value)) {
		return $fallback;
	}

	$number = (int) $value;
	if ($number >= 1000000) {
		return number_format_i18n($number / 1000000, 1) . 'm';
	}

	if ($number >= 1000) {
		return number_format_i18n($number / 1000, $number >= 10000 ? 0 : 1) . 'k';
	}

	return number_format_i18n($number);
}

function bbb_media_kit_updated_label(array $stats): string {
	$manual = $stats['manual'] ?? array();
	if (is_array($manual) && '' !== trim((string) ($manual['last_updated'] ?? ''))) {
		return trim((string) $manual['last_updated']);
	}

	$timestamp = (int) ($stats['generated_at'] ?? time());
	return 'live refresh ' . wp_date('M j, Y', $timestamp);
}

function bbb_media_kit_admin_menu(): void {
	add_options_page(
		'Media Kit',
		'Media Kit',
		'manage_options',
		'bbb-media-kit',
		'bbb_media_kit_render_admin_page'
	);
}
add_action('admin_menu', 'bbb_media_kit_admin_menu');

function bbb_media_kit_render_admin_page(): void {
	if (!current_user_can('manage_options')) {
		return;
	}

	$manual = bbb_media_kit_manual_stats();
	$fields = array(
		'instagram_followers' => 'Instagram followers',
		'tiktok_followers'    => 'TikTok followers',
		'threads_followers'   => 'Threads followers',
		'avg_open_rate'       => 'Average open rate',
		'monthly_pageviews'   => 'Monthly pageviews',
		'last_updated'        => 'Public updated label',
	);
	?>
	<div class="wrap">
		<h1>Media Kit</h1>
		<p>Live site metrics refresh automatically. Use these fields for partner-facing stats that need manual or external-platform updates.</p>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<input type="hidden" name="action" value="bbb_save_media_kit">
			<?php wp_nonce_field('bbb_save_media_kit'); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<?php foreach ($fields as $key => $label) : ?>
						<tr>
							<th scope="row"><label for="bbb-media-kit-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
							<td>
								<input
									class="regular-text"
									type="text"
									id="bbb-media-kit-<?php echo esc_attr($key); ?>"
									name="bbb_media_kit_manual_stats[<?php echo esc_attr($key); ?>]"
									value="<?php echo esc_attr((string) ($manual[$key] ?? '')); ?>"
								>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button('Save media kit stats'); ?>
		</form>
	</div>
	<?php
}

function bbb_media_kit_save_admin_page(): void {
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to update the media kit.', 'bybookishbabe-shopify-port'));
	}

	check_admin_referer('bbb_save_media_kit');

	$raw     = isset($_POST['bbb_media_kit_manual_stats']) && is_array($_POST['bbb_media_kit_manual_stats'])
		? wp_unslash($_POST['bbb_media_kit_manual_stats'])
		: array();
	$allowed = array_keys(bbb_media_kit_manual_stats());
	$clean   = array();

	foreach ($allowed as $key) {
		$clean[$key] = sanitize_text_field((string) ($raw[$key] ?? ''));
	}

	update_option('bbb_media_kit_manual_stats', $clean, false);
	delete_transient('bbb_media_kit_stats_v1');

	wp_safe_redirect(add_query_arg(array('page' => 'bbb-media-kit', 'updated' => 'true'), admin_url('options-general.php')));
	exit;
}
add_action('admin_post_bbb_save_media_kit', 'bbb_media_kit_save_admin_page');

add_filter(
	'wp_robots',
	static function (array $robots): array {
		if (!bbb_is_media_kit_route()) {
			return $robots;
		}

		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset($robots['index'], $robots['follow']);

		return $robots;
	},
	20
);

add_filter(
	'rank_math/frontend/title',
	static function (string $title): string {
		return bbb_is_media_kit_route() ? 'bybookishbabe media kit' : $title;
	},
	100
);

add_filter(
	'rank_math/frontend/description',
	static function (string $description): string {
		return bbb_is_media_kit_route() ? 'a private, shareable media kit for bybookishbabe partnership inquiries.' : $description;
	},
	100
);

add_filter(
	'rank_math/opengraph/facebook/title',
	static function (string $title): string {
		return bbb_is_media_kit_route() ? 'bybookishbabe media kit' : $title;
	},
	100
);

add_filter(
	'rank_math/opengraph/facebook/description',
	static function (string $description): string {
		return bbb_is_media_kit_route() ? 'a private, shareable media kit for bybookishbabe partnership inquiries.' : $description;
	},
	100
);

add_filter(
	'rank_math/opengraph/twitter/title',
	static function (string $title): string {
		return bbb_is_media_kit_route() ? 'bybookishbabe media kit' : $title;
	},
	100
);

add_filter(
	'rank_math/opengraph/twitter/description',
	static function (string $description): string {
		return bbb_is_media_kit_route() ? 'a private, shareable media kit for bybookishbabe partnership inquiries.' : $description;
	},
	100
);

add_filter(
	'rank_math/json_ld',
	static function (array $data): array {
		if (!bbb_is_media_kit_route()) {
			return $data;
		}

		foreach ($data as &$entity) {
			if (!is_array($entity) || empty($entity['@type'])) {
				continue;
			}

			$types = (array) $entity['@type'];
			if (array_intersect($types, array('WebPage', 'Article'))) {
				$entity['name']        = 'ByBookishBabe Media Kit';
				$entity['description'] = 'A private, shareable media kit for ByBookishBabe partnership inquiries.';
				$entity['url']         = home_url('/work-with-me/');
			}
		}
		unset($entity);

		return $data;
	},
	100
);

add_action(
	'send_headers',
	static function (): void {
		if (bbb_is_media_kit_route()) {
			header('X-Robots-Tag: noindex, nofollow', true);
		}
	}
);
