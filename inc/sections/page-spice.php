<?php
declare(strict_types=1);

$query = new WP_Query(
	array(
		'post_type'      => 'sss_book',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'     => 'spice_level',
				'value'   => '0',
				'compare' => '>',
				'type'    => 'NUMERIC',
			),
			array(
				'relation' => 'OR',
				array('key' => 'hide_from_library', 'compare' => 'NOT EXISTS'),
				array('key' => 'hide_from_library', 'value' => '1', 'compare' => '!='),
			),
			array(
				'relation' => 'OR',
				array('key' => 'is_private', 'compare' => 'NOT EXISTS'),
				array('key' => 'is_private', 'value' => '1', 'compare' => '!='),
			),
		),
	)
);
?>
<section class="sss-lib sss-lib--spicePage" data-sss-lib="public">
	<div class="sss-lib__wrap">
		<header class="sss-tropeTop">
			<div class="sss-tropeTop__left">
				<p class="sss-lib__kicker">browse by spice</p>
				<h1 class="sss-lib__title">romance books by spice level</h1>
			</div>
			<div class="sss-tropeTop__right">
				<div class="sss-lib__societyInviteCard">
					<div class="sss-lib__societyInviteKicker">the private layer</div>
					<div class="sss-lib__societyInviteTitle">join the society for the weekly recommendation</div>
					<a href="https://thesmutandsentimentsociety.substack.com/subscribe" class="sss-lib__societyInviteBtn">enter the society</a>
				</div>
			</div>
		</header>
		<nav class="sss-spiceNav">
			<button class="sss-spiceNav__pill" type="button" data-spice-filter="1">🌶 barely there</button>
			<button class="sss-spiceNav__pill" type="button" data-spice-filter="2">🌶🌶 warming up</button>
			<button class="sss-spiceNav__pill" type="button" data-spice-filter="3">🌶🌶🌶 medium heat</button>
			<button class="sss-spiceNav__pill" type="button" data-spice-filter="4">🌶🌶🌶🌶 getting hot</button>
			<button class="sss-spiceNav__pill" type="button" data-spice-filter="5">🌶🌶🌶🌶🌶 five chili nights</button>
		</nav>
		<p class="sss-lib__spiceCount">showing <span id="sssSpiceCount">0</span> books</p>
		<div class="sss-lib__grid sss-lib__grid--spicePage" id="sssSpiceGrid">
			<?php
			while ($query->have_posts()) {
				$query->the_post();
				bbb_render_component('sss-book-card', array('book' => get_post()));
			}
			wp_reset_postdata();
			?>
		</div>
		<div class="sss-lib__spiceActions">
			<a href="<?php echo esc_url(bbb_resolve_page_url('romance-library')); ?>">← back to full library</a>
			<a href="https://thesmutandsentimentsociety.substack.com/subscribe">join the society →</a>
		</div>
	</div>
	<?php bbb_render_component('library-modal'); ?>
</section>
