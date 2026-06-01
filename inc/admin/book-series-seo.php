<?php
/**
 * Admin planning table for book and series page SEO.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_book_series_seo_strlen(string $value): int {
	return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function bbb_book_series_seo_clean(string $value): string {
	$value = wp_strip_all_tags(strip_shortcodes($value), true);
	$value = preg_replace('/\s+/', ' ', $value);

	return trim((string) $value);
}

function bbb_book_series_seo_post_types(): array {
	$post_types = array();
	if (post_type_exists('bbb_book')) {
		$post_types['bbb_book'] = 'Book';
	}
	if (post_type_exists('sss_series')) {
		$post_types['sss_series'] = 'Series';
	}

	return $post_types;
}

function bbb_book_series_seo_first_meta(int $post_id, array $keys): string {
	foreach ($keys as $key) {
		$value = bbb_book_series_seo_clean((string) get_post_meta($post_id, $key, true));
		if ('' !== $value) {
			return $value;
		}
	}

	return '';
}

function bbb_book_series_seo_context(int $post_id, string $post_type): string {
	if ('sss_series' === $post_type) {
		$author = bbb_book_series_seo_clean((string) get_post_meta($post_id, '_bbb_series_author', true));
		$count  = bbb_book_series_seo_clean((string) get_post_meta($post_id, '_bbb_series_books_in_series', true));
		$parts  = array_filter(array($author, '' !== $count ? $count . ' books' : ''));

		return implode(' · ', $parts);
	}

	$author = bbb_book_series_seo_clean((string) get_post_meta($post_id, '_bbb_author', true));
	if ('' === $author) {
		$author = bbb_book_series_seo_clean((string) get_post_meta($post_id, 'sss_author', true));
	}

	$series = bbb_book_series_seo_clean((string) get_post_meta($post_id, '_bbb_series_handle', true));
	$number = bbb_book_series_seo_clean((string) get_post_meta($post_id, '_bbb_series_number', true));
	$parts  = array_filter(array($author, '' !== $series ? 'series: ' . $series : '', '' !== $number ? 'book ' . $number : ''));

	return implode(' · ', $parts);
}

function bbb_book_series_seo_row(int $post_id): array {
	$post_type  = (string) get_post_type($post_id);
	$type_label = bbb_book_series_seo_post_types()[$post_type] ?? $post_type;
	$title      = bbb_book_series_seo_clean(get_the_title($post_id));
	$seo_title  = bbb_book_series_seo_first_meta($post_id, array('rank_math_title', '_yoast_wpseo_title'));
	$seo_desc   = bbb_book_series_seo_first_meta($post_id, array('rank_math_description', '_yoast_wpseo_metadesc'));
	$keyword    = bbb_book_series_seo_first_meta($post_id, array('rank_math_focus_keyword', '_yoast_wpseo_focuskw'));
	$status     = '' !== $seo_title && '' !== $seo_desc && '' !== $keyword ? 'complete' : 'needs-seo';

	return array(
		'id'                 => $post_id,
		'type'               => $post_type,
		'type_label'         => $type_label,
		'post_status'        => get_post_status($post_id) ?: '',
		'title'              => $title,
		'page'               => get_permalink($post_id),
		'context'            => bbb_book_series_seo_context($post_id, $post_type),
		'focus'              => $keyword,
		'seo_title'          => $seo_title,
		'seo_title_length'   => bbb_book_series_seo_strlen($seo_title),
		'description'        => $seo_desc,
		'description_length' => bbb_book_series_seo_strlen($seo_desc),
		'status'             => $status,
		'edit_url'           => get_edit_post_link($post_id, ''),
	);
}

function bbb_book_series_seo_rows(): array {
	$post_types = array_keys(bbb_book_series_seo_post_types());
	if (!$post_types) {
		return array();
	}

	$posts = get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => array('publish', 'draft', 'pending', 'private'),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	return array_map('bbb_book_series_seo_row', array_map('absint', $posts));
}

function bbb_book_series_seo_filtered_rows(): array {
	$type   = isset($_GET['bbb_book_series_seo_type']) ? sanitize_key((string) wp_unslash($_GET['bbb_book_series_seo_type'])) : '';
	$status = isset($_GET['bbb_book_series_seo_status']) ? sanitize_key((string) wp_unslash($_GET['bbb_book_series_seo_status'])) : '';
	$search = isset($_GET['s']) ? strtolower(sanitize_text_field((string) wp_unslash($_GET['s']))) : '';
	$rows   = bbb_book_series_seo_rows();

	return array_values(
		array_filter(
			$rows,
			static function (array $row) use ($type, $status, $search): bool {
				if (in_array($type, array('bbb_book', 'sss_series'), true) && $type !== $row['type']) {
					return false;
				}
				if (in_array($status, array('complete', 'needs-seo'), true) && $status !== $row['status']) {
					return false;
				}
				if ('' === $search) {
					return true;
				}

				$haystack = strtolower(implode(' ', array($row['title'], $row['page'], $row['context'], $row['focus'], $row['seo_title'], $row['description'])));

				return str_contains($haystack, $search);
			}
		)
	);
}

function bbb_book_series_seo_export(): void {
	if (empty($_GET['bbb_book_series_seo_export']) || !current_user_can('manage_options')) {
		return;
	}

	check_admin_referer('bbb_book_series_seo_export');

	nocache_headers();
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=bybookishbabe-book-series-seo-' . gmdate('Y-m-d') . '.csv');

	$output = fopen('php://output', 'w');
	if (false === $output) {
		exit;
	}

	fputcsv($output, array('type', 'id', 'status', 'content title', 'page', 'context', 'new focus keyword', 'seo title (<=60 chars)', 'meta description (<=155 chars)', 'edit url'));
	foreach (bbb_book_series_seo_filtered_rows() as $row) {
		fputcsv($output, array($row['type_label'], $row['id'], $row['status'], $row['title'], $row['page'], $row['context'], $row['focus'], $row['seo_title'], $row['description'], $row['edit_url']));
	}

	exit;
}
add_action('admin_init', 'bbb_book_series_seo_export');

function bbb_book_series_seo_admin_page(): void {
	$type       = isset($_GET['bbb_book_series_seo_type']) ? sanitize_key((string) wp_unslash($_GET['bbb_book_series_seo_type'])) : '';
	$status     = isset($_GET['bbb_book_series_seo_status']) ? sanitize_key((string) wp_unslash($_GET['bbb_book_series_seo_status'])) : '';
	$search     = isset($_GET['s']) ? sanitize_text_field((string) wp_unslash($_GET['s'])) : '';
	$rows       = bbb_book_series_seo_filtered_rows();
	$all_rows   = bbb_book_series_seo_rows();
	$post_types = bbb_book_series_seo_post_types();
	$complete   = count(array_filter($all_rows, static fn(array $row): bool => 'complete' === $row['status']));
	$needs_seo  = count($all_rows) - $complete;
	$export_url = wp_nonce_url(
		add_query_arg(
			array(
				'page'                       => 'bbb-book-series-seo',
				'bbb_book_series_seo_type'   => $type,
				'bbb_book_series_seo_status' => $status,
				's'                          => $search,
				'bbb_book_series_seo_export' => '1',
			),
			admin_url('tools.php')
		),
		'bbb_book_series_seo_export'
	);
	?>
	<div class="wrap bbb-book-series-seo">
		<h1>Book + Series SEO</h1>
		<p class="description">Planning table for book and series pages. Filter by content type or SEO status, then export the current view as CSV.</p>

		<form method="get" class="bbb-book-series-seo__filters">
			<input type="hidden" name="page" value="bbb-book-series-seo">
			<label for="bbb_book_series_seo_type">Type</label>
			<select id="bbb_book_series_seo_type" name="bbb_book_series_seo_type">
				<option value="">Books + Series</option>
				<?php foreach ($post_types as $post_type => $label) : ?>
					<option value="<?php echo esc_attr($post_type); ?>" <?php selected($type, $post_type); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>

			<label for="bbb_book_series_seo_status">Status</label>
			<select id="bbb_book_series_seo_status" name="bbb_book_series_seo_status">
				<option value="">All rows</option>
				<option value="needs-seo" <?php selected($status, 'needs-seo'); ?>>Needs SEO</option>
				<option value="complete" <?php selected($status, 'complete'); ?>>Complete</option>
			</select>

			<label for="bbb_book_series_seo_search">Search</label>
			<input id="bbb_book_series_seo_search" type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Title, URL, author, series">
			<?php submit_button('Filter', 'secondary', '', false); ?>
			<a class="button" href="<?php echo esc_url(admin_url('tools.php?page=bbb-book-series-seo')); ?>">Reset</a>
			<a class="button" href="<?php echo esc_url($export_url); ?>">Export CSV</a>
		</form>

		<p><strong><?php echo esc_html((string) count($all_rows)); ?></strong> book/series rows · <strong><?php echo esc_html((string) $complete); ?></strong> complete · <strong><?php echo esc_html((string) $needs_seo); ?></strong> need SEO</p>

		<div class="bbb-book-series-seo__table-scroll" role="region" aria-label="Book and series SEO table" tabindex="0">
			<table class="widefat striped bbb-book-series-seo__table">
				<thead>
					<tr>
						<th>Type</th>
						<th>Content</th>
						<th>Page</th>
						<th>Status</th>
						<th>Focus keyword</th>
						<th>SEO title</th>
						<th>Meta description</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!$rows) : ?>
						<tr><td colspan="7">No matching book or series pages found.</td></tr>
					<?php endif; ?>
					<?php foreach ($rows as $row) : ?>
						<?php $is_done = 'complete' === $row['status']; ?>
						<tr>
							<td><strong><?php echo esc_html((string) $row['type_label']); ?></strong><div class="bbb-book-series-seo__meta"><?php echo esc_html((string) $row['type']); ?></div></td>
							<td>
								<strong><a href="<?php echo esc_url((string) $row['edit_url']); ?>"><?php echo esc_html((string) $row['title']); ?></a></strong>
								<div class="bbb-book-series-seo__meta">ID <?php echo esc_html((string) $row['id']); ?> · <?php echo esc_html((string) $row['post_status']); ?></div>
								<?php if ('' !== $row['context']) : ?>
									<div class="bbb-book-series-seo__meta"><?php echo esc_html((string) $row['context']); ?></div>
								<?php endif; ?>
							</td>
							<td><a href="<?php echo esc_url((string) $row['page']); ?>" target="_blank" rel="noopener"><?php echo esc_html((string) $row['page']); ?></a></td>
							<td><span class="bbb-book-series-seo__status <?php echo $is_done ? 'is-complete' : 'needs-seo'; ?>"><?php echo $is_done ? 'Complete' : 'Needs SEO'; ?></span></td>
							<td><?php echo '' !== $row['focus'] ? esc_html((string) $row['focus']) : '<span class="bbb-book-series-seo__blank">Blank</span>'; ?></td>
							<td>
								<?php echo '' !== $row['seo_title'] ? esc_html((string) $row['seo_title']) : '<span class="bbb-book-series-seo__blank">Blank</span>'; ?>
								<?php if ('' !== $row['seo_title']) : ?>
									<div class="bbb-book-series-seo__meta"><?php echo esc_html((string) $row['seo_title_length']); ?> chars</div>
								<?php endif; ?>
							</td>
							<td>
								<?php echo '' !== $row['description'] ? esc_html((string) $row['description']) : '<span class="bbb-book-series-seo__blank">Blank</span>'; ?>
								<?php if ('' !== $row['description']) : ?>
									<div class="bbb-book-series-seo__meta"><?php echo esc_html((string) $row['description_length']); ?> chars</div>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<style>
		.bbb-book-series-seo__filters{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:16px 0;padding:12px;background:#fff;border:1px solid #dcdcde;border-radius:6px}
		.bbb-book-series-seo__filters label{font-weight:600}
		.bbb-book-series-seo__filters select,.bbb-book-series-seo__filters input[type="search"]{min-width:190px}
		.bbb-book-series-seo__table-scroll{overflow-x:auto;margin-top:12px;border:1px solid #c3c4c7;background:#fff;-webkit-overflow-scrolling:touch}
		.bbb-book-series-seo__table{min-width:1420px;border:0;table-layout:fixed}
		.bbb-book-series-seo__table th:nth-child(1){width:120px}
		.bbb-book-series-seo__table th:nth-child(2){width:260px}
		.bbb-book-series-seo__table th:nth-child(3){width:260px}
		.bbb-book-series-seo__table th:nth-child(4){width:110px}
		.bbb-book-series-seo__table th:nth-child(5){width:210px}
		.bbb-book-series-seo__table th:nth-child(6){width:300px}
		.bbb-book-series-seo__table th:nth-child(7){width:360px}
		.bbb-book-series-seo__table td{vertical-align:top}
		.bbb-book-series-seo__table a{overflow-wrap:anywhere}
		.bbb-book-series-seo__status{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:700}
		.bbb-book-series-seo__status.is-complete{background:#edfaef;color:#008a20}
		.bbb-book-series-seo__status.needs-seo{background:#fcf0f1;color:#b32d2e}
		.bbb-book-series-seo__blank{color:#b32d2e;font-weight:700}
		.bbb-book-series-seo__meta{margin-top:5px;color:#646970;font-size:12px}
	</style>
	<?php
}

function bbb_book_series_seo_admin_menu(): void {
	add_management_page(
		__('Book + Series SEO', 'bybookishbabe-shopify-port'),
		__('Book + Series SEO', 'bybookishbabe-shopify-port'),
		'manage_options',
		'bbb-book-series-seo',
		'bbb_book_series_seo_admin_page'
	);
}
add_action('admin_menu', 'bbb_book_series_seo_admin_menu');
