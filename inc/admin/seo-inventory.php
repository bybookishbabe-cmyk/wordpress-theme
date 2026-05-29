<?php
/**
 * Admin SEO inventory for posts, pages, books, and products.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_seo_inventory_strlen(string $value): int {
	return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function bbb_seo_inventory_clean(string $value): string {
	$value = wp_strip_all_tags(strip_shortcodes($value), true);
	$value = preg_replace('/\s+/', ' ', $value);

	return trim((string) $value);
}

function bbb_seo_inventory_post_types(): array {
	$objects = get_post_types(array('show_ui' => true), 'objects');
	$exclude = array(
		'attachment',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'revision',
		'user_request',
		'wp_block',
		'wp_font_face',
		'wp_font_family',
		'wp_global_styles',
		'wp_navigation',
		'wp_template',
		'wp_template_part',
	);

	$post_types = array();
	foreach ($objects as $post_type => $object) {
		if (in_array($post_type, $exclude, true)) {
			continue;
		}

		$post_types[$post_type] = $object->labels->name ?: $post_type;
	}

	foreach (array('post' => 'Posts', 'page' => 'Pages', 'bbb_book' => 'Books', 'sss_book' => 'SSS Books', 'download' => 'Downloads', 'product' => 'Products', 'sss_series' => 'Series') as $post_type => $label) {
		if (post_type_exists($post_type)) {
			$post_types[$post_type] = $label;
		}
	}

	asort($post_types);

	return $post_types;
}

function bbb_seo_inventory_statuses(): array {
	return array(
		'publish' => 'Published',
		'draft'   => 'Draft',
		'pending' => 'Pending',
		'future'  => 'Scheduled',
		'private' => 'Private',
	);
}

function bbb_seo_inventory_first_meta(int $post_id, array $keys): string {
	foreach ($keys as $key) {
		$value = bbb_seo_inventory_clean((string) get_post_meta($post_id, $key, true));
		if ('' !== $value) {
			return $value;
		}
	}

	return '';
}

function bbb_seo_inventory_title(int $post_id): array {
	$title = bbb_seo_inventory_first_meta(
		$post_id,
		array(
			'rank_math_title',
			'_yoast_wpseo_title',
			'rank_math_facebook_title',
			'_yoast_wpseo_opengraph-title',
		)
	);

	if ('' !== $title) {
		return array('value' => $title, 'source' => 'SEO field');
	}

	if ('bbb_book' === get_post_type($post_id) && function_exists('bbb_book_seo_title')) {
		$title = bbb_seo_inventory_clean(bbb_book_seo_title($post_id));
		if ('' !== $title) {
			return array('value' => $title, 'source' => 'Book default');
		}
	}

	$fallback = bbb_seo_inventory_clean(get_the_title($post_id));

	return array('value' => $fallback, 'source' => 'Post title');
}

function bbb_seo_inventory_description(int $post_id): array {
	$description = bbb_seo_inventory_first_meta(
		$post_id,
		array(
			'rank_math_description',
			'_yoast_wpseo_metadesc',
			'rank_math_facebook_description',
			'_yoast_wpseo_opengraph-description',
		)
	);

	if ('' !== $description) {
		return array('value' => $description, 'source' => 'SEO field');
	}

	if ('bbb_book' === get_post_type($post_id) && function_exists('bbb_book_seo_description')) {
		$description = bbb_seo_inventory_clean(bbb_book_seo_description($post_id));
		if ('' !== $description) {
			return array('value' => $description, 'source' => 'Book default');
		}
	}

	$excerpt = bbb_seo_inventory_clean((string) get_the_excerpt($post_id));
	if ('' !== $excerpt) {
		return array('value' => wp_trim_words($excerpt, 30, ''), 'source' => 'Excerpt');
	}

	return array('value' => '', 'source' => 'Missing');
}

function bbb_seo_inventory_focus_keyword(int $post_id): string {
	$keyword = bbb_seo_inventory_first_meta($post_id, array('rank_math_focus_keyword', '_yoast_wpseo_focuskw'));
	if ('' !== $keyword) {
		return $keyword;
	}

	if ('bbb_book' === get_post_type($post_id) && function_exists('bbb_book_seo_focus_keyword')) {
		return bbb_seo_inventory_clean(bbb_book_seo_focus_keyword($post_id));
	}

	return '';
}

function bbb_seo_inventory_terms(int $post_id): array {
	$post_type  = (string) get_post_type($post_id);
	$taxonomies = get_object_taxonomies($post_type, 'objects');
	$groups     = array();

	foreach ($taxonomies as $taxonomy => $object) {
		if (empty($object->show_ui) && empty($object->public)) {
			continue;
		}

		$terms = get_the_terms($post_id, $taxonomy);
		if (!is_array($terms) || !$terms) {
			continue;
		}

		$names = array();
		foreach ($terms as $term) {
			if ($term instanceof WP_Term) {
				$names[] = $term->name;
			}
		}

		if ($names) {
			$groups[] = ($object->labels->name ?: $taxonomy) . ': ' . implode(', ', $names);
		}
	}

	return $groups;
}

function bbb_seo_inventory_row(int $post_id): array {
	$title       = bbb_seo_inventory_title($post_id);
	$description = bbb_seo_inventory_description($post_id);
	$keyword     = bbb_seo_inventory_focus_keyword($post_id);
	$seo_title   = (string) $title['value'];
	$seo_desc    = (string) $description['value'];
	$warnings    = array();
	$post_type   = (string) get_post_type($post_id);
	$post_object = get_post_type_object($post_type);
	$type_label  = $post_object ? $post_object->labels->singular_name : $post_type;

	if ('' === $seo_title) {
		$warnings[] = 'missing title';
	} elseif (bbb_seo_inventory_strlen($seo_title) > 65) {
		$warnings[] = 'long title';
	}

	if ('' === $seo_desc) {
		$warnings[] = 'missing description';
	} elseif (bbb_seo_inventory_strlen($seo_desc) < 120) {
		$warnings[] = 'short description';
	} elseif (bbb_seo_inventory_strlen($seo_desc) > 160) {
		$warnings[] = 'long description';
	}

	if ('' === $keyword) {
		$warnings[] = 'missing keyword';
	}

	return array(
		'id'                 => $post_id,
		'post_type'          => $post_type,
		'post_type_label'    => $type_label,
		'status'             => get_post_status($post_id) ?: '',
		'title'              => bbb_seo_inventory_clean(get_the_title($post_id)),
		'url'                => get_permalink($post_id),
		'seo_title'          => $seo_title,
		'seo_title_source'   => (string) $title['source'],
		'seo_title_length'   => bbb_seo_inventory_strlen($seo_title),
		'description'        => $seo_desc,
		'description_source' => (string) $description['source'],
		'description_length' => bbb_seo_inventory_strlen($seo_desc),
		'focus_keyword'      => $keyword,
		'terms'              => implode(' | ', bbb_seo_inventory_terms($post_id)),
		'warnings'           => implode(', ', $warnings),
		'edit_url'           => get_edit_post_link($post_id, ''),
	);
}

function bbb_seo_inventory_query_args(bool $for_export = false): array {
	$post_types = bbb_seo_inventory_post_types();
	$statuses   = bbb_seo_inventory_statuses();
	$type       = isset($_GET['bbb_seo_type']) ? sanitize_key((string) wp_unslash($_GET['bbb_seo_type'])) : '';
	$status     = isset($_GET['bbb_seo_status']) ? sanitize_key((string) wp_unslash($_GET['bbb_seo_status'])) : 'publish';
	$search     = isset($_GET['s']) ? sanitize_text_field((string) wp_unslash($_GET['s'])) : '';
	$paged      = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;

	return array(
		'post_type'      => isset($post_types[$type]) ? array($type) : array_keys($post_types),
		'post_status'    => isset($statuses[$status]) ? array($status) : array_keys($statuses),
		'posts_per_page' => $for_export ? -1 : 50,
		'paged'          => $for_export ? 1 : $paged,
		's'              => $search,
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'fields'         => 'ids',
	);
}

function bbb_seo_inventory_save_meta(): void {
	if (empty($_POST['bbb_seo_inventory_save']) || !current_user_can('manage_options')) {
		return;
	}

	$post_id = isset($_POST['bbb_seo_post_id']) ? absint($_POST['bbb_seo_post_id']) : 0;
	if (!$post_id || !current_user_can('edit_post', $post_id)) {
		wp_die(esc_html__('You do not have permission to edit this item.', 'bybookishbabe-shopify-port'));
	}

	check_admin_referer('bbb_seo_inventory_save_' . $post_id, 'bbb_seo_inventory_nonce');

	$seo_title       = isset($_POST['bbb_seo_title']) ? bbb_seo_inventory_clean((string) wp_unslash($_POST['bbb_seo_title'])) : '';
	$seo_description = isset($_POST['bbb_seo_description']) ? bbb_seo_inventory_clean((string) wp_unslash($_POST['bbb_seo_description'])) : '';
	$title_keys      = array('rank_math_title', 'rank_math_facebook_title', 'rank_math_twitter_title', '_yoast_wpseo_title', '_yoast_wpseo_opengraph-title', '_yoast_wpseo_twitter-title');
	$description_keys = array('rank_math_description', 'rank_math_facebook_description', 'rank_math_twitter_description', '_yoast_wpseo_metadesc', '_yoast_wpseo_opengraph-description', '_yoast_wpseo_twitter-description');

	foreach ($title_keys as $key) {
		if ('' === $seo_title) {
			delete_post_meta($post_id, $key);
		} else {
			update_post_meta($post_id, $key, $seo_title);
		}
	}

	foreach ($description_keys as $key) {
		if ('' === $seo_description) {
			delete_post_meta($post_id, $key);
		} else {
			update_post_meta($post_id, $key, $seo_description);
		}
	}

	clean_post_cache($post_id);

	$redirect = wp_get_referer() ?: admin_url('tools.php?page=bbb-seo-inventory');
	$redirect = remove_query_arg(array('bbb_seo_saved'), $redirect);
	wp_safe_redirect(add_query_arg('bbb_seo_saved', $post_id, $redirect));
	exit;
}
add_action('admin_init', 'bbb_seo_inventory_save_meta');

function bbb_seo_inventory_export(): void {
	if (empty($_GET['bbb_seo_export']) || !current_user_can('manage_options')) {
		return;
	}

	check_admin_referer('bbb_seo_inventory_export');

	$query = new WP_Query(bbb_seo_inventory_query_args(true));
	$rows  = array_map('bbb_seo_inventory_row', array_map('absint', $query->posts));

	nocache_headers();
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=bybookishbabe-seo-inventory-' . gmdate('Y-m-d') . '.csv');

	$output = fopen('php://output', 'w');
	if (false === $output) {
		exit;
	}

	fputcsv($output, array('ID', 'Type', 'Post Type', 'Status', 'Content Title', 'URL', 'SEO Title', 'SEO Title Source', 'SEO Title Length', 'Description', 'Description Source', 'Description Length', 'Focus Keyword', 'Tags / Terms', 'Warnings', 'Edit URL'));
	foreach ($rows as $row) {
		fputcsv(
			$output,
			array(
				$row['id'],
				$row['post_type_label'],
				$row['post_type'],
				$row['status'],
				$row['title'],
				$row['url'],
				$row['seo_title'],
				$row['seo_title_source'],
				$row['seo_title_length'],
				$row['description'],
				$row['description_source'],
				$row['description_length'],
				$row['focus_keyword'],
				$row['terms'],
				$row['warnings'],
				$row['edit_url'],
			)
		);
	}

	exit;
}
add_action('admin_init', 'bbb_seo_inventory_export');

function bbb_seo_inventory_admin_page(): void {
	$post_types      = bbb_seo_inventory_post_types();
	$statuses        = bbb_seo_inventory_statuses();
	$selected_type   = isset($_GET['bbb_seo_type']) ? sanitize_key((string) wp_unslash($_GET['bbb_seo_type'])) : '';
	$selected_status = isset($_GET['bbb_seo_status']) ? sanitize_key((string) wp_unslash($_GET['bbb_seo_status'])) : 'publish';
	$search          = isset($_GET['s']) ? sanitize_text_field((string) wp_unslash($_GET['s'])) : '';
	$query           = new WP_Query(bbb_seo_inventory_query_args());
	$rows            = array_map('bbb_seo_inventory_row', array_map('absint', $query->posts));
	$total_pages     = max(1, (int) $query->max_num_pages);
	$current_page    = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
	$export_url      = wp_nonce_url(
		add_query_arg(
			array(
				'page'           => 'bbb-seo-inventory',
				'bbb_seo_type'   => $selected_type,
				'bbb_seo_status' => $selected_status,
				's'              => $search,
				'bbb_seo_export' => '1',
			),
			admin_url('tools.php')
		),
		'bbb_seo_inventory_export'
	);
	?>
	<div class="wrap bbb-seo-inventory">
		<h1>SEO Inventory</h1>
		<p class="description">Scan every page, post, book, product, and series for SEO title, description, focus keyword, and WordPress tags/terms.</p>
		<?php if (!empty($_GET['bbb_seo_saved'])) : ?>
			<div class="notice notice-success is-dismissible"><p>SEO title and description saved.</p></div>
		<?php endif; ?>

		<form method="get" class="bbb-seo-inventory__filters">
			<input type="hidden" name="page" value="bbb-seo-inventory">
			<label for="bbb_seo_type">Type</label>
			<select id="bbb_seo_type" name="bbb_seo_type">
				<option value="">All content</option>
				<?php foreach ($post_types as $post_type => $label) : ?>
					<option value="<?php echo esc_attr($post_type); ?>" <?php selected($selected_type, $post_type); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>

			<label for="bbb_seo_status">Status</label>
			<select id="bbb_seo_status" name="bbb_seo_status">
				<option value="">All statuses</option>
				<?php foreach ($statuses as $status => $label) : ?>
					<option value="<?php echo esc_attr($status); ?>" <?php selected($selected_status, $status); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>

			<label for="bbb_seo_search">Search</label>
			<input id="bbb_seo_search" type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Title or content">
			<?php submit_button('Filter', 'secondary', '', false); ?>
			<a class="button" href="<?php echo esc_url($export_url); ?>">Export CSV</a>
		</form>

		<p><strong><?php echo esc_html(number_format_i18n((int) $query->found_posts)); ?></strong> matching items. Showing newest modified first.</p>

		<div class="bbb-seo-inventory__table-scroll" role="region" aria-label="SEO inventory table" tabindex="0">
			<table class="widefat striped bbb-seo-inventory__table">
				<thead>
					<tr>
						<th>Content</th>
						<th>Type</th>
						<th>URL</th>
						<th>SEO title</th>
						<th>Description</th>
						<th>Keyword</th>
						<th>Tags / terms</th>
						<th>Flags</th>
						<th>Save</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!$rows) : ?>
						<tr><td colspan="9">No matching content found.</td></tr>
					<?php endif; ?>
					<?php foreach ($rows as $row) : ?>
						<?php $form_id = 'bbb-seo-edit-' . (int) $row['id']; ?>
						<tr>
							<td>
								<form id="<?php echo esc_attr($form_id); ?>" method="post">
									<input type="hidden" name="bbb_seo_inventory_save" value="1">
									<input type="hidden" name="bbb_seo_post_id" value="<?php echo esc_attr((string) $row['id']); ?>">
									<?php wp_nonce_field('bbb_seo_inventory_save_' . (int) $row['id'], 'bbb_seo_inventory_nonce'); ?>
								</form>
								<strong><a href="<?php echo esc_url((string) $row['edit_url']); ?>"><?php echo esc_html((string) $row['title']); ?></a></strong>
								<div class="bbb-seo-inventory__meta">
									<?php echo esc_html((string) $row['status']); ?> · ID <?php echo esc_html((string) $row['id']); ?>
								</div>
							</td>
							<td>
								<strong><?php echo esc_html((string) $row['post_type_label']); ?></strong>
								<div class="bbb-seo-inventory__meta"><?php echo esc_html((string) $row['post_type']); ?></div>
							</td>
							<td>
								<?php if (!empty($row['url'])) : ?>
									<a href="<?php echo esc_url((string) $row['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html((string) $row['url']); ?></a>
								<?php else : ?>
									<span class="bbb-seo-inventory__missing">Missing</span>
								<?php endif; ?>
							</td>
							<td>
								<label class="screen-reader-text" for="bbb_seo_title_<?php echo esc_attr((string) $row['id']); ?>">SEO title</label>
								<textarea id="bbb_seo_title_<?php echo esc_attr((string) $row['id']); ?>" form="<?php echo esc_attr($form_id); ?>" name="bbb_seo_title" rows="3" class="bbb-seo-inventory__field"><?php echo esc_textarea((string) $row['seo_title']); ?></textarea>
								<div class="bbb-seo-inventory__meta"><?php echo esc_html((string) $row['seo_title_source']); ?> · <?php echo esc_html((string) $row['seo_title_length']); ?> chars</div>
							</td>
							<td>
								<label class="screen-reader-text" for="bbb_seo_description_<?php echo esc_attr((string) $row['id']); ?>">SEO description</label>
								<textarea id="bbb_seo_description_<?php echo esc_attr((string) $row['id']); ?>" form="<?php echo esc_attr($form_id); ?>" name="bbb_seo_description" rows="4" class="bbb-seo-inventory__field"><?php echo esc_textarea((string) $row['description']); ?></textarea>
								<div class="bbb-seo-inventory__meta"><?php echo esc_html((string) $row['description_source']); ?> · <?php echo esc_html((string) $row['description_length']); ?> chars</div>
							</td>
							<td><?php echo '' !== $row['focus_keyword'] ? esc_html((string) $row['focus_keyword']) : '<span class="bbb-seo-inventory__missing">Missing</span>'; ?></td>
							<td><?php echo '' !== $row['terms'] ? esc_html((string) $row['terms']) : '<span class="bbb-seo-inventory__muted">None</span>'; ?></td>
							<td><?php echo '' !== $row['warnings'] ? esc_html((string) $row['warnings']) : '<span class="bbb-seo-inventory__ok">Looks okay</span>'; ?></td>
							<td><button form="<?php echo esc_attr($form_id); ?>" type="submit" class="button button-primary">Save</button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ($total_pages > 1) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg('paged', '%#%'),
								'format'    => '',
								'current'   => $current_page,
								'total'     => $total_pages,
								'prev_text' => '&lsaquo;',
								'next_text' => '&rsaquo;',
							)
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<style>
		.bbb-seo-inventory__filters{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:16px 0;padding:12px;background:#fff;border:1px solid #dcdcde;border-radius:6px}
		.bbb-seo-inventory__filters label{font-weight:600}
		.bbb-seo-inventory__filters select,.bbb-seo-inventory__filters input[type="search"]{min-width:160px}
		.bbb-seo-inventory__table-scroll{overflow-x:auto;margin-top:12px;border:1px solid #c3c4c7;background:#fff;-webkit-overflow-scrolling:touch}
		.bbb-seo-inventory__table{min-width:1680px;border:0;table-layout:fixed}
		.bbb-seo-inventory__table th:nth-child(1){width:240px}
		.bbb-seo-inventory__table th:nth-child(2){width:130px}
		.bbb-seo-inventory__table th:nth-child(3){width:260px}
		.bbb-seo-inventory__table th:nth-child(4){width:300px}
		.bbb-seo-inventory__table th:nth-child(5){width:360px}
		.bbb-seo-inventory__table th:nth-child(6){width:160px}
		.bbb-seo-inventory__table th:nth-child(7){width:200px}
		.bbb-seo-inventory__table th:nth-child(8){width:160px}
		.bbb-seo-inventory__table th:nth-child(9){width:90px}
		.bbb-seo-inventory__table td{vertical-align:top}
		.bbb-seo-inventory__table a{overflow-wrap:anywhere}
		.bbb-seo-inventory__field{width:100%;min-height:74px;resize:vertical}
		.bbb-seo-inventory__meta{margin-top:5px;color:#646970;font-size:12px}
		.bbb-seo-inventory__missing{color:#b32d2e;font-weight:600}
		.bbb-seo-inventory__muted{color:#646970}
		.bbb-seo-inventory__ok{color:#008a20}
	</style>
	<?php
}

function bbb_seo_inventory_admin_menu(): void {
	add_management_page(
		__('SEO Inventory', 'bybookishbabe-shopify-port'),
		__('SEO Inventory', 'bybookishbabe-shopify-port'),
		'manage_options',
		'bbb-seo-inventory',
		'bbb_seo_inventory_admin_page'
	);
}
add_action('admin_menu', 'bbb_seo_inventory_admin_menu');
