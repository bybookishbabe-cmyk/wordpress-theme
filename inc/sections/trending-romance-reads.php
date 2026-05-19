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
		return function_exists('bbb_get_field') ? bbb_get_field($key, $post_id, $default) : get_post_meta($post_id, $key, true);
	}
}

if (!function_exists('bbb_trending_book_is_visible')) {
	function bbb_trending_book_is_visible(int $post_id): bool {
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

$current_month = wp_date('Y-m');
$today         = wp_date('Y-m-d');
$timezone      = wp_timezone();
$sundays       = bbb_trending_get_sunday_dates($current_month);
$issue_types   = count($sundays) >= 5
	? array('smutty', 'sentimental', 'trope report', 'extra extra', "chapter's end")
	: array('smutty', 'sentimental', 'trope report', "chapter's end");
$matched_books = array();
$post_type     = post_type_exists('sss_library') ? 'sss_library' : 'sss_book';

$query = new WP_Query(
	array(
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => 250,
		'meta_query'     => array(
			array(
				'key'     => 'featured_in_newsletter_date',
				'compare' => 'EXISTS',
			),
		),
	)
);

if ($query->have_posts()) {
	while ($query->have_posts()) {
		$query->the_post();

		$book_id       = get_the_ID();
		$featured_date = (string) bbb_trending_field('featured_in_newsletter_date', $book_id, '');

		if (
			'' === $featured_date
			|| !bbb_trending_book_is_visible($book_id)
			|| bbb_trending_bool(bbb_trending_field('is_private', $book_id, false))
		) {
			continue;
		}

		$featured_day = substr($featured_date, 0, 10);
		if (preg_match('/^\d{8}$/', $featured_day)) {
			$featured_day = substr($featured_day, 0, 4) . '-' . substr($featured_day, 4, 2) . '-' . substr($featured_day, 6, 2);
		}

		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $featured_day)) {
			continue;
		}

		$featured = new DateTimeImmutable($featured_day, $timezone);
		if ($featured->format('Y-m-d') > $today || $featured->format('Y-m') !== $current_month) {
			continue;
		}

		$sunday_index = bbb_trending_sunday_index_for_date($featured, $sundays);
		if ($sunday_index < 1 || $sunday_index > count($issue_types)) {
			continue;
		}

		$issue = $issue_types[$sunday_index - 1];
		if (empty($matched_books[$issue])) {
			$matched_books[$issue] = get_post();
		}
	}

	wp_reset_postdata();
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
