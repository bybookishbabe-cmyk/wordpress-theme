<?php
/**
 * Shopify-faithful header search modal.
 *
 * @package ByBookishBabeShopifyPort
 */

$input_id = $args['input_id'] ?? 'Search-In-Modal';
?>
<details-modal class="header__search">
	<details>
		<summary
			class="header__icon header__icon--search header__icon--summary link focus-inset modal__toggle"
			aria-haspopup="dialog"
			aria-label="<?php esc_attr_e('Search', 'bybookishbabe-shopify-port'); ?>"
		>
			<span>
				<span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-search'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="svg-wrapper header__icon-close"><?php echo bbb_get_inline_svg('icon-close'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</span>
		</summary>
		<div
			class="search-modal modal__content gradient"
			role="dialog"
			aria-modal="true"
			aria-label="<?php esc_attr_e('Search', 'bybookishbabe-shopify-port'); ?>"
		>
			<div class="modal-overlay"></div>
			<div class="search-modal__content search-modal__content-bottom" tabindex="-1">
				<predictive-search class="search-modal__form" data-loading-text="<?php esc_attr_e('Loading', 'bybookishbabe-shopify-port'); ?>">
					<form action="<?php echo esc_url(home_url('/')); ?>" method="get" role="search" class="search search-modal__form">
						<div class="field">
							<input
								class="search__input field__input"
								id="<?php echo esc_attr($input_id); ?>"
								type="search"
								name="s"
								value="<?php echo esc_attr(get_search_query()); ?>"
								placeholder="<?php esc_attr_e('Search', 'bybookishbabe-shopify-port'); ?>"
								role="combobox"
								aria-expanded="false"
								aria-owns="predictive-search-results"
								aria-controls="predictive-search-results"
								aria-haspopup="listbox"
								aria-autocomplete="list"
								autocorrect="off"
								autocomplete="off"
								autocapitalize="off"
								spellcheck="false"
							>
							<label class="field__label" for="<?php echo esc_attr($input_id); ?>"><?php esc_html_e('Search', 'bybookishbabe-shopify-port'); ?></label>
							<input type="hidden" name="options[prefix]" value="last">
							<button type="reset" class="reset__button field__button hidden" aria-label="<?php esc_attr_e('Reset', 'bybookishbabe-shopify-port'); ?>">
								<span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-reset'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</button>
							<button class="search__button field__button" aria-label="<?php esc_attr_e('Search', 'bybookishbabe-shopify-port'); ?>">
								<span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-search'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</button>
						</div>
						<div class="predictive-search predictive-search--header" tabindex="-1" data-predictive-search>
							<div class="predictive-search__loading-state"><?php echo bbb_get_inline_svg('loading-spinner'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						</div>
						<span class="predictive-search-status visually-hidden" role="status" aria-hidden="true"></span>
					</form>
				</predictive-search>
				<button
					type="button"
					class="search-modal__close-button modal__close-button link link--text focus-inset"
					aria-label="<?php esc_attr_e('Close', 'bybookishbabe-shopify-port'); ?>"
				>
					<span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-close'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</button>
			</div>
		</div>
	</details>
</details-modal>
