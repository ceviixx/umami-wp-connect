<?php
/**
 * WPForms Integration - Hooks (procedural)
 *
 * Server-side injection of Umami tracking attributes into WPForms submit buttons
 * based on per-form settings configured in the WPForms builder.
 *
 * @package UmamiConnect
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Frontend only and only if WPForms is available.
if ( is_admin() || ! function_exists( 'wpforms' ) ) {
    return;
}

/**
 * Inject Umami attributes into WPForms submit buttons by scanning post content.
 *
 * Strategy:
 * - Detect WPForms form IDs in the rendered content (e.g., wpforms-form-123).
 * - Load form settings via WPForms API (wpforms()->form->get( $id )).
 * - Read settings[umami_event_name] and settings[umami_event_data] (JSON string).
 * - Build data-umami-* attributes and append them to the matching submit element with id wpforms-submit-<id>.
 * - Skip if the submit already has data-umami-event to avoid duplicates.
 *
 * Runs before the generic autotrack so our explicit attributes take precedence.
 *
 * @param string $content The post content.
 * @return string Modified content.
 */
function umami_wpforms_inject_submit_attributes_in_content( $content ) {
    if ( ! is_string( $content ) || $content === '' ) {
        return $content;
    }

    // Quick check to avoid unnecessary work.
    if ( strpos( $content, 'wpforms-form-' ) === false && strpos( $content, 'wpforms-submit-' ) === false ) {
        return $content;
    }

    // Find unique WPForms form IDs referenced in the content.
    $form_ids = array();
    if ( preg_match_all( '/wpforms-form-(\d+)/', $content, $matches ) ) {
        foreach ( $matches[1] as $idstr ) {
            $form_ids[ (int) $idstr ] = true;
        }
    }
    if ( empty( $form_ids ) && preg_match_all( '/wpforms-submit-(\d+)/', $content, $matches2 ) ) {
        foreach ( $matches2[1] as $idstr ) {
            $form_ids[ (int) $idstr ] = true;
        }
    }

    if ( empty( $form_ids ) ) {
        return $content;
    }

    foreach ( array_keys( $form_ids ) as $form_id ) {
        if ( ! $form_id ) {
            continue;
        }

        // Load full form data to access settings.
        $form     = wpforms()->form->get( $form_id );
        $settings = array();

        // WPForms may return an array with settings, or an object with post_content to decode.
        if ( is_array( $form ) ) {
            if ( isset( $form['settings'] ) && is_array( $form['settings'] ) ) {
                $settings = $form['settings'];
            } elseif ( isset( $form['post_content'] ) && is_string( $form['post_content'] ) ) {
                if ( function_exists( 'wpforms_decode' ) ) {
                    $decoded = wpforms_decode( $form['post_content'] );
                    if ( is_array( $decoded ) && isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ) {
                        $settings = $decoded['settings'];
                    }
                }
            }
        } elseif ( is_object( $form ) ) {
            if ( isset( $form->post_content ) && is_string( $form->post_content ) && function_exists( 'wpforms_decode' ) ) {
                $decoded = wpforms_decode( $form->post_content );
                if ( is_array( $decoded ) && isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ) {
                    $settings = $decoded['settings'];
                }
            } elseif ( isset( $form->settings ) && is_array( $form->settings ) ) {
                // Fallback in case settings are directly present.
                $settings = $form->settings;
            }
        }

        if ( empty( $settings ) ) {
            continue;
        }

        $event_name = isset( $settings['umami_event_name'] ) ? trim( (string) $settings['umami_event_name'] ) : '';
        if ( $event_name === '' ) {
            continue;
        }

        // Build data attributes string.
        $data_attrs = ' data-umami-event="' . esc_attr( $event_name ) . '"';

        $pairs_raw = isset( $settings['umami_event_data'] ) ? $settings['umami_event_data'] : '';
        if ( is_string( $pairs_raw ) && $pairs_raw !== '' ) {
            $decoded = json_decode( $pairs_raw, true );
            if ( is_array( $decoded ) ) {
                foreach ( $decoded as $k => $v ) {
                    $key = sanitize_key( (string) $k );
                    if ( $key === '' ) {
                        continue;
                    }
                    $data_attrs .= ' data-umami-event-' . $key . '="' . esc_attr( (string) $v ) . '"';
                }
            }
        }

        // Inject attributes into button[type="submit"] and input[type="submit"] with wpforms-submit-<id> id.
        $content = preg_replace(
            '/<(button|input)([^>]*\bid\s*=\s*["\']wpforms-submit-' . $form_id . '["\'][^>]*)(>)/i',
            '<$1$2' . $data_attrs . '$3',
            $content
        );
    }

    return $content;
}
add_filter( 'the_content', 'umami_wpforms_inject_submit_attributes_in_content', 19 );

