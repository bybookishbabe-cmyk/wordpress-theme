<?php
/**
 * Admin importer and member pricing rules for Society shop products.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_society_product_importer_truthy($value): bool {
	if (is_bool($value)) {
		return $value;
	}

	return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'y', 'on', 'free'), true);
}

function bbb_society_product_importer_split_terms($value): array {
	if (is_array($value)) {
		$items = $value;
	} else {
		$items = preg_split('/[|,]/', (string) $value) ?: array();
	}

	$terms = array();
	foreach ($items as $item) {
		$term = trim((string) $item);
		if ('' !== $term) {
			$terms[] = $term;
		}
	}

	return array_values(array_unique($terms));
}

function bbb_society_product_importer_array_is_list(array $value): bool {
	if (function_exists('array_is_list')) {
		return array_is_list($value);
	}

	return array_keys($value) === range(0, count($value) - 1);
}

function bbb_society_product_importer_platform(): string {
	if (post_type_exists('download')) {
		return 'edd';
	}

	if (post_type_exists('product')) {
		return 'woocommerce';
	}

	return '';
}

function bbb_society_product_importer_money($value): string {
	if (is_array($value)) {
		$value = $value['amount'] ?? $value['value'] ?? '';
	}

	$raw = preg_replace('/[^0-9.]/', '', (string) $value);
	if (null === $raw || '' === $raw) {
		return '';
	}

	return number_format((float) $raw, 2, '.', '');
}

function bbb_society_product_importer_first_node(array $value): array {
	if (!empty($value['edges'][0]['node']) && is_array($value['edges'][0]['node'])) {
		return $value['edges'][0]['node'];
	}

	if (!empty($value['nodes'][0]) && is_array($value['nodes'][0])) {
		return $value['nodes'][0];
	}

	if (!empty($value[0]) && is_array($value[0])) {
		return $value[0];
	}

	return array();
}

function bbb_society_product_importer_image_url(array $product): string {
	$candidates = array(
		$product['featuredImage']['url'] ?? '',
		$product['featured_image']['src'] ?? '',
		$product['image']['src'] ?? '',
		$product['image']['url'] ?? '',
	);

	if (!empty($product['images']) && is_array($product['images'])) {
		$image = bbb_society_product_importer_first_node($product['images']);
		$candidates[] = $image['url'] ?? $image['src'] ?? '';
	}

	foreach ($candidates as $candidate) {
		$url = trim((string) $candidate);
		if ('' !== $url) {
			return esc_url_raw($url);
		}
	}

	return '';
}

function bbb_society_product_importer_download_url(array $product): string {
	foreach (array('download_url', 'downloadUrl', 'file_url', 'fileUrl', 'digital_file', 'digitalFile') as $key) {
		if (!empty($product[$key]) && is_scalar($product[$key])) {
			return esc_url_raw((string) $product[$key]);
		}
	}

	foreach ((array) ($product['downloads'] ?? array()) as $download) {
		if (is_array($download) && !empty($download['url'])) {
			return esc_url_raw((string) $download['url']);
		}
	}

	foreach ((array) ($product['metafields']['edges'] ?? array()) as $edge) {
		$node = $edge['node'] ?? array();
		if (!is_array($node)) {
			continue;
		}

		$key = strtolower((string) ($node['key'] ?? ''));
		if (str_contains($key, 'download') || str_contains($key, 'file')) {
			$value = trim((string) ($node['value'] ?? ''));
			if (str_starts_with($value, 'http')) {
				return esc_url_raw($value);
			}
		}
	}

	return '';
}

function bbb_society_product_importer_download_files(array $product): array {
	$files = array();
	$raw   = $product['download_files'] ?? $product['downloadFiles'] ?? array();

	if (is_string($raw) && '' !== trim($raw)) {
		$decoded = json_decode($raw, true);
		$raw     = is_array($decoded) ? $decoded : preg_split('/[|,]/', $raw);
	}

	foreach ((array) $raw as $index => $file) {
		if (is_array($file)) {
			$url  = esc_url_raw((string) ($file['url'] ?? $file['file'] ?? ''));
			$name = trim((string) ($file['name'] ?? ''));
		} else {
			$url  = esc_url_raw((string) $file);
			$name = '';
		}

		if ('' === $url) {
			continue;
		}

		$files[] = array(
			'name' => '' !== $name ? $name : 'Download ' . ((int) $index + 1),
			'url'  => $url,
		);
	}

	$single = bbb_society_product_importer_download_url($product);
	if ('' !== $single && !in_array($single, array_column($files, 'url'), true)) {
		array_unshift(
			$files,
			array(
				'name' => trim((string) ($product['title'] ?? 'Download')),
				'url'  => $single,
			)
		);
	}

	return $files;
}

function bbb_society_product_importer_is_digital(array $product): bool {
	if (isset($product['is_digital'])) {
		return bbb_society_product_importer_truthy($product['is_digital']);
	}

	if ('' !== bbb_society_product_importer_download_url($product)) {
		return true;
	}

	if (bbb_society_product_importer_download_files($product)) {
		return true;
	}

	$type     = strtolower((string) ($product['product_type'] ?? $product['productType'] ?? ''));
	$title    = strtolower((string) ($product['title'] ?? $product['name'] ?? ''));
	$handle   = strtolower((string) ($product['handle'] ?? $product['slug'] ?? ''));
	$keywords = $type . ' ' . $title . ' ' . $handle . ' ' . strtolower(implode(' ', bbb_society_product_importer_split_terms($product['tags'] ?? '')));

	if (str_contains($type, 'physical') || str_contains($type, 'bookmark')) {
		return false;
	}

	return (bool) preg_match('/printable|digital|template|vault|tracker|download|canva/', $keywords);
}

function bbb_society_product_importer_price(array $product): string {
	foreach (array('price', 'regular_price', 'regularPrice') as $key) {
		if (isset($product[$key])) {
			$price = bbb_society_product_importer_money($product[$key]);
			if ('' !== $price) {
				return $price;
			}
		}
	}

	if (!empty($product['variants']) && is_array($product['variants'])) {
		$variant = bbb_society_product_importer_first_node($product['variants']);
		$price   = bbb_society_product_importer_money($variant['price'] ?? '');
		if ('' !== $price) {
			return $price;
		}
	}

	return bbb_society_product_importer_money($product['priceRangeV2']['minVariantPrice'] ?? '');
}

function bbb_society_product_importer_rows_from_json(string $json) {
	$data = json_decode($json, true);
	if (!is_array($data)) {
		return new WP_Error('bbb_society_products_bad_json', 'That JSON did not parse. Export Shopify products as JSON, or use the CSV template below.');
	}

	if (!empty($data['products']['edges'])) {
		return array_values(array_filter(array_map(static fn($edge) => $edge['node'] ?? null, (array) $data['products']['edges'])));
	}

	if (!empty($data['data']['products']['edges'])) {
		return array_values(array_filter(array_map(static fn($edge) => $edge['node'] ?? null, (array) $data['data']['products']['edges'])));
	}

	if (!empty($data['products']) && is_array($data['products'])) {
		return $data['products'];
	}

	if (bbb_society_product_importer_array_is_list($data)) {
		return $data;
	}

	return new WP_Error('bbb_society_products_unknown_json', 'Expected a products array, a Shopify Admin products export, or a GraphQL products connection.');
}

function bbb_society_product_importer_rows_from_csv(string $csv) {
	$handle = fopen('php://temp', 'r+');
	if (!$handle) {
		return new WP_Error('bbb_society_products_csv_temp', 'Could not read the CSV.');
	}

	fwrite($handle, $csv);
	rewind($handle);

	$headers = fgetcsv($handle);
	if (!is_array($headers)) {
		fclose($handle);
		return new WP_Error('bbb_society_products_csv_empty', 'The CSV needs a header row.');
	}

	$headers = array_map(static fn($header) => sanitize_key((string) $header), $headers);
	$rows    = array();

	while (($row = fgetcsv($handle)) !== false) {
		$item = array();
		foreach ($headers as $index => $key) {
			if ('' !== $key) {
				$item[$key] = $row[$index] ?? '';
			}
		}
		$rows[] = $item;
	}

	fclose($handle);

	return $rows;
}

function bbb_society_product_importer_normalize_row(array $row, bool $default_free): array {
	$handle = sanitize_title((string) ($row['handle'] ?? $row['slug'] ?? ''));
	$title  = trim((string) ($row['title'] ?? $row['name'] ?? $handle));
	$is_digital = bbb_society_product_importer_is_digital($row);
	$download_files = bbb_society_product_importer_download_files($row);

	return array(
		'id'           => (string) ($row['id'] ?? $row['admin_graphql_api_id'] ?? ''),
		'handle'       => $handle,
		'title'        => $title,
		'description'  => (string) ($row['body_html'] ?? $row['descriptionHtml'] ?? $row['description'] ?? ''),
		'price'        => bbb_society_product_importer_price($row),
		'image_url'    => bbb_society_product_importer_image_url($row),
		'download_url' => bbb_society_product_importer_download_url($row),
		'download_files' => $download_files,
		'society_free' => $is_digital && (isset($row['society_free']) ? bbb_society_product_importer_truthy($row['society_free']) : $default_free),
		'status'       => in_array((string) ($row['status'] ?? ''), array('publish', 'draft', 'private'), true) ? (string) $row['status'] : 'draft',
		'is_digital'   => $is_digital,
		'product_type' => (string) ($row['product_type'] ?? $row['productType'] ?? ''),
		'categories'   => bbb_society_product_importer_split_terms($row['categories'] ?? $row['collections'] ?? ''),
		'tags'         => bbb_society_product_importer_split_terms($row['tags'] ?? ''),
		'vendor'       => (string) ($row['vendor'] ?? ''),
		'shopify_url'  => esc_url_raw((string) ($row['shopify_url'] ?? $row['onlineStoreUrl'] ?? '')),
		'source_status' => (string) ($row['source_status'] ?? ''),
		'source_variant_id' => (string) ($row['source_variant_id'] ?? ''),
	);
}

function bbb_society_product_importer_upsert_product(array $product) {
	$platform = bbb_society_product_importer_platform();
	if ('' === $platform) {
		return new WP_Error('bbb_society_products_no_platform', 'Activate Easy Digital Downloads or WooCommerce before importing products.');
	}

	$handle = sanitize_title((string) ($product['handle'] ?? ''));
	$title  = trim((string) ($product['title'] ?? $handle));
	if ('' === $handle || '' === $title) {
		return new WP_Error('bbb_society_products_missing_handle', 'A product row is missing a handle or title.');
	}

	$post_type = 'edd' === $platform ? 'download' : 'product';
	$existing  = get_page_by_path($handle, OBJECT, $post_type);
	$post_id  = $existing instanceof WP_Post ? (int) $existing->ID : 0;
	$args     = array(
		'ID'           => $post_id,
		'post_type'    => $post_type,
		'post_status'  => (string) ($product['status'] ?? 'draft'),
		'post_name'    => $handle,
		'post_title'   => $title,
		'post_content' => wp_kses_post((string) ($product['description'] ?? '')),
	);

	$result = $post_id ? wp_update_post($args, true) : wp_insert_post($args, true);
	if (is_wp_error($result) || !$result) {
		return $result;
	}

	$post_id      = (int) $result;
	$price        = (string) ($product['price'] ?? '');
	$download_url = esc_url_raw((string) ($product['download_url'] ?? ''));
	$download_files = array_values(array_filter((array) ($product['download_files'] ?? array()), static fn($file): bool => is_array($file) && !empty($file['url'])));
	if ('' === $download_url && $download_files) {
		$download_url = esc_url_raw((string) ($download_files[0]['url'] ?? ''));
	}
	$image_url    = esc_url_raw((string) ($product['image_url'] ?? ''));
	$is_free      = !empty($product['society_free']);
	$is_digital   = !empty($product['is_digital']);

	update_post_meta($post_id, '_bbb_import_source', 'society_product_importer');
	update_post_meta($post_id, '_bbb_import_platform', $platform);
	update_post_meta($post_id, '_bbb_shopify_product_gid', (string) ($product['id'] ?? ''));
	update_post_meta($post_id, '_bbb_shopify_product_handle', $handle);
	update_post_meta($post_id, '_bbb_shopify_product_type', (string) ($product['product_type'] ?? ''));
	update_post_meta($post_id, '_bbb_shopify_vendor', (string) ($product['vendor'] ?? ''));
	update_post_meta($post_id, '_bbb_shopify_url', esc_url_raw((string) ($product['shopify_url'] ?? '')));
	update_post_meta($post_id, '_bbb_shopify_source_status', (string) ($product['source_status'] ?? ''));
	update_post_meta($post_id, '_bbb_shopify_variant_gid', (string) ($product['source_variant_id'] ?? ''));
	update_post_meta($post_id, '_bbb_is_digital_product', $is_digital ? 'yes' : 'no');
	update_post_meta($post_id, '_bbb_society_free_download', $is_free ? 'yes' : 'no');

	if ('edd' === $platform) {
		update_post_meta($post_id, '_edd_price', $price);
		update_post_meta($post_id, 'edd_price', $price);
		update_post_meta($post_id, '_edd_product_type', 'default');

		if ($download_files) {
			update_post_meta(
				$post_id,
				'edd_download_files',
				array_map(
					static fn(array $file): array => array(
						'name'      => (string) ($file['name'] ?? $title),
						'file'      => esc_url_raw((string) ($file['url'] ?? '')),
						'condition' => '0',
					),
					$download_files
				)
			);
		}
	} else {
		wp_set_object_terms($post_id, 'simple', 'product_type', false);
		update_post_meta($post_id, '_virtual', $is_digital ? 'yes' : 'no');
		update_post_meta($post_id, '_downloadable', '' !== $download_url ? 'yes' : 'no');
		update_post_meta($post_id, '_stock_status', 'instock');
		update_post_meta($post_id, '_sold_individually', 'yes');
		update_post_meta($post_id, '_regular_price', $price);
		update_post_meta($post_id, '_price', $price);
		update_post_meta($post_id, '_purchase_note', '' !== $download_url ? 'Your download will be available after checkout.' : '');
	}

	if ('' !== $download_url) {
		if ('woocommerce' === $platform) {
			$woo_files = array();
			foreach ($download_files as $file) {
				$url = esc_url_raw((string) ($file['url'] ?? ''));
				if ('' === $url) {
					continue;
				}

				$woo_files[md5($url)] = array(
					'name' => (string) ($file['name'] ?? $title),
					'file' => $url,
				);
			}

			if (!$woo_files && '' !== $download_url) {
				$woo_files[md5($download_url)] = array(
					'name' => $title,
					'file' => $download_url,
				);
			}

			update_post_meta(
				$post_id,
				'_downloadable_files',
				$woo_files
			);
			update_post_meta($post_id, '_download_limit', '');
			update_post_meta($post_id, '_download_expiry', '');
		}
		delete_post_meta($post_id, '_bbb_missing_download_url');
	} elseif ($is_digital) {
		delete_post_meta($post_id, '_downloadable_files');
		delete_post_meta($post_id, 'edd_download_files');
		update_post_meta($post_id, '_bbb_missing_download_url', 'yes');
	} else {
		delete_post_meta($post_id, '_downloadable_files');
		delete_post_meta($post_id, 'edd_download_files');
		delete_post_meta($post_id, '_bbb_missing_download_url');
	}

	if ('' !== $image_url) {
		update_post_meta($post_id, '_bbb_source_image_url', $image_url);
	}

	if (!empty($product['categories'])) {
		$taxonomy = 'edd' === $platform ? 'download_category' : 'product_cat';
		if (taxonomy_exists($taxonomy)) {
			wp_set_object_terms($post_id, (array) $product['categories'], $taxonomy, false);
		}
	}

	if (!empty($product['tags'])) {
		$taxonomy = 'edd' === $platform ? 'download_tag' : 'product_tag';
		if (taxonomy_exists($taxonomy)) {
			wp_set_object_terms($post_id, (array) $product['tags'], $taxonomy, false);
		}
	}

	return array(
		'post_id'      => $post_id,
		'handle'       => $handle,
		'downloadable' => '' !== $download_url,
		'missing_download' => $is_digital && '' === $download_url,
		'society_free' => $is_free,
		'platform'     => $platform,
	);
}

function bbb_society_product_importer_import(string $payload, string $format, bool $default_free) {
	$rows = 'csv' === $format
		? bbb_society_product_importer_rows_from_csv($payload)
		: bbb_society_product_importer_rows_from_json($payload);

	if (is_wp_error($rows)) {
		return $rows;
	}

	$results = array();
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}

		$product = bbb_society_product_importer_normalize_row($row, $default_free);
		$result  = bbb_society_product_importer_upsert_product($product);
		if (is_wp_error($result)) {
			return $result;
		}

		$results[] = $result;
	}

	return $results;
}

function bbb_society_product_importer_handle_request() {
	if (empty($_POST['bbb_society_products_import']) || !current_user_can('manage_options')) {
		return null;
	}

	check_admin_referer('bbb_society_products_import', 'bbb_society_products_import_nonce');

	$payload = '';
	if (!empty($_FILES['bbb_society_products_file']['tmp_name'])) {
		$payload = (string) file_get_contents((string) $_FILES['bbb_society_products_file']['tmp_name']);
	}

	if ('' === trim($payload) && !empty($_POST['bbb_society_products_text'])) {
		$payload = (string) wp_unslash($_POST['bbb_society_products_text']);
	}

	if ('' === trim($payload)) {
		return new WP_Error('bbb_society_products_empty', 'Upload or paste a Shopify products JSON or the CSV template.');
	}

	$format = 'csv' === ($_POST['bbb_society_products_format'] ?? '') ? 'csv' : 'json';

	return bbb_society_product_importer_import($payload, $format, !empty($_POST['bbb_society_products_default_free']));
}

function bbb_society_product_importer_admin_page(): void {
	$result   = bbb_society_product_importer_handle_request();
	$missing_downloads = is_array($result) ? count(array_filter($result, static fn($item): bool => !empty($item['missing_download']))) : 0;
	$platform = bbb_society_product_importer_platform();
	$product_post_type = 'edd' === $platform ? 'download' : 'product';
	$products = '' !== $platform ? get_posts(
		array(
			'post_type'      => $product_post_type,
			'post_status'    => 'any',
			'posts_per_page' => 50,
			'meta_key'       => '_bbb_import_source',
			'meta_value'     => 'society_product_importer',
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	) : array();
	?>
	<div class="wrap bbb-society-products-admin">
		<h1>Society Products</h1>
		<p class="description">Import Shopify digital products into Easy Digital Downloads or WooCommerce as clean draft products, preserve source metadata, attach download URLs, and mark which products paid Society members receive for free.</p>

		<?php if ('' === $platform) : ?>
			<div class="notice notice-warning"><p>Easy Digital Downloads or WooCommerce needs to be active before importing products.</p></div>
		<?php elseif ('edd' === $platform) : ?>
			<div class="notice notice-info"><p>Import target: Easy Digital Downloads.</p></div>
		<?php else : ?>
			<div class="notice notice-info"><p>Import target: WooCommerce.</p></div>
		<?php endif; ?>

		<?php if (is_wp_error($result)) : ?>
			<div class="notice notice-error"><p><?php echo esc_html($result->get_error_message()); ?></p></div>
		<?php elseif (is_array($result)) : ?>
			<div class="notice notice-success"><p><?php echo esc_html('Imported or updated ' . count($result) . ' products.'); ?> <?php echo $missing_downloads ? esc_html($missing_downloads . ' still need download URLs before publishing.') : ''; ?></p></div>
		<?php endif; ?>

		<form class="bbb-society-products-admin__import" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field('bbb_society_products_import', 'bbb_society_products_import_nonce'); ?>
			<input type="hidden" name="bbb_society_products_import" value="1">
			<h2>Import products</h2>
			<p>
				<label><input type="radio" name="bbb_society_products_format" value="json" checked> Shopify products JSON</label>
				<label style="margin-left:16px;"><input type="radio" name="bbb_society_products_format" value="csv"> CSV mapping</label>
			</p>
			<p><label for="bbb_society_products_file">Upload file</label></p>
			<input type="file" id="bbb_society_products_file" name="bbb_society_products_file" accept=".json,.csv,application/json,text/csv">
			<p><label for="bbb_society_products_text">Or paste JSON/CSV</label></p>
			<textarea id="bbb_society_products_text" name="bbb_society_products_text" rows="10" class="large-text code" placeholder="handle,title,price,download_url,image_url,society_free,status,id,product_type,categories,tags,vendor,shopify_url"></textarea>
			<p>
				<label><input type="checkbox" name="bbb_society_products_default_free" value="1" checked> default imported products to free for paid Society members</label>
			</p>
			<?php submit_button('Import society products'); ?>
		</form>

		<h2>CSV template</h2>
		<pre class="bbb-society-products-admin__template">handle,title,price,download_url,image_url,society_free,status,id,product_type,categories,tags,vendor,shopify_url
gothic-lace-calendar,gothic lace calendar,7.00,https://example.com/calendar.pdf,https://example.com/preview.jpg,yes,draft,gid://shopify/Product/123,Printable,Digital Products|Printable,kindle|dark romance,Bookish Babe,https://bybookishbabe.com/products/gothic-lace-calendar</pre>

		<h2>Imported products</h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Product</th>
					<th>Handle</th>
					<th>Price</th>
					<th>Download</th>
					<th>Paid members</th>
					<th>Type</th>
				</tr>
			</thead>
			<tbody>
				<?php if (!$products) : ?>
					<tr><td colspan="6">No Society products imported yet.</td></tr>
				<?php endif; ?>
				<?php foreach ($products as $product_post) : ?>
					<?php $files = get_post_meta($product_post->ID, '_downloadable_files', true); ?>
					<?php $edd_files = get_post_meta($product_post->ID, 'edd_download_files', true); ?>
					<?php $price = 'edd' === get_post_meta($product_post->ID, '_bbb_import_platform', true) ? get_post_meta($product_post->ID, '_edd_price', true) : get_post_meta($product_post->ID, '_regular_price', true); ?>
					<tr>
						<td><a href="<?php echo esc_url(get_edit_post_link($product_post->ID)); ?>"><?php echo esc_html(get_the_title($product_post)); ?></a></td>
						<td><code><?php echo esc_html((string) get_post_meta($product_post->ID, '_bbb_shopify_product_handle', true)); ?></code></td>
						<td><?php echo esc_html((string) $price); ?></td>
						<td><?php echo esc_html(!empty($files) || !empty($edd_files) ? 'attached' : ('yes' === get_post_meta($product_post->ID, '_bbb_missing_download_url', true) ? 'missing' : 'none')); ?></td>
						<td><?php echo esc_html('yes' === get_post_meta($product_post->ID, '_bbb_society_free_download', true) ? 'free' : 'paid'); ?></td>
						<td><?php echo esc_html((string) get_post_meta($product_post->ID, '_bbb_shopify_product_type', true)); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<style>
		.bbb-society-products-admin__import{max-width:880px;margin:18px 0 24px;padding:16px;background:#fff;border:1px solid #dcdcde;border-radius:6px}
		.bbb-society-products-admin__import h2{margin-top:0}
		.bbb-society-products-admin__template{max-width:880px;padding:14px;background:#111;color:#fff;white-space:pre-wrap}
	</style>
	<?php
}

function bbb_society_product_importer_admin_menu(): void {
	add_users_page(
		__('Society Products', 'bybookishbabe-shopify-port'),
		__('Society Products', 'bybookishbabe-shopify-port'),
		'manage_options',
		'bbb-society-products',
		'bbb_society_product_importer_admin_page'
	);
}
add_action('admin_menu', 'bbb_society_product_importer_admin_menu');

function bbb_society_product_is_member_free(int $product_id): bool {
	return 'yes' === get_post_meta($product_id, '_bbb_society_free_download', true)
		&& function_exists('bbb_reader_is_society')
		&& bbb_reader_is_society();
}

function bbb_society_product_cart_member_pricing($cart): void {
	if (is_admin() && !defined('DOING_AJAX')) {
		return;
	}

	if (!$cart || !function_exists('bbb_reader_is_society') || !bbb_reader_is_society()) {
		return;
	}

	foreach ((array) $cart->get_cart() as $cart_item) {
		$product = $cart_item['data'] ?? null;
		if (!is_object($product) || !method_exists($product, 'get_id') || !method_exists($product, 'set_price')) {
			continue;
		}

		if (bbb_society_product_is_member_free((int) $product->get_id())) {
			$product->set_price(0);
		}
	}
}
add_action('woocommerce_before_calculate_totals', 'bbb_society_product_cart_member_pricing', 20);

function bbb_society_product_price_html(string $price_html, $product): string {
	if (!is_object($product) || !method_exists($product, 'get_id')) {
		return $price_html;
	}

	if (bbb_society_product_is_member_free((int) $product->get_id())) {
		return '<span class="bbb-society-product-free">free for paid society members</span>';
	}

	return $price_html;
}
add_filter('woocommerce_get_price_html', 'bbb_society_product_price_html', 20, 2);

function bbb_society_product_add_to_cart_text(string $text, $product = null): string {
	if (is_object($product) && method_exists($product, 'get_id') && bbb_society_product_is_member_free((int) $product->get_id())) {
		return 'download free';
	}

	return $text;
}
add_filter('woocommerce_product_single_add_to_cart_text', 'bbb_society_product_add_to_cart_text', 20, 2);
add_filter('woocommerce_product_add_to_cart_text', 'bbb_society_product_add_to_cart_text', 20, 2);

function bbb_society_product_member_note(): void {
	global $product;
	if (!is_object($product) || !method_exists($product, 'get_id')) {
		return;
	}

	if ('yes' !== get_post_meta((int) $product->get_id(), '_bbb_society_free_download', true)) {
		return;
	}

	$message = bbb_society_product_is_member_free((int) $product->get_id())
		? 'included with your paid society membership.'
		: 'paid society members can download this for free.';

	echo '<p class="bbb-society-product-note">' . esc_html($message) . '</p>';
}
add_action('woocommerce_before_add_to_cart_form', 'bbb_society_product_member_note', 8);
