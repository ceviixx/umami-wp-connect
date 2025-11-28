<?php
// Debug route for auto-update trigger (for development/testing)
add_action(
	'init',
	function () {
		if ( isset( $_GET['force_auto_update'] ) && $_GET['force_auto_update'] == '1' ) {
			require_once ABSPATH . 'wp-admin/includes/admin.php';

			$updates = array();
			$timestamp = gmdate( 'c' );

			// Collect updates via WordPress hooks
			add_action(
				'upgrader_process_complete',
				function ( $upgrader, $options ) use ( &$updates ) {
					if ( ! empty( $options['type'] ) && ! empty( $options['action'] ) && $options['action'] === 'update' ) {
						$type         = $options['type'];
						$updated_items = array();

						// Try to get updated items from upgrader result
						if ( isset( $upgrader->result['updated'] ) && is_array( $upgrader->result['updated'] ) ) {
							$updated_items = $upgrader->result['updated'];
						} elseif ( isset( $upgrader->result['destination_name'] ) ) {
							$updated_items[] = $upgrader->result['destination_name'];
						} elseif ( ! empty( $options['plugins'] ) ) {
							$updated_items = $options['plugins'];
						} elseif ( ! empty( $options['themes'] ) ) {
							$updated_items = $options['themes'];
						} elseif ( ! empty( $options['core'] ) ) {
							$updated_items = $options['core'];
						}

						$updates[] = array(
							'type'  => $type,
							'items' => $updated_items,
						);
					}
				},
				10,
				2
			);

			wp_maybe_auto_update();

			$response = array(
				'status'    => 'success',
				'timestamp' => $timestamp,
				'updates'   => $updates,
				'message'   => empty( $updates )
					? 'No updates were performed.'
					: 'Updates completed successfully.',
			);

			header( 'Content-Type: application/json' );
			echo json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
			exit;
		}
	}
);
