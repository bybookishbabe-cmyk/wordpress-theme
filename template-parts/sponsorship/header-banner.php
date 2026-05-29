<?php
/**
 * Sponsorship Zone 1: header leaderboard placeholder.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$sponsor_name = (string) get_theme_mod('bbb_sponsor_header_brand', '');
$sponsor_url  = (string) get_theme_mod('bbb_sponsor_header_url', '');
$sponsor_copy = (string) get_theme_mod('bbb_sponsor_header_copy', '');

$has_sponsor = '' !== trim($sponsor_name) && '' !== trim($sponsor_url);
$label       = $has_sponsor ? sprintf(__('presented by %s', 'bybookishbabe-shopify-port'), strtolower($sponsor_name)) : __('presented by future bookish partners', 'bybookishbabe-shopify-port');
$copy        = $has_sponsor && '' !== trim($sponsor_copy) ? strtolower($sponsor_copy) : __('this space is reserved for brands we actually love', 'bybookishbabe-shopify-port');
$sponsor_status = isset($_GET['bbb_sponsor']) ? sanitize_key((string) wp_unslash($_GET['bbb_sponsor'])) : '';
$modal_id    = 'bbb-sponsor-work-with-me';
?>

<aside id="bbb-sponsor-header" class="bbb-sponsor bbb-sponsor--header" aria-label="<?php esc_attr_e('sponsored placement', 'bybookishbabe-shopify-port'); ?>">
	<?php if ($has_sponsor) : ?>
	<a class="bbb-sponsor__leaderboard" href="<?php echo esc_url($sponsor_url); ?>" target="_blank" rel="noopener sponsored">
	<?php else : ?>
	<button class="bbb-sponsor__leaderboard" type="button" aria-haspopup="dialog" aria-controls="<?php echo esc_attr($modal_id); ?>" data-bbb-sponsor-open>
	<?php endif; ?>
		<span class="bbb-sponsor__badge"><?php esc_html_e('sponsor spot', 'bybookishbabe-shopify-port'); ?></span>
		<span class="bbb-sponsor__copy">
			<span class="bbb-sponsor__label"><?php echo esc_html($label); ?></span>
			<span class="bbb-sponsor__text"><?php echo esc_html($copy); ?></span>
		</span>
		<span class="bbb-sponsor__cta"><?php echo esc_html($has_sponsor ? __('visit partner', 'bybookishbabe-shopify-port') : __('book this spot', 'bybookishbabe-shopify-port')); ?></span>
	<?php echo $has_sponsor ? '</a>' : '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<?php if (!$has_sponsor) : ?>
		<div id="<?php echo esc_attr($modal_id); ?>" class="bbb-sponsor-modal" hidden aria-hidden="true" data-bbb-sponsor-modal>
			<div class="bbb-sponsor-modal__overlay" data-bbb-sponsor-close></div>
			<div class="bbb-sponsor-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="bbb-sponsor-modal-title">
				<button class="bbb-sponsor-modal__close" type="button" aria-label="<?php esc_attr_e('close sponsorship form', 'bybookishbabe-shopify-port'); ?>" data-bbb-sponsor-close>&times;</button>

				<p class="bbb-sponsor-modal__kicker">work with me</p>
				<h2 id="bbb-sponsor-modal-title" class="bbb-sponsor-modal__title">sponsorship inquiry</h2>
				<p class="bbb-sponsor-modal__intro">tell me a little about your brand, the campaign, and why it belongs in this reader space.</p>

				<?php if ('sent' === $sponsor_status) : ?>
					<div class="bbb-sponsor-modal__notice bbb-sponsor-modal__notice--success" role="status">inquiry received. i'll be in touch soon.</div>
				<?php elseif ('error' === $sponsor_status) : ?>
					<div class="bbb-sponsor-modal__notice bbb-sponsor-modal__notice--error" role="alert">something's off - check the fields and try again.</div>
				<?php endif; ?>

				<form class="bbb-sponsor-modal__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<input type="hidden" name="action" value="bbb_footer_contact">
					<input type="hidden" name="contact_source" value="sponsor">
					<input type="hidden" name="contact_subject" value="sponsorship inquiry">
					<?php wp_nonce_field('bbb_footer_contact'); ?>

					<div class="bbb-sponsor-modal__grid">
						<label class="bbb-sponsor-field">
							<span>name</span>
							<input type="text" name="contact_name" autocomplete="name" required>
						</label>

						<label class="bbb-sponsor-field">
							<span>email</span>
							<input type="email" name="contact_email" autocomplete="email" required>
						</label>
					</div>

					<label class="bbb-sponsor-field">
						<span>brand / company</span>
						<input type="text" name="contact_brand" autocomplete="organization">
					</label>

					<label class="bbb-sponsor-field">
						<span>what are you hoping to sponsor?</span>
						<textarea name="contact_message" rows="5" required></textarea>
					</label>

					<button class="bbb-sponsor-modal__submit" type="submit">send inquiry</button>
				</form>
			</div>
		</div>
	<?php endif; ?>
</aside>
