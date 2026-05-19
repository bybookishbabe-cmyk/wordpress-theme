<?php
/**
 * Shopify-faithful mobile header drawer.
 *
 * @package ByBookishBabeShopifyPort
 */

$items = bbb_get_header_menu_items();

function bbb_render_drawer_item(WP_Post $item, int $index, string $parent_handle = ''): void {
	$handle       = bbb_menu_item_handle($item);
	$id_prefix    = $parent_handle ? $parent_handle . '-' . $handle : $handle;
	$has_children = !empty($item->children);
	$is_current   = bbb_menu_item_is_current($item);
	$child_active = bbb_menu_item_child_active($item);

	if (!$has_children) :
		?>
		<li>
			<a
				id="HeaderDrawer-<?php echo esc_attr($id_prefix); ?>"
				href="<?php echo esc_url($item->url); ?>"
				class="menu-drawer__menu-item list-menu__item link link--text focus-inset<?php echo $is_current ? ' menu-drawer__menu-item--active' : ''; ?>"
				<?php echo $is_current ? 'aria-current="page"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			><?php echo esc_html($item->title); ?></a>
		</li>
		<?php
		return;
	endif;

	$details_id = $parent_handle ? 'Details-menu-drawer-' . $id_prefix : 'Details-menu-drawer-menu-item-' . $index;
	$link_id    = $parent_handle ? 'childlink-' . $handle : 'link-' . $handle;
	?>
	<li>
		<details id="<?php echo esc_attr($details_id); ?>">
			<summary id="HeaderDrawer-<?php echo esc_attr($id_prefix); ?>" class="menu-drawer__menu-item list-menu__item link link--text focus-inset<?php echo $child_active ? ' menu-drawer__menu-item--active' : ''; ?>">
				<?php echo esc_html($item->title); ?>
				<span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-arrow'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-caret'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</summary>
			<div id="<?php echo esc_attr($link_id); ?>" class="menu-drawer__submenu has-submenu gradient motion-reduce" tabindex="-1">
				<div class="menu-drawer__inner-submenu">
					<button class="menu-drawer__close-button link link--text focus-inset" aria-expanded="true">
						<span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-arrow'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php echo esc_html($item->title); ?>
					</button>
					<ul class="menu-drawer__menu list-menu" role="list" tabindex="-1">
						<?php if (bbb_menu_item_has_link($item)) : ?>
							<li>
								<a
									id="HeaderDrawer-<?php echo esc_attr($id_prefix . '-overview'); ?>"
									href="<?php echo esc_url($item->url); ?>"
									class="menu-drawer__menu-item list-menu__item link link--text focus-inset"
								><?php esc_html_e('overview', 'bybookishbabe-shopify-port'); ?></a>
							</li>
						<?php endif; ?>
						<?php foreach ($item->children as $child_index => $child) : ?>
							<?php bbb_render_drawer_item($child, $child_index + 1, $id_prefix); ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</details>
	</li>
	<?php
}
?>
<header-drawer data-breakpoint="tablet">
	<details id="Details-menu-drawer-container" class="menu-drawer-container">
		<summary
			class="header__icon header__icon--menu header__icon--summary link focus-inset"
			aria-label="<?php esc_attr_e('Menu', 'bybookishbabe-shopify-port'); ?>"
		>
			<span>
				<?php echo bbb_get_inline_svg('icon-hamburger'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo bbb_get_inline_svg('icon-close'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
		</summary>
		<div id="menu-drawer" class="gradient menu-drawer motion-reduce color-scheme-1">
			<div class="menu-drawer__inner-container">
				<div class="menu-drawer__navigation-container">
					<nav class="menu-drawer__navigation">
						<ul class="menu-drawer__menu has-submenu list-menu" role="list">
							<?php foreach ($items as $index => $item) : ?>
								<?php bbb_render_drawer_item($item, $index + 1); ?>
							<?php endforeach; ?>
						</ul>
					</nav>
					<div class="menu-drawer__utility-links">
						<a
							href="<?php echo esc_url(is_user_logged_in() ? bbb_wc_account_url() : wp_login_url()); ?>"
							class="menu-drawer__account link focus-inset h5 medium-hide large-up-hide"
							rel="nofollow"
						>
							<account-icon>
								<?php if (is_user_logged_in() && bbb_user_has_avatar()) : ?>
									<?php echo get_avatar(get_current_user_id(), 44); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php else : ?>
									<span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-account'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<?php endif; ?>
							</account-icon>
							<?php echo esc_html(is_user_logged_in() ? __('Account', 'bybookishbabe-shopify-port') : __('Log in', 'bybookishbabe-shopify-port')); ?>
						</a>
						<ul class="list list-social list-unstyled" role="list">
							<li class="list-social__item"><a href="https://www.instagram.com/bybookishbabe" class="list-social__link link"><span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-instagram'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span class="visually-hidden">Instagram</span></a></li>
							<li class="list-social__item"><a href="https://www.tiktok.com/@bybookishbabe" class="list-social__link link"><span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-tiktok'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span class="visually-hidden">TikTok</span></a></li>
							<li class="list-social__item"><a href="https://www.pinterest.com/bybookishbabe/" class="list-social__link link"><span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-pinterest'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span class="visually-hidden">Pinterest</span></a></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</details>
</header-drawer>
