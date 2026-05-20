<?php
/**
 * Admin importer for Shopify SSS monthly drops.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_sss_drop_importer_register_cpt(): void {
	register_post_type(
		'sss_drop',
		array(
			'labels' => array(
				'name'          => __('Society Drops', 'bybookishbabe-shopify-port'),
				'singular_name' => __('Society Drop', 'bybookishbabe-shopify-port'),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => false,
			'supports'     => array('title', 'custom-fields'),
			'capability_type' => 'post',
		)
	);
}
add_action('init', 'bbb_sss_drop_importer_register_cpt');

function bbb_sss_drop_importer_field_map(array $entry): array {
	$fields = array();
	foreach ((array) ($entry['fields'] ?? array()) as $field) {
		if (is_array($field) && !empty($field['key'])) {
			$fields[(string) $field['key']] = $field;
		}
	}

	return $fields;
}

function bbb_sss_drop_importer_field_value(array $fields, string $key, string $default = ''): string {
	if (empty($fields[$key]) || !is_array($fields[$key])) {
		return $default;
	}

	$value = $fields[$key]['jsonValue'] ?? $fields[$key]['value'] ?? $default;
	if (is_array($value)) {
		$value = $fields[$key]['value'] ?? $default;
	}

	return trim((string) $value);
}

function bbb_sss_drop_importer_reference_nodes(array $fields, array $keys): array {
	$nodes = array();
	foreach ($keys as $key) {
		if (!empty($fields[$key]['reference']) && is_array($fields[$key]['reference'])) {
			$nodes[] = $fields[$key]['reference'];
		}

		foreach ((array) ($fields[$key]['references']['nodes'] ?? array()) as $node) {
			if (is_array($node)) {
				$nodes[] = $node;
			}
		}
	}

	return $nodes;
}

function bbb_sss_drop_importer_product_refs(array $fields): array {
	$products = bbb_sss_drop_importer_reference_nodes(
		$fields,
		array(
			'bonus_printable_product',
			'bonus_physical_product',
			'monthly_collection_printable_products',
			'monthly_collection_physical_products',
		)
	);

	$seen = array();
	$out  = array();
	foreach ($products as $product) {
		$handle = sanitize_title((string) ($product['handle'] ?? ''));
		$id     = (string) ($product['id'] ?? '');
		$key    = $handle ?: $id;
		if ('' === $key || isset($seen[$key])) {
			continue;
		}

		$seen[$key] = true;
		$out[] = array(
			'id'     => $id,
			'handle' => $handle,
			'title'  => (string) ($product['title'] ?? $handle),
			'type'   => (string) ($product['__typename'] ?? 'Product'),
		);
	}

	return $out;
}

function bbb_sss_drop_importer_file_refs(array $fields): array {
	$files = bbb_sss_drop_importer_reference_nodes(
		$fields,
		array(
			'gram_image',
			'calendar_image',
			'calendar_pdf',
			'mood_images',
			'mood_stickers',
			'era_images',
			'wallpaper_images',
		)
	);

	$out = array();
	foreach ($files as $file) {
		$url = $file['image']['url'] ?? $file['url'] ?? '';
		if (!is_string($url) || '' === $url) {
			continue;
		}

		$out[] = array(
			'id'     => (string) ($file['id'] ?? ''),
			'type'   => (string) ($file['__typename'] ?? 'File'),
			'url'    => $url,
			'alt'    => (string) ($file['image']['altText'] ?? ''),
			'width'  => (int) ($file['image']['width'] ?? 0),
			'height' => (int) ($file['image']['height'] ?? 0),
		);
	}

	return $out;
}

function bbb_sss_drop_importer_upsert_product_stubs(array $products): int {
	if (!post_type_exists('product')) {
		return 0;
	}

	$count = 0;
	foreach ($products as $product) {
		$handle = sanitize_title((string) ($product['handle'] ?? ''));
		$title  = trim((string) ($product['title'] ?? $handle));
		if ('' === $handle || '' === $title) {
			continue;
		}

		$existing = get_page_by_path($handle, OBJECT, 'product');
		$post_id  = $existing instanceof WP_Post ? (int) $existing->ID : 0;
		$args     = array(
			'ID'          => $post_id,
			'post_type'   => 'product',
			'post_status' => $post_id ? get_post_status($post_id) : 'draft',
			'post_name'   => $handle,
			'post_title'  => $title,
			'post_content' => '',
		);

		$new_id = $post_id ? wp_update_post($args, true) : wp_insert_post($args, true);
		if (is_wp_error($new_id) || !$new_id) {
			continue;
		}

		update_post_meta((int) $new_id, '_bbb_shopify_product_gid', (string) ($product['id'] ?? ''));
		update_post_meta((int) $new_id, '_bbb_import_source', 'sss_drop_product_reference');
		$count++;
	}

	return $count;
}

function bbb_sss_drop_importer_import_entry(array $entry, bool $create_product_stubs = false) {
	$fields = bbb_sss_drop_importer_field_map($entry);
	$handle = sanitize_title((string) ($entry['handle'] ?? bbb_sss_drop_importer_field_value($fields, 'name')));
	$name   = bbb_sss_drop_importer_field_value($fields, 'name', $handle);

	if ('' === $handle || '' === $name) {
		return new WP_Error('bbb_sss_drop_missing_handle', 'A drop is missing a handle or name.');
	}

	$existing = get_posts(
		array(
			'post_type'      => 'sss_drop',
			'name'           => $handle,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	$post_id = !empty($existing[0]) ? (int) $existing[0] : 0;
	$args    = array(
		'ID'          => $post_id,
		'post_type'   => 'sss_drop',
		'post_status' => 'publish',
		'post_name'   => $handle,
		'post_title'  => $name,
	);

	$result = $post_id ? wp_update_post($args, true) : wp_insert_post($args, true);
	if (is_wp_error($result)) {
		return $result;
	}

	$post_id  = (int) $result;
	$products = bbb_sss_drop_importer_product_refs($fields);
	$files    = bbb_sss_drop_importer_file_refs($fields);

	update_post_meta($post_id, '_bbb_sss_drop_entry', wp_json_encode($entry));
	update_post_meta($post_id, '_bbb_sss_drop_handle', $handle);
	update_post_meta($post_id, '_bbb_sss_drop_shopify_id', (string) ($entry['id'] ?? ''));
	update_post_meta($post_id, '_bbb_sss_drop_release_date', bbb_sss_drop_importer_field_value($fields, 'release_date'));
	update_post_meta($post_id, '_bbb_sss_drop_end_date', bbb_sss_drop_importer_field_value($fields, 'end_date'));
	update_post_meta($post_id, '_bbb_sss_drop_product_refs', wp_json_encode($products));
	update_post_meta($post_id, '_bbb_sss_drop_file_refs', wp_json_encode($files));
	update_post_meta($post_id, '_bbb_sss_drop_imported_at', gmdate('c'));

	$product_count = $create_product_stubs ? bbb_sss_drop_importer_upsert_product_stubs($products) : 0;

	return array(
		'post_id'        => $post_id,
		'handle'         => $handle,
		'products'       => count($products),
		'files'          => count($files),
		'product_stubs'  => $product_count,
	);
}

function bbb_sss_drop_importer_import_json(string $json, bool $create_product_stubs = false) {
	$data = json_decode($json, true);
	if (!is_array($data) || empty($data['entries']) || !is_array($data['entries'])) {
		return new WP_Error('bbb_sss_drop_invalid_json', 'Expected a Shopify metaobject export with an entries array.');
	}

	$results = array();
	foreach ($data['entries'] as $entry) {
		if (!is_array($entry)) {
			continue;
		}

		$result = bbb_sss_drop_importer_import_entry($entry, $create_product_stubs);
		if (is_wp_error($result)) {
			return $result;
		}

		$results[] = $result;
	}

	return $results;
}

function bbb_sss_drop_importer_active_entry(): array {
	$posts = get_posts(
		array(
			'post_type'      => 'sss_drop',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_bbb_sss_drop_release_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
		)
	);

	if (!$posts) {
		return array();
	}

	$today = (int) current_time('timestamp');
	$best = null;
	$best_time = 0;
	$fallback = null;
	$fallback_time = 0;

	foreach ($posts as $post) {
		if (!$post instanceof WP_Post) {
			continue;
		}

		$release = (string) get_post_meta($post->ID, '_bbb_sss_drop_release_date', true);
		$time    = '' !== $release ? strtotime($release . ' 00:00:00') : false;
		if (!$time) {
			continue;
		}

		$end      = (string) get_post_meta($post->ID, '_bbb_sss_drop_end_date', true);
		$end_time = '' !== $end ? strtotime($end . ' 23:59:59') : false;
		if ($time <= $today && (!$end_time || $end_time >= $today) && $time >= $best_time) {
			$best = $post;
			$best_time = $time;
		}

		if ($time <= $today && $time >= $fallback_time) {
			$fallback = $post;
			$fallback_time = $time;
		}
	}

	$active = $best ?: $fallback;
	if (!$active instanceof WP_Post) {
		return array();
	}

	$raw = (string) get_post_meta($active->ID, '_bbb_sss_drop_entry', true);
	$entry = json_decode($raw, true);

	return is_array($entry) ? $entry : array();
}

function bbb_sss_drop_importer_handle_request() {
	if (empty($_POST['bbb_sss_drop_import']) || !current_user_can('manage_options')) {
		return null;
	}

	check_admin_referer('bbb_sss_drop_import', 'bbb_sss_drop_import_nonce');

	$json = '';
	if (!empty($_FILES['bbb_sss_drop_json']['tmp_name'])) {
		$json = (string) file_get_contents((string) $_FILES['bbb_sss_drop_json']['tmp_name']);
	}

	if ('' === trim($json) && !empty($_POST['bbb_sss_drop_json_text'])) {
		$json = (string) wp_unslash($_POST['bbb_sss_drop_json_text']);
	}

	if ('' === trim($json) && !empty($_POST['bbb_sss_drop_use_theme_file'])) {
		$path = get_theme_file_path('firstpass/migration/exports/metaobjects/sss_drop.json');
		$json = file_exists($path) ? (string) file_get_contents($path) : '';
	}

	if ('' === trim($json)) {
		return new WP_Error('bbb_sss_drop_empty_import', 'Upload, paste, or choose the theme export JSON first.');
	}

	return bbb_sss_drop_importer_import_json($json, !empty($_POST['bbb_sss_drop_create_products']));
}

function bbb_sss_drop_importer_admin_page(): void {
	$result = bbb_sss_drop_importer_handle_request();
	$drops  = get_posts(
		array(
			'post_type'      => 'sss_drop',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'meta_key'       => '_bbb_sss_drop_release_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
		)
	);
	?>
	<div class="wrap bbb-sss-drop-admin">
		<h1>Society Drops</h1>
		<p class="description">Import the Shopify <code>sss_drop</code> metaobject export into WordPress. This stores the monthly themes, files, prompts, Canva links, and Shopify product references in WordPress records.</p>

		<?php if (is_wp_error($result)) : ?>
			<div class="notice notice-error"><p><?php echo esc_html($result->get_error_message()); ?></p></div>
		<?php elseif (is_array($result)) : ?>
			<div class="notice notice-success"><p><?php echo esc_html('Imported ' . count($result) . ' drops.'); ?></p></div>
		<?php endif; ?>

		<form class="bbb-sss-drop-admin__import" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field('bbb_sss_drop_import', 'bbb_sss_drop_import_nonce'); ?>
			<input type="hidden" name="bbb_sss_drop_import" value="1">
			<h2>Import drops</h2>
			<p><label for="bbb_sss_drop_json">Upload <code>sss_drop.json</code></label></p>
			<input type="file" id="bbb_sss_drop_json" name="bbb_sss_drop_json" accept=".json,application/json">
			<p><label for="bbb_sss_drop_json_text">Or paste JSON</label></p>
			<textarea id="bbb_sss_drop_json_text" name="bbb_sss_drop_json_text" rows="8" class="large-text code"></textarea>
			<p>
				<label><input type="checkbox" name="bbb_sss_drop_use_theme_file" value="1"> import from current theme file if no upload/paste is provided</label>
			</p>
			<p>
				<label><input type="checkbox" name="bbb_sss_drop_create_products" value="1"> create/update draft WooCommerce product stubs for referenced Shopify products</label>
			</p>
			<?php submit_button('Import society drops'); ?>
		</form>

		<h2>Imported drops</h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Drop</th>
					<th>Handle</th>
					<th>Dates</th>
					<th>Product refs</th>
					<th>File refs</th>
				</tr>
			</thead>
			<tbody>
				<?php if (!$drops) : ?>
					<tr><td colspan="5">No drops imported yet.</td></tr>
				<?php endif; ?>
				<?php foreach ($drops as $drop) : ?>
					<?php
					$products = json_decode((string) get_post_meta($drop->ID, '_bbb_sss_drop_product_refs', true), true);
					$files    = json_decode((string) get_post_meta($drop->ID, '_bbb_sss_drop_file_refs', true), true);
					?>
					<tr>
						<td><strong><?php echo esc_html(get_the_title($drop)); ?></strong></td>
						<td><code><?php echo esc_html((string) get_post_meta($drop->ID, '_bbb_sss_drop_handle', true)); ?></code></td>
						<td><?php echo esc_html((string) get_post_meta($drop->ID, '_bbb_sss_drop_release_date', true) . ' - ' . (string) get_post_meta($drop->ID, '_bbb_sss_drop_end_date', true)); ?></td>
						<td><?php echo esc_html((string) count(is_array($products) ? $products : array())); ?></td>
						<td><?php echo esc_html((string) count(is_array($files) ? $files : array())); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<style>
		.bbb-sss-drop-admin__import{max-width:880px;margin:18px 0 24px;padding:16px;background:#fff;border:1px solid #dcdcde;border-radius:6px}
		.bbb-sss-drop-admin__import h2{margin-top:0}
	</style>
	<?php
}

function bbb_sss_drop_importer_admin_menu(): void {
	add_users_page(
		__('Society Drops', 'bybookishbabe-shopify-port'),
		__('Society Drops', 'bybookishbabe-shopify-port'),
		'manage_options',
		'bbb-society-drops',
		'bbb_sss_drop_importer_admin_page'
	);
}
add_action('admin_menu', 'bbb_sss_drop_importer_admin_menu');
