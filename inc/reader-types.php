<?php
/**
 * Reader type registry for quiz/dashboard presentation.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_reader_type_registry(): array {
	$themes = array(
		'chaos_reader'          => array('name' => 'scarlet riot', 'surface' => '#1C0A0C', 'border' => '#3D1216', 'deep' => '#8A1622', 'accent' => '#FF4040', 'accent2' => '#FFB3B8', 'onAccent' => '#4A0408', 'textHeading' => '#FFF2F2', 'textBody' => '#F4E2E4', 'textMuted' => '#C49AA0', 'glow' => 'rgba(255,64,64,.14)'),
		'dark_romance_girlie'   => array('name' => 'oxblood velvet', 'surface' => '#170609', 'border' => '#38101C', 'deep' => '#6E1233', 'accent' => '#E0245E', 'accent2' => '#F77FAB', 'onAccent' => '#3D0218', 'textHeading' => '#FBEFF4', 'textBody' => '#EFDCE4', 'textMuted' => '#B58FA0', 'glow' => 'rgba(224,36,94,.13)'),
		'tension_addict'        => array('name' => 'live wire', 'surface' => '#1A0C06', 'border' => '#3D1C0E', 'deep' => '#8A3A14', 'accent' => '#FF7438', 'accent2' => '#FFC09A', 'onAccent' => '#471A04', 'textHeading' => '#FFF4ED', 'textBody' => '#F4E4DA', 'textMuted' => '#C7A48F', 'glow' => 'rgba(255,116,56,.13)'),
		'slow_burn_girlie'      => array('name' => 'candlelit amber', 'surface' => '#181004', 'border' => '#3B2A0C', 'deep' => '#7D5A14', 'accent' => '#F2B13D', 'accent2' => '#FFE0A3', 'onAccent' => '#3F2A04', 'textHeading' => '#FFF8EC', 'textBody' => '#F2E8D4', 'textMuted' => '#C2B08C', 'glow' => 'rgba(242,177,61,.12)'),
		'fake_dating_fanatic'   => array('name' => 'bubblegum noir', 'surface' => '#1B0814', 'border' => '#3F1230', 'deep' => '#87265F', 'accent' => '#FF6FC2', 'accent2' => '#FFC2E4', 'onAccent' => '#470A30', 'textHeading' => '#FFF1F8', 'textBody' => '#F4DEEA', 'textMuted' => '#C397B0', 'glow' => 'rgba(255,111,194,.14)'),
		'sweet_romance_devotee' => array('name' => 'blush hour', 'surface' => '#170D11', 'border' => '#38202A', 'deep' => '#74404F', 'accent' => '#FFB3C6', 'accent2' => '#FFDDE7', 'onAccent' => '#4A1626', 'textHeading' => '#FFF4F7', 'textBody' => '#F2E3E8', 'textMuted' => '#BFA0AB', 'glow' => 'rgba(255,179,198,.10)'),
		'fantasy_girlie'        => array('name' => 'amethyst hour', 'surface' => '#110A1D', 'border' => '#271746', 'deep' => '#4E2E96', 'accent' => '#A875FF', 'accent2' => '#D4BCFF', 'onAccent' => '#25104F', 'textHeading' => '#F7F2FF', 'textBody' => '#E8DEF7', 'textMuted' => '#A996C9', 'glow' => 'rgba(168,117,255,.14)'),
		'jersey_chaser'         => array('name' => 'rink lights', 'surface' => '#07101C', 'border' => '#122B4A', 'deep' => '#1A4E8F', 'accent' => '#4FA8FF', 'accent2' => '#A8D2FF', 'onAccent' => '#062646', 'textHeading' => '#F0F7FF', 'textBody' => '#DCE8F4', 'textMuted' => '#8FA9C7', 'glow' => 'rgba(79,168,255,.13)'),
		'romance_reader'        => array('name' => 'unsorted silver', 'surface' => '#131013', 'border' => '#2E282C', 'deep' => '#5E5258', 'accent' => '#D4C2CE', 'accent2' => '#EFE4EA', 'onAccent' => '#2E242A', 'textHeading' => '#FAF6F8', 'textBody' => '#EAE2E6', 'textMuted' => '#A89AA1', 'glow' => 'rgba(212,194,206,.08)'),
	);

	return array(
		array('key' => 'chaos_reader', 'label' => 'the chaos reader', 'emoji' => 'why-choose', 'bio' => 'maximum spice, maximum plot complications, very little concern for emotional safety.', 'signal' => 'maximum spice, maximum plot complications, very little concern for emotional safety.', 'triggers' => array('why-choose', 'forbidden-love', 'mafia-romance', 'touch-her-and-die', 'stalker-romance'), 'theme' => $themes['chaos_reader']),
		array('key' => 'dark_romance_girlie', 'label' => 'the dark romance girlie', 'emoji' => 'touch-her-and-die', 'bio' => 'danger, obsession, morally gray choices, and devotion with teeth.', 'signal' => 'danger, obsession, morally gray choices, and devotion with teeth.', 'triggers' => array('dark-romance', 'stalker-romance', 'mafia-romance', 'captor-x-captive', 'villain-gets-the-girl', 'bully-romance'), 'theme' => $themes['dark_romance_girlie']),
		array('key' => 'fantasy_girlie', 'label' => 'the fantasy girlie', 'emoji' => 'fated-mates', 'bio' => 'magic systems, fated stakes, crowns, monsters, and dramatic yearning.', 'signal' => 'magic systems, fated stakes, crowns, monsters, and dramatic yearning.', 'triggers' => array('romantasy', 'paranormal-romance', 'fated-mates', 'dystopian-romance'), 'theme' => $themes['fantasy_girlie']),
		array('key' => 'jersey_chaser', 'label' => 'the jersey chaser', 'emoji' => 'hockey-romance', 'bio' => 'athletes, rivalry, teammates, locker-room confidence, and softness after the game.', 'signal' => 'athletes, rivalry, teammates, locker-room confidence, and softness after the game.', 'triggers' => array('sports-romance', 'hockey-romance', 'baseball-romance'), 'theme' => $themes['jersey_chaser']),
		array('key' => 'slow_burn_girlie', 'label' => 'the slow burn girlie', 'emoji' => 'slow-burn', 'bio' => 'glances, restraint, almost-confessions, and payoff that takes its sweet time.', 'signal' => 'glances, restraint, almost-confessions, and payoff that takes its sweet time.', 'triggers' => array('slow-burn', 'he-falls-first', 'forbidden-love', 'second-chance'), 'theme' => $themes['slow_burn_girlie']),
		array('key' => 'tension_addict', 'label' => 'the tension addict', 'emoji' => 'enemies-to-lovers', 'bio' => 'banter, friction, rivalry, and the very specific joy of two people losing the argument.', 'signal' => 'banter, friction, rivalry, and the very specific joy of two people losing the argument.', 'triggers' => array('enemies-to-lovers', 'forced-proximity', 'grumpy-sunshine', 'opposites-attract', 'boss-x-employee'), 'theme' => $themes['tension_addict']),
		array('key' => 'fake_dating_fanatic', 'label' => 'the fake dating fanatic', 'emoji' => 'fake-dating', 'bio' => 'contracts, public pretending, private feelings, and the moment the lie starts telling the truth.', 'signal' => 'contracts, public pretending, private feelings, and the moment the lie starts telling the truth.', 'triggers' => array('fake-dating', 'one-bed', 'marriage-of-convenience'), 'theme' => $themes['fake_dating_fanatic']),
		array('key' => 'sweet_romance_devotee', 'label' => 'the sweet romance devotee', 'emoji' => 'friends-to-lovers', 'bio' => 'comfort, tenderness, caretaking, and low-spice softness that still knows how to ache.', 'signal' => 'comfort, tenderness, caretaking, and low-spice softness that still knows how to ache.', 'triggers' => array('friends-to-lovers', 'small-town', 'found-family', 'single-dad', 'grumpy-sunshine', 'contemporary-romance'), 'theme' => $themes['sweet_romance_devotee']),
		array('key' => 'romance_reader', 'label' => 'the romance reader', 'emoji' => 'found-family', 'bio' => 'balanced taste, flexible moods, and a dashboard still learning the exact flavor of book chaos.', 'signal' => 'balanced taste, flexible moods, and a dashboard still learning the exact flavor of book chaos.', 'triggers' => array('found-family', 'opposites-attract', 'second-chance', 'he-falls-first', 'contemporary-romance'), 'theme' => $themes['romance_reader']),
	);
}

function bbb_reader_type_by_key(string $key): ?array {
	foreach (bbb_reader_type_registry() as $reader_type) {
		if ((string) $reader_type['key'] === $key) {
			return $reader_type;
		}
	}

	return null;
}

function bbb_custom_emoji_registry(): array {
	$files  = glob(get_theme_file_path('assets/images/custom-emojis/*.png')) ?: array();
	$emojis = array();
	foreach ($files as $file) {
		$key = basename($file, '.png');
		if ('grumpy-sunshine' === $key) {
			continue;
		}

		$emojis[] = array(
			'key'   => $key,
			'label' => str_replace('-', ' ', $key),
			'url'   => get_theme_file_uri('assets/images/custom-emojis/' . basename($file)),
		);
	}

	usort($emojis, static fn(array $a, array $b): int => strcmp((string) $a['label'], (string) $b['label']));

	return $emojis;
}

function bbb_custom_emoji_url(string $key): string {
	$key = sanitize_title($key);
	if ('' === $key) {
		return '';
	}

	$path = 'assets/images/custom-emojis/' . $key . '.png';
	return file_exists(get_theme_file_path($path)) ? get_theme_file_uri($path) : '';
}
