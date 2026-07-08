<?php
/**
 * Template Name: Monthly ByBookishBabe Romance Theme
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$request_path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$is_staging_route = 'monthly-bybookishbabe-romance-theme' === $request_path;
$is_archive_route = function_exists('bbb_is_monthly_theme_archive_route') && bbb_is_monthly_theme_archive_route($request_path);
$theme_is_released = function_exists('bbb_july_2026_monthly_theme_is_released') && bbb_july_2026_monthly_theme_is_released();

if (!$is_archive_route && !current_user_can('manage_options')) {
	status_header(403);
	nocache_headers();
	wp_die(
		esc_html__('This monthly theme is in private staging until release.', 'bybookishbabe-shopify-port'),
		esc_html__('Private monthly theme', 'bybookishbabe-shopify-port'),
		array('response' => 403)
	);
}

add_filter(
	'wp_robots',
	static function (array $robots) use ($is_archive_route): array {
		if ($is_archive_route) {
			unset($robots['noindex'], $robots['nofollow']);
			$robots['index']  = true;
			$robots['follow'] = true;
			return $robots;
		}

		$robots['noindex'] = true;
		$robots['nofollow'] = true;
		return $robots;
	}
);

if (!function_exists('bbb_monthly_theme_has_private_cache_context')) {
	function bbb_monthly_theme_has_private_cache_context(): bool {
		if (is_user_logged_in()) {
			return true;
		}

		foreach (array_keys($_COOKIE) as $cookie_name) {
			$cookie_name = strtolower((string) $cookie_name);
			if (
				str_contains($cookie_name, 'wordpress_logged_in') ||
				str_contains($cookie_name, 'bbb_reader') ||
				str_contains($cookie_name, 'substack') ||
				str_starts_with($cookie_name, 'edd_') ||
				str_contains($cookie_name, 'cart') ||
				str_contains($cookie_name, 'checkout')
			) {
				return true;
			}
		}

		return false;
	}
}

if ($is_archive_route) {
	$bbb_monthly_private_cache = 'no-store, no-cache, must-revalidate, max-age=0, private';
	add_filter(
		'nocache_headers',
		static function (array $headers) use ($bbb_monthly_private_cache): array {
			return array(
				'Cache-Control' => $bbb_monthly_private_cache,
				'Expires'       => 'Wed, 11 Jan 1984 05:00:00 GMT',
			);
		},
		100
	);

	if (!headers_sent()) {
		header_remove('Pragma');
		header_remove('Expires');
		header('Cache-Control: ' . $bbb_monthly_private_cache, true);
		header('Expires: Wed, 11 Jan 1984 05:00:00 GMT', true);
	}
}

wp_enqueue_style('bbb-font-midnight-summer', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap', array(), null);
bbb_enqueue_css('bbb-july-2026-monthly-theme', 'assets/css/july-2026-monthly-theme.css', array('bbb-font-midnight-summer'));
bbb_enqueue_js('bbb-july-2026-monthly-theme', 'assets/js/july-2026-monthly-theme.js', array(), true);

$asset_base = 'assets/monthly-themes/july-2026';
$is_paid_society_member = function_exists('bbb_reader_is_society') && bbb_reader_is_society();
$has_reader_identity = function_exists('bbb_reader_current_identity') && (bool) bbb_reader_current_identity();
$has_theme_access = ($is_staging_route && current_user_can('manage_options')) || ($is_paid_society_member && $theme_is_released);
$has_wallpaper_access = ($is_staging_route && current_user_can('manage_options')) || ($has_reader_identity && $theme_is_released);
$has_freebie_access = $has_wallpaper_access;
$join_url = function_exists('bbb_substack_subscribe_url') ? bbb_substack_subscribe_url() : 'https://thesmutandsentimentsociety.substack.com/subscribe';
$locked_url = $is_paid_society_member ? '#kindle-downloads' : $join_url;
$locked_link_attrs = $is_paid_society_member ? '' : ' target="_blank" rel="noopener"';
$calendar_pdf_path = $asset_base . '/downloads/July2026_Calendar.pdf';
$calendar_pdf_url = function_exists('bbb_forced_theme_asset_download_url')
	? bbb_forced_theme_asset_download_url($calendar_pdf_path, 'July2026_Calendar.pdf')
	: get_theme_file_uri($calendar_pdf_path);
$calendar_preview_url = get_theme_file_uri($asset_base . '/display/july-2026-calendar-preview.jpg');
$review_template_canva_url = 'https://canva.link/eh9ufy2nn6175r7';
$review_template_pdf_path = $asset_base . '/templates/midnight-summer-book-review-canva-link.pdf';
$review_template_pdf_url = function_exists('bbb_forced_theme_asset_download_url')
	? bbb_forced_theme_asset_download_url($review_template_pdf_path, 'MidnightSummerBookReviewCanvaLink.pdf')
	: get_theme_file_uri($review_template_pdf_path);
$review_template_mockup_url = get_theme_file_uri($asset_base . '/display/midnight-summer-book-review-mockup.jpg');
$bookmarks_pdf_path = $asset_base . '/freebies/midnight-summer-bookmarks.pdf';
$bookmarks_pdf_url = function_exists('bbb_forced_theme_asset_download_url')
	? bbb_forced_theme_asset_download_url($bookmarks_pdf_path, 'MidnightSummer_Bookmarks.pdf')
	: get_theme_file_uri($bookmarks_pdf_path);
$bookmarks_mockup_url = get_theme_file_uri($asset_base . '/display/midnight-summer-bookmark-mockup.jpg');

$inserts = array(
	array(
		'class'   => 'swim',
		'num'     => '01',
		'world'   => 'moonlit water',
		'name'    => 'midnight swim',
		'slogan'  => 'meet me where the dark turns blue',
		'desc'    => 'ocean nights, beach notes, wet hair, and the feeling of sneaking out after everyone else is asleep.',
		'vibe'    => array('salt air', 'blue hour', 'secret beach', 'summer trouble'),
		'wallpaper_target' => 'wallpaper-swim',
		'download_target'  => 'download-swim',
		'preview' => 'assets/monthly-themes/july-2026/display/midnight-swim-insert-preview.jpg',
		'alt'     => 'Midnight Swim kindle insert preview collage',
		'bottom'  => 'bybookishbabe / july 2026',
	),
	array(
		'class'   => 'movie',
		'num'     => '02',
		'world'   => 'late show',
		'name'    => 'midnight movie',
		'slogan'  => 'the credits roll but nobody leaves',
		'desc'    => 'neon cinema signs, popcorn, ticket stubs, and that after-movie daze where everything feels possible.',
		'vibe'    => array('old theater', 'open late', 'film grain', 'soft chaos'),
		'wallpaper_target' => 'wallpaper-movie',
		'download_target'  => 'download-movie',
		'preview' => 'assets/monthly-themes/july-2026/display/midnight-movie-insert-preview.jpg',
		'alt'     => 'Midnight Movie kindle insert preview collage',
		'bottom'  => 'bybookishbabe / july 2026',
	),
	array(
		'class'   => 'drive',
		'num'     => '03',
		'world'   => 'summer road',
		'name'    => 'midnight drive',
		'slogan'  => 'think i will miss you forever',
		'desc'    => 'gas station lights, blurred roads, dashboard glow, and the kind of drive you take when you cannot sleep.',
		'vibe'    => array('night drive', 'motel neon', 'blue camera', 'radio ache'),
		'wallpaper_target' => 'wallpaper-drive',
		'download_target'  => 'download-drive',
		'preview' => 'assets/monthly-themes/july-2026/display/midnight-drive-insert-preview.jpg',
		'alt'     => 'Midnight Drive kindle insert preview collage',
		'bottom'  => 'bybookishbabe / july 2026',
	),
	array(
		'class'   => 'makeout',
		'num'     => '04',
		'world'   => 'dark room',
		'name'    => 'midnight makeout',
		'slogan'  => 'i cannot love you in the dark',
		'desc'    => 'black-and-white longing, messy notes, purple light, and a romance that is probably a bad idea.',
		'vibe'    => array('purple neon', 'bad idea', 'film strip', 'after hours'),
		'wallpaper_target' => 'wallpaper-makeout',
		'download_target'  => 'download-makeout',
		'preview' => 'assets/monthly-themes/july-2026/display/midnight-makeout-insert-preview.jpg',
		'alt'     => 'Midnight Makeout kindle insert preview collage',
		'bottom'  => 'bybookishbabe / july 2026',
	),
);

$download_designs = array(
	array(
		'name'     => 'midnight swim',
		'mockup'   => 'display/midnight-swim-mockup.jpg',
		'file_key' => 'MidnightSwim',
		'id'       => 'download-swim',
	),
	array(
		'name'     => 'midnight movie',
		'mockup'   => 'display/midnight-movie-mockup.jpg',
		'file_key' => 'MidnightMovie',
		'id'       => 'download-movie',
	),
	array(
		'name'     => 'midnight drive',
		'mockup'   => 'display/midnight-drive-mockup.jpg',
		'file_key' => 'MidnightDrive',
		'id'       => 'download-drive',
	),
	array(
		'name'     => 'midnight makeout',
		'mockup'   => 'display/midnight-makeout-mockup.jpg',
		'file_key' => 'MidnightMakeout',
		'id'       => 'download-makeout',
	),
);

$download_sizes = array(
	'6 inch'   => '6Inch_Printable_%s.pdf',
	'10th gen' => '10thGen_Printable_%s.pdf',
	'11th gen' => '11thGen_Printable_%s.pdf',
	'12th gen' => '12thGen_Printable_%s.pdf',
);

$wallpapers = array(
	array('image' => 'wallpapers/midnight-swim-wallpaper.png', 'preview' => 'display/midnight-swim-wallpaper-preview.jpg', 'label' => 'midnight swim wallpaper', 'id' => 'wallpaper-swim'),
	array('image' => 'wallpapers/midnight-movie-wallpaper.png', 'preview' => 'display/midnight-movie-wallpaper-preview.jpg', 'label' => 'midnight movie wallpaper', 'id' => 'wallpaper-movie'),
	array('image' => 'wallpapers/midnight-drive-wallpaper.png', 'preview' => 'display/midnight-drive-wallpaper-preview.jpg', 'label' => 'midnight drive wallpaper', 'id' => 'wallpaper-drive'),
	array('image' => 'wallpapers/midnight-makeout-wallpaper.png', 'preview' => 'display/midnight-makeout-wallpaper-preview.jpg', 'label' => 'midnight makeout wallpaper', 'id' => 'wallpaper-makeout'),
);

$book_spotlights = array(
	array(
		'handle'     => 'haunting-adeline',
		'title'      => 'Haunting Adeline',
		'author'     => 'H.D. Carlton',
		'cover'      => 'https://bybookishbabe.com/wp-content/uploads/2026/05/hauntingadeline-683x1024.png',
		'url'        => home_url('/books/haunting-adeline/'),
		'amazon'     => 'https://amzn.to/3PBUmq9',
		'bookshop'   => 'https://bookshop.org/a/120204/9781638932468',
		'newsletter' => '',
		'spice'      => 4,
		'shelf'      => 'dark romance',
		'why'        => 'the book that sold me on stalker romances.',
		'mini'       => 'she can manipulate anyone... except the man who has been watching her long enough to make her his anyway.',
		'tension'    => 2,
		'damage'     => 3,
		'darkness'   => 4,
		'yearning'   => '1',
		'reread'     => '0',
		'ku'         => '1',
		'tags'       => array('stalker romance', 'touch her and die'),
	),
	array(
		'handle'     => 'insatiable',
		'title'      => 'Insatiable',
		'author'     => 'Leigh Rivers',
		'cover'      => 'https://bybookishbabe.com/wp-content/uploads/2026/05/insatiable-683x1024.png',
		'url'        => home_url('/books/insatiable/'),
		'amazon'     => 'https://a.co/d/7pni9CY',
		'bookshop'   => 'https://bookshop.org/a/120204/9781739433000',
		'newsletter' => '',
		'spice'      => 4,
		'shelf'      => 'dark romance',
		'why'        => 'a series that will alter your brain chemistry.',
		'mini'       => 'lovers to enemies to lovers again after a betrayal destroys everything and fate forces them to face each other again.',
		'tension'    => 3,
		'damage'     => 4,
		'darkness'   => 3,
		'yearning'   => '2',
		'reread'     => '1',
		'ku'         => '1',
		'tags'       => array('enemies to lovers', 'second chance romance'),
	),
	array(
		'handle'     => 'bad-bishop',
		'title'      => 'Bad Bishop',
		'author'     => 'L.J. Shen',
		'cover'      => 'https://bybookishbabe.com/wp-content/uploads/2026/05/badbishop-683x1024.png',
		'url'        => home_url('/books/bad-bishop/'),
		'amazon'     => 'https://amzn.to/4cjhrHy',
		'bookshop'   => 'https://bookshop.org/a/120204/9781464252051',
		'newsletter' => 'https://thesmutandsentimentsociety.substack.com/p/i-should-not-have-enjoyed-this',
		'spice'      => 4,
		'shelf'      => 'dark romance',
		'why'        => 'he is crazy, and she is like a wicked princess.',
		'mini'       => 'sold to a psychopath prince who thinks she is a pawn... but she might be the one who brings him to his knees.',
		'tension'    => 2,
		'damage'     => 2,
		'darkness'   => 4,
		'yearning'   => '1',
		'reread'     => '0',
		'ku'         => '1',
		'tags'       => array('forced proximity', 'mafia romance', 'touch her and die'),
	),
	array(
		'handle'     => 'under-your-scars',
		'title'      => 'Under Your Scars',
		'author'     => 'Ariel N. Anderson',
		'cover'      => 'https://bybookishbabe.com/wp-content/uploads/2026/05/underyourscars-683x1024.png',
		'url'        => home_url('/books/under-your-scars/'),
		'amazon'     => 'https://amzn.to/3P6wICa',
		'bookshop'   => 'https://bookshop.org/a/120204/9798988296201',
		'newsletter' => '',
		'spice'      => 4,
		'shelf'      => 'dark romance',
		'why'        => 'no one prepared me for the ending... so let me tell you... be prepared.',
		'mini'       => 'a serial killer meets the one girl who sees something human in him... and decides she is his, even if loving him destroys her.',
		'tension'    => 2,
		'damage'     => 5,
		'darkness'   => 4,
		'yearning'   => '1',
		'reread'     => '0',
		'ku'         => '1',
		'tags'       => array('billionaire romance', 'stalker romance', 'touch her and die'),
	),
);

$books = array_map(
	static function (array $book): array {
		$book['id'] = 0;
		$post = get_page_by_path((string) $book['handle'], OBJECT, array('bbb_book', 'sss_book'));

		if ($post instanceof WP_Post) {
			$book['id'] = (int) $post->ID;
			$book['title'] = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title(get_the_title($post)) : get_the_title($post);
			$book['author'] = function_exists('bbb_get_book_author') ? bbb_get_book_author($post->ID) : (string) get_post_meta($post->ID, '_bbb_author', true);
			$book['cover'] = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($post->ID) : (string) ($book['cover'] ?? '');
			$book['url'] = get_permalink($post) ?: (string) $book['url'];
			$book['spice'] = (int) get_post_meta($post->ID, '_bbb_spice', true) ?: (int) $book['spice'];
			$book['shelf'] = (string) get_post_meta($post->ID, '_bbb_shelf_name', true) ?: (string) $book['shelf'];
			$book['why'] = (string) get_post_meta($post->ID, '_bbb_why', true) ?: (string) $book['why'];
			$book['mini'] = (string) get_post_meta($post->ID, '_bbb_mini_note', true) ?: (string) $book['mini'];
			$book['tension'] = (int) get_post_meta($post->ID, '_bbb_tension', true) ?: (int) $book['tension'];
			$book['damage'] = (int) get_post_meta($post->ID, '_bbb_damage', true) ?: (int) $book['damage'];
			$book['darkness'] = (int) get_post_meta($post->ID, '_bbb_darkness', true) ?: (int) $book['darkness'];
			$book['yearning'] = (string) get_post_meta($post->ID, '_bbb_yearning', true) ?: (string) $book['yearning'];
			$book['reread'] = (string) get_post_meta($post->ID, '_bbb_reread', true) ?: (string) $book['reread'];
			$book['ku'] = (string) get_post_meta($post->ID, '_bbb_ku', true) ?: (string) $book['ku'];
			$book['amazon'] = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value((string) get_post_meta($post->ID, '_bbb_amazon_url', true)) : (string) $book['amazon'];
			$book['bookshop'] = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value((string) get_post_meta($post->ID, '_bbb_bookshop_url', true)) : (string) $book['bookshop'];
			$book['newsletter'] = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value((string) get_post_meta($post->ID, '_bbb_newsletter_url', true)) : (string) $book['newsletter'];
		}

		return $book;
	},
	$book_spotlights
);

$quotes = array_values(
	array_filter(
		array_map(
			static function (array $book): ?array {
				$quote_text = '';
				$quote_url  = '';
				$book_id    = (int) ($book['id'] ?? 0);
				$post       = $book_id > 0 ? get_post($book_id) : null;

				if ($post instanceof WP_Post && function_exists('bbb_book_quote_posts')) {
					$quote_posts = bbb_book_quote_posts($post, 1);
					$quote_post  = $quote_posts[0] ?? null;
					if ($quote_post instanceof WP_Post) {
						$quote_text = function_exists('bbb_bookquote_quote_text')
							? bbb_bookquote_quote_text($quote_post)
							: trim(wp_strip_all_tags((string) $quote_post->post_content));
					}
				}

				if ('' === trim($quote_text)) {
					$quote_text = (string) ($book['mini'] ?? $book['why'] ?? '');
				}
				if ('' === trim($quote_text)) {
					return null;
				}

				if ($book_id > 0 && function_exists('bbb_book_quotes_url')) {
					$quote_url = bbb_book_quotes_url($book_id);
				}

				return array(
					'text'   => trim(wp_strip_all_tags($quote_text)),
					'title'  => (string) ($book['title'] ?? ''),
					'author' => (string) ($book['author'] ?? ''),
					'url'    => '' !== $quote_url ? $quote_url : (string) ($book['url'] ?? ''),
				);
			},
			$books
		)
	)
);

if (!$quotes) {
	$quotes = array(
		array('text' => 'dark nights reads for the after hours. you know what you are signing up for.', 'title' => 'july spotlight shelf', 'author' => 'bybookishbabe', 'url' => ''),
	);
}

$moods = array(
	array('feeling' => 'obsessive, consuming love', 'vibe' => 'dark romance / five spice', 'read' => 'Haunting Adeline', 'note' => 'no lifeguard warning'),
);

$playlists = array(
	array(
		'class'  => 'makeout',
		'icon'   => '🖤',
		'title'  => 'midnight makeout',
		'vibe'   => 'dark, consuming, kissing in the dark',
		'tracks' => array(
			array('title' => 'Apocalypse', 'artist' => 'Cigarettes After Sex'),
			array('title' => 'Love In The Dark', 'artist' => 'Adele'),
			array('title' => 'Wicked Game', 'artist' => 'Chris Isaak'),
			array('title' => 'Dark Red', 'artist' => 'Steve Lacy'),
			array('title' => 'Liability', 'artist' => 'Lorde'),
			array('title' => 'Is It Cool?', 'artist' => 'SZA'),
			array('title' => 'War of Hearts', 'artist' => 'Ruelle'),
			array('title' => 'I See Red', 'artist' => 'Everybody Loves an Outlaw'),
		),
	),
	array(
		'class'  => 'drive',
		'icon'   => '🌙',
		'title'  => 'midnight drive',
		'vibe'   => 'lana era, windows down, summer sadness',
		'tracks' => array(
			array('title' => 'Summertime Sadness', 'artist' => 'Lana Del Rey'),
			array('title' => 'Young and Beautiful', 'artist' => 'Lana Del Rey'),
			array('title' => 'Video Games', 'artist' => 'Lana Del Rey'),
			array('title' => 'Starboy', 'artist' => 'The Weeknd'),
			array('title' => 'Call Out My Name', 'artist' => 'The Weeknd'),
			array('title' => 'Electric Feel', 'artist' => 'MGMT'),
			array('title' => 'Ribs', 'artist' => 'Lorde'),
		),
	),
	array(
		'class'  => 'movie',
		'icon'   => '🎬',
		'title'  => 'midnight movie',
		'vibe'   => 'cinematic, atmospheric, late night energy',
		'tracks' => array(
			array('title' => 'Sweater Weather', 'artist' => 'The Neighbourhood'),
			array('title' => 'R U Mine?', 'artist' => 'Arctic Monkeys'),
			array('title' => 'Do I Wanna Know?', 'artist' => 'Arctic Monkeys'),
			array('title' => 'Ivy', 'artist' => 'Frank Ocean'),
			array('title' => 'Night Moves', 'artist' => 'Bob Seger'),
			array('title' => 'Motion Sickness', 'artist' => 'Phoebe Bridgers'),
			array('title' => 'Walkin\' After Midnight', 'artist' => 'Patsy Cline'),
		),
	),
	array(
		'class'  => 'swim',
		'icon'   => '🌊',
		'title'  => 'midnight swim',
		'vibe'   => 'ocean air, campfire, gold star summer',
		'tracks' => array(
			array('title' => 'Ocean Eyes', 'artist' => 'Billie Eilish'),
			array('title' => 'Skin', 'artist' => 'Sabrina Carpenter'),
			array('title' => 'Ceilings', 'artist' => 'Lizzy McAlpine'),
			array('title' => 'I Found', 'artist' => 'Amber Run'),
			array('title' => 'August', 'artist' => 'Taylor Swift'),
			array('title' => 'this is me trying', 'artist' => 'Taylor Swift'),
			array('title' => 'Everything I Wanted', 'artist' => 'Billie Eilish'),
			array('title' => 'Exile', 'artist' => 'Taylor Swift ft. Bon Iver'),
		),
	),
);

get_header();
?>

<main class="bbb-midnight-kit" id="main-content">
	<canvas class="bbb-midnight-kit__stars" data-midnight-starfield></canvas>
	<div class="bbb-midnight-kit__skyProps" aria-hidden="true">
		<span class="bbb-midnight-kit__shooting bbb-midnight-kit__shooting--one"></span>
		<span class="bbb-midnight-kit__shooting bbb-midnight-kit__shooting--two"></span>
		<span class="bbb-midnight-kit__shooting bbb-midnight-kit__shooting--three"></span>
		<span class="bbb-midnight-kit__moon bbb-midnight-kit__moon--one"></span>
		<span class="bbb-midnight-kit__moon bbb-midnight-kit__moon--two"></span>
		<span class="bbb-midnight-kit__jeep">
			<span class="bbb-midnight-kit__jeepCab"></span>
			<span class="bbb-midnight-kit__jeepBody"></span>
			<span class="bbb-midnight-kit__jeepWheel bbb-midnight-kit__jeepWheel--front"></span>
			<span class="bbb-midnight-kit__jeepWheel bbb-midnight-kit__jeepWheel--back"></span>
		</span>
		<span class="bbb-midnight-kit__actionSign">
			<span class="bbb-midnight-kit__actionClap"></span>
			<span>ACTION</span>
		</span>
	</div>

	<section class="bbb-midnight-kit__hero" aria-labelledby="midnightSummerTitle">
		<div class="bbb-midnight-kit__glow" aria-hidden="true"></div>
		<canvas class="bbb-midnight-kit__waves" data-midnight-waves></canvas>
		<div class="bbb-midnight-kit__heroLine" aria-label="Midnight Summer polaroid previews clipped on a line">
			<?php foreach ($inserts as $insert) : ?>
				<figure class="bbb-midnight-kit__polaroid bbb-midnight-kit__polaroid--<?php echo esc_attr($insert['class']); ?>">
					<img src="<?php echo esc_url(get_theme_file_uri($insert['preview'])); ?>" alt="<?php echo esc_attr($insert['alt']); ?>" loading="eager" decoding="async">
				</figure>
			<?php endforeach; ?>
		</div>
		<div class="bbb-midnight-kit__heroInner">
			<p class="bbb-midnight-kit__eyebrow">bybookishbabe</p>
			<h1 id="midnightSummerTitle">midnight summer,<em>after hours</em></h1>
			<div class="bbb-midnight-kit__rule" aria-hidden="true"></div>
			<p class="bbb-midnight-kit__sub">morally gray · dark nights · reads that ruin you on purpose</p>
			<span class="bbb-midnight-kit__badge">july 2026</span>
		</div>
	</section>

	<nav class="bbb-midnight-kit__nav" aria-label="July monthly theme sections">
		<a href="#kindle">the designs</a>
		<a href="#calendar">calendar</a>
		<a href="#wallpapers">wallpapers</a>
		<a href="#bookmarks">bookmarks</a>
		<a href="#kindle-downloads">kindle inserts</a>
		<a href="#review-template">review template</a>
		<a href="#books">book recs</a>
		<a href="#playlists">playlists</a>
		<a href="#quotes">quotes</a>
	</nav>

	<section class="bbb-midnight-section" id="kindle" aria-labelledby="midnightKindleTitle">
		<div class="bbb-midnight-section__head"><h2 id="midnightKindleTitle">the designs</h2></div>
		<p class="bbb-midnight-section__kicker">four designs, four moods, all midnight.</p>
		<div class="bbb-midnight-inserts">
			<?php foreach ($inserts as $insert) : ?>
				<article class="bbb-midnight-insert bbb-midnight-insert--<?php echo esc_attr($insert['class']); ?>">
					<div class="bbb-midnight-insert__media">
						<img class="bbb-midnight-insert__preview" src="<?php echo esc_url(get_theme_file_uri($insert['preview'])); ?>" alt="<?php echo esc_attr($insert['alt']); ?>" loading="lazy" decoding="async">
					</div>
					<div class="bbb-midnight-insert__body">
						<p class="bbb-midnight-insert__num"><?php echo esc_html($insert['num'] . ' / ' . $insert['world']); ?></p>
						<h3><?php echo esc_html($insert['name']); ?></h3>
						<p class="bbb-midnight-insert__slogan"><?php echo esc_html($insert['slogan']); ?></p>
						<p class="bbb-midnight-insert__desc"><?php echo esc_html($insert['desc']); ?></p>
						<ul class="bbb-midnight-vibes" aria-label="<?php echo esc_attr($insert['name']); ?> visual direction">
							<li><a href="#<?php echo esc_attr($insert['download_target']); ?>">kindle insert</a></li>
							<li><a href="#<?php echo esc_attr($insert['wallpaper_target']); ?>">wallpaper</a></li>
						</ul>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="bbb-midnight-section" id="calendar" aria-labelledby="midnightCalendarTitle">
		<div class="bbb-midnight-section__head"><h2 id="midnightCalendarTitle">july calendar</h2></div>
		<p class="bbb-midnight-section__kicker">the finished midnight summer calendar. print it, save it, plan the month after dark.</p>
		<div class="bbb-midnight-calendar">
			<img src="<?php echo esc_url($calendar_preview_url); ?>" alt="July 2026 Midnight Summer calendar preview" loading="lazy" decoding="async">
			<footer>
				<span>july 2026 calendar</span>
				<?php if ($has_theme_access) : ?>
					<a href="<?php echo esc_url($calendar_pdf_url); ?>" download>download pdf</a>
				<?php else : ?>
					<a class="is-locked" href="<?php echo esc_url($locked_url); ?>"<?php echo $locked_link_attrs; ?>><?php echo esc_html($theme_is_released ? 'unlock calendar' : 'releases july 1'); ?></a>
				<?php endif; ?>
			</footer>
		</div>
	</section>

	<section class="bbb-midnight-section" id="wallpapers" aria-labelledby="midnightWallpapersTitle">
		<div class="bbb-midnight-section__head"><h2 id="midnightWallpapersTitle">iphone wallpapers</h2></div>
		<p class="bbb-midnight-section__kicker">lock screen and home screen. all dark nights, no apologies.</p>
		<div class="bbb-midnight-wallpapers">
			<?php foreach ($wallpapers as $wallpaper) : ?>
				<article id="<?php echo esc_attr($wallpaper['id']); ?>">
					<?php
					$wallpaper_path = $asset_base . '/' . $wallpaper['image'];
					$wallpaper_file = basename((string) $wallpaper['image']);
					$wallpaper_url  = function_exists('bbb_forced_theme_asset_download_url')
						? bbb_forced_theme_asset_download_url($wallpaper_path, $wallpaper_file)
						: get_theme_file_uri($wallpaper_path);
					?>
					<img src="<?php echo esc_url(get_theme_file_uri($asset_base . '/' . $wallpaper['preview'])); ?>" alt="<?php echo esc_attr($wallpaper['label']); ?> wallpaper preview" loading="lazy" decoding="async">
					<footer>
						<span><?php echo esc_html($wallpaper['label']); ?></span>
						<?php if ($has_wallpaper_access) : ?>
							<a href="<?php echo esc_url($wallpaper_url); ?>" download>download</a>
						<?php else : ?>
							<a class="is-locked" href="<?php echo esc_url($locked_url); ?>"<?php echo $locked_link_attrs; ?>><?php echo esc_html($theme_is_released ? 'unlock' : 'releases july 1'); ?></a>
						<?php endif; ?>
					</footer>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="bbb-midnight-section bbb-midnight-freebie" id="bookmarks" aria-labelledby="midnightBookmarksTitle">
		<div class="bbb-midnight-section__head"><h2 id="midnightBookmarksTitle">printable bookmarks</h2></div>
		<p class="bbb-midnight-section__kicker">the monthly freebie for free and paid society members.</p>
		<div class="bbb-midnight-freebie__card">
			<figure class="bbb-midnight-freebie__media">
				<img src="<?php echo esc_url($bookmarks_mockup_url); ?>" alt="Midnight Summer printable bookmark mockup" loading="lazy" decoding="async">
			</figure>
			<div class="bbb-midnight-freebie__copy">
				<span>monthly freebie</span>
				<h3>midnight summer bookmarks</h3>
				<p>print them, trim them, tuck them into whatever book is keeping you up too late.</p>
				<?php if ($has_freebie_access) : ?>
					<a class="bbb-midnight-freebie__button" href="<?php echo esc_url($bookmarks_pdf_url); ?>" download>download bookmarks</a>
				<?php else : ?>
					<a class="bbb-midnight-freebie__button is-locked" href="<?php echo esc_url($join_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($theme_is_released ? 'join free to download' : 'releases july 1'); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="bbb-midnight-section bbb-midnight-downloads" id="kindle-downloads" aria-labelledby="midnightDownloadsTitle">
		<div class="bbb-midnight-section__head"><h2 id="midnightDownloadsTitle">kindle insert sizes</h2></div>
		<p class="bbb-midnight-section__kicker">
			<?php if ($has_theme_access) : ?>
				paid society access active. download your size.
			<?php elseif ($is_paid_society_member) : ?>
				paid society access recognized. midnight summer unlocks july 1.
			<?php else : ?>
				midnight summer unlocks for paid society members july 1.
			<?php endif; ?>
		</p>
		<div class="bbb-midnight-downloads__grid">
			<?php foreach ($download_designs as $design) : ?>
				<article class="bbb-midnight-download" id="<?php echo esc_attr($design['id']); ?>">
					<figure class="bbb-midnight-download__mockup">
						<img src="<?php echo esc_url(get_theme_file_uri($asset_base . '/' . $design['mockup'])); ?>" alt="<?php echo esc_attr($design['name']); ?> mockup preview" loading="lazy" decoding="async">
					</figure>
					<div class="bbb-midnight-download__body">
						<h3><?php echo esc_html($design['name']); ?></h3>
						<div class="bbb-midnight-download__links">
							<?php foreach ($download_sizes as $label => $pattern) : ?>
								<?php
								$file = sprintf($pattern, $design['file_key']);
								$file_path = $asset_base . '/downloads/' . $file;
								$url = function_exists('bbb_forced_theme_asset_download_url')
									? bbb_forced_theme_asset_download_url($file_path, $file)
									: get_theme_file_uri($file_path);
								?>
								<?php if ($has_theme_access) : ?>
									<a href="<?php echo esc_url($url); ?>" download><?php echo esc_html($label); ?></a>
								<?php else : ?>
									<a class="is-locked" href="<?php echo esc_url($locked_url); ?>"<?php echo $locked_link_attrs; ?>><?php echo esc_html($theme_is_released ? $label . ' locked' : $label . ' releases july 1'); ?></a>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="bbb-midnight-section" id="books" aria-labelledby="midnightBooksTitle">
		<div class="bbb-midnight-section__head"><h2 id="midnightBooksTitle">books that fit the vibe</h2></div>
		<p class="bbb-midnight-section__kicker">dark nights reads for the after hours. you know what you are signing up for.</p>
		<div class="bbb-midnight-books" data-sss-lib="midnight-summer">
			<?php foreach ($books as $book_index => $book) : ?>
				<?php
				$book_tropes = implode(', ', $book['tags']);
				$spice_text  = str_repeat('🌶', min(5, max(0, (int) $book['spice'])));
				?>
				<article
					class="sss-lib__book bbb-midnight-book"
					role="button"
					tabindex="0"
					data-handle="<?php echo esc_attr($book['handle']); ?>"
					data-url="<?php echo esc_url($book['url']); ?>"
					data-title="<?php echo esc_attr($book['title']); ?>"
					data-author="<?php echo esc_attr($book['author']); ?>"
					data-cover="<?php echo esc_url($book['cover']); ?>"
					data-spice="<?php echo esc_attr((string) $book['spice']); ?>"
					data-tropes="<?php echo esc_attr($book_tropes); ?>"
					data-tropes-display="<?php echo esc_attr($book_tropes); ?>"
					data-shelf="<?php echo esc_attr($book['shelf']); ?>"
					data-why="<?php echo esc_attr($book['why']); ?>"
					data-mini="<?php echo esc_attr($book['mini']); ?>"
					data-amazon="<?php echo esc_url($book['amazon']); ?>"
					data-bookshop="<?php echo esc_url($book['bookshop']); ?>"
					data-newsletter="<?php echo esc_attr($book['newsletter']); ?>"
					data-tension="<?php echo esc_attr((string) $book['tension']); ?>"
					data-damage="<?php echo esc_attr((string) $book['damage']); ?>"
					data-darkness="<?php echo esc_attr((string) $book['darkness']); ?>"
					data-yearning="<?php echo esc_attr((string) $book['yearning']); ?>"
					data-reread="<?php echo esc_attr($book['reread']); ?>"
					data-ku="<?php echo esc_attr($book['ku']); ?>"
				>
					<span class="sss-lib__coverWrap bbb-midnight-book__coverWrap">
						<button class="sss-lib__heart bbb-midnight-book__heart" type="button" data-heart aria-label="<?php echo esc_attr(sprintf('save %s to your bookshelf', $book['title'])); ?>">
							<span aria-hidden="true">♡</span>
							<span class="screen-reader-text">save</span>
						</button>
						<img class="sss-lib__cover bbb-midnight-book__cover" src="<?php echo esc_url($book['cover']); ?>" alt="<?php echo esc_attr($book['title'] . ' book cover'); ?>" loading="lazy" decoding="async">
						<span class="sss-lib__floatSpice bbb-midnight-book__spice" aria-label="<?php echo esc_attr((string) $book['spice'] . ' spice level'); ?>"><?php echo esc_html($spice_text); ?></span>
					</span>
					<div class="bbb-midnight-book__body">
						<span class="bbb-midnight-book__shelf"><?php echo esc_html($book['shelf']); ?></span>
						<h3><?php echo esc_html($book['title']); ?></h3>
						<p><?php echo esc_html($book['author']); ?></p>
						<ul>
							<?php foreach ($book['tags'] as $tag_index => $tag) : ?>
								<li class="tone-<?php echo esc_attr((string) (($book_index + $tag_index) % 3)); ?>"><?php echo esc_html($tag); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="bbb-midnight-section" id="playlists" aria-labelledby="midnightPlaylistsTitle">
		<div class="bbb-midnight-section__head"><h2 id="midnightPlaylistsTitle">curated playlists</h2></div>
		<p class="bbb-midnight-section__kicker">four moods, four soundtracks. pick the scene you are in and press play.</p>
		<div class="bbb-midnight-playlists">
			<?php foreach ($playlists as $playlist) : ?>
				<article class="bbb-midnight-playlist bbb-midnight-playlist--<?php echo esc_attr($playlist['class']); ?>">
					<header>
						<span aria-hidden="true"><?php echo esc_html($playlist['icon']); ?></span>
						<div>
							<h3><?php echo esc_html($playlist['title']); ?></h3>
							<p><?php echo esc_html($playlist['vibe']); ?></p>
						</div>
					</header>
					<ol>
						<?php foreach ($playlist['tracks'] as $track) : ?>
							<?php
							$spotify_url = 'https://open.spotify.com/search/' . rawurlencode($track['title'] . ' ' . $track['artist']);
							?>
							<li>
								<a href="<?php echo esc_url($spotify_url); ?>" target="_blank" rel="noopener">
									<strong><?php echo esc_html($track['title']); ?></strong>
									<span><?php echo esc_html($track['artist']); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ol>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="bbb-midnight-section bbb-midnight-reviewTemplate" id="review-template" aria-labelledby="midnightReviewTemplateTitle">
		<div class="bbb-midnight-section__head"><h2 id="midnightReviewTemplateTitle">review template</h2></div>
		<p class="bbb-midnight-section__kicker">review your books with style</p>
		<div class="bbb-midnight-reviewTemplate__card">
			<div class="bbb-midnight-reviewTemplate__copy">
				<span><?php echo esc_html($has_theme_access ? 'template ready' : 'paid template'); ?></span>
				<h3>midnight summer review template</h3>
				<p>edit in canva, come back to anytime, have forever</p>
				<div class="bbb-midnight-reviewTemplate__actions">
					<?php if ($has_theme_access) : ?>
						<a class="bbb-midnight-reviewTemplate__button" href="<?php echo esc_url($review_template_canva_url); ?>" target="_blank" rel="noopener">open template</a>
					<?php else : ?>
						<a class="bbb-midnight-reviewTemplate__button is-locked" href="<?php echo esc_url($locked_url); ?>"<?php echo $locked_link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html($theme_is_released ? 'paid members only' : 'releases july 1'); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<figure class="bbb-midnight-reviewTemplate__media">
				<img src="<?php echo esc_url($review_template_mockup_url); ?>" alt="Midnight Summer book review Canva template mockup" loading="lazy" decoding="async">
			</figure>
		</div>
	</section>

	<section class="bbb-midnight-section" id="quotes" aria-labelledby="midnightQuotesTitle">
		<div class="bbb-midnight-section__head"><h2 id="midnightQuotesTitle">spotlight book quotes</h2></div>
		<div class="bbb-midnight-quotes">
			<?php foreach ($quotes as $index => $quote) : ?>
				<figure class="tone-<?php echo esc_attr((string) ($index % 4)); ?>">
					<blockquote><span><?php echo esc_html($quote['text']); ?></span></blockquote>
					<figcaption>
						<strong><?php echo esc_html($quote['title']); ?></strong>
						<span><?php echo esc_html($quote['author']); ?></span>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="bbb-midnight-bingoCta" aria-labelledby="midnightBingoTitle">
		<div>
			<span>summer edition</span>
			<h2 id="midnightBingoTitle">romance reading bingo</h2>
			<p>keep the midnight summer mood going. mark the tropes, chaos, book boyfriends, and after-hours reads you finish this season.</p>
		</div>
		<a href="<?php echo esc_url(home_url('/romance-reading-bingo/')); ?>">play summer bingo</a>
	</section>

	<footer class="bbb-midnight-kit__footer">
		<strong>bybookishbabe</strong>
		<span>midnight summer, after hours</span>
		<a href="<?php echo esc_url(function_exists('bbb_substack_subscribe_url') ? bbb_substack_subscribe_url() : 'https://thesmutandsentimentsociety.substack.com'); ?>" target="_blank" rel="noopener">the smut & sentiment society</a>
	</footer>
</main>

<?php
get_footer();
