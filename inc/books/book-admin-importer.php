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
add_action('admin_post_bbb_import_series_json', 'bbb_handle_series_json_import');
add_action('admin_post_bbb_import_newsletter_issues_json', 'bbb_handle_newsletter_issues_json_import');
add_action('admin_post_bbb_import_blog_post_dates_json', 'bbb_handle_blog_post_dates_json_import');

function bbb_render_shopify_import_page(): void {
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to import BBB content.', 'bybookishbabe-shopify-port'));
	}

	$result = get_transient('bbb_shopify_import_result_' . get_current_user_id());
	if (is_array($result)) {
		delete_transient('bbb_shopify_import_result_' . get_current_user_id());
	}
	$status = bbb_get_shopify_import_status();
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
				<h2><?php esc_html_e('Current Import Status', 'bybookishbabe-shopify-port'); ?></h2>
				<p>
					<?php
					echo esc_html(
						sprintf(
							__('Books: %1$d total. Books with shelves: %2$d. Shelf terms: %3$d.', 'bybookishbabe-shopify-port'),
							$status['book_count'],
							$status['books_with_shelves'],
							$status['shelf_count']
						)
					);
					?>
				</p>
				<p>
					<?php
					echo esc_html(
						sprintf(
							__('Books with spice: %1$d. Starter Pack books: %2$d. Society Classics books: %3$d.', 'bybookishbabe-shopify-port'),
							$status['books_with_spice'],
							$status['starter_count'],
							$status['top_shelf_count']
						)
					);
					?>
				</p>
				<?php if ($status['book_count'] > 0 && 0 === $status['books_with_shelves']) : ?>
					<p style="color:#b32d2e;"><strong><?php esc_html_e('No imported books currently have shelves attached. Re-import the Books JSON after updating the theme.', 'bybookishbabe-shopify-port'); ?></strong></p>
				<?php elseif ($status['book_count'] > $status['books_with_shelves']) : ?>
					<p style="color:#996800;"><strong><?php echo esc_html(sprintf(__('%d books are missing shelves. Re-importing the Books JSON will repair any shelf data present in Shopify.', 'bybookishbabe-shopify-port'), $status['book_count'] - $status['books_with_shelves'])); ?></strong></p>
				<?php endif; ?>
				<?php if (!empty($status['shelf_names'])) : ?>
					<p><?php echo esc_html__('Shelves found: ', 'bybookishbabe-shopify-port') . esc_html(implode(', ', $status['shelf_names'])); ?></p>
				<?php endif; ?>
				<p>
					<?php
					echo esc_html(
						sprintf(
							__('Newsletter issues: %1$d total. Latest imported: %2$s.', 'bybookishbabe-shopify-port'),
							$status['newsletter_issue_count'],
							$status['latest_newsletter_issue']
						)
					);
					?>
				</p>
				<p>
					<?php
					echo esc_html(
						sprintf(
							__('Series: %d total.', 'bybookishbabe-shopify-port'),
							$status['series_count']
						)
					);
					?>
				</p>
			</div>

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
				<h2><?php esc_html_e('Series', 'bybookishbabe-shopify-port'); ?></h2>
				<p><?php esc_html_e('Use the sss_series export. This creates/upserts Series records and stores every Shopify metafield for future editing.', 'bybookishbabe-shopify-port'); ?></p>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="bbb_import_series_json">
					<?php wp_nonce_field('bbb_import_series_json'); ?>
					<input type="file" name="bbb_import_file" accept=".json,application/json">
					<p>
						<label>
							<input type="checkbox" name="bbb_use_theme_file" value="1">
							<?php esc_html_e('Use bundled theme export if no file is uploaded', 'bybookishbabe-shopify-port'); ?>
						</label>
					</p>
					<?php submit_button(__('Import Series JSON', 'bybookishbabe-shopify-port')); ?>
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

			<div class="card" style="max-width:none;">
				<h2><?php esc_html_e('Blog Post Dates', 'bybookishbabe-shopify-port'); ?></h2>
				<p><?php esc_html_e('Use Shopify blog-articles.json from the content export. This updates existing WordPress posts by slug so archive and post dates match Shopify.', 'bybookishbabe-shopify-port'); ?></p>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="bbb_import_blog_post_dates_json">
					<?php wp_nonce_field('bbb_import_blog_post_dates_json'); ?>
					<input type="file" name="bbb_import_file" accept=".json,application/json" required>
					<?php submit_button(__('Reload Blog Post Dates JSON', 'bybookishbabe-shopify-port')); ?>
				</form>
			</div>
		</div>
	</div>
	<?php
}

function bbb_get_shopify_import_status(): array {
	$book_ids = get_posts(
		array(
			'post_type'      => 'bbb_book',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$books_with_shelves = 0;
	$books_with_spice   = 0;
	$starter_count      = 0;
	$top_shelf_count    = 0;
	foreach ($book_ids as $book_id) {
		if ((int) get_post_meta((int) $book_id, '_bbb_spice', true) > 0) {
			++$books_with_spice;
		}

		if (function_exists('sss_book_is_starter_pack') && sss_book_is_starter_pack((int) $book_id)) {
			++$starter_count;
		}

		if (function_exists('sss_book_is_top_shelf') && sss_book_is_top_shelf((int) $book_id)) {
			++$top_shelf_count;
		}

		$terms = get_the_terms((int) $book_id, 'bbb_shelf');
		if ($terms && !is_wp_error($terms)) {
			++$books_with_shelves;
			continue;
		}

		if ((string) get_post_meta((int) $book_id, '_bbb_shelf_name', true) !== '') {
			++$books_with_shelves;
		}
	}

	$shelves     = get_terms(array('taxonomy' => 'bbb_shelf', 'hide_empty' => false));
	$shelf_names = array();
	if ($shelves && !is_wp_error($shelves)) {
		foreach ($shelves as $shelf) {
			if ($shelf instanceof WP_Term) {
				$shelf_names[] = $shelf->name;
			}
		}
	}

	$newsletter_issues = post_type_exists('newsletter_issue')
		? get_posts(
			array(
				'post_type'      => 'newsletter_issue',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		)
		: array();
	$latest_newsletter_issue = !empty($newsletter_issues[0]) && $newsletter_issues[0] instanceof WP_Post
		? get_the_title($newsletter_issues[0]) . ' (' . get_post_meta($newsletter_issues[0]->ID, '_issue_publish_date', true) . ')'
		: __('none', 'bybookishbabe-shopify-port');

	return array(
		'book_count'         => count($book_ids),
		'books_with_shelves' => $books_with_shelves,
		'books_with_spice'   => $books_with_spice,
		'starter_count'      => $starter_count,
		'top_shelf_count'    => $top_shelf_count,
		'shelf_count'        => count($shelf_names),
		'shelf_names'        => array_slice($shelf_names, 0, 12),
		'newsletter_issue_count' => post_type_exists('newsletter_issue') ? (int) wp_count_posts('newsletter_issue')->publish : 0,
		'latest_newsletter_issue' => $latest_newsletter_issue,
		'series_count'       => post_type_exists('sss_series') ? (int) wp_count_posts('sss_series')->publish : 0,
	);
}

function bbb_handle_books_json_import(): void {
	bbb_handle_shopify_json_import('bbb_import_books_json', 'bbb_import_books_from_data', 'books');
}

function bbb_handle_series_json_import(): void {
	bbb_handle_shopify_json_import('bbb_import_series_json', 'bbb_import_series_from_data', 'series');
}

function bbb_handle_newsletter_issues_json_import(): void {
	bbb_handle_shopify_json_import('bbb_import_newsletter_issues_json', 'bbb_import_newsletter_issues_from_data', 'newsletter issues');
}

function bbb_handle_blog_post_dates_json_import(): void {
	bbb_handle_shopify_json_import('bbb_import_blog_post_dates_json', 'bbb_import_blog_post_dates_from_data', 'blog post dates');
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

	$json = false;
	$file = isset($_FILES['bbb_import_file']) && is_array($_FILES['bbb_import_file']) ? $_FILES['bbb_import_file'] : array();
	if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
		$tmp_name = (string) ($file['tmp_name'] ?? '');
		$json     = $tmp_name !== '' ? file_get_contents($tmp_name) : false;
	} elseif (!empty($_POST['bbb_use_theme_file']) && 'bbb_import_series_json' === $nonce_action) {
		$theme_file = get_theme_file_path('firstpass/migration/exports/metaobjects/sss_series.json');
		$json       = is_readable($theme_file) ? file_get_contents($theme_file) : false;
	} else {
		$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
		$result['messages'][] = UPLOAD_ERR_NO_FILE === $error
			? __('No JSON file was uploaded.', 'bybookishbabe-shopify-port')
			: sprintf(__('Upload failed with error code %d.', 'bybookishbabe-shopify-port'), $error);
		bbb_redirect_shopify_import_result($result);
	}

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
			'type'     => $count > 0 ? 'success' : 'warning',
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
