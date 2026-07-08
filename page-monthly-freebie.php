<?php
/**
 * Template Name: Society Monthly Freebie
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$reader_identity = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
$is_society_member = is_array($reader_identity) && '' !== trim((string) ($reader_identity['email'] ?? ''));
$join_url = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$asset_base = 'assets/monthly-themes/july-2026';
$bookmarks_pdf_path = $asset_base . '/freebies/midnight-summer-bookmarks.pdf';
$download_url = function_exists('bbb_forced_theme_asset_download_url')
	? bbb_forced_theme_asset_download_url($bookmarks_pdf_path, 'MidnightSummer_Bookmarks.pdf')
	: get_theme_file_uri($bookmarks_pdf_path);
$preview_url = get_theme_file_uri($asset_base . '/display/midnight-summer-bookmark-mockup.jpg');

if (!function_exists('bbb_monthly_freebie_has_private_cache_context')) {
	function bbb_monthly_freebie_has_private_cache_context(): bool {
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

if (!$is_society_member && !bbb_monthly_freebie_has_private_cache_context()) {
	$bbb_freebie_public_cache = 'public, max-age=300, s-maxage=900, stale-while-revalidate=86400';
	add_filter(
		'nocache_headers',
		static function (array $headers) use ($bbb_freebie_public_cache): array {
			return array(
				'Cache-Control' => $bbb_freebie_public_cache,
				'Expires'       => gmdate('D, d M Y H:i:s', time() + 300) . ' GMT',
			);
		},
		100
	);

	if (!headers_sent()) {
		header_remove('Pragma');
		header_remove('Expires');
		header('Cache-Control: ' . $bbb_freebie_public_cache, true);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT', true);
	}
}

get_header();
?>

<section class="bbb-society-page bbb-monthly-freebie" aria-labelledby="bbb-monthly-freebie-title">
	<div class="bbb-society-page__inner bbb-monthly-freebie__inner">
		<header class="bbb-society-page__header bbb-monthly-freebie__header">
			<p class="bbb-society-landing__eyebrow">monthly freebie</p>
			<h1 id="bbb-monthly-freebie-title">printable bookmarks</h1>
			<p>
				this month's society freebie is a set of midnight summer bookmarks made for the books
				that keep you reading past reasonable hours.
			</p>
		</header>

		<div class="bbb-monthly-freebie__layout">
			<figure class="bbb-monthly-freebie__art">
				<img src="<?php echo esc_url($preview_url); ?>" alt="Midnight Summer printable bookmark mockup" loading="eager">
			</figure>

			<aside class="bbb-monthly-freebie__panel" aria-label="monthly freebie details">
				<p class="bbb-society-landing__eyebrow">july society file</p>
				<h2>midnight summer bookmarks</h2>
				<p>
					download the PDF, print it, trim it, and keep the after-hours mood tucked inside your current read.
				</p>

				<ul class="bbb-monthly-freebie__facts" aria-label="print details">
					<li><span>type</span><strong>printable bookmarks</strong></li>
					<li><span>file</span><strong>pdf</strong></li>
					<li><span>access</span><strong>free + paid members</strong></li>
				</ul>

				<?php if ($is_society_member) : ?>
					<a class="bbb-monthly-freebie__button" href="<?php echo esc_url($download_url); ?>" download>download bookmarks</a>
					<p class="bbb-monthly-freebie__note">included for free and paid society members.</p>
				<?php else : ?>
					<a class="bbb-monthly-freebie__button" href="<?php echo esc_url($join_url); ?>" target="_blank" rel="noopener">join to download</a>
					<p class="bbb-monthly-freebie__note">free and paid society members can download the monthly freebie here.</p>
				<?php endif; ?>

				<a class="bbb-monthly-freebie__back" href="<?php echo esc_url(bbb_page_url('smut-sentiment-society')); ?>">back to the society</a>
			</aside>
		</div>
	</div>
</section>

<?php
get_footer();
