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
		printf(
			// translators: %1$s: <span class="umami-star">★</span>, %2$s: <a href="https://github.com/ceviixx/umami-wp-connect" target="_blank" rel="noopener noreferrer">GitHub</a>
			esc_html__( 'Enjoying umami Connect? Show your support with a %1$s on %2$s!', 'umami-connect' ),
			'<span class="umami-star">★</span>',
			'<a href="' . esc_url( $github ) . '" target="_blank" rel="noopener noreferrer">GitHub</a>'
		);
	}
);
