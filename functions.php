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
			'https://fonts.googleapis.com/css2?family=Allura&family=Cormorant:wght@500;600&family=Cormorant+Garamond:wght@400;500;600;700&family=Great+Vibes&family=Kaushan+Script&family=Playfair+Display:ital,wght@0,400;0,600;1,400;1,600&display=swap',
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
				'has_archive'  => true,
				'rewrite'      => array('slug' => 'library'),
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
			),
			$atts,
			'bbb_library'
		);

		$post_status = current_user_can('edit_posts') ? array('publish', 'draft') : array('publish');

		$query = new WP_Query(
			array(
				'post_type'      => 'bbb_book',
				'post_status'    => $post_status,
				'posts_per_page' => max(1, (int) $atts['limit']),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if (!$query->have_posts()) {
			return current_user_can('edit_posts') ? '<p class="bbb-library-empty">No imported books found yet. Check Books > All Books to confirm the library import finished.</p>' : '';
		}

		ob_start();
		?>
		<div class="bbb-library-grid">
			<?php
			while ($query->have_posts()) {
				$query->the_post();
				$cover_url = (string) get_post_meta(get_the_ID(), '_bbb_cover_url', true);
				$author    = (string) get_post_meta(get_the_ID(), '_bbb_author', true);
				?>
				<a class="bbb-library-card" href="<?php the_permalink(); ?>">
					<?php if ($cover_url) : ?>
						<img class="bbb-library-card__cover" src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?> cover" loading="lazy">
					<?php endif; ?>
					<span class="bbb-library-card__title"><?php the_title(); ?></span>
					<?php if ($author) : ?>
						<span class="bbb-library-card__author"><?php echo esc_html($author); ?></span>
					<?php endif; ?>
					<?php if ('draft' === get_post_status()) : ?>
						<span class="bbb-library-card__status">draft</span>
					<?php endif; ?>
				</a>
				<?php
			}
			wp_reset_postdata();
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
);
