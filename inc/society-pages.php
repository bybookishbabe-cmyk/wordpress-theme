<?php
/**
 * Shared helpers for Society landing and newsletter pages.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_society_reader_has_member_access')) {
	function bbb_society_reader_has_member_access(): bool {
		$identity = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
		return is_array($identity) && '' !== trim((string) ($identity['email'] ?? ''));
	}
}

if (!function_exists('bbb_society_render_locked_preview_page')) {
	function bbb_society_render_locked_preview_page(array $args): void {
		$access = (string) ($args['access'] ?? 'paid');
		$join_url = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
		$kicker = (string) ($args['kicker'] ?? ('member' === $access ? 'member preview' : 'paid society preview'));
		$title = (string) ($args['title'] ?? 'preview locked');
		$intro = (string) ($args['intro'] ?? 'peek at what lives here, then join the society to unlock it.');
		$panel_title = (string) ($args['panel_title'] ?? ('member' === $access ? 'join the society to unlock' : 'upgrade to unlock'));
		$panel_copy = (string) ($args['panel_copy'] ?? ('member' === $access ? 'this page is open to free and paid society members.' : 'this page is reserved for paid society members.'));
		$cta = (string) ($args['cta'] ?? ('member' === $access ? 'join the society' : 'upgrade to paid society'));
		$items = array_values(array_filter((array) ($args['items'] ?? array()), 'is_string'));
		?>
		<section class="bbb-access-preview" aria-labelledby="bbb-access-preview-title">
			<div class="bbb-access-preview__wrap page-width">
				<header class="bbb-access-preview__hero">
					<p class="bbb-access-preview__kicker"><?php echo esc_html($kicker); ?></p>
					<h1 id="bbb-access-preview-title"><?php echo esc_html($title); ?></h1>
					<p><?php echo esc_html($intro); ?></p>
				</header>
				<div class="bbb-access-preview__panel">
					<div>
						<p class="bbb-access-preview__eyebrow"><?php echo esc_html('member' === $access ? 'member access' : 'paid access'); ?></p>
						<h2><?php echo esc_html($panel_title); ?></h2>
						<p><?php echo esc_html($panel_copy); ?></p>
					</div>
					<?php if ($items) : ?>
						<ul class="bbb-access-preview__list">
							<?php foreach ($items as $item) : ?>
								<li><?php echo esc_html($item); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<a class="bbb-access-preview__button" href="<?php echo esc_url($join_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($cta); ?></a>
					<a class="bbb-access-preview__back" href="<?php echo esc_url(bbb_page_url('smut-sentiment-society')); ?>">back to the society</a>
				</div>
			</div>
		</section>
		<style>
			.bbb-access-preview{background:#090909;color:#f6f1ef;min-height:70vh;padding:clamp(42px,7vw,86px) 0 clamp(56px,8vw,104px);text-transform:lowercase}
			.bbb-access-preview__wrap{max-width:112rem}
			.bbb-access-preview__hero{max-width:88rem;margin:0 0 2.6rem}
			.bbb-access-preview__kicker,.bbb-access-preview__eyebrow{margin:0;color:#f3bfd5;font-size:1.1rem;letter-spacing:.16em;text-transform: lowercase}
			.bbb-access-preview h1,.bbb-access-preview h2{margin:0;color:#fff;font-family:Cormorant,"Cormorant Garamond",Georgia,serif;font-weight:400;line-height:.96}
			.bbb-access-preview h1{margin-top:.8rem;font-size:clamp(4.2rem,7vw,8rem)}
			.bbb-access-preview__hero p{max-width:78ch;margin:1.4rem 0 0;color:rgba(246,241,239,.76);font-size:1.7rem;line-height:1.65}
			.bbb-access-preview__panel{display:grid;gap:1.6rem;max-width:82rem;padding:clamp(2rem,4vw,3rem);border:1px solid rgba(239,137,191,.28);border-radius:1rem;background:linear-gradient(180deg,rgba(44,39,41,.96),rgba(24,21,22,.98));box-shadow:0 2.4rem 6rem rgba(0,0,0,.24)}
			.bbb-access-preview h2{margin-top:.4rem;font-size:clamp(3rem,5vw,5.6rem)}
			.bbb-access-preview__panel p{max-width:70ch;margin:1rem 0 0;color:rgba(246,241,239,.76);font-size:1.55rem;line-height:1.62}
			.bbb-access-preview__list{display:grid;gap:.8rem;margin:.4rem 0 0;padding:0;list-style:none}
			.bbb-access-preview__list li{padding:1rem 1.2rem;border:1px solid rgba(255,255,255,.1);border-radius:.8rem;background:rgba(255,255,255,.035);color:rgba(246,241,239,.78);font-size:1.35rem;line-height:1.45}
			.bbb-access-preview__button{display:inline-flex;align-items:center;justify-content:center;width:min(34rem,100%);min-height:4.6rem;margin-top:.6rem;padding:1rem 1.6rem;border:1px solid rgba(239,137,191,.5);border-radius:999px;background:rgba(239,137,191,.16);color:#fff;font-size:1.2rem;font-weight:800;letter-spacing:.08em;text-decoration:none;text-transform: lowercase}
			.bbb-access-preview__back{display:inline-flex;color:#ff8ac7;font-size:1.25rem;text-decoration:underline;text-underline-offset:.4rem}
		</style>
		<?php
	}
}

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
				$image_url = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book->ID) : (string) get_post_meta($book->ID, '_bbb_cover_url', true);
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
