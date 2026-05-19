<?php
/**
 * Template part: Society Hero / Newsletter CTA.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$society_hero_option = static function (string $field_name, string $default): string {
	$value = function_exists('get_field') ? get_field($field_name, 'option') : '';
	$value = is_string($value) ? trim($value) : '';

	return '' !== $value ? $value : $default;
};

$parse_issue_date = static function (string $raw): int {
	$raw = trim($raw);
	if ('' === $raw) {
		return 0;
	}

	if (preg_match('/^\d{8}$/', $raw)) {
		$date = DateTimeImmutable::createFromFormat('!Ymd', $raw, wp_timezone());
		return $date instanceof DateTimeImmutable ? $date->getTimestamp() : 0;
	}

	$timestamp = strtotime($raw);

	return false === $timestamp ? 0 : $timestamp;
};

$now       = current_time('timestamp');
$ten_hours = 10 * 60 * 60;
$issues    = get_posts(
	array(
		'post_type'      => 'newsletter_issue',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			'relation' => 'OR',
			array(
				'key'     => 'publish_date',
				'compare' => 'EXISTS',
			),
			array(
				'key'     => '_issue_publish_date',
				'compare' => 'EXISTS',
			),
		),
	)
);

$latest_issue = null;
$latest_ts    = 0;

foreach ($issues as $issue) {
	$raw = function_exists('get_field') ? get_field('publish_date', $issue->ID) : get_post_meta($issue->ID, 'publish_date', true);
	if (!$raw) {
		$raw = get_post_meta($issue->ID, '_issue_publish_date', true);
	}

	$ts = $parse_issue_date((string) $raw);
	if (!$ts) {
		continue;
	}

	$live_ts = $ts + $ten_hours;
	if ($live_ts <= $now && $ts > $latest_ts) {
		$latest_ts    = $ts;
		$latest_issue = $issue;
	}
}

$is_new = false;
if ($latest_issue) {
	$live_ts = $latest_ts + $ten_hours;
	$diff    = $now - $live_ts;
	$is_new  = $diff < 604800;
}

$kicker        = $society_hero_option('sh_kicker', 'for the bookaholics who love romance');
$title         = $society_hero_option('sh_title', 'the smut & sentiment society');
$subtitle      = $society_hero_option('sh_subtitle', "weekly letters, obsessive recs, and reader-core you pretend you're not addicted to.");
$society_title = $society_hero_option('sh_society_title', 'inside the society');
$society_text  = $society_hero_option('sh_society_text', 'the archive. reading lists. the fictional men problem. a tasteful amount of chaos.');
$society_url   = $society_hero_option('sh_society_url', '/pages/smut-sentiment-society');
?>

<section class="bbb-newsletter-cta" id="bbb-newsletter-cta-society-hero">

	<div class="bbb-newsletter-cta__rain" aria-hidden="true"></div>

	<div class="bbb-newsletter-cta__wrap page-width">

		<header class="bbb-newsletter-cta__head">
			<p class="bbb-newsletter-cta__kicker"><?php echo esc_html($kicker); ?></p>
			<h2 class="bbb-newsletter-cta__title"><?php echo esc_html($title); ?></h2>
			<p class="bbb-newsletter-cta__sub"><?php echo esc_html($subtitle); ?></p>
		</header>

		<div class="bbb-newsletter-cta__grid">

			<?php if ($latest_issue) : ?>
				<?php
				$issue_url      = function_exists('get_field') ? (string) get_field('issue_url', $latest_issue->ID) : (string) get_post_meta($latest_issue->ID, 'issue_url', true);
				$issue_no       = function_exists('get_field') ? get_field('issue_no', $latest_issue->ID) : get_post_meta($latest_issue->ID, 'issue_no', true);
				$issue_label    = function_exists('get_field') ? (string) get_field('issue_label', $latest_issue->ID) : (string) get_post_meta($latest_issue->ID, 'issue_label', true);
				$issue_subtitle = function_exists('get_field') ? (string) get_field('issue_subtitle', $latest_issue->ID) : (string) get_post_meta($latest_issue->ID, 'issue_subtitle', true);
				$preview_img    = '';
				$img_field      = function_exists('get_field') ? get_field('preview_image', $latest_issue->ID) : null;

				if (is_array($img_field) && !empty($img_field['url'])) {
					$preview_img = (string) $img_field['url'];
				} elseif (is_string($img_field)) {
					$preview_img = $img_field;
				}

				$issue_label    = '' !== trim($issue_label) ? $issue_label : 'latest edition ✦';
				$issue_subtitle = '' !== trim($issue_subtitle) ? $issue_subtitle : 'one book a week. quotes, recs, and reader-core chaos.';
				$issue_title    = get_the_title($latest_issue);
				?>
				<article class="bbb-nc bbb-nc--latest">
					<a class="bbb-nc__latestLink" href="<?php echo esc_url($issue_url); ?>" target="_blank" rel="noopener">
						<div class="bbb-nc__latest">
							<div class="bbb-nc__copy">

								<div class="bbb-nc__meta">
									<span><?php echo esc_html(wp_date('M j, Y', $latest_ts)); ?></span>
									<span><?php echo esc_html('issue ' . $issue_no); ?></span>
								</div>

								<div class="bbb-nc__top">
									<p class="bbb-nc__kicker"><?php echo esc_html($issue_label); ?></p>
									<?php if ($is_new) : ?>
										<span class="bbb-nc__badge" aria-label="New">new</span>
									<?php endif; ?>
								</div>

								<div class="bbb-nc__rule"></div>

								<h3 class="bbb-nc__title"><?php echo esc_html($issue_title); ?></h3>

								<p class="bbb-nc__desc">
									<?php echo esc_html($issue_subtitle); ?>
								</p>

								<?php if ($preview_img) : ?>
									<div class="bbb-nc__img">
										<img src="<?php echo esc_url($preview_img); ?>" alt="<?php echo esc_attr($issue_title); ?>" loading="lazy">
									</div>
								<?php endif; ?>

								<div class="bbb-nc__link bbb-nc__link--primary">
									read the latest newsletter →
								</div>

							</div>
						</div>
					</a>
				</article>
			<?php endif; ?>

			<article class="bbb-nc bbb-nc--society">
				<a class="bbb-nc__societyLink" href="<?php echo esc_url($society_url); ?>">
					<div class="bbb-nc__societyInner">
						<p class="bbb-nc__kicker">the society ♡</p>
						<h3 class="bbb-nc__title"><?php echo esc_html($society_title); ?></h3>
						<p class="bbb-nc__desc"><?php echo esc_html($society_text); ?></p>
						<div class="bbb-nc__link">learn more →</div>
						<p class="bbb-nc__fineprint">🖤 includes the archive, reading lists, and fictional-men problems (organized).</p>
					</div>
				</a>
			</article>

		</div>
	</div>
</section>
