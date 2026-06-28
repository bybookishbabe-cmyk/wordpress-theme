<?php
/**
 * Template Name: Reader Types
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_enqueue_css('bbb-reader-types', 'assets/css/reader-types.css', array('bbb-bookshelf-signup'));
bbb_enqueue_js('bbb-reader-types', 'assets/js/reader-types.js', array(), true);

$reader_types     = function_exists('bbb_reader_type_registry') ? bbb_reader_type_registry() : array();
$made_for_you_url = function_exists('bbb_page_url') ? bbb_page_url('made-for-you') : home_url('/made-for-you/');
$society_join_url = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$society_join_url = '' !== trim((string) $society_join_url) ? (string) $society_join_url : 'https://thesmutandsentimentsociety.substack.com/subscribe';
$boyfriends       = array();

if (post_type_exists('bbb_boyfriend') && function_exists('bbb_fictional_boyfriend_profile_ready')) {
	$boyfriend_posts = get_posts(
		array(
			'post_type'              => 'bbb_boyfriend',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	foreach ($boyfriend_posts as $boyfriend_post) {
		if (!$boyfriend_post instanceof WP_Post) {
			continue;
		}

		$boyfriend_id = (int) $boyfriend_post->ID;
		if (!bbb_fictional_boyfriend_profile_ready($boyfriend_id)) {
			continue;
		}

		if (function_exists('bbb_content_is_publicly_discoverable') && !bbb_content_is_publicly_discoverable($boyfriend_id)) {
			continue;
		}

		$tropes = function_exists('bbb_fictional_boyfriend_tropes') ? bbb_fictional_boyfriend_tropes($boyfriend_id) : array();
		$traits = function_exists('bbb_fictional_boyfriend_traits') ? bbb_fictional_boyfriend_traits($boyfriend_id) : array();
		$boyfriends[] = array(
			'id'          => $boyfriend_id,
			'title'       => get_the_title($boyfriend_post),
			'url'         => get_permalink($boyfriend_post),
			'image'       => get_the_post_thumbnail($boyfriend_post, 'thumbnail'),
			'source'      => (string) get_post_meta($boyfriend_id, '_bbb_fb_source', true),
			'descriptor'  => function_exists('bbb_fictional_boyfriend_descriptor') ? bbb_fictional_boyfriend_descriptor($boyfriend_id) : '',
			'tropes'      => $tropes,
			'trope_slugs' => array_map('sanitize_title', $tropes),
			'trait_slugs' => array_map('sanitize_title', $traits),
		);
	}
}

$reader_type_boyfriends = static function (array $reader_type) use ($boyfriends): array {
	$affinity_terms = array(
		'chaos_reader'          => array('morally-gray', 'obsessive', 'possessive', 'dangerous', 'ruthless', 'intense'),
		'dark_romance_girlie'   => array('morally-gray', 'obsessive', 'possessive', 'dangerous', 'ruthless', 'commanding'),
		'fantasy_girlie'        => array('morally-gray', 'protective', 'devoted', 'intense', 'commanding', 'soft-for-her'),
		'jersey_chaser'         => array('athletic', 'competitive', 'cocky', 'confident', 'protective', 'soft-for-her'),
		'slow_burn_girlie'      => array('patient', 'reserved', 'emotionally-guarded', 'protective', 'devoted', 'soft-for-her'),
		'tension_addict'        => array('banter-heavy', 'sarcastic', 'competitive', 'grumpy', 'cocky', 'teasing'),
		'fake_dating_fanatic'   => array('charming', 'playful', 'protective', 'confident', 'soft-for-her', 'teasing'),
		'sweet_romance_devotee' => array('gentle', 'warm', 'golden-retriever', 'emotionally-intelligent', 'soft-for-her', 'steady'),
		'romance_reader'        => array('devoted', 'protective', 'soft-for-her', 'charming', 'emotionally-intelligent', 'loyal'),
	);
	$triggers = array_values(array_unique(array_filter(array_map('sanitize_title', (array) ($reader_type['triggers'] ?? array())))));
	if (!$triggers || !$boyfriends) {
		return array();
	}

	$matches = array();
	foreach ($boyfriends as $boyfriend) {
		$matched_tropes = array_values(array_intersect($triggers, (array) ($boyfriend['trope_slugs'] ?? array())));
		$matched_traits = array();
		if (!$matched_tropes) {
			$type_key = (string) ($reader_type['key'] ?? '');
			$matched_traits = array_values(array_intersect((array) ($affinity_terms[$type_key] ?? array()), (array) ($boyfriend['trait_slugs'] ?? array())));
			if (!$matched_traits) {
				continue;
			}
		}

		$boyfriend['score'] = (count($matched_tropes) * 10) + count($matched_traits);
		$boyfriend['matched_tropes'] = $matched_tropes;
		$boyfriend['matched_traits'] = $matched_traits;
		$matches[] = $boyfriend;
	}

	usort(
		$matches,
		static function (array $a, array $b): int {
			$score_compare = ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
			return 0 !== $score_compare ? $score_compare : strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
		}
	);

	return array_slice($matches, 0, 3);
};

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none bbb-reader-types" role="main" tabindex="-1">
	<section class="bbb-reader-types__hero" aria-labelledby="reader-types-title">
		<div class="bbb-reader-types__heroCopy">
			<p class="bbb-reader-types__kicker">made for you</p>
			<h1 id="reader-types-title">find out your reader type</h1>
			<p>answer Made For You to get the reader badge that fits your tropes, moods, spice comfort zone, and fictional men.</p>
			<div class="bbb-reader-types__actions" aria-label="reader type actions">
				<a class="bbb-reader-types__button bbb-reader-types__button--primary" href="<?php echo esc_url($made_for_you_url); ?>">answer made for you</a>
				<a class="bbb-reader-types__button" href="<?php echo esc_url($society_join_url); ?>" target="_blank" rel="noopener">join the society</a>
			</div>
		</div>
		<div class="bbb-reader-types__heroPanel" aria-label="reader type membership note">
			<span>free + paid members</span>
			<strong>free profiles + paid perks</strong>
			<p>start free for your reader profile. upgrade for the deeper weekly rec layer, archive perks, and member-only extras.</p>
		</div>
	</section>

	<section class="bbb-reader-types__how" aria-labelledby="reader-type-how">
		<div class="bbb-reader-types__sectionHead">
			<p class="bbb-reader-types__kicker">how it works</p>
			<h2 id="reader-type-how">get your badge in three steps</h2>
		</div>
		<div class="bbb-reader-types__steps">
			<div class="bbb-reader-types__step">
				<span>01</span>
				<h3>become a Society member</h3>
				<p>start free to unlock your reader account, bookshelf, and Made For You tools. upgrade when you want the paid Society extras.</p>
				<a href="<?php echo esc_url($society_join_url); ?>" target="_blank" rel="noopener">see free vs paid</a>
			</div>
			<div class="bbb-reader-types__step">
				<span>02</span>
				<h3>answer Made For You</h3>
				<p>tell the dashboard what you reach for: tropes, intensity, romance moods, favorite chaos, and the fictional men you cannot stop thinking about.</p>
				<a href="<?php echo esc_url($made_for_you_url); ?>">open Made For You</a>
			</div>
			<div class="bbb-reader-types__step">
				<span>03</span>
				<h3>get assigned a reader type</h3>
				<p>your answers turn into a reader badge, supporting emoji set, and better recommendations across quizzes, cards, and member views.</p>
			</div>
		</div>
	</section>

	<section class="bbb-reader-types__section" aria-labelledby="reader-type-standards">
		<div class="bbb-reader-types__sectionHead">
			<p class="bbb-reader-types__kicker">possible results</p>
			<h2 id="reader-type-standards">reader type badges</h2>
		</div>

		<div class="bbb-reader-types__grid">
			<?php foreach ($reader_types as $reader_type) : ?>
				<?php
				$emoji_key = (string) ($reader_type['emoji'] ?? '');
				$emoji_url = function_exists('bbb_custom_emoji_url') ? bbb_custom_emoji_url($emoji_key) : '';
				$theme     = is_array($reader_type['theme'] ?? null) ? $reader_type['theme'] : array();
				$swatches  = array(
					'surface' => (string) ($theme['surface'] ?? '#131013'),
					'border'  => (string) ($theme['border'] ?? '#2E282C'),
					'deep'    => (string) ($theme['deep'] ?? '#5E5258'),
					'accent'  => (string) ($theme['accent'] ?? '#D4C2CE'),
					'accent2' => (string) ($theme['accent2'] ?? '#EFE4EA'),
				);
				$style_vars = array(
					'--reader-surface:' . $swatches['surface'],
					'--reader-border:' . $swatches['border'],
					'--reader-deep:' . $swatches['deep'],
					'--reader-accent:' . $swatches['accent'],
					'--reader-accent-2:' . $swatches['accent2'],
					'--reader-on-accent:' . (string) ($theme['onAccent'] ?? '#2E242A'),
					'--reader-heading:' . (string) ($theme['textHeading'] ?? '#FAF6F8'),
					'--reader-body:' . (string) ($theme['textBody'] ?? '#EAE2E6'),
					'--reader-muted:' . (string) ($theme['textMuted'] ?? '#A89AA1'),
					'--reader-glow:' . (string) ($theme['glow'] ?? 'rgba(212,194,206,.08)'),
				);
				$supporting = (array) ($reader_type['supporting'] ?? ($reader_type['triggers'] ?? array()));
				?>
				<article class="bbb-reader-type-card" style="<?php echo esc_attr(implode(';', $style_vars)); ?>" data-reader-type-card tabindex="0" aria-expanded="false">
					<button class="bbb-reader-type-card__close" type="button" aria-label="<?php echo esc_attr('close ' . (string) ($reader_type['label'] ?? 'reader type') . ' details'); ?>" data-reader-type-close>&times;</button>
					<div class="bbb-reader-type-card__badge">
						<?php if ($emoji_url) : ?>
							<img src="<?php echo esc_url($emoji_url); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
						<?php endif; ?>
						<span><?php echo esc_html((string) ($reader_type['label'] ?? 'reader type')); ?></span>
					</div>
					<p><?php echo esc_html((string) ($reader_type['signal'] ?? '')); ?></p>
					<dl>
						<div>
							<dt>key tropes</dt>
							<dd><?php echo esc_html(implode(', ', array_map(static fn($key): string => str_replace('-', ' ', (string) $key), (array) ($reader_type['triggers'] ?? array())))); ?></dd>
						</div>
					</dl>
					<div class="bbb-reader-type-card__emojiRow" aria-label="<?php echo esc_attr((string) ($reader_type['label'] ?? 'reader type') . ' supporting emoji'); ?>">
						<?php foreach ($supporting as $supporting_key) : ?>
							<?php $supporting_url = function_exists('bbb_custom_emoji_url') ? bbb_custom_emoji_url((string) $supporting_key) : ''; ?>
							<?php if ($supporting_url) : ?>
								<span title="<?php echo esc_attr(str_replace('-', ' ', (string) $supporting_key)); ?>">
									<img src="<?php echo esc_url($supporting_url); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
								</span>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
					<?php $matched_boyfriends = $reader_type_boyfriends($reader_type); ?>
					<div class="bbb-reader-type-card__boyfriends">
						<strong>fictional boyfriends</strong>
						<?php if ($matched_boyfriends) : ?>
							<div class="bbb-reader-type-card__boyfriendList">
								<?php foreach ($matched_boyfriends as $boyfriend) : ?>
									<a class="bbb-reader-type-card__boyfriend" href="<?php echo esc_url((string) ($boyfriend['url'] ?? '')); ?>">
										<span class="bbb-reader-type-card__boyfriendImage">
											<?php echo wp_kses_post((string) ($boyfriend['image'] ?? '')); ?>
										</span>
										<span class="bbb-reader-type-card__boyfriendCopy">
											<span><?php echo esc_html((string) ($boyfriend['title'] ?? 'fictional boyfriend')); ?></span>
											<small><?php echo esc_html((string) ($boyfriend['source'] ?: ($boyfriend['descriptor'] ?? 'matched by trope'))); ?></small>
										</span>
									</a>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<p class="bbb-reader-type-card__empty">no profile matches yet</p>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

</main>

<?php
get_footer();
