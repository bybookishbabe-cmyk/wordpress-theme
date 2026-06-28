<?php
/**
 * Template Name: Fictional Boyfriends Hub
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$fictional_boyfriends_social_title       = 'fictional boyfriends — bybookishbabe';
$fictional_boyfriends_social_description = 'morally gray, protective, obsessed fictional boyfriend profiles with all the juicy information curated by bybookishbabe.';
$fictional_boyfriends_canonical          = home_url('/fictional-boyfriends/');
$fictional_boyfriends_image              = get_theme_file_uri('assets/seo/share-cards/fictional-boyfriends.png');
$fictional_boyfriends_image_alt          = 'fictional boyfriends by bybookishbabe cover collage';

add_filter('rank_math/opengraph/facebook/title', static fn(): string => $fictional_boyfriends_social_title, 99);
add_filter('rank_math/opengraph/facebook/description', static fn(): string => $fictional_boyfriends_social_description, 99);
add_filter('rank_math/opengraph/facebook/url', static fn(): string => $fictional_boyfriends_canonical, 99);
add_filter('rank_math/opengraph/facebook/image', static fn(): string => $fictional_boyfriends_image, 99);
add_filter('rank_math/opengraph/twitter/title', static fn(): string => $fictional_boyfriends_social_title, 99);
add_filter('rank_math/opengraph/twitter/description', static fn(): string => $fictional_boyfriends_social_description, 99);
add_filter('rank_math/opengraph/twitter/url', static fn(): string => $fictional_boyfriends_canonical, 99);
add_filter('rank_math/opengraph/twitter/image', static fn(): string => $fictional_boyfriends_image, 99);
add_filter('rank_math/opengraph/type', static fn(): string => 'website', 99);
add_action(
	'rank_math/opengraph/facebook',
	static function () use ($fictional_boyfriends_social_title, $fictional_boyfriends_social_description, $fictional_boyfriends_canonical, $fictional_boyfriends_image, $fictional_boyfriends_image_alt): void {
		remove_all_actions('rank_math/opengraph/facebook', 30);

		printf('<meta property="og:type" content="website">%s', "\n");
		printf('<meta property="og:title" content="%s">%s', esc_attr($fictional_boyfriends_social_title), "\n");
		printf('<meta property="og:description" content="%s">%s', esc_attr($fictional_boyfriends_social_description), "\n");
		printf('<meta property="og:image" content="%s">%s', esc_url($fictional_boyfriends_image), "\n");
		printf('<meta property="og:image:secure_url" content="%s">%s', esc_url($fictional_boyfriends_image), "\n");
		printf('<meta property="og:image:width" content="1800">%s', "\n");
		printf('<meta property="og:image:height" content="1000">%s', "\n");
		printf('<meta property="og:image:type" content="image/png">%s', "\n");
		printf('<meta property="og:image:alt" content="%s">%s', esc_attr($fictional_boyfriends_image_alt), "\n");
		printf('<meta property="og:url" content="%s">%s', esc_url($fictional_boyfriends_canonical), "\n");
	},
	4
);
add_action(
	'rank_math/opengraph/twitter',
	static function () use ($fictional_boyfriends_social_title, $fictional_boyfriends_social_description, $fictional_boyfriends_image): void {
		remove_all_actions('rank_math/opengraph/twitter', 30);

		printf('<meta name="twitter:title" content="%s">%s', esc_attr($fictional_boyfriends_social_title), "\n");
		printf('<meta name="twitter:description" content="%s">%s', esc_attr($fictional_boyfriends_social_description), "\n");
		printf('<meta name="twitter:image" content="%s">%s', esc_url($fictional_boyfriends_image), "\n");
	},
	4
);

bbb_enqueue_css('bbb-book-breakdown-page', 'assets/css/book-breakdown-page.css', array('bbb-base'));
bbb_enqueue_css('bbb-fictional-boyfriends', 'assets/css/fictional-boyfriends.css', array('bbb-book-breakdown-page'));

$boyfriends = get_posts(
	array(
		'post_type'      => 'bbb_boyfriend',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	)
);
$boyfriends = array_values(
	array_filter(
		$boyfriends,
		static fn($post): bool => $post instanceof WP_Post
			&& bbb_fictional_boyfriend_profile_ready((int) $post->ID)
			&& (!function_exists('bbb_content_is_publicly_discoverable') || bbb_content_is_publicly_discoverable((int) $post->ID))
	)
);

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none bbb-fb" role="main" tabindex="-1">
	<nav class="bbb-fb-breadcrumb" aria-label="breadcrumb">
		<a href="<?php echo esc_url(home_url('/')); ?>">home</a>
		<span aria-hidden="true">›</span>
		<a href="<?php echo esc_url(home_url('/library/')); ?>">library</a>
		<span aria-hidden="true">›</span>
		<span>fictional boyfriends</span>
	</nav>

	<section class="bbb-fb-hero">
		<div class="bbb-fb-hero__inner">
			<div class="bbb-fb-hero__copy">
				<p class="bbb-fb-kicker bbb-fb-kicker--pill">fictional boyfriend directory</p>
				<h1>fictional boyfriends that ruined your standards</h1>
				<p>they live in pages, they've never texted you back, and somehow they've completely destroyed your expectations. here's the full lineup.</p>
				<a class="bbb-fb-cta" href="<?php echo esc_url(home_url('/fictional-boyfriend-quiz/')); ?>">don't know who yours is yet? find out in 2 minutes <span aria-hidden="true">→</span></a>
			</div>
			<?php if ($boyfriends) : ?>
				<?php $featured = $boyfriends[0]; ?>
				<div class="bbb-fb-hero__portrait" aria-hidden="true">
					<?php echo get_the_post_thumbnail($featured, 'large'); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="bbb-fb-directory" aria-labelledby="fictional-boyfriend-directory-title">
		<div class="bbb-fb-wrap">
			<div class="bbb-fb-filter" aria-label="filter fictional boyfriends">
				<a href="#fictional-boyfriend-directory-title" data-fb-filter="all" class="is-active">all</a>
			</div>

			<h2 id="fictional-boyfriend-directory-title">meet the lineup</h2>

			<?php if (!$boyfriends) : ?>
				<p class="bbb-fb-empty">no fictional boyfriend profiles are ready yet.</p>
			<?php else : ?>
				<div class="bbb-fb-grid" data-fb-grid>
					<?php foreach ($boyfriends as $boyfriend) : ?>
						<?php
						$post_id  = (int) $boyfriend->ID;
						$tropes   = array_slice(bbb_fictional_boyfriend_tropes($post_id), 0, 3);
						$filter   = bbb_fictional_boyfriend_filter_key($post_id);
						$source   = (string) get_post_meta($post_id, '_bbb_fb_source', true);
						$author   = (string) get_post_meta($post_id, '_bbb_fb_author', true);
						$spice    = bbb_fictional_boyfriend_spice($post_id);
						?>
						<article class="bbb-fb-card" data-fb-card data-filter="<?php echo esc_attr($filter); ?>">
							<a class="bbb-fb-card__image" href="<?php echo esc_url(get_permalink($boyfriend)); ?>">
								<?php echo get_the_post_thumbnail($boyfriend, 'large'); ?>
								<?php if (function_exists('bbb_fictional_boyfriend_is_popular_now') && bbb_fictional_boyfriend_is_popular_now($post_id)) : ?>
									<?php echo wp_kses_post(bbb_fictional_boyfriend_popular_badge('bbb-fb-popular-badge--card')); ?>
								<?php endif; ?>
							</a>
							<div class="bbb-fb-card__body">
								<h3><a href="<?php echo esc_url(get_permalink($boyfriend)); ?>"><?php echo esc_html(get_the_title($boyfriend)); ?></a></h3>
								<?php if ($source || $author) : ?>
									<p class="bbb-fb-card__book"><?php echo esc_html(trim($source . ($source && $author ? ' by ' : '') . $author)); ?></p>
								<?php endif; ?>
								<p class="bbb-fb-card__desc"><?php echo esc_html(bbb_fictional_boyfriend_descriptor($post_id)); ?></p>
								<?php if ($tropes) : ?>
									<div class="bbb-fb-tags">
										<?php foreach ($tropes as $trope) : ?>
											<span><?php echo esc_html($trope); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
								<div class="bbb-fb-card__meta">
									<span><?php echo esc_html(bbb_fictional_boyfriend_peppers($spice)); ?></span>
									<a href="<?php echo esc_url(get_permalink($boyfriend)); ?>">meet him <span aria-hidden="true">→</span></a>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="bbb-fb-bottom-cta">
		<div class="bbb-fb-wrap">
			<h2>still not sure?</h2>
			<a class="bbb-fb-cta bbb-fb-cta--light" href="<?php echo esc_url(home_url('/fictional-boyfriend-quiz/')); ?>">take the quiz <span aria-hidden="true">→</span></a>
		</div>
	</section>
</main>

<script>
document.addEventListener('click', function (event) {
	var trigger = event.target.closest('[data-fb-filter]');
	if (!trigger) return;
	event.preventDefault();
	var filter = trigger.getAttribute('data-fb-filter');
	document.querySelectorAll('[data-fb-filter]').forEach(function (item) {
		item.classList.toggle('is-active', item === trigger);
	});
	document.querySelectorAll('[data-fb-card]').forEach(function (card) {
		card.hidden = filter !== 'all' && card.getAttribute('data-filter') !== filter;
	});
});
</script>

<?php
get_footer();
