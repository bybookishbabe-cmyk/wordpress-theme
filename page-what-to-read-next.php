<?php
/**
 * Shopify-compatible "what to read next" recommendation generator.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

wp_enqueue_style('bbb-what-to-read-next', get_theme_file_uri('assets/css/bbb-what-to-read-next.css'), array('bbb-sss-library'), wp_get_theme()->get('Version'));
wp_enqueue_script('bbb-what-to-read-next', get_theme_file_uri('assets/js/bbb-what-to-read-next.js'), array('bbb-sss-library'), wp_get_theme()->get('Version'), true);
get_header();

$books = array();
if (function_exists('bbb_books_like_all_visible_books') && function_exists('bbb_books_like_book_data')) {
	foreach (bbb_books_like_all_visible_books() as $book_post) {
		if (function_exists('bbb_book_is_private') && bbb_book_is_private($book_post->ID)) {
			continue;
		}
		$data = bbb_books_like_book_data($book_post->ID);
		$series_number = (int) ($data['series_number'] ?? 0);
		if ($series_number > 1 && empty($data['standalone'])) {
			continue;
		}
		$books[] = array(
			'handle'       => (string) ($data['handle'] ?? ''),
			'label'        => trim((string) ($data['title'] ?? '') . (!empty($data['author']) ? ' by ' . (string) $data['author'] : '')),
			'title'        => (string) ($data['title'] ?? ''),
			'author'       => (string) ($data['author'] ?? ''),
			'cover'        => (string) ($data['cover'] ?? ''),
			'why'          => wp_strip_all_tags((string) ($data['why'] ?? '')),
			'mini'         => wp_strip_all_tags((string) ($data['mini'] ?? '')),
			'shelf'        => (string) ($data['shelf']['name'] ?? ''),
			'shelfSlug'    => (string) ($data['shelf']['slug'] ?? ''),
			'spice'        => (int) ($data['spice'] ?? 0),
			'amazon'       => (string) ($data['amazon'] ?? ''),
			'bookshop'     => (string) ($data['bookshop'] ?? ''),
			'newsletter'   => (string) ($data['newsletter'] ?? ''),
			'tension'      => (string) ($data['tension'] ?? ''),
			'damage'       => (string) ($data['damage'] ?? ''),
			'darkness'     => (string) ($data['darkness'] ?? ''),
			'yearning'     => (string) ($data['yearning'] ?? ''),
			'boyfriend'    => (string) ($data['boyfriend'] ?? ''),
			'boyfriendName'=> (string) ($data['boyfriend_name'] ?? ''),
			'reread'       => (string) ($data['reread'] ?? ''),
			'ku'           => !empty($data['ku']) ? 'true' : 'false',
			'series'       => (string) ($data['series_handle'] ?? ''),
			'seriesName'   => (string) ($data['series_name'] ?? ''),
			'seriesNumber' => (string) ($data['series_number'] ?? ''),
			'url'          => home_url('/library/?book=' . rawurlencode((string) ($data['handle'] ?? ''))),
			'tropes'       => array_values(
				array_filter(
					array_map(
						static fn(array $trope): string => (string) ($trope['name'] ?? ''),
						(array) ($data['tropes'] ?? array())
					)
				)
			),
		);
	}
}
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="sss-lib bbb-next" id="bbb-next-wp" data-sss-lib="public">
		<div class="sss-lib__wrap bbb-next__wrap">
			<div class="sss-tropeTop bbb-next__top">
				<div class="sss-tropeTop__left">
					<header class="sss-trope__header bbb-next__hero">
						<div class="sss-trope__eyebrow">reader rec engine</div>
						<h1 class="sss-trope__title bbb-next__title">what to read next</h1>
						<p class="sss-trope__desc bbb-next__sub">pick a book from the library and I’ll pull three next-read options based on shelf chemistry, trope overlap, and spice mood.</p>
					</header>
				</div>

				<div class="sss-tropeTop__right">
					<div class="sss-tropeInvite bbb-next__societyInvite">
						<div class="sss-tropeInvite__kicker">the private layer</div>
						<div class="sss-tropeInvite__title">one romance recommendation. every sunday.</div>
						<div class="sss-tropeInvite__sub">morally gray men delivered straight to you</div>
						<a href="https://thesmutandsentimentsociety.substack.com/subscribe" class="sss-tropeInvite__btn" target="_blank" rel="noopener">join the society →</a>
					</div>
				</div>
			</div>

			<div class="sss-trope__divider"></div>

			<div class="bbb-next__panel">
				<a href="https://thesmutandsentimentsociety.substack.com/subscribe" class="bbb-next__mobileInvite" target="_blank" rel="noopener">
					<span class="bbb-next__mobileInviteCopy">the private layer: one romance recommendation every sunday</span>
					<span class="bbb-next__mobileInviteCta">join the society →</span>
				</a>

				<div class="bbb-next__picker">
					<span class="bbb-next__pickerLabel">Choose a book from the society library</span>
					<div class="bbb-next__pickerWrap">
						<button id="bbbNextPicker-wp" class="bbb-next__select bbb-next__selectBtn" type="button" data-next-picker-trigger aria-haspopup="dialog" aria-expanded="false">
							<span data-next-picker-label>pick a book to start</span>
						</button>
					</div>
				</div>

				<div class="bbb-next__source is-hidden" data-next-source hidden>
					<div class="bbb-next__sourceCoverWrap">
						<span class="sss-lib__heart" data-next-source-heart role="button" aria-label="save to your bookshelf">
							<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
							<span class="sss-lib__heartLabel" data-heart-label>save</span>
						</span>
						<div class="sss-lib__statusRibbon" data-next-source-ribbon hidden></div>
						<div class="sss-lib__floatSpice" data-next-source-spice hidden></div>
						<img class="bbb-next__sourceCover" data-next-source-cover alt="" loading="lazy">
					</div>
					<div class="bbb-next__sourceBody">
						<div class="bbb-next__sourceTop">
							<div class="bbb-next__sourceLabel">you picked</div>
							<button type="button" class="bbb-next__clearBtn" data-next-clear>clear</button>
						</div>
						<h2 class="bbb-next__sourceTitle" data-next-source-title></h2>
						<p class="bbb-next__sourceAuthor" data-next-source-author></p>
						<div class="bbb-next__sourceMeta" data-next-source-meta></div>
						<p class="bbb-next__sourceWhy" data-next-source-why></p>
					</div>
				</div>
			</div>

			<div class="bbb-next__results is-hidden" data-next-results hidden>
				<div class="bbb-next__resultsHead">
					<p class="bbb-next__resultsKicker">from me to you</p>
					<h2 class="bbb-next__resultsTitle">here’s where I’d send you next</h2>
					<p class="bbb-next__resultsSub">Start with the closest shelf twin, then move into the trope twin, then the spice-mood wildcard.</p>
					<button type="button" class="bbb-next__refreshBtn" data-next-refresh>refresh picks</button>
				</div>

				<div class="bbb-next__cards">
					<?php foreach (array(1 => 'closest match', 2 => 'similar vibes', 3 => 'maybe something new') as $index => $pill) : ?>
						<article class="bbb-next__card is-hidden" data-next-card="<?php echo esc_attr((string) $index); ?>" hidden>
							<div class="bbb-next__cardPill"><?php echo esc_html($pill); ?></div>
							<div class="bbb-next__cardMood" data-next-mood></div>
							<div class="bbb-next__cardInner">
								<div class="bbb-next__cardCoverWrap sss-lib__book" data-next-open data-book-preview role="button" tabindex="0" aria-label="open book details">
									<span class="sss-lib__heart" data-next-heart role="button" aria-label="save to your bookshelf">
										<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
										<span class="sss-lib__heartLabel" data-heart-label>save</span>
									</span>
									<div class="sss-lib__statusRibbon" data-next-ribbon hidden></div>
									<div class="sss-lib__floatSpice" data-next-spice hidden></div>
									<img class="bbb-next__cardCover" data-next-cover alt="" loading="lazy">
								</div>
								<div class="bbb-next__cardBody">
									<h3 class="bbb-next__cardTitle" data-next-title></h3>
									<p class="bbb-next__cardAuthor" data-next-author></p>
									<div class="bbb-next__cardMeta" data-next-meta></div>
									<p class="bbb-next__cardReason" data-next-reason></p>
									<div class="bbb-next__statusRow">
										<button type="button" class="bbb-next__statusCheck" data-next-status="read"><span class="bbb-next__statusBox" aria-hidden="true"></span><span>mark as read</span></button>
										<button type="button" class="bbb-next__statusCheck" data-next-status="tbr"><span class="bbb-next__statusBox" aria-hidden="true"></span><span>add to tbr</span></button>
									</div>
									<a class="bbb-next__cardLink" data-next-link href="#" hidden>go to my bookshelf →</a>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="sss-trope__actions bbb-next__actions">
				<a href="<?php echo esc_url(home_url('/library/')); ?>" class="sss-trope__actionLink">see the full romance library →</a>
				<a href="<?php echo esc_url(home_url('/reader-quizes/')); ?>" class="sss-trope__actionLink">find your fictional boyfriend →</a>
				<a href="<?php echo esc_url(home_url('/books-like/')); ?>" class="sss-trope__actionLink">browse books like x →</a>
				<a href="<?php echo esc_url(home_url('/romance-books-by-spice-level/')); ?>" class="sss-trope__actionLink">browse by spice →</a>
			</div>
		</div>

		<div class="bbb-next__searchModal" data-next-search-modal hidden>
			<div class="bbb-next__searchScrim" data-next-search-close></div>
			<div class="bbb-next__searchPanel" role="dialog" aria-modal="true" aria-labelledby="bbb-next-search-title-wp">
				<button type="button" class="bbb-next__searchClose" data-next-search-close aria-label="close">×</button>
				<p class="bbb-next__searchKicker">choose your anchor book</p>
				<h3 class="bbb-next__searchTitle" id="bbb-next-search-title-wp">what should we match from?</h3>
				<p class="bbb-next__searchHint">start typing, or choose from the books below.</p>
				<input type="search" class="bbb-next__searchInput" autocomplete="off" placeholder="search by title or author" data-next-picker>
				<div class="bbb-next__searchResults" data-next-search-results></div>
			</div>
		</div>

		<script type="application/json" data-next-books><?php echo wp_json_encode($books, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
	</section>
</main>

<?php
get_footer();
