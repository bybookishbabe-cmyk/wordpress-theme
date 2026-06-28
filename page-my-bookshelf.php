<?php
/**
 * Template Name: My Bookshelf
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$my_bookshelf_css_path = get_theme_file_path('assets/css/my-bookshelf.css');
$my_bookshelf_js_path  = get_theme_file_path('assets/js/my-bookshelf.js');
$reader_identity = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
$reader_email    = $reader_identity ? (string) ($reader_identity['email'] ?? '') : '';
$reader_user_id  = $reader_identity ? (int) ($reader_identity['userId'] ?? 0) : 0;
$reader_account_key = $reader_user_id > 0
	? 'user-' . $reader_user_id
	: ('' !== $reader_email ? 'email-' . md5(strtolower($reader_email)) : '');
$reader_account_api = array(
	'endpoint'       => set_url_scheme(rest_url('bbb/v1/reader-account'), is_ssl() ? 'https' : 'http'),
	'shelfEndpoint'  => set_url_scheme(rest_url('bbb/v1/reader-account/shelf'), is_ssl() ? 'https' : 'http'),
	'spiceEndpoint'  => set_url_scheme(rest_url('bbb/v1/reader-account/spice-profile'), is_ssl() ? 'https' : 'http'),
	'notesEndpoint'  => set_url_scheme(rest_url('bbb/v1/reader-account/notes'), is_ssl() ? 'https' : 'http'),
	'hasNotesAccess' => function_exists('bbb_reader_can_use_notes') ? bbb_reader_can_use_notes() : (bool) $reader_identity,
	'accountKey'     => $reader_account_key,
	'nonce'          => wp_create_nonce('wp_rest'),
);
$my_bookshelf_asset_version = 'bookshelf-journal-202606240620';
wp_enqueue_style('bbb-my-bookshelf', get_theme_file_uri('assets/css/my-bookshelf.css'), array('bbb-sss-library'), $my_bookshelf_asset_version);
wp_enqueue_style('bbb-my-bookshelf-live-fix', get_theme_file_uri('assets/css/my-bookshelf-live-fix.css'), array('bbb-my-bookshelf'), $my_bookshelf_asset_version);
wp_enqueue_script('bbb-my-bookshelf', get_theme_file_uri('assets/js/my-bookshelf.js'), array('bbb-sss-library', 'bbb-supabase'), $my_bookshelf_asset_version, true);
wp_localize_script('bbb-sss-library', 'BBBReaderAccountApi', $reader_account_api);
wp_localize_script(
	'bbb-my-bookshelf',
	'BBBReaderAccountApi',
	$reader_account_api
);

$has_reader_access = '' !== $reader_email;
$is_society = function_exists('bbb_reader_is_society') ? bbb_reader_is_society() : false;
$books      = function_exists('bbb_reader_quiz_books') ? bbb_reader_quiz_books() : array();
$account_data = array();

if ($has_reader_access && function_exists('bbb_reader_account_response_for_identity')) {
	try {
		$account_data = bbb_reader_account_response_for_identity((array) $reader_identity);
	} catch (Throwable $error) {
		error_log('BBB bookshelf page failed softly: ' . $error->getMessage());
		$account_data = array(
			'accessTier' => 'free',
			'books'      => array(),
			'readerType' => array(
				'title'     => 'fresh shelf romantic',
				'summary'   => 'your bookshelf opened, but the account sync needs a retry.',
				'topTropes' => array(),
				'counts'    => array('saved' => 0, 'read' => 0, 'reading' => 0, 'tbr' => 0),
			),
			'nextRead'   => null,
		);
	}
}
$reader_type = isset($account_data['readerType']) && is_array($account_data['readerType']) ? $account_data['readerType'] : array(
	'title'     => 'fresh shelf romantic',
	'summary'   => 'save or tag a few books and this will start calling your pattern.',
	'topTropes' => array(),
	'counts'    => array('saved' => 0, 'read' => 0, 'reading' => 0, 'tbr' => 0),
);

if (!function_exists('bbb_my_bookshelf_mfy_reader_type_key')) {
	function bbb_my_bookshelf_mfy_reader_type_key(array $profile): string {
		$direct = sanitize_key((string) ($profile['reader_type_prior'] ?? $profile['theme'] ?? ''));
		if ('' !== $direct) {
			return $direct;
		}

		$picks = array_values(
			array_filter(
				array_map(
					'sanitize_key',
					array(
						(string) ($profile['group_chat_text'] ?? ''),
						(string) ($profile['love_interest'] ?? ''),
						(string) ($profile['wall_line'] ?? ''),
					)
				)
			)
		);
		if (count($picks) < 3) {
			return '';
		}

		$counts = array_count_values($picks);
		foreach ($counts as $key => $count) {
			if ($count >= 2) {
				return (string) $key;
			}
		}

		$order = array(
			'sweet_romance_devotee',
			'slow_burn_girlie',
			'fake_dating_fanatic',
			'jersey_chaser',
			'fantasy_girlie',
			'tension_addict',
			'dark_romance_girlie',
			'chaos_reader',
		);
		usort(
			$picks,
			static function (string $a, string $b) use ($order): int {
				$a_index = array_search($a, $order, true);
				$b_index = array_search($b, $order, true);
				return (false === $a_index ? 99 : (int) $a_index) <=> (false === $b_index ? 99 : (int) $b_index);
			}
		);

		$lane = sanitize_key((string) ($profile['heat_lane'] ?? ''));
		if ('unhinged' === $lane && in_array('dark_romance_girlie', $picks, true) && in_array('chaos_reader', $picks, true)) {
			return 'chaos_reader';
		}
		if ('closed' === $lane) {
			return (string) ($picks[0] ?? '');
		}
		if ('open' === $lane || 'unhinged' === $lane) {
			return (string) ($picks[count($picks) - 1] ?? '');
		}

		return (string) ($picks[1] ?? $picks[0] ?? '');
	}
}

$made_for_you_profile = isset($account_data['madeForYouProfile']) && is_array($account_data['madeForYouProfile']) ? $account_data['madeForYouProfile'] : array();
$reader_type_key = '';
if (
	$made_for_you_profile
	&& function_exists('bbb_reader_mfy_profile_is_complete')
	&& bbb_reader_mfy_profile_is_complete($made_for_you_profile)
	&& function_exists('bbb_reader_type_by_key')
) {
	$reader_type_key = bbb_my_bookshelf_mfy_reader_type_key($made_for_you_profile);
	$made_for_you_reader_type = bbb_reader_type_by_key($reader_type_key);
	if (is_array($made_for_you_reader_type)) {
		$favorite_trope = trim((string) ($made_for_you_profile['favorite_trope'] ?? ''));
		$reader_type['title'] = (string) ($made_for_you_reader_type['label'] ?? $reader_type['title'] ?? 'made for you reader');
		$reader_type['summary'] = (string) ($made_for_you_reader_type['bio'] ?? $reader_type['summary'] ?? '');
		$reader_type['redFlag'] = (string) ($made_for_you_reader_type['bio'] ?? $reader_type['redFlag'] ?? '');
		if ('' !== $favorite_trope) {
			$reader_type['topTropes'] = array_values(
				array_unique(
					array_filter(
						array_merge(array(str_replace('-', ' ', $favorite_trope)), (array) ($reader_type['topTropes'] ?? array()))
					)
				)
			);
		}
	}
}
$reader_type_registry = function_exists('bbb_reader_type_registry') ? array_values(bbb_reader_type_registry()) : array();
if ('' === $reader_type_key && $reader_type_registry) {
	foreach ($reader_type_registry as $registered_reader_type) {
		if (strtolower(trim((string) ($registered_reader_type['label'] ?? ''))) === strtolower(trim((string) ($reader_type['title'] ?? '')))) {
			$reader_type_key = (string) ($registered_reader_type['key'] ?? '');
			break;
		}
	}
}
$reader_type_key = '' !== $reader_type_key ? $reader_type_key : 'romance_reader';
$reader_type_registry_item = function_exists('bbb_reader_type_by_key') ? bbb_reader_type_by_key($reader_type_key) : null;
$reader_type_theme = is_array($reader_type_registry_item) && is_array($reader_type_registry_item['theme'] ?? null) ? $reader_type_registry_item['theme'] : array();
$reader_type_accent = (string) ($reader_type_theme['accent'] ?? '#D4C2CE');
$reader_type_border = (string) ($reader_type_theme['border'] ?? $reader_type_accent);
$reader_type_emoji_key = is_array($reader_type_registry_item) ? (string) ($reader_type_registry_item['emoji'] ?? 'found-family') : 'found-family';
$reader_type_emoji_url = function_exists('bbb_custom_emoji_url') ? bbb_custom_emoji_url($reader_type_emoji_key) : '';
$notes_url = function_exists('bbb_page_url') ? bbb_page_url('my-notes') : home_url('/my-notes/');
$account_url = function_exists('bbb_page_url') ? bbb_page_url('account') : home_url('/account/');
$made_for_you_url = function_exists('bbb_page_url') ? bbb_page_url('made-for-you') : home_url('/made-for-you/');
$reader_logged_out = isset($_GET['bbb_reader_logged_out']);
$reader_email_error = isset($_GET['reader_email_error']) ? sanitize_text_field((string) wp_unslash($_GET['reader_email_error'])) : '';
	$reader_type_title = trim((string) ($reader_type['title'] ?? 'fresh shelf romantic'));
$reader_type_summary = trim((string) ($reader_type['summary'] ?? 'save or tag a few books and this will start calling your pattern.'));
$reader_type_red_flag = trim((string) ($reader_type['redFlag'] ?? ''));
$reader_type_counts = is_array($reader_type['counts'] ?? null) ? $reader_type['counts'] : array();
$reader_type_tropes = is_array($reader_type['topTropes'] ?? null) ? array_values(array_filter($reader_type['topTropes'])) : array();
$spice_profile = function_exists('bbb_reader_spice_profile_for_identity')
	? bbb_reader_spice_profile_for_identity((array) ($reader_identity ?: array()))
	: array('level' => 0, 'label' => '', 'peppers' => '', 'description' => '');
$spice_profile_level = (int) ($spice_profile['level'] ?? 0);
$spice_profiles = function_exists('bbb_reader_spice_profiles') ? bbb_reader_spice_profiles() : array();

if (!function_exists('bbb_my_bookshelf_quote_text')) {
	function bbb_my_bookshelf_quote_text(WP_Post $quote): string {
		$text = trim((string) get_post_meta($quote->ID, '_quote_text', true));
		$text = '' !== $text ? $text : trim((string) get_post_meta($quote->ID, 'quote_text', true));
		$text = '' !== $text ? $text : trim((string) get_post_meta($quote->ID, 'quote', true));
		$text = '' !== $text ? $text : trim((string) get_post_meta($quote->ID, '_bbb_quote', true));
		$text = '' !== $text ? $text : trim(wp_strip_all_tags($quote->post_content));

		return wp_strip_all_tags($text);
	}
}

if (!function_exists('bbb_my_bookshelf_quote_entries')) {
	function bbb_my_bookshelf_decode_text(string $value): string {
		$decoded = $value;
		for ($i = 0; $i < 3; $i++) {
			$next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			if ($next === $decoded) {
				break;
			}
			$decoded = $next;
		}

		return $decoded;
	}

	function bbb_my_bookshelf_quote_book_can_surface(WP_Post $quote): bool {
		$book_id = max(
			(int) get_post_meta($quote->ID, '_quote_book_id', true),
			(int) get_post_meta($quote->ID, '_quote_library_book_id', true),
			(int) get_post_meta($quote->ID, 'book_id', true),
			(int) get_post_meta($quote->ID, 'library_book_id', true)
		);

		$book = $book_id > 0 ? get_post($book_id) : null;
		if (!$book instanceof WP_Post) {
			$book_handle = (string) get_post_meta($quote->ID, '_quote_book_handle', true);
			$book_handle = '' !== $book_handle ? $book_handle : (string) get_post_meta($quote->ID, 'book_handle', true);
			$book_handle = '' !== $book_handle ? $book_handle : (string) get_post_meta($quote->ID, '_bbb_book_handle', true);
			if ('' !== $book_handle) {
				foreach (array('bbb_book', 'sss_book') as $post_type) {
					$found = get_page_by_path($book_handle, OBJECT, $post_type);
					if ($found instanceof WP_Post) {
						$book = $found;
						break;
					}
				}
			}
		}

		if (!$book instanceof WP_Post) {
			return true;
		}
		if ('publish' !== get_post_status($book)) {
			return false;
		}
		if (function_exists('bbb_book_is_publicly_visible') && !bbb_book_is_publicly_visible((int) $book->ID)) {
			return false;
		}
		if (function_exists('bbb_book_is_hidden') && bbb_book_is_hidden((int) $book->ID)) {
			return false;
		}

		return true;
	}

	function bbb_my_bookshelf_quote_entries(int $limit = 16): array {
		$entries          = array();
		$quote_post_types = function_exists('bbb_quote_post_types') ? bbb_quote_post_types() : array();
		$quotes           = $quote_post_types
			? get_posts(
				array(
					'post_type'      => $quote_post_types,
					'post_status'    => 'publish',
					'posts_per_page' => max(75, $limit),
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			)
			: array();

		foreach ($quotes as $quote) {
			if (!$quote instanceof WP_Post) {
				continue;
			}

			$text = bbb_my_bookshelf_quote_text($quote);
			if ('' === $text) {
				continue;
			}
			if (!bbb_my_bookshelf_quote_book_can_surface($quote)) {
				continue;
			}

			$book_title  = (string) get_post_meta($quote->ID, '_quote_book_title', true);
			$book_title  = '' !== $book_title ? $book_title : (string) get_post_meta($quote->ID, 'book_title', true);
			$book_title  = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($book_title) : $book_title;
			$book_title  = bbb_my_bookshelf_decode_text($book_title);
			$book_handle = (string) get_post_meta($quote->ID, '_quote_book_handle', true);
			$book_handle = '' !== $book_handle ? $book_handle : (string) get_post_meta($quote->ID, 'book_handle', true);
			$book_handle = '' !== $book_handle ? $book_handle : (string) get_post_meta($quote->ID, '_bbb_book_handle', true);

			$entries[] = array(
				'text'        => bbb_my_bookshelf_decode_text($text),
				'book_title'  => $book_title,
				'book_handle' => $book_handle,
			);
		}

		if (!$entries && function_exists('bbb_quote_export_entries')) {
			$entries = bbb_quote_export_entries($limit);
		}

		return array_slice($entries, 0, $limit);
	}
}

$quotes = bbb_my_bookshelf_quote_entries();

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section
		class="bbb-account-shelf<?php echo $has_reader_access ? '' : ' bbb-account-shelf--locked'; ?>"
		data-account-shelf
		data-sss-lib="<?php echo esc_attr($is_society ? 'society' : 'public'); ?>"
		data-logged-in="<?php echo esc_attr($has_reader_access ? 'true' : 'false'); ?>"
		data-customer-id="<?php echo esc_attr($reader_user_id ? (string) $reader_user_id : ''); ?>"
		data-customer-email="<?php echo esc_attr($reader_email); ?>"
		data-account-key="<?php echo esc_attr($reader_account_key); ?>"
		data-is-society="<?php echo esc_attr($is_society ? 'true' : 'false'); ?>"
		data-mfy-profile-version="<?php echo esc_attr(function_exists('bbb_reader_mfy_profile_version') ? bbb_reader_mfy_profile_version() : 'mfy-2026-06-11-reader-types'); ?>"
	>
		<div class="bbb-account-shelf__wrap">
			<div class="bbb-account-shelf__hero">
				<div class="bbb-account-shelf__memberBadge<?php echo $is_society ? ' bbb-account-shelf__memberBadge--secret' : ''; ?>" data-account-shelf-badge>
					<span aria-hidden="true"><?php echo esc_html($is_society ? '♥' : '*'); ?></span>
					<span data-account-shelf-badge-label><?php echo esc_html($has_reader_access ? ($is_society ? 'secret society member' : 'free reader') : 'society account required'); ?></span>
				</div>
				<h1 class="bbb-account-shelf__title">my bookshelf</h1>
				<p class="bbb-account-shelf__sub"><?php echo esc_html($has_reader_access ? 'saved books, current obsessions, and the beginning of your personal romance archive.' : 'log in with your society account to see your saved books, ratings, and reader profile.'); ?></p>

				<?php if ($has_reader_access) : ?>
					<div class="bbb-account-shelf__actions">
						<a class="bbb-account-shelf__button" href="<?php echo esc_url(bbb_page_url('library')); ?>">browse the library</a>
						<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url($notes_url); ?>">reading journal</a>
						<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url($account_url); ?>">account</a>
					</div>
				<?php endif; ?>
			</div>

			<?php if (!$has_reader_access) : ?>
				<div class="bbb-account-shelf__loginGate" id="reader-email-access">
					<div class="bbb-account-shelf__loginGateIcon" aria-hidden="true">*</div>
					<h2>log in with your society account.</h2>
					<p>your bookshelf is private to your member login. enter the email you use for bybookishbabe or the smut &amp; sentiment society to open your saved shelf.</p>
					<form class="bbb-account-shelf__emailForm" method="post" action="<?php echo esc_url($account_url); ?>" data-reader-email-access-form>
						<input type="hidden" name="bbb_reader_email_access" value="1">
						<label class="screen-reader-text" for="bbb-reader-email">reader email</label>
						<input id="bbb-reader-email" type="email" name="email" autocomplete="email" placeholder="you@example.com" required>
						<button type="submit">open my bookshelf</button>
						<p class="bbb-account-shelf__formStatus" data-reader-email-access-status data-tone="<?php echo $reader_email_error ? esc_attr('error') : ($reader_logged_out ? esc_attr('success') : ''); ?>"<?php echo ($reader_logged_out || $reader_email_error) ? '' : ' hidden'; ?>><?php echo esc_html($reader_email_error ?: ($reader_logged_out ? 'you are logged out. enter the email you want to use next.' : '')); ?></p>
					</form>
				</div>
				<?php else : ?>
				<section class="bbb-account-shelf__snapshot" aria-label="reader snapshot">
					<div class="bbb-account-shelf__snapshotMain">
						<p class="bbb-account-shelf__toolbarKicker">reader snapshot</p>
						<div class="bbb-account-shelf__snapshotRows">
							<div class="bbb-account-shelf__snapshotRow">
								<span class="bbb-account-shelf__snapshotIcon" aria-hidden="true">
									<?php if ($reader_type_emoji_url) : ?>
										<img src="<?php echo esc_url($reader_type_emoji_url); ?>" alt="" loading="lazy" decoding="async" data-snapshot-reader-emoji>
									<?php else : ?>
										<span data-snapshot-reader-emoji>☆</span>
									<?php endif; ?>
								</span>
								<span>
									<strong data-snapshot-reader-type><?php echo esc_html($reader_type_title); ?></strong>
									<em>reader type</em>
								</span>
							</div>
							<div class="bbb-account-shelf__snapshotRow">
								<span class="bbb-account-shelf__snapshotIcon bbb-account-shelf__snapshotIcon--text" aria-hidden="true" data-snapshot-spice-peppers><?php echo esc_html($spice_profile_level ? (string) ($spice_profile['peppers'] ?? '') : '🌶'); ?></span>
								<span>
									<strong data-snapshot-spice><?php echo esc_html($spice_profile_level ? (string) ($spice_profile['label'] ?? 'spice profile') : 'spice profile not set'); ?></strong>
									<em>spice profile</em>
								</span>
							</div>
						</div>
					</div>
					<div class="bbb-account-shelf__snapshotStats">
						<span><strong data-snapshot-saved>0</strong> books saved</span>
						<span><strong data-snapshot-finished>0</strong> books finished</span>
						<span><strong data-snapshot-reading>0</strong> currently reading</span>
					</div>
				</section>

				<section class="bbb-account-shelf__current" data-account-current-reading hidden aria-label="currently reading">
					<div class="bbb-account-shelf__currentHead">
						<div>
							<p class="bbb-account-shelf__toolbarKicker">currently reading</p>
							<h2>currently reading</h2>
						</div>
						<a href="<?php echo esc_url($notes_url); ?>">my notes</a>
					</div>
					<div class="bbb-account-shelf__currentBody" data-account-current-reading-body></div>
				</section>

				<?php if ($has_reader_access) : ?>
					<section
						class="bbb-account-welcome__readerProfile bbb-account-shelf__readerProfile bbb-account-shelf__readerProfile--bookshelf is-ready"
						aria-label="reader type"
						data-bookshelf-reader-profile
						data-reader-profile-theme="<?php echo esc_attr($reader_type_key); ?>"
						style="<?php echo esc_attr('--reader-profile-accent:' . $reader_type_accent . ';--reader-profile-accent-soft:color-mix(in srgb, ' . $reader_type_accent . ' 16%, transparent);--reader-profile-accent-border:color-mix(in srgb, ' . $reader_type_border . ' 42%, transparent);--reader-profile-panel:linear-gradient(135deg, color-mix(in srgb, ' . $reader_type_accent . ' 12%, transparent), rgba(255, 255, 255, 0.025));'); ?>"
					>
						<div class="bbb-account-welcome__readerProfileHead">
							<a class="bbb-account-welcome__readerTypeBadge" href="<?php echo esc_url($made_for_you_url); ?>" aria-label="<?php echo esc_attr('open made for you dashboard for ' . $reader_type_title); ?>">
								<span class="bbb-account-welcome__readerTypeIcon" aria-hidden="true">
									<?php if ($reader_type_emoji_url) : ?>
										<img src="<?php echo esc_url($reader_type_emoji_url); ?>" alt="" loading="lazy" decoding="async" data-bookshelf-reader-type-emoji>
									<?php else : ?>
										<span data-bookshelf-reader-type-emoji>♥</span>
									<?php endif; ?>
								</span>
								<div>
									<span>reader type</span>
									<strong data-bookshelf-reader-type-title><?php echo esc_html($reader_type_title); ?></strong>
								</div>
							</a>
						</div>
					</section>
				<?php endif; ?>

				<div class="bbb-account-shelf__syncSpiceRow">
					<div class="bbb-account-shelf__status" data-account-shelf-status>
						<div class="bbb-account-shelf__statusMain">
							<span class="bbb-account-shelf__statusIcon" aria-hidden="true">*</span>
							<div>
								<strong>syncing your shelf...</strong>
								<span data-account-shelf-status-copy>your saved books and ratings are loading for this account.</span>
							</div>
						</div>
						<div class="bbb-account-shelf__tools" data-account-shelf-tools hidden>
							<button type="button" data-account-copy>copy list</button>
							<button type="button" data-account-email>email list</button>
						</div>
					</div>

				<section
					class="bbb-account-shelf__spiceProfile"
				aria-label="spice profile"
				data-spice-profile
				data-initial-level="<?php echo esc_attr((string) $spice_profile_level); ?>"
			>
				<div>
					<p class="bbb-account-shelf__perkKicker">spice profile</p>
					<h2 data-spice-profile-title><?php echo esc_html($spice_profile_level ? (string) ($spice_profile['label'] ?? '') : 'pick your preferred heat'); ?></h2>
					<p data-spice-profile-copy><?php echo esc_html($spice_profile_level ? (string) ($spice_profile['description'] ?? '') : 'choose the spice level you want your shelf and future recs to align with.'); ?></p>
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
				</div>

				<div class="bbb-account-shelf__feature" data-account-read-feature hidden>
				<div class="bbb-account-shelf__readShelf">
					<div class="bbb-account-shelf__featureHead">
						<p class="bbb-account-shelf__featureKicker">marked as read</p>
						<h2>your finished shelf</h2>
						<p data-account-read-copy>covers you have marked as read will stack here, face-out like a private trophy shelf.</p>
					</div>
					<div class="bbb-account-shelf__rail" aria-hidden="true"></div>
					<div class="bbb-account-shelf__coverStage" data-account-read-covers></div>
				</div>
				<a class="bbb-account-shelf__quoteCard" href="<?php echo esc_url(bbb_page_url('quote-wall')); ?>" data-account-quote-card>
					<p class="bbb-account-shelf__featureKicker">pulled from the quote wall</p>
					<blockquote data-account-quote-text>mark a few books as read and a related quote can find you here.</blockquote>
					<span data-account-quote-source>visit the quote wall →</span>
				</a>
			</div>

			<section class="bbb-account-shelf__ratings" data-account-ratings hidden aria-label="your rated books">
				<div class="bbb-account-shelf__ratingsHead">
					<div>
						<p class="bbb-account-shelf__toolbarKicker">my ratings</p>
						<h2>rated books</h2>
					</div>
					<span data-account-ratings-count>0 rated</span>
				</div>
				<div class="bbb-account-shelf__ratingsGrid" data-account-ratings-grid></div>
				<div class="bbb-account-shelf__ratingsEmpty" data-account-ratings-empty hidden>
					rate a book from its detail card and it will collect here.
				</div>
			</section>

			<div class="bbb-account-shelf__lanes" data-account-shelf-lanes>
				<article class="bbb-account-shelf__lane bbb-account-shelf__lane--read" data-account-status-lane="read">
					<div class="bbb-account-shelf__laneHead">
						<p>finished</p>
						<span data-account-status-count="read">0 books</span>
					</div>
					<div class="bbb-account-shelf__laneBooks" data-account-status-books="read"></div>
				</article>
				<article class="bbb-account-shelf__lane bbb-account-shelf__lane--reading" data-account-status-lane="reading">
					<div class="bbb-account-shelf__laneHead">
						<p>reading now</p>
						<span data-account-status-count="reading">0 books</span>
					</div>
					<div class="bbb-account-shelf__laneBooks" data-account-status-books="reading"></div>
				</article>
				<article class="bbb-account-shelf__lane bbb-account-shelf__lane--tbr" data-account-status-lane="tbr">
					<div class="bbb-account-shelf__laneHead">
						<p>on the tbr</p>
						<span data-account-status-count="tbr">0 books</span>
					</div>
					<div class="bbb-account-shelf__laneBooks" data-account-status-books="tbr"></div>
				</article>
			</div>

				<div class="bbb-account-shelf__sectionHead">
				<div>
					<p class="bbb-account-shelf__toolbarKicker">saved shelf</p>
					<h2>all saved books</h2>
				</div>
				<a class="bbb-account-shelf__notesCta" href="<?php echo esc_url($notes_url); ?>">my notes</a>
			</div>
			<div class="bbb-account-shelf__shelfControls" aria-label="shelf controls">
				<div class="bbb-account-shelf__tabs" role="tablist" aria-label="bookshelf filters" data-account-shelf-tabs>
					<button type="button" class="is-active" data-shelf-tab="all" aria-pressed="true">all</button>
					<button type="button" data-shelf-tab="reading" aria-pressed="false">reading</button>
					<button type="button" data-shelf-tab="read" aria-pressed="false">finished</button>
					<button type="button" data-shelf-tab="tbr" aria-pressed="false">tbr</button>
					<button type="button" data-shelf-tab="dnf" aria-pressed="false">dnf</button>
				</div>
				<div class="bbb-account-shelf__shelfTools">
					<label>
						<span class="screen-reader-text">search my books</span>
						<input type="search" placeholder="search my books" data-shelf-search>
					</label>
					<label>
						<span class="screen-reader-text">sort shelf</span>
						<select data-shelf-sort>
							<option value="recent">recently added</option>
							<option value="title">title</option>
							<option value="rating">rating</option>
							<option value="spice">spice</option>
						</select>
					</label>
				</div>
			</div>
			<span class="screen-reader-text" data-reader-note-toggle hidden aria-hidden="true"></span>
			<div class="bbb-account-shelf__grid" data-account-shelf-grid></div>

			<div class="bbb-account-shelf__empty" data-account-shelf-empty hidden>
				<div class="bbb-account-shelf__emptyIcon" aria-hidden="true">📖</div>
				<h2>your shelf is waiting.</h2>
				<p>save a few books from the library and they’ll collect here.</p>
				<a href="<?php echo esc_url(home_url('/library/')); ?>">find your first save →</a>
			</div>

			<script type="application/json" data-account-library-books><?php echo wp_json_encode($books, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
			<script type="application/json" data-account-library-quotes><?php echo wp_json_encode($quotes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
			<script type="application/json" data-account-reader-types><?php echo wp_json_encode(function_exists('bbb_reader_type_registry') ? array_values(bbb_reader_type_registry()) : array(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
			<script type="application/json" data-account-made-for-you-profile><?php echo wp_json_encode($made_for_you_profile, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
			<?php endif; ?>
		</div>
	</section>
	<?php bbb_render_component('library-modal'); ?>
</main>

<?php
get_footer();
