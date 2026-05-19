<?php
/**
 * Admin upload screen for Shopify JSON book imports.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'admin_menu',
	static function (): void {
		add_management_page(
			__('BBB Shopify Import', 'bybookishbabe-shopify-port'),
			__('BBB Shopify Import', 'bybookishbabe-shopify-port'),
			'manage_options',
			'bbb-shopify-import',
			'bbb_render_shopify_import_page'
		);
	}
);

add_action('admin_post_bbb_import_books_json', 'bbb_handle_books_json_import');
add_action('admin_post_bbb_import_newsletter_issues_json', 'bbb_handle_newsletter_issues_json_import');

function bbb_render_shopify_import_page(): void {
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to import BBB content.', 'bybookishbabe-shopify-port'));
	}

	$result = get_transient('bbb_shopify_import_result_' . get_current_user_id());
	if (is_array($result)) {
		delete_transient('bbb_shopify_import_result_' . get_current_user_id());
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e('BBB Shopify Import', 'bybookishbabe-shopify-port'); ?></h1>

		<?php if (is_array($result)) : ?>
			<div class="notice notice-<?php echo esc_attr($result['type'] ?? 'info'); ?> is-dismissible">
				<p><strong><?php echo esc_html($result['summary'] ?? 'Import finished.'); ?></strong></p>
				<?php if (!empty($result['messages']) && is_array($result['messages'])) : ?>
					<ul>
						<?php foreach (array_slice($result['messages'], 0, 25) as $message) : ?>
							<li><?php echo esc_html((string) $message); ?></li>
						<?php endforeach; ?>
					</ul>
					<?php if (count($result['messages']) > 25) : ?>
						<p><?php echo esc_html(sprintf(__('%d additional messages hidden.', 'bybookishbabe-shopify-port'), count($result['messages']) - 25)); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<p><?php esc_html_e('Upload the Shopify GraphQL JSON exports here. This is the custom BBB importer, not the default WordPress Importer.', 'bybookishbabe-shopify-port'); ?></p>

		<div style="display:grid;gap:24px;max-width:760px;">
			<div class="card" style="max-width:none;">
				<h2><?php esc_html_e('Books', 'bybookishbabe-shopify-port'); ?></h2>
				<p><?php esc_html_e('Use the sss_library books export. Import books before newsletter issues so issue references can resolve to book posts.', 'bybookishbabe-shopify-port'); ?></p>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="bbb_import_books_json">
					<?php wp_nonce_field('bbb_import_books_json'); ?>
					<input type="file" name="bbb_import_file" accept=".json,application/json" required>
					<?php submit_button(__('Import Books JSON', 'bybookishbabe-shopify-port')); ?>
				</form>
			</div>

			<div class="card" style="max-width:none;">
				<h2><?php esc_html_e('Newsletter Issues', 'bybookishbabe-shopify-port'); ?></h2>
				<p><?php esc_html_e('Use the newsletter_issue export that includes book or library_book references. This powers Weekly Obsession.', 'bybookishbabe-shopify-port'); ?></p>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="bbb_import_newsletter_issues_json">
					<?php wp_nonce_field('bbb_import_newsletter_issues_json'); ?>
					<input type="file" name="bbb_import_file" accept=".json,application/json" required>
					<?php submit_button(__('Import Newsletter Issues JSON', 'bybookishbabe-shopify-port')); ?>
				</form>
			</div>
		</div>
	</div>
	<?php
}

function bbb_handle_books_json_import(): void {
	bbb_handle_shopify_json_import('bbb_import_books_json', 'bbb_import_books_from_data', 'books');
}

function bbb_handle_newsletter_issues_json_import(): void {
	bbb_handle_shopify_json_import('bbb_import_newsletter_issues_json', 'bbb_import_newsletter_issues_from_data', 'newsletter issues');
}

function bbb_handle_shopify_json_import(string $nonce_action, string $import_callback, string $label): void {
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to import BBB content.', 'bybookishbabe-shopify-port'));
	}

	check_admin_referer($nonce_action);

	$result = array(
		'type'     => 'error',
		'summary'  => sprintf(__('Could not import %s.', 'bybookishbabe-shopify-port'), $label),
		'messages' => array(),
	);

	if (!isset($_FILES['bbb_import_file']) || !is_array($_FILES['bbb_import_file'])) {
		$result['messages'][] = __('No JSON file was uploaded.', 'bybookishbabe-shopify-port');
		bbb_redirect_shopify_import_result($result);
	}

	$file = $_FILES['bbb_import_file'];
	if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
		$result['messages'][] = sprintf(__('Upload failed with error code %d.', 'bybookishbabe-shopify-port'), (int) ($file['error'] ?? 0));
		bbb_redirect_shopify_import_result($result);
	}

	$tmp_name = (string) ($file['tmp_name'] ?? '');
	$json     = $tmp_name !== '' ? file_get_contents($tmp_name) : false;
	$data     = is_string($json) ? json_decode($json, true) : null;

	if (!is_array($data)) {
		$result['messages'][] = __('The uploaded file is not valid JSON.', 'bybookishbabe-shopify-port');
		bbb_redirect_shopify_import_result($result);
	}

	if (!function_exists($import_callback)) {
		$result['messages'][] = __('The BBB import callback is unavailable.', 'bybookishbabe-shopify-port');
		bbb_redirect_shopify_import_result($result);
	}

	$imported = call_user_func($import_callback, $data);
	$count    = is_array($imported) ? (int) ($imported['count'] ?? 0) : 0;
	$messages = is_array($imported) ? (array) ($imported['messages'] ?? array()) : array();

	bbb_redirect_shopify_import_result(
		array(
			'type'     => 'success',
			'summary'  => sprintf(__('Imported %1$d %2$s.', 'bybookishbabe-shopify-port'), $count, $label),
			'messages' => $messages,
		)
	);
}

function bbb_redirect_shopify_import_result(array $result): void {
	set_transient('bbb_shopify_import_result_' . get_current_user_id(), $result, MINUTE_IN_SECONDS);

	wp_safe_redirect(
		add_query_arg(
			array(
				'page' => 'bbb-shopify-import',
			),
			admin_url('tools.php')
		)
	);
	exit;
}
