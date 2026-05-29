<?php
/**
 * Page slug template for Mafia Romance Books.
 *
 * Keeps /mafia-romance-books/ on the WordPress page template hierarchy while
 * reusing the library trope page renderer.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_filter(
	'rank_math/opengraph/type',
	static function (): string {
		return 'website';
	},
	99
);

add_action(
	'rank_math/opengraph/facebook',
	static function (): void {
		remove_all_actions('rank_math/opengraph/facebook', 19);
		remove_all_actions('rank_math/opengraph/facebook', 90);
	},
	6
);

require get_theme_file_path('page-trope.php');
