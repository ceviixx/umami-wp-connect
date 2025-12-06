<?php
/**
 * WPForms Integration - Admin Settings (procedural)
 *
 * Adds a settings panel to WPForms forms to configure an Umami event name and optional key/value event data.
 *
 * @package UmamiConnect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constants for meta keys.
if ( ! defined( 'UMAMI_WPFORMS_META_EVENT_NAME' ) ) {
	define( 'UMAMI_WPFORMS_META_EVENT_NAME', '_umami_wpforms_custom_event' );
}
if ( ! defined( 'UMAMI_WPFORMS_META_EVENT_DATA' ) ) {
	define( 'UMAMI_WPFORMS_META_EVENT_DATA', '_umami_wpforms_event_data' );
}

// Guard: run only if WPForms is present (admin side).
if ( ! is_admin() || ! function_exists( 'wpforms' ) ) {
	return;
}

/**
 * Add Umami Tracking settings section to WPForms builder.
 *
 * @param array $sections Existing sections.
 * @return array
 */
function umami_wpforms_add_settings_section( $sections ) {
	$sections['umami_tracking'] = wp_kses_post( 'Umami Tracking', 'umami-connect' );
	return $sections;
}
add_filter( 'wpforms_builder_settings_sections', 'umami_wpforms_add_settings_section', 20 );

/**
 * Add Umami Tracking settings content to WPForms builder.
 *
 * @param object $instance Form builder instance.
 */
function umami_wpforms_settings_content( $instance ) {
	$form_data = isset( $instance->form_data ) ? $instance->form_data : array();

	// Get values from form_data settings, not post meta.
	$event      = isset( $form_data['settings']['umami_event_name'] ) ? $form_data['settings']['umami_event_name'] : '';
	$event_data = isset( $form_data['settings']['umami_event_data'] ) ? $form_data['settings']['umami_event_data'] : '';

	// Normalize to array of key => value for rendering.
	$pairs = array();
	if ( is_string( $event_data ) && $event_data !== '' ) {
		$decoded = json_decode( $event_data, true );
		if ( is_array( $decoded ) ) {
			$pairs = $decoded;
		}
	}
	?>
	<div class="wpforms-panel-content-section wpforms-panel-content-section-umami_tracking">
		<div class="wpforms-panel-content-section-title">
			<?php esc_html_e( 'Event Configuration', 'umami-connect' ); ?>
		</div>
		<div class="wpforms-panel-field">
			<label for="wpforms-panel-field-settings-umami_event_name">
				<?php esc_html_e( 'Event Name', 'umami-connect' ); ?>
				<span class="wpforms-help-tooltip" style="margin-left: 5px;" title="<?php esc_attr_e( 'The name of the event to track when this form is submitted', 'umami-connect' ); ?>">?</span>
			</label>
			<input type="text" id="wpforms-panel-field-settings-umami_event_name" name="settings[umami_event_name]" value="<?php echo esc_attr( (string) $event ); ?>" placeholder="e.g., form_submission" style="width: 100%; max-width: 500px;" />
		</div>
	</div>

	<div class="wpforms-panel-content-section wpforms-panel-content-section-umami_tracking" style="margin-top: 15px;">
		<div class="wpforms-panel-field">
			<label style="font-weight: 600; display: block; margin-bottom: 5px;">
				<?php esc_html_e( 'Event Data (Key/Value)', 'umami-connect' ); ?>
			</label>
			<p class="description" style="margin-top: 0; margin-bottom: 15px;">
				<?php esc_html_e( 'Add custom key-value pairs to send additional data with this event.', 'umami-connect' ); ?>
			</p>
			<div id="umami-wpforms-kv-list" style="margin-bottom: 10px;">
				<?php if ( ! empty( $pairs ) ) : ?>
					<?php foreach ( $pairs as $k => $v ) : ?>
						<div class="umami-kv-row" style="margin-bottom: 8px; display: flex; gap: 8px; align-items: center;">
							<input type="text" name="umami_wpforms_event_kv[key][]" placeholder="<?php echo esc_attr( wp_kses_post( 'Key', 'umami-connect' ) ); ?>" value="<?php echo esc_attr( (string) $k ); ?>" style="flex: 1; max-width: 240px;" />
							<input type="text" name="umami_wpforms_event_kv[value][]" placeholder="<?php echo esc_attr( wp_kses_post( 'Value', 'umami-connect' ) ); ?>" value="<?php echo esc_attr( (string) $v ); ?>" style="flex: 1; max-width: 240px;" />
							<button type="button" class="button umami-kv-remove" aria-label="<?php echo esc_attr( wp_kses_post( 'Remove pair', 'umami-connect' ) ); ?>" style="min-width: 32px;">&minus;</button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<input type="hidden" id="umami-wpforms-event-data-hidden" name="settings[umami_event_data]" value="<?php echo esc_attr( $event_data ); ?>" />
			<button type="button" class="button button-secondary" id="umami-wpforms-kv-add">
				<span class="dashicons dashicons-plus-alt2" style="margin-top: 3px;"></span>
				<?php esc_html_e( 'Add Key-Value Pair', 'umami-connect' ); ?>
			</button>
		</div>
	</div>
	<?php
}
add_action( 'wpforms_form_settings_panel_content', 'umami_wpforms_settings_content', 20 );

/**
 * Print footer script to handle dynamic add/remove for key/value rows on WPForms admin screens.
 */
function umami_wpforms_admin_print_kv_script() {
	$screen = get_current_screen();
	if ( ! $screen || strpos( $screen->id, 'wpforms' ) === false ) {
		return;
	}
	?>
	<script>
	(function(){
		function createRow(k, v) {
			var row = document.createElement('div');
			row.className = 'umami-kv-row';
			row.style.cssText = 'margin-bottom: 8px; display: flex; gap: 8px; align-items: center;';

			var keyInput = document.createElement('input');
			keyInput.type = 'text';
			keyInput.name = 'umami_wpforms_event_kv[key][]';
			keyInput.placeholder = '<?php echo esc_js( wp_kses_post( 'Key', 'umami-connect' ) ); ?>';
			keyInput.value = k || '';
			keyInput.style.cssText = 'flex: 1; max-width: 240px;';

			var valInput = document.createElement('input');
			valInput.type = 'text';
			valInput.name = 'umami_wpforms_event_kv[value][]';
			valInput.placeholder = '<?php echo esc_js( wp_kses_post( 'Value', 'umami-connect' ) ); ?>';
			valInput.value = v || '';
			valInput.style.cssText = 'flex: 1; max-width: 240px;';

			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'button umami-kv-remove';
			btn.setAttribute('aria-label', '<?php echo esc_js( wp_kses_post( 'Remove pair', 'umami-connect' ) ); ?>');
			btn.textContent = '−';
			btn.style.cssText = 'min-width: 32px;';

			row.appendChild(keyInput);
			row.appendChild(valInput);
			row.appendChild(btn);
			return row;
		}

		function updateHiddenField() {
			var list = document.getElementById('umami-wpforms-kv-list');
			var hidden = document.getElementById('umami-wpforms-event-data-hidden');
			if (!list || !hidden) return;

			var data = {};
			var rows = list.querySelectorAll('.umami-kv-row');
			rows.forEach(function(row) {
				var keyInput = row.querySelector('input[name="umami_wpforms_event_kv[key][]"]');
				var valInput = row.querySelector('input[name="umami_wpforms_event_kv[value][]"]');
				if (keyInput && valInput && keyInput.value.trim() !== '') {
					data[keyInput.value.trim()] = valInput.value.trim();
				}
			});

			hidden.value = Object.keys(data).length > 0 ? JSON.stringify(data) : '';
		}

		function bindKV() {
			var list = document.getElementById('umami-wpforms-kv-list');
			var addBtn = document.getElementById('umami-wpforms-kv-add');

			if (!list || !addBtn) return;

			addBtn.addEventListener('click', function(){
				var row = createRow();
				list.appendChild(row);
			});

			list.addEventListener('click', function(e){
				if (e.target && e.target.classList.contains('umami-kv-remove')) {
					var row = e.target.closest('.umami-kv-row');
					if (row) {
						row.remove();
						updateHiddenField();
					}
				}
			});

			// Update hidden field on input change.
			list.addEventListener('input', updateHiddenField);
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', bindKV);
		} else {
			bindKV();
		}
	})();
	</script>
	<?php
}
add_action( 'admin_print_footer_scripts', 'umami_wpforms_admin_print_kv_script' );
