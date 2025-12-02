<?php
add_action( 'admin_menu', function() {
	$hook = add_submenu_page(
		'umami_connect_welcome',
		'<span style="color:#d63638;">Umami Connect Dev</span>',
		'<span style="color:#d63638;">Development</span>',
		'manage_options',
		'umami_connect_dev_settings',
		'umami_connect_dev_settings_page'
	);
	add_action( 'admin_head', function() use ( $hook ) {
		if ( ! empty( $hook ) ) {
			echo '<style>#toplevel_page_umami_connect_welcome .wp-submenu li a[href*="umami_connect_dev_settings"] { color: #d63638 !important; font-weight: bold; }</style>';
		}
	} );
} );

function umami_connect_dev_settings_page() {
	if ( isset( $_POST['umami_dev_settings_save'] ) && check_admin_referer( 'umami_dev_settings_save', 'umami_dev_settings_nonce' ) ) {
		$show_lang_notice = isset( $_POST['umami_show_lang_notice'] ) ? '1' : '0';
		update_option( 'umami_show_lang_notice', $show_lang_notice );
		echo '<div class="updated"><p>Settings saved.</p></div>';
	}
	$show_lang_notice = get_option( 'umami_show_lang_notice', '1' );
	?>
	<div class="wrap">
		<h1><b>umami Connect</b></h1>
        <h3>Developer Settings</h3>
		<form method="post">
			<?php wp_nonce_field( 'umami_dev_settings_save', 'umami_dev_settings_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">Show Language Debug Notice</th>
					<td>
						<input type="checkbox" name="umami_show_lang_notice" value="1" <?php checked( $show_lang_notice, '1' ); ?> />
						<label for="umami_show_lang_notice">Display the i18n language/locale notice in admin area</label>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Save Settings', 'primary', 'umami_dev_settings_save' ); ?>
		</form>
<!--
		<h2>Developer Documentation</h2>
		<p>Hier kannst du weitere Hinweise, Links oder Markdown für Entwickler-Doku einfügen.</p>
-->
	</div>
	<?php
}
