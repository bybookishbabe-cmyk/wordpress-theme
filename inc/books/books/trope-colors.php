<?php
/**
 * Trope pill colors.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

/**
 * Returns [bg_hex, text_hex] for a trope slug.
 *
 * @param string $slug
 * @return array{0:string,1:string}
 */
function bbb_get_trope_colors(string $slug): array {
	$map = array(
		'enemies-to-lovers'       => array('#f2a7ad', '#6e1422'),
		'friends-to-lovers'       => array('#bfe3cb', '#144a31'),
		'slow-burn'               => array('#f2c179', '#6a3700'),
		'billionaire-romance'     => array('#bfdca0', '#365316'),
		'billionaire'             => array('#bfdca0', '#365316'),
		'second-chance'           => array('#cfbef5', '#4b2280'),
		'forced-proximity'        => array('#a9cdf6', '#163f72'),
		'grumpy-sunshine'         => array('#f2d35f', '#5f4700'),
		'workplace-romance'       => array('#bfd0ef', '#274469'),
		'fake-dating'             => array('#efb6d3', '#6e2147'),
		'marriage-of-convenience' => array('#dbc2a7', '#6c4221'),
		'sports-romance'          => array('#9fd8e5', '#0f5064'),
		'small-town'              => array('#c7d89b', '#405719'),
		'brothers-best-friend'    => array('#ebb99c', '#71351a'),
		'dark-romance'            => array('#b8a0d8', '#2f1646'),
		'stalker-romance'         => array('#b8a0d8', '#2f1646'),
		'stalker'                 => array('#b8a0d8', '#2f1646'),
		'morally-gray-hero'       => array('#b9c1cb', '#26303b'),
		'morally-gray-men'        => array('#b9c1cb', '#26303b'),
		'morally-gray'            => array('#b9c1cb', '#26303b'),
		'touch-her-and-die'       => array('#e596a8', '#641223'),
		'one-bed'                 => array('#d8b9ea', '#55276f'),
		'fated-mates'             => array('#e7acd1', '#74204f'),
		'age-gap'                 => array('#c4d4ec', '#31486e'),
		'single-dad'              => array('#b7dbc9', '#1f543b'),
		'reverse-harem'           => array('#d7a8d7', '#651c58'),
	);

	return $map[$slug] ?? array('#f3bfd5', '#4b112d');
}
