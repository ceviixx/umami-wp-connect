<?php
/**
 * Contact Form 7 Integration - Admin Settings (procedural)
 *
 * Adds an editor panel to CF7 forms to configure an Umami event name and optional key/value event data.
 *
 * @package UmamiConnect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constants for meta keys.
define( 'UMAMI_CF7_META_EVENT_NAME', '_umami_cf7_custom_event' );
define( 'UMAMI_CF7_META_EVENT_DATA', '_umami_cf7_event_data' );

// Guard: run only if CF7 is present (admin side).
if ( ! is_admin() || ( ! class_exists( 'WPCF7' ) && ! function_exists( 'wpcf7' ) ) ) {
	return;
}

/**
 * Add a custom panel to the CF7 editor.
 *
 * @param array $panels Existing panels.
 * @return array
 */
function umami_cf7_add_editor_panel( $panels ) {
	$panels['umami-tracking-panel'] = array(
		'title'    => __( 'Umami Tracking', 'umami-connect' ),
		'callback' => 'umami_cf7_render_editor_panel',
	);
	return $panels;
}
add_filter( 'wpcf7_editor_panels', 'umami_cf7_add_editor_panel' );

/**
 * Render the Umami Tracking panel in CF7 editor.
 *
 * @param WPCF7_ContactForm $post Form instance.
 */
function umami_cf7_render_editor_panel( $post ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	$form_id    = method_exists( $post, 'id' ) ? (int) $post->id() : 0;
	$event      = get_post_meta( $form_id, UMAMI_CF7_META_EVENT_NAME, true );
	$event_data = get_post_meta( $form_id, UMAMI_CF7_META_EVENT_DATA, true );

	// Normalize to array of key => value for rendering.
	$pairs = array();
	if ( is_string( $event_data ) && $event_data !== '' ) {
		$decoded = json_decode( $event_data, true );
		if ( is_array( $decoded ) ) {
			$pairs = $decoded;
		}
	}
	?>
	<div class="wrap">
		<?php wp_nonce_field( 'umami_cf7_save', 'umami_cf7_nonce' ); ?>
		<p>
			<label for="umami_cf7_custom_event"><strong><?php esc_html_e( 'Event name', 'umami-connect' ); ?></strong></label><br />
			<input type="text" id="umami_cf7_custom_event" name="umami_cf7_custom_event" class="regular-text" value="<?php echo esc_attr( (string) $event ); ?>" placeholder="signup_success" />
		</p>
		<div>
			<label><strong><?php esc_html_e( 'Event Data (Key/Value)', 'umami-connect' ); ?></strong></label>
			<div id="umami-cf7-kv-list">
				<?php foreach ( $pairs as $k => $v ) : ?>
					<div class="umami-kv-row" style="margin-bottom:6px; display:flex; gap:6px; align-items:center;">
						<input type="text" name="umami_cf7_event_kv[key][]" class="regular-text" placeholder="<?php echo esc_attr( __( 'Key', 'umami-connect' ) ); ?>" value="<?php echo esc_attr( (string) $k ); ?>" />
						<input type="text" name="umami_cf7_event_kv[value][]" class="regular-text" placeholder="<?php echo esc_attr( __( 'Value', 'umami-connect' ) ); ?>" value="<?php echo esc_attr( (string) $v ); ?>" />
						<button type="button" class="button umami-kv-remove" aria-label="<?php echo esc_attr( __( 'Remove pair', 'umami-connect' ) ); ?>">&minus;</button>
					</div>
				<?php endforeach; ?>
			</div>
			<p>
				<button type="button" class="button" id="umami-kv-add">+ <?php esc_html_e( 'Add pair', 'umami-connect' ); ?></button>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Print footer script to handle dynamic add/remove for key/value rows on CF7 admin screens.
 */
function umami_cf7_admin_print_kv_script() {
	// Nur auf CF7-Edit-Seite ausgeben.
	$is_cf7_page = ( isset( $_GET['page'] ) && 'wpcf7' === $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$is_edit     = ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] && isset( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $is_cf7_page || ! $is_edit ) {
		return;
	}
	?>
	<script>
	(function(){
		function createRow(k, v) {
			var row = document.createElement('div');
			row.className = 'umami-kv-row';
			row.style.cssText = 'margin-bottom:6px; display:flex; gap:6px; align-items:center;';

			var keyInput = document.createElement('input');
			keyInput.type = 'text';
			keyInput.name = 'umami_cf7_event_kv[key][]';
			keyInput.className = 'regular-text';
			keyInput.placeholder = '<?php echo esc_js( __( 'Key', 'umami-connect' ) ); ?>';
			keyInput.value = k || '';

			var valInput = document.createElement('input');
			valInput.type = 'text';
			valInput.name = 'umami_cf7_event_kv[value][]';
			valInput.className = 'regular-text';
			valInput.placeholder = '<?php echo esc_js( __( 'Value', 'umami-connect' ) ); ?>';
			valInput.value = v || '';

			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'button umami-kv-remove';
			btn.setAttribute('aria-label', '<?php echo esc_js( __( 'Remove pair', 'umami-connect' ) ); ?>');
			btn.textContent = '−';

			row.appendChild(keyInput);
			row.appendChild(valInput);
			row.appendChild(btn);
			return row;
		}

		function bindKV() {
			var list = document.getElementById('umami-cf7-kv-list');
			var addBtn = document.getElementById('umami-kv-add');

			if (!list || !addBtn) return;

			addBtn.addEventListener('click', function(){
				var row = createRow();
				list.appendChild(row);
			});

			list.addEventListener('click', function(e){
				if (e.target && e.target.classList.contains('umami-kv-remove')) {
					var row = e.target.closest('.umami-kv-row');
					if (row) { row.remove(); }
				}
			});
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', bindKV);
		} else {
			bindKV();
		}
	})();

	// Auto-activate CF7 tab via ?umami_tab=...
	(function(){
		var tab = new URLSearchParams(location.search).get('umami_tab') || location.hash.slice(1);
		if (!tab) return;
		
		var iv = setInterval(function(){
			var btn = document.getElementById(tab + '-tab') || document.querySelector('[aria-controls="' + tab + '"]');
			if (btn) { btn.click(); clearInterval(iv); }
		}, 50);
		
		setTimeout(function(){ clearInterval(iv); }, 5000);
	})();
	</script>
	<?php
}
add_action( 'admin_print_footer_scripts', 'umami_cf7_admin_print_kv_script' );

/**
 * Save Umami settings when CF7 form is saved.
 *
 * @param WPCF7_ContactForm $contact_form Form instance after save.
 */
function umami_cf7_save_meta( $contact_form ) {
	$form_id = method_exists( $contact_form, 'id' ) ? (int) $contact_form->id() : 0;

	if ( ! $form_id ) {
		return;
	}

	if ( ! isset( $_POST['umami_cf7_nonce'] ) || ! check_admin_referer( 'umami_cf7_save', 'umami_cf7_nonce' ) ) {
		return;
	}

	$event = isset( $_POST['umami_cf7_custom_event'] ) ? sanitize_text_field( wp_unslash( $_POST['umami_cf7_custom_event'] ) ) : '';

	update_post_meta( $form_id, UMAMI_CF7_META_EVENT_NAME, $event );

	// Build associative array from key/value inputs.
	$data      = array();
	$kv_parent = filter_input( INPUT_POST, 'umami_cf7_event_kv', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$kv_parent = is_array( $kv_parent ) ? (array) wp_unslash( $kv_parent ) : array();
	$keys      = isset( $kv_parent['key'] ) ? (array) $kv_parent['key'] : array();
	$values    = isset( $kv_parent['value'] ) ? (array) $kv_parent['value'] : array();

	$max = min( count( $keys ), count( $values ) );
	for ( $i = 0; $i < $max; $i++ ) {
		$k = sanitize_text_field( (string) $keys[ $i ] );
		$v = sanitize_text_field( (string) $values[ $i ] );
		if ( $k === '' ) {
			continue;
		}
		$data[ $k ] = $v;
	}

	if ( ! empty( $data ) ) {
		update_post_meta( $form_id, UMAMI_CF7_META_EVENT_DATA, wp_json_encode( $data ) );
	} else {
		delete_post_meta( $form_id, UMAMI_CF7_META_EVENT_DATA );
	}
}
add_action( 'wpcf7_after_save', 'umami_cf7_save_meta' );
