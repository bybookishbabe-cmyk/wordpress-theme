<?php
/**
 * Homepage monthly theme teaser.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$theme_url     = function_exists('bbb_page_url') ? bbb_page_url('monthly-theme') : home_url('/monthly-theme/');
$subscribe_url = function_exists('bbb_substack_subscribe_url') ? bbb_substack_subscribe_url() : 'https://thesmutandsentimentsociety.substack.com/subscribe';
$release_at    = '2026-07-01T00:00:00-07:00';
$release_time  = strtotime($release_at);
$current_time  = current_time('timestamp');
$promo_ends_at = $release_time ? strtotime('+10 days', $release_time) : 0;
$is_current    = $release_time && $current_time >= $release_time;
$show_teaser   = !$is_current || !$promo_ends_at || $current_time < $promo_ends_at;
$theme_label   = $is_current ? 'current monthly theme' : 'theme coming next';
$asset_base    = 'assets/monthly-themes/july-2026';
$previews      = array(
	array(
		'src' => 'display/midnight-swim-insert-preview.jpg',
		'alt' => 'Midnight Swim kindle insert artwork preview',
	),
	array(
		'src' => 'display/midnight-movie-insert-preview.jpg',
		'alt' => 'Midnight Movie kindle insert artwork preview',
	),
	array(
		'src' => 'display/midnight-drive-insert-preview.jpg',
		'alt' => 'Midnight Drive kindle insert artwork preview',
	),
);
?>
<?php if ($show_teaser) : ?>
<section class="bbb-monthly-teaser" aria-labelledby="bbb-monthly-teaser-title">
	<div class="bbb-monthly-teaser__inner">
		<a class="bbb-monthly-teaser__art" href="<?php echo esc_url($theme_url); ?>" aria-label="Preview the July 2026 Midnight Summer monthly theme">
			<?php foreach ($previews as $index => $preview) : ?>
				<figure class="bbb-monthly-teaser__print bbb-monthly-teaser__print--<?php echo esc_attr((string) ($index + 1)); ?>">
					<img src="<?php echo esc_url(get_theme_file_uri($asset_base . '/' . $preview['src'])); ?>" alt="<?php echo esc_attr($preview['alt']); ?>" loading="lazy">
				</figure>
			<?php endforeach; ?>
		</a>

		<div class="bbb-monthly-teaser__copy">
			<?php if ($is_current) : ?>
				<div class="bbb-monthly-teaser__countdown" aria-label="Monthly theme is live">
					<span class="bbb-monthly-teaser__countdown-label">live now</span>
					<div class="bbb-monthly-teaser__timer" aria-live="polite">
						<span class="bbb-monthly-teaser__timerUnit"><strong>don't miss out</strong></span>
					</div>
				</div>
			<?php else : ?>
				<div class="bbb-monthly-teaser__countdown" data-monthly-release="<?php echo esc_attr($release_at); ?>" aria-label="Countdown to monthly theme release">
					<span class="bbb-monthly-teaser__countdown-label">releases in</span>
					<div class="bbb-monthly-teaser__timer" aria-live="polite">
						<span class="bbb-monthly-teaser__timerUnit"><strong data-monthly-days>00</strong><span>days</span></span>
						<span class="bbb-monthly-teaser__timerUnit"><strong data-monthly-hours>00</strong><span>hrs</span></span>
						<span class="bbb-monthly-teaser__timerUnit"><strong data-monthly-minutes>00</strong><span>min</span></span>
						<span class="bbb-monthly-teaser__timerUnit"><strong data-monthly-seconds>00</strong><span>sec</span></span>
					</div>
				</div>
			<?php endif; ?>
			<p class="bbb-monthly-teaser__eyebrow">partial for free, FULL for paid</p>
			<h2 id="bbb-monthly-teaser-title"><?php echo esc_html($theme_label); ?>: midnight summer</h2>
			<p>peek at the <?php echo esc_html($is_current ? 'current' : 'next'); ?> monthly theme page: printable kindle inserts, wallpapers, a calendar, review template, and the whole after-dark summer mood.</p>
			<div class="bbb-monthly-teaser__actions" aria-label="Monthly theme actions">
				<a class="bbb-monthly-teaser__button bbb-monthly-teaser__button--secondary" href="<?php echo esc_url($theme_url); ?>"><?php echo esc_html($is_current ? 'open the theme' : 'preview the theme'); ?></a>
				<a class="bbb-monthly-teaser__button bbb-monthly-teaser__button--primary" href="<?php echo esc_url($subscribe_url); ?>" target="_blank" rel="noopener">subscribe to get the goods</a>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>
