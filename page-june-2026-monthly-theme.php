<?php
/**
 * Template Name: June 2026 Monthly Theme
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

wp_enqueue_style('bbb-font-burn-bright', 'https://fonts.googleapis.com/css2?family=DM+Mono:wght@300;400;500&family=DM+Sans:wght@300;400;500;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&display=swap', array(), null);
bbb_enqueue_css('bbb-june-2026-monthly-theme', 'assets/css/june-2026-monthly-theme.css', array('bbb-font-burn-bright'));
bbb_enqueue_js('bbb-june-2026-monthly-theme', 'assets/js/june-2026-monthly-theme.js', array('bbb-sss-library'), true);

$asset_base = 'assets/monthly-themes/june-2026';
$is_paid_society_member = function_exists('bbb_reader_is_society') && bbb_reader_is_society();
$theme_release_at = '2026-06-01 00:00:00';
$theme_is_released = current_time('Y-m-d H:i:s') >= $theme_release_at;
$has_theme_access = $is_paid_society_member && $theme_is_released;
$calendar_pdf_url = get_theme_file_uri($asset_base . '/downloads/June2026_Calendar.pdf');
$calendar_preview_url = get_theme_file_uri($asset_base . '/previews/june-2026-calendar.png');
$canva_template_url = 'https://canva.link/595w9ekd1jv34re';
$join_url = function_exists('bbb_substack_subscribe_url') ? bbb_substack_subscribe_url() : 'https://thesmutandsentimentsociety.substack.com/subscribe';
$locked_url = $is_paid_society_member ? '#burn-designs' : $join_url;
$locked_link_attrs = $is_paid_society_member ? '' : ' target="_blank" rel="noopener"';
$locked_release_label = $theme_is_released ? 'locked' : 'releases june 1';
$theme_access_note = 'preview mode: downloads and templates unlock with paid society membership.';

if ($has_theme_access) {
	$theme_access_note = 'paid society access active: downloads, templates, and extras are unlocked.';
} elseif ($is_paid_society_member && !$theme_is_released) {
	$theme_access_note = 'paid society access recognized: burn bright unlocks june 1 at midnight.';
} elseif (!$theme_is_released) {
	$theme_access_note = 'preview mode: burn bright unlocks for paid society members june 1 at midnight.';
}
$designs = array(
	array(
		'num'      => '01',
		'world'    => 'disco ember',
		'name'     => 'alive in the night',
		'slogan'   => 'i never let them know too much',
		'desc'     => 'film strips, disco balls, neon signs. dark and electric. she moves through rooms like a rumor.',
		'vibe'     => array('film grain', 'neon orange', 'midnight', 'untouchable'),
		'image'    => 'previews/alive-in-the-night.png',
		'mockup'   => 'previews/alive-in-the-night-mockup.png',
		'wallpaper' => 'wallpapers/alive-in-the-night-wallpaper.png',
		'file_key' => 'AliveintheNight',
		'class'    => 'night',
	),
	array(
		'num'      => '02',
		'world'    => 'golden hour',
		'name'     => 'golden & unbothered',
		'slogan'   => 'great times are coming',
		'desc'     => 'burnt sienna, vintage chrome, torn edges. moody richness. she does not chase. things find her.',
		'vibe'     => array('deep burnt', 'collage', 'vintage car', 'unhurried'),
		'image'    => 'previews/golden-and-unbothered.png',
		'mockup'   => 'previews/golden-and-unbothered-mockup.png',
		'wallpaper' => 'wallpapers/golden-and-unbothered-wallpaper.png',
		'file_key' => 'GoldenandUnbothered',
		'class'    => 'golden',
	),
	array(
		'num'      => '03',
		'world'    => 'the monarch',
		'name'     => 'you glow different',
		'slogan'   => 'the kind of light you cannot fake',
		'desc'     => 'bright orange, citrus, butterflies, torn paper, open sky. soft daylight maximalism. she is the warm part of summer.',
		'vibe'     => array('bright citrus', 'monarchs', 'torn paper', 'golden'),
		'image'    => 'previews/the-light-finds-you-first.png',
		'mockup'   => 'previews/the-light-finds-you-first-mockup.png',
		'wallpaper' => 'wallpapers/the-light-finds-you-first-wallpaper.png',
		'file_key' => 'TheLightFindsYouFirst',
		'class'    => 'light',
	),
	array(
		'num'      => '04',
		'world'    => 'marigold season',
		'name'     => 'the light finds you first',
		'slogan'   => 'radiant by nature',
		'desc'     => 'dark botanicals, marigolds, camera lens quiet. cinematic stillness. she blooms in the dark.',
		'vibe'     => array('dark floral', 'botanical', 'cinematic', 'quiet fire'),
		'image'    => 'previews/you-glow-different.png',
		'mockup'   => 'previews/you-glow-different-mockup.png',
		'wallpaper' => 'wallpapers/you-glow-different-wallpaper.png',
		'file_key' => 'YouGlowDifferent',
		'class'    => 'glow',
	),
);

$sizes = array(
	'6 inch'   => '6Inch_Printable_%s.pdf',
	'10th gen' => '10thGen_Printable_%s.pdf',
	'11th gen' => '11thGen_Printable_%s.pdf',
	'12th gen' => '12thGen_Printable_%s.pdf',
);

$quotes = array(
	array('text' => 'she was becoming herself and daily casting aside that fictitious self.', 'attr' => 'kate chopin', 'for' => 'you glow different'),
	array('text' => 'there is no instinct like that of the heart.', 'attr' => 'lord byron', 'for' => 'alive in the night'),
	array('text' => 'i am not afraid of storms, for i am learning how to sail my ship.', 'attr' => 'louisa may alcott', 'for' => 'the light finds you first'),
	array('text' => 'she is too fond of books, and it has turned her brain.', 'attr' => 'louisa may alcott', 'for' => 'golden & unbothered'),
);

$book_picks = array(
	array(
		'handle'     => 'caught-up',
		'title'      => 'caught up',
		'author'     => 'liz tomforde',
		'cover'      => 'https://m.media-amazon.com/images/I/91dbecj7GdL._UF1000,1000_QL80_.jpg',
		'shelf'      => 'sports romance',
		'spice'      => 2,
		'darkness'   => '',
		'tension'    => 3,
		'damage'     => 3,
		'yearning'   => 3,
		'ku'         => 'true',
		'reread'     => 'true',
		'why'        => 'the ultimate baseball daddy.',
		'mini'       => 'a grumpy single dad baseball player hires the one nanny he cannot fire, and the summer starts feeling like home.',
		'amazon'     => 'https://amzn.to/3P2hCgJ',
		'bookshop'   => 'https://bookshop.org/a/120204/9781649379733',
		'tropes'     => array('single dad romance', 'baseball romance', 'nanny romance'),
	),
	array(
		'handle'     => 'of-ink-and-alchemy',
		'title'      => 'of ink and alchemy',
		'author'     => 'sloane st. james',
		'cover'      => 'https://m.media-amazon.com/images/S/compressed.photo.goodreads.com/books/1770196856i/238363698.jpg',
		'shelf'      => 'contemporary romance',
		'spice'      => 3,
		'darkness'   => 2,
		'tension'    => 2,
		'damage'     => 2,
		'yearning'   => 2,
		'ku'         => 'true',
		'reread'     => 'false',
		'why'        => 'tattoo shop tension with years of quiet obsession underneath it.',
		'mini'       => 'they co-own a tattoo shop, but he has been quietly obsessed with her for years.',
		'amazon'     => 'https://a.co/d/08vHguEQ',
		'bookshop'   => '',
		'tropes'     => array('friends to lovers', 'contemporary romance'),
	),
	array(
		'handle'     => 'trial-of-the-sun-queen',
		'title'      => 'trial of the sun queen',
		'author'     => 'nisha j tuli',
		'cover'      => 'https://images-us.bookshop.org/ingram/9781538767481.jpg?v=647677fca15b969c583c1fc559bd8f74',
		'shelf'      => 'romantasy',
		'spice'      => 2,
		'darkness'   => 3,
		'tension'    => 2,
		'damage'     => 2,
		'yearning'   => 1,
		'ku'         => 'true',
		'reread'     => 'false',
		'why'        => 'deadly competition, sun court danger, and bright fantasy heat.',
		'mini'       => 'forced into a deadly competition for the sun king\'s heart, she has to win to earn her freedom.',
		'amazon'     => 'https://amzn.to/4qFISPo',
		'bookshop'   => 'https://bookshop.org/a/120204/9781538767481',
		'tropes'     => array('enemies to lovers', 'fated mates', 'slow burn'),
	),
	array(
		'handle'     => 'please-dont-go',
		'title'      => 'please don\'t go',
		'author'     => 'e. salvador',
		'cover'      => 'https://m.media-amazon.com/images/I/813Rss4SRhL._UF350,350_QL50_.jpg',
		'shelf'      => 'sports romance',
		'spice'      => 2,
		'darkness'   => 1,
		'tension'    => 2,
		'damage'     => 3,
		'yearning'   => 2,
		'ku'         => 'true',
		'reread'     => 'false',
		'why'        => 'as a black cat girl, i love a golden retriever man always.',
		'mini'       => 'two people drowning in grief collide, try to forget each other, and keep getting pulled back together.',
		'amazon'     => 'https://amzn.to/4wiLkzt',
		'bookshop'   => 'https://bookshop.org/a/120204/9781668250570',
		'newsletter' => 'https://thesmutandsentimentsociety.substack.com/p/please-dont-go',
		'tropes'     => array('he falls first', 'baseball romance', 'slow burn'),
	),
);

$playlist_tracks = array(
	array('title' => 'say yes to heaven', 'artist' => 'lana del rey', 'duration' => '3:29', 'mood' => 'soft glow', 'query' => 'say yes to heaven lana del rey', 'spotify_url' => 'https://open.spotify.com/track/6GGtHZgBycCgGBUhZo81xe'),
	array('title' => 'better off', 'artist' => 'ariana grande', 'duration' => '2:51', 'mood' => 'quiet confidence', 'query' => 'better off ariana grande', 'spotify_url' => 'https://open.spotify.com/track/3NbTQ8ZbHU6MSEVUFAVCJ9'),
	array('title' => '12 to 12', 'artist' => 'sombr', 'duration' => '4:02', 'mood' => 'after dark', 'query' => '12 to 12 sombr', 'spotify_url' => 'https://open.spotify.com/track/1srR2mSBDQ6sc9KECxwggH'),
	array('title' => 'come through - slowed', 'artist' => 'aurelia', 'duration' => '3:11', 'mood' => 'golden haze', 'query' => 'come through slowed aurelia'),
	array('title' => 'golden', 'artist' => 'harry styles', 'duration' => '3:29', 'mood' => 'sunlit main character', 'query' => 'golden harry styles', 'spotify_url' => 'https://open.spotify.com/track/4usmW5vSbOgPXNA06c3DRL'),
	array('title' => 'golden hour', 'artist' => 'jvke', 'duration' => '3:29', 'mood' => 'warm lens flare', 'query' => 'golden hour jvke', 'spotify_url' => 'https://open.spotify.com/track/34VhnIUNsHQFDyxhymwnZl'),
);

$journal_prompts = array(
	'what do i want june to feel like?',
	'what am i finally ready to let go of?',
	'where in my life am i playing small?',
	'what does my ideal summer day look like?',
	'what version of myself am i becoming?',
	'what am i pretending not to know?',
	'what would i do if i was not afraid of being seen?',
	'what does my aura actually feel like right now?',
	'where am i giving energy that is not returned?',
	'what do i keep almost saying out loud?',
	'what does "golden & unbothered" mean to me personally?',
	'what am i most proud of that nobody knows about?',
	'who inspires me and what specifically is it about them?',
	'what part of me is ready to bloom?',
	'what habits make me feel most like myself?',
	'what do i need to stop apologizing for?',
	'what would i do differently if i trusted myself completely?',
	'what does my morning routine say about how i see myself?',
	'what is my relationship with rest?',
	'what does luxury mean to me — not money, but feeling?',
	'what am i tolerating that i should not be?',
	'what book changed how i see myself and why?',
	'what conversation do i keep avoiding?',
	'what would my most radiant self do today?',
	'what do i want people to feel when they are around me?',
	'what does this version of me deserve?',
	'what am i learning to want without guilt?',
	'how have i grown since january?',
	'what was the best moment of june so far?',
	'what do i want to carry into july?',
);

$theme_timestamp = current_time('timestamp');
$theme_year      = (int) date_i18n('Y', $theme_timestamp);
$theme_month     = (int) date_i18n('n', $theme_timestamp);
$theme_day       = (int) date_i18n('j', $theme_timestamp);
$journal_day     = 1;

if (2026 === $theme_year && 6 === $theme_month) {
	$journal_day = min(30, max(1, $theme_day));
} elseif ($theme_year > 2026 || (2026 === $theme_year && $theme_month > 6)) {
	$journal_day = 30;
}

$journal_prompt_today = $journal_prompts[$journal_day - 1] ?? $journal_prompts[0];

$palette = array(
	array('name' => 'void', 'hex' => '#060100'),
	array('name' => 'ember', 'hex' => '#3A1200'),
	array('name' => 'burnt', 'hex' => '#C44A00'),
	array('name' => 'blaze', 'hex' => '#FF6B1A'),
	array('name' => 'amber', 'hex' => '#FF9A40'),
	array('name' => 'cream', 'hex' => '#FFD0A8'),
);

get_header();
?>

<main class="bbb-burn-bright <?php echo esc_attr($has_theme_access ? 'is-paid-society' : 'is-preview-locked'); ?>" id="main-content">
	<section class="bbb-burn-hero" aria-labelledby="bbb-burn-title">
		<div class="bbb-burn-hero__copy">
			<p class="bbb-burn-kicker">june 2026 monthly theme</p>
			<h1 id="bbb-burn-title">burn bright</h1>
			<p class="bbb-burn-hero__tagline">she arrived in orange and never apologized for it</p>
			<p class="bbb-burn-locknote"><?php echo esc_html($theme_access_note); ?></p>
			<div class="bbb-burn-hero__actions" aria-label="monthly theme links">
				<a href="#burn-designs">view the designs</a>
				<?php if ($has_theme_access) : ?>
					<a href="#burn-downloads">download files</a>
				<?php else : ?>
					<a class="bbb-burn-locked" href="<?php echo esc_url($locked_url); ?>"<?php echo $locked_link_attrs; ?> aria-label="<?php echo esc_attr($theme_is_released ? 'Join paid society to unlock files' : 'Burn Bright releases June 1 at midnight'); ?>"><?php echo esc_html($theme_is_released ? 'locked files' : 'releases june 1'); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<div class="bbb-burn-hero__wall" aria-label="burn bright design previews">
			<?php foreach ($designs as $design) : ?>
				<figure>
					<img src="<?php echo esc_url(get_theme_file_uri($asset_base . '/' . $design['image'])); ?>" alt="<?php echo esc_attr($design['name']); ?> kindle insert artwork preview" loading="eager">
				</figure>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="bbb-burn-designs" id="burn-designs" aria-label="burn bright designs">
		<?php foreach ($designs as $index => $design) : ?>
			<article class="bbb-burn-card bbb-burn-card--<?php echo esc_attr($design['class'] . (!$has_theme_access && $index >= 2 ? ' bbb-burn-preview-veil' : '')); ?>">
				<div class="bbb-burn-card__media">
					<img src="<?php echo esc_url(get_theme_file_uri($asset_base . '/' . $design['image'])); ?>" alt="<?php echo esc_attr($design['name']); ?> printable design preview" loading="lazy">
				</div>
				<div class="bbb-burn-card__body">
					<p class="bbb-burn-card__num"><?php echo esc_html($design['num'] . ' / ' . $design['world']); ?></p>
					<h2><?php echo esc_html($design['name']); ?></h2>
					<p class="bbb-burn-card__slogan"><?php echo esc_html($design['slogan']); ?></p>
					<p class="bbb-burn-card__desc"><?php echo esc_html($design['desc']); ?></p>
					<ul class="bbb-burn-vibes" aria-label="<?php echo esc_attr($design['name']); ?> visual direction">
						<?php foreach ($design['vibe'] as $vibe) : ?>
							<li><?php echo esc_html($vibe); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</article>
		<?php endforeach; ?>
	</section>

	<section class="bbb-burn-playlist" aria-labelledby="bbb-burn-playlist-title">
		<div class="bbb-burn-playlist__intro">
			<p class="bbb-burn-kicker">vibes for the month playlist</p>
			<h2 id="bbb-burn-playlist-title">spotify-coded, burn bright approved</h2>
			<p>a little mood enhancer</p>
		</div>
		<div class="bbb-burn-playlist__tracks">
			<?php foreach ($playlist_tracks as $index => $track) : ?>
				<article class="bbb-burn-track<?php echo esc_attr(!$has_theme_access && $index >= 3 ? ' bbb-burn-preview-veil' : ''); ?>">
					<div class="bbb-burn-track__art" aria-hidden="true">
						<span><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
					</div>
					<div class="bbb-burn-track__body">
						<p><?php echo esc_html($track['mood']); ?></p>
						<h3><?php echo esc_html($track['title']); ?></h3>
						<span><?php echo esc_html($track['artist'] . ' / ' . $track['duration']); ?></span>
						<?php if ($has_theme_access) : ?>
							<?php
							$spotify_query = (string) ($track['query'] ?? trim($track['title'] . ' ' . $track['artist']));
							$spotify_url   = trim((string) ($track['spotify_url'] ?? ''));

							if ('' === $spotify_url) {
								$spotify_url = 'https://open.spotify.com/search/' . rawurlencode($spotify_query);
							}
							?>
							<a href="<?php echo esc_url($spotify_url); ?>" target="_blank" rel="noopener"><?php echo isset($track['spotify_url']) ? esc_html('open song') : esc_html('search spotify'); ?></a>
						<?php else : ?>
							<a class="bbb-burn-locked" href="<?php echo esc_url($locked_url); ?>"<?php echo $locked_link_attrs; ?>><?php echo esc_html($theme_is_released ? 'locked playlist' : 'releases june 1'); ?></a>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="bbb-burn-books" aria-labelledby="bbb-burn-books-title" data-sss-lib="burn-bright">
		<div class="bbb-burn-books__head">
			<p class="bbb-burn-kicker">reading mood</p>
			<h2 id="bbb-burn-books-title">books that fit this aesthetic</h2>
			<p>four books for the orange-lit, summer-chaos, soft-but-unbothered version of the month.</p>
		</div>
		<div class="bbb-burn-books__grid">
			<?php foreach ($book_picks as $index => $book) : ?>
				<article
					class="sss-lib__book bbb-burn-book<?php echo esc_attr(!$has_theme_access && $index >= 2 ? ' bbb-burn-preview-veil' : ''); ?>"
					role="button"
					tabindex="0"
					data-handle="<?php echo esc_attr($book['handle']); ?>"
					data-url="<?php echo esc_url(home_url('/library/?book=' . rawurlencode($book['handle']))); ?>"
					data-title="<?php echo esc_attr($book['title']); ?>"
					data-author="<?php echo esc_attr($book['author']); ?>"
					data-cover="<?php echo esc_url($book['cover']); ?>"
					data-spice="<?php echo esc_attr((string) $book['spice']); ?>"
					data-tropes="<?php echo esc_attr(implode(', ', $book['tropes'])); ?>"
					data-tropes-display="<?php echo esc_attr(implode(', ', $book['tropes'])); ?>"
					data-shelf="<?php echo esc_attr($book['shelf']); ?>"
					data-why="<?php echo esc_attr($book['why']); ?>"
					data-mini="<?php echo esc_attr($book['mini']); ?>"
					data-amazon="<?php echo esc_url($book['amazon']); ?>"
					data-bookshop="<?php echo esc_url($book['bookshop']); ?>"
					data-newsletter="<?php echo esc_url($book['newsletter'] ?? ''); ?>"
					data-tension="<?php echo esc_attr((string) $book['tension']); ?>"
					data-damage="<?php echo esc_attr((string) $book['damage']); ?>"
					data-darkness="<?php echo esc_attr((string) $book['darkness']); ?>"
					data-yearning="<?php echo esc_attr((string) $book['yearning']); ?>"
					data-reread="<?php echo esc_attr($book['reread']); ?>"
					data-ku="<?php echo esc_attr($book['ku']); ?>"
				>
					<span class="sss-lib__coverWrap bbb-burn-book__coverWrap">
						<button class="sss-lib__heart" type="button" data-heart aria-label="save to your bookshelf" onclick="return window.bbbBurnToggleBookSave ? window.bbbBurnToggleBookSave(event, this) : true;">
							<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
							<span class="sss-lib__heartLabel" data-heart-label>save</span>
						</button>
						<span class="sss-lib__floatSpice bbb-burn-book__spice" aria-label="<?php echo esc_attr((string) $book['spice'] . ' spice level'); ?>">
							<?php echo esc_html(str_repeat('🌶', (int) $book['spice'])); ?>
						</span>
						<img class="sss-lib__cover bbb-burn-book__cover" src="<?php echo esc_url($book['cover']); ?>" alt="<?php echo esc_attr($book['title']); ?> book cover" loading="lazy">
					</span>
					<div class="bbb-burn-book__body">
						<h3><?php echo esc_html($book['title']); ?></h3>
						<p><?php echo esc_html($book['author']); ?></p>
						<ul aria-label="<?php echo esc_attr($book['title']); ?> tropes">
							<?php foreach (array_slice($book['tropes'], 0, 2) as $trope) : ?>
								<li><?php echo esc_html($trope); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="bbb-burn-calendar" aria-labelledby="bbb-burn-calendar-title">
		<div class="bbb-burn-calendar__media<?php echo esc_attr(!$has_theme_access ? ' bbb-burn-preview-crop' : ''); ?>">
			<img src="<?php echo esc_url($calendar_preview_url); ?>" alt="june 2026 burn bright calendar preview" loading="lazy">
		</div>
		<div class="bbb-burn-calendar__body">
			<p class="bbb-burn-kicker">monthly ritual</p>
			<h2 id="bbb-burn-calendar-title">june 2026 calendar</h2>
			<p>
				a burn bright planning page for the month: launches, reading rituals, soft deadlines,
				and the little sparks worth keeping in view.
			</p>
			<div class="bbb-burn-calendar__actions">
				<?php if ($has_theme_access) : ?>
					<a href="<?php echo esc_url($calendar_pdf_url); ?>" download>download calendar</a>
					<a href="<?php echo esc_url($canva_template_url); ?>" target="_blank" rel="noopener">edit in canva</a>
				<?php else : ?>
					<a class="bbb-burn-locked" href="<?php echo esc_url($locked_url); ?>"<?php echo $locked_link_attrs; ?>><?php echo esc_html($theme_is_released ? 'unlock calendar' : 'calendar releases june 1'); ?></a>
					<a class="bbb-burn-locked" href="<?php echo esc_url($locked_url); ?>"<?php echo $locked_link_attrs; ?>><?php echo esc_html($theme_is_released ? 'unlock canva template' : 'canva releases june 1'); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="bbb-burn-prompts" aria-labelledby="bbb-burn-prompts-title" data-burn-prompts>
		<div class="bbb-burn-prompts__head">
			<div>
				<p class="bbb-burn-kicker">daily journal prompts</p>
				<h2 id="bbb-burn-prompts-title">today's june prompt</h2>
			</div>
		</div>
		<div class="bbb-burn-prompts__viewport<?php echo esc_attr(!$has_theme_access ? ' bbb-burn-preview-crop' : ''); ?>">
			<article class="bbb-burn-prompt" aria-label="<?php echo esc_attr('june ' . (string) $journal_day . ' journal prompt'); ?>">
				<span><?php echo esc_html('june ' . (string) $journal_day); ?></span>
				<p><?php echo esc_html($journal_prompt_today); ?></p>
			</article>
		</div>
	</section>

	<section class="bbb-burn-wallpapers" aria-labelledby="bbb-burn-wallpapers-title">
		<div class="bbb-burn-wallpapers__head">
			<p class="bbb-burn-kicker">phone wallpapers</p>
			<h2 id="bbb-burn-wallpapers-title">burn bright for your lockscreen</h2>
		</div>
		<div class="bbb-burn-wallpapers__grid">
			<?php foreach ($designs as $index => $design) : ?>
				<article class="bbb-burn-wallpaper<?php echo esc_attr(!$has_theme_access && $index >= 2 ? ' bbb-burn-preview-veil' : ''); ?>">
					<figure>
						<img src="<?php echo esc_url(get_theme_file_uri($asset_base . '/' . $design['wallpaper'])); ?>" alt="<?php echo esc_attr($design['name']); ?> iphone wallpaper preview" loading="lazy">
					</figure>
					<div class="bbb-burn-wallpaper__body">
						<h3><?php echo esc_html($design['name']); ?></h3>
						<?php if ($has_theme_access) : ?>
							<a href="<?php echo esc_url(get_theme_file_uri($asset_base . '/' . $design['wallpaper'])); ?>" download>download wallpaper</a>
						<?php else : ?>
							<a class="bbb-burn-locked" href="<?php echo esc_url($locked_url); ?>"<?php echo $locked_link_attrs; ?>><?php echo esc_html($theme_is_released ? 'unlock wallpaper' : 'wallpaper releases june 1'); ?></a>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="bbb-burn-lower" aria-label="burn bright palette and quotes">
		<div class="bbb-burn-quotes">
			<p class="bbb-burn-kicker">book quotes</p>
			<?php foreach ($quotes as $index => $quote) : ?>
				<figure class="bbb-burn-quote<?php echo esc_attr(!$has_theme_access && $index >= 2 ? ' bbb-burn-preview-veil' : ''); ?>">
					<blockquote><?php echo esc_html($quote['text']); ?></blockquote>
				</figure>
			<?php endforeach; ?>
		</div>

		<div class="bbb-burn-palette">
			<p class="bbb-burn-kicker">steal the color palette</p>
			<div class="bbb-burn-swatches" aria-label="burn bright colors">
				<?php foreach ($palette as $index => $color) : ?>
					<button<?php echo $has_theme_access ? '' : ' class="bbb-burn-locked' . ($index >= 3 ? ' bbb-burn-preview-veil' : '') . '" disabled'; ?> type="button" style="--swatch:<?php echo esc_attr($color['hex']); ?>"<?php echo $has_theme_access ? ' data-copy-color="' . esc_attr($color['hex']) . '"' : ' aria-label="' . esc_attr('locked color ' . $color['name']) . '"'; ?>>
						<span><?php echo esc_html($color['name']); ?></span>
						<strong><?php echo esc_html($has_theme_access ? strtolower($color['hex']) : $locked_release_label); ?></strong>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="bbb-burn-aura">
				<p>your aura is pretty</p>
				<strong>golden &amp; unbothered</strong>
				<span>she glows on purpose</span>
				<span>radiant by nature</span>
				<span>the light finds you first</span>
			</div>
		</div>
	</section>

	<section class="bbb-burn-downloads" id="burn-downloads" aria-labelledby="bbb-burn-downloads-title">
		<div class="bbb-burn-downloads__head">
			<p class="bbb-burn-kicker">launch files</p>
			<h2 id="bbb-burn-downloads-title">kindle insert sizes</h2>
		</div>
		<div class="bbb-burn-downloads__grid">
			<?php foreach ($designs as $index => $design) : ?>
				<div class="bbb-burn-download<?php echo esc_attr(!$has_theme_access && $index >= 2 ? ' bbb-burn-preview-veil' : ''); ?>">
					<figure class="bbb-burn-download__mockup">
						<img src="<?php echo esc_url(get_theme_file_uri($asset_base . '/' . $design['mockup'])); ?>" alt="<?php echo esc_attr($design['name']); ?> mockup preview" loading="lazy">
					</figure>
					<h3><?php echo esc_html($design['name']); ?></h3>
					<div class="bbb-burn-download__links">
						<?php foreach ($sizes as $label => $pattern) : ?>
							<?php
							$file = sprintf($pattern, $design['file_key']);
							$url = get_theme_file_uri($asset_base . '/downloads/' . $file);
							?>
							<?php if ($has_theme_access) : ?>
								<a href="<?php echo esc_url($url); ?>" download><?php echo esc_html($label); ?></a>
							<?php else : ?>
								<a class="bbb-burn-locked" href="<?php echo esc_url($locked_url); ?>"<?php echo $locked_link_attrs; ?>><?php echo esc_html($theme_is_released ? $label . ' locked' : $label . ' releases june 1'); ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
</main>

<?php
get_footer();
