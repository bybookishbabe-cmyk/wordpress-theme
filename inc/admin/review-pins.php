<?php
/**
 * Admin controls for blog review Pinterest pins.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_review_pin_token_context(int $post_id): array {
	$books = function_exists('sss_article_books_for_post') ? sss_article_books_for_post($post_id) : array();
	$book = $books[0] ?? null;
	$book_data = $book instanceof WP_Post && function_exists('sss_article_book_data') ? sss_article_book_data($book->ID) : array();
	$book_title = $book instanceof WP_Post ? wp_specialchars_decode(get_the_title($book), ENT_QUOTES) : '';
	$author = wp_specialchars_decode(trim((string) ($book_data['author'] ?? ($book instanceof WP_Post ? get_post_meta($book->ID, '_bbb_author', true) : ''))), ENT_QUOTES);

	return array(
		'permalink'  => get_permalink($post_id),
		'title'      => wp_specialchars_decode(get_the_title($post_id), ENT_QUOTES),
		'book_title' => $book_title,
		'author'     => $author,
	);
}

function bbb_review_pin_apply_template(string $template, int $post_id): string {
	$context = bbb_review_pin_token_context($post_id);
	$value = $template;

	foreach ($context as $token => $replacement) {
		$value = str_replace('{' . $token . '}', (string) $replacement, $value);
	}

	return trim($value);
}

function bbb_review_pin_title(int $post_id): string {
	$stored = trim((string) get_post_meta($post_id, '_bbb_review_pin_title', true));
	if ('' !== $stored) {
		return bbb_review_pin_apply_template($stored, $post_id);
	}

	$context = bbb_review_pin_token_context($post_id);
	return trim((string) ($context['book_title'] ?: $context['title']) . ' review');
}

function bbb_review_pin_description(int $post_id): string {
	$stored = trim((string) get_post_meta($post_id, '_bbb_review_pin_description', true));
	if ('' !== $stored) {
		return bbb_review_pin_apply_template($stored, $post_id);
	}

	$context = bbb_review_pin_token_context($post_id);
	$source = trim((string) ($context['book_title'] ?: $context['title']) . (!empty($context['author']) ? ' by ' . $context['author'] : ''));
	return trim('Save this romance book review' . ('' !== $source ? ' for ' . $source : '') . '.');
}

function bbb_review_pin_link_url(int $post_id): string {
	$template = trim((string) get_post_meta($post_id, '_bbb_review_pin_link_template', true));
	if ('' === $template) {
		$template = '{permalink}';
	}

	$url = bbb_review_pin_apply_template($template, $post_id);
	return esc_url_raw($url ?: get_permalink($post_id));
}

function bbb_review_pin_add_meta_box(): void {
	add_meta_box(
		'bbb_review_pin',
		__('Pinterest review pin', 'bybookishbabe-shopify-port'),
		'bbb_review_pin_render_meta_box',
		'post',
		'side',
		'default'
	);
}
add_action('add_meta_boxes_post', 'bbb_review_pin_add_meta_box');

function bbb_review_pin_render_meta_box(WP_Post $post): void {
	$enabled = '1' === (string) get_post_meta($post->ID, '_bbb_review_pin_enabled', true);
	$media_url = trim((string) get_post_meta($post->ID, '_bbb_review_pin_media_url', true));
	$title = (string) get_post_meta($post->ID, '_bbb_review_pin_title', true);
	$description = (string) get_post_meta($post->ID, '_bbb_review_pin_description', true);
	$link_template = (string) get_post_meta($post->ID, '_bbb_review_pin_link_template', true);
	if ('' === $link_template) {
		$link_template = '{permalink}';
	}
	$studio_url = add_query_arg('url', get_permalink($post->ID), 'http://localhost:4177/');

	wp_nonce_field('bbb_review_pin_save', 'bbb_review_pin_nonce');
	?>
	<p>
		<label>
			<input type="checkbox" name="bbb_review_pin_enabled" value="1" <?php checked($enabled); ?>>
			<?php esc_html_e('Show Pinterest pin button on this review', 'bybookishbabe-shopify-port'); ?>
		</label>
	</p>
	<p>
		<label for="bbb_review_pin_media_url"><strong><?php esc_html_e('Studio PNG URL', 'bybookishbabe-shopify-port'); ?></strong></label>
		<input id="bbb_review_pin_media_url" name="bbb_review_pin_media_url" type="url" value="<?php echo esc_attr($media_url); ?>" style="width:100%;" placeholder="https://.../review-pin.png">
	</p>
	<?php if ('' !== $media_url) : ?>
		<p>
			<img src="<?php echo esc_url($media_url); ?>" alt="" style="display:block;width:100%;height:auto;border:1px solid #dcdcde;border-radius:6px;background:#fff;">
		</p>
		<p>
			<a class="button" href="<?php echo esc_url($media_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Preview', 'bybookishbabe-shopify-port'); ?></a>
			<a class="button" href="<?php echo esc_url($media_url); ?>" download><?php esc_html_e('Download PNG', 'bybookishbabe-shopify-port'); ?></a>
		</p>
	<?php endif; ?>
	<p>
		<a class="button button-secondary" href="<?php echo esc_url($studio_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open in local studio', 'bybookishbabe-shopify-port'); ?></a>
	</p>
	<p>
		<label for="bbb_review_pin_title"><strong><?php esc_html_e('Pin title', 'bybookishbabe-shopify-port'); ?></strong></label>
		<input id="bbb_review_pin_title" name="bbb_review_pin_title" type="text" value="<?php echo esc_attr($title); ?>" style="width:100%;" placeholder="{book_title} review">
	</p>
	<p>
		<label for="bbb_review_pin_description"><strong><?php esc_html_e('Pin description', 'bybookishbabe-shopify-port'); ?></strong></label>
		<textarea id="bbb_review_pin_description" name="bbb_review_pin_description" rows="4" style="width:100%;" placeholder="Save this review for {book_title} by {author}."><?php echo esc_textarea($description); ?></textarea>
	</p>
	<p>
		<label for="bbb_review_pin_link_template"><strong><?php esc_html_e('Pin link template', 'bybookishbabe-shopify-port'); ?></strong></label>
		<input id="bbb_review_pin_link_template" name="bbb_review_pin_link_template" type="text" value="<?php echo esc_attr($link_template); ?>" style="width:100%;">
	</p>
	<p class="description">
		<?php esc_html_e('Tokens: {permalink}, {title}, {book_title}, {author}. Generate/download the PNG in the local studio, upload or save it locally, paste its URL here, then enable the button when ready.', 'bybookishbabe-shopify-port'); ?>
	</p>
	<?php
}

function bbb_review_pin_save_post(int $post_id): void {
	if (!isset($_POST['bbb_review_pin_nonce']) || !wp_verify_nonce((string) wp_unslash($_POST['bbb_review_pin_nonce']), 'bbb_review_pin_save')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$enabled = isset($_POST['bbb_review_pin_enabled']) ? '1' : '0';
	update_post_meta($post_id, '_bbb_review_pin_enabled', $enabled);

	foreach (
		array(
			'_bbb_review_pin_media_url'     => array('field' => 'bbb_review_pin_media_url', 'type' => 'url'),
			'_bbb_review_pin_title'         => array('field' => 'bbb_review_pin_title', 'type' => 'text'),
			'_bbb_review_pin_description'   => array('field' => 'bbb_review_pin_description', 'type' => 'textarea'),
			'_bbb_review_pin_link_template' => array('field' => 'bbb_review_pin_link_template', 'type' => 'text'),
		) as $meta_key => $config
	) {
		$field = (string) $config['field'];
		$value = isset($_POST[$field]) && is_scalar($_POST[$field]) ? (string) wp_unslash($_POST[$field]) : '';
		$value = 'url' === $config['type'] ? esc_url_raw($value) : sanitize_textarea_field($value);
		if ('' !== trim($value)) {
			update_post_meta($post_id, $meta_key, trim($value));
		} else {
			delete_post_meta($post_id, $meta_key);
		}
	}
}
add_action('save_post_post', 'bbb_review_pin_save_post');
