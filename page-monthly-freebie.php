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
$print_path = get_theme_file_path('assets/freebies/may-2026-bookend-8x10-art-print.php');
$download_url = add_query_arg('download', 'print', bbb_page_url('monthly-freebie'));
$preview_url = get_theme_file_uri('assets/freebies/may-2026-bookend-8x10-art-print-mockup.jpg');

if (isset($_GET['download']) && 'print' === sanitize_key((string) wp_unslash($_GET['download']))) {
	$print_contents = file_exists($print_path) ? file_get_contents($print_path) : false;
	$halt_marker = '__halt_compiler(); ?>';
	$halt_position = is_string($print_contents) ? strpos($print_contents, $halt_marker) : false;
	$print_binary = false !== $halt_position && is_string($print_contents)
		? substr($print_contents, $halt_position + strlen($halt_marker))
		: '';

	if (!$is_society_member || '' === $print_binary) {
		wp_redirect($join_url);
		exit;
	}

	nocache_headers();
	header('Content-Type: image/png');
	header('Content-Disposition: attachment; filename="may-2026-bookend-8x10-art-print.png"');
	header('Content-Length: ' . (string) strlen($print_binary));
	echo $print_binary;
	exit;
}

get_header();
?>

<section class="bbb-society-page bbb-monthly-freebie" aria-labelledby="bbb-monthly-freebie-title">
	<div class="bbb-society-page__inner bbb-monthly-freebie__inner">
		<header class="bbb-society-page__header bbb-monthly-freebie__header">
			<p class="bbb-society-landing__eyebrow">monthly freebie</p>
			<h1 id="bbb-monthly-freebie-title">8x10 art print</h1>
			<p>
				this month's society freebie is a printable bookish art piece made for frames, reading corners,
				and the little wall spaces that deserve a softer love story.
			</p>
		</header>

		<div class="bbb-monthly-freebie__layout">
			<figure class="bbb-monthly-freebie__art">
				<img src="<?php echo esc_url($preview_url); ?>" alt="framed mockup preview of the may society 8 by 10 art print" loading="eager">
			</figure>

			<aside class="bbb-monthly-freebie__panel" aria-label="monthly freebie details">
				<p class="bbb-society-landing__eyebrow">may society file</p>
				<h2>in every book</h2>
				<p>
					a 2400 by 3000 pixel png designed at 8x10 inches. download it, print it, and let it look
					like it was always meant to live beside your shelf.
				</p>

				<ul class="bbb-monthly-freebie__facts" aria-label="print details">
					<li><span>size</span><strong>8x10 inches</strong></li>
					<li><span>file</span><strong>png</strong></li>
					<li><span>resolution</span><strong>2400x3000</strong></li>
				</ul>

				<?php if ($is_society_member) : ?>
					<a class="bbb-monthly-freebie__button" href="<?php echo esc_url($download_url); ?>" download>download the print</a>
					<p class="bbb-monthly-freebie__note">included with your society membership.</p>
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
