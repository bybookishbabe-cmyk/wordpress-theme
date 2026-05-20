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

$paths = array(
	array(
		'slug'  => 'visitor',
		'title' => 'visitor',
		'url'   => bbb_page_url('society-visitor'),
	),
	array(
		'slug'  => 'free member',
		'title' => 'free member',
		'url'   => bbb_page_url('society-free-member'),
	),
	array(
		'slug'  => 'paid member',
		'title' => 'paid member',
		'url'   => bbb_page_url('society-paid-member'),
	),
);

$sections = array(
	array(
		'label' => 'the newsletter',
		'items' => array(
			array('title' => 'visitor page', 'copy' => 'what the society is, what is free, and where to begin.', 'url' => bbb_page_url('society-visitor'), 'badge' => 'open'),
			array('title' => 'free member page', 'copy' => 'recent issues, reader updates, and preview access.', 'url' => bbb_page_url('society-free-member'), 'badge' => 'free'),
			array('title' => 'paid member page', 'copy' => 'full archive, monthly extras, and paid member notes.', 'url' => bbb_page_url('society-paid-member'), 'badge' => 'society'),
		),
	),
	array(
		'label' => 'society exclusives',
		'items' => array(
			array('title' => 'reading guides', 'copy' => 'deep-dive trope guides and member-only rec lists.', 'url' => bbb_page_url('society-paid-member'), 'badge' => 'society'),
			array('title' => 'exclusive rec lists', 'copy' => 'book lists that do not live on the public blog.', 'url' => bbb_page_url('society-paid-member'), 'badge' => 'society'),
			array('title' => 'early access', 'copy' => 'posts and picks before they go public.', 'url' => bbb_page_url('society-free-member'), 'badge' => 'preview'),
		),
	),
	array(
		'label' => 'member tools',
		'items' => array(
			array('title' => 'reading tracker', 'copy' => 'tbr, ratings, notes, and reading history.', 'url' => bbb_page_url('my-bookshelf'), 'badge' => 'society'),
			array('title' => 'ai rec tool', 'copy' => 'a conversational recommender for your current mood.', 'url' => bbb_page_url('what-to-read-next'), 'badge' => 'preview'),
			array('title' => 'reading challenge', 'copy' => 'monthly prompts and themed reading goals.', 'url' => bbb_page_url('society-paid-member'), 'badge' => 'society'),
		),
	),
	array(
		'label' => 'shop perks',
		'items' => array(
			array('title' => 'monthly freebie', 'copy' => 'a rotating digital good for paid members.', 'url' => bbb_page_url('society-paid-member'), 'badge' => 'society'),
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
				a central page for the newsletter, the member paths, and the pieces that live behind each level.
			</p>
			<div class="bbb-society-landing__status">
				<span class="bbb-society-landing__statusLabel">current view</span>
				<strong><?php echo esc_html($reader_state); ?></strong>
				<nav class="bbb-society-view-nav" aria-label="society view pages">
					<?php foreach ($paths as $path) : ?>
						<a class="<?php echo $reader_state === $path['title'] ? 'is-active' : ''; ?>" href="<?php echo esc_url($path['url']); ?>">
							<?php echo esc_html($path['title']); ?>
						</a>
					<?php endforeach; ?>
				</nav>
			</div>
		</div>

		<aside class="bbb-society-theme" aria-label="<?php echo esc_attr($monthly_theme); ?>">
			<div>
				<p class="bbb-society-theme__eyebrow"><?php echo esc_html($monthly_theme); ?>. live now</p>
				<h2>burn for me</h2>
				<p>dark romance month with mafia, obsession, enemies to lovers, and a little emotional damage.</p>
				<div class="bbb-society-theme__links" aria-label="theme links">
					<a href="<?php echo esc_url(bbb_page_url('reading-list')); ?>">reading list</a>
					<a href="<?php echo esc_url(bbb_page_url('weekly-obsession')); ?>">newsletter issue</a>
					<a href="<?php echo esc_url(bbb_page_url('society-paid-member')); ?>">printable pack</a>
				</div>
			</div>
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
