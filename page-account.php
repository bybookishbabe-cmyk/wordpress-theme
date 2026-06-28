<?php
/**
 * Template Name: Reader Account
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

nocache_headers();

add_filter(
	'body_class',
	static function (array $classes): array {
		$classes[] = 'bbb-account-page';
		return $classes;
	}
);

if (!function_exists('bbb_account_drop_file_items')) {
	function bbb_account_drop_file_items(array $fields, array $keys): array {
		$fields = function_exists('bbb_reader_drop_field_map') ? bbb_reader_drop_field_map($fields) : $fields;
		$items = array();

		foreach ($keys as $key) {
			$field = $fields[$key] ?? null;
			if (!is_array($field)) {
				continue;
			}

			$nodes = array();
			if (!empty($field['references']['nodes']) && is_array($field['references']['nodes'])) {
				$nodes = $field['references']['nodes'];
			} elseif (!empty($field['reference']) && is_array($field['reference'])) {
				$nodes = array($field['reference']);
			}

			foreach ($nodes as $node) {
				if (!is_array($node)) {
					continue;
				}

				$url = $node['image']['url'] ?? $node['url'] ?? '';
				if (!is_string($url) || '' === trim($url)) {
					continue;
				}

				$alt = $node['image']['altText'] ?? $node['title'] ?? '';
				$items[] = array(
					'name'  => '' !== trim((string) $alt) ? (string) $alt : 'monthly theme preview',
					'image' => $url,
				);
			}
		}

		return array_values(array_unique($items, SORT_REGULAR));
	}
}

if (!function_exists('bbb_account_book_url')) {
	function bbb_account_book_url(array $book): string {
		$handle = sanitize_title((string) ($book['book_handle'] ?? $book['handle'] ?? $book['slug'] ?? ''));
		if ('' !== $handle) {
			$post_types = array('sss_book', 'book', 'post', 'page');
			foreach ($post_types as $post_type) {
				$post = get_page_by_path($handle, OBJECT, $post_type);
				if ($post instanceof WP_Post) {
					$permalink = get_permalink($post);
					if ($permalink) {
						return (string) $permalink;
					}
				}
			}

			return home_url('/books/' . $handle . '/');
		}

		$title = sanitize_title((string) ($book['book_title'] ?? $book['title'] ?? ''));
		if ('' !== $title) {
			return home_url('/books/' . $title . '/');
		}

		return function_exists('bbb_page_url') ? bbb_page_url('library') : home_url('/library/');
	}
}

if (!function_exists('bbb_account_popular_pages_path')) {
	function bbb_account_popular_pages_path(string $url): string {
		$path = (string) wp_parse_url($url, PHP_URL_PATH);
		$path = '/' . trim($path, '/') . '/';

		return '//' === $path ? '/' : $path;
	}
}

if (!function_exists('bbb_account_popular_pages_candidates')) {
	function bbb_account_popular_pages_candidates(): array {
		$route_items = array(
			array('Reader Quizzes', 'reader-quizzes', 'quiz hub', 'personality quizzes, trope matches, and reader chaos in one place.'),
			array('Reader Mood Quiz', 'reader-mood-quiz', 'quiz', 'pick the mood and let the site find the reading lane.'),
			array('Fictional Boyfriend Quiz', 'fictional-boyfriend-quiz', 'quiz', 'for finding exactly which fictional man is your current problem.'),
			array('Find Your Next Read', 'what-to-read-next', 'recommendation tool', 'a fast route into a book match by shelf, trope, and mood.'),
			array('Romance Books by Spice Level', 'romance-books-by-spice-level', 'spice guide', 'browse by heat level without guessing.'),
			array('Enemies to Lovers', 'enemies-to-lovers', 'trope page', 'tension, resentment, and payoff that actually earns it.'),
			array('Slow Burn Books', 'slow-burn-books', 'trope page', 'for readers who want the almost before the finally.'),
			array('Sports Romance Books', 'sports-romance-books', 'shelf page', 'hockey, athletes, competition, and locker-room-level tension.'),
			array('Book Reviews', 'book-reviews', 'review index', 'the review shelf for deciding what deserves your weekend.'),
			array('Books Like X', 'books-like', 'reading guide', 'next-read guides based on books you already loved.'),
			array('Series Reading Orders', 'series-reading-orders', 'reading order', 'start the series in the right place and avoid the chaos.'),
			array('Weekly Obsession', 'weekly-obsession', 'weekly pick', 'the current book taking up too much space in the group chat.'),
			array('Quote Wall', 'sss-quote-wall', 'quote archive', 'reader-favorite lines, beautifully collected.'),
		);

		$items = array();
		foreach ($route_items as $item) {
			[$title, $slug, $type, $description] = $item;
			$post = get_page_by_path($slug);
			$url = function_exists('bbb_page_url') ? bbb_page_url($slug) : '';
			if (!is_string($url) || '' === $url) {
				$url = home_url('/' . trim($slug, '/') . '/');
			}

			$items[] = array(
				'title'       => $post instanceof WP_Post ? get_the_title($post) : $title,
				'url'         => $url,
				'path'        => bbb_account_popular_pages_path($url),
				'type'        => $type,
				'description' => $description,
			);
		}

		return $items;
	}
}

$my_bookshelf_css_path = get_theme_file_path('assets/css/my-bookshelf.css');
$my_bookshelf_live_fix_css_path = get_theme_file_path('assets/css/my-bookshelf-live-fix.css');
$my_bookshelf_js_path  = get_theme_file_path('assets/js/my-bookshelf.js');
wp_enqueue_style('bbb-my-bookshelf', get_theme_file_uri('assets/css/my-bookshelf.css'), array('bbb-sss-library'), file_exists($my_bookshelf_css_path) ? (string) filemtime($my_bookshelf_css_path) : wp_get_theme()->get('Version'));
if (file_exists($my_bookshelf_live_fix_css_path)) {
	wp_enqueue_style('bbb-my-bookshelf-live-fix', get_theme_file_uri('assets/css/my-bookshelf-live-fix.css'), array('bbb-my-bookshelf'), (string) filemtime($my_bookshelf_live_fix_css_path));
}
wp_enqueue_script('bbb-my-bookshelf', get_theme_file_uri('assets/js/my-bookshelf.js'), array('bbb-sss-library'), file_exists($my_bookshelf_js_path) ? (string) filemtime($my_bookshelf_js_path) : wp_get_theme()->get('Version'), true);
wp_localize_script(
	'bbb-my-bookshelf',
		'BBBReaderAccountApi',
		array(
			'endpoint'      => set_url_scheme(rest_url('bbb/v1/reader-account'), is_ssl() ? 'https' : 'http'),
			'emailEndpoint' => set_url_scheme(rest_url('bbb/v1/reader-account/email-session'), is_ssl() ? 'https' : 'http'),
			'shelfEndpoint' => set_url_scheme(rest_url('bbb/v1/reader-account/shelf'), is_ssl() ? 'https' : 'http'),
			'spiceEndpoint' => set_url_scheme(rest_url('bbb/v1/reader-account/spice-profile'), is_ssl() ? 'https' : 'http'),
			'profileEndpoint' => set_url_scheme(rest_url('bbb/v1/reader-account/made-for-you'), is_ssl() ? 'https' : 'http'),
			'nonce'         => wp_create_nonce('wp_rest'),
		)
	);
wp_enqueue_style('bbb-font-burn-bright', 'https://fonts.googleapis.com/css2?family=DM+Mono:wght@300;400;500&family=DM+Sans:wght@300;400;500;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&display=swap', array(), null);
$account_trend_js_path = get_theme_file_path('assets/js/account-popular-trend.js');
wp_enqueue_script('bbb-account-popular-trend', get_theme_file_uri('assets/js/account-popular-trend.js'), array('bbb-supabase'), file_exists($account_trend_js_path) ? (string) filemtime($account_trend_js_path) : wp_get_theme()->get('Version'), true);
wp_localize_script(
	'bbb-account-popular-trend',
	'BBBAccountPopularTrend',
	array(
		'candidates'  => bbb_account_popular_pages_candidates(),
		'supabaseUrl' => defined('SUPABASE_URL') ? SUPABASE_URL : 'https://efmrfxsmgbeikfgtrxjv.supabase.co',
		'supabaseKey' => defined('SUPABASE_ANON_KEY') ? SUPABASE_ANON_KEY : 'sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk',
	)
);

$identity     = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
if (!$identity && function_exists('bbb_reader_is_local_request') && bbb_reader_is_local_request()) {
	$identity = array(
		'email'       => 'autumn@example.com',
		'displayName' => 'autumn',
		'userId'      => 0,
		'user'        => null,
	);
}
$is_logged_in = is_user_logged_in();
$user         = isset($identity['user']) && $identity['user'] instanceof WP_User ? $identity['user'] : ($is_logged_in ? wp_get_current_user() : null);
$reader_email = $identity ? (string) ($identity['email'] ?? '') : '';
$reader_user_id = $identity ? (int) ($identity['userId'] ?? 0) : 0;
$reader_account_key = $reader_user_id > 0
	? 'user:' . (string) $reader_user_id
	: ('' !== $reader_email ? 'email:' . md5(strtolower(trim($reader_email))) : '');
$has_reader_access = '' !== $reader_email;
$account_data = array();
$account_error = null;

if ($has_reader_access && function_exists('bbb_reader_account_response_for_identity')) {
	try {
		$account_data = bbb_reader_account_response_for_identity((array) $identity);
	} catch (Throwable $error) {
		$account_error = $error;
		error_log('BBB account page failed softly: ' . $error->getMessage());
		$account_data = array(
			'accessTier' => 'free',
			'books'      => array(),
			'readerType' => array(
				'title'     => 'fresh shelf romantic',
				'summary'   => 'your account opened, but the bookshelf sync needs a retry.',
				'topTropes' => array(),
				'counts'    => array('saved' => 0, 'read' => 0, 'reading' => 0, 'tbr' => 0),
			),
			'nextRead'   => null,
		);
	}
}
$books        = isset($account_data['books']) && is_array($account_data['books']) ? $account_data['books'] : array();
$reader_type  = isset($account_data['readerType']) && is_array($account_data['readerType']) ? $account_data['readerType'] : array(
	'title'     => 'fresh shelf romantic',
	'summary'   => 'save or tag a few books and this will start calling your pattern.',
	'topTropes' => array(),
	'counts'    => array('saved' => count($books), 'read' => 0, 'reading' => 0, 'tbr' => 0),
);
$account_reader_types = function_exists('bbb_reader_type_registry') ? bbb_reader_type_registry() : array();
$account_reader_type = function_exists('bbb_reader_type_by_key') ? bbb_reader_type_by_key('romance_reader') : null;
$account_reader_type = is_array($account_reader_type) ? $account_reader_type : array(
	'key'   => 'romance_reader',
	'label' => 'the romance reader',
	'emoji' => 'found-family',
	'bio'   => 'your taste is still sorting itself into a sharper pattern, so the dashboard is keeping things flexible.',
	'theme' => array('name' => 'unsorted silver', 'surface' => '#131013', 'border' => '#2E282C', 'deep' => '#5E5258', 'accent' => '#D4C2CE', 'accent2' => '#EFE4EA'),
);
$account_reader_type_theme = is_array($account_reader_type['theme'] ?? null) ? $account_reader_type['theme'] : array();
$account_reader_type_emoji_url = function_exists('bbb_custom_emoji_url') ? bbb_custom_emoji_url((string) ($account_reader_type['emoji'] ?? 'found-family')) : '';
$account_mfy_profile = isset($account_data['madeForYouProfile']) && is_array($account_data['madeForYouProfile']) ? $account_data['madeForYouProfile'] : array();
$account_mfy_profile_is_complete = function_exists('bbb_reader_mfy_profile_is_complete') ? bbb_reader_mfy_profile_is_complete($account_mfy_profile) : false;
$next_read    = isset($account_data['nextRead']) && is_array($account_data['nextRead']) ? $account_data['nextRead'] : null;
$tier         = (string) ($account_data['accessTier'] ?? 'free');
$is_society   = 'society' === $tier || (function_exists('bbb_reader_is_society') && bbb_reader_is_society());
$tier         = $is_society ? 'society' : $tier;
$display_name = ($identity && '' !== trim((string) ($identity['displayName'] ?? ''))) ? (string) $identity['displayName'] : 'reader';
$tier_label   = 'society' === $tier ? 'tier: paid society member' : ($has_reader_access ? 'tier: free reader member' : 'tier: visitor');
$account_url  = function_exists('bbb_page_url') ? bbb_page_url('account') : home_url('/account/');
$bookshelf_url = function_exists('bbb_page_url') ? bbb_page_url('my-bookshelf') : home_url('/my-bookshelf/');
$vault_url = function_exists('bbb_page_url') ? bbb_page_url('my-vault') : home_url('/my-vault/');
$notes_url = function_exists('bbb_page_url') ? bbb_page_url('my-notes') : home_url('/my-notes/');
$made_for_you_url = function_exists('bbb_page_url') ? bbb_page_url('made-for-you') : home_url('/made-for-you/');
$monthly_drop_url = function_exists('bbb_page_url') ? bbb_page_url('monthly-theme') : home_url('/monthly-theme/');
$society_url = function_exists('bbb_page_url') ? bbb_page_url('smut-sentiment-society') : home_url('/smut-sentiment-society/');
$society_join_url = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$society_join_url = '' !== trim((string) $society_join_url) ? (string) $society_join_url : 'https://thesmutandsentimentsociety.substack.com/subscribe';
$shop_url = function_exists('bbb_page_url') ? bbb_page_url('shop') : home_url('/shop/');
$active_monthly_drop = function_exists('bbb_reader_active_society_drop') ? bbb_reader_active_society_drop() : array();
$daily_prompt = is_array($account_data['dailyJournalPrompt'] ?? null) ? $account_data['dailyJournalPrompt'] : array();
if (
	(empty($daily_prompt['text']) || '' === trim((string) $daily_prompt['text']))
	&& function_exists('bbb_reader_active_society_daily_prompt')
) {
	$daily_prompt = bbb_reader_active_society_daily_prompt($active_monthly_drop);
}
$daily_prompt_text = isset($daily_prompt['text']) ? trim((string) $daily_prompt['text']) : '';
$daily_prompt_day = isset($daily_prompt['day']) ? (int) $daily_prompt['day'] : 0;
$daily_prompt_total = isset($daily_prompt['total']) ? (int) $daily_prompt['total'] : 0;
$show_daily_prompt = '' !== $daily_prompt_text;
$reader_logged_out = isset($_GET['bbb_reader_logged_out']);
$reader_email_error = isset($_GET['reader_email_error']) ? sanitize_text_field((string) wp_unslash($_GET['reader_email_error'])) : '';
$daily_prompt_meta = ($daily_prompt_day > 0 && $daily_prompt_total > 0)
	? sprintf('day %s of %s', (string) $daily_prompt_day, (string) $daily_prompt_total)
	: 'daily journal prompt';
$purchase_rows = array();
$bookshelf_preview_books = array_slice(
	array_values(
		array_filter(
			$books,
			static fn($book): bool => is_array($book) && ('' !== trim((string) ($book['cover'] ?? '')) || '' !== trim((string) ($book['book_title'] ?? $book['title'] ?? '')))
		)
	),
	0,
	3
);
$bookshelf_display_books = array_slice(
	array_values(
		array_filter(
			$books,
			static fn($book): bool => is_array($book) && ('' !== trim((string) ($book['cover'] ?? '')) || '' !== trim((string) ($book['book_title'] ?? $book['title'] ?? '')))
		)
	),
	0,
	7
);
$reader_type_title = trim((string) ($reader_type['title'] ?? 'fresh shelf romantic'));
$reader_type_summary = trim((string) ($reader_type['summary'] ?? 'save or tag a few books and this will start calling your pattern.'));
$reader_type_red_flag = trim((string) ($reader_type['redFlag'] ?? ''));
$reader_type_counts = is_array($reader_type['counts'] ?? null) ? $reader_type['counts'] : array();
$reader_type_tropes = is_array($reader_type['topTropes'] ?? null) ? array_values(array_filter($reader_type['topTropes'])) : array();
$spice_profile = is_array($account_data['spiceProfile'] ?? null)
	? $account_data['spiceProfile']
	: (function_exists('bbb_reader_spice_profile_for_identity') ? bbb_reader_spice_profile_for_identity((array) ($identity ?: array())) : array('level' => 0, 'label' => '', 'peppers' => '', 'description' => ''));
$spice_profile_level = (int) ($spice_profile['level'] ?? 0);
$spice_profiles = function_exists('bbb_reader_spice_profiles') ? bbb_reader_spice_profiles() : array();
$moon_mood_trope = $reader_type_tropes ? (string) $reader_type_tropes[0] : 'slow burn';
$moon_mood_url = function_exists('bbb_trope_page_url') ? bbb_trope_page_url($moon_mood_trope) : home_url('/slow-burn-books/');
$society_trend_fallback = bbb_account_popular_pages_candidates()[0] ?? array(
	'title' => 'Reader Quizzes',
	'url'   => home_url('/reader-quizzes/'),
	'type'  => 'quiz hub',
);
$next_read_title = $next_read ? trim((string) ($next_read['book_title'] ?? $next_read['title'] ?? '')) : '';
$next_read_title = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($next_read_title) : $next_read_title;
$next_read_author = $next_read ? trim((string) ($next_read['author'] ?? '')) : '';
$next_read_author = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($next_read_author) : $next_read_author;
$next_read_cover = $next_read ? trim((string) ($next_read['cover'] ?? '')) : '';
$next_read_handle = $next_read ? sanitize_title((string) ($next_read['book_handle'] ?? $next_read['handle'] ?? '')) : '';
$next_read_url = $next_read ? bbb_account_book_url($next_read) : '';
$next_read_amazon = $next_read ? trim((string) ($next_read['amazon'] ?? '')) : '';
$next_read_bookshop = $next_read ? trim((string) ($next_read['bookshop'] ?? '')) : '';
$next_read_spice = $next_read ? (int) ($next_read['spice_level'] ?? $next_read['spice'] ?? 0) : 0;
$next_read_darkness = $next_read ? (int) ($next_read['darkness_level'] ?? $next_read['darkness'] ?? 0) : 0;
$next_read_tropes = $next_read ? array_slice(
	array_values(
		array_filter(
			array_map(
				'trim',
				is_array($next_read['tropes'] ?? null) ? (array) $next_read['tropes'] : preg_split('/[,|]/', (string) ($next_read['tropes'] ?? ''))
			)
		)
	),
	0,
	3
) : array();
$next_read_tropes_text = implode(', ', $next_read_tropes);
$reader_signal_text = strtolower($reader_type_title . ' ' . implode(' ', array_map('strval', $reader_type_tropes)));
$reader_emoji_pool = array('📚', '✨', '💕', '🌙', '💌', '📖');
if (str_contains($reader_signal_text, 'dark') || str_contains($reader_signal_text, 'mafia') || str_contains($reader_signal_text, 'stalker')) {
	$reader_emoji_pool = array('🖤', '🌙', '🥀', '🔪', '✨', '📖');
} elseif (str_contains($reader_signal_text, 'spice') || str_contains($reader_signal_text, 'steamy')) {
	$reader_emoji_pool = array('🌶️', '🔥', '💋', '💕', '✨', '📖');
} elseif (str_contains($reader_signal_text, 'sports') || str_contains($reader_signal_text, 'hockey')) {
	$reader_emoji_pool = array('🏒', '🏟️', '💕', '✨', '📚', '🔥');
} elseif (str_contains($reader_signal_text, 'romantasy') || str_contains($reader_signal_text, 'fantasy') || str_contains($reader_signal_text, 'fated')) {
	$reader_emoji_pool = array('🐉', '⚔️', '🌙', '✨', '💕', '📖');
} elseif (str_contains($reader_signal_text, 'billionaire') || str_contains($reader_signal_text, 'royal')) {
	$reader_emoji_pool = array('👑', '🥂', '💎', '✨', '💕', '📚');
} elseif (str_contains($reader_signal_text, 'slow')) {
	$reader_emoji_pool = array('⏳', '🌹', '💕', '✨', '📖', '☕');
}
$reader_background_emoji = (string) ($reader_emoji_pool[0] ?? '📚');
$reader_type_display_tropes = $reader_type_tropes ?: array('slow burn', 'friends to lovers', 'found family');
$reader_profile_trope_buttons = array_slice($reader_type_display_tropes, 0, 5);
$reader_profile_trope_fallback = implode('|', array_map('strval', $reader_profile_trope_buttons));
$monthly_theme_fields = is_array($active_monthly_drop['fields'] ?? null) && function_exists('bbb_reader_drop_field_map')
	? bbb_reader_drop_field_map((array) $active_monthly_drop['fields'])
	: array();
$monthly_era_title = function_exists('bbb_reader_drop_field_value') ? bbb_reader_drop_field_value($monthly_theme_fields, 'name', 'burn bright') : 'burn bright';
$monthly_era_copy = function_exists('bbb_reader_drop_field_value') ? bbb_reader_drop_field_value($monthly_theme_fields, 'gram_sub') : '';
if ('' === $monthly_era_copy && function_exists('bbb_reader_drop_field_value')) {
	$monthly_era_copy = bbb_reader_drop_field_value($monthly_theme_fields, 'quote_text');
}
if ('' === $monthly_era_copy && function_exists('bbb_reader_drop_field_value')) {
	$monthly_era_copy = bbb_reader_drop_field_value($monthly_theme_fields, 'moodboard_title');
}
$fallback_monthly_era_designs = array(
	array('name' => 'alive in the night', 'image' => get_theme_file_uri('assets/monthly-themes/june-2026/previews/alive-in-the-night-mockup.png')),
	array('name' => 'golden and unbothered', 'image' => get_theme_file_uri('assets/monthly-themes/june-2026/previews/golden-and-unbothered-mockup.png')),
	array('name' => 'you glow different', 'image' => get_theme_file_uri('assets/monthly-themes/june-2026/previews/you-glow-different-mockup.png')),
);
$monthly_era_designs = bbb_account_drop_file_items(
	$monthly_theme_fields,
	array('wallpaper_images', 'mood_images', 'era_images', 'gram_image', 'calendar_image')
);
if (!$monthly_era_designs) {
	$monthly_era_designs = $fallback_monthly_era_designs;
}
$monthly_era = array(
	'title'   => '' !== trim($monthly_era_title) ? $monthly_era_title : 'monthly theme',
	'kicker'  => 'monthly era',
	'copy'    => '' !== trim($monthly_era_copy) ? $monthly_era_copy : 'printable kindle inserts, wallpapers, calendar prompts, and the whole monthly mood.',
	'accent'  => function_exists('bbb_reader_drop_field_value') ? bbb_reader_drop_field_value($monthly_theme_fields, 'mood_accent', '#ff6b1a') : '#ff6b1a',
	'cream'   => function_exists('bbb_reader_drop_field_value') ? bbb_reader_drop_field_value($monthly_theme_fields, 'mood_pill_bg', '#ffd0a8') : '#ffd0a8',
	'paper'   => '#fff8f0',
	'url'     => $monthly_drop_url,
	'texture' => get_theme_file_uri('assets/monthly-themes/june-2026/textures/burn-bright-botanical-texture.png'),
	'designs' => array_slice($monthly_era_designs, 0, 3),
);
$account_vintage_images = array(
	get_theme_file_uri('assets/freebies/may-2026-bookend-8x10-art-print-preview.jpg'),
	get_theme_file_uri('assets/monthly-themes/june-2026/previews/golden-and-unbothered.png'),
	get_theme_file_uri('assets/monthly-themes/june-2026/previews/the-light-finds-you-first.png'),
);

if ($has_reader_access && function_exists('wc_get_orders')) {
	$order_args = array(
		'limit'   => 4,
		'orderby' => 'date',
		'order'   => 'DESC',
		'status'  => array('wc-completed', 'wc-processing', 'wc-on-hold'),
	);
	$orders_by_id = $reader_user_id
		? wc_get_orders(
			array(
				'customer_id' => $reader_user_id,
			)
			+ $order_args
		)
		: array();
	$orders_by_email = wc_get_orders(
		array(
			'billing_email' => $reader_email,
		)
		+ $order_args
	);
	$orders = array();

	foreach (array_merge((array) $orders_by_id, (array) $orders_by_email) as $order) {
		if ($order instanceof WC_Order) {
			$orders[$order->get_id()] = $order;
		}
	}
	usort(
		$orders,
		static function (WC_Order $a, WC_Order $b): int {
			$a_time = $a->get_date_created() ? $a->get_date_created()->getTimestamp() : 0;
			$b_time = $b->get_date_created() ? $b->get_date_created()->getTimestamp() : 0;
			return $b_time <=> $a_time;
		}
	);

	foreach ($orders as $order) {
		$items = array();
		foreach ($order->get_items() as $item) {
			$items[] = $item->get_name();
		}

		$purchase_rows[] = array(
			'title'  => $items ? implode(', ', array_slice($items, 0, 2)) : sprintf('order #%s', $order->get_order_number()),
			'meta'   => trim(sprintf('%s - %s', wc_get_order_status_name($order->get_status()), $order->get_date_created() ? $order->get_date_created()->date_i18n('M j, Y') : '')),
			'url'    => $reader_user_id ? $order->get_view_order_url() : '',
			'total'  => wp_strip_all_tags($order->get_formatted_order_total()),
		);
	}

	$purchase_rows = array_slice($purchase_rows, 0, 4);
}

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section
		class="bbb-account-shelf bbb-account-home"
		data-account-shelf
		data-account-key="<?php echo esc_attr($reader_account_key); ?>"
		data-logged-in="<?php echo esc_attr($has_reader_access ? 'true' : 'false'); ?>"
		data-customer-id="<?php echo esc_attr((string) $reader_user_id); ?>"
		data-customer-email="<?php echo esc_attr($reader_email); ?>"
		data-is-society="<?php echo esc_attr($is_society ? 'true' : 'false'); ?>"
		data-mfy-profile-version="<?php echo esc_attr(function_exists('bbb_reader_mfy_profile_version') ? bbb_reader_mfy_profile_version() : ''); ?>"
		data-sss-lib="<?php echo esc_attr($is_society ? 'society' : 'public'); ?>"
	>
		<div class="bbb-account-shelf__wrap">
			<div class="bbb-account-profile__app">
				<header class="bbb-account-welcome">
					<div class="bbb-account-welcome__emojiRain" aria-hidden="true">
						<?php for ($emoji_index = 0; $emoji_index < 18; $emoji_index++) : ?>
							<span style="--i: <?php echo esc_attr((string) $emoji_index); ?>;"><?php echo esc_html($reader_background_emoji); ?></span>
						<?php endfor; ?>
					</div>
					<div class="bbb-account-welcome__scene" aria-hidden="true">
						<span class="bbb-account-welcome__spine bbb-account-welcome__spine--one"></span>
						<span class="bbb-account-welcome__spine bbb-account-welcome__spine--two"></span>
						<span class="bbb-account-welcome__spine bbb-account-welcome__spine--three"></span>
						<span class="bbb-account-welcome__sigil">b</span>
						<span class="bbb-account-welcome__rule"></span>
					</div>
					<div class="bbb-account-welcome__copy">
						<p class="bbb-account-shelf__kicker">reader account</p>
						<h1><?php echo esc_html($has_reader_access ? 'welcome back.' : 'welcome, reader.'); ?></h1>
						<?php if ($has_reader_access) : ?>
							<div class="bbb-account-welcome__meta">
								<p><?php echo esc_html($reader_email); ?></p>
								<a href="<?php echo esc_url(add_query_arg('bbb_reader_logout', '1', $account_url)); ?>">sign out</a>
							</div>
							<article
								class="bbb-account-welcome__readerProfile is-locked"
								data-reader-profile-card
								data-made-for-you-url="<?php echo esc_url($made_for_you_url); ?>"
								data-fallback-tropes="<?php echo esc_attr($reader_profile_trope_fallback); ?>"
								data-made-for-you-complete="<?php echo esc_attr($account_mfy_profile_is_complete ? 'true' : 'false'); ?>"
							>
								<div class="bbb-account-welcome__readerProfileHead">
									<div class="bbb-account-welcome__readerTypeBadge">
										<span class="bbb-account-welcome__readerTypeIcon" aria-hidden="true">
											<img src="<?php echo esc_url($account_reader_type_emoji_url); ?>" alt="" loading="lazy" decoding="async" data-reader-profile-emoji>
										</span>
										<div>
											<span>reader type</span>
											<strong data-reader-profile-type>profile pending</strong>
										</div>
									</div>
									<span class="bbb-account-welcome__readerProfileStatus" data-reader-profile-status>made for you pending</span>
								</div>
								<div class="bbb-account-welcome__readerProfileGrid">
									<div>
										<small>red flag</small>
										<p data-reader-profile-red-flag>answer the made for you questions to generate this.</p>
									</div>
									<div>
										<small>top saved/read tropes</small>
										<div class="bbb-account-welcome__tropeButtons" aria-label="top saved and read tropes">
											<button type="button" class="is-active" aria-pressed="true" data-reader-profile-trope data-reader-profile-detail="waiting on your made for you answers.">locked</button>
											<button type="button" aria-pressed="false" data-reader-profile-trope data-reader-profile-detail="finish the profile to reveal this pattern.">profile</button>
											<button type="button" aria-pressed="false" data-reader-profile-trope data-reader-profile-detail="your quiz answers unlock this shelf signal.">pending</button>
										</div>
									</div>
								</div>
								<p class="bbb-account-welcome__readerProfileDetail" data-reader-profile-detail-text aria-live="polite">
									your reader profile is blurred until you finish made for you.
								</p>
								<a class="bbb-account-welcome__readerProfileCta" href="<?php echo esc_url($made_for_you_url); ?>" data-reader-profile-cta>get your reader type</a>
							</article>
						<?php else : ?>
							<p>enter your email to open your shelf, picks, and account details.</p>
						<?php endif; ?>
					</div>
				</header>

				<div class="bbb-account-profile__stats" aria-label="account stats">
					<div>
						<strong><?php echo esc_html((string) ($reader_type_counts['saved'] ?? count($books))); ?></strong>
						<span>on the shelf</span>
					</div>
					<div>
						<strong><?php echo esc_html((string) ($reader_type_counts['reading'] ?? 0)); ?></strong>
						<span>reading</span>
					</div>
					<div>
						<strong data-account-spice-stat><?php echo esc_html($spice_profile_level ? (string) $spice_profile_level . '/5' : '-'); ?></strong>
						<span>spice profile</span>
					</div>
				</div>
				<div class="bbb-account-flowLine" aria-hidden="true">
					<span></span>
				</div>
				<?php if ($has_reader_access) : ?>
					<section
						class="bbb-account-profile__section bbb-account-shelf__spiceProfile"
						aria-label="spice profile"
						data-account-reveal
						data-spice-profile
						data-initial-level="<?php echo esc_attr((string) $spice_profile_level); ?>"
					>
						<div>
							<p class="bbb-account-profile__cardLabel">spice profile</p>
							<h2 data-spice-profile-title><?php echo esc_html($spice_profile_level ? (string) ($spice_profile['label'] ?? '') : 'pick your preferred heat'); ?></h2>
							<p data-spice-profile-copy><?php echo esc_html($spice_profile_level ? (string) ($spice_profile['description'] ?? '') : 'set the heat level you want your bookshelf, account, and future recs to orbit.'); ?></p>
							<strong data-spice-profile-peppers><?php echo esc_html($spice_profile_level ? (string) ($spice_profile['peppers'] ?? '') : 'not set yet'); ?></strong>
						</div>
						<div class="bbb-account-shelf__spiceChoices" role="radiogroup" aria-label="preferred spice level">
							<?php foreach ($spice_profiles as $level => $profile) : ?>
								<button
									type="button"
									class="bbb-account-shelf__spiceChoice<?php echo $spice_profile_level === (int) $level ? ' is-active' : ''; ?>"
									role="radio"
									aria-checked="<?php echo $spice_profile_level === (int) $level ? 'true' : 'false'; ?>"
									data-spice-choice="<?php echo esc_attr((string) $level); ?>"
									data-spice-label="<?php echo esc_attr((string) ($profile['label'] ?? '')); ?>"
									data-spice-peppers="<?php echo esc_attr((string) ($profile['peppers'] ?? '')); ?>"
									data-spice-description="<?php echo esc_attr((string) ($profile['description'] ?? '')); ?>"
								>
									<span><?php echo esc_html((string) ($profile['peppers'] ?? '')); ?></span>
									<strong><?php echo esc_html((string) ($profile['label'] ?? '')); ?></strong>
								</button>
							<?php endforeach; ?>
						</div>
						<p class="bbb-account-shelf__spiceStatus" data-spice-profile-status aria-live="polite"></p>
					</section>

					<a
						class="bbb-account-profile__section bbb-account-profile__monthlyEra"
						href="<?php echo esc_url($monthly_era['url']); ?>"
						aria-label="<?php echo esc_attr('open ' . $monthly_era['title'] . ' monthly era'); ?>"
						data-account-reveal
						style="--era-accent: <?php echo esc_attr($monthly_era['accent']); ?>; --era-cream: <?php echo esc_attr($monthly_era['cream']); ?>; --era-paper: <?php echo esc_attr($monthly_era['paper']); ?>; --era-texture: url('<?php echo esc_url($monthly_era['texture']); ?>');"
					>
					<div class="bbb-account-profile__monthlyEraCopy">
						<p class="bbb-account-profile__cardLabel"><?php echo esc_html($monthly_era['kicker']); ?></p>
						<h2><?php echo esc_html($monthly_era['title']); ?></h2>
						<p><?php echo esc_html($monthly_era['copy']); ?></p>
						<span class="bbb-account-profile__monthlyEraCta">open monthly era</span>
					</div>
					<div class="bbb-account-profile__monthlyEraDesigns" aria-label="monthly theme designs">
						<?php foreach ($monthly_era['designs'] as $design_index => $design) : ?>
							<span class="bbb-account-profile__monthlyEraDesign bbb-account-profile__monthlyEraDesign--<?php echo esc_attr((string) ($design_index + 1)); ?>">
								<img src="<?php echo esc_url((string) $design['image']); ?>" alt="<?php echo esc_attr((string) $design['name']); ?>" loading="lazy">
							</span>
						<?php endforeach; ?>
					</div>
					<article class="bbb-account-profile__monthlyEraPrompt" aria-label="daily journal prompt">
						<span>daily journal prompt</span>
						<?php if ($show_daily_prompt) : ?>
							<strong><?php echo esc_html($daily_prompt_meta); ?></strong>
							<p><?php echo esc_html($daily_prompt_text); ?></p>
						<?php else : ?>
							<strong>prompt coming soon</strong>
							<p>journal prompts are added from your active monthly drop.</p>
						<?php endif; ?>
					</article>
				</a>

					<section class="bbb-account-profile__section bbb-account-profile__section--quickLinks" aria-label="quick links" data-account-reveal>
						<div class="bbb-account-profile__sectionHeader">
							<h2>quick links</h2>
						</div>
						<div class="bbb-account-profile__quickLinksGrid">
							<a class="bbb-account-profile__quickLink" href="<?php echo esc_url($bookshelf_url); ?>">
								<span>bookshelf</span>
								<strong>saved books, statuses, and shelf edits</strong>
							</a>
							<a class="bbb-account-profile__quickLink" href="<?php echo esc_url($made_for_you_url); ?>">
								<span>made for you</span>
								<strong>reader profile, spice, and recommendation questions</strong>
							</a>
							<a class="bbb-account-profile__quickLink" href="<?php echo esc_url($monthly_drop_url); ?>">
								<span>monthly era</span>
								<strong>current drop, files, prompts, and designs</strong>
							</a>
							<a class="bbb-account-profile__quickLink" href="<?php echo esc_url($vault_url); ?>">
								<span>my vault</span>
								<strong>bybookishbabe vault downloads, inserts, templates, and extras</strong>
							</a>
							<a class="bbb-account-profile__quickLink" href="<?php echo esc_url($notes_url); ?>">
								<span>reading journal</span>
								<strong>notes, private thoughts, and read tracking</strong>
							</a>
							<a class="bbb-account-profile__quickLink" href="<?php echo esc_url(function_exists('bbb_page_url') ? bbb_page_url('library') : home_url('/library/')); ?>">
								<span>library</span>
								<strong>browse books and save your next read</strong>
							</a>
							<a class="bbb-account-profile__quickLink" href="<?php echo esc_url($shop_url); ?>">
								<span>shop</span>
								<strong>downloads, inserts, and bookish extras</strong>
							</a>
						</div>
					</section>

				<section class="bbb-account-profile__section bbb-account-profile__section--account" aria-label="account details" data-account-reveal>
					<div class="bbb-account-profile__sectionHeader">
						<h2>account details</h2>
					</div>
					<div class="bbb-account-shelf__panel">
						<div>
							<p class="bbb-account-shelf__perkKicker">kept out of the way</p>
							<h2><?php echo esc_html($display_name); ?></h2>
							<p><?php echo esc_html($reader_email); ?></p>
							<div class="bbb-account-home__utilityLinks">
								<?php if ($user instanceof WP_User && $user->ID) : ?>
									<a href="<?php echo esc_url(get_edit_user_link((int) $user->ID)); ?>">edit profile</a>
								<?php endif; ?>
								<a href="<?php echo esc_url(add_query_arg('bbb_reader_logout', '1', $account_url)); ?>">use different email</a>
							</div>
						</div>
						<?php if ($purchase_rows) : ?>
							<div class="bbb-account-shelf__purchaseList">
								<?php foreach ($purchase_rows as $purchase) : ?>
									<?php if ('' !== $purchase['url']) : ?>
										<a class="bbb-account-shelf__purchase" href="<?php echo esc_url($purchase['url']); ?>">
											<span>
												<strong><?php echo esc_html($purchase['title']); ?></strong>
												<small><?php echo esc_html($purchase['meta']); ?></small>
											</span>
											<em><?php echo esc_html($purchase['total']); ?></em>
										</a>
									<?php else : ?>
										<div class="bbb-account-shelf__purchase">
											<span>
												<strong><?php echo esc_html($purchase['title']); ?></strong>
												<small><?php echo esc_html($purchase['meta']); ?></small>
											</span>
											<em><?php echo esc_html($purchase['total']); ?></em>
										</div>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<div class="bbb-account-shelf__quiet">
								<strong>no purchases yet</strong>
								<span>your shop orders and digital drops will show here once they are tied to this email.</span>
								<a href="<?php echo esc_url($shop_url); ?>">browse the shop</a>
							</div>
						<?php endif; ?>
					</div>
				</section>

				<section class="bbb-account-profile__section bbb-account-profile__membershipPanel" aria-label="membership and preferences" data-account-reveal>
					<div class="bbb-account-profile__societyCard">
						<div>
							<h2>the smut &amp; sentiment society ✦</h2>
							<p><?php echo esc_html('society' === $tier ? 'your membership — active in your reader account' : 'your private layer — upgrade when you want the full weekly recs'); ?></p>
							<ul>
								<li>weekly curated romance letters, every sunday</li>
								<li>the full archive — all past newsletters</li>
								<li>monthly theme content: kindle inserts, wallpapers, playlists</li>
								<li>private polls and reader extras</li>
							</ul>
						</div>
						<div class="bbb-account-profile__societyActions">
							<a class="bbb-account-profile__societyButton" href="<?php echo esc_url($society_join_url); ?>" target="_blank" rel="noopener"><?php echo esc_html('society' === $tier ? 'manage membership' : 'enter the society'); ?></a>
							<a href="<?php echo esc_url($society_join_url); ?>" target="_blank" rel="noopener">billing &amp; substack -></a>
						</div>
					</div>

					<div class="bbb-account-profile__preferences">
						<h3>notification preferences</h3>
						<div class="bbb-account-profile__preferenceRow">
							<span>
								<strong>sunday newsletter</strong>
								<em>weekly romance recs to your inbox</em>
							</span>
							<button type="button" class="bbb-account-profile__toggle is-on" aria-pressed="true" aria-label="sunday newsletter on"><span></span></button>
						</div>
						<div class="bbb-account-profile__preferenceRow">
							<span>
								<strong>new book alerts</strong>
								<em>notify me when bbb books are reviewed</em>
							</span>
							<button type="button" class="bbb-account-profile__toggle is-on" aria-pressed="true" aria-label="new book alerts on"><span></span></button>
						</div>
						<div class="bbb-account-profile__preferenceRow">
							<span>
								<strong>weekly obsession email</strong>
								<em>the book taking over the society this week</em>
							</span>
							<button type="button" class="bbb-account-profile__toggle" aria-pressed="false" aria-label="weekly obsession email off"><span></span></button>
						</div>
					</div>
				</section>
			<?php else : ?>
				<div class="bbb-account-shelf__empty" id="reader-email-access">
					<div class="bbb-account-shelf__emptyIcon" aria-hidden="true">*</div>
					<h2>open your reader account.</h2>
					<p>use the same email you use for bybookishbabe or the smut & sentiment society.</p>
					<form class="bbb-account-shelf__emailForm" method="post" action="<?php echo esc_url($account_url); ?>" data-reader-email-access-form>
						<input type="hidden" name="bbb_reader_email_access" value="1">
						<label class="screen-reader-text" for="bbb-reader-email">reader email</label>
						<input id="bbb-reader-email" type="email" name="email" autocomplete="email" placeholder="you@example.com" required>
						<button type="submit">open account</button>
						<p class="bbb-account-shelf__formStatus" data-reader-email-access-status data-tone="<?php echo $reader_email_error ? esc_attr('error') : ($reader_logged_out ? esc_attr('success') : ''); ?>"<?php echo ($reader_logged_out || $reader_email_error) ? '' : ' hidden'; ?>><?php echo esc_html($reader_email_error ?: ($reader_logged_out ? 'you are logged out. enter the email you want to use next.' : '')); ?></p>
					</form>
				</div>
			<?php endif; ?>
			</div>
		</div>
	</section>
</main>
<script>
(function () {
	var readerTypes = <?php echo wp_json_encode(array_values($account_reader_types)); ?> || [];
	var mfyProfileVersion = <?php echo wp_json_encode(function_exists('bbb_reader_mfy_profile_version') ? bbb_reader_mfy_profile_version() : 'mfy-2026-06-11-reader-types'); ?>;
	var tropeLabels = {
		'slow burn': 'slow burn',
		'forced proximity': 'forced proximity',
		'enemies to lovers': 'enemies to lovers',
		'touch her and die': 'touch her and die',
		'why choose': 'why choose',
		'found family': 'found family',
		'second chance': 'second chance',
		'friends to lovers': 'friends to lovers',
		'forbidden romance': 'forbidden romance',
		'fake dating': 'fake dating'
	};
	var tropeProfiles = {
		'slow burn': {
			type: 'the slow ache romantic',
			flag: 'you call emotional restraint chemistry and you are not entirely wrong.'
		},
		'forced proximity': {
			type: 'the trapped-together theorist',
			flag: 'you see one shared room and immediately trust the process.'
		},
		'enemies to lovers': {
			type: 'the tension archivist',
			flag: 'you have mistaken a threat for flirting at least once.'
		},
		'touch her and die': {
			type: 'the devotion maximalist',
			flag: 'basic affection is fine, but you prefer it with consequences.'
		},
		'why choose': {
			type: 'the why choose witness',
			flag: 'you believe logistics are just another romance subplot.'
		},
		'found family': {
			type: 'the soft-place seeker',
			flag: 'you pretend you want plot, then fold for the first loyal group chat.'
		},
		'second chance': {
			type: 'the history keeper',
			flag: 'you are extremely vulnerable to unfinished business.'
		},
		'friends to lovers': {
			type: 'the quiet devotion reader',
			flag: 'you will wait three hundred pages for one almost-confession.'
		},
		'forbidden romance': {
			type: 'the bad-idea loyalist',
			flag: 'the word forbidden has never successfully warned you away.'
		},
		'fake dating': {
			type: 'the contract clause romantic',
			flag: 'you know the arrangement is fake and still pack emotionally.'
		}
	};
		var cravingProfiles = {
			comfort_devotion: 'comfort devotion',
			chaos_chemistry: 'chaos chemistry',
			dark_dangerous: 'dark dangerous',
		slow_ache: 'slow ache',
		messy_obsession: 'messy obsession',
		cozy: 'comfort devotion',
		spicy: 'chaos chemistry',
		dark: 'dark dangerous',
			slowburn: 'slow ache',
			surprise: 'curated chaos'
		};
		var accountMadeForYouProfile = <?php echo wp_json_encode($account_mfy_profile); ?> || {};
		var accountMadeForYouProfileIsComplete = <?php echo $account_mfy_profile_is_complete ? 'true' : 'false'; ?>;
		var accountStorageKey = <?php echo wp_json_encode($reader_account_key); ?> || '';
		function accountScopedStorageKey(key) {
			accountStorageKey = String(accountStorageKey || '').trim();
			return accountStorageKey ? key + '::' + accountStorageKey : key;
		}
		function getStoredJSON(key) {
			try {
				return JSON.parse(window.localStorage.getItem(key) || '{}') || {};
			} catch (error) {
				return {};
			}
		}
		function setStoredJSON(key, value) {
			try {
				window.localStorage.setItem(key, JSON.stringify(value || {}));
			} catch (error) {}
		}
		function clearStoredKey(key) {
			try {
				window.localStorage.removeItem(key);
			} catch (error) {}
		}
		function profileTime(profile) {
			var raw = profile && (profile.updatedAt || profile.updated_at || '');
			var time = raw ? Date.parse(String(raw)) : 0;
			return Number.isFinite(time) ? time : 0;
		}
		function isNewerProfile(candidate, current) {
			if (!candidate || typeof candidate !== 'object' || !Object.keys(candidate).length) {
				return false;
			}
			if (!current || typeof current !== 'object' || !Object.keys(current).length) {
				return true;
			}
			return profileTime(candidate) > profileTime(current);
		}
		function readStoredProfile() {
			var storageKey = accountScopedStorageKey('sssMadeForYouProfile');
			var profile = getStoredJSON(storageKey);
			var legacyProfile = accountStorageKey ? getStoredJSON('sssMadeForYouProfile') : {};

			if (accountStorageKey && isProfileComplete(legacyProfile) && isNewerProfile(legacyProfile, profile)) {
				profile = legacyProfile;
				setStoredJSON(storageKey, profile);
			}

			if (!isCurrentProfile(profile) || !isProfileComplete(profile)) {
				profile = {};
				clearStoredKey(storageKey);
				clearStoredKey(accountScopedStorageKey('bbbReaderTypeState'));
			}

			if (accountMadeForYouProfileIsComplete && isNewerProfile(accountMadeForYouProfile, profile)) {
				profile = accountMadeForYouProfile;
				setStoredJSON(storageKey, profile);
			}

			if (accountStorageKey) {
				clearStoredKey('sssMadeForYouProfile');
				clearStoredKey('bbbReaderTypeState');
			}

			return profile;
		}

	function normalizeTrope(value) {
		return String(value || '').trim().toLowerCase().replace(/[_-]+/g, ' ');
	}

	function fallbackTropes(card) {
		return String(card.getAttribute('data-fallback-tropes') || '')
			.split('|')
			.map(normalizeTrope)
			.filter(Boolean);
	}

	function isCurrentProfile(profile) {
		return !!(profile && String(profile.mfy_profile_version || profile.profile_version || '') === mfyProfileVersion);
	}

	function isProfileComplete(profile) {
		if (!isCurrentProfile(profile)) return false;
		var required = ['name', 'heat_lane', 'group_chat_text', 'love_interest', 'wall_line'];
		return required.every(function (key) {
			return String(profile && profile[key] || '').trim() !== '';
		}) && !!(profile && profile.dashboard_built);
	}

	function isProfileReady(profile) {
		return !!(isProfileComplete(profile) && (profile.reader_type_prior || profile.theme || profile.favorite_trope));
	}

	function getReaderType(key) {
		key = String(key || '').trim();
		return readerTypes.find(function (type) {
			return String(type && type.key || '') === key;
		}) || readerTypes.find(function (type) {
			return String(type && type.key || '') === 'romance_reader';
		}) || null;
	}

	function getQuizReaderTypeKey(profile) {
		var picks = [profile && profile.group_chat_text, profile && profile.love_interest, profile && profile.wall_line].filter(Boolean);
		if (picks.length < 3) return '';

		var counts = {};
		picks.forEach(function (key) {
			counts[key] = (counts[key] || 0) + 1;
		});

		var matched = Object.keys(counts).find(function (key) {
			return counts[key] >= 2;
		});
		if (matched) return matched;

		var order = [
			'sweet_romance_devotee',
			'slow_burn_girlie',
			'fake_dating_fanatic',
			'jersey_chaser',
			'fantasy_girlie',
			'tension_addict',
			'dark_romance_girlie',
			'chaos_reader'
		];
		var sorted = picks.slice().sort(function (a, b) {
			return order.indexOf(a) - order.indexOf(b);
		});
		var lane = String(profile && profile.heat_lane || '');

		if (lane === 'unhinged' && picks.indexOf('dark_romance_girlie') > -1 && picks.indexOf('chaos_reader') > -1) {
			return 'chaos_reader';
		}
		if (lane === 'closed') return sorted[0] || '';
		if (lane === 'open' || lane === 'unhinged') return sorted[sorted.length - 1] || '';
		return sorted[1] || sorted[0] || '';
	}

		function applyReaderTheme(card, readerType) {
			var theme = readerType && readerType.theme ? readerType.theme : {};
			var accent = theme.accent || '#D4C2CE';
			var border = theme.border || accent;
			var app = card.closest('.bbb-account-profile__app');
			card.setAttribute('data-reader-profile-theme', String(readerType && readerType.key || 'romance_reader'));
			card.style.setProperty('--reader-profile-accent', accent);
			card.style.setProperty('--reader-profile-accent-soft', 'color-mix(in srgb, ' + accent + ' 16%, transparent)');
			card.style.setProperty('--reader-profile-accent-border', 'color-mix(in srgb, ' + border + ' 42%, transparent)');
			card.style.setProperty('--reader-profile-panel', 'linear-gradient(135deg, color-mix(in srgb, ' + accent + ' 12%, transparent), rgba(255, 255, 255, 0.025))');
			if (app) {
				app.setAttribute('data-account-reader-theme', String(readerType && readerType.key || 'romance_reader'));
				app.style.setProperty('--account-reader-accent', accent);
				app.style.setProperty('--account-reader-accent-soft', 'color-mix(in srgb, ' + accent + ' 15%, transparent)');
				app.style.setProperty('--account-reader-accent-border', 'color-mix(in srgb, ' + border + ' 38%, transparent)');
			}
			if (readerType && readerType.emoji) {
				document.querySelectorAll('.bbb-account-welcome__emojiRain span').forEach(function (node) {
					node.textContent = '';
					var img = document.createElement('img');
					img.src = '/wp-content/themes/wordpress-theme/assets/images/custom-emojis/' + readerType.emoji + '.png';
					img.alt = '';
					img.loading = 'lazy';
					img.decoding = 'async';
					img.setAttribute('aria-hidden', 'true');
					node.appendChild(img);
				});
			}
		}

	function buildProfile(profile, card) {
		var favoriteTrope = normalizeTrope(profile.favorite_trope);
		var profileRule = tropeProfiles[favoriteTrope] || null;
		var readerType = getReaderType(profile.reader_type_prior || getQuizReaderTypeKey(profile));
		var fallback = fallbackTropes(card);
		var tropes = [favoriteTrope].concat(fallback).filter(Boolean).filter(function(item, index, list) {
			return list.indexOf(item) === index;
		}).slice(0, 5);
		var craving = cravingProfiles[profile.craving] || normalizeTrope(profile.craving);
		var type = readerType && readerType.label ? readerType.label : (profileRule ? profileRule.type : (craving ? craving + ' reader' : 'made for you reader'));
		var flag = readerType && readerType.bio ? readerType.bio : (profileRule ? profileRule.flag : 'your answers are specific enough to be a little suspicious.');

		return {
			type: type,
			flag: flag,
			tropes: tropes.length ? tropes : ['made for you'],
			theme: String(readerType && readerType.key || profile.theme || ''),
			emoji: String(readerType && readerType.emoji || 'found-family'),
			readerType: readerType
		};
	}

	function setActiveTrope(card, button) {
		var buttons = card.querySelectorAll('[data-reader-profile-trope]');
		var detail = card.querySelector('[data-reader-profile-detail-text]');
		buttons.forEach(function (item) {
			var isActive = item === button;
			item.classList.toggle('is-active', isActive);
			item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
		});

		if (detail) {
			detail.textContent = button.textContent.trim() + ' is ' + (button.getAttribute('data-reader-profile-detail') || 'part of your made for you profile.');
		}
	}

	function bindTropeButtons(card) {
		card.querySelectorAll('[data-reader-profile-trope]').forEach(function (button) {
			if (button.dataset.readerProfileBound === '1') return;
			button.dataset.readerProfileBound = '1';
			button.addEventListener('click', function () {
				setActiveTrope(card, button);
			});
			button.addEventListener('focus', function () {
				setActiveTrope(card, button);
			});
			button.addEventListener('mouseenter', function () {
				setActiveTrope(card, button);
			});
		});
	}

	function renderLockedProfile(card) {
		var cta = card.querySelector('[data-reader-profile-cta]');
		var status = card.querySelector('[data-reader-profile-status]');
		var emoji = card.querySelector('[data-reader-profile-emoji]');
		card.classList.add('is-locked');
		card.classList.remove('is-ready');
		card.removeAttribute('data-reader-profile-theme');
		if (cta) cta.textContent = 'get your reader type';
		if (status) status.textContent = 'made for you pending';
		if (emoji) emoji.style.opacity = '0.32';
	}

	function renderReadyProfile(card, built) {
		var typeEl = card.querySelector('[data-reader-profile-type]');
		var redFlagEl = card.querySelector('[data-reader-profile-red-flag]');
		var tropeWrap = card.querySelector('.bbb-account-welcome__tropeButtons');
		var detail = card.querySelector('[data-reader-profile-detail-text]');
		var cta = card.querySelector('[data-reader-profile-cta]');
		var status = card.querySelector('[data-reader-profile-status]');
		var emoji = card.querySelector('[data-reader-profile-emoji]');
		if (!typeEl || !redFlagEl || !tropeWrap || !detail) return;

		card.classList.remove('is-locked');
		card.classList.add('is-ready');
		if (built.readerType) {
			applyReaderTheme(card, built.readerType);
		} else {
			card.setAttribute('data-reader-profile-theme', built.theme);
		}
		typeEl.textContent = built.type;
		redFlagEl.textContent = built.flag;
		if (status) status.textContent = 'reader type unlocked';
		if (emoji && built.emoji) {
			emoji.src = '/wp-content/themes/wordpress-theme/assets/images/custom-emojis/' + built.emoji + '.png';
			emoji.style.opacity = '1';
		}
		tropeWrap.innerHTML = '';

		built.tropes.forEach(function (trope, index) {
			var label = tropeLabels[trope] || trope;
			var button = document.createElement('button');
			button.type = 'button';
			button.className = index === 0 ? 'is-active' : '';
			button.setAttribute('aria-pressed', index === 0 ? 'true' : 'false');
			button.setAttribute('data-reader-profile-trope', '');
			button.setAttribute('data-reader-profile-detail', index === 0 ? 'your lead made for you signal right now.' : 'part of the pattern your quiz and shelf are building.');
			button.textContent = label;
			tropeWrap.appendChild(button);
		});

		detail.textContent = (tropeLabels[built.tropes[0]] || built.tropes[0]) + ' is your lead made for you signal right now.';
			if (cta) cta.textContent = 'open made for you';
		bindTropeButtons(card);
	}

	var profileCards = document.querySelectorAll('[data-reader-profile-card]');
	var madeForYouProfile = readStoredProfile();
	if (accountMadeForYouProfileIsComplete && isNewerProfile(accountMadeForYouProfile, madeForYouProfile)) {
		madeForYouProfile = accountMadeForYouProfile;
	}
	profileCards.forEach(function (card) {
		if (!isProfileReady(madeForYouProfile)) {
			renderLockedProfile(card);
			bindTropeButtons(card);
			return;
		}

		renderReadyProfile(card, buildProfile(madeForYouProfile, card));
	});

	var items = document.querySelectorAll('[data-account-reveal]');
	if (!items.length) return;

	if (!('IntersectionObserver' in window)) {
		items.forEach(function (item) {
			item.classList.add('is-account-visible');
		});
		return;
	}

	var observer = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (!entry.isIntersecting) return;
			entry.target.classList.add('is-account-visible');
			observer.unobserve(entry.target);
		});
	}, { rootMargin: '0px 0px -12% 0px', threshold: 0.16 });

	items.forEach(function (item) {
		observer.observe(item);
	});
})();
</script>
<?php
get_footer();
