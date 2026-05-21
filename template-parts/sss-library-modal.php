<?php
/**
 * Blog library modal bridge.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!is_singular('post')) {
	return;
}

bbb_render_component('library-modal');
