<?php
/**
 * Homepage CTA for the Romance Release Radar.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$release_radar_url = function_exists('bbb_page_url') ? bbb_page_url('romance-release-radar') : home_url('/romance-release-radar/');
?>
<section class="bbb-release-radar-cta" aria-labelledby="bbb-release-radar-cta-title">
	<a class="bbb-release-radar-cta__inner" href="<?php echo esc_url($release_radar_url); ?>">
		<div class="bbb-release-radar-cta__copy">
			<p class="bbb-release-radar-cta__kicker">release radar</p>
			<h2 id="bbb-release-radar-cta-title">what's coming out soon. is it on your tbr?</h2>
			<span class="bbb-release-radar-cta__button">scan the radar</span>
		</div>
		<div class="bbb-release-radar-cta__visual" aria-hidden="true">
			<div class="bbb-release-radar-cta__disc">
				<span class="bbb-release-radar-cta__ring bbb-release-radar-cta__ring--outer"></span>
				<span class="bbb-release-radar-cta__ring bbb-release-radar-cta__ring--middle"></span>
				<span class="bbb-release-radar-cta__ring bbb-release-radar-cta__ring--inner"></span>
				<span class="bbb-release-radar-cta__sweep"></span>
				<span class="bbb-release-radar-cta__center"></span>
				<span class="bbb-release-radar-cta__blip bbb-release-radar-cta__blip--one"></span>
				<span class="bbb-release-radar-cta__blip bbb-release-radar-cta__blip--two"></span>
				<span class="bbb-release-radar-cta__blip bbb-release-radar-cta__blip--three"></span>
				<span class="bbb-release-radar-cta__blip bbb-release-radar-cta__blip--four"></span>
			</div>
		</div>
	</a>
</section>
