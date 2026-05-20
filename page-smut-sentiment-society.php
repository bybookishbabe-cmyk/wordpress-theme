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
		'tone'  => 'pink',
		'badge' => 'open',
		'copy'  => 'peek inside the society, see what is free, and decide where you want to start.',
		'links' => array('about the society', 'recent issues', 'join free'),
	),
	array(
		'slug'  => 'free member',
		'title' => 'free member',
		'url'   => bbb_page_url('society-free-member'),
		'tone'  => 'teal',
		'badge' => 'free',
		'copy'  => 'use the free shelf, preview member picks, and keep your reader profile warm.',
		'links' => array('my bookshelf', 'free previews', 'reader updates'),
	),
	array(
		'slug'  => 'paid member',
		'title' => 'paid member',
		'url'   => bbb_page_url('society-paid-member'),
		'tone'  => 'amber',
		'badge' => 'society',
		'copy'  => 'enter the full smut and sentiment society archive, tools, printables, and member shelves.',
		'links' => array('full archive', 'printable pack', 'member tools'),
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
				choose your doorway: visitor, free member, or paid member. each path will get its own page next.
			</p>
			<div class="bbb-society-landing__status">
				<span>current view</span>
				<strong><?php echo esc_html($reader_state); ?></strong>
			</div>
		</div>

		<aside class="bbb-society-theme" aria-label="<?php echo esc_attr($monthly_theme); ?>">
			<div class="bbb-society-theme__icon" aria-hidden="true">s</div>
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

		<div class="bbb-society-paths" aria-label="society paths">
			<?php foreach ($paths as $path) : ?>
				<a class="bbb-society-path bbb-society-path--<?php echo esc_attr($path['tone']); ?>" href="<?php echo esc_url($path['url']); ?>">
					<span class="bbb-society-path__badge"><?php echo esc_html($path['badge']); ?></span>
					<span class="bbb-society-path__icon" aria-hidden="true"><?php echo esc_html(substr($path['slug'], 0, 1)); ?></span>
					<span class="bbb-society-path__body">
						<span class="bbb-society-path__title"><?php echo esc_html($path['title']); ?></span>
						<span class="bbb-society-path__copy"><?php echo esc_html($path['copy']); ?></span>
						<span class="bbb-society-path__links">
							<?php foreach ($path['links'] as $link) : ?>
								<span><?php echo esc_html($link); ?></span>
							<?php endforeach; ?>
						</span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php
get_footer();
