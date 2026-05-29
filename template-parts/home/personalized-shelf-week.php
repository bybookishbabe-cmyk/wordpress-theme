<?php
/**
 * Personalized homepage shelf for Society readers.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_home_personalized_book_key')) {
	function bbb_home_personalized_book_key(array $book): string {
		return strtolower(trim(sanitize_text_field((string) ($book['handle'] ?? $book['book_handle'] ?? $book['title'] ?? $book['book_title'] ?? ''))));
	}
}

if (!function_exists('bbb_home_personalized_subscriber_is_active')) {
	function bbb_home_personalized_subscriber_is_active(array $subscriber): bool {
		$weekly_opt_in = $subscriber['weekly_email_opt_in'] ?? null;
		if (false === $weekly_opt_in || 'false' === strtolower((string) $weekly_opt_in) || '0' === (string) $weekly_opt_in) {
			return false;
		}

		$status = strtolower(trim((string) ($subscriber['account_status'] ?? '')));
		if ('' !== $status && preg_match('/\b(unsubscribed|inactive|cancell?ed|expired|paused|deleted)\b/', $status)) {
			return false;
		}

		return true;
	}
}

if (!function_exists('bbb_home_personalized_reader_is_subscribed')) {
	function bbb_home_personalized_reader_is_subscribed(string $email, int $user_id = 0): bool {
		if (function_exists('bbb_reader_is_society') && bbb_reader_is_society()) {
			return true;
		}

		if ('' === $email || !function_exists('bbb_reader_supabase_request')) {
			return false;
		}

		$or = $user_id
			? sprintf('(wordpress_user_id.eq.%1$d,email_normalized.eq.%2$s,email.eq.%2$s,customer_email.eq.%2$s)', $user_id, $email)
			: sprintf('(email_normalized.eq.%1$s,email.eq.%1$s,customer_email.eq.%1$s)', $email);

		$rows = bbb_reader_supabase_request(
			'GET',
			'bookshelf_subscribers',
			array(
				'select' => 'email,email_normalized,customer_email,access_tier,society_key_used_at,weekly_email_opt_in,account_status',
				'or'     => $or,
				'limit'  => 10,
			)
		);

		if (is_wp_error($rows)) {
			return false;
		}

		foreach ((array) $rows as $row) {
			if (is_array($row) && bbb_home_personalized_subscriber_is_active($row)) {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('bbb_reader_is_society') || !bbb_reader_is_society()) {
	return;
}

$identity = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
$reader_email = is_array($identity) ? (string) ($identity['email'] ?? '') : '';
$reader_user_id = is_array($identity) ? (int) ($identity['userId'] ?? 0) : 0;
if (!function_exists('bbb_reader_fetch_account_books_for_identity')) {
	return;
}

if (!function_exists('bbb_home_personalized_split_tropes')) {
	function bbb_home_personalized_split_tropes($value): array {
		if (function_exists('bbb_reader_split_book_tropes')) {
			return bbb_reader_split_book_tropes($value);
		}

		$items = is_array($value) ? $value : (preg_split('/[,|]/', (string) $value) ?: array());
		return array_values(
			array_filter(
				array_map(
					static function ($item): string {
						if (is_array($item)) {
							$item = $item['name'] ?? $item['label'] ?? $item['title'] ?? '';
						}

						return strtolower(trim(sanitize_text_field((string) $item)));
					},
					$items
				)
			)
		);
	}
}

if (!function_exists('bbb_home_personalized_title_case')) {
	function bbb_home_personalized_title_case(string $value): string {
		return ucwords(str_replace(array('-', '_'), ' ', strtolower(trim($value))));
	}
}

if (!function_exists('bbb_home_personalized_bool_attr')) {
	function bbb_home_personalized_bool_attr($value): string {
		if (function_exists('bbb_truthy')) {
			return bbb_truthy($value) ? 'true' : 'false';
		}

		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}

		$normalized = strtolower(trim((string) $value));
		return in_array($normalized, array('1', 'true', 'yes', 'y', 'on'), true) ? 'true' : 'false';
	}
}

if (!function_exists('bbb_home_personalized_book_post')) {
	function bbb_home_personalized_book_post(array $book): ?WP_Post {
		$post_types = array_values(
			array_filter(
				array('bbb_book', 'sss_book'),
				static fn(string $post_type): bool => post_type_exists($post_type)
			)
		);

		if (!$post_types) {
			return null;
		}

		$handle = sanitize_title((string) ($book['handle'] ?? $book['book_handle'] ?? ''));
		if ('' !== $handle) {
			foreach ($post_types as $post_type) {
				$post = get_page_by_path($handle, OBJECT, $post_type);
				if ($post instanceof WP_Post) {
					return $post;
				}
			}
		}

		$title = trim((string) ($book['title'] ?? $book['book_title'] ?? ''));
		if ('' === $title) {
			return null;
		}

		$matches = get_posts(
			array(
				'post_type'              => $post_types,
				'post_status'            => 'publish',
				'title'                  => $title,
				'posts_per_page'         => 1,
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
			)
		);

		return $matches && $matches[0] instanceof WP_Post ? $matches[0] : null;
	}
}

if (!function_exists('bbb_home_personalized_enrich_book')) {
	function bbb_home_personalized_enrich_book(array $book): array {
		$post = bbb_home_personalized_book_post($book);
		if (!$post instanceof WP_Post || !function_exists('sss_book_data')) {
			return $book;
		}

		$data = sss_book_data($post);
		$full = array(
			'handle'              => $data['handle'] ?? $post->post_name,
			'title'               => $data['title'] ?? get_the_title($post),
			'author'              => $data['author'] ?? '',
			'cover'               => $data['cover'] ?? '',
			'amazon'              => $data['amazon'] ?? '',
			'bookshop'            => $data['bookshop'] ?? '',
			'spice'               => $data['spice'] ?? '',
			'darkness'            => $data['darkness'] ?? '',
			'tropes'              => $data['tropes'] ?? array(),
			'why'                 => $data['why'] ?? '',
			'mini'                => $data['mini'] ?? '',
			'newsletter'          => $data['newsletter'] ?? '',
			'series'              => $data['series_handle'] ?? '',
			'seriesName'          => $data['series_name'] ?? '',
			'seriesNumber'        => $data['series_number'] ?? '',
			'series_handle'       => $data['series_handle'] ?? '',
			'series_name'         => $data['series_name'] ?? '',
			'series_number'       => $data['series_number'] ?? '',
			'standalone'          => !empty($data['standalone']) ? 'true' : 'false',
			'tension'             => $data['tension'] ?? '',
			'damage'              => $data['damage'] ?? '',
			'yearning'            => $data['yearning'] ?? '',
			'boyfriend'           => $data['boyfriend'] ?? '',
			'boyfriend_name'      => $data['boyfriend_name'] ?? '',
			'boyfriendName'       => $data['boyfriend_name'] ?? '',
			'reread'              => !empty($data['reread']) ? 'true' : 'false',
			'ku'                  => !empty($data['ku']) ? 'true' : 'false',
			'on_ku'               => !empty($data['ku']) ? 'true' : 'false',
			'on_kindle_unlimited' => !empty($data['ku']) ? 'true' : 'false',
		);

		return array_merge($book, $full);
	}
}

if (!function_exists('bbb_home_personalized_enrich_books')) {
	function bbb_home_personalized_enrich_books(array $books): array {
		return array_map(
			static fn($book): array => is_array($book) ? bbb_home_personalized_enrich_book($book) : array(),
			$books
		);
	}
}

if (!function_exists('bbb_home_personalized_recommendations')) {
	function bbb_home_personalized_recommendations(array $saved_books, array $reader_type, int $limit = 3): array {
		if (!function_exists('bbb_reader_quiz_books')) {
			return array();
		}

		$saved_keys = array();
		foreach ($saved_books as $book) {
			if (!is_array($book)) {
				continue;
			}

			$key = bbb_home_personalized_book_key($book);
			if ('' !== $key) {
				$saved_keys[$key] = true;
			}
		}

		$top_tropes = is_array($reader_type['topTropes'] ?? null) ? array_map('strtolower', (array) $reader_type['topTropes']) : array();
		$library_books = bbb_reader_quiz_books();
		$scored = array();

		foreach ($library_books as $book) {
			if (!is_array($book)) {
				continue;
			}

			$key = bbb_home_personalized_book_key($book);
			if ('' === $key || isset($saved_keys[$key])) {
				continue;
			}

			$book_tropes = bbb_home_personalized_split_tropes($book['tropes'] ?? array());
			$score = 0;
			foreach ($top_tropes as $trope) {
				if (in_array($trope, $book_tropes, true)) {
					$score += 4;
				}
			}

			$score += (int) ($book['spice'] ?? 0) > 0 ? 1 : 0;
			$scored[] = array(
				'book'  => $book,
				'score' => $score,
			);
		}

		usort(
			$scored,
			static function (array $a, array $b): int {
				if ($a['score'] === $b['score']) {
					return strcmp((string) ($a['book']['title'] ?? ''), (string) ($b['book']['title'] ?? ''));
				}

				return $b['score'] <=> $a['score'];
			}
		);

		return array_slice(array_column($scored, 'book'), 0, $limit);
	}
}

if (!function_exists('bbb_home_personalized_reader_emojis')) {
	function bbb_home_personalized_reader_emojis(array $reader_type, string $lead_trope): array {
		$signature = strtolower((string) ($reader_type['title'] ?? '') . ' ' . $lead_trope . ' ' . implode(' ', (array) ($reader_type['topTropes'] ?? array())));

		if (str_contains($signature, 'dark') || str_contains($signature, 'obsession') || str_contains($signature, 'morally') || str_contains($signature, 'villain')) {
			return array('🖤', '🗡️', '🌹', '🔥', '💋', '🕯️', '⛓️', '✨');
		}

		if (str_contains($signature, 'slow') || str_contains($signature, 'yearning') || str_contains($signature, 'forbidden')) {
			return array('💌', '🌙', '🕯️', '🥀', '✨', '📖', '💫', '🤍');
		}

		if (str_contains($signature, 'ache') || str_contains($signature, 'angst') || str_contains($signature, 'second chance')) {
			return array('🥀', '💔', '🌧️', '💌', '🕯️', '✨', '🤍', '📖');
		}

		if (str_contains($signature, 'romantasy') || str_contains($signature, 'fated') || str_contains($signature, 'magic') || str_contains($signature, 'fae')) {
			return array('⚔️', '✨', '🌙', '🗝️', '🔥', '👑', '💫', '📜');
		}

		if (str_contains($signature, 'heat') || str_contains($signature, 'spicy')) {
			return array('🌶️', '🔥', '💋', '🖤', '✨', '🥂', '🌹', '🕯️');
		}

		if (str_contains($signature, 'collector') || str_contains($signature, 'future')) {
			return array('📚', '✨', '🔖', '💌', '🌙', '🛒', '🖤', '📖');
		}

		return array('📚', '💌', '✨', '🌙', '🖤', '🔖', '📖', '🌹');
	}
}

if (!function_exists('bbb_home_personalized_trope_emoji')) {
	function bbb_home_personalized_trope_emoji(array $reader_type, string $lead_trope): string {
		$signature = strtolower((string) ($reader_type['title'] ?? '') . ' ' . $lead_trope);

		if (str_contains($signature, 'dark') || str_contains($signature, 'obsession') || str_contains($signature, 'morally') || str_contains($signature, 'villain')) {
			return '🖤';
		}

		if (str_contains($signature, 'slow') || str_contains($signature, 'yearning') || str_contains($signature, 'forbidden')) {
			return '💌';
		}

		if (str_contains($signature, 'ache') || str_contains($signature, 'angst') || str_contains($signature, 'second chance')) {
			return '🥀';
		}

		if (str_contains($signature, 'romantasy') || str_contains($signature, 'fated') || str_contains($signature, 'magic') || str_contains($signature, 'fae')) {
			return '⚔️';
		}

		if (str_contains($signature, 'heat') || str_contains($signature, 'spicy')) {
			return '🌶️';
		}

		if (str_contains($signature, 'collector') || str_contains($signature, 'future')) {
			return '📚';
		}

		return '✨';
	}
}

$saved_books = '' !== $reader_email ? bbb_home_personalized_enrich_books(bbb_reader_fetch_account_books_for_identity($reader_email, $reader_user_id)) : array();
$statuses = '' !== $reader_email && function_exists('bbb_reader_fetch_account_book_statuses_for_identity')
	? bbb_reader_fetch_account_book_statuses_for_identity($reader_email, $reader_user_id)
	: array();
$insights = function_exists('bbb_reader_account_insights')
	? bbb_reader_account_insights($saved_books, $statuses)
	: array('books' => $saved_books, 'readerType' => array(), 'nextRead' => null);
$books = is_array($insights['books'] ?? null) ? (array) $insights['books'] : $saved_books;
$reader_type = is_array($insights['readerType'] ?? null) ? (array) $insights['readerType'] : array();
$top_tropes = is_array($reader_type['topTropes'] ?? null) ? array_values(array_filter((array) $reader_type['topTropes'])) : array();
$lead_trope = $top_tropes ? (string) $top_tropes[0] : 'romance';
$lead_trope_count = 0;

foreach ($books as $book) {
	if (!is_array($book)) {
		continue;
	}

	if (in_array(strtolower($lead_trope), bbb_home_personalized_split_tropes($book['tropes'] ?? array()), true)) {
		++$lead_trope_count;
	}
}

$saved_count = count($books);
$lead_count = $lead_trope_count > 0 ? $lead_trope_count : $saved_count;
$recommendations = bbb_home_personalized_recommendations($books, $reader_type, 3);
$next_read = is_array($insights['nextRead'] ?? null) ? (array) $insights['nextRead'] : null;

if (!$recommendations && $next_read) {
	$recommendations = array(
		array(
			'handle' => (string) ($next_read['book_handle'] ?? ''),
			'title'  => (string) ($next_read['book_title'] ?? ''),
			'author' => (string) ($next_read['author'] ?? ''),
			'cover'  => (string) ($next_read['cover'] ?? ''),
			'tropes' => $next_read['tropes'] ?? array(),
			'url'    => !empty($next_read['book_handle']) ? home_url('/library/?book=' . rawurlencode((string) $next_read['book_handle'])) : home_url('/library/'),
		),
	);
}

$latest_save = null;
foreach ($books as $book) {
	if (is_array($book) && '' !== trim((string) ($book['title'] ?? $book['book_title'] ?? ''))) {
		$latest_save = $book;
		break;
	}
}

$what_to_read_next = $recommendations && is_array($recommendations[0]) ? $recommendations[0] : null;
if (!$what_to_read_next && $next_read) {
	$what_to_read_next = array(
		'handle' => (string) ($next_read['book_handle'] ?? $next_read['handle'] ?? ''),
		'title'  => (string) ($next_read['book_title'] ?? $next_read['title'] ?? ''),
		'author' => (string) ($next_read['author'] ?? ''),
		'cover'  => (string) ($next_read['cover'] ?? ''),
		'tropes' => $next_read['tropes'] ?? array(),
		'url'    => !empty($next_read['book_handle']) ? home_url('/library/?book=' . rawurlencode((string) $next_read['book_handle'])) : home_url('/library/'),
	);
}

$dashboard_picks = array_values(
	array_filter(
		array(
			array('label' => 'latest save', 'book' => $latest_save),
			array('label' => 'what to read next', 'book' => $what_to_read_next),
		),
		static fn(array $pick): bool => is_array($pick['book'])
	)
);
$reader_name = '';
if (is_user_logged_in()) {
	$current_user = wp_get_current_user();
	$reader_name = trim((string) ($current_user->display_name ?: $current_user->user_firstname));
}
$reader_first_name = '' !== $reader_name ? strtok($reader_name, ' ') : '';
$headline = '' !== $reader_first_name ? sprintf('made for %s', strtolower($reader_first_name)) : 'made for you';
$summary = $saved_count > 0
	? sprintf(
		'your society shelf has %1$s saved %2$s, with %3$s leading the pattern.',
		(string) $saved_count,
		1 === $saved_count ? 'book' : 'books',
		strtolower(bbb_home_personalized_title_case($lead_trope))
	)
	: 'your paid member dashboard is ready. save a few books and this gets sharper every time you come home.';
$library_url = function_exists('bbb_page_url') ? bbb_page_url('library') : home_url('/library/');
$bookshelf_url = function_exists('bbb_page_url') ? bbb_page_url('my-bookshelf') : home_url('/my-bookshelf/');
$dashboard_url = function_exists('bbb_page_url') ? bbb_page_url('made-for-you') : home_url('/made-for-you/');
$read_count = 0;
foreach ($statuses as $status) {
	if (!is_array($status)) {
		continue;
	}

	if ('read' === strtolower(trim((string) ($status['status'] ?? '')))) {
		++$read_count;
	}
}
$next_read_title = $recommendations && is_array($recommendations[0])
	? trim((string) ($recommendations[0]['title'] ?? $recommendations[0]['book_title'] ?? ''))
	: '';
$emoji_fall = bbb_home_personalized_reader_emojis($reader_type, $lead_trope);
$reader_type_title = trim((string) ($reader_type['title'] ?? 'mood-led romance reader'));
$reader_type_summary = trim((string) ($reader_type['summary'] ?? 'your saved shelf is still learning your pattern.'));
$reader_type_emoji = bbb_home_personalized_trope_emoji($reader_type, $lead_trope);
$stat_cards = array(
	array(
		'label' => 'saved',
		'value' => (string) $saved_count,
		'note'  => 1 === $saved_count ? 'book on your shelf' : 'books on your shelf',
	),
	array(
		'label' => 'pattern',
		'value' => $saved_count > 0 ? strtolower(bbb_home_personalized_title_case($lead_trope)) : 'learning',
		'note'  => $lead_count > 0 ? sprintf('%s matching %s', (string) $lead_count, 1 === $lead_count ? 'save' : 'saves') : 'save books to tune it',
	),
	array(
		'label' => 'finished',
		'value' => (string) $read_count,
		'note'  => 1 === $read_count ? 'book marked read' : 'books marked read',
	),
);
?>
<section class="bbb-home-shelf-week bbb-home-shelf-week--dashboard" aria-label="made for you member dashboard" data-sss-lib="society">
	<div class="bbb-home-shelf-week__inner">
		<div class="bbb-home-shelf-week__emojiFall" aria-hidden="true">
			<?php foreach ($emoji_fall as $index => $emoji) : ?>
				<span style="--fall-index: <?php echo esc_attr((string) $index); ?>;"><?php echo esc_html($emoji); ?></span>
			<?php endforeach; ?>
		</div>
		<div class="bbb-home-shelf-week__copy">
			<p class="bbb-home-shelf-week__kicker">paid member dashboard</p>
			<h2><?php echo esc_html($headline); ?></h2>
			<p><?php echo esc_html($summary); ?></p>
			<div class="bbb-home-shelf-week__readerType" aria-label="reader type">
				<span class="bbb-home-shelf-week__readerEmoji" aria-hidden="true"><?php echo esc_html($reader_type_emoji); ?></span>
				<span>
					<strong><?php echo esc_html($reader_type_title); ?></strong>
					<small><?php echo esc_html($reader_type_emoji . ' ' . strtolower(bbb_home_personalized_title_case($lead_trope))); ?></small>
				</span>
			</div>
			<p class="bbb-home-shelf-week__readerSummary"><?php echo esc_html($reader_type_summary); ?></p>
			<div class="bbb-home-shelf-week__stats" aria-label="reader dashboard stats">
				<?php foreach ($stat_cards as $card) : ?>
					<div class="bbb-home-shelf-week__stat">
						<span><?php echo esc_html($card['label']); ?></span>
						<strong><?php echo esc_html($card['value']); ?></strong>
						<small><?php echo esc_html($card['note']); ?></small>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="bbb-home-shelf-week__actions">
				<a href="<?php echo esc_url($dashboard_url); ?>">open made for you</a>
				<a href="<?php echo esc_url($bookshelf_url); ?>">my bookshelf</a>
				<a href="<?php echo esc_url($library_url); ?>">library</a>
			</div>
		</div>

		<div class="bbb-home-shelf-week__panel">
			<div class="bbb-home-shelf-week__panelHead">
				<div>
					<span>from your dashboard</span>
					<strong><?php echo esc_html($dashboard_picks ? 'latest save + next read' : 'your saved shelf'); ?></strong>
				</div>
				<a href="<?php echo esc_url($dashboard_url); ?>">view all</a>
			</div>
			<div class="bbb-home-shelf-week__rail" aria-label="your made for you bookshelf">
			<?php if ($dashboard_picks) : ?>
				<?php foreach ($dashboard_picks as $index => $pick) : ?>
					<?php
					$book = (array) $pick['book'];
					$title = trim((string) ($book['title'] ?? $book['book_title'] ?? ''));
					$author = trim((string) ($book['author'] ?? ''));
					$cover = trim((string) ($book['cover'] ?? ''));
					$handle = trim((string) ($book['handle'] ?? $book['book_handle'] ?? ''));
					$tropes = bbb_home_personalized_split_tropes($book['tropes'] ?? array());
					$tropes_display = implode(', ', array_map('bbb_home_personalized_title_case', $tropes));
					$ku_value = $book['ku'] ?? $book['on_ku'] ?? $book['on_kindle_unlimited'] ?? $book['kindle_unlimited'] ?? $book['is_ku'] ?? false;
					$spice_count = (int) ($book['spice'] ?? $book['spice_level'] ?? 0);
					?>
					<button
						class="sss-lib__book bbb-home-shelf-week__book bbb-home-shelf-week__pick"
						type="button"
						style="--i: <?php echo esc_attr((string) $index); ?>;"
						data-handle="<?php echo esc_attr($handle); ?>"
						data-url="<?php echo esc_url((string) ($book['url'] ?? ('' !== $handle ? home_url('/books/' . rawurlencode($handle) . '/') : ''))); ?>"
						data-title="<?php echo esc_attr($title); ?>"
						data-author="<?php echo esc_attr($author); ?>"
						data-cover="<?php echo esc_url($cover); ?>"
						data-amazon="<?php echo esc_url((string) ($book['amazon'] ?? '')); ?>"
						data-bookshop="<?php echo esc_url((string) ($book['bookshop'] ?? '')); ?>"
						data-newsletter="<?php echo esc_url((string) ($book['newsletter'] ?? '')); ?>"
						data-spice="<?php echo esc_attr((string) ($book['spice'] ?? $book['spice_level'] ?? '')); ?>"
						data-ku="<?php echo esc_attr(bbb_home_personalized_bool_attr($ku_value)); ?>"
						data-tropes="<?php echo esc_attr(implode(', ', $tropes)); ?>"
						data-tropes-display="<?php echo esc_attr($tropes_display); ?>"
						data-mini="<?php echo esc_attr((string) ($book['mini'] ?? $book['mini_note'] ?? '')); ?>"
						data-why="<?php echo esc_attr((string) ($book['why'] ?? $book['review_note'] ?? '')); ?>"
						data-tension="<?php echo esc_attr((string) ($book['tension'] ?? '')); ?>"
						data-damage="<?php echo esc_attr((string) ($book['damage'] ?? $book['emotional_damage'] ?? '')); ?>"
						data-yearning="<?php echo esc_attr((string) ($book['yearning'] ?? '')); ?>"
						data-boyfriend="<?php echo esc_attr((string) ($book['boyfriend'] ?? $book['book_boyfriend'] ?? '')); ?>"
						data-boyfriend-name="<?php echo esc_attr((string) ($book['boyfriendName'] ?? $book['boyfriend_name'] ?? '')); ?>"
						data-darkness="<?php echo esc_attr((string) ($book['darkness'] ?? '')); ?>"
						data-reread="<?php echo esc_attr((string) ($book['reread'] ?? $book['reread_worthy'] ?? '')); ?>"
						data-series="<?php echo esc_attr((string) ($book['series'] ?? $book['series_handle'] ?? '')); ?>"
						data-series-name="<?php echo esc_attr((string) ($book['seriesName'] ?? $book['series_name'] ?? '')); ?>"
						data-series-number="<?php echo esc_attr((string) ($book['seriesNumber'] ?? $book['series_number'] ?? '')); ?>"
						data-standalone="<?php echo esc_attr((string) ($book['standalone'] ?? '')); ?>"
						aria-label="<?php echo esc_attr(sprintf('open details for %s', $title ?: 'this society pick')); ?>"
					>
						<span class="bbb-home-shelf-week__pickLabel"><?php echo esc_html((string) $pick['label']); ?></span>
						<span class="bbb-home-shelf-week__cover" aria-hidden="true">
							<?php if ('' !== $cover) : ?>
								<img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy">
							<?php else : ?>
								<span><?php echo esc_html(substr($title ?: 'b', 0, 1)); ?></span>
							<?php endif; ?>
							<span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
								<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
								<span class="sss-lib__heartLabel" data-heart-label>save</span>
							</span>
							<?php if ($spice_count > 0) : ?>
								<span class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', $spice_count)); ?></span>
							<?php endif; ?>
						</span>
						<span class="bbb-home-shelf-week__meta">
							<strong><?php echo esc_html($title ?: 'society pick'); ?></strong>
							<?php if ('' !== $author) : ?>
								<span><?php echo esc_html($author); ?></span>
							<?php endif; ?>
						</span>
					</button>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="bbb-home-shelf-week__empty">
					<strong>your dashboard is waiting for its first clue.</strong>
					<span>Open the library, save a few books, and this space becomes a personal read-next lane.</span>
				</div>
			<?php endif; ?>
			</div>
		</div>
	</div>
</section>
