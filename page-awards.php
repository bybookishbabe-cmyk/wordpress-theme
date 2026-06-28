<?php
/**
 * Template Name: Romance Awards
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (function_exists('bbb_enqueue_css')) {
	bbb_enqueue_css('bbb-romance-awards', 'assets/css/romance-awards.css', array('bbb-bookshelf-signup'));
}

$awards_title              = 'the bybookishbabe romance awards | bybookishbabe';
$awards_description        = 'annual awards for the year\'s best romance books, chosen with reader votes and bybookishbabe editorial picks.';
$awards_canonical          = 'https://bybookishbabe.com/awards/';
$awards_social_title       = 'the bybookishbabe romance awards';
$awards_social_description = 'the bybookishbabe romance awards: reader votes, editorial picks, and dramatic category reveals.';
$awards_image              = function_exists('bbb_social_share_card_url') ? bbb_social_share_card_url() : 'https://bybookishbabe.com/wp-content/uploads/2026/05/bybookishbabe.png';

add_filter('pre_get_document_title', static fn(string $title): string => $awards_title, 100);
add_filter('rank_math/frontend/title', static fn(string $title): string => $awards_title, 100);
add_filter('rank_math/frontend/description', static fn(string $description): string => $awards_description, 100);
add_filter('rank_math/frontend/canonical', static fn(string $canonical): string => $awards_canonical, 100);
add_filter('rank_math/opengraph/facebook/title', static fn(string $title): string => $awards_social_title, 100);
add_filter('rank_math/opengraph/facebook/description', static fn(string $description): string => $awards_social_description, 100);
add_filter('rank_math/opengraph/facebook/url', static fn(string $url): string => $awards_canonical, 100);
add_filter('rank_math/opengraph/facebook/image', static fn(string $image): string => $awards_image, 100);
add_filter('rank_math/opengraph/twitter/title', static fn(string $title): string => $awards_social_title, 100);
add_filter('rank_math/opengraph/twitter/description', static fn(string $description): string => $awards_social_description, 100);
add_filter('rank_math/opengraph/twitter/image', static fn(string $image): string => $awards_image, 100);
add_filter('rank_math/opengraph/type', static fn(string $type): string => 'website', 100);
add_action(
	'wp_head',
	static function () use ($awards_canonical): void {
		printf('<link rel="canonical" href="%s">%s', esc_url($awards_canonical), "\n");

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'WebPage',
			'name'        => 'The ByBookishBabe Romance Awards',
			'url'         => $awards_canonical,
			'description' => 'Annual romance book awards run by ByBookishBabe, with reader voting and editorial verdicts.',
			'publisher'   => array(
				'@type' => 'Person',
				'name'  => 'ByBookishBabe',
				'url'   => 'https://bybookishbabe.com',
			),
		);

		echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
	},
	30
);

$awards_categories = array(
	array('tag' => 'the finale', 'name' => 'book boyfriend of the year', 'copy' => 'the fictional man of the year. the one who made everyone raise their standards. revealed last, because obviously.', 'date' => 'nominees unlock nov 15', 'flag' => true),
	array('tag' => 'the headline', 'name' => 'romance of the year', 'copy' => 'the best book in the library, full stop.', 'date' => 'nominees unlock nov 15'),
	array('name' => 'best debut', 'copy' => 'the strongest first book by a new author - the name you will be smug about knowing early.', 'date' => 'nominees unlock nov 15'),
	array('name' => 'spiciest read of the year', 'copy' => 'the highest heat that actually earned its chilis. rated on the official scale, obviously.', 'date' => 'nominees unlock nov 15'),
	array('name' => 'best slow burn', 'copy' => 'the longest, most agonizing, most worth-it wait of the year.', 'date' => 'nominees unlock nov 15'),
	array('name' => 'morally gray of the year', 'copy' => 'the villain-coded love interest who made everyone question their judgment. and keep reading.', 'date' => 'nominees unlock nov 15'),
	array('name' => 'the ruined-my-sleep award', 'copy' => 'the book most impossible to put down. "one more chapter" became sunrise.', 'date' => 'nominees unlock nov 15'),
	array('name' => 'series of the year', 'copy' => 'the series - ongoing or complete - that owned the year, binge after binge.', 'date' => 'nominees unlock nov 15'),
	array('name' => 'indie gem of the year', 'copy' => 'the best indie or self-published book in the library. the genre runs on these authors - they get their own crown.', 'date' => 'nominees unlock nov 15'),
	array('name' => 'biggest letdown of the year', 'copy' => 'the hyped book that did not deliver. verdict only - no vote, no pile-on, just one honest reader taking the heat.', 'date' => 'verdict unlocks dec 14 · no public vote', 'verdict_only' => true),
);

get_header();
?>

<main class="bbb-awards">
	<div class="bbb-awards__ticker" aria-hidden="true">
		<span>the 2026 romance awards · the first vote opens november 1 · final vote closes december 4 · reveals december 7-16 · every library book is in the running ·</span>
	</div>

	<section class="bbb-awards__hero">
		<div class="bbb-awards__wrap">
			<div class="bbb-awards__seal" role="img" aria-label="2026 romance awards seal">
				<span class="bbb-awards__seal-year">'26</span>
				<span class="bbb-awards__seal-label">romance awards</span>
			</div>
			<h1><span class="bbb-awards__title-line">the bybookishbabe</span> <em>romance awards</em></h1>
			<div class="bbb-awards__actions">
				<a class="bbb-awards__btn bbb-awards__btn--ghost" href="#categories">see the categories</a>
				<a class="bbb-awards__btn" href="https://thesmutandsentimentsociety.substack.com/subscribe">join the society free</a>
			</div>
		</div>
	</section>

	<section class="bbb-awards__section" id="how">
		<div class="bbb-awards__wrap">
			<p class="bbb-awards__eyebrow">how it works</p>
			<h2>three steps. zero vote-piles.</h2>
			<p class="bbb-awards__lede">no endless lists, no paid hype, no pretending the genre begins and ends with whatever got loudest this month. the model is simple:</p>
			<div class="bbb-awards__steps">
				<article class="bbb-awards__step">
					<span>i.</span>
					<h3>every library book is in the running</h3>
					<p>only books read, rated, and added to the bybookishbabe library this year can compete. every contender was actually vetted.</p>
				</article>
				<article class="bbb-awards__step">
					<span>ii.</span>
					<h3>you vote twice</h3>
					<p>the first vote picks the nominees from the running. the final vote crowns society's choice. paid society members get the snub hearing between rounds.</p>
				</article>
				<article class="bbb-awards__step">
					<span>iii.</span>
					<h3>the babe delivers verdicts</h3>
					<p>each category also gets an editorial pick from bybookishbabe. when the vote and verdict disagree, the reveal gets interesting.</p>
				</article>
			</div>
		</div>
	</section>

	<section class="bbb-awards__section bbb-awards__tracks">
		<div class="bbb-awards__wrap">
			<p class="bbb-awards__eyebrow">two winners per category</p>
			<h2>the people's pick. the babe's verdict.</h2>
			<div class="bbb-awards__track-grid">
				<article class="bbb-awards__track">
					<div class="bbb-awards__stamp" aria-hidden="true">♡</div>
					<h3>society's choice</h3>
					<p>decided by reader vote. the community favorite, fair and square - campaigning allowed, ballot-stuffing not.</p>
				</article>
				<article class="bbb-awards__track bbb-awards__track--verdict">
					<div class="bbb-awards__stamp" aria-hidden="true">♥</div>
					<h3>the babe's verdict</h3>
					<p>decided by one reader who read everything on the list. final, not negotiated, and absolutely not for sale.</p>
				</article>
			</div>
			<p class="bbb-awards__double">when both tracks crown the same book, it earns the rare <strong>double crown</strong>.</p>
		</div>
	</section>

	<section class="bbb-awards__section bbb-awards__categories" id="categories">
		<div class="bbb-awards__wrap">
			<p class="bbb-awards__eyebrow">the 2026 slate</p>
			<h2>ten categories. every one earned.</h2>
			<p class="bbb-awards__lede">right now the whole <a href="<?php echo esc_url(home_url('/library/')); ?>">library</a> is in the running. on nov 15 each voting category gets five nominees: the top four from the first vote, plus the babe's wildcard.</p>
			<div class="bbb-awards__cat-grid">
				<?php foreach ($awards_categories as $category) : ?>
					<article class="bbb-awards__cat<?php echo !empty($category['flag']) ? ' bbb-awards__cat--flag' : ''; ?>">
						<?php if (!empty($category['tag'])) : ?>
							<span class="bbb-awards__cat-tag"><?php echo esc_html($category['tag']); ?></span>
						<?php endif; ?>
						<h3><?php echo esc_html($category['name']); ?></h3>
						<p><?php echo esc_html($category['copy']); ?></p>
						<p class="bbb-awards__reveal"><?php echo esc_html($category['date']); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="bbb-awards__section">
		<div class="bbb-awards__wrap">
			<p class="bbb-awards__eyebrow">the 2026 calendar</p>
			<h2>how the season unfolds</h2>
			<ol class="bbb-awards__timeline">
				<li class="is-now"><span>now - oct 31</span><h3>the running grows every sunday</h3><p>every book added to the library between january 1 and october 31 is eligible. the library locks halloween night.</p></li>
				<li><span>nov 1 - nov 14</span><h3>the first vote</h3><p>everyone with a free account picks up to three favorites per category from the library only.</p></li>
				<li><span>nov 15 - dec 4</span><h3>the nominees + the final vote</h3><p>nominees go live with wildcard picks, then the final vote opens to crown society's choice.</p></li>
				<li><span>dec 7 - dec 16</span><h3>reveal week</h3><p>one category per day, both winners revealed together, building to romance of the year and book boyfriend of the year.</p></li>
				<li><span>dec 16 onward</span><h3>the archive</h3><p>full results live forever, winners get their badges, and nominated books keep their seals on-site.</p></li>
			</ol>
		</div>
	</section>

	<section class="bbb-awards__section bbb-awards__rules">
		<div class="bbb-awards__wrap">
			<p class="bbb-awards__eyebrow">the rules</p>
			<h2>read this part even if you skim everything else</h2>
			<div class="bbb-awards__rule-card">
				<ol>
					<li>only books from the <a href="<?php echo esc_url(home_url('/library/')); ?>">bybookishbabe library</a> are eligible. no exceptions.</li>
					<li>votes, wildcards, and verdicts cannot be purchased. not by publishers, authors, brands, or friends.</li>
					<li>fan campaigning is allowed and encouraged. duplicate accounts and bots get ballots removed.</li>
					<li>sponsors may present the event. they may never touch a verdict. full policy: <a href="<?php echo esc_url(home_url('/trust/')); ?>">the trust policy</a>.</li>
					<li>the babe's verdict and the wildcard are one reader's honest opinion, final and not for sale.</li>
				</ol>
				<p>sponsor the space. never the verdict.</p>
			</div>
		</div>
	</section>

	<section class="bbb-awards__join">
		<div class="bbb-awards__wrap">
			<p class="bbb-awards__eyebrow">your ballot is waiting</p>
			<h2>voting is a society thing</h2>
			<p>the society is the only door: join free on substack, get your login for the site, and cast one ballot per round - same account that runs your bookshelf. one curated rec every sunday comes with it, because of course it does.</p>
			<p class="bbb-awards__prize">every completed final-vote ballot is entered to win <strong>the winner's TBR</strong> - your choice of a new kindle + a year of kindle unlimited, or a gift card of equal value. free membership is all it takes; no purchase necessary. drawn after voting closes, announced at the finale.</p>
			<a class="bbb-awards__btn" href="https://thesmutandsentimentsociety.substack.com/subscribe">join the society free</a>
		</div>
	</section>

	<section class="bbb-awards__section bbb-awards__faq">
		<div class="bbb-awards__wrap">
			<p class="bbb-awards__eyebrow">questions</p>
			<h2>the fine print, but make it readable</h2>
			<details>
				<summary>do books have to be published in 2026 to be eligible?</summary>
				<p>no. these are awards for the year's best reads, not just new releases. if it entered the library this year, it is eligible.</p>
			</details>
			<details>
				<summary>can authors or publishers submit books?</summary>
				<p>no. the library is built by one reader's taste. ARCs are welcome, but never guarantee a read, rating, nomination, or win.</p>
			</details>
			<details>
				<summary>how do i vote?</summary>
				<p>voting will live at <a href="<?php echo esc_url(home_url('/awards/vote/')); ?>">/awards/vote/</a>. log in with your society account, vote in the live round, and edit until that round closes.</p>
			</details>
			<details>
				<summary>why is there a biggest letdown category?</summary>
				<p>because awards that only say nice things start to feel like advertising. this one is a verdict-only category, never a public pile-on.</p>
			</details>
		</div>
	</section>
</main>

<?php
get_footer();
