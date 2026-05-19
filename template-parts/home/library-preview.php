<?php
/**
 * Homepage Society Obsessed Preview.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$settings = wp_parse_args(
	$args ?? array(),
	array(
		'demo_kicker'       => 'what to read next',
		'demo_title'        => 'for the bookaholics who love romance',
		'demo_subtext'      => 'pick one book and watch the next recommendation slide into place based on shelf chemistry, tropes, and mood.',
		'demo_button'       => 'try the rec engine →',
		'demo_link'         => 'https://bybookishbabe.com/pages/what-to-read-next',
		'demo_pick_cover'   => '',
		'demo_pick_title'   => 'daggermouth',
		'demo_pick_meta'    => 'dystopian romance + enemies to lovers',
		'demo_result_cover' => '',
		'demo_result_title' => 'until i die',
		'demo_result_meta'  => 'closest match • dystopian romance + enemies to lovers',
	)
);

if (!function_exists('bbb_library_preview_find_book')) {
	function bbb_library_preview_find_book(string $title): ?WP_Post {
		$post_type = post_type_exists('sss_library') ? 'sss_library' : 'sss_book';
		$slug      = sanitize_title($title);

		if ('' !== $slug) {
			$by_slug = get_page_by_path($slug, OBJECT, $post_type);
			if ($by_slug instanceof WP_Post) {
				return $by_slug;
			}
		}

		$matches = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'publish',
				'title'            => $title,
				'posts_per_page'   => 1,
				'suppress_filters' => false,
			)
		);

		return !empty($matches[0]) && $matches[0] instanceof WP_Post ? $matches[0] : null;
	}
}

if (!function_exists('bbb_library_preview_cover')) {
	function bbb_library_preview_cover(string $provided_cover, string $title): string {
		if ('' !== trim($provided_cover)) {
			return $provided_cover;
		}

		$book = bbb_library_preview_find_book($title);
		if (!$book) {
			return '';
		}

		return function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book->ID) : (get_the_post_thumbnail_url($book->ID, 'large') ?: '');
	}
}

$demo_pick_cover   = bbb_library_preview_cover((string) $settings['demo_pick_cover'], (string) $settings['demo_pick_title']);
$demo_result_cover = bbb_library_preview_cover((string) $settings['demo_result_cover'], (string) $settings['demo_result_title']);
$post_type         = post_type_exists('sss_library') ? 'sss_library' : 'sss_book';
$top_shelf_books   = array();
$top_shelf_query   = new WP_Query(
	array(
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => 250,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		),
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'key'     => 'top_shelf',
					'value'   => '1',
					'compare' => '=',
				),
				array(
					'key'     => 'top_shelf',
					'value'   => 'true',
					'compare' => '=',
				),
			),
			array(
				'relation' => 'OR',
				array(
					'key'     => 'hide_from_library',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'hide_from_library',
					'value'   => '1',
					'compare' => '!=',
				),
			),
			array(
				'relation' => 'OR',
				array(
					'key'     => 'is_private',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'is_private',
					'value'   => '1',
					'compare' => '!=',
				),
			),
		),
	)
);

if ($top_shelf_query->have_posts()) {
	while ($top_shelf_query->have_posts()) {
		$top_shelf_query->the_post();
		$book_id = get_the_ID();

		if (function_exists('bbb_book_is_publicly_visible') && !bbb_book_is_publicly_visible($book_id)) {
			continue;
		}

		$top_shelf_books[] = get_post($book_id);
		if (count($top_shelf_books) >= 5) {
			break;
		}
	}

	wp_reset_postdata();
}
?>
<section class="sss-lib sss-lib--preview" data-sss-lib="public">
	<div class="sss-lib__wrap">
		<a class="bbb-homeRecDemo" href="<?php echo esc_url((string) $settings['demo_link']); ?>">
			<div class="bbb-homeRecDemo__copy">
				<div class="bbb-homeRecDemo__kicker">
					<?php echo esc_html((string) $settings['demo_kicker']); ?>
				</div>

				<div class="bbb-homeRecDemo__title">
					<?php echo esc_html((string) $settings['demo_title']); ?>
				</div>

				<div class="bbb-homeRecDemo__sub">
					<?php echo esc_html((string) $settings['demo_subtext']); ?>
				</div>

				<div class="bbb-homeRecDemo__cta">
					<?php echo esc_html((string) $settings['demo_button']); ?>
				</div>
			</div>

			<div class="bbb-homeRecDemo__stage" aria-hidden="true">
				<div class="bbb-homeRecDemo__book bbb-homeRecDemo__book--picked">
					<div class="bbb-homeRecDemo__label">you picked</div>
					<?php if ('' !== $demo_pick_cover) : ?>
						<img src="<?php echo esc_url($demo_pick_cover); ?>" alt="<?php echo esc_attr((string) $settings['demo_pick_title']); ?>" loading="lazy">
					<?php endif; ?>
					<div class="bbb-homeRecDemo__meta">
						<div class="bbb-homeRecDemo__bookTitle"><?php echo esc_html((string) $settings['demo_pick_title']); ?></div>
						<div class="bbb-homeRecDemo__bookLine"><?php echo esc_html((string) $settings['demo_pick_meta']); ?></div>
					</div>
				</div>

				<div class="bbb-homeRecDemo__book bbb-homeRecDemo__book--result">
					<div class="bbb-homeRecDemo__label">closest match</div>
					<?php if ('' !== $demo_result_cover) : ?>
						<img src="<?php echo esc_url($demo_result_cover); ?>" alt="<?php echo esc_attr((string) $settings['demo_result_title']); ?>" loading="lazy">
					<?php endif; ?>
					<div class="bbb-homeRecDemo__meta">
						<div class="bbb-homeRecDemo__bookTitle"><?php echo esc_html((string) $settings['demo_result_title']); ?></div>
						<div class="bbb-homeRecDemo__bookLine"><?php echo esc_html((string) $settings['demo_result_meta']); ?></div>
					</div>
				</div>
			</div>
		</a>

		<div class="sss-lib__archiveHead">
			<div class="sss-lib__archiveKicker">
				society favorites
			</div>

			<h2 class="sss-lib__archiveTitle">
				what the society is obsessed with
			</h2>

			<div class="sss-lib__archiveSub">
				a few books the society can’t stop recommending.
			</div>
		</div>

		<div class="sss-lib__shelf">
			<div class="sss-lib__shelfRow" id="sssPreviewTrending" data-sss-lib="public">
				<?php foreach ($top_shelf_books as $book) : ?>
					<?php bbb_render_component('sss-book-card', array('book' => $book, 'mini' => true)); ?>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="sss-lib__previewLink">
			<a href="/pages/library">
				explore the full library →
			</a>
		</div>
	</div>
</section>
<?php
bbb_render_component('sss-library-modal');
