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
		'books-like'                     => '',
		'books-like-directory'           => '',
		'bookshelf-weekly-preview'       => '',
		'account'                        => '',
		'cart'                           => '',
		'contact'                        => 'page-contact.php',
		'curated-romance-guides'         => '',
		'enemies-to-lovers'              => '',
		'fictional-boyfriend-quiz'       => '',
		'find-your-read'                 => '',
		'for-readers'                    => '',
		'kindle-insert-vault'            => '',
		'kindle-inserts'                 => '',
		'library'                        => 'page-library.php',
		'media-kit'                      => '',
		'my-bookshelf'                   => '',
		'my-vault'                       => '',
		'our-story'                      => 'page-our-story.php',
		'privacy-policy'                 => '',
		'reader-mood-quiz'               => '',
		'reader-quizes'                  => '',
		'reading-list'                   => 'page-reading-list.php',
		'romance-books-by-spice-level'   => 'page-spice.php',
		'series'                         => 'page-series.php',
		'series-reading-orders'          => 'page-series-reading-orders.php',
		'shelf'                          => 'page-shelf.php',
		'shop'                           => '',
		'slow-burn-books'                => '',
		'smut-sentiment-society'         => '',
		'society-library'                => 'page-societylibrary.php',
		'societylibrary'                 => 'page-societylibrary.php',
		'sports-romance-books'           => '',
		'sss-canva-templates'            => '',
		'sss-freebies'                   => '',
		'sss-library'                    => 'page-sss-library.php',
		'sss-library-page'               => 'page-sss-library-page.php',
		'sss-made-for-you'               => '',
		'made-for-you'                   => '',
		'sss-printable-kindle'           => '',
		'sss-printable-kindle-inserts'   => '',
		'sss-private-shelf'              => '',
		'sss-quote-wall'                 => '',
		'sss-series'                     => '',
		'sss-series-page'                => '',
		'weekly-obsession'               => '',
		'what-to-read-next'              => '',
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

function bbb_render_waiting_on_template(string $slug): void {
	status_header(200);
	nocache_headers();

	$title = ucwords(str_replace('-', ' ', $slug));

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
		$is_legacy_path = str_starts_with($request_path, 'pages/')
			|| str_starts_with($request_path, 'blogs/')
			|| $request_path === 'collections/all';
		$routes = bbb_page_route_registry();
		if (!array_key_exists($slug, $routes) && !$is_legacy_path) {
			return;
		}

		if (
			!is_404()
			&& !$is_legacy_path
		) {
			return;
		}

		$template = bbb_route_template_for_slug($slug);
		if ($template !== '') {
			status_header(200);
			nocache_headers();
			require $template;
			exit;
		}

		bbb_render_waiting_on_template($slug);
		exit;
	},
	0
);
