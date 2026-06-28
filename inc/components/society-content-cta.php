<?php
/**
 * Shared Society CTA for content detail pages.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_society_content_cta_url')) {
	function bbb_society_content_cta_url(string $slug, string $fallback): string {
		return function_exists('bbb_page_url') ? bbb_page_url($slug) : home_url($fallback);
	}
}

if (!function_exists('bbb_society_content_cta_icon')) {
	function bbb_society_content_cta_icon(string $icon): string {
		$icon = sanitize_key($icon);

		if ('sss' === $icon) {
			return sprintf(
				'<img src="%s" alt="" class="header__sss-image" loading="lazy" decoding="async" width="104" height="104">',
				esc_url(get_theme_file_uri('assets/SSS_Logo.png'))
			);
		}

		if ('account' === $icon) {
			return '<span class="header__account-dot" aria-hidden="true">A</span>';
		}

		return '<span class="bbb-bookshelf-header__emoji" aria-hidden="true">📚</span>';
	}
}

if (!function_exists('bbb_render_society_content_cta')) {
	function bbb_render_society_content_cta(array $args = array()): void {
		$defaults = array(
			'variant'       => 'book',
			'kicker'        => 'the society',
			'title'         => 'the fun keeps going...',
			'copy'          => 'Save books to your bookshelf, come back for more fictional men, collect quotes, track your reader chaos, and keep the good stuff close.',
			'join_label'    => 'join the society',
			'login_label'   => 'log into account',
			'play_label'    => 'start having fun',
			'play_url'      => bbb_society_content_cta_url('library', '/library/'),
			'play_target'   => '',
			'features'      => array(
				array('title' => 'join the society', 'text' => 'start with the society so your reader world has a home.'),
				array('title' => 'log into account', 'text' => 'come back anytime to your shelf, notes, quotes, and saves.'),
				array('title' => 'have fun', 'text' => 'save books, collect quotes, meet fictional men, and wander freely.'),
			),
		);

		$args          = array_merge($defaults, $args);
		$variant       = sanitize_html_class((string) $args['variant']);
		$join_url      = function_exists('bbb_substack_subscribe_url') ? bbb_substack_subscribe_url() : 'https://thesmutandsentimentsociety.substack.com/subscribe';
		$account_url   = bbb_society_content_cta_url('account', '/account/');
		$play_url      = (string) $args['play_url'];
		$play_target   = '_blank' === (string) $args['play_target'] ? ' target="_blank" rel="noopener"' : '';
		$feature_items = array_values(array_filter((array) $args['features'], 'is_array'));
		?>
		<section class="bbb-society-content-cta bbb-society-content-cta--<?php echo esc_attr($variant); ?>" aria-label="join the society">
			<div class="bbb-society-content-cta__copy">
				<p class="bbb-society-content-cta__kicker"><?php echo esc_html((string) $args['kicker']); ?></p>
				<h2><?php echo esc_html(strtolower((string) $args['title'])); ?></h2>
				<p><?php echo esc_html(strtolower((string) $args['copy'])); ?></p>
			</div>
			<div class="bbb-society-content-cta__steps" aria-label="society perks">
				<?php foreach ($feature_items as $feature_index => $feature) : ?>
					<?php
					$step_number = (string) ((int) $feature_index + 1);
					$step_icons  = array('sss', 'account', 'bookshelf');
					$step_icon   = strtolower((string) ($feature['icon'] ?? ($step_icons[(int) $feature_index] ?? 'bookshelf')));
					$feature_url = (string) ($feature['url'] ?? match ((int) $feature_index) {
						0 => $join_url,
						1 => $account_url,
						default => $play_url,
					});
					?>
					<a class="bbb-society-content-cta__step" href="<?php echo esc_url($feature_url); ?>" target="_blank" rel="noopener">
						<span class="bbb-society-content-cta__stepTop" aria-hidden="true">
							<span class="bbb-society-content-cta__stepNumber"><?php echo esc_html($step_number); ?></span>
							<span class="bbb-society-content-cta__stepIcon" data-society-step-icon="<?php echo esc_attr($step_icon); ?>">
								<?php echo bbb_society_content_cta_icon($step_icon); ?>
							</span>
						</span>
						<strong><?php echo esc_html(strtolower((string) ($feature['title'] ?? 'save the moment'))); ?></strong>
						<p><?php echo esc_html(strtolower((string) ($feature['text'] ?? 'keep your reader world in one place.'))); ?></p>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
