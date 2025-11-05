<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'enqueue_block_editor_assets',
	function () {
		wp_enqueue_script(
			'umami-extend-core-button',
			plugins_url( 'assets/editor/umami-extend.js', UMAMI_CONNECT_PLUGIN_FILE ),
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-compose', 'wp-hooks', 'wp-rich-text', 'wp-data' ),
			'1.1.1',
			true
		);
	}
);
