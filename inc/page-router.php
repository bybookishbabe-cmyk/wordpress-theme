<?php
/**
 * Shopify page-route compatibility layer.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_page_route_registry(): array {
	return array(
		'artprints'                      => '',
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
		'curated-romance-guides'         => 'page-curated-romance-guides.php',
		'enemies-to-lovers'              => 'page-trope.php',
		'fictional-boyfriend-quiz'       => 'page-fictional-boyfriend-quiz.php',
		'find-your-read'                 => 'page-what-to-read-next.php',
		'for-readers'                    => '',
		'if-you-liked-pages'             => 'page-if-you-liked-pages.php',
		'kindle-insert-vault'            => '',
		'kindle-inserts'                 => '',
		'library'                        => 'page-library.php',
		'media-kit'                      => '',
		'monthly-theme'                  => 'page-societylibrary.php',
		'my-bookshelf'                   => 'page-my-bookshelf.php',
		'my-vault'                       => '',
		'our-story'                      => 'page-our-story.php',
		'popular-pages'                  => 'page-popular-pages.php',
		'privacy-policy'                 => '',
		'reader-mood-quiz'               => 'page-reader-mood-quiz.php',
		'reader-quizes'                  => 'page-reader-quizes.php',
		'reader-quizzes'                 => 'page-reader-quizes.php',
		'reading-list'                   => 'page-reading-list.php',
		'romance-books-by-spice-level'   => 'page-spice.php',
		'series'                         => 'page-series.php',
		'series-reading-orders'          => 'page-series-reading-orders.php',
		'shelf'                          => 'page-shelf.php',
		'shop'                           => 'page-shop.php',
		'slow-burn-books'                => 'page-trope.php',
		'smut-sentiment-society'         => 'page-smut-sentiment-society.php',
		'newsletter-submissions'         => 'page-society-submissions.php',
		'society-newsletter-recent'      => 'page-society-newsletter-recent.php',
		'society-newsletter-archive'     => 'page-society-newsletter-archive.php',
		'society-library'                => 'page-societylibrary.php',
		'society-submissions'            => 'page-society-submissions.php',
		'societylibrary'                 => 'page-societylibrary.php',
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

function bbb_mark_virtual_route_found(): void {
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
	nocache_headers();
}

function bbb_virtual_route_title(string $slug): string {
	return ucwords(str_replace('-', ' ', $slug));
}

add_filter(
	'document_title_parts',
	static function (array $title): array {
		$slug = bbb_current_route_slug();
		if ('' === bbb_route_template_for_slug($slug)) {
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
		if ('' === bbb_route_template_for_slug($slug)) {
			return $title;
		}

		return bbb_virtual_route_title($slug) . ' - ' . get_bloginfo('name');
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

add_action(
	'template_redirect',
	static function (): void {
		$slug = bbb_current_route_slug();
		if ($slug === '') {
			return;
		}

		$request_path   = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
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

		$routes             = bbb_page_route_registry();
		$registered_template = (string) ($routes[$slug] ?? '');
		$forced_tax_kind     = '';
		if ('page-shelf.php' === $registered_template) {
			$forced_tax_kind = 'shelf';
		} elseif ('page-trope.php' === $registered_template) {
			$forced_tax_kind = 'trope';
		}

		if (function_exists('bbb_find_book_taxonomy_term')) {
			$route_term = bbb_find_book_taxonomy_term($slug, $forced_tax_kind);
			if ($route_term instanceof WP_Term) {
				$route_kind = bbb_book_taxonomy_kind_for_taxonomy($route_term->taxonomy);
				$template   = get_theme_file_path('page-' . $route_kind . '.php');
				if (file_exists($template)) {
					$GLOBALS['bbb_book_taxonomy_route_term'] = $route_term;
					bbb_mark_virtual_route_found();
					require $template;
					exit;
				}
			}
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
			bbb_mark_virtual_route_found();
			require $template;
			exit;
		}

		bbb_render_waiting_on_template($slug);
		exit;
	},
	0
);
