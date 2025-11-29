<?php
// Global admin notices for success and error after form submission
add_action(
	'admin_notices',
	function () {
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
			echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
		}
		if ( isset( $_GET['error'] ) && $_GET['error'] === 'true' ) {
			echo '<div class="notice notice-error is-dismissible"><p>Error saving settings.</p></div>';
		}
		// Auto-hide script for notices
		echo '<script>
		document.addEventListener("DOMContentLoaded", function() {
			var notices = document.querySelectorAll(".notice-success, .notice-error");
			notices.forEach(function(notice) {
				setTimeout(function() {
					notice.style.transition = "opacity 0.4s ease";
					notice.style.opacity = "0";
					setTimeout(function() {
						if (notice.parentNode) {
							notice.parentNode.removeChild(notice);
						}
					}, 400);
				}, 3000);
			});
		});
		</script>';
	}
);
