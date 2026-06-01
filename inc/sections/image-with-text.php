<?php
declare(strict_types=1);

$image   = $args['image'] ?? bbb_get_field('image_with_text_image', get_the_ID(), '');
$heading = $args['heading'] ?? bbb_get_field('image_with_text_heading', get_the_ID(), 'smut ceo');
$body    = $args['body'] ?? bbb_get_field('image_with_text_body', get_the_ID(), '');
$layout  = $args['layout'] ?? bbb_get_field('image_with_text_layout', get_the_ID(), 'image_first');
$url     = is_array($image) ? (string) ($image['url'] ?? '') : (string) $image;
?>
<section class="image-with-text page-width image-with-text--<?php echo esc_attr((string) $layout); ?>">
	<?php if ($url) : ?>
		<div class="image-with-text__media"><img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr((string) $heading); ?>"></div>
	<?php endif; ?>
	<div class="image-with-text__content">
		<h2 class="image-with-text__heading h1"><?php echo esc_html((string) $heading); ?></h2>
		<?php if ($body) : ?>
			<div class="image-with-text__text rte"><?php echo wp_kses_post(wpautop((string) $body)); ?></div>
		<?php endif; ?>
	</div>
</section>
