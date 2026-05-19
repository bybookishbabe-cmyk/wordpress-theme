<?php
/**
 * Homepage connect cards.
 *
 * @package ByBookishBabeShopifyPort
 */

$cards = array(
	array(
		'type'     => 'tiktok',
		'label'    => 'tiktok',
		'handle'   => '@bybookishbabe',
		'url'      => 'https://www.tiktok.com/@bybookishbabe',
		'delay'    => '.45s',
		'rows'     => array('feed' => 'daily', 'posts' => 'book recs · reading updates · library chaos', 'good for' => 'fast takes and reader spirals'),
		'icon'     => '<svg viewBox="0 0 24 24" class="libcard__ico" aria-hidden="true"><path fill="currentColor" d="M21 8.25a6.75 6.75 0 0 1-4.5-1.694v6.706A6.264 6.264 0 1 1 9.75 7.05v2.496a3.768 3.768 0 1 0 2.25 3.451V3h2.25a4.5 4.5 0 0 0 4.5 4.5z"/></svg>',
	),
	array(
		'type'     => 'substack',
		'label'    => 'substack',
		'handle'   => 'the smut & sentiment society',
		'url'      => 'https://thesmutandsentimentsociety.substack.com/subscribe',
		'delay'    => '.82s',
		'rows'     => array('tier' => 'free or society', 'inside' => 'one curated romance recommendation', 'extras' => 'private notes · polls · extra reader bits'),
		'icon'     => '<svg viewBox="0 0 24 24" class="libcard__ico" aria-hidden="true"><path fill="currentColor" d="M4 4h16v3H4V4zm0 4h16v3H4V8zm0 4h16v8L12 16 4 20v-8z"/></svg>',
	),
	array(
		'type'     => 'instagram',
		'label'    => 'instagram',
		'handle'   => '@bybookishbabe',
		'url'      => 'https://www.instagram.com/bybookishbabe/',
		'delay'    => '1.19s',
		'rows'     => array('grid' => 'shelf styling · current reads · pretty proof', 'focus' => 'books, mood, and newsletter-adjacent things', 'good for' => 'slower reader life updates'),
		'icon'     => '<svg viewBox="0 0 24 24" class="libcard__ico" aria-hidden="true"><path fill="currentColor" d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7zm5 3.5a5.5 5.5 0 1 1 0 11.001A5.5 5.5 0 0 1 12 7.5zm0 2a3.5 3.5 0 1 0 .001 7.001A3.5 3.5 0 0 0 12 9.5zm5.75-.75a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5z"/></svg>',
	),
);
?>
<section id="bbb-connect-home" class="bbb-connect-cards" data-bbb-connect>
	<div class="bbb-connect-cards__inner">
		<header class="bbb-connect-cards__header">
			<h2 class="bbb-connect-cards__title">
				<span class="bbb-lets">come</span>
				<span class="bbb-word">closer</span>
			</h2>
			<p class="bbb-connect-cards__sub">
				book recs, newsletter notes, and all the reader-life chaos in the places i actually post.
			</p>
		</header>

		<div class="bbb-card-wrap">
			<div class="bbb-card-row" data-swipe-row>
				<?php foreach ($cards as $card) : ?>
					<a class="libcard<?php echo 'substack' === $card['type'] ? ' is-substack' : ''; ?>" href="<?php echo esc_url($card['url']); ?>" target="_blank" rel="noopener" style="--bbb-card-delay: <?php echo esc_attr($card['delay']); ?>;">
						<div class="libcard__paper">
							<div class="libcard__hole"></div>
							<div class="libcard__head">
								<span class="libcard__platform">
									<?php echo $card['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php echo esc_html($card['label']); ?>
								</span>
								<span class="libcard__handle"><?php echo esc_html($card['handle']); ?></span>
							</div>
							<div class="libcard__body">
								<?php foreach ($card['rows'] as $label => $value) : ?>
									<div class="libcard__row"><span><?php echo esc_html($label); ?></span><span><?php echo esc_html($value); ?></span></div>
								<?php endforeach; ?>
							</div>
						</div>
					</a>
				<?php endforeach; ?>
			</div>

			<div class="bbb-swipe-hint" aria-hidden="true">
				<span class="bbb-dot"></span>
				<span class="bbb-dot"></span>
				<span class="bbb-dot"></span>
				<span class="bbb-hint-text">swipe me</span>
			</div>
		</div>
	</div>
</section>
