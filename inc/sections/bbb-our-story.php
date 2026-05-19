<?php
declare(strict_types=1);

$accent = (string) bbb_get_field('accent', get_the_ID(), '#b91c1c');
$hero_image = bbb_get_field('hero_image', get_the_ID(), '');
$hero_url = is_array($hero_image) ? (string) ($hero_image['url'] ?? '') : (string) $hero_image;
$boyfriend_image = bbb_get_field('favorite_boyfriend_image', get_the_ID(), '');
$boyfriend_url = is_array($boyfriend_image) ? (string) ($boyfriend_image['url'] ?? '') : (string) $boyfriend_image;
?>
<section class="bbb-our-story" style="--bbb-accent: <?php echo esc_attr($accent); ?>">
	<div class="bbb-our-story__wrap page-width">
		<header class="bbb-our-story__hero">
			<div>
				<p class="bbb-our-story__kicker"><?php echo esc_html((string) bbb_get_field('hero_kicker', get_the_ID(), 'why i started this')); ?></p>
				<h1 class="bbb-our-story__title"><?php echo esc_html((string) bbb_get_field('hero_title', get_the_ID(), 'i wanted reading to feel as beautiful as it felt.')); ?></h1>
				<div class="rte"><?php the_content(); ?></div>
			</div>
			<?php if ($hero_url) : ?>
				<img class="bbb-our-story__image" src="<?php echo esc_url($hero_url); ?>" alt="">
			<?php endif; ?>
		</header>
		<?php bbb_render_component('society-classics'); ?>
		<section class="bbb-our-story__favorite">
			<div>
				<p class="bbb-our-story__kicker"><?php echo esc_html((string) bbb_get_field('favorite_boyfriend_kicker', get_the_ID(), 'favorite book boyfriend')); ?></p>
				<h2><?php echo esc_html((string) bbb_get_field('favorite_boyfriend_title', get_the_ID(), 'the fictional man i would defend in a group chat')); ?></h2>
			</div>
			<?php if ($boyfriend_url) : ?>
				<img src="<?php echo esc_url($boyfriend_url); ?>" alt="">
			<?php endif; ?>
		</section>
		<footer class="bbb-our-story__closing">
			<p class="bbb-our-story__kicker"><?php echo esc_html((string) bbb_get_field('closing_kicker', get_the_ID(), 'thank you for being here')); ?></p>
			<h2><?php echo esc_html((string) bbb_get_field('closing_title', get_the_ID(), 'if you made it this far, stay a while.')); ?></h2>
			<a class="button" href="<?php echo esc_url((string) bbb_get_field('society_url', get_the_ID(), 'https://thesmutandsentimentsociety.substack.com/subscribe')); ?>"><?php echo esc_html((string) bbb_get_field('society_label', get_the_ID(), 'join the smut and sentiment society →')); ?></a>
		</footer>
	</div>
</section>
