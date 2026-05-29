<?php
/**
 * Template Name: Romance Trope Dictionary
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$dictionary_css_path = get_theme_file_path('assets/css/romance-trope-dictionary.css');
wp_enqueue_style(
	'bbb-romance-trope-dictionary',
	get_theme_file_uri('assets/css/romance-trope-dictionary.css'),
	array('bbb-bookshelf-signup'),
	file_exists($dictionary_css_path) ? (string) filemtime($dictionary_css_path) : wp_get_theme()->get('Version')
);

$dictionary_title              = 'romance tropes list — every trope defined & explained';
$dictionary_description        = 'a complete romance tropes list — every trope defined with links to curated book lists. from enemies to lovers to dark romance, all in one place.';
$dictionary_social_description = 'every romance trope defined in one place — enemies to lovers, slow burn, dark romance and 40+ more. with links to curated book lists for each.';
$dictionary_canonical          = home_url('/romance-trope-dictionary/');
$dictionary_image              = get_theme_file_uri('assets/images/romance-tropes-list-og.png');
$dictionary_image_alt          = 'romance tropes list — every romance trope defined by bybookishbabe';

add_filter('pre_get_document_title', static fn(): string => $dictionary_title, 99);
add_filter('rank_math/frontend/title', static fn(): string => $dictionary_title, 99);
add_filter('rank_math/frontend/description', static fn(): string => $dictionary_description, 99);
add_filter('rank_math/frontend/canonical', static fn(): string => $dictionary_canonical, 99);
add_filter('rank_math/opengraph/facebook/title', static fn(): string => $dictionary_title, 99);
add_filter('rank_math/opengraph/facebook/description', static fn(): string => $dictionary_social_description, 99);
add_filter('rank_math/opengraph/facebook/url', static fn(): string => $dictionary_canonical, 99);
add_filter('rank_math/opengraph/twitter/title', static fn(): string => $dictionary_title, 99);
add_filter('rank_math/opengraph/twitter/description', static fn(): string => $dictionary_social_description, 99);
add_filter('rank_math/opengraph/twitter/url', static fn(): string => $dictionary_canonical, 99);
add_filter('rank_math/opengraph/type', static fn(): string => 'website', 99);
add_filter('rank_math/opengraph/facebook/image', static fn(): string => $dictionary_image, 99);
add_filter('rank_math/opengraph/twitter/image', static fn(): string => $dictionary_image, 99);
add_action(
	'rank_math/opengraph/facebook',
	static function () use ($dictionary_title, $dictionary_social_description, $dictionary_image, $dictionary_image_alt, $dictionary_canonical): void {
		remove_all_actions('rank_math/opengraph/facebook', 5);
		remove_all_actions('rank_math/opengraph/facebook', 10);
		remove_all_actions('rank_math/opengraph/facebook', 11);
		remove_all_actions('rank_math/opengraph/facebook', 12);
		remove_all_actions('rank_math/opengraph/facebook', 30);

		printf('<meta property="og:type" content="website">%s', "\n");
		printf('<meta property="og:title" content="%s">%s', esc_attr($dictionary_title), "\n");
		printf('<meta property="og:description" content="%s">%s', esc_attr($dictionary_social_description), "\n");
		printf('<meta property="og:image" content="%s">%s', esc_url($dictionary_image), "\n");
		printf('<meta property="og:image:alt" content="%s">%s', esc_attr($dictionary_image_alt), "\n");
		printf('<meta property="og:url" content="%s">%s', esc_url($dictionary_canonical), "\n");
	},
	4
);
add_action(
	'rank_math/opengraph/twitter',
	static function () use ($dictionary_title, $dictionary_social_description, $dictionary_image): void {
		remove_all_actions('rank_math/opengraph/twitter', 5);
		remove_all_actions('rank_math/opengraph/twitter', 10);
		remove_all_actions('rank_math/opengraph/twitter', 11);
		remove_all_actions('rank_math/opengraph/twitter', 30);

		printf('<meta name="twitter:title" content="%s">%s', esc_attr($dictionary_title), "\n");
		printf('<meta name="twitter:description" content="%s">%s', esc_attr($dictionary_social_description), "\n");
		printf('<meta name="twitter:image" content="%s">%s', esc_url($dictionary_image), "\n");
	},
	4
);
add_action(
	'wp_head',
	static function () use ($dictionary_canonical): void {
		printf('<link rel="canonical" href="%s">%s', esc_url($dictionary_canonical), "\n");
	},
	2
);
add_filter(
	'rank_math/frontend/robots',
	static function (array $robots): array {
		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = 'index';
		$robots['follow'] = 'follow';

		return $robots;
	},
	99
);
add_filter(
	'wp_robots',
	static function (array $robots): array {
		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = true;
		$robots['follow'] = true;

		return $robots;
	},
	99
);
add_filter(
	'rank_math/json_ld',
	static function (array $data) use ($dictionary_title, $dictionary_description, $dictionary_canonical, $dictionary_image): array {
		foreach ($data as $key => $entity) {
			if (!is_array($entity)) {
				continue;
			}

			if (($entity['@type'] ?? '') === 'ImageObject') {
				$data[$key]['@id']    = $dictionary_image;
				$data[$key]['url']    = $dictionary_image;
				$data[$key]['width']  = '1200';
				$data[$key]['height'] = '630';
				continue;
			}

			if (($entity['@type'] ?? '') === 'WebPage') {
				$data[$key]['@id']               = $dictionary_canonical . '#webpage';
				$data[$key]['url']               = $dictionary_canonical;
				$data[$key]['name']              = $dictionary_title;
				$data[$key]['description']       = $dictionary_description;
				$data[$key]['primaryImageOfPage'] = array('@id' => $dictionary_image);
			}
		}

		return $data;
	},
	99
);

$bbb_trope_dictionary_known = array(
	array(
		'name'        => 'age gap',
		'slug'        => 'age-gap',
		'url_slug'    => 'age-gap-romance-books',
		'description' => 'romance where a noticeable age difference shapes the tension, stakes, and dynamic.',
	),
	array(
		'name'        => 'baseball romance',
		'slug'        => 'baseball-romance',
		'url_slug'    => 'baseball-romance-books',
		'description' => 'sports romance with baseball schedules, dugout tension, and game-day feelings.',
	),
	array(
		'name'        => 'billionaire romance',
		'slug'        => 'billionaire-romance',
		'url_slug'    => 'billionaire-romance-books',
		'description' => 'money, power, rules, and the person who makes all of it feel negotiable.',
	),
	array(
		'name'        => 'boss x employee',
		'slug'        => 'boss-x-employee',
		'url_slug'    => 'boss-x-employee-romance-books',
		'description' => 'workplace power tension, forbidden attraction, and office doors that should probably stay open.',
	),
	array(
		'name'        => "brother's best friend",
		'slug'        => 'brothers-best-friend',
		'url_slug'    => 'brothers-best-friend-romance-books',
		'description' => 'forbidden proximity, family history, and the person who was supposed to be off limits.',
	),
	array(
		'name'        => 'bully romance',
		'slug'        => 'bully-romance',
		'url_slug'    => 'bully-romance-books',
		'description' => 'sharp power games, cruel edges, and chemistry that starts with conflict.',
	),
	array(
		'name'        => 'captor x captive',
		'slug'        => 'captor-x-captive',
		'url_slug'    => 'captor-x-captive-romance-books',
		'description' => 'forced proximity, danger, obsession, and power dynamics that turn complicated fast.',
	),
	array(
		'name'        => 'contemporary romance',
		'slug'        => 'contemporary-romance',
		'url_slug'    => 'contemporary-romance-books',
		'description' => 'modern romance grounded in present-day lives, messy feelings, and real-world stakes.',
	),
	array(
		'name'        => 'dark academia',
		'slug'        => 'dark-academia',
		'url_slug'    => 'dark-academia-books',
		'description' => 'old halls, secrets, obsession, and scholarly settings with a shadowed edge.',
	),
	array(
		'name'        => 'dark romance',
		'slug'        => 'dark-romance',
		'url_slug'    => 'dark-romance-books',
		'description' => 'romance with danger, obsession, moral gray, and sharper emotional stakes.',
	),
	array(
		'name'        => 'dystopian romance',
		'slug'        => 'dystopian-romance',
		'url_slug'    => 'dystopian-romance-books',
		'description' => 'love stories set in broken worlds, survival stakes, and systems worth burning down.',
	),
	array(
		'name'        => 'mafia romance',
		'slug'        => 'mafia-romance',
		'url_slug'    => 'mafia-romance-books',
		'description' => 'danger, loyalty, power, and love that does not ask permission.',
	),
	array(
		'name'        => 'marriage of convenience',
		'slug'        => 'marriage-of-convenience',
		'url_slug'    => 'marriage-of-convenience-romance-books',
		'description' => 'an arrangement, a bargain, and feelings that refuse to stay practical.',
	),
	array(
		'name'        => 'nanny',
		'slug'        => 'nanny',
		'url_slug'    => 'nanny-romance-books',
		'description' => 'caretaking, household closeness, and feelings that cross professional lines.',
	),
	array(
		'name'        => 'one bed',
		'slug'        => 'one-bed',
		'url_slug'    => 'one-bed-romance-books',
		'description' => 'forced closeness, inconvenient sleeping arrangements, and tension with nowhere to go.',
	),
	array(
		'name'        => 'slow burn',
		'slug'        => 'slow-burn',
		'url_slug'    => 'slow-burn-books',
		'description' => 'romance where the almost-touch matters as much as the payoff.',
	),
	array(
		'name'        => 'trauma bonding',
		'slug'        => 'trauma-bonding',
		'url_slug'    => 'trauma-bonding-romance-books',
		'description' => 'characters bound through shared survival, emotional fallout, and messy dependence.',
	),
	array(
		'name'        => 'second chance',
		'slug'        => 'second-chance',
		'url_slug'    => 'second-chance-romance-books',
		'description' => 'old feelings, unfinished history, and love trying again with new scars.',
	),
	array(
		'name'        => 'single dad',
		'slug'        => 'single-dad',
		'url_slug'    => 'single-dad-romance-books',
		'description' => 'parenting stakes, tenderness, responsibility, and a heart with less free time.',
	),
	array(
		'name'        => 'step siblings',
		'slug'        => 'step-siblings',
		'url_slug'    => 'step-siblings-romance-books',
		'description' => 'taboo proximity, complicated family lines, and attraction that knows better.',
	),
	array(
		'name'        => 'small town',
		'slug'        => 'small-town',
		'url_slug'    => 'small-town-romance-books',
		'description' => 'close-knit settings, familiar faces, and love where everyone knows your business.',
	),
	array(
		'name'        => 'sports romance',
		'slug'        => 'sports-romance',
		'url_slug'    => 'sports-romance-books',
		'description' => 'athletes, competition, team pressure, and off-field feelings that hit hard.',
	),
	array(
		'name'        => 'stalker romance',
		'slug'        => 'stalker-romance',
		'url_slug'    => 'stalker-romance-books',
		'description' => 'obsession, surveillance, and devotion with very questionable boundaries.',
	),
	array(
		'name'        => 'enemies to lovers',
		'slug'        => 'enemies-to-lovers',
		'url_slug'    => 'enemies-to-lovers',
		'description' => 'sharp edges, real conflict, and chemistry that turns into devotion.',
	),
	array(
		'name'        => 'fake dating',
		'slug'        => 'fake-dating',
		'url_slug'    => 'fake-dating-romance-books',
		'description' => 'pretend romance, real feelings, and an arrangement that gets out of hand.',
	),
	array(
		'name'        => 'fated mates',
		'slug'        => 'fated-mates',
		'url_slug'    => 'fated-mates-romance-books',
		'description' => 'destiny, bonds, and the feeling that the universe already chose.',
	),
	array(
		'name'        => 'forbidden love',
		'slug'        => 'forbidden-love',
		'url_slug'    => 'forbidden-love-romance-books',
		'description' => 'love that breaks rules, crosses lines, or asks for what it should not want.',
	),
	array(
		'name'        => 'friends to lovers',
		'slug'        => 'friends-to-lovers',
		'url_slug'    => 'friends-to-lovers-romance-books',
		'description' => 'comfort, history, and the moment familiar love turns into something undeniable.',
	),
	array(
		'name'        => 'forced proximity',
		'slug'        => 'forced-proximity',
		'url_slug'    => 'forced-proximity-romance-books',
		'description' => 'one room, one trip, one situation, and nowhere for the tension to hide.',
	),
	array(
		'name'        => 'found family',
		'slug'        => 'found-family',
		'url_slug'    => 'found-family-romance-books',
		'description' => 'chosen people, hard-won belonging, and love that feels like home.',
	),
	array(
		'name'        => 'grumpy x sunshine',
		'slug'        => 'grumpy-x-sunshine',
		'url_slug'    => 'grumpy-x-sunshine-romance-books',
		'description' => 'one sharp edge, one bright heart, and chemistry in the contrast.',
	),
	array(
		'name'        => 'he falls first',
		'slug'        => 'he-falls-first',
		'url_slug'    => 'he-falls-first-romance-books',
		'description' => 'he realizes he is gone before anyone else catches up.',
	),
	array(
		'name'        => 'historical romance',
		'slug'        => 'historical-romance',
		'url_slug'    => 'historical-romance-books',
		'description' => 'past eras, reputation risk, scandal, longing, and love under stricter rules.',
	),
	array(
		'name'        => 'hockey romance',
		'slug'        => 'hockey-romance',
		'url_slug'    => 'hockey-romance-books',
		'description' => 'sports romance with rink tension, team pressure, and off-ice chemistry.',
	),
	array(
		'name'        => 'touch her and die',
		'slug'        => 'touch-her-and-die',
		'url_slug'    => 'touch-her-and-die-books',
		'description' => 'feral protectiveness, absolute devotion, and consequences for anyone who hurts her.',
	),
	array(
		'name'        => 'villain gets the girl',
		'slug'        => 'villain-gets-the-girl',
		'url_slug'    => 'villain-gets-the-girl-books',
		'description' => 'the dangerous one is not the obstacle. he is the love interest.',
	),
	array(
		'name'        => 'who did this to you',
		'slug'        => 'who-did-this-to-you',
		'url_slug'    => 'who-did-this-to-you-books',
		'description' => 'protective rage, hurt/comfort, and someone ready to handle the damage.',
	),
	array(
		'name'        => 'romantasy',
		'slug'        => 'romantasy',
		'url_slug'    => 'romantasy-books',
		'description' => 'fantasy stakes, magic, kingdoms, creatures, and romance with teeth.',
	),
	array(
		'name'        => 'why choose',
		'slug'        => 'why-choose',
		'url_slug'    => 'why-choose-romance-books',
		'description' => 'one heroine, multiple love interests, and no requirement to pick only one.',
	),
	array(
		'name'        => 'paranormal romance',
		'slug'        => 'paranormal-romance',
		'url_slug'    => 'paranormal-romance-books',
		'description' => 'ghosts, vampires, shifters, immortals, and supernatural obsession.',
	),
);

$bbb_trope_dictionary_items = array();
$bbb_trope_dictionary_key   = static function (string $name, string $slug = ''): string {
	if (function_exists('bbb_custom_emoji_key')) {
		$custom_key = bbb_custom_emoji_key($name, $slug);
		if ('' !== $custom_key) {
			return $custom_key;
		}
	}

	$normalized = strtolower(trim($name . ' ' . $slug));
	$normalized = str_replace(array("brother's", 'brother-s'), 'brothers', $normalized);
	$normalized = preg_replace('/\bromance books\b|\bbooks\b|\bromance\b/', '', (string) $normalized);
	$normalized = trim((string) preg_replace('/\s+/', ' ', (string) $normalized));

	return sanitize_title('' !== $normalized ? $normalized : ($slug ?: $name));
};
$bbb_trope_dictionary_add   = static function (array $item) use (&$bbb_trope_dictionary_items, $bbb_trope_dictionary_key): void {
	$name = trim((string) ($item['name'] ?? ''));
	if ('' === $name) {
		return;
	}

	$slug = sanitize_title((string) ($item['slug'] ?? $name));
	$key  = $bbb_trope_dictionary_key($name, $slug);

	if (isset($bbb_trope_dictionary_items[$key])) {
		$bbb_trope_dictionary_items[$key] = array_merge($item, $bbb_trope_dictionary_items[$key]);
		return;
	}

	$bbb_trope_dictionary_items[$key] = array_merge(
		array(
			'name'        => $name,
			'slug'        => $slug,
			'url_slug'    => $slug,
			'url'         => '',
			'description' => '',
			'emoji'       => '',
			'kind'        => 'trope',
		),
		$item,
		array(
			'name' => $name,
			'slug' => $slug,
		)
	);
};

foreach ($bbb_trope_dictionary_known as $item) {
	$bbb_trope_dictionary_add($item);
}

$bbb_trope_dictionary_definition_fallback = static function (string $name): string {
	$label = strtolower(trim($name));

	$definitions = array(
		'bodyguard romance'       => 'protection, proximity, danger, and feelings that cross the professional line.',
		'football romance'        => 'sports romance with game-day pressure, locker room stakes, and off-field tension.',
		'golden retriever'        => 'warm, devoted love interests with big feelings and open-hearted energy.',
		'grumpy sunshine'         => 'one sharp edge, one bright heart, and chemistry in the contrast.',
		'hate to love'            => 'hostility, friction, and feelings that sneak in through the argument.',
		'morally gray'            => 'love interests who live in the messy middle between right, wrong, and devotion.',
		'morally gray hero'       => 'a hero with questionable methods, complicated motives, and absolute loyalty.',
		'morally gray men'        => 'dangerous, complicated men who love like the rules are optional.',
		'opposites attract'       => 'two very different people finding chemistry in the space between them.',
		'protective hero'         => 'a love interest whose devotion shows up as fierce care and protection.',
		'reverse harem'           => 'one heroine with multiple love interests and no need to choose just one.',
		'rivals to lovers'        => 'competition, tension, and attraction that turns the rivalry into romance.',
		'rom-com'                 => 'romance with humor, banter, chaos, and a lighter emotional landing.',
		'teammates'               => 'sports or group dynamics where shared goals turn into deeper attachment.',
		'villain romance'         => 'a romance where the dangerous one is not just the threat, but the love interest.',
		'workplace romance'       => 'professional tension, close quarters, and feelings that complicate the job.',
		'yearning'                => 'longing, restraint, and emotional tension stretched until it aches.',
	);

	if (isset($definitions[$label])) {
		return $definitions[$label];
	}

	if (str_contains($label, 'romance')) {
		return 'romance built around ' . $label . ' dynamics, tension, and emotional payoff.';
	}

	return 'a romance label for books centered on ' . $label . ' energy, tension, and reader-favorite dynamics.';
};

$bbb_trope_dictionary_page_field = static function (string $key, int $post_id, string $fallback = ''): string {
	if (function_exists('bbb_get_field')) {
		return (string) bbb_get_field($key, $post_id, $fallback);
	}

	if (function_exists('get_field')) {
		$value = get_field($key, $post_id);

		return '' !== (string) $value ? (string) $value : $fallback;
	}

	$value = get_post_meta($post_id, $key, true);

	return '' !== (string) $value ? (string) $value : $fallback;
};

$bbb_trope_dictionary_page_templates = array(
	'page-trope.php' => 'trope',
	'page-shelf.php' => 'shelf',
);
foreach ($bbb_trope_dictionary_page_templates as $template => $kind) {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_key'       => '_wp_page_template',
			'meta_value'     => $template,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	foreach ($pages as $page) {
		if (!$page instanceof WP_Post) {
			continue;
		}

		$name_key        = 'shelf' === $kind ? 'shelf_name' : 'trope_name';
		$description_key = 'shelf' === $kind ? 'shelf_description' : 'trope_description';
		$emoji_key       = 'shelf' === $kind ? 'shelf_emoji' : 'trope_emoji';
		$name            = $bbb_trope_dictionary_page_field($name_key, (int) $page->ID, get_the_title($page));
		$description     = $bbb_trope_dictionary_page_field($description_key, (int) $page->ID, '');

		$bbb_trope_dictionary_add(
			array(
				'name'        => $name,
				'slug'        => $page->post_name,
				'url_slug'    => $page->post_name,
				'url'         => get_permalink($page),
				'description' => $description,
				'emoji'       => $bbb_trope_dictionary_page_field($emoji_key, (int) $page->ID, ''),
				'kind'        => $kind,
			)
		);
	}
}

if (function_exists('bbb_get_book_taxonomy_discovery_items')) {
	foreach (array('trope', 'shelf') as $kind) {
		foreach (bbb_get_book_taxonomy_discovery_items($kind) as $item) {
			$bbb_trope_dictionary_add(
				array(
					'name'        => (string) ($item['name'] ?? ''),
					'slug'        => sanitize_title((string) ($item['name'] ?? '')),
					'url'         => (string) ($item['url'] ?? ''),
					'description' => (string) ($item['description'] ?? ''),
					'emoji'       => (string) ($item['emoji'] ?? ''),
					'kind'        => $kind,
				)
			);
		}
	}
}

uasort(
	$bbb_trope_dictionary_items,
	static fn(array $a, array $b): int => strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''))
);

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-trope-dictionary">
		<div class="bbb-trope-dictionary__wrap">
			<header class="bbb-trope-dictionary__hero">
				<p class="bbb-trope-dictionary__kicker">reader shorthand</p>
				<h1>romance trope dictionary</h1>
				<p>an alphabetized view of the trope and genre labels, with quick definitions and links to browse the books.</p>
			</header>

			<div class="bbb-trope-dictionary__list" aria-label="Romance trope dictionary">
				<?php foreach ($bbb_trope_dictionary_items as $item) : ?>
					<?php
					$name        = (string) ($item['name'] ?? '');
					$slug        = (string) ($item['slug'] ?? '');
					$url_slug    = (string) ($item['url_slug'] ?? $slug);
					$url         = (string) ($item['url'] ?? '');
					if (str_contains($url, '/blog/') || str_contains($url, '/curated-romance-guides/')) {
						$url = '';
					}
					$url         = '' !== $url ? $url : (function_exists('bbb_page_url') ? bbb_page_url($url_slug) : home_url('/' . trim($url_slug, '/') . '/'));
					$emoji_html  = function_exists('bbb_custom_emoji_html') ? bbb_custom_emoji_html($name, $slug, 'bbb-trope-dictionary__emojiImage') : '';
					$has_custom  = '' !== $emoji_html;
					$emoji       = trim((string) ($item['emoji'] ?? ''));
					$description = trim((string) ($item['description'] ?? ''));
					$description = '' !== $description ? $description : $bbb_trope_dictionary_definition_fallback($name);
					$kind        = (string) ($item['kind'] ?? 'trope');
					?>
					<a class="bbb-trope-dictionary__card <?php echo $has_custom ? 'has-custom-emoji' : 'needs-custom-emoji'; ?>" href="<?php echo esc_url($url); ?>">
						<span class="bbb-trope-dictionary__emoji" aria-hidden="true">
							<?php echo $has_custom ? wp_kses_post($emoji_html) : ''; ?>
						</span>
						<span class="bbb-trope-dictionary__copy">
							<strong><?php echo esc_html($name); ?></strong>
							<span><?php echo esc_html($description); ?></span>
						</span>
						<span class="bbb-trope-dictionary__meta">
							<span>see books</span>
						</span>
						<span class="bbb-trope-dictionary__arrow" aria-hidden="true">→</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
