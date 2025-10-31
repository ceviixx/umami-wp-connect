<?php
add_action(
	'admin_menu',
	function () {

		$general_page = add_menu_page(
			'umami Connect',
			'umami Connect',
			'manage_options',
			'umami_connect',
			'umami_connect_settings_page',
			'dashicons-chart-area',
			90
		);
		add_action( "load-{$general_page}", 'umami_connect_add_help' );

		$self_protection_page = add_submenu_page(
			'umami_connect',
			'Self protection',
			'Self protection',
			'manage_options',
			'umami_connect_self_protection',
			'umami_connect_self_protection_page',
		);
		add_action( "load-{$self_protection_page}", 'umami_connect_add_help_self_protection' );

		$automation_page = add_submenu_page(
			'umami_connect',
			'Automation',
			'Automation',
			'manage_options',
			'umami_connect_automation',
			'umami_connect_automation_page'
		);
		add_action( "load-{$automation_page}", 'umami_connect_add_help_automation' );

		$events_overview_page = add_submenu_page(
			'umami_connect',
			'Events overview',
			'Events overview',
			'edit_posts',
			'umami_connect_events_overview',
			'umami_connect_render_events_overview_page'
		);
		add_action( "load-{$events_overview_page}", 'umami_connect_add_help_events_overview' );

		$update_page = add_submenu_page(
			'umami_connect',
			'Update',
			'Update',
			'manage_options',
			'umami_connect_update',
			'umami_connect_update_page'
		);
		add_action( "load-{$update_page}", 'umami_connect_add_help_update' );
	}
);

function umami_connect_add_help() {
	$screen = get_current_screen();

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_setup',
			'title'   => 'Setup',
			'content' => '<p><strong>Quick Setup Guide</strong></p>' .
						'<ol>' .
						'<li>Create an account at <a href="https://umami.is" target="_blank">umami.is</a> (Cloud) or set up your own Umami instance (Self-hosted).</li>' .
						'<li>Add your website in your Umami dashboard and copy the Website ID.</li>' .
						'<li>Paste the Website ID below and select your mode (Cloud or Self-hosted).</li>' .
						'<li>Save the settings - tracking will start automatically.</li>' .
						'</ol>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_settings',
			'title'   => 'Settings',
			'content' => '<p><strong>Mode</strong><br>' .
						'Choose between Cloud (umami.is hosted service) or Self-hosted (your own Umami instance).</p>' .
						'<p><strong>Website ID</strong><br>' .
						'Your unique Umami Website ID in UUID format (e.g., 12345678-1234-1234-1234-123456789abc). Find this in your Umami dashboard under Settings → Websites.</p>' .
						'<p><strong>Host URL</strong><br>' .
						'Only required for self-hosted instances. Enter your Umami installation URL (e.g., https://analytics.yourdomain.com).</p>' .
						'<p><strong>Script Loading</strong><br>' .
						'<strong>defer</strong>: Execute after HTML is parsed (recommended).<br>' .
						'<strong>async</strong>: Load as soon as possible (may execute before DOM is ready).</p>',
		)
	);

	$screen->set_help_sidebar(
		'<p><strong>Support & Resources</strong></p>' .
		'<p><a href="https://github.com/ceviixx/umami-wp-connect" target="_blank">GitHub</a></p>' .
		'<p><a href="https://discord.gg/84w4CQU7Jb" target="_blank">Discord</a></p>' .
		'<p><a href="https://umami.is/docs" target="_blank">Umami Documentation</a></p>'
	);
}

function umami_connect_add_help_self_protection() {
	$screen = get_current_screen();

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_self_protection',
			'title'   => 'Overview',
			'content' => '<p><strong>Self Protection</strong></p>' .
						'<p>Prevent logged-in users (like admins, editors, or authors) from being tracked by Umami Analytics.</p>' .
						'<p>This helps keep your analytics data focused on actual visitors rather than your own site activity.</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_self_protection_settings',
			'title'   => 'Settings',
			'content' => '<p><strong>Do not track my own visits</strong><br>' .
						'When enabled, logged-in WordPress users will not be tracked. This works by setting <code>localStorage.umami.disabled = "true"</code> in the browser.</p>' .
						'<p>Disable this option if you want to measure admin/editor visits as well.</p>',
		)
	);

	$screen->set_help_sidebar(
		'<p><strong>Support & Resources</strong></p>' .
		'<p><a href="https://github.com/ceviixx/umami-wp-connect" target="_blank">GitHub</a></p>' .
		'<p><a href="https://discord.gg/84w4CQU7Jb" target="_blank">Discord</a></p>' .
		'<p><a href="https://umami.is/docs" target="_blank">Umami Documentation</a></p>'
	);
}

function umami_connect_add_help_automation() {
	$screen = get_current_screen();

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_automation',
			'title'   => 'Overview',
			'content' => '<p><strong>Automation</strong></p>' .
						'<p>Automatically track user interactions like link clicks, button clicks, and form submissions without manual configuration.</p>' .
						'<p>Tracking attributes are injected server-side for supported elements. You can override them using <code>data-umami-event</code> attributes.</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_automation_settings',
			'title'   => 'Settings',
			'content' => '<p><strong>Auto-track links</strong><br>' .
						'Automatically adds click event tracking to all <code>&lt;a&gt;</code> links on your site.</p>' .
						'<p><strong>Auto-track buttons</strong><br>' .
						'Adds click event tracking to Gutenberg Button blocks and native <code>&lt;button&gt;</code> elements.</p>' .
						'<p><strong>Auto-track forms</strong><br>' .
						'Sends a submit event when forms are submitted on your site.</p>' .
						'<p><em>Note: Manually configured tracking in the editor will always be respected and not overridden.</em></p>',
		)
	);

	$screen->set_help_sidebar(
		'<p><strong>Support & Resources</strong></p>' .
		'<p><a href="https://github.com/ceviixx/umami-wp-connect" target="_blank">GitHub</a></p>' .
		'<p><a href="https://discord.gg/84w4CQU7Jb" target="_blank">Discord</a></p>' .
		'<p><a href="https://umami.is/docs" target="_blank">Umami Documentation</a></p>'
	);
}

function umami_connect_add_help_events_overview() {
	$screen = get_current_screen();

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_events_overview',
			'title'   => 'Overview',
			'content' => '<p><strong>Events Overview</strong></p>' .
						'<p>Overview of all configured tracking events in your Gutenberg blocks. Events are custom tracking points that measure specific user interactions on your website.</p>' .
						'<p>This page helps you understand how visitors engage with your content beyond basic page views and manage all tracking configurations in one place.</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_events_creating',
			'title'   => 'Creating Events',
			'content' => '<p><strong>Supported Blocks</strong><br>' .
						'Buttons, Paragraphs, Images, and Headings can be configured for event tracking.</p>' .
						'<p><strong>Block Inspector</strong><br>' .
						'Select any supported block in Gutenberg and look for the "Umami Event Tracking" panel in the block inspector.</p>' .
						'<p><strong>Event Configuration</strong><br>' .
						'• <strong>Event Name:</strong> Add a descriptive name (e.g., "Newsletter Signup", "Download PDF")<br>' .
						'• <strong>Data Pairs:</strong> Add key-value pairs to track additional context (optional)<br>' .
						'• <strong>Link Events:</strong> Connect events to specific page URLs for cross-page tracking</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_events_managing',
			'title'   => 'Managing Events',
			'content' => '<p><strong>Filter View</strong><br>' .
						'Use "All", "Events", or "Candidates" to filter the display:<br>' .
						'• <strong>Events:</strong> Blocks with configured event names and tracking data<br>' .
						'• <strong>Candidates:</strong> Supported blocks without event configuration (potential events)</p>' .
						'<p><strong>Search & Edit</strong><br>' .
						'• Use the search box to find specific events by name or post title<br>' .
						'• Click "Edit Page/Post" to open Gutenberg and modify event settings<br>' .
						'• Use "Delete" to remove event tracking while preserving the original block</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_events_best_practices',
			'title'   => 'Best Practices',
			'content' => '<p><strong>Event Naming</strong><br>' .
						'Use clear, descriptive event names and avoid special characters for better dashboard readability.</p>' .
						'<p><strong>Data Context</strong><br>' .
						'Add data pairs to capture important context like user type, content category, or interaction details.</p>' .
						'<p><strong>Testing & Maintenance</strong><br>' .
						'• Test events in your Umami dashboard after configuration<br>' .
						'• Regularly review and clean up unused event configurations<br>' .
						'• Changes made in Gutenberg are reflected immediately in your tracking</p>',
		)
	);

	$screen->set_help_sidebar(
		'<p><strong>Support & Resources</strong></p>' .
		'<p><a href="https://github.com/ceviixx/umami-wp-connect" target="_blank">GitHub</a></p>' .
		'<p><a href="https://discord.gg/84w4CQU7Jb" target="_blank">Discord</a></p>' .
		'<p><a href="https://umami.is/docs" target="_blank">Umami Documentation</a></p>'
	);
}

function umami_connect_add_help_update() {
	$screen = get_current_screen();

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_update_overview',
			'title'   => 'Overview',
			'content' => '<p><strong>Plugin Updates</strong></p>' .
						'<p>This page allows you to manually update the plugin directly from GitHub releases.</p>' .
						'<p>You will see your current version, the latest available version, and release notes with all changes.</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'umami_help_update_usage',
			'title'   => 'Usage',
			'content' => '<p><strong>Check for Updates</strong><br>' .
						'The page automatically fetches the latest release information from GitHub when you visit it.</p>' .
						'<p><strong>Install Update</strong><br>' .
						'Click the update button to download and install the latest version. The plugin will be automatically reactivated after the update.</p>' .
						'<p><strong>Development Mode</strong><br>' .
						'Updates are disabled on localhost:8080 to prevent accidental overwrites during development.</p>' .
						'<p><strong>Release Notes</strong><br>' .
						'Each release includes detailed notes about new features, improvements, and bug fixes.</p>',
		)
	);

	$screen->set_help_sidebar(
		'<p><strong>Support & Resources</strong></p>' .
		'<p><a href="https://github.com/ceviixx/umami-wp-connect" target="_blank">GitHub</a></p>' .
		'<p><a href="https://discord.gg/84w4CQU7Jb" target="_blank">Discord</a></p>' .
		'<p><a href="https://umami.is/docs" target="_blank">Umami Documentation</a></p>'
	);
}
