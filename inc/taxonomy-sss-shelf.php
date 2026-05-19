<?php
/**
 * SSS Shelf taxonomy.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'init',
	static function (): void {
		register_taxonomy(
			'sss_shelf',
			'sss_book',
			array(
				'label'        => 'Shelves',
				'hierarchical' => false,
				'show_in_rest' => true,
				'rewrite'      => array('slug' => 'shelf'),
			)
		);
	}
);
