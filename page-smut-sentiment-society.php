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
	'kicker' => strtolower((string) get_theme_mod('bbb_society_month_kicker', 'this month inside the society')),
	'title'  => strtolower((string) get_theme_mod('bbb_society_month_title', 'burn for me')),
	'text'   => strtolower((string) get_theme_mod('bbb_society_month_text', 'dark romance month with mafia, obsession, enemies to lovers, and the member tools that keep the whole reading life in one place.')),
);

$monthly_hub_links = array();
for ($i = 1; $i <= 6; $i++) {
	$label = strtolower((string) get_theme_mod("bbb_society_month_link_{$i}_label", ''));
	$url   = (string) get_theme_mod("bbb_society_month_link_{$i}_url", '');
	if ('' === trim($label) || '' === trim($url)) {
		continue;
	}

	$monthly_hub_links[] = array(
		'label' => $label,
		'url'   => bbb_resolve_shopify_url($url),
	);
}

$sections = array(
	array(
		'label' => 'the newsletter',
		'items' => array(
			array('title' => 'about', 'copy' => 'what the society is, who it is for, and how the newsletter fits in.', 'url' => bbb_page_url('about-the-society'), 'badge' => 'start'),
			array('title' => 'recent', 'copy' => 'the latest newsletter issues and current dispatches.', 'url' => bbb_page_url('society-newsletter-recent'), 'badge' => 'latest'),
			array('title' => 'full archive', 'copy' => 'the complete newsletter shelf, wired to the imported issues.', 'url' => bbb_page_url('society-newsletter-archive'), 'badge' => 'archive'),
		),
	),
	array(
		'label' => 'society exclusives',
		'items' => array(
			array('title' => 'reading guides', 'copy' => 'deep-dive trope guides and member-only rec lists.', 'url' => bbb_page_url('society-library'), 'badge' => 'society'),
			array('title' => 'exclusive rec lists', 'copy' => 'book lists that do not live on the public blog.', 'url' => bbb_page_url('society-library'), 'badge' => 'society'),
			array('title' => 'early access', 'copy' => 'posts and picks before they go public.', 'url' => bbb_page_url('society-newsletter-recent'), 'badge' => 'preview'),
		),
	),
	array(
		'label' => 'member tools',
		'items' => array(
			array('title' => 'book tracking calendar', 'copy' => 'the shopify read tracker: click a day, choose the book you read, and let the cover live there.', 'url' => bbb_page_url('sss-library'), 'badge' => 'society'),
			array('title' => 'my bookshelf', 'copy' => 'your saved books, current obsessions, and personal romance archive.', 'url' => bbb_page_url('my-bookshelf'), 'badge' => 'free'),
			array('title' => 'your dashboard', 'copy' => 'made-for-you reader logic, mood-based recommendations, and smarter next-read picks.', 'url' => bbb_page_url('what-to-read-next'), 'badge' => 'preview'),
		),
	),
	array(
		'label' => 'shop perks',
		'items' => array(
			array('title' => 'monthly freebie', 'copy' => 'a rotating digital good for paid members.', 'url' => bbb_page_url('shop'), 'badge' => 'society'),
			array('title' => 'shop discount', 'copy' => 'member savings on templates, printables, and extras.', 'url' => bbb_page_url('shop'), 'badge' => 'society'),
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
			<div class="bbb-society-theme__header">
				<p class="bbb-society-theme__eyebrow"><?php echo esc_html($monthly_hub['kicker']); ?></p>
				<h2><?php echo esc_html($monthly_hub['title']); ?></h2>
				<p><?php echo esc_html($monthly_hub['text']); ?></p>
			</div>
			<?php if ($monthly_hub_links) : ?>
				<div class="bbb-society-main-hub" aria-label="monthly society hub links">
					<?php foreach ($monthly_hub_links as $link) : ?>
						<a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</aside>

		<div class="bbb-society-sections">
			<?php foreach ($sections as $section) : ?>
				<section class="bbb-society-section" aria-labelledby="<?php echo esc_attr(sanitize_title($section['label'])); ?>">
					<h2 id="<?php echo esc_attr(sanitize_title($section['label'])); ?>"><?php echo esc_html($section['label']); ?></h2>
					<div class="bbb-society-link-grid">
						<?php foreach ($section['items'] as $item) : ?>
							<a class="bbb-society-link-card" href="<?php echo esc_url($item['url']); ?>">
								<span class="bbb-society-link-card__top">
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
