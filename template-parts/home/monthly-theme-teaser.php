<?php
/**
 * Homepage monthly theme teaser.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$theme_url     = function_exists('bbb_page_url') ? bbb_page_url('burn-bright') : home_url('/burn-bright/');
$subscribe_url = function_exists('bbb_substack_subscribe_url') ? bbb_substack_subscribe_url() : 'https://thesmutandsentimentsociety.substack.com/subscribe';
$release_at    = '2026-06-01T00:00:00-07:00';
$release_time  = strtotime($release_at);
$is_current    = $release_time && time() >= $release_time;
$theme_label   = $is_current ? 'current monthly theme' : 'theme coming next';
$asset_base    = 'assets/monthly-themes/june-2026';
$previews      = array(
	array(
		'src' => 'previews/alive-in-the-night.png',
		'alt' => 'Alive in the Night kindle insert artwork preview',
	),
	array(
		'src' => 'previews/golden-and-unbothered.png',
		'alt' => 'Golden and Unbothered kindle insert artwork preview',
	),
	array(
		'src' => 'previews/you-glow-different.png',
		'alt' => 'You Glow Different kindle insert artwork preview',
	),
);
?>
<section class="bbb-monthly-teaser" aria-labelledby="bbb-monthly-teaser-title">
	<div class="bbb-monthly-teaser__inner">
		<a class="bbb-monthly-teaser__art" href="<?php echo esc_url($theme_url); ?>" aria-label="Preview the June 2026 Burn Bright monthly theme">
			<?php foreach ($previews as $index => $preview) : ?>
				<figure class="bbb-monthly-teaser__print bbb-monthly-teaser__print--<?php echo esc_attr((string) ($index + 1)); ?>">
					<img src="<?php echo esc_url(get_theme_file_uri($asset_base . '/' . $preview['src'])); ?>" alt="<?php echo esc_attr($preview['alt']); ?>" loading="lazy">
				</figure>
			<?php endforeach; ?>
		</a>

		<div class="bbb-monthly-teaser__copy">
			<div class="bbb-monthly-teaser__countdown" data-monthly-release="<?php echo esc_attr($release_at); ?>" aria-label="Countdown to monthly theme release">
				<span class="bbb-monthly-teaser__countdown-label">releases in</span>
				<div class="bbb-monthly-teaser__timer" aria-live="polite">
					<span class="bbb-monthly-teaser__timerUnit"><strong data-monthly-days>00</strong><span>days</span></span>
					<span class="bbb-monthly-teaser__timerUnit"><strong data-monthly-hours>00</strong><span>hrs</span></span>
					<span class="bbb-monthly-teaser__timerUnit"><strong data-monthly-minutes>00</strong><span>min</span></span>
					<span class="bbb-monthly-teaser__timerUnit"><strong data-monthly-seconds>00</strong><span>sec</span></span>
				</div>
			</div>
			<p class="bbb-monthly-teaser__eyebrow">included in paid society membership</p>
			<h2 id="bbb-monthly-teaser-title"><?php echo esc_html($theme_label); ?>: burn bright</h2>
			<p>peek at the <?php echo esc_html($is_current ? 'current' : 'next'); ?> monthly theme page: printable kindle inserts, wallpapers, a calendar, playlist vibes, and the whole orange-lit mood.</p>
			<div class="bbb-monthly-teaser__actions" aria-label="Monthly theme actions">
				<a class="bbb-monthly-teaser__button bbb-monthly-teaser__button--secondary" href="<?php echo esc_url($theme_url); ?>">preview the theme</a>
				<a class="bbb-monthly-teaser__button bbb-monthly-teaser__button--primary" href="<?php echo esc_url($subscribe_url); ?>" target="_blank" rel="noopener">subscribe on substack</a>
			</div>
		</div>
	</div>
</section>
