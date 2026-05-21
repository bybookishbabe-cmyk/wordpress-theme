<?php
/**
 * Switch local EDD checkout from the fake store/manual gateway to Stripe.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$socket = getenv('BBB_WP_DB_SOCKET') ?: '/Users/autumnmarie/Library/Application Support/Local/run/1wlaP1REx/mysql/mysqld.sock';
$mysqli = mysqli_init();
if (!$mysqli || !mysqli_real_connect($mysqli, 'localhost', 'root', 'root', 'local', null, $socket)) {
	fwrite(STDERR, "Could not connect to local WordPress database.\n");
	exit(1);
}

$row = mysqli_query($mysqli, "SELECT option_value FROM wp_options WHERE option_name = 'edd_settings' LIMIT 1");
if (!$row) {
	fwrite(STDERR, mysqli_error($mysqli) . PHP_EOL);
	exit(1);
}

$settings_row = mysqli_fetch_assoc($row);
$settings = isset($settings_row['option_value']) ? @unserialize((string) $settings_row['option_value']) : array();
if (!is_array($settings)) {
	$settings = array();
}

$settings['gateways'] = array('stripe' => 1);
$settings['default_gateway'] = 'stripe';
$settings['gateways_order'] = 'stripe';
unset($settings['manual']);

$serialized = mysqli_real_escape_string($mysqli, serialize($settings));
if (!mysqli_query($mysqli, "UPDATE wp_options SET option_value = '{$serialized}' WHERE option_name = 'edd_settings'")) {
	fwrite(STDERR, mysqli_error($mysqli) . PHP_EOL);
	exit(1);
}

echo json_encode(
	array(
		'gateway' => 'stripe',
		'manualGatewayDisabled' => true,
		'testMode' => !empty($settings['test_mode']),
	),
	JSON_PRETTY_PRINT
) . PHP_EOL;
