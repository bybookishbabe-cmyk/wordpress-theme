<?php
/**
 * Reader bookshelf access icon.
 *
 * @package ByBookishBabeShopifyPort
 */

?>
<a
	class="header__icon bbb-bookshelf-header link focus-inset"
	href="<?php echo esc_url(home_url('/my-bookshelf/')); ?>"
	aria-label="<?php esc_attr_e('open your bookshelf', 'bybookishbabe-shopify-port'); ?>"
	data-bbb-bookshelf-link
>
	<span class="bbb-bookshelf-header__count" data-bbb-bookshelf-count hidden>0</span>
	<span class="bbb-bookshelf-header__emoji" aria-hidden="true">📚</span>
</a>
