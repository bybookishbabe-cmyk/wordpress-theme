<?php
/**
 * Template part: Weekly Obsession
 * Included from front-page.php or page-home.php
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$current_issue = sss_get_current_newsletter_issue();
$featured_book = $current_issue ? sss_get_obsession_book($current_issue) : null;
if (!$featured_book) {
	$featured_book = function_exists('sss_get_latest_featured_book') ? sss_get_latest_featured_book() : null;
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

$issue_title = $current_issue && !empty(get_post_meta($current_issue->ID, '_issue_title_override', true))
	? get_post_meta($current_issue->ID, '_issue_title_override', true)
	: ($current_issue ? $current_issue->post_title : '');
if (empty($issue_title)) {
	$issue_title = $featured_book->post_title;
}
$issue_subtitle = $current_issue ? get_post_meta($current_issue->ID, '_issue_subtitle', true) : get_post_meta($book_id, '_bbb_mini_note', true);
$cover_url      = 'bbb_book' === $featured_book->post_type ? (string) get_post_meta($book_id, '_bbb_cover_url', true) : '';
?>

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
			<a class="bbb-home-obsession__coverLink" href="<?php echo esc_url($obsession_url); ?>">

				<div class="bbb-home-obsession__coverWrap">
					<?php if ($thumb_id) : ?>
						<?php if ($has_native_dims) : ?>
							<?php
							echo wp_get_attachment_image(
								$thumb_id,
								'full',
								false,
								array(
									'class'    => 'bbb-home-obsession__cover',
									'sizes'    => '(max-width: 749px) 78vw, 280px',
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

				<!-- Meta: shelf + trope pills -->
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
								$colors    = function_exists('bbb_get_trope_colors') ? bbb_get_trope_colors($trope->slug) : sss_get_trope_colors($trope->slug);
								$trope_url = '';
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
										href="<?php echo esc_url($trope_url); ?>"
										style="--trope-bg: <?php echo esc_attr($colors[0]); ?>; --trope-text: <?php echo esc_attr($colors[1]); ?>;">
										<?php echo esc_html($trope->name); ?>
									</a>
								<?php else : ?>
									<span class="bbb-home-obsession__trope"
										style="--trope-bg: <?php echo esc_attr($colors[0]); ?>; --trope-text: <?php echo esc_attr($colors[1]); ?>;">
										<?php echo esc_html($trope->name); ?>
									</span>
								<?php endif; ?>
							<?php endforeach; ?>
						</div><!-- /.bbb-home-obsession__tropes -->
					<?php endif; ?>

				</div><!-- /.bbb-home-obsession__meta -->

			</a><!-- /.bbb-home-obsession__coverLink -->

			<!-- Copy column -->
			<div class="bbb-home-obsession__copy">
				<p class="bbb-home-obsession__kicker"><?php echo esc_html($kicker); ?></p>
				<h2 class="bbb-home-obsession__title"><?php echo esc_html($issue_title); ?></h2>
				<?php if ($issue_subtitle) : ?>
					<p class="bbb-home-obsession__sub"><?php echo esc_html($issue_subtitle); ?></p>
				<?php endif; ?>
			</div><!-- /.bbb-home-obsession__copy -->

		</div><!-- /.bbb-home-obsession__row -->

	</div><!-- /.bbb-home-obsession__inner -->
</section>
