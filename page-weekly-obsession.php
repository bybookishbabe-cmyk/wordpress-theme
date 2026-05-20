<?php
/**
 * Shopify-compatible Weekly Obsession page.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_weekly_issue_meta')) {
	function bbb_weekly_issue_meta(?WP_Post $issue, array $keys, string $default = ''): string {
		if (!$issue instanceof WP_Post) {
			return $default;
		}

		foreach ($keys as $key) {
			$value = get_post_meta($issue->ID, $key, true);
			if ('' !== $value && null !== $value) {
				return is_scalar($value) ? trim((string) $value) : $default;
			}
		}

		return $default;
	}
}

if (!function_exists('bbb_weekly_issue_date')) {
	function bbb_weekly_issue_date(?WP_Post $issue): string {
		$raw = bbb_weekly_issue_meta($issue, array('_issue_publish_date', 'publish_date'));
		if (preg_match('/^\d{8}$/', $raw)) {
			$raw = substr($raw, 0, 4) . '-' . substr($raw, 4, 2) . '-' . substr($raw, 6, 2);
		}

		$ts = $raw ? strtotime($raw) : false;
		return false !== $ts ? wp_date('M j, Y', $ts) : '';
	}
}

if (!function_exists('bbb_weekly_book_data')) {
	function bbb_weekly_book_data(WP_Post $book): array {
		if (function_exists('bbb_books_like_book_data')) {
			return bbb_books_like_book_data($book->ID);
		}

		if (function_exists('sss_article_book_data')) {
			return sss_article_book_data($book->ID);
		}

		return array(
			'id'              => $book->ID,
			'title'           => get_the_title($book),
			'author'          => (string) get_post_meta($book->ID, '_bbb_author', true),
			'cover'           => (string) get_post_meta($book->ID, '_bbb_cover_url', true),
			'newsletter'      => (string) get_post_meta($book->ID, '_bbb_newsletter_url', true),
			'spice'           => (int) get_post_meta($book->ID, '_bbb_spice', true),
			'ku'              => '1' === get_post_meta($book->ID, '_bbb_ku', true),
			'shelf'           => array('name' => '', 'slug' => ''),
			'tropes'          => array(),
			'boyfriend'       => (string) get_post_meta($book->ID, '_bbb_boyfriend_type', true),
			'boyfriend_name'  => (string) get_post_meta($book->ID, '_bbb_boyfriend_name', true),
			'series_number'   => (string) get_post_meta($book->ID, '_bbb_series_number', true),
			'standalone'      => '1' === get_post_meta($book->ID, '_bbb_standalone', true),
		);
	}
}

if (!function_exists('bbb_weekly_trope_url')) {
	function bbb_weekly_trope_url(array $trope): string {
		$slug = sanitize_title((string) ($trope['slug'] ?? $trope['name'] ?? ''));
		if ('' === $slug) {
			return home_url('/library/');
		}

		return home_url('/' . ('-books' === substr($slug, -6) ? $slug : $slug . '-books') . '/');
	}
}

if (!function_exists('bbb_weekly_featured_quote')) {
	function bbb_weekly_featured_quote(WP_Post $book): array {
		if (!post_type_exists('sss_quote')) {
			return array();
		}

		$handle = get_post_field('post_name', $book->ID);
		$quotes = get_posts(
			array(
				'post_type'      => 'sss_quote',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ($quotes as $quote) {
			$linked_ids = array(
				(int) get_post_meta($quote->ID, '_quote_book_id', true),
				(int) get_post_meta($quote->ID, '_quote_library_book_id', true),
				(int) get_post_meta($quote->ID, 'book_id', true),
				(int) get_post_meta($quote->ID, 'library_book_id', true),
			);
			$linked_handles = array_filter(
				array_map(
					'strval',
					array(
						get_post_meta($quote->ID, '_quote_book_handle', true),
						get_post_meta($quote->ID, '_quote_library_book_handle', true),
						get_post_meta($quote->ID, 'book_handle', true),
						get_post_meta($quote->ID, 'library_book_handle', true),
					)
				)
			);

			if (!in_array((int) $book->ID, $linked_ids, true) && !in_array($handle, $linked_handles, true)) {
				continue;
			}

			$text = (string) get_post_meta($quote->ID, '_quote_text', true);
			if ('' === $text) {
				$text = (string) get_post_meta($quote->ID, 'quote', true);
			}
			if ('' === $text) {
				$text = wp_strip_all_tags($quote->post_content);
			}

			if ('' !== trim($text)) {
				return array('text' => trim($text), 'post' => $quote);
			}
		}

		return array();
	}
}

if (!function_exists('bbb_weekly_related_books')) {
	function bbb_weekly_related_books(WP_Post $book, array $source, int $limit = 3): array {
		if (!function_exists('bbb_books_like_all_visible_books') || !function_exists('bbb_books_like_book_data')) {
			return array();
		}

		$source_shelf = strtolower((string) ($source['shelf']['name'] ?? ''));
		$source_boyfriend = strtolower((string) ($source['boyfriend'] ?? ''));
		$source_spice = (int) ($source['spice'] ?? 0);
		$source_tropes = array();
		foreach (array_slice((array) ($source['tropes'] ?? array()), 0, 3) as $trope) {
			$name = strtolower((string) ($trope['name'] ?? ''));
			if ($name) {
				$source_tropes[$name] = true;
			}
		}

		$buckets = array_fill_keys(array('exact', 'shelf_trope', 'shelf_spice', 'boyfriend', 'shelf', 'trope', 'spice'), array());

		foreach (bbb_books_like_all_visible_books() as $candidate) {
			if ((int) $candidate->ID === (int) $book->ID) {
				continue;
			}

			$data = bbb_books_like_book_data($candidate->ID);
			$series_number = (int) ($data['series_number'] ?? 0);
			if ($series_number > 1 && empty($data['standalone'])) {
				continue;
			}

			$candidate_shelf = strtolower((string) ($data['shelf']['name'] ?? ''));
			if ('private shelf' === $candidate_shelf) {
				continue;
			}

			$same_shelf = $source_shelf && $candidate_shelf && $source_shelf === $candidate_shelf;
			$same_boyfriend = $source_boyfriend && strtolower((string) ($data['boyfriend'] ?? '')) === $source_boyfriend;
			$close_spice = $source_spice > 0 && (int) ($data['spice'] ?? 0) > 0 && abs($source_spice - (int) ($data['spice'] ?? 0)) <= 1;
			$has_trope = false;
			foreach ((array) ($data['tropes'] ?? array()) as $trope) {
				$name = strtolower((string) ($trope['name'] ?? ''));
				if ($name && isset($source_tropes[$name])) {
					$has_trope = true;
					break;
				}
			}

			if ($same_shelf && $has_trope && $close_spice && $same_boyfriend) {
				$buckets['exact'][] = $candidate;
			} elseif ($same_shelf && $has_trope && $close_spice) {
				$buckets['shelf_trope'][] = $candidate;
			} elseif ($same_shelf && $close_spice) {
				$buckets['shelf_spice'][] = $candidate;
			} elseif ($has_trope && $close_spice && $same_boyfriend) {
				$buckets['boyfriend'][] = $candidate;
			} elseif ($same_shelf && $has_trope) {
				$buckets['shelf'][] = $candidate;
			} elseif ($has_trope && $close_spice) {
				$buckets['trope'][] = $candidate;
			} elseif ($close_spice) {
				$buckets['spice'][] = $candidate;
			} elseif ($same_boyfriend) {
				$buckets['boyfriend'][] = $candidate;
			} elseif ($has_trope) {
				$buckets['trope'][] = $candidate;
			}
		}

		$related = array();
		$seen = array();
		foreach ($buckets as $bucket) {
			foreach ($bucket as $candidate) {
				if (isset($seen[$candidate->ID])) {
					continue;
				}
				$seen[$candidate->ID] = true;
				$related[] = $candidate;
				if (count($related) >= $limit) {
					return $related;
				}
			}
		}

		return $related;
	}
}

wp_enqueue_style('sss-weekly-obsession', get_theme_file_uri('assets/css/weekly-obsession.css'), array('bbb-sss-library'), wp_get_theme()->get('Version'));

$current_issue = function_exists('sss_get_current_newsletter_issue') ? sss_get_current_newsletter_issue() : null;
$featured_book = $current_issue && function_exists('sss_get_obsession_book') ? sss_get_obsession_book($current_issue) : null;
if (!$featured_book instanceof WP_Post && function_exists('sss_get_latest_featured_book')) {
	$featured_book = sss_get_latest_featured_book();
}

$book_data = $featured_book instanceof WP_Post ? bbb_weekly_book_data($featured_book) : array();
$issue_title = bbb_weekly_issue_meta($current_issue, array('_issue_title_override', 'title'), $current_issue instanceof WP_Post ? $current_issue->post_title : 'this week’s obsession');
$issue_subtitle = bbb_weekly_issue_meta($current_issue, array('_issue_subtitle', 'subtitle'), '');
$issue_excerpt = bbb_weekly_issue_meta($current_issue, array('_issue_excerpt', 'excerpt'), '');
if ('' === $issue_excerpt && $current_issue instanceof WP_Post) {
	$issue_excerpt = $current_issue->post_excerpt ?: wp_trim_words(wp_strip_all_tags($current_issue->post_content), 42, '');
}
$issue_link = bbb_weekly_issue_meta($current_issue, array('_bbb_newsletter_url', '_issue_url', 'url', 'newsletter_url'), (string) ($book_data['newsletter'] ?? ''));
$issue_date = bbb_weekly_issue_date($current_issue);
$boyfriend_name = trim((string) ($book_data['boyfriend_name'] ?? ''));
if ('' === $boyfriend_name) {
	$boyfriend_name = trim((string) ($book_data['boyfriend'] ?? ''));
}
$featured_quote = $featured_book instanceof WP_Post ? bbb_weekly_featured_quote($featured_book) : array();
$related_books = $featured_book instanceof WP_Post ? bbb_weekly_related_books($featured_book, $book_data, 3) : array();

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="sss-lib sss-lib--weekly-obsession sss-wo" data-sss-lib="public">
		<div class="sss-lib__wrap sss-wo__wrap">
			<?php if ($featured_book instanceof WP_Post) : ?>
				<header class="sss-lib__head sss-wo__pageHead">
					<p class="sss-lib__kicker">weekly obsession</p>
					<p class="sss-wo__introLine">the romance book you need to add to your tbr immediately. this week’s obsession from the smut &amp; sentiment society.</p>
					<div class="sss-wo__masthead">
						<div class="sss-wo__mastDivider" aria-hidden="true"></div>
						<h1 class="sss-lib__title"><?php echo esc_html($issue_title ?: 'this week’s obsession'); ?></h1>
						<?php if ($issue_subtitle) : ?>
							<p class="sss-lib__sub"><?php echo esc_html($issue_subtitle); ?></p>
						<?php endif; ?>
					</div>
				</header>

				<div class="sss-wo__hero">
					<div class="sss-wo__coverCol">
						<div class="sss-wo__coverFrame">
							<div class="sss-wo__heroBook">
								<?php bbb_render_component('sss-book-card', array('book' => $featured_book)); ?>
							</div>
						</div>
					</div>

					<div class="sss-wo__copyCol">
						<?php if ($issue_link) : ?>
							<a class="sss-wo__stamp" href="<?php echo esc_url($issue_link); ?>" target="_blank" rel="noopener">newsletter saw it first</a>
						<?php else : ?>
							<div class="sss-wo__stamp">newsletter saw it first</div>
						<?php endif; ?>

						<div class="sss-wo__bookMetaCard">
							<div class="sss-wo__bookMetaHead">
								<span class="sss-wo__bookMetaStamp">library card</span>
								<?php if ($issue_date) : ?>
									<span class="sss-wo__bookMetaDate"><?php echo esc_html($issue_date); ?></span>
								<?php endif; ?>
							</div>

							<div class="sss-wo__bookMeta">
								<div class="sss-wo__bookLine">
									<span class="sss-wo__bookLabel">book</span>
									<span class="sss-wo__bookValue"><?php echo esc_html((string) ($book_data['title'] ?? get_the_title($featured_book))); ?></span>
								</div>
								<?php if (!empty($book_data['author'])) : ?>
									<div class="sss-wo__bookLine">
										<span class="sss-wo__bookLabel">author</span>
										<span class="sss-wo__bookValue"><?php echo esc_html((string) $book_data['author']); ?></span>
									</div>
								<?php endif; ?>
								<?php if (!empty($book_data['shelf']['name']) && 'private shelf' !== strtolower((string) $book_data['shelf']['name'])) : ?>
									<div class="sss-wo__bookLine">
										<span class="sss-wo__bookLabel">shelf</span>
										<span class="sss-wo__bookValue"><?php echo esc_html((string) $book_data['shelf']['name']); ?></span>
									</div>
								<?php endif; ?>
								<?php if ((int) ($book_data['spice'] ?? 0) > 0) : ?>
									<div class="sss-wo__bookLine">
										<span class="sss-wo__bookLabel">spice</span>
										<span class="sss-wo__bookValue"><?php echo esc_html(str_repeat('🌶', (int) $book_data['spice'])); ?></span>
									</div>
								<?php endif; ?>
								<div class="sss-wo__bookLine">
									<span class="sss-wo__bookLabel">kindle unlimited</span>
									<span class="sss-wo__bookValue sss-wo__statusValue <?php echo !empty($book_data['ku']) ? 'is-yes' : 'is-no'; ?>">
										<span class="sss-wo__statusBox" aria-hidden="true"><?php echo !empty($book_data['ku']) ? '✓' : '×'; ?></span>
										<span><?php echo !empty($book_data['ku']) ? 'yes' : 'no'; ?></span>
									</span>
								</div>
							</div>
						</div>

						<?php if ($issue_excerpt) : ?>
							<div class="sss-wo__editorial">
								<p><?php echo esc_html($issue_excerpt); ?></p>
							</div>
						<?php endif; ?>

						<?php if (!empty($book_data['tropes'])) : ?>
							<div class="sss-wo__detailBlock">
								<div class="sss-wo__detailKicker">tropes in the dossier</div>
								<div class="sss-wo__chips">
									<?php
									$chip_classes = array('sss-wo__chip--ink', 'sss-wo__chip--plum', 'sss-wo__chip--berry', 'sss-wo__chip--rose', 'sss-wo__chip--blush', 'sss-wo__chip--pearl');
									foreach (array_slice((array) $book_data['tropes'], 0, 6) as $index => $trope) :
										$trope_name = (string) ($trope['name'] ?? '');
										if ('' === $trope_name) {
											continue;
										}
									?>
										<a class="sss-wo__chip <?php echo esc_attr($chip_classes[$index % count($chip_classes)]); ?>" href="<?php echo esc_url(bbb_weekly_trope_url($trope)); ?>">
											<?php echo esc_html($trope_name); ?>
										</a>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>

						<?php if ($boyfriend_name) : ?>
							<div class="sss-wo__boyfriend">
								<div class="sss-wo__detailKicker">the man causing the damage</div>
								<div class="sss-wo__boyfriendName"><?php echo esc_html($boyfriend_name); ?></div>
							</div>
						<?php endif; ?>

						<div class="sss-wo__actions">
							<a class="sss-wo__btn sss-wo__btn--ghost" href="<?php echo esc_url(home_url('/library/')); ?>">see the library</a>
							<?php if ($issue_link) : ?>
								<a class="sss-wo__btn sss-wo__btn--primary" href="<?php echo esc_url($issue_link); ?>" target="_blank" rel="noopener">read the newsletter</a>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<?php if (!empty($featured_quote['text'])) : ?>
					<div class="sss-wo__quoteBlock">
						<div class="sss-wo__quoteKicker">a line that ruined us</div>
						<blockquote class="sss-wo__quote">“<?php echo esc_html((string) $featured_quote['text']); ?>”</blockquote>
						<div class="sss-wo__quoteMeta">
							<?php echo esc_html((string) ($book_data['title'] ?? get_the_title($featured_book))); ?><?php echo !empty($book_data['author']) ? ' by ' . esc_html((string) $book_data['author']) : ''; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ($related_books) : ?>
					<div class="sss-wo__moreLikeThis">
						<div class="sss-wo__sectionHead">
							<div class="sss-wo__sectionKicker">keep the spiral going</div>
							<h2 class="sss-wo__sectionTitle">more like this</h2>
							<p class="sss-wo__sectionSub">same orbit. same damage. a few more books that belong in this week’s obsession.</p>
						</div>

						<div class="sss-wo__relatedRow">
							<?php foreach ($related_books as $related_book) : ?>
								<div class="sss-wo__relatedItem">
									<?php bbb_render_component('sss-book-card', array('book' => $related_book, 'mini' => true)); ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<div class="sss-wo__empty">
					<p class="sss-wo__kicker">weekly obsession</p>
					<h1 class="sss-wo__emptyTitle">next obsession is loading</h1>
					<p class="sss-wo__emptySub">once the next newsletter issue is live and its monday handoff passes, this dossier will fill itself in.</p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
bbb_render_component('library-modal');
get_footer();
