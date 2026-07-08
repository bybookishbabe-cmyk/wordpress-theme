<?php
/**
 * Fictional boyfriend profiles.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_fictional_boyfriend_synced_meta_keys(): array {
	return array(
		'_bbb_fb_book_ids',
		'_bbb_fb_source',
		'_bbb_fb_author',
		'_bbb_fb_series',
		'_bbb_fb_shelf',
		'_bbb_fb_tropes',
		'_bbb_fb_seo_description',
		'_bbb_fb_traits',
		'_bbb_fb_trait_scores',
		'_bbb_fb_pinterest_title',
		'_bbb_fb_pinterest_description',
		'_bbb_fb_pinterest_urls',
		'_bbb_fb_would_text_back',
		'_bbb_fb_love_language',
		'_bbb_fb_one_line_profile',
		'_bbb_fb_read_next_note',
	);
}

function bbb_fictional_boyfriend_trait_options(): array {
	return array(
		'acts-of-service',
		'alpha',
		'ambitious',
		'athletic',
		'banter-heavy',
		'broody',
		'calm-under-pressure',
		'charming',
		'cocky',
		'commanding',
		'competitive',
		'confident',
		'controlled',
		'dangerous',
		'devoted',
		'disciplined',
		'emotionally-guarded',
		'emotionally-intelligent',
		'expressive',
		'flirty',
		'focused',
		'gentle',
		'golden-retriever',
		'grounded',
		'grumpy',
		'he-falls-first',
		'intense',
		'jealous',
		'leader',
		'loyal',
		'morally-gray',
		'obsessive',
		'patient',
		'playful',
		'possessive',
		'protective',
		'quiet',
		'relentless',
		'reserved',
		'ruthless',
		'sarcastic',
		'soft-for-her',
		'steady',
		'stern',
		'teasing',
		'tortured',
		'trustworthy',
		'vulnerable-underneath',
		'warm',
		'watchful',
	);
}

function bbb_fictional_boyfriend_trait_label(string $trait): string {
	return ucwords(str_replace('-', ' ', $trait));
}

function bbb_fictional_boyfriend_score_options(): array {
	return array(
		'possessive' => 'Possessive',
		'protective' => 'Protective',
		'emotionally-available' => 'Emotionally available',
		'will-apologize' => 'Will apologize',
		'actually-good-for-you' => 'Actually good for you',
	);
}

function bbb_fictional_boyfriend_love_language_options(): array {
	return array(
		'acts of service',
		'words of affirmation',
		'physical touch',
		'quality time',
		'gift giving',
	);
}

function bbb_fictional_boyfriend_selectable_books(int $post_id = 0): array {
	$post_types = array_values(
		array_filter(
			array('bbb_book', 'sss_book'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);

	if (!$post_types) {
		return array();
	}

	$current_ids = $post_id > 0 && function_exists('bbb_fictional_boyfriend_book_ids')
		? bbb_fictional_boyfriend_book_ids($post_id)
		: array();
	$books = get_posts(
		array(
			'post_type'              => $post_types,
			'post_status'            => array('publish', 'draft', 'pending', 'private', 'future'),
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);
	$by_id = array();

	foreach ($books as $book) {
		if ($book instanceof WP_Post) {
			$by_id[(int) $book->ID] = $book;
		}
	}

	$ordered = array();
	foreach ($current_ids as $book_id) {
		if (isset($by_id[$book_id])) {
			$ordered[$book_id] = $by_id[$book_id];
			unset($by_id[$book_id]);
		}
	}

	foreach ($by_id as $book_id => $book) {
		$ordered[$book_id] = $book;
	}

	return array_values($ordered);
}

add_action(
	'init',
	static function (): void {
		register_post_type(
			'bbb_boyfriend',
			array(
				'labels'       => array(
					'name'               => __('Fictional Boyfriends', 'bybookishbabe-shopify-port'),
					'singular_name'      => __('Fictional Boyfriend', 'bybookishbabe-shopify-port'),
					'add_new_item'       => __('Add Fictional Boyfriend', 'bybookishbabe-shopify-port'),
					'edit_item'          => __('Edit Fictional Boyfriend', 'bybookishbabe-shopify-port'),
					'new_item'           => __('New Fictional Boyfriend', 'bybookishbabe-shopify-port'),
					'view_item'          => __('View Fictional Boyfriend', 'bybookishbabe-shopify-port'),
					'search_items'       => __('Search Fictional Boyfriends', 'bybookishbabe-shopify-port'),
					'not_found'          => __('No fictional boyfriends found.', 'bybookishbabe-shopify-port'),
					'not_found_in_trash' => __('No fictional boyfriends found in Trash.', 'bybookishbabe-shopify-port'),
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-heart',
				'supports'     => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions'),
				'has_archive'  => false,
				'rewrite'      => array('slug' => 'fictional-boyfriends'),
				'template'     => bbb_fictional_boyfriend_editor_template(),
			)
		);

		$taxonomies = array(
			'bbb_boyfriend_trope'     => array('label' => 'Boyfriend Tropes', 'slug' => 'book-boyfriend-trope'),
			'bbb_boyfriend_trait'     => array('label' => 'Boyfriend Traits', 'slug' => 'book-boyfriend-trait'),
			'bbb_boyfriend_archetype' => array('label' => 'Boyfriend Archetypes', 'slug' => 'book-boyfriend-archetype'),
		);

		foreach ($taxonomies as $taxonomy => $args) {
			register_taxonomy(
				$taxonomy,
				'bbb_boyfriend',
				array(
					'label'        => $args['label'],
					'hierarchical' => false,
					'meta_box_cb'  => false,
					'show_in_rest' => true,
					'rewrite'      => array('slug' => $args['slug']),
				)
			);
		}

		foreach (bbb_fictional_boyfriend_synced_meta_keys() as $meta_key) {
			register_post_meta(
				'bbb_boyfriend',
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_textarea_field',
					'auth_callback'     => static fn(): bool => current_user_can('edit_posts'),
				)
			);
		}
	}
);

add_action(
	'add_meta_boxes_bbb_boyfriend',
	static function (): void {
		add_meta_box(
			'bbb_fictional_boyfriend_details',
			__('Boyfriend Details', 'bybookishbabe-shopify-port'),
			'bbb_fictional_boyfriend_render_meta_box',
			'bbb_boyfriend',
			'normal',
			'high'
		);

		if (function_exists('bbb_render_hidden_from_public_browsing_meta_box')) {
			add_meta_box(
				'bbb_fictional_boyfriend_visibility',
				__('Public Visibility', 'bybookishbabe-shopify-port'),
				'bbb_render_hidden_from_public_browsing_meta_box',
				'bbb_boyfriend',
				'side',
				'high'
			);
		}
	}
);

function bbb_fictional_boyfriend_render_meta_box(WP_Post $post): void {
	wp_enqueue_media();
	wp_nonce_field('bbb_save_fictional_boyfriend_details', 'bbb_fictional_boyfriend_nonce');
	$current_book_ids = bbb_fictional_boyfriend_book_ids($post->ID);
	$book_options = bbb_fictional_boyfriend_selectable_books($post->ID);
	$current_traits = array_filter(array_map('sanitize_title', preg_split('/\s*,\s*/', (string) get_post_meta($post->ID, '_bbb_fb_traits', true)) ?: array()));
	$current_scores = bbb_fictional_boyfriend_trait_scores($post->ID);
	$one_line_profile = (string) get_post_meta($post->ID, '_bbb_fb_one_line_profile', true);
	$seo_description = (string) get_post_meta($post->ID, '_bbb_fb_seo_description', true);
	$pinterest_title = (string) get_post_meta($post->ID, '_bbb_fb_pinterest_title', true);
	$pinterest_description = (string) get_post_meta($post->ID, '_bbb_fb_pinterest_description', true);
	$love_language = (string) get_post_meta($post->ID, '_bbb_fb_love_language', true);
	$would_text_back = (string) get_post_meta($post->ID, '_bbb_fb_would_text_back', true);
	$read_next_note = (string) get_post_meta($post->ID, '_bbb_fb_read_next_note', true);
	$dropbox_aesthetic_folder = 'Apps/bybookishbabe-edd-products/images/fictional-boyfriends/' . $post->post_name . '/';
	$dropbox_aesthetic_url = 'https://www.dropbox.com/home/' . str_replace('%2F', '/', rawurlencode($dropbox_aesthetic_folder));
	$aesthetic_images = array();
	foreach (bbb_fictional_boyfriend_lines_meta($post->ID, '_bbb_fb_pinterest_urls') as $line) {
		$parts = array_map('trim', explode('|', $line, 2));
		$aesthetic_images[] = array(
			'image' => (string) ($parts[0] ?? ''),
			'link'  => (string) ($parts[1] ?? ''),
		);
	}
	for ($slot = count($aesthetic_images); $slot < 3; $slot++) {
		$aesthetic_images[] = array(
			'image' => '',
			'link'  => '',
		);
	}
	$aesthetic_images = array_slice($aesthetic_images, 0, 3);
	?>
	<style>
		.bbb-fb-admin-fields { display: grid; gap: 16px; }
		.bbb-fb-admin-fields label { display: block; margin-bottom: 6px; font-weight: 600; }
		.bbb-fb-admin-fields input[type="text"],
		.bbb-fb-admin-fields input[type="url"],
		.bbb-fb-admin-fields input[type="password"],
		.bbb-fb-admin-fields input[type="search"],
		.bbb-fb-admin-fields select,
		.bbb-fb-admin-fields textarea { width: 100%; max-width: 760px; }
		.bbb-fb-admin-fields textarea { min-height: 110px; }
		.bbb-fb-admin-fields .description { max-width: 760px; }
		.bbb-fb-admin-linked { border: 1px solid #dcdcde; border-radius: 6px; padding: 12px; max-width: 760px; background: #fff; }
		.bbb-fb-admin-linked select { min-height: 180px; }
		.bbb-fb-admin-linked__current { margin: 8px 0 0; }
		.bbb-fb-admin-aesthetic { display: grid; gap: 12px; max-width: 760px; }
		.bbb-fb-admin-aesthetic__dropbox { padding: 12px; border: 1px solid #dcdcde; border-radius: 6px; background: #fff; }
		.bbb-fb-admin-aesthetic__dropbox code { display: block; margin: 8px 0; white-space: normal; word-break: break-word; }
		.bbb-fb-admin-aesthetic__slot { display: grid; grid-template-columns: 112px minmax(0, 1fr); gap: 12px; align-items: start; padding: 12px; border: 1px solid #dcdcde; border-radius: 6px; background: #fff; }
		.bbb-fb-admin-aesthetic__preview { display: block; width: 100px; aspect-ratio: 2 / 3; object-fit: cover; border: 1px solid #dcdcde; border-radius: 4px; background: #f6f7f7; }
		.bbb-fb-admin-aesthetic__controls { display: grid; gap: 8px; }
		.bbb-fb-admin-aesthetic__buttons { display: flex; flex-wrap: wrap; gap: 8px; }
		.bbb-fb-admin-aesthetic-import { padding: 12px; border: 1px solid #dcdcde; border-radius: 6px; background: #fff; }
		.bbb-fb-admin-aesthetic-import summary { cursor: pointer; font-weight: 700; }
		.bbb-fb-admin-aesthetic-import__fields { display: grid; gap: 10px; margin-top: 12px; }
		.bbb-fb-admin-aesthetic-import__row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 10px; }
		.bbb-fb-admin-aesthetic-import__actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
		.bbb-fb-admin-aesthetic-import__status { color: #646970; }
		.bbb-fb-admin-aesthetic-import__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(116px, 1fr)); gap: 10px; margin-top: 8px; max-height: 420px; overflow: auto; }
		.bbb-fb-admin-aesthetic-import__pin { display: grid; gap: 6px; padding: 6px; border: 1px solid #dcdcde; border-radius: 6px; background: #f6f7f7; cursor: pointer; text-align: left; }
		.bbb-fb-admin-aesthetic-import__pin[aria-pressed="true"] { border-color: #d63638; box-shadow: 0 0 0 2px rgba(214, 54, 56, 0.16); }
		.bbb-fb-admin-aesthetic-import__pin img { width: 100%; aspect-ratio: 2 / 3; object-fit: cover; border-radius: 4px; background: #fff; }
		.bbb-fb-admin-aesthetic-import__pin span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; }
		@media (max-width: 782px) {
			.bbb-fb-admin-aesthetic-import__row { grid-template-columns: 1fr; }
		}
	</style>
	<div class="bbb-fb-admin-fields">
	<div class="bbb-fb-admin-linked">
		<label for="bbb_fictional_boyfriend_book_ids"><strong><?php esc_html_e('Linked Book', 'bybookishbabe-shopify-port'); ?></strong></label>
		<select id="bbb_fictional_boyfriend_book_ids" name="bbb_fictional_boyfriend_book_ids[]" multiple size="10">
			<?php foreach ($book_options as $book) : ?>
				<?php
				$book_id = (int) $book->ID;
				$author = bbb_fictional_boyfriend_book_author($book_id);
				$label = get_the_title($book);
				if ('' !== $author) {
					$label .= ' by ' . $author;
				}
				$label .= ' (#' . $book_id . ')';
				?>
				<option value="<?php echo esc_attr((string) $book_id); ?>" <?php selected(in_array($book_id, $current_book_ids, true)); ?>>
					<?php echo esc_html($label); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<span class="description"><?php esc_html_e('Select the book this fictional boyfriend belongs to. Hold Command on Mac or Control on Windows to select more than one; the first selected book is used as the primary source.', 'bybookishbabe-shopify-port'); ?></span>
		<?php if ($current_book_ids) : ?>
			<p class="bbb-fb-admin-linked__current">
				<?php esc_html_e('Current:', 'bybookishbabe-shopify-port'); ?>
				<?php foreach ($current_book_ids as $index => $book_id) : ?>
					<?php $edit_link = get_edit_post_link($book_id); ?>
					<?php if ($index > 0) : ?>
						<?php echo esc_html(', '); ?>
					<?php endif; ?>
					<?php if ($edit_link) : ?>
						<a href="<?php echo esc_url($edit_link); ?>"><?php echo esc_html(get_the_title($book_id) . ' (#' . $book_id . ')'); ?></a>
					<?php else : ?>
						<?php echo esc_html(get_the_title($book_id) . ' (#' . $book_id . ')'); ?>
					<?php endif; ?>
				<?php endforeach; ?>
			</p>
		<?php endif; ?>
	</div>
	<p>
		<label for="bbb_fictional_boyfriend_one_line_profile"><strong><?php esc_html_e('One-line Profile', 'bybookishbabe-shopify-port'); ?></strong></label>
		<input id="bbb_fictional_boyfriend_one_line_profile" name="bbb_fictional_boyfriend_one_line_profile" type="text" value="<?php echo esc_attr($one_line_profile); ?>" placeholder="the villain. the commander. the man who will ruin every real relationship you ever have.">
		<span class="description"><?php esc_html_e('This is the italic profile line that appears in the hero.', 'bybookishbabe-shopify-port'); ?></span>
	</p>
	<p>
		<label for="bbb_fictional_boyfriend_seo_description"><strong><?php esc_html_e('SEO Description', 'bybookishbabe-shopify-port'); ?></strong></label>
		<textarea id="bbb_fictional_boyfriend_seo_description" name="bbb_fictional_boyfriend_seo_description" maxlength="170" placeholder="aaron warner fictional boyfriend profile with his tropes, spice rating, personality breakdown, quotes, and what to read next."><?php echo esc_textarea($seo_description); ?></textarea>
		<span class="description"><?php esc_html_e('Used for Rank Math, Open Graph, and Twitter descriptions. Keep it around 150-160 characters.', 'bybookishbabe-shopify-port'); ?></span>
	</p>
	<p>
		<label for="bbb_fictional_boyfriend_pinterest_title"><strong><?php esc_html_e('Pinterest Title', 'bybookishbabe-shopify-port'); ?></strong></label>
		<input id="bbb_fictional_boyfriend_pinterest_title" name="bbb_fictional_boyfriend_pinterest_title" type="text" value="<?php echo esc_attr($pinterest_title); ?>" placeholder="Aaron Warner fictional boyfriend profile">
		<span class="description"><?php esc_html_e('Used only for the Pinterest save button. Keep names and book titles capitalized; keep the rest in bybookishbabe voice.', 'bybookishbabe-shopify-port'); ?></span>
	</p>
	<p>
		<label for="bbb_fictional_boyfriend_pinterest_description"><strong><?php esc_html_e('Pinterest Description', 'bybookishbabe-shopify-port'); ?></strong></label>
		<textarea id="bbb_fictional_boyfriend_pinterest_description" name="bbb_fictional_boyfriend_pinterest_description" maxlength="260" placeholder="meet Aaron Warner from Shatter Me: his book boyfriend tropes, spice rating, personality breakdown, quotes, and what to read next if he ruined you."><?php echo esc_textarea($pinterest_description); ?></textarea>
		<span class="description"><?php esc_html_e('Used only for Pinterest. Lowercase brand framing is fine, but proper names and book titles stay capitalized.', 'bybookishbabe-shopify-port'); ?></span>
	</p>
	<p>
		<label for="bbb_fictional_boyfriend_traits"><strong><?php esc_html_e('Personality Traits', 'bybookishbabe-shopify-port'); ?></strong></label>
		<select id="bbb_fictional_boyfriend_traits" name="bbb_fictional_boyfriend_traits[]" multiple class="widefat" size="14">
		<?php foreach (bbb_fictional_boyfriend_trait_options() as $trait) : ?>
			<option value="<?php echo esc_attr($trait); ?>" <?php selected(in_array($trait, $current_traits, true)); ?>>
				<?php echo esc_html(bbb_fictional_boyfriend_trait_label($trait)); ?>
			</option>
		<?php endforeach; ?>
		</select>
		<span class="description"><?php esc_html_e('Hold Command on Mac or Control on Windows to select multiple traits.', 'bybookishbabe-shopify-port'); ?></span>
	</p>
	<div>
		<p><strong><?php esc_html_e('Personality Scores', 'bybookishbabe-shopify-port'); ?></strong></p>
		<?php foreach (bbb_fictional_boyfriend_score_options() as $score_key => $score_label) : ?>
			<p>
				<label for="bbb_fictional_boyfriend_score_<?php echo esc_attr($score_key); ?>"><?php echo esc_html($score_label); ?></label>
				<input
					id="bbb_fictional_boyfriend_score_<?php echo esc_attr($score_key); ?>"
					name="bbb_fictional_boyfriend_scores[<?php echo esc_attr($score_key); ?>]"
					type="number"
					min="0"
					max="10"
					step="1"
					value="<?php echo esc_attr((string) ($current_scores[$score_key] ?? 0)); ?>"
				>
			</p>
		<?php endforeach; ?>
	</div>
	<p>
		<label for="bbb_fictional_boyfriend_love_language"><?php esc_html_e('Love Language', 'bybookishbabe-shopify-port'); ?></label>
		<select id="bbb_fictional_boyfriend_love_language" name="bbb_fictional_boyfriend_love_language">
			<option value=""><?php esc_html_e('Select love language', 'bybookishbabe-shopify-port'); ?></option>
			<?php foreach (bbb_fictional_boyfriend_love_language_options() as $option) : ?>
				<option value="<?php echo esc_attr($option); ?>" <?php selected($love_language, $option); ?>>
					<?php echo esc_html($option); ?>
				</option>
			<?php endforeach; ?>
			<?php if ('' !== trim($love_language) && !in_array($love_language, bbb_fictional_boyfriend_love_language_options(), true)) : ?>
				<option value="<?php echo esc_attr($love_language); ?>" selected>
					<?php echo esc_html($love_language); ?>
				</option>
			<?php endif; ?>
		</select>
	</p>
	<p>
		<label for="bbb_fictional_boyfriend_would_text"><?php esc_html_e('Would he text back?', 'bybookishbabe-shopify-port'); ?></label>
		<input id="bbb_fictional_boyfriend_would_text" name="bbb_fictional_boyfriend_would_text" type="text" value="<?php echo esc_attr($would_text_back); ?>" placeholder="absolutely not">
	</p>
	<p>
		<label for="bbb_fictional_boyfriend_read_next_note"><?php esc_html_e('Read-next why line', 'bybookishbabe-shopify-port'); ?></label>
		<input id="bbb_fictional_boyfriend_read_next_note" name="bbb_fictional_boyfriend_read_next_note" type="text" value="<?php echo esc_attr($read_next_note); ?>" placeholder="same emotional damage, different man">
	</p>
		<div class="bbb-fb-admin-aesthetic" data-bbb-fb-aesthetic-field>
			<p>
				<strong><?php esc_html_e('Aesthetic images', 'bybookishbabe-shopify-port'); ?></strong><br>
				<span class="description"><?php esc_html_e('Use the Dropbox folder for moodboard/collage images. The page reads the first three image files by filename order.', 'bybookishbabe-shopify-port'); ?></span>
			</p>
			<div class="bbb-fb-admin-aesthetic__dropbox">
				<strong><?php esc_html_e('Dropbox folder', 'bybookishbabe-shopify-port'); ?></strong>
				<code><?php echo esc_html($dropbox_aesthetic_folder); ?></code>
				<a class="button" href="<?php echo esc_url($dropbox_aesthetic_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open Dropbox folder', 'bybookishbabe-shopify-port'); ?></a>
			</div>
			<?php foreach ($aesthetic_images as $slot => $image) : ?>
				<?php
				$image_url = (string) ($image['image'] ?? '');
				$link_url = (string) ($image['link'] ?? '');
				?>
				<div class="bbb-fb-admin-aesthetic__slot" data-bbb-fb-aesthetic-slot>
					<img class="bbb-fb-admin-aesthetic__preview" src="<?php echo esc_url($image_url); ?>" alt="" data-bbb-fb-aesthetic-preview <?php echo $image_url ? '' : 'hidden'; ?>>
					<div class="bbb-fb-admin-aesthetic__controls">
						<label>
							<?php echo esc_html(sprintf(__('Image %d URL', 'bybookishbabe-shopify-port'), $slot + 1)); ?>
							<input name="bbb_fictional_boyfriend_aesthetic_images[<?php echo esc_attr((string) $slot); ?>][image]" type="text" value="<?php echo esc_attr($image_url); ?>" placeholder="https://.../image.jpg" data-bbb-fb-aesthetic-image>
						</label>
						<label>
							<?php esc_html_e('Click/source URL optional', 'bybookishbabe-shopify-port'); ?>
							<input name="bbb_fictional_boyfriend_aesthetic_images[<?php echo esc_attr((string) $slot); ?>][link]" type="text" value="<?php echo esc_attr($link_url); ?>" placeholder="https://www.pinterest.com/.../aaron-warner/" data-bbb-fb-aesthetic-link>
						</label>
						<div class="bbb-fb-admin-aesthetic__buttons">
							<button type="button" class="button" data-bbb-fb-aesthetic-clear><?php esc_html_e('Clear legacy URL', 'bybookishbabe-shopify-port'); ?></button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
			<details class="bbb-fb-admin-aesthetic-import" data-bbb-fb-pinterest-import>
				<summary><?php esc_html_e('Import from Pinterest board', 'bybookishbabe-shopify-port'); ?></summary>
				<div class="bbb-fb-admin-aesthetic-import__fields">
					<div class="bbb-fb-admin-aesthetic-import__row">
						<label>
							<?php esc_html_e('Scheduler secret', 'bybookishbabe-shopify-port'); ?>
							<input type="password" autocomplete="off" data-bbb-fb-pinterest-secret>
						</label>
						<label>
							<?php esc_html_e('Pinterest board URL', 'bybookishbabe-shopify-port'); ?>
							<input type="url" placeholder="https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/" data-bbb-fb-pinterest-board-url>
						</label>
					</div>
					<div class="bbb-fb-admin-aesthetic-import__row">
						<label>
							<?php esc_html_e('Link selected images to', 'bybookishbabe-shopify-port'); ?>
							<select data-bbb-fb-pinterest-section disabled>
								<option value=""><?php esc_html_e('Fetch a board first', 'bybookishbabe-shopify-port'); ?></option>
							</select>
						</label>
						<label>
							<?php esc_html_e('Filter pins', 'bybookishbabe-shopify-port'); ?>
							<input type="search" placeholder="aaron warner" data-bbb-fb-pinterest-search disabled>
						</label>
					</div>
					<div class="bbb-fb-admin-aesthetic-import__actions">
						<button type="button" class="button" data-bbb-fb-pinterest-fetch><?php esc_html_e('Fetch Pinterest pins', 'bybookishbabe-shopify-port'); ?></button>
						<button type="button" class="button button-primary" data-bbb-fb-pinterest-use disabled><?php esc_html_e('Use selected pins', 'bybookishbabe-shopify-port'); ?></button>
						<span class="bbb-fb-admin-aesthetic-import__status" data-bbb-fb-pinterest-status></span>
					</div>
					<div class="bbb-fb-admin-aesthetic-import__grid" data-bbb-fb-pinterest-grid hidden></div>
				</div>
			</details>
		</div>
		</div>
		<script>
			(function($) {
				var frame;
				var pinterestState = {
					board: null,
					sections: [],
					pins: [],
					selectedIds: []
				};
				var schedulerPreviewEndpoints = [
					'http://localhost:8787/admin/pinterest-board-preview',
					'http://127.0.0.1:8787/admin/pinterest-board-preview'
				];
				var siteBaseUrl = '<?php echo esc_js(trailingslashit(home_url('/'))); ?>';

				function setPinterestStatus(message) {
					$('[data-bbb-fb-pinterest-status]').text(message || '');
				}

				function selectedPinterestPins() {
					return pinterestState.selectedIds
						.map(function(id) {
							return pinterestState.pins.find(function(pin) {
								return String(pin.id) === String(id);
							});
						})
						.filter(Boolean);
				}

				function renderPinterestSections() {
					var $section = $('[data-bbb-fb-pinterest-section]');
					$section.empty();

					if (!pinterestState.board) {
						$section.append($('<option>', { value: '', text: '<?php echo esc_js(__('Fetch a board first', 'bybookishbabe-shopify-port')); ?>' }));
						$section.prop('disabled', true);
						return;
					}

					$section.append($('<option>', {
						value: pinterestState.board.url || '',
						text: '<?php echo esc_js(__('Board link', 'bybookishbabe-shopify-port')); ?>'
					}));

					pinterestState.sections.forEach(function(section) {
						$section.append($('<option>', {
							value: section.url || pinterestState.board.url || '',
							text: section.name || section.url || ''
						}));
					});

					$section.prop('disabled', false);
				}

				function renderPinterestPins() {
					var $grid = $('[data-bbb-fb-pinterest-grid]');
					var filter = String($('[data-bbb-fb-pinterest-search]').val() || '').trim().toLowerCase();
					var pins = pinterestState.pins.filter(function(pin) {
						if (!filter) {
							return true;
						}

						return [pin.title, pin.description, pin.id]
							.join(' ')
							.toLowerCase()
							.indexOf(filter) !== -1;
					});

					$grid.empty().prop('hidden', !pins.length);
					pins.forEach(function(pin) {
						var isSelected = pinterestState.selectedIds.indexOf(String(pin.id)) !== -1;
						var label = pin.title || pin.description || pin.id;
						var $button = $('<button>', {
							type: 'button',
							class: 'bbb-fb-admin-aesthetic-import__pin',
							'aria-pressed': isSelected ? 'true' : 'false',
							'data-bbb-fb-pinterest-pin': pin.id
						});
						$button.append($('<img>', {
							src: pin.imageUrl,
							alt: label
						}));
						$button.append($('<span>').text(label || '<?php echo esc_js(__('Pinterest pin', 'bybookishbabe-shopify-port')); ?>'));
						$grid.append($button);
					});

					$('[data-bbb-fb-pinterest-use]').prop('disabled', !pinterestState.selectedIds.length);
					if (pinterestState.pins.length && !pins.length) {
						setPinterestStatus('<?php echo esc_js(__('No pins match that filter.', 'bybookishbabe-shopify-port')); ?>');
					}
				}

				function updateSlot($slot, imageUrl, linkUrl) {
					$slot.find('[data-bbb-fb-aesthetic-image]').val(imageUrl || '');
					$slot.find('[data-bbb-fb-aesthetic-link]').val(linkUrl || '');
					$slot.find('[data-bbb-fb-aesthetic-preview]').attr('src', imageUrl || '').prop('hidden', !imageUrl);
				}

				function isSiteImageUrl(imageUrl) {
					return String(imageUrl || '').indexOf(siteBaseUrl) === 0;
				}

				function currentUploadedLeadImage() {
					var uploaded = null;
					$('[data-bbb-fb-aesthetic-slot]').each(function() {
						if (uploaded) {
							return;
						}

						var $slot = $(this);
						var imageUrl = String($slot.find('[data-bbb-fb-aesthetic-image]').val() || '').trim();
						var linkUrl = String($slot.find('[data-bbb-fb-aesthetic-link]').val() || '').trim();
						if (imageUrl && !linkUrl && isSiteImageUrl(imageUrl)) {
							uploaded = {
								imageUrl: imageUrl,
								linkUrl: ''
							};
						}
					});
					return uploaded;
				}

				function fetchPinterestPreview(secret, boardUrl, endpointIndex) {
					var index = endpointIndex || 0;
					var endpoint = schedulerPreviewEndpoints[index];
					if (!endpoint) {
						return Promise.reject(new Error('<?php echo esc_js(__('Could not reach the local scheduler. Make sure http://localhost:8787 is running, then refresh this editor and try again.', 'bybookishbabe-shopify-port')); ?>'));
					}

					return fetch(endpoint + '?secret=' + encodeURIComponent(secret) + '&boardUrl=' + encodeURIComponent(boardUrl), {
						method: 'GET',
						mode: 'cors'
					}).then(function(response) {
						return response.json().then(function(data) {
							if (!response.ok) {
								throw new Error(data && data.error ? data.error : response.statusText);
							}
							return data;
						});
					}).catch(function(error) {
						if (error && error.message && error.message !== 'Failed to fetch') {
							throw error;
						}
						return fetchPinterestPreview(secret, boardUrl, index + 1);
					});
				}

				$(document).on('click', '[data-bbb-fb-aesthetic-pick]', function(e) {
					e.preventDefault();

					var $slot = $(this).closest('[data-bbb-fb-aesthetic-slot]');

					frame = wp.media({
						title: '<?php echo esc_js(__('Choose aesthetic image', 'bybookishbabe-shopify-port')); ?>',
						button: { text: '<?php echo esc_js(__('Use this image', 'bybookishbabe-shopify-port')); ?>' },
						library: { type: 'image' },
						multiple: false
					});

					frame.on('select', function() {
						var attachment = frame.state().get('selection').first().toJSON();
						var preview = (attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url) || '';

						$slot.find('[data-bbb-fb-aesthetic-image]').val(attachment.url || '');
						$slot.find('[data-bbb-fb-aesthetic-link]').val('');
						$slot.find('[data-bbb-fb-aesthetic-preview]').attr('src', preview).prop('hidden', !preview);
					});

					frame.open();
				});

				$(document).on('click', '[data-bbb-fb-aesthetic-clear]', function(e) {
					e.preventDefault();

					var $slot = $(this).closest('[data-bbb-fb-aesthetic-slot]');
					$slot.find('[data-bbb-fb-aesthetic-image]').val('');
					$slot.find('[data-bbb-fb-aesthetic-link]').val('');
					$slot.find('[data-bbb-fb-aesthetic-preview]').attr('src', '').prop('hidden', true);
				});

				$(document).on('input', '[data-bbb-fb-aesthetic-image]', function() {
					var $slot = $(this).closest('[data-bbb-fb-aesthetic-slot]');
					var value = $(this).val();
					$slot.find('[data-bbb-fb-aesthetic-preview]').attr('src', value).prop('hidden', !value);
				});

				$(document).on('click', '[data-bbb-fb-pinterest-fetch]', function(e) {
					e.preventDefault();

					var secret = String($('[data-bbb-fb-pinterest-secret]').val() || '').trim();
					var boardUrl = String($('[data-bbb-fb-pinterest-board-url]').val() || '').trim();
					if (!secret || !boardUrl) {
						setPinterestStatus('<?php echo esc_js(__('Add the scheduler secret and Pinterest board URL first.', 'bybookishbabe-shopify-port')); ?>');
						return;
					}

					var $button = $(this);
					$button.prop('disabled', true);
					setPinterestStatus('<?php echo esc_js(__('Fetching Pinterest board...', 'bybookishbabe-shopify-port')); ?>');

					fetchPinterestPreview(secret, boardUrl, 0)
						.then(function(data) {
							pinterestState.board = data.board || null;
							pinterestState.sections = Array.isArray(data.sections) ? data.sections : [];
							pinterestState.pins = Array.isArray(data.pins) ? data.pins : [];
							pinterestState.selectedIds = [];
							$('[data-bbb-fb-pinterest-search]').prop('disabled', !pinterestState.pins.length);
							renderPinterestSections();
							renderPinterestPins();
							setPinterestStatus(pinterestState.pins.length
								? '<?php echo esc_js(__('Choose up to 3 pins, then use selected pins.', 'bybookishbabe-shopify-port')); ?>'
								: '<?php echo esc_js(__('Board loaded, but Pinterest returned no visible pins.', 'bybookishbabe-shopify-port')); ?>'
							);
						})
						.catch(function(error) {
							var message = error.message || '';
							if (!message || message === 'Failed to fetch') {
								message = '<?php echo esc_js(__('Could not reach the local scheduler. Make sure http://localhost:8787 is running, then refresh this editor and try again.', 'bybookishbabe-shopify-port')); ?>';
							}
							setPinterestStatus(message);
						})
						.finally(function() {
							$button.prop('disabled', false);
						});
				});

				$(document).on('input', '[data-bbb-fb-pinterest-search]', renderPinterestPins);

				$(document).on('click', '[data-bbb-fb-pinterest-pin]', function(e) {
					e.preventDefault();

					var id = String($(this).data('bbbFbPinterestPin') || $(this).attr('data-bbb-fb-pinterest-pin') || '');
					var existing = pinterestState.selectedIds.indexOf(id);
					if (existing !== -1) {
						pinterestState.selectedIds.splice(existing, 1);
					} else if (pinterestState.selectedIds.length < 3) {
						pinterestState.selectedIds.push(id);
					} else {
						setPinterestStatus('<?php echo esc_js(__('Only 3 carousel images can be selected.', 'bybookishbabe-shopify-port')); ?>');
						return;
					}

					renderPinterestPins();
					if (pinterestState.selectedIds.length) {
						setPinterestStatus(pinterestState.selectedIds.length + ' / 3 <?php echo esc_js(__('selected', 'bybookishbabe-shopify-port')); ?>');
					}
				});

				$(document).on('click', '[data-bbb-fb-pinterest-use]', function(e) {
					e.preventDefault();

					var pins = selectedPinterestPins().slice(0, 3);
					var linkUrl = String($('[data-bbb-fb-pinterest-section]').val() || pinterestState.board?.url || '').trim();
					var $slots = $('[data-bbb-fb-aesthetic-slot]');
					var leadImage = currentUploadedLeadImage();
					var nextImages = leadImage ? [leadImage] : [];

					pins.slice(0, leadImage ? 2 : 3).forEach(function(pin) {
						nextImages.push({
							imageUrl: pin.imageUrl,
							linkUrl: linkUrl
						});
					});

					$slots.each(function(index) {
						var image = nextImages[index] || null;
						updateSlot($(this), image ? image.imageUrl : '', image ? image.linkUrl : '');
					});

					setPinterestStatus((leadImage ? '<?php echo esc_js(__('Uploaded image kept first. ', 'bybookishbabe-shopify-port')); ?>' : '') + Math.min(pins.length, leadImage ? 2 : 3) + ' <?php echo esc_js(__('pin image(s) added. Save/update the profile to keep them.', 'bybookishbabe-shopify-port')); ?>');
				});
			})(jQuery);
		</script>
		<?php
	}

add_action(
	'save_post_bbb_boyfriend',
	static function (int $post_id): void {
		if (!isset($_POST['bbb_fictional_boyfriend_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bbb_fictional_boyfriend_nonce'])), 'bbb_save_fictional_boyfriend_details')) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		$posted_traits = isset($_POST['bbb_fictional_boyfriend_traits']) && is_array($_POST['bbb_fictional_boyfriend_traits'])
			? array_map('sanitize_title', wp_unslash($_POST['bbb_fictional_boyfriend_traits']))
			: array();
		$allowed_traits = bbb_fictional_boyfriend_trait_options();
		$traits = array_values(array_intersect($allowed_traits, $posted_traits));

		$posted_book_ids = isset($_POST['bbb_fictional_boyfriend_book_ids']) && is_array($_POST['bbb_fictional_boyfriend_book_ids'])
			? array_map('absint', wp_unslash($_POST['bbb_fictional_boyfriend_book_ids']))
			: array();
		$book_ids = array();
		foreach ($posted_book_ids as $book_id) {
			$book = get_post($book_id);
			if ($book instanceof WP_Post && in_array($book->post_type, array('bbb_book', 'sss_book'), true) && in_array($book->post_status, array('publish', 'draft', 'pending', 'private', 'future'), true)) {
				$book_ids[] = $book_id;
			}
		}
		$book_ids = array_values(array_unique(array_filter($book_ids)));
		if ($book_ids) {
			update_post_meta($post_id, '_bbb_fb_book_ids', implode(',', $book_ids));
		} else {
			delete_post_meta($post_id, '_bbb_fb_book_ids');
		}

		update_post_meta($post_id, '_bbb_fb_traits', implode(', ', $traits));
		wp_set_object_terms($post_id, array_map('bbb_fictional_boyfriend_trait_label', $traits), 'bbb_boyfriend_trait', false);

		foreach (
			array(
				'_bbb_fb_one_line_profile' => 'bbb_fictional_boyfriend_one_line_profile',
				'_bbb_fb_seo_description' => 'bbb_fictional_boyfriend_seo_description',
				'_bbb_fb_pinterest_title' => 'bbb_fictional_boyfriend_pinterest_title',
				'_bbb_fb_pinterest_description' => 'bbb_fictional_boyfriend_pinterest_description',
					'_bbb_fb_love_language' => 'bbb_fictional_boyfriend_love_language',
					'_bbb_fb_would_text_back' => 'bbb_fictional_boyfriend_would_text',
					'_bbb_fb_read_next_note' => 'bbb_fictional_boyfriend_read_next_note',
				) as $meta_key => $post_key
			) {
			$value = isset($_POST[$post_key]) ? sanitize_textarea_field((string) wp_unslash($_POST[$post_key])) : '';
			if ('' === trim($value)) {
				delete_post_meta($post_id, $meta_key);
			} else {
					update_post_meta($post_id, $meta_key, $value);
				}
			}

			$aesthetic_lines = array();
			$aesthetic_uploaded_lines = array();
			$aesthetic_external_lines = array();
			$site_base_url = trailingslashit(home_url('/'));
			$posted_aesthetic_images = isset($_POST['bbb_fictional_boyfriend_aesthetic_images']) && is_array($_POST['bbb_fictional_boyfriend_aesthetic_images'])
				? wp_unslash($_POST['bbb_fictional_boyfriend_aesthetic_images'])
				: array();
			foreach (array_slice($posted_aesthetic_images, 0, 3) as $posted_aesthetic_image) {
				if (!is_array($posted_aesthetic_image)) {
					continue;
				}

				$image_url = esc_url_raw((string) ($posted_aesthetic_image['image'] ?? ''));
				$link_url = esc_url_raw((string) ($posted_aesthetic_image['link'] ?? ''));
				if ('' === $image_url) {
					continue;
				}

				$aesthetic_line = '' !== $link_url ? $image_url . ' | ' . $link_url : $image_url;
				if ('' === $link_url && 0 === strpos($image_url, $site_base_url)) {
					$aesthetic_uploaded_lines[] = $aesthetic_line;
				} else {
					$aesthetic_external_lines[] = $aesthetic_line;
				}
			}
			$aesthetic_lines = array_slice(array_merge($aesthetic_uploaded_lines, $aesthetic_external_lines), 0, 3);

			if ($aesthetic_lines) {
				update_post_meta($post_id, '_bbb_fb_pinterest_urls', implode("\n", $aesthetic_lines));
			} else {
				delete_post_meta($post_id, '_bbb_fb_pinterest_urls');
			}

			$score_values = array();
		$posted_scores = isset($_POST['bbb_fictional_boyfriend_scores']) && is_array($_POST['bbb_fictional_boyfriend_scores'])
			? wp_unslash($_POST['bbb_fictional_boyfriend_scores'])
			: array();
		foreach (array_keys(bbb_fictional_boyfriend_score_options()) as $score_key) {
			$score_values[$score_key] = max(0, min(10, absint($posted_scores[$score_key] ?? 0)));
		}

		update_post_meta($post_id, '_bbb_fb_trait_scores', wp_json_encode($score_values));

		bbb_fictional_boyfriend_sync_from_books($post_id);
	}
);
add_action('save_post_bbb_boyfriend', 'bbb_save_hidden_from_public_browsing_meta');

function bbb_fictional_boyfriend_book_ids(int $post_id): array {
	$raw = (string) get_post_meta($post_id, '_bbb_fb_book_ids', true);
	if ('' === trim($raw)) {
		return array();
	}

	return array_values(
		array_unique(
			array_filter(
				array_map('absint', preg_split('/[\s,]+/', $raw) ?: array())
			)
		)
	);
}

function bbb_fictional_boyfriend_for_book(int $book_id, string $boyfriend_name = ''): ?WP_Post {
	if ($book_id <= 0 || !post_type_exists('bbb_boyfriend')) {
		return null;
	}

	$cache_key = $book_id . '|' . bbb_fictional_boyfriend_match_key($boyfriend_name);
	static $cache = array();

	if (array_key_exists($cache_key, $cache)) {
		return $cache[$cache_key];
	}

	$profiles = get_posts(
		array(
			'post_type'              => 'bbb_boyfriend',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'menu_order title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields'                 => 'all',
		)
	);

	foreach ($profiles as $profile) {
		if (!$profile instanceof WP_Post) {
			continue;
		}

		if (in_array($book_id, bbb_fictional_boyfriend_book_ids((int) $profile->ID), true)) {
			$cache[$cache_key] = $profile;
			return $profile;
		}
	}

	if ('' !== trim($boyfriend_name)) {
		foreach ($profiles as $profile) {
			if ($profile instanceof WP_Post && bbb_fictional_boyfriend_name_matches($boyfriend_name, array(get_the_title($profile)))) {
				$cache[$cache_key] = $profile;
				return $profile;
			}
		}
	}

	$cache[$cache_key] = null;
	return null;
}

function bbb_fictional_boyfriend_book_author(int $book_id): string {
	foreach (array('_bbb_author', 'author', 'sss_author', '_bbb_book_author') as $key) {
		$value = trim((string) get_post_meta($book_id, $key, true));
		if ('' !== $value) {
			return $value;
		}
	}

	return '';
}

function bbb_fictional_boyfriend_term_names(int $book_id, string $taxonomy): array {
	if (!taxonomy_exists($taxonomy)) {
		return array();
	}

	$terms = wp_get_post_terms($book_id, $taxonomy, array('fields' => 'names'));
	if (is_wp_error($terms)) {
		return array();
	}

	return array_values(array_filter(array_map('strval', $terms)));
}

function bbb_fictional_boyfriend_sync_from_books(int $post_id): void {
	$book_ids = bbb_fictional_boyfriend_book_ids($post_id);
	if (!$book_ids) {
		foreach (array('_bbb_fb_source', '_bbb_fb_author', '_bbb_fb_series', '_bbb_fb_shelf', '_bbb_fb_tropes') as $meta_key) {
			delete_post_meta($post_id, $meta_key);
		}
		return;
	}

	$sources = array();
	$authors = array();
	$series = array();
	$shelves = array();
	$tropes = array();

	foreach ($book_ids as $book_id) {
		$book = get_post($book_id);
		if (!$book instanceof WP_Post || !in_array($book->post_type, array('bbb_book', 'sss_book'), true)) {
			continue;
		}

		$sources[] = get_the_title($book);

		$author = bbb_fictional_boyfriend_book_author($book_id);
		if ('' !== $author) {
			$authors[] = $author;
		}

		$series = array_merge($series, bbb_fictional_boyfriend_term_names($book_id, 'bbb_series'));
		if (!$series) {
			$series_handle = trim((string) get_post_meta($book_id, '_bbb_series_handle', true));
			if ('' !== $series_handle) {
				$series[] = ucwords(str_replace('-', ' ', $series_handle));
			}
		}
		$shelves = array_merge($shelves, bbb_fictional_boyfriend_term_names($book_id, 'bbb_shelf'));
		$tropes = array_merge($tropes, bbb_fictional_boyfriend_term_names($book_id, 'bbb_trope'));
		$tropes = array_merge($tropes, bbb_fictional_boyfriend_term_names($book_id, 'sss_trope'));
	}

	$sync_values = array(
		'_bbb_fb_source' => implode(', ', array_unique($sources)),
		'_bbb_fb_author' => implode(', ', array_unique($authors)),
		'_bbb_fb_series' => implode(', ', array_unique($series)),
		'_bbb_fb_shelf' => implode(', ', array_unique($shelves)),
		'_bbb_fb_tropes' => implode(', ', array_unique($tropes)),
	);

	foreach ($sync_values as $key => $value) {
		if ('' !== $value) {
			update_post_meta($post_id, $key, $value);
		} else {
			delete_post_meta($post_id, $key);
		}
	}

	if ($tropes) {
		wp_set_object_terms($post_id, array_unique($tropes), 'bbb_boyfriend_trope', false);
	}
}

function bbb_fictional_boyfriend_primary_book_id(int $post_id): int {
	$book_ids = bbb_fictional_boyfriend_book_ids($post_id);
	return $book_ids ? (int) $book_ids[0] : 0;
}

function bbb_fictional_boyfriend_series_handle(int $post_id): string {
	$book_id = bbb_fictional_boyfriend_primary_book_id($post_id);
	if ($book_id) {
		$handle = sanitize_title((string) get_post_meta($book_id, '_bbb_series_handle', true));
		if ('' !== $handle) {
			return $handle;
		}
	}

	$series = trim((string) get_post_meta($post_id, '_bbb_fb_series', true));
	return '' !== $series ? sanitize_title($series) : '';
}

function bbb_fictional_boyfriend_series_post(int $post_id): ?WP_Post {
	$handle = bbb_fictional_boyfriend_series_handle($post_id);
	if ('' === $handle || !post_type_exists('sss_series')) {
		return null;
	}

	$series = get_page_by_path($handle, OBJECT, 'sss_series');
	return $series instanceof WP_Post ? $series : null;
}

function bbb_fictional_boyfriend_series_siblings(int $post_id, int $limit = 3): array {
	$book_id = bbb_fictional_boyfriend_primary_book_id($post_id);
	$book = $book_id > 0 ? get_post($book_id) : null;
	if (!$book instanceof WP_Post || !post_type_exists('bbb_boyfriend')) {
		return array();
	}

	$series_handle = sanitize_title((string) get_post_meta($book_id, '_bbb_series_handle', true));
	if ('' === $series_handle) {
		return array();
	}

	$post_types = array_values(array_filter(array('bbb_book', 'sss_book'), 'post_type_exists'));
	if (!$post_types) {
		return array();
	}

	$series_books = get_posts(
		array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'meta_value_num title',
			'order'                  => 'ASC',
			'meta_key'               => '_bbb_series_number',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'   => '_bbb_series_handle',
					'value' => $series_handle,
				),
			),
		)
	);

	$siblings = array();
	$seen_ids = array($post_id);
	foreach ($series_books as $series_book) {
		if (!$series_book instanceof WP_Post || (int) $series_book->ID === $book_id) {
			continue;
		}

		$boyfriend_name = trim((string) get_post_meta($series_book->ID, '_bbb_boyfriend_name', true));
		$boyfriend = bbb_fictional_boyfriend_for_book((int) $series_book->ID, $boyfriend_name);
		if (!$boyfriend instanceof WP_Post || in_array((int) $boyfriend->ID, $seen_ids, true)) {
			continue;
		}
		if (function_exists('bbb_content_is_publicly_discoverable') && !bbb_content_is_publicly_discoverable((int) $boyfriend->ID)) {
			continue;
		}

		$seen_ids[] = (int) $boyfriend->ID;
		$siblings[] = array(
			'post'          => $boyfriend,
			'book'          => $series_book,
			'name'          => get_the_title($boyfriend),
			'url'           => get_permalink($boyfriend),
			'image'         => (string) get_the_post_thumbnail_url($boyfriend, 'medium'),
			'book_title'    => get_the_title($series_book),
			'book_url'      => get_permalink($series_book),
			'series_number' => trim((string) get_post_meta($series_book->ID, '_bbb_series_number', true)),
			'type'          => trim((string) get_post_meta($series_book->ID, '_bbb_boyfriend_type', true)),
		);

		if ($limit > 0 && count($siblings) >= $limit) {
			break;
		}
	}

	return $siblings;
}

function bbb_fictional_boyfriend_series_books(int $post_id): array {
	$primary_book_id = bbb_fictional_boyfriend_primary_book_id($post_id);
	$series = bbb_fictional_boyfriend_series_post($post_id);
	$book_ids = array();

	if ($series instanceof WP_Post && function_exists('bbb_series_current_book_ids')) {
		$book_ids = array_map('absint', bbb_series_current_book_ids($series));
	}

	if (!$book_ids) {
		$handle = bbb_fictional_boyfriend_series_handle($post_id);
		$post_types = function_exists('bbb_series_book_post_types') ? bbb_series_book_post_types() : array('bbb_book', 'sss_book');
		$post_types = array_values(array_filter($post_types, 'post_type_exists'));
		if ('' !== $handle && $post_types) {
			$book_ids = get_posts(
				array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'   => '_bbb_series_handle',
							'value' => $handle,
						),
					),
					'orderby'        => 'meta_value_num',
					'meta_key'       => '_bbb_series_number',
					'order'          => 'ASC',
					'no_found_rows'  => true,
				)
			);
		}
	}

	if ($primary_book_id) {
		array_unshift($book_ids, $primary_book_id);
	}

	return array_values(array_unique(array_filter(array_map('absint', $book_ids))));
}

function bbb_fictional_boyfriend_book_meta(int $post_id, string $key, $default = '') {
	$book_id = bbb_fictional_boyfriend_primary_book_id($post_id);
	if (!$book_id) {
		return $default;
	}

	$value = get_post_meta($book_id, $key, true);
	return '' !== (string) $value ? $value : $default;
}

function bbb_fictional_boyfriend_spice(int $post_id): int {
	return max(0, min(5, (int) bbb_fictional_boyfriend_book_meta($post_id, '_bbb_spice', 0)));
}

function bbb_fictional_boyfriend_trait_scores(int $post_id): array {
	$defaults = array(
		'possessive' => 8,
		'protective' => 9,
		'emotionally-available' => 3,
		'will-apologize' => 2,
		'actually-good-for-you' => 1,
	);
	$raw = (string) get_post_meta($post_id, '_bbb_fb_trait_scores', true);
	if ('' === trim($raw)) {
		return $defaults;
	}

	$decoded = json_decode($raw, true);
	if (!is_array($decoded)) {
		return $defaults;
	}

	$scores = array();
	foreach (bbb_fictional_boyfriend_score_options() as $score_key => $score_label) {
		$scores[$score_key] = max(0, min(10, (int) ($decoded[$score_key] ?? $defaults[$score_key] ?? 0)));
	}

	return $scores;
}

function bbb_fictional_boyfriend_damage(int $post_id): int {
	return max(0, min(10, (int) bbb_fictional_boyfriend_book_meta($post_id, '_bbb_damage', 0) * 2));
}

function bbb_fictional_boyfriend_peppers(int $count): string {
	return '' === str_repeat('🌶', $count) ? 'not rated' : str_repeat('🌶', $count);
}

function bbb_fictional_boyfriend_traits(int $post_id): array {
	$traits = array_filter(array_map('sanitize_title', preg_split('/\s*,\s*/', (string) get_post_meta($post_id, '_bbb_fb_traits', true)) ?: array()));
	return array_values(array_unique($traits));
}

function bbb_fictional_boyfriend_tropes(int $post_id): array {
	$tropes = array_filter(array_map('trim', explode(',', (string) get_post_meta($post_id, '_bbb_fb_tropes', true))));
	return array_values(array_unique($tropes));
}

function bbb_fictional_boyfriend_profile_ready(int $post_id): bool {
	return bbb_fictional_boyfriend_primary_book_id($post_id) > 0 && has_post_thumbnail($post_id);
}

function bbb_fictional_boyfriend_filter_key(int $post_id): string {
	$traits = bbb_fictional_boyfriend_traits($post_id);
	$tropes = array_map('sanitize_title', bbb_fictional_boyfriend_tropes($post_id));
	$haystack = array_merge($traits, $tropes);

	if (array_intersect($haystack, array('morally-gray', 'stalker-romance', 'touch-her-and-die', 'dangerous', 'obsessive'))) {
		return 'morally-gray';
	}

	if (array_intersect($haystack, array('golden-retriever', 'warm', 'playful', 'charming'))) {
		return 'golden-retriever';
	}

	if (array_intersect($haystack, array('grumpy', 'broody', 'emotionally-guarded', 'reserved'))) {
		return 'grumpy';
	}

	if (array_intersect($haystack, array('villain-gets-the-girl', 'villain-era', 'ruthless', 'commanding'))) {
		return 'villain-era';
	}

	if (array_intersect($haystack, array('soft-for-her', 'gentle', 'emotionally-intelligent'))) {
		return 'soft-boy';
	}

	if (array_intersect($haystack, array('athletic', 'baseball-romance', 'hockey-romance', 'sports-romance'))) {
		return 'athlete';
	}

	return 'all';
}

function bbb_fictional_boyfriend_descriptor(int $post_id): string {
	$book_descriptor = trim((string) bbb_fictional_boyfriend_book_meta($post_id, '_bbb_boyfriend_type', ''));
	if ('' !== $book_descriptor) {
		return $book_descriptor;
	}

	$traits = bbb_fictional_boyfriend_traits($post_id);
	if ($traits) {
		return implode(' / ', array_slice(array_map('bbb_fictional_boyfriend_trait_label', $traits), 0, 3));
	}

	return 'needs your one-line descriptor';
}

function bbb_fictional_boyfriend_seo_clean(string $value): string {
	$value = wp_strip_all_tags($value);
	$value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$value = preg_replace('/\s+/', ' ', $value) ?? $value;
	return trim($value);
}

function bbb_fictional_boyfriend_seo_lower(string $value): string {
	return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function bbb_fictional_boyfriend_seo_name(int $post_id): string {
	return bbb_fictional_boyfriend_seo_lower(bbb_fictional_boyfriend_seo_clean((string) get_post_field('post_title', $post_id)));
}

function bbb_fictional_boyfriend_seo_source(int $post_id): string {
	$source = bbb_fictional_boyfriend_seo_clean((string) get_post_meta($post_id, '_bbb_fb_source', true));
	if ('' !== $source) {
		return $source;
	}

	$book_id = bbb_fictional_boyfriend_primary_book_id($post_id);
	return $book_id ? bbb_fictional_boyfriend_seo_clean(get_the_title($book_id)) : '';
}

function bbb_fictional_boyfriend_seo_hook(int $post_id): string {
	$hook = bbb_fictional_boyfriend_seo_clean((string) get_post_meta($post_id, '_bbb_fb_one_line_profile', true));
	if ('' === $hook) {
		$hook = bbb_fictional_boyfriend_seo_clean((string) get_the_excerpt($post_id));
	}
	if (preg_match('/^draft fictional boyfriend profile\b/i', $hook)) {
		$hook = '';
	}
	if ('' === $hook) {
		$hook = bbb_fictional_boyfriend_descriptor($post_id);
	}

	return bbb_fictional_boyfriend_seo_clean($hook);
}

function bbb_fictional_boyfriend_seo_title(int $post_id): string {
	$name = bbb_fictional_boyfriend_seo_name($post_id);
	$book = bbb_fictional_boyfriend_seo_source($post_id);

	return '' !== $book
		? $name . ' — ' . $book . ' | fictional boyfriend profile'
		: $name . ' | fictional boyfriend profile';
}

function bbb_fictional_boyfriend_normalize_seo_description(int $post_id, string $description): string {
	$description = bbb_fictional_boyfriend_seo_clean($description);
	$name = bbb_fictional_boyfriend_seo_clean((string) get_post_field('post_title', $post_id));
	$name_lower = bbb_fictional_boyfriend_seo_name($post_id);
	$source = bbb_fictional_boyfriend_seo_source($post_id);

	if ('' !== $description) {
		if ('' !== $name && '' !== $name_lower) {
			$description = preg_replace('/^' . preg_quote($name, '/') . '\b/iu', $name_lower, $description, 1) ?? $description;
		}

		if ('' !== $source) {
			$source_lower = bbb_fictional_boyfriend_seo_lower($source);
			$description = preg_replace('/\bfrom\s+' . preg_quote($source_lower, '/') . '\b/u', 'from ' . $source, $description) ?? $description;
			$description = preg_replace('/\|\s*' . preg_quote($source_lower, '/') . '\b/u', '| ' . $source, $description) ?? $description;
		}

		return bbb_fictional_boyfriend_seo_clean(wp_html_excerpt(lcfirst($description), 155, ''));
	}

	$tropes = bbb_fictional_boyfriend_tropes($post_id);
	$trope_line = '';
	if ($tropes) {
		$trope_line = bbb_fictional_boyfriend_seo_lower(implode(' + ', array_slice(array_map('bbb_fictional_boyfriend_seo_clean', $tropes), 0, 2))) . '.';
	}

	$description = trim(
		implode(
			' ',
				array_filter(
					array(
						$name_lower . '.',
						$trope_line,
						bbb_fictional_boyfriend_seo_hook($post_id),
					)
				)
		)
	);

	return bbb_fictional_boyfriend_seo_clean(wp_html_excerpt($description, 155, ''));
}

function bbb_fictional_boyfriend_seo_description(int $post_id): string {
	return bbb_fictional_boyfriend_normalize_seo_description(
		$post_id,
		(string) get_post_meta($post_id, '_bbb_fb_seo_description', true)
	);
}

function bbb_fictional_boyfriend_pinterest_title(int $post_id): string {
	$title = bbb_fictional_boyfriend_seo_clean((string) get_post_meta($post_id, '_bbb_fb_pinterest_title', true));
	if ('' !== $title) {
		return $title;
	}

	$name = bbb_fictional_boyfriend_seo_clean((string) get_post_field('post_title', $post_id));
	if ('' === $name) {
		$name = 'fictional boyfriend';
	}

	return $name . ' fictional boyfriend profile';
}

function bbb_fictional_boyfriend_pinterest_description(int $post_id): string {
	$description = bbb_fictional_boyfriend_seo_clean((string) get_post_meta($post_id, '_bbb_fb_pinterest_description', true));
	if ('' !== $description) {
		return wp_html_excerpt($description, 260, '');
	}

	$name = bbb_fictional_boyfriend_seo_clean((string) get_post_field('post_title', $post_id));
	$source = bbb_fictional_boyfriend_seo_source($post_id);
	if ('' !== $name && '' !== $source) {
		return wp_html_excerpt(
			'meet ' . $name . ' from ' . $source . ': his book boyfriend tropes, spice rating, personality breakdown, quotes, and what to read next if he ruined you.',
			260,
			''
		);
	}

	$fallback = bbb_fictional_boyfriend_seo_description($post_id);
	if ('' === $fallback) {
		$fallback = bbb_fictional_boyfriend_seo_clean((string) get_post_meta($post_id, '_bbb_fb_one_line_profile', true));
	}
	if ('' === $fallback) {
		$fallback = bbb_fictional_boyfriend_seo_clean((string) get_the_excerpt($post_id));
	}
	if ('' === $fallback) {
		$fallback = bbb_fictional_boyfriend_pinterest_title($post_id);
	}

	return wp_html_excerpt($fallback, 260, '');
}

function bbb_fictional_boyfriend_main_image_pinterest_description(int $post_id): string {
	$name = bbb_fictional_boyfriend_seo_clean((string) get_post_field('post_title', $post_id));
	if ('' === $name) {
		$name = 'this fictional boyfriend';
	}

	$source = bbb_fictional_boyfriend_seo_source($post_id);
	if ('' !== $source) {
		return wp_html_excerpt($name . ' is from ' . $source . ' - check out his full profile at bybookishbabe.com', 260, '');
	}

	return wp_html_excerpt($name . ' fictional boyfriend profile - check out his full profile at bybookishbabe.com', 260, '');
}

function bbb_fictional_boyfriend_seo_focus_keyword(int $post_id): string {
	return bbb_fictional_boyfriend_seo_name($post_id) . ' fictional boyfriend';
}

function bbb_fictional_boyfriend_sync_seo_meta(int $post_id): void {
	if ('bbb_boyfriend' !== get_post_type($post_id) || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
		return;
	}

	$title = bbb_fictional_boyfriend_seo_title($post_id);
	$description = bbb_fictional_boyfriend_seo_description($post_id);
	$social_title = bbb_fictional_boyfriend_pinterest_title($post_id);
	$social_description = bbb_fictional_boyfriend_pinterest_description($post_id);
	$keyword = bbb_fictional_boyfriend_seo_focus_keyword($post_id);

	$meta = array(
		'rank_math_title'                => $title,
		'rank_math_description'          => $description,
		'rank_math_focus_keyword'        => $keyword,
		'rank_math_facebook_title'       => $social_title,
		'rank_math_facebook_description' => $social_description,
		'rank_math_twitter_title'        => $social_title,
		'rank_math_twitter_description'  => $social_description,
		'_yoast_wpseo_title'             => $title,
		'_yoast_wpseo_metadesc'          => $description,
		'_yoast_wpseo_focuskw'           => $keyword,
		'_yoast_wpseo_opengraph-title'   => $social_title,
		'_yoast_wpseo_opengraph-description' => $social_description,
		'_yoast_wpseo_twitter-title'     => $social_title,
		'_yoast_wpseo_twitter-description' => $social_description,
		'_bbb_fb_seo_description'        => $description,
	);

	foreach ($meta as $key => $value) {
		if ('' === $value) {
			delete_post_meta($post_id, $key);
			continue;
		}

		update_post_meta($post_id, $key, $value);
	}
}
add_action('save_post_bbb_boyfriend', 'bbb_fictional_boyfriend_sync_seo_meta', 60);

function bbb_fictional_boyfriend_lines_meta(int $post_id, string $key): array {
	$raw = trim((string) get_post_meta($post_id, $key, true));
	if ('' === $raw) {
		return array();
	}

	return array_values(
		array_filter(
			array_map(
				'trim',
				preg_split('/\r\n|\r|\n/', $raw) ?: array()
			)
		)
	);
}

function bbb_fictional_boyfriend_pinterest_links(int $post_id): array {
	$links = array();
	foreach (bbb_fictional_boyfriend_lines_meta($post_id, '_bbb_fb_pinterest_urls') as $line) {
		$parts = array_map('trim', explode('|', $line, 2));
		$media_url = esc_url_raw((string) ($parts[0] ?? ''));
		$source_url = esc_url_raw((string) ($parts[1] ?? ''));
		if ('' === $media_url) {
			continue;
		}

		if ('' !== $source_url) {
			$links[] = $media_url . ' | ' . $source_url;
		} else {
			$links[] = $media_url;
		}
	}

	return array_values(array_unique($links));
}

function bbb_fictional_boyfriend_editor_template(): array {
	return array(
		array(
			'core/heading',
			array(
				'level' => 2,
				'content' => 'who he is',
			),
		),
		array(
			'core/paragraph',
			array(
				'placeholder' => '3-4 paragraphs in your voice: personality, what makes him compelling, what he does that he should not, why readers fall for him anyway, and the moment that defines him. paraphrase only.',
			),
		),
		array(
			'core/heading',
			array(
				'level' => 2,
				'content' => 'his best quality',
			),
		),
		array(
			'core/paragraph',
			array(
				'placeholder' => 'one punchy paragraph: the thing that makes him redeemable or irresistible.',
			),
		),
		array(
			'core/heading',
			array(
				'level' => 2,
				'content' => 'his worst quality',
			),
		),
		array(
			'core/paragraph',
			array(
				'placeholder' => 'one punchy paragraph: the red flag readers ignore anyway.',
			),
		),
		array(
			'core/heading',
			array(
				'level' => 2,
				'content' => 'the moment everyone talks about',
			),
		),
		array(
			'core/paragraph',
			array(
				'placeholder' => 'paraphrase the scene or chapter people remember. no direct quotes.',
			),
		),
		array(
			'core/heading',
			array(
				'level' => 2,
				'content' => 'why he ruins your standards',
			),
		),
		array(
			'core/paragraph',
			array(
				'placeholder' => 'optional closing paragraph in your voice.',
			),
		),
	);
}

function bbb_fictional_boyfriend_template_content(): string {
	return <<<HTML
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">who he is</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"placeholder":"3-4 paragraphs in your voice: personality, what makes him compelling, what he does that he should not, why readers fall for him anyway, and the moment that defines him. paraphrase only."} -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">his best quality</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"placeholder":"one punchy paragraph: the thing that makes him redeemable or irresistible."} -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">his worst quality</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"placeholder":"one punchy paragraph: the red flag readers ignore anyway."} -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">the moment everyone talks about</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"placeholder":"paraphrase the scene or chapter people remember. no direct quotes."} -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">why he ruins your standards</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"placeholder":"optional closing paragraph in your voice."} -->
<p></p>
<!-- /wp:paragraph -->
HTML;
}

function bbb_fictional_boyfriend_find_existing_for_book(int $book_id, string $boyfriend_name): ?WP_Post {
	if ($book_id <= 0 || '' === trim($boyfriend_name) || !post_type_exists('bbb_boyfriend')) {
		return null;
	}

	$profiles = get_posts(
		array(
			'post_type'              => 'bbb_boyfriend',
			'post_status'            => array('publish', 'draft', 'pending', 'private', 'future'),
			'posts_per_page'         => -1,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	foreach ($profiles as $profile) {
		if ($profile instanceof WP_Post && in_array($book_id, bbb_fictional_boyfriend_book_ids((int) $profile->ID), true)) {
			return $profile;
		}
	}

	foreach ($profiles as $profile) {
		if ($profile instanceof WP_Post && bbb_fictional_boyfriend_name_matches($boyfriend_name, array(get_the_title($profile)))) {
			return $profile;
		}
	}

	return null;
}

function bbb_fictional_boyfriend_link_book(int $post_id, int $book_id): void {
	$book_ids = bbb_fictional_boyfriend_book_ids($post_id);
	if (!in_array($book_id, $book_ids, true)) {
		$book_ids[] = $book_id;
		update_post_meta($post_id, '_bbb_fb_book_ids', implode(',', array_values(array_unique(array_filter($book_ids)))));
	}

	bbb_fictional_boyfriend_sync_from_books($post_id);
}

function bbb_fictional_boyfriend_maybe_create_from_book(int $book_id): void {
	if (wp_is_post_revision($book_id) || wp_is_post_autosave($book_id) || !post_type_exists('bbb_boyfriend')) {
		return;
	}

	$book = get_post($book_id);
	if (!$book instanceof WP_Post || 'bbb_book' !== $book->post_type || 'auto-draft' === $book->post_status) {
		return;
	}

	$boyfriend_name = trim((string) get_post_meta($book_id, '_bbb_boyfriend_name', true));
	if ('' === $boyfriend_name) {
		return;
	}

	$existing = bbb_fictional_boyfriend_find_existing_for_book($book_id, $boyfriend_name);
	if ($existing instanceof WP_Post) {
		bbb_fictional_boyfriend_link_book((int) $existing->ID, $book_id);
		return;
	}

	$book_title = get_the_title($book);
	$boyfriend_type = trim((string) get_post_meta($book_id, '_bbb_boyfriend_type', true));
	$excerpt_parts = array_filter(
		array(
			'Draft fictional boyfriend profile created from ' . $book_title . '.',
			$boyfriend_type,
		)
	);

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'bbb_boyfriend',
			'post_status'  => 'draft',
			'post_title'   => sanitize_text_field($boyfriend_name),
			'post_excerpt' => sanitize_textarea_field(implode(' ', $excerpt_parts)),
			'post_content' => bbb_fictional_boyfriend_template_content(),
		),
		true
	);

	if (is_wp_error($post_id) || $post_id <= 0) {
		return;
	}

	update_post_meta((int) $post_id, '_bbb_fb_book_ids', (string) $book_id);
	if ('' !== $boyfriend_type) {
		update_post_meta((int) $post_id, '_bbb_fb_one_line_profile', $boyfriend_type);
	}

	$thumbnail_id = (int) get_post_thumbnail_id($book_id);
	if ($thumbnail_id > 0) {
		set_post_thumbnail((int) $post_id, $thumbnail_id);
	}

	bbb_fictional_boyfriend_sync_from_books((int) $post_id);
}
add_action('save_post_bbb_book', 'bbb_fictional_boyfriend_maybe_create_from_book', 40);

function bbb_fictional_boyfriend_match_key(string $value): string {
	$value = strtolower(wp_strip_all_tags($value));
	$value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;
	return trim((string) preg_replace('/\s+/', ' ', $value));
}

function bbb_fictional_boyfriend_name_matches(string $candidate, array $names): bool {
	$candidate = bbb_fictional_boyfriend_match_key($candidate);
	if ('' === $candidate) {
		return false;
	}

	foreach ($names as $name) {
		if ($candidate === bbb_fictional_boyfriend_match_key((string) $name)) {
			return true;
		}
	}

	return false;
}

function bbb_fictional_boyfriend_quote_boyfriend_ids(WP_Post $quote): array {
	$ids = array();
	foreach (array('_quote_boyfriend_id', '_bbb_quote_boyfriend_id', 'quote_boyfriend_id', 'boyfriend_id') as $key) {
		$value = get_post_meta($quote->ID, $key, true);
		if (is_array($value)) {
			$ids = array_merge($ids, array_map('absint', $value));
		} else {
			$ids[] = absint($value);
		}
	}

	return array_values(array_unique(array_filter($ids)));
}

function bbb_fictional_boyfriend_book_boyfriend_names(WP_Post $book): array {
	$names = array();
	foreach (array('_bbb_boyfriend_name', 'bbb_boyfriend_name', 'boyfriend_name', '_sss_boyfriend_name', 'sss_boyfriend_name') as $key) {
		$value = get_post_meta($book->ID, $key, true);
		if (is_array($value)) {
			$names = array_merge($names, array_map('strval', $value));
		} elseif ('' !== trim((string) $value)) {
			$names[] = (string) $value;
		}
	}

	if (function_exists('sss_book_data')) {
		$data = sss_book_data($book);
		foreach (array('boyfriend_name', 'boyfriendName') as $key) {
			if (!empty($data[$key])) {
				$names[] = (string) $data[$key];
			}
		}
	}

	return array_values(array_unique(array_filter(array_map('trim', $names))));
}

function bbb_fictional_boyfriend_quote_matches_profile(WP_Post $quote, int $post_id, array $book_ids): bool {
	$direct_ids = bbb_fictional_boyfriend_quote_boyfriend_ids($quote);
	if ($direct_ids) {
		return in_array($post_id, $direct_ids, true);
	}

	$profile_names = array(get_the_title($post_id));
	$stored_name = trim((string) get_post_meta($quote->ID, '_quote_boyfriend_name', true));
	if ('' !== $stored_name) {
		return bbb_fictional_boyfriend_name_matches($stored_name, $profile_names);
	}

	$quote_book = function_exists('bbb_quote_wall_book') ? bbb_quote_wall_book($quote) : null;
	if (!$quote_book instanceof WP_Post) {
		return false;
	}

	$quote_book_id = (int) $quote_book->ID;
	if (!in_array($quote_book_id, $book_ids, true)) {
		return false;
	}

	if ($quote_book_id === bbb_fictional_boyfriend_primary_book_id($post_id)) {
		return true;
	}

	return bbb_fictional_boyfriend_name_matches(
		implode(' ', bbb_fictional_boyfriend_book_boyfriend_names($quote_book)),
		$profile_names
	);
}

function bbb_fictional_boyfriend_quotes(int $post_id, int $limit = 0): array {
	$book_id = bbb_fictional_boyfriend_primary_book_id($post_id);
	$book = $book_id ? get_post($book_id) : null;
	$quote_types = function_exists('bbb_quote_post_types') ? bbb_quote_post_types() : array();
	if (!$book instanceof WP_Post || !$quote_types) {
		return array();
	}

	$book_ids = bbb_fictional_boyfriend_series_books($post_id);
	if (!$book_ids) {
		$book_ids = array((int) $book->ID);
	}
	$book_ids = array_values(array_unique(array_filter(array_map('absint', $book_ids))));
	$book_handles = array_values(
		array_filter(
			array_map(
				static fn(int $related_book_id): string => sanitize_title((string) get_post_field('post_name', $related_book_id)),
				$book_ids
			)
		)
	);
	$id_values = array_map('strval', $book_ids);
	$posts_per_page = $limit > 0 ? $limit : -1;

	$quote_ids = get_posts(
		array(
			'post_type'      => $quote_types,
			'post_status'    => 'publish',
			'posts_per_page' => $posts_per_page,
			'fields'         => 'ids',
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'meta_query'     => array(
				'relation' => 'OR',
				array('key' => '_quote_book_id', 'value' => $id_values, 'compare' => 'IN'),
				array('key' => '_quote_library_book_id', 'value' => $id_values, 'compare' => 'IN'),
				array('key' => 'book_id', 'value' => $id_values, 'compare' => 'IN'),
				array('key' => 'library_book_id', 'value' => $id_values, 'compare' => 'IN'),
				array('key' => '_quote_book_handle', 'value' => $book_handles, 'compare' => 'IN'),
				array('key' => 'book_handle', 'value' => $book_handles, 'compare' => 'IN'),
				array('key' => '_bbb_book_handle', 'value' => $book_handles, 'compare' => 'IN'),
			),
		)
	);

	$quotes = array_values(
		array_filter(
			array_map('get_post', array_map('absint', $quote_ids)),
			static fn($quote): bool => $quote instanceof WP_Post
		)
	);
	$quotes = array_values(
		array_filter(
			$quotes,
			static fn(WP_Post $quote): bool => bbb_fictional_boyfriend_quote_matches_profile($quote, $post_id, $book_ids)
		)
	);

	if (($limit > 0 && count($quotes) >= $limit) || !function_exists('bbb_bookquote_quote_book_matches')) {
		return $limit > 0 ? array_slice($quotes, 0, $limit) : $quotes;
	}

	$maybe_quotes = get_posts(
		array(
			'post_type'      => $quote_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	$series_books = array_values(
		array_filter(
			array_map('get_post', $book_ids),
			static fn($series_book): bool => $series_book instanceof WP_Post
		)
	);

	foreach ($maybe_quotes as $quote) {
		if ($limit > 0 && count($quotes) >= $limit) {
			break;
		}

		if (!$quote instanceof WP_Post || in_array($quote->ID, wp_list_pluck($quotes, 'ID'), true)) {
			continue;
		}

		foreach ($series_books as $series_book) {
			if (bbb_bookquote_quote_book_matches($quote, $series_book) && bbb_fictional_boyfriend_quote_matches_profile($quote, $post_id, $book_ids)) {
				$quotes[] = $quote;
				break;
			}
		}
	}

	return $limit > 0 ? array_slice($quotes, 0, $limit) : $quotes;
}

function bbb_fictional_boyfriend_related(int $post_id, int $limit = 3): array {
	$current_filter = bbb_fictional_boyfriend_filter_key($post_id);
	$posts = get_posts(
		array(
			'post_type'      => 'bbb_boyfriend',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'exclude'        => array($post_id),
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$related = array();
	foreach ($posts as $post) {
		if (!$post instanceof WP_Post || !bbb_fictional_boyfriend_profile_ready($post->ID)) {
			continue;
		}
		if (function_exists('bbb_content_is_publicly_discoverable') && !bbb_content_is_publicly_discoverable($post->ID)) {
			continue;
		}

		if (bbb_fictional_boyfriend_filter_key($post->ID) === $current_filter || count($related) < $limit) {
			$related[] = $post;
		}

		if (count($related) >= $limit) {
			break;
		}
	}

	return $related;
}

function bbb_fictional_boyfriend_popular_slug(): string {
	$slug = sanitize_title((string) get_option('bbb_fictional_boyfriend_popular_slug', 'malachi-vize'));

	return (string) apply_filters('bbb_fictional_boyfriend_popular_slug', $slug ?: 'malachi-vize');
}

function bbb_fictional_boyfriend_popular_post(): ?WP_Post {
	$slug = bbb_fictional_boyfriend_popular_slug();
	if ('' === $slug || !post_type_exists('bbb_boyfriend')) {
		return null;
	}

	$post = get_page_by_path($slug, OBJECT, 'bbb_boyfriend');
	if (!$post instanceof WP_Post || 'publish' !== $post->post_status) {
		return null;
	}

	if (function_exists('bbb_fictional_boyfriend_profile_ready') && !bbb_fictional_boyfriend_profile_ready((int) $post->ID)) {
		return null;
	}

	if (function_exists('bbb_content_is_publicly_discoverable') && !bbb_content_is_publicly_discoverable((int) $post->ID)) {
		return null;
	}

	return $post;
}

function bbb_fictional_boyfriend_is_popular_now(int $post_id): bool {
	$popular = bbb_fictional_boyfriend_popular_post();

	return $popular instanceof WP_Post && (int) $popular->ID === $post_id;
}

function bbb_fictional_boyfriend_popular_badge(string $class_name = ''): string {
	$classes = trim('bbb-fb-popular-badge ' . $class_name);

	return sprintf('<span class="%s">most popular now</span>', esc_attr($classes));
}

add_filter(
	'rank_math/frontend/robots',
	static function (array $robots): array {
		if (is_singular('bbb_boyfriend')) {
			$content = trim(wp_strip_all_tags((string) get_post_field('post_content', get_queried_object_id())));
			if ('' === $content) {
				$robots['noindex'] = 'noindex';
				$robots['nofollow'] = 'nofollow';
			}
		}

		return $robots;
	},
	99
);

add_filter(
	'rank_math/frontend/description',
	static function (string $description): string {
		return is_singular('bbb_boyfriend') ? bbb_fictional_boyfriend_seo_description((int) get_queried_object_id()) : $description;
	},
	99
);

add_filter(
	'rank_math/frontend/title',
	static function (string $title): string {
		return is_singular('bbb_boyfriend') ? bbb_fictional_boyfriend_seo_title((int) get_queried_object_id()) : $title;
	},
	99
);

add_filter(
	'rank_math/opengraph/facebook/title',
	static function (string $title): string {
		return is_singular('bbb_boyfriend') ? bbb_fictional_boyfriend_pinterest_title((int) get_queried_object_id()) : $title;
	},
	99
);

add_filter(
	'rank_math/opengraph/facebook/description',
	static function (string $description): string {
		return is_singular('bbb_boyfriend') ? bbb_fictional_boyfriend_pinterest_description((int) get_queried_object_id()) : $description;
	},
	99
);

add_filter(
	'rank_math/opengraph/twitter/title',
	static function (string $title): string {
		return is_singular('bbb_boyfriend') ? bbb_fictional_boyfriend_pinterest_title((int) get_queried_object_id()) : $title;
	},
	99
);

add_filter(
	'rank_math/opengraph/twitter/description',
	static function (string $description): string {
		return is_singular('bbb_boyfriend') ? bbb_fictional_boyfriend_pinterest_description((int) get_queried_object_id()) : $description;
	},
	99
);
