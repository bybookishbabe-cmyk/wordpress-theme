<?php
declare(strict_types=1);

$settings = array(
	'kicker' => bbb_get_field('kicker', get_the_ID(), 'official library'),
	'title' => bbb_get_field('title', get_the_ID(), 'the romance library'),
	'subtext' => bbb_get_field('subtext', get_the_ID(), 'the official collection of romance books curated and catalogued by the smut and sentiment society.'),
);
?>
<section class="sss-lib sss-lib--public" data-sss-lib="public">
	<div class="sss-lib__wrap">
		<header class="sss-lib__head">
			<p class="sss-lib__kicker"><?php echo esc_html((string) $settings['kicker']); ?></p>
			<h1 class="sss-lib__title"><?php echo esc_html((string) $settings['title']); ?></h1>
			<p class="sss-lib__seoLine">made for romance readers by a romance reader.</p>
			<p class="sss-lib__sub"><?php echo esc_html((string) $settings['subtext']); ?></p>
			<a class="sss-lib__kuLink" href="https://amzn.to/4uZ8Y3a" target="_blank" rel="noopener">on a kindle kick? try kindle unlimited →</a>
		</header>
		<div class="sss-lib__societyInviteCard">
			<div class="sss-lib__societyInviteKicker">the private layer</div>
			<div class="sss-lib__societyInviteTitle">join the society for the weekly recommendation</div>
			<a href="https://thesmutandsentimentsociety.substack.com/subscribe" class="sss-lib__societyInviteBtn">enter the society</a>
		</div>
		<?php bbb_render_component('trending-shelf'); ?>
		<nav class="sss-lib__jumpNav">
			<div class="sss-lib__jumpTitle">choose where to begin</div>
			<div class="sss-lib__jumpLinks">
				<a href="#sssMyShelfSection">📚 your bookshelf</a>
				<a href="#society-classics">👑 classics</a>
				<a href="<?php echo esc_url(bbb_resolve_page_url('series-reading-orders')); ?>">🔗 series</a>
				<a href="#starter-pack">✨ start here</a>
				<a href="#monthly">📅 books of the month</a>
				<a href="#moods">🖤 trope shelves</a>
				<a href="#archive">🗂️ full library</a>
			</div>
		</nav>
		<a class="sss-lib__spiceTease" href="<?php echo esc_url(bbb_resolve_page_url('romance-books-by-spice-level')); ?>">browse by spice →</a>
		<div class="sss-lib__myshelf" id="sssMyShelfSection">
			<div class="sss-lib__myshelfActions">
				<button id="sssExportNotes" type="button">copy list</button>
				<button id="sssEmailShelf" type="button">email to self</button>
			</div>
			<div class="sss-lib__grid" id="sssMyShelfGrid"></div>
		</div>
		<?php bbb_render_component('society-classics'); ?>
		<section id="starter-pack" class="sss-lib__starter">
			<p class="sss-lib__kicker">start here</p>
			<h2 class="sss-lib__sectionTitle">starter pack</h2>
			<div class="sss-lib__grid">
				<?php
				$starter = bbb_get_public_books_query(array('meta_key' => 'starter_pack', 'meta_value' => '1'));
				while ($starter->have_posts()) {
					$starter->the_post();
					bbb_render_component('sss-book-card', array('book' => get_post()));
				}
				wp_reset_postdata();
				?>
			</div>
		</section>
		<?php bbb_render_component('books-of-month'); ?>
		<?php bbb_render_component('mood-shelves'); ?>
		<?php bbb_render_component('full-archive'); ?>
	</div>
	<?php bbb_render_component('library-modal'); ?>
	<div id="sssNotepad" hidden></div>
	<div id="sssFloatingShare"></div>
	<div id="sssBackToTop"></div>
	<div id="sssTropePopup" class="sss-tropePopup" hidden></div>
</section>
