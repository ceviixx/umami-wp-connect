<?php
add_action(
	'wp_dashboard_setup',
	function () {
		wp_add_dashboard_widget(
			'umami_connect_update_widget',
			'umami Connect',
			function () {

				$events                = apply_filters( 'umami_connect_get_all_events', array() );
				$event_names_count     = 0;
				$event_key_value_count = 0;
				$event_names           = array();
				foreach ( $events as $row ) {
					$is_tracked = isset( $row['is_tracked'] ) ? (bool) $row['is_tracked'] : true;
					if ( $is_tracked && ! empty( $row['event'] ) ) {
						$event_names[ $row['event'] ] = true;
					}
					if ( $is_tracked && ! empty( $row['data_pairs'] ) && is_array( $row['data_pairs'] ) ) {
						$event_key_value_count += count( $row['data_pairs'] );
					}
				}
				$event_names_count = count( $event_names );
				echo '<div class="umami-health-widget" style="display:flex; gap:0; align-items:stretch; margin:auto;">';
				echo '<div style="flex:1.3; min-width:180px; display:flex; flex-direction:column; justify-content:center;">';
				echo '<table style="width:100%; border-collapse:collapse; font-size:14px;">';
				echo '<tr><td style="padding:6px 10px 6px 0; color:#666;">Event names</td><td style="padding:6px 0; font-weight:bold; text-align:right;">' . intval( $event_names_count ) . '</td></tr>';
				echo '<tr><td style="padding:6px 10px 6px 0; color:#666;">Event key-value pairs</td><td style="padding:6px 0; font-weight:bold; text-align:right;">' . intval( $event_key_value_count ) . '</td></tr>';
				echo '</table>';
				echo '</div>';
				echo '</div>';

				$mode      = get_option( 'umami_mode', 'cloud' );
				$host      = get_option( 'umami_host', '' );
				$login_url = '';
				if ( $mode === 'self' && ! empty( $host ) ) {
					$parsed = parse_url( $host );
					if ( ! empty( $parsed['scheme'] ) && ! empty( $parsed['host'] ) ) {
						$host_url = $parsed['scheme'] . '://' . $parsed['host'];
						if ( isset( $parsed['port'] ) ) {
							$host_url .= ':' . $parsed['port'];
						}
						$login_url = rtrim( $host_url, '/' ) . '/login';
					} else {
						$login_url = rtrim( $host, '/' ) . '/login';
					}
				} else {
					$login_url = 'https://cloud.umami.is/login';
				}
				echo '<hr style="border:0; border-top:1px solid #e0e0e0; margin:18px -12px 0 -12px; padding:0; width:calc(100% + 24px);" />';
				$share_url = get_option( 'umami_advanced_share_url' );
				$allowed_roles = get_option( 'umami_statistics_allowed_roles', array() );
				if ( ! is_array( $allowed_roles ) ) {
					$allowed_roles = array();
				}
				$user = wp_get_current_user();
				$user_roles = (array) $user->roles;
				$has_access = in_array( 'administrator', $user_roles );
				if ( ! $has_access ) {
					foreach ( $allowed_roles as $role ) {
						if ( in_array( $role, $user_roles ) ) {
							$has_access = true;
							break;
						}
					}
				}
				echo '<div style="padding-top:8px; text-align:left; display:flex; align-items:center; gap:0;">';
				echo '<a href="' . esc_url( $login_url ) . '" target="_blank" rel="noopener noreferrer" style="color:#21759b; font-size:13px; text-decoration:none; font-weight:400; display:inline-flex; align-items:center; gap:3px;">Umami Login'
					. '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#21759b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left:1px;"><path d="M18 13v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>'
					. '</a>';
				if ( ! empty( $share_url ) && $has_access ) {
					echo '<span style="display:inline-block; width:1px; height:18px; background:#e0e0e0; margin:0 12px; vertical-align:middle;"></span>';
					echo '<a href="' . esc_url( $share_url ) . '" target="_blank" rel="noopener noreferrer" style="color:#21759b; font-size:13px; text-decoration:none; font-weight:400; display:inline-flex; align-items:center; gap:3px;">See stats'
						. '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#21759b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left:1px;"><path d="M18 13v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>'
						. '</a>';
				}
				echo '</div>';
				echo '<style>'
					. '.umami-health-widget .umami-status-label.orange{color:#d63638;} .umami-health-widget .umami-status-label.green{color:#46b450;} .umami-progress-wrapper.orange svg circle:last-child{stroke:#d63638;} .umami-progress-wrapper.green svg circle:last-child{stroke:#46b450;}'
					. '</style>';
			}
		);
	}
);
