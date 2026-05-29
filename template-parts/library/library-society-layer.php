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
$mode         = (string) ($args['mode'] ?? 'all');
$show_private = in_array($mode, array('all', 'private_shelf'), true);
$show_match   = in_array($mode, array('all', 'matchmaker'), true);

$private_books = array_values(
	array_filter(
		$books,
		static fn(WP_Post $book): bool => function_exists('sss_book_is_private') && sss_book_is_private($book->ID)
	)
);

if (!function_exists('bbb_library_matchmaker_shelf_name')) {
	function bbb_library_matchmaker_shelf_name(WP_Post $book, array $data): string {
		$shelf = trim((string) ($data['shelf'] ?? ''));
		if ('' !== $shelf) {
			return $shelf;
		}

		foreach (array('bbb_shelf', 'sss_shelf') as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			$terms = get_the_terms($book->ID, $taxonomy);
			if ($terms && !is_wp_error($terms)) {
				$term = reset($terms);
				if ($term instanceof WP_Term && '' !== trim($term->name)) {
					return trim($term->name);
				}
			}
		}

		foreach (array('_bbb_shelf_name', 'sss_shelf') as $meta_key) {
			$meta = trim((string) get_post_meta($book->ID, $meta_key, true));
			if ('' !== $meta) {
				return $meta;
			}
		}

		return 'all romance';
	}
}

if (!function_exists('bbb_library_matchmaker_trope_names')) {
	function bbb_library_matchmaker_trope_names(WP_Post $book, array $data): array {
		$names = array();

		foreach ((array) ($data['tropes'] ?? array()) as $trope) {
			if (is_array($trope)) {
				$name = trim((string) ($trope['name'] ?? $trope['label'] ?? $trope['title'] ?? ''));
			} else {
				$name = trim((string) $trope);
			}

			if ('' !== $name) {
				$names[] = $name;
			}
		}

		foreach (array('bbb_trope', 'sss_trope') as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			$terms = get_the_terms($book->ID, $taxonomy);
			if (!$terms || is_wp_error($terms)) {
				continue;
			}

			foreach ($terms as $term) {
				if ($term instanceof WP_Term && '' !== trim($term->name)) {
					$names[] = trim($term->name);
				}
			}
		}

		return array_values(array_unique(array_filter($names)));
	}
}

$finder_books = array_map(
	static function (WP_Post $book): array {
		$data = sss_book_data($book);
		$shelf = bbb_library_matchmaker_shelf_name($book, $data);

		return array(
			'handle' => $data['handle'],
			'title'  => $data['title'],
			'author' => $data['author'],
			'cover'  => $data['cover'],
			'shelf'  => $shelf,
			'tropes' => bbb_library_matchmaker_trope_names($book, $data),
			'why'    => $data['why'],
			'mini'   => $data['mini'],
			'amazon' => $data['amazon'] ?? '',
			'bookshop' => $data['bookshop'] ?? '',
			'newsletter' => $data['newsletter'] ?? '',
			'spice'  => $data['spice'] ?? '',
			'darkness' => $data['darkness'] ?? '',
			'series' => $data['series_handle'] ?? '',
			'seriesName' => $data['series_name'] ?? '',
			'seriesNumber' => $data['series_number'] ?? '',
			'standalone' => !empty($data['standalone']) ? 'true' : 'false',
			'tension' => $data['tension'] ?? '',
			'damage' => $data['damage'] ?? '',
			'yearning' => $data['yearning'] ?? '',
			'boyfriend' => $data['boyfriend'] ?? '',
			'boyfriendName' => $data['boyfriend_name'] ?? '',
			'reread' => !empty($data['reread']) ? 'true' : 'false',
			'ku' => !empty($data['ku']) ? 'true' : 'false',
		);
	},
	$public_books
);

$finder_shelves = array_values(
	array_unique(
		array_filter(
			array_map(
				static fn(array $book): string => trim((string) ($book['shelf'] ?? '')),
				$finder_books
			),
			static fn(string $shelf): bool => '' !== $shelf && 'private shelf' !== strtolower($shelf)
		)
	)
);
natcasesort($finder_shelves);
$finder_shelves = array_values($finder_shelves);

$finder_tropes = array_values(
	array_unique(
		array_merge(
			...array_map(
				static fn(array $book): array => array_values(array_filter(array_map('strval', (array) ($book['tropes'] ?? array())))),
				$finder_books
			)
		)
	)
);
natcasesort($finder_tropes);
$finder_tropes = array_values($finder_tropes);

$join_url  = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$login_url = home_url('/account/');
?>
<section class="sss-lib__societyLayer sss-lib__societyLayer--<?php echo esc_attr(str_replace('_', '-', $mode)); ?>" id="<?php echo esc_attr($show_match ? 'society-matchmaker' : 'society-private-shelf'); ?>">
	<div class="sss-lib__societyLayerHead">
		<p class="sss-lib__archiveKicker">society library</p>
		<h2 class="sss-lib__archiveTitle"><?php echo esc_html($show_match ? 'book matchmaker' : 'the private shelf'); ?></h2>
		<p class="sss-lib__archiveSub">
			<?php echo esc_html($show_match ? 'Pick a shelf and trope, then let the library pull your next read.' : 'Member-only books, private notes, and the recs tucked inside the society.'); ?>
		</p>
	</div>

	<?php if ($is_society) : ?>
		<?php if ($show_match) : ?>
			<div class="sss-lib__finder" id="sssReadFinder">
			<div class="sss-lib__finderHead">
				<p class="sss-lib__finderKicker">book matchmaker</p>
				<h3 class="sss-lib__finderTitle">find your next read</h3>
				<p class="sss-lib__finderSub">choose a shelf, add a trope, and i will pull a recommendation from the library.</p>
			</div>

			<div class="sss-lib__finderGrid">
				<label class="sss-lib__finderField" data-finder-step="1">
					<span>start with a shelf</span>
					<select id="sssFinderShelf">
						<option value="">choose a shelf</option>
						<?php foreach ($finder_shelves as $shelf) : ?>
							<option value="<?php echo esc_attr($shelf); ?>"><?php echo esc_html($shelf); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="sss-lib__finderField" data-finder-step="2">
					<span>pick the main trope</span>
					<select id="sssFinderTropeOne">
						<option value="">choose a trope</option>
						<?php foreach ($finder_tropes as $trope) : ?>
							<option value="<?php echo esc_attr($trope); ?>"><?php echo esc_html($trope); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="sss-lib__finderField" data-finder-step="3">
					<span>add a second mood</span>
					<select id="sssFinderTropeTwo">
						<option value="">surprise me</option>
						<?php foreach ($finder_tropes as $trope) : ?>
							<option value="<?php echo esc_attr($trope); ?>"><?php echo esc_html($trope); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>

			<div class="sss-lib__finderActions">
				<button id="sssFinderSubmit" class="sss-lib__finderBtn" type="button" disabled>find my match</button>
			</div>

			<div class="sss-lib__finderResult" id="sssFinderResult" hidden>
				<div class="sss-lib__finderResultCard">
					<button class="sss-lib__finderCoverBtn" id="sssFinderOpen" type="button" hidden>
						<span class="sss-lib__heart sss-lib__finderHeart" id="sssFinderHeart" data-finder-heart role="button" aria-label="save to your bookshelf">
							<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
							<span class="sss-lib__heartLabel" data-heart-label>save</span>
						</span>
						<span class="sss-lib__seriesBadge sss-lib__finderSeriesBadge" id="sssFinderSeriesBadge" hidden></span>
						<span class="sss-lib__floatSpice sss-lib__finderSpice" id="sssFinderSpice" hidden></span>
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
		<?php endif; ?>

		<?php if ($show_private && $private_books) : ?>
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
			<div class="sss-lib__societyLockedPreview" aria-hidden="true">
				<?php if ($show_match) : ?>
					<div class="sss-lib__finder sss-lib__finder--lockedPreview">
						<div class="sss-lib__finderGrid">
							<div class="sss-lib__finderField">
								<span>start with a shelf</span>
								<div class="sss-lib__lockedSelect"><?php echo esc_html($finder_shelves[0] ?? 'dark romance'); ?></div>
							</div>
							<div class="sss-lib__finderField">
								<span>pick the main trope</span>
								<div class="sss-lib__lockedSelect"><?php echo esc_html($finder_tropes[0] ?? 'enemies to lovers'); ?></div>
							</div>
							<div class="sss-lib__finderField">
								<span>add a second mood</span>
								<div class="sss-lib__lockedSelect"><?php echo esc_html($finder_tropes[1] ?? 'obsession'); ?></div>
							</div>
						</div>
						<div class="sss-lib__finderResultCard">
							<?php
							$preview_book = $public_books[0] ?? null;
							$preview_data = $preview_book instanceof WP_Post ? sss_book_data($preview_book) : array();
							?>
							<div class="sss-lib__finderCoverBtn">
								<?php if (!empty($preview_data['cover'])) : ?>
									<img class="sss-lib__finderCover" src="<?php echo esc_url((string) $preview_data['cover']); ?>" alt="">
								<?php endif; ?>
							</div>
							<div class="sss-lib__finderResultBody">
								<div class="sss-lib__finderResultLabel">your match</div>
								<div class="sss-lib__finderResultTitle"><?php echo esc_html((string) ($preview_data['title'] ?? 'your next society read')); ?></div>
								<div class="sss-lib__finderResultAuthor"><?php echo esc_html(!empty($preview_data['author']) ? 'by ' . (string) $preview_data['author'] : 'curated by the society'); ?></div>
								<div class="sss-lib__finderResultMeta">shelf chemistry + trope match</div>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<?php if ($show_private && $private_books) : ?>
					<div class="sss-lib__shelfRow sss-lib__lockedPreviewBooks">
						<?php foreach (array_slice($private_books, 0, 5) as $book) : ?>
							<?php get_template_part('template-parts/library/book-card', null, array('post' => $book, 'mini' => true)); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div>
				<p class="sss-lib__finderKicker">paid member access</p>
				<h3 class="sss-lib__finderTitle"><?php echo esc_html($show_match ? 'book matchmaker' : 'the private shelf'); ?></h3>
				<p class="sss-lib__finderSub">
					<?php echo esc_html($show_match ? 'paid members can use the full library matchmaker here.' : 'paid members can open the private shelf here.'); ?>
				</p>
			</div>
			<div class="sss-lib__societyLockedActions">
				<a class="sss-lib__finderBtn" href="<?php echo esc_url($join_url); ?>">join the society</a>
				<a class="sss-lib__finderBtn sss-lib__finderBtn--ghost" href="<?php echo esc_url($login_url); ?>">log in</a>
			</div>
		</div>
	<?php endif; ?>
</section>
