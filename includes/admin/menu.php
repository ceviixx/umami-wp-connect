<?php
add_action(
	'admin_menu',
	function () {

		$general_page = add_menu_page(
			'umami Connect',
			'umami Connect',
			'manage_options',
			'umami_connect_welcome',
			'umami_connect_welcome_page',
			'dashicons-chart-area',
			90
		);

		$welcome_page = add_submenu_page(
			'umami_connect_welcome',
			esc_html__( 'Welcome', 'umami-connect' ),
			esc_html__( 'Welcome', 'umami-connect' ),
			'manage_options',
			'umami_connect_welcome',
			'umami_connect_welcome_page'
		);

		$general_submenu_page = add_submenu_page(
			'umami_connect_welcome',
			esc_html__( 'General', 'umami-connect' ),
			esc_html__( 'General', 'umami-connect' ),
			'manage_options',
			'umami_connect',
			'umami_connect_settings_page'
		);
		add_action( "load-{$general_submenu_page}", 'umami_connect_add_help' );

		$self_protection_page = add_submenu_page(
			'umami_connect_welcome',
			esc_html__( 'Self protection', 'umami-connect' ),
			esc_html__( 'Self protection', 'umami-connect' ),
			'manage_options',
			'umami_connect_self_protection',
			'umami_connect_self_protection_page',
		);
		add_action( "load-{$self_protection_page}", 'umami_connect_add_help_self_protection' );

		$automation_page = add_submenu_page(
			'umami_connect_welcome',
			esc_html__( 'Automation', 'umami-connect' ),
			esc_html__( 'Automation', 'umami-connect' ),
			'manage_options',
			'umami_connect_automation',
			'umami_connect_automation_page'
		);
		add_action( "load-{$automation_page}", 'umami_connect_add_help_automation' );

		$events_overview_page = add_submenu_page(
			'umami_connect_welcome',
			esc_html__( 'Events overview', 'umami-connect' ),
			esc_html__( 'Events overview', 'umami-connect' ),
			'manage_options',
			'umami_connect_events_overview',
			'umami_connect_render_events_overview_page'
		);
		add_action( "load-{$events_overview_page}", 'umami_connect_add_help_events_overview' );
		add_action( "load-{$events_overview_page}", 'umami_connect_add_screen_options_events_overview' );

		$advanced_page = add_submenu_page(
			'umami_connect_welcome',
			esc_html__( 'Advanced', 'umami-connect' ),
			esc_html__( 'Advanced', 'umami-connect' ),
			'manage_options',
			'umami_connect_advanced',
			'umami_connect_advanced_page'
		);
		add_action( "load-{$advanced_page}", 'umami_connect_add_help_advanced' );
	}
);

// Backward compatibility: redirect old Update page slug to new one.
add_action(
	'admin_init',
	function () {
		if ( isset( $_GET['page'] ) && sanitize_key( wp_unslash( $_GET['page'] ) ) === 'umami_connect_support' ) {
			wp_safe_redirect( admin_url( 'admin.php?page=umami_connect_update' ), 301 );
			exit;
		}
	}
);

function umami_connect_add_help() {
	$screen = get_current_screen();

	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'setup'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$screen->set_help_sidebar(
		'<p><strong>' . esc_html__( 'Support & Resources', 'umami-connect' ) . '</strong></p>' .
		'<p><a href="https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '" target="_blank">GitHub</a></p>' .
		'<p><a href="' . UMAMI_CONNECT_DISCORD_INVITE . '" target="_blank">Discord</a></p>' .
		'<p><a href="https://umami.is/docs" target="_blank">' . esc_html__( 'Umami Documentation', 'umami-connect' ) . '</a></p>'
	);

	switch ( $tab ) {
		case 'setup':
			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_setup',
					'title'   => __( 'Setup', 'umami-connect' ),
					'content' => __(
						'<p><strong>Quick Setup Guide</strong></p>' .
						'<ol>' .
						'<li>Create an account at <a href="https://umami.is" target="_blank">umami.is</a> (Cloud) or set up your own Umami instance (Self-hosted).</li>' .
						'<li>Add your website in your Umami dashboard and copy the Website ID.</li>' .
						'<li>Paste the Website ID below and select your mode (Cloud or Self-hosted).</li>' .
						'<li>Save the settings - tracking will start automatically.</li>' .
						'</ol>',
						'umami-connect'
					),
				)
			);

			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_settings',
					'title'   => __( 'Settings', 'umami-connect' ),
					'content' => __(
						'<p><strong>Mode</strong><br>' .
						'Choose between Cloud (umami.is hosted service) or Self-hosted (your own Umami instance).</p>' .
						'<p><strong>Website ID</strong><br>' .
						'Your unique Umami Website ID in UUID format (e.g., 12345678-1234-1234-1234-123456789abc). Find this in your Umami dashboard under Settings → Websites.</p>' .
						'<p><strong>Host URL</strong><br>' .
						'Only required for self-hosted instances. Enter your Umami installation URL.<br>' .
						'<strong>Examples:</strong><br>' .
						'- Base URL: <code>https://analytics.yourdomain.com</code> (will use <code>/script.js</code>)<br>' .
						'- Custom path: <code>https://analytics.yourdomain.com/custom</code> or <code>https://analytics.yourdomain.com/custom.js</code> (will use exactly what you enter after the hostname, with or without <code>.js</code>)<br>' .
						'If you enter only the base URL, <code>/script.js</code> will be appended automatically. If you provide any path after the hostname, it will be used exactly as entered—regardless of whether it ends with <code>.js</code> or not.</p>' .
						'<p><strong>Script Loading</strong><br>' .
						'<strong>defer</strong>: Execute after HTML is parsed (recommended).<br>' .
						'<strong>async</strong>: Load as soon as possible (may execute before DOM is ready).</p>',
						'umami-connect'
					),
				)
			);
			break;
		case 'share-url':
			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_share_url_overview',
					'title'   => __( 'Overview', 'umami-connect' ),
					'content' => __(
						'<p><strong>Umami Share URL</strong></p>' .
						'<p>The Share URL is the public link to your Umami dashboard. Enter the URL generated by Umami ("Share Dashboard").</p>' .
						'<ul><li>Only shared dashboards can be embedded.</li><li>The URL must be publicly accessible and allowed for iFrame embedding.</li></ul>',
						'umami-connect'
					),
				)
			);
			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_share_url_settings',
					'title'   => __( 'Settings', 'umami-connect' ),
					'content' => __(
						'<p><strong>Share URL</strong><br>' .
						'Enter the full Share URL from your Umami dashboard (e.g. <code>https://cloud.umami.is/share/abc123</code>).<br>' .
						'Only this URL will be shown in the statistics menu. Leave empty to disable the feature.</p>' .
						'<p><strong>Reset or Change</strong><br>' .
						'To change the Share URL, you must first use the reset function. After resetting, you can enter a new URL in the settings.</p>' .
						'<p><strong>Requirements</strong><br>' .
						'- The URL must be a valid public Umami share link.<br>' .
						'- The dashboard must be shared and embeddable (no iFrame restrictions).</p>',
						'umami-connect'
					),
				)
			);
			break;
	}
}

function umami_connect_add_help_self_protection() {
	$screen = get_current_screen();

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_self_protection',
			'title'   => __( 'Overview', 'umami-connect' ),
			'content' => __(
				'<p><strong>Self Protection</strong></p>' .
				'<p>Prevent logged-in users (like admins, editors, or authors) from being tracked by Umami Analytics.</p>' .
				'<p>This helps keep your analytics data focused on actual visitors rather than your own site activity.</p>',
				'umami-connect'
			),
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_self_protection_settings',
			'title'   => __( 'Settings', 'umami-connect' ),
			'content' => __(
				'<p><strong>Do not track my own visits</strong><br>' .
				'When enabled, logged-in WordPress users will not be tracked. This works by setting <code>localStorage.umami.disabled = "true"</code> in the browser.</p>' .
				'<p>Disable this option if you want to measure admin/editor visits as well.</p>',
				'umami-connect'
			),
		)
	);

	$screen->set_help_sidebar(
		'<p><strong>' . esc_html__( 'Support & Resources', 'umami-connect' ) . '</strong></p>' .
		'<p><a href="https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '" target="_blank">GitHub</a></p>' .
		'<p><a href="' . UMAMI_CONNECT_DISCORD_INVITE . '" target="_blank">Discord</a></p>' .
		'<p><a href="https://umami.is/docs" target="_blank">' . esc_html__( 'Umami Documentation', 'umami-connect' ) . '</a></p>'
	);
}

function umami_connect_add_help_automation() {
	$screen = get_current_screen();

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_automation',
			'title'   => __( 'Overview', 'umami-connect' ),
			'content' => __(
				'<p><strong>Automation</strong></p>' .
				'<p>Automatically track user interactions like link clicks, button clicks, and form submissions without manual configuration.</p>' .
				'<p>Tracking attributes are injected server-side for supported elements. You can override them using <code>data-umami-event</code> attributes.</p>',
				'umami-connect'
			),
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_automation_settings',
			'title'   => __( 'Settings', 'umami-connect' ),
			'content' => __(
				'<p><strong>Auto-track links</strong><br>' .
				'Automatically adds click event tracking to all <code>&lt;a&gt;</code> links on your site.</p>' .
				'<p><strong>Auto-track buttons</strong><br>' .
				'Adds click event tracking to Gutenberg Button blocks and native <code>&lt;button&gt;</code> elements.</p>' .
				'<p><strong>Auto-track forms</strong><br>' .
				'Sends a submit event when forms are submitted on your site.</p>' .
				'<p><em>Note: Manually configured tracking in the editor will always be respected and not overridden.</em></p>',
				'umami-connect'
			),
		)
	);

	$screen->set_help_sidebar(
		'<p><strong>' . esc_html__( 'Support & Resources', 'umami-connect' ) . '</strong></p>' .
		'<p><a href="https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '" target="_blank">GitHub</a></p>' .
		'<p><a href="' . UMAMI_CONNECT_DISCORD_INVITE . '" target="_blank">Discord</a></p>' .
		'<p><a href="https://umami.is/docs" target="_blank">' . esc_html__( 'Umami Documentation', 'umami-connect' ) . '</a></p>'
	);
}

function umami_connect_add_help_events_overview() {
	$screen = get_current_screen();

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_events_overview',
			'title'   => __( 'Overview', 'umami-connect' ),
			'content' => __(
				'<p><strong>Events Overview</strong></p>' .
				'<p>This page provides a centralized overview of all Umami tracking events configured across your site.</p>' .
				'<p>You can view, search, edit, and manage events from various sources in one place.</p>',
				'umami-connect'
			),
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_events_creating',
			'title'   => __( 'Creating Events', 'umami-connect' ),
			'content' => __(
				'<p><strong>Creating Events</strong><br>' .
				'Events are created automatically when you configure tracking in supported integrations (e.g., Gutenberg blocks, Contact Form 7, WPForms).</p>' .
				'<p>Once created, events will appear in this overview for further management.</p>',
				'umami-connect'
			),
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_events_managing',
			'title'   => __( 'Managing Events', 'umami-connect' ),
			'content' => __(
				'<p><strong>Filter View</strong><br>' .
				'Use "All", "Events", or "Candidates" to filter the display:<br>' .
				'• <strong>Events:</strong> Configured tracking events with active event names<br>' .
				'• <strong>Candidates:</strong> Available tracking opportunities without event configuration</p>' .
				'<p><strong>Search & Edit</strong><br>' .
				'• Use the search box to find specific events by name or source<br>' .
				'• Click "Edit" to open the configuration interface for that event<br>' .
				'• Use "Delete" to remove event tracking while preserving the original content</p>',
				'umami-connect'
			),
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_events_best_practices',
			'title'   => __( 'Best Practices', 'umami-connect' ),
			'content' => __(
				'<p><strong>Event Naming</strong><br>' .
				'Use clear, descriptive event names and avoid special characters for better dashboard readability.</p>' .
				'<p><strong>Data Context</strong><br>' .
				'Add data pairs to capture important context like user type, content category, or interaction details.</p>' .
				'<p><strong>Testing & Maintenance</strong><br>' .
				'• Test events in your Umami dashboard after configuration<br>' .
				'• Regularly review and clean up unused event configurations<br>' .
				'• Configuration changes are reflected immediately in your tracking</p>',
				'umami-connect'
			),
		)
	);

	$screen->set_help_sidebar(
		'<p><strong>' . esc_html__( 'Support & Resources', 'umami-connect' ) . '</strong></p>' .
		'<p><a href="https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '" target="_blank">GitHub</a></p>' .
		'<p><a href="' . UMAMI_CONNECT_DISCORD_INVITE . '" target="_blank">Discord</a></p>' .
		'<p><a href="https://umami.is/docs" target="_blank">' . esc_html__( 'Umami Documentation', 'umami-connect' ) . '</a></p>'
	);
}

function umami_connect_add_help_advanced() {
	$screen = get_current_screen();

	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'host-url'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$screen->set_help_sidebar(
		'<p><strong>' . esc_html__( 'Support & Resources', 'umami-connect' ) . '</strong></p>' .
		'<p><a href="https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '" target="_blank">GitHub</a></p>' .
		'<p><a href="' . UMAMI_CONNECT_DISCORD_INVITE . '" target="_blank">Discord</a></p>' .
		'<p><a href="https://umami.is/docs/tracker-configuration" target="_blank">' . esc_html__( 'Tracker configuration', 'umami-connect' ) . '</a></p>'
	);

	switch ( $tab ) {
		case 'host-url':
			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_adv_host_overview',
					'title'   => __( 'Overview', 'umami-connect' ),
					'content' => __(
						'<p><strong>Host URL override</strong></p>' .
						'<p>Overrides the endpoint the tracker uses to send analytics events. Useful when:</p>' .
						'<ul><li>You load <code>script.js</code> from a CDN.</li><li>Your Umami collector runs on a different domain.</li></ul>' .
						'<p>Maps to <code>data-host-url</code>.</p>',
						'umami-connect'
					),
				)
			);
			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_adv_host_usage',
					'title'   => __( 'Usage', 'umami-connect' ),
					'content' => sprintf(
						// translators: %1$s: <code>https://analytics.example.com</code>
						__(
							'<p>Enter a full URL (e.g. %1$s). Leave empty to use the script\'s own host.</p>',
							'umami-connect'
						),
						'<code>https://analytics.example.com</code>'
					),
				)
			);
			break;
		case 'auto-track':
			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_adv_autotrack',
					'title'   => __( 'Overview', 'umami-connect' ),
					'content' => __(
						'<p><strong>Disable auto tracking</strong></p>' .
						'<p>Umami\'s tracker can automatically collect page views and clicks. Disable it if you want full manual control.</p>' .
						'<p>Maps to <code>data-auto-track="false"</code>.</p>' .
						'<p><em>Note:</em> The plugin\'s Automation features are separate and can still emit events.</p>',
						'umami-connect'
					),
				),
			);
			break;
		case 'domains':
			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_adv_domains',
					'title'   => __( 'Overview', 'umami-connect' ),
					'content' => __(
						'<p><strong>Allowed domains</strong></p>' .
						'<p>Restrict the tracker to specific hostnames. Comma-separated list, no spaces.</p>' .
						'<p>Example: <code>example.com,blog.example.com</code></p>' .
						'<p>Maps to <code>data-domains</code>.</p>',
						'umami-connect'
					),
				)
			);
			break;
		case 'tag':
			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_adv_tag',
					'title'   => __( 'Overview', 'umami-connect' ),
					'content' => __(
						'<p><strong>Event tag</strong></p>' .
						'<p>Add a tag to all events so you can filter them in reports. Maps to <code>data-tag</code>.</p>' .
						'<p>Example: <code>umami-eu</code></p>',
						'umami-connect'
					),
				)
			);
			break;
		case 'exclude-search':
			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_adv_exclude_search',
					'title'   => __( 'Overview', 'umami-connect' ),
					'content' => __(
						'<p><strong>Exclude search</strong></p>' .
						'<p>Ignores URL query parameters when collecting page views. Maps to <code>data-exclude-search</code>.</p>',
						'umami-connect'
					),
				)
			);
			break;
		case 'exclude-hash':
			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_adv_exclude_hash',
					'title'   => __( 'Overview', 'umami-connect' ),
					'content' => __(
						'<p><strong>Exclude hash</strong></p>' .
						'<p>Ignores the URL hash (fragment) when collecting page views. Maps to <code>data-exclude-hash</code>.</p>',
						'umami-connect'
					),
				)
			);
			break;
		case 'dnt':
			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_adv_dnt',
					'title'   => __( 'Overview', 'umami-connect' ),
					'content' => __(
						'<p><strong>Do Not Track</strong></p>' .
						'<p>Respects the user\'s browser DNT preference. Maps to <code>data-do-not-track</code>.</p>',
						'umami-connect'
					),
				)
			);
			break;
		case 'before-send':
		default:
			$screen->add_help_tab(
				array(
					'id'      => 'umami_help_adv_before_send',
					'title'   => __( 'Overview', 'umami-connect' ),
					'content' => __(
						'<p><strong>beforeSend</strong></p>' .
						'<p>Let you inspect or modify the payload before it is sent. Return the payload to continue or a false-y value to cancel sending.</p>' .
						'<p>Three modes:</p>' .
						'<ul>' .
						'<li><strong>Disabled</strong>: No beforeSend hook is active (default).</li>' .
						'<li><strong>Function name</strong>: Provide the global function path (e.g., <code>MyApp.handlers.beforeSend</code>). Use the "Check function" button to verify it is available on the public site.</li>' .
						'<li><strong>Inline</strong>: Define the function body directly. Use the "Test function" button to validate before saving.</li>' .
						'</ul>' .
						'<p>Maps to <code>data-before-send</code>.</p>',
						'umami-connect'
					),
				)
			);
			break;
	}
}

/**
 * Add screen options for Events Overview page
 */
function umami_connect_add_screen_options_events_overview() {
	$screen = get_current_screen();

	if ( ! $screen ) {
		return;
	}
	// Be robust to parent slug changes: only proceed for the Events Overview page regardless of parent.
	if ( ! preg_match( '/_page_umami_connect_events_overview$/', (string) $screen->id ) ) {
		return;
	}

	add_screen_option(
		'per_page',
		array(
			'label'   => __( 'Events per page', 'umami-connect' ),
			'default' => 20,
			'option'  => 'umami_connect_events_overview_per_page',
		)
	);

	add_filter( 'manage_' . $screen->id . '_columns', 'umami_connect_events_overview_columns' );

	add_filter( 'set-screen-option', 'umami_connect_set_screen_option', 10, 3 );
}

/**
 * Define available columns for Events Overview
 */
function umami_connect_events_overview_columns( $columns ) {
	return array(
		'event'       => __( 'Event', 'umami-connect' ),
		'integration' => __( 'Integration', 'umami-connect' ),
		'post'        => __( 'Source', 'umami-connect' ),
		'block_type'  => __( 'Block Type', 'umami-connect' ),
		'label'       => __( 'Label/Text', 'umami-connect' ),
		'data_pairs'  => __( 'Data Pairs', 'umami-connect' ),
	);
}

/**
 * Handle screen option saving
 */
function umami_connect_set_screen_option( $status, $option, $value ) {
	if ( 'umami_connect_events_overview_per_page' === $option ) {
		return max( 1, (int) $value );
	}
	return $status;
}

// Also hook the dynamic filter for this option key to ensure saving works on all WP versions.
add_filter(
	'set_screen_option_umami_connect_events_overview_per_page',
	function ( $status, $option, $value ) {
		return max( 1, (int) $value );
	},
	10,
	3
);
