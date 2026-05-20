<?php
/**
 * Admin view for free and paid Society members.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_society_admin_normalize_email(string $email): string {
	return strtolower(trim($email));
}

function bbb_society_admin_supabase_config(): array {
	return array(
		'url' => defined('SUPABASE_URL') ? rtrim((string) SUPABASE_URL, '/') : 'https://efmrfxsmgbeikfgtrxjv.supabase.co',
		'key' => defined('SUPABASE_SERVICE_ROLE_KEY') ? (string) SUPABASE_SERVICE_ROLE_KEY : (string) getenv('SUPABASE_SERVICE_ROLE_KEY'),
	);
}

function bbb_society_admin_fetch_supabase_members(): array {
	$config = bbb_society_admin_supabase_config();
	if ('' === $config['url'] || '' === $config['key']) {
		return array(
			'rows'  => array(),
			'counts' => array(
				'total' => 0,
				'paid'  => 0,
				'free'  => 0,
			),
			'error' => 'Supabase service role key is not configured, so this table is showing WordPress accounts only.',
		);
	}

	$query = http_build_query(
		array(
			'select' => 'id,email,email_normalized,access_tier,society_key_used_at,weekly_email_opt_in,subscribed_at,updated_at,last_weekly_sent_at,account_status',
			'order'  => 'subscribed_at.desc',
			'limit'  => 500,
		),
		'',
		'&',
		PHP_QUERY_RFC3986
	);

	$response = wp_remote_get(
		$config['url'] . '/rest/v1/bookshelf_subscribers?' . $query,
		array(
			'timeout' => 15,
			'headers' => array(
				'apikey'        => $config['key'],
				'Authorization' => 'Bearer ' . $config['key'],
				'Accept'        => 'application/json',
			),
		)
	);

	if (is_wp_error($response)) {
		return array('rows' => array(), 'error' => $response->get_error_message());
	}

	$code = (int) wp_remote_retrieve_response_code($response);
	$body = (string) wp_remote_retrieve_body($response);
	$rows = json_decode($body, true);

	if ($code < 200 || $code >= 300 || !is_array($rows)) {
		return array(
			'rows'  => array(),
			'error' => 'Supabase returned an unexpected response while loading bookshelf subscribers.',
		);
	}

	return array(
		'rows'   => $rows,
		'counts' => bbb_society_admin_fetch_supabase_counts($config),
		'error'  => '',
	);
}

function bbb_society_admin_fetch_supabase_count(array $config, array $filters = array()): int {
	$query = http_build_query(
		array_merge(
			array(
				'select' => 'id',
				'limit'  => 1,
			),
			$filters
		),
		'',
		'&',
		PHP_QUERY_RFC3986
	);

	$response = wp_remote_get(
		$config['url'] . '/rest/v1/bookshelf_subscribers?' . $query,
		array(
			'timeout' => 15,
			'headers' => array(
				'apikey'        => $config['key'],
				'Authorization' => 'Bearer ' . $config['key'],
				'Accept'        => 'application/json',
				'Prefer'        => 'count=exact',
			),
		)
	);

	if (is_wp_error($response)) {
		return 0;
	}

	$content_range = (string) wp_remote_retrieve_header($response, 'content-range');
	if (preg_match('#/([0-9]+)$#', $content_range, $matches)) {
		return (int) $matches[1];
	}

	return 0;
}

function bbb_society_admin_fetch_supabase_counts(array $config): array {
	$total = bbb_society_admin_fetch_supabase_count($config);
	$paid  = bbb_society_admin_fetch_supabase_count(
		$config,
		array(
			'or' => '(access_tier.eq.society,society_key_used_at.not.is.null)',
		)
	);

	return array(
		'total' => $total,
		'paid'  => $paid,
		'free'  => max($total - $paid, 0),
	);
}

function bbb_society_admin_csv_key(string $value): string {
	return strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', trim($value)));
}

function bbb_society_admin_find_csv_column(array $headers, array $patterns): string {
	foreach ($headers as $header) {
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $header)) {
				return $header;
			}
		}
	}

	return '';
}

function bbb_society_admin_csv_row_is_paid(array $row): bool {
	$signals = array();

	foreach ($row as $key => $value) {
		$key = bbb_society_admin_csv_key((string) $key);
		if (!preg_match('/(tier|type|status|plan|subscription|stripe|paid|comp|founding|membership)/', $key)) {
			continue;
		}

		$signals[] = strtolower(trim((string) $value));
	}

	$text = trim(implode(' ', array_filter($signals)));
	if ('' === $text) {
		return false;
	}

	if (preg_match('/\b(free|unpaid|inactive|cancell?ed|expired|paused|trial_ended)\b/', $text)) {
		return false;
	}

	return (bool) preg_match('/\b(paid|active|founding|monthly|annual|yearly|comped|gifted|subscriber|member|society)\b/', $text);
}

function bbb_society_admin_parse_substack_csv(string $file_path): array {
	$handle = fopen($file_path, 'r');
	if (!$handle) {
		return new WP_Error('bbb_substack_csv_open_failed', 'Could not open the uploaded CSV.');
	}

	$raw_headers = fgetcsv($handle);
	if (!is_array($raw_headers)) {
		fclose($handle);
		return new WP_Error('bbb_substack_csv_empty', 'The CSV is empty.');
	}

	$headers = array_map('bbb_society_admin_csv_key', $raw_headers);
	$email_column = bbb_society_admin_find_csv_column($headers, array('/(^|_)email$/', '/email/'));
	if ('' === $email_column) {
		fclose($handle);
		return new WP_Error('bbb_substack_csv_missing_email', 'Could not find an email column in the CSV.');
	}

	$rows = array();
	$seen = array();
	while (($cells = fgetcsv($handle)) !== false) {
		$row = array();
		foreach ($headers as $index => $header) {
			$row[$header] = isset($cells[$index]) ? (string) $cells[$index] : '';
		}

		$email = bbb_society_admin_normalize_email((string) ($row[$email_column] ?? ''));
		if ('' === $email || !is_email($email) || isset($seen[$email])) {
			continue;
		}

		$seen[$email] = true;
		$is_paid = bbb_society_admin_csv_row_is_paid($row);
		$rows[] = array(
			'email'               => $email,
			'email_normalized'    => $email,
			'customer_email'      => $email,
			'account_status'      => 'email_only',
			'access_tier'         => $is_paid ? 'society' : 'free',
			'society_key_used_at' => $is_paid ? gmdate('c') : null,
			'society_key_source'  => $is_paid ? 'substack_csv_import' : null,
			'weekly_email_opt_in' => true,
			'source'              => 'substack_csv_import',
			'last_synced_at'      => gmdate('c'),
			'metadata'            => array(
				'imported_from' => 'substack_csv',
				'csv_status'    => array_filter(
					$row,
					static fn($key): bool => (bool) preg_match('/(tier|type|status|plan|subscription|stripe|paid|comp|founding|membership)/', (string) $key),
					ARRAY_FILTER_USE_KEY
				),
			),
		);
	}

	fclose($handle);
	return $rows;
}

function bbb_society_admin_upsert_substack_rows(array $rows) {
	$config = bbb_society_admin_supabase_config();
	if ('' === $config['url'] || '' === $config['key']) {
		return new WP_Error('bbb_supabase_not_configured', 'Supabase service role key is not configured.');
	}

	$imported = 0;
	foreach (array_chunk($rows, 500) as $chunk) {
		$response = wp_remote_request(
			$config['url'] . '/rest/v1/bookshelf_subscribers?on_conflict=email_normalized',
			array(
				'method'  => 'POST',
				'timeout' => 30,
				'headers' => array(
					'apikey'        => $config['key'],
					'Authorization' => 'Bearer ' . $config['key'],
					'Accept'        => 'application/json',
					'Content-Type'  => 'application/json',
					'Prefer'        => 'resolution=merge-duplicates,return=minimal',
				),
				'body'    => wp_json_encode($chunk),
			)
		);

		if (is_wp_error($response)) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		if ($code < 200 || $code >= 300) {
			return new WP_Error(
				'bbb_supabase_import_failed',
				'Supabase rejected the subscriber import.',
				array('status' => $code, 'body' => wp_remote_retrieve_body($response))
			);
		}

		$imported += count($chunk);
	}

	return $imported;
}

function bbb_society_admin_handle_substack_import() {
	if (
		empty($_POST['bbb_substack_import'])
		|| !current_user_can('manage_options')
		|| empty($_FILES['bbb_substack_csv']['tmp_name'])
	) {
		return null;
	}

	check_admin_referer('bbb_substack_import', 'bbb_substack_import_nonce');

	$rows = bbb_society_admin_parse_substack_csv((string) $_FILES['bbb_substack_csv']['tmp_name']);
	if (is_wp_error($rows)) {
		return $rows;
	}

	$result = bbb_society_admin_upsert_substack_rows($rows);
	if (is_wp_error($result)) {
		return $result;
	}

	$paid = count(array_filter($rows, static fn(array $row): bool => 'society' === $row['access_tier']));
	return array(
		'imported' => (int) $result,
		'paid'     => $paid,
		'free'     => max((int) $result - $paid, 0),
	);
}

function bbb_society_admin_wp_user_is_paid(WP_User $user): bool {
	$user_id = (int) $user->ID;

	return (function_exists('bbb_user_is_society') && bbb_user_is_society($user_id))
		|| in_array('sss_member', (array) $user->roles, true)
		|| in_array('society_member', (array) $user->roles, true)
		|| (function_exists('wc_memberships_is_user_active_member')
			&& wc_memberships_is_user_active_member($user_id, 'smut-sentiment-society'));
}

function bbb_society_admin_member_rows(): array {
	$supabase = bbb_society_admin_fetch_supabase_members();
	$rows     = array();

	foreach ((array) $supabase['rows'] as $subscriber) {
		$email = bbb_society_admin_normalize_email((string) ($subscriber['email_normalized'] ?? $subscriber['email'] ?? ''));
		if ('' === $email) {
			continue;
		}

		$is_paid = !empty($subscriber['society_key_used_at']);
		$rows[$email] = array(
			'email'              => $email,
			'name'               => '',
			'supabase_tier'      => $is_paid ? 'society' : 'free',
			'wp_access'          => 'no account',
			'weekly_email_opt_in' => !empty($subscriber['weekly_email_opt_in']) ? 'yes' : 'no',
			'subscribed_at'      => (string) ($subscriber['subscribed_at'] ?? ''),
			'last_weekly_sent_at' => (string) ($subscriber['last_weekly_sent_at'] ?? ''),
			'updated_at'         => (string) ($subscriber['updated_at'] ?? ''),
			'source'             => 'supabase',
		);
	}

	$wp_users = get_users(
		array(
			'fields' => 'all',
			'number' => 1000,
			'orderby' => 'registered',
			'order' => 'DESC',
		)
	);

	foreach ($wp_users as $user) {
		if (!$user instanceof WP_User) {
			continue;
		}

		$email = bbb_society_admin_normalize_email((string) $user->user_email);
		if ('' === $email) {
			continue;
		}

		$is_paid = bbb_society_admin_wp_user_is_paid($user);
		if (!isset($rows[$email])) {
			$rows[$email] = array(
				'email'              => $email,
				'name'               => (string) $user->display_name,
				'supabase_tier'      => 'not subscribed',
				'wp_access'          => $is_paid ? 'paid access' : 'free account',
				'weekly_email_opt_in' => '',
				'subscribed_at'      => '',
				'last_weekly_sent_at' => '',
				'updated_at'         => '',
				'source'             => 'wordpress',
			);
			continue;
		}

		$rows[$email]['name']      = (string) $user->display_name;
		$rows[$email]['wp_access'] = $is_paid ? 'paid access' : 'free account';
		$rows[$email]['source']    = 'supabase + wordpress';
	}

	return array(
		'rows'   => array_values($rows),
		'counts' => is_array($supabase['counts'] ?? null) ? $supabase['counts'] : array(),
		'error'  => (string) ($supabase['error'] ?? ''),
	);
}

function bbb_society_admin_filter_rows(array $rows): array {
	$tier   = isset($_GET['bbb_tier']) ? sanitize_key((string) wp_unslash($_GET['bbb_tier'])) : 'all';
	$search = isset($_GET['s']) ? bbb_society_admin_normalize_email((string) wp_unslash($_GET['s'])) : '';

	return array_values(
		array_filter(
			$rows,
			static function (array $row) use ($tier, $search): bool {
				if ('paid' === $tier && 'society' !== $row['supabase_tier'] && 'paid access' !== $row['wp_access']) {
					return false;
				}

				if ('free' === $tier && ('society' === $row['supabase_tier'] || 'paid access' === $row['wp_access'])) {
					return false;
				}

				if ($search && !str_contains((string) $row['email'], $search) && !str_contains(strtolower((string) $row['name']), $search)) {
					return false;
				}

				return true;
			}
		)
	);
}

function bbb_society_admin_badge(string $value): string {
	$class = 'bbb-society-admin-badge';
	if (in_array($value, array('society', 'paid access'), true)) {
		$class .= ' is-paid';
	} elseif (in_array($value, array('free', 'free account'), true)) {
		$class .= ' is-free';
	}

	return '<span class="' . esc_attr($class) . '">' . esc_html($value) . '</span>';
}

function bbb_society_admin_page(): void {
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to view this page.', 'bybookishbabe-shopify-port'));
	}

	$import_result = bbb_society_admin_handle_substack_import();
	$data = bbb_society_admin_member_rows();
	$rows = bbb_society_admin_filter_rows($data['rows']);
	$counts = is_array($data['counts'] ?? null) ? $data['counts'] : array();
	$total_count = (int) ($counts['total'] ?? count($data['rows']));
	$paid_count = (int) ($counts['paid'] ?? 0);
	$free_count = (int) ($counts['free'] ?? max($total_count - $paid_count, 0));
	?>
	<div class="wrap bbb-society-admin">
		<h1><?php esc_html_e('Newsletter Subscribers', 'bybookishbabe-shopify-port'); ?></h1>
		<p class="description">Summary counts are exact from Supabase. Paid logic: <code>access_tier = society</code> or <code>society_key_used_at</code>; otherwise the subscriber is free.</p>

		<?php if (!empty($data['error'])) : ?>
			<div class="notice notice-warning"><p><?php echo esc_html($data['error']); ?></p></div>
		<?php endif; ?>

		<?php if (is_wp_error($import_result)) : ?>
			<div class="notice notice-error"><p><?php echo esc_html($import_result->get_error_message()); ?></p></div>
		<?php elseif (is_array($import_result)) : ?>
			<div class="notice notice-success"><p>
				<?php
				echo esc_html(
					sprintf(
						'Imported %1$d Substack subscribers: %2$d paid/society and %3$d free.',
						(int) $import_result['imported'],
						(int) $import_result['paid'],
						(int) $import_result['free']
					)
				);
				?>
			</p></div>
		<?php endif; ?>

		<div class="bbb-society-admin-summary">
			<div><strong><?php echo esc_html((string) $total_count); ?></strong><span>total</span></div>
			<div><strong><?php echo esc_html((string) $paid_count); ?></strong><span>paid/society</span></div>
			<div><strong><?php echo esc_html((string) $free_count); ?></strong><span>free/no paid access</span></div>
		</div>

		<form method="post" enctype="multipart/form-data" class="bbb-society-admin-import">
			<h2><?php esc_html_e('Import Substack CSV', 'bybookishbabe-shopify-port'); ?></h2>
			<p>Export subscribers from Substack with all columns, then upload the CSV here. WordPress will sync those emails into Supabase and mark paid subscribers as society access.</p>
			<?php wp_nonce_field('bbb_substack_import', 'bbb_substack_import_nonce'); ?>
			<input type="file" name="bbb_substack_csv" accept=".csv,text/csv" required>
			<button class="button button-primary" name="bbb_substack_import" value="1">Import Substack subscribers</button>
		</form>

		<div class="bbb-society-admin-import">
			<h2><?php esc_html_e('Automatic Substack Sync', 'bybookishbabe-shopify-port'); ?></h2>
			<p>Use this webhook URL in Zapier, Make, SubstackSync, or another Substack-compatible automation. Send a JSON payload with <code>email</code> and a paid/free status field.</p>
			<p><code><?php echo esc_html(rest_url('bbb/v1/substack-subscriber')); ?></code></p>
			<p>
				<?php if (function_exists('bbb_reader_substack_sync_secret') && '' !== bbb_reader_substack_sync_secret()) : ?>
					<strong><?php esc_html_e('Webhook secret is configured.', 'bybookishbabe-shopify-port'); ?></strong>
				<?php else : ?>
					<strong><?php esc_html_e('Webhook secret is not configured.', 'bybookishbabe-shopify-port'); ?></strong>
					<?php esc_html_e('Add SUBSTACK_SYNC_SECRET to wp-config.php before using automatic sync.', 'bybookishbabe-shopify-port'); ?>
				<?php endif; ?>
			</p>
		</div>

		<form method="get" class="bbb-society-admin-filters">
			<input type="hidden" name="page" value="bbb-society-members">
			<select name="bbb_tier">
				<?php $tier = isset($_GET['bbb_tier']) ? sanitize_key((string) wp_unslash($_GET['bbb_tier'])) : 'all'; ?>
				<option value="all" <?php selected($tier, 'all'); ?>>All tiers</option>
				<option value="paid" <?php selected($tier, 'paid'); ?>>Paid / Society</option>
				<option value="free" <?php selected($tier, 'free'); ?>>Free / no paid access</option>
			</select>
			<input type="search" name="s" value="<?php echo esc_attr(isset($_GET['s']) ? (string) wp_unslash($_GET['s']) : ''); ?>" placeholder="Search email or name">
			<button class="button">Filter</button>
		</form>

		<table class="widefat striped bbb-society-admin-table">
			<thead>
				<tr>
					<th>Email</th>
					<th>Name</th>
					<th>Supabase tier</th>
					<th>WordPress access</th>
					<th>Weekly email</th>
					<th>Subscribed</th>
					<th>Last weekly sent</th>
					<th>Source</th>
				</tr>
			</thead>
			<tbody>
				<?php if (!$rows) : ?>
					<tr><td colspan="8">No matching members found.</td></tr>
				<?php endif; ?>
				<?php foreach ($rows as $row) : ?>
					<tr>
						<td><strong><?php echo esc_html((string) $row['email']); ?></strong></td>
						<td><?php echo esc_html((string) $row['name']); ?></td>
						<td><?php echo wp_kses_post(bbb_society_admin_badge((string) $row['supabase_tier'])); ?></td>
						<td><?php echo wp_kses_post(bbb_society_admin_badge((string) $row['wp_access'])); ?></td>
						<td><?php echo esc_html((string) $row['weekly_email_opt_in']); ?></td>
						<td><?php echo esc_html((string) $row['subscribed_at']); ?></td>
						<td><?php echo esc_html((string) $row['last_weekly_sent_at']); ?></td>
						<td><?php echo esc_html((string) $row['source']); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<style>
		.bbb-society-admin-summary{display:flex;gap:12px;margin:18px 0 16px}
		.bbb-society-admin-summary div{min-width:150px;padding:14px 16px;background:#fff;border:1px solid #dcdcde;border-radius:6px}
		.bbb-society-admin-summary strong{display:block;font-size:24px;line-height:1.1}
		.bbb-society-admin-summary span{display:block;margin-top:4px;color:#646970}
		.bbb-society-admin-import{max-width:760px;margin:0 0 18px;padding:16px;background:#fff;border:1px solid #dcdcde;border-radius:6px}
		.bbb-society-admin-import h2{margin:0 0 8px}
		.bbb-society-admin-import p{margin:0 0 12px;color:#646970}
		.bbb-society-admin-filters{display:flex;gap:8px;align-items:center;margin:0 0 14px}
		.bbb-society-admin-badge{display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;background:#f0f0f1;color:#2c3338;font-size:12px}
		.bbb-society-admin-badge.is-paid{background:#fce7f3;color:#9d174d}
		.bbb-society-admin-badge.is-free{background:#ecfdf5;color:#047857}
		.bbb-society-admin-table td{vertical-align:middle}
	</style>
	<?php
}

function bbb_society_admin_menu(): void {
	add_users_page(
		__('Society Members', 'bybookishbabe-shopify-port'),
		__('Society Members', 'bybookishbabe-shopify-port'),
		'manage_options',
		'bbb-society-members',
		'bbb_society_admin_page'
	);
}
add_action('admin_menu', 'bbb_society_admin_menu');
