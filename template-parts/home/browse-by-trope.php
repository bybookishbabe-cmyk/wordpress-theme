<?php
/**
 * Browse by trope homepage section.
 *
 * @package ByBookishBabeShopifyPort
 */

$tropes = array(
	array('title' => 'sports romance', 'emoji' => '🏒', 'url' => home_url('/sports-romance-books/')),
	array('title' => 'enemies to lovers', 'emoji' => '⚔️', 'url' => home_url('/enemies-to-lovers/')),
	array('title' => 'slow burn', 'emoji' => '🕯️', 'url' => home_url('/slow-burn-books/')),
	array('title' => 'dark romance + morally gray men', 'emoji' => '💀', 'url' => home_url('/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you/')),
);
?>
<section class="bbb-tropes">
	<div class="bbb-tropes__inner">
		<a class="bbb-spiceCallout" href="<?php echo esc_url(home_url('/romance-books-by-spice-level/')); ?>">
			<span class="bbb-spiceCallout__rain" aria-hidden="true">
				<span>🌶</span><span>🌶</span><span>🌶</span><span>🌶</span><span>🌶</span>
			</span>
			<span class="bbb-spiceCallout__kicker">new way to browse</span>
			<span class="bbb-spiceCallout__text">want the exact spice level? browse romance by spice level →</span>
		</a>

		<div class="bbb-tropes__row">
			<div class="bbb-tropes__titleWrap">
				<p class="bbb-tropes__kicker">romance navigation</p>
				<h2 class="bbb-tropes__title">browse by trope</h2>
			</div>

			<div class="bbb-tropes__grid">
				<?php foreach ($tropes as $trope) : ?>
					<a href="<?php echo esc_url($trope['url']); ?>" class="bbb-trope-card" data-emoji="<?php echo esc_attr($trope['emoji']); ?>">
						<div class="bbb-emoji-rain"></div>
						<div class="bbb-trope-card__label">trope</div>
						<div class="bbb-trope-card__title"><?php echo esc_html($trope['title']); ?></div>
						<div class="bbb-trope-card__arrow">see books →</div>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
