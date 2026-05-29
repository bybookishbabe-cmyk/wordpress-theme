<?php
/**
 * Template Name: media kit
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$stats  = function_exists('bbb_media_kit_stats') ? bbb_media_kit_stats() : array();
$manual = is_array($stats['manual'] ?? null) ? $stats['manual'] : array();

$hero_stats = array(
	array(
		'value' => function_exists('bbb_media_kit_number') ? bbb_media_kit_number($stats['total_subscribers'] ?? null) : 'updating',
		'label' => 'reader emails',
	),
	array(
		'value' => function_exists('bbb_media_kit_number') ? bbb_media_kit_number($stats['society_members'] ?? null) : 'updating',
		'label' => 'society members',
	),
	array(
		'value' => function_exists('bbb_media_kit_number') ? bbb_media_kit_number($stats['library_books'] ?? null) : 'updating',
		'label' => 'library books',
	),
	array(
		'value' => function_exists('bbb_media_kit_number') ? bbb_media_kit_number($stats['newsletter_issues'] ?? null) : 'updating',
		'label' => 'newsletter issues',
	),
);

$numbers = array(
	array(
		'value' => function_exists('bbb_media_kit_number') ? bbb_media_kit_number($stats['total_subscribers'] ?? null) : 'updating',
		'label' => 'opted-in readers',
		'text'  => 'Reader emails synced from the site and society subscriber flow.',
	),
	array(
		'value' => function_exists('bbb_media_kit_number') ? bbb_media_kit_number($stats['society_members'] ?? null) : 'updating',
		'label' => 'paid society layer',
		'text'  => 'Paid/member access currently recognized by the site sync.',
	),
	array(
		'value' => function_exists('bbb_media_kit_number') ? bbb_media_kit_number($stats['saved_books'] ?? null) : 'updating',
		'label' => 'saved book actions',
		'text'  => 'Active books readers have saved into their bookshelf.',
	),
	array(
		'value' => function_exists('bbb_media_kit_number') ? bbb_media_kit_number($stats['read_marks'] ?? null) : 'updating',
		'label' => 'finished reads tracked',
		'text'  => 'Books readers have marked read inside the site experience.',
	),
);

$platforms = array(
	array(
		'title'  => 'newsletter',
		'handle' => 'the smut and sentiment society',
		'text'   => 'The core channel: one curated romance recommendation delivered every Sunday, with paid society context layered behind it.',
		'badge'  => 'primary',
	),
	array(
		'title'  => 'website',
		'handle' => 'bybookishbabe.com',
		'text'   => 'Searchable romance guides, book-library paths, reader quizzes, and member tools built for intent-driven discovery.',
		'badge'  => 'owned',
	),
	array(
		'title'  => 'instagram',
		'handle' => '@bybookishbabe',
		'text'   => 'Shelf styling, reader-life proof, and visual touchpoints around the recommendation engine.',
		'badge'  => (string) ($manual['instagram_followers'] ?? ''),
	),
	array(
		'title'  => 'tiktok',
		'handle' => '@bybookishbabe',
		'text'   => 'Fast book recs, reading updates, and community discovery moments.',
		'badge'  => (string) ($manual['tiktok_followers'] ?? ''),
	),
);

$content_cards = array(
	array('icon' => '01', 'title' => 'curated romance recs', 'text' => 'Specific reader-first recommendations by trope, spice, mood, and emotional damage level.'),
	array('icon' => '02', 'title' => 'newsletter features', 'text' => 'Sponsored or editorial placements that sit inside a high-intent weekly reading ritual.'),
	array('icon' => '03', 'title' => 'reader tools', 'text' => 'Quizzes, libraries, saved shelves, and themed pages that keep readers clicking deeper.'),
);

$packages = array(
	array('number' => '01', 'type' => 'sponsored', 'title' => 'newsletter feature', 'text' => 'A paid feature built around one book, product, or reader offer.', 'includes' => array('editorial placement', 'tracked link', 'light website support'), 'featured' => false),
	array('number' => '02', 'type' => 'campaign', 'title' => 'reader path package', 'text' => 'A richer feature that connects newsletter, site, and social entry points.', 'includes' => array('newsletter placement', 'site section or guide mention', 'optional social support'), 'featured' => true),
	array('number' => '03', 'type' => 'custom', 'title' => 'brand collaboration', 'text' => 'A bespoke partnership for publishers, authors, bookish products, or reader-adjacent brands.', 'includes' => array('custom creative', 'audience-fit concept', 'performance recap'), 'featured' => false),
);

$audiences = array(
	array('label' => 'reader profile', 'title' => 'romance readers with intent', 'text' => 'People looking for their next obsession, not passive scrollers. They search by trope, spice, mood, book boyfriend, and reading problem.', 'tags' => array('romance', 'kindle readers', 'booktok-adjacent', 'trope-driven')),
	array('label' => 'buying context', 'title' => 'bookish buyers and subscribers', 'text' => 'Readers who opt into recommendations, save books, join the society, and return to tools that make choosing easier.', 'tags' => array('email-first', 'paid membership', 'digital downloads', 'recommendation-led')),
);

$partners = array(
	array('title' => 'Publishers and authors', 'text' => ' Romance launches, backlist discovery, series awareness, and trope-specific campaigns.'),
	array('title' => 'Bookish products', 'text' => ' Kindle accessories, reading journals, stickers, digital templates, and reader lifestyle offers.'),
	array('title' => 'Subscription brands', 'text' => ' Boxes, memberships, apps, and services that fit a romance reader ritual.'),
	array('title' => 'Aligned small businesses', 'text' => ' Pretty, useful, reader-adjacent things with a real audience fit.'),
);

get_header();
?>

<main id="primary" class="site-main bbb-mk" aria-labelledby="bbb-media-kit-title">
	<section class="bbb-mk__hero">
		<div class="bbb-mk__halo" aria-hidden="true"></div>
		<div class="bbb-mk__wrap bbb-mk__heroInner">
			<p class="bbb-mk__eyebrow">media kit 2026</p>
			<h1 class="bbb-mk__title" id="bbb-media-kit-title">bybookishbabe</h1>
			<p class="bbb-mk__subtitle">smut meets sentiment for soft hearts with sinful taste.</p>
			<p class="bbb-mk__ornament">the smut and sentiment society</p>
			<div class="bbb-mk__heroStats" aria-label="media kit highlights">
				<?php foreach ($hero_stats as $stat) : ?>
					<div class="bbb-mk__heroStat">
						<span class="bbb-mk__heroStatNum"><?php echo esc_html($stat['value']); ?></span>
						<span class="bbb-mk__heroStatLabel"><?php echo esc_html($stat['label']); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="bbb-mk__tagline"><?php echo esc_html(function_exists('bbb_media_kit_updated_label') ? bbb_media_kit_updated_label($stats) : 'live refresh'); ?></p>
		</div>
	</section>

	<section class="bbb-mk__band">
		<div class="bbb-mk__wrap">
			<div class="bbb-mk__section bbb-mk__about">
				<div class="bbb-mk__sectionHead">
					<p class="bbb-mk__kicker">about</p>
					<h2 class="bbb-mk__sectionTitle">the brand</h2>
				</div>
				<div class="bbb-mk__aboutGrid">
					<div class="bbb-mk__copy">
						<p>bybookishbabe is a romance book recommendation platform built for readers who take their reading life seriously and enjoy it sinfully. It is home to the Smut and Sentiment Society, a weekly newsletter featuring one curated romance recommendation delivered every Sunday.</p>
						<p>The brand lives at the intersection of aesthetic curation and genuine reader obsession. Content is specific, intentional, and deeply embedded in the romance reading community.</p>
						<p>No lazy roundups. No algorithmic filler. Just one-reader-to-another recs, delivered with taste.</p>
					</div>
					<div class="bbb-mk__factList" aria-label="brand facts">
						<div class="bbb-mk__fact"><span class="bbb-mk__factKey">platform</span><span class="bbb-mk__factValue">newsletter + website</span></div>
						<div class="bbb-mk__fact"><span class="bbb-mk__factKey">niche</span><span class="bbb-mk__factValue">romance readers</span></div>
						<div class="bbb-mk__fact"><span class="bbb-mk__factKey">cadence</span><span class="bbb-mk__factValue">weekly sunday recs</span></div>
						<div class="bbb-mk__fact"><span class="bbb-mk__factKey">collabs</span><span class="bbb-mk__factValue">paid only</span></div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="bbb-mk__band bbb-mk__band--alt">
		<div class="bbb-mk__wrap">
			<div class="bbb-mk__section">
				<div class="bbb-mk__sectionHead">
					<p class="bbb-mk__kicker">by the numbers</p>
					<h2 class="bbb-mk__sectionTitle">real stats, real readers</h2>
				</div>
				<div class="bbb-mk__numbers">
					<?php foreach ($numbers as $number) : ?>
						<article class="bbb-mk__numberCard">
							<p class="bbb-mk__numberValue"><?php echo esc_html($number['value']); ?></p>
							<h3 class="bbb-mk__cardLabel"><?php echo esc_html($number['label']); ?></h3>
							<p class="bbb-mk__smallText"><?php echo esc_html($number['text']); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="bbb-mk__band">
		<div class="bbb-mk__wrap">
			<div class="bbb-mk__section">
				<div class="bbb-mk__sectionHead">
					<p class="bbb-mk__kicker">where i live</p>
					<h2 class="bbb-mk__sectionTitle">platforms</h2>
				</div>
				<div class="bbb-mk__platforms">
					<?php foreach ($platforms as $platform) : ?>
						<article class="bbb-mk__platform">
							<div>
								<h3 class="bbb-mk__platformTitle"><?php echo esc_html($platform['title']); ?></h3>
								<p class="bbb-mk__handle"><?php echo esc_html($platform['handle']); ?></p>
							</div>
							<p class="bbb-mk__platformText"><?php echo esc_html($platform['text']); ?></p>
							<?php if ('' !== trim($platform['badge'])) : ?>
								<span class="bbb-mk__badge"><?php echo esc_html($platform['badge']); ?></span>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="bbb-mk__band bbb-mk__band--alt">
		<div class="bbb-mk__wrap">
			<div class="bbb-mk__section">
				<div class="bbb-mk__sectionHead">
					<p class="bbb-mk__kicker">content</p>
					<h2 class="bbb-mk__sectionTitle">what i create</h2>
				</div>
				<div class="bbb-mk__cards">
					<?php foreach ($content_cards as $card) : ?>
						<article class="bbb-mk__card">
							<span class="bbb-mk__icon"><?php echo esc_html($card['icon']); ?></span>
							<h3 class="bbb-mk__cardTitle"><?php echo esc_html($card['title']); ?></h3>
							<p class="bbb-mk__smallText"><?php echo esc_html($card['text']); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="bbb-mk__band">
		<div class="bbb-mk__wrap">
			<div class="bbb-mk__section">
				<div class="bbb-mk__sectionHead">
					<p class="bbb-mk__kicker">collaboration</p>
					<h2 class="bbb-mk__sectionTitle">ways to work together</h2>
				</div>
				<div class="bbb-mk__packages">
					<?php foreach ($packages as $package) : ?>
						<article class="bbb-mk__package<?php echo !empty($package['featured']) ? ' bbb-mk__package--featured' : ''; ?>">
							<?php if (!empty($package['featured'])) : ?><span class="bbb-mk__ribbon">most flexible</span><?php endif; ?>
							<p class="bbb-mk__packageNum"><?php echo esc_html($package['number']); ?></p>
							<p class="bbb-mk__packageType"><?php echo esc_html($package['type']); ?></p>
							<h3 class="bbb-mk__packageTitle"><?php echo esc_html($package['title']); ?></h3>
							<p class="bbb-mk__packageText"><?php echo esc_html($package['text']); ?></p>
							<div class="bbb-mk__includes">
								<?php foreach ($package['includes'] as $item) : ?>
									<span><?php echo esc_html($item); ?></span>
								<?php endforeach; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="bbb-mk__band bbb-mk__band--alt">
		<div class="bbb-mk__wrap">
			<div class="bbb-mk__section">
				<div class="bbb-mk__sectionHead">
					<p class="bbb-mk__kicker">who is reading</p>
					<h2 class="bbb-mk__sectionTitle">the audience</h2>
				</div>
				<div class="bbb-mk__audience">
					<?php foreach ($audiences as $audience) : ?>
						<article class="bbb-mk__audienceCard">
							<p class="bbb-mk__cardLabel"><?php echo esc_html($audience['label']); ?></p>
							<h3 class="bbb-mk__audienceTitle"><?php echo esc_html($audience['title']); ?></h3>
							<p class="bbb-mk__smallText"><?php echo esc_html($audience['text']); ?></p>
							<div class="bbb-mk__tags">
								<?php foreach ($audience['tags'] as $tag) : ?>
									<span class="bbb-mk__tag"><?php echo esc_html($tag); ?></span>
								<?php endforeach; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="bbb-mk__band">
		<div class="bbb-mk__wrap">
			<div class="bbb-mk__section">
				<div class="bbb-mk__sectionHead">
					<p class="bbb-mk__kicker">ideal brands</p>
					<h2 class="bbb-mk__sectionTitle">who i work with</h2>
				</div>
				<div class="bbb-mk__partners">
					<?php foreach ($partners as $partner) : ?>
						<article class="bbb-mk__partner">
							<span class="bbb-mk__dot" aria-hidden="true"></span>
							<p><strong><?php echo esc_html($partner['title']); ?></strong><?php echo esc_html($partner['text']); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="bbb-mk__closing">
		<div class="bbb-mk__wrap bbb-mk__closingInner">
			<h2 class="bbb-mk__closingTitle">a small, obsessed audience outperforms a large, distracted one.</h2>
			<p class="bbb-mk__closingText">All collaborations are paid. I bring editorial care, intentionality, and a community that reads every word.</p>
			<a class="bbb-mk__cta" href="mailto:bybookishbabe@gmail.com">bybookishbabe@gmail.com</a>
		</div>
	</section>
</main>

<?php
get_footer();
