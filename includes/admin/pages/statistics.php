<?php
function umami_statistics_page() {
	$iframe_url              = apply_filters( 'umami_analytics_iframe_url', get_option( 'umami_advanced_share_url' ) );
	$headers                 = @get_headers( $iframe_url, 1 );
	$blocked                 = false;
	$block_reason            = '';
	$is_umami_cloud          = strpos( $iframe_url, 'cloud.umami.is' ) !== false;
	$current_host            = ( isset( $_SERVER['HTTP_HOST'] ) ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : 'localhost';
	$current_proto           = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) ? 'https://' : 'http://';
	$current_origin          = $current_proto . $current_host;
	$frame_ancestors_allowed = false;
	if ( $headers && is_array( $headers ) ) {
		foreach ( $headers as $key => $value ) {
			$key_lower = strtolower( $key );
			if ( $key_lower === 'x-frame-options' ) {
				if ( ! $is_umami_cloud ) {
					$blocked      = true;
					$block_reason = 'X-Frame-Options: ' . ( is_array( $value ) ? implode( ', ', $value ) : $value );
				}
			}
			if ( $key_lower === 'content-security-policy' ) {
				if ( is_array( $value ) ) {
					$value = implode( ' ', $value );
				}
				if ( stripos( $value, 'frame-ancestors' ) !== false && ! $is_umami_cloud ) {
					if ( preg_match( '/frame-ancestors ([^;]+)/i', $value, $matches ) ) {
						$ancestors = preg_split( '/\s+/', trim( $matches[1] ) );
						foreach ( $ancestors as $ancestor ) {
							if ( $ancestor === "'self'" || $ancestor === $current_origin || $ancestor === $current_host || strpos( $ancestor, $current_host ) !== false ) {
								$frame_ancestors_allowed = true;
								break;
							}
						}
					}
					if ( ! $frame_ancestors_allowed ) {
						$blocked      = true;
						$block_reason = 'Content-Security-Policy: ' . $value . ' (Host/Origin nicht erlaubt: ' . esc_html( $current_origin ) . ')';
					}
				}
			}
		}
	}
	echo '<div id="" style="">';
	if ( $blocked ) {
		echo '<style>@media (min-width: 600px) { .umami-embed-alert { max-width:600px !important; padding:40px 32px !important; margin:48px auto 0 auto !important; font-size:1.15em !important; } } </style>';
		echo '<div class="umami-embed-alert" style="color:#b00;background:#fff;border-radius:12px;border:1.5px solid #b00;padding:7vw 4vw;margin:8vw auto 0 auto;font-size:1.08em;text-align:center;max-width:96vw;width:100%;box-sizing:border-box;box-shadow:0 4px 24px 0 rgba(0,0,0,0.10),0 1.5px 6px 0 rgba(0,0,0,0.08);">'
			. '<h2 style="color:#b00;margin-bottom:16px;">Umami Dashboard cannot be embedded</h2>'
			. '<p style="margin-bottom:12px;">The Umami dashboard does not allow embedding for this website.</p>'
			. '<p style="color:#333;font-size:1em;margin-bottom:12px;">Reason: <strong>' . esc_html( $block_reason ) . '</strong></p>'
			. '<p style="color:#555;font-size:0.95em;margin-bottom:12px;">Allowed hosts/origins for embedding must be configured in your proxy.<br>Please check your proxy configuration or contact your administrator.</p>'
			. '<p style="margin-top:18px;"><a href="https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/wiki" target="_blank" style="color:#b00;text-decoration:underline;">Troubleshooting: Proxy & Access</a></p>'
			. '</div>';
	} elseif ( $headers === false || empty( $headers ) ) {
		echo '<style>@media (min-width: 600px) { .umami-embed-alert { max-width:600px !important; padding:40px 32px !important; margin:48px auto 0 auto !important; font-size:1.15em !important; } } </style>';
		echo '<div class="umami-embed-alert" style="color:#b00;background:#fff;border-radius:12px;border:1.5px solid #b00;padding:7vw 4vw;margin:8vw auto 0 auto;font-size:1.08em;text-align:center;max-width:96vw;width:100%;box-sizing:border-box;box-shadow:0 4px 24px 0 rgba(0,0,0,0.10),0 1.5px 6px 0 rgba(0,0,0,0.08);">'
			. '<h2 style="color:#b00;margin-bottom:16px;">Dashboard could not be loaded</h2>'
			. '<p style="margin-bottom:12px;">The Umami dashboard is currently offline or not reachable.</p>'
			. '<p style="color:#555;font-size:0.95em;margin-bottom:12px;">Please check your Umami instance or the Share URL.</p>'
			. '</div>';
	} else {
		echo '<style>#wpcontent { padding-left: 0 !important; } #wpbody-content { padding-bottom: 0 !important; padding-top: 0 !important; } #wpfooter { display: none !important; height: 0 !important; min-height: 0 !important; max-height: 0 !important; padding: 0 !important; border: none !important; overflow: hidden !important; }</style>';
		echo '<iframe id="umami-analytics-iframe" src="' . esc_url( $iframe_url ) . '" style="width:100%; min-height:400px; border:none; margin:0; padding:0; display:block;"></iframe>';
		echo '<script>
			document.addEventListener("DOMContentLoaded", () => {
				const iframe = document.getElementById("umami-analytics-iframe");
				const setHeight = () =>
				iframe.style.height = Math.max(400, window.innerHeight - (document.getElementById("wpadminbar")?.offsetHeight || 32)) + "px";
				setHeight();
				window.addEventListener("resize", setHeight);
			});
		</script>';
	}
	echo '</div>';
}
