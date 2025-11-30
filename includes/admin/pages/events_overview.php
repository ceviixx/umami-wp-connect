<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UMAMI_CONNECT_MAX_BLOCK_NESTING_DEPTH', 15 );

function umami_connect_render_events_overview_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'umami-connect' ) );
	}

	if ( isset( $_POST['umami_delete_event'] ) && check_admin_referer( 'umami_delete_event', 'umami_delete_nonce' ) ) {
		$post_id     = isset( $_POST['post_id'] ) ? sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) : ''; // Can be numeric post ID or string form_id
		$block_index = isset( $_POST['block_index'] ) ? sanitize_text_field( wp_unslash( $_POST['block_index'] ) ) : '';
		$event_type  = isset( $_POST['event_type'] ) ? sanitize_key( wp_unslash( $_POST['event_type'] ) ) : 'button';

		$result = false;

		if ( strpos( $event_type, 'integration_' ) === 0 ) {
			// Integration events - pass form_id/identifier as-is to the filter
			$result = apply_filters( 'umami_connect_delete_integration_event', false, $event_type, $post_id );
		} else {
			// Gutenberg block events - require valid numeric post_id
			$numeric_post_id = absint( $post_id );
			if ( ! $numeric_post_id || $numeric_post_id <= 0 ) {
				echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Error: Invalid ID.', 'umami-connect' ) . '</strong></p></div>';
			} elseif ( empty( $block_index ) || ! is_string( $block_index ) ) {
				echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Error: Invalid block index.', 'umami-connect' ) . '</strong></p></div>';
			} elseif ( ! in_array( $event_type, array( 'button', 'link' ), true ) ) {
				echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Error: Invalid event type.', 'umami-connect' ) . '</strong></p></div>';
			} else {
				$result = umami_connect_delete_event_from_block( $numeric_post_id, $block_index, $event_type );
			}
		}

		if ( $result ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Event deleted successfully.', 'umami-connect' ) . '</strong></p></div>';
		} elseif ( strpos( $event_type, 'integration_' ) === 0 ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Error: Could not delete integration event.', 'umami-connect' ) . '</p></div>';
		} elseif ( ! empty( $post_id ) && absint( $post_id ) > 0 ) {
			// Only show generic error if we had a valid post_id but deletion failed
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Error: Could not delete event.', 'umami-connect' ) . '</p></div>';
		}
	}

	echo '<div class="wrap">';
	echo '<h1><b>umami Connect</b></h1>';
	echo '<h3>' . esc_html__( 'Event overview', 'umami-connect' ) . '</h3>';
	echo '<p>' . esc_html__( 'Overview of all configured tracking events from Gutenberg blocks and integrations. Click "Edit" to manage individual events.', 'umami-connect' ) . '</p>';

	$screen = get_current_screen();
	// Per-page aus Screen Options lesen (eigener Key)
	$per_page = (int) get_user_meta( get_current_user_id(), 'umami_connect_events_overview_per_page', true );
	if ( $per_page < 1 ) {
		$per_page = 20;
	}

	// Alle Events von Core & Integrationen holen
	$events = apply_filters( 'umami_connect_get_all_events', array(), $per_page );

	// Current Filter & Search
	$current_filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : 'all';
	$search         = isset( $_GET['s'] ) ? trim( strtolower( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) ) : '';

	// Zähler für Views (nach Search-Filter)
	$all_count        = 0;
	$events_count     = 0;
	$candidates_count = 0;
	if ( ! empty( $events ) ) {
		// Load integrations for label matching in search
		$all_integrations = umami_connect_get_integrations();

		foreach ( $events as $e ) {
			// Apply search filter for counting
			if ( $search !== '' ) {
				$integration_label = '';
				$integration_key   = isset( $e['integration'] ) ? (string) $e['integration'] : '';
				if ( $integration_key !== '' && isset( $all_integrations[ $integration_key ]['label'] ) ) {
					$integration_label = (string) $all_integrations[ $integration_key ]['label'];
				}
				// Fallback: use provided integration_label from row if registry lookup failed
				if ( $integration_label === '' && isset( $e['integration_label'] ) && is_string( $e['integration_label'] ) ) {
					$integration_label = $e['integration_label'];
				}
				$rowtext = strtolower( ( $e['event'] ?? '' ) . ' ' . ( $e['post_title'] ?? '' ) . ' ' . ( $e['label'] ?? '' ) . ' ' . $integration_label . ' ' . $integration_key );
				if ( strpos( $rowtext, $search ) === false ) {
					continue;
				}
			}

			++$all_count;
			$is_tracked = isset( $e['is_tracked'] ) ? (bool) $e['is_tracked'] : true;
			if ( $is_tracked ) {
				++$events_count;
			} else {
				++$candidates_count;
			}
		}
	}

	// Views + Search UI
	echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; min-height: 32px;">';
	echo '<ul class="subsubsub" style="margin: 0;">';
	$base_url = remove_query_arg( array( 'filter', 'paged' ) );

	// All filter - always visible
	echo '<li class="all"><a href="' . esc_url( $base_url ) . '" class="' . ( $current_filter === 'all' ? 'current' : '' ) . '">' . esc_html__( 'All', 'umami-connect' ) . ' <span class="count">(' . (int) $all_count . ')</span></a> | </li>';

	// Events filter - always visible
	$events_url = add_query_arg( 'filter', 'events', $base_url );
	echo '<li class="events"><a href="' . esc_url( $events_url ) . '" class="' . ( $current_filter === 'events' ? 'current' : '' ) . '">' . esc_html__( 'Events', 'umami-connect' ) . ' <span class="count">(' . (int) $events_count . ')</span></a> | </li>';

	// Candidates filter - always visible
	$candidates_url = add_query_arg( 'filter', 'candidates', $base_url );
	echo '<li class="candidates"><a href="' . esc_url( $candidates_url ) . '" class="' . ( $current_filter === 'candidates' ? 'current' : '' ) . '">' . esc_html__( 'Candidates', 'umami-connect' ) . ' <span class="count">(' . (int) $candidates_count . ')</span></a></li>';
	echo '</ul>';
	echo '<form method="get" style="margin: 0;">';
	echo '<input type="hidden" name="page" value="umami_connect_events_overview">';
	if ( $current_filter !== 'all' ) {
		echo '<input type="hidden" name="filter" value="' . esc_attr( $current_filter ) . '">';
	}
	echo '<div class="search-box" style="display:flex; align-items:center; gap:8px; position:relative;">';
	echo '<label class="screen-reader-text" for="event-search-input">' . esc_html__( 'Search events', 'umami-connect' ) . ':</label>';
	echo '<div style="position:relative;">';
	echo '<input type="search" id="event-search-input" name="s" value="' . esc_attr( isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '' ) . '" placeholder="' . esc_attr__( 'Search events...', 'umami-connect' ) . '" style="width: 200px;" autocomplete="off" />';
	echo '<div id="search-suggestions" style="display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #ddd; border-top:none; box-shadow:0 2px 4px rgba(0,0,0,0.1); z-index:1000; max-height:200px; overflow-y:auto;"></div>';
	echo '</div>';
	echo '<input type="submit" id="search-submit" class="button" value="' . esc_attr__( 'Search', 'umami-connect' ) . '">';
	echo '</div>';
	echo '</form>';
	echo '</div>';

	// Build integration suggestions data (include color)
	$integration_suggestions = array();
	if ( ! isset( $all_integrations ) || empty( $all_integrations ) ) {
		$all_integrations = umami_connect_get_integrations();
	}
	if ( ! empty( $all_integrations ) && is_array( $all_integrations ) ) {
		foreach ( $all_integrations as $key => $config ) {
			if ( isset( $config['check'] ) && is_callable( $config['check'] ) && call_user_func( $config['check'] ) ) {
				$integration_suggestions[] = array(
					'label' => isset( $config['label'] ) ? $config['label'] : ucfirst( str_replace( '-', ' ', $key ) ),
					'key'   => $key,
					'color' => isset( $config['color'] ) ? $config['color'] : '#777777',
				);
			}
		}
	}

	echo '<script>
	(function() {
		var searchInput = document.getElementById("event-search-input");
		var suggestionsBox = document.getElementById("search-suggestions");
		var integrations = ' . wp_json_encode( $integration_suggestions ) . ';

		searchInput.addEventListener("input", function() {
			var value = this.value.toLowerCase().trim();

			if (value.length === 0) {
				suggestionsBox.style.display = "none";
				return;
			}

			var matches = integrations.filter(function(int) {
				return int.label.toLowerCase().indexOf(value) === 0;
			});

			if (matches.length === 0) {
				suggestionsBox.style.display = "none";
				return;
			}

			var html = "";
			matches.forEach(function(int) {
				var dot = "<span class=\\"integration-dot\\" style=\\"display:inline-block;width:10px;height:10px;border-radius:50%;background:" + (int.color || \'#777\') + ";margin-right:8px;vertical-align:middle;\\"></span>";
				html += "<div class=\\"suggestion-item\\" style=\\"padding:8px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; display:flex; align-items:center;\\" data-value=\\"" + int.label + "\\">" + dot + "<span>" + int.label + "</span></div>";
			});

			suggestionsBox.innerHTML = html;
			suggestionsBox.style.display = "block";

			// Add click handlers
			var items = suggestionsBox.querySelectorAll(".suggestion-item");
			items.forEach(function(item) {
				item.addEventListener("mouseenter", function() {
					this.style.backgroundColor = "#f0f0f0";
				});
				item.addEventListener("mouseleave", function() {
					this.style.backgroundColor = "";
				});
				item.addEventListener("click", function() {
					searchInput.value = this.getAttribute("data-value");
					suggestionsBox.style.display = "none";
					searchInput.form.submit();
				});
			});
		});

		// Close suggestions on click outside
		document.addEventListener("click", function(e) {
			if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
				suggestionsBox.style.display = "none";
			}
		});

		// Close on escape
		searchInput.addEventListener("keydown", function(e) {
			if (e.key === "Escape") {
				suggestionsBox.style.display = "none";
			}
		});
	})();
	</script>';

	// Filter-Umschaltung nicht nötig: Views basieren direkt auf den gelieferten Counts der List Table

	// Hidden Columns aus Screen Options
	$hidden_columns = get_hidden_columns( $screen );
	$columns        = umami_connect_events_overview_columns( array() );

	// Sarch + Filter apply
	$filtered = array();
	if ( ! empty( $events ) ) {
		// Load integrations for label matching in search
		$all_integrations = umami_connect_get_integrations();

		foreach ( $events as $row ) {
			// Build search text including integration label
			$integration_label = '';
			$integration_key   = isset( $row['integration'] ) ? (string) $row['integration'] : '';
			if ( $integration_key !== '' && isset( $all_integrations[ $integration_key ]['label'] ) ) {
				$integration_label = (string) $all_integrations[ $integration_key ]['label'];
			}
			// Fallback: use provided integration_label from row if registry lookup failed
			if ( $integration_label === '' && isset( $row['integration_label'] ) && is_string( $row['integration_label'] ) ) {
				$integration_label = $row['integration_label'];
			}

			$rowtext = strtolower( ( $row['event'] ?? '' ) . ' ' . ( $row['post_title'] ?? '' ) . ' ' . ( $row['label'] ?? '' ) . ' ' . $integration_label . ' ' . $integration_key );
			if ( $search !== '' && strpos( $rowtext, $search ) === false ) {
				continue;
			}
			$is_tracked = isset( $row['is_tracked'] ) ? (bool) $row['is_tracked'] : true;
			if ( $current_filter === 'events' && ! $is_tracked ) {
				continue;
			}
			if ( $current_filter === 'candidates' && $is_tracked ) {
				continue;
			}
			$filtered[] = $row;
		}
	}

	// Sort
	$orderby      = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'event';
	$order        = isset( $_GET['order'] ) ? strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : 'asc';
	$block_labels = array(
		'core/button'       => 'Button',
		'core/paragraph'    => 'Paragraph',
		'core/post-excerpt' => 'Excerpt',
		'core/heading'      => 'Heading',
		'core/quote'        => 'Quote',
		'core/pullquote'    => 'Pullquote',
		'core/list'         => 'List',
		'core/list-item'    => 'List Item',
		'core/columns'      => 'Columns',
		'core/cover'        => 'Cover',
		'core/group'        => 'Group',
	);

	usort(
		$filtered,
		function ( $a, $b ) use ( $orderby, $order, $block_labels ) {
			$a_val = '';
			$b_val = '';
			if ( 'block_type' === $orderby ) {
				$a_val = strtolower( $block_labels[ $a['block_type'] ?? '' ] ?? ( $a['block_type'] ?? '' ) );
				$b_val = strtolower( $block_labels[ $b['block_type'] ?? '' ] ?? ( $b['block_type'] ?? '' ) );
			} elseif ( 'post_title' === $orderby || 'post' === $orderby ) {
				$a_val = strtolower( $a['post_title'] ?? '' );
				$b_val = strtolower( $b['post_title'] ?? '' );
			} elseif ( 'integration_label' === $orderby || 'integration' === $orderby ) {
				$a_val = strtolower( $a['integration_label'] ?? ( $a['integration'] ?? '' ) );
				$b_val = strtolower( $b['integration_label'] ?? ( $b['integration'] ?? '' ) );
			} else {
				$a_val = strtolower( $a[ $orderby ] ?? '' );
				$b_val = strtolower( $b[ $orderby ] ?? '' );
			}
			if ( $a_val === $b_val ) {
				return 0; }
			$cmp = ( $a_val < $b_val ) ? -1 : 1;
			return ( 'desc' === $order ) ? -$cmp : $cmp;
		}
	);

	// Pagination calculation
	$total_items  = count( $filtered );
	$total_pages  = (int) ceil( $total_items / max( 1, $per_page ) );
	$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$paged_rows   = array_slice( $filtered, ( $current_page - 1 ) * $per_page, $per_page );

	// Helper: Order-Link
	$umami_events_sort_link = function ( $label, $col ) {
		$current = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'event';
		$order   = isset( $_GET['order'] ) ? strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : 'asc';
		$next    = ( $current === $col && $order === 'asc' ) ? 'desc' : 'asc';
		$url     = add_query_arg(
			array(
				'orderby' => $col,
				'order'   => $next,
			)
		);
		$arrow   = ( $current === $col ) ? ( ' <span style="font-size:0.9em;">' . ( $order === 'asc' ? '▲' : '▼' ) . '</span>' ) : '';
		return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . $arrow . '</a>';
	};

	if ( empty( $events ) ) {
		echo '<div class="notice notice-info" style="margin:20px 0; padding:12px 16px 12px 12px; display:flex; align-items:center; gap:12px;">';
		echo '<span class="dashicons dashicons-info" style="font-size:22px; color:#2271b1;"></span>';
		echo '<div>';
		echo '<strong style="color:#2271b1; font-size:15px;">' . esc_html__( 'No events found', 'umami-connect' ) . '</strong>';
		echo '<p style="margin:4px 0 0 0;">' . esc_html__( 'Start adding custom events by editing blocks in the Gutenberg editor and configuring Umami tracking in the block inspector.', 'umami-connect' ) . '</p>';
		echo '</div>';
		echo '</div>';
	} else {
		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr>';
		foreach ( $columns as $column_key => $column_name ) {
			if ( in_array( $column_key, $hidden_columns, true ) ) {
				continue; }
			$sortable_key = $column_key;
			if ( 'post' === $column_key ) {
				$sortable_key = 'post_title'; }
			if ( 'integration' === $column_key ) {
				$sortable_key = 'integration_label'; }
			if ( in_array( $column_key, array( 'event', 'post', 'block_type', 'label', 'integration' ), true ) ) {
				echo '<th scope="col" class="manage-column column-' . esc_attr( $column_key ) . '">' . $umami_events_sort_link( $column_name, $sortable_key ) . '</th>';
			} else {
				echo '<th scope="col" class="manage-column column-' . esc_attr( $column_key ) . '">' . esc_html( $column_name ) . '</th>';
			}
		}
		echo '</tr></thead>';
		echo '<tfoot><tr>';
		foreach ( $columns as $column_key => $column_name ) {
			if ( in_array( $column_key, $hidden_columns, true ) ) {
				continue; }
			$sortable_key = $column_key;
			if ( 'post' === $column_key ) {
				$sortable_key = 'post_title'; }
			if ( 'integration' === $column_key ) {
				$sortable_key = 'integration_label'; }
			if ( in_array( $column_key, array( 'event', 'post', 'block_type', 'label', 'integration' ), true ) ) {
				echo '<th scope="col" class="manage-column column-' . esc_attr( $column_key ) . '">' . $umami_events_sort_link( $column_name, $sortable_key ) . '</th>';
			} else {
				echo '<th scope="col" class="manage-column column-' . esc_attr( $column_key ) . '">' . esc_html( $column_name ) . '</th>';
			}
		}
		echo '</tr></tfoot>';
		echo '<tbody>';

		// Default WP List Table empty state row if no items on this page (after filters/search)
		$visible_count = 0;
		foreach ( $columns as $column_key => $column_name ) {
			if ( in_array( $column_key, $hidden_columns, true ) ) {
				continue;
			}
			++$visible_count;
		}

		if ( empty( $paged_rows ) ) {
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . (int) $visible_count . '">' . esc_html__( 'No events found.', 'umami-connect' ) . '</td></tr>';
		} else {
			foreach ( $paged_rows as $row ) {
				$block_type  = $row['block_type'] ?? '';
				$block_label = $block_labels[ $block_type ] ?? $block_type;
				$block_index = $row['block_index'] ?? '';
				$event_type  = $row['event_type'] ?? 'button';
				$is_tracked  = isset( $row['is_tracked'] ) ? (bool) $row['is_tracked'] : true;
				$post_type   = ! empty( $row['post_id'] ) ? get_post_type( $row['post_id'] ) : '';

				echo '<tr>';

				if ( ! in_array( 'event', $hidden_columns, true ) ) {
					echo '<td class="event column-event has-row-actions column-primary">';
					if ( $is_tracked ) {
						echo '<strong><code>' . esc_html( (string) $row['event'] ) . '</code></strong>';
					} else {
						echo '<strong style="color:#999;"><em>' . esc_html( (string) $row['event'] ) . '</em></strong>';
					}

					// Row actions
					$actions = array();
					if ( ! empty( $row['edit_link'] ) ) {
						$actions['edit'] = '<a href="' . esc_url( $row['edit_link'] ) . '" target="_blank">' . esc_html( $row['edit_label'] ?? __( 'Edit', 'umami-connect' ) ) . '</a>';
					} elseif ( ! empty( $row['post_id'] ) && $block_index ) {
						$edit_label      = ( 'page' === $post_type ) ? __( 'Edit Page', 'umami-connect' ) : __( 'Edit Post', 'umami-connect' );
						$actions['edit'] = '<a href="' . esc_url( get_edit_post_link( (int) $row['post_id'] ) ) . '" target="_blank">' . esc_html( $edit_label ) . '</a>';
					}
					$is_integration = is_string( $event_type ) && strpos( $event_type, 'integration_' ) === 0;
					if ( $is_tracked && $event_type !== 'none' && ( $block_index || $is_integration ) ) {
						// For integrations, use form_id if available, otherwise post_id
						$delete_id         = $is_integration && isset( $row['form_id'] ) ? $row['form_id'] : ( $row['post_id'] ?? 0 );
						$actions['delete'] = '<a href="#" class="delete-event submitdelete" data-post-id="' . esc_attr( (string) $delete_id ) . '" data-block-index="' . esc_attr( (string) $block_index ) . '" data-event-type="' . esc_attr( (string) $event_type ) . '" style="color:#b32d2e;">' . esc_html__( 'Delete', 'umami-connect' ) . '</a>';
					}
					if ( ! empty( $actions ) ) {
						echo '<div class="row-actions">' . implode( ' | ', $actions ) . '</div>';
					}
					echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( 'Show more details', 'umami-connect' ) . '</span></button>';
					echo '</td>';
				}

				if ( ! in_array( 'integration', $hidden_columns, true ) ) {
					$label = isset( $row['integration_label'] ) ? $row['integration_label'] : ( isset( $row['integration'] ) ? $row['integration'] : 'Core' );
					$color = isset( $row['integration_color'] ) ? $row['integration_color'] : '#2271b1';
					$style = 'display:inline-block;padding:2px 8px;border-radius:10px;font-size:12px;background:' . esc_attr( $color ) . ';color:#fff;';
					echo '<td class="column-integration"><span style="' . esc_attr( $style ) . '">' . esc_html( (string) $label ) . '</span></td>';
				}

				if ( ! in_array( 'post', $hidden_columns, true ) ) {
					echo '<td class="column-post">';
					$title = '';
					if ( ! empty( $row['post_title'] ) ) {
						$title = trim( (string) $row['post_title'] );
					} elseif ( ! empty( $row['edit_label'] ) ) {
						$title = trim( (string) $row['edit_label'] );
					}

					if ( ! empty( $title ) ) {
						echo esc_html( $title );
					} elseif ( ! empty( $row['post_id'] ) ) {
						echo '<em>' . esc_html__( '(no title)', 'umami-connect' ) . '</em>';
					} else {
						echo '&mdash;';
					}
					echo '</td>';
				}

				if ( ! in_array( 'block_type', $hidden_columns, true ) ) {
					echo '<td class="column-block_type">' . esc_html( (string) $block_label ) . '</td>';
				}

				if ( ! in_array( 'label', $hidden_columns, true ) ) {
					echo '<td class="column-label">' . esc_html( (string) ( $row['label'] ?? '' ) ) . '</td>';
				}

				if ( ! in_array( 'data_pairs', $hidden_columns, true ) ) {
					echo '<td class="column-data_pairs">';
					if ( ! empty( $row['data_pairs'] ) && is_array( $row['data_pairs'] ) ) {
						$pairs = array();
						foreach ( $row['data_pairs'] as $pair ) {
							if ( ! empty( $pair['key'] ) ) {
								$pairs[] = '<li><span>' . esc_html( (string) $pair['key'] ) . '</span>: <b>' . esc_html( (string) ( $pair['value'] ?? '' ) ) . '</b></li>';
							}
						}
						if ( ! empty( $pairs ) ) {
							echo '<ul style="margin:0 0 0 18px; padding:0; list-style:disc;">' . implode( '', $pairs ) . '</ul>';
						} else {
							echo '<span style="color:#888;">&ndash;</span>';
						}
					} else {
						echo '<span style="color:#888;">&ndash;</span>';
					}
					echo '</td>';
				}

				echo '</tr>';
			}
		}

		echo '</tbody></table>';

		// Pagination bottom
		if ( $total_pages > 1 ) {
			echo '<div class="tablenav bottom">';
			echo '<div class="alignleft actions bulkactions"></div>';
			echo '<div class="tablenav-pages">';
			/* translators: %s: Number of items. */
			echo '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items, 'umami-connect' ), number_format_i18n( $total_items ) ) . '</span>';

			$base = remove_query_arg( 'paged' );
			if ( $current_filter !== 'all' ) {
				$base = add_query_arg( 'filter', $current_filter, $base ); }
			if ( $search !== '' ) {
				$base = add_query_arg( 's', $search, $base ); }

			echo '<span class="pagination-links">';
			if ( $current_page > 1 ) {
				echo '<a class="first-page button" href="' . esc_url( $base ) . '"><span class="screen-reader-text">' . esc_html__( 'First page', 'umami-connect' ) . '</span><span aria-hidden="true">&laquo;</span></a>';
				echo '<a class="prev-page button" href="' . esc_url( add_query_arg( 'paged', $current_page - 1, $base ) ) . '"><span class="screen-reader-text">' . esc_html__( 'Previous page', 'umami-connect' ) . '</span><span aria-hidden="true">‹</span></a>';
			} else {
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
			}

			echo '<span class="paging-input">';
			echo '<label for="current-page-selector" class="screen-reader-text">' . esc_html__( 'Current Page', 'umami-connect' ) . '</label>';
			echo '<input class="current-page" id="current-page-selector" type="text" name="paged" value="' . (int) $current_page . '" size="' . strlen( (string) $total_pages ) . '" aria-describedby="table-paging" />';
			echo '<span class="tablenav-paging-text"> ' . esc_html__( 'of', 'umami-connect' ) . ' <span class="total-pages">' . number_format_i18n( $total_pages ) . '</span></span>';
			echo '</span>';

			if ( $current_page < $total_pages ) {
				echo '<a class="next-page button" href="' . esc_url( add_query_arg( 'paged', $current_page + 1, $base ) ) . '"><span class="screen-reader-text">' . esc_html__( 'Next page', 'umami-connect' ) . '</span><span aria-hidden="true">›</span></a>';
				echo '<a class="last-page button" href="' . esc_url( add_query_arg( 'paged', $total_pages, $base ) ) . '"><span class="screen-reader-text">' . esc_html__( 'Last page', 'umami-connect' ) . '</span><span aria-hidden="true">&raquo;</span></a>';
			} else {
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
			}
			echo '</span>';
			echo '</div><br class="clear" />';
			echo '</div>';
		}
	}
		echo '<form id="delete-event-form" method="post" style="display:none;">';
		wp_nonce_field( 'umami_delete_event', 'umami_delete_nonce' );
		echo '<input type="hidden" id="delete-post-id" name="post_id" value="">';
		echo '<input type="hidden" id="delete-block-index" name="block_index" value="">';
		echo '<input type="hidden" id="delete-event-type" name="event_type" value="">';
		echo '<input type="hidden" name="umami_delete_event" value="1">';
		echo '</form>';

		wp_enqueue_script( 'wp-a11y' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-dialog' );
	?>

		<div id="umami-delete-dialog" title="<?php esc_attr_e( 'Confirm Event Deletion', 'umami-connect' ); ?>" style="display: none;">
			<div style="padding: 8px 0;">
				<p style="margin: 0 0 12px; font-size: 14px; line-height: 1.5;">
					<?php esc_html_e( 'Are you sure you want to delete this event tracking configuration?', 'umami-connect' ); ?>
				</p>
				<p style="margin: 0; color: #d63638; font-weight: 600; font-size: 13px;">
					<?php esc_html_e( 'This action cannot be undone.', 'umami-connect' ); ?>
				</p>
			</div>
		</div>

	<script type="text/javascript">
		jQuery(document).ready(function($) {
			var currentDeleteData = {};

			$('#umami-delete-dialog').dialog({
				autoOpen: false,
				modal: true,
				draggable: false,
				resizable: false,
				width: 450,
				height: 'auto',
				dialogClass: 'wp-dialog umami-delete-dialog',
				closeOnEscape: true,
				buttons: [
					{
						text: '<?php echo esc_js( __( 'Cancel', 'umami-connect' ) ); ?>',
						class: 'button',
						style: 'background: #f6f7f7; border-color: #50575e; color: #50575e;',
						click: function() {
							$(this).dialog('close');
						}
					},
					{
						text: '<?php echo esc_js( __( 'Delete Event', 'umami-connect' ) ); ?>',
						class: 'button button-primary',
						style: 'background: #d63638; border-color: #d63638;',
						click: function() {
							$('#delete-post-id').val(currentDeleteData.postId);
							$('#delete-block-index').val(currentDeleteData.blockIndex);
							$('#delete-event-type').val(currentDeleteData.eventType);

							$(this).dialog('close');
							$('#delete-event-form').submit();
						}
					}
				],
				open: function() {
					$('.ui-dialog-buttonset .button:first').focus();

					$('.umami-delete-dialog .ui-dialog-titlebar').css({
						'background': '#f9f9f9',
						'border-bottom': '1px solid #e2e4e7',
						'padding': '12px 16px'
					});

					$('.umami-delete-dialog .ui-dialog-buttonpane').css({
						'background': '#f9f9f9',
						'border-top': '1px solid #e2e4e7',
						'padding': '12px 16px'
					});
				}
			});

			$('.delete-event').on('click', function(e) {
				e.preventDefault();

				currentDeleteData = {
					postId: $(this).data('post-id'),
					blockIndex: $(this).data('block-index'),
					eventType: $(this).data('event-type')
				};

				$('#umami-delete-dialog').dialog('open');
			});

			// Default WP List Table pagination events are handled by core.
		});
		</script>
		<?php
		echo '</div>';
}

add_filter(
	'umami_connect_get_all_events',
	function ( $events, $per_page = 25 ) {
		$result = is_array( $events ) ? $events : array();

		// Fetch all published posts and pages; pagination is handled by the List Table.
		$posts = get_posts(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		function find_umami_events( $blocks, &$result, $post_id, $post_title, $parent_path = '' ) {
			foreach ( $blocks as $idx => $block ) {
				$block_path = $parent_path === '' ? (string) $idx : $parent_path . '.' . $idx;
				$block_name = $block['blockName'] ?? '';

				$trackable_blocks = array( 'core/button', 'core/paragraph', 'core/heading', 'core/quote', 'core/pullquote' );
				$is_trackable     = in_array( $block_name, $trackable_blocks );

				if ( ! empty( $block['attrs']['umamiEvent'] ) ) {
					$event = trim( $block['attrs']['umamiEvent'] );
					$label = '';
					if ( ! empty( $block['attrs']['text'] ) ) {
						$label = $block['attrs']['text'];
					} elseif ( ! empty( $block['innerHTML'] ) ) {
						$label = wp_strip_all_tags( $block['innerHTML'] );
					}
					$data_pairs = array();
					if ( ! empty( $block['attrs']['umamiDataPairs'] ) && is_array( $block['attrs']['umamiDataPairs'] ) ) {
						$data_pairs = $block['attrs']['umamiDataPairs'];
					}
					$result[] = array(
						'event'             => $event,
						'post_id'           => $post_id,
						'post_title'        => $post_title,
						'block_type'        => $block_name,
						'label'             => $label,
						'data_pairs'        => $data_pairs,
						'block_index'       => $block_path,
						'event_type'        => 'button',
						'is_tracked'        => true,
						'integration'       => 'gutenberg',
						'integration_label' => 'Gutenberg',
						'integration_color' => '#2271b1',
					);
				}
				if ( ! empty( $block['attrs']['umamiLinkEvents'] ) && is_array( $block['attrs']['umamiLinkEvents'] ) ) {
					foreach ( $block['attrs']['umamiLinkEvents'] as $ev ) {
						$event_name = isset( $ev['event'] ) ? trim( (string) $ev['event'] ) : '';
						$pairs      = array();
						if ( ! empty( $ev['pairs'] ) && is_array( $ev['pairs'] ) ) {
							$pairs = $ev['pairs'];
						}
						if ( $event_name !== '' || ! empty( $pairs ) ) {
							$link_text = isset( $ev['linkText'] ) ? (string) $ev['linkText'] : '';
							$link_url  = isset( $ev['linkUrl'] ) ? (string) $ev['linkUrl'] : '';
							$label     = trim( $link_text ) . ( $link_url ? ' → ' . $link_url : '' );
							$result[]  = array(
								'event'             => $event_name !== '' ? $event_name : 'link_click',
								'post_id'           => $post_id,
								'post_title'        => $post_title,
								'block_type'        => $block_name,
								'label'             => $label,
								'data_pairs'        => $pairs,
								'block_index'       => $block_path,
								'event_type'        => 'link',
								'is_tracked'        => true,
								'integration'       => 'gutenberg',
								'integration_label' => 'Gutenberg',
								'integration_color' => '#2271b1',
							);
						}
					}
				}

				if ( $is_trackable && empty( $block['attrs']['umamiEvent'] ) && empty( $block['attrs']['umamiLinkEvents'] ) ) {
					$label = '';
					if ( ! empty( $block['attrs']['text'] ) ) {
						$label = $block['attrs']['text'];
					} elseif ( ! empty( $block['innerHTML'] ) ) {
						$label = wp_strip_all_tags( $block['innerHTML'] );
					}

					if ( $block_name === 'core/button' && ! empty( $label ) ) {
						$result[] = array(
							'event'             => '(Candidate)',
							'post_id'           => $post_id,
							'post_title'        => $post_title,
							'block_type'        => $block_name,
							'label'             => $label,
							'data_pairs'        => array(),
							'block_index'       => $block_path,
							'event_type'        => 'none',
							'is_tracked'        => false,
							'integration'       => 'gutenberg',
							'integration_label' => 'Gutenberg',
							'integration_color' => '#2271b1',
						);
					} elseif ( $block_name !== 'core/button' && ! empty( $block['innerHTML'] ) && strpos( $block['innerHTML'], '<a ' ) !== false ) {
						$clean_html = wp_kses_post( $block['innerHTML'] );
						if ( preg_match_all( '/<a\s+[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/i', $clean_html, $matches, PREG_SET_ORDER ) ) {
							foreach ( $matches as $match ) {
								$link_url   = esc_url_raw( $match[1] );
								$link_text  = wp_strip_all_tags( $match[2] );
								$link_label = trim( $link_text ) . ( $link_url ? ' → ' . $link_url : '' );

								if ( ! empty( $link_text ) ) {
									$result[] = array(
										'event'       => '(Candidate)',
										'post_id'     => $post_id,
										'post_title'  => $post_title,
										'block_type'  => $block_name,
										'label'       => $link_label,
										'data_pairs'  => array(),
										'block_index' => $block_path,
										'event_type'  => 'none',
										'is_tracked'  => false,
										'integration' => 'gutenberg',
										'integration_label' => 'Gutenberg',
										'integration_color' => '#2271b1',
									);
								}
							}
						}
					}
				}

				if ( ! empty( $block['innerBlocks'] ) ) {
					find_umami_events( $block['innerBlocks'], $result, $post_id, $post_title, $block_path );
				}
			}
		}

		foreach ( $posts as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue; }
			$blocks = parse_blocks( $post->post_content );
			find_umami_events( $blocks, $result, $post->ID, $post->post_title );
		}
		return $result;
	},
	10,
	2
);

/**
 * Delete an event from a specific block - HTML cleaning approach
 */
function umami_connect_delete_event_from_block( $post_id, $block_index, $event_type = 'button' ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return false;
	}

	$content = $post->post_content;
	$blocks  = parse_blocks( $content );
	$changed = false;

	remove_event_from_block_by_path( $blocks, $block_index, $event_type, $changed );

	if ( $changed ) {
		$new_content = serialize_blocks( $blocks );

		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $new_content,
			),
			true
		);

		if ( ! is_wp_error( $result ) ) {
			clean_post_cache( $post_id );

			wp_cache_delete( $post_id, 'posts' );
			wp_cache_delete( $post_id, 'post_meta' );

			if ( function_exists( 'wp_cache_flush_group' ) ) {
				wp_cache_flush_group( 'blocks' );
			}

			return true;
		}
	}

	return false;
}



/**
 * Recursively find and remove event from block by path
 */
function remove_event_from_block_by_path( &$blocks, $target_path, $event_type, &$changed, $current_path = '', $depth = 0 ) {
	if ( $depth > UMAMI_CONNECT_MAX_BLOCK_NESTING_DEPTH ) {
		return;
	}

	if ( ! is_array( $blocks ) ) {
		return;
	}

	foreach ( $blocks as $idx => &$block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$block_path = $current_path === '' ? (string) $idx : $current_path . '.' . $idx;

		if ( $block_path === $target_path ) {
			if ( $event_type === 'button' ) {
				$attrs_changed = false;
				if ( isset( $block['attrs']['umamiEvent'] ) ) {
					unset( $block['attrs']['umamiEvent'] );
					$attrs_changed = true;
				}
				if ( isset( $block['attrs']['umamiDataPairs'] ) ) {
					unset( $block['attrs']['umamiDataPairs'] );
					$attrs_changed = true;
				}

				if ( $attrs_changed ) {
					if ( isset( $block['innerHTML'] ) ) {
						$clean_html         = $block['innerHTML'];
						$clean_html         = preg_replace( '/\s+data-umami-event="[^"]*"/', '', $clean_html );
						$clean_html         = preg_replace( '/\s+data-umami-event-[^=\s]*="[^"]*"/', '', $clean_html );
						$block['innerHTML'] = $clean_html;
					}

					if ( isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
						$inner_content_count = count( $block['innerContent'] );
						for ( $i = 0; $i < $inner_content_count; $i++ ) {
							if ( is_string( $block['innerContent'][ $i ] ) ) {
								$clean_content               = $block['innerContent'][ $i ];
								$clean_content               = preg_replace( '/\s+data-umami-event="[^"]*"/', '', $clean_content );
								$clean_content               = preg_replace( '/\s+data-umami-event-[^=\s]*="[^"]*"/', '', $clean_content );
								$block['innerContent'][ $i ] = $clean_content;
							}
						}
					}
					$changed = true;
				}
			} elseif ( $event_type === 'link' ) {
				$attrs_changed = false;
				if ( isset( $block['attrs']['umamiLinkEvents'] ) ) {
					unset( $block['attrs']['umamiLinkEvents'] );
					$attrs_changed = true;
				}

				if ( $attrs_changed ) {
					if ( isset( $block['innerHTML'] ) ) {
						$clean_html = $block['innerHTML'];

						$clean_html = preg_replace( '/\s+rel="[^"]*umami:[^"]*"/', '', $clean_html );
						$clean_html = preg_replace( '/\s+rel="[^"]*umami--event--[^"]*"/', '', $clean_html );
						$clean_html = preg_replace( '/\s+data-umami-event="[^"]*"/', '', $clean_html );
						$clean_html = preg_replace( '/\s+data-umami-event-[^=\s]*="[^"]*"/', '', $clean_html );

						$clean_html = preg_replace_callback(
							'/\s+rel="([^"]*)"/',
							function ( $matches ) {
								$rel_value    = $matches[1];
								$tokens       = preg_split( '/\s+/', trim( $rel_value ) );
								$clean_tokens = array_filter(
									$tokens,
									function ( $token ) {
										return ! preg_match( '/^umami[:\-]/', $token );
									}
								);
								$clean_rel    = implode( ' ', $clean_tokens );

								if ( empty( trim( $clean_rel ) ) ) {
									return '';
								}
								return ' rel="' . $clean_rel . '"';
							},
							$clean_html
						);

						$clean_html = preg_replace( '/\s+rel=""/', '', $clean_html );
						$clean_html = preg_replace( '/\s+rel="\s*"/', '', $clean_html );

						$block['innerHTML'] = $clean_html;
					}

					if ( isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
						$inner_content_count = count( $block['innerContent'] );
						for ( $i = 0; $i < $inner_content_count; $i++ ) {
							if ( is_string( $block['innerContent'][ $i ] ) ) {
								$clean_content = $block['innerContent'][ $i ];

								$clean_content = preg_replace( '/\s+rel="[^"]*umami:[^"]*"/', '', $clean_content );
								$clean_content = preg_replace( '/\s+rel="[^"]*umami--event--[^"]*"/', '', $clean_content );
								$clean_content = preg_replace( '/\s+data-umami-event="[^"]*"/', '', $clean_content );
								$clean_content = preg_replace( '/\s+data-umami-event-[^=\s]*="[^"]*"/', '', $clean_content );

								$clean_content = preg_replace_callback(
									'/\s+rel="([^"]*)"/',
									function ( $matches ) {
										$rel_value    = $matches[1];
										$tokens       = preg_split( '/\s+/', trim( $rel_value ) );
										$clean_tokens = array_filter(
											$tokens,
											function ( $token ) {
												return ! preg_match( '/^umami[:\-]/', $token );
											}
										);
										$clean_rel    = implode( ' ', $clean_tokens );

										if ( empty( trim( $clean_rel ) ) ) {
											return '';
										}
										return ' rel="' . $clean_rel . '"';
									},
									$clean_content
								);

								$clean_content = preg_replace( '/\s+rel=""/', '', $clean_content );
								$clean_content = preg_replace( '/\s+rel="\s*"/', '', $clean_content );

								$block['innerContent'][ $i ] = $clean_content;
							}
						}
					}
					$changed = true;
				}
			}
			return;
		}

		if ( ! $changed && ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			if ( strpos( $target_path, $block_path . '.' ) === 0 ) {
				remove_event_from_block_by_path( $block['innerBlocks'], $target_path, $event_type, $changed, $block_path, $depth + 1 );
			}
		}
	}
}
