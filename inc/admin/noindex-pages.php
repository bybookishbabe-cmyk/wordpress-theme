<?php
/**
 * Admin table and robots controls for pages that should stay out of search.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_noindex_page_targets(): array {
	return array(
		array('path' => '/checkout/', 'label' => 'checkout', 'group' => 'utility/account', 'reason' => 'checkout flow'),
		array('path' => '/checkout/confirmation/', 'label' => 'confirmation', 'group' => 'utility/account', 'reason' => 'purchase confirmation'),
		array('path' => '/checkout/order-history/', 'label' => 'order history', 'group' => 'utility/account', 'reason' => 'customer order history'),
		array('path' => '/checkout/transaction-failed/', 'label' => 'transaction failed', 'group' => 'utility/account', 'reason' => 'payment failure page'),
		array('path' => '/checkout/receipt/', 'label' => 'receipt', 'group' => 'utility/account', 'reason' => 'customer receipt'),
		array('path' => '/my-bookshelf/', 'label' => 'my bookshelf', 'group' => 'utility/account', 'reason' => 'personal reader account page'),
		array('path' => '/my-kindle-inserts/', 'label' => 'my kindle inserts', 'group' => 'utility/account', 'reason' => 'personal download/account page'),
		array('path' => '/my-vault/', 'label' => 'my vault', 'group' => 'utility/account', 'reason' => 'personal vault/account page'),
		array('path' => '/data-sharing-opt-out/', 'label' => 'your privacy choices', 'group' => 'utility/account', 'reason' => 'privacy control page'),
		array('path' => '/privacy-policy/', 'label' => 'privacy policy', 'group' => 'utility/account', 'reason' => 'duplicate/legal utility page'),
		array('path' => '/privacy-policy-2/', 'label' => 'privacy policy duplicate', 'group' => 'utility/account', 'reason' => 'duplicate privacy policy'),
		array('path' => '/sample-page/', 'label' => 'sample page', 'group' => 'utility/account', 'reason' => 'default WordPress sample page'),
		array('path' => '/society-newsletter-recent/', 'label' => 'society newsletter recent', 'group' => 'newsletter/internal', 'reason' => 'imported newsletter issue listing'),
		array('path' => '/society-newsletter-archive/', 'label' => 'society newsletter archive', 'group' => 'newsletter/internal', 'reason' => 'imported newsletter archive'),
		array('path' => '/society-submissions/', 'label' => 'society submissions', 'group' => 'newsletter/internal', 'reason' => 'member newsletter submission form'),
		array('path' => '/newsletter-submissions/', 'label' => 'newsletter submissions', 'group' => 'newsletter/internal', 'reason' => 'newsletter submission form alias'),
		array('path' => '/monthly-staging/', 'label' => 'monthly staging', 'group' => 'staging/internal', 'reason' => 'staging page'),
		array('path' => '/bookshelf-weekly-preview/', 'label' => 'bookshelf weekly preview', 'group' => 'staging/internal', 'reason' => 'internal preview page'),
		array('path' => '/preview/', 'label' => 'preview', 'group' => 'staging/internal', 'reason' => 'internal preview page'),
	);
}

function bbb_noindex_path(): string {
	$path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');

	return '/' . trim($path, '/') . '/';
}

function bbb_noindex_target_for_path(string $path): array {
	$path = '/' . trim($path, '/') . '/';
	foreach (bbb_noindex_page_targets() as $target) {
		if ($path === $target['path']) {
			return $target;
		}
	}

	return array();
}

function bbb_is_noindex_page_path(): bool {
	if (bbb_noindex_target_for_path(bbb_noindex_path())) {
		return true;
	}

	return bbb_is_noindex_newsletter_context();
}

function bbb_is_noindex_newsletter_context(): bool {
	if (is_singular('newsletter_issue')) {
		return true;
	}

	if (function_exists('is_page_template') && is_page_template(array('page-society-newsletter-recent.php', 'page-society-newsletter-archive.php', 'page-society-submissions.php'))) {
		return true;
	}

	$post = get_queried_object();
	if (!$post instanceof WP_Post || 'page' !== $post->post_type) {
		return false;
	}

	$template = (string) get_page_template_slug($post);
	return in_array(
		basename($template),
		array('page-society-newsletter-recent.php', 'page-society-newsletter-archive.php', 'page-society-submissions.php'),
		true
	);
}

function bbb_noindex_page_by_path(string $path): ?WP_Post {
	$slug = trim($path, '/');
	if ('' === $slug) {
		return null;
	}

	$post = get_page_by_path($slug, OBJECT, 'page');
	if ($post instanceof WP_Post) {
		return $post;
	}

	$parts = explode('/', $slug);
	$post  = get_page_by_path((string) end($parts), OBJECT, 'page');

	return $post instanceof WP_Post ? $post : null;
}

function bbb_noindex_sync_post_meta(int $post_id): void {
	$robots = array('noindex', 'nofollow');
	update_post_meta($post_id, 'rank_math_robots', $robots);
	update_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', '1');
	update_post_meta($post_id, '_yoast_wpseo_meta-robots-nofollow', '1');
}

function bbb_noindex_sync_all(): int {
	$count = 0;
	foreach (bbb_noindex_page_targets() as $target) {
		$post = bbb_noindex_page_by_path((string) $target['path']);
		if ($post instanceof WP_Post) {
			bbb_noindex_sync_post_meta($post->ID);
			$count++;
		}
	}

	return $count;
}

function bbb_noindex_rank_math_robots(array $robots): array {
	if (!bbb_is_noindex_page_path()) {
		return $robots;
	}

	unset($robots['index'], $robots['follow']);
	$robots['noindex']  = 'noindex';
	$robots['nofollow'] = 'nofollow';

	return $robots;
}
add_filter('rank_math/frontend/robots', 'bbb_noindex_rank_math_robots', 999);

function bbb_noindex_wp_robots(array $robots): array {
	if (!bbb_is_noindex_page_path()) {
		return $robots;
	}

	unset($robots['index'], $robots['follow']);
	$robots['noindex']  = true;
	$robots['nofollow'] = true;

	return $robots;
}
add_filter('wp_robots', 'bbb_noindex_wp_robots', 999);

function bbb_noindex_x_robots_header(): void {
	if (bbb_is_noindex_page_path() && !headers_sent()) {
		header('X-Robots-Tag: noindex, nofollow', true);
	}
}
add_action('send_headers', 'bbb_noindex_x_robots_header', 20);

function bbb_noindex_row(array $target): array {
	$post     = bbb_noindex_page_by_path((string) $target['path']);
	$post_id  = $post instanceof WP_Post ? $post->ID : 0;
	$rm_robot = $post_id ? get_post_meta($post_id, 'rank_math_robots', true) : array();
	if (!is_array($rm_robot)) {
		$rm_robot = array_filter(array_map('trim', explode(',', (string) $rm_robot)));
	}

	$stored_noindex = $post_id && in_array('noindex', $rm_robot, true);
	$stored_nofollow = $post_id && in_array('nofollow', $rm_robot, true);

	return array(
		'path'             => (string) $target['path'],
		'label'            => (string) $target['label'],
		'group'            => (string) $target['group'],
		'reason'           => (string) $target['reason'],
		'post_id'          => $post_id,
		'title'            => $post instanceof WP_Post ? get_the_title($post) : '',
		'status'           => $post instanceof WP_Post ? (get_post_status($post) ?: '') : 'missing',
		'edit_url'         => $post_id ? get_edit_post_link($post_id, '') : '',
		'url'              => home_url((string) $target['path']),
		'stored_noindex'   => $stored_noindex,
		'stored_nofollow'  => $stored_nofollow,
		'expected_noindex' => true,
	);
}

function bbb_noindex_rows(): array {
	return array_map('bbb_noindex_row', bbb_noindex_page_targets());
}

function bbb_noindex_admin_init(): void {
	if (empty($_POST['bbb_noindex_sync']) || !current_user_can('manage_options')) {
		return;
	}

	check_admin_referer('bbb_noindex_sync');
	$count = bbb_noindex_sync_all();
	wp_safe_redirect(add_query_arg(array('page' => 'bbb-noindex-pages', 'synced' => $count), admin_url('tools.php')));
	exit;
}
add_action('admin_init', 'bbb_noindex_admin_init');

function bbb_noindex_admin_page(): void {
	$rows   = bbb_noindex_rows();
	$synced = isset($_GET['synced']) ? absint($_GET['synced']) : 0;
	$ok     = count(
		array_filter(
			$rows,
			static fn(array $row): bool => $row['post_id'] > 0 && $row['stored_noindex'] && $row['stored_nofollow']
		)
	);
	?>
	<div class="wrap bbb-noindex-pages">
		<h1>No Index</h1>
		<p class="description">Utility, account, staging, and internal pages that should stay out of search results.</p>
		<?php if ($synced) : ?>
			<div class="notice notice-success is-dismissible"><p>Synced noindex/nofollow metadata for <?php echo esc_html((string) $synced); ?> pages.</p></div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field('bbb_noindex_sync'); ?>
			<input type="hidden" name="bbb_noindex_sync" value="1">
			<?php submit_button('Sync noindex metadata', 'primary', '', false); ?>
		</form>
		<p><strong><?php echo esc_html((string) count($rows)); ?></strong> target rows · <strong><?php echo esc_html((string) $ok); ?></strong> stored as noindex/nofollow</p>
		<table class="widefat striped bbb-noindex-pages__table">
			<thead>
				<tr>
					<th>Group</th>
					<th>Page</th>
					<th>Reason</th>
					<th>Stored Robots</th>
					<th>Validation</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $row) : ?>
					<?php $valid = $row['post_id'] > 0 && $row['stored_noindex'] && $row['stored_nofollow']; ?>
					<tr>
						<td><?php echo esc_html($row['group']); ?></td>
						<td>
							<strong><?php echo $row['edit_url'] ? '<a href="' . esc_url($row['edit_url']) . '">' . esc_html($row['label']) . '</a>' : esc_html($row['label']); ?></strong>
							<div class="bbb-noindex-pages__meta"><?php echo esc_html($row['path']); ?></div>
							<div class="bbb-noindex-pages__meta">ID <?php echo $row['post_id'] ? esc_html((string) $row['post_id']) : 'missing'; ?> · <?php echo esc_html($row['status']); ?></div>
						</td>
						<td><?php echo esc_html($row['reason']); ?></td>
						<td><?php echo esc_html(($row['stored_noindex'] ? 'noindex' : 'missing noindex') . ' / ' . ($row['stored_nofollow'] ? 'nofollow' : 'missing nofollow')); ?></td>
						<td><span class="bbb-noindex-pages__status <?php echo $valid ? 'is-valid' : 'is-missing'; ?>"><?php echo $valid ? 'stored' : 'needs sync'; ?></span></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<style>
		.bbb-noindex-pages form{margin:16px 0}
		.bbb-noindex-pages__table{margin-top:12px}
		.bbb-noindex-pages__table td{vertical-align:top}
		.bbb-noindex-pages__meta{margin-top:4px;color:#646970;font-size:12px}
		.bbb-noindex-pages__status{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:700}
		.bbb-noindex-pages__status.is-valid{background:#edfaef;color:#008a20}
		.bbb-noindex-pages__status.is-missing{background:#fcf0f1;color:#b32d2e}
	</style>
	<?php
}

function bbb_noindex_admin_menu(): void {
	add_management_page(
		'No Index',
		'No Index',
		'manage_options',
		'bbb-noindex-pages',
		'bbb_noindex_admin_page'
	);
}
add_action('admin_menu', 'bbb_noindex_admin_menu');
