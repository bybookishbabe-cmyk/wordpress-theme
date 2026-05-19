<?php
/**
 * Template Part: Homepage Hero
 * Converted from: sections/hero-smut-sentiment.liquid
 *
 * @package ByBookishBabeShopifyPort
 */

$hero_heading         = get_theme_mod('hero_heading', 'smut meets sentiment');
$hero_mini_text       = get_theme_mod('hero_mini_text', 'for soft hearts with sinful taste.');
$hero_subtitle        = get_theme_mod('hero_subtitle', 'morally gray men delivered every sunday 🖤');
$hero_primary_label   = get_theme_mod('hero_primary_label', 'explore library');
$hero_primary_link    = get_theme_mod('hero_primary_link', '/library/');
$hero_secondary_label = get_theme_mod('hero_secondary_label', 'join the society');
$hero_secondary_link  = get_theme_mod('hero_secondary_link', 'https://thesmutandsentimentsociety.substack.com/subscribe');
?>

<section class="hero-custom">
	<div class="hero-overlay"></div>

	<div class="hero-frame">
		<h1 class="hero-heading">
			<span class="hero-cursive"><?php echo esc_html($hero_heading); ?></span>
		</h1>

		<p class="hero-mini"><?php echo esc_html($hero_mini_text); ?></p>

		<p class="hero-subtitle">
			<?php echo esc_html($hero_subtitle); ?>
		</p>

		<div class="hero-buttons">
			<?php if (!empty($hero_primary_link)) : ?>
				<a href="<?php echo esc_url($hero_primary_link); ?>" class="btn-primary">
					<?php echo esc_html($hero_primary_label); ?>
				</a>
			<?php endif; ?>

			<?php if (!empty($hero_secondary_link)) : ?>
				<a href="<?php echo esc_url($hero_secondary_link); ?>" class="btn-secondary">
					<?php echo esc_html($hero_secondary_label); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>
