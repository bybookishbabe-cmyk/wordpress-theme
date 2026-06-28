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

$source                    = bbb_books_like_book_data($source_post->ID);
$has_society_member_access = function_exists('bbb_reader_has_member_identity')
	? bbb_reader_has_member_identity()
	: (function_exists('bbb_society_reader_has_member_access') && bbb_society_reader_has_member_access());
if (!$has_society_member_access && function_exists('bbb_society_render_locked_preview_page')) {
	get_header();
	bbb_society_render_locked_preview_page(
		array(
			'access'      => 'member',
			'kicker'      => 'member reading guide',
			'title'       => 'if you liked ' . (string) ($source['title'] ?? 'these books'),
			'intro'       => 'these curated reading guides are tucked behind the society email wall.',
			'panel_title' => 'enter your society email to unlock',
			'panel_copy'  => 'free and paid society members can open these pages. paid access is not required.',
			'cta'         => 'open account',
			'cta_url'     => home_url('/account/'),
			'items'       => array('curated books-like pages', 'same-energy recommendations', 'member-only reader routes'),
		)
	);
	get_footer();
	return;
}

get_header();

$all_recommendations       = array_slice(bbb_books_like_recommendations($source_post->ID), 0, 7);
$recommendations           = $has_society_member_access ? $all_recommendations : array();
$locked_count              = max(0, count($all_recommendations) - count($recommendations));
$source_tropes             = array_slice((array) $source['tropes'], 0, 5);

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
	<section class="bbb-like<?php echo $has_society_member_access ? ' is-unlocked' : ' is-preview'; ?>" data-books-like data-sss-lib="<?php echo esc_attr($has_society_member_access ? 'society' : 'public'); ?>">
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
					<div class="bbb-like__spiceFilter">
						<span>minimum spice</span>
						<input class="bbb-like__spiceRange" type="range" min="0" max="5" step="1" value="0" data-like-spice aria-label="minimum spice">
						<div class="bbb-like__spiceChoices" role="radiogroup" aria-label="minimum spice level">
							<?php
							$books_like_spice_profiles = function_exists('bbb_reader_spice_profiles') ? bbb_reader_spice_profiles() : array(
								1 => array('label' => 'soft spice', 'peppers' => '🌶'),
								2 => array('label' => 'some heat', 'peppers' => '🌶🌶'),
								3 => array('label' => 'balanced', 'peppers' => '🌶🌶🌶'),
								4 => array('label' => 'high spice', 'peppers' => '🌶🌶🌶🌶'),
								5 => array('label' => 'wreck me', 'peppers' => '🌶🌶🌶🌶🌶'),
							);
							?>
							<?php foreach ($books_like_spice_profiles as $level => $profile) : ?>
								<button
									type="button"
									class="bbb-like__spiceChoice"
									role="radio"
									aria-checked="false"
									data-like-spice-choice="<?php echo esc_attr((string) $level); ?>"
								>
									<span><?php echo esc_html((string) ($profile['peppers'] ?? '')); ?></span>
									<strong><?php echo esc_html((string) ($profile['label'] ?? '')); ?></strong>
								</button>
							<?php endforeach; ?>
						</div>
						<output data-like-spice-label>any heat</output>
					</div>
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
						<img class="sss-lib__cover" src="<?php echo esc_url((string) $source['cover']); ?>" alt="<?php echo esc_attr(function_exists('bbb_book_cover_alt') ? bbb_book_cover_alt((string) $source['title'], (string) ($source['author'] ?? ''), (string) ($source['shelf']['name'] ?? '')) : (string) $source['title'] . ' book cover'); ?>" loading="lazy">
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
				</div>
			</article>

			<section class="bbb-like__matchesSection">
				<p class="bbb-like__sectionTitle">
					<?php echo esc_html((string) count($all_recommendations)); ?> books that hit the same nerve
				</p>
				<div class="bbb-like__list" data-like-list>
					<?php foreach ($recommendations as $index => $book) :
							$all_tropes            = array_values(array_filter((array) ($book['tropes'] ?? array()), static fn($trope): bool => is_array($trope) && '' !== trim((string) ($trope['name'] ?? ''))));
							$matching_trope_keys   = array();
							foreach ((array) ($book['shared_tropes'] ?? array()) as $shared_trope) {
								$matching_trope_keys[] = sanitize_title((string) $shared_trope);
							}
							$why = (string) ($book['mini'] ?: $book['why']);
							$book_url = !empty($book['url'])
								? (string) $book['url']
								: (get_permalink((int) ($book['id'] ?? 0)) ?: home_url('/books/' . sanitize_title((string) ($book['handle'] ?? $book['title'] ?? '')) . '/'));
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
									<img class="sss-lib__cover" src="<?php echo esc_url((string) $book['cover']); ?>" alt="<?php echo esc_attr(function_exists('bbb_book_cover_alt') ? bbb_book_cover_alt((string) $book['title'], (string) ($book['author'] ?? ''), (string) ($book['shelf']['name'] ?? '')) : (string) $book['title'] . ' book cover'); ?>" loading="lazy">
								<?php endif; ?>
								</div>
								<div class="bbb-like__matchCopy">
									<h3><?php echo esc_html((string) $book['title']); ?></h3>
									<?php if (!empty($book['author'])) : ?>
										<p class="bbb-like__matchAuthor"><?php echo esc_html((string) $book['author']); ?></p>
									<?php endif; ?>
								<?php if ($all_tropes) : ?>
									<div class="bbb-like__tropeGroups">
										<div class="bbb-like__tropeGroup">
											<p class="bbb-like__tropeKicker">tropes</p>
											<div class="bbb-like__recTags bbb-like__recTags--all">
												<?php foreach ($all_tropes as $trope) :
													$trope_name = (string) ($trope['name'] ?? '');
													$trope_slug = (string) ($trope['slug'] ?? $trope_name);
													$is_match   = in_array(sanitize_title($trope_name), $matching_trope_keys, true) || in_array(sanitize_title($trope_slug), $matching_trope_keys, true);
												?>
													<span class="bbb-like__recTag<?php echo $is_match ? ' is-match' : ''; ?>"><?php echo wp_kses_post(bbb_books_like_trope_display_html($trope)); ?></span>
												<?php endforeach; ?>
											</div>
										</div>
									</div>
								<?php endif; ?>
									<?php if ($why) : ?>
										<p class="bbb-like__whyKicker">why you'll love it</p>
										<p class="bbb-like__matchWhy"><?php echo esc_html($why); ?></p>
									<?php endif; ?>
									<?php
									$series_handle = sanitize_title((string) ($book['series_handle'] ?? $book['series'] ?? ''));
									$series_name   = trim((string) ($book['series_name'] ?? ''));
									?>
									<div class="bbb-like__matchActions">
										<?php if (!empty($book['ku'])) : ?>
											<a class="bbb-like__cta bbb-like__cta--ku" href="<?php echo esc_url((string) ($book['amazon'] ?: $book['bookshop'] ?: '#')); ?>" target="_blank" rel="noopener">read free on kindle unlimited</a>
										<?php endif; ?>
										<?php if (!empty($book['amazon'])) : ?>
											<a class="bbb-like__cta bbb-like__cta--amazon" href="<?php echo esc_url((string) $book['amazon']); ?>" target="_blank" rel="noopener">buy on amazon <span>&middot; own it forever</span></a>
										<?php endif; ?>
										<?php if (!empty($book['bookshop'])) : ?>
											<a class="bbb-like__cta bbb-like__cta--bookshop" href="<?php echo esc_url((string) $book['bookshop']); ?>" target="_blank" rel="noopener">prefer indie? bookshop.org →</a>
										<?php endif; ?>
									</div>
									<div class="bbb-like__secondaryLinks" aria-label="more book links">
										<?php if ('' !== $series_handle && '' !== $series_name) : ?>
											<div class="bbb-like__secondaryRow">
												<span class="bbb-like__secondaryLabel">part of a series?</span>
												<a class="bbb-like__secondaryLink" href="<?php echo esc_url(home_url('/series/' . $series_handle . '/')); ?>"><?php echo esc_html(strtolower(function_exists('bbb_book_series_label') ? bbb_book_series_label($series_name) : $series_name)); ?> reading order →</a>
											</div>
										<?php endif; ?>
										<div class="bbb-like__secondaryRow">
											<span class="bbb-like__secondaryLabel">save it to your shelf</span>
											<a class="bbb-like__secondaryLink" href="<?php echo esc_url($book_url); ?>">view in library →</a>
										</div>
									</div>
								</div>
							</article>
					<?php endforeach; ?>
				</div>
				<p class="bbb-like__emptyMatches" data-like-empty hidden>no visible matches for those filters yet. clear one and the list comes back.</p>

				<?php if (!$has_society_member_access && $locked_count > 0) : ?>
					<div class="bbb-like__unlock" data-like-lock>
						<div>
							<span>society shelf</span>
							<p><?php echo esc_html((string) $locked_count); ?> matching picks are waiting behind the email wall.</p>
						</div>
						<a href="<?php echo esc_url(get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe')); ?>">unlock the picks →</a>
					</div>
				<?php endif; ?>
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
	var spiceChoices = Array.prototype.slice.call(root.querySelectorAll('[data-like-spice-choice]'));
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
		var minSpice = spice ? parseInt(spice.value || '0', 10) : 0;
		var visible = 0;

		if (spiceLabel) {
			spiceLabel.textContent = labels[minSpice] || labels[0];
		}
		spiceChoices.forEach(function(choice) {
			var level = Number(choice.getAttribute('data-like-spice-choice') || 0);
			var active = level === minSpice;
			choice.classList.toggle('is-active', active);
			choice.setAttribute('aria-checked', active ? 'true' : 'false');
		});

		matches.forEach(function(book) {
			var spiceValue = parseInt(book.getAttribute('data-spice') || '0', 10);
			var tropes = bookTropes(book);
			var spiceMatch = !minSpice || spiceValue >= minSpice;
			var tropeMatch = selected.length === 0 || selected.every(function(trope) { return tropes.indexOf(trope) !== -1; });
			var show = spiceMatch && tropeMatch;
			book.hidden = !show;
			book.classList.toggle('is-filtered-out', !show);
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
	spiceChoices.forEach(function(choice) {
		choice.addEventListener('click', function() {
			if (!spice) return;
			var value = String(choice.getAttribute('data-like-spice-choice') || '0');
			spice.value = spice.value === value ? '0' : value;
			spice.dispatchEvent(new Event('change', { bubbles: true }));
			applyFilters();
		});
	});
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
