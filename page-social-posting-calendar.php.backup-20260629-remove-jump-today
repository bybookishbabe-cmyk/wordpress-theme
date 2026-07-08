<?php
/**
 * Private creator social posting calendar.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!current_user_can('manage_options')) {
	status_header(404);
	nocache_headers();
	if (!headers_sent()) {
		header('X-Robots-Tag: noindex, nofollow', true);
	}
	echo '<!doctype html><html><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title>Not found</title></head><body>Not found.</body></html>';
	exit;
}

bbb_social_calendar_save_settings_from_request();
nocache_headers();

if (!headers_sent()) {
	header('X-Robots-Tag: noindex, nofollow', true);
}

$bbb_social_settings = bbb_social_calendar_settings();
$bbb_ics_proxy_url   = add_query_arg(
	array(
		'action' => 'bbb_social_calendar_ics',
		'nonce'  => wp_create_nonce('bbb_social_calendar_ics'),
	),
	admin_url('admin-ajax.php')
);
$bbb_has_ics         = '' !== trim((string) ($bbb_social_settings['ical_url'] ?? ''));
$bbb_saved           = isset($_GET['saved']) && '1' === (string) $_GET['saved'];
$bbb_newsletter_types = function_exists('bbb_urgency_banner_newsletter_types') ? bbb_urgency_banner_newsletter_types() : array();
$bbb_newsletter_schedule = function_exists('bbb_social_calendar_newsletter_schedule') ? bbb_social_calendar_newsletter_schedule() : array();

wp_enqueue_style('bbb-social-posting-calendar-fonts', 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap', array(), null);
bbb_enqueue_css('bbb-social-posting-calendar', 'assets/css/social-posting-calendar.css', array('bbb-social-posting-calendar-fonts'));
wp_enqueue_script('bbb-fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js', array(), '6.1.17', true);
wp_enqueue_script('bbb-ical-js', 'https://cdn.jsdelivr.net/npm/ical.js@1.5.0/build/ical.min.js', array(), '1.5.0', true);
wp_enqueue_script('bbb-fullcalendar-ical', 'https://cdn.jsdelivr.net/npm/@fullcalendar/icalendar@6.1.17/index.global.min.js', array('bbb-fullcalendar', 'bbb-ical-js'), '6.1.17', true);
bbb_enqueue_js('bbb-social-posting-calendar', 'assets/js/social-posting-calendar.js', array('bbb-fullcalendar', 'bbb-fullcalendar-ical'), true);
wp_localize_script(
	'bbb-social-posting-calendar',
	'bbbSocialCalendar',
	array(
		'icsProxyUrl' => $bbb_has_ics ? $bbb_ics_proxy_url : '',
		'ajaxUrl'     => admin_url('admin-ajax.php'),
		'stateNonce'  => wp_create_nonce('bbb_social_calendar_state'),
		'state'       => function_exists('bbb_social_calendar_post_state') ? bbb_social_calendar_post_state() : array(),
		'pinterestBoards' => function_exists('bbb_social_calendar_pinterest_boards') ? bbb_social_calendar_pinterest_boards() : array(),
		'timeZone'    => wp_timezone_string(),
		'threadsUrl'  => (string) ($bbb_social_settings['threads_url'] ?? 'https://www.threads.net/'),
		'instagramUrl' => (string) ($bbb_social_settings['instagram_url'] ?? 'https://www.instagram.com/'),
		'tiktokUrl'    => (string) ($bbb_social_settings['tiktok_url'] ?? 'https://www.tiktok.com/'),
		'pinterestUrl' => (string) ($bbb_social_settings['pinterest_url'] ?? ''),
		'pinterestApp' => (string) ($bbb_social_settings['pinterest_app'] ?? 'https://www.pinterest.com/'),
		'dashboardUrl' => (string) ($bbb_social_settings['dashboard_url'] ?? ''),
		'newsletterTypes' => array_map(
			static fn(array $type): string => (string) ($type['label'] ?? ''),
			$bbb_newsletter_types
		),
		'newsletterSchedule' => $bbb_newsletter_schedule,
	)
);

$bbb_link_cards = array(
	array('label' => 'daily dashboard', 'url' => $bbb_social_settings['dashboard_url'], 'tag' => 'command center'),
	array('label' => 'Pinterest board', 'url' => $bbb_social_settings['pinterest_url'], 'tag' => 'idea bank'),
	array('label' => 'Notion', 'url' => $bbb_social_settings['notion_url'], 'tag' => 'drafts'),
	array('label' => 'Trello', 'url' => $bbb_social_settings['trello_url'], 'tag' => 'pipeline'),
	array('label' => 'Threads', 'url' => $bbb_social_settings['threads_url'], 'tag' => 'daily'),
	array('label' => 'Instagram', 'url' => $bbb_social_settings['instagram_url'], 'tag' => 'publish'),
	array('label' => 'TikTok', 'url' => $bbb_social_settings['tiktok_url'], 'tag' => 'publish'),
	array('label' => 'Pinterest', 'url' => $bbb_social_settings['pinterest_app'], 'tag' => 'publish'),
	array('label' => 'Canva', 'url' => $bbb_social_settings['canva_url'], 'tag' => 'assets'),
);

get_header();
?>

<main class="bbb-social-calendar is-calendar-mode" data-social-calendar-page data-current-mode="calendar">
	<section class="bbb-social-calendar__hero">
		<div>
			<p class="bbb-social-calendar__eyebrow">private creator desk</p>
			<h1>social posting calendar</h1>
			<p>Click a day, clear the platform to-dos, and keep the newsletter rhythm visible.</p>
		</div>
	</section>

	<?php if ($bbb_saved) : ?>
		<div class="bbb-social-calendar__notice" role="status">Calendar settings saved.</div>
	<?php endif; ?>

	<section class="bbb-social-calendar__layout">
		<div class="bbb-social-calendar__main">
			<div class="bbb-social-calendar__toolbar" aria-label="calendar controls">
				<span>Current week plus the next 3 weeks</span>
				<div class="bbb-social-calendar__toolbarActions">
					<button type="button" class="is-active" data-calendar-mode="calendar" aria-pressed="true">calendar</button>
					<button type="button" data-calendar-mode="visual" aria-pressed="false">visual week</button>
					<button type="button" data-calendar-today>jump to today</button>
				</div>
			</div>
			<div id="bbbSocialFullCalendar" class="bbb-social-calendar__calendar" data-has-feed="<?php echo $bbb_has_ics ? 'true' : 'false'; ?>"></div>
			<section class="bbb-social-calendar__visual" data-visual-planner hidden>
				<div class="bbb-social-calendar__visualHead">
					<div>
						<p class="bbb-social-calendar__eyebrow">visual planner</p>
						<h2 data-visual-week-label>Today + next 7 days</h2>
					</div>
					<div class="bbb-social-calendar__weekNav" aria-label="visual week controls">
						<button type="button" data-visual-prev>prev week</button>
						<button type="button" data-visual-today>this week</button>
						<button type="button" data-visual-next>next week</button>
					</div>
				</div>
				<div class="bbb-social-calendar__platformTabs" data-visual-platforms aria-label="visual platform"></div>
				<div class="bbb-social-calendar__visualGrid" data-visual-week></div>
			</section>
		</div>
	</section>

	<div class="bbb-social-calendar__modal" data-social-modal aria-hidden="true">
		<div class="bbb-social-calendar__modalBackdrop" data-social-modal-close></div>
		<section class="bbb-social-calendar__modalCard" role="dialog" aria-modal="true" aria-labelledby="bbbSocialModalTitle">
			<button class="bbb-social-calendar__modalClose" type="button" data-social-modal-close aria-label="Close">x</button>
			<div class="bbb-social-calendar__modalHead">
				<p data-social-modal-date>select a day</p>
				<h2 id="bbbSocialModalTitle" data-social-modal-title>choose platform</h2>
				<span data-social-modal-status>not scheduled</span>
			</div>
			<div class="bbb-social-calendar__platformGrid" data-platform-picker></div>
			<form class="bbb-social-calendar__postForm" data-post-form hidden>
				<div class="bbb-social-calendar__postMeta">
					<strong data-post-platform>Threads</strong>
					<span data-post-time>time loading</span>
				</div>
				<label>
					<span>post draft</span>
					<textarea data-post-draft rows="8" placeholder="Paste or write the post here."></textarea>
				</label>
				<div class="bbb-social-calendar__postActions">
					<a href="https://www.threads.net/" target="_blank" rel="noopener" data-post-open>open platform</a>
					<button type="button" data-post-copy>copy post</button>
					<button type="button" data-post-scheduled>mark scheduled</button>
				</div>
			</form>
		</section>
	</div>

	<?php if (!empty($bbb_social_settings['google_embed']) || !empty($bbb_social_settings['dashboard_url'])) : ?>
		<section class="bbb-social-calendar__embeds">
			<?php if (!empty($bbb_social_settings['google_embed'])) : ?>
				<div class="bbb-social-calendar__embed">
					<h2>Google Calendar</h2>
					<iframe title="Google Calendar" src="<?php echo esc_url((string) $bbb_social_settings['google_embed']); ?>" loading="lazy"></iframe>
				</div>
			<?php endif; ?>
			<?php if (!empty($bbb_social_settings['dashboard_url'])) : ?>
				<div class="bbb-social-calendar__embed">
					<h2>daily dashboard</h2>
					<iframe title="Daily dashboard" src="<?php echo esc_url((string) $bbb_social_settings['dashboard_url']); ?>" loading="lazy"></iframe>
				</div>
			<?php endif; ?>
		</section>
	<?php endif; ?>

</main>

<?php
get_footer();
