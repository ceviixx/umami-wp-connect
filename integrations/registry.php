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
		'gutenberg'      => array(
			'label'       => 'Gutenberg',
			'description' => __( 'Create and track events in your editor blocks.', 'umami-connect' ),
			'color'       => '#2271b1',
			'check'       => function () {
				return function_exists( 'register_block_type' );
			},
			'files'       => array(
				// Gutenberg is included as an core feature
			),
		),
		'contact-form-7' => array(
			'label'       => 'Contact Form 7',
			'description' => __( 'Track form submissions with custom event names.', 'umami-connect' ),
			'color'       => '#f38020',
			'check'       => function () {
				return ( class_exists( 'WPCF7' ) || function_exists( 'wpcf7' ) );
			},
			'files'       => array(
				'hooks.php',
				'admin-settings.php',
				'analytics-data.php',
			),
		),
		'wpforms'        => array(
			'label'       => 'WPForms',
			'description' => __( 'Add Umami events to your WPForms.', 'umami-connect' ),
			'color'       => '#7a63f1',
			'check'       => function () {
				return function_exists( 'wpforms' );
			},
			'files'       => array(
				'hooks.php',
				'admin-settings.php',
				'analytics-data.php',
			),
		),
		// Add more integrations here.
	);
}
