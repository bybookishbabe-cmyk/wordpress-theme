<?php
/**
 * Admin planning table for blog post SEO.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_blog_post_seo_strlen(string $value): int {
	return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function bbb_blog_post_seo_clean(string $value): string {
	$value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8');
	$value = wp_strip_all_tags(strip_shortcodes($value), true);
	$value = preg_replace('/\s+/', ' ', $value);

	return trim((string) $value);
}

function bbb_blog_post_seo_first_meta(int $post_id, array $keys): string {
	foreach ($keys as $key) {
		$value = bbb_blog_post_seo_clean((string) get_post_meta($post_id, $key, true));
		if ('' !== $value) {
			return $value;
		}
	}

	return '';
}

function bbb_blog_post_seo_categories(int $post_id): array {
	$terms = get_the_terms($post_id, 'category');
	if (!is_array($terms)) {
		return array();
	}

	$names = array();
	foreach ($terms as $term) {
		if ($term instanceof WP_Term) {
			$names[] = bbb_blog_post_seo_clean($term->name);
		}
	}

	return array_values(array_filter($names));
}

function bbb_blog_post_seo_is_book_review(int $post_id): bool {
	return has_category('book-reviews', $post_id) || has_category('Book Reviews', $post_id);
}

function bbb_blog_post_seo_review_rating(int $post_id): string {
	foreach (array('review_rating', 'rating', 'star_rating', 'book_rating', 'bbb_review_rating', 'bbb_star_rating') as $key) {
		$value = bbb_blog_post_seo_clean((string) get_post_meta($post_id, $key, true));
		if ('' !== $value && preg_match('/([0-5](?:\.\d+)?)/', $value, $matches)) {
			$rating = (float) $matches[1];
			if ($rating > 0 && $rating <= 5) {
				return (string) $rating;
			}
		}
	}

	return '';
}

function bbb_blog_post_seo_norm(string $value): string {
	$value = strtolower(bbb_blog_post_seo_clean($value));
	$value = str_replace(array('’', '–'), array("'", '—'), $value);
	$value = preg_replace('/[^a-z0-9]+/', ' ', $value);

	return trim((string) preg_replace('/\s+/', ' ', (string) $value));
}

function bbb_blog_post_seo_terms(int $post_id, array $taxonomies): array {
	foreach ($taxonomies as $taxonomy) {
		if (!taxonomy_exists($taxonomy)) {
			continue;
		}

		$terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));
		if (!is_wp_error($terms) && is_array($terms) && $terms) {
			return array_values(array_filter(array_map('bbb_blog_post_seo_clean', $terms)));
		}
	}

	return array();
}

function bbb_blog_post_seo_book_author(int $book_id): string {
	$author = function_exists('bbb_get_book_author')
		? (string) bbb_get_book_author($book_id)
		: (string) get_post_meta($book_id, '_bbb_author', true);

	return function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($author) : bbb_blog_post_seo_clean($author);
}

function bbb_blog_post_seo_book_for_review(int $post_id, string $post_title): ?WP_Post {
	$linked_book_id = (int) get_post_meta($post_id, '_bbb_article_book_1', true);
	if ($linked_book_id > 0 && 'bbb_book' === get_post_type($linked_book_id)) {
		$book = get_post($linked_book_id);
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	if (!preg_match('/^(.*?)\s+review\b/i', $post_title, $matches)) {
		return null;
	}

	$needle = bbb_blog_post_seo_norm((string) $matches[1]);
	if ('' === $needle) {
		return null;
	}

	$books = get_posts(
		array(
			'post_type'      => 'bbb_book',
			'post_status'    => array('publish', 'draft', 'private', 'pending', 'future'),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ($books as $book) {
		if ($book instanceof WP_Post && $needle === bbb_blog_post_seo_norm($book->post_title)) {
			return $book;
		}
	}

	return null;
}

function bbb_blog_post_seo_review_description(int $book_id): string {
	$shelves = bbb_blog_post_seo_terms($book_id, array('bbb_shelf', 'book_shelves', 'sss_shelf'));
	$tropes  = bbb_blog_post_seo_terms($book_id, array('bbb_trope', 'book_tropes', 'post_tag'));
	$spice   = max(0, min(5, (int) get_post_meta($book_id, '_bbb_spice', true)));
	$shelf   = strtolower($shelves[0] ?? 'romance');
	$tropes  = array_values(array_filter(array_map('strtolower', $tropes)));
	$hook    = $shelf;

	if ($tropes) {
		$hook .= ' with ' . implode(', ', array_slice($tropes, 0, 2));
	} else {
		$hook .= ' with reader-favorite tension';
	}

	return $hook . ' — ' . $spice . '/5 spice. tropes, content warnings & honest verdict inside.';
}

function bbb_blog_post_seo_autofill_review(int $post_id, WP_Post $post, bool $update): void {
	unset($update);

	if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || 'post' !== $post->post_type) {
		return;
	}

	if ('auto-draft' === $post->post_status || '' === trim($post->post_title)) {
		return;
	}

	if (!bbb_blog_post_seo_is_book_review($post_id)) {
		return;
	}

	$book = bbb_blog_post_seo_book_for_review($post_id, $post->post_title);
	if (!$book instanceof WP_Post) {
		return;
	}

	$author = bbb_blog_post_seo_book_author($book->ID);
	if ('' === $author) {
		return;
	}

	$book_title = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($book->post_title) : bbb_blog_post_seo_clean($book->post_title);
	$seo_title  = $book_title . ' review — ' . $author . ' (spice level, tropes & is it worth it)';
	$slug       = sanitize_title((string) $book->post_name . '-review');
	$focus      = strtolower($book_title . ' review');
	$desc       = bbb_blog_post_seo_review_description($book->ID);

	$post_changes = array('ID' => $post_id);
	if ($post->post_title !== $seo_title) {
		$post_changes['post_title'] = wp_slash($seo_title);
	}
	if ($slug && $post->post_name !== $slug) {
		$post_changes['post_name'] = $slug;
	}

	if (count($post_changes) > 1) {
		remove_action('save_post_post', 'bbb_blog_post_seo_autofill_review', 80);
		wp_update_post($post_changes);
		add_action('save_post_post', 'bbb_blog_post_seo_autofill_review', 80, 3);
	}

	update_post_meta($post_id, 'rank_math_title', wp_slash($seo_title));
	update_post_meta($post_id, 'rank_math_description', wp_slash($desc));
	update_post_meta($post_id, 'rank_math_focus_keyword', wp_slash($focus));
}
add_action('save_post_post', 'bbb_blog_post_seo_autofill_review', 80, 3);

function bbb_blog_post_seo_row(int $post_id): array {
	$seo_title = bbb_blog_post_seo_first_meta($post_id, array('rank_math_title', '_yoast_wpseo_title'));
	$seo_desc  = bbb_blog_post_seo_first_meta($post_id, array('rank_math_description', '_yoast_wpseo_metadesc'));
	$keyword   = bbb_blog_post_seo_first_meta($post_id, array('rank_math_focus_keyword', '_yoast_wpseo_focuskw'));
	$status    = '' !== $seo_title && '' !== $seo_desc && '' !== $keyword ? 'complete' : 'needs-seo';
	$warnings  = array();
	$is_review = bbb_blog_post_seo_is_book_review($post_id);
	$rating    = $is_review ? bbb_blog_post_seo_review_rating($post_id) : '';

	if ('' === $seo_title) {
		$warnings[] = 'missing title';
	} elseif (bbb_blog_post_seo_strlen($seo_title) > 60) {
		$warnings[] = 'title over 60';
	}

	if ('' === $seo_desc) {
		$warnings[] = 'missing description';
	} elseif (bbb_blog_post_seo_strlen($seo_desc) > 155) {
		$warnings[] = 'description over 155';
	}

	if ('' === $keyword) {
		$warnings[] = 'missing keyword';
	}

	if ($is_review && '' === $rating) {
		$warnings[] = 'missing review rating';
	}

	return array(
		'id'                 => $post_id,
		'post_status'        => get_post_status($post_id) ?: '',
		'title'              => bbb_blog_post_seo_clean(get_the_title($post_id)),
		'page'               => get_permalink($post_id),
		'categories'         => implode(', ', bbb_blog_post_seo_categories($post_id)),
		'review_schema'      => $is_review ? ('' !== $rating ? 'review schema ready' : 'review schema missing rating') : '',
		'review_rating'      => $rating,
		'focus'              => $keyword,
		'seo_title'          => $seo_title,
		'seo_title_length'   => bbb_blog_post_seo_strlen($seo_title),
		'description'        => $seo_desc,
		'description_length' => bbb_blog_post_seo_strlen($seo_desc),
		'status'             => $status,
		'warnings'           => implode(', ', $warnings),
		'edit_url'           => get_edit_post_link($post_id, ''),
		'modified'           => get_post_modified_time('Y-m-d H:i', false, $post_id) ?: '',
	);
}

function bbb_blog_post_seo_category_options(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if (!is_array($terms) || is_wp_error($terms)) {
		return array();
	}

	return array_values(array_filter($terms, static fn($term): bool => $term instanceof WP_Term));
}

function bbb_blog_post_seo_query_ids(): array {
	$category = isset($_GET['bbb_blog_post_seo_category']) ? sanitize_title((string) wp_unslash($_GET['bbb_blog_post_seo_category'])) : '';
	$status   = isset($_GET['bbb_blog_post_seo_status']) ? sanitize_key((string) wp_unslash($_GET['bbb_blog_post_seo_status'])) : '';
	$search   = isset($_GET['s']) ? sanitize_text_field((string) wp_unslash($_GET['s'])) : '';
	$args     = array(
		'post_type'      => 'post',
		'post_status'    => array('publish', 'draft', 'pending', 'future', 'private'),
		'posts_per_page' => -1,
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'fields'         => 'ids',
		's'              => $search,
	);

	if ('' !== $category) {
		$args['category_name'] = $category;
	}

	$ids = array_map('absint', get_posts($args));
	if (in_array($status, array('complete', 'needs-seo'), true)) {
		$ids = array_values(
			array_filter(
				$ids,
				static fn(int $id): bool => $status === bbb_blog_post_seo_row($id)['status']
			)
		);
	}

	return $ids;
}

function bbb_blog_post_seo_rows(): array {
	return array_map('bbb_blog_post_seo_row', bbb_blog_post_seo_query_ids());
}

function bbb_blog_post_seo_all_rows(): array {
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => array('publish', 'draft', 'pending', 'future', 'private'),
			'posts_per_page' => -1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);

	return array_map('bbb_blog_post_seo_row', array_map('absint', $ids));
}

function bbb_blog_post_seo_export(): void {
	if (empty($_GET['bbb_blog_post_seo_export']) || !current_user_can('manage_options')) {
		return;
	}

	check_admin_referer('bbb_blog_post_seo_export');

	nocache_headers();
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=bybookishbabe-blog-post-seo-' . gmdate('Y-m-d') . '.csv');

	$output = fopen('php://output', 'w');
	if (false === $output) {
		exit;
	}

	fputcsv($output, array('id', 'status', 'post title', 'page', 'categories', 'review schema', 'review rating', 'focus keyword', 'seo title (<=60 chars)', 'meta description (<=155 chars)', 'warnings', 'edit url'));
	foreach (bbb_blog_post_seo_rows() as $row) {
		fputcsv($output, array($row['id'], $row['status'], $row['title'], $row['page'], $row['categories'], $row['review_schema'], $row['review_rating'], $row['focus'], $row['seo_title'], $row['description'], $row['warnings'], $row['edit_url']));
	}

	exit;
}
add_action('admin_init', 'bbb_blog_post_seo_export');

function bbb_blog_post_seo_admin_page(): void {
	$category   = isset($_GET['bbb_blog_post_seo_category']) ? sanitize_title((string) wp_unslash($_GET['bbb_blog_post_seo_category'])) : '';
	$status     = isset($_GET['bbb_blog_post_seo_status']) ? sanitize_key((string) wp_unslash($_GET['bbb_blog_post_seo_status'])) : '';
	$search     = isset($_GET['s']) ? sanitize_text_field((string) wp_unslash($_GET['s'])) : '';
	$rows       = bbb_blog_post_seo_rows();
	$all_rows   = bbb_blog_post_seo_all_rows();
	$complete   = count(array_filter($all_rows, static fn(array $row): bool => 'complete' === $row['status']));
	$needs_seo  = count($all_rows) - $complete;
	$categories = bbb_blog_post_seo_category_options();
	$export_url = wp_nonce_url(
		add_query_arg(
			array(
				'page'                       => 'bbb-blog-post-seo',
				'bbb_blog_post_seo_category' => $category,
				'bbb_blog_post_seo_status'   => $status,
				's'                          => $search,
				'bbb_blog_post_seo_export'   => '1',
			),
			admin_url('tools.php')
		),
		'bbb_blog_post_seo_export'
	);
	?>
	<div class="wrap bbb-blog-post-seo">
		<h1>Blog Post SEO</h1>
		<p class="description">Planning table for blog posts. Filter by category or SEO status, then export the current view as CSV.</p>

		<form method="get" class="bbb-blog-post-seo__filters">
			<input type="hidden" name="page" value="bbb-blog-post-seo">
			<label for="bbb_blog_post_seo_category">Category</label>
			<select id="bbb_blog_post_seo_category" name="bbb_blog_post_seo_category">
				<option value="">All categories</option>
				<?php foreach ($categories as $term) : ?>
					<option value="<?php echo esc_attr($term->slug); ?>" <?php selected($category, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
				<?php endforeach; ?>
			</select>

			<label for="bbb_blog_post_seo_status">Status</label>
			<select id="bbb_blog_post_seo_status" name="bbb_blog_post_seo_status">
				<option value="">All rows</option>
				<option value="needs-seo" <?php selected($status, 'needs-seo'); ?>>Needs SEO</option>
				<option value="complete" <?php selected($status, 'complete'); ?>>Complete</option>
			</select>

			<label for="bbb_blog_post_seo_search">Search</label>
			<input id="bbb_blog_post_seo_search" type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Title, URL, category, keyword">
			<?php submit_button('Filter', 'secondary', '', false); ?>
			<a class="button" href="<?php echo esc_url(admin_url('tools.php?page=bbb-blog-post-seo')); ?>">Reset</a>
			<a class="button" href="<?php echo esc_url($export_url); ?>">Export CSV</a>
		</form>

		<p><strong><?php echo esc_html((string) count($all_rows)); ?></strong> blog post rows · <strong><?php echo esc_html((string) $complete); ?></strong> complete · <strong><?php echo esc_html((string) $needs_seo); ?></strong> need SEO</p>

		<div class="bbb-blog-post-seo__table-scroll" role="region" aria-label="Blog post SEO table" tabindex="0">
			<table class="widefat striped bbb-blog-post-seo__table">
				<thead>
					<tr>
						<th>Post</th>
						<th>Page</th>
						<th>Categories</th>
						<th>Review schema</th>
						<th>Status</th>
						<th>Focus keyword</th>
						<th>SEO title</th>
						<th>Meta description</th>
						<th>Warnings</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!$rows) : ?>
						<tr><td colspan="9">No matching blog posts found.</td></tr>
					<?php endif; ?>
					<?php foreach ($rows as $row) : ?>
						<?php $is_done = 'complete' === $row['status']; ?>
						<tr>
							<td>
								<strong><a href="<?php echo esc_url((string) $row['edit_url']); ?>"><?php echo esc_html((string) $row['title']); ?></a></strong>
								<div class="bbb-blog-post-seo__meta">ID <?php echo esc_html((string) $row['id']); ?> · <?php echo esc_html((string) $row['post_status']); ?></div>
								<?php if ('' !== $row['modified']) : ?>
									<div class="bbb-blog-post-seo__meta">modified <?php echo esc_html((string) $row['modified']); ?></div>
								<?php endif; ?>
							</td>
							<td><a href="<?php echo esc_url((string) $row['page']); ?>" target="_blank" rel="noopener"><?php echo esc_html((string) $row['page']); ?></a></td>
							<td><?php echo '' !== $row['categories'] ? esc_html((string) $row['categories']) : '<span class="bbb-blog-post-seo__blank">Uncategorized</span>'; ?></td>
							<td>
								<?php if ('' !== $row['review_schema']) : ?>
									<?php echo esc_html((string) $row['review_schema']); ?>
									<div class="bbb-blog-post-seo__meta"><?php echo '' !== $row['review_rating'] ? esc_html((string) $row['review_rating'] . '/5') : 'rating blank'; ?></div>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
							<td><span class="bbb-blog-post-seo__status <?php echo $is_done ? 'is-complete' : 'needs-seo'; ?>"><?php echo $is_done ? 'Complete' : 'Needs SEO'; ?></span></td>
							<td><?php echo '' !== $row['focus'] ? esc_html((string) $row['focus']) : '<span class="bbb-blog-post-seo__blank">Blank</span>'; ?></td>
							<td>
								<?php echo '' !== $row['seo_title'] ? esc_html((string) $row['seo_title']) : '<span class="bbb-blog-post-seo__blank">Blank</span>'; ?>
								<?php if ('' !== $row['seo_title']) : ?>
									<div class="bbb-blog-post-seo__meta"><?php echo esc_html((string) $row['seo_title_length']); ?> chars</div>
								<?php endif; ?>
							</td>
							<td>
								<?php echo '' !== $row['description'] ? esc_html((string) $row['description']) : '<span class="bbb-blog-post-seo__blank">Blank</span>'; ?>
								<?php if ('' !== $row['description']) : ?>
									<div class="bbb-blog-post-seo__meta"><?php echo esc_html((string) $row['description_length']); ?> chars</div>
								<?php endif; ?>
							</td>
							<td><?php echo '' !== $row['warnings'] ? esc_html((string) $row['warnings']) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<style>
		.bbb-blog-post-seo__filters{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:16px 0;padding:12px;background:#fff;border:1px solid #dcdcde;border-radius:6px}
		.bbb-blog-post-seo__filters label{font-weight:600}
		.bbb-blog-post-seo__filters select,.bbb-blog-post-seo__filters input[type="search"]{min-width:190px}
		.bbb-blog-post-seo__table-scroll{overflow-x:auto;margin-top:12px;border:1px solid #c3c4c7;background:#fff;-webkit-overflow-scrolling:touch}
		.bbb-blog-post-seo__table{min-width:1810px;border:0;table-layout:fixed}
		.bbb-blog-post-seo__table th:nth-child(1){width:260px}
		.bbb-blog-post-seo__table th:nth-child(2){width:270px}
		.bbb-blog-post-seo__table th:nth-child(3){width:180px}
		.bbb-blog-post-seo__table th:nth-child(4){width:150px}
		.bbb-blog-post-seo__table th:nth-child(5){width:110px}
		.bbb-blog-post-seo__table th:nth-child(6){width:220px}
		.bbb-blog-post-seo__table th:nth-child(7){width:290px}
		.bbb-blog-post-seo__table th:nth-child(8){width:370px}
		.bbb-blog-post-seo__table th:nth-child(9){width:160px}
		.bbb-blog-post-seo__table td{vertical-align:top}
		.bbb-blog-post-seo__table a{overflow-wrap:anywhere}
		.bbb-blog-post-seo__status{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:700}
		.bbb-blog-post-seo__status.is-complete{background:#edfaef;color:#008a20}
		.bbb-blog-post-seo__status.needs-seo{background:#fcf0f1;color:#b32d2e}
		.bbb-blog-post-seo__blank{color:#b32d2e;font-weight:700}
		.bbb-blog-post-seo__meta{margin-top:5px;color:#646970;font-size:12px}
	</style>
	<?php
}

function bbb_blog_post_seo_admin_menu(): void {
	add_management_page(
		'Blog Post SEO',
		'Blog Post SEO',
		'manage_options',
		'bbb-blog-post-seo',
		'bbb_blog_post_seo_admin_page'
	);
}
add_action('admin_menu', 'bbb_blog_post_seo_admin_menu');
