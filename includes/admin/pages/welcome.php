<?php
function umami_connect_welcome_page() {
	?>
	<div class="wrap">
		<h1 style="display: none;">
			<?php echo printf( esc_html__( 'Welcome to %s', 'umami-connect' ), 'umami Connect' ); ?>
		</h1>
		
		<div id="umami-connect-notices"></div>

		<div style="background: linear-gradient(135deg, #1a1a1a 0%, #3a3a3a 100%); color: #ffffff; padding: 48px 48px; border-radius: 12px; margin: 20px 0 32px 0; box-shadow: 0 4px 16px rgba(0,0,0,0.2);">
			<h2 style="color: #ffffff; margin: 0 0 20px 0; font-size: 32px; font-weight: 700; letter-spacing: -0.5px;">
				<?php printf( esc_html__( 'Welcome to %s', 'umami-connect' ), 'umami Connect' ); ?>
			</h2>
			<p style="margin: 0; font-size: 16px; color: #e8e8e8; line-height: 1.6; max-width: 720px;">
				<?php
				printf(
					// translators: 1: umami Connect, 2: Umami Analytics
					__( '<strong>%1$s</strong> brings privacy-friendly <strong>%2$s</strong> to WordPress. Effortless event tracking, easy setup, and seamless plugin integration—right where you need it.', 'umami-connect' ),
					'umami Connect',
					'Umami Analytics'
				);
				?>
			</p>
		</div>

		<script>
		document.querySelectorAll('.notice,.error,.updated,.update-nag').forEach(n=>document.getElementById('umami-connect-notices')?.appendChild(n));
		</script>

		<!-- Key Features -->
		<style>
			.umami-features-row {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
				gap: 20px;
				margin-bottom: 32px;
			}
			.umami-feature-card {
				display: flex;
				align-items: center;
				background: #f7f8fa;
				border-radius: 12px;
				padding: 18px 20px;
				box-shadow: 0 1px 4px rgba(34,113,177,0.04);
				border: 1.5px solid #e3e6ea;
			}
			.umami-feature-icon {
				display: flex;
				align-items: center;
				justify-content: center;
				width: 48px;
				height: 48px;
				min-width: 48px;
				min-height: 48px;
				max-width: 48px;
				max-height: 48px;
				background: #2271b1;
				border-radius: 50%;
				margin-right: 18px;
				box-shadow: 0 2px 8px rgba(34,113,177,0.10);
				flex-shrink: 0;
			}
			.umami-feature-icon .dashicons {
				color: #fff;
				font-size: 24px;
				width: 24px;
				height: 24px;
			}
			.umami-feature-content strong {
				display: block;
				font-size: 16px;
				font-weight: 700;
				color: #1d2327;
				margin-bottom: 2px;
			}
			.umami-feature-content span {
				font-size: 13px;
				color: #646970;
				line-height: 1.5;
			}
			.umami-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
				gap: 20px;
				margin-top: 24px;
			}
			.umami-grid .card {
				margin: 0;
			}
			/* Additional card styling */
			.umami-extra-card strong { font-size:16px; color:#1d2327; }
			.umami-extra-card a { color:#2271b1; text-decoration:none; font-weight:600; }
			.umami-extra-card a:hover { text-decoration:underline; }
		</style>
		<div class="umami-features-row">
			<div class="umami-feature-card">
				<span class="umami-feature-icon"><span class="dashicons dashicons-chart-line"></span></span>
				<div class="umami-feature-content">
					<strong><?php echo esc_html__( 'Event Tracking', 'umami-connect' ); ?></strong>
					<span><?php echo esc_html__( 'Track custom events across your site', 'umami-connect' ); ?></span>
				</div>
			</div>
			<div class="umami-feature-card">
				<span class="umami-feature-icon"><span class="dashicons dashicons-shield"></span></span>
				<div class="umami-feature-content">
					<strong><?php echo esc_html__( 'Privacy-First', 'umami-connect' ); ?></strong>
					<span><?php echo esc_html__( 'GDPR compliant, no cookies required', 'umami-connect' ); ?></span>
				</div>
			</div>
			<div class="umami-feature-card">
				<span class="umami-feature-icon"><span class="dashicons dashicons-update"></span></span>
				<div class="umami-feature-content">
					<strong><?php echo esc_html__( 'Easy Setup', 'umami-connect' ); ?></strong>
					<span><?php echo esc_html__( 'Configure in minutes, no coding needed', 'umami-connect' ); ?></span>
				</div>
			</div>
			<div class="umami-feature-card">
				<span class="umami-feature-icon"><span class="dashicons dashicons-admin-plugins"></span></span>
				<div class="umami-feature-content">
					<strong><?php echo esc_html__( 'Multiple Integrations', 'umami-connect' ); ?></strong>
					<span><?php echo esc_html__( 'Works with popular plugins & blocks', 'umami-connect' ); ?></span>
				</div>
			</div>
			<div class="umami-feature-card">
				<span class="umami-feature-icon"><span class="dashicons dashicons-visibility"></span></span>
				<div class="umami-feature-content">
					<strong><?php echo esc_html__( 'Inline Dashboard', 'umami-connect' ); ?></strong>
					<span><?php echo esc_html__( 'View umami Statistics directly in your WordPress admin', 'umami-connect' ); ?></span>
				</div>
			</div>
		</div>

		<?php
		// Load integrations from registry.
		$integrations = umami_connect_get_integrations();
		?>

		<div class="umami-grid">
			<?php foreach ( $integrations as $key => $integration ) : ?>
				<?php
				$is_active = isset( $integration['check'] ) && is_callable( $integration['check'] ) ? $integration['check']() : false;
				$label     = isset( $integration['label'] ) ? $integration['label'] : ucfirst( str_replace( '-', ' ', $key ) );
				$desc      = isset( $integration['description'] ) ? $integration['description'] : '';
				$color     = isset( $integration['color'] ) ? $integration['color'] : '#646970';
				?>
				<div class="card" style="background:#fff; border-radius:8px; padding:20px;">
					<div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
						<span style="display:inline-block; width:16px; height:16px; background:<?php echo esc_attr( $color ); ?>; border-radius:4px; flex-shrink:0;"></span>
						<strong style="font-size:16px; color:#1d2327; flex-grow:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
							<?php echo esc_html( $label ); ?>
						</strong>
						<span style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; padding:4px 8px; border-radius:4px; background:<?php echo $is_active ? '#e6f4ea' : '#f3f4f6'; ?>; color:<?php echo $is_active ? '#137333' : '#646970'; ?>; white-space:nowrap; flex-shrink:0;">
							<?php echo $is_active ? esc_html__( 'Active', 'umami-connect' ) : esc_html__( 'Inactive', 'umami-connect' ); ?>
						</span>
					</div>
					<?php if ( $desc ) : ?>
						<div style="font-size:13px; color:#646970; line-height:1.6;">
							<?php echo esc_html( $desc ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<!-- Additional integrations / contribution card -->
			<div class="card umami-extra-card" style="background:#fff; border-radius:8px; padding:20px; display:flex; flex-direction:column; gap:12px;">
				<div style="display:flex; align-items:center; gap:12px;">
					<span style="display:inline-block; width:16px; height:16px; background:#d1d5db; border-radius:4px; flex-shrink:0;"></span>
					<strong><?php echo esc_html__( 'More Integrations Planned', 'umami-connect' ); ?></strong>
				</div>
				<div style="font-size:13px; color:#646970; line-height:1.6;">
					<?php
						$issues_url  = 'https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/issues/new?template=feature_request.yml&labels=enhancement';
						$pulls_url   = 'https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/pulls';
						$contrib_url = 'https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/blob/main/CONTRIBUTING.md';
						$text_html   = sprintf(
							// translators: 1: feature request URL, 2: pull request list URL, 3: contributing guide URL
							__( 'Suggest an integration via a <a href="%1$s" target="_blank" rel="noopener noreferrer">feature request</a>, or add it directly with a <a href="%2$s" target="_blank" rel="noopener noreferrer">pull request</a>. See the <a href="%3$s" target="_blank" rel="noopener noreferrer">contribution guide</a>.', 'umami-connect' ),
							esc_url( $issues_url ),
							esc_url( $pulls_url ),
							esc_url( $contrib_url )
						);
						echo wp_kses(
							$text_html,
							array(
								'a' => array(
									'href'   => array(),
									'target' => array(),
									'rel'    => array(),
								),
							)
						);
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>
