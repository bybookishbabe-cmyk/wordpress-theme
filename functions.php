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

add_shortcode(
	'bbb_library',
	static function (): string {
		$query = new WP_Query(
			array(
				'post_type'      => 'bbb_book',
				'post_status'    => 'publish',
				'posts_per_page' => 24,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if (!$query->have_posts()) {
			return '';
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
