<?php
/**
 * Contact Form 7 Integration - Events Provider
 *
 * Appends CF7 events and candidates to the Events Overview via filter.
 *
 * @package UmamiConnect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPCF7' ) && ! function_exists( 'wpcf7' ) ) {
	return;
}

if ( ! defined( 'UMAMI_CF7_META_EVENT_NAME' ) ) {
	define( 'UMAMI_CF7_META_EVENT_NAME', '_umami_cf7_custom_event' );
}
if ( ! defined( 'UMAMI_CF7_META_EVENT_DATA' ) ) {
	define( 'UMAMI_CF7_META_EVENT_DATA', '_umami_cf7_event_data' );
}

/**
 * Append CF7 items to events list.
 *
 * @param array $events Existing events.
 * @param int   $per_page Per page (unused here; pagination handled in table).
 * @return array
 */
add_filter(
	'umami_connect_get_all_events',
	function ( $events, $per_page = 25 ) {
		$events = is_array( $events ) ? $events : array();

		$forms = get_posts(
			array(
				'post_type'      => 'wpcf7_contact_form',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$integration_label = 'Contact Form 7';
		$integration_color = '#f38020'; // Integration color can be changed here.

		foreach ( $forms as $form_id ) {
			$event_name     = get_post_meta( $form_id, UMAMI_CF7_META_EVENT_NAME, true );
			$event_data_raw = get_post_meta( $form_id, UMAMI_CF7_META_EVENT_DATA, true );

			$pairs = array();
			if ( is_string( $event_data_raw ) && $event_data_raw !== '' ) {
				$decoded = json_decode( $event_data_raw, true );
				if ( is_array( $decoded ) ) {
					foreach ( $decoded as $k => $v ) {
						$k = sanitize_key( (string) $k );
						if ( '' === $k ) {
							continue;
						}
						$pairs[] = array(
							'key'   => $k,
							'value' => (string) $v,
						);
					}
				}
			}       $is_tracked = ( $event_name !== '' );
			$label              = get_the_title( $form_id );

			$events[] = array(
				'event'             => $is_tracked ? (string) $event_name : '(Candidate)',
				'post_id'           => (int) $form_id,
				'post_title'        => $label,
				'block_type'        => 'contact-form-7',
				'label'             => $label,
				'data_pairs'        => $pairs,
				'block_index'       => '',
				'event_type'        => $is_tracked ? 'integration_cf7' : 'none',
				'is_tracked'        => $is_tracked,
				'integration'       => 'contact-form-7',
				'integration_label' => $integration_label,
				'integration_color' => $integration_color,
				'edit_link'         => admin_url(
					'admin.php?page=wpcf7&action=edit&post=' . (int) $form_id . '&umami_tab=umami-tracking-panel'
				),
				'edit_label'        => __( 'Edit form', 'umami-connect' ),
			);
		}

		return $events;
	},
	15,
	2
);
