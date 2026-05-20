<?php
/**
 * Paid Society Library layer for the main Library page.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$books        = array_values(array_filter($args['books'] ?? array(), static fn($book): bool => $book instanceof WP_Post));
$public_books = array_values(array_filter($args['public_books'] ?? array(), static fn($book): bool => $book instanceof WP_Post));
$is_society   = !empty($args['is_society']);

$private_books = array_values(
	array_filter(
		$books,
		static fn(WP_Post $book): bool => function_exists('sss_book_is_private') && sss_book_is_private($book->ID)
	)
);

$finder_books = array_map(
	static function (WP_Post $book): array {
		$data = sss_book_data($book);

		return array(
			'handle' => $data['handle'],
			'title'  => $data['title'],
			'author' => $data['author'],
			'cover'  => $data['cover'],
			'shelf'  => $data['shelf'],
			'tropes' => array_values(array_filter(array_column($data['tropes'], 'name'))),
			'why'    => $data['why'],
			'mini'   => $data['mini'],
		);
	},
	$public_books
);

$join_url  = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$login_url = wp_login_url(get_permalink());
?>
<section class="sss-lib__societyLayer" id="society-library">
	<div class="sss-lib__societyLayerHead">
		<p class="sss-lib__archiveKicker">society library</p>
		<h2 class="sss-lib__archiveTitle">the private reader layer</h2>
		<p class="sss-lib__archiveSub">book matchmaker plus the private shelf, tucked into the main library for paid members.</p>
	</div>

	<?php if ($is_society) : ?>
		<div class="sss-lib__finder" id="sssReadFinder">
			<div class="sss-lib__finderHead">
				<p class="sss-lib__finderKicker">book matchmaker</p>
				<h3 class="sss-lib__finderTitle">find your next read</h3>
				<p class="sss-lib__finderSub">Choose a shelf, add a trope, and I will pull a recommendation from the library.</p>
			</div>

			<div class="sss-lib__finderGrid">
				<label class="sss-lib__finderField" data-finder-step="1">
					<span>start with a shelf</span>
					<select id="sssFinderShelf"><option value="">choose a genre</option></select>
				</label>
				<label class="sss-lib__finderField" data-finder-step="2">
					<span>pick the main trope</span>
					<select id="sssFinderTropeOne"><option value="">choose a trope</option></select>
				</label>
				<label class="sss-lib__finderField" data-finder-step="3">
					<span>add a second mood</span>
					<select id="sssFinderTropeTwo"><option value="">surprise me</option></select>
				</label>
			</div>

			<div class="sss-lib__finderActions">
				<button id="sssFinderSubmit" class="sss-lib__finderBtn" type="button" disabled>find my match</button>
			</div>

			<div class="sss-lib__finderResult" id="sssFinderResult" hidden>
				<div class="sss-lib__finderResultCard">
					<button class="sss-lib__finderCoverBtn" id="sssFinderOpen" type="button" hidden>
						<img class="sss-lib__finderCover" id="sssFinderCover" src="" alt="">
					</button>
					<div class="sss-lib__finderResultBody">
						<div class="sss-lib__finderResultLabel">your match</div>
						<div class="sss-lib__finderResultTitle" id="sssFinderResultTitle"></div>
						<div class="sss-lib__finderResultAuthor" id="sssFinderResultAuthor"></div>
						<div class="sss-lib__finderResultMeta" id="sssFinderResultMeta"></div>
						<div class="sss-lib__finderResultWhy" id="sssFinderResultWhy"></div>
						<div class="sss-lib__finderResultActions">
							<button class="sss-lib__finderBtn" id="sssFinderRead" type="button" hidden>read this mood</button>
							<button class="sss-lib__finderBtn sss-lib__finderBtn--ghost" id="sssFinderRetry" type="button" hidden>try another</button>
						</div>
						<div class="sss-lib__finderResultNote" id="sssFinderResultNote"></div>
					</div>
				</div>
			</div>
		</div>

		<script type="application/json" id="sssFinderData"><?php echo wp_json_encode($finder_books); ?></script>

		<?php if ($private_books) : ?>
			<section class="sss-lib__shelf sss-lib__shelf--private" id="private-shelf">
				<div class="sss-lib__shelfHead">
					<div class="sss-lib__privateKicker">members only</div>
					<h3 class="sss-lib__shelfTitle">the private shelf</h3>
					<p class="sss-lib__shelfDesc">member-only books, private notes, and the recs I keep tucked inside the society.</p>
				</div>
				<div class="sss-lib__shelfRow">
					<?php foreach ($private_books as $book) : ?>
						<?php get_template_part('template-parts/library/book-card', null, array('post' => $book, 'mini' => true)); ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
	<?php else : ?>
		<div class="sss-lib__societyLocked">
			<div>
				<p class="sss-lib__finderKicker">paid member access</p>
				<h3 class="sss-lib__finderTitle">book matchmaker + private shelf</h3>
				<p class="sss-lib__finderSub">Paid members unlock the private shelf and the library matchmaker here, without leaving the main Library page.</p>
			</div>
			<div class="sss-lib__societyLockedActions">
				<a class="sss-lib__finderBtn" href="<?php echo esc_url($join_url); ?>">join the society</a>
				<a class="sss-lib__finderBtn sss-lib__finderBtn--ghost" href="<?php echo esc_url($login_url); ?>">log in</a>
			</div>
		</div>
	<?php endif; ?>
</section>
