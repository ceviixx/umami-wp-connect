<?php
add_action(
	'admin_notices',
	function () {
		$show_lang_notice = get_option( 'umami_show_lang_notice', '1' );
		if (
			isset( $_POST['umami_dev_settings_save'] ) &&
			check_admin_referer( 'umami_dev_settings_save', 'umami_dev_settings_nonce' )
		) {
			if ( isset( $_POST['umami_show_lang_notice'] ) ) {
				$show_lang_notice = '1';
			} else {
				$show_lang_notice = '0';
			}
		}
		if ( $show_lang_notice !== '1' ) {
			return;
		}
		$current_locale   = determine_locale();
		$plugin_textdomain = 'umami-connect';
		$plugin_root      = dirname( dirname( __DIR__ ) );
		$plugin_lang_dir  = $plugin_root . '/languages/';
		$po_file          = $plugin_lang_dir . $plugin_textdomain . '-' . $current_locale . '.po';
		$mo_file          = $plugin_lang_dir . $plugin_textdomain . '-' . $current_locale . '.mo';
		$po_exists        = file_exists( $po_file );
		$mo_exists        = file_exists( $mo_file );
		$status_po        = $po_exists ? 'found' : 'missing';
		$status_mo        = $mo_exists ? 'found' : 'missing';
		$status_color_po  = $po_exists ? '#46b450' : '#d63638';
		$status_color_mo  = $mo_exists ? '#46b450' : '#d63638';
		printf(
			'<div class="notice notice-info" style="font-size:13px; padding:10px 15px 10px 15px; margin-bottom:18px;">'
			. '<strong>umami Connect i18n Debug</strong>'
			. '<table style="margin-top:8px; border-collapse:collapse; width:auto;">'
			. '<tr><td style="padding:2px 8px;">Locale</td><td style="padding:2px 8px;"><code>%s</code></td></tr>'
			. '<tr><td style="padding:2px 8px;">PO-File</td><td style="padding:2px 8px;"><code>%s</code> <span style="color:%s; font-weight:bold;">%s</span></td></tr>'
			. '<tr><td style="padding:2px 8px;">MO-File</td><td style="padding:2px 8px;"><code>%s</code> <span style="color:%s; font-weight:bold;">%s</span></td></tr>'
			. '</table>'
			. '</div>',
			esc_html( $current_locale ),
			esc_html( basename( $po_file ) ),
			$status_color_po,
			$status_po,
			esc_html( basename( $mo_file ) ),
			$status_color_mo,
			$status_mo
		);
	}
);
