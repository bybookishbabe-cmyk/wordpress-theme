<?php
/**
 * Homepage book boyfriend recommendation rotator.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_home_boyfriend_field')) {
	function bbb_home_boyfriend_field(int $post_id, string $key, $default = '') {
		$map = array(
			'boyfriend_name' => array('boyfriend_name', 'sss_boyfriend_name', '_bbb_boyfriend_name'),
			'boyfriend_type' => array('boyfriend_type', 'sss_boyfriend_type', '_bbb_boyfriend_type'),
		);

		foreach (($map[$key] ?? array($key)) as $candidate) {
			$value = get_post_meta($post_id, $candidate, true);
			if ('' !== $value && null !== $value) {
				return $value;
			}

			if (function_exists('get_field')) {
				$field = get_field($candidate, $post_id);
				if ('' !== $field && null !== $field && false !== $field) {
					return $field;
				}
			}
		}

		return $default;
	}
}

if (!function_exists('bbb_home_boyfriend_shelf_key')) {
	function bbb_home_boyfriend_shelf_key(int $post_id): string {
		$post_type = get_post_type($post_id);
		$taxonomy  = 'bbb_book' === $post_type ? 'bbb_shelf' : 'sss_shelf';
		$terms     = taxonomy_exists($taxonomy) ? get_the_terms($post_id, $taxonomy) : array();

		if ($terms && !is_wp_error($terms)) {
			return (string) $terms[0]->slug;
		}

		$meta = get_post_meta($post_id, 'sss_shelf', true);
		if ('' === $meta || null === $meta) {
			$meta = get_post_meta($post_id, '_bbb_shelf_handle', true);
		}

		if (is_array($meta)) {
			$meta = $meta['slug'] ?? $meta['handle'] ?? $meta['name'] ?? '';
		}

		return sanitize_title((string) $meta);
	}
}

if (!function_exists('bbb_home_boyfriend_book_is_visible')) {
	function bbb_home_boyfriend_book_is_visible(int $post_id): bool {
		if (function_exists('bbb_is_book_visible') && 'bbb_book' === get_post_type($post_id)) {
			return bbb_is_book_visible($post_id);
		}

		if (function_exists('bbb_book_is_publicly_visible')) {
			return bbb_book_is_publicly_visible($post_id);
		}

		return 'publish' === get_post_status($post_id);
	}
}

if (!function_exists('bbb_home_boyfriend_render_card')) {
	function bbb_home_boyfriend_render_card(int $post_id): string {
		ob_start();
		bbb_render_component('sss-book-card', array('book' => $post_id, 'mini' => true));
		return trim((string) ob_get_clean());
	}
}

$post_types = array_values(
	array_filter(
		array('bbb_book', 'sss_book'),
		static fn(string $post_type): bool => post_type_exists($post_type)
	)
);

if (!$post_types) {
	return;
}

$books = get_posts(
	array(
		'post_type'        => $post_types,
		'post_status'      => 'publish',
		'posts_per_page'   => 80,
		'orderby'          => 'date',
		'order'            => 'DESC',
		'suppress_filters' => false,
	)
);

$visible_books = array_values(
	array_filter(
		$books,
		static fn(WP_Post $book): bool => bbb_home_boyfriend_book_is_visible((int) $book->ID)
	)
);

$slides     = array();
$max_slides = 4;

foreach ($visible_books as $lead_book) {
	$lead_id        = (int) $lead_book->ID;
	$boyfriend_name = trim((string) bbb_home_boyfriend_field($lead_id, 'boyfriend_name', ''));
	$boyfriend_type = trim((string) bbb_home_boyfriend_field($lead_id, 'boyfriend_type', ''));

	if ('' === $boyfriend_name || '' === $boyfriend_type) {
		continue;
	}

	$lead_card = bbb_home_boyfriend_render_card($lead_id);
	if ('' === $lead_card) {
		continue;
	}

	$lead_shelf    = bbb_home_boyfriend_shelf_key($lead_id);
	$related_cards = array();

	foreach ($visible_books as $related_book) {
		$related_id = (int) $related_book->ID;
		if ($related_id === $lead_id) {
			continue;
		}

		$related_type = trim((string) bbb_home_boyfriend_field($related_id, 'boyfriend_type', ''));
		if ('' === $related_type) {
			continue;
		}

		$related_shelf = bbb_home_boyfriend_shelf_key($related_id);
		$is_match      = strcasecmp($related_type, $boyfriend_type) === 0 || ('' !== $lead_shelf && $lead_shelf === $related_shelf);

		if (!$is_match) {
			continue;
		}

		$related_card = bbb_home_boyfriend_render_card($related_id);
		if ('' === $related_card) {
			continue;
		}

		$related_cards[] = $related_card;

		if (2 === count($related_cards)) {
			break;
		}
	}

	if (!$related_cards) {
		continue;
	}

	$slides[] = array(
		'book'           => $lead_book,
		'boyfriend_name' => $boyfriend_name,
		'boyfriend_type' => $boyfriend_type,
		'lead_card'      => $lead_card,
		'related_cards'  => $related_cards,
	);

	if (count($slides) >= $max_slides) {
		break;
	}
}

if (!$slides) {
	return;
}
?>
<section class="bbb-boyfriends" data-sss-lib="public">
	<div class="bbb-boyfriends__inner">
		<div class="bbb-boyfriends__head">
			<p class="bbb-boyfriends__kicker">reader chemistry</p>
			<h2 class="bbb-boyfriends__title">for the reader who loves one book boyfriend too much</h2>
			<p class="bbb-boyfriends__sub">the lead obsession, then two more books with dangerously similar energy.</p>
		</div>

		<div class="bbb-boyfriends__rotator" id="bbbBoyfriendRotator">
			<?php foreach ($slides as $index => $slide) : ?>
				<div class="bbb-boyfriends__slide<?php echo 0 === $index ? ' is-active' : ''; ?>" data-boyfriend-slide>
					<div class="bbb-boyfriends__label">
						if <span><?php echo esc_html($slide['boyfriend_name']); ?></span> is your type
					</div>

					<div class="bbb-boyfriends__type">
						<?php echo esc_html(strtolower($slide['boyfriend_type'])); ?> energy, with a dangerously familiar pull.
					</div>

					<div class="bbb-boyfriends__row">
						<div class="bbb-boyfriends__lead">
							<?php echo $slide['lead_card']; ?>
						</div>

						<div class="bbb-boyfriends__arrow" aria-hidden="true">&rarr;</div>

						<div class="bbb-boyfriends__similar">
							<?php foreach ($slide['related_cards'] as $related_index => $related_card) : ?>
								<div class="bbb-boyfriends__similarItem<?php echo 0 === $related_index ? ' bbb-boyfriends__similarItem--wiggle' : ''; ?>">
									<?php echo $related_card; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="bbb-boyfriends__meta">
						quietly curated for the reader who fell for <?php echo esc_html($slide['boyfriend_name']); ?> and gives <?php echo esc_html(strtolower($slide['boyfriend_type'])); ?> energy.
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<?php bbb_render_component('sss-library-modal'); ?>
</section>

<style>
	.bbb-boyfriends{
		padding:0 20px 56px;
		background:#0b0b0b;
		color:#f6f6f6;
	}
	.bbb-boyfriends__inner{
		max-width:1100px;
		margin:0 auto;
	}
	.bbb-boyfriends__head{
		max-width:720px;
		margin-bottom:24px;
	}
	.bbb-boyfriends__kicker{
		font-size:11px;
		letter-spacing:.18em;
		text-transform:uppercase;
		color:rgba(246,246,246,.56);
	}
	.bbb-boyfriends__title{
		margin-top:8px;
		font-size:clamp(2.4rem, 4vw, 3.4rem);
		text-transform:lowercase;
	}
	.bbb-boyfriends__sub{
		margin-top:8px;
		font-size:14px;
		line-height:1.7;
		color:rgba(246,246,246,.7);
	}
	.bbb-boyfriends__rotator{
		position:relative;
		min-height:360px;
	}
	.bbb-boyfriends__slide{
		display:none;
		padding:24px;
		border-radius:22px;
		border:1px solid rgba(255,255,255,.1);
		background:rgba(255,255,255,.03);
		box-shadow:0 20px 48px rgba(0,0,0,.24);
	}
	.bbb-boyfriends__slide.is-active{
		display:block;
		animation:bbbBoyfriendFade .45s ease;
	}
	.bbb-boyfriends__label{
		margin-bottom:8px;
		font-size:clamp(1.9rem, 2.6vw, 2.5rem);
		line-height:1.2;
		text-transform:lowercase;
	}
	.bbb-boyfriends__label span{
		color:#ff8ac7;
		font-family:'Kaushan Script', cursive;
		font-size:1.15em;
		font-weight:400;
		letter-spacing:.01em;
		display:inline-block;
		padding:0 .06em;
	}
	.bbb-boyfriends__type{
		margin-bottom:18px;
		font-size:13px;
		letter-spacing:.08em;
		text-transform:uppercase;
		color:rgba(246,246,246,.56);
	}
	.bbb-boyfriends__row{
		display:grid;
		grid-template-columns:minmax(0, 168px) 40px minmax(0, 360px);
		gap:14px;
		align-items:center;
	}
	.bbb-boyfriends__lead .sss-lib__book,
	.bbb-boyfriends__similarItem .sss-lib__book{
		width:100%;
	}
	.bbb-boyfriends__lead .sss-lib__coverWrap{
		border-radius:16px;
	}
	.bbb-boyfriends__lead .sss-lib__cover{
		aspect-ratio:2/3;
		object-fit:cover;
	}
	.bbb-boyfriends__arrow{
		display:flex;
		align-items:center;
		justify-content:center;
		width:40px;
		height:40px;
		border-radius:999px;
		border:1px solid rgba(255,138,199,.22);
		color:#ff8ac7;
		font-size:20px;
		background:rgba(255,138,199,.05);
		animation:bbbArrowNudge 3.2s ease-in-out infinite;
	}
	.bbb-boyfriends__similar{
		display:grid;
		grid-template-columns:repeat(2, minmax(0, 1fr));
		gap:10px;
		max-width:320px;
	}
	.bbb-boyfriends__similar .sss-lib__coverWrap{
		border-radius:14px;
	}
	.bbb-boyfriends__similar .sss-lib__cover{
		aspect-ratio:2/3;
		object-fit:cover;
	}
	.bbb-boyfriends__similarItem--wiggle{
		animation:bbbBoyfriendWiggle 5.5s ease-in-out infinite;
		animation-delay:1.2s;
	}
	.bbb-boyfriends__meta{
		margin-top:14px;
		font-size:13px;
		line-height:1.65;
		color:rgba(246,246,246,.64);
		max-width:62ch;
	}
	@keyframes bbbArrowNudge{
		0%, 100% { transform:translateX(0); }
		50% { transform:translateX(3px); }
	}
	@keyframes bbbBoyfriendWiggle{
		0%, 100% { transform:rotate(0deg) translateY(0); }
		8% { transform:rotate(-1.2deg) translateY(-1px); }
		16% { transform:rotate(1deg) translateY(0); }
		24% { transform:rotate(0deg) translateY(0); }
	}
	@keyframes bbbBoyfriendFade{
		from{ opacity:0; transform:translateY(8px); }
		to{ opacity:1; transform:translateY(0); }
	}
	@media(max-width: 749px){
		.bbb-boyfriends{
			padding:0 16px 42px;
		}
		.bbb-boyfriends__rotator{
			min-height:auto;
		}
		.bbb-boyfriends__slide{
			padding:18px 16px;
			border-radius:18px;
		}
		.bbb-boyfriends__row{
			display:flex;
			align-items:center;
			gap:10px;
			overflow-x:auto;
			overflow-y:hidden;
			padding-bottom:6px;
			scroll-snap-type:x proximity;
			-webkit-overflow-scrolling:touch;
		}
		.bbb-boyfriends__arrow{
			margin:0;
			flex:0 0 auto;
			width:30px;
			height:30px;
			font-size:16px;
			transform:none;
			animation:bbbArrowNudge 4.2s ease-in-out infinite;
		}
		.bbb-boyfriends__lead{
			flex:0 0 124px;
			scroll-snap-align:start;
		}
		.bbb-boyfriends__similar{
			display:flex;
			align-items:flex-start;
			gap:10px;
			max-width:none;
			min-height:0;
			padding-left:0;
			flex:0 0 auto;
		}
		.bbb-boyfriends__similarItem{
			width:92px;
			flex:0 0 auto;
			scroll-snap-align:start;
		}
		.bbb-boyfriends__similarItem:last-child{
			transform:none;
		}
		.bbb-boyfriends__similarItem .sss-lib__under,
		.bbb-boyfriends__lead .sss-lib__under{
			display:none;
		}
		.bbb-boyfriends__lead .sss-lib__coverWrap,
		.bbb-boyfriends__similarItem .sss-lib__coverWrap{
			border-radius:12px;
		}
		.bbb-boyfriends__row::-webkit-scrollbar{
			height:4px;
		}
		.bbb-boyfriends__row::-webkit-scrollbar-thumb{
			background:rgba(255,255,255,.18);
			border-radius:999px;
		}
	}
</style>

<script>
	(function() {
		var root = document.getElementById('bbbBoyfriendRotator');
		if (!root) return;

		var slides = Array.from(root.querySelectorAll('[data-boyfriend-slide]'));
		if (slides.length < 2) return;

		var index = 0;
		var timer = null;
		var paused = false;

		function show(nextIndex) {
			slides.forEach(function(slide, slideIndex) {
				slide.classList.toggle('is-active', slideIndex === nextIndex);
			});
			index = nextIndex;
		}

		function next() {
			if (paused) return;
			show((index + 1) % slides.length);
		}

		function start() {
			if (timer) window.clearInterval(timer);
			timer = window.setInterval(next, 18000);
		}

		function stopRotation() {
			paused = true;
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		}

		root.addEventListener('mouseenter', function() {
			paused = true;
		});

		root.addEventListener('mouseleave', function() {
			paused = false;
		});

		root.addEventListener('focusin', function() {
			stopRotation();
		});

		root.addEventListener('focusout', function() {
			paused = false;
		});

		root.addEventListener('pointerdown', function() {
			stopRotation();
		});

		root.addEventListener('click', function() {
			stopRotation();
		});

		start();
	})();
</script>
