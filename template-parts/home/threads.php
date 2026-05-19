<?php
/**
 * Threads homepage custom-liquid block.
 *
 * @package ByBookishBabeShopifyPort
 */

$threads_url = 'https://www.threads.net/@bybookishbabe';
$threads     = array(
	'sundays are for letting fiction ruin me a little &amp; pretending it’s self care.',
	'i don’t “wake up early” – i simply regain consciousness and reach for my kindle.',
	'decorating my kindle like it’s an altar to all the fictional men who will never text back.',
	'“just one more chapter” is how i time travel from 9pm to 2am in a single blink.',
);
?>
<div class="bbb-threads">
	<div class="bbb-threads__inner">
		<p class="bbb-threads__label">from the feed</p>
		<h2 class="bbb-threads__title">threads from the society</h2>
		<p class="bbb-threads__sub">
			reader brainrot, smutty confessions, and unhinged kindle devotion in real time.
		</p>

		<div class="bbb-threads__carousel" id="bbbThreadsCarousel">
			<?php foreach ($threads as $index => $thread) : ?>
				<a href="<?php echo esc_url($threads_url); ?>" target="_blank" class="bbb-thread-card<?php echo 0 === $index ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr((string) $index); ?>">
					<div class="bbb-thread-card__body">
						<?php echo wp_kses_post($thread); ?>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</div>
