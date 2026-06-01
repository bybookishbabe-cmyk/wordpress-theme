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
$account_data = array();
$account_error = null;

if ($has_reader_access && function_exists('bbb_reader_account_response_for_identity')) {
	try {
		$account_data = bbb_reader_account_response_for_identity((array) $identity);
	} catch (Throwable $error) {
		$account_error = $error;
		error_log('BBB account page failed softly: ' . $error->getMessage());
		$account_data = array(
			'accessTier' => 'free',
			'books'      => array(),
			'readerType' => array(
				'title'     => 'fresh shelf romantic',
				'summary'   => 'your account opened, but the bookshelf sync needs a retry.',
				'topTropes' => array(),
				'counts'    => array('saved' => 0, 'read' => 0, 'reading' => 0, 'tbr' => 0),
			),
			'nextRead'   => null,
		);
	}
}
$books        = isset($account_data['books']) && is_array($account_data['books']) ? $account_data['books'] : array();
$reader_type  = isset($account_data['readerType']) && is_array($account_data['readerType']) ? $account_data['readerType'] : array(
	'title'     => 'fresh shelf romantic',
	'summary'   => 'save or tag a few books and this will start calling your pattern.',
	'topTropes' => array(),
	'counts'    => array('saved' => count($books), 'read' => 0, 'reading' => 0, 'tbr' => 0),
);
$next_read    = isset($account_data['nextRead']) && is_array($account_data['nextRead']) ? $account_data['nextRead'] : null;
$tier         = (string) ($account_data['accessTier'] ?? 'free');
$is_society   = 'society' === $tier || (function_exists('bbb_reader_is_society') && bbb_reader_is_society());
$tier         = $is_society ? 'society' : $tier;
$display_name = ($identity && '' !== trim((string) ($identity['displayName'] ?? ''))) ? (string) $identity['displayName'] : 'reader';
$tier_label   = 'society' === $tier ? 'tier: paid society member' : ($has_reader_access ? 'tier: free reader member' : 'tier: visitor');
$account_url  = function_exists('bbb_page_url') ? bbb_page_url('account') : home_url('/account/');
$bookshelf_url = function_exists('bbb_page_url') ? bbb_page_url('my-bookshelf') : home_url('/my-bookshelf/');
$dashboard_url = function_exists('bbb_page_url') ? bbb_page_url('member-dashboard') : home_url('/member-dashboard/');
$monthly_drop_url = function_exists('bbb_page_url') ? bbb_page_url('monthly-theme') : home_url('/monthly-theme/');
$society_url = function_exists('bbb_page_url') ? bbb_page_url('smut-sentiment-society') : home_url('/smut-sentiment-society/');
$society_join_url = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$society_join_url = '' !== trim((string) $society_join_url) ? (string) $society_join_url : 'https://thesmutandsentimentsociety.substack.com/subscribe';
$shop_url = function_exists('bbb_page_url') ? bbb_page_url('shop') : home_url('/shop/');
$daily_prompt = is_array($account_data['dailyJournalPrompt'] ?? null) ? $account_data['dailyJournalPrompt'] : array();
$daily_prompt_text = isset($daily_prompt['text']) ? trim((string) $daily_prompt['text']) : '';
$daily_prompt_day = isset($daily_prompt['day']) ? (int) $daily_prompt['day'] : 0;
$daily_prompt_total = isset($daily_prompt['total']) ? (int) $daily_prompt['total'] : 0;
$show_daily_prompt = $is_society && '' !== $daily_prompt_text;
$reader_logged_out = isset($_GET['bbb_reader_logged_out']);
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
$reader_type_title = trim((string) ($reader_type['title'] ?? 'fresh shelf romantic'));
$reader_type_summary = trim((string) ($reader_type['summary'] ?? 'save or tag a few books and this will start calling your pattern.'));
$reader_type_counts = is_array($reader_type['counts'] ?? null) ? $reader_type['counts'] : array();
$reader_type_tropes = is_array($reader_type['topTropes'] ?? null) ? array_values(array_filter($reader_type['topTropes'])) : array();
$next_read_title = $next_read ? trim((string) ($next_read['book_title'] ?? $next_read['title'] ?? '')) : '';
$next_read_title = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($next_read_title) : $next_read_title;
$next_read_author = $next_read ? trim((string) ($next_read['author'] ?? '')) : '';
$next_read_author = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($next_read_author) : $next_read_author;
$next_read_cover = $next_read ? trim((string) ($next_read['cover'] ?? '')) : '';
$next_read_handle = $next_read ? sanitize_title((string) ($next_read['book_handle'] ?? $next_read['handle'] ?? '')) : '';
$next_read_amazon = $next_read ? trim((string) ($next_read['amazon'] ?? '')) : '';
$next_read_bookshop = $next_read ? trim((string) ($next_read['bookshop'] ?? '')) : '';
$next_read_spice = $next_read ? (int) ($next_read['spice_level'] ?? $next_read['spice'] ?? 0) : 0;
$next_read_darkness = $next_read ? (int) ($next_read['darkness_level'] ?? $next_read['darkness'] ?? 0) : 0;
$next_read_tropes = $next_read ? array_slice(
	array_values(
		array_filter(
			array_map(
				'trim',
				is_array($next_read['tropes'] ?? null) ? (array) $next_read['tropes'] : preg_split('/[,|]/', (string) ($next_read['tropes'] ?? ''))
			)
		)
	),
	0,
	3
) : array();
$next_read_tropes_text = implode(', ', $next_read_tropes);

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
	<section
		class="bbb-account-shelf"
		data-sss-lib="<?php echo esc_attr($is_society ? 'society' : 'public'); ?>"
	>
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
							<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(add_query_arg('bbb_reader_logout', '1', $account_url)); ?>">use a different email</a>
						<?php else : ?>
							<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(add_query_arg('bbb_reader_logout', '1', $account_url)); ?>">use a different email</a>
						<?php endif; ?>
					<?php else : ?>
						<a class="bbb-account-shelf__button" href="#reader-email-access">enter email</a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ($has_reader_access) : ?>
				<section class="bbb-account-shelf__readerProfile" aria-label="reader type">
					<div class="bbb-account-shelf__readerType">
						<p class="bbb-account-shelf__perkKicker">reader type</p>
						<h2><?php echo esc_html($reader_type_title); ?></h2>
						<p><?php echo esc_html($reader_type_summary); ?></p>
						<div class="bbb-account-shelf__readerStats" aria-label="bookshelf stats">
							<span><?php echo esc_html((string) ($reader_type_counts['saved'] ?? count($books))); ?> saved</span>
							<span><?php echo esc_html((string) ($reader_type_counts['read'] ?? 0)); ?> read</span>
							<span><?php echo esc_html((string) ($reader_type_counts['reading'] ?? 0)); ?> reading</span>
							<span><?php echo esc_html((string) ($reader_type_counts['tbr'] ?? 0)); ?> tbr</span>
						</div>
						<?php if ($reader_type_tropes) : ?>
							<div class="bbb-account-shelf__readerSignals" aria-label="top reader signals">
								<?php foreach (array_slice($reader_type_tropes, 0, 3) as $trope) : ?>
									<span><?php echo esc_html((string) $trope); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="bbb-account-shelf__nextRead<?php echo 'society' === $tier ? '' : ' bbb-account-shelf__nextRead--locked'; ?>">
						<p class="bbb-account-shelf__perkKicker"><?php echo esc_html('society' === $tier ? 'what we suggest you read next' : 'member preview'); ?></p>
						<?php if ($next_read_title) : ?>
							<?php if ('society' === $tier) : ?>
								<button
									type="button"
									class="bbb-account-shelf__nextReadCard"
									data-book-preview
									data-handle="<?php echo esc_attr($next_read_handle); ?>"
									data-title="<?php echo esc_attr($next_read_title); ?>"
									data-author="<?php echo esc_attr($next_read_author); ?>"
									data-cover="<?php echo esc_attr($next_read_cover); ?>"
									data-amazon="<?php echo esc_attr($next_read_amazon); ?>"
									data-bookshop="<?php echo esc_attr($next_read_bookshop); ?>"
									data-spice="<?php echo esc_attr((string) min(max($next_read_spice, 0), 5)); ?>"
									data-tropes="<?php echo esc_attr($next_read_tropes_text); ?>"
									data-tropes-display="<?php echo esc_attr($next_read_tropes_text); ?>"
									data-darkness="<?php echo esc_attr((string) min(max($next_read_darkness, 0), 5)); ?>"
									data-private-shelf="false"
									aria-label="<?php echo esc_attr(sprintf('Open %s book details', $next_read_title)); ?>"
								>
							<?php else : ?>
								<a class="bbb-account-shelf__nextReadCard" href="<?php echo esc_url($society_join_url); ?>" target="_blank" rel="noopener">
							<?php endif; ?>
								<span class="bbb-account-shelf__nextReadCover" aria-hidden="true">
									<?php if ('' !== $next_read_cover) : ?>
										<img src="<?php echo esc_url($next_read_cover); ?>" alt="">
									<?php else : ?>
										<span><?php echo esc_html(substr($next_read_title, 0, 1)); ?></span>
									<?php endif; ?>
								</span>
								<span class="bbb-account-shelf__nextReadBody">
									<span class="bbb-account-shelf__nextReadTitle"><?php echo esc_html('society' === $tier ? $next_read_title : 'hidden next-read pick'); ?></span>
									<?php if ('society' === $tier && '' !== $next_read_author) : ?>
										<span class="bbb-account-shelf__nextReadMeta">by <?php echo esc_html($next_read_author); ?></span>
									<?php else : ?>
										<span class="bbb-account-shelf__nextReadMeta">paid members see the book pulled from their dashboard.</span>
									<?php endif; ?>
									<?php if ('society' === $tier && ($next_read_tropes || $next_read_spice > 0)) : ?>
										<span class="bbb-account-shelf__nextReadTags">
											<?php if ($next_read_spice > 0) : ?>
												<span><?php echo esc_html('heat x' . (string) min($next_read_spice, 5)); ?></span>
											<?php endif; ?>
											<?php foreach ($next_read_tropes as $trope) : ?>
												<span><?php echo esc_html((string) $trope); ?></span>
											<?php endforeach; ?>
										</span>
									<?php endif; ?>
									<span class="bbb-account-shelf__nextReadCta"><?php echo esc_html('society' === $tier ? 'open the pick ->' : 'unlock the pick ->'); ?></span>
								</span>
							<?php if ('society' === $tier) : ?>
								</button>
							<?php else : ?>
								</a>
							<?php endif; ?>
						<?php else : ?>
							<div class="bbb-account-shelf__nextReadEmpty">
								<strong>your next pick is waiting for a little more shelf data.</strong>
								<span>save or tag a few books and this space will get smarter.</span>
							</div>
						<?php endif; ?>
					</div>
				</section>

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
					<a
						class="bbb-account-shelf__preview<?php echo 'society' === $tier ? '' : ' bbb-account-shelf__preview--locked'; ?>"
						href="<?php echo esc_url('society' === $tier ? $dashboard_url : $society_join_url); ?>"
						<?php if ('society' !== $tier) : ?>
							target="_blank"
							rel="noopener"
						<?php endif; ?>
					>
						<p class="bbb-account-shelf__perkKicker">dashboard preview</p>
						<h2>member dashboard</h2>
						<p><?php echo esc_html('society' === $tier ? 'made-for-you reader logic, mood-based recommendations, and smarter next-read picks.' : 'your dashboard preview is here. upgrade to unlock the richer recommendation layer.'); ?></p>
						<span><?php echo esc_html('society' === $tier ? 'open dashboard ->' : 'unlock dashboard ->'); ?></span>
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
									$title = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($title) : $title;
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
						<p class="bbb-account-shelf__formStatus" data-reader-email-access-status data-tone="<?php echo $reader_logged_out ? esc_attr('success') : ''; ?>"<?php echo $reader_logged_out ? '' : ' hidden'; ?>><?php echo $reader_logged_out ? esc_html('you are logged out. enter the email you want to use next.') : ''; ?></p>
					</form>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
