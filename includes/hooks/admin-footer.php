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
