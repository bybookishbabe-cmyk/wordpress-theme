<?php
declare(strict_types=1);

$books = array_values(array_filter($args['books'] ?? array(), static fn($book): bool => $book instanceof WP_Post));
$current_month = wp_date('Y-m');
?>
<section class="sss-lib__archiveSection" data-archive-section>
	<div id="archive" class="sss-lib__archiveHead">
		<div class="sss-lib__archiveKicker">the archive</div>
		<h2 class="sss-lib__archiveTitle">full library</h2>
		<div class="sss-lib__archiveSub">every book i've ever recommended - shelved, annotated, unforgettable.</div>
	</div>

	<div class="sss-lib__ranker">
		<div class="sss-lib__rankerTitle">build your obsession</div>

		<div class="sss-lib__rankerControls">
			<div class="sss-lib__rankGroup">
				<label>spice</label>
				<input type="range" min="0" max="5" value="0" data-rank="spice">
				<div class="sss-lib__rankValue" data-rank-value="spice">any</div>
			</div>

			<div class="sss-lib__rankGroup">
				<label>darkness</label>
				<input type="range" min="0" max="5" value="0" data-rank="darkness">
				<div class="sss-lib__rankValue" data-rank-value="darkness">any</div>
			</div>

			<div class="sss-lib__rankGroup">
				<label>tension</label>
				<input type="range" min="0" max="5" value="0" data-rank="tension">
				<div class="sss-lib__rankValue" data-rank-value="tension">any</div>
			</div>

			<div class="sss-lib__rankGroup">
				<label>emotional damage</label>
				<input type="range" min="0" max="5" value="0" data-rank="damage">
				<div class="sss-lib__rankValue" data-rank-value="damage">any</div>
			</div>
		</div>

		<div class="sss-lib__searchWrap sss-lib__searchWrap--obsession">
			<input
				type="text"
				id="sssSearchInput"
				class="sss-lib__searchInput"
				placeholder="search by trope, keyword, boyfriend type..."
				autocomplete="off"
			>
		</div>
	</div>

	<div class="sss-lib__grid" id="sssArchiveGrid">
		<?php foreach ($books as $book) : ?>
			<?php
			$data = sss_book_data($book);
			if (($data['featured_month'] ?? '') === $current_month) {
				continue;
			}
			?>
			<?php get_template_part('template-parts/library/book-card', null, array('post' => $book)); ?>
		<?php endforeach; ?>
	</div>
</section>
