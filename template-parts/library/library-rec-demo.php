<?php
declare(strict_types=1);

$pick_title   = (string) get_option('sss_lib_rec_pick', 'daggermouth');
$result_title = (string) get_option('sss_lib_rec_result', 'until i die');
$rec_kicker   = (string) get_option('sss_lib_rec_kicker', 'reader chemistry');
$rec_link     = (string) get_option('sss_lib_rec_link', '/what-to-read-next/');
$rec_href     = preg_match('/^https?:\/\//', $rec_link) ? $rec_link : home_url($rec_link);

$find_book = static function (string $title): ?WP_Post {
	$posts = get_posts(
		array(
			'post_type'        => 'sss_book',
			'title'            => $title,
			'posts_per_page'   => 1,
			'suppress_filters' => false,
		)
	);

	return isset($posts[0]) && $posts[0] instanceof WP_Post ? $posts[0] : null;
};

$pick_book    = $find_book($pick_title);
$result_book  = $find_book($result_title);
$pick_cover   = $pick_book instanceof WP_Post ? sss_get_book_cover_url($pick_book->ID) : '';
$result_cover = $result_book instanceof WP_Post ? sss_get_book_cover_url($result_book->ID) : '';
?>
<a href="<?php echo esc_url($rec_href); ?>" class="bbb-homeRecDemo bbb-homeRecDemo--library">
	<div class="bbb-homeRecDemo__copy">
		<div class="bbb-homeRecDemo__kicker"><?php echo esc_html($rec_kicker); ?></div>
		<div class="bbb-homeRecDemo__title"><span>if you liked...</span> <?php echo esc_html($pick_title); ?></div>
		<div class="bbb-homeRecDemo__sub">read this next</div>
		<div class="bbb-homeRecDemo__cta">what to read next</div>
	</div>
	<div class="bbb-homeRecDemo__stage" aria-hidden="true">
		<div class="bbb-homeRecDemo__book bbb-homeRecDemo__book--picked">
			<div class="bbb-homeRecDemo__label">you picked</div>
			<?php if ($pick_cover) : ?>
				<img src="<?php echo esc_url($pick_cover); ?>" alt="<?php echo esc_attr($pick_title); ?>" loading="lazy">
			<?php endif; ?>
			<div class="bbb-homeRecDemo__meta">
				<div class="bbb-homeRecDemo__bookTitle"><?php echo esc_html($pick_title); ?></div>
			</div>
		</div>
		<div class="bbb-homeRecDemo__book bbb-homeRecDemo__book--result">
			<div class="bbb-homeRecDemo__label">read this next</div>
			<?php if ($result_cover) : ?>
				<img src="<?php echo esc_url($result_cover); ?>" alt="<?php echo esc_attr($result_title); ?>" loading="lazy">
			<?php endif; ?>
			<div class="bbb-homeRecDemo__meta">
				<div class="bbb-homeRecDemo__bookTitle"><?php echo esc_html($result_title); ?></div>
			</div>
		</div>
	</div>
</a>
