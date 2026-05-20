<?php
/**
 * Template Name: about the society
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

get_header();
?>

<section class="bbb-society-page" aria-labelledby="bbb-about-society-title">
	<div class="bbb-society-page__inner">
		<header class="bbb-society-page__header">
			<p class="bbb-society-landing__eyebrow">the newsletter</p>
			<h1 id="bbb-about-society-title">about the society</h1>
			<p>
				the smut and sentiment society is the reader room for romance recs, newsletter dispatches, monthly themes,
				and the little extras that make a tbr feel personal.
			</p>
		</header>

		<div class="bbb-society-about-grid">
			<section class="bbb-society-about-panel">
				<h2>what lives here</h2>
				<p>weekly notes, recent obsessions, trope trails, reading lists, and member-only shelves built around the kind of romance you actually want to read next.</p>
			</section>
			<section class="bbb-society-about-panel">
				<h2>how to use it</h2>
				<p>start with the recent issues, browse the archive when you want a backlist rabbit hole, then move into member tools when you want tracking, challenges, and extras.</p>
			</section>
			<section class="bbb-society-about-panel">
				<h2>who it is for</h2>
				<p>romance readers who want the sparkle and the structure: mood-based recs, themed reading months, and a softer way to organize the books they love.</p>
			</section>
		</div>

		<section class="bbb-society-section">
			<h2>newsletter doors</h2>
			<div class="bbb-society-link-grid">
				<a class="bbb-society-link-card" href="<?php echo esc_url(bbb_page_url('society-newsletter-recent')); ?>">
					<span class="bbb-society-link-card__top">
						<span class="bbb-society-link-card__title">recent</span>
						<span class="bbb-society-link-card__badge">latest</span>
					</span>
					<span class="bbb-society-link-card__copy">read the newest society dispatches.</span>
				</a>
				<a class="bbb-society-link-card" href="<?php echo esc_url(bbb_page_url('society-newsletter-archive')); ?>">
					<span class="bbb-society-link-card__top">
						<span class="bbb-society-link-card__title">full archive</span>
						<span class="bbb-society-link-card__badge">all issues</span>
					</span>
					<span class="bbb-society-link-card__copy">browse every imported newsletter issue.</span>
				</a>
				<a class="bbb-society-link-card" href="https://thesmutandsentimentsociety.substack.com/subscribe" target="_blank" rel="noopener">
					<span class="bbb-society-link-card__top">
						<span class="bbb-society-link-card__title">subscribe</span>
						<span class="bbb-society-link-card__badge">substack</span>
					</span>
					<span class="bbb-society-link-card__copy">join the list where the dispatches are sent.</span>
				</a>
			</div>
		</section>
	</div>
</section>

<?php
get_footer();
