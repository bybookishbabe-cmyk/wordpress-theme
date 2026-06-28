<?php
/**
 * Template part: Weekly Obsession
 * Included from front-page.php or page-home.php
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$obsession_context = function_exists('sss_get_current_obsession_context') ? sss_get_current_obsession_context() : array();
$current_issue     = $obsession_context['issue'] ?? (function_exists('sss_get_current_newsletter_issue') ? sss_get_current_newsletter_issue() : null);
$featured_book     = $obsession_context['book'] ?? ($current_issue instanceof WP_Post ? sss_get_obsession_book($current_issue) : null);
if (!$featured_book instanceof WP_Post && function_exists('sss_get_latest_featured_book')) {
	$featured_book = sss_get_latest_featured_book();
}

if (!$featured_book instanceof WP_Post) {
	return;
}

$kicker           = sss_get_homepage_field('wo_kicker', 'weekly obsession');
$section_kicker   = sss_get_homepage_field('wo_section_kicker', 'from the society');
$section_title    = sss_get_homepage_field('wo_section_title', 'weekly obsession');
$section_subtitle = sss_get_homepage_field('wo_section_subtitle', 'the book currently taking over the smut & sentiment society.');
$obsession_url    = sss_get_homepage_field('wo_obsession_url', '/pages/weekly-obsession');
if (empty(trim($obsession_url))) {
	$obsession_url = '/pages/weekly-obsession';
}
$obsession_url = function_exists('bbb_resolve_shopify_url') ? bbb_resolve_shopify_url($obsession_url) : $obsession_url;

$book_id         = $featured_book->ID;
$book_handle     = sanitize_title((string) get_post_field('post_name', $book_id));
$thumb_id        = get_post_thumbnail_id($book_id);
$has_native_dims = (bool) $thumb_id;

$spice_level = 'bbb_book' === $featured_book->post_type
	? (int) get_post_meta($book_id, '_bbb_spice', true)
	: (int) get_post_meta($book_id, '_book_spice_level', true);

$shelf_name  = '';
$shelf_terms = get_the_terms($book_id, 'bbb_book' === $featured_book->post_type ? 'bbb_shelf' : 'sss_shelf');
if ($shelf_terms && !is_wp_error($shelf_terms)) {
	$candidate = trim($shelf_terms[0]->name);
	if ('private shelf' !== $candidate) {
		$shelf_name = $candidate;
	}
}

$trope_terms = get_the_terms($book_id, 'bbb_book' === $featured_book->post_type ? 'bbb_trope' : 'sss_trope');
$tropes      = ($trope_terms && !is_wp_error($trope_terms))
	? array_slice($trope_terms, 0, 3)
	: array();

$issue_title = (string) ($obsession_context['title'] ?? '');
if ('' === trim($issue_title)) {
	$issue_title = $current_issue && !empty(get_post_meta($current_issue->ID, '_issue_title_override', true))
		? get_post_meta($current_issue->ID, '_issue_title_override', true)
		: ($current_issue ? $current_issue->post_title : '');
}
if (empty($issue_title)) {
	$issue_title = $featured_book->post_title;
}
$issue_title = function_exists('bbb_brand_standard_text') ? (string) bbb_brand_standard_text($issue_title) : $issue_title;
$issue_subtitle = (string) ($obsession_context['subtitle'] ?? '');
if ('' === trim($issue_subtitle)) {
	$issue_subtitle = $current_issue ? get_post_meta($current_issue->ID, '_issue_subtitle', true) : get_post_meta($book_id, '_bbb_mini_note', true);
}
$issue_subtitle = function_exists('bbb_brand_standard_text') ? (string) bbb_brand_standard_text($issue_subtitle) : $issue_subtitle;
$issue_excerpt = trim((string) ($obsession_context['excerpt'] ?? ''));
if ('' === $issue_excerpt && $current_issue instanceof WP_Post) {
	$issue_excerpt = (string) get_post_meta($current_issue->ID, '_issue_excerpt', true);
}
if ('' === $issue_excerpt) {
	$issue_excerpt = $issue_subtitle;
}
$issue_excerpt = function_exists('bbb_brand_standard_text') ? (string) bbb_brand_standard_text($issue_excerpt) : $issue_excerpt;
$issue_quote = trim((string) ($obsession_context['pull_quote'] ?? ''));
$issue_quote_display = '';
if ('' !== $issue_quote) {
	$issue_quote_inner = html_entity_decode(wp_strip_all_tags($issue_quote), ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8');
	$issue_quote_inner = preg_replace('/^[\s"\'“”‘’]+|[\s"\'“”‘’]+$/u', '', $issue_quote_inner) ?: '';
	$issue_quote_display = '"' . wp_trim_words($issue_quote_inner, 24, '') . '"';
}
$issue_url   = trim((string) ($obsession_context['url'] ?? ''));
$issue_url   = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($issue_url) : $issue_url;
$secret_url  = trim((string) ($obsession_context['secret_url'] ?? ''));
$secret_url  = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($secret_url) : $secret_url;
$weekly_url  = function_exists('bbb_page_url') ? bbb_page_url('weekly-obsession') : $obsession_url;
$book_url    = get_permalink($featured_book);
$subscribe_url = function_exists('bbb_substack_subscribe_url') ? bbb_substack_subscribe_url() : 'https://thesmutandsentimentsociety.substack.com/subscribe';
$cover_url      = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book_id) : ('bbb_book' === $featured_book->post_type ? (string) get_post_meta($book_id, '_bbb_cover_url', true) : '');
$darkness_level = (int) get_post_meta($book_id, '_bbb_darkness', true);
$damage_level   = (int) get_post_meta($book_id, '_bbb_damage', true);
if (function_exists('sss_article_field')) {
	$darkness_level = (int) sss_article_field('darkness_score', $book_id, $darkness_level);
	$damage_level   = (int) sss_article_field('emotional_damage_score', $book_id, $damage_level);
}

$popular_boyfriend = function_exists('bbb_fictional_boyfriend_popular_post') ? bbb_fictional_boyfriend_popular_post() : null;
?>

<?php if ($popular_boyfriend instanceof WP_Post) : ?>
	<section class="bbb-home-popular-man" aria-label="most popular fictional man">
		<div class="bbb-home-popular-man__inner">
			<a class="bbb-home-popular-man__profile" href="<?php echo esc_url(get_permalink($popular_boyfriend)); ?>">
				<?php echo get_the_post_thumbnail($popular_boyfriend, 'medium_large', array('class' => 'bbb-home-popular-man__image')); ?>
				<span>
					<?php echo wp_kses_post(function_exists('bbb_fictional_boyfriend_popular_badge') ? bbb_fictional_boyfriend_popular_badge('bbb-home-popular-man__badge') : '<span class="bbb-fb-popular-badge bbb-home-popular-man__badge">most popular now</span>'); ?>
					<strong><?php echo esc_html(get_the_title($popular_boyfriend)); ?></strong>
				</span>
			</a>
			<div class="bbb-home-popular-man__copy">
				<p>the most popular fictional man rn, who is yours?</p>
				<a href="<?php echo esc_url(function_exists('bbb_page_url') ? bbb_page_url('fictional-boyfriend-quiz') : home_url('/fictional-boyfriend-quiz/')); ?>">take the quiz <span aria-hidden="true">→</span></a>
			</div>
		</div>
	</section>
<?php endif; ?>

<section class="bbb-home-obsession">
	<div class="section-divider"></div>
	<div class="bbb-home-obsession__inner">

		<div class="bbb-home-obsession__sparkles" aria-hidden="true">
			<span>✦</span><span>✧</span><span>⋆</span><span>✦</span><span>✧</span><span>⋆</span><span>✦</span><span>✧</span><span>⋆</span><span>✦</span><span>✧</span><span>⋆</span>
		</div>

		<div class="bbb-home-obsession__head">
			<p class="bbb-home-obsession__sectionKicker"><?php echo esc_html($section_kicker); ?></p>
			<h2 class="bbb-home-obsession__sectionTitle"><?php echo esc_html($section_title); ?></h2>
			<p class="bbb-home-obsession__sectionSub"><?php echo esc_html($section_subtitle); ?></p>
		</div>

		<div class="bbb-home-obsession__row">

			<!-- Cover column -->
			<div class="bbb-home-obsession__feature">
				<a class="bbb-home-obsession__coverLink" href="<?php echo esc_url($obsession_url); ?>">
					<div
						class="bbb-home-obsession__coverWrap"
						data-book-rating-cover
						data-handle="<?php echo esc_attr($book_handle); ?>"
						data-title="<?php echo esc_attr($featured_book->post_title); ?>"
					>
						<?php if ($thumb_id) : ?>
							<?php if ($has_native_dims) : ?>
								<?php
								echo wp_get_attachment_image(
									$thumb_id,
									'full',
									false,
									array(
										'class'    => 'bbb-home-obsession__cover',
										'sizes'    => '(max-width: 749px) 78vw, 340px',
										'loading'  => 'lazy',
										'decoding' => 'async',
										'alt'      => esc_attr($featured_book->post_title),
									)
								);
								?>
							<?php else : ?>
								<img
									src="<?php echo esc_url(wp_get_attachment_url($thumb_id)); ?>"
									alt="<?php echo esc_attr($featured_book->post_title); ?>"
									class="bbb-home-obsession__cover"
									loading="lazy"
									decoding="async"
								>
							<?php endif; ?>
						<?php elseif ($cover_url) : ?>
							<img
								src="<?php echo esc_url($cover_url); ?>"
								alt="<?php echo esc_attr($featured_book->post_title); ?>"
								class="bbb-home-obsession__cover"
								loading="lazy"
								decoding="async"
							>
						<?php endif; ?>

						<?php if ($spice_level > 0) : ?>
							<div class="bbb-home-obsession__spice">
								<?php echo esc_html(str_repeat('🌶️', $spice_level)); ?>
							</div>
						<?php endif; ?>
					</div><!-- /.bbb-home-obsession__coverWrap -->
				</a><!-- /.bbb-home-obsession__coverLink -->

				<div class="bbb-home-obsession__meta">

					<?php if ($shelf_name) : ?>
						<div class="bbb-home-obsession__shelf">
							<span class="bbb-home-obsession__line" aria-hidden="true"></span>
							<span><?php echo esc_html($shelf_name); ?></span>
						</div>
					<?php endif; ?>

					<?php if ($tropes) : ?>
						<div class="bbb-home-obsession__tropes">
							<?php foreach ($tropes as $trope) : ?>
								<?php
								$emoji     = function_exists('bbb_trope_emoji') ? bbb_trope_emoji(get_term_meta($trope->term_id, 'trope_emoji', true)) : (string) get_term_meta($trope->term_id, 'trope_emoji', true);
								$trope_url = function_exists('bbb_trope_page_url') ? bbb_trope_page_url($trope->name, $trope->slug) : '';
								$page_a    = get_page_by_path($trope->slug . '-books');
								if (!$page_a) {
									$page_a = get_page_by_path($trope->slug);
								}
								if ($page_a) {
									$trope_url = get_permalink($page_a);
								}
								?>
									<?php if ($trope_url) : ?>
										<a class="bbb-home-obsession__trope"
											href="<?php echo esc_url($trope_url); ?>">
											<?php echo function_exists('bbb_trope_label_html') ? bbb_trope_label_html($trope->name, $emoji, $trope->slug) : esc_html(trim(($emoji ? $emoji : '🖤') . ' ' . $trope->name)); ?>
										</a>
									<?php endif; ?>
							<?php endforeach; ?>
						</div><!-- /.bbb-home-obsession__tropes -->
					<?php endif; ?>

				</div><!-- /.bbb-home-obsession__meta -->
			</div><!-- /.bbb-home-obsession__feature -->

			<!-- Copy column -->
			<div class="bbb-home-obsession__copy">
				<p class="bbb-home-obsession__kicker"><?php echo esc_html($kicker); ?></p>
				<h2 class="bbb-home-obsession__title"><?php echo esc_html($issue_title); ?></h2>
				<?php if ($secret_url && $secret_url !== $issue_url) : ?>
					<a class="bbb-home-obsession__memberLink" href="<?php echo esc_url($secret_url); ?>" target="_blank" rel="noopener">secret society members only →</a>
				<?php endif; ?>
				<?php if ($issue_excerpt) : ?>
					<p class="bbb-home-obsession__sub"><?php echo esc_html(wp_trim_words($issue_excerpt, 22, '')); ?></p>
				<?php endif; ?>
				<div class="bbb-home-obsession__ratings" aria-label="newsletter ratings">
					<?php if ($spice_level > 0) : ?>
						<span><strong>smut</strong> ?</span>
					<?php endif; ?>
					<?php if ($damage_level > 0) : ?>
						<span><strong>sentiment</strong> ??</span>
					<?php endif; ?>
				</div>
				<?php if ($issue_quote_display) : ?>
					<blockquote class="bbb-home-obsession__quote"><?php echo esc_html($issue_quote_display); ?></blockquote>
				<?php endif; ?>
				<div class="bbb-home-obsession__actions">
					<a class="bbb-home-obsession__button bbb-home-obsession__button--primary" href="<?php echo esc_url($book_url ?: $weekly_url); ?>">ruin my tbr</a>
					<a class="bbb-home-obsession__button bbb-home-obsession__button--ghost" href="<?php echo esc_url($issue_url ?: $subscribe_url); ?>" target="_blank" rel="noopener"><?php echo $issue_url ? esc_html('read the full take →') : esc_html('subscribe'); ?></a>
				</div>
			</div><!-- /.bbb-home-obsession__copy -->

		</div><!-- /.bbb-home-obsession__row -->

	</div><!-- /.bbb-home-obsession__inner -->
</section>
