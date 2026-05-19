<?php
declare(strict_types=1);

$heading = $args['heading'] ?? bbb_get_field('rich_text_heading', get_the_ID(), '');
$text    = $args['text'] ?? bbb_get_field('rich_text_body', get_the_ID(), '');
$label   = $args['button_label'] ?? bbb_get_field('rich_text_button_label', get_the_ID(), '');
$url     = $args['button_url'] ?? bbb_get_field('rich_text_button_url', get_the_ID(), '');
?>
<section class="rich-text page-width page-width--narrow">
	<?php if ($heading) : ?>
		<h2 class="rich-text__heading h1"><?php echo esc_html((string) $heading); ?></h2>
	<?php endif; ?>
	<?php if ($text) : ?>
		<div class="rich-text__text rte"><?php echo wp_kses_post(wpautop((string) $text)); ?></div>
	<?php endif; ?>
	<?php if ($label && $url) : ?>
		<a class="button" href="<?php echo esc_url((string) $url); ?>"><?php echo esc_html((string) $label); ?></a>
	<?php endif; ?>
</section>
