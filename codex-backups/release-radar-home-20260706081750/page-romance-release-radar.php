<?php
/**
 * Template Name: Romance Release Radar
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

const BBB_RRR_SEO_TITLE = 'romance release radar: new romance releases this month | bybookishbabe';
const BBB_RRR_SEO_DESCRIPTION = 'every new romance release, tracked by date and trope — dark romance, romantasy, sports romance, and more. updated every monday.';

add_filter(
	'wp_robots',
	static function (array $robots): array {
		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = true;
		$robots['follow'] = true;
		return $robots;
	}
);

add_filter(
	'rank_math/frontend/robots',
	static function (array $robots): array {
		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = 'index';
		$robots['follow'] = 'follow';
		return $robots;
	},
	1000
);

add_filter(
	'pre_get_document_title',
	static fn(string $title): string => BBB_RRR_SEO_TITLE,
	1000
);

add_filter(
	'document_title_parts',
	static function (array $parts): array {
		$parts['title'] = BBB_RRR_SEO_TITLE;
		unset($parts['site'], $parts['tagline']);
		return $parts;
	},
	1000
);

add_filter('rank_math/frontend/title', static fn(string $title): string => BBB_RRR_SEO_TITLE, 1000);
add_filter('rank_math/opengraph/facebook/title', static fn(string $title): string => BBB_RRR_SEO_TITLE, 1000);
add_filter('rank_math/opengraph/twitter/title', static fn(string $title): string => BBB_RRR_SEO_TITLE, 1000);
add_filter('wpseo_title', static fn(string $title): string => BBB_RRR_SEO_TITLE, 1000);
add_filter('wpseo_opengraph_title', static fn(string $title): string => BBB_RRR_SEO_TITLE, 1000);
add_filter('wpseo_twitter_title', static fn(string $title): string => BBB_RRR_SEO_TITLE, 1000);

add_filter('rank_math/frontend/description', static fn(string $description): string => BBB_RRR_SEO_DESCRIPTION, 1000);
add_filter('rank_math/opengraph/facebook/description', static fn(string $description): string => BBB_RRR_SEO_DESCRIPTION, 1000);
add_filter('rank_math/opengraph/twitter/description', static fn(string $description): string => BBB_RRR_SEO_DESCRIPTION, 1000);
add_filter('wpseo_metadesc', static fn(string $description): string => BBB_RRR_SEO_DESCRIPTION, 1000);
add_filter('wpseo_opengraph_desc', static fn(string $description): string => BBB_RRR_SEO_DESCRIPTION, 1000);
add_filter('wpseo_twitter_description', static fn(string $description): string => BBB_RRR_SEO_DESCRIPTION, 1000);

bbb_enqueue_css('bbb-cta-design-system', 'assets/css/cta-design-system.css');
bbb_enqueue_css('bbb-romance-release-radar', 'assets/css/romance-release-radar.css', array('bbb-cta-design-system'));
bbb_enqueue_js('bbb-romance-release-radar', 'assets/js/romance-release-radar.js', array(), true);

if (!function_exists('bbb_rrr_month_start')) {
	function bbb_rrr_month_start(?DateTimeImmutable $date = null): DateTimeImmutable {
		$date = $date ?: new DateTimeImmutable('now', wp_timezone());
		return $date->modify('first day of this month')->setTime(0, 0);
	}
}

if (!function_exists('bbb_rrr_month_label')) {
	function bbb_rrr_month_label(DateTimeImmutable $month): string {
		return strtolower($month->format('F Y'));
	}
}

if (!function_exists('bbb_rrr_sweep_monday')) {
	function bbb_rrr_sweep_monday(?DateTimeImmutable $date = null): DateTimeImmutable {
		$date = $date ?: new DateTimeImmutable('now', wp_timezone());
		return $date->modify('monday this week')->setTime(0, 0);
	}
}

if (!function_exists('bbb_rrr_next_sweep_monday')) {
	function bbb_rrr_next_sweep_monday(?DateTimeImmutable $date = null): DateTimeImmutable {
		$date = $date ?: new DateTimeImmutable('now', wp_timezone());
		return bbb_rrr_sweep_monday($date)->modify('+1 week');
	}
}

if (!function_exists('bbb_rrr_seconds_until_next_sweep')) {
	function bbb_rrr_seconds_until_next_sweep(?DateTimeImmutable $date = null): int {
		$date = $date ?: new DateTimeImmutable('now', wp_timezone());
		$next_sweep = bbb_rrr_next_sweep_monday($date);
		return max(HOUR_IN_SECONDS, $next_sweep->getTimestamp() - $date->getTimestamp());
	}
}

if (!function_exists('bbb_rrr_cover_url')) {
	function bbb_rrr_cover_url(array $doc): string {
		if (!empty($doc['cover_i'])) {
			return 'https://covers.openlibrary.org/b/id/' . rawurlencode((string) $doc['cover_i']) . '-L.jpg';
		}

		return '';
	}
}

if (!function_exists('bbb_rrr_amazon_search_url')) {
	function bbb_rrr_amazon_search_url(string $title, string $author): string {
		return add_query_arg(
			array(
				'k' => trim($title . ' ' . $author),
			),
			'https://www.amazon.com/s'
		);
	}
}

if (!function_exists('bbb_rrr_release_seeds')) {
	function bbb_rrr_release_seeds(): array {
		return array(
			array(
				'title'      => 'the romance revival',
				'author'     => 'christina lauren',
				'date'       => '2026-07-14',
				'tropes'     => array('romance', 'speculative', 'second chance'),
				'source_url' => 'https://www.southernliving.com/new-books-to-read-july-2026-11986024',
				'cover_url'  => 'https://cdn.hachette.com.au/books/9780349440439.jpg',
			),
			array(
				'title'      => 'in stormy weather',
				'author'     => 'chelsea curto',
				'date'       => '2026-07-14',
				'tropes'     => array('romance', 'enemies to lovers', 'contemporary'),
				'source_url' => 'https://people.com/in-stormy-weather-cover-reveal-excerpt-exclusive-11839430',
				'cover_url'  => 'https://static.showit.co/400/xJ7DF7L0sB7L5Kq_66ZAMw/285807/in_stormy_weather_isw_audiobook_mockup.png',
			),
			array(
				'title'      => 'steelborn',
				'author'     => 'taylor j larue',
				'date'       => '2026-07-28',
				'tropes'     => array('romantasy', 'fantasy romance', 'metadata'),
				'source_url' => 'https://en.wikipedia.org/wiki/FairyLoot',
			),
		);
	}
}

if (!function_exists('bbb_rrr_find_open_library_doc')) {
	function bbb_rrr_find_open_library_doc(string $title, string $author): array {
		$sweep_monday = bbb_rrr_sweep_monday();
		$cache_key = 'bbb_rrr_open_library_enrich_' . md5(strtolower($title . '|' . $author)) . '_' . $sweep_monday->format('Ymd');
		$cache_ttl = bbb_rrr_seconds_until_next_sweep();
		$cached = get_transient($cache_key);
		if (is_array($cached)) {
			return $cached;
		}

		$url = add_query_arg(
			array(
				'title'        => $title,
				'author'       => $author,
				'fields'       => 'title,author_name,cover_i,first_publish_year,publish_date,isbn,key',
				'limit'        => '5',
			),
			'https://openlibrary.org/search.json'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 8,
				'headers' => array(
					'User-Agent' => 'bybookishbabe romance release radar; ' . home_url('/'),
				),
			)
		);

		if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
			set_transient($cache_key, array(), HOUR_IN_SECONDS);
			return array();
		}

		$payload = json_decode(wp_remote_retrieve_body($response), true);
		$docs = is_array($payload) && !empty($payload['docs']) && is_array($payload['docs']) ? $payload['docs'] : array();
		$doc = !empty($docs[0]) && is_array($docs[0]) ? $docs[0] : array();
		set_transient($cache_key, $doc, $cache_ttl);

		return $doc;
	}
}

if (!function_exists('bbb_rrr_books_for_month')) {
	function bbb_rrr_books_for_month(DateTimeImmutable $month, int $limit = 16): array {
		$books = array();

		foreach (bbb_rrr_release_seeds() as $seed) {
			if (empty($seed['title']) || empty($seed['author']) || empty($seed['date'])) {
				continue;
			}

			try {
				$release_date = new DateTimeImmutable((string) $seed['date'], wp_timezone());
			} catch (Exception $e) {
				continue;
			}

			if ($release_date->format('Y-m') !== $month->format('Y-m')) {
				continue;
			}

			$doc = bbb_rrr_find_open_library_doc((string) $seed['title'], (string) $seed['author']);

			$books[] = array(
				'title'       => (string) $seed['title'],
				'author'      => (string) $seed['author'],
				'date'        => $release_date,
				'cover'       => !empty($seed['cover_url']) ? (string) $seed['cover_url'] : bbb_rrr_cover_url($doc),
					'tropes'      => !empty($seed['tropes']) && is_array($seed['tropes']) ? $seed['tropes'] : array('romance'),
					'source_url'  => !empty($seed['source_url']) ? (string) $seed['source_url'] : 'https://openlibrary.org/search?title=' . rawurlencode((string) $seed['title']),
					'amazon_url'  => !empty($seed['amazon_url']) ? (string) $seed['amazon_url'] : bbb_rrr_amazon_search_url((string) $seed['title'], (string) $seed['author']),
					'is_fallback' => false,
				);

			if (count($books) >= $limit) {
				break;
			}
		}

		usort(
			$books,
			static function (array $a, array $b): int {
				$a_time = $a['date'] instanceof DateTimeImmutable ? $a['date']->getTimestamp() : 0;
				$b_time = $b['date'] instanceof DateTimeImmutable ? $b['date']->getTimestamp() : 0;
				return $a_time <=> $b_time;
			}
		);

		return array_slice($books, 0, $limit);
	}
}

if (!function_exists('bbb_rrr_group_by_week')) {
	function bbb_rrr_group_by_week(array $books): array {
		$weeks = array();
		foreach ($books as $book) {
			$date = $book['date'];
			$week = $date instanceof DateTimeImmutable ? $date->modify('monday this week') : new DateTimeImmutable('monday this week', wp_timezone());
			$key = $week->format('Y-m-d');
			$weeks[$key]['label'] = strtolower('week of ' . $week->format('F j'));
			$weeks[$key]['books'][] = $book;
		}

		return $weeks;
	}
}

$radar_month = bbb_rrr_month_start();
$radar_books = bbb_rrr_books_for_month($radar_month);
$radar_weeks = bbb_rrr_group_by_week($radar_books);
$archive_months = array(
	$radar_month->modify('-1 month'),
	$radar_month->modify('-2 months'),
	$radar_month->modify('-3 months'),
);
$last_swept = strtolower(bbb_rrr_sweep_monday()->format('M j, Y'));
$next_sweep = strtolower(bbb_rrr_next_sweep_monday()->format('M j, Y'));
$reader_identity = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
$reader_email = is_array($reader_identity) ? (string) ($reader_identity['email'] ?? '') : '';
$reader_user_id = is_array($reader_identity) ? (int) ($reader_identity['userId'] ?? 0) : 0;
$reader_subscriber = null;
if ('' !== $reader_email && function_exists('bbb_reader_fetch_subscriber_by_email')) {
	$reader_subscriber = bbb_reader_fetch_subscriber_by_email($reader_email);
	$reader_subscriber = is_wp_error($reader_subscriber) ? null : $reader_subscriber;
}
$reader_access_tier = '' !== $reader_email && function_exists('bbb_reader_access_tier_for_email')
	? bbb_reader_access_tier_for_email($reader_email, $reader_user_id, is_array($reader_subscriber) ? $reader_subscriber : null)
	: 'free';
$reader_is_society = 'society' === $reader_access_tier;
$radar_alerts_connected = function_exists('bbb_reader_update_radar_alerts_for_identity') && function_exists('bbb_reader_radar_alerts_enabled_for_subscriber');
$radar_alerts_enabled = $radar_alerts_connected && bbb_reader_radar_alerts_enabled_for_subscriber(is_array($reader_subscriber) ? $reader_subscriber : null);
$radar_account_url = function_exists('bbb_page_url') ? bbb_page_url('account') : home_url('/account/');
$radar_join_url = (string) get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');

wp_localize_script(
	'bbb-romance-release-radar',
	'bbbRomanceReleaseRadar',
	array(
		'alertsEndpoint' => set_url_scheme(rest_url('bbb/v1/reader-account/radar-alerts'), is_ssl() ? 'https' : 'http'),
		'nonce'          => wp_create_nonce('wp_rest'),
	)
);

get_header();
?>

<section class="rrr-page" data-radar-page>
	<div class="rrr-topper">
		<span>private local mockup</span>
		<strong>romance release radar</strong>
		<span><?php echo esc_html(count($radar_books)); ?> blips pulled for <?php echo esc_html(bbb_rrr_month_label($radar_month)); ?></span>
	</div>

	<section class="rrr-hero">
		<div class="rrr-wrap rrr-hero__grid">
				<div class="rrr-hero__copy">
					<p class="rrr-eyebrow"><span></span> updated weekly on mondays</p>
					<h1><span>romance release</span> <em>radar</em></h1>
						<p>track new romance releases before they make it into the bybookishbabe library.</p>
				<div class="rrr-actions">
					<a class="rrr-btn rrr-btn--primary" href="#rrr-alerts">get radar alerts</a>
					<a class="rrr-btn rrr-btn--ghost" href="#rrr-month">see this month</a>
				</div>
			</div>
				<div class="rrr-radar" aria-label="<?php esc_attr_e('animated radar illustration with release blips', 'bybookishbabe-shopify-port'); ?>">
					<div class="rrr-radar__disc">
						<div class="rrr-radar__ring rrr-radar__ring--one"></div>
						<div class="rrr-radar__ring rrr-radar__ring--two"></div>
						<div class="rrr-radar__ring rrr-radar__ring--three"></div>
						<div class="rrr-radar__sweep"></div>
						<div class="rrr-radar__center"></div>
					<?php foreach (array_slice($radar_books, 0, 6) as $index => $book) : ?>
						<?php
						$positions = array(
							array('22%', '60%'),
							array('38%', '24%'),
							array('65%', '70%'),
							array('74%', '38%'),
							array('30%', '78%'),
							array('52%', '15%'),
						);
						$position = $positions[$index] ?? array('50%', '50%');
						$label = sprintf('%s - %s', (string) $book['title'], $book['date'] instanceof DateTimeImmutable ? strtolower($book['date']->format('M j')) : '');
						?>
						<span class="rrr-radar__blip<?php echo 0 === $index % 2 ? ' is-new' : ''; ?>" style="top:<?php echo esc_attr($position[0]); ?>;left:<?php echo esc_attr($position[1]); ?>;" data-label="<?php echo esc_attr($label); ?>"></span>
					<?php endforeach; ?>
				</div>
				<p>hover a blip &middot; bright marks need editorial review</p>
			</div>
		</div>
	</section>

	<section class="rrr-section" id="rrr-month">
		<div class="rrr-wrap">
			<header class="rrr-section__head">
					<div>
						<h2><?php echo esc_html(bbb_rrr_month_label($radar_month)); ?> radar</h2>
						<p><?php echo esc_html(count($radar_books)); ?> releases coming</p>
					</div>
				<span><?php echo esc_html(count($radar_weeks)); ?> weeks &middot; <?php echo esc_html(count($radar_books)); ?> books</span>
			</header>

				<?php if (empty($radar_weeks)) : ?>
					<div class="rrr-empty">
						<strong>no confirmed <?php echo esc_html(bbb_rrr_month_label($radar_month)); ?> releases yet</strong>
						<p>the weekly seed list does not have confirmed <?php echo esc_html(bbb_rrr_month_label($radar_month)); ?> titles yet. add dated releases to the seed list and the page will enrich covers from book metadata when available.</p>
					</div>
				<?php else : ?>
					<?php foreach ($radar_weeks as $week) : ?>
						<section class="rrr-week">
							<div class="rrr-week__head"><span><?php echo esc_html((string) $week['label']); ?></span><i></i></div>
							<div class="rrr-grid">
								<?php foreach ($week['books'] as $book) : ?>
									<?php
									$title = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title((string) $book['title']) : (string) $book['title'];
									$author = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name((string) $book['author']) : (string) $book['author'];
									$cover = (string) $book['cover'];
									$date = $book['date'] instanceof DateTimeImmutable ? strtolower($book['date']->format('M j, Y')) : '';
									$tropes = !empty($book['tropes']) && is_array($book['tropes']) ? $book['tropes'] : array('romance');
									$amazon_url = (string) ($book['amazon_url'] ?? '');
									?>
									<article class="rrr-card" data-radar-card data-radar-tags="<?php echo esc_attr(implode(' ', array_map('sanitize_title', $tropes))); ?>">
										<div class="rrr-card__cover">
											<?php if ('' !== $cover) : ?>
												<img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr(function_exists('bbb_book_cover_alt') ? bbb_book_cover_alt($title, $author, 'release radar') : $title . ' book cover'); ?>" loading="lazy">
											<?php else : ?>
												<div class="rrr-card__placeholder"><span><?php echo esc_html($title); ?></span></div>
											<?php endif; ?>
										</div>
										<div class="rrr-card__body">
											<span class="rrr-card__date"><?php echo esc_html($date); ?></span>
											<h3><?php echo esc_html($title); ?></h3>
											<p><?php echo esc_html($author); ?></p>
											<div class="rrr-card__status">on my radar &middot; not yet read</div>
											<div class="rrr-card__links">
												<a class="rrr-card__primary rrr-card__amazon" href="<?php echo esc_url($amazon_url); ?>" target="_blank" rel="noopener sponsored">check out book</a>
											</div>
										</div>
									</article>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endforeach; ?>
				<?php endif; ?>
		</div>
	</section>

		<section class="rrr-wrap" id="rrr-alerts">
			<div class="rrr-alerts">
				<div>
					<h2>never miss a blip</h2>
					<?php if ($reader_is_society && $radar_alerts_connected) : ?>
						<p>radar alerts are tied to your society account. turn them on here and we will mark your subscriber record for weekly release pings.</p>
					<?php elseif ($reader_is_society) : ?>
						<p>radar alerts are tied to your society account. weekly release pings are being connected to subscriber records now.</p>
					<?php elseif ('' !== $reader_email) : ?>
						<p>radar alerts are a society perk. you are logged in as <?php echo esc_html($reader_email); ?>; subscribe to the society to unlock weekly release pings.</p>
					<?php else : ?>
						<p>subscribe to the society, then log in with that email to turn on weekly release pings.</p>
					<?php endif; ?>
				</div>
				<?php if ($reader_is_society && $radar_alerts_connected) : ?>
					<form action="#" method="post" data-radar-alert-form>
						<button
							type="submit"
							data-radar-alert-toggle
							aria-pressed="<?php echo esc_attr($radar_alerts_enabled ? 'true' : 'false'); ?>"
							data-enabled="<?php echo esc_attr($radar_alerts_enabled ? '1' : '0'); ?>"
						>
							<?php echo esc_html($radar_alerts_enabled ? 'radar alerts on' : 'turn on radar alerts'); ?>
						</button>
						<p class="rrr-alerts__status" data-radar-alert-status aria-live="polite">
							<?php echo esc_html($radar_alerts_enabled ? 'you are set for radar alerts.' : 'off for now.'); ?>
						</p>
					</form>
				<?php else : ?>
					<div class="rrr-alerts__actions">
						<a class="rrr-btn rrr-btn--primary" href="<?php echo esc_url($radar_join_url); ?>" target="_blank" rel="noopener">subscribe to the society</a>
						<a class="rrr-btn rrr-btn--ghost" href="<?php echo esc_url($radar_account_url . '#reader-email-access'); ?>">log in</a>
					</div>
				<?php endif; ?>
			</div>
		</section>

	<section class="rrr-section rrr-archive">
		<div class="rrr-wrap">
			<header class="rrr-section__head">
				<div>
					<h2>past sweeps</h2>
					<p>archived months can stay browsable after the current radar rolls over.</p>
				</div>
				</header>
				<div class="rrr-archive__list">
					<div class="rrr-archive__empty">
						<p>past sweeps will begin after this month rolls over.</p>
					</div>
				</div>
			</div>
		</section>
</section>

<?php
get_footer();
