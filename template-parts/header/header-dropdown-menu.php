<?php
/**
 * Shopify-faithful desktop dropdown menu.
 *
 * @package ByBookishBabeShopifyPort
 */

$items = bbb_get_header_menu_items();
if (!$items) {
	return;
}

function bbb_render_header_dropdown_item(WP_Post $item, int $index, string $parent_handle = ''): void {
	$handle      = bbb_menu_item_handle($item);
	$id_prefix   = $parent_handle ? $parent_handle . '-' . $handle : $handle;
	$has_children = !empty($item->children);
	$is_current  = bbb_menu_item_is_current($item);
	$child_active = bbb_menu_item_child_active($item);

	if ($has_children && !$parent_handle) :
		?>
		<li>
			<header-menu>
				<details id="Details-HeaderMenu-<?php echo esc_attr((string) $index); ?>">
					<summary id="HeaderMenu-<?php echo esc_attr($handle); ?>" class="header__menu-item list-menu__item link focus-inset">
						<span<?php echo $child_active ? ' class="header__active-menu-item"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<?php echo esc_html($item->title); ?>
						</span>
						<?php echo bbb_get_inline_svg('icon-caret'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</summary>
					<ul id="HeaderMenu-MenuList-<?php echo esc_attr((string) $index); ?>" class="header__submenu list-menu list-menu--disclosure color-scheme-1 gradient caption-large motion-reduce global-settings-popup" role="list" tabindex="-1">
						<?php if (bbb_menu_item_has_link($item)) : ?>
							<li>
								<a
									id="HeaderMenu-<?php echo esc_attr($handle . '-overview'); ?>"
									href="<?php echo esc_url($item->url); ?>"
									class="header__menu-item list-menu__item link link--text focus-inset caption-large"
								><?php esc_html_e('overview', 'bybookishbabe-shopify-port'); ?></a>
							</li>
						<?php endif; ?>
						<?php foreach ($item->children as $child) : ?>
							<?php bbb_render_header_dropdown_child($child, $handle); ?>
						<?php endforeach; ?>
					</ul>
				</details>
			</header-menu>
		</li>
		<?php
		return;
	endif;
	?>
	<li>
		<a
			id="HeaderMenu-<?php echo esc_attr($id_prefix); ?>"
			href="<?php echo esc_url($item->url); ?>"
			class="header__menu-item list-menu__item link link--text focus-inset"
			<?php echo $is_current ? 'aria-current="page"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
			<span<?php echo $is_current ? ' class="header__active-menu-item"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php echo esc_html($item->title); ?>
			</span>
		</a>
	</li>
	<?php
}

function bbb_render_header_dropdown_child(WP_Post $item, string $parent_handle): void {
	$handle       = bbb_menu_item_handle($item);
	$id_prefix    = $parent_handle . '-' . $handle;
	$has_children = !empty($item->children);
	$is_current   = bbb_menu_item_is_current($item);

	if (!$has_children) :
		?>
		<li>
			<a
				id="HeaderMenu-<?php echo esc_attr($id_prefix); ?>"
				href="<?php echo esc_url($item->url); ?>"
				class="header__menu-item list-menu__item link link--text focus-inset caption-large<?php echo $is_current ? ' list-menu__item--active' : ''; ?>"
				<?php echo $is_current ? 'aria-current="page"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			><?php echo esc_html($item->title); ?></a>
		</li>
		<?php
		return;
	endif;
	?>
	<li>
		<details id="Details-HeaderSubMenu-<?php echo esc_attr($id_prefix); ?>">
			<summary id="HeaderMenu-<?php echo esc_attr($id_prefix); ?>" class="header__menu-item link link--text list-menu__item focus-inset caption-large">
				<span><?php echo esc_html($item->title); ?></span>
				<?php echo bbb_get_inline_svg('icon-caret'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</summary>
			<ul id="HeaderMenu-SubMenuList-<?php echo esc_attr($id_prefix); ?>" class="header__submenu list-menu motion-reduce">
				<?php if (bbb_menu_item_has_link($item)) : ?>
					<li>
						<a
							id="HeaderMenu-<?php echo esc_attr($id_prefix . '-overview'); ?>"
							href="<?php echo esc_url($item->url); ?>"
							class="header__menu-item list-menu__item link link--text focus-inset caption-large"
						><?php esc_html_e('overview', 'bybookishbabe-shopify-port'); ?></a>
					</li>
				<?php endif; ?>
				<?php foreach ($item->children as $grandchild) : ?>
					<?php
					$grandchild_handle = bbb_menu_item_handle($grandchild);
					$grandchild_current = bbb_menu_item_is_current($grandchild);
					?>
					<li>
						<a
							id="HeaderMenu-<?php echo esc_attr($id_prefix . '-' . $grandchild_handle); ?>"
							href="<?php echo esc_url($grandchild->url); ?>"
							class="header__menu-item list-menu__item link link--text focus-inset caption-large<?php echo $grandchild_current ? ' list-menu__item--active' : ''; ?>"
							<?php echo $grandchild_current ? 'aria-current="page"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						><?php echo esc_html($grandchild->title); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</details>
	</li>
	<?php
}
?>
<nav class="header__inline-menu">
	<ul class="list-menu list-menu--inline" role="list">
		<?php foreach ($items as $index => $item) : ?>
			<?php bbb_render_header_dropdown_item($item, $index + 1); ?>
		<?php endforeach; ?>
	</ul>
</nav>
