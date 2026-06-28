<?php
/**
 * Template Name: Trust Policy
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

require_once get_theme_file_path('inc/policy-meta.php');

$trust_title              = 'the trust policy | bybookishbabe';
$trust_description        = 'how bybookishbabe protects editorial independence: sponsors can buy attention, never reviews, ratings, rankings, or verdicts.';
$trust_social_title       = 'the trust policy';
$trust_social_description = 'why no one can buy their way into the bybookishbabe library.';
$trust_canonical          = 'https://bybookishbabe.com/trust/';
$trust_image              = function_exists('bbb_social_share_card_url') ? bbb_social_share_card_url() : 'https://bybookishbabe.com/wp-content/uploads/2026/05/bybookishbabe.png';

add_filter('pre_get_document_title', static fn(string $title): string => $trust_title, 100);
add_filter('rank_math/frontend/title', static fn(string $title): string => $trust_title, 100);
add_filter('rank_math/frontend/description', static fn(string $description): string => $trust_description, 100);
add_filter('rank_math/opengraph/facebook/title', static fn(string $title): string => $trust_social_title, 100);
add_filter('rank_math/opengraph/facebook/description', static fn(string $description): string => $trust_social_description, 100);
add_filter('rank_math/opengraph/twitter/title', static fn(string $title): string => $trust_social_title, 100);
add_filter('rank_math/opengraph/twitter/description', static fn(string $description): string => $trust_social_description, 100);
add_filter('rank_math/frontend/canonical', static fn(string $canonical): string => $trust_canonical, 100);
add_filter('rank_math/opengraph/facebook/url', static fn(string $url): string => $trust_canonical, 100);
add_filter('rank_math/opengraph/twitter/url', static fn(string $url): string => $trust_canonical, 100);
add_filter('rank_math/opengraph/type', static fn(string $type): string => 'website', 100);
add_filter('rank_math/opengraph/facebook/image', static fn(string $image): string => $trust_image, 100);
add_filter('rank_math/opengraph/twitter/image', static fn(string $image): string => $trust_image, 100);
add_filter(
	'rank_math/frontend/robots',
	static function (array $robots): array {
		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = 'index';
		$robots['follow'] = 'follow';

		return $robots;
	},
	100
);
add_filter(
	'wp_robots',
	static function (array $robots): array {
		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = true;
		$robots['follow'] = true;

		return $robots;
	},
	100
);
add_action(
	'wp_head',
	static function () use ($trust_canonical): void {
		printf('<link rel="canonical" href="%s">%s', esc_url($trust_canonical), "\n");

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'WebPage',
			'name'        => 'The Trust Policy',
			'url'         => $trust_canonical,
			'description' => 'ByBookishBabe editorial independence policy for reviews, ratings, rankings, sponsorships, affiliate links, and free books.',
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

get_header();
?>

<main class="bbb-trust-policy">
	<section class="bbb-trust-policy__hero page-width page-width--narrow section-main-padding">
		<h1 class="main-page-title page-title h0">the trust policy</h1>
		<?php bbb_render_policy_meta('bbb-trust-policy__meta'); ?>
		<p class="bbb-trust-policy__dek">or: why no one can buy their way into my library</p>
		<p>every book on this site is here for exactly one reason: i read it, and it earned its place. that's the whole model. so before any brand deals, affiliate links, or sponsor spots enter the picture, here's the wall - in writing - between money and my verdicts.</p>
	</section>

	<section class="bbb-trust-policy__body page-width page-width--narrow rte" aria-label="trust policy">
		<h2>what money can never buy here</h2>
		<ul>
			<li>a spot in the library</li>
			<li>a spice rating (the chilis are earned, not sold)</li>
			<li>a review score or a kinder verdict</li>
			<li>a ranking on any "best of" or guide page</li>
			<li>a mention in the weekly obsession</li>
			<li>a place in any future awards or year-end lists</li>
		</ul>
		<p>no exceptions. not for publishers, not for authors, not for brands, not for friends. if a verdict can be bought, it's worthless - and then this whole site is worthless. so it can't.</p>

		<h2>what sponsors can buy</h2>
		<ul>
			<li>the clearly-labeled sponsor spot on the homepage</li>
			<li>clearly-labeled sponsorship slots in the newsletter</li>
			<li>clearly-marked sponsored posts (rare, picky, always disclosed)</li>
		</ul>
		<p>sponsors get my audience's attention, never my opinion. you'll always know when something is sponsored, because it will say so - plainly, not in tiny gray font at the bottom.</p>

		<h2>about the affiliate links</h2>
		<p>some links on this site are affiliate links, which means i may earn a small commission if you buy through them (at no extra cost to you). here's what that does and doesn't mean:</p>
		<p><strong>it means:</strong> you're helping keep this site running, and i adore you for it.</p>
		<p><strong>it doesn't mean:</strong> a book got recommended because of the link. i'd recommend the exact same books if every link paid me nothing. commission has never moved a chili and never will.</p>
		<p>full details live in the <a href="<?php echo esc_url(home_url('/affiliate-disclosure/')); ?>">affiliate disclosure</a>.</p>

		<h2>about free books &amp; ARCs</h2>
		<p>sometimes publishers or authors send me advance or free copies. when that happens:</p>
		<ul>
			<li>i'll always tell you</li>
			<li>a free copy never guarantees a review</li>
			<li>a free copy definitely never guarantees a good review</li>
		</ul>
		<p>i've DNF'd books people sent me for free. i'll do it again.</p>

		<h2>about authors</h2>
		<p>i love this community and i'm friendly with authors. friendly does not mean placement. if a book by someone i know lands in the library, it's because the book earned it - and if i can't be impartial about something, i'll either disclose it or sit it out.</p>

		<h2>if i change my mind</h2>
		<p>taste is a living thing. if a re-read changes my verdict, i'll update the review and say so. you'll never catch a quietly edited rating here - changes get acknowledged.</p>

		<h2>the short version</h2>
		<p><strong>sponsor the space. never the verdict.</strong></p>
		<p>questions about any of this? <a href="mailto:bybookishbabe@gmail.com">send me a note</a> - i reply within 1-2 business days, usually mid-chapter.</p>
	</section>
</main>

<style>
	.bbb-trust-policy {
		--trust-bg: #080808;
		--trust-panel: #101010;
		--trust-ink: #f7f3ee;
		--trust-muted: #b8adae;
		--trust-accent: #ff8ac7;
		--trust-accent-deep: #2b111f;
		--trust-border: rgba(255, 255, 255, 0.12);
		background:
			linear-gradient(180deg, rgba(255, 138, 199, 0.1), transparent 28rem),
			var(--trust-bg);
		color: var(--trust-ink);
		text-transform: lowercase;
	}

	.bbb-trust-policy__hero {
		position: relative;
		border-bottom: 1px solid var(--trust-border);
	}

	.bbb-trust-policy .main-page-title {
		color: var(--trust-ink);
	}

	.bbb-trust-policy__dek {
		margin: 1.6rem 0 2rem;
		font-style: italic;
		color: var(--trust-accent);
	}

	.bbb-trust-policy__hero > p:last-child,
	.bbb-trust-policy__body {
		color: var(--trust-muted);
	}

	.bbb-trust-policy__hero > p:last-child {
		max-width: 74rem;
		margin-bottom: 0;
	}

	.bbb-trust-policy__body {
		padding-top: clamp(3.2rem, 5vw, 5.6rem);
		padding-bottom: clamp(6.4rem, 9vw, 10.4rem);
	}

	.bbb-trust-policy__body h2 {
		margin-top: 4rem;
		margin-bottom: 1rem;
		color: var(--trust-ink);
	}

	.bbb-trust-policy__body h2:first-child {
		margin-top: 0;
	}

	.bbb-trust-policy__body p,
	.bbb-trust-policy__body ul {
		max-width: 76rem;
	}

	.bbb-trust-policy__body ul {
		margin: 1.4rem 0 2rem;
		padding: 1.8rem 2rem 1.8rem 4rem;
		border: 1px solid var(--trust-border);
		border-left: .4rem solid var(--trust-accent);
		background: linear-gradient(135deg, var(--trust-accent-deep), var(--trust-panel));
	}

	.bbb-trust-policy__body li + li {
		margin-top: .45rem;
	}

	.bbb-trust-policy__body a {
		color: var(--trust-accent);
		text-decoration-thickness: .12em;
		text-underline-offset: .18em;
	}

	.bbb-trust-policy__body strong {
		color: var(--trust-ink);
	}

	.bbb-trust-policy__body em {
		color: var(--trust-muted);
	}

	.bbb-trust-policy__meta {
		max-width: 76rem;
		margin: 1.6rem 0 0;
		padding: 1.4rem 0 0;
		border-top: 1px solid var(--trust-border);
		color: var(--trust-muted);
	}

	.bbb-trust-policy__meta p {
		margin: 0;
	}

	.bbb-trust-policy__meta p + p {
		margin-top: .35rem;
	}

	.bbb-trust-policy__meta span {
		color: var(--trust-ink);
		font-weight: 700;
	}

	@media screen and (max-width: 749px) {
		.bbb-trust-policy__hero,
		.bbb-trust-policy__body {
			width: calc(100% - 3.2rem);
		}

		.bbb-trust-policy__body ul {
			padding-left: 3.2rem;
		}
	}
</style>

<?php
get_footer();
