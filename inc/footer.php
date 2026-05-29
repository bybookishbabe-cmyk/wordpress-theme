<?php
/**
 * Footer helpers for the Shopify-faithful footer conversion.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_footer_social_links(): array {
	$defaults = array(
		'facebook'  => '',
		'instagram' => 'https://www.instagram.com/bybookishbabe?igsh=MXB6aGtsbm11Ym00aw%3D%3D&utm_source=qr',
		'youtube'   => '',
		'tiktok'    => 'https://www.tiktok.com/@bybookishbabe?_t=ZP-8w1cjhlhwBK&_r=1',
		'twitter'   => '',
		'pinterest' => 'https://www.pinterest.com/bybookishbabe/',
		'snapchat'  => '',
		'tumblr'    => '',
		'vimeo'     => '',
	);

	$links = array();
	foreach ($defaults as $platform => $default_url) {
		$option_key       = 'bbb_social_' . $platform . '_link';
		$shopify_key      = 'social_' . $platform . '_link';
		$links[$platform] = (string) get_theme_mod($shopify_key, get_option($option_key, $default_url));
	}

	return $links;
}

function bbb_footer_has_social_links(): bool {
	foreach (bbb_footer_social_links() as $url) {
		if ('' !== trim($url)) {
			return true;
		}
	}

	return false;
}

function bbb_footer_icon_svg(string $icon): string {
	$allowed_icons = array(
		'facebook',
		'instagram',
		'youtube',
		'tiktok',
		'twitter',
		'pinterest',
		'snapchat',
		'tumblr',
		'vimeo',
		'arrow',
		'error',
		'success',
	);

	if (!in_array($icon, $allowed_icons, true)) {
		return '';
	}

	$path = get_theme_file_path('assets/icons/icon-' . $icon . '.svg');
	if (!file_exists($path)) {
		return '';
	}

	return (string) file_get_contents($path);
}

function bbb_render_social_icons(string $class = ''): void {
	$labels = array(
		'facebook'  => __('Facebook', 'bybookishbabe-shopify-port'),
		'instagram' => __('Instagram', 'bybookishbabe-shopify-port'),
		'youtube'   => __('YouTube', 'bybookishbabe-shopify-port'),
		'tiktok'    => __('TikTok', 'bybookishbabe-shopify-port'),
		'twitter'   => __('Twitter', 'bybookishbabe-shopify-port'),
		'pinterest' => __('Pinterest', 'bybookishbabe-shopify-port'),
		'snapchat'  => __('Snapchat', 'bybookishbabe-shopify-port'),
		'tumblr'    => __('Tumblr', 'bybookishbabe-shopify-port'),
		'vimeo'     => __('Vimeo', 'bybookishbabe-shopify-port'),
	);

	$list_class = trim('list-unstyled list-social ' . $class);
	?>
	<ul class="<?php echo esc_attr($list_class); ?>" role="list">
		<?php foreach (bbb_footer_social_links() as $platform => $url) : ?>
			<?php
			if ('' === trim($url)) {
				continue;
			}

			$label = $labels[$platform] ?? ucfirst($platform);
			?>
			<li class="list-social__item">
				<a href="<?php echo esc_url($url); ?>" class="link list-social__link">
					<span class="svg-wrapper"><?php echo bbb_footer_icon_svg($platform); ?></span>
					<span class="visually-hidden"><?php echo esc_html($label); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

function bbb_footer_menu_items(): array {
	$locations = get_nav_menu_locations();
	$menu_id   = isset($locations['main-menu']) ? (int) $locations['main-menu'] : 0;
	$menu      = $menu_id ? wp_get_nav_menu_object($menu_id) : wp_get_nav_menu_object('main-menu');

	if (!$menu) {
		$menu = wp_get_nav_menu_object('Main Menu');
	}

	if (!$menu) {
		return function_exists('bbb_get_fallback_main_menu') ? bbb_footer_fallback_menu_items() : array();
	}

	$items = wp_get_nav_menu_items(
		$menu->term_id,
		array(
			'update_post_term_cache' => false,
		)
	);

	if (!is_array($items)) {
		return function_exists('bbb_get_fallback_main_menu') ? bbb_footer_fallback_menu_items() : array();
	}

	if (!$items && function_exists('bbb_get_fallback_main_menu')) {
		return bbb_footer_fallback_menu_items();
	}

	foreach ($items as $item) {
		if (isset($item->url)) {
			$item->url = function_exists('bbb_normalize_menu_item_url')
				? bbb_normalize_menu_item_url((string) $item->url)
				: (string) $item->url;
		}
	}

	return $items;
}

function bbb_footer_fallback_menu_items(): array {
	if (!function_exists('bbb_get_fallback_main_menu')) {
		return array();
	}

	$items = array();
	foreach (bbb_get_fallback_main_menu() as $index => $item) {
		$post              = new stdClass();
		$post->ID          = $index + 1;
		$post->title       = (string) ($item['title'] ?? '');
		$post->url         = (string) ($item['url'] ?? home_url('/'));
		$post->classes     = array();
		$post->menu_order  = $index + 1;
		$post->post_title  = $post->title;
		$post->post_name   = sanitize_title($post->title);
		$post->post_type   = 'nav_menu_item';
		$items[]           = $post;
	}

	return $items;
}

function bbb_footer_menu_item_is_active(object $item): bool {
	$classes = array_filter((array) $item->classes);
	if (array_intersect($classes, array('current-menu-item', 'current_page_item', 'current-menu-ancestor', 'current_page_ancestor'))) {
		return true;
	}

	$request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '';
	$current_url = '' !== $request_uri ? untrailingslashit(home_url(strtok($request_uri, '?'))) : untrailingslashit((string) wp_get_canonical_url());
	$item_url    = untrailingslashit((string) $item->url);

	return '' !== $current_url && '' !== $item_url && $current_url === $item_url;
}

function bbb_footer_affiliate_disclaimer(): string {
	$default = 'some links may be affiliate links, so thank you for supporting the recs. <3';
	$value   = get_option('bbb_footer_affiliate_disclaimer', $default);

	return is_string($value) && '' !== trim($value) ? $value : $default;
}

add_action('admin_post_nopriv_bbb_footer_contact', 'bbb_handle_footer_contact');
add_action('admin_post_bbb_footer_contact', 'bbb_handle_footer_contact');

function bbb_handle_footer_contact(): void {
	check_admin_referer('bbb_footer_contact');

	$name    = isset($_POST['contact_name']) ? sanitize_text_field((string) wp_unslash($_POST['contact_name'])) : '';
	$email   = isset($_POST['contact_email']) ? sanitize_email((string) wp_unslash($_POST['contact_email'])) : '';
	$subject = isset($_POST['contact_subject']) ? sanitize_text_field((string) wp_unslash($_POST['contact_subject'])) : '';
	$message = isset($_POST['contact_message']) ? sanitize_textarea_field((string) wp_unslash($_POST['contact_message'])) : '';
	$source  = isset($_POST['contact_source']) ? sanitize_key((string) wp_unslash($_POST['contact_source'])) : '';
	$brand   = isset($_POST['contact_brand']) ? sanitize_text_field((string) wp_unslash($_POST['contact_brand'])) : '';

	$status = 'error';
	if ('' !== $name && is_email($email) && '' !== $message) {
		$to      = (string) get_option('admin_email', 'bybookishbabe@gmail.com');
		$subject = '' !== $subject ? $subject : 'Footer note from bybookishbabe';
		$body    = "Name: {$name}\nEmail: {$email}";
		if ('' !== $brand) {
			$body .= "\nBrand: {$brand}";
		}
		$body .= "\n\n{$message}";
		$headers = array('Reply-To: ' . $name . ' <' . $email . '>');

		$status = wp_mail($to, $subject, $body, $headers) ? 'sent' : 'error';
	}

	$referer = wp_get_referer() ?: home_url('/');
	if ('sponsor' === $source) {
		wp_safe_redirect(add_query_arg('bbb_sponsor', $status, remove_query_arg('bbb_sponsor', $referer)) . '#bbb-sponsor-header');
		exit;
	}

	wp_safe_redirect(add_query_arg('bbb_note', $status, remove_query_arg('bbb_note', $referer)) . '#bbb-contact-footer');
	exit;
}

function bbb_footer_newsletter_enabled(): bool {
	return (bool) get_option('bbb_footer_newsletter_enabled', false);
}

function bbb_footer_payment_enabled(): bool {
	return (bool) get_option('bbb_footer_payment_enabled', false);
}

function bbb_footer_policy_enabled(): bool {
	return (bool) get_option('bbb_footer_policy_enabled', false);
}

function bbb_render_footer_newsletter(): void {
	?>
	<div class="footer-block__newsletter">
		<h2 class="footer-block__heading inline-richtext"><?php echo esc_html(get_option('bbb_footer_newsletter_heading', __('Contact Me', 'bybookishbabe-shopify-port'))); ?></h2>
		<form id="ContactFooter" class="footer__newsletter newsletter-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<input type="hidden" name="action" value="bbb_footer_newsletter">
			<input type="hidden" name="contact[tags]" value="newsletter">
			<div class="newsletter-form__field-wrapper">
				<div class="field">
					<input id="NewsletterForm--footer" type="email" name="email" class="field__input" autocomplete="email" placeholder="<?php esc_attr_e('Email', 'bybookishbabe-shopify-port'); ?>" required>
					<label class="field__label" for="NewsletterForm--footer"><?php esc_html_e('Email', 'bybookishbabe-shopify-port'); ?></label>
					<button type="submit" class="newsletter-form__button field__button" name="commit" id="Subscribe" aria-label="<?php esc_attr_e('Subscribe', 'bybookishbabe-shopify-port'); ?>">
						<span class="svg-wrapper"><?php echo bbb_footer_icon_svg('arrow'); ?></span>
					</button>
				</div>
			</div>
		</form>
	</div>
	<?php
}

function bbb_render_footer_payment_icons(): void {
	if (!bbb_footer_payment_enabled()) {
		return;
	}
	?>
	<div class="footer__payment">
		<span class="visually-hidden"><?php esc_html_e('Payment methods', 'bybookishbabe-shopify-port'); ?></span>
		<ul class="list list-payment" role="list">
			<?php
			$woocommerce = function_exists('WC') ? WC() : null;
			if ($woocommerce && $woocommerce->payment_gateways()) {
				foreach ($woocommerce->payment_gateways()->get_available_payment_gateways() as $gateway) {
					$icon = method_exists($gateway, 'get_icon') ? $gateway->get_icon() : '';
					if ('' === trim($icon)) {
						continue;
					}
					echo '<li class="list-payment__item">' . wp_kses_post($icon) . '</li>';
				}
			}
			?>
		</ul>
	</div>
	<?php
}

function bbb_render_footer_policies(): void {
	if (!bbb_footer_policy_enabled() || !has_nav_menu('footer-policies')) {
		return;
	}

	$locations = get_nav_menu_locations();
	$items     = isset($locations['footer-policies']) ? wp_get_nav_menu_items((int) $locations['footer-policies']) : array();

	if (empty($items) || !is_array($items)) {
		return;
	}
	?>
	<ul class="policies list-unstyled">
		<?php foreach ($items as $item) : ?>
			<li>
				<small class="copyright__content"><a href="<?php echo esc_url($item->url); ?>"><?php echo esc_html($item->title); ?></a></small>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}
