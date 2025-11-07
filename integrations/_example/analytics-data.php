<?php
/**
 * Example Integration - Events Provider
 *
 * Pushes integration-specific events and candidates into the Events Overview.
 * Copy to your integration folder and replace placeholders.
 *
 * @package UmamiConnect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Guard: only run when your dependency is present.
if ( ! function_exists( 'my_dependency_function' ) ) {
	return;
}

/**
 * Append items to events list.
 *
 * @param array $events Existing events.
 * @param int   $per_page Per page (unused; table paginates separately).
 * @return array
 */
add_filter(
	'umami_connect_get_all_events',
	function ( $events, $per_page = 25 ) {
		$events = is_array( $events ) ? $events : array();

		// TODO: Replace with your own source discovery (posts, forms, etc.)
		$items = array(); // e.g. get_posts([...])

		$integration_key   = 'myintegration'; // must match your folder name
		$integration_label = 'My Integration';
		$integration_color = '#606770';

		foreach ( $items as $item_id ) {
			$label       = 'Item ' . (int) $item_id; // Human-friendly label
			$event_name  = ''; // Load from meta/settings if configured
			$pairs       = array(); // Convert key/value into [ ['key'=>..,'value'=>..], ... ] if needed
			$is_tracked  = ( $event_name !== '' );

			$events[] = array(
				'event'             => $is_tracked ? (string) $event_name : '(Candidate)',
				'post_id'           => (int) $item_id,
				'post_title'        => $label,
				'block_type'        => 'MyIntegration',
				'label'             => $label,
				'data_pairs'        => $pairs,
				'block_index'       => '',
				'event_type'        => $is_tracked ? 'integration_myintegration' : 'none',
				'is_tracked'        => $is_tracked,
				'integration'       => $integration_key,
				'integration_label' => $integration_label,
				'integration_color' => $integration_color,
				'edit_link'         => admin_url( 'admin.php?page=myintegration-settings&item=' . (int) $item_id ),
				'edit_label'        => __( 'Edit', 'umami-connect' ),
			);
		}

		return $events;
	},
	15,
	2
);
