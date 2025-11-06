<?php
/**
 * WPForms Integration - Events Provider
 *
 * Appends WPForms events and candidates to the Events Overview via filter.
 *
 * @package UmamiConnect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wpforms' ) ) {
	return;
}

/**
 * Append WPForms items to events list.
 *
 * @param array $events Existing events.
 * @param int   $per_page Per page (unused; pagination handled by List Table).
 * @return array
 */
add_filter(
	'umami_connect_get_all_events',
	function ( $events, $per_page = 25 ) {
		$events = is_array( $events ) ? $events : array();

		$forms = get_posts(
			array(
				'post_type'      => 'wpforms',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$integration_label = 'WPForms';
		$integration_color = '#7f54b3'; // Integration color can be adjusted here.

		foreach ( $forms as $form_id ) {
			$form     = wpforms()->form->get( $form_id );
			$settings = array();

			if ( is_array( $form ) ) {
				if ( isset( $form['settings'] ) && is_array( $form['settings'] ) ) {
					$settings = $form['settings'];
				} elseif ( isset( $form['post_content'] ) && is_string( $form['post_content'] ) && function_exists( 'wpforms_decode' ) ) {
					$decoded = wpforms_decode( $form['post_content'] );
					if ( is_array( $decoded ) && isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ) {
						$settings = $decoded['settings'];
					}
				}
			} elseif ( is_object( $form ) ) {
				if ( isset( $form->post_content ) && is_string( $form->post_content ) && function_exists( 'wpforms_decode' ) ) {
					$decoded = wpforms_decode( $form->post_content );
					if ( is_array( $decoded ) && isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ) {
						$settings = $decoded['settings'];
					}
				} elseif ( isset( $form->settings ) && is_array( $form->settings ) ) {
					$settings = $form->settings;
				}
			}

			$event_name = isset( $settings['umami_event_name'] ) ? trim( (string) $settings['umami_event_name'] ) : '';
			$pairs_raw  = isset( $settings['umami_event_data'] ) ? $settings['umami_event_data'] : '';

			$pairs = array();
			if ( is_string( $pairs_raw ) && $pairs_raw !== '' ) {
				$decoded = json_decode( $pairs_raw, true );
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
			}

			$is_tracked = ( $event_name !== '' );
			$label      = get_the_title( $form_id );

			$events[] = array(
				'event'             => $is_tracked ? (string) $event_name : '(Candidate)',
				'post_id'           => (int) $form_id,
				'post_title'        => $label,
				'block_type'        => 'WPForms',
				'label'             => $label,
				'data_pairs'        => $pairs,
				'block_index'       => '',
				'event_type'        => $is_tracked ? 'integration_wpforms' : 'none',
				'is_tracked'        => $is_tracked,
				'integration'       => 'wpforms',
				'integration_label' => $integration_label,
				'integration_color' => $integration_color,
				'edit_link'         => admin_url(
					'admin.php?page=wpforms-builder&view=settings&form_id=' . (int) $form_id . '&section=umami_tracking'
				),
				'edit_label'        => __( 'Edit form', 'umami-connect' ),
			);
		}

		return $events;
		return $events;
	},
	15,
	2
);
