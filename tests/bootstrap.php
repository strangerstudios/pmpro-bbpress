<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Pmpro_Bbpress
 */

/**
 * Manually load the plugin being tested and dependencies.
 */
$pmpro_plugins = [
	dirname( dirname( __FILE__ ) ) . '/../bbpress/bbpress.php',
	dirname( dirname( __FILE__ ) ) . '/pmpro-bbpress.php',
];

/**
 * Include bootstrap file from Paid Memberships Pro.
 */
require_once dirname( dirname( __FILE__ ) ) . '/../paid-memberships-pro/tests/bootstrap.php';

