<?php
/**
 * Book custom post type and taxonomies.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_register_book_cpt(): void {
	register_post_type(
		'bbb_book',
		array(
			'label'        => 'Books',
			'public'       => true,
			'has_archive'  => true,
			'rewrite'      => array('slug' => 'books'),
			'supports'     => array('title', 'thumbnail', 'custom-fields'),
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-book',
		)
	);
}
add_action('init', 'bbb_register_book_cpt');

function bbb_register_book_taxonomies(): void {
	$labels = array(
		'bbb_trope'  => 'Tropes',
		'bbb_shelf'  => 'Shelves',
		'bbb_series' => 'Series',
	);

	foreach ($labels as $tax => $label) {
		register_taxonomy(
			$tax,
			'bbb_book',
			array(
				'label'        => $label,
				'hierarchical' => false,
				'meta_box_cb'  => false,
				'rewrite'      => array('slug' => str_replace('bbb_', '', $tax)),
				'show_in_rest' => true,
			)
		);
	}
}
add_action('init', 'bbb_register_book_taxonomies');
