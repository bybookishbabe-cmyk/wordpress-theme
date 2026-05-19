<?php
/**
 * Featured romance lists homepage section.
 *
 * @package ByBookishBabeShopifyPort
 */

$posts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 5,
		'category_name'  => 'curated-romance-guides',
	)
);

if (!$posts) {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 5,
		)
	);
}
?>
<section class="bbb-romance-lists">
	<div class="bbb-romance-lists__inner">
		<div class="bbb-romance-lists__head">
			<p class="bbb-romance-lists__kicker">reader favorites</p>
			<h2 class="bbb-romance-lists__title">featured romance lists</h2>
			<p class="bbb-romance-lists__sub">quick romance reading lists for when you're not sure what to read next.</p>
		</div>

		<div class="bbb-romance-lists__shelf">
			<?php foreach ($posts as $index => $list_post) : ?>
				<a href="<?php echo esc_url(get_permalink($list_post)); ?>" class="bbb-romance-lists__spine">
					<span class="bbb-romance-lists__number"><?php echo esc_html((string) ($index + 1)); ?></span>
					<span class="bbb-romance-lists__name"><?php echo esc_html(get_the_title($list_post)); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="bbb-romance-lists__cta">
			<a href="<?php echo esc_url(home_url('/blog/curated-romance-guides/')); ?>">
				explore all romance lists →
			</a>
		</div>
	</div>
</section>
