<?php
/**
 * Umami Connect Integrations Registry
 *
 * Define available integrations and their loader configuration.
 *
 * @package UmamiConnect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return integrations configuration.
 *
 * @return array
 */
function umami_connect_get_integrations() {
	return array(
		'contact-form-7' => array(
			'check' => function () {
				return ( class_exists( 'WPCF7' ) || function_exists( 'wpcf7' ) );
			},
			'files' => array(
				'hooks.php',
				'admin-settings.php',
				'analytics-data.php',
			),
		),
		'wpforms'        => array(
			'check' => function () {
				return function_exists( 'wpforms' );
			},
			'files' => array(
				'hooks.php',
				'admin-settings.php',
				'analytics-data.php',
			),
		),
		// Add more integrations here.
	);
}
