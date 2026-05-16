<?php
/**
 * Theme setup.
 *
 * @package WordPressTheme
 */

declare(strict_types=1);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'wordpress-theme-fonts',
			'https://fonts.googleapis.com/css2?family=Allura&family=Cormorant:wght@500;600&family=Cormorant+Garamond:wght@400;500;600;700&family=Great+Vibes&family=Kaushan+Script&family=Libre+Baskerville:wght@400;700&family=Playfair+Display:ital,wght@0,400;0,600;1,400;1,600&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'wordpress-theme-main',
			get_theme_file_uri('assets/css/main.css'),
			array('wordpress-theme-fonts'),
			wp_get_theme()->get('Version')
		);

		wp_enqueue_style(
			'wordpress-theme-shopify-replica',
			get_stylesheet_uri(),
			array('wordpress-theme-main'),
			wp_get_theme()->get('Version')
		);
	}
);

add_action(
	'init',
	static function (): void {
		if (!get_role('society_member')) {
			add_role(
				'society_member',
				__('Society Member', 'wordpress-theme'),
				array(
					'read'               => true,
					'bbb_society_access' => true,
				)
			);
		}

		register_post_type(
			'bbb_book',
			array(
				'labels'       => array(
					'name'          => __('Books', 'wordpress-theme'),
					'singular_name' => __('Book', 'wordpress-theme'),
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-book-alt',
				'supports'     => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
				'has_archive'  => 'books',
				'rewrite'      => array('slug' => 'books'),
			)
		);

		register_post_type(
			'bbb_quote',
			array(
				'labels'       => array(
					'name'          => __('Quotes', 'wordpress-theme'),
					'singular_name' => __('Quote', 'wordpress-theme'),
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-format-quote',
				'supports'     => array('title', 'editor', 'custom-fields'),
				'has_archive'  => true,
				'rewrite'      => array('slug' => 'quotes'),
			)
		);

		foreach (
			array(
				'bbb_genre'  => array('Genres', 'Genre', 'book-genre'),
				'bbb_trope'  => array('Tropes', 'Trope', 'book-trope'),
				'bbb_series' => array('Series', 'Series', 'book-series'),
			) as $taxonomy => $config
		) {
			register_taxonomy(
				$taxonomy,
				array('bbb_book'),
				array(
					'labels'       => array(
						'name'          => __($config[0], 'wordpress-theme'),
						'singular_name' => __($config[1], 'wordpress-theme'),
					),
					'public'       => true,
					'show_in_rest' => true,
					'hierarchical' => false,
					'rewrite'      => array('slug' => $config[2]),
				)
			);
		}
	}
);

add_action(
	'init',
	static function (): void {
		foreach (array_keys(bbb_get_shopify_page_templates()) as $slug) {
			add_rewrite_rule('^' . preg_quote($slug, '/') . '/?$', 'index.php?bbb_shopify_page=' . $slug, 'top');
			add_rewrite_rule('^pages/' . preg_quote($slug, '/') . '/?$', 'index.php?bbb_shopify_page=' . $slug, 'top');
		}

		$rewrite_version = '2026-05-16-shopify-pages-v3';

		if (get_option('bbb_rewrite_version') !== $rewrite_version) {
			flush_rewrite_rules(false);
			update_option('bbb_rewrite_version', $rewrite_version);
		}
	},
	20
);

add_filter(
	'query_vars',
	static function (array $vars): array {
		$vars[] = 'bbb_shopify_page';

		return $vars;
	}
);

add_filter(
	'template_include',
	static function (string $template): string {
		$route = (string) get_query_var('bbb_shopify_page');
		$slug  = $route ?: (is_page() ? (string) get_post_field('post_name', get_queried_object_id()) : '');

		if ($slug && isset(bbb_get_shopify_page_templates()[$slug])) {
			$route_template = get_theme_file_path('templates/bbb-shopify-page.php');
			if (file_exists($route_template)) {
				return $route_template;
			}
		}

		return $template;
	}
);

function bbb_get_shopify_page_templates(): array {
	return array_merge(
		array(
		'artprints'                     => array('label' => 'art prints', 'alias' => 'shop'),
		'library'                       => array('label' => 'library'),
		'what-to-read-next'             => array('label' => 'what to read next'),
		'reader-quizzes'                => array('label' => 'reader quizzes'),
		'reader-quizes'                 => array('label' => 'reader quizzes', 'alias' => 'reader-quizzes'),
		'reader-mood-quiz'              => array('label' => 'reader mood quiz'),
		'fictional-boyfriend-quiz'      => array('label' => 'fictional boyfriend quiz'),
		'romance-books-by-spice-level'  => array('label' => 'spice level'),
		'spice'                         => array('label' => 'spice level', 'alias' => 'romance-books-by-spice-level'),
		'book-trope'                    => array('label' => 'trope shelves'),
		'trope'                         => array('label' => 'trope shelves', 'alias' => 'book-trope'),
		'book-reviews'                  => array('label' => 'book reviews'),
		'books-like'                    => array('label' => 'books like this'),
		'books-like-directory'          => array('label' => 'books like x'),
		'blog'                          => array('label' => 'blog'),
		'book-series'                   => array('label' => 'series reading orders'),
		'series-reading-orders'         => array('label' => 'series reading orders', 'alias' => 'book-series'),
		'for-readers'                   => array('label' => 'for readers', 'alias' => 'reader-quizzes'),
		'smut-sentiment-society'        => array('label' => 'the society'),
		'society-library'               => array('label' => 'society library'),
		'societylibrary'                => array('label' => 'society library', 'alias' => 'society-library'),
		'ssslibrary'                    => array('label' => 'society library', 'alias' => 'society-library'),
		'sss-library'                   => array('label' => 'society library', 'alias' => 'society-library'),
		'sss-library-page'              => array('label' => 'society library', 'alias' => 'society-library'),
		'sss-made-for-you'              => array('label' => 'made for you', 'alias' => 'what-to-read-next'),
		'sss-private-shelf'             => array('label' => 'private shelf', 'alias' => 'society-library'),
		'sss-quote-wall'                => array('label' => 'quote wall'),
		'sss-freebies'                  => array('label' => 'freebies', 'alias' => 'society-library'),
		'sss-canva-templates'           => array('label' => 'canva templates', 'alias' => 'bookish-templates'),
		'sss-printable-kindle'          => array('label' => 'printable kindle', 'alias' => 'kindle-inserts'),
		'sss-monthly-staging'           => array('label' => 'monthly staging', 'alias' => 'society-library'),
		'sss-series'                    => array('label' => 'society series', 'alias' => 'book-series'),
		'sss-series-page'               => array('label' => 'society series', 'alias' => 'book-series'),
		'my-vault'                      => array('label' => 'my vault'),
		'my-bookshelf'                  => array('label' => 'my bookshelf', 'alias' => 'library'),
		'shop'                          => array('label' => 'shop'),
		'bookish-templates'             => array('label' => 'bookish templates'),
		'digitalproductstemplate'       => array('label' => 'digital products', 'alias' => 'shop'),
		'kindle-inserts'                => array('label' => 'kindle inserts'),
		'kindle-insert-vault'           => array('label' => 'kindle insert vault', 'alias' => 'kindle-inserts'),
		'our-story'                     => array('label' => 'our story'),
		'contact'                       => array('label' => 'contact'),
		'customerreviews'               => array('label' => 'customer reviews'),
		'media-kit'                     => array('label' => 'media kit'),
		'newslettertemplate'            => array('label' => 'newsletter', 'alias' => 'smut-sentiment-society'),
		'newsletter-submissions'        => array('label' => 'newsletter submissions'),
		'society-submissions'           => array('label' => 'society submissions', 'alias' => 'newsletter-submissions'),
		'privacy-policy'                => array('label' => 'privacy policy'),
		'preview'                       => array('label' => 'preview', 'alias' => 'society-library'),
		'quote-audit'                   => array('label' => 'quote audit', 'alias' => 'sss-quote-wall'),
		'reading-list'                  => array('label' => 'reading list', 'alias' => 'library'),
		'shelf'                         => array('label' => 'shelf', 'alias' => 'library'),
		'weekly-obsession'              => array('label' => 'weekly obsession'),
		'bookshelf-weekly-preview'      => array('label' => 'bookshelf weekly', 'alias' => 'weekly-obsession'),
		),
		bbb_get_shopify_topic_page_templates()
	);
}

function bbb_get_shopify_topic_page_templates(): array {
	$templates = array();
	$configs   = bbb_get_library_topic_configs();

	foreach ($configs as $slug => $config) {
		$label = (string) ($config['title'] ?? '');
		if ('' === $label && isset($config['alias'], $configs[$config['alias']]['title'])) {
			$label = (string) $configs[$config['alias']]['title'];
		}

		$templates[$slug] = array(
			'label' => $label ?: 'library shelf',
			'topic' => true,
		);
	}

	return $templates;
}

function bbb_get_library_topic_configs(): array {
	return array(
		'sports-romance-books'                 => array(
			'kicker' => 'trope shelf',
			'title' => 'sports romance books',
			'subtext' => 'hockey romance, baseball players, college athletes, and professional athletes with tension on and off the field.',
			'terms' => array('sports-romance', 'sports', 'hockey-romance', 'baseball-romance', 'football-romance', 'athlete-romance'),
			'search' => 'sports romance',
		),
		'dark-romance-books'                   => array('kicker' => 'trope shelf', 'title' => 'dark romance books', 'subtext' => 'morally questionable men, obsession, danger, and the kind of tension that leaves fingerprints.', 'terms' => array('dark-romance', 'dark', 'morally-gray-men', 'touch-her-and-die'), 'search' => 'dark romance'),
		'dark-romance'                         => array('alias' => 'dark-romance-books'),
		'romantasy-books'                      => array('kicker' => 'genre shelf', 'title' => 'romantasy books', 'subtext' => 'magic, kingdoms, curses, trials, and romance with teeth.', 'terms' => array('romantasy', 'fantasy-romance', 'fantasy'), 'search' => 'romantasy'),
		'fantasy-romance-books'                => array('alias' => 'romantasy-books'),
		'enemies-to-lovers-books'              => array('kicker' => 'trope shelf', 'title' => 'enemies to lovers books', 'subtext' => 'for readers who want sharp edges before softness.', 'terms' => array('enemies-to-lovers', 'rivals-to-lovers'), 'search' => 'enemies to lovers'),
		'enemies-to-lovers-romance-books'      => array('alias' => 'enemies-to-lovers-books'),
		'slow-burn-books'                      => array('kicker' => 'trope shelf', 'title' => 'slow burn romance books', 'subtext' => 'when the almost-touch matters as much as the kiss.', 'terms' => array('slow-burn', 'slow-burn-romance'), 'search' => 'slow burn'),
		'slow-burn-romance-books'              => array('alias' => 'slow-burn-books'),
		'morally-gray-men-books'               => array('kicker' => 'trope shelf', 'title' => 'morally gray men books', 'subtext' => 'bad decisions, devotion, and men who should probably come with warnings.', 'terms' => array('morally-gray-men', 'morally-gray', 'antihero'), 'search' => 'morally gray'),
		'morally-gray-fantasy-books'           => array('alias' => 'morally-gray-men-books'),
		'touch-her-and-die-books'              => array('kicker' => 'trope shelf', 'title' => 'touch her and die books', 'subtext' => 'protective, possessive, and very serious about consequences.', 'terms' => array('touch-her-and-die', 'protective-hero', 'possessive-hero'), 'search' => 'touch her and die'),
		'fake-dating-romance-books'            => array('kicker' => 'trope shelf', 'title' => 'fake dating romance books', 'subtext' => 'pretend feelings, real jealousy, inevitable chaos.', 'terms' => array('fake-dating', 'fake-relationship'), 'search' => 'fake dating'),
		'forced-proximity-romance-books'       => array('kicker' => 'trope shelf', 'title' => 'forced proximity romance books', 'subtext' => 'one room, one trip, one situation neither of them can escape.', 'terms' => array('forced-proximity', 'one-bed'), 'search' => 'forced proximity'),
		'grumpy-sunshine-romance-books'        => array('kicker' => 'trope shelf', 'title' => 'grumpy sunshine romance books', 'subtext' => 'soft light meets permanent scowl.', 'terms' => array('grumpy-sunshine', 'opposites-attract'), 'search' => 'grumpy sunshine'),
		'he-falls-first-romance-books'         => array('kicker' => 'trope shelf', 'title' => 'he falls first romance books', 'subtext' => 'men realizing they are in trouble before anyone else does.', 'terms' => array('he-falls-first', 'falls-first'), 'search' => 'he falls first'),
		'villain-gets-the-girl-romance-books'  => array('kicker' => 'trope shelf', 'title' => 'villain gets the girl books', 'subtext' => 'for when the villain was the love interest all along.', 'terms' => array('villain-gets-the-girl', 'villain-romance'), 'search' => 'villain gets the girl'),
		'stalker-romance-books'                => array('kicker' => 'trope shelf', 'title' => 'stalker romance books', 'subtext' => 'obsession, surveillance, and absolutely questionable choices.', 'terms' => array('stalker-romance', 'stalker'), 'search' => 'stalker romance'),
		'captor-captive-romance-books'         => array('kicker' => 'trope shelf', 'title' => 'captor captive romance books', 'subtext' => 'high stakes, blurred lines, and tension that does not behave.', 'terms' => array('captor-captive', 'captive-romance'), 'search' => 'captor captive'),
		'mafia-romance-books'                  => array('kicker' => 'genre shelf', 'title' => 'mafia romance books', 'subtext' => 'danger, power, loyalty, and love that does not ask permission.', 'terms' => array('mafia-romance', 'mafia'), 'search' => 'mafia romance'),
		'billionaire-romance-books'            => array('kicker' => 'genre shelf', 'title' => 'billionaire romance books', 'subtext' => 'money, power, rules, and the people who break them.', 'terms' => array('billionaire-romance', 'billionaire'), 'search' => 'billionaire romance'),
		'cowboy-romance-books'                 => array('kicker' => 'genre shelf', 'title' => 'cowboy romance books', 'subtext' => 'small towns, hard work, and men who know their way around longing.', 'terms' => array('cowboy-romance', 'cowboy', 'western-romance'), 'search' => 'cowboy romance'),
		'small-town-romance-books'             => array('kicker' => 'genre shelf', 'title' => 'small town romance books', 'subtext' => 'close quarters, local gossip, and a love story everyone can see coming.', 'terms' => array('small-town-romance', 'small-town'), 'search' => 'small town romance'),
		'college-romance-books'                => array('kicker' => 'genre shelf', 'title' => 'college romance books', 'subtext' => 'campus tension, first freedoms, and beautifully bad decisions.', 'terms' => array('college-romance', 'college'), 'search' => 'college romance'),
	);
}

function bbb_normalize_shopify_page_slug(string $slug): string {
	$config = bbb_get_shopify_page_templates()[$slug] ?? array();
	if (isset($config['alias'])) {
		return bbb_normalize_shopify_page_slug((string) $config['alias']);
	}

	return $slug;
}

function bbb_render_shopify_page_template(string $slug): string {
	$slug = bbb_normalize_shopify_page_slug(sanitize_title($slug));

	if (isset(bbb_get_library_topic_configs()[$slug])) {
		return bbb_render_topic_page_template($slug);
	}

	switch ($slug) {
		case 'library':
			return bbb_render_library_page_template();
		case 'what-to-read-next':
			return bbb_render_recommendation_page_template();
		case 'reader-quizzes':
			return bbb_render_reader_quizzes_template();
		case 'reader-mood-quiz':
			return bbb_render_simple_hub_template(
				'reader mood quiz',
				'find your reading mood',
				'choose the kind of chaos you want and jump into the matching shelf.',
				array(
					'dark romance' => '/book-genre/dark-romance/',
					'romantasy'    => '/book-genre/romantasy/',
					'slow burn'    => '/book-trope/slow-burn/',
					'full library' => '/library/',
				)
			);
		case 'fictional-boyfriend-quiz':
			return bbb_render_simple_hub_template(
				'fictional boyfriend quiz',
				'who is your fictional boyfriend?',
				'answer with your reading taste, then use the result to find your next obsession.',
				array(
					'take me to morally gray men' => '/book-trope/morally-gray-men/',
					'romantasy men'               => '/book-genre/romantasy/',
					'society classics'            => '/library/#classics',
				)
			);
		case 'romance-books-by-spice-level':
			return bbb_render_spice_page_template();
		case 'book-trope':
			return bbb_render_taxonomy_page_template('bbb_trope', 'trope shelves', 'browse the library by the exact kind of trouble you are in the mood for.');
		case 'book-series':
			return bbb_render_taxonomy_page_template('bbb_series', 'series reading orders', 'every series in one place, with the reading order ready when the obsession starts.');
		case 'book-reviews':
			return bbb_render_posts_page_template('book reviews', 'read, rated, recommended', 'all the reviews and reading guides imported from Shopify.', '');
		case 'books-like':
			return bbb_render_posts_page_template('books like this', 'reading guide', 'books that match the mood, trope, and damage level of the one that ruined you.', 'books like');
		case 'books-like-directory':
			return bbb_render_posts_page_template('books like x', 'reading guides', 'finished something that wrecked you? start here for the closest next-read lists.', 'books like');
		case 'blog':
			return bbb_render_posts_page_template('the society journal', 'reviews & guides', 'book reviews, reading guides, and reader-life notes.', '');
		case 'smut-sentiment-society':
			return bbb_render_society_page_template();
		case 'society-library':
			return bbb_render_society_library_template();
		case 'sss-quote-wall':
			return bbb_render_quote_wall_template();
		case 'my-vault':
			return bbb_render_vault_template();
		case 'shop':
			return bbb_render_shop_template();
		case 'bookish-templates':
			return bbb_render_shop_family_template('bookish templates', 'Canva templates, reader trackers, and digital things for making your book life prettier.', array('my vault' => '/my-vault/', 'shop' => '/shop/', 'society library' => '/society-library/'));
		case 'kindle-inserts':
			return bbb_render_shop_family_template('printable kindle inserts', 'Printable inserts and vault links from the Shopify shop, mapped into WordPress while products move later.', array('my vault' => '/my-vault/', 'shop' => '/shop/', 'society library' => '/society-library/'));
		case 'our-story':
			return bbb_render_our_story_template();
		case 'contact':
			return bbb_render_simple_hub_template(
				'contact',
				'come closer',
				'For collabs, questions, and reader-life things, these are the best places to find bybookishbabe.',
				array(
					'email'     => 'mailto:bybookishbabe@gmail.com',
					'instagram' => 'https://www.instagram.com/bybookishbabe/',
					'tiktok'    => 'https://www.tiktok.com/@bybookishbabe',
					'substack'  => 'https://thesmutandsentimentsociety.substack.com/subscribe',
				)
			);
		case 'customerreviews':
			return bbb_render_simple_hub_template('reader proof', 'customer reviews', 'Reader notes, social proof, and happy chaos from the bybookishbabe world.', array('shop' => '/shop/', 'library' => '/library/', 'contact' => '/contact/'));
		case 'media-kit':
			return bbb_render_simple_hub_template('media kit', 'work with bybookishbabe', 'Collabs, features, and brand partnership information live here.', array('email' => 'mailto:bybookishbabe@gmail.com', 'instagram' => 'https://www.instagram.com/bybookishbabe/', 'tiktok' => 'https://www.tiktok.com/@bybookishbabe'));
		case 'newsletter-submissions':
			return bbb_render_simple_hub_template('submissions', 'send something to the society', 'A WordPress landing page for the old Shopify submission flow.', array('email me' => 'mailto:bybookishbabe@gmail.com', 'join substack' => 'https://thesmutandsentimentsociety.substack.com/subscribe', 'society library' => '/society-library/'));
		case 'privacy-policy':
			return bbb_render_simple_hub_template('privacy policy', 'privacy policy', 'Your Shopify privacy-policy page is routed here in WordPress. Replace this content with the final legal policy before launch.', array('contact' => '/contact/', 'home' => '/'));
		case 'weekly-obsession':
			return bbb_render_weekly_obsession_template();
		default:
			return bbb_render_simple_hub_template('page', 'bybookishbabe', 'This Shopify page is mapped into WordPress.', array('open library' => '/library/'));
	}
}

function bbb_render_page_shell(string $kicker, string $title, string $subtext, string $body, string $class = ''): string {
	return sprintf(
		'<main class="bbb-shopify-page %5$s"><section class="bbb-page-hero"><p>%1$s</p><h1>%2$s</h1><div>%3$s</div></section>%4$s</main>',
		esc_html($kicker),
		esc_html($title),
		wp_kses_post(wpautop($subtext)),
		$body,
		esc_attr($class)
	);
}

function bbb_render_library_page_template(): string {
	$body = '<section class="bbb-page-panel bbb-page-panel--wide">'
		. do_shortcode('[bbb_library_shelf title="trending in the society" subtitle="the books the society is obsessing over right now" limit="5" meta_key="_bbb_top_shelf" meta_value="1" class="bbb-shelf--featured" fallback="true"]')
		. '</section>'
		. '<nav class="bbb-template-jump" aria-label="Library sections"><a href="#full-library">full library</a><a href="/book-trope/">trope shelves</a><a href="/book-series/">series</a><a href="/romance-books-by-spice-level/">spice level</a><a href="/what-to-read-next/">what to read next</a></nav>'
		. '<section class="bbb-page-panel" id="full-library"><p class="bbb-page-kicker">full library</p><h2>browse every book</h2>' . do_shortcode('[bbb_library limit="96" filters="true"]') . '</section>';

	return bbb_render_page_shell('official library', 'the romance library', 'the official collection of romance books curated and catalogued by the smut and sentiment society.', $body, 'bbb-shopify-page--library');
}

function bbb_render_recommendation_page_template(): string {
	$body = '<section class="bbb-page-panel bbb-recommendation-panel"><div><p class="bbb-page-kicker">start with a book you already loved</p><h2>reader chemistry</h2><p>Pick from the imported library, then use the shelves below to follow the same mood, trope, or spice energy.</p></div><div class="bbb-rec-stack"><a href="/library/?bbb_search=daggermouth">if you liked... daggermouth</a><a href="/book-trope/morally-gray-men/">morally gray men</a><a href="/book-genre/romantasy/">romantasy girls</a></div></section>'
		. do_shortcode('[bbb_library_shelf title="closest matches" subtitle="start with the books the society keeps coming back to" limit="8" class="bbb-shelf--plain" fallback="true"]');

	return bbb_render_page_shell('reader rec engine', 'what to read next', 'pick a book from the library and follow the shelf chemistry to your next obsession.', $body);
}

function bbb_render_reader_quizzes_template(): string {
	return bbb_render_simple_hub_template(
		'reader quizzes',
		'pick your poison',
		'quick reader paths for finding the next shelf, trope, or fictional man to make your problem.',
		array(
			'what to read next'        => '/what-to-read-next/',
			'reader mood quiz'         => '/reader-mood-quiz/',
			'fictional boyfriend quiz' => '/fictional-boyfriend-quiz/',
			'spice level'              => '/romance-books-by-spice-level/',
		)
	);
}

function bbb_render_spice_page_template(): string {
	$cards = '';
	for ($i = 1; $i <= 5; $i++) {
		$cards .= '<a class="bbb-template-card" href="/library/?bbb_spice=' . esc_attr((string) $i) . '"><span>' . esc_html(str_repeat('🌶', $i)) . '</span><strong>' . esc_html((string) $i) . '+ spice</strong><em>browse the shelf</em></a>';
	}

	return bbb_render_page_shell('browse by heat', 'romance books by spice level', 'go straight to the exact spice mood you want.', '<section class="bbb-template-grid">' . $cards . '</section>');
}

function bbb_render_taxonomy_page_template(string $taxonomy, string $title, string $subtext): string {
	$terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
	$cards = '';

	if (!is_wp_error($terms) && $terms) {
		foreach ($terms as $term) {
			$cards .= '<a class="bbb-template-card" href="' . esc_url(get_term_link($term)) . '"><strong>' . esc_html($term->name) . '</strong><em>' . esc_html((string) $term->count) . ' books</em></a>';
		}
	}

	$body = '<section class="bbb-template-grid">' . ($cards ?: '<p class="bbb-library-empty">No shelves are showing yet.</p>') . '</section>';

	return bbb_render_page_shell('library guide', $title, $subtext, $body);
}

function bbb_render_topic_page_template(string $slug): string {
	$config = bbb_get_library_topic_configs()[$slug] ?? array();
	if (isset($config['alias'])) {
		return bbb_render_topic_page_template((string) $config['alias']);
	}

	$body = '<section class="bbb-page-panel bbb-page-panel--wide bbb-topic-panel">'
		. '<p class="bbb-page-kicker">matching shelf</p>'
		. '<h2>books from the library</h2>'
		. bbb_render_topic_book_grid($config)
		. '</section>'
		. '<nav class="bbb-template-jump" aria-label="Related library links"><a href="/library/">full library</a><a href="/book-trope/">trope shelves</a><a href="/romance-books-by-spice-level/">spice level</a><a href="/what-to-read-next/">what to read next</a></nav>';

	return bbb_render_page_shell(
		(string) ($config['kicker'] ?? 'library shelf'),
		(string) ($config['title'] ?? 'romance books'),
		(string) ($config['subtext'] ?? 'a curated shelf from the bybookishbabe library.'),
		$body,
		'bbb-shopify-page--topic'
	);
}

function bbb_render_topic_book_grid(array $config): string {
	$post_status = current_user_can('edit_posts') ? array('publish', 'draft') : array('publish');
	$query_args  = array(
		'post_type'      => 'bbb_book',
		'post_status'    => $post_status,
		'posts_per_page' => 48,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$tax_query = array('relation' => 'OR');
	foreach ((array) ($config['terms'] ?? array()) as $term_slug) {
		foreach (array('bbb_genre', 'bbb_trope', 'bbb_series') as $taxonomy) {
			if (term_exists((string) $term_slug, $taxonomy)) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => sanitize_title((string) $term_slug),
				);
			}
		}
	}

	if (count($tax_query) > 1) {
		$query_args['tax_query'] = $tax_query;
	} elseif (!empty($config['search'])) {
		$query_args['s'] = (string) $config['search'];
	}

	$query = new WP_Query($query_args);

	if (!$query->have_posts() && !empty($config['search'])) {
		unset($query_args['tax_query']);
		$query_args['s'] = (string) $config['search'];
		$query = new WP_Query($query_args);
	}

	if (!$query->have_posts()) {
		unset($query_args['s'], $query_args['tax_query']);
		$query_args['posts_per_page'] = 12;
		$query = new WP_Query($query_args);
	}

	if (!$query->have_posts()) {
		return '<p class="bbb-library-empty">No books are showing yet.</p>';
	}

	ob_start();
	?>
	<div class="bbb-library-grid bbb-library-grid--archive bbb-library-grid--topic">
		<?php
		while ($query->have_posts()) {
			$query->the_post();
			echo bbb_render_library_card(get_the_ID()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		wp_reset_postdata();
		?>
	</div>
	<?php
	return (string) ob_get_clean();
}

function bbb_render_posts_page_template(string $title, string $kicker, string $subtext, string $search = ''): string {
	$query_args = array(
		'post_type'      => 'post',
		'post_status'    => current_user_can('edit_posts') ? array('publish', 'draft') : array('publish'),
		'posts_per_page' => 24,
	);

	if ($search) {
		$query_args['s'] = $search;
	}

	$query = new WP_Query($query_args);
	$cards = '';

	while ($query->have_posts()) {
		$query->the_post();
		$cards .= '<a class="bbb-template-card bbb-template-card--post" href="' . esc_url(get_permalink()) . '"><span>' . esc_html(get_the_date()) . '</span><strong>' . esc_html(get_the_title()) . '</strong><em>read the guide</em></a>';
	}
	wp_reset_postdata();

	return bbb_render_page_shell($kicker, $title, $subtext, '<section class="bbb-template-grid">' . ($cards ?: '<p class="bbb-library-empty">No posts are showing yet.</p>') . '</section>');
}

function bbb_render_society_page_template(): string {
	$body = '<section class="bbb-page-panel bbb-society-panel"><p class="bbb-page-kicker">the private layer</p><h2>one curated romance every sunday</h2><p>Substack stays the main membership source. WordPress can unlock society-only pages for synced members, while public readers still get the library and guides.</p><div class="bbb-template-actions"><a href="https://thesmutandsentimentsociety.substack.com/subscribe">join on substack</a><a href="/society-library/">open society library</a></div></section>';

	return bbb_render_page_shell('the society', 'the smut & sentiment society', 'for soft hearts with sinful taste, private notes, and weekly reader chaos.', $body);
}

function bbb_render_society_library_template(): string {
	$tabs = array(
		'main page'          => '/society-library/',
		'library'            => '/library/',
		'made for you'       => '/what-to-read-next/',
		'quote library'      => '/sss-quote-wall/',
		'private shelf'      => '/sss-private-shelf/',
		'printable inserts'  => '/shop/',
		'bookish templates'  => '/shop/',
	);
	$links = '';
	foreach ($tabs as $label => $url) {
		$links .= '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
	}

	$body = '<nav class="bbb-template-jump">' . $links . '</nav><section class="bbb-page-panel">' . do_shortcode('[bbb_society_only message="Join on Substack and log in with the same email to unlock the private shelf."][bbb_library_shelf title="private shelf" limit="8" meta_key="_bbb_access_level" meta_value="society" fallback="true"][/bbb_society_only]') . '</section>';

	return bbb_render_page_shell('library files', 'society library', 'pick a folder. dive in.', $body);
}

function bbb_render_quote_wall_template(): string {
	$query = new WP_Query(array('post_type' => 'bbb_quote', 'post_status' => 'publish', 'posts_per_page' => 30));
	$quotes = '';
	while ($query->have_posts()) {
		$query->the_post();
		$quotes .= '<blockquote class="bbb-quote-card">' . wp_kses_post(wpautop(get_the_content())) . '<cite>' . esc_html(get_the_title()) . '</cite></blockquote>';
	}
	wp_reset_postdata();

	return bbb_render_page_shell('lines that ruined me', 'quote wall', 'the imported society quote library, collected in one place.', '<section class="bbb-quote-grid">' . ($quotes ?: '<p class="bbb-library-empty">No quotes are showing yet.</p>') . '</section>');
}

function bbb_render_vault_template(): string {
	return bbb_render_simple_hub_template(
		'my vault',
		'everything you own, in one place.',
		'Your digital bybookishbabe things live here. For now, WordPress routes the old Shopify vault paths into the right hubs.',
		array(
			'printable inserts'  => '/shop/',
			'bookish templates'  => '/shop/',
			'society library'    => '/society-library/',
			'join on substack'   => 'https://thesmutandsentimentsociety.substack.com/subscribe',
		)
	);
}

function bbb_render_shop_template(): string {
	return bbb_render_simple_hub_template(
		'digital shop',
		'curate your bybookishbabe vault',
		'Shopify product pages are being translated into WordPress hubs. This page keeps the shop nav alive while products move over later.',
		array(
			'printable kindle inserts' => '/my-vault/',
			'bookish templates'        => '/my-vault/',
			'society membership'       => 'https://thesmutandsentimentsociety.substack.com/subscribe',
		)
	);
}

function bbb_render_shop_family_template(string $title, string $subtext, array $links): string {
	$body = '<section class="bbb-page-panel bbb-page-panel--wide">'
		. '<p class="bbb-page-kicker">digital shop</p><h2>' . esc_html($title) . '</h2><p>' . esc_html($subtext) . '</p>'
		. '<div class="bbb-template-actions">';

	foreach ($links as $label => $url) {
		$body .= '<a href="' . esc_url((string) $url) . '">' . esc_html((string) $label) . '</a>';
	}

	$body .= '</div></section>'
		. '<section class="bbb-page-panel"><p class="bbb-page-kicker">library preview</p>'
		. do_shortcode('[bbb_library_shelf title="reader favorites" limit="4" class="bbb-shelf--plain" fallback="true"]')
		. '</section>';

	return bbb_render_page_shell('shop files', $title, $subtext, $body);
}

function bbb_render_our_story_template(): string {
	return bbb_render_simple_hub_template(
		'why i started this',
		'i wanted reading to feel as beautiful as it felt.',
		'bybookishbabe started because books were never just books. they were the hobby, the aesthetic, the mood, and the little world built around them.',
		array(
			'open the library' => '/library/',
			'take the quiz'   => '/reader-quizzes/',
			'join society'    => 'https://thesmutandsentimentsociety.substack.com/subscribe',
		)
	);
}

function bbb_render_weekly_obsession_template(): string {
	$body = do_shortcode('[bbb_library_shelf title="weekly obsession" subtitle="the book currently taking over the smut & sentiment society." limit="1" meta_key="_bbb_top_shelf" meta_value="1" class="bbb-shelf--featured" fallback="true"]')
		. do_shortcode('[bbb_library_shelf title="read this next" limit="3" class="bbb-shelf--plain" fallback="true"]');

	return bbb_render_page_shell('weekly obsession', 'this week’s obsession', 'the current society pick, plus a few nearby reads.', $body);
}

function bbb_render_simple_hub_template(string $kicker, string $title, string $subtext, array $links): string {
	$cards = '';
	foreach ($links as $label => $url) {
		$cards .= '<a class="bbb-template-card" href="' . esc_url((string) $url) . '"><strong>' . esc_html((string) $label) . '</strong><em>open</em></a>';
	}

	return bbb_render_page_shell($kicker, $title, $subtext, '<section class="bbb-template-grid">' . $cards . '</section>');
}

add_shortcode(
	'bbb_shopify_page',
	static function (array $atts = array()): string {
		$atts = shortcode_atts(array('slug' => ''), $atts, 'bbb_shopify_page');

		return bbb_render_shopify_page_template((string) $atts['slug']);
	}
);

function bbb_get_member_sync_secret(): string {
	if (defined('BBB_MEMBER_SYNC_SECRET') && BBB_MEMBER_SYNC_SECRET) {
		return (string) BBB_MEMBER_SYNC_SECRET;
	}

	$env_secret = getenv('BBB_MEMBER_SYNC_SECRET');
	return $env_secret ? (string) $env_secret : '';
}

function bbb_current_user_is_society_member(): bool {
	if (!is_user_logged_in()) {
		return false;
	}

	if (current_user_can('manage_options') || current_user_can('bbb_society_access')) {
		return true;
	}

	$user = wp_get_current_user();
	return in_array('society_member', (array) $user->roles, true);
}

function bbb_get_bearer_token(WP_REST_Request $request): string {
	$authorization = (string) $request->get_header('authorization');
	if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
		return trim($matches[1]);
	}

	return trim((string) $request->get_header('x-bbb-member-sync-secret'));
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'bbb/v1',
			'/society-member',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => static function (WP_REST_Request $request) {
					$secret = bbb_get_member_sync_secret();
					if (!$secret) {
						return new WP_Error(
							'bbb_member_sync_not_configured',
							'Member sync secret is not configured.',
							array('status' => 503)
						);
					}

					if (!hash_equals($secret, bbb_get_bearer_token($request))) {
						return new WP_Error(
							'bbb_member_sync_forbidden',
							'Invalid member sync secret.',
							array('status' => 403)
						);
					}

					return true;
				},
				'callback'            => static function (WP_REST_Request $request) {
					$email  = sanitize_email((string) $request->get_param('email'));
					$active = rest_sanitize_boolean($request->get_param('active'));

					if (!$email || !is_email($email)) {
						return new WP_Error(
							'bbb_member_sync_invalid_email',
							'A valid email is required.',
							array('status' => 400)
						);
					}

					$user = get_user_by('email', $email);
					if (!$user && $active) {
						$user_id = wp_create_user($email, wp_generate_password(32, true), $email);
						if (is_wp_error($user_id)) {
							return $user_id;
						}
						$user = get_user_by('id', $user_id);
					}

					if (!$user) {
						return rest_ensure_response(
							array(
								'email'  => $email,
								'active' => false,
								'synced' => false,
								'reason' => 'user_not_found',
							)
						);
					}

					$wp_user = new WP_User($user->ID);
					if ($active) {
						$wp_user->add_role('society_member');
					} else {
						$wp_user->remove_role('society_member');
						if (!$wp_user->roles) {
							$wp_user->add_role('subscriber');
						}
					}

					update_user_meta($user->ID, '_bbb_society_member_active', $active ? '1' : '0');
					update_user_meta($user->ID, '_bbb_society_member_synced_at', current_time('mysql', true));

					return rest_ensure_response(
						array(
							'user_id' => $user->ID,
							'email'   => $email,
							'active'  => $active,
							'synced'  => true,
						)
					);
				},
			)
		);
	}
);

add_shortcode(
	'bbb_society_only',
	static function (array $atts = array(), ?string $content = null): string {
		$atts = shortcode_atts(
			array(
				'message' => 'Join the Society on Substack to unlock this.',
			),
			$atts,
			'bbb_society_only'
		);

		if (bbb_current_user_is_society_member()) {
			return do_shortcode((string) $content);
		}

		return '<div class="bbb-society-lock"><p>' . esc_html((string) $atts['message']) . '</p><a href="https://thesmutandsentimentsociety.substack.com/subscribe">Join the Society</a></div>';
	}
);

add_shortcode(
	'bbb_library',
	static function (array $atts = array()): string {
		$atts = shortcode_atts(
			array(
				'limit' => 24,
				'filters' => 'false',
			),
			$atts,
			'bbb_library'
		);

		$post_status = current_user_can('edit_posts') ? array('publish', 'draft') : array('publish');
		$query_args  = array(
			'post_type'      => 'bbb_book',
			'post_status'    => $post_status,
			'posts_per_page' => max(1, (int) $atts['limit']),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ('true' === strtolower((string) $atts['filters'])) {
			$query_args = bbb_apply_library_filters($query_args);
		}

		$query = new WP_Query($query_args);

		if (!$query->have_posts()) {
			$empty = current_user_can('edit_posts') ? 'No imported books found yet. Check Books > All Books to confirm the library import finished.' : 'No books found.';
			return ('true' === strtolower((string) $atts['filters']) ? bbb_render_library_filters() : '') . '<p class="bbb-library-empty">' . esc_html($empty) . '</p>';
		}

		ob_start();
		?>
		<?php if ('true' === strtolower((string) $atts['filters'])) : ?>
			<?php echo bbb_render_library_filters(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
		<div class="bbb-library-grid <?php echo 'true' === strtolower((string) $atts['filters']) ? 'bbb-library-grid--archive' : ''; ?>">
			<?php
			while ($query->have_posts()) {
				$query->the_post();
				echo bbb_render_library_card(get_the_ID()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			wp_reset_postdata();
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
);

add_shortcode(
	'bbb_book_profile',
	static function (): string {
		if ('bbb_book' !== get_post_type()) {
			return '';
		}

		return bbb_render_book_profile(get_the_ID());
	}
);

add_shortcode(
	'bbb_library_shelf',
	static function (array $atts = array()): string {
		$atts = shortcode_atts(
			array(
				'title'      => '',
				'kicker'     => '',
				'subtitle'   => '',
				'limit'      => 5,
				'meta_key'   => '',
				'meta_value' => '',
				'fallback'   => 'true',
				'class'      => '',
			),
			$atts,
			'bbb_library_shelf'
		);

		$post_status = current_user_can('edit_posts') ? array('publish', 'draft') : array('publish');
		$query_args  = array(
			'post_type'      => 'bbb_book',
			'post_status'    => $post_status,
			'posts_per_page' => max(1, (int) $atts['limit']),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ($atts['meta_key']) {
			$query_args['meta_query'] = array(
				array(
					'key'   => sanitize_key((string) $atts['meta_key']),
					'value' => sanitize_text_field((string) $atts['meta_value']),
				),
			);
		}

		$query = new WP_Query($query_args);
		if (!$query->have_posts() && 'true' === strtolower((string) $atts['fallback']) && $atts['meta_key']) {
			unset($query_args['meta_query']);
			$query = new WP_Query($query_args);
		}

		if (!$query->have_posts()) {
			return '';
		}

		ob_start();
		?>
		<section class="bbb-shelf <?php echo esc_attr((string) $atts['class']); ?>">
			<?php if ($atts['kicker']) : ?>
				<p class="bbb-shelf__kicker"><?php echo esc_html((string) $atts['kicker']); ?></p>
			<?php endif; ?>
			<?php if ($atts['title']) : ?>
				<h2 class="bbb-shelf__title"><?php echo esc_html((string) $atts['title']); ?></h2>
			<?php endif; ?>
			<?php if ($atts['subtitle']) : ?>
				<p class="bbb-shelf__sub"><?php echo esc_html((string) $atts['subtitle']); ?></p>
			<?php endif; ?>
			<div class="bbb-shelf__row">
				<?php
				while ($query->have_posts()) {
					$query->the_post();
					echo bbb_render_library_card(get_the_ID(), 'shelf'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				wp_reset_postdata();
				?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
);

function bbb_apply_library_filters(array $query_args): array {
	$search = isset($_GET['bbb_search']) ? sanitize_text_field(wp_unslash($_GET['bbb_search'])) : '';
	if ($search) {
		$query_args['s'] = $search;
	}

	$tax_query = array();
	foreach (array('bbb_genre', 'bbb_trope', 'bbb_series') as $taxonomy) {
		$value = isset($_GET[$taxonomy]) ? sanitize_title(wp_unslash($_GET[$taxonomy])) : '';
		if ($value) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $value,
			);
		}
	}

	if ($tax_query) {
		$query_args['tax_query'] = count($tax_query) > 1 ? array_merge(array('relation' => 'AND'), $tax_query) : $tax_query;
	}

	$meta_query = array();
	$spice      = isset($_GET['bbb_spice']) ? absint($_GET['bbb_spice']) : 0;
	if ($spice) {
		$meta_query[] = array(
			'key'     => '_bbb_spice_level',
			'value'   => $spice,
			'compare' => '>=',
			'type'    => 'NUMERIC',
		);
	}

	$ku = isset($_GET['bbb_ku']) ? sanitize_text_field(wp_unslash($_GET['bbb_ku'])) : '';
	if ('1' === $ku) {
		$meta_query[] = array(
			'key'   => '_bbb_on_kindle_unlimited',
			'value' => '1',
		);
	}

	if ($meta_query) {
		$query_args['meta_query'] = count($meta_query) > 1 ? array_merge(array('relation' => 'AND'), $meta_query) : $meta_query;
	}

	return $query_args;
}

function bbb_render_library_filters(): string {
	ob_start();
	?>
	<form class="bbb-library-filter" method="get">
		<label class="bbb-library-filter__search">
			<span>Find your read</span>
			<input type="search" name="bbb_search" value="<?php echo esc_attr(isset($_GET['bbb_search']) ? sanitize_text_field(wp_unslash($_GET['bbb_search'])) : ''); ?>" placeholder="search title, author, mood">
		</label>
		<?php echo bbb_render_taxonomy_select('bbb_genre', 'Shelf'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo bbb_render_taxonomy_select('bbb_trope', 'Trope'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<label>
			<span>Minimum spice</span>
			<select name="bbb_spice">
				<option value="">Any</option>
				<?php for ($i = 1; $i <= 5; $i++) : ?>
					<option value="<?php echo esc_attr((string) $i); ?>" <?php selected(isset($_GET['bbb_spice']) ? absint($_GET['bbb_spice']) : 0, $i); ?>><?php echo esc_html((string) $i); ?>+</option>
				<?php endfor; ?>
			</select>
		</label>
		<label>
			<span>Kindle Unlimited</span>
			<select name="bbb_ku">
				<option value="">Any</option>
				<option value="1" <?php selected(isset($_GET['bbb_ku']) ? sanitize_text_field(wp_unslash($_GET['bbb_ku'])) : '', '1'); ?>>Yes</option>
			</select>
		</label>
		<button type="submit">Search</button>
		<a class="bbb-library-filter__reset" href="<?php echo esc_url(home_url('/library/')); ?>">Reset</a>
	</form>
	<?php
	return (string) ob_get_clean();
}

function bbb_render_taxonomy_select(string $taxonomy, string $label): string {
	$terms    = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
	$selected = isset($_GET[$taxonomy]) ? sanitize_title(wp_unslash($_GET[$taxonomy])) : '';

	if (is_wp_error($terms) || !$terms) {
		return '';
	}

	ob_start();
	?>
	<label>
		<span><?php echo esc_html($label); ?></span>
		<select name="<?php echo esc_attr($taxonomy); ?>">
			<option value="">Any</option>
			<?php foreach ($terms as $term) : ?>
				<option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
			<?php endforeach; ?>
		</select>
	</label>
	<?php
	return (string) ob_get_clean();
}

function bbb_render_library_card(int $post_id, string $variant = ''): string {
	$cover_url    = (string) get_post_meta($post_id, '_bbb_cover_url', true);
	$author       = (string) get_post_meta($post_id, '_bbb_author', true);
	$access_level = (string) get_post_meta($post_id, '_bbb_access_level', true);
	$is_locked    = 'society' === $access_level && !bbb_current_user_is_society_member();
	$spice        = (string) get_post_meta($post_id, '_bbb_spice_level', true);
	$darkness     = (string) get_post_meta($post_id, '_bbb_darkness_level', true);
	$series_no    = (string) get_post_meta($post_id, '_bbb_series_number', true);
	$is_ku        = '1' === (string) get_post_meta($post_id, '_bbb_on_kindle_unlimited', true);
	$tropes       = get_the_terms($post_id, 'bbb_trope');
	$genres       = get_the_terms($post_id, 'bbb_genre');

	ob_start();
	?>
	<a class="bbb-library-card <?php echo $is_locked ? 'is-locked' : ''; ?> <?php echo esc_attr($variant ? 'bbb-library-card--' . $variant : ''); ?>" href="<?php echo esc_url(get_permalink($post_id)); ?>">
		<span class="bbb-library-card__media">
			<?php if ($cover_url) : ?>
				<img class="bbb-library-card__cover" src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?> cover" loading="lazy">
			<?php endif; ?>
			<?php if ($series_no) : ?>
				<span class="bbb-library-card__badge"><?php echo esc_html($series_no); ?></span>
			<?php endif; ?>
			<?php if ($spice) : ?>
				<span class="bbb-library-card__spice"><?php echo esc_html(str_repeat('🌶', min(5, max(1, (int) $spice)))); ?></span>
			<?php endif; ?>
			<span class="bbb-library-card__save">♡ save</span>
			<?php if ($is_ku) : ?>
				<span class="bbb-library-card__ribbon">read</span>
			<?php endif; ?>
			<?php if ($is_locked) : ?>
				<span class="bbb-library-card__lock">Society</span>
			<?php endif; ?>
		</span>
		<span class="bbb-library-card__title"><?php echo esc_html(get_the_title($post_id)); ?></span>
		<?php if ($author) : ?>
			<span class="bbb-library-card__author"><?php echo esc_html($author); ?></span>
		<?php endif; ?>
		<span class="bbb-library-card__meta">
			<?php if ($spice) : ?><span>spice <?php echo esc_html($spice); ?></span><?php endif; ?>
			<?php if ($darkness) : ?><span>dark <?php echo esc_html($darkness); ?></span><?php endif; ?>
		</span>
		<?php if (!is_wp_error($genres) && $genres) : ?>
			<span class="bbb-library-card__chip"><?php echo esc_html($genres[0]->name); ?></span>
		<?php elseif (!is_wp_error($tropes) && $tropes) : ?>
			<span class="bbb-library-card__chip"><?php echo esc_html($tropes[0]->name); ?></span>
		<?php endif; ?>
		<?php if ('draft' === get_post_status($post_id)) : ?>
			<span class="bbb-library-card__status">draft</span>
		<?php endif; ?>
	</a>
	<?php
	return (string) ob_get_clean();
}

function bbb_render_book_profile(int $post_id): string {
	$cover_url    = (string) get_post_meta($post_id, '_bbb_cover_url', true);
	$author       = (string) get_post_meta($post_id, '_bbb_author', true);
	$access_level = (string) get_post_meta($post_id, '_bbb_access_level', true);
	$is_locked    = 'society' === $access_level && !bbb_current_user_is_society_member();
	$fields       = array(
		'Spice'            => get_post_meta($post_id, '_bbb_spice_level', true),
		'Darkness'         => get_post_meta($post_id, '_bbb_darkness_level', true),
		'Tension'          => get_post_meta($post_id, '_bbb_tension_score', true),
		'Emotional damage' => get_post_meta($post_id, '_bbb_emotional_damage_score', true),
		'Yearning'         => get_post_meta($post_id, '_bbb_yearning_level', true),
	);
	$amazon_url   = (string) get_post_meta($post_id, '_bbb_amazon_url', true);
	$bookshop_url = (string) get_post_meta($post_id, '_bbb_bookshop_url', true);

	ob_start();
	?>
	<article class="bbb-book-profile">
		<div class="bbb-book-profile__cover">
			<?php if ($cover_url) : ?>
				<img src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?> cover">
			<?php endif; ?>
		</div>
		<div class="bbb-book-profile__body">
			<p class="bbb-book-profile__kicker"><?php echo $is_locked ? 'Society shelf' : 'Library shelf'; ?></p>
			<h1><?php echo esc_html(get_the_title($post_id)); ?></h1>
			<?php if ($author) : ?>
				<p class="bbb-book-profile__author">by <?php echo esc_html($author); ?></p>
			<?php endif; ?>
			<div class="bbb-book-profile__terms">
				<?php echo bbb_render_post_terms($post_id, 'bbb_genre'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo bbb_render_post_terms($post_id, 'bbb_trope'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo bbb_render_post_terms($post_id, 'bbb_series'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php if ($is_locked) : ?>
				<div class="bbb-book-profile__teaser">
					<p>This book is on the Society shelf. Join through Substack and log in with the same email to unlock the full notes.</p>
					<a href="https://thesmutandsentimentsociety.substack.com/subscribe">Join the Society</a>
				</div>
			<?php else : ?>
				<div class="bbb-book-profile__scores">
					<?php foreach ($fields as $label => $value) : ?>
						<?php if ('' !== (string) $value) : ?>
							<div><span><?php echo esc_html($label); ?></span><strong><?php echo esc_html((string) $value); ?></strong></div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<div class="bbb-book-profile__content">
					<?php echo apply_filters('the_content', get_post_field('post_content', $post_id)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
			<div class="bbb-book-profile__links">
				<?php if ($amazon_url) : ?><a href="<?php echo esc_url($amazon_url); ?>">Amazon</a><?php endif; ?>
				<?php if ($bookshop_url) : ?><a href="<?php echo esc_url($bookshop_url); ?>">Bookshop</a><?php endif; ?>
				<a href="<?php echo esc_url(home_url('/library/')); ?>">Back to library</a>
			</div>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

function bbb_render_post_terms(int $post_id, string $taxonomy): string {
	$terms = get_the_terms($post_id, $taxonomy);
	if (is_wp_error($terms) || !$terms) {
		return '';
	}

	return implode(
		'',
		array_map(
			static fn($term): string => '<span>' . esc_html($term->name) . '</span>',
			$terms
		)
	);
}

// ============================================================
// BLOG SHORTCODES
// ============================================================

add_filter(
	'the_content',
	static function (string $content): string {
		$content = preg_replace('/\[specific:([A-Za-z0-9_-]+)\]/', '[bbb_specific context="$1"]', $content) ?? $content;
		$content = preg_replace('/\[book:([0-9]+)\]/', '[bbb_article_book index="$1"]', $content) ?? $content;

		return $content;
	},
	9
);

function bbb_find_book_by_title(string $title): ?WP_Post {
	$title = trim(wp_strip_all_tags($title));
	if ('' === $title) {
		return null;
	}

	$query = new WP_Query(
		array(
			'post_type'      => 'bbb_book',
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			's'              => $title,
		)
	);

	if (!$query->have_posts()) {
		return null;
	}

	$normalized_title = strtolower($title);
	$fallback         = null;

	foreach ($query->posts as $book) {
		if (!$book instanceof WP_Post) {
			continue;
		}

		if (!$fallback) {
			$fallback = $book;
		}

		if (strtolower(get_the_title($book)) === $normalized_title) {
			return $book;
		}
	}

	return $fallback;
}

function bbb_get_article_book_title(int $post_id, int $index): string {
	$content = (string) get_post_field('post_content', $post_id);
	$token   = '[book:' . $index . ']';
	$offset  = strpos($content, $token);

	if (false === $offset) {
		return '';
	}

	$after = substr($content, $offset + strlen($token));
	$after = preg_replace('/<!--\s*\/?wp:[^>]*-->/i', "\n", $after) ?? $after;
	$after = preg_replace('/<\/p>|<br\s*\/?>|<\/h[1-6]>|<\/div>/i', "\n", $after) ?? $after;
	$after = wp_strip_all_tags($after);
	$lines = preg_split('/\R+/', html_entity_decode($after, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset')));

	foreach ((array) $lines as $line) {
		$line = trim($line);
		if ('' === $line || 0 === strpos($line, '[')) {
			continue;
		}

		return $line;
	}

	return '';
}

function bbb_render_article_book_card(int $post_id): string {
	$cover_url    = (string) get_post_meta($post_id, '_bbb_cover_url', true);
	$author       = (string) get_post_meta($post_id, '_bbb_author', true);
	$spice        = (string) get_post_meta($post_id, '_bbb_spice_level', true);
	$series_no    = (string) get_post_meta($post_id, '_bbb_series_number', true);
	$is_ku        = '1' === (string) get_post_meta($post_id, '_bbb_on_kindle_unlimited', true);
	$amazon_url   = (string) get_post_meta($post_id, '_bbb_amazon_url', true);
	$bookshop_url = (string) get_post_meta($post_id, '_bbb_bookshop_url', true);
	$genres       = get_the_terms($post_id, 'bbb_genre');
	$tropes       = get_the_terms($post_id, 'bbb_trope');

	ob_start();
	?>
	<aside class="article-book-card" data-book-preview>
		<div class="article-book-card__header">
			<?php if (!is_wp_error($genres) && $genres) : ?>
				<div class="article-book-card__genreRow">
					<span class="article-book-card__genreLine" aria-hidden="true"></span>
					<span class="article-book-card__genre"><?php echo esc_html($genres[0]->name); ?></span>
				</div>
			<?php endif; ?>
			<h3><a href="<?php echo esc_url((string) get_permalink($post_id)); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a></h3>
			<?php if ($author) : ?><div class="article-book-card__author"><?php echo esc_html($author); ?></div><?php endif; ?>
			<?php if ($series_no) : ?><div class="article-book-card__series">#<?php echo esc_html($series_no); ?></div><?php endif; ?>
		</div>

		<div class="article-book-card__image">
			<span class="article-book-card__heart" aria-label="save to your bookshelf"><span aria-hidden="true">♡</span><span>save</span></span>
			<?php if ($spice) : ?><div class="article-book-card__spice"><?php echo esc_html(str_repeat('🌶', min(5, max(1, (int) $spice)))); ?></div><?php endif; ?>
			<?php if ($cover_url) : ?><img src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?> cover" loading="lazy"><?php endif; ?>
		</div>

		<div class="article-book-card__content">
			<?php if (!is_wp_error($tropes) && $tropes) : ?>
				<div class="article-book-card__tropes">
					<?php foreach (array_slice($tropes, 0, 6) as $trope) : ?>
						<span class="article-book-card__trope"><?php echo esc_html($trope->name); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="article-book-card__ratings">
				<span class="article-book-card__ku <?php echo $is_ku ? 'article-book-card__ku--yes' : 'article-book-card__ku--no'; ?>"><?php echo $is_ku ? '✓ on kindle unlimited' : 'check kindle unlimited'; ?></span>
			</div>
			<div class="article-book-card__buttons">
				<?php if ($amazon_url) : ?><a class="article-book-card__button article-book-card__button--amazon" href="<?php echo esc_url($amazon_url); ?>" target="_blank" rel="noopener noreferrer">amazon</a><?php endif; ?>
				<?php if ($bookshop_url) : ?><a class="article-book-card__button article-book-card__button--bookshop" href="<?php echo esc_url($bookshop_url); ?>" target="_blank" rel="noopener noreferrer">bookshop</a><?php endif; ?>
			</div>
		</div>
	</aside>
	<?php
	return (string) ob_get_clean();
}

function bbb_render_specific_links(string $context): string {
	$context = sanitize_title($context);
	$maps    = array(
		'morallygrayfantasy' => array(
			'romantasy books' => '/blogs/curated-romance-guides/the-ultimate-romantasy-reading-guide',
			'morally gray men' => '/blogs/curated-romance-guides/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you',
			'touch her and die' => '/pages/touch-her-and-die-books',
			'villain gets the girl' => '/pages/villain-gets-the-girl-romance-books',
		),
		'darkromantasy'     => array(
			'romantasy books' => '/pages/romantasy-books',
			'dark romantasy' => '/blogs/curated-romance-guides/dark-romantasy-books',
			'touch her and die' => '/pages/touch-her-and-die-books',
			'villain gets the girl' => '/pages/villain-gets-the-girl-romance-books',
		),
		'morallygraymen'    => array(
			'dark romance books' => '/pages/dark-romance-books',
			'villain gets the girl' => '/pages/villain-gets-the-girl-romance-books',
			'touch her and die' => '/pages/touch-her-and-die-books',
			'stalker romance' => '/pages/stalker-romance-books',
		),
		'dark'              => array(
			'stalker romance' => '/pages/stalker-romance-books',
			'captor x captive' => '/pages/captor-captive-romance-books',
			'morally gray men' => '/blogs/curated-romance-guides/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you',
		),
		'sports'            => array(
			'slow burn romance books' => '/pages/slow-burn-books',
			'fake dating romance' => '/pages/fake-dating-romance-books',
			'forced proximity' => '/pages/forced-proximity-romance-books',
		),
		'slowburn'          => array(
			'enemies to lovers' => '/pages/enemies-to-lovers',
			'forced proximity' => '/pages/forced-proximity-romance-books',
			'he falls first' => '/pages/he-falls-first-romance-books',
		),
	);

	$links = $maps[$context] ?? array('browse the library' => '/library');

	ob_start();
	?>
	<nav class="blog-specific-links" aria-label="specific romance guide links">
		<span class="blog-specific-links__prompt">looking for something specific?</span>
		<span class="blog-specific-links__arrow" aria-hidden="true">&rarr;</span>
		<?php $i = 0; foreach ($links as $label => $url) : ?>
			<?php if ($i > 0) : ?><span class="blog-specific-links__dot" aria-hidden="true">·</span><?php endif; ?>
			<a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
		<?php $i++; endforeach; ?>
	</nav>
	<?php
	return (string) ob_get_clean();
}

add_shortcode(
	'bbb_specific',
	static function (array $atts = array()): string {
		$atts = shortcode_atts(array('context' => ''), $atts, 'bbb_specific');

		return bbb_render_specific_links((string) $atts['context']);
	}
);

add_shortcode(
	'bbb_article_book',
	static function (array $atts = array()): string {
		global $post;

		if (!$post instanceof WP_Post) {
			return '';
		}

		$atts  = shortcode_atts(array('index' => '1'), $atts, 'bbb_article_book');
		$index = max(1, absint($atts['index']));
		$title = bbb_get_article_book_title($post->ID, $index);
		$book  = bbb_find_book_by_title($title);

		if (!$book instanceof WP_Post) {
			if (!current_user_can('edit_posts')) {
				return '';
			}

			return sprintf(
				'<p class="bbb-token-notice"><em>No book found for <code>[book:%1$s]</code>%2$s.</em></p>',
				esc_html((string) $index),
				$title ? ' after title <code>' . esc_html($title) . '</code>' : ''
			);
		}

		return bbb_render_article_book_card($book->ID);
	}
);

add_shortcode(
	'bookcard',
	static function (): string {
		global $post;

		if (!$post instanceof WP_Post) {
			return '';
		}

		preg_match_all('/\[book:([0-9]+)\]/', (string) get_post_field('post_content', $post->ID), $matches);
		$indexes = array_values(array_unique(array_map('absint', $matches[1] ?? array())));

		if (!$indexes) {
			return '';
		}

		$cards = '';
		foreach ($indexes as $index) {
			$title = bbb_get_article_book_title($post->ID, $index);
			$book  = bbb_find_book_by_title($title);

			if ($book instanceof WP_Post) {
				$cards .= bbb_render_article_book_card($book->ID);
			}
		}

		return $cards ? '<div class="article-book-list">' . $cards . '</div>' : '';
	}
);

/**
 * [faq]...[/faq]
 * Wraps a block of [q]/[a] pairs in a styled FAQ section.
 */
add_shortcode(
	'faq',
	static function (array $atts = array(), ?string $content = null): string {
		return '<div class="bbb-faq">' . do_shortcode((string) $content) . '</div>';
	}
);

/**
 * [q]Question text here[/q]
 * Renders a single FAQ question.
 */
add_shortcode(
	'q',
	static function (array $atts = array(), ?string $content = null): string {
		return '<div class="bbb-faq__q">' . wp_kses_post(do_shortcode((string) $content)) . '</div>';
	}
);

/**
 * [a]Answer text here[/a]
 * Renders a single FAQ answer.
 */
add_shortcode(
	'a',
	static function (array $atts = array(), ?string $content = null): string {
		return '<div class="bbb-faq__a">' . wp_kses_post(do_shortcode((string) $content)) . '</div>';
	}
);

/**
 * [ku]
 * Renders a Kindle Unlimited availability badge for the current post.
 * Reads the _bbb_on_kindle_unlimited post meta (set to '1' if on KU).
 * Falls back to a neutral "check KU" link if meta is not set.
 */
add_shortcode(
	'ku',
	static function (array $atts = array()): string {
		global $post;
		if (!$post) {
			return '';
		}

		$on_ku = get_post_meta($post->ID, '_bbb_on_kindle_unlimited', true);

		if ('1' === (string) $on_ku) {
			$label = 'available on kindle unlimited';
			$mod   = 'bbb-ku--yes';
			$icon  = '✓';
		} elseif ('0' === (string) $on_ku) {
			$label = 'not on kindle unlimited';
			$mod   = 'bbb-ku--no';
			$icon  = '✗';
		} else {
			$label = 'check kindle unlimited';
			$mod   = 'bbb-ku--unknown';
			$icon  = '';
		}

		return sprintf(
			'<div class="bbb-ku %s">%s<a class="bbb-ku__link" href="https://www.amazon.com/kindle-dbs/hz/subscribe/ku" target="_blank" rel="noopener noreferrer">%s</a></div>',
			esc_attr($mod),
			$icon ? '<span class="bbb-ku__icon" aria-hidden="true">' . esc_html($icon) . '</span>' : '',
			esc_html($label)
		);
	}
);

/**
 * [series] or [series name="series-slug"]
 * Renders an ordered list of books in a series.
 *
 * Priority for determining the series:
 * 1. name="" shortcode attribute (slug of a bbb_series term)
 * 2. _bbb_series_slug custom field on the current post
 *
 * Books are ordered by the _bbb_series_order post meta (integer).
 */
add_shortcode(
	'series',
	static function (array $atts = array()): string {
		global $post;

		$atts       = shortcode_atts(array('name' => ''), $atts, 'series');
		$series_slug = sanitize_title((string) $atts['name']);

		if (!$series_slug && $post) {
			$series_slug = sanitize_title((string) get_post_meta($post->ID, '_bbb_series_slug', true));
		}

		if (!$series_slug) {
			if (current_user_can('edit_posts')) {
				return '<p class="bbb-series-notice"><em>Set the <code>_bbb_series_slug</code> custom field on this post, or use <code>[series name="slug"]</code>, to display a reading order here.</em></p>';
			}
			return '';
		}

		$term = get_term_by('slug', $series_slug, 'bbb_series');

		if (!$term || is_wp_error($term)) {
			if (current_user_can('edit_posts')) {
				return '<p class="bbb-series-notice"><em>No series found with slug "<code>' . esc_html($series_slug) . '</code>". Make sure the series exists under Books &rarr; Series.</em></p>';
			}
			return '';
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'bbb_book',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'tax_query'      => array(
					array(
						'taxonomy' => 'bbb_series',
						'field'    => 'term_id',
						'terms'    => $term->term_id,
					),
				),
				'meta_key' => '_bbb_series_order',
				'orderby'  => 'meta_value_num',
				'order'    => 'ASC',
			)
		);

		if (!$query->have_posts()) {
			if (current_user_can('edit_posts')) {
				return '<p class="bbb-series-notice"><em>Series "' . esc_html($term->name) . '" exists but has no published books assigned to it yet.</em></p>';
			}
			return '';
		}

		ob_start();
		echo '<div class="bbb-series-list">';
		$i = 1;
		while ($query->have_posts()) {
			$query->the_post();
			$cover_url = (string) get_post_meta(get_the_ID(), '_bbb_cover_url', true);
			$author    = (string) get_post_meta(get_the_ID(), '_bbb_author', true);
			echo '<a class="bbb-series-item" href="' . esc_url((string) get_permalink()) . '">';
			if ($cover_url) {
				echo '<img class="bbb-series-item__cover" src="' . esc_url($cover_url) . '" alt="' . esc_attr(get_the_title()) . '" loading="lazy">';
			}
			echo '<div class="bbb-series-item__info">';
			echo '<span class="bbb-series-item__num">book ' . esc_html((string) $i) . '</span>';
			echo '<span class="bbb-series-item__title">' . esc_html(get_the_title()) . '</span>';
			if ($author) {
				echo '<span class="bbb-series-item__author">' . esc_html($author) . '</span>';
			}
			echo '</div></a>';
			$i++;
		}
		wp_reset_postdata();
		echo '</div>';
		return (string) ob_get_clean();
	}
);

/**
 * [bigspecific]
 * Renders a "dangerously specific recommendation" CTA block
 * linking to the /what-to-read-next tool.
 */
add_shortcode(
	'bigspecific',
	static function (): string {
		return '<div class="bbb-bigspecific">
			<p class="bbb-bigspecific__kicker">reader matchmaker</p>
			<p class="bbb-bigspecific__title">want a dangerously specific recommendation?</p>
			<p class="bbb-bigspecific__sub">pick a book you loved and get a romance rec that\'s almost too specific to be legal.</p>
			<a class="bbb-bigspecific__cta" href="/what-to-read-next">find your match &rarr;</a>
		</div>';
	}
);

/**
 * [library]
 * Renders a short inline link to the full book library.
 */
add_shortcode(
	'library',
	static function (): string {
		$url = home_url('/library/');
		return '<a class="bbb-inline-library" href="' . esc_url($url) . '">browse the full library &rarr;</a>';
	}
);

/**
 * [signoff]
 * Renders a newsletter signup CTA at the end of posts.
 * Matches the .bbb-signoff structure from Shopify blog-signoff.css.
 *
 * Optional attributes:
 *   kicker  — overline text   (default: "you made it to the end")
 *   title   — headline        (default: "stay in the loop")
 *   text    — body copy       (default: see below)
 *   url     — subscribe link  (default: Substack subscribe)
 *   cta     — button label    (default: "join the society →")
 */
add_shortcode(
	'signoff',
	static function ( array $atts ): string {
		$a = shortcode_atts(
			array(
				'kicker' => 'you made it to the end',
				'title'  => 'stay in the loop',
				'text'   => 'morally gray men. spicy recs. weekly drama. in your inbox.',
				'url'    => 'https://thesmutandsentimentsociety.substack.com/subscribe',
				'cta'    => 'join the society &rarr;',
			),
			$atts,
			'signoff'
		);

		$kicker = esc_html( $a['kicker'] );
		$title  = esc_html( $a['title'] );
		$text   = esc_html( $a['text'] );
		$url    = esc_url( $a['url'] );
		$cta    = wp_kses( $a['cta'], array( 'span' => array(), 'em' => array() ) );

		return sprintf(
			'<div class="bbb-signoff">
				<div class="bbb-signoff__sparkle" aria-hidden="true"></div>
				<div class="bbb-signoff__inner">
					<p class="bbb-signoff__kicker">%s</p>
					<h2 class="bbb-signoff__title">%s</h2>
					<p class="bbb-signoff__text">%s</p>
					<div class="bbb-signoff__actions">
						<a class="bbb-signoff__btn bbb-signoff__btn--primary" href="%s" target="_blank" rel="noopener noreferrer">%s</a>
					</div>
				</div>
			</div>',
			$kicker,
			$title,
			$text,
			$url,
			$cta
		);
	}
);
