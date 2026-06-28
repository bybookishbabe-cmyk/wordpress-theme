<?php
/**
 * Blog poll shortcode and REST endpoints.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_blog_poll_normalize_key(string $value): string {
	$value = sanitize_title($value);
	return '' !== $value ? $value : 'option-' . wp_generate_password(6, false, false);
}

function bbb_blog_poll_clean_option_text(string $value): string {
	return trim($value, " \t\n\r\0\x0B\"'");
}

function bbb_blog_poll_label_from_key(string $value): string {
	$value = bbb_blog_poll_clean_option_text($value);
	if (function_exists('sss_article_book_from_name')) {
		$book = sss_article_book_from_name($value);
		if ($book instanceof WP_Post) {
			return get_the_title($book);
		}
	}

	return ucwords(str_replace(array('-', '_'), ' ', $value));
}

function bbb_blog_poll_parse_options(string $raw_options): array {
	$options = array();
	$separator = str_contains($raw_options, ';') ? '/\s*;\s*/' : '/\s*,\s*|\R+/';
	foreach (preg_split($separator, trim($raw_options)) ?: array() as $raw_option) {
		$raw_option = bbb_blog_poll_clean_option_text($raw_option);
		if ('' === $raw_option) {
			continue;
		}

		$parts = array_map('bbb_blog_poll_clean_option_text', explode('|', $raw_option));
		$key   = bbb_blog_poll_normalize_key((string) ($parts[0] ?? ''));
		$label = '' !== (string) ($parts[1] ?? '') ? (string) $parts[1] : bbb_blog_poll_label_from_key((string) ($parts[0] ?? ''));
		$note  = (string) ($parts[2] ?? '');
		$book  = (string) ($parts[3] ?? $parts[0] ?? '');

		if ('' === trim($label)) {
			continue;
		}

		$options[$key] = array(
			'key'   => $key,
			'label' => wp_strip_all_tags($label),
			'note'  => wp_strip_all_tags($note),
			'book'  => sanitize_title($book),
		);
	}

	return array_values($options);
}

function bbb_blog_poll_option_book(array $option): ?WP_Post {
	if (!function_exists('sss_article_book_from_name')) {
		return null;
	}

	$candidates = array_filter(
		array(
			(string) ($option['book'] ?? ''),
			(string) ($option['label'] ?? ''),
		)
	);

	foreach ($candidates as $candidate) {
		$book = sss_article_book_from_name($candidate);
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	return null;
}

function bbb_blog_poll_assets(): void {
	if (function_exists('bbb_enqueue_css')) {
		bbb_enqueue_css('bbb-blog-poll', 'assets/css/blog-poll.css', array('blog-system'));
	} else {
		wp_enqueue_style('bbb-blog-poll', get_theme_file_uri('assets/css/blog-poll.css'), array(), wp_get_theme()->get('Version'));
	}

	if (function_exists('bbb_enqueue_js')) {
		bbb_enqueue_js('bbb-blog-poll', 'assets/js/blog-poll.js', array('bbb-globals'));
	} else {
		wp_enqueue_script('bbb-blog-poll', get_theme_file_uri('assets/js/blog-poll.js'), array(), wp_get_theme()->get('Version'), true);
	}

	wp_localize_script(
		'bbb-blog-poll',
		'BBBBlogPollData',
		array(
			'endpoint' => set_url_scheme(rest_url('bbb/v1/blog-poll'), is_ssl() ? 'https' : 'http'),
			'nonce'    => wp_create_nonce('wp_rest'),
		)
	);
}

function bbb_blog_poll_shortcode($atts): string {
	$atts = shortcode_atts(
		array(
			'question' => '',
			'key'      => '',
			'poll'     => '',
			'options'  => '',
			'kicker'   => 'reader poll',
		),
		(array) $atts,
		'bbb_poll'
	);

	$question = trim(wp_strip_all_tags((string) $atts['question']));
	$kicker   = trim(wp_strip_all_tags((string) $atts['kicker']));
	$kicker   = preg_match('/^[📊🗳️]/u', $kicker) ? $kicker : '📊 ' . $kicker;
	$options  = bbb_blog_poll_parse_options((string) $atts['options']);
	if ('' === $question || count($options) < 2) {
		return '';
	}

	bbb_blog_poll_assets();

	$post_id  = get_the_ID() ?: 0;
	$poll_key = bbb_blog_poll_normalize_key((string) ($atts['key'] ?: $atts['poll'] ?: $question . '-' . $post_id));

	ob_start();
	?>
	<section
		class="bbb-blog-poll"
		data-bbb-blog-poll
		data-poll-key="<?php echo esc_attr($poll_key); ?>"
		data-post-id="<?php echo esc_attr((string) $post_id); ?>"
		data-question="<?php echo esc_attr($question); ?>"
		data-options="<?php echo esc_attr(wp_json_encode(wp_list_pluck($options, 'key')) ?: '[]'); ?>"
		data-option-config="<?php echo esc_attr(wp_json_encode($options) ?: '[]'); ?>"
		aria-labelledby="bbb-blog-poll-<?php echo esc_attr($poll_key); ?>"
	>
		<div class="bbb-blog-poll__head">
			<p class="bbb-blog-poll__kicker"><?php echo esc_html($kicker); ?></p>
			<p class="bbb-blog-poll__question" id="bbb-blog-poll-<?php echo esc_attr($poll_key); ?>"><?php echo esc_html($question); ?></p>
		</div>
		<div class="bbb-blog-poll__options" role="radiogroup" aria-label="<?php echo esc_attr($question); ?>">
			<?php foreach ($options as $option) : ?>
				<div class="bbb-blog-poll__optionWrap">
					<button class="bbb-blog-poll__option" type="button" data-poll-option="<?php echo esc_attr((string) $option['key']); ?>">
						<span class="bbb-blog-poll__optionMain">
							<span class="bbb-blog-poll__optionLabel"><?php echo esc_html((string) $option['label']); ?></span>
							<?php if ('' !== (string) $option['note']) : ?>
								<span class="bbb-blog-poll__optionNote"><?php echo esc_html((string) $option['note']); ?></span>
							<?php endif; ?>
						</span>
						<span class="bbb-blog-poll__result" aria-hidden="true">
							<span class="bbb-blog-poll__bar"></span>
							<span class="bbb-blog-poll__percent">0%</span>
						</span>
					</button>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="bbb-blog-poll__status" data-poll-status aria-live="polite">Vote to see how the shelf is leaning.</p>
	</section>
	<?php
	return (string) ob_get_clean();
}

add_shortcode('bbb_poll', 'bbb_blog_poll_shortcode');
add_shortcode('poll', 'bbb_blog_poll_shortcode');

function bbb_blog_poll_voter_hash(WP_REST_Request $request): string {
	$voter_id = sanitize_text_field((string) $request->get_param('voter_id'));
	if ('' === $voter_id) {
		$voter_id = (string) ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
	}

	return hash_hmac('sha256', $voter_id, wp_salt('nonce'));
}

function bbb_blog_poll_request_options(WP_REST_Request $request): array {
	$options = $request->get_param('options');
	if (is_string($options)) {
		$options = preg_split('/\s*,\s*/', $options) ?: array();
	}
	if (!is_array($options)) {
		$options = array();
	}

	return array_values(array_filter(array_unique(array_map('bbb_blog_poll_normalize_key', $options))));
}

function bbb_blog_poll_request_option_config(WP_REST_Request $request): array {
	$options = $request->get_param('option_config');
	if (is_string($options)) {
		$decoded = json_decode($options, true);
		$options = is_array($decoded) ? $decoded : array();
	}
	if (!is_array($options)) {
		return array();
	}

	$clean = array();
	foreach ($options as $option) {
		if (!is_array($option)) {
			continue;
		}
		$key = bbb_blog_poll_normalize_key((string) ($option['key'] ?? ''));
		if ('' === $key) {
			continue;
		}
		$clean[] = array(
			'key'   => $key,
			'label' => wp_strip_all_tags((string) ($option['label'] ?? $key)),
			'note'  => wp_strip_all_tags((string) ($option['note'] ?? '')),
			'book'  => sanitize_title((string) ($option['book'] ?? '')),
		);
	}

	return $clean;
}

function bbb_blog_poll_sync_definition(string $poll_key, int $post_id, WP_REST_Request $request): void {
	$question = trim(wp_strip_all_tags((string) $request->get_param('question')));
	$options  = bbb_blog_poll_request_option_config($request);
	if ('' === $question || count($options) < 2) {
		return;
	}

	bbb_reader_supabase_request(
		'POST',
		'blog_polls',
		array('on_conflict' => 'poll_key'),
		array(
			array(
				'poll_key'   => $poll_key,
				'post_id'    => $post_id,
				'post_url'   => get_permalink($post_id) ?: '',
				'question'   => $question,
				'options'    => $options,
				'updated_at' => gmdate('c'),
			),
		)
	);
}

function bbb_blog_poll_results(string $poll_key, int $post_id, array $allowed_options): array {
	$rows = bbb_reader_supabase_request(
		'GET',
		'blog_poll_votes',
		array(
			'select'   => 'option_key',
			'poll_key' => 'eq.' . $poll_key,
			'post_id'  => 'eq.' . $post_id,
			'limit'    => 5000,
		)
	);

	if (is_wp_error($rows)) {
		return array('error' => $rows);
	}

	$counts = array_fill_keys($allowed_options, 0);
	foreach ($rows as $row) {
		$key = (string) ($row['option_key'] ?? '');
		if (isset($counts[$key])) {
			$counts[$key]++;
		}
	}

	$total = array_sum($counts);

	return array(
		'total'   => $total,
		'counts'  => $counts,
		'percent' => array_map(
			static fn(int $count): int => $total > 0 ? (int) round(($count / $total) * 100) : 0,
			$counts
		),
	);
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'bbb/v1',
			'/blog-poll',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'permission_callback' => '__return_true',
					'callback'            => static function (WP_REST_Request $request) {
						$poll_key = bbb_blog_poll_normalize_key((string) $request->get_param('poll_key'));
						$post_id  = absint($request->get_param('post_id'));
						$options  = bbb_blog_poll_request_options($request);

						if (!$post_id || count($options) < 2) {
							return new WP_Error('bbb_blog_poll_bad_request', 'Poll key, post ID, and options are required.', array('status' => 400));
						}

						bbb_blog_poll_sync_definition($poll_key, $post_id, $request);
						$results = bbb_blog_poll_results($poll_key, $post_id, $options);
						if (isset($results['error']) && is_wp_error($results['error'])) {
							return $results['error'];
						}

						return rest_ensure_response($results);
					},
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => '__return_true',
					'callback'            => static function (WP_REST_Request $request) {
						$poll_key = bbb_blog_poll_normalize_key((string) $request->get_param('poll_key'));
						$post_id  = absint($request->get_param('post_id'));
						$option   = bbb_blog_poll_normalize_key((string) $request->get_param('option'));
						$options  = bbb_blog_poll_request_options($request);

						if (!$post_id || count($options) < 2 || !in_array($option, $options, true)) {
							return new WP_Error('bbb_blog_poll_bad_vote', 'A valid poll option is required.', array('status' => 400));
						}

						bbb_blog_poll_sync_definition($poll_key, $post_id, $request);
						$identity = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
						$email    = is_array($identity) ? bbb_reader_normalize_email((string) ($identity['email'] ?? '')) : '';
						$voter    = bbb_blog_poll_voter_hash($request);
						$save     = bbb_reader_supabase_request(
							'POST',
							'blog_poll_votes',
							array('on_conflict' => 'poll_key,voter_hash'),
							array(
								array(
									'poll_key'          => $poll_key,
									'post_id'           => $post_id,
									'post_url'          => get_permalink($post_id) ?: '',
									'option_key'        => $option,
									'voter_hash'        => $voter,
									'reader_email_hash' => '' !== $email ? hash_hmac('sha256', $email, wp_salt('auth')) : null,
									'user_agent'        => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
									'updated_at'        => gmdate('c'),
								),
							)
						);

						if (is_wp_error($save)) {
							return $save;
						}

						$results = bbb_blog_poll_results($poll_key, $post_id, $options);
						if (isset($results['error']) && is_wp_error($results['error'])) {
							return $results['error'];
						}

						$results['selected'] = $option;
						return rest_ensure_response($results);
					},
				),
			)
		);
	}
);
