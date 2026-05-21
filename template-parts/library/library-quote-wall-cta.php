<?php
/**
 * Quote wall CTA for the main Library page.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$is_society = !empty($args['is_society']);
$quote_url  = function_exists('bbb_page_url') ? bbb_page_url('quote-wall') : home_url('/quote-wall/');

$tiles = array(
	array(
		'eyebrow' => 'save',
		'title'   => 'favorite the fatal lines',
		'copy'    => 'keep the quotes you want to come back to when the book hangover hits.',
	),
	array(
		'eyebrow' => 'browse',
		'title'   => 'wander by book mood',
		'copy'    => 'scan the little quote cards and let the next read choose itself.',
	),
	array(
		'eyebrow' => 'return',
		'title'   => 'jump back to the book',
		'copy'    => 'each line points back to its library card when you need the full rec.',
	),
);
?>
<a class="sss-lib__quoteCta" id="quote-wall-preview" href="<?php echo esc_url($quote_url); ?>" aria-label="<?php echo esc_attr($is_society ? 'open quote library' : 'preview quote library'); ?>">
	<div class="sss-lib__quoteCtaCopy">
		<p class="sss-lib__archiveKicker">quote library</p>
		<h2 class="sss-lib__archiveTitle">lines worth reopening.</h2>
		<p class="sss-lib__archiveSub">
			<?php echo esc_html($is_society ? 'a private little archive of the lines worth keeping.' : 'a peek at the softer, sharper side of the library.'); ?>
		</p>
		<span class="sss-lib__quoteCtaBtn">
			<?php echo esc_html($is_society ? 'open quote wall' : 'preview quote wall'); ?>
		</span>
	</div>
	<div class="sss-lib__quoteCuteGrid" aria-hidden="true">
		<?php foreach ($tiles as $index => $tile) : ?>
			<div class="sss-lib__quoteCuteCard">
				<span><?php echo esc_html('0' . (string) ($index + 1)); ?></span>
				<p><?php echo esc_html($tile['eyebrow']); ?></p>
				<h3><?php echo esc_html($tile['title']); ?></h3>
				<small><?php echo esc_html($tile['copy']); ?></small>
			</div>
		<?php endforeach; ?>
	</div>
</a>
