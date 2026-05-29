<?php
/**
 * Trope pill colors.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_default_trope_emoji')) {
	function bbb_default_trope_emoji(): string {
		return '🖤';
	}
}

if (!function_exists('bbb_trope_emoji')) {
	function bbb_trope_emoji($emoji = ''): string {
		$emoji = trim((string) $emoji);

		return '' !== $emoji ? $emoji : bbb_default_trope_emoji();
	}
}

if (!function_exists('bbb_trope_label')) {
	function bbb_trope_label(string $name, $emoji = ''): string {
		$name = trim($name);
		if ('' !== $name && function_exists('bbb_custom_emoji_asset') && '' !== bbb_custom_emoji_asset($name)) {
			return $name;
		}

		return trim(bbb_trope_emoji($emoji) . ' ' . $name);
	}
}

if (!function_exists('bbb_trope_page_url')) {
	function bbb_trope_page_url(string $name = '', string $slug = ''): string {
		$handle = sanitize_title($slug ?: $name);
		if ('' === $handle) {
			return '#';
		}

		$lookup_slugs = array_values(array_unique(array_filter(array(
			$handle,
			preg_replace('/-books$/', '', $handle),
		))));

		foreach (array('bbb_trope', 'sss_trope') as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			foreach ($lookup_slugs as $lookup_slug) {
				$term = get_term_by('slug', $lookup_slug, $taxonomy);
				if ($term instanceof WP_Term) {
					$url = function_exists('bbb_book_taxonomy_term_url') ? bbb_book_taxonomy_term_url($term) : get_term_link($term);
					if (!is_wp_error($url)) {
						return $url;
					}
				}
			}
		}

		if (substr($handle, -6) !== '-books') {
			$handle .= '-books';
		}

		return home_url('/' . $handle . '/');
	}
}

if (!function_exists('bbb_custom_emoji_key')) {
	function bbb_custom_emoji_key(string $text = '', string $slug = ''): string {
		$key      = sanitize_title($slug ?: $text);
		$haystack = strtolower($key . ' ' . $text . ' ' . $slug);

		if ('mafia' === $key || 'mafia-romance' === $key || str_contains($haystack, 'mafia')) {
			return 'mafia-romance';
		}

		if ('slow-burn' === $key || str_contains($haystack, 'slow-burn') || str_contains($haystack, 'slow burn')) {
			return 'slow-burn';
		}

		if ('enemies-to-lovers' === $key || str_contains($haystack, 'enemies-to-lovers') || str_contains($haystack, 'enemies to lovers')) {
			return 'enemies-to-lovers';
		}

		if ('friends-to-lovers' === $key || str_contains($haystack, 'friends-to-lovers') || str_contains($haystack, 'friends to lovers')) {
			return 'friends-to-lovers';
		}

		if ('he-falls-first' === $key || 'falls-first' === $key || str_contains($haystack, 'he-falls-first') || str_contains($haystack, 'he falls first') || str_contains($haystack, 'falls first')) {
			return 'he-falls-first';
		}

		if ('billionaire-romance' === $key || 'billionaire' === $key || str_contains($haystack, 'billionaire romance') || str_contains($haystack, 'billionaire-romance')) {
			return 'billionaire-romance';
		}

		if ('stalker-romance' === $key || 'stalker' === $key || str_contains($haystack, 'stalker romance') || str_contains($haystack, 'stalker-romance')) {
			return 'stalker-romance';
		}

		if ('dystopian-romance' === $key || str_contains($haystack, 'dystopian romance') || str_contains($haystack, 'dystopian-romance')) {
			return 'dystopian-romance';
		}

		if ('sports-romance' === $key || 'sports' === $key || str_contains($haystack, 'sports romance') || str_contains($haystack, 'sports-romance')) {
			return 'sports-romance';
		}

		if ('bully-romance' === $key || 'bully' === $key || str_contains($haystack, 'bully romance') || str_contains($haystack, 'bully-romance')) {
			return 'bully-romance';
		}

		if ('forced-proximity' === $key || str_contains($haystack, 'forced proximity') || str_contains($haystack, 'forced-proximity')) {
			return 'forced-proximity';
		}

		if ('villain-gets-the-girl' === $key || 'villain-romance' === $key || str_contains($haystack, 'villain gets the girl') || str_contains($haystack, 'villain-gets-the-girl')) {
			return 'villain-gets-the-girl';
		}

		if ('historical-romance' === $key || str_contains($haystack, 'historical romance') || str_contains($haystack, 'historical-romance')) {
			return 'historical-romance';
		}

		if ('bodyguard-romance' === $key || 'bodyguard' === $key || str_contains($haystack, 'bodyguard romance') || str_contains($haystack, 'bodyguard-romance')) {
			return 'bodyguard-romance';
		}

		if ('opposites-attract' === $key || str_contains($haystack, 'opposites attract') || str_contains($haystack, 'opposites-attract')) {
			return 'opposites-attract';
		}

		if ('marriage-of-convenience' === $key || str_contains($haystack, 'marriage of convenience') || str_contains($haystack, 'marriage-of-convenience')) {
			return 'marriage-of-convenience';
		}

		if ('found-family' === $key || str_contains($haystack, 'found family') || str_contains($haystack, 'found-family')) {
			return 'found-family';
		}

		if ('dark-academia' === $key || 'dark-academia-romance' === $key || str_contains($haystack, 'dark academia')) {
			return 'dark-academia';
		}

		if ('captor-x-captive' === $key || 'captor-captive-romance' === $key || str_contains($haystack, 'captor') || str_contains($haystack, 'captive')) {
			return 'captor-x-captive';
		}

		if ('boss-x-employee' === $key || 'boss-employee' === $key || str_contains($haystack, 'boss x employee') || str_contains($haystack, 'boss-x-employee') || str_contains($haystack, 'boss employee')) {
			return 'boss-x-employee';
		}

		if ('age-gap' === $key || str_contains($haystack, 'age gap') || str_contains($haystack, 'age-gap')) {
			return 'age-gap';
		}

		if ('trauma-bonding' === $key || str_contains($haystack, 'trauma bonding') || str_contains($haystack, 'trauma-bonding')) {
			return 'trauma-bonding';
		}

		if ('baseball-romance' === $key || str_contains($haystack, 'baseball romance') || str_contains($haystack, 'baseball-romance')) {
			return 'baseball-romance';
		}

		if ('hockey-romance' === $key || str_contains($haystack, 'hockey romance') || str_contains($haystack, 'hockey-romance')) {
			return 'hockey-romance';
		}

		if ('contemporary-romance' === $key || str_contains($haystack, 'contemporary romance') || str_contains($haystack, 'contemporary-romance')) {
			return 'contemporary-romance';
		}

		if ('dark-romance' === $key || str_contains($haystack, 'dark romance') || str_contains($haystack, 'dark-romance')) {
			return 'dark-romance';
		}

		if ('forbidden-love' === $key || 'forbidden-romance' === $key || str_contains($haystack, 'forbidden love') || str_contains($haystack, 'forbidden romance') || str_contains($haystack, 'forbidden-love') || str_contains($haystack, 'forbidden-romance')) {
			return 'forbidden-love';
		}

		if ('step-siblings' === $key || 'stepsiblings' === $key || str_contains($haystack, 'step siblings') || str_contains($haystack, 'step-siblings') || str_contains($haystack, 'stepsiblings')) {
			return 'step-siblings';
		}

		if ('nanny' === $key || 'nanny-romance' === $key || str_contains($haystack, 'nanny romance') || str_contains($haystack, 'nanny')) {
			return 'nanny';
		}

		if ('single-dad' === $key || 'single-dad-romance' === $key || str_contains($haystack, 'single dad') || str_contains($haystack, 'single-dad')) {
			return 'single-dad';
		}

		if ('small-town' === $key || 'small-town-romance' === $key || str_contains($haystack, 'small town') || str_contains($haystack, 'small-town')) {
			return 'small-town';
		}

		if ('grumpy-x-sunshine' === $key || 'grumpy-sunshine' === $key || str_contains($haystack, 'grumpy x sunshine') || str_contains($haystack, 'grumpy sunshine') || str_contains($haystack, 'grumpy-x-sunshine') || str_contains($haystack, 'grumpy-sunshine')) {
			return 'grumpy-x-sunshine';
		}

		if ('one-bed' === $key || str_contains($haystack, 'one bed') || str_contains($haystack, 'one-bed')) {
			return 'one-bed';
		}

		if ('brothers-best-friend' === $key || 'brother-s-best-friend' === $key || str_contains($haystack, 'brother best friend') || str_contains($haystack, 'brothers best friend') || str_contains($haystack, "brother's best friend") || str_contains($haystack, 'brothers-best-friend') || str_contains($haystack, 'brother-s-best-friend')) {
			return 'brothers-best-friend';
		}

		if ('second-chance' === $key || str_contains($haystack, 'second chance') || str_contains($haystack, 'second-chance')) {
			return 'second-chance';
		}

		if ('fake-dating' === $key || 'fake-dating-romance' === $key || str_contains($haystack, 'fake dating')) {
			return 'fake-dating';
		}

		if ('fated-mates' === $key || str_contains($haystack, 'fated-mates') || str_contains($haystack, 'fated mates')) {
			return 'fated-mates';
		}

		if ('who-did-this-to-you' === $key || str_contains($haystack, 'who-did-this-to-you') || str_contains($haystack, 'who did this to you')) {
			return 'who-did-this-to-you';
		}

		if ('touch-her-and-die' === $key || str_contains($haystack, 'touch-her-and-die') || str_contains($haystack, 'touch her and die')) {
			return 'touch-her-and-die';
		}

		if ('why-choose' === $key || str_contains($haystack, 'why-choose') || str_contains($haystack, 'why choose')) {
			return 'why-choose';
		}

		if ('paranormal' === $key || 'paranormal-romance' === $key || str_contains($haystack, 'paranormal')) {
			return 'paranormal-romance';
		}

		if ('romantasy' === $key || 'fantasy-romance' === $key || str_contains($haystack, 'romantasy') || str_contains($haystack, 'fantasy romance')) {
			return 'romantasy';
		}

		return '';
	}
}

if (!function_exists('bbb_custom_emoji_assets')) {
	function bbb_custom_emoji_assets(): array {
		return array(
			'age-gap'         => 'assets/images/custom-emojis/age-gap.png',
			'baseball-romance' => 'assets/images/custom-emojis/baseball-romance.png',
			'billionaire-romance' => 'assets/images/custom-emojis/billionaire-romance.png',
			'bodyguard-romance' => 'assets/images/custom-emojis/bodyguard-romance.png',
			'boss-x-employee' => 'assets/images/custom-emojis/boss-x-employee.png',
			'brothers-best-friend' => 'assets/images/custom-emojis/brothers-best-friend.png',
			'bully-romance'    => 'assets/images/custom-emojis/bully-romance.png',
			'captor-x-captive' => 'assets/images/custom-emojis/captor-x-captive.png',
			'contemporary-romance' => 'assets/images/custom-emojis/contemporary-romance.png',
			'dark-academia'     => 'assets/images/custom-emojis/dark-academia.png',
			'dark-romance'      => 'assets/images/custom-emojis/dark-romance.png',
			'dystopian-romance' => 'assets/images/custom-emojis/dystopian-romance.png',
			'enemies-to-lovers' => 'assets/images/custom-emojis/enemies-to-lovers.png',
			'fake-dating'       => 'assets/images/custom-emojis/fake-dating.png',
			'fated-mates'       => 'assets/images/custom-emojis/fated-mates.png',
			'forbidden-love'    => 'assets/images/custom-emojis/forbidden-love.png',
			'forced-proximity'  => 'assets/images/custom-emojis/forced-proximity.png',
			'found-family'      => 'assets/images/custom-emojis/found-family.png',
			'friends-to-lovers' => 'assets/images/custom-emojis/friends-to-lovers.png',
			'grumpy-x-sunshine' => 'assets/images/custom-emojis/grumpy-x-sunshine.png',
			'he-falls-first'    => 'assets/images/custom-emojis/he-falls-first.png',
			'historical-romance' => 'assets/images/custom-emojis/historical-romance.png',
			'hockey-romance'    => 'assets/images/custom-emojis/hockey-romance.png',
			'mafia-romance'     => 'assets/images/custom-emojis/mafia-romance.png',
			'marriage-of-convenience' => 'assets/images/custom-emojis/marriage-of-convenience.png',
			'nanny'            => 'assets/images/custom-emojis/nanny.png',
			'one-bed'          => 'assets/images/custom-emojis/one-bed.png',
			'opposites-attract' => 'assets/images/custom-emojis/opposites-attract.png',
			'paranormal-romance' => 'assets/images/custom-emojis/paranormal-romance.png',
			'romantasy'         => 'assets/images/custom-emojis/romantasy.png',
			'second-chance'     => 'assets/images/custom-emojis/second-chance.png',
			'single-dad'       => 'assets/images/custom-emojis/single-dad.png',
			'slow-burn'         => 'assets/images/custom-emojis/slow-burn.png',
			'small-town'       => 'assets/images/custom-emojis/small-town.png',
			'sports-romance'   => 'assets/images/custom-emojis/sports-romance.png',
			'stalker-romance'  => 'assets/images/custom-emojis/stalker-romance.png',
			'step-siblings'     => 'assets/images/custom-emojis/step-siblings.png',
			'trauma-bonding'    => 'assets/images/custom-emojis/trauma-bonding.png',
			'touch-her-and-die' => 'assets/images/custom-emojis/touch-her-and-die.png',
			'villain-gets-the-girl' => 'assets/images/custom-emojis/villain-gets-the-girl.png',
			'who-did-this-to-you' => 'assets/images/custom-emojis/who-did-this-to-you.png',
			'why-choose'        => 'assets/images/custom-emojis/why-choose.png',
		);
	}
}

if (!function_exists('bbb_custom_emoji_urls')) {
	function bbb_custom_emoji_urls(): array {
		$urls = array();

		foreach (bbb_custom_emoji_assets() as $key => $asset) {
			$urls[$key] = get_theme_file_uri($asset);
		}

		return $urls;
	}
}

if (!function_exists('bbb_custom_emoji_asset')) {
	function bbb_custom_emoji_asset(string $text = '', string $slug = ''): string {
		$map = bbb_custom_emoji_assets();
		return $map[bbb_custom_emoji_key($text, $slug)] ?? '';
	}
}

if (!function_exists('bbb_custom_emoji_html')) {
	function bbb_custom_emoji_html(string $text = '', string $slug = '', string $class = ''): string {
		$asset = bbb_custom_emoji_asset($text, $slug);
		if ('' !== $asset) {
			$classes = trim('bbb-custom-emoji ' . $class);

			return sprintf(
				'<img class="%s" src="%s" alt="" aria-hidden="true" loading="lazy" decoding="async">',
				esc_attr($classes),
				esc_url(get_theme_file_uri($asset))
			);
		}

		return '';
	}
}

if (!function_exists('bbb_custom_trope_emoji_asset')) {
	function bbb_custom_trope_emoji_asset(string $name = '', string $slug = ''): string {
		return bbb_custom_emoji_asset($name, $slug);
	}
}

if (!function_exists('bbb_trope_emoji_html')) {
	function bbb_trope_emoji_html(string $name, $emoji = '', string $slug = ''): string {
		$custom_emoji = bbb_custom_emoji_html($name, $slug);
		if ('' !== $custom_emoji) {
			return $custom_emoji;
		}

		return sprintf(
			'<span class="bbb-custom-emoji bbb-custom-emoji--text" aria-hidden="true">%s</span>',
			esc_html(bbb_trope_emoji($emoji))
		);
	}
}

if (!function_exists('bbb_trope_label_html')) {
	function bbb_trope_label_html(string $name, $emoji = '', string $slug = ''): string {
		$name = trim($name);

		if ('' === $name) {
			return bbb_trope_emoji_html($name, $emoji, $slug);
		}

		if ('grumpy-x-sunshine' === bbb_custom_emoji_key($name, $slug)) {
			$name = 'grumpy x sunshine';
		}

		return bbb_trope_emoji_html($name, $emoji, $slug) . ' <span class="bbb-custom-emoji-label">' . esc_html($name) . '</span>';
	}
}

/**
 * Returns [bg_hex, text_hex] for a trope slug.
 *
 * @param string $slug
 * @return array{0:string,1:string}
 */
function bbb_get_trope_colors(string $slug): array {
	$map = array(
		'enemies-to-lovers'       => array('#f2a7ad', '#6e1422'),
		'friends-to-lovers'       => array('#bfe3cb', '#144a31'),
		'slow-burn'               => array('#f2c179', '#6a3700'),
		'billionaire-romance'     => array('#bfdca0', '#365316'),
		'billionaire'             => array('#bfdca0', '#365316'),
		'second-chance'           => array('#cfbef5', '#4b2280'),
		'forced-proximity'        => array('#a9cdf6', '#163f72'),
		'grumpy-sunshine'         => array('#f2d35f', '#5f4700'),
		'workplace-romance'       => array('#bfd0ef', '#274469'),
		'fake-dating'             => array('#efb6d3', '#6e2147'),
		'marriage-of-convenience' => array('#dbc2a7', '#6c4221'),
		'sports-romance'          => array('#9fd8e5', '#0f5064'),
		'small-town'              => array('#c7d89b', '#405719'),
		'brothers-best-friend'    => array('#ebb99c', '#71351a'),
		'dark-romance'            => array('#b8a0d8', '#2f1646'),
		'stalker-romance'         => array('#b8a0d8', '#2f1646'),
		'stalker'                 => array('#b8a0d8', '#2f1646'),
		'morally-gray-hero'       => array('#b9c1cb', '#26303b'),
		'morally-gray-men'        => array('#b9c1cb', '#26303b'),
		'morally-gray'            => array('#b9c1cb', '#26303b'),
		'touch-her-and-die'       => array('#e596a8', '#641223'),
		'one-bed'                 => array('#d8b9ea', '#55276f'),
		'fated-mates'             => array('#e7acd1', '#74204f'),
		'age-gap'                 => array('#c4d4ec', '#31486e'),
		'single-dad'              => array('#b7dbc9', '#1f543b'),
		'reverse-harem'           => array('#d7a8d7', '#651c58'),
	);

	return $map[$slug] ?? array('#f3bfd5', '#4b112d');
}
