<?php
/**
 * Monthly Society shop discount.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

const BBB_SOCIETY_DISCOUNT_FEE_ID = 'bbb_society_monthly_discount';

function bbb_society_discount_month_key(): string {
	return (string) date_i18n('Ym');
}

function bbb_society_discount_percent(): int {
	$discounts = array(10, 20, 30, 40, 50, 60, 70);
	$seed      = (int) sprintf('%u', crc32(bbb_society_discount_month_key()));

	return (int) $discounts[$seed % count($discounts)];
}

function bbb_society_discount_member_has_access(): bool {
	if (function_exists('bbb_reader_is_society') && bbb_reader_is_society()) {
		return true;
	}

	if (is_user_logged_in()) {
		return true;
	}

	if (!function_exists('bbb_reader_current_identity')) {
		return false;
	}

	$identity = bbb_reader_current_identity();

	return is_array($identity) && '' !== trim((string) ($identity['email'] ?? ''));
}

function bbb_society_discount_identity_hash(): string {
	if (is_user_logged_in()) {
		return 'user:' . (string) get_current_user_id();
	}

	if (!function_exists('bbb_reader_current_identity')) {
		return '';
	}

	$identity = bbb_reader_current_identity();
	if (!is_array($identity)) {
		return '';
	}

	$email = strtolower(trim((string) ($identity['email'] ?? '')));
	if ('' === $email) {
		return '';
	}

	return 'email:' . hash_hmac('sha256', $email, wp_salt('auth'));
}

function bbb_society_discount_has_used_this_month(): bool {
	$month = bbb_society_discount_month_key();

	if (is_user_logged_in() && $month === (string) get_user_meta(get_current_user_id(), '_bbb_society_discount_used_month', true)) {
		return true;
	}

	$identity_hash = bbb_society_discount_identity_hash();
	if ('' === $identity_hash) {
		return false;
	}

	$used = get_option('bbb_society_discount_used_months', array());

	return is_array($used) && $month === (string) ($used[$identity_hash] ?? '');
}

function bbb_society_discount_applied_months_option(): array {
	$applied = get_option('bbb_society_discount_applied_months', array());

	return is_array($applied) ? $applied : array();
}

function bbb_society_discount_is_applied_for_identity(): bool {
	$month = bbb_society_discount_month_key();

	if (is_user_logged_in() && $month === (string) get_user_meta(get_current_user_id(), '_bbb_society_discount_applied_month', true)) {
		return true;
	}

	$identity_hash = bbb_society_discount_identity_hash();
	if ('' === $identity_hash) {
		return false;
	}

	$applied = bbb_society_discount_applied_months_option();

	return $month === (string) ($applied[$identity_hash] ?? '');
}

function bbb_society_discount_is_applied_to_session(): bool {
	if (bbb_society_discount_is_applied_for_identity()) {
		return true;
	}

	return function_exists('EDD') && EDD()->session && bbb_society_discount_month_key() === (string) EDD()->session->get('bbb_society_discount_month');
}

function bbb_society_discount_clear_cart_fee(): void {
	if (function_exists('EDD') && EDD()->fees) {
		EDD()->fees->remove_fee(BBB_SOCIETY_DISCOUNT_FEE_ID);
	}
}

function bbb_society_discount_refresh_cart_fee(): void {
	if (!function_exists('EDD') || !EDD()->fees) {
		return;
	}

	bbb_society_discount_clear_cart_fee();
}

function bbb_society_discount_cart_fee(): array {
	if (!function_exists('edd_get_cart_subtotal')) {
		return array();
	}

	$subtotal = (float) edd_get_cart_subtotal();
	if ($subtotal <= 0) {
		return array();
	}

	$percent  = bbb_society_discount_percent();
	$amount   = min($subtotal, round($subtotal * ($percent / 100), 2));
	$label    = sprintf('Society monthly discount (%d%% off)', $percent);

	return array(
		'amount' => number_format(-1 * $amount, function_exists('edd_currency_decimal_filter') ? edd_currency_decimal_filter() : 2, '.', ''),
		'label'  => $label,
		'no_tax' => true,
		'type'   => 'fee',
	);
}

function bbb_society_discount_inject_cart_fee(array $fees): array {
	unset($fees[BBB_SOCIETY_DISCOUNT_FEE_ID]);

	if (!bbb_society_discount_is_applied_to_session() || bbb_society_discount_has_used_this_month()) {
		return $fees;
	}

	$fee = bbb_society_discount_cart_fee();
	if (!$fee) {
		return $fees;
	}

	$fees[BBB_SOCIETY_DISCOUNT_FEE_ID] = $fee;

	return $fees;
}

function bbb_society_discount_redirect_with_status(string $status): void {
	$redirect = wp_get_referer();
	if (!is_string($redirect) || '' === $redirect) {
		$redirect = function_exists('bbb_page_url') ? bbb_page_url('smut-sentiment-society') : home_url('/');
	}

	$redirect = remove_query_arg('society_discount', $redirect);
	$redirect = strtok($redirect, '#') ?: $redirect;

	wp_safe_redirect(add_query_arg('society_discount', rawurlencode($status), $redirect) . '#society-shop-discount');
	exit;
}

function bbb_society_discount_handle_apply(): void {
	check_admin_referer('bbb_apply_society_discount');

	if (!bbb_society_discount_member_has_access()) {
		bbb_society_discount_redirect_with_status('member-required');
	}

	if (bbb_society_discount_has_used_this_month()) {
		if (function_exists('EDD') && EDD()->session) {
			EDD()->session->set('bbb_society_discount_month', null);
			EDD()->session->set('bbb_society_discount_identity', null);
		}
		bbb_society_discount_clear_cart_fee();
		bbb_society_discount_redirect_with_status('used');
	}

	$month = bbb_society_discount_month_key();
	if (is_user_logged_in()) {
		update_user_meta(get_current_user_id(), '_bbb_society_discount_applied_month', $month);
	}

	$identity_hash = bbb_society_discount_identity_hash();
	if ('' !== $identity_hash) {
		$applied = bbb_society_discount_applied_months_option();
		$applied[$identity_hash] = $month;
		update_option('bbb_society_discount_applied_months', $applied, false);
	}

	if (function_exists('EDD') && EDD()->session) {
		EDD()->session->set('bbb_society_discount_month', $month);
		EDD()->session->set('bbb_society_discount_identity', $identity_hash);
	}

	bbb_society_discount_refresh_cart_fee();
	bbb_society_discount_redirect_with_status('applied');
}

function bbb_society_discount_order_has_fee($order): bool {
	if (!$order || !method_exists($order, 'get_fees')) {
		return false;
	}

	foreach ((array) $order->get_fees() as $fee) {
		$type_key = is_object($fee) ? (string) ($fee->type_key ?? '') : '';
		$label    = is_object($fee) ? (string) ($fee->description ?? '') : '';

		if (BBB_SOCIETY_DISCOUNT_FEE_ID === $type_key || str_starts_with($label, 'Society monthly discount')) {
			return true;
		}
	}

	return false;
}

function bbb_society_discount_mark_month_used(int $order_id): void {
	if (!function_exists('edd_get_order')) {
		return;
	}

	$order = edd_get_order($order_id);
	if (!bbb_society_discount_order_has_fee($order)) {
		return;
	}

	$month = bbb_society_discount_month_key();

	if (is_user_logged_in()) {
		update_user_meta(get_current_user_id(), '_bbb_society_discount_used_month', $month);
	}

	$identity_hash = function_exists('EDD') && EDD()->session ? (string) EDD()->session->get('bbb_society_discount_identity') : '';
	if ('' === $identity_hash) {
		$identity_hash = bbb_society_discount_identity_hash();
	}

	if ('' !== $identity_hash) {
		$used = get_option('bbb_society_discount_used_months', array());
		$used = is_array($used) ? $used : array();
		$used[$identity_hash] = $month;
		update_option('bbb_society_discount_used_months', $used, false);
	}

	if (function_exists('EDD') && EDD()->session) {
		EDD()->session->set('bbb_society_discount_month', null);
		EDD()->session->set('bbb_society_discount_identity', null);
	}

	if (is_user_logged_in()) {
		delete_user_meta(get_current_user_id(), '_bbb_society_discount_applied_month');
	}

	$applied = bbb_society_discount_applied_months_option();
	if ('' !== $identity_hash && isset($applied[$identity_hash])) {
		unset($applied[$identity_hash]);
		update_option('bbb_society_discount_applied_months', $applied, false);
	}
}

add_action('admin_post_bbb_apply_society_discount', 'bbb_society_discount_handle_apply');
add_action('admin_post_nopriv_bbb_apply_society_discount', 'bbb_society_discount_handle_apply');
add_action('wp', 'bbb_society_discount_refresh_cart_fee', 20);
add_action('edd_post_add_to_cart', 'bbb_society_discount_refresh_cart_fee', 20);
add_action('edd_post_remove_from_cart', 'bbb_society_discount_refresh_cart_fee', 20);
add_action('edd_after_set_cart_item_quantity', 'bbb_society_discount_refresh_cart_fee', 20);
add_action('edd_cart_discounts_updated', 'bbb_society_discount_refresh_cart_fee', 20);
add_action('edd_complete_purchase', 'bbb_society_discount_mark_month_used', 20);
add_filter('edd_fees_get_fees', 'bbb_society_discount_inject_cart_fee', 20);
