<?php
// Custom admin footer for all umami Connect pages.
add_filter(
	'admin_footer_text',
	function ( $footer_text ) {
		$screen = get_current_screen();
		if ( empty( $screen ) || ( strpos( $screen->base, 'umami' ) === false ) ) {
			return $footer_text;
		}
		$github = 'https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO;
		return 'Enjoying umami Connect? Show your support with a ⭐ on <a href="' . esc_url( $github ) . '" target="_blank" rel="noopener">GitHub</a>!';
	}
);

add_filter(
	'update_footer',
	function ( $text ) {
		$screen = get_current_screen();
		if ( empty( $screen ) || ( strpos( $screen->base, 'umami' ) === false ) ) {
			return $text;
		}
		if ( ! function_exists( 'umami_connect_get_plugin_version' ) ) {
			require_once __DIR__ . '/../core/version_check.php';
		}
		$version = function_exists( 'umami_connect_get_plugin_version' ) ? umami_connect_get_plugin_version() : '';
		return $version ? 'umami Connect v' . esc_html( $version ) : '';
	}
);
