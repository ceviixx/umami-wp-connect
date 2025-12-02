<?php
add_action( 'wp_dashboard_setup', 'umami_connect_add_stats_widget' );

function umami_connect_add_stats_widget() {
	$share_url = get_option( 'umami_advanced_share_url', '' );
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
	if ( empty( $share_url ) || ! $has_access ) {
		return;
	}
	wp_add_dashboard_widget( 'umami_stats_widget', 'Umami Stats', 'umami_connect_render_stats_widget' );
}

function umami_connect_render_stats_widget() {
	$share_url = get_option( 'umami_advanced_share_url', '' );
	if ( empty( $share_url ) ) {
		echo '<p>' . esc_html__( 'No Umami Share URL defined.', 'umami-connect' ) . '</p>';
		return;
	}

	// Token transient key depends on share URL
	$transient_key = 'umami_stats_token_' . md5( $share_url );
	$token_data = get_transient( $transient_key );

	// Wenn kein Token oder Share-URL geändert, neu holen
	if ( ! $token_data || ! isset( $token_data['token'] ) || ! isset( $token_data['websiteId'] ) ) {
		$api_url = umami_connect_get_token_api_url( $share_url );
		$response = wp_remote_get( $api_url );
		if ( is_wp_error( $response ) ) {
			echo '<p>' . esc_html__( 'Error fetching token.', 'umami-connect' ) . '</p>';
			return;
		}
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! isset( $data['token'] ) || ! isset( $data['websiteId'] ) ) {
			echo '<p>' . esc_html__( 'Invalid token response.', 'umami-connect' ) . '</p>';
			return;
		}
		$token_data = array(
			'token' => $data['token'],
			'websiteId' => $data['websiteId'],
		);
		set_transient( $transient_key, $token_data, DAY_IN_SECONDS );
	}

	// Stats holen
	$stats = umami_connect_get_stats( $token_data['websiteId'], $token_data['token'] );
	if ( is_wp_error( $stats ) ) {
		echo '<p>' . esc_html__( 'Error fetching stats.', 'umami-connect' ) . '</p>';
		return;
	}
	// Tabellenlayout wie Status-Widget
	echo '<div class="umami-stats-table" style="width:100%;">';
	echo '<table style="width:100%; border-collapse:collapse; font-size:15px; text-align:left; table-layout:fixed; background:#fff; border-radius:8px; box-shadow:none;">';
	echo '<colgroup>';
	for ( $i = 0; $i < 5; $i++ ) {
		echo '<col style="width:20%;">';
	}
	echo '</colgroup>';
	echo '<tr>';
	echo '<th style="padding:5px 0; color:#666; font-weight:500; text-align:left;">' . esc_html__( 'Visitors', 'umami-connect' ) . '</th>';
	echo '<th style="padding:5px 0; color:#666; font-weight:500; text-align:left;">' . esc_html__( 'Visits', 'umami-connect' ) . '</th>';
	echo '<th style="padding:5px 0; color:#666; font-weight:500; text-align:left;">' . esc_html__( 'Views', 'umami-connect' ) . '</th>';
	echo '<th style="padding:5px 0; color:#666; font-weight:500; text-align:left;">' . esc_html__( 'Bounce rate', 'umami-connect' ) . '</th>';
	echo '<th style="padding:5px 0; color:#666; font-weight:500; text-align:left;">' . esc_html__( 'Visit duration', 'umami-connect' ) . '</th>';
	echo '</tr>';
	echo '<tr>';
	echo '<td style="font-size:2em; font-weight:700; color:#23282d; padding:5px 0; text-align:left;">' . intval( $stats['visitors'] ) . '</td>';
	echo '<td style="font-size:2em; font-weight:700; color:#23282d; padding:5px 0; text-align:left;">' . intval( $stats['visits'] ) . '</td>';
	echo '<td style="font-size:2em; font-weight:700; color:#23282d; padding:5px 0; text-align:left;">' . intval( $stats['pageviews'] ) . '</td>';
	$bounce = ( isset( $stats['bounces'] ) && isset( $stats['visits'] ) && $stats['visits'] > 0 ) ? round( $stats['bounces'] / $stats['visits'] * 100 ) : 0;
	echo '<td style="font-size:2em; font-weight:700; color:#23282d; padding:5px 0; text-align:left;">' . $bounce . '%</td>';
	$duration = ( isset( $stats['totaltime'] ) && isset( $stats['visits'] ) && $stats['visits'] > 0 ) ? round( $stats['totaltime'] / max( 1, $stats['visits'] ) ) : 0;
	echo '<td style="font-size:2em; font-weight:700; color:#23282d; padding:5px 0; text-align:left;">' . umami_connect_format_short_time($duration) . '</td>';
	echo '</tr>';
	// Vergleichs-Badges als Tabellenzeile unterhalb der Hauptwerte
	$comp = $stats['comparison'];
	echo '<tr>';
	// Visitors
	$visitors_val = intval($stats['visitors']);
	$visitors_comp = intval($comp['visitors']);
	$visitors_change = ($visitors_comp > 0) ? round((($visitors_val - $visitors_comp) / $visitors_comp) * 100) : 0;
	$visitors_icon = ($visitors_change >= 0) ? 'arrow-up-alt' : 'arrow-down-alt';
	$visitors_color = ($visitors_change >= 0) ? '#46b450' : '#d63638';
	echo '<td style="text-align:left; padding:2px 0;">';
	umami_connect_stats_badge( $visitors_change, $visitors_color, $visitors_icon );
	echo '</td>';
	// Visits
	$visits_val = intval($stats['visits']);
	$visits_comp = intval($comp['visits']);
	$visits_change = ($visits_comp > 0) ? round((($visits_val - $visits_comp) / $visits_comp) * 100) : 0;
	$visits_icon = ($visits_change >= 0) ? 'arrow-up-alt' : 'arrow-down-alt';
	$visits_color = ($visits_change >= 0) ? '#46b450' : '#d63638';
	echo '<td style="text-align:left; padding:2px 0;">';
	umami_connect_stats_badge( $visits_change, $visits_color, $visits_icon );
	echo '</td>';
	// Views
	$views_val = intval($stats['pageviews']);
	$views_comp = intval($comp['pageviews']);
	$views_change = ($views_comp > 0) ? round((($views_val - $views_comp) / $views_comp) * 100) : 0;
	$views_icon = ($views_change >= 0) ? 'arrow-up-alt' : 'arrow-down-alt';
	$views_color = ($views_change >= 0) ? '#46b450' : '#d63638';
	echo '<td style="text-align:left; padding:2px 0;">';
	umami_connect_stats_badge( $views_change, $views_color, $views_icon );
	echo '</td>';
	// Bounce
	$bounce_val = (isset($stats['bounces']) && isset($stats['visits']) && $stats['visits'] > 0) ? round($stats['bounces'] / $stats['visits'] * 100) : 0;
	$bounce_comp = (isset($comp['bounces']) && isset($comp['visits']) && $comp['visits'] > 0) ? round($comp['bounces'] / $comp['visits'] * 100) : 0;
	$bounce_change = ($bounce_comp > 0) ? round((($bounce_val - $bounce_comp) / $bounce_comp) * 100) : 0;
	$bounce_icon = ($bounce_change > 0) ? 'arrow-up-alt' : 'arrow-down-alt'; // Mehr Bounce ist schlechter
	$bounce_color = ($bounce_change > 0) ? '#d63638' : '#46b450';
	echo '<td style="text-align:left; padding:2px 0;">';
	umami_connect_stats_badge( $bounce_change, $bounce_color, $bounce_icon, 'percent', true );
	echo '</td>';
	// Duration
	$duration_val = (isset($stats['totaltime']) && isset($stats['visits']) && $stats['visits'] > 0) ? round($stats['totaltime'] / max(1, $stats['visits'])) : 0;
	$duration_comp = (isset($comp['totaltime']) && isset($comp['visits']) && $comp['visits'] > 0) ? round($comp['totaltime'] / max(1, $comp['visits'])) : 0;
	$duration_change = ($duration_comp > 0) ? round((($duration_val - $duration_comp) / $duration_comp) * 100) : 0;
	$duration_icon = ($duration_change > 0) ? 'arrow-up-alt' : 'arrow-down-alt'; // Mehr Dauer ist schlechter
	$duration_color = ($duration_change > 0) ? '#d63638' : '#46b450';
	echo '<td style="text-align:left; padding:2px 0;">';
	umami_connect_stats_badge( $duration_change, $duration_color, $duration_icon, 'duration', true );
	echo '</td>';
	echo '</tr>';
	echo '</table>';
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
	echo '<div style="padding-top:8px; text-align:left; display:flex; align-items:center; gap:0;">';
	echo '<a href="' . esc_url( $login_url ) . '" target="_blank" rel="noopener noreferrer" style="color:#21759b; font-size:13px; text-decoration:none; font-weight:400; display:inline-flex; align-items:center; gap:3px;">Umami ' . esc_html__( 'Login', 'umami-connect' )
		. '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#21759b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left:1px;"><path d="M18 13v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>'
		. '</a>';
	echo '<span style="display:inline-block; width:1px; height:18px; background:#e0e0e0; margin:0 12px; vertical-align:middle;"></span>';
	echo '<a href="' . esc_url( $share_url ) . '" target="_blank" rel="noopener noreferrer" style="color:#21759b; font-size:13px; text-decoration:none; font-weight:400; display:inline-flex; align-items:center; gap:3px;">' . esc_html__( 'See stats', 'umami-connect' )
		. '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#21759b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left:1px;"><path d="M18 13v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>'
		. '</a>';
	echo '<span style="flex:1 1 auto;"></span>';
	echo '<span style="font-size:12px; color:#888; margin-left:8px;">' . esc_html__( 'Last 24 hours', 'umami-connect' ) . '</span>';
	echo '</div>';
    echo '<style>'
        . '.umami-health-widget .umami-status-label.orange{color:#d63638;} .umami-health-widget .umami-status-label.green{color:#46b450;} .umami-progress-wrapper.orange svg circle:last-child{stroke:#d63638;} .umami-progress-wrapper.green svg circle:last-child{stroke:#46b450;}'
        . '</style>';
}



function umami_connect_get_token_api_url( $share_url ) {
	$parts = wp_parse_url( $share_url );
	if ( ! isset( $parts['scheme'], $parts['host'], $parts['path'] ) ) {
		return '';
	}
	$share_id = basename( $parts['path'] );
	// Cloud: https://cloud.umami.is/share/ID → https://cloud.umami.is/analytics/eu/api/share/ID
	if ( strpos( $parts['host'], 'cloud.umami.is' ) !== false ) {
		$base = $parts['scheme'] . '://' . $parts['host'] . '/analytics/eu/';
		return trailingslashit( $base ) . 'api/share/' . $share_id;
	}
	// Self-hosted: http(s)://host/share/ID → http(s)://host/api/share/ID
	$base = $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
	return trailingslashit( $base ) . 'api/share/' . $share_id;
}

function umami_connect_get_stats( $website_id, $token ) {
	$share_url = get_option( 'umami_advanced_share_url', '' );
	$parts = wp_parse_url( $share_url );
	if ( ! isset( $parts['scheme'], $parts['host'] ) ) {
		return new WP_Error( 'invalid_url', 'Invalid share URL' );
	}
	$timezone = 'Europe/Berlin';
	$end = time() * 1000;
	// $start = $end - ( 24 * 60 * 60 * 1000 ); // last 24 hours
    $start = $end - ( 7 * 24 * 60 * 60 * 1000 ); // last 7 days
	// Cloud: https://cloud.umami.is → https://cloud.umami.is/analytics/eu/api/websites/{id}/stats
	if ( strpos( $parts['host'], 'cloud.umami.is' ) !== false ) {
		$base = $parts['scheme'] . '://' . $parts['host'] . '/analytics/eu/';
		$api_url = sprintf( '%sapi/websites/%s/stats?startAt=%d&endAt=%d&unit=hour&timezone=%s',
			$base,
			urlencode( $website_id ),
			$start,
			$end,
			urlencode( $timezone )
		);
	} else {
		// Self-hosted: http(s)://host/api/websites/{id}/stats
		$base = $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
		$api_url = sprintf( '%s/api/websites/%s/stats?startAt=%d&endAt=%d&unit=hour&timezone=%s',
			$base,
			urlencode( $website_id ),
			$start,
			$end,
			urlencode( $timezone )
		);
	}
	$args = array(
		'headers' => array(
			'x-umami-share-token' => $token,
		),
		'timeout' => 10,
	);
	$response = wp_remote_get( $api_url, $args );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	return $data;
}


function umami_connect_stats_badge( $change, $color, $arrow, $type = 'percent', $forceRed = false ) {
    // Bounce rate und Visit duration: 0 = rot, kein Arrow
    // Andere Werte: 0 = grün, kein Arrow
    if ($change === 0 || $change === 0.0) {
        $color = $forceRed ? '#d63638' : '#46b450';
        $arrow = '';
    }
    $outline = ($color === '#46b450') ? '1px solid #46b450' : '1px solid #d63638';
    $icon_color = $color;
    echo '<span style="display:inline-flex; align-items:center; justify-content:center; gap:1px; min-width:28px; height:22px; font-size:0.85em; color:#23282d; border:' . $outline . '; border-radius:8px; background:transparent; padding:10px 8px; font-weight:500; box-sizing:border-box;">';
    if ($arrow) {
        echo '<span class="dashicons dashicons-' . esc_attr( $arrow ) . '" style="font-size:1em; color:' . $icon_color . '; vertical-align:middle; position:relative; top:4px;"></span>';
    }
    if ($type === 'percent') {
        echo '<span style="vertical-align:middle; font-family:inherit; font-weight:500; letter-spacing:0.5px;">' . abs( intval( $change ) ) . '%</span>';
    } elseif ($type === 'duration') {
        $abs_change = abs($change);
        $formatted = umami_connect_format_short_time(round($abs_change));
        echo '<span style="vertical-align:middle; font-family:inherit; font-weight:500; letter-spacing:0.5px;">' . ($change < 0 ? '-' : '') . $formatted . '</span>';
    } else {
        echo '<span style="vertical-align:middle; font-family:inherit; font-weight:500; letter-spacing:0.5px;">' . abs(intval($change)) . '</span>';
    }
    echo '</span>';
}

function umami_connect_format_short_time($val) {
    $days = (int)($val / 86400);
    $hours = (int)($val / 3600) - $days * 24;
    $minutes = (int)($val / 60) - $days * 1440 - $hours * 60;
    $seconds = (int)$val - $days * 86400 - $hours * 3600 - $minutes * 60;
    $parts = array();
    if ($hours > 0) $parts[] = $hours . 'h';
    if ($minutes > 0) $parts[] = $minutes . 'm';
    if ($seconds > 0 || empty($parts)) $parts[] = $seconds . 's';
    return implode(' ', $parts);
}