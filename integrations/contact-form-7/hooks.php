<?php
/**
 * Contact Form 7 Integration - Hooks (procedural)
 *
 * Provides event tracking for successful submissions.
 *
 * @package UmamiConnect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Guard: run only if CF7 is present.
if ( ! class_exists( 'WPCF7' ) && ! function_exists( 'wpcf7' ) ) {
	return;
}

// Use constants from admin-settings if available.
if ( ! defined( 'UMAMI_CF7_META_EVENT_NAME' ) ) {
	define( 'UMAMI_CF7_META_EVENT_NAME', '_umami_cf7_custom_event' );
}
if ( ! defined( 'UMAMI_CF7_META_EVENT_DATA' ) ) {
	define( 'UMAMI_CF7_META_EVENT_DATA', '_umami_cf7_event_data' );
}

/**
 * Inject Umami element-tracking attributes into CF7 submit buttons.
 * Uses do_shortcode_tag filter to modify the shortcode output HTML directly.
 *
 * @param string $output Shortcode output.
 * @param string $tag    Shortcode tag.
 * @param array  $attr   Shortcode attributes.
 * @return string Modified output.
 */
function umami_cf7_inject_submit_attributes_shortcode( $output, $tag, $attr ) {
	// Only process contact-form-7 shortcode.
	if ( 'contact-form-7' !== $tag ) {
		return $output;
	}

	// Extract form ID from attributes.
	$form_id_raw = isset( $attr['id'] ) ? $attr['id'] : 0;
	$form_id     = 0;

	// CF7 uses the actual post ID, not the shortcode hex ID.
	// Try to get the form by title first if we can't parse the ID.
	if ( function_exists( 'wpcf7_contact_form' ) ) {
		// Try numeric ID first.
		if ( is_numeric( $form_id_raw ) ) {
			$form_id  = (int) $form_id_raw;
			$cf7_form = wpcf7_contact_form( $form_id );
		} else {
			// If not numeric, search by title.
			$cf7_form = null;
			if ( isset( $attr['title'] ) ) {
				$forms = get_posts(
					array(
						'post_type'      => 'wpcf7_contact_form',
						'title'          => $attr['title'],
						'posts_per_page' => 1,
					)
				);
				if ( ! empty( $forms ) ) {
					$form_id  = $forms[0]->ID;
					$cf7_form = wpcf7_contact_form( $form_id );
				}
			}
		}       if ( $cf7_form && method_exists( $cf7_form, 'id' ) ) {
			$form_id = (int) $cf7_form->id();
		}
	}

	if ( ! $form_id ) {
		return $output;
	}

	$event_name = get_post_meta( $form_id, UMAMI_CF7_META_EVENT_NAME, true );
	if ( empty( $event_name ) ) {
		return $output;
	}

	// Build data attributes string.
	$data_attrs = ' data-umami-event="' . esc_attr( (string) $event_name ) . '"';

	$event_data_raw = get_post_meta( $form_id, UMAMI_CF7_META_EVENT_DATA, true );
	if ( is_string( $event_data_raw ) && $event_data_raw !== '' ) {
		$decoded = json_decode( $event_data_raw, true );
		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $k => $v ) {
				$key = sanitize_key( (string) $k );
				if ( '' === $key ) {
					continue;
				}
				$data_attrs .= ' data-umami-event-' . $key . '="' . esc_attr( (string) $v ) . '"';
			}
		}
	}

	// Inject attributes into input[type="submit"] tags.
	$output = preg_replace(
		'/(<input\s+[^>]*type\s*=\s*["\']submit["\'][^>]*)(>)/i',
		'$1' . $data_attrs . '$2',
		$output
	);

	// Inject attributes into button[type="submit"] tags.
	$output = preg_replace(
		'/(<button\s+[^>]*type\s*=\s*["\']submit["\'][^>]*)(>)/i',
		'$1' . $data_attrs . '$2',
		$output
	);

	return $output;
}
add_filter( 'do_shortcode_tag', 'umami_cf7_inject_submit_attributes_shortcode', 10, 3 );
