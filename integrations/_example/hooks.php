<?php
/**
 * Example Integration - Hooks
 *
 * Implement the runtime behavior here (inject attributes, listen to actions,
 * or fire JS events). Keep everything guarded and fail-safe.
 *
 * @package UmamiConnect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Guard: only run when your dependency is present.
if ( ! function_exists( 'my_dependency_function' ) ) {
	return;
}

/**
 * Example: inject data attributes into a submit button in rendered content.
 *
 * @param string $content Post content.
 * @return string
 */
function umami_myintegration_inject_submit_attrs( $content ) {
	if ( is_admin() || ! is_string( $content ) || $content === '' ) {
		return $content;
	}

	// Bail fast if no marker is present (adjust to your output):
	if ( strpos( $content, 'myintegration-submit-' ) === false ) {
		return $content;
	}

	// Example: only inject if event configured somewhere (meta/settings).
	$event_name = '';
	if ( $event_name === '' ) {
		return $content;
	}

	$data_attrs = ' data-umami-event="' . esc_attr( (string) $event_name ) . '"';

	// Optionally add custom key/value pairs as data-umami-event-keys
	// $data_attrs .= ' data-umami-event-foo="bar"';

	// Inject into a specific submit element (adjust selector/regex as needed).
	$content = preg_replace(
		'/(<button[^>]*\bid=\"myintegration-submit-\d+\"[^>]*)(>)/i',
		'$1' . $data_attrs . '$2',
		$content
	);

	return $content;
}
add_filter( 'the_content', 'umami_myintegration_inject_submit_attrs', 19 );
