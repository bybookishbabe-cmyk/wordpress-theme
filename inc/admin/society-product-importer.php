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

function bbb_society_product_importer_array_is_list(array $value): bool {
	if (function_exists('array_is_list')) {
		return array_is_list($value);
	}

	return array_keys($value) === range(0, count($value) - 1);
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

	return array(
		'id'           => (string) ($row['id'] ?? $row['admin_graphql_api_id'] ?? ''),
		'handle'       => $handle,
		'title'        => $title,
		'description'  => (string) ($row['body_html'] ?? $row['descriptionHtml'] ?? $row['description'] ?? ''),
		'price'        => bbb_society_product_importer_price($row),
		'image_url'    => bbb_society_product_importer_image_url($row),
		'download_url' => bbb_society_product_importer_download_url($row),
		'society_free' => isset($row['society_free']) ? bbb_society_product_importer_truthy($row['society_free']) : $default_free,
		'status'       => in_array((string) ($row['status'] ?? ''), array('publish', 'draft', 'private'), true) ? (string) $row['status'] : 'draft',
	);
}

function bbb_society_product_importer_upsert_product(array $product) {
	if (!post_type_exists('product')) {
		return new WP_Error('bbb_society_products_no_woo', 'WooCommerce needs to be active before importing products.');
	}

	$handle = sanitize_title((string) ($product['handle'] ?? ''));
	$title  = trim((string) ($product['title'] ?? $handle));
	if ('' === $handle || '' === $title) {
		return new WP_Error('bbb_society_products_missing_handle', 'A product row is missing a handle or title.');
	}

	$existing = get_page_by_path($handle, OBJECT, 'product');
	$post_id  = $existing instanceof WP_Post ? (int) $existing->ID : 0;
	$args     = array(
		'ID'           => $post_id,
		'post_type'    => 'product',
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
	$image_url    = esc_url_raw((string) ($product['image_url'] ?? ''));
	$is_free      = !empty($product['society_free']);

	wp_set_object_terms($post_id, 'simple', 'product_type', false);
	update_post_meta($post_id, '_bbb_import_source', 'society_product_importer');
	update_post_meta($post_id, '_bbb_shopify_product_gid', (string) ($product['id'] ?? ''));
	update_post_meta($post_id, '_bbb_shopify_product_handle', $handle);
	update_post_meta($post_id, '_bbb_society_free_download', $is_free ? 'yes' : 'no');
	update_post_meta($post_id, '_virtual', 'yes');
	update_post_meta($post_id, '_downloadable', '' !== $download_url ? 'yes' : 'no');
	update_post_meta($post_id, '_stock_status', 'instock');
	update_post_meta($post_id, '_sold_individually', 'yes');
	update_post_meta($post_id, '_regular_price', $price);
	update_post_meta($post_id, '_price', $price);

	if ('' !== $download_url) {
		$file_key = md5($download_url);
		update_post_meta(
			$post_id,
			'_downloadable_files',
			array(
				$file_key => array(
					'name' => $title,
					'file' => $download_url,
				),
			)
		);
		update_post_meta($post_id, '_download_limit', '');
		update_post_meta($post_id, '_download_expiry', '');
	}

	if ('' !== $image_url) {
		update_post_meta($post_id, '_bbb_source_image_url', $image_url);
	}

	return array(
		'post_id'      => $post_id,
		'handle'       => $handle,
		'downloadable' => '' !== $download_url,
		'society_free' => $is_free,
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
	$products = post_type_exists('product') ? get_posts(
		array(
			'post_type'      => 'product',
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
		<p class="description">Import Shopify digital products into WooCommerce, attach download URLs, and mark which products paid Society members receive for free.</p>

		<?php if (!post_type_exists('product')) : ?>
			<div class="notice notice-warning"><p>WooCommerce is not active yet. Activate WooCommerce before importing products.</p></div>
		<?php endif; ?>

		<?php if (is_wp_error($result)) : ?>
			<div class="notice notice-error"><p><?php echo esc_html($result->get_error_message()); ?></p></div>
		<?php elseif (is_array($result)) : ?>
			<div class="notice notice-success"><p><?php echo esc_html('Imported or updated ' . count($result) . ' products.'); ?></p></div>
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
			<textarea id="bbb_society_products_text" name="bbb_society_products_text" rows="10" class="large-text code" placeholder="handle,title,price,download_url,image_url,society_free,status"></textarea>
			<p>
				<label><input type="checkbox" name="bbb_society_products_default_free" value="1" checked> default imported products to free for paid Society members</label>
			</p>
			<?php submit_button('Import society products'); ?>
		</form>

		<h2>CSV template</h2>
		<pre class="bbb-society-products-admin__template">handle,title,price,download_url,image_url,society_free,status
gothic-lace-calendar,gothic lace calendar,7.00,https://example.com/calendar.pdf,https://example.com/preview.jpg,yes,draft</pre>

		<h2>Imported products</h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Product</th>
					<th>Handle</th>
					<th>Price</th>
					<th>Download</th>
					<th>Paid members</th>
				</tr>
			</thead>
			<tbody>
				<?php if (!$products) : ?>
					<tr><td colspan="5">No Society products imported yet.</td></tr>
				<?php endif; ?>
				<?php foreach ($products as $product_post) : ?>
					<?php $files = get_post_meta($product_post->ID, '_downloadable_files', true); ?>
					<tr>
						<td><a href="<?php echo esc_url(get_edit_post_link($product_post->ID)); ?>"><?php echo esc_html(get_the_title($product_post)); ?></a></td>
						<td><code><?php echo esc_html((string) get_post_meta($product_post->ID, '_bbb_shopify_product_handle', true)); ?></code></td>
						<td><?php echo esc_html((string) get_post_meta($product_post->ID, '_regular_price', true)); ?></td>
						<td><?php echo esc_html(!empty($files) ? 'attached' : 'missing'); ?></td>
						<td><?php echo esc_html('yes' === get_post_meta($product_post->ID, '_bbb_society_free_download', true) ? 'free' : 'paid'); ?></td>
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
