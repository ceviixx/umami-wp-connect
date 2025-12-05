<?php
function umami_connect_self_protection_page() {
	?>
	<div class="wrap">
		<h1><b>umami Connect</b></h1>
		<h3><?php echo esc_html__( 'Self protection', 'umami-connect' ); ?></h3>
		<form action="options.php" method="post">
	<?php
	settings_fields( 'umami_connect_self' );
	do_settings_sections( 'umami_connect_self_protection' );

	submit_button( esc_html__( 'Save', 'umami-connect' ) );
	?>
		</form>
	</div>
	<?php
}
?>