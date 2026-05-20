<?php
/**
 * Shared helpers for Society landing and newsletter pages.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_society_newsletter_issue_url(WP_Post $issue): string {
	$url = (string) get_post_meta($issue->ID, '_bbb_newsletter_url', true);
	if ('' === $url) {
		$url = (string) get_post_meta($issue->ID, 'issue_url', true);
	}
	if ('' === $url) {
		$url = (string) get_post_meta($issue->ID, '_issue_url', true);
	}

	$url = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($url) : trim($url);

	return '' !== $url ? $url : 'https://thesmutandsentimentsociety.substack.com/';
}

function bbb_society_newsletter_issue_date(WP_Post $issue): string {
	$raw = (string) get_post_meta($issue->ID, '_issue_publish_date', true);
	if ('' === $raw) {
		return '';
	}

	$timestamp = strtotime($raw);
	if (!$timestamp && preg_match('/^\d{8}$/', $raw)) {
		$timestamp = strtotime(substr($raw, 0, 4) . '-' . substr($raw, 4, 2) . '-' . substr($raw, 6, 2));
	}

	return $timestamp ? strtolower(date_i18n('M j, Y', $timestamp)) : strtolower($raw);
}

function bbb_society_newsletter_issue_summary(WP_Post $issue): string {
	$summary = (string) get_post_meta($issue->ID, '_issue_excerpt', true);
	if ('' === $summary) {
		$summary = (string) get_post_meta($issue->ID, '_issue_subtitle', true);
	}

	return '' !== $summary ? wp_strip_all_tags($summary) : 'a society dispatch from the smut and sentiment shelf.';
}

function bbb_society_newsletter_issue_image(WP_Post $issue): array {
	$image_url = '';
	$image_alt = '';

	if (function_exists('get_field')) {
		$image_field = get_field('preview_image', $issue->ID);
		if (is_array($image_field) && !empty($image_field['url'])) {
			$image_url = (string) $image_field['url'];
			$image_alt = isset($image_field['alt']) ? (string) $image_field['alt'] : '';
		} elseif (is_string($image_field) && '' !== trim($image_field)) {
			$image_url = trim($image_field);
		}
	}

	if ('' === $image_url) {
		$image_url = (string) get_post_meta($issue->ID, '_issue_preview_url', true);
		$image_alt = (string) get_post_meta($issue->ID, '_issue_preview_alt', true);
	}

	if ('' === $image_url && function_exists('sss_get_obsession_book')) {
		$book = sss_get_obsession_book($issue);
		if ($book instanceof WP_Post) {
			if (function_exists('sss_article_cover_url')) {
				$image_url = (string) sss_article_cover_url($book);
			}
			if ('' === $image_url) {
				$image_url = (string) get_post_meta($book->ID, '_bbb_cover_url', true);
			}
			if ('' === $image_url) {
				$image_url = (string) get_post_meta($book->ID, 'cover', true);
			}
			$image_alt = get_the_title($book);
		}
	}

	return array(
		'url' => $image_url,
		'alt' => '' !== $image_alt ? $image_alt : get_the_title($issue),
	);
}

function bbb_society_get_newsletter_issues(int $limit = 3): array {
	if (!post_type_exists('newsletter_issue')) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => 'newsletter_issue',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => '_issue_publish_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
		)
	);
}

function bbb_society_render_newsletter_issue_grid(array $issues): void {
	if (!$issues) {
		?>
		<div class="bbb-society-empty">
			<p>newsletter issues will appear here once they are imported.</p>
			<a href="https://thesmutandsentimentsociety.substack.com/" target="_blank" rel="noopener">open substack</a>
		</div>
		<?php
		return;
	}
	?>
	<div class="bbb-society-link-grid">
		<?php foreach ($issues as $issue) : ?>
			<?php
			if (!$issue instanceof WP_Post) {
				continue;
			}

			$label = (string) get_post_meta($issue->ID, '_issue_label', true);
			if ('' === $label) {
				$label = bbb_society_newsletter_issue_date($issue);
			}
			$image = bbb_society_newsletter_issue_image($issue);
			?>
			<a class="bbb-society-link-card" href="<?php echo esc_url(bbb_society_newsletter_issue_url($issue)); ?>" target="_blank" rel="noopener">
				<?php if ('' !== $image['url']) : ?>
					<span class="bbb-society-link-card__media">
						<img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" loading="lazy">
					</span>
				<?php endif; ?>
				<span class="bbb-society-link-card__top">
					<span class="bbb-society-link-card__title"><?php echo esc_html(strtolower(get_the_title($issue))); ?></span>
					<span class="bbb-society-link-card__badge"><?php echo esc_html(strtolower($label)); ?></span>
				</span>
				<span class="bbb-society-link-card__copy"><?php echo esc_html(strtolower(bbb_society_newsletter_issue_summary($issue))); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}
