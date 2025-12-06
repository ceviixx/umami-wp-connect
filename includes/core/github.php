<?php
/**
 * Umami Connect GitHub Update & Info Logic
 *
 * Contains update API override and plugin info logic for Umami Connect.
 */
class Umami_Connect_Github {
	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'github_plugin_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'github_plugin_api' ), 10, 3 );
	}

	public static function github_plugin_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		$release = self::get_latest_github_release();
		if ( ! $release || empty( $release['version'] ) ) {
			return $transient;
		}
		$plugin_file           = 'umami-wp-connect/umami-connect.php';
		$current_version_data  = get_plugin_data( WP_PLUGIN_DIR . '/umami-wp-connect/umami-connect.php' );
		$current_version       = isset( $current_version_data['Version'] ) ? $current_version_data['Version'] : '0.0.0';
		$current_version_clean = ltrim( strtolower( $current_version ), 'v' );
		$latest_version_clean  = ltrim( strtolower( $release['version'] ), 'v' );
		if ( version_compare( $latest_version_clean, $current_version_clean, '>' ) ) {
			$obj                                 = new stdClass();
			$obj->slug                           = 'umami-wp-connect';
			$obj->plugin                         = $plugin_file;
			$obj->new_version                    = $latest_version_clean;
			$obj->url                            = 'https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO;
			$obj->package                        = $release['zip_url'];
			$transient->response[ $plugin_file ] = $obj;
		}
		return $transient;
	}

	public static function github_plugin_api( $res, $action, $args ) {
		if ( ! isset( $args->slug ) || 'umami-wp-connect' !== $args->slug ) {
			return $res;
		}
		$release = self::get_latest_github_release();
		if ( ! $release ) {
			return $res;
		}
		$res                = new stdClass();
		$res->name          = 'umami Connect';
		$res->slug          = 'umami-wp-connect';
		$res->version       = ltrim( $release['version'], 'v' );
		$res->author        = '<a href="https://github.com/' . esc_attr( UMAMI_CONNECT_GITHUB_USER ) . '">' . esc_html( UMAMI_CONNECT_GITHUB_USER ) . '</a>';
		$res->homepage      = 'https://github.com/' . esc_attr( UMAMI_CONNECT_GITHUB_USER ) . '/' . esc_attr( UMAMI_CONNECT_GITHUB_REPO ) . '/';
		$res->download_link = $release['zip_url'];
		$changelog          = '';
		$home_url           = get_home_url();
		$is_dev             = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || strpos( $home_url, '.local' ) !== false || strpos( $home_url, 'localhost' ) !== false;
		if ( $is_dev ) {
			$changelog_file = ABSPATH . 'wp-content/plugins/umami-wp-connect/CHANGELOG.md';
			if ( file_exists( $changelog_file ) ) {
				$changelog_raw  = file_get_contents( $changelog_file );
			} else {
				$changelog_raw = '';
			}
		} else {
			$changelog_url = 'https://raw.githubusercontent.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/main/CHANGELOG.md';
			$changelog_raw = @file_get_contents( $changelog_url );
		}
		if ( $changelog_raw ) {
			$changelog_html = preg_replace( '/^\*\*v?([0-9]+\.[0-9]+\.[0-9]+)\*\*/m', '<strong>v$1</strong>', $changelog_raw );
			$changelog_html = preg_replace( '/^##\s*v?([0-9]+\.[0-9]+\.[0-9]+)/m', '<strong>v$1</strong>', $changelog_html );
			$changelog_html = preg_replace_callback(
				'/(^|\n)(- .+?)(?=(\n[^-]|$))/s',
				function ( $matches ) {
					$items = preg_split( '/\n/', trim( $matches[2] ) );
					$lis   = '';
					foreach ( $items as $item ) {
						if ( preg_match( '/^- (.+)/', $item, $m ) ) {
							$lis .= '<li>' . htmlspecialchars( $m[1] ) . '</li>';
						}
					}
					return "<ul>$lis</ul>";
				},
				$changelog_html
			);
			$changelog = $changelog_html;
		} else {
			$changelog = 'See <a href="https://github.com/' . esc_attr( UMAMI_CONNECT_GITHUB_USER ) . '/' . esc_attr( UMAMI_CONNECT_GITHUB_REPO ) . '/releases">GitHub Releases</a>.';
		}

		$readme_url = 'https://raw.githubusercontent.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/main/README.md';
		$readme_raw = @file_get_contents( $readme_url );
		if ( $readme_raw ) {
			$readme_lines = explode( "\n", $readme_raw );
			array_shift( $readme_lines );
			$readme_raw_no_title = implode( "\n", $readme_lines );

			$readme_raw_no_title = preg_replace( '/## Installation[\s\S]*/', '', $readme_raw_no_title );

			$readme_clean = preg_replace( '/!\[[^\]]*\]\([^)]*\)/', '', $readme_raw_no_title );
			$readme_clean = preg_replace( '/<img[^>]*>/', '', $readme_clean );
			$readme_clean = preg_replace( '/\[!\[[^\]]*\]\([^)]*\)\][^)]*\)/', '', $readme_clean );
			$readme_clean = preg_replace( '/\[\]\([^)]*\)/', '', $readme_clean );
			$readme_clean = preg_replace( '/<div[^>]*>\s*<\/div>/', '', $readme_clean );
			$readme_clean = preg_replace( '/<table[^>]*>\s*<tr>\s*(<td[^>]*><\/td>\s*)+<\/tr>\s*<\/table>/', '', $readme_clean );

			$readme_clean = preg_replace( '/\n{3,}/', "\n\n", $readme_clean );

			$readme_clean = preg_replace( '/^# (.*)$/m', '<h1>$1</h1>', $readme_clean );
			$readme_clean = preg_replace( '/^## (.*)$/m', '<h2>$1</h2>', $readme_clean );
			$readme_clean = preg_replace( '/^### (.*)$/m', '<h3>$1</h3>', $readme_clean );
			$readme_clean = preg_replace( '/^#### (.*)$/m', '<h4>$1</h4>', $readme_clean );
			$readme_clean = preg_replace( '/^##### (.*)$/m', '<h5>$1</h5>', $readme_clean );
			$readme_clean = preg_replace( '/^###### (.*)$/m', '<h6>$1</h6>', $readme_clean );

			$readme_clean = preg_replace( '/^---$/m', '<hr>', $readme_clean );
			$readme_clean = preg_replace( '/\*\*(.*?)\*\*/', '<strong>$1</strong>', $readme_clean );
			$readme_clean = preg_replace( '/\*(.*?)\*/', '<i>$1</i>', $readme_clean );

			$readme_clean = preg_replace( '/^# (.*)$/m', '<h1>$1</h1>', $readme_clean );
			$readme_clean = preg_replace( '/^## (.*)$/m', '<h2>$1</h2>', $readme_clean );
			$readme_clean = preg_replace( '/^### (.*)$/m', '<h3>$1</h3>', $readme_clean );
			$readme_clean = preg_replace( '/^#### (.*)$/m', '<h4>$1</h4>', $readme_clean );
			$readme_clean = preg_replace( '/^##### (.*)$/m', '<h5>$1</h5>', $readme_clean );
			$readme_clean = preg_replace( '/^###### (.*)$/m', '<h6>$1</h6>', $readme_clean );

			$readme_clean = preg_replace( '/^>\s?(.*)$/m', '<blockquote>$1</blockquote>', $readme_clean );

			$readme_clean = preg_replace( '/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" target="_blank">$1</a>', $readme_clean );

			$readme_clean = preg_replace( '/^\s*\- (.*)$/m', '<li>$1</li>', $readme_clean );
			$readme_clean = preg_replace( '/^\s*\d+\. (.*)$/m', '<li>$1</li>', $readme_clean );

			$readme_clean = preg_replace( '/\n{2,}/', '</p><p>', $readme_clean );

			$readme_clean = preg_replace( '/(<li>.*?<\/li>)+/', '<ul>$0</ul>', $readme_clean );

			$readme_clean = str_replace( '<br>', '', $readme_clean );
			$readme_clean = str_replace( '<br/>', '', $readme_clean );
			$readme_clean = str_replace( '<br />', '', $readme_clean );

			$readme_clean = '<p>' . $readme_clean . '</p>';
			$readme_clean = preg_replace( '/<p><\/p>/', '', $readme_clean );
			$description  = $readme_clean;

		} else {
			$description = 'Simple integration of Umami Analytics in WordPress for Cloud and Self-hosted.';
		}
		$res->sections = array(
			'description' => $description,
			'changelog'   => $changelog,
		);
		return $res;
	}

	public static function get_latest_github_release() {
		$transient_key = 'umami_connect_github_release';
		$cached        = get_transient( $transient_key );
		if ( $cached !== false ) {
			return $cached;
		}

		$api_url  = 'https://api.github.com/repos/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/releases/latest';
		$response = wp_remote_get(
			$api_url,
			array(
				'headers' => array( 'User-Agent' => 'WordPress/Umami-Connect' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['tag_name'] ) || empty( $data['assets'] ) ) {
			return false;
		}
		$zip_url = '';
		foreach ( $data['assets'] as $asset ) {
			if (
				strpos( $asset['name'], 'umami-wp-connect-' ) === 0 &&
				substr( $asset['name'], -4 ) === '.zip'
			) {
				$zip_url = $asset['browser_download_url'];
				break;
			}
		}
		if ( ! $zip_url ) {
			return false;
		}
		$result = array(
			'version' => $data['tag_name'],
			'zip_url' => $zip_url,
		);
		set_transient( $transient_key, $result, 15 * MINUTE_IN_SECONDS );
		return $result;
	}
}

add_action(
	'upgrader_process_complete',
	function ( $upgrader, $options ) {
		if (
		isset( $options['action'], $options['type'] ) &&
		$options['action'] === 'update' &&
		$options['type'] === 'plugin' &&
		! empty( $options['plugins'] ) &&
		in_array( 'umami-wp-connect/umami-connect.php', (array) $options['plugins'], true )
		) {
			if ( class_exists( 'Umami_Connect_Github' ) && method_exists( 'Umami_Connect_Github', 'clear_github_release_cache' ) ) {
				Umami_Connect_Github::clear_github_release_cache();
			}
		}
	},
	10,
	2
);
