<?php
/**
 * Additional taxonomies for the conversion.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'init',
	static function (): void {
		register_taxonomy(
			'sss_spice',
			'sss_book',
			array(
				'label'        => 'Spice Levels',
				'hierarchical' => false,
				'show_in_rest' => true,
				'rewrite'      => array('slug' => 'spice-level'),
			)
		);

		register_taxonomy(
			'book_review_category',
			'post',
			array(
				'label'        => 'Book Review Categories',
				'hierarchical' => true,
				'show_in_rest' => true,
				'rewrite'      => array('slug' => 'book-review-category'),
			)
		);
	}
);
