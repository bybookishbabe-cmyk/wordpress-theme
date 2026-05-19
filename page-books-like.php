<?php
/**
 * Shopify-compatible "books like source book" page.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$source_post = function_exists('bbb_books_like_current_source_book') ? bbb_books_like_current_source_book() : null;
if (!$source_post instanceof WP_Post) {
	require get_theme_file_path('page-books-like-directory.php');
	return;
}

wp_enqueue_style('bbb-books-like', get_theme_file_uri('assets/css/books-like.css'), array('bbb-sss-library'), wp_get_theme()->get('Version'));
get_header();

$source = bbb_books_like_book_data($source_post->ID);
$recommendations = array_slice(bbb_books_like_recommendations($source_post->ID), 0, 9);
$source_tropes = array_slice((array) $source['tropes'], 0, 5);
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-like" data-books-like data-sss-lib="public">
		<div class="bbb-like__wrap">
			<header class="bbb-like__hero">
				<div class="bbb-like__top">
					<div>
						<p class="bbb-like__kicker">read this next</p>
						<h1 class="bbb-like__title">books with the same energy as <?php echo esc_html((string) $source['title']); ?></h1>
						<p class="bbb-like__subtext">a personally matched stack based on shelf, tropes, spice, tension, darkness, emotional damage, and overall reading mood.</p>
					</div>
					<button class="bbb-like__share" type="button" data-books-like-share>share</button>
				</div>

				<div class="bbb-like__chips">
					<?php if (!empty($source['shelf']['name'])) : ?>
						<span class="bbb-like__chip"><?php echo esc_html((string) $source['shelf']['name']); ?></span>
					<?php endif; ?>
					<?php foreach ($source_tropes as $trope) : ?>
						<span class="bbb-like__chip"><?php echo esc_html(trim(((string) ($trope['emoji'] ?? '')) . ' ' . ((string) ($trope['name'] ?? '')))); ?></span>
					<?php endforeach; ?>
					<?php if (!empty($source['boyfriend'])) : ?>
						<span class="bbb-like__chip"><?php echo esc_html((string) $source['boyfriend']); ?></span>
					<?php endif; ?>
				</div>
			</header>

			<div class="bbb-like__sourceGrid">
				<article class="bbb-like__sourceCard sss-lib__book" data-book-preview <?php echo bbb_books_like_data_attrs($source); ?>>
					<div class="bbb-like__sourceCover">
						<?php if (!empty($source['cover'])) : ?>
							<img src="<?php echo esc_url((string) $source['cover']); ?>" alt="<?php echo esc_attr((string) $source['title']); ?>" loading="lazy">
						<?php endif; ?>
					</div>
					<?php if ((int) ($source['spice'] ?? 0) > 0) : ?>
						<div class="bbb-like__sourceSpice"><?php echo esc_html(str_repeat('🌶', (int) $source['spice'])); ?></div>
					<?php endif; ?>
				</article>
				<div class="bbb-like__sourceMeta">
					<p class="bbb-like__sectionKicker">the source read</p>
					<h2><?php echo esc_html((string) $source['title']); ?></h2>
					<?php if (!empty($source['author'])) : ?>
						<div class="bbb-like__sourceAuthor">by <?php echo esc_html((string) $source['author']); ?></div>
					<?php endif; ?>
					<?php if (!empty($source['mini'])) : ?>
						<p><?php echo esc_html((string) $source['mini']); ?></p>
					<?php elseif (!empty($source['why'])) : ?>
						<p><?php echo esc_html((string) $source['why']); ?></p>
					<?php endif; ?>
					<div class="bbb-like__stats">
						<div class="bbb-like__stat"><span>spice</span><?php echo esc_html((string) ((int) ($source['spice'] ?? 0) ?: '')); ?>/5</div>
						<div class="bbb-like__stat"><span>tension</span><?php echo esc_html((string) ($source['tension'] ?? '')); ?></div>
						<div class="bbb-like__stat"><span>damage</span><?php echo esc_html((string) ($source['damage'] ?? '')); ?></div>
						<div class="bbb-like__stat"><span>yearning</span><?php echo esc_html((string) ($source['yearning'] ?? '')); ?></div>
					</div>
				</div>
			</div>

			<section class="bbb-like__section" data-like-list>
				<p class="bbb-like__sectionKicker">best matches</p>
				<h2 class="bbb-like__sectionTitle">what to read after <?php echo esc_html((string) $source['title']); ?></h2>
				<div class="bbb-like__matches">
					<?php foreach ($recommendations as $book) :
						$tags = $book['shared_tropes'] ?: array_filter(array($book['shelf']['name'] ?? '', $book['boyfriend'] ?? ''));
					?>
						<button type="button" class="bbb-like__rec sss-lib__book" data-book-preview <?php echo bbb_books_like_data_attrs($book); ?>>
							<div class="bbb-like__recMedia">
								<?php if (!empty($book['cover'])) : ?>
									<img src="<?php echo esc_url((string) $book['cover']); ?>" alt="<?php echo esc_attr((string) $book['title']); ?>" loading="lazy">
								<?php endif; ?>
								<?php if ((int) ($book['spice'] ?? 0) > 0) : ?>
									<div class="bbb-like__recSpice"><?php echo esc_html(str_repeat('🌶', (int) $book['spice'])); ?></div>
								<?php endif; ?>
							</div>
							<div class="bbb-like__recCopy">
								<p class="bbb-like__recScore"><?php echo esc_html((string) round((float) $book['score'])); ?> match points</p>
								<h3><?php echo esc_html((string) $book['title']); ?></h3>
								<?php if (!empty($book['author'])) : ?>
									<p class="bbb-like__recAuthor">by <?php echo esc_html((string) $book['author']); ?></p>
								<?php endif; ?>
								<?php if (!empty($book['mini'])) : ?>
									<p class="bbb-like__recWhy"><?php echo esc_html(wp_trim_words((string) $book['mini'], 24, '')); ?></p>
								<?php endif; ?>
								<?php if ($tags) : ?>
									<div class="bbb-like__recTags">
										<?php foreach (array_slice($tags, 0, 4) as $tag) : ?>
											<span class="bbb-like__recTag"><?php echo esc_html((string) $tag); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						</button>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="bbb-like__lock" data-like-lock>
				<h2>want the deeper cut?</h2>
				<p class="bbb-like__subtext">save this list to your bookshelf, then keep going through the full library when your mood gets more specific.</p>
				<a class="bbb-like__cta" href="<?php echo esc_url(home_url('/library/')); ?>">explore library</a>
			</section>

			<section class="bbb-like__quiz">
				<h2>not quite the mood?</h2>
				<p class="bbb-like__subtext">take the reader quiz and let the site narrow it down by trope, spice, chaos level, and emotional damage.</p>
				<a class="bbb-like__cta" href="<?php echo esc_url(home_url('/reader-quizes/')); ?>">find your read</a>
			</section>

			<section class="bbb-like__newsletter">
				<h2>get next week's obsession</h2>
				<p class="bbb-like__subtext">new romance guides, weekly obsession picks, and unhinged-but-useful reading notes.</p>
				<a class="bbb-like__cta" href="https://thesmutandsentimentsociety.substack.com/subscribe" target="_blank" rel="noopener">join the newsletter</a>
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
			share.textContent = 'copied';
			window.setTimeout(function() { share.textContent = 'share'; }, 1600);
		});
	}
});
</script>

<?php
get_footer();
