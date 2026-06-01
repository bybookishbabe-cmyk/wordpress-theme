<?php
declare(strict_types=1);

if (!function_exists('bbb_library_month_sundays')) {
	function bbb_library_month_sundays(string $year_month): array {
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

if (!function_exists('bbb_library_issue_slot')) {
	function bbb_library_issue_slot(DateTimeImmutable $date, array $sundays, array $issue_types): string {
		$sunday_index = 0;
		foreach ($sundays as $sunday) {
			if ($sunday->format('Y-m-d') <= $date->format('Y-m-d')) {
				++$sunday_index;
			}
		}

		return $issue_types[$sunday_index - 1] ?? '';
	}
}

if (!function_exists('bbb_library_active_month')) {
	function bbb_library_active_month(DateTimeImmutable $today): string {
		$current_month = $today->format('Y-m');
		$sundays       = bbb_library_month_sundays($current_month);
		$first_sunday  = $sundays[0] ?? null;

		if ($first_sunday instanceof DateTimeImmutable && $today->format('Y-m-d') <= $first_sunday->format('Y-m-d')) {
			return $today->modify('first day of previous month')->format('Y-m');
		}

		return $current_month;
	}
}

if (!function_exists('bbb_library_issue_date')) {
	function bbb_library_issue_date(WP_Post $issue, DateTimeZone $timezone): ?DateTimeImmutable {
		$raw = function_exists('get_field') ? get_field('publish_date', $issue->ID) : get_post_meta($issue->ID, 'publish_date', true);
		if (empty($raw)) {
			$raw = get_post_meta($issue->ID, '_issue_publish_date', true);
		}

		$date = trim((string) $raw);
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

$books         = array_values(array_filter($args['books'] ?? array(), static fn($book): bool => $book instanceof WP_Post));
$timezone      = wp_timezone();
$today_date    = new DateTimeImmutable('today', $timezone);
$current_month = bbb_library_active_month($today_date);
$today         = $today_date->format('Y-m-d');
$sundays       = bbb_library_month_sundays($current_month);
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

	foreach ($issues as $issue) {
		if (!$issue instanceof WP_Post) {
			continue;
		}

		$issue_date = bbb_library_issue_date($issue, $timezone);
		if (!$issue_date || $issue_date->format('Y-m') !== $current_month || $issue_date->format('Y-m-d') > $today) {
			continue;
		}

		$slot = bbb_library_issue_slot($issue_date, $sundays, $issue_types);
		if ('' === $slot || !empty($matched_books[$slot])) {
			continue;
		}

		$book = function_exists('sss_get_obsession_book') ? sss_get_obsession_book($issue) : null;
		if ($book instanceof WP_Post) {
			$matched_books[$slot] = $book;
		}
	}
}

foreach ($books as $book) {
	$data = sss_book_data($book);
	if (($data['featured_month'] ?? '') !== $current_month) {
		continue;
	}

	$slot = '';
	$date = (string) (
		'bbb_book' === $book->post_type
			? get_post_meta($book->ID, '_bbb_newsletter_date', true)
			: sss_meta($book->ID, 'featured_in_newsletter_date', '')
	);

	if (preg_match('/^\d{8}$/', $date)) {
		$date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
	}

	$date = substr($date, 0, 10);
	if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		$slot = bbb_library_issue_slot(new DateTimeImmutable($date, $timezone), $sundays, $issue_types);
	}

	if ('' !== $slot && empty($matched_books[$slot])) {
		$matched_books[$slot] = $book;
	}
}
?>
<div id="monthly" class="sss-lib__monthlyIntro">
	<div class="sss-lib__divider"></div>
	<div class="sss-lib__monthlyTitle">books of the month</div>
	<div class="sss-lib__monthlySub">what we're obsessed with in <?php echo esc_html(wp_date('F Y', (new DateTimeImmutable($current_month . '-01', $timezone))->getTimestamp())); ?></div>
</div>

<div id="topshelf" class="sss-lib__topshelf">
	<div class="sss-lib__topshelfHead">
		<div class="sss-lib__topshelfTitle">
			<span class="sss-lib__topshelfMonth">currently reading</span>
		</div>
	</div>
	<div class="sss-lib__topshelfRow">
		<?php foreach ($issue_types as $index => $issue) : ?>
			<div class="sss-lib__topshelfItem">
				<div class="sss-lib__topshelfIssue"><?php echo esc_html($issue); ?></div>
				<?php if (!empty($matched_books[$issue])) : ?>
					<?php get_template_part('template-parts/library/book-card', null, array('post' => $matched_books[$issue])); ?>
				<?php else : ?>
					<?php
					$issue_date = $sundays[$index] ?? null;
					$label      = $issue_date instanceof DateTimeImmutable ? wp_date('F j', $issue_date->getTimestamp()) : '';
					?>
					<div class="sss-lib__topshelfPlaceholder">
						<span><?php echo esc_html($label ? 'revealing ' . $label : 'revealing soon'); ?></span>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
