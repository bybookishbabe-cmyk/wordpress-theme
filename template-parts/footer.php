<?php
/**
 * Shopify-faithful footer section.
 *
 * @package ByBookishBabeShopifyPort
 */

$menu_items       = bbb_footer_menu_items();
$has_social_icons = bbb_footer_has_social_links();
?>
<footer class="footer color-scheme-1 gradient section-footer-padding">
	<div class="footer__content-top page-width">
		<div class="footer__blocks-wrapper grid grid--1-col grid--2-col grid--4-col-tablet scroll-trigger animate--slide-in" data-cascade>
			<div class="footer-block grid__item footer-block--menu scroll-trigger animate--slide-in" data-cascade>
				<h2 class="footer-block__heading inline-richtext"><?php echo esc_html('© bybookishbabe'); ?></h2>
				<ul class="footer-block__details-content list-unstyled">
					<?php foreach ($menu_items as $item) : ?>
						<?php
						$link_class = 'link link--text list-menu__item list-menu__item--link';
						if (bbb_footer_menu_item_is_active($item)) {
							$link_class .= ' list-menu__item--active';
						}
						?>
						<li>
							<a href="<?php echo esc_url($item->url); ?>" class="<?php echo esc_attr($link_class); ?>">
								<?php echo esc_html($item->title); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

		<div class="footer-block--newsletter scroll-trigger animate--slide-in" data-cascade>
			<?php
			if (bbb_footer_newsletter_enabled()) {
				bbb_render_footer_newsletter();
			}
			?>

			<?php if ($has_social_icons) : ?>
				<div class="footer__social-with-substack">
					<?php bbb_render_social_icons('footer__list-social'); ?>
					<a class="footer__substack-link" href="https://thesmutandsentimentsociety.substack.com" target="_blank" rel="noopener">Substack</a>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="footer__content-bottom scroll-trigger animate--slide-in" data-cascade>
		<div class="footer__affiliate-note page-width">
			<p><?php echo esc_html(bbb_footer_affiliate_disclaimer()); ?></p>
		</div>

		<div class="footer__content-bottom-wrapper page-width">
			<div class="footer__column footer__localization isolate"></div>
			<div class="footer__column footer__column--info">
				<?php bbb_render_footer_payment_icons(); ?>
			</div>
		</div>

		<div class="footer__content-bottom-wrapper page-width footer__content-bottom-wrapper--center">
			<div class="footer__copyright caption">
				<small class="copyright__content">
					<?php
					printf(
						/* translators: 1: current year, 2: linked site name. */
						esc_html__('© %1$s, %2$s', 'bybookishbabe-shopify-port'),
						esc_html(date_i18n('Y')),
						'<a href="' . esc_url(home_url('/')) . '">' . esc_html(get_bloginfo('name')) . '</a>'
					);
					?>
				</small>
				<?php bbb_render_footer_policies(); ?>
			</div>
		</div>
	</div>
</footer>
