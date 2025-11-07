<?php
/**
 * Example Integration - Admin Settings (Optional)
 *
 * Add a small settings section in the target plugin/admin to configure
 * event name and custom key/value pairs (stored as JSON or meta).
 *
 * @package UmamiConnect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Guard: only if dependency is present.
if ( ! function_exists( 'my_dependency_function' ) ) {
	return;
}

// Example constants for meta/options.
if ( ! defined( 'UMAMI_MYINTEGRATION_EVENT_NAME' ) ) {
	define( 'UMAMI_MYINTEGRATION_EVENT_NAME', '_umami_myintegration_event_name' );
}
if ( ! defined( 'UMAMI_MYINTEGRATION_EVENT_DATA' ) ) {
	define( 'UMAMI_MYINTEGRATION_EVENT_DATA', '_umami_myintegration_event_data' );
}

// TODO: Register UI (settings field, meta box, or builder panel) where users
// can set the event name and optional JSON pairs. Persist them to the constants
// above. Keep everything translatable and capability-checked.
