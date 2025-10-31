<?php
/**
 * Plugin Name: umami Connect
 * Description: Simple integration of Umami Analytics in WordPress for  Cloud and Self-hosted.
 * Version: v0.0.0
 * Author: ceviixx
 * Author URI: https://ceviixx.github.io/
 * Plugin URI: https://github.com/ceviixx/umami-wp-connect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/constants.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/version_check.php';

require_once plugin_dir_path( __FILE__ ) . 'includes/menu.php';

// Pages.
require_once plugin_dir_path( __FILE__ ) . 'includes/pages/general.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/pages/self_protection.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/pages/automation.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/pages/update.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/pages/events_overview.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/pages/advanced.php';

// Filters.
require_once plugin_dir_path( __FILE__ ) . 'includes/add_filter/plugin_action_links.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/add_filter/render_block.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/add_filter/autotrack.php';

// Dashboard.
require_once plugin_dir_path( __FILE__ ) . 'includes/dashboard/status_widget.php';

// Non excluded functions.

add_action(
	'admin_init',
	function () {
		// Advanced tracker configuration options.
		register_setting(
			'umami_connect_advanced',
			'umami_tracker_host_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $value ) {
					$value = trim( (string) $value );
					if ( $value === '' ) {
						return '';
					}
					if ( ! preg_match( '~^https?://~i', $value ) ) {
						$value = 'https://' . $value;
					}
					return esc_url_raw( rtrim( $value, '/' ) );
				},
				'default'           => '',
			)
		);
		register_setting(
			'umami_connect_advanced',
			'umami_disable_auto_track',
			array(
				'type'              => 'string',
				'sanitize_callback' => fn( $v ) => $v ? '1' : '0',
				'default'           => '0',
			)
		);
		register_setting(
			'umami_connect_advanced',
			'umami_tracker_domains',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $value ) {
					$value = trim( (string) $value );
					$value = preg_replace( '/\s+/', '', $value );
					return $value;
				},
				'default'           => '',
			)
		);
		register_setting(
			'umami_connect_advanced',
			'umami_tracker_tag',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $value ) {
					return sanitize_text_field( $value );
				},
				'default'           => '',
			)
		);
		register_setting(
			'umami_connect_advanced',
			'umami_tracker_exclude_search',
			array(
				'type'              => 'string',
				'sanitize_callback' => fn( $v ) => $v ? '1' : '0',
				'default'           => '0',
			)
		);
		register_setting(
			'umami_connect_advanced',
			'umami_tracker_exclude_hash',
			array(
				'type'              => 'string',
				'sanitize_callback' => fn( $v ) => $v ? '1' : '0',
				'default'           => '0',
			)
		);
		register_setting(
			'umami_connect_advanced',
			'umami_tracker_do_not_track',
			array(
				'type'              => 'string',
				'sanitize_callback' => fn( $v ) => $v ? '1' : '0',
				'default'           => '0',
			)
		);
		register_setting(
			'umami_connect_advanced',
			'umami_tracker_before_send_mode',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $value ) {
					return in_array( $value, array( 'disabled', 'function_name', 'inline' ), true ) ? $value : 'disabled';
				},
				'default'           => 'disabled',
			)
		);
		register_setting(
			'umami_connect_advanced',
			'umami_tracker_before_send',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $value ) {
					$value = trim( (string) $value );
					if ( $value === '' ) {
						return '';
					}
					return preg_match( '/^[A-Za-z_$][A-Za-z0-9_$.]*$/', $value ) ? $value : '';
				},
				'default'           => '',
			)
		);
		register_setting(
			'umami_connect_advanced',
			'umami_tracker_before_send_inline',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $value ) {
					// Basic JS code sanitization: strip script tags but allow function definition.
					$value = trim( (string) $value );
					if ( $value === '' ) {
						return '';
					}
					// Remove any <script> tags for safety.
					$value = preg_replace( '/<\/?script[^>]*>/i', '', $value );

					// Validate syntax: must start with "function".
					if ( ! preg_match( '/^function\s*\(/', $value ) ) {
						add_settings_error(
							'umami_tracker_before_send_inline',
							'invalid_function',
							'beforeSend inline code must start with "function(".',
							'error'
						);
						return get_option( 'umami_tracker_before_send_inline', '' );
					}

					return $value;
				},
				'default'           => '',
			)
		);
		register_setting(
			'umami_connect_general',
			'umami_script_loading',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $v ) {
					$v = strtolower( trim( (string) $v ) );
					return in_array( $v, array( 'async', 'defer' ), true ) ? $v : 'async';
				},
				'default'           => 'async',
			)
		);

		register_setting(
			'umami_connect_general',
			'umami_mode',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $v ) {
					$v = strtolower( trim( (string) $v ) );
					return in_array( $v, array( 'cloud', 'self' ), true ) ? $v : 'cloud';
				},
				'default'           => 'cloud',
			)
		);

		register_setting(
			'umami_connect_general',
			'umami_host',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $value ) {
					$mode  = get_option( 'umami_mode', 'cloud' );
					$value = trim( (string) $value );

					if ( $mode === 'self' && $value === '' ) {
						add_settings_error(
							'umami_host',
							'umami_host_required',
							'For self-hosted, a Host URL is required.',
							'error'
						);
						return get_option( 'umami_host', '' );
					}

					if ( $value !== '' ) {
						if ( ! preg_match( '~^https?://~i', $value ) ) {
							$value = 'https://' . $value;
						}
						$value = esc_url_raw( rtrim( $value, '/' ) );
					}
						return $value;
				},
				'default'           => UMAMI_CONNECT_DEFAULT_HOST,
			)
		);

		register_setting(
			'umami_connect_general',
			'umami_website_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $value ) {
					$value = trim( (string) $value );
					if ( $value === '' ) {
						return '';
					}
					if ( preg_match( '/^[a-f0-9-]{10,}$/i', $value ) ) {
						return $value;
					}
						return preg_replace( '/[^a-zA-Z0-9\-_]/', '', $value );
				},
				'default'           => '',
			)
		);

		register_setting(
			'umami_connect_automation',
			'umami_autotrack_links',
			array(
				'type'              => 'string',
				'sanitize_callback' => fn( $v ) => $v ? '1' : '0',
				'default'           => '1',
			)
		);

		register_setting(
			'umami_connect_automation',
			'umami_autotrack_buttons',
			array(
				'type'              => 'string',
				'sanitize_callback' => fn( $v ) => $v ? '1' : '0',
				'default'           => '1',
			)
		);

		register_setting(
			'umami_connect_automation',
			'umami_autotrack_forms',
			array(
				'type'              => 'string',
				'sanitize_callback' => fn( $v ) => $v ? '1' : '0',
				'default'           => '1',
			)
		);

		add_settings_section(
			'umami_connect_main',
			'',
			function () {
				echo '<p>Configure your Umami Analytics tracking settings. See Help (top right) for detailed information.</p>';
				if ( empty( get_option( 'permalink_structure' ) ) ) {
					echo '<div class="notice notice-warning" style="margin:16px 0 20px 0; padding:12px 16px 12px 12px; display:flex; align-items:center; gap:12px;">';
					echo '<span class="dashicons dashicons-warning" style="font-size:22px; color:#d48806;"></span>';
					echo '<div>';
					echo '<strong style="color:#d48806; font-size:15px;">Permalinks are not enabled</strong>';
					echo '<div style="font-size:12px; margin-top:2px;">Page views cannot be tracked correctly. <a href="options-permalink.php" style="text-decoration:underline;">Enable permalinks</a> for optimal tracking.</div>';
					echo '</div>';
					echo '</div>';
				} else {
					$structure = get_option( 'permalink_structure' );
					echo '<div class="notice notice-success" style="margin:16px 0 20px 0; padding:12px 16px 12px 12px; display:flex; align-items:center; gap:12px;">';
					echo '<span class="dashicons dashicons-yes" style="font-size:22px; color:#389e0d;"></span>';
					echo '<div>';
					echo '<strong style="color:#227a13; font-size:15px;">Permalinks are enabled</strong>';
					echo '<div style="color:#444; font-size:13px; margin:2px 0 0 0;">Current structure: <code style="font-size:13px;">' . esc_html( $structure ) . '</code></div>';
					echo '</div>';
					echo '</div>';
				}
			},
			'umami_connect'
		);

		add_settings_field(
			'umami_mode',
			'Mode',
			function () {
				$mode = esc_attr( get_option( 'umami_mode', 'cloud' ) );
				?>
			<select name="umami_mode" id="umami_mode">
						<option value="cloud" <?php selected( $mode, 'cloud' ); ?>>Cloud</option>
						<option value="self"  <?php selected( $mode, 'self' ); ?>>Self-hosted</option>
			</select>
				<?php
			},
			'umami_connect',
			'umami_connect_main'
		);

		add_settings_field(
			'umami_host',
			'Host URL',
			function () {
				$value = esc_attr( get_option( 'umami_host', UMAMI_CONNECT_DEFAULT_HOST ) );
				echo '<input required type="url" name="umami_host" id="umami_host" value="' . $value . '" class="regular-text" placeholder="https://umami.example.com">';
			},
			'umami_connect',
			'umami_connect_main'
		);

		add_settings_field(
			'umami_website_id',
			'Website ID',
			function () {
				$value = esc_attr( get_option( 'umami_website_id', '' ) );
				echo '<input required type="text" name="umami_website_id" id="umami_website_id" value="' . $value . '" class="regular-text" placeholder="Website UUID">';
				echo '<div id="umami-website-id-error" style="color:#b32d2e; margin-top:8px; display:none;"></div>';
			},
			'umami_connect',
			'umami_connect_main'
		);

		add_settings_field(
			'umami_script_loading',
			'Script loading',
			function () {
				$mode = esc_attr( get_option( 'umami_script_loading', 'defer' ) );
				?>
			<select name="umami_script_loading" id="umami_script_loading">
						<option value="defer" <?php selected( $mode, 'defer' ); ?>>defer</option>
						<option value="async" <?php selected( $mode, 'async' ); ?>>async</option>
			</select>
				<?php
			},
			'umami_connect',
			'umami_connect_main'
		);

		add_settings_section(
			'umami_connect_self_protection',
			'',
			function () {
				echo '<p>Exclude your own visits from analytics. See Help (top right) for details.</p>';
			},
			'umami_connect_self_protection'
		);

		add_settings_section(
			'umami_connect_automation',
			'',
			function () {
				echo '<p>Enable automatic event tracking for user interactions. See Help (top right) for details.</p>';
			},
			'umami_connect_automation'
		);

		add_settings_field(
			'umami_autotrack_links',
			'Auto-track links',
			function () {
				$v = get_option( 'umami_autotrack_links', '1' );
				echo '<label><input type="checkbox" name="umami_autotrack_links" value="1" ' . checked( $v, '1', false ) . '> Add default click events to <code>&lt;a&gt;</code> links</label>';
			},
			'umami_connect_automation',
			'umami_connect_automation'
		);

		add_settings_field(
			'umami_autotrack_buttons',
			'Auto-track buttons',
			function () {
				$v = get_option( 'umami_autotrack_buttons', '1' );
				echo '<label><input type="checkbox" name="umami_autotrack_buttons" value="1" ' . checked( $v, '1', false ) . '> Add default click events to Gutenberg Button blocks and native <code>&lt;button&gt;</code> elements</label>';
			},
			'umami_connect_automation',
			'umami_connect_automation'
		);

		add_settings_field(
			'umami_autotrack_forms',
			'Auto-track forms',
			function () {
				$v = get_option( 'umami_autotrack_forms', '1' );
				echo '<label><input type="checkbox" name="umami_autotrack_forms" value="1" ' . checked( $v, '1', false ) . '> Send a submit event on <code>&lt;form&gt;</code> submit</label>';
			},
			'umami_connect_automation',
			'umami_connect_automation'
		);

		add_settings_field(
			'umami_consent_required',
			'Require consent',
			function () {
				$v = get_option( 'umami_consent_required', '0' );
				echo '<label><input type="checkbox" name="umami_consent_required" value="1" ' . checked( $v, '1', false ) . '> Only run Umami after consent was granted</label>';
				echo '<p class="description">If enabled, tracking waits for the consent flag below to be truthy.</p>';
			},
			'umami_connect',
			'umami_connect_consent'
		);

		add_settings_field(
			'umami_consent_flag',
			'Consent flag (global)',
			function () {
				$v = esc_attr( get_option( 'umami_consent_flag', 'umamiConsentGranted' ) );
				echo '<input type="text" name="umami_consent_flag" value="' . $v . '" class="regular-text" placeholder="umamiConsentGranted">';
				echo '<p class="description">Example: <code>window.umamiConsentGranted = true</code> after your CMP grants consent.</p>';
			},
			'umami_connect',
			'umami_connect_consent'
		);

		add_settings_field(
			'umami_debug',
			'Verbose console logging',
			function () {
				$v = get_option( 'umami_debug', '0' );
				echo '<label><input type="checkbox" name="umami_debug" value="1" ' . checked( $v, '1', false ) . '> Enable debug logs in the browser console</label>';
			},
			'umami_connect',
			'umami_connect_debug'
		);

		register_setting(
			'umami_connect_self',
			'umami_exclude_logged_in',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $value ) {
					return $value ? '1' : '0';
				},
				'default'           => '1',
			)
		);

		add_settings_field(
			'umami_exclude_logged_in',
			'Do not track my own visits',
			function () {
				$value = get_option( 'umami_exclude_logged_in', '1' );
				?>
			<label>
						<input type="checkbox" name="umami_exclude_logged_in" id="umami_exclude_logged_in" value="1" <?php checked( $value, '1' ); ?>>
				Automatically exclude logged-in users from Umami
			</label>
				<?php
			},
			'umami_connect_self_protection',
			'umami_connect_self_protection'
		);
	}
);



register_activation_hook(
	__FILE__,
	function () {
		if ( get_option( 'umami_mode', '' ) === '' ) {
			add_option( 'umami_mode', 'cloud' );
		}
		if ( get_option( 'umami_host', '' ) === '' ) {
			add_option( 'umami_host', UMAMI_CONNECT_DEFAULT_HOST );
		}
		add_option( 'umami_website_id', get_option( 'umami_website_id', '' ) );

		if ( get_option( 'umami_exclude_logged_in', '' ) === '' ) {
			add_option( 'umami_exclude_logged_in', '1' );
		}
	}
);






add_action(
	'wp_head',
	function () {
		if ( is_admin() ) {
			return;
		}
		if ( ! isset( $_GET['umami_check_before_send'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check
			return;
		}

		echo "\n<script>\n";
		echo "(function(){\n";
		echo "try {\n";
		echo "  var usp = new URLSearchParams(window.location.search);\n";
		echo "  var path = usp.get('path') || '';\n";
		echo "  var token = usp.get('token') || '';\n";
		echo "  function resolve(p){\n";
		echo "    if(!p){return undefined;}\n";
		echo "    var obj = window;\n";
		echo "    var parts = p.split('.');\n";
		echo "    for(var i=0;i<parts.length;i++){\n";
		echo "      var s = parts[i];\n";
		echo "      if(!s){return undefined;}\n";
		echo "      try { obj = obj[s]; } catch(e){ return undefined; }\n";
		echo "      if(typeof obj === 'undefined'){ return undefined; }\n";
		echo "    }\n";
		echo "    return obj;\n";
		echo "  }\n";
		echo "  var ref = resolve(path);\n";
		echo "  var exists = typeof ref !== 'undefined';\n";
		echo "  var isFunction = typeof ref === 'function';\n";
		echo "  if (window.parent) {\n";
		echo "    window.parent.postMessage({ type:'umami-before-send-check', path: path, exists: exists, isFunction: isFunction, token: token }, window.location.origin);\n";
		echo "  }\n";
		echo "} catch(e){}\n";
		echo "})();\n";
		echo "</script>\n";
	},
	1
);

add_action(
	'wp_head',
	function () {
		if ( is_admin() ) {
			return;
		}
		$mode       = get_option( 'umami_mode', 'cloud' );
		$host_input = get_option( 'umami_host', UMAMI_CONNECT_DEFAULT_HOST );
		$website_id = get_option( 'umami_website_id', '' );

		if ( ! $website_id ) {
			return;
		}

		$base_host = ( $mode === 'self' )
		? ( $host_input ? $host_input : '' )
		: UMAMI_CONNECT_DEFAULT_HOST;

		if ( ! $base_host ) {
			return;
		}

		$script_url = $base_host;
		if ( ! preg_match( '~\.js(\?.*)?$~i', $script_url ) ) {
			$script_url = rtrim( $script_url, '/' ) . '/script.js';
		}

		$script_loading = get_option( 'umami_script_loading', 'async' );
		$attr           = ( $script_loading === 'defer' ) ? 'defer' : 'async';
		$attrs          = array();
		$attrs[]        = $attr;
		$attrs[]        = 'src="' . esc_url( $script_url ) . '"';
		$attrs[]        = 'data-website-id="' . esc_attr( $website_id ) . '"';

		$host_override = get_option( 'umami_tracker_host_url', '' );
		if ( ! empty( $host_override ) ) {
			$attrs[] = 'data-host-url="' . esc_url( $host_override ) . '"';
		}
		if ( get_option( 'umami_disable_auto_track', '0' ) === '1' ) {
			$attrs[] = 'data-auto-track="false"';
		}
		$domains = get_option( 'umami_tracker_domains', '' );
		if ( $domains !== '' ) {
			$attrs[] = 'data-domains="' . esc_attr( $domains ) . '"';
		}
		$tag = get_option( 'umami_tracker_tag', '' );
		if ( $tag !== '' ) {
			$attrs[] = 'data-tag="' . esc_attr( $tag ) . '"';
		}
		if ( get_option( 'umami_tracker_exclude_search', '0' ) === '1' ) {
			$attrs[] = 'data-exclude-search="true"';
		}
		if ( get_option( 'umami_tracker_exclude_hash', '0' ) === '1' ) {
			$attrs[] = 'data-exclude-hash="true"';
		}
		if ( get_option( 'umami_tracker_do_not_track', '0' ) === '1' ) {
			$attrs[] = 'data-do-not-track="true"';
		}

		$before_send_mode = get_option( 'umami_tracker_before_send_mode', 'disabled' );
		if ( $before_send_mode === 'inline' ) {
			$inline_code = get_option( 'umami_tracker_before_send_inline', '' );
			if ( $inline_code !== '' ) {
				echo "\n<script>\n";
				echo 'window.__umamiBeforeSend = (' . $inline_code . ");\n";
				echo "</script>\n";
				$attrs[] = 'data-before-send="__umamiBeforeSend"';
			}
		} elseif ( $before_send_mode === 'function_name' ) {
			$before_send = get_option( 'umami_tracker_before_send', '' );
			if ( $before_send !== '' ) {
				$attrs[] = 'data-before-send="' . esc_attr( $before_send ) . '"';
			}
		}
		echo "\n" . '<script ' . implode( ' ', $attrs ) . '></script>' . "\n";
	},
	20
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_script(
			'umami-autotrack',
			plugins_url( 'assets/umami-autotrack.js', __FILE__ ),
			array(),
			'1.2.0',
			true
		);

		$cfg = array(
			'autotrackLinks'   => get_option( 'umami_autotrack_links', '1' ) === '1',
			'autotrackButtons' => get_option( 'umami_autotrack_buttons', '1' ) === '1',
			'autotrackForms'   => get_option( 'umami_autotrack_forms', '1' ) === '1',
			'consentRequired'  => get_option( 'umami_consent_required', '0' ) === '1',
			'consentFlag'      => (string) get_option( 'umami_consent_flag', 'umamiConsentGranted' ),
			'debug'            => get_option( 'umami_debug', '0' ) === '1',
		);
		wp_add_inline_script( 'umami-autotrack', 'window.__UMAMI_CONNECT__ = ' . wp_json_encode( $cfg ) . ';', 'before' );
	},
	30
);

add_action(
	'wp_head',
	function () {
		if ( is_admin() ) {
			return;
		}
		$exclude = get_option( 'umami_exclude_logged_in', '1' ) === '1';
		if ( $exclude && is_user_logged_in() ) {
			echo "<script>try{localStorage.setItem('umami.disabled','true');}catch(e){}</script>\n";
		} elseif ( ! $exclude && is_user_logged_in() ) {
			echo "<script>try{localStorage.removeItem('umami.disabled');}catch(e){}</script>\n";
		}
	},
	5
);

add_action(
	'enqueue_block_editor_assets',
	function () {
		wp_enqueue_script(
			'umami-extend-core-button',
			plugins_url( 'assets/editor/umami-extend.js', __FILE__ ),
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-compose', 'wp-hooks', 'wp-rich-text', 'wp-data' ),
			'1.1.1',
			true
		);

		wp_enqueue_style(
			'umami-extend-editor-style',
			plugins_url( 'assets/editor/umami-extend.css', __FILE__ ),
			array( 'wp-components' ),
			'1.0.0'
		);
	}
);



