<?php
/**
 * Blog archive template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$paged = (get_query_var('paged')) ? (int) get_query_var('paged') : 1;
$forced_category = isset($GLOBALS['bbb_forced_blog_category']) ? sanitize_title((string) $GLOBALS['bbb_forced_blog_category']) : '';
$archive_object  = get_queried_object();
$category_slug   = $forced_category;
if (!$category_slug && $archive_object instanceof WP_Term && 'category' === $archive_object->taxonomy) {
	$category_slug = $archive_object->slug;
}

$query_args = array(
	'post_type'      => 'post',
	'posts_per_page' => 10,
	'paged'          => $paged,
	'post_status'    => 'publish',
);
if ($category_slug) {
	$query_args['category_name'] = $category_slug;
}

$query = new WP_Query($query_args);
if ($forced_category && 0 === (int) $query->post_count) {
	$query_args_without_category = $query_args;
	unset($query_args_without_category['category_name']);
	$query_args_without_category['meta_query'] = array(
		array(
			'key'   => '_shopify_blog_handle',
			'value' => $forced_category,
		),
	);
	$query = new WP_Query($query_args_without_category);
}
$is_page_one = ($paged <= 1);

$archive_title = isset($archive_object->name) ? (string) $archive_object->name : get_the_archive_title();
if ($forced_category) {
	$forced_term   = get_category_by_slug($forced_category);
	$archive_title = $forced_term instanceof WP_Term ? $forced_term->name : ucwords(str_replace('-', ' ', $forced_category));
}

$archive_get_field = static function (string $key, $post_id = null, $default = null) {
	if (function_exists('bbb_get_field')) {
		return bbb_get_field($key, $post_id, $default);
	}

	if (function_exists('get_field')) {
		$value = get_field($key, $post_id);

		return (null !== $value && '' !== $value) ? $value : $default;
	}

	$value = get_post_meta((int) $post_id, $key, true);

	return ('' !== $value) ? $value : $default;
};

$archive_first_item = static function ($value) {
	if (is_array($value)) {
		return reset($value) ?: null;
	}

	return $value;
};

$archive_post_id = static function ($value): int {
	if ($value instanceof WP_Post) {
		return (int) $value->ID;
	}

	return is_numeric($value) ? (int) $value : 0;
};

$archive_image_url = static function ($image): string {
	if (is_array($image) && isset($image['url'])) {
		return (string) $image['url'];
	}

	if (is_numeric($image)) {
		return (string) wp_get_attachment_image_url((int) $image, 'medium');
	}

	return is_string($image) ? $image : '';
};

$archive_book_cover_url = static function (int $book_id) use ($archive_get_field, $archive_image_url): string {
	if ($book_id <= 0) {
		return '';
	}

	if (function_exists('bbb_get_book_cover_url')) {
		$cover = bbb_get_book_cover_url($book_id);
		if ($cover) {
			return $cover;
		}
	}

	if (function_exists('sss_get_book_cover_url')) {
		$cover = sss_get_book_cover_url($book_id);
		if ($cover) {
			return $cover;
		}
	}

	if (function_exists('sss_article_cover_url')) {
		$cover = sss_article_cover_url($book_id);
		if ($cover) {
			return $cover;
		}
	}

	$cover = $archive_image_url($archive_get_field('cover', $book_id, ''));
	if ($cover) {
		return $cover;
	}

	return get_the_post_thumbnail_url($book_id, 'medium') ?: '';
};

$archive_trope_data = static function ($trope) use ($archive_get_field): array {
	if ($trope instanceof WP_Term) {
		$name  = $trope->name;
		$emoji = function_exists('get_field') ? get_field('emoji', $trope->taxonomy . '_' . $trope->term_id) : get_term_meta($trope->term_id, 'emoji', true);

		return array(
			'name'  => $name,
			'emoji' => (string) $emoji,
			'slug'  => $trope->slug,
		);
	}

	$trope_id = ($trope instanceof WP_Post) ? (int) $trope->ID : (is_numeric($trope) ? (int) $trope : 0);

	if ($trope_id > 0) {
		return array(
			'name'  => (string) ($archive_get_field('name', $trope_id, '') ?: get_the_title($trope_id)),
			'emoji' => (string) $archive_get_field('emoji', $trope_id, ''),
			'slug'  => get_post_field('post_name', $trope_id),
		);
	}

	return array(
		'name'  => '',
		'emoji' => '',
		'slug'  => '',
	);
};

$archive_fallback_emoji = static function (string $slug, string $name = ''): string {
	if (function_exists('bbb_book_taxonomy_fallback_emoji')) {
		return bbb_book_taxonomy_fallback_emoji($slug, $name);
	}

	return '📚';
};

$archive_discovery_emoji = static function (string $slug, string $name = '', string $emoji = '', string $kind = 'trope') use ($archive_fallback_emoji): array {
	$key      = sanitize_title($slug ?: $name);
	$haystack = strtolower($key . ' ' . $name);
	$emoji    = trim($emoji);

	$emoji_map = array(
		'bully-romance-books'          => '😈',
		'bully-romance'                => '😈',
		'captor-captive-romance-books' => '🔐',
		'captor-x-captive'             => '🔐',
		'dark-academia-romance-books'  => '📚',
		'dark-academia'                => '📚',
		'dark-romance'                 => '🥀',
		'fake-dating-romance-books'    => '💍',
		'fake-dating-romance'          => '💍',
		'fated-mates-romance-books'    => '💜',
		'fated-mates'                  => '💜',
		'historical-romance'           => '🏰',
	);

	if (isset($emoji_map[$key])) {
		$emoji = $emoji_map[$key];
	} elseif (str_contains($haystack, 'captor') || str_contains($haystack, 'captive')) {
		$emoji = '🔐';
	} elseif (str_contains($haystack, 'fake dating')) {
		$emoji = '💍';
	} elseif (str_contains($haystack, 'fated mates')) {
		$emoji = '💜';
	} elseif (str_contains($haystack, 'dark romance')) {
		$emoji = '🥀';
	} elseif (str_contains($haystack, 'historical')) {
		$emoji = '🏰';
	} elseif ('' === $emoji) {
		$emoji = 'shelf' === $kind ? $archive_fallback_emoji($key, $name) : '📚';
	}

	$custom_emoji_html = function_exists('bbb_custom_emoji_html') ? bbb_custom_emoji_html($name, $key, 'blog-discovery__emojiImage') : '';

	return array(
		'emoji'      => $emoji,
		'emoji_html' => $custom_emoji_html,
	);
};

$archive_trope_page_url = static function (array $trope_item): string {
	$current_url = (string) ($trope_item['url'] ?? '');
	if ('' !== $current_url && !str_contains($current_url, 'curated-romance-guides')) {
		return $current_url;
	}

	$term = $trope_item['term'] ?? null;
	if ($term instanceof WP_Term && function_exists('bbb_book_taxonomy_term_url')) {
		return bbb_book_taxonomy_term_url($term);
	}

	$slug = sanitize_title((string) ($trope_item['slug'] ?? $trope_item['name'] ?? ''));
	if ('' === $slug) {
		return '#';
	}

	if (substr($slug, -6) !== '-books') {
		$slug .= '-books';
	}

	return home_url('/' . $slug . '/');
};

$archive_shelf_page_url = static function (array $shelf_item): string {
	$current_url = (string) ($shelf_item['url'] ?? '');
	if ('' !== $current_url && !str_contains($current_url, 'curated-romance-guides') && !str_contains($current_url, '/blog/')) {
		return $current_url;
	}

	$term = $shelf_item['term'] ?? null;
	if ($term instanceof WP_Term && function_exists('bbb_book_taxonomy_term_url')) {
		return bbb_book_taxonomy_term_url($term);
	}

	$slug = sanitize_title((string) ($shelf_item['slug'] ?? $shelf_item['name'] ?? ''));
	if ('' === $slug) {
		return '#';
	}

	if (substr($slug, -6) !== '-books') {
		$slug .= '-books';
	}

	return home_url('/' . $slug . '/');
};

$current_issue = null;
$trope_one     = null;
$trope_two     = null;

if ($is_page_one) {
	if (function_exists('sss_get_current_newsletter_issue')) {
		$current_issue = sss_get_current_newsletter_issue();
	}

	if ($current_issue) {
		$book = function_exists('sss_get_obsession_book') ? sss_get_obsession_book($current_issue) : null;
		if (!$book) {
			$book = $archive_first_item($archive_get_field('book', $current_issue->ID, array()));
		}
		$book_id = $archive_post_id($book);
		$tropes  = $book_id > 0 ? (array) $archive_get_field('tropes', $book_id, array()) : array();
		$tropes  = array_values(array_filter(array_map($archive_trope_data, $tropes), static fn(array $trope): bool => '' !== $trope['name']));
		if (!$tropes && $book_id > 0 && function_exists('sss_article_tropes')) {
			$tropes = array_values(array_filter(array_map(
				static fn(array $trope): array => array('name' => $trope['name'] ?? '', 'emoji' => $trope['emoji'] ?? '', 'slug' => $trope['slug'] ?? $trope['handle'] ?? ''),
				sss_article_tropes($book_id)
			), static fn(array $trope): bool => '' !== $trope['name']));
		}

		$trope_one = $tropes[0] ?? null;
		$trope_two = $tropes[1] ?? null;
	}
}

$trope_pages = $is_page_one ? get_posts(
	array(
		'post_type'      => 'page',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_key'       => '_wp_page_template',
		'meta_value'     => 'page-trope.php',
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
	)
) : array();

$trope_items = array();
foreach ($trope_pages as $trope_page) {
	$trope_name  = (string) $archive_get_field('trope_name', $trope_page->ID, get_the_title($trope_page->ID));
	$trope_emoji = $archive_discovery_emoji($trope_page->post_name, $trope_name, (string) $archive_get_field('trope_emoji', $trope_page->ID, ''), 'trope');
	$trope_items[$trope_page->post_name] = array(
		'url'        => get_permalink($trope_page->ID),
		'name'       => $trope_name,
		'slug'       => $trope_page->post_name,
		'emoji'      => $trope_emoji['emoji'],
		'emoji_html' => $trope_emoji['emoji_html'],
		'bg'         => (string) $archive_get_field('trope_bg_color', $trope_page->ID, '#ff8ac7'),
		'text'       => (string) $archive_get_field('trope_text_color', $trope_page->ID, '#ffb0d8'),
	);
}
if ($is_page_one && function_exists('bbb_get_book_taxonomy_discovery_items')) {
	foreach (bbb_get_book_taxonomy_discovery_items('trope') as $item) {
		$key = sanitize_title((string) ($item['name'] ?? ''));
		if ($key && !isset($trope_items[$key])) {
			$trope_emoji = $archive_discovery_emoji($key, (string) ($item['name'] ?? ''), (string) ($item['emoji'] ?? ''), 'trope');
			$item['emoji'] = $trope_emoji['emoji'];
			$item['emoji_html'] = $trope_emoji['emoji_html'];
			$trope_items[$key] = $item;
		}
	}
}
$trope_items = array_values($trope_items);

$shelf_pages = $is_page_one ? get_posts(
	array(
		'post_type'      => 'page',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_key'       => '_wp_page_template',
		'meta_value'     => 'page-shelf.php',
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
	)
) : array();

$shelf_items = array();
foreach ($shelf_pages as $shelf_page) {
	$shelf_name  = (string) $archive_get_field('shelf_name', $shelf_page->ID, get_the_title($shelf_page->ID));
	$shelf_emoji = $archive_discovery_emoji($shelf_page->post_name, $shelf_name, (string) $archive_get_field('shelf_emoji', $shelf_page->ID, ''), 'shelf');
	$shelf_items[$shelf_page->post_name] = array(
		'url'        => get_permalink($shelf_page->ID),
		'name'       => $shelf_name,
		'slug'       => $shelf_page->post_name,
		'emoji'      => $shelf_emoji['emoji'],
		'emoji_html' => $shelf_emoji['emoji_html'],
		'description'=> (string) $archive_get_field('shelf_description', $shelf_page->ID, ''),
	);
}
if ($is_page_one && function_exists('bbb_get_book_taxonomy_discovery_items')) {
	foreach (bbb_get_book_taxonomy_discovery_items('shelf') as $item) {
		$key = sanitize_title((string) ($item['name'] ?? ''));
		if ($key && !isset($shelf_items[$key])) {
			$shelf_emoji = $archive_discovery_emoji($key, (string) ($item['name'] ?? ''), (string) ($item['emoji'] ?? ''), 'shelf');
			$item['emoji'] = $shelf_emoji['emoji'];
			$item['emoji_html'] = $shelf_emoji['emoji_html'];
			$shelf_items[$key] = $item;
		}
	}
}
$shelf_items = array_values($shelf_items);

$rec_pick_title       = (string) $archive_get_field('rec_pick_title', 'option', '');
$rec_result_title     = (string) $archive_get_field('rec_result_title', 'option', '');
$tease_pick_book      = $rec_pick_title ? get_page_by_path(sanitize_title($rec_pick_title), OBJECT, array('bbb_book', 'sss_book', 'library_book')) : null;
$tease_result_book    = $rec_result_title ? get_page_by_path(sanitize_title($rec_result_title), OBJECT, array('bbb_book', 'sss_book', 'library_book')) : null;

if ((!$tease_pick_book || !$tease_result_book) && $is_page_one) {
	$tease_fallback_books = get_posts(
		array(
			'post_type'      => array('bbb_book', 'sss_book', 'library_book'),
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	foreach ($tease_fallback_books as $fallback_book) {
		if (!$fallback_book instanceof WP_Post || !$archive_book_cover_url((int) $fallback_book->ID)) {
			continue;
		}

		if (!$tease_pick_book) {
			$tease_pick_book = $fallback_book;
			continue;
		}

		if (!$tease_result_book && (int) $fallback_book->ID !== (int) $tease_pick_book->ID) {
			$tease_result_book = $fallback_book;
			break;
		}
	}
}

if (!$rec_pick_title && $tease_pick_book instanceof WP_Post) {
	$rec_pick_title = get_the_title($tease_pick_book);
}

if (!$rec_result_title && $tease_result_book instanceof WP_Post) {
	$rec_result_title = get_the_title($tease_result_book);
}

$tease_pick_cover_url   = $tease_pick_book instanceof WP_Post ? $archive_book_cover_url((int) $tease_pick_book->ID) : '';
$tease_result_cover_url = $tease_result_book instanceof WP_Post ? $archive_book_cover_url((int) $tease_result_book->ID) : '';
$rec_demo_cta           = (string) $archive_get_field('rec_title', 'option', 'open the matcher');
if ('' === trim($rec_demo_cta)) {
	$rec_demo_cta = 'open the matcher';
}

$featured_slugs = array(
	'the-ultimate-dark-romance-reading-guide',
	'the-ultimate-romantasy-reading-guide',
	'the-ultimate-sports-romance-reading-guide',
);
$featured_guides = array();
foreach ($featured_slugs as $featured_slug) {
	$featured_post = get_page_by_path($featured_slug, OBJECT, 'post');
	if (!$featured_post instanceof WP_Post || 'publish' !== $featured_post->post_status) {
		continue;
	}

	$featured_guides[] = array(
		'url'      => get_permalink($featured_post),
		'title'    => get_the_title($featured_post),
		'date'     => get_the_date('F j, Y', $featured_post),
		'date_iso' => get_the_date('Y-m-d', $featured_post),
	);
}

get_header();
?>

<div class="main-blog page-width">

	<div class="main-blog__hero">
		<p class="main-blog__eyebrow">romance guide archive</p>
		<h1 class="title--primary"><?php echo esc_html($archive_title); ?></h1>
		<p class="main-blog__intro">
			curated romance reading guides for readers looking for their next obsession,
			from trope deep dives to booktok favorite series.
		</p>
	</div>

	<?php if ($is_page_one) : ?>
		<?php if ($current_issue && $trope_one) : ?>
			<?php
			$trope_one_emoji_html = function_exists('bbb_trope_emoji_html') ? bbb_trope_emoji_html((string) ($trope_one['name'] ?? ''), $trope_one['emoji'] ?? '', (string) ($trope_one['slug'] ?? '')) : '';
			$trope_two_emoji_html = $trope_two && function_exists('bbb_trope_emoji_html') ? bbb_trope_emoji_html((string) ($trope_two['name'] ?? ''), $trope_two['emoji'] ?? '', (string) ($trope_two['slug'] ?? '')) : '';
			$trope_one_url        = function_exists('bbb_trope_page_url') ? bbb_trope_page_url((string) ($trope_one['name'] ?? ''), (string) ($trope_one['slug'] ?? '')) : $archive_trope_page_url($trope_one);
			$trope_two_url        = $trope_two ? (function_exists('bbb_trope_page_url') ? bbb_trope_page_url((string) ($trope_two['name'] ?? ''), (string) ($trope_two['slug'] ?? '')) : $archive_trope_page_url($trope_two)) : '';
			?>
			<a class="blog-obsession-banner" href="/pages/weekly-obsession"
				aria-label="see this week's weekly obsession">
				<p class="blog-obsession-banner__eyebrow">see the book everyone is talking about</p>
				<p class="blog-obsession-banner__text">
					what the society is obsessed with this week...
					think
					<span class="blog-obsession-banner__trope" data-trope-url="<?php echo esc_url($trope_one_url); ?>" role="link" tabindex="0">
						<?php if ('' !== $trope_one_emoji_html) : ?>
							<span class="blog-obsession-banner__tropeEmoji"><?php echo wp_kses_post($trope_one_emoji_html); ?></span>
						<?php endif; ?>
						<?php echo esc_html($trope_one['name']); ?>
					</span>
					<?php if ($trope_two) : ?>
						and
						<span class="blog-obsession-banner__trope" data-trope-url="<?php echo esc_url($trope_two_url); ?>" role="link" tabindex="0">
							<?php if ('' !== $trope_two_emoji_html) : ?>
								<span class="blog-obsession-banner__tropeEmoji"><?php echo wp_kses_post($trope_two_emoji_html); ?></span>
							<?php endif; ?>
							<?php echo esc_html($trope_two['name']); ?>
						</span>
					<?php endif; ?>
					<span class="blog-obsession-banner__cta">see the weekly obsession</span>
				</p>
			</a>
		<?php endif; ?>

		<section class="blog-discovery blog-discovery--tropes" aria-labelledby="blog-discovery-tropes">
			<div class="blog-discovery__header">
				<p class="blog-discovery__kicker">start small</p>
				<h2 class="blog-discovery__title" id="blog-discovery-tropes">explore books by trope</h2>
				<a class="blog-discovery__headerLink" href="<?php echo esc_url(home_url('/romance-trope-dictionary/')); ?>">see all tropes →</a>
			</div>

			<div class="blog-discovery__tropeStage" data-trope-rotator>
				<?php foreach (array_chunk($trope_items, 6) as $set_index => $set) : ?>
					<div class="blog-discovery__grid blog-discovery__grid--tropes blog-discovery__tropeSet <?php echo 0 === $set_index ? 'is-active' : ''; ?>"
						data-trope-set
						<?php echo 0 !== $set_index ? 'hidden' : ''; ?>>

						<?php
						foreach ($set as $trope_item) :
							$emoji      = (string) ($trope_item['emoji'] ?? '');
							$emoji_html = (string) ($trope_item['emoji_html'] ?? '');
							$name       = (string) ($trope_item['name'] ?? '');
							$trope_url  = $archive_trope_page_url($trope_item);
							?>
							<a href="<?php echo esc_url($trope_url); ?>"
								class="blog-discovery__card blog-discovery__card--trope">
								<?php if ($emoji || $emoji_html) : ?>
									<span class="blog-discovery__emoji"><?php echo '' !== $emoji_html ? wp_kses_post($emoji_html) : esc_html($emoji); ?></span>
								<?php endif; ?>
								<span class="blog-discovery__name"><?php echo esc_html($name); ?></span>
							</a>
						<?php endforeach; ?>

					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="blog-discovery blog-discovery--shelves" aria-labelledby="blog-discovery-shelves">
			<div class="blog-discovery__header">
				<p class="blog-discovery__kicker">go wider</p>
				<h2 class="blog-discovery__title" id="blog-discovery-shelves">browse by genre</h2>
			</div>

			<div class="blog-discovery__grid blog-discovery__grid--shelves">
				<?php
				foreach ($shelf_items as $shelf_item) :
					$name        = (string) ($shelf_item['name'] ?? '');
					$emoji       = (string) ($shelf_item['emoji'] ?? '');
					$emoji_html  = (string) ($shelf_item['emoji_html'] ?? '');
					$description = (string) ($shelf_item['description'] ?? '');
					$shelf_url   = $archive_shelf_page_url($shelf_item);
					if (!$name) {
						continue;
					}
					?>
					<a href="<?php echo esc_url($shelf_url); ?>"
						class="blog-discovery__card blog-discovery__card--shelf">
						<div class="blog-discovery__content">
							<div class="blog-discovery__line">
								<?php if ($emoji || $emoji_html) : ?>
									<span class="blog-discovery__emoji"><?php echo '' !== $emoji_html ? wp_kses_post($emoji_html) : esc_html($emoji); ?></span>
								<?php endif; ?>
								<span class="blog-discovery__name"><?php echo esc_html($name); ?></span>
							</div>
							<?php if ($description) : ?>
								<p class="blog-discovery__description"><?php echo esc_html($description); ?></p>
							<?php endif; ?>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<a class="blog-spice-callout" href="/pages/romance-books-by-spice-level">
			<span class="blog-spice-callout__rain" aria-hidden="true">
				<span>🌶</span><span>🌶</span><span>🌶</span><span>🌶</span><span>🌶</span>
			</span>
			<span class="blog-spice-callout__kicker">or choose the heat first</span>
			<span class="blog-spice-callout__text">
				browse romance by spice level and go straight to the kind of spice you want →
			</span>
		</a>

		<a href="<?php echo esc_url($archive_get_field('rec_link', 'option', '/pages/what-to-read-next') ?: '/pages/what-to-read-next'); ?>"
			class="bbb-homeRecDemo bbb-homeRecDemo--guides">
			<div class="bbb-homeRecDemo__copy">
				<div class="bbb-homeRecDemo__kicker"><?php echo esc_html((string) $archive_get_field('rec_kicker', 'option', '')); ?></div>
				<div class="bbb-homeRecDemo__title"><span>if you liked...</span></div>
				<div class="bbb-homeRecDemo__sub">read this next</div>
				<div class="bbb-homeRecDemo__cta"><?php echo esc_html($rec_demo_cta); ?></div>
			</div>

			<div class="bbb-homeRecDemo__stage" aria-hidden="true">
				<div class="bbb-homeRecDemo__book bbb-homeRecDemo__book--picked">
					<div class="bbb-homeRecDemo__label">you picked</div>
					<?php if ($tease_pick_cover_url) : ?>
						<img src="<?php echo esc_url($tease_pick_cover_url); ?>"
							alt="<?php echo esc_attr($rec_pick_title); ?>"
							loading="lazy">
					<?php endif; ?>
					<div class="bbb-homeRecDemo__meta">
						<div class="bbb-homeRecDemo__bookTitle">
							<?php echo esc_html($rec_pick_title); ?>
						</div>
					</div>
				</div>

				<div class="bbb-homeRecDemo__book bbb-homeRecDemo__book--result">
					<div class="bbb-homeRecDemo__label">read this next</div>
					<?php if ($tease_result_cover_url) : ?>
						<img src="<?php echo esc_url($tease_result_cover_url); ?>"
							alt="<?php echo esc_attr($rec_result_title); ?>"
							loading="lazy">
					<?php endif; ?>
					<div class="bbb-homeRecDemo__meta">
						<div class="bbb-homeRecDemo__bookTitle">
							<?php echo esc_html($rec_result_title); ?>
						</div>
					</div>
				</div>
			</div>
		</a>
	<?php endif; ?>

	<section class="main-blog__posts" aria-labelledby="main-blog-posts">
		<div class="blog-discovery__header blog-discovery__header--posts">
			<p class="blog-discovery__kicker">go deeper</p>
			<h2 class="blog-discovery__title" id="main-blog-posts">from the romance guides</h2>
		</div>

		<?php if ($is_page_one) : ?>
			<div class="blog-ultimate-guides" aria-label="ultimate romance guide collection">
				<div class="blog-ultimate-guides__head">
					<p class="blog-ultimate-guides__kicker">ultimate guides</p>
					<p class="blog-ultimate-guides__copy">start with the big, full-shelf guides first.</p>
				</div>
				<div class="blog-articles blog-articles--ultimate">
					<?php foreach ($featured_guides as $guide) : ?>
						<div class="blog-articles__article blog-articles__article--ultimate">
							<article class="article-card-wrapper article-card-wrapper--list underline-links-hover">
								<a href="<?php echo esc_url($guide['url']); ?>"
									class="article-card article-card--list full-unstyled-link">
									<div class="article-card__content article-card__content--list">
										<div class="article-card__meta article-card__meta--list">
											<span class="article-card__info article-card__info--list">
												<time datetime="<?php echo esc_attr($guide['date_iso']); ?>">
													<?php echo esc_html($guide['date']); ?>
												</time>
											</span>
										</div>
										<h3 class="article-card__heading article-card__heading--list">
											<?php echo esc_html($guide['title']); ?>
										</h3>
									</div>
								</a>
							</article>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="blog-articles">
			<?php while ($query->have_posts()) : ?>
				<?php
				$query->the_post();
				if ($is_page_one && in_array(get_post_field('post_name'), $featured_slugs, true)) {
					continue;
				}
				?>
				<div class="blog-articles__article">
					<?php get_template_part('template-parts/article-card'); ?>
				</div>
			<?php endwhile; ?>
		</div>
	</section>

	<?php if ($query->max_num_pages > 1) : ?>
		<nav class="pagination" aria-label="Blog navigation">
			<?php
			$pagination_base = $forced_category
				? home_url('/' . trim($forced_category, '/') . '/page/%#%/')
				: str_replace(999999999, '%#%', get_pagenum_link(999999999, false));
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => $pagination_base,
						'format'    => '',
						'current'   => max(1, $paged),
						'total'     => $query->max_num_pages,
						'prev_text' => '&larr; previous',
						'next_text' => 'next &rarr;',
						'type'      => 'plain',
					)
				)
			);
			?>
		</nav>
	<?php endif; ?>

</div>

<?php
wp_reset_postdata();
get_footer();
