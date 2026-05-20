<?php
/**
 * Template Name: the society landing
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$reader_state = 'visitor';
if (function_exists('bbb_reader_is_society') && bbb_reader_is_society()) {
	$reader_state = 'paid member';
} elseif (is_user_logged_in()) {
	$reader_state = 'free member';
}

$monthly_theme = strtolower((string) date_i18n('F')) . ' theme';
$monthly_hub   = array(
	'kicker' => strtolower((string) get_theme_mod('bbb_society_month_kicker', 'monthly theme')),
	'title'  => strtolower((string) get_theme_mod('bbb_society_month_title', 'burn for me')),
	'text'   => strtolower((string) get_theme_mod('bbb_society_month_text', 'dark romance month with mafia, obsession, enemies to lovers, and the member tools that keep the whole reading life in one place.')),
);
if ('this month inside the society' === $monthly_hub['kicker']) {
	$monthly_hub['kicker'] = 'monthly theme';
}
$monthly_theme_url = bbb_page_url('monthly-theme');
$drop_export_path = get_theme_file_path('firstpass/migration/exports/metaobjects/sss_drop.json');
if (file_exists($drop_export_path)) {
	$drop_export = json_decode((string) file_get_contents($drop_export_path), true);
	if (is_array($drop_export) && !empty($drop_export['entries']) && is_array($drop_export['entries'])) {
		$active_drop = array();
		$active_time = 0;
		foreach ($drop_export['entries'] as $entry) {
			if (!is_array($entry) || empty($entry['fields']) || !is_array($entry['fields'])) {
				continue;
			}

			$fields = array();
			foreach ($entry['fields'] as $field) {
				if (is_array($field) && !empty($field['key'])) {
					$fields[(string) $field['key']] = $field;
				}
			}

			$date = (string) ($fields['release_date']['value'] ?? '');
			$time = '' !== $date ? strtotime($date . ' 00:00:00') : false;
			if ($time && $time <= (int) current_time('timestamp') && $time >= $active_time) {
				$active_drop = $fields;
				$active_time = $time;
			}
		}

		if ($active_drop) {
			$monthly_hub['title'] = strtolower((string) ($active_drop['name']['value'] ?? $monthly_hub['title']));
			$monthly_hub['text'] = strtolower((string) ($active_drop['quote_text']['value'] ?? $monthly_hub['text']));
		}
	}
}

$sections = array(
	array(
		'label' => 'the newsletter',
		'items' => array(
			array('title' => 'about', 'copy' => 'what the society is, who it is for, and how the newsletter fits in.', 'url' => bbb_page_url('about-the-society'), 'badge' => 'start', 'emoji' => '💌'),
			array('title' => 'recent', 'copy' => 'the latest newsletter issues and current dispatches.', 'url' => bbb_page_url('society-newsletter-recent'), 'badge' => 'latest', 'emoji' => '🗞️'),
			array('title' => 'full archive', 'copy' => 'the complete newsletter shelf, wired to the imported issues.', 'url' => bbb_page_url('society-newsletter-archive'), 'badge' => 'archive', 'emoji' => '🗂️'),
		),
	),
	array(
		'label' => 'society exclusives',
		'items' => array(
			array('title' => 'reading guides', 'copy' => 'deep-dive trope guides and member-only rec lists.', 'url' => bbb_page_url('society-library'), 'badge' => 'society', 'emoji' => '📖'),
			array('title' => 'exclusive rec lists', 'copy' => 'book lists that do not live on the public blog.', 'url' => bbb_page_url('society-library'), 'badge' => 'society', 'emoji' => '🌹'),
			array('title' => 'early access', 'copy' => 'posts and picks before they go public.', 'url' => bbb_page_url('society-newsletter-recent'), 'badge' => 'preview', 'emoji' => '🔐'),
		),
	),
	array(
		'label' => 'member tools',
		'items' => array(
			array('title' => 'book tracking calendar', 'copy' => 'the shopify read tracker: click a day, choose the book you read, and let the cover live there.', 'url' => bbb_page_url('sss-library'), 'badge' => 'society', 'emoji' => '📅'),
			array('title' => 'my bookshelf', 'copy' => 'your saved books, current obsessions, and personal romance archive.', 'url' => bbb_page_url('my-bookshelf'), 'badge' => 'free', 'emoji' => '📚'),
			array('title' => 'member dashboard', 'copy' => 'made-for-you reader logic, mood-based recommendations, and smarter next-read picks.', 'url' => bbb_page_url('member-dashboard'), 'badge' => 'society', 'emoji' => '✨'),
		),
	),
	array(
		'label' => 'shop perks',
		'items' => array(
			array('title' => 'monthly freebie', 'copy' => 'a rotating digital good for paid members.', 'url' => bbb_page_url('shop'), 'badge' => 'society', 'emoji' => '🎁'),
			array('title' => 'shop discount', 'copy' => 'member savings on templates, printables, and extras.', 'url' => bbb_page_url('shop'), 'badge' => 'society', 'emoji' => '🏷️'),
		),
	),
);

get_header();
?>

<section class="bbb-society-landing" aria-labelledby="bbb-society-title">
	<div class="bbb-society-landing__inner">
		<div class="bbb-society-landing__hero">
			<p class="bbb-society-landing__eyebrow">the smut and sentiment society</p>
			<h1 id="bbb-society-title">the society</h1>
			<p class="bbb-society-landing__intro">
				a central page for the newsletter, the archive, and the society pieces that live around each issue.
			</p>
			<div class="bbb-society-landing__status">
				<span class="bbb-society-landing__statusLabel">current view</span>
				<strong><?php echo esc_html($reader_state); ?></strong>
			</div>
		</div>

		<aside class="bbb-society-theme bbb-society-theme--main" aria-label="<?php echo esc_attr($monthly_theme); ?>">
			<a class="bbb-society-theme__featureLink" href="<?php echo esc_url($monthly_theme_url); ?>">
				<p class="bbb-society-theme__eyebrow"><?php echo esc_html($monthly_hub['kicker']); ?></p>
				<h2><?php echo esc_html($monthly_hub['title']); ?></h2>
				<p><?php echo esc_html($monthly_hub['text']); ?></p>
				<span class="bbb-society-theme__cta">open monthly theme</span>
			</a>
		</aside>

		<div class="bbb-society-sections">
			<?php foreach ($sections as $section) : ?>
				<section class="bbb-society-section" aria-labelledby="<?php echo esc_attr(sanitize_title($section['label'])); ?>">
					<h2 id="<?php echo esc_attr(sanitize_title($section['label'])); ?>"><?php echo esc_html($section['label']); ?></h2>
					<div class="bbb-society-link-grid">
						<?php foreach ($section['items'] as $item) : ?>
							<a class="bbb-society-link-card" href="<?php echo esc_url($item['url']); ?>">
								<span class="bbb-society-link-card__top">
									<span class="bbb-society-link-card__emoji" aria-hidden="true"><?php echo esc_html($item['emoji'] ?? '♡'); ?></span>
									<span class="bbb-society-link-card__title"><?php echo esc_html($item['title']); ?></span>
									<span class="bbb-society-link-card__badge"><?php echo esc_html($item['badge']); ?></span>
								</span>
								<span class="bbb-society-link-card__copy"><?php echo esc_html($item['copy']); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php
get_footer();
