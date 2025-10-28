<?php
add_action(
	'admin_menu',
	function () {

		add_menu_page(
			'umami Connect',
			'umami Connect',
			'manage_options',
			'umami_connect',
			'umami_connect_settings_page',
			'dashicons-chart-area',
			90
		);

		add_submenu_page(
			'umami_connect',
			'Self protection',
			'Self protection',
			'manage_options',
			'umami_connect_self_protection',
			'umami_connect_self_protection_page',
		);

		add_submenu_page(
			'umami_connect',
			'Automation',
			'Automation',
			'manage_options',
			'umami_connect_automation',
			'umami_connect_automation_page'
		);

		add_submenu_page(
			'umami_connect',
			'Events overview',
			'Events overview',
			'manage_options',
			'umami_connect_events_overview',
			'umami_connect_render_events_overview_page'
		);

		add_submenu_page(
			'umami_connect',
			'Support & Updates',
			'Support & Updates',
			'read',
			'umami_connect_support',
			'umami_connect_support_page'
		);
	}
);
