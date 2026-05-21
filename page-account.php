<?php
/**
 * Template Name: Reader Account
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$my_bookshelf_css_path = get_theme_file_path('assets/css/my-bookshelf.css');
wp_enqueue_style('bbb-my-bookshelf', get_theme_file_uri('assets/css/my-bookshelf.css'), array('bbb-sss-library'), file_exists($my_bookshelf_css_path) ? (string) filemtime($my_bookshelf_css_path) : wp_get_theme()->get('Version'));

$identity     = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
$is_logged_in = is_user_logged_in();
$user         = isset($identity['user']) && $identity['user'] instanceof WP_User ? $identity['user'] : ($is_logged_in ? wp_get_current_user() : null);
$reader_email = $identity ? (string) ($identity['email'] ?? '') : '';
$reader_user_id = $identity ? (int) ($identity['userId'] ?? 0) : 0;
$has_reader_access = '' !== $reader_email;
$is_society   = ($has_reader_access && function_exists('bbb_reader_access_tier_for_email') && 'society' === bbb_reader_access_tier_for_email($reader_email, $reader_user_id));
$account_data = ($has_reader_access && function_exists('bbb_reader_account_response_for_identity'))
	? bbb_reader_account_response_for_identity((array) $identity)
	: array();
$books        = isset($account_data['books']) && is_array($account_data['books']) ? $account_data['books'] : array();
$tier         = $is_society ? 'society' : (string) ($account_data['accessTier'] ?? 'free');
$display_name = ($identity && '' !== trim((string) ($identity['displayName'] ?? ''))) ? (string) $identity['displayName'] : 'reader';
$tier_label   = 'society' === $tier ? 'tier: paid society member' : ($has_reader_access ? 'tier: free reader member' : 'tier: visitor');
$account_url  = function_exists('bbb_page_url') ? bbb_page_url('account') : home_url('/account/');
$bookshelf_url = function_exists('bbb_page_url') ? bbb_page_url('my-bookshelf') : home_url('/my-bookshelf/');
$dashboard_url = function_exists('bbb_page_url') ? bbb_page_url('member-dashboard') : home_url('/member-dashboard/');
$monthly_drop_url = function_exists('bbb_page_url') ? bbb_page_url('monthly-theme') : home_url('/monthly-theme/');
$society_url = function_exists('bbb_page_url') ? bbb_page_url('smut-sentiment-society') : home_url('/smut-sentiment-society/');
$shop_url = function_exists('bbb_page_url') ? bbb_page_url('shop') : home_url('/shop/');
$daily_prompt = is_array($account_data['dailyJournalPrompt'] ?? null) ? $account_data['dailyJournalPrompt'] : array();
$daily_prompt_text = isset($daily_prompt['text']) ? trim((string) $daily_prompt['text']) : '';
$daily_prompt_day = isset($daily_prompt['day']) ? (int) $daily_prompt['day'] : 0;
$daily_prompt_total = isset($daily_prompt['total']) ? (int) $daily_prompt['total'] : 0;
$show_daily_prompt = $is_society && '' !== $daily_prompt_text;
$daily_prompt_meta = ($daily_prompt_day > 0 && $daily_prompt_total > 0)
	? sprintf('day %s of %s', (string) $daily_prompt_day, (string) $daily_prompt_total)
	: 'daily journal prompt';
$purchase_rows = array();
$bookshelf_preview_books = array_slice(
	array_values(
		array_filter(
			$books,
			static fn($book): bool => is_array($book) && ('' !== trim((string) ($book['cover'] ?? '')) || '' !== trim((string) ($book['book_title'] ?? $book['title'] ?? '')))
		)
	),
	0,
	3
);

if ($has_reader_access && function_exists('wc_get_orders')) {
	$order_args = array(
		'limit'   => 4,
		'orderby' => 'date',
		'order'   => 'DESC',
		'status'  => array('wc-completed', 'wc-processing', 'wc-on-hold'),
	);
	$orders_by_id = $reader_user_id
		? wc_get_orders(
			array(
				'customer_id' => $reader_user_id,
			)
			+ $order_args
		)
		: array();
	$orders_by_email = wc_get_orders(
		array(
			'billing_email' => $reader_email,
		)
		+ $order_args
	);
	$orders = array();

	foreach (array_merge((array) $orders_by_id, (array) $orders_by_email) as $order) {
		if ($order instanceof WC_Order) {
			$orders[$order->get_id()] = $order;
		}
	}
	usort(
		$orders,
		static function (WC_Order $a, WC_Order $b): int {
			$a_time = $a->get_date_created() ? $a->get_date_created()->getTimestamp() : 0;
			$b_time = $b->get_date_created() ? $b->get_date_created()->getTimestamp() : 0;
			return $b_time <=> $a_time;
		}
	);

	foreach ($orders as $order) {
		$items = array();
		foreach ($order->get_items() as $item) {
			$items[] = $item->get_name();
		}

		$purchase_rows[] = array(
			'title'  => $items ? implode(', ', array_slice($items, 0, 2)) : sprintf('order #%s', $order->get_order_number()),
			'meta'   => trim(sprintf('%s - %s', wc_get_order_status_name($order->get_status()), $order->get_date_created() ? $order->get_date_created()->date_i18n('M j, Y') : '')),
			'url'    => $reader_user_id ? $order->get_view_order_url() : '',
			'total'  => wp_strip_all_tags($order->get_formatted_order_total()),
		);
	}

	$purchase_rows = array_slice($purchase_rows, 0, 4);
}

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-account-shelf">
		<div class="bbb-account-shelf__wrap">
			<div class="bbb-account-shelf__hero">
				<p class="bbb-account-shelf__kicker">reader account</p>
				<div class="bbb-account-shelf__memberBadge<?php echo 'society' === $tier ? ' bbb-account-shelf__memberBadge--secret' : ''; ?>">
					<span aria-hidden="true"><?php echo esc_html('society' === $tier ? '♥' : '*'); ?></span>
					<span><?php echo esc_html($tier_label); ?></span>
				</div>
				<h1 class="bbb-account-shelf__title">reader account</h1>
				<p class="bbb-account-shelf__sub">
					<?php echo esc_html($has_reader_access ? 'everything tied to your reader email lives here: purchases, tier access, dashboard shortcuts, and your bookshelf.' : 'enter the email you use for bybookishbabe or the society. no extra wordpress account needed.'); ?>
				</p>

				<div class="bbb-account-shelf__actions">
					<a class="bbb-account-shelf__backSociety" href="<?php echo esc_url($society_url); ?>">
						<span aria-hidden="true">←</span>
						back to society
					</a>
					<?php if ($has_reader_access) : ?>
						<?php if ($user instanceof WP_User && $user->ID) : ?>
							<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(get_edit_user_link((int) $user->ID)); ?>">edit profile</a>
							<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">log out</a>
						<?php else : ?>
							<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(add_query_arg('bbb_reader_logout', '1', $account_url)); ?>">use a different email</a>
						<?php endif; ?>
					<?php else : ?>
						<a class="bbb-account-shelf__button" href="#reader-email-access">enter email</a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ($has_reader_access) : ?>
				<div class="bbb-account-shelf__panel">
					<div>
						<p class="bbb-account-shelf__perkKicker">your purchases</p>
						<h2><?php echo esc_html($display_name); ?></h2>
						<p><?php echo esc_html($reader_email); ?></p>
					</div>
					<?php if ($purchase_rows) : ?>
						<div class="bbb-account-shelf__purchaseList">
							<?php foreach ($purchase_rows as $purchase) : ?>
								<?php if ('' !== $purchase['url']) : ?>
									<a class="bbb-account-shelf__purchase" href="<?php echo esc_url($purchase['url']); ?>">
										<span>
											<strong><?php echo esc_html($purchase['title']); ?></strong>
											<small><?php echo esc_html($purchase['meta']); ?></small>
										</span>
										<em><?php echo esc_html($purchase['total']); ?></em>
									</a>
								<?php else : ?>
									<div class="bbb-account-shelf__purchase">
										<span>
											<strong><?php echo esc_html($purchase['title']); ?></strong>
											<small><?php echo esc_html($purchase['meta']); ?></small>
										</span>
										<em><?php echo esc_html($purchase['total']); ?></em>
									</div>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<div class="bbb-account-shelf__quiet">
							<strong>no purchases yet</strong>
							<span>your shop orders and digital drops will show here once they are tied to this email.</span>
							<a href="<?php echo esc_url($shop_url); ?>">browse the shop</a>
						</div>
					<?php endif; ?>
				</div>

				<?php if ('society' === $tier) : ?>
					<a class="bbb-account-shelf__dropPrompt" href="<?php echo esc_url($monthly_drop_url); ?>">
						<span class="bbb-account-shelf__dropPromptMain">
							<span class="bbb-account-shelf__perkKicker">monthly drop</span>
							<span class="bbb-account-shelf__dropPromptTitle">your member drop is ready.</span>
							<span class="bbb-account-shelf__dropPromptCopy">open this month's society theme, files, prompts, and member-only extras.</span>
							<span class="bbb-account-shelf__dropPromptCta">open monthly drop</span>
						</span>
						<span class="bbb-account-shelf__dropPromptSide">
							<span class="bbb-account-shelf__perkKicker">daily journal prompt</span>
							<?php if ($show_daily_prompt) : ?>
								<span class="bbb-account-shelf__journalPromptMeta"><?php echo esc_html($daily_prompt_meta); ?></span>
								<span class="bbb-account-shelf__journalPromptBody"><?php echo esc_html($daily_prompt_text); ?></span>
							<?php else : ?>
								<span class="bbb-account-shelf__dropPromptSideTitle">your prompt will appear here.</span>
								<span class="bbb-account-shelf__journalPromptMeta">journal prompts are added from your active monthly drop.</span>
							<?php endif; ?>
						</span>
					</a>
				<?php endif; ?>

				<div class="bbb-account-shelf__previewGrid">
					<a class="bbb-account-shelf__preview" href="<?php echo esc_url($dashboard_url); ?>">
						<p class="bbb-account-shelf__perkKicker">dashboard preview</p>
						<h2>member dashboard</h2>
						<p><?php echo esc_html('society' === $tier ? 'made-for-you reader logic, mood-based recommendations, and smarter next-read picks.' : 'your dashboard preview is here. upgrade to unlock the richer recommendation layer.'); ?></p>
						<span><?php echo esc_html('society' === $tier ? 'open dashboard ->' : 'preview dashboard ->'); ?></span>
					</a>
					<a class="bbb-account-shelf__preview" href="<?php echo esc_url($bookshelf_url); ?>">
						<div class="bbb-account-shelf__previewSplit">
							<div>
								<p class="bbb-account-shelf__perkKicker">bookshelf preview</p>
								<h2>track your reads</h2>
								<p><?php echo esc_html(count($books) . (1 === count($books) ? ' saved book' : ' saved books')); ?> in your personal romance archive.</p>
							</div>
							<div class="bbb-account-shelf__miniShelf" aria-hidden="true">
								<?php foreach ($bookshelf_preview_books as $index => $book) : ?>
									<?php
									$cover = (string) ($book['cover'] ?? '');
									$title = (string) ($book['book_title'] ?? $book['title'] ?? 'book');
									?>
									<?php if ('' !== $cover) : ?>
										<img style="--i: <?php echo esc_attr((string) $index); ?>;" src="<?php echo esc_url($cover); ?>" alt="">
									<?php else : ?>
										<span style="--i: <?php echo esc_attr((string) $index); ?>;"><?php echo esc_html(substr($title, 0, 1)); ?></span>
									<?php endif; ?>
								<?php endforeach; ?>
								<?php if (!$bookshelf_preview_books) : ?>
									<span style="--i: 0;">b</span>
									<span style="--i: 1;">b</span>
									<span style="--i: 2;">b</span>
								<?php endif; ?>
							</div>
						</div>
						<span>open bookshelf -></span>
					</a>
				</div>
			<?php else : ?>
				<div class="bbb-account-shelf__empty" id="reader-email-access">
					<div class="bbb-account-shelf__emptyIcon" aria-hidden="true">*</div>
					<h2>open your reader account.</h2>
					<p>use the same email you use for bybookishbabe or the smut & sentiment society.</p>
					<form class="bbb-account-shelf__emailForm" data-reader-email-access-form>
						<label class="screen-reader-text" for="bbb-reader-email">reader email</label>
						<input id="bbb-reader-email" type="email" name="email" autocomplete="email" placeholder="you@example.com" required>
						<button type="submit">open account</button>
						<p class="bbb-account-shelf__formStatus" data-reader-email-access-status hidden></p>
					</form>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
