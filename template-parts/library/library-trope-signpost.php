<?php
/**
 * Trope signpost for the public library.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$default_signs = array(
	array(
		'label' => 'enemies to lovers',
		'slug'  => 'enemies-to-lovers',
		'emoji' => '⚔️',
	),
	array(
		'label' => 'slow burn',
		'slug'  => 'slow-burn-books',
		'emoji' => '🕯️',
	),
	array(
		'label' => 'fake dating',
		'slug'  => 'fake-dating-romance-books',
		'emoji' => '💌',
	),
	array(
		'label' => 'villain gets the girl',
		'slug'  => 'villain-gets-the-girl-books',
		'emoji' => '🗡️',
	),
	array(
		'label' => 'captor x captive',
		'slug'  => 'captor-x-captive-romance-books',
		'emoji' => '🔒',
	),
	array(
		'label' => 'touch her and die',
		'slug'  => 'touch-her-and-die-books',
		'emoji' => '🩸',
	),
	array(
		'label' => 'second chance',
		'slug'  => 'second-chance-romance-books',
		'emoji' => '⏳',
	),
);

$signs = array();

$trope_pages = get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 8,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'meta_query'     => array(
			array(
				'key'   => '_wp_page_template',
				'value' => 'page-trope.php',
			),
		),
	)
);

foreach ($trope_pages as $trope_page) {
	$label = function_exists('bbb_get_field') ? (string) bbb_get_field('trope_name', $trope_page->ID, '') : '';
	$label = '' !== trim($label) ? $label : get_the_title($trope_page);
	$url   = get_permalink($trope_page);

	if (!$url || '' === trim($label)) {
		continue;
	}

	$signs[$trope_page->post_name] = array(
		'label' => $label,
		'url'   => $url,
		'slug'  => $trope_page->post_name,
		'emoji' => '',
	);
}

foreach ($default_signs as $default_sign) {
	$slug = (string) $default_sign['slug'];

	if (isset($signs[$slug])) {
		continue;
	}

	$signs[$slug] = array(
		'label' => (string) $default_sign['label'],
		'url'   => function_exists('bbb_page_url') ? bbb_page_url($slug) : home_url('/' . $slug . '/'),
		'slug'  => $slug,
		'emoji' => (string) ($default_sign['emoji'] ?? ''),
	);
}

$signs = array_slice(array_values($signs), 0, 7);

if (!$signs) {
	return;
}
?>
<section class="sss-lib__tropeSignpost" aria-labelledby="sss-lib-trope-signpost-title">
	<div class="sss-lib__tropeSignpostCopy">
		<p class="sss-lib__tropeSignpostKicker">trope crossroads</p>
		<h2 class="sss-lib__tropeSignpostTitle" id="sss-lib-trope-signpost-title">choose your next shelf by trope</h2>
		<p class="sss-lib__tropeSignpostSub">tap a sign and wander straight into the books with that exact kind of tension.</p>
	</div>

	<div class="sss-lib__tropeLamp" aria-label="Trope page shortcuts">
		<svg
			class="sss-lib__tropeLampSvg"
			viewBox="0 0 140 520"
			aria-hidden="true"
			focusable="false"
			role="img"
		>
			<g class="sss-lib__tropeLampGlow">
				<path d="M70 20c18 0 34 12 39 30l8 32H23l8-32c5-18 21-30 39-30Z" />
				<path d="M34 82h72l-9 86H43L34 82Z" />
				<path d="M53 102h34l-5 48H58l-5-48Z" />
				<path d="M70 176v238" />
			</g>
			<g class="sss-lib__tropeLampLight">
				<path d="M55 96h30l-5 58H60z" />
				<ellipse cx="70" cy="127" rx="24" ry="34" />
			</g>
			<g class="sss-lib__tropeLampInk">
				<path d="M70 7v18" />
				<path d="M60 20c0-8 4-14 10-14s10 6 10 14" />
				<path d="M45 32c6-11 15-17 25-17s19 6 25 17" />
				<path d="M30 70c8-32 22-48 40-48s32 16 40 48" />
				<path d="M20 82h100" />
				<path d="M14 82l21-16h70l21 16" />
				<path d="M34 82h72l-9 86H43L34 82Z" />
				<path d="M46 91l8 77" />
				<path d="M94 91l-8 77" />
				<path d="M53 102h34l-5 48H58l-5-48Z" />
				<path d="M49 176h42" />
				<path d="M70 176v238" />
				<path d="M54 188c-20 10-19 29 0 39" />
				<path d="M86 188c20 10 19 29 0 39" />
				<path d="M52 224c-11 14-4 29 18 35" />
				<path d="M88 224c11 14 4 29-18 35" />
				<path d="M57 414h26" />
				<path d="M54 428h32" />
				<path d="M58 428c0-14 5-24 12-24s12 10 12 24" />
				<path d="M48 456h44" />
				<path d="M45 474h50" />
				<path d="M52 456c0-17 7-28 18-28s18 11 18 28" />
				<path d="M58 474h24v28H58z" />
				<path d="M44 502h52v12H44z" />
				<path d="M38 514h64" />
			</g>
		</svg>

		<nav class="sss-lib__tropeSigns" aria-label="Browse library by trope">
			<?php foreach ($signs as $index => $sign) : ?>
				<?php
				$side       = 0 === $index % 2 ? 'left' : 'right';
				$label      = (string) ($sign['label'] ?? '');
				$sign_slug  = (string) ($sign['slug'] ?? sanitize_title($label));
				$emoji_html = function_exists('bbb_trope_emoji_html') ? bbb_trope_emoji_html($label, (string) ($sign['emoji'] ?? ''), $sign_slug) : esc_html((string) ($sign['emoji'] ?? ''));
				?>
				<a
					class="sss-lib__tropeSign sss-lib__tropeSign--<?php echo esc_attr($side); ?>"
					href="<?php echo esc_url((string) $sign['url']); ?>"
					style="--sign-index: <?php echo esc_attr((string) $index); ?>;"
				>
					<span class="sss-lib__tropeSignEmoji"><?php echo wp_kses_post($emoji_html); ?></span>
					<span class="sss-lib__tropeSignLabel"><?php echo esc_html($label); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>
	</div>

	<a class="sss-lib__tropeSignpostCta" href="<?php echo esc_url(home_url('/romance-trope-dictionary/')); ?>">take me to all tropes <span aria-hidden="true">→</span></a>
</section>
