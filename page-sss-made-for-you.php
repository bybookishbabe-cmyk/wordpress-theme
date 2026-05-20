<?php
/**
 * Template Name: SSS Made For You
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_made_for_you_books')) {
	function bbb_made_for_you_books(): array {
		if (!function_exists('bbb_books_like_all_visible_books') || !function_exists('bbb_books_like_book_data')) {
			return array();
		}

		$books = array();
		foreach (bbb_books_like_all_visible_books() as $book_post) {
			if (!$book_post instanceof WP_Post) {
				continue;
			}

			$data   = bbb_books_like_book_data((int) $book_post->ID);
			$tropes = array();
			foreach ((array) ($data['tropes'] ?? array()) as $trope) {
				$name = (string) ($trope['name'] ?? '');
				if ('' !== $name) {
					$tropes[] = $name;
				}
			}

			$books[] = array(
				'id'              => (int) $book_post->ID,
				'handle'          => (string) ($data['handle'] ?? $book_post->post_name),
				'title'           => (string) ($data['title'] ?? get_the_title($book_post)),
				'author'          => (string) ($data['author'] ?? ''),
				'shelf'           => (string) ($data['shelf']['name'] ?? ''),
				'boyfriend_name'  => (string) ($data['boyfriend_name'] ?? ''),
				'boyfriend_type'  => (string) ($data['boyfriend'] ?? ''),
				'spice'           => (int) ($data['spice'] ?? 0),
				'tension'         => (int) ($data['tension'] ?? 0),
				'damage'          => (int) ($data['damage'] ?? 0),
				'darkness'        => (int) ($data['darkness'] ?? 0),
				'yearning'        => (int) ($data['yearning'] ?? 0),
				'ku'              => !empty($data['ku']),
				'tropes'          => $tropes,
			);
		}

		return $books;
	}
}

if (!function_exists('bbb_made_for_you_quotes')) {
	function bbb_made_for_you_quotes(array $books): array {
		if (!post_type_exists('sss_quote')) {
			return array();
		}

		$books_by_id     = array();
		$books_by_handle = array();
		foreach ($books as $book) {
			$books_by_id[(int) $book['id']] = $book;
			if (!empty($book['handle'])) {
				$books_by_handle[(string) $book['handle']] = $book;
			}
		}

		$quotes = get_posts(
			array(
				'post_type'      => 'sss_quote',
				'post_status'    => 'publish',
				'posts_per_page' => 75,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$out = array();
		foreach ($quotes as $quote) {
			if (!$quote instanceof WP_Post) {
				continue;
			}

			$text = trim((string) get_post_meta($quote->ID, '_quote_text', true));
			if ('' === $text) {
				$text = trim((string) get_post_meta($quote->ID, 'quote', true));
			}
			if ('' === $text) {
				$text = trim(wp_strip_all_tags($quote->post_content));
			}
			if ('' === $text) {
				continue;
			}

			$book_id = max(
				(int) get_post_meta($quote->ID, '_quote_book_id', true),
				(int) get_post_meta($quote->ID, '_quote_library_book_id', true),
				(int) get_post_meta($quote->ID, 'book_id', true),
				(int) get_post_meta($quote->ID, 'library_book_id', true)
			);
			$handle  = (string) get_post_meta($quote->ID, '_quote_book_handle', true);
			$handle  = '' !== $handle ? $handle : (string) get_post_meta($quote->ID, 'book_handle', true);
			$book    = $books_by_id[$book_id] ?? $books_by_handle[$handle] ?? null;

			if (!$book) {
				continue;
			}

			$shelf = strtolower((string) ($book['shelf'] ?? ''));
			$theme = 'gray';
			if (str_contains($shelf, 'fantasy') || str_contains($shelf, 'romantasy')) {
				$theme = 'blue';
			} elseif (str_contains($shelf, 'dark') || str_contains($shelf, 'private')) {
				$theme = 'red';
			} elseif (str_contains($shelf, 'soft') || str_contains($shelf, 'sentimental') || str_contains($shelf, 'starter')) {
				$theme = 'yellow';
			}

			$out[] = array(
				'handle' => (string) $book['handle'],
				'title'  => (string) $book['title'],
				'author' => (string) $book['author'],
				'quote'  => wp_strip_all_tags($text),
				'theme'  => $theme,
			);
		}

		return $out;
	}
}

$mfy_books  = bbb_made_for_you_books();
$mfy_quotes = bbb_made_for_you_quotes($mfy_books);

get_header();
bbb_render_component('sss-folder-tabs');
?>
<section class="sss-lib sss-lib--mfy-page" id="sss-lib-made-for-you" data-sss-lib="society">
		<div class="sss-lib__wrap">
			<header class="sss-lib__head">
				<p class="sss-lib__kicker">private reader file</p>
				<h1 class="sss-lib__title">member dashboard</h1>
				<p class="sss-lib__sub">your taste, your spice, your finished shelf, and the books most likely to hit next.</p>
			</header>
			<div class="sss-mfy__dashboardIntro" aria-label="member dashboard shortcuts">
				<a class="sss-lib__finderBtn" href="<?php echo esc_url(bbb_page_url('sss-library-page')); ?>">open the library</a>
				<a class="sss-lib__finderBtn sss-lib__finderBtn--ghost" href="<?php echo esc_url(bbb_page_url('my-bookshelf')); ?>">open my bookshelf</a>
				<a class="sss-lib__finderBtn sss-lib__finderBtn--ghost" href="<?php echo esc_url(bbb_page_url('sss-quote-wall')); ?>">open quote library</a>
			</div>
			<?php if (!$mfy_books) : ?>
				<div class="sss-mfy__empty sss-mfy__empty--page">member dashboard is connected, but there are not any visible library books available for recommendations yet.</div>
			<?php endif; ?>

			<section class="sss-lib__madeForYou" id="sssMadeForYou">
				<div class="sss-mfy">
					<div class="sss-mfy__quiz" id="sssMadeForYouQuiz">
						<div class="sss-mfy__eyebrow">a little reader profiling. a little emotional damage. a much smarter next-read list.</div>
						<div class="sss-mfy__prepNote">
							<strong>before you start:</strong>
							results get much better once you have gone through the <a class="sss-mfy__prepLink" href="<?php echo esc_url(bbb_page_url('sss-library-page')); ?>">library</a> and favorited, saved, or read a few books.
						</div>
						<div class="sss-mfy__progress">
							<div class="sss-mfy__progressMeta">
								<span id="sssMfyStepCount">question 1 of 6</span>
								<button type="button" class="sss-mfy__resetLink" id="sssMadeForYouReset">start over</button>
							</div>
							<div class="sss-mfy__progressTrack">
								<span class="sss-mfy__progressFill" id="sssMfyProgressFill"></span>
							</div>
						</div>

						<div class="sss-mfy__track" id="sssMfyTrack">
							<div class="sss-mfy__slide" data-mfy-question="name" data-mfy-step="0">
								<div class="sss-mfy__label">name on library card</div>
								<div class="sss-mfy__nameField">
									<input type="text" id="sssMfyNameInput" class="sss-mfy__nameInput" placeholder="enter your name" maxlength="40" autocomplete="off">
									<button type="button" class="sss-lib__finderBtn" id="sssMfyNameContinue">continue</button>
								</div>
							</div>

							<div class="sss-mfy__slide" data-mfy-question="craving" data-mfy-step="1">
								<div class="sss-mfy__label">your next read needs to be...</div>
								<div class="sss-mfy__options">
									<button type="button" data-mfy-answer="craving" data-value="slow_ache">slow burn</button>
									<button type="button" data-mfy-answer="craving" data-value="messy_obsession">obsession / stalker</button>
									<button type="button" data-mfy-answer="craving" data-value="comfort_devotion">friends to lovers</button>
									<button type="button" data-mfy-answer="craving" data-value="chaos_chemistry">enemies to lovers</button>
									<button type="button" data-mfy-answer="craving" data-value="dark_dangerous">touch her and die</button>
								</div>
							</div>

							<div class="sss-mfy__slide" data-mfy-question="payoff" data-mfy-step="2">
								<div class="sss-mfy__label">also in the mood for...</div>
								<div class="sss-mfy__options">
									<button type="button" data-mfy-answer="payoff" data-value="long_tension">yearning / long tension</button>
									<button type="button" data-mfy-answer="payoff" data-value="emotional_devastation">angst / emotional devastation</button>
									<button type="button" data-mfy-answer="payoff" data-value="soft_after_storm">healing / softness after</button>
									<button type="button" data-mfy-answer="payoff" data-value="plot_addiction">plot-heavy / addictive</button>
									<button type="button" data-mfy-answer="payoff" data-value="illegal_chemistry">forbidden / sharp chemistry</button>
								</div>
							</div>

							<div class="sss-mfy__slide" data-mfy-question="boyfriend_hook" data-mfy-step="3">
								<div class="sss-mfy__label">what gets you first?</div>
								<div class="sss-mfy__options">
									<button type="button" data-mfy-answer="boyfriend_hook" data-value="brain_and_banter">sharp mouth / smarter than me</button>
									<button type="button" data-mfy-answer="boyfriend_hook" data-value="cold_and_unreadable">cold stare / unreadable energy</button>
									<button type="button" data-mfy-answer="boyfriend_hook" data-value="dangerous_and_powerful">dangerous / powerful / should be avoided</button>
									<button type="button" data-mfy-answer="boyfriend_hook" data-value="protective_and_all_in">protective / all in / a little possessive</button>
									<button type="button" data-mfy-answer="boyfriend_hook" data-value="charming_and_soft">charming / warm / easy to love</button>
								</div>
							</div>

							<div class="sss-mfy__slide" data-mfy-question="boyfriend_dynamic" data-mfy-step="4">
								<div class="sss-mfy__label">what dynamic always gets you?</div>
								<div class="sss-mfy__options">
									<button type="button" data-mfy-answer="boyfriend_dynamic" data-value="rivals_with_tension">rivals with too much tension</button>
									<button type="button" data-mfy-answer="boyfriend_dynamic" data-value="grump_softening">grump who only softens for her</button>
									<button type="button" data-mfy-answer="boyfriend_dynamic" data-value="mean_and_magnetic">mean / rude / still magnetic</button>
									<button type="button" data-mfy-answer="boyfriend_dynamic" data-value="touch_her_and_die">touch her and die</button>
									<button type="button" data-mfy-answer="boyfriend_dynamic" data-value="falls_first_hard">falls first and hard</button>
									<button type="button" data-mfy-answer="boyfriend_dynamic" data-value="villainous_obsession">villainous obsession</button>
								</div>
							</div>

							<div class="sss-mfy__slide" data-mfy-question="theme" data-mfy-step="5">
								<div class="sss-mfy__label">pick your theme.</div>
								<div class="sss-mfy__options">
									<button type="button" class="sss-mfy__themeOption" data-mfy-answer="theme" data-value="dark_hearts"><span class="sss-mfy__themeName">annotated in black tabs</span><span class="sss-mfy__themeMeta">dark / sharp / candlelit</span></button>
									<button type="button" class="sss-mfy__themeOption" data-mfy-answer="theme" data-value="obsession_red"><span class="sss-mfy__themeName">dog-eared after midnight</span><span class="sss-mfy__themeMeta">red / reckless / heated</span></button>
									<button type="button" class="sss-mfy__themeOption" data-mfy-answer="theme" data-value="rose_ribbon"><span class="sss-mfy__themeName">pressed petals in chapter ten</span><span class="sss-mfy__themeMeta">pink / soft / pretty</span></button>
									<button type="button" class="sss-mfy__themeOption" data-mfy-answer="theme" data-value="stormy_blue"><span class="sss-mfy__themeName">margin notes in the rain</span><span class="sss-mfy__themeMeta">blue / moody / aching</span></button>
									<button type="button" class="sss-mfy__themeOption" data-mfy-answer="theme" data-value="pearl_white"><span class="sss-mfy__themeName">cream dust jacket energy</span><span class="sss-mfy__themeMeta">white / classic / quiet</span></button>
									<button type="button" class="sss-mfy__themeOption" data-mfy-answer="theme" data-value="royal_violet"><span class="sss-mfy__themeName">underlined in velvet ink</span><span class="sss-mfy__themeMeta">violet / dreamy / dramatic</span></button>
								</div>
							</div>
						</div>

						<div class="sss-mfy__actions">
							<button type="button" class="sss-lib__finderBtn sss-lib__finderBtn--ghost" id="sssMadeForYouBack">back</button>
							<div class="sss-mfy__continueNote">tap an answer to keep going</div>
						</div>
					</div>

					<div class="sss-mfy__results" id="sssMadeForYouResults" hidden>
						<div class="sss-mfy__resultsHead">
							<div class="sss-mfy__resultsIdentity">
								<div class="sss-mfy__eyebrow" id="sssMfyDashboardKicker">curated for you</div>
								<div class="sss-mfy__resultsTitle" id="sssMfyDashboardTitle">member dashboard</div>
							</div>
							<button type="button" class="sss-mfy__resetLink" id="sssMadeForYouResetResults">reset</button>
						</div>

						<div class="sss-mfy__resultsRail">
							<div class="sss-mfy__panel is-active" data-mfy-panel="0">
								<div class="sss-mfy__hero sss-mfy__module sss-mfy__module--core">
									<div class="sss-mfy__emojiRain" id="sssMfyHeroRain" aria-hidden="true"></div>
									<div class="sss-mfy__moduleEmoji" id="sssMfyCoreEmojiBadge">heart</div>
									<div class="sss-mfy__cardKicker" id="sssMfyHeroKicker">your reader core</div>
									<div class="sss-mfy__heroTitle" id="sssMfyCoreTitle">waiting on your answers</div>
									<div class="sss-mfy__heroEmotion" id="sssMfyCoreEmotion">falling emotion: loading</div>
									<div class="sss-mfy__heroBody" id="sssMfyCoreBody">pick a few answers and i’ll tell you what kind of romance damage you’re actually here for.</div>
									<div class="sss-mfy__heroTokens" id="sssMfyThemeTokens"></div>
								</div>
							</div>

							<div class="sss-mfy__panel" data-mfy-panel="1">
								<div class="sss-mfy__spotlight sss-mfy__module sss-mfy__module--boyfriend">
									<div class="sss-mfy__emojiRain" id="sssMfyBoyfriendRain" aria-hidden="true"></div>
									<div class="sss-mfy__moduleEmoji" id="sssMfyBoyfriendEmojiBadge">spark</div>
									<div class="sss-mfy__spotlightCopy">
										<div class="sss-mfy__cardKicker" id="sssMfyBoyfriendKicker">your fictional boyfriend</div>
										<div class="sss-mfy__cardTitle" id="sssMfyTypeTitle">currently unreadable</div>
										<div class="sss-mfy__cardBody" id="sssMfyTypeBody">this is where i’ll lovingly explain what your taste in fictional men says about you.</div>
									</div>
									<div class="sss-mfy__spotlightBook" id="sssMfyMatchBook"></div>
								</div>
							</div>

							<div class="sss-mfy__panel" data-mfy-panel="2">
								<div class="sss-mfy__recBlock sss-mfy__module sss-mfy__module--reads">
									<div class="sss-mfy__emojiRain" id="sssMfyReadsRain" aria-hidden="true"></div>
									<div class="sss-mfy__moduleEmoji" id="sssMfyReadsEmojiBadge">letter</div>
									<div class="sss-mfy__cardKicker" id="sssMfyReadsKicker">your next read</div>
									<div class="sss-mfy__recTitle" id="sssMfyRecTitle">your next read will land here</div>
									<div class="sss-lib__shelfRow" id="sssMadeForYouRow"></div>
								</div>
							</div>

							<div class="sss-mfy__resultsActions">
								<div class="sss-mfy__resultsMeta" id="sssMfyResultsMeta">step 1 of 4</div>
								<button type="button" class="sss-lib__finderBtn" id="sssMfyNextResult">next</button>
							</div>
						</div>

						<div class="sss-mfy__customize" id="sssMfyCustomize" hidden>
							<div class="sss-mfy__customizeHead">
								<div class="sss-mfy__eyebrow">make it more you</div>
								<div class="sss-mfy__customizeTitle">add a few personal layers</div>
							</div>

							<div class="sss-mfy__addonRow" id="sssMfyAddonRow">
								<button type="button" class="sss-mfy__addonCard" data-mfy-addon="hard_nos"><span class="sss-mfy__addonEmoji">no</span><span class="sss-mfy__addonText"><strong>set your hard no's</strong><small id="sssMfyHardNoSummary">shape your recs</small></span></button>
								<button type="button" class="sss-mfy__addonCard" data-mfy-addon="spice_dial"><span class="sss-mfy__addonEmoji">spice</span><span class="sss-mfy__addonText"><strong>spice dial</strong><small id="sssMfyManDialSummary">be honest</small></span></button>
								<button type="button" class="sss-mfy__addonCard" data-mfy-addon="favorite_book"><span class="sss-mfy__addonEmoji">book</span><span class="sss-mfy__addonText"><strong>favorite book</strong><small id="sssMfyFavoriteSummary">the one that changed you</small></span></button>
							</div>

							<div class="sss-mfy__addonModules">
								<section class="sss-mfy__addonModule sss-mfy__addonModule--hardnos" data-mfy-module="hard_nos" hidden>
									<div class="sss-mfy__addonHeader">
										<div><div class="sss-mfy__cardKicker">hard no's</div><h3>the things you never want me to sneak into your recs</h3></div>
										<button type="button" class="sss-mfy__collapse" data-mfy-close="hard_nos">close</button>
									</div>
									<div class="sss-mfy__chipSet sss-mfy__chipSet--hardnos" id="sssMfyHardNos">
										<button type="button" data-mfy-hard-no="love triangle">love triangle</button>
										<button type="button" data-mfy-hard-no="accidental pregnancy">accidental pregnancy</button>
										<button type="button" data-mfy-hard-no="cheating">cheating</button>
										<button type="button" data-mfy-hard-no="bully romance">bully romance</button>
										<button type="button" data-mfy-hard-no="second chance">second chance</button>
										<button type="button" data-mfy-hard-no="secret baby">secret baby</button>
										<button type="button" data-mfy-hard-no="why choose">why choose</button>
										<button type="button" data-mfy-hard-no="friends with benefits">friends with benefits</button>
									</div>
									<div class="sss-mfy__addonActions"><button type="button" class="sss-lib__finderBtn" id="sssMfySaveHardNos">save choices</button></div>
								</section>

								<section class="sss-mfy__addonModule sss-mfy__addonModule--dial" data-mfy-module="spice_dial" hidden>
									<div class="sss-mfy__addonHeader">
										<div><div class="sss-mfy__cardKicker">spice dial</div><h3>tell me how spicy you want the lower recs to run</h3></div>
										<button type="button" class="sss-mfy__collapse" data-mfy-close="spice_dial">close</button>
									</div>
									<div class="sss-mfy__dialWrap" id="sssMfyManDial">
										<div class="sss-mfy__dialOrb" id="sssMfyManDialOrb"><div class="sss-mfy__dialCenter"><span class="sss-mfy__dialMini">current spice lane</span><strong id="sssMfyManDialValue">soft open door</strong></div></div>
										<div class="sss-mfy__dialLabels"><span>soft open door</span><span>some heat</span><span>balanced</span><span>high spice</span><span>wreck me</span></div>
										<input type="range" min="0" max="4" step="1" value="0" id="sssMfyManDialInput" class="sss-mfy__dialInput">
										<div class="sss-mfy__dialChoices" id="sssMfyManDialChoices">
											<button type="button" class="sss-mfy__dialChoice" data-mfy-dial-choice="soft_open_door">soft open door</button>
											<button type="button" class="sss-mfy__dialChoice" data-mfy-dial-choice="some_heat">some heat</button>
											<button type="button" class="sss-mfy__dialChoice" data-mfy-dial-choice="balanced">balanced</button>
											<button type="button" class="sss-mfy__dialChoice" data-mfy-dial-choice="high_spice">high spice</button>
											<button type="button" class="sss-mfy__dialChoice" data-mfy-dial-choice="wreck_me">wreck me</button>
										</div>
									</div>
									<p class="sss-mfy__moduleNote" id="sssMfyManDialNote">this will only tune the reads suggested below your member dashboard.</p>
									<div class="sss-mfy__addonActions"><button type="button" class="sss-lib__finderBtn" id="sssMfySaveManDial">save dial</button></div>
								</section>

								<section class="sss-mfy__addonModule sss-mfy__addonModule--favorite" data-mfy-module="favorite_book" hidden>
									<div class="sss-mfy__addonHeader">
										<div><div class="sss-mfy__cardKicker">favorite book</div><h3>the one that permanently altered your standards</h3></div>
										<button type="button" class="sss-mfy__collapse" data-mfy-close="favorite_book">close</button>
									</div>
									<div class="sss-mfy__favoriteGrid">
										<div class="sss-mfy__favoritePicker">
											<label class="visually-hidden" for="sssMfyFavoriteBookSearch">favorite book</label>
											<input type="search" id="sssMfyFavoriteBookSearch" class="sss-mfy__select sss-mfy__searchInput" placeholder="search for your favorite book" autocomplete="off">
											<div class="sss-mfy__searchResults" id="sssMfyFavoriteBookResults" role="listbox" aria-label="favorite book matches"></div>
											<div class="sss-mfy__favoriteEcho" id="sssMfyFavoriteBookEcho">pick the book that changed everything and i’ll weave that into your recs.</div>
										</div>
										<div class="sss-mfy__favoritePreview" id="sssMfyFavoriteBookPreview"></div>
									</div>
									<div class="sss-mfy__addonActions"><button type="button" class="sss-lib__finderBtn" id="sssMfySaveFavoriteBook">save favorite book</button></div>
								</section>
							</div>
						</div>

						<section class="sss-mfy__quoteSpotlight" id="sssMfyQuoteSpotlight" hidden>
							<div class="sss-mfy__quoteSpotlightHead"><div><div class="sss-mfy__eyebrow" id="sssMfyQuoteEyebrow">quote spotlight</div><div class="sss-mfy__quoteSpotlightTitle">a line for your current reading era</div></div></div>
							<div class="sss-mfy__quoteStage">
								<div class="sss-mfy__emojiRain" id="sssMfyQuoteRain" aria-hidden="true"></div>
								<blockquote class="sss-mfy__quoteCard" id="sssMfyQuoteCard"><span class="sss-mfy__quoteBar"></span><p id="sssMfyQuoteText">a line that fits your taste will land here.</p><footer id="sssMfyQuoteSource"></footer></blockquote>
							</div>
						</section>

						<section class="sss-mfy__savedQuotes" id="sssMfySavedQuotes" hidden>
							<div class="sss-mfy__quoteSpotlightHead"><div><div class="sss-mfy__eyebrow">saved from the quote wall</div><div class="sss-mfy__quoteSpotlightTitle">the lines you wanted to keep</div></div><div class="sss-mfy__readShelfMeta" id="sssMfySavedQuotesMeta">save quotes in the wall and they’ll land here.</div></div>
							<div class="sss-mfy__savedQuoteGrid" id="sssMfySavedQuotesRow"></div>
						</section>

						<section class="sss-mfy__readShelf" id="sssMfyReadShelf" hidden>
							<div class="sss-mfy__readShelfHead"><div><div class="sss-mfy__eyebrow" id="sssMfyReadShelfEyebrow">books you've read in the library</div><div class="sss-mfy__quoteSpotlightTitle">the books you already finished are shaping what comes next</div></div><div class="sss-mfy__readShelfMeta" id="sssMfyReadShelfMeta">mark books as read anywhere on the site and they’ll show up here.</div></div>
							<div class="sss-mfy__readShelfGrid">
								<div class="sss-mfy__readShelfColumn"><div class="sss-mfy__cardKicker">your read shelf</div><div class="sss-lib__shelfRow" id="sssMfyReadShelfRow"></div></div>
								<div class="sss-mfy__readShelfColumn sss-mfy__readShelfColumn--insights"><div class="sss-mfy__cardKicker">the tropes you keep returning to</div><div class="sss-mfy__heroTokens sss-mfy__heroTokens--left" id="sssMfyReadTropes"></div><p class="sss-mfy__moduleNote" id="sssMfyReadShelfInsight">once you’ve marked a few finished books, i’ll pull the patterns and aim your next recs harder.</p></div>
							</div>
							<div class="sss-mfy__insight sss-mfy__module sss-mfy__module--shelf">
								<div class="sss-mfy__emojiRain" id="sssMfyShelfRain" aria-hidden="true"></div>
								<div class="sss-mfy__moduleEmoji" id="sssMfyShelfEmojiBadge">stack</div>
								<div class="sss-mfy__cardKicker" id="sssMfyShelfKicker">what your bookshelf says about you...</div>
								<div class="sss-mfy__cardTitle" id="sssMfyShelfTitle">not enough evidence yet</div>
								<div class="sss-mfy__cardBody" id="sssMfyShelfBody">save a few books or tag them as tbr / reading and this gets smarter.</div>
							</div>
							<div class="sss-mfy__readShelfNext">
								<p class="sss-mfy__moduleNote">updates as you mark books read, rate them, and save your personal layers.</p>
								<div class="sss-mfy__cardKicker">what you should read next</div>
								<div class="sss-mfy__recTitle" id="sssMfyReadNextTitle">your next reads will land here once your read shelf has a pattern.</div>
								<div class="sss-lib__shelfRow" id="sssMfyReadNextRow"></div>
								<div class="sss-mfy__swipeCue">swipe to see more</div>
							</div>
						</section>
					</div>
				</div>

				<div class="sss-mfy__sourceGrid" hidden aria-hidden="true">
					<?php foreach ($mfy_books as $book) : ?>
						<?php echo bbb_render_library_book_card((int) $book['id']); ?>
					<?php endforeach; ?>
				</div>

				<script type="application/json" id="sssMadeForYouData"><?php echo wp_json_encode(array_values($mfy_books)); ?></script>
				<script type="application/json" id="sssMadeForYouQuotes"><?php echo wp_json_encode(array_values($mfy_quotes)); ?></script>
			</section>
		</div>
</section>
<?php
get_footer();
