<?php
/**
 * Trending Romance Reads homepage section.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_trending_bool')) {
	function bbb_trending_bool($value): bool {
		if (is_bool($value)) {
			return $value;
		}

		$normalized = strtolower(trim((string) $value));

		return in_array($normalized, array('1', 'true', 'yes', 'on'), true);
	}
}

if (!function_exists('bbb_trending_field')) {
	function bbb_trending_field(string $key, int $post_id, $default = '') {
		if ('bbb_book' === get_post_type($post_id)) {
			$bbb_map = array(
				'featured_in_newsletter_date' => '_bbb_newsletter_date',
				'hide_from_library'           => '_bbb_hide_from_library',
				'is_private'                  => '_bbb_private_shelf',
			);

			if (isset($bbb_map[$key])) {
				$value = get_post_meta($post_id, $bbb_map[$key], true);
				return '' !== $value ? $value : $default;
			}
		}

		return function_exists('bbb_get_field') ? bbb_get_field($key, $post_id, $default) : get_post_meta($post_id, $key, true);
	}
}

if (!function_exists('bbb_trending_book_is_visible')) {
	function bbb_trending_book_is_visible(int $post_id): bool {
		if (function_exists('sss_book_is_visible')) {
			return sss_book_is_visible($post_id);
		}

		$is_visible = bbb_trending_field('is_visible', $post_id, null);
		$is_hidden  = bbb_trending_bool(bbb_trending_field('hide_from_library', $post_id, false));

		if (null !== $is_visible && '' !== $is_visible) {
			return bbb_trending_bool($is_visible) && !$is_hidden;
		}

		return !$is_hidden;
	}
}

if (!function_exists('bbb_trending_get_sunday_dates')) {
	function bbb_trending_get_sunday_dates(string $year_month): array {
		$timezone = wp_timezone();
		$first    = new DateTimeImmutable($year_month . '-01', $timezone);
		$days     = (int) $first->format('t');
		$sundays  = array();

		for ($day = 1; $day <= $days; $day++) {
			$date = $first->setDate((int) $first->format('Y'), (int) $first->format('m'), $day);
			if ('0' === $date->format('w')) {
				$sundays[] = $date;
			}
		}

		return $sundays;
	}
}

if (!function_exists('bbb_trending_sunday_index_for_date')) {
	function bbb_trending_sunday_index_for_date(DateTimeImmutable $featured_date, array $sundays): int {
		$index = 0;

		foreach ($sundays as $sunday) {
			if ($sunday->format('Y-m-d') <= $featured_date->format('Y-m-d')) {
				$index++;
			}
		}

		return $index;
	}
}

if (!function_exists('bbb_trending_issue_date')) {
	function bbb_trending_issue_date(WP_Post $issue, DateTimeZone $timezone): ?DateTimeImmutable {
		$raw = function_exists('get_field') ? get_field('publish_date', $issue->ID) : get_post_meta($issue->ID, 'publish_date', true);
		if (empty($raw)) {
			$raw = get_post_meta($issue->ID, '_issue_publish_date', true);
		}

		$date = trim((string) $raw);
		if ('' === $date) {
			return null;
		}

		if (preg_match('/^\d{8}$/', $date)) {
			$date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
		}

		$date = substr($date, 0, 10);
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			return null;
		}

		return new DateTimeImmutable($date, $timezone);
	}
}

if (!function_exists('bbb_trending_issue_label')) {
	function bbb_trending_issue_label(WP_Post $issue): string {
		foreach (array('_issue_label', 'issue_label', 'label') as $key) {
			$value = get_post_meta($issue->ID, $key, true);
			if ('' !== $value && null !== $value) {
				return strtolower(trim((string) $value));
			}
		}

		if (function_exists('get_field')) {
			foreach (array('issue_label', 'label') as $key) {
				$value = get_field($key, $issue->ID);
				if (is_string($value) && '' !== trim($value)) {
					return strtolower(trim($value));
				}
			}
		}

		return '';
	}
}

$settings = wp_parse_args(
	$args ?? array(),
	array(
		'kicker'    => 'what the society is reading right now',
		'title'     => 'trending romance reads',
		'subtext'   => 'the books currently circulating through the smut & sentiment society.',
		'cta_label' => 'explore the full library →',
		'cta_link'  => '/pages/library',
	)
);
$settings['cta_link'] = function_exists('bbb_resolve_shopify_url') ? bbb_resolve_shopify_url((string) $settings['cta_link']) : (string) $settings['cta_link'];

$current_month = wp_date('Y-m');
$today         = wp_date('Y-m-d');
$timezone      = wp_timezone();
$sundays       = bbb_trending_get_sunday_dates($current_month);
$issue_types   = count($sundays) >= 5
	? array('smutty', 'sentimental', 'trope report', 'extra extra', "chapter's end")
	: array('smutty', 'sentimental', 'trope report', "chapter's end");
$matched_books = array();
$issue_post_types = array_values(
	array_filter(
		array('newsletter_issue', 'bbb_newsletter_issue'),
		static fn(string $post_type): bool => post_type_exists($post_type)
	)
);

if ($issue_post_types) {
	$issues = get_posts(
		array(
			'post_type'      => $issue_post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
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

	foreach ($issues as $newsletter_issue) {
		if (!$newsletter_issue instanceof WP_Post) {
			continue;
		}

		$issue_date = bbb_trending_issue_date($newsletter_issue, $timezone);
		if (!$issue_date || $issue_date->format('Y-m') !== $current_month || $issue_date->format('Y-m-d') > $today) {
			continue;
		}

		$issue_label = str_replace(array('_', '-'), ' ', bbb_trending_issue_label($newsletter_issue));
		$issue       = in_array($issue_label, $issue_types, true) ? $issue_label : '';
		if ('' === $issue) {
			foreach ($issue_types as $issue_type) {
				if ('' !== $issue_label && str_contains($issue_label, $issue_type)) {
					$issue = $issue_type;
					break;
				}
			}
		}

		if ('' === $issue) {
			$sunday_index = bbb_trending_sunday_index_for_date($issue_date, $sundays);
			if ($sunday_index < 1 || $sunday_index > count($issue_types)) {
				continue;
			}
			$issue = $issue_types[$sunday_index - 1];
		}

		$book = function_exists('sss_get_obsession_book') ? sss_get_obsession_book($newsletter_issue) : null;
		if (!$book instanceof WP_Post || !bbb_trending_book_is_visible((int) $book->ID)) {
			continue;
		}

		if (empty($matched_books[$issue])) {
			$matched_books[$issue] = $book;
		}
	}
}
?>
<section class="bbb-trending">
	<div class="bbb-trending__inner">
		<div class="bbb-trending__head">
			<p class="bbb-trending__kicker"><?php echo esc_html($settings['kicker']); ?></p>
			<h2 class="bbb-trending__title"><?php echo esc_html($settings['title']); ?></h2>
			<p class="bbb-trending__sub"><?php echo esc_html($settings['subtext']); ?></p>
		</div>

		<div class="bbb-trending__row" data-sss-lib="public">
			<?php foreach ($issue_types as $index => $issue) : ?>
				<?php if (!empty($matched_books[$issue])) : ?>
					<div class="bbb-trending__book">
						<?php get_template_part('template-parts/sss/book-card', null, array('book' => $matched_books[$issue])); ?>
					</div>
				<?php else : ?>
					<?php
					$issue_date = $sundays[$index] ?? null;
					$label      = $issue_date instanceof DateTimeImmutable ? wp_date('F j', $issue_date->getTimestamp()) : '';
					?>
					<div class="bbb-trending__placeholder">
						<div class="bbb-trending__placeholderIssue"><?php echo esc_html($issue); ?></div>
						<div class="bbb-trending__placeholderText">
							<?php echo esc_html($label ? 'revealing ' . $label : 'revealing soon'); ?>
						</div>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>

		<div class="bbb-trending__cta">
			<a href="https://thesmutandsentimentsociety.substack.com/subscribe" target="_blank" rel="noopener" class="bbb-trending__ctaSecondary">
				don't miss a sunday →
			</a>
			<a href="<?php echo esc_url($settings['cta_link']); ?>"><?php echo esc_html($settings['cta_label']); ?></a>
		</div>
	</div>
</section>
<?php
bbb_render_component('sss-library-modal');
