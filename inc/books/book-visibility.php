<?php
/**
 * Book visibility helpers.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

/**
 * Returns true if the book should be shown in the library.
 *
 * @param int  $post_id
 * @param bool $allow_hidden_from_library Pass true to bypass hide_from_library check.
 * @return bool
 */
function bbb_is_book_visible(int $post_id, bool $allow_hidden_from_library = false): bool {
	if (get_post_status($post_id) !== 'publish') {
		return false;
	}

	if (apply_filters('bbb_show_all_imported_books', true, $post_id)) {
		return true;
	}

	if (!$allow_hidden_from_library && get_post_meta($post_id, '_bbb_hide_from_library', true) === '1') {
		return false;
	}

	if (!$allow_hidden_from_library) {
		$newsletter_date = get_post_meta($post_id, '_bbb_newsletter_date', true);
		if ($newsletter_date) {
			$tz     = new DateTimeZone('America/Los_Angeles');
			$unlock = new DateTime($newsletter_date . ' 10:00:00', $tz);
			$now    = new DateTime('now', $tz);

			if ($now < $unlock) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Returns true if the book is on a private shelf.
 *
 * @param int $post_id
 * @return bool
 */
function bbb_is_book_private(int $post_id): bool {
	$raw = get_post_meta($post_id, '_bbb_private_shelf', true);
	if ($raw === '' || $raw === null) {
		return false;
	}

	$lower = strtolower(trim((string) $raw));

	return in_array($lower, array('1', 'true', 'yes', 'private shelf'), true);
}
