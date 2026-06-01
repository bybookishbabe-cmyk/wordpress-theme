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
	$primary_copy   = $is_ipad
		? 'On iPad, tap the square Share icon in Safari\'s top bar, then tap Add to Home Screen.'
		: 'Tap Share, then Add to Home Screen.';
	$secondary_copy = $is_ipad
		? 'Apple does not allow websites to open that iPad install sheet directly, but this saves bybookishbabe like an app.'
		: 'After you open it from your Home Screen, bybookishbabe can ask if you want bookish alerts.';

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
$app_title         = function_exists('bbb_pwa_request_path_is') && bbb_pwa_request_path_is('bybookishbabe-app-preview') ? 'bybookishbabe app preview' : 'bybookishbabe app';

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
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url(function_exists('bbb_pwa_asset_uri') ? bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png') : get_theme_file_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>">
	<style>
		body{background:#000;color:#f7f4ef;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;min-height:100vh}
		main{box-sizing:border-box;display:grid;min-height:100vh;padding:32px 22px;place-items:center;text-align:center}
		section{width:min(520px,100%)}
		img{border:1px solid rgba(255,255,255,.16);border-radius:18px;display:block;height:78px;margin:0 auto 24px;width:78px}
		p{color:rgba(247,244,239,.62);font-size:16px;line-height:1.5;margin:0 auto 22px;max-width:360px}
		h1{font-family:Georgia,serif;font-size:clamp(42px,13vw,76px);font-weight:400;letter-spacing:0;line-height:.88;margin:0 0 18px;text-transform:lowercase}
		nav{display:flex;flex-wrap:wrap;gap:10px;justify-content:center}
		a{align-items:center;border:1px solid rgba(255,255,255,.18);border-radius:999px;color:#f7f4ef;display:inline-flex;font-size:13px;font-weight:800;justify-content:center;min-height:40px;padding:0 16px;text-decoration:none;text-transform:lowercase}
	</style>
</head>
<body>
	<main id="MainContent" role="main" tabindex="-1">
		<section aria-label="bybookishbabe app reset">
			<img src="<?php echo esc_url(function_exists('bbb_pwa_asset_uri') ? bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png') : get_theme_file_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>" alt="">
			<h1>welcome back, <?php echo esc_html($reader_first_name); ?></h1>
			<p>the app home is being rebuilt from scratch.</p>
			<nav aria-label="temporary app links">
				<a href="<?php echo esc_url($account_url); ?>">account</a>
				<a href="<?php echo esc_url($dashboard_url); ?>">member dashboard</a>
				<a href="<?php echo esc_url($bookshelf_url); ?>">bookshelf</a>
			</nav>
		</section>
	</main>
</body>
</html>
