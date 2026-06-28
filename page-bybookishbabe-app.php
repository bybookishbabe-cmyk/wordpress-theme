<?php
/**
 * Installed app home for bybookishbabe.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (function_exists('bbb_pwa_is_install_request') && bbb_pwa_is_install_request()) {
	$device         = isset($_GET['device']) ? sanitize_key((string) wp_unslash($_GET['device'])) : '';
	$is_ipad        = 'ipad' === $device;
	$is_android     = 'android' === $device;
	$primary_copy   = 'Tap Share, then Add to Home Screen.';
	$secondary_copy = 'After you open it from your Home Screen, bybookishbabe can ask if you want bookish alerts.';

	if ($is_ipad) {
		$primary_copy   = 'On iPad, tap the square Share icon in Safari\'s top bar, then tap Add to Home Screen.';
		$secondary_copy = 'Apple does not allow websites to open that iPad install sheet directly, but this saves bybookishbabe like an app.';
	} elseif ($is_android) {
		$primary_copy   = 'Tap the three-dot menu, then tap Add to Home screen or Install app.';
		$secondary_copy = 'If Chrome shows an install pop-up instead, tap Install and open bybookishbabe from your Home Screen.';
	}

	status_header(200);
	nocache_headers();
	header('Content-Type: text/html; charset=' . get_option('blog_charset'));
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Install bybookishbabe</title>
	<link rel="manifest" href="<?php echo esc_url(add_query_arg('v', function_exists('bbb_pwa_version') ? bbb_pwa_version() : wp_get_theme()->get('Version'), home_url('/bybookishbabe.webmanifest'))); ?>">
	<meta name="theme-color" content="<?php echo esc_attr(function_exists('bbb_pwa_theme_color') ? bbb_pwa_theme_color() : '#f6d7df'); ?>">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="bybookishbabe">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url(function_exists('bbb_pwa_asset_uri') ? bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png') : get_theme_file_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>">
	<style>
		body{align-items:center;background:#fff7fa;color:#171417;display:grid;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;min-height:100vh;padding:24px;text-align:center}
		img{border-radius:24px;display:block;height:132px;margin:0 auto 24px;width:132px}
		h1{font-size:28px;margin:0 0 12px}
		p{color:#6d5965;font-size:17px;line-height:1.45;margin:0 auto;max-width:320px}
		.bbb-pwa-install-note{margin-top:14px}
		.bbb-pwa-install-emphasis{color:#2f7df6;font-weight:700}
	</style>
</head>
<body>
	<main>
		<img src="<?php echo esc_url(function_exists('bbb_pwa_asset_uri') ? bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png') : get_theme_file_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>" alt="bybookishbabe">
		<h1>bybookishbabe</h1>
		<p><?php echo wp_kses_post(str_replace(array('Share', 'Add to Home Screen'), array('<span class="bbb-pwa-install-emphasis">Share</span>', '<span class="bbb-pwa-install-emphasis">Add to Home Screen</span>'), esc_html($primary_copy))); ?></p>
		<p class="bbb-pwa-install-note"><?php echo esc_html($secondary_copy); ?></p>
	</main>
</body>
</html>
	<?php
	exit;
}

$identity          = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
$has_reader_access = is_array($identity) && '' !== trim((string) ($identity['email'] ?? ''));
$display_name      = $has_reader_access && '' !== trim((string) ($identity['displayName'] ?? ''))
	? (string) $identity['displayName']
	: 'babe';
$reader_first_name = trim(strtok($display_name, ' ') ?: $display_name);
$reader_first_name = '' !== $reader_first_name ? $reader_first_name : 'babe';
$account_url       = function_exists('bbb_page_url') ? bbb_page_url('account') : home_url('/account/');
$dashboard_url     = function_exists('bbb_page_url') ? bbb_page_url('member-dashboard') : home_url('/member-dashboard/');
$bookshelf_url     = function_exists('bbb_page_url') ? bbb_page_url('my-bookshelf') : home_url('/my-bookshelf/');
$library_url       = function_exists('bbb_page_url') ? bbb_page_url('library') : home_url('/library/');
$society_url       = function_exists('bbb_page_url') ? bbb_page_url('smut-sentiment-society') : home_url('/smut-sentiment-society/');
$app_share_url     = home_url('/bybookishbabe-app/');
$home_url          = home_url('/');
$is_app_preview    = function_exists('bbb_pwa_request_path_is') && bbb_pwa_request_path_is('bybookishbabe-app-preview');
$is_app_home       = function_exists('bbb_pwa_request_path_is') && bbb_pwa_request_path_is('bybookishbabe-app');
$is_logged_in_account = is_user_logged_in();
$show_app_hub         = ($is_logged_in_account || $has_reader_access) && ($is_app_home || $is_app_preview || isset($_GET['app_hub_preview']));
$app_title         = $is_app_preview ? 'bybookishbabe app preview' : 'bybookishbabe app';
$reader_email      = $has_reader_access ? trim((string) ($identity['email'] ?? '')) : '';
$reader_user_id    = $has_reader_access ? absint($identity['userId'] ?? 0) : get_current_user_id();
$reader_tier       = 'free';

if ('' !== $reader_email && function_exists('bbb_reader_access_tier_for_email')) {
	$reader_tier = bbb_reader_access_tier_for_email($reader_email, $reader_user_id);
} elseif (function_exists('bbb_reader_access_tier')) {
	$reader_tier = bbb_reader_access_tier($reader_user_id);
}

$is_paid_society = 'society' === $reader_tier;

$newsletter_title = 'the latest society newsletter';
$newsletter_url   = function_exists('bbb_substack_home_url') ? bbb_substack_home_url() : 'https://thesmutandsentimentsociety.substack.com/';
$newsletter_image = function_exists('bbb_pwa_asset_uri') ? bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png') : get_theme_file_uri('assets/pwa/bybookishbabe-apple-touch-icon.png');
$newsletter_alt   = 'bybookishbabe';

if ($show_app_hub && function_exists('bbb_society_get_newsletter_issues')) {
	$latest_issues = bbb_society_get_newsletter_issues(1);
	$latest_issue  = isset($latest_issues[0]) && $latest_issues[0] instanceof WP_Post ? $latest_issues[0] : null;

	if ($latest_issue instanceof WP_Post) {
		$newsletter_title = get_the_title($latest_issue);
		$newsletter_url   = function_exists('bbb_society_newsletter_issue_url') ? bbb_society_newsletter_issue_url($latest_issue) : get_permalink($latest_issue);
		$issue_image      = function_exists('bbb_society_newsletter_issue_image') ? bbb_society_newsletter_issue_image($latest_issue) : array();
		if (is_array($issue_image) && !empty($issue_image['url'])) {
			$newsletter_image = (string) $issue_image['url'];
			$newsletter_alt   = !empty($issue_image['alt']) ? (string) $issue_image['alt'] : $newsletter_title;
		}
	}
}

$popular_tropes = array();
$trope_emoji_map = array(
	'age-gap'             => '⏳',
	'brothers-best-friend' => '🤫',
	'captor-captive'      => '🔒',
	'dark-romance'        => '🖤',
	'enemies-to-lovers'   => '⚔️',
	'fake-dating'         => '💍',
	'forced-proximity'    => '🏠',
	'friends-to-lovers'   => '💌',
	'mafia'               => '🗡️',
	'morally-gray-men'    => '🩶',
	'second-chance'       => '↩️',
	'slow-burn'           => '🔥',
	'sports-romance'      => '🏒',
	'touch-her-and-die'   => '🔪',
);

if ($show_app_hub && taxonomy_exists('bbb_trope')) {
	$trope_terms = get_terms(
		array(
			'taxonomy'   => 'bbb_trope',
			'hide_empty' => true,
			'number'     => 3,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);

	if (!is_wp_error($trope_terms)) {
		foreach ($trope_terms as $term) {
			if (!$term instanceof WP_Term) {
				continue;
			}

			$popular_tropes[] = array(
				'emoji' => $trope_emoji_map[$term->slug] ?? '♡',
				'label' => strtolower($term->name),
				'url'   => function_exists('bbb_book_taxonomy_term_url') ? bbb_book_taxonomy_term_url($term) : home_url('/' . $term->slug . '-books/'),
			);
		}
	}
}

if ($show_app_hub && count($popular_tropes) < 3) {
	$popular_tropes = array_slice(
		array_merge(
			$popular_tropes,
			array(
				array('emoji' => '🖤', 'label' => 'dark romance', 'url' => home_url('/dark-romance-books/')),
				array('emoji' => '⚔️', 'label' => 'enemies to lovers', 'url' => home_url('/enemies-to-lovers-books/')),
				array('emoji' => '🏒', 'label' => 'sports romance', 'url' => home_url('/sports-romance-books/')),
			)
		),
		0,
		3
	);
}

$quiz_links = array(
	array('label' => 'take the reader quiz', 'sub' => 'find the book mood waiting for you', 'url' => function_exists('bbb_page_url') ? bbb_page_url('reader-quizzes') : home_url('/reader-quizzes/')),
	array('label' => 'find your fictional boyfriend', 'sub' => 'choose the obsession respectfully', 'url' => home_url('/fictional-boyfriend-quiz/')),
	array('label' => 'pick your trope fate', 'sub' => 'let the shelf decide your next spiral', 'url' => home_url('/romance-trope-quiz/')),
);

status_header(200);
nocache_headers();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html($app_title); ?></title>
	<meta name="robots" content="noindex,nofollow">
	<link rel="manifest" href="<?php echo esc_url(add_query_arg('v', function_exists('bbb_pwa_version') ? bbb_pwa_version() : wp_get_theme()->get('Version'), home_url('/bybookishbabe.webmanifest'))); ?>">
	<meta name="theme-color" content="#000000">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="bybookishbabe">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url(function_exists('bbb_pwa_asset_uri') ? bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png') : get_theme_file_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>">
	<link rel="stylesheet" href="<?php echo esc_url(get_theme_file_uri('assets/home-static.css')); ?>">
	<style>
		body{background:#000;color:#f7f4ef;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;min-height:100vh}
		main{box-sizing:border-box;display:grid;min-height:100vh;padding:32px 22px;place-items:center;text-align:center}
		section{width:min(520px,100%)}
		img{border:1px solid rgba(255,255,255,.16);border-radius:18px;display:block;height:78px;margin:0 auto 24px;width:78px}
		p{color:rgba(247,244,239,.62);font-size:16px;line-height:1.5;margin:0 auto 22px;max-width:360px}
		h1{font-family:Georgia,serif;font-size:clamp(42px,13vw,76px);font-weight:400;letter-spacing:0;line-height:.88;margin:0 0 18px;text-transform:lowercase}
		nav{display:flex;flex-wrap:wrap;gap:10px;justify-content:center}
		a{align-items:center;border:1px solid rgba(255,255,255,.18);border-radius:999px;color:#f7f4ef;display:inline-flex;font-size:13px;font-weight:800;justify-content:center;min-height:40px;padding:0 16px;text-decoration:none;text-transform:lowercase}
		.bbb-app-back{align-items:center;appearance:none;background:rgba(23,20,23,.9);border:1px solid rgba(255,255,255,.28);border-radius:999px;bottom:calc(82px + env(safe-area-inset-bottom));box-shadow:0 10px 24px rgba(23,20,23,.22);color:#fff;cursor:pointer;display:inline-flex;font:inherit;font-size:21px;font-weight:700;height:46px;justify-content:center;left:calc(20px + env(safe-area-inset-left));line-height:1;padding:0;position:fixed;text-decoration:none;width:46px;z-index:2147482400}
		.bbb-app-back[hidden]{display:none!important}
		.bbb-app-share{align-items:center;appearance:none;background:rgba(255,138,199,.95);border:1px solid rgba(255,255,255,.34);border-radius:999px;bottom:calc(82px + env(safe-area-inset-bottom));box-shadow:0 12px 28px rgba(0,0,0,.34);color:#1c0b13;cursor:pointer;display:inline-flex;height:46px;justify-content:center;padding:0;position:fixed;right:calc(20px + env(safe-area-inset-right));width:46px;z-index:2147482400}
		.bbb-app-shareIcon{display:block;font-size:21px;line-height:1}
		.bbb-app-share__text{height:1px;overflow:hidden;position:absolute;white-space:nowrap;width:1px;clip:rect(1px,1px,1px,1px)}
		.bbb-app-share__toast{background:#fff7fb;border:1px solid rgba(255,138,199,.4);border-radius:999px;bottom:calc(132px + env(safe-area-inset-bottom));box-shadow:0 12px 28px rgba(0,0,0,.28);color:#1c0b13;font-size:12px;font-weight:900;opacity:0;padding:8px 11px;pointer-events:none;position:fixed;right:calc(20px + env(safe-area-inset-right));text-transform:lowercase;transform:translateY(6px);transition:opacity .18s ease,transform .18s ease;z-index:2147482400}
		.bbb-app-share__toast.is-visible{opacity:1;transform:translateY(0)}
		body.bbb-book-modal-open .bbb-app-back,body.bbb-book-modal-open .bbb-app-share,body.bbb-book-modal-open .bbb-app-share__toast{display:none!important}
		.bbb-app-hub{align-items:center;background:rgba(0,0,0,.82);box-sizing:border-box;display:flex;inset:0;justify-content:center;opacity:0;padding:18px;position:fixed;transition:opacity .24s ease;z-index:20}
		.bbb-app-hub.is-open{opacity:1}
		.bbb-app-hub[hidden]{display:none}
		.bbb-app-hub__panel{background:#141012;border:1px solid rgba(255,138,199,.28);border-radius:6px;box-shadow:0 28px 90px rgba(0,0,0,.62);box-sizing:border-box;color:#fff7fb;display:grid;gap:12px;max-height:min(760px,92vh);max-width:440px;opacity:0;overflow:auto;padding:18px;position:relative;transform:translateY(18px) scale(.97);width:100%}
		.bbb-app-hub.is-open .bbb-app-hub__panel{animation:bbbAppHubIn .46s cubic-bezier(.2,.86,.2,1) both}
		.bbb-app-hub__close{align-items:center;background:#fff7fb;border:0;border-radius:999px;color:#161014;display:inline-flex;font-size:18px;font-weight:800;height:34px;justify-content:center;line-height:1;padding:0;position:absolute;right:14px;top:14px;width:34px}
		.bbb-app-hub__kicker{color:#ff8ac7;font-size:11px;font-weight:900;letter-spacing:.14em;margin:2px 40px 0 0;text-align:left;text-transform:uppercase}
		.bbb-app-hub h2{color:#fff7fb;font-family:Georgia,serif;font-size:34px;font-weight:400;letter-spacing:0;line-height:.95;margin:0 40px 2px 0;text-align:left;text-transform:lowercase}
		.bbb-app-hub__newsletter{background:#21181d;border:1px solid rgba(255,138,199,.32);border-radius:4px;color:#fff7fb;display:grid;gap:12px;grid-template-columns:118px 1fr;justify-content:stretch;min-height:0;padding:10px;text-align:left;text-transform:lowercase}
		.bbb-app-hub__newsletter-cover{aspect-ratio:1/1;background:#080708;border-radius:4px;box-shadow:8px 0 22px rgba(0,0,0,.22);display:block;height:auto;margin:0;object-fit:contain;transform-origin:left center;width:118px}
		.bbb-app-hub.is-open .bbb-app-hub__newsletter-cover{animation:bbbAppCoverOpen .78s ease both .16s}
		.bbb-app-hub__newsletter strong,.bbb-app-hub__quiz strong{display:block;font-size:12px;font-weight:900;letter-spacing:.08em;margin:4px 0 8px;text-transform:uppercase}
		.bbb-app-hub__newsletter span{display:block;color:rgba(255,248,251,.76);font-family:Georgia,serif;font-size:22px;font-weight:400;line-height:1.04;text-transform:lowercase}
		.bbb-app-hub__grid{display:grid;gap:10px;grid-template-columns:1fr 1fr}
		.bbb-app-hub__tile,.bbb-app-hub__quiz{background:#1f171c;border:1px solid rgba(255,255,255,.1);border-radius:4px;color:#fff7fb;justify-content:flex-start;min-height:88px;padding:14px;text-align:left;text-transform:lowercase}
		.bbb-app-hub__tile{align-items:flex-start;display:flex;flex-direction:column;font-size:17px;gap:8px}
		.bbb-app-hub__tile small{color:#ff8ac7;font-size:11px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}
		.bbb-app-hub__trope-row{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-start}
		.bbb-app-hub__trope{align-items:center;background:#2a1b24;border:1px solid rgba(255,138,199,.34);border-radius:999px;color:#ffd6e8;display:inline-flex;font-size:12px;font-weight:800;justify-content:center;line-height:1.15;min-height:36px;padding:7px 11px;text-align:left}
		.bbb-app-hub__trope-emoji{display:inline-block;font-size:15px;margin-right:6px}
		.bbb-app-hub__quiz{display:block;grid-column:1/-1;min-height:82px;padding:14px;text-decoration:none}
		.bbb-app-hub__quiz span{display:block;color:#d5a4bc;font-size:13px;font-weight:700;line-height:1.35;margin-top:8px;text-transform:lowercase}
		.bbb-app-hub__paid{display:grid;gap:8px;grid-template-columns:1fr 1fr}
		.bbb-app-hub__paid a{background:#ff8ac7;border-color:#ff8ac7;border-radius:4px;color:#1c0b13;min-height:42px}
		.bbb-app-shortcuts{width:min(460px,100%)}
		.bbb-app-shortcuts .bbb-app-hub__panel{opacity:1;transform:none}
		.bbb-app-shortcuts h1{color:#fff7fb;font-size:clamp(38px,10vw,58px);line-height:.94;margin:0 0 4px;text-align:left}
		.bbb-app-shortcuts .bbb-app-hub__kicker{margin-right:0}
		.bbb-app-visitor{width:min(320px,100%);text-align:left}
		.bbb-app-visitor__card{box-sizing:border-box;display:grid;gap:11px;padding:0;text-align:left}
		.bbb-app-visitor__kicker{color:#ff8ac7;font-size:10px;font-weight:900;letter-spacing:.14em;line-height:1.2;margin:0;text-transform:uppercase}
		.bbb-app-visitor h1{color:#fff7fb;font-size:clamp(30px,10vw,42px);line-height:.92;margin:0;text-transform:lowercase}
		.bbb-app-visitor__copy{color:rgba(255,248,251,.68);font-size:13px;line-height:1.45;margin:0;max-width:none}
		.bbb-app-visitor__mini{display:grid;gap:8px;padding:0}
		.bbb-app-visitor__miniTop{align-items:center;display:flex;justify-content:space-between;gap:10px}
		.bbb-app-visitor__miniTop span{color:#ff8ac7;font-size:10px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}
		.bbb-app-visitor__miniTop strong{color:#fff7fb;font-family:Georgia,serif;font-size:22px;font-weight:400;line-height:1;text-transform:lowercase}
		.bbb-app-visitor__rows{display:grid;gap:7px}
		.bbb-app-visitor__row{align-items:center;border:1px solid rgba(255,255,255,.1);border-radius:4px;display:grid;gap:8px;grid-template-columns:34px minmax(0,1fr);min-height:46px;padding:7px}
		.bbb-app-visitor__cover{background:linear-gradient(135deg,#ff8ac7,#5547ff);border-radius:3px;display:block;height:34px;width:34px}
		.bbb-app-visitor__row strong{color:#fff7fb;display:block;font-size:12px;line-height:1.2;text-transform:lowercase}
		.bbb-app-visitor__row small{color:rgba(255,248,251,.58);display:block;font-size:11px;line-height:1.25;margin-top:2px;text-transform:lowercase}
		.bbb-app-visitor__actions{display:grid;gap:8px;grid-template-columns:1fr}
		.bbb-app-visitor__button{border-radius:4px;box-sizing:border-box;min-height:42px;width:100%}
		.bbb-app-visitor__button--primary{background:#ff8ac7;border-color:#ff8ac7;color:#1c0b13}
		@media (min-width:641px) and (max-width:1024px){
			main:has(.bbb-app-visitor){padding:22px 18px}
		}
		@keyframes bbbAppHubIn{0%{opacity:0;transform:translateY(18px) scale(.97)}58%{opacity:1;transform:translateY(-3px) scale(1.01)}100%{opacity:1;transform:translateY(0) scale(1)}}
		@keyframes bbbAppCoverOpen{0%{filter:brightness(.74);transform:perspective(700px) rotateY(-24deg) translateX(-2px)}60%{filter:brightness(1.04);transform:perspective(700px) rotateY(4deg)}100%{filter:brightness(1);transform:perspective(700px) rotateY(0)}}
		@media (prefers-reduced-motion:reduce){.bbb-app-hub,.bbb-app-hub__panel,.bbb-app-hub__newsletter-cover{animation:none!important;transition:none!important;transform:none!important}.bbb-app-hub__panel{opacity:1}}
		@media (max-width:380px){.bbb-app-hub{padding:12px}.bbb-app-hub__panel{border-radius:6px;padding:14px}.bbb-app-hub__newsletter{grid-template-columns:98px 1fr}.bbb-app-hub__newsletter-cover{width:98px}.bbb-app-hub__grid,.bbb-app-hub__paid{grid-template-columns:1fr}}
	</style>
</head>
<body>
	<button class="bbb-app-back" type="button" aria-label="go back" data-app-back hidden>←</button>
	<button class="bbb-app-share" type="button" aria-label="share bybookishbabe app" data-app-share data-share-url="<?php echo esc_url($app_share_url); ?>" data-share-title="bybookishbabe app" data-share-text="save this bookish shortcut">
		<span class="bbb-app-shareIcon" aria-hidden="true">📲</span>
		<span class="bbb-app-share__text" data-app-share-label>share app link</span>
	</button>
	<span class="bbb-app-share__toast" data-app-share-toast role="status" aria-live="polite">link copied</span>
	<main id="MainContent" role="main" tabindex="-1">
		<?php if (!$show_app_hub && ($is_app_home || $is_app_preview)) : ?>
			<section class="bbb-app-visitor" aria-label="locked reader dashboard preview">
				<div class="bbb-app-visitor__card">
					<p class="bbb-app-visitor__kicker">locked dashboard</p>
					<h1>made for you</h1>
					<p class="bbb-app-visitor__copy">Save books, keep your shelf, and open reader picks from the app.</p>
					<div class="bbb-app-visitor__mini" aria-hidden="true">
						<div class="bbb-app-visitor__miniTop">
							<span>preview</span>
							<strong>reader dashboard</strong>
						</div>
						<div class="bbb-app-visitor__rows">
							<div class="bbb-app-visitor__row">
								<span class="bbb-app-visitor__cover"></span>
								<span><strong>latest save</strong><small>unlock with account</small></span>
							</div>
							<div class="bbb-app-visitor__row">
								<span class="bbb-app-visitor__cover"></span>
								<span><strong>society layer</strong><small>join the society</small></span>
							</div>
						</div>
					</div>
					<div class="bbb-app-visitor__actions">
						<a class="bbb-app-visitor__button bbb-app-visitor__button--primary" href="<?php echo esc_url($account_url); ?>">unlock with account</a>
						<a class="bbb-app-visitor__button" href="<?php echo esc_url($society_url); ?>">join the society</a>
					</div>
				</div>
			</section>
		<?php else : ?>
			<section class="bbb-app-shortcuts" aria-label="bybookishbabe app">
				<div class="bbb-app-hub__panel">
					<p class="bbb-app-hub__kicker">reader shortcut</p>
					<h1>welcome back, <?php echo esc_html($reader_first_name); ?></h1>
					<a class="bbb-app-hub__newsletter" href="<?php echo esc_url($newsletter_url); ?>">
						<img class="bbb-app-hub__newsletter-cover" src="<?php echo esc_url($newsletter_image); ?>" alt="<?php echo esc_attr($newsletter_alt); ?>">
						<span>
							<strong>see the latest newsletter</strong>
							<?php echo esc_html($newsletter_title); ?>
						</span>
					</a>
					<div class="bbb-app-hub__grid">
						<a class="bbb-app-hub__tile" href="<?php echo esc_url($bookshelf_url); ?>">
							<small>open</small>
							see bookshelf
						</a>
						<a class="bbb-app-hub__tile" href="<?php echo esc_url($library_url); ?>">
							<small>open</small>
							see library
						</a>
						<a class="bbb-app-hub__quiz" href="<?php echo esc_url($quiz_links[0]['url']); ?>">
							<strong><?php echo esc_html($quiz_links[0]['label']); ?></strong>
							<span><?php echo esc_html($quiz_links[0]['sub']); ?></span>
						</a>
					</div>
					<div class="bbb-app-hub__trope-row" aria-label="popular tropes">
						<?php foreach ($popular_tropes as $trope) : ?>
							<a class="bbb-app-hub__trope" href="<?php echo esc_url($trope['url']); ?>"><span class="bbb-app-hub__trope-emoji" aria-hidden="true"><?php echo esc_html($trope['emoji'] ?? '♡'); ?></span><?php echo esc_html($trope['label']); ?></a>
						<?php endforeach; ?>
					</div>
					<?php if ($is_paid_society) : ?>
						<div class="bbb-app-hub__paid" aria-label="member shortcuts">
							<a href="<?php echo esc_url($dashboard_url); ?>">dashboard</a>
							<a href="<?php echo esc_url($account_url); ?>">account</a>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>
	</main>
	<script>
		(function() {
			var shareButton = document.querySelector('[data-app-share]');
			var shareToast = document.querySelector('[data-app-share-toast]');

			if (!shareButton) {
				return;
			}

			function syncBookModalState() {
				document.body.classList.toggle('bbb-book-modal-open', !!document.querySelector('.sss-lib__modal:not([hidden])'));
			}

			syncBookModalState();

			if (window.MutationObserver) {
				new MutationObserver(syncBookModalState).observe(document.body, {
					attributes: true,
					attributeFilter: ['hidden', 'aria-hidden', 'class'],
					childList: true,
					subtree: true
				});
			}

			function showCopiedToast(message) {
				if (!shareToast) {
					return;
				}

				shareToast.textContent = message || 'link copied';
				shareToast.classList.add('is-visible');
				window.clearTimeout(showCopiedToast.timeout);
				showCopiedToast.timeout = window.setTimeout(function() {
					shareToast.classList.remove('is-visible');
				}, 1800);
			}

			function copyLink(url) {
				if (navigator.clipboard && navigator.clipboard.writeText) {
					return navigator.clipboard.writeText(url);
				}

				var field = document.createElement('textarea');
				field.value = url;
				field.setAttribute('readonly', '');
				field.style.position = 'fixed';
				field.style.left = '-9999px';
				document.body.appendChild(field);
				field.select();

				try {
					document.execCommand('copy');
				} finally {
					document.body.removeChild(field);
				}

				return Promise.resolve();
			}

			shareButton.addEventListener('click', function() {
				var shareUrl = shareButton.getAttribute('data-share-url') || window.location.href;
				var shareData = {
					title: shareButton.getAttribute('data-share-title') || document.title,
					text: shareButton.getAttribute('data-share-text') || '',
					url: shareUrl
				};

				if (navigator.share) {
					navigator.share(shareData).catch(function(error) {
						if (!error || 'AbortError' === error.name) {
							return;
						}
						copyLink(shareUrl).then(function() {
							showCopiedToast('link copied');
						});
					});
					return;
				}

				copyLink(shareUrl).then(function() {
					showCopiedToast('link copied');
				}).catch(function() {
					showCopiedToast('copy failed');
				});
			});
		})();
	</script>
</body>
</html>
