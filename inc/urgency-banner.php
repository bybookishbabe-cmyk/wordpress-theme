<?php
/**
 * Sitewide urgency/countdown banner.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_urgency_banner_config(): array {
	$config = array(
		'id'          => 'chapters-end-reveal-2026-05-31',
		'enabled'     => true,
		'label'       => "chapter's end reveal",
		'message'     => 'the next society reveal is almost here',
		'cta_label'   => 'join the society',
		'cta_url'     => 'https://thesmutandsentimentsociety.substack.com/subscribe',
		'target_time' => '2026-05-31T10:00:00-07:00',
	);

	return apply_filters('bbb_urgency_banner_config', $config);
}

function bbb_urgency_banner_is_active(): bool {
	$config = bbb_urgency_banner_config();
	if (empty($config['enabled']) || empty($config['target_time'])) {
		return false;
	}

	$target = strtotime((string) $config['target_time']);
	if (!$target) {
		return false;
	}

	return time() < $target;
}

function bbb_urgency_banner_enqueue(): void {
	if (bbb_urgency_banner_is_active() && function_exists('bbb_enqueue_css')) {
		bbb_enqueue_css('bbb-urgency-banner', 'assets/css/urgency-banner-v2.css', array('bbb-bookshelf-signup'));
	}
	if (bbb_urgency_banner_is_active() && function_exists('bbb_enqueue_js')) {
		bbb_enqueue_js('bbb-urgency-banner', 'assets/js/urgency-banner.js', array('bbb-global'));
	}
	if (bbb_sunday_drop_toast_enabled() && function_exists('bbb_enqueue_css')) {
		bbb_enqueue_css('bbb-sunday-drop-toast', 'assets/css/sunday-drop-toast.css', array('bbb-bookshelf-signup'));
	}
	if (bbb_sunday_drop_toast_enabled() && function_exists('bbb_enqueue_js')) {
		bbb_enqueue_js('bbb-sunday-drop-toast', 'assets/js/sunday-drop-toast.js', array('bbb-global'));
	}
}
add_action('wp_enqueue_scripts', 'bbb_urgency_banner_enqueue', 30);

function bbb_urgency_banner_render(): void {
	if (!bbb_urgency_banner_is_active()) {
		return;
	}

	$config = bbb_urgency_banner_config();
	$id     = sanitize_key((string) ($config['id'] ?? 'bbb-urgency-banner'));
	$target = (string) ($config['target_time'] ?? '');
	$url    = esc_url((string) ($config['cta_url'] ?? home_url('/smut-sentiment-society/')));
	?>
	<div
		class="bbb-urgency-banner"
		data-bbb-urgency-banner
		data-banner-id="<?php echo esc_attr($id); ?>"
		data-target-time="<?php echo esc_attr($target); ?>"
		role="region"
		aria-label="<?php esc_attr_e('Limited time announcement', 'bybookishbabe-shopify-port'); ?>"
		hidden
	>
		<div class="bbb-urgency-banner__inner">
			<div class="bbb-urgency-banner__copy">
				<span class="bbb-urgency-banner__label"><?php echo esc_html((string) ($config['label'] ?? 'limited time')); ?></span>
				<span class="bbb-urgency-banner__message"><?php echo esc_html((string) ($config['message'] ?? 'join before the reveal')); ?></span>
			</div>
			<div class="bbb-urgency-banner__timer" data-bbb-urgency-timer aria-live="polite">
				<span class="bbb-urgency-banner__timerUnit"><strong data-bbb-days>00</strong><span>days</span></span>
				<span class="bbb-urgency-banner__timerUnit"><strong data-bbb-hours>00</strong><span>hrs</span></span>
				<span class="bbb-urgency-banner__timerUnit"><strong data-bbb-minutes>00</strong><span>min</span></span>
				<span class="bbb-urgency-banner__timerUnit"><strong data-bbb-seconds>00</strong><span>sec</span></span>
			</div>
			<a class="bbb-urgency-banner__cta" href="<?php echo $url; ?>" target="_blank" rel="noopener">
				<?php echo esc_html((string) ($config['cta_label'] ?? 'join now')); ?>
			</a>
			<button class="bbb-urgency-banner__dismiss" type="button" data-bbb-urgency-dismiss aria-label="<?php esc_attr_e('Dismiss announcement', 'bybookishbabe-shopify-port'); ?>">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	</div>
	<?php
}
add_action('wp_body_open', 'bbb_urgency_banner_render', 8);

function bbb_sunday_drop_toast_enabled(): bool {
	return (bool) apply_filters('bbb_sunday_drop_toast_enabled', false);
}

function bbb_sunday_drop_toast_config(): array {
	if (!bbb_sunday_drop_toast_enabled()) {
		return array(
			'id'      => 'sunday-drop',
			'enabled' => false,
		);
	}

	$issue = bbb_sunday_drop_latest_issue();
	$substack = $issue instanceof WP_Post ? array() : bbb_sunday_drop_latest_substack_post();
	$title = '';
	$url   = 'https://thesmutandsentimentsociety.substack.com/';
	$id    = 'sunday-drop-morally-gray-chaos-2026';

	if (!empty($substack['title']) && !empty($substack['url'])) {
		$title = (string) $substack['title'];
		$url   = (string) $substack['url'];
		$id    = 'sunday-drop-substack-' . sanitize_key((string) ($substack['id'] ?? md5($url)));
	} elseif ($issue instanceof WP_Post) {
		$title = (string) get_post_meta($issue->ID, '_issue_title_override', true);
		if ('' === trim($title)) {
			$title = get_the_title($issue);
		}

		$url = bbb_sunday_drop_issue_url($issue);
		$id  = 'sunday-drop-issue-' . (int) $issue->ID . '-' . (int) get_post_modified_time('U', true, $issue);
	}

	$config = array(
		'id'      => $id,
		'enabled' => true,
		'title'   => '' !== trim($title) ? strtolower(wp_strip_all_tags($title)) : 'morally gray men & chaos',
		'url'     => $url,
	);

	return apply_filters('bbb_sunday_drop_toast_config', $config);
}

function bbb_sunday_drop_latest_substack_post(): array {
	$cached = get_transient('bbb_sunday_drop_latest_substack_post');
	if (is_array($cached)) {
		return $cached;
	}

	$result = array();
	$response = wp_remote_get(
		'https://thesmutandsentimentsociety.substack.com/api/v1/archive?sort=new&search=&offset=0&limit=1',
		array(
			'timeout' => 3,
		)
	);

	if (!is_wp_error($response) && 200 === (int) wp_remote_retrieve_response_code($response)) {
		$posts = json_decode((string) wp_remote_retrieve_body($response), true);
		$post = is_array($posts) && isset($posts[0]) && is_array($posts[0]) ? $posts[0] : array();
		$title = isset($post['title']) ? trim(wp_strip_all_tags((string) $post['title'])) : '';
		$url = '';
		foreach (array('canonical_url', 'web_url', 'url') as $key) {
			if (!empty($post[$key])) {
				$url = (string) $post[$key];
				break;
			}
		}
		$url = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($url) : trim($url);

		if ('' !== $title && '' !== $url) {
			$result = array(
				'id'    => (string) ($post['id'] ?? md5($url)),
				'title' => $title,
				'url'   => $url,
			);
		}
	}

	set_transient('bbb_sunday_drop_latest_substack_post', $result, 15 * MINUTE_IN_SECONDS);

	return $result;
}

function bbb_sunday_drop_latest_issue(): ?WP_Post {
	if (function_exists('sss_get_latest_newsletter_issue_by_weekday')) {
		$sunday_issue = sss_get_latest_newsletter_issue_by_weekday(7);
		if ($sunday_issue instanceof WP_Post) {
			return $sunday_issue;
		}
	}

	$post_types = array_values(
		array_filter(
			array('newsletter_issue', 'bbb_newsletter_issue'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);

	if (!$post_types) {
		return null;
	}

	$today = wp_date('Y-m-d', time(), new DateTimeZone('America/Los_Angeles'));
	$issues = get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_issue_publish_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'     => '_issue_publish_date',
					'value'   => $today,
					'compare' => '<=',
					'type'    => 'DATE',
				),
			),
		)
	);

	if (!empty($issues[0]) && $issues[0] instanceof WP_Post) {
		return $issues[0];
	}

	$issues = get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	return !empty($issues[0]) && $issues[0] instanceof WP_Post ? $issues[0] : null;
}

function bbb_sunday_drop_issue_url(WP_Post $issue): string {
	if (function_exists('bbb_society_newsletter_issue_url')) {
		return bbb_society_newsletter_issue_url($issue);
	}

	foreach (array('_bbb_newsletter_url', 'issue_url', '_issue_url', 'newsletter_url') as $meta_key) {
		$url = (string) get_post_meta($issue->ID, $meta_key, true);
		$url = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($url) : trim($url);
		if ('' !== $url) {
			return $url;
		}
	}

	return 'https://thesmutandsentimentsociety.substack.com/';
}

function bbb_sunday_drop_toast_render(): void {
	$config = bbb_sunday_drop_toast_config();
	if (empty($config['enabled'])) {
		return;
	}

	$id    = sanitize_key((string) ($config['id'] ?? 'sunday-drop'));
	$title = rtrim((string) ($config['title'] ?? 'morally gray men & chaos'), ".!?\t\n\r\0\x0B ");
	$url   = esc_url((string) ($config['url'] ?? 'https://thesmutandsentimentsociety.substack.com/'));
	?>
	<div
		class="bbb-sunday-drop"
		data-bbb-sunday-drop
		data-toast-id="<?php echo esc_attr($id); ?>"
		role="status"
		aria-live="polite"
		hidden
	>
		<button class="bbb-sunday-drop__close" type="button" data-bbb-sunday-drop-close aria-label="<?php esc_attr_e('dismiss sunday drop note', 'bybookishbabe-shopify-port'); ?>">
			<span aria-hidden="true">&times;</span>
		</button>
		<a class="bbb-sunday-drop__link" href="<?php echo $url; ?>" target="_blank" rel="noopener">
			<span class="bbb-sunday-drop__kicker">📖 what's new</span>
			<span class="bbb-sunday-drop__message">new sunday letter just dropped — <?php echo esc_html($title); ?>.</span>
			<span class="bbb-sunday-drop__cta">read it →</span>
		</a>
	</div>
	<?php
}
add_action('wp_body_open', 'bbb_sunday_drop_toast_render', 20);
