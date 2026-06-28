<?php
/**
 * Template Name: media kit
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$numbers = array(
	array('value' => '2,100', 'label' => 'opted-in newsletter subscribers'),
	array('value' => '40%', 'label' => 'average open rate'),
	array('value' => '29', 'label' => 'issues published'),
	array('value' => '523', 'label' => 'google clicks in 28 days'),
);

$packages = array(
	array('type' => 'site', 'title' => 'site placement', 'price' => 'contact for pricing', 'text' => 'a paid mention inside a relevant guide, library path, or reader tool where search-intent readers are already looking.', 'includes' => array('contextual site mention', 'tracked link', 'reader-fit positioning'), 'featured' => false),
	array('type' => 'newsletter', 'title' => 'newsletter placement', 'price' => '$150-$200 / placement', 'text' => 'one dedicated feature inside the weekly sunday rec, written in my voice.', 'includes' => array('editorial placement', 'tracked link', '2,100+ readers', '40% avg open rate'), 'featured' => true),
	array('type' => 'bundle', 'title' => 'site + newsletter', 'price' => 'contact for pricing', 'text' => 'your offer gets both an evergreen site home and a focused weekly newsletter push.', 'includes' => array('newsletter feature', 'site placement', 'tracked link', 'performance recap'), 'featured' => false),
);

$brand_tags = array('newsletter + website', 'romance readers', 'weekly sunday recs', 'paid collabs only', '523 google clicks in 28 days');
$audience_tags = array('romance readers with intent', 'trope-driven', 'kindle devotees', 'booktok-adjacent', 'email-first', 'active buyers');
$partners = array(
	'subscription boxes',
	'bookish product brands',
	'reading accessories',
	'digital products',
	'reader-adjacent services',
	'aligned small businesses',
);

get_header();
?>

<main id="primary" class="site-main bbb-mk" aria-labelledby="bbb-media-kit-title">
	<section class="bbb-mk__hero bbb-mk__reveal" data-mk-reveal="hero">
		<div class="bbb-mk__halo" aria-hidden="true"></div>
		<div class="bbb-mk__wrap bbb-mk__heroInner">
			<h1 class="bbb-mk__title" id="bbb-media-kit-title">bybookishbabe</h1>
			<p class="bbb-mk__subtitle">smut meets sentiment for soft hearts with sinful taste.</p>
			<p class="bbb-mk__ornament">currently booking - spots available now</p>
		</div>
	</section>

	<section class="bbb-mk__band bbb-mk__reveal" data-mk-reveal="slide-left">
		<div class="bbb-mk__wrap">
			<div class="bbb-mk__section">
				<div class="bbb-mk__sectionHead">
					<p class="bbb-mk__kicker">bybookishbabe</p>
					<h2 class="bbb-mk__sectionTitle">the brand</h2>
				</div>
				<div class="bbb-mk__copy bbb-mk__copy--wide">
					<p>bybookishbabe is a romance book recommendation platform built for readers who take their tbr seriously and enjoy it sinfully. it is home to the smut and sentiment society - a weekly newsletter delivering one curated romance rec every sunday, written like a text from a friend who reads too much and has excellent taste.</p>
					<p>no lazy roundups. no algorithmic filler. just one reader to another, with intent.</p>
					<blockquote class="bbb-mk__pullQuote">"the ones who hate you the loudest are always the most interesting." that's the energy here.</blockquote>
					<div class="bbb-mk__tags">
						<?php foreach ($brand_tags as $tag) : ?>
							<span class="bbb-mk__tag"><?php echo esc_html($tag); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="bbb-mk__band bbb-mk__band--alt bbb-mk__reveal" data-mk-reveal="rise">
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
						</article>
					<?php endforeach; ?>
				</div>
				<p class="bbb-mk__note">a 40% open rate is roughly 2x the industry average for newsletters, and 523 google clicks in 28 days shows real search-driven discovery. these readers show up. they read every word. they click.</p>
			</div>
		</div>
	</section>

	<section class="bbb-mk__band bbb-mk__reveal" data-mk-reveal="slide-right">
		<div class="bbb-mk__wrap">
			<div class="bbb-mk__section">
				<div class="bbb-mk__sectionHead">
					<p class="bbb-mk__kicker">the audience</p>
					<h2 class="bbb-mk__sectionTitle">romance readers with intent</h2>
				</div>
				<div class="bbb-mk__tags">
					<?php foreach ($audience_tags as $tag) : ?>
						<span class="bbb-mk__tag"><?php echo esc_html($tag); ?></span>
					<?php endforeach; ?>
				</div>
				<p class="bbb-mk__note">these are not passive scrollers. they search by trope, filter by spice level, save books to a personal shelf, and return weekly for the rec. they buy. they subscribe. they tell their group chats.</p>
			</div>
		</div>
	</section>

	<section class="bbb-mk__band bbb-mk__band--alt bbb-mk__reveal" data-mk-reveal="cards">
		<div class="bbb-mk__wrap">
			<div class="bbb-mk__section">
				<div class="bbb-mk__sectionHead">
					<p class="bbb-mk__kicker">what a placement looks like</p>
					<h2 class="bbb-mk__sectionTitle">editorial, specific, rec-style</h2>
				</div>
				<div class="bbb-mk__sample">
					<p class="bbb-mk__sampleLabel">sample newsletter voice</p>
					<p class="bbb-mk__sampleQuote">"don't say i didn't warn you. smut rating: 7. sentiment rating: 3. this one doesn't hold your hand. it holds your throat."</p>
					<p class="bbb-mk__sampleSource">from the smut and sentiment society, may 24 - "you were warned."</p>
				</div>
				<p class="bbb-mk__note">a sponsored feature is written in this same voice - editorial, specific, and genuinely rec-style. not a banner. not a copy-paste ad. your brand, placed like a recommendation from someone readers trust.</p>
			</div>
		</div>
	</section>

	<section class="bbb-mk__band bbb-mk__reveal" data-mk-reveal="scale">
		<div class="bbb-mk__wrap">
			<div class="bbb-mk__section">
				<div class="bbb-mk__sectionHead">
					<p class="bbb-mk__kicker">collaboration</p>
					<h2 class="bbb-mk__sectionTitle">ways to work together</h2>
				</div>
				<div class="bbb-mk__packages">
					<?php foreach ($packages as $package) : ?>
						<article class="bbb-mk__package<?php echo !empty($package['featured']) ? ' bbb-mk__package--featured' : ''; ?>">
							<?php if (!empty($package['featured'])) : ?><span class="bbb-mk__ribbon">most popular</span><?php endif; ?>
							<p class="bbb-mk__packageType"><?php echo esc_html($package['type']); ?></p>
							<h3 class="bbb-mk__packageTitle"><?php echo esc_html($package['title']); ?></h3>
							<p class="bbb-mk__packagePrice"><?php echo esc_html($package['price']); ?></p>
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

	<section class="bbb-mk__band bbb-mk__reveal" data-mk-reveal="rise">
		<div class="bbb-mk__wrap">
			<div class="bbb-mk__section">
				<div class="bbb-mk__sectionHead">
					<p class="bbb-mk__kicker">who i work with</p>
					<h2 class="bbb-mk__sectionTitle">who i work with</h2>
				</div>
				<div class="bbb-mk__partnerLine">
					<?php foreach ($partners as $partner) : ?>
						<span><?php echo esc_html($partner); ?></span>
					<?php endforeach; ?>
				</div>
				<p class="bbb-mk__note">all collaborations are paid. i bring editorial care, a highly specific audience, and a community that reads every word.</p>
			</div>
		</div>
	</section>

	<section class="bbb-mk__closing bbb-mk__reveal" data-mk-reveal="hero">
		<div class="bbb-mk__wrap bbb-mk__closingInner">
			<h2 class="bbb-mk__closingTitle">a small, obsessed audience outperforms a large, distracted one.</h2>
			<p class="bbb-mk__closingText">spots are limited. currently booking now.</p>
			<a class="bbb-mk__cta" href="mailto:bybookishbabe@gmail.com">let's get started</a>
		</div>
	</section>
</main>

<script>
	(function () {
		var sections = document.querySelectorAll('.bbb-mk__reveal');
		if (!sections.length) {
			return;
		}

		if (!('IntersectionObserver' in window)) {
			sections.forEach(function (section) {
				section.classList.add('is-visible');
			});
			return;
		}

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) {
					return;
				}

				entry.target.classList.add('is-visible');
				observer.unobserve(entry.target);
			});
		}, { rootMargin: '0px 0px -12% 0px', threshold: 0.18 });

		sections.forEach(function (section) {
			observer.observe(section);
		});
	})();
</script>

<?php
get_footer();
