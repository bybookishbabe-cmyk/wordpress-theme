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

$society_issue_meta = static function (?WP_Post $issue, array $keys, string $default = ''): string {
	if (!$issue instanceof WP_Post) {
		return $default;
	}

	foreach ($keys as $key) {
		$value = get_post_meta($issue->ID, $key, true);
		if ('' !== $value && null !== $value) {
			return is_scalar($value) ? trim((string) $value) : $default;
		}
	}

	if (function_exists('get_field')) {
		foreach ($keys as $key) {
			$value = get_field($key, $issue->ID);
			if (is_scalar($value) && '' !== trim((string) $value)) {
				return trim((string) $value);
			}
		}
	}

	return $default;
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

$now               = current_time('timestamp');
$ten_hours         = 10 * 60 * 60;
$obsession_context = function_exists('sss_get_current_obsession_context') ? sss_get_current_obsession_context() : array();
$latest_issue      = $obsession_context['issue'] ?? (function_exists('sss_get_current_newsletter_issue') ? sss_get_current_newsletter_issue() : null);
$latest_book       = $obsession_context['book'] ?? ($latest_issue instanceof WP_Post && function_exists('sss_get_obsession_book') ? sss_get_obsession_book($latest_issue) : null);
$latest_ts         = (int) ($obsession_context['timestamp'] ?? 0);
if (!$latest_ts) {
	$latest_ts = $parse_issue_date($society_issue_meta($latest_issue, array('_issue_publish_date', 'publish_date')));
}

$is_new = false;
if ($latest_ts) {
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
$society_url   = function_exists('bbb_resolve_shopify_url') ? bbb_resolve_shopify_url($society_url) : $society_url;
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

			<?php if ($latest_issue || $latest_book instanceof WP_Post) : ?>
				<?php
				$issue_url      = (string) ($obsession_context['url'] ?? '');
				if ('' === trim($issue_url)) {
					$issue_url = $society_issue_meta($latest_issue, array('_bbb_newsletter_url', 'issue_url', 'url', 'newsletter_url'));
				}
				$issue_no       = $society_issue_meta($latest_issue, array('_issue_no', 'issue_no'));
				$issue_label    = $society_issue_meta($latest_issue, array('_issue_label', 'issue_label', 'label'));
				$issue_subtitle = (string) ($obsession_context['subtitle'] ?? '');
				if ('' === trim($issue_subtitle)) {
					$issue_subtitle = $society_issue_meta($latest_issue, array('_issue_subtitle', 'issue_subtitle', 'subtitle'));
				}
				$preview_img    = '';
				$img_field      = $latest_issue instanceof WP_Post && function_exists('get_field') ? get_field('preview_image', $latest_issue->ID) : null;
				$issue_book     = $latest_book instanceof WP_Post ? $latest_book : ($latest_issue instanceof WP_Post && function_exists('sss_get_obsession_book') ? sss_get_obsession_book($latest_issue) : null);

				if (is_array($img_field) && !empty($img_field['url'])) {
					$preview_img = (string) $img_field['url'];
				} elseif (is_string($img_field)) {
					$preview_img = $img_field;
				}
				if ('' === $issue_url && $issue_book instanceof WP_Post) {
					$issue_url = 'bbb_book' === $issue_book->post_type
						? (string) get_post_meta($issue_book->ID, '_bbb_newsletter_url', true)
						: (string) bbb_get_field('newsletter_url', $issue_book->ID, '');
				}
				$issue_url = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($issue_url) : $issue_url;
				if ('' === $issue_url) {
					$issue_url = 'https://thesmutandsentimentsociety.substack.com';
				}

				$issue_label    = '' !== trim($issue_label) ? $issue_label : 'latest edition ✦';
				$issue_subtitle = '' !== trim($issue_subtitle) ? $issue_subtitle : 'one book a week. quotes, recs, and reader-core chaos.';
				$issue_title    = (string) ($obsession_context['title'] ?? '');
				if ('' === trim($issue_title) && $latest_issue instanceof WP_Post) {
					$issue_title = get_the_title($latest_issue);
				}
				if ('' === trim($issue_title) && $issue_book instanceof WP_Post) {
					$issue_title = get_the_title($issue_book);
				}
				$issue_date     = $latest_ts ? wp_date('M j, Y', $latest_ts) : '';
				?>
				<article class="bbb-nc bbb-nc--latest">
					<a class="bbb-nc__latestLink" href="<?php echo esc_url($issue_url); ?>" target="_blank" rel="noopener">
						<div class="bbb-nc__latest">
							<div class="bbb-nc__copy">

								<div class="bbb-nc__meta">
									<?php if ($issue_date) : ?>
										<span><?php echo esc_html($issue_date); ?></span>
									<?php endif; ?>
									<?php if ($issue_no) : ?>
										<span><?php echo esc_html('issue ' . $issue_no); ?></span>
									<?php endif; ?>
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
