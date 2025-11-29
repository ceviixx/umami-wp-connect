<?php
function umami_connect_settings_page() {
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'setup';
	$tabs = array(
		'setup'     => 'Setup',
		'share-url' => 'umami Statistics',
	);
	?>
	<div class="wrap">
		<h1><b>umami Connect</b></h1>
		<h3>General</h3>
		<h2 class="nav-tab-wrapper" style="margin-top:12px;">
			<?php foreach ( $tabs as $key => $label ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=umami_connect&tab=' . $key ) ); ?>" class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</h2>
		<div style="margin-top:16px; max-width: 760px;">
			<?php if ( $tab === 'setup' ) : ?>
				<form action="options.php" method="post" id="umami-connect-form">
					<?php
					settings_fields( 'umami_connect_general' );
					do_settings_sections( 'umami_connect' );
					submit_button();
					?>
				</form>
				<script>
				(function(){
					var modeSelect = document.getElementById('umami_mode');
					var hostInput = document.getElementById('umami_host');
					var hostRow = hostInput ? hostInput.closest('tr') : null;
					function toggleHost() {
						if (!modeSelect || !hostRow) return;
						var isSelf = modeSelect.value === 'self';
						hostRow.style.display = isSelf ? '' : 'none';
						if (hostInput) {
							hostInput.required = isSelf;
							hostInput.disabled = !isSelf;
						}
					}
					toggleHost();
					if (modeSelect) modeSelect.addEventListener('change', toggleHost);

					var form = document.getElementById('umami-connect-form');
					var websiteIdInput = document.getElementById('umami_website_id');
					var errorDiv = document.getElementById('umami-website-id-error');
					if (form && websiteIdInput && errorDiv) {
						var uuidPattern = /^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i;

						function validateUuidInput() {
							var uuid = websiteIdInput.value.trim();
							if (!uuidPattern.test(uuid)) {
								errorDiv.textContent = 'Please enter a valid Umami Website ID (UUID format).';
								errorDiv.style.display = 'block';
								websiteIdInput.classList.add('input-error');
							} else {
								errorDiv.textContent = '';
								errorDiv.style.display = 'none';
								websiteIdInput.classList.remove('input-error');
							}
						}

						websiteIdInput.addEventListener('input', validateUuidInput);

						form.addEventListener('submit', function(e){
							var uuid = websiteIdInput.value.trim();
							if (!uuidPattern.test(uuid)) {
								e.preventDefault();
								errorDiv.textContent = 'Please enter a valid Umami Website ID (UUID format).';
								errorDiv.style.display = 'block';
								websiteIdInput.focus();
								websiteIdInput.classList.add('input-error');
							} else {
								errorDiv.textContent = '';
								errorDiv.style.display = 'none';
								websiteIdInput.classList.remove('input-error');
							}
						});
					}
				})();
				</script>
			<?php elseif ( $tab === 'share-url' ) : ?>
				<form action="options.php" method="post" id="umami-share-url-form">
					<?php
					settings_fields( 'umami_connect_share_url' );
					$mode = get_option( 'umami_mode', 'cloud' );
					$ph   = ( $mode === 'self' ) ? 'https://your-umami-instance.com/share/xxxx' : 'https://cloud.umami.is/share/xxxx';
					?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="umami_advanced_share_url">Share URL</label></th>
							<td>
								<?php $share_url_saved = get_option( 'umami_advanced_share_url', '' ); ?>
								<input type="url" class="regular-text" id="umami_advanced_share_url" name="umami_advanced_share_url" value="<?php echo esc_attr( $share_url_saved ); ?>" placeholder="<?php echo esc_attr( $ph ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">Allowed Roles</th>
							<td>
								<?php
								$roles         = get_editable_roles();
								$allowed_roles = get_option( 'umami_statistics_allowed_roles', array() );
								if ( ! is_array( $allowed_roles ) ) {
									$allowed_roles = array();
								}
								echo '<div style="display:flex;flex-wrap:wrap;gap:16px;">';
								foreach ( $roles as $role_key => $role ) {
									if ( $role_key === 'administrator' ) {
										continue;
									}
									$checked = in_array( $role_key, $allowed_roles ) ? 'checked' : '';
									echo '<label style="margin-right:16px;"><input type="checkbox" name="umami_statistics_allowed_roles[]" value="' . esc_attr( $role_key ) . '" ' . $checked . '> ' . esc_html( $role['name'] ) . '</label>';
								}
								echo '</div>';
								?>
							</td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</form>
			<?php endif; ?>
		</div>
	</div>
	<?php
}







add_action(
	'wp_ajax_umami_check_share_url',
	function () {
		$url = isset( $_GET['url'] ) ? esc_url_raw( wp_unslash( $_GET['url'] ) ) : '';
		if ( empty( $url ) ) {
			wp_send_json_error( array( 'reason' => 'No URL provided' ) );
		}
		$headers        = @get_headers( $url, 1 );
		$embeddable     = true;
		$reason         = '';
		$is_umami_cloud = strpos( $url, 'cloud.umami.is' ) !== false;
		$current_host   = ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : 'localhost' );
		$current_proto  = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) ? 'https://' : 'http://';
		$current_origin = $current_proto . $current_host;
		if ( $headers && is_array( $headers ) ) {
			foreach ( $headers as $key => $value ) {
				$key_lower = strtolower( $key );
				if ( $key_lower === 'x-frame-options' ) {
					if ( ! $is_umami_cloud ) {
						$embeddable = false;
						$reason     = 'X-Frame-Options: ' . ( is_array( $value ) ? implode( ', ', $value ) : $value );
					}
				}
				if ( $key_lower === 'content-security-policy' ) {
					if ( is_array( $value ) ) {
						$value = implode( ' ', $value );
					}
					if ( stripos( $value, 'frame-ancestors' ) !== false && ! $is_umami_cloud ) {
						if ( preg_match( '/frame-ancestors\s+([^;]+)/i', $value, $matches ) ) {
							$ancestors = preg_split( '/\s+/', trim( $matches[1] ) );
							$found     = false;
							foreach ( $ancestors as $ancestor ) {
								if ( $ancestor === "'self'" || $ancestor === $current_origin || strpos( $url, $ancestor ) === 0 ) {
									$found = true;
									break;
								}
							}
							if ( $found ) {
								$embeddable = true;
								$reason     = '';
							} else {
								$embeddable = false;
								$reason     = 'Content-Security-Policy: ' . $value;
							}
						} else {
							$embeddable = false;
							$reason     = 'Content-Security-Policy: ' . $value;
						}
					}
				}
			}
		} else {
			$embeddable = false;
			$reason     = 'Could not fetch headers.';
		}
		if ( $embeddable ) {
			wp_send_json(
				array(
					'success'    => true,
					'embeddable' => true,
				)
			);
		} else {
			wp_send_json(
				array(
					'success'    => true,
					'embeddable' => false,
					'reason'     => $reason,
				)
			);
		}
	}
);

add_action(
	'wp_ajax_umami_reset_share_url',
	function () {
		if ( delete_option( 'umami_advanced_share_url' ) ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}
);
