<?php
/**
 * Shopify-compatible "books like source book" page template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$source_post = function_exists('bbb_books_like_current_source_book') ? bbb_books_like_current_source_book() : null;
if (!$source_post instanceof WP_Post) {
	require get_theme_file_path('page-books-like-directory.php');
	return;
}

$books_like_css_path = get_theme_file_path('assets/css/books-like.css');
wp_enqueue_style('bbb-books-like', get_theme_file_uri('assets/css/books-like.css'), array('bbb-sss-library'), file_exists($books_like_css_path) ? (string) filemtime($books_like_css_path) : wp_get_theme()->get('Version'));
get_header();

$source                 = bbb_books_like_book_data($source_post->ID);
$is_paid_society_member = function_exists('bbb_reader_is_society') && bbb_reader_is_society();
$all_recommendations    = array_slice(bbb_books_like_recommendations($source_post->ID), 0, 7);
$preview_limit          = 2;
$recommendations        = $is_paid_society_member ? $all_recommendations : array_slice($all_recommendations, 0, $preview_limit);
$locked_count           = max(0, count($all_recommendations) - count($recommendations));
$source_tropes          = array_slice((array) $source['tropes'], 0, 5);

function bbb_books_like_rating_dots(int $value): string {
	$value = max(0, min(5, $value));
	return $value > 0 ? str_repeat('<span></span>', $value) : '';
}

function bbb_books_like_skulls(int $value): string {
	$value = max(0, min(5, $value));
	return $value > 0 ? str_repeat('💀', $value) : '';
}

function bbb_books_like_trope_display(array $trope): string {
	$name  = trim((string) ($trope['name'] ?? ''));
	$emoji = trim((string) ($trope['emoji'] ?? ''));
	return function_exists('bbb_trope_label') ? bbb_trope_label($name, $emoji) : trim(($emoji !== '' ? $emoji : '🖤') . ' ' . $name);
}

function bbb_books_like_trope_display_html(array $trope): string {
	$name  = trim((string) ($trope['name'] ?? ''));
	$emoji = trim((string) ($trope['emoji'] ?? ''));
	$slug  = trim((string) ($trope['slug'] ?? $trope['handle'] ?? ''));

	return function_exists('bbb_trope_label_html') ? bbb_trope_label_html($name, $emoji, $slug) : esc_html(bbb_books_like_trope_display($trope));
}

function bbb_books_like_ensure_emoji_label(string $label): string {
	$label = trim($label);
	if ('' === $label) {
		return '';
	}

	if (preg_match('/^[^\p{L}\p{N}]/u', $label)) {
		return $label;
	}

	return function_exists('bbb_trope_label') ? bbb_trope_label($label, '') : '🖤 ' . $label;
}

function bbb_books_like_ensure_emoji_label_html(string $label): string {
	$label = trim($label);
	if ('' === $label) {
		return '';
	}

	return function_exists('bbb_trope_label_html') ? bbb_trope_label_html($label) : esc_html(bbb_books_like_ensure_emoji_label($label));
}

function bbb_books_like_trope_display_values(array $book, array $names): array {
	$by_slug = array();
	foreach ((array) ($book['tropes'] ?? array()) as $trope) {
		$name = trim((string) ($trope['name'] ?? ''));
		if ($name === '') {
			continue;
		}
		$by_slug[sanitize_title($name)] = bbb_books_like_trope_display_html($trope);
	}

	$display = array();
	foreach ($names as $name) {
		$key = sanitize_title((string) $name);
		$display[] = $by_slug[$key] ?? bbb_books_like_ensure_emoji_label_html((string) $name);
	}

	return array_values(array_filter($display));
}

function bbb_books_like_trope_filter_value(array $trope): string {
	return sanitize_title((string) ($trope['slug'] ?? $trope['name'] ?? ''));
}
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-like<?php echo $is_paid_society_member ? ' is-unlocked' : ' is-preview'; ?>" data-books-like data-sss-lib="<?php echo esc_attr($is_paid_society_member ? 'society' : 'public'); ?>">
		<div class="bbb-like__wrap">
			<header class="bbb-like__hero">
				<button class="bbb-like__share" type="button" data-books-like-share aria-label="share this reading guide">
					<span aria-hidden="true">📲</span>
					<span data-books-like-share-label>share</span>
				</button>
				<p class="bbb-like__kicker">curated based on what you like</p>
				<h1 class="bbb-like__title">books with the same energy as <?php echo esc_html((string) $source['title']); ?></h1>
				<p class="bbb-like__subtext">you finished it. you stared at the ceiling. you need something that hits the exact same nerve — the same obsession, tension, and impossible-to-put-down feeling.</p>

				<div class="bbb-like__chips" aria-label="matched energy">
					<?php if (!empty($source['shelf']['name'])) : ?>
						<span class="bbb-like__chip"><?php echo wp_kses_post(bbb_books_like_ensure_emoji_label_html((string) $source['shelf']['name'])); ?></span>
					<?php endif; ?>
					<?php foreach ($source_tropes as $index => $trope) : ?>
						<?php $trope_filter = bbb_books_like_trope_filter_value($trope); ?>
						<?php if ($trope_filter !== '') : ?>
							<button class="bbb-like__chip bbb-like__filterChip<?php echo $index > 1 ? ' is-locked-chip' : ''; ?>" type="button" data-like-trope="<?php echo esc_attr($trope_filter); ?>" aria-pressed="false">
								<?php echo wp_kses_post(bbb_books_like_trope_display_html($trope)); ?>
							</button>
						<?php else : ?>
							<span class="bbb-like__chip<?php echo $index > 1 ? ' is-locked-chip' : ''; ?>"><?php echo wp_kses_post(bbb_books_like_trope_display_html($trope)); ?></span>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php if (!empty($source['boyfriend'])) : ?>
						<span class="bbb-like__chip is-locked-chip"><?php echo wp_kses_post(bbb_books_like_ensure_emoji_label_html((string) $source['boyfriend'])); ?></span>
					<?php endif; ?>
				</div>

				<div class="bbb-like__filters" data-like-filters>
					<label class="bbb-like__spiceFilter">
						<span>minimum spice</span>
						<input type="range" min="0" max="5" step="1" value="0" data-like-spice>
						<output data-like-spice-label>any heat</output>
					</label>
					<button class="bbb-like__clearFilters" type="button" data-like-clear hidden>clear filters</button>
				</div>
			</header>

			<div class="bbb-like__rule"></div>

			<article class="bbb-like__source sss-lib__book" data-book-preview <?php echo bbb_books_like_data_attrs($source); ?>>
				<div class="bbb-like__sourceCover sss-lib__coverWrap">
					<span class="sss-lib__heart bbb-like__heart" data-heart role="button" aria-label="save to your bookshelf">
						<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
						<span class="sss-lib__heartLabel" data-heart-label>save</span>
					</span>
					<?php if ((int) ($source['spice'] ?? 0) > 0) : ?>
						<div class="sss-lib__floatSpice bbb-like__floatSpice" aria-label="<?php echo esc_attr((string) $source['spice']); ?> spice">
							<?php echo esc_html(str_repeat('🌶', (int) $source['spice'])); ?>
						</div>
					<?php endif; ?>
					<?php if (!empty($source['cover'])) : ?>
						<img class="sss-lib__cover" src="<?php echo esc_url((string) $source['cover']); ?>" alt="<?php echo esc_attr((string) $source['title']); ?>" loading="lazy">
					<?php else : ?>
						<span aria-hidden="true">▮</span>
					<?php endif; ?>
				</div>
				<div class="bbb-like__sourceCopy">
					<p class="bbb-like__sourceKicker">you read</p>
					<h2><?php echo esc_html((string) $source['title']); ?></h2>
					<?php if (!empty($source['author'])) : ?>
						<p class="bbb-like__sourceAuthor"><?php echo esc_html((string) $source['author']); ?></p>
					<?php endif; ?>
					<?php if ((int) ($source['spice'] ?? 0) > 0) : ?>
						<div class="bbb-like__spice" aria-label="<?php echo esc_attr((string) $source['spice']); ?> spice level">
							<?php echo wp_kses_post(bbb_books_like_rating_dots((int) $source['spice'])); ?>
						</div>
					<?php endif; ?>
					<?php if ((int) ($source['darkness'] ?? 0) > 0) : ?>
						<div class="bbb-like__darkness" aria-label="<?php echo esc_attr((string) $source['darkness']); ?> darkness level">
							<span>darkness</span>
							<i aria-hidden="true"><?php echo esc_html(bbb_books_like_skulls((int) $source['darkness'])); ?></i>
							<em><?php echo esc_html((string) (int) $source['darkness']); ?>/5</em>
						</div>
					<?php endif; ?>
				</div>
			</article>

			<section class="bbb-like__matchesSection">
				<p class="bbb-like__sectionTitle">
					<?php echo esc_html((string) count($all_recommendations)); ?> books that hit the same nerve
				</p>
				<div class="bbb-like__list" data-like-list>
					<?php foreach ($recommendations as $index => $book) :
						$tags = !empty($book['shared_tropes'])
							? bbb_books_like_trope_display_values($book, $book['shared_tropes'])
							: array_map('bbb_books_like_ensure_emoji_label_html', array_filter(array($book['shelf']['name'] ?? '', $book['boyfriend'] ?? '')));
						$why  = (string) ($book['mini'] ?: $book['why']);
					?>
						<article class="bbb-like__match sss-lib__book" data-book-preview <?php echo bbb_books_like_data_attrs($book); ?>>
							<div class="bbb-like__matchRank"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></div>
							<div class="bbb-like__matchCover sss-lib__coverWrap">
								<span class="sss-lib__heart bbb-like__heart" data-heart role="button" aria-label="save to your bookshelf">
									<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
									<span class="sss-lib__heartLabel" data-heart-label>save</span>
								</span>
								<?php if ((int) ($book['spice'] ?? 0) > 0) : ?>
									<div class="sss-lib__floatSpice bbb-like__floatSpice" aria-label="<?php echo esc_attr((string) $book['spice']); ?> spice">
										<?php echo esc_html(str_repeat('🌶', (int) $book['spice'])); ?>
									</div>
								<?php endif; ?>
								<?php if (!empty($book['cover'])) : ?>
									<img class="sss-lib__cover" src="<?php echo esc_url((string) $book['cover']); ?>" alt="<?php echo esc_attr((string) $book['title']); ?>" loading="lazy">
								<?php endif; ?>
							</div>
							<div class="bbb-like__matchCopy">
								<h3><?php echo esc_html((string) $book['title']); ?></h3>
								<?php if (!empty($book['author'])) : ?>
									<p class="bbb-like__matchAuthor"><?php echo esc_html((string) $book['author']); ?></p>
								<?php endif; ?>
								<?php if ($tags) : ?>
									<div class="bbb-like__recTags">
										<?php foreach (array_slice($tags, 0, 3) as $tag) : ?>
											<span class="bbb-like__recTag"><?php echo wp_kses_post((string) $tag); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
								<?php if ((int) ($book['darkness'] ?? 0) > 0) : ?>
									<div class="bbb-like__darkness bbb-like__darkness--small" aria-label="<?php echo esc_attr((string) $book['darkness']); ?> darkness level">
										<span>darkness</span>
										<i aria-hidden="true"><?php echo esc_html(bbb_books_like_skulls((int) $book['darkness'])); ?></i>
										<em><?php echo esc_html((string) (int) $book['darkness']); ?>/5</em>
									</div>
								<?php endif; ?>
								<?php if ($why) : ?>
									<p class="bbb-like__whyKicker">why you'll love it</p>
									<p class="bbb-like__matchWhy"><?php echo esc_html($why); ?></p>
								<?php endif; ?>
								<div class="bbb-like__matchActions">
									<?php if (!empty($book['amazon'])) : ?>
										<a class="bbb-like__cta bbb-like__cta--amazon" href="<?php echo esc_url((string) $book['amazon']); ?>" target="_blank" rel="noopener">get on amazon</a>
									<?php endif; ?>
									<?php if (!empty($book['ku'])) : ?>
										<a class="bbb-like__cta bbb-like__cta--ku" href="<?php echo esc_url((string) ($book['amazon'] ?: $book['bookshop'] ?: '#')); ?>" target="_blank" rel="noopener">on kindle unlimited</a>
									<?php endif; ?>
									<?php if (empty($book['amazon']) && !empty($book['bookshop'])) : ?>
										<a class="bbb-like__cta bbb-like__cta--bookshop" href="<?php echo esc_url((string) $book['bookshop']); ?>" target="_blank" rel="noopener">get on bookshop</a>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<p class="bbb-like__emptyMatches" data-like-empty hidden>no visible matches for those filters yet. clear one and the list comes back.</p>

				<?php if (!$is_paid_society_member && $locked_count > 0) : ?>
					<div class="bbb-like__unlock" data-like-lock>
						<div>
							<span>society shelf</span>
							<p>+<?php echo esc_html((string) $locked_count); ?> more matching picks are waiting in the private library.</p>
						</div>
						<a href="<?php echo esc_url(get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe')); ?>">unlock the picks →</a>
					</div>
				<?php endif; ?>
			</section>

			<div class="bbb-like__rule"></div>

			<a class="bbb-like__quizNudge" href="<?php echo esc_url(home_url('/fictional-boyfriend-quiz/')); ?>">
				<span class="bbb-like__quizCover" aria-hidden="true">
					<?php if (!empty($source['cover'])) : ?>
						<img src="<?php echo esc_url((string) $source['cover']); ?>" alt="" loading="lazy">
					<?php else : ?>
						<span>♡</span>
					<?php endif; ?>
				</span>
				<span class="bbb-like__quizCopy">
					<strong>find your fictional match</strong>
				</span>
				<span aria-hidden="true">›</span>
			</a>

			<section class="bbb-like__newsletter">
				<h2>one perfect romance, every sunday</h2>
				<p>the smut &amp; sentiment society letter. morally gray men, sinful recs, soft feelings. free to join.</p>
				<a class="bbb-like__cta" href="https://thesmutandsentimentsociety.substack.com/subscribe" target="_blank" rel="noopener">join</a>
			</section>
		</div>
	</section>
</main>

<script>
document.addEventListener('click', function(event) {
	var share = event.target.closest('[data-books-like-share]');
	if (!share) return;
	if (navigator.share) {
		navigator.share({ title: document.title, url: window.location.href }).catch(function() {});
		return;
	}
	if (navigator.clipboard) {
		navigator.clipboard.writeText(window.location.href).then(function() {
			var label = share.querySelector('[data-books-like-share-label]') || share;
			label.textContent = 'copied';
			window.setTimeout(function() { label.textContent = 'share'; }, 1600);
		});
	}
});

(function() {
	var root = document.querySelector('[data-books-like]');
	if (!root) return;

	var spice = root.querySelector('[data-like-spice]');
	var spiceLabel = root.querySelector('[data-like-spice-label]');
	var clear = root.querySelector('[data-like-clear]');
	var empty = root.querySelector('[data-like-empty]');
	var chips = Array.prototype.slice.call(root.querySelectorAll('[data-like-trope]'));
	var matches = Array.prototype.slice.call(root.querySelectorAll('.bbb-like__match'));
	var selected = [];
	var labels = ['any heat', '1 pepper+', '2 peppers+', '3 peppers+', '4 peppers+', '5 peppers'];

	function bookTropes(book) {
		return String(book.getAttribute('data-tropes') || '')
			.split(',')
			.map(function(trope) { return trope.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''); })
			.filter(Boolean);
	}

	function applyFilters() {
		var minSpice = spice ? Number(spice.value || 0) : 0;
		var visible = 0;

		if (spiceLabel) {
			spiceLabel.textContent = labels[minSpice] || labels[0];
		}

		matches.forEach(function(book) {
			var spiceValue = Number(book.getAttribute('data-spice') || 0);
			var tropes = bookTropes(book);
			var spiceMatch = !minSpice || spiceValue >= minSpice;
			var tropeMatch = selected.length === 0 || selected.every(function(trope) { return tropes.indexOf(trope) !== -1; });
			var show = spiceMatch && tropeMatch;
			book.hidden = !show;
			if (show) visible += 1;
		});

		if (empty) {
			empty.hidden = visible !== 0;
		}
		if (clear) {
			clear.hidden = minSpice === 0 && selected.length === 0;
		}
	}

	chips.forEach(function(chip) {
		chip.addEventListener('click', function() {
			var value = chip.getAttribute('data-like-trope') || '';
			var index = selected.indexOf(value);
			if (index === -1) {
				selected.push(value);
				chip.setAttribute('aria-pressed', 'true');
			} else {
				selected.splice(index, 1);
				chip.setAttribute('aria-pressed', 'false');
			}
			applyFilters();
		});
	});

	if (spice) {
		spice.addEventListener('input', applyFilters);
	}
	if (clear) {
		clear.addEventListener('click', function() {
			selected = [];
			chips.forEach(function(chip) { chip.setAttribute('aria-pressed', 'false'); });
			if (spice) {
				spice.value = '0';
			}
			applyFilters();
		});
	}

	applyFilters();
}());
</script>

<?php
get_footer();
