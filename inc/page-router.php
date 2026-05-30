<?php
/**
 * Shopify page-route compatibility layer.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_page_route_registry(): array {
	$monthly_theme_template = current_time('Y-m-d H:i:s') >= '2026-06-01 00:00:00'
		? 'page-june-2026-monthly-theme.php'
		: 'page-societylibrary.php';

	return array(
		'artprints'                      => '',
		'about'                          => 'page-about.php',
		'book-reviews'                   => 'page-book-reviews.php',
		'bookish-templates'              => '',
		'book-tracking-calendar'         => 'page-book-tracking-calendar.php',
		'books-like'                     => 'page-books-like.php',
		'books-like-directory'           => 'page-books-like-directory.php',
		'bookshelf-weekly-preview'       => '',
		'account'                        => 'page-account.php',
		'about-the-society'              => 'page-about-the-society.php',
		'cart'                           => '',
		'contact'                        => 'page-contact.php',
		'come-in'                        => 'page-come-in.php',
		'curated-romance-guides'         => 'page-curated-romance-guides.php',
		'enemies-to-lovers'              => 'page-trope.php',
		'fictional-boyfriend-quiz'       => 'page-fictional-boyfriend-quiz.php',
		'find-your-read'                 => 'page-what-to-read-next.php',
		'for-readers'                    => '',
		'historical-romance-books'       => 'page-shelf.php',
		'if-you-liked-pages'             => 'page-if-you-liked-pages.php',
		'june-2026-monthly-theme'        => 'page-june-2026-monthly-theme.php',
		'burn-bright'                    => 'page-june-2026-monthly-theme.php',
		'kindle-insert-vault'            => '',
		'kindle-inserts'                 => '',
		'library'                        => 'page-library.php',
		'work-with-me'                   => 'page-media-kit.php',
		'monthly-freebie'                => 'page-monthly-freebie.php',
		'monthly-theme'                  => $monthly_theme_template,
		'my-bookshelf'                   => 'page-my-bookshelf.php',
		'my-vault'                       => '',
		'our-story'                      => 'page-our-story.php',
		'popular-pages'                  => 'page-popular-pages.php',
		'paranormal-romance-books'       => 'page-shelf.php',
		'privacy-policy'                 => '',
		'reader-mood-quiz'               => 'page-reader-mood-quiz.php',
		'reader-quizes'                  => 'page-reader-quizes.php',
		'reader-quizzes'                 => 'page-reader-quizes.php',
		'romance-trope-quiz'             => 'page-romance-trope-quiz.php',
		'romance-trope-dictionary'       => 'page-romance-trope-dictionary.php',
		'romance-book-moodboards'        => 'page-romance-book-moodboards.php',
		'reading-challenge'              => 'page-reading-challenge.php',
		'reading-list'                   => 'page-reading-list.php',
		'romance-books-by-spice-level'   => 'page-spice.php',
		'series'                         => 'page-series.php',
		'series-reading-orders'          => 'page-series-reading-orders.php',
		'shelf'                          => 'page-shelf.php',
		'shop'                           => 'page-shop.php',
		'slow-burn-books'                => 'page-trope.php',
		'smut-sentiment-society'         => 'page-smut-sentiment-society.php',
		'villain-gets-the-girl-books'    => 'page-trope.php',
		'newsletter-submissions'         => 'page-society-submissions.php',
		'society-newsletter-recent'      => 'page-society-newsletter-recent.php',
		'society-newsletter-archive'     => 'page-society-newsletter-archive.php',
		'society-library'                => 'page-societylibrary.php',
		'society-shop-discount'          => 'page-society-shop-discount.php',
		'society-submissions'            => 'page-society-submissions.php',
		'societylibrary'                 => 'page-societylibrary.php',
		'dark-romance-books'             => 'page-shelf.php',
		'romantasy-books'                => 'page-shelf.php',
		'sports-romance-books'           => 'page-shelf.php',
		'canva-templates'                => '',
		'freebies'                       => '',
		'kindle-inserts'                 => '',
		'member-library'                 => 'page-sss-library-page.php',
		'private-shelf'                  => '',
		'sss-canva-templates'            => '',
		'sss-freebies'                   => '',
		'sss-library'                    => 'page-sss-library.php',
		'sss-library-page'               => 'page-sss-library-page.php',
		'member-dashboard'               => 'page-sss-made-for-you.php',
		'sss-made-for-you'               => 'page-sss-made-for-you.php',
		'made-for-you'                   => 'page-sss-made-for-you.php',
		'sss-printable-kindle'           => '',
		'sss-printable-kindle-inserts'   => '',
		'sss-private-shelf'              => '',
		'sss-quote-wall'                 => 'page-sss-quote-wall.php',
		'quote-wall'                     => 'page-sss-quote-wall.php',
		'quote-library'                  => 'page-sss-quote-wall.php',
		'sss-series'                     => '',
		'sss-series-page'                => '',
		'weekly-obsession'               => 'page-weekly-obsession.php',
		'what-to-read-next'              => 'page-what-to-read-next.php',
	);
}

function bbb_current_route_slug(): string {
	$path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
	if ($path === '') {
		return '';
	}

	if (str_starts_with($path, 'pages/')) {
		$path = substr($path, strlen('pages/'));
	}

	if (str_starts_with($path, 'blogs/')) {
		$path = substr($path, strlen('blogs/'));
	}

	if ($path === 'collections/all') {
		$path = 'shop';
	}

	if (str_starts_with($path, 'collections/')) {
		$path = substr($path, strlen('collections/'));
	}

	if (str_starts_with($path, 'products/')) {
		$path = substr($path, strlen('products/'));
	}

	if (str_starts_with($path, 'product/')) {
		$path = substr($path, strlen('product/'));
	}

	if (str_starts_with($path, 'product-category/')) {
		$path = substr($path, strlen('product-category/'));
	}

	return sanitize_title(trim($path, '/'));
}

function bbb_route_template_for_slug(string $slug): string {
	$routes = bbb_page_route_registry();
	if (!array_key_exists($slug, $routes)) {
		return '';
	}

	$template = (string) $routes[$slug];
	if ($template === '') {
		return '';
	}

	$path = get_theme_file_path($template);

	return file_exists($path) ? $path : '';
}

function bbb_mark_virtual_route_found(bool $send_nocache_headers = true): void {
	global $post, $wp_query;

	$slug  = bbb_current_route_slug();
	$title = bbb_virtual_route_title($slug);
	$now   = current_time('mysql');
	$post  = new WP_Post(
		(object) array(
			'ID'                    => 0,
			'post_author'           => get_current_user_id() ?: 1,
			'post_date'             => $now,
			'post_date_gmt'         => get_gmt_from_date($now),
			'post_content'          => '',
			'post_title'            => $title,
			'post_excerpt'          => '',
			'post_status'           => 'publish',
			'comment_status'        => 'closed',
			'ping_status'           => 'closed',
			'post_password'         => '',
			'post_name'             => $slug,
			'to_ping'               => '',
			'pinged'                => '',
			'post_modified'         => $now,
			'post_modified_gmt'     => get_gmt_from_date($now),
			'post_content_filtered' => '',
			'post_parent'           => 0,
			'guid'                  => home_url('/' . trim($slug, '/') . '/'),
			'menu_order'            => 0,
			'post_type'             => 'page',
			'post_mime_type'        => '',
			'comment_count'         => 0,
			'filter'                => 'raw',
		)
	);

	if ($wp_query instanceof WP_Query) {
		$wp_query->is_404      = false;
		$wp_query->is_page     = true;
		$wp_query->is_singular = true;
		$wp_query->is_home     = false;
		$wp_query->is_archive  = false;
		$wp_query->post        = $post;
		$wp_query->posts       = array($post);
		$wp_query->post_count  = 1;
		$wp_query->found_posts = 1;
		$wp_query->queried_object = $post;
		$wp_query->queried_object_id = 0;
	}

	status_header(200);
	if ($send_nocache_headers) {
		nocache_headers();
	}
}

function bbb_virtual_route_title(string $slug): string {
	$request_path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
	if (preg_match('#^if-you-liked-pages/(?:books-like|if-you-liked)-([^/]+)/?$#', $request_path, $matches)) {
		return 'if you liked ' . ucwords(str_replace('-', ' ', sanitize_title($matches[1])));
	}

	return ucwords(str_replace('-', ' ', $slug));
}

function bbb_is_nested_books_like_route(): bool {
	$request_path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
	return (bool) preg_match('#^if-you-liked-pages/(?:books-like|if-you-liked)-[^/]+/?$#', $request_path);
}

function bbb_current_virtual_book_taxonomy_term(): ?WP_Term {
	if (!function_exists('bbb_find_book_taxonomy_term')) {
		return null;
	}

	$slug     = bbb_current_route_slug();
	$routes   = bbb_page_route_registry();
	$template = (string) ($routes[$slug] ?? '');
	if (!in_array($template, array('page-trope.php', 'page-shelf.php'), true)) {
		return null;
	}

	$kind = 'page-shelf.php' === $template ? 'shelf' : 'trope';
	return bbb_find_book_taxonomy_term($slug, $kind);
}

function bbb_virtual_book_taxonomy_meta_title(WP_Term $term): string {
	return bbb_virtual_route_title($term->slug . '-books') . ' - ' . get_bloginfo('name');
}

function bbb_virtual_book_taxonomy_meta_description(WP_Term $term): string {
	if (function_exists('bbb_book_taxonomy_term_description')) {
		$description = trim((string) bbb_book_taxonomy_term_description($term));
		if ('' !== $description) {
			return $description;
		}
	}

	$name = strtolower($term->name);
	$kind = function_exists('bbb_book_taxonomy_kind_for_taxonomy') ? bbb_book_taxonomy_kind_for_taxonomy($term->taxonomy) : 'trope';
	return 'shelf' === $kind
		? 'browse ' . $name . ' books in the bybookishbabe library.'
		: 'browse ' . $name . ' romance books in the bybookishbabe library.';
}

add_filter(
	'document_title_parts',
	static function (array $title): array {
		$slug = bbb_current_route_slug();
		if (!bbb_is_nested_books_like_route() && '' === bbb_route_template_for_slug($slug)) {
			return $title;
		}

		$title['title'] = bbb_virtual_route_title($slug);
		return $title;
	}
);

add_filter(
	'pre_get_document_title',
	static function (string $title): string {
		$slug = bbb_current_route_slug();
		if (!bbb_is_nested_books_like_route() && '' === bbb_route_template_for_slug($slug)) {
			return $title;
		}

		return bbb_virtual_route_title($slug) . ' - ' . get_bloginfo('name');
	},
	99
);

add_filter(
	'rank_math/frontend/title',
	static function (string $title): string {
		$term = bbb_current_virtual_book_taxonomy_term();
		if ($term instanceof WP_Term) {
			return bbb_virtual_book_taxonomy_meta_title($term);
		}

		return bbb_is_nested_books_like_route() ? bbb_virtual_route_title(bbb_current_route_slug()) . ' - ' . get_bloginfo('name') : $title;
	},
	99
);

add_filter(
	'rank_math/opengraph/facebook/title',
	static function (string $title): string {
		$term = bbb_current_virtual_book_taxonomy_term();
		if ($term instanceof WP_Term) {
			return bbb_virtual_book_taxonomy_meta_title($term);
		}

		return bbb_is_nested_books_like_route() ? bbb_virtual_route_title(bbb_current_route_slug()) . ' - ' . get_bloginfo('name') : $title;
	},
	99
);

add_filter(
	'rank_math/opengraph/twitter/title',
	static function (string $title): string {
		$term = bbb_current_virtual_book_taxonomy_term();
		if ($term instanceof WP_Term) {
			return bbb_virtual_book_taxonomy_meta_title($term);
		}

		return bbb_is_nested_books_like_route() ? bbb_virtual_route_title(bbb_current_route_slug()) . ' - ' . get_bloginfo('name') : $title;
	},
	99
);

add_filter(
	'rank_math/frontend/description',
	static function (string $description): string {
		$term = bbb_current_virtual_book_taxonomy_term();
		return $term instanceof WP_Term ? bbb_virtual_book_taxonomy_meta_description($term) : $description;
	},
	99
);

add_filter(
	'rank_math/opengraph/facebook/description',
	static function (string $description): string {
		$term = bbb_current_virtual_book_taxonomy_term();
		return $term instanceof WP_Term ? bbb_virtual_book_taxonomy_meta_description($term) : $description;
	},
	99
);

add_filter(
	'rank_math/opengraph/twitter/description',
	static function (string $description): string {
		$term = bbb_current_virtual_book_taxonomy_term();
		return $term instanceof WP_Term ? bbb_virtual_book_taxonomy_meta_description($term) : $description;
	},
	99
);

add_filter(
	'rank_math/frontend/canonical',
	static function (string $canonical): string {
		$term = bbb_current_virtual_book_taxonomy_term();
		if (!$term instanceof WP_Term || !function_exists('bbb_book_taxonomy_term_url')) {
			return $canonical;
		}

		return bbb_book_taxonomy_term_url($term);
	},
	99
);

add_filter(
	'rank_math/frontend/robots',
	static function (array $robots): array {
		$term = bbb_current_virtual_book_taxonomy_term();
		if (!$term instanceof WP_Term) {
			return $robots;
		}

		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = 'index';
		$robots['follow'] = 'follow';
		return $robots;
	},
	99
);

add_filter(
	'wp_robots',
	static function (array $robots): array {
		$term = bbb_current_virtual_book_taxonomy_term();
		if (!$term instanceof WP_Term) {
			return $robots;
		}

		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = true;
		$robots['follow'] = true;
		return $robots;
	},
	99
);

function bbb_render_waiting_on_template(string $slug): void {
	bbb_mark_virtual_route_found();

	$title = bbb_virtual_route_title($slug);

	get_header();
	?>
	<section class="page-width page-width--narrow section-main-padding bbb-waiting-template">
		<p class="bbb-waiting-template__kicker">wordpress route ready</p>
		<h1 class="main-page-title page-title h0"><?php echo esc_html($title); ?></h1>
		<div class="rte">
			<p>waiting on template</p>
		</div>
	</section>
	<?php
	get_footer();
}

function bbb_render_books_like_blog_post(WP_Post $post): void {
	global $wp_query;

	if ($wp_query instanceof WP_Query) {
		$wp_query->is_404      = false;
		$wp_query->is_page     = false;
		$wp_query->is_single   = true;
		$wp_query->is_singular = true;
		$wp_query->is_home     = false;
		$wp_query->is_archive  = false;
		$wp_query->post        = $post;
		$wp_query->posts       = array($post);
		$wp_query->post_count  = 1;
		$wp_query->found_posts = 1;
		$wp_query->queried_object = $post;
		$wp_query->queried_object_id = (int) $post->ID;
	}

	status_header(200);
	nocache_headers();
	setup_postdata($post);

	$template = locate_template('single-post.php');
	if ('' === $template) {
		$template = get_single_template();
	}
	if ('' === $template) {
		$template = get_theme_file_path('index.php');
	}

	require $template;
	wp_reset_postdata();
}

function bbb_route_published_post_for_path(string $request_path): ?WP_Post {
	if (str_contains($request_path, '/')) {
		return null;
	}

	$posts = get_posts(
		array(
			'name'           => sanitize_title($request_path),
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		)
	);

	return $posts[0] ?? null;
}

function bbb_render_book_taxonomy_route_for_path(string $request_path, string $kind = ''): bool {
	if (!function_exists('bbb_find_book_taxonomy_term')) {
		return false;
	}

	$route_term = bbb_find_book_taxonomy_term($request_path, $kind);
	$route_kind_override = '';
	if (!$route_term instanceof WP_Term && 'trope' === $kind && in_array($request_path, array('dark-romance-books', 'sports-romance-books'), true)) {
		$route_term = bbb_find_book_taxonomy_term($request_path, 'shelf');
		$route_kind_override = 'trope';
	}
	if (!$route_term instanceof WP_Term) {
		return false;
	}

	$route_kind = '' !== $route_kind_override ? $route_kind_override : bbb_book_taxonomy_kind_for_taxonomy($route_term->taxonomy);
	$template   = get_theme_file_path('page-' . $route_kind . '.php');
	if (!file_exists($template)) {
		return false;
	}

	$GLOBALS['bbb_book_taxonomy_route_term'] = $route_term;
	if ('' !== $route_kind_override) {
		$GLOBALS['bbb_book_taxonomy_route_kind_override'] = $route_kind_override;
	}
	bbb_mark_virtual_route_found();
	require $template;
	return true;
}

add_action(
	'template_redirect',
	static function (): void {
		$slug = bbb_current_route_slug();
		if ($slug === '') {
			return;
		}

		$request_path   = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
		$empty_post_redirects = array(
			'media-kit'                    => 'work-with-me',
			'pages/media-kit'              => 'work-with-me',
			'the-best-hockey-romance-books' => 'hockey-romance-books',
			'the-best-mafia-romance-books'  => 'mafia-romance-books',
			'why-choose-books'             => 'why-choose-romance-books',
		);
		if (isset($empty_post_redirects[$request_path])) {
			wp_safe_redirect(home_url('/' . $empty_post_redirects[$request_path] . '/'), 301);
			exit;
		}

		$routes              = bbb_page_route_registry();
		$registered_template = (string) ($routes[$slug] ?? '');
			$taxonomy_first_routes = array(
				'dark-romance-books'          => 'shelf',
				'romantasy-books'             => 'shelf',
				'second-chance-romance-books' => 'trope',
				'sports-romance-books'        => 'shelf',
			);
			if (isset($taxonomy_first_routes[$request_path])) {
				if (bbb_render_book_taxonomy_route_for_path($request_path, $taxonomy_first_routes[$request_path])) {
					exit;
				}
			}
			if (bbb_render_book_taxonomy_route_for_path($request_path, 'shelf')) {
				exit;
			}

			$published_post = bbb_route_published_post_for_path($request_path);
		if ($published_post instanceof WP_Post) {
			if ('' === trim(wp_strip_all_tags((string) $published_post->post_content)) && function_exists('bbb_find_book_taxonomy_term')) {
				if (bbb_render_book_taxonomy_route_for_path($request_path)) {
					exit;
				}
			}

			bbb_render_books_like_blog_post($published_post);
			exit;
		}

		if (preg_match('#^romance-books-by-spice-level/(?:spice-)?([1-5])/?$#', $request_path, $matches)) {
			set_query_var('bbb_spice_level', (int) $matches[1]);
			$template = bbb_route_template_for_slug('romance-books-by-spice-level');
			if ($template !== '') {
				bbb_mark_virtual_route_found();
				require $template;
				exit;
			}
		}

		if (preg_match('#^series/([^/]+)/?$#', $request_path, $matches)) {
			set_query_var('bbb_series_handle', sanitize_title($matches[1]));
			$template = bbb_route_template_for_slug('series');
			if ($template !== '') {
				bbb_mark_virtual_route_found();
				require $template;
				exit;
			}
		}

		if (preg_match('#^books-like-[^/]+/?$#', $request_path)) {
			$blog_posts = get_posts(
				array(
					'name'           => sanitize_title(basename($request_path)),
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
				)
			);
			if ($blog_posts) {
				bbb_render_books_like_blog_post($blog_posts[0]);
				exit;
			}

			$if_you_liked_slug = preg_replace('#^books-like-#', 'if-you-liked-', sanitize_title(basename($request_path)));
			if (is_string($if_you_liked_slug) && '' !== $if_you_liked_slug && function_exists('bbb_books_like_source_for_slug') && bbb_books_like_source_for_slug($if_you_liked_slug) instanceof WP_Post) {
				wp_safe_redirect(home_url('/if-you-liked-pages/' . $if_you_liked_slug . '/'), 301);
				exit;
			}
		}

		if ('if-you-liked-pages' !== $request_path && preg_match('#^if-you-liked-[^/]+/?$#', $request_path)) {
			wp_safe_redirect(home_url('/if-you-liked-pages/' . sanitize_title(basename($request_path)) . '/'), 301);
			exit;
		}

		if (preg_match('#^if-you-liked-pages/(books-like-[^/]+)/?$#', $request_path, $matches)) {
			$if_you_liked_slug = preg_replace('#^books-like-#', 'if-you-liked-', sanitize_title($matches[1]));
			if (is_string($if_you_liked_slug) && '' !== $if_you_liked_slug) {
				wp_safe_redirect(home_url('/if-you-liked-pages/' . $if_you_liked_slug . '/'), 301);
				exit;
			}
		}

		if (preg_match('#^if-you-liked-pages/((?:books-like|if-you-liked)-[^/]+)/?$#', $request_path, $matches) && function_exists('bbb_books_like_source_for_slug')) {
			$source = bbb_books_like_source_for_slug(sanitize_title($matches[1]));
			$template = get_theme_file_path('page-books-like.php');
			if ($source instanceof WP_Post && file_exists($template)) {
				bbb_mark_virtual_route_found();
				require $template;
				exit;
			}
		}

		if (preg_match('#^(?:blogs/)?curated-romance-guides/page/([0-9]+)/?$#', $request_path, $matches)) {
			set_query_var('paged', max(1, (int) $matches[1]));
			$template = bbb_route_template_for_slug('curated-romance-guides');
			if ($template !== '') {
				bbb_mark_virtual_route_found();
				require $template;
				exit;
			}
		}

		if (preg_match('#^(?:blogs/)?curated-romance-guides/([^/]+)/?$#', $request_path, $matches)) {
			$post = get_posts(
				array(
					'name'           => sanitize_title($matches[1]),
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
				)
			);
			if ($post) {
				wp_safe_redirect(get_permalink($post[0]), 301);
				exit;
			}
		}

		if (
			($request_path === 'cart' && function_exists('wc_get_cart_url'))
			|| $request_path === 'account/login'
		) {
			return;
		}

		$forced_tax_kind     = '';
		if ('page-shelf.php' === $registered_template) {
			$forced_tax_kind = 'shelf';
		} elseif ('page-trope.php' === $registered_template) {
			$forced_tax_kind = 'trope';
		}

		if (bbb_render_book_taxonomy_route_for_path($slug, $forced_tax_kind)) {
			exit;
		}

		$is_legacy_path = str_starts_with($request_path, 'pages/')
			|| str_starts_with($request_path, 'blogs/')
			|| str_starts_with($request_path, 'collections/')
			|| str_starts_with($request_path, 'products/')
			|| str_starts_with($request_path, 'product/')
			|| str_starts_with($request_path, 'product-category/')
			|| str_starts_with($request_path, 'curated-romance-guides/');
		$is_registered_route = array_key_exists($slug, $routes);

		$page_id = is_page() ? get_queried_object_id() : 0;
		if ($page_id) {
			$shopify_template = (string) get_post_meta($page_id, '_shopify_template_suffix', true);
			$shopify_templates = array(
				'books-like'           => 'page-books-like.php',
				'books-like-directory' => 'page-books-like-directory.php',
			);
			$template = (string) ($shopify_templates[$shopify_template] ?? '');
			if ($template !== '') {
				$template_path = get_theme_file_path($template);
				if (file_exists($template_path)) {
					bbb_mark_virtual_route_found();
					require $template_path;
					exit;
				}
			}
		}

		if (!$is_registered_route && !$is_legacy_path) {
			return;
		}

		$template = bbb_route_template_for_slug($slug);
		if ($template !== '') {
			bbb_mark_virtual_route_found('series-reading-orders' !== $slug);
			require $template;
			exit;
		}

		bbb_render_waiting_on_template($slug);
		exit;
	},
	0
);
