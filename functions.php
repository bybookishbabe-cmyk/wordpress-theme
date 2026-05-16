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
		$rewrite_version = '2026-05-16-books-v2';

		if (get_option('bbb_rewrite_version') !== $rewrite_version) {
			flush_rewrite_rules(false);
			update_option('bbb_rewrite_version', $rewrite_version);
		}
	},
	20
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
 */
add_shortcode(
	'signoff',
	static function (): string {
		return '<div class="bbb-signoff">
			<p class="bbb-signoff__line">morally gray men. weekly. in your inbox.</p>
			<a class="bbb-signoff__cta" href="https://thesmutandsentimentsociety.substack.com/subscribe" target="_blank" rel="noopener noreferrer">join the smut &amp; sentiment society &rarr;</a>
		</div>';
	}
);
