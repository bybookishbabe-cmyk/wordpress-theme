<?php
/**
 * Template Name: Reader Account
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$my_bookshelf_css_path = get_theme_file_path('assets/css/my-bookshelf.css');
wp_enqueue_style('bbb-my-bookshelf', get_theme_file_uri('assets/css/my-bookshelf.css'), array('bbb-sss-library'), file_exists($my_bookshelf_css_path) ? (string) filemtime($my_bookshelf_css_path) : wp_get_theme()->get('Version'));

$is_logged_in = is_user_logged_in();
$user         = $is_logged_in ? wp_get_current_user() : null;
$is_society   = ($user instanceof WP_User && function_exists('bbb_reader_access_tier') && 'society' === bbb_reader_access_tier((int) $user->ID));
$account_data = ($is_logged_in && $user instanceof WP_User && function_exists('bbb_reader_account_response'))
	? bbb_reader_account_response($user)
	: array();
$books        = isset($account_data['books']) && is_array($account_data['books']) ? $account_data['books'] : array();
$tier         = $is_society ? 'society' : (string) ($account_data['accessTier'] ?? 'free');
$display_name = ($user instanceof WP_User && '' !== trim((string) $user->display_name)) ? (string) $user->display_name : 'reader';
$tier_label   = 'society' === $tier ? 'tier: paid society member' : ($is_logged_in ? 'tier: free reader member' : 'tier: visitor');
$account_url  = function_exists('bbb_page_url') ? bbb_page_url('account') : home_url('/account/');
$bookshelf_url = function_exists('bbb_page_url') ? bbb_page_url('my-bookshelf') : home_url('/my-bookshelf/');
$dashboard_url = function_exists('bbb_page_url') ? bbb_page_url('member-dashboard') : home_url('/member-dashboard/');
$monthly_drop_url = function_exists('bbb_page_url') ? bbb_page_url('monthly-theme') : home_url('/monthly-theme/');
$society_url = function_exists('bbb_page_url') ? bbb_page_url('smut-sentiment-society') : home_url('/smut-sentiment-society/');
$shop_url = function_exists('bbb_page_url') ? bbb_page_url('shop') : home_url('/shop/');
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

if ($is_logged_in && $user instanceof WP_User && function_exists('wc_get_orders')) {
	$order_args = array(
		'limit'   => 4,
		'orderby' => 'date',
		'order'   => 'DESC',
		'status'  => array('wc-completed', 'wc-processing', 'wc-on-hold'),
	);
	$orders_by_id = wc_get_orders(
		array(
			'customer_id' => (int) $user->ID,
		)
		+ $order_args
	);
	$orders_by_email = wc_get_orders(
		array(
			'billing_email' => (string) $user->user_email,
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
			'url'    => $order->get_view_order_url(),
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
					<?php echo esc_html($is_logged_in ? 'everything tied to your reader life lives here: purchases, tier access, dashboard shortcuts, and your bookshelf.' : 'log in or create an account to keep purchases, tier access, and your bookshelf in one place.'); ?>
				</p>

				<div class="bbb-account-shelf__actions">
					<a class="bbb-account-shelf__backSociety" href="<?php echo esc_url($society_url); ?>">
						<span aria-hidden="true">←</span>
						back to society
					</a>
					<?php if ($is_logged_in) : ?>
						<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(get_edit_user_link((int) $user->ID)); ?>">edit profile</a>
						<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">log out</a>
					<?php else : ?>
						<a class="bbb-account-shelf__button" href="<?php echo esc_url(wp_login_url(home_url('/account/'))); ?>">log in</a>
						<?php if (get_option('users_can_register')) : ?>
							<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(wp_registration_url()); ?>">create account</a>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

			<?php if ($is_logged_in && $user instanceof WP_User) : ?>
				<div class="bbb-account-shelf__panel">
					<div>
						<p class="bbb-account-shelf__perkKicker">your purchases</p>
						<h2><?php echo esc_html($display_name); ?></h2>
						<p><?php echo esc_html((string) $user->user_email); ?></p>
					</div>
					<?php if ($purchase_rows) : ?>
						<div class="bbb-account-shelf__purchaseList">
							<?php foreach ($purchase_rows as $purchase) : ?>
								<a class="bbb-account-shelf__purchase" href="<?php echo esc_url($purchase['url']); ?>">
									<span>
										<strong><?php echo esc_html($purchase['title']); ?></strong>
										<small><?php echo esc_html($purchase['meta']); ?></small>
									</span>
									<em><?php echo esc_html($purchase['total']); ?></em>
								</a>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<div class="bbb-account-shelf__quiet">
							<strong>no purchases yet</strong>
							<span>your shop orders and digital drops will show here once they are tied to this account.</span>
							<a href="<?php echo esc_url($shop_url); ?>">browse the shop</a>
						</div>
					<?php endif; ?>
				</div>

				<?php if ('society' === $tier) : ?>
					<div class="bbb-account-shelf__dropCta">
						<p class="bbb-account-shelf__perkKicker">monthly drop</p>
						<h2>your member drop is ready.</h2>
						<p>open this month's society theme, files, prompts, and member-only extras.</p>
						<a class="bbb-account-shelf__button" href="<?php echo esc_url($monthly_drop_url); ?>">open monthly drop</a>
					</div>
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
				<div class="bbb-account-shelf__empty">
					<div class="bbb-account-shelf__emptyIcon" aria-hidden="true">*</div>
					<h2>your reader account is waiting.</h2>
					<p>create a wordpress account with the same email you use for society access, then purchases, tier access, and your shelf can follow you.</p>
					<a href="<?php echo esc_url(wp_login_url($account_url)); ?>">log in</a>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
