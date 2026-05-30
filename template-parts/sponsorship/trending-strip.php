<?php
/**
 * Thin sponsorship strip for trending/book sections.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$variant = (string) ($args['variant'] ?? 'home-soft');

$configs = array(
	'home-soft' => array(
		'modifier'   => 'bbb-sponsor-strip--home',
		'presented'  => 'presented by future bookish favorites',
		'headline'   => 'something my bookish heart is currently obsessed with',
		'sub'        => 'brand notes · reader-loved finds · soft-sell only',
		'cta'        => 'work with bybookishbabe →',
		'disclosure' => 'sponsored',
		'href'       => '',
	),
	'kindle-unlimited' => array(
		'modifier'   => 'bbb-sponsor-strip--ku',
		'presented'  => 'presented by kindle unlimited',
		'headline'   => 'all the dark romance you can read. one monthly price. no apologies.',
		'sub'        => 'kindle unlimited · unlimited reads · cancel anytime',
		'cta'        => 'try free →',
		'disclosure' => 'sponsored',
		'href'       => 'https://amzn.to/4uZ8Y3a',
	),
);

$config = $configs[$variant] ?? $configs['home-soft'];
$classes = trim('bbb-sponsor-strip ' . $config['modifier']);
?>

<?php if ('' !== $config['href']) : ?>
	<a class="<?php echo esc_attr($classes); ?>" href="<?php echo esc_url($config['href']); ?>" target="_blank" rel="noopener sponsored">
<?php else : ?>
	<button class="<?php echo esc_attr($classes); ?>" type="button" aria-haspopup="dialog" aria-controls="bbb-sponsor-work-with-me" data-bbb-sponsor-open>
<?php endif; ?>
	<span class="bbb-sponsor-strip__left">
		<span class="bbb-sponsor-strip__presented"><?php echo esc_html($config['presented']); ?></span>
		<span class="bbb-sponsor-strip__headline"><?php echo esc_html($config['headline']); ?></span>
		<span class="bbb-sponsor-strip__sub"><?php echo esc_html($config['sub']); ?></span>
	</span>
	<span class="bbb-sponsor-strip__right">
		<span class="bbb-sponsor-strip__disclosure"><?php echo esc_html($config['disclosure']); ?></span>
		<span class="bbb-sponsor-strip__cta"><?php echo esc_html($config['cta']); ?></span>
	</span>
<?php echo '' !== $config['href'] ? '</a>' : '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
