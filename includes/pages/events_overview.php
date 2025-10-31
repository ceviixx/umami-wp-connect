<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function umami_connect_render_events_overview_page() {
	// Handle delete action
	if ( isset( $_POST['umami_delete_event'] ) && check_admin_referer( 'umami_delete_event', 'umami_delete_nonce' ) ) {
		$post_id     = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$block_index = isset( $_POST['block_index'] ) ? sanitize_text_field( wp_unslash( $_POST['block_index'] ) ) : '';
		$event_type  = isset( $_POST['event_type'] ) ? sanitize_text_field( wp_unslash( $_POST['event_type'] ) ) : 'button';

		if ( $post_id && $block_index ) {
			$result = umami_connect_delete_event_from_block( $post_id, $block_index, $event_type );
			if ( $result ) {
				echo '<div class="notice notice-success is-dismissible"><p><strong>Event deleted successfully.</strong></p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Could not delete event.</p></div>';
			}
		}
	}

	echo '<div class="wrap">';
	echo '<h1><b>umami Connect</b></h1>
        <h3>Event overview</h3>';
	echo '<p>Overview of all configured tracking events. Use "Edit Page/Post" to manage events in Gutenberg.</p>';
	
	$events = apply_filters( 'umami_connect_get_all_events', array() );
	
	// Calculate filter counts
	$all_count = count( $events );
	$events_count = 0;
	$candidates_count = 0;
	
	foreach ( $events as $event ) {
		if ( isset( $event['is_tracked'] ) && $event['is_tracked'] ) {
			$events_count++;
		} else {
			$candidates_count++;
		}
	}
	
	// Get current filter
	$current_filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : 'all';
	
	// Auto-switch to 'all' if selected filter would show empty results
	$original_filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : 'all';
	$filter_switched = false;
	
	if ( $current_filter === 'events' && $events_count === 0 ) {
		$current_filter = 'all';
		$filter_switched = ( $original_filter === 'events' );
	}
	if ( $current_filter === 'candidates' && $candidates_count === 0 ) {
		$current_filter = 'all';
		$filter_switched = ( $original_filter === 'candidates' );
	}
	
	// Show notice if filter was automatically switched
	if ( $filter_switched ) {
		$switched_from = $original_filter === 'events' ? 'Events' : 'Candidates';
		echo '<div class="notice notice-warning is-dismissible" style="margin:15px 0;">';
		echo '<p><strong>Filter switched to "All":</strong> The "' . esc_html( $switched_from ) . '" filter shows no results, so all items are displayed instead.</p>';
		echo '</div>';
	}
	
	if ( empty( $events ) ) {
		echo '<div class="notice notice-info" style="margin:20px 0; padding:12px 16px 12px 12px; display:flex; align-items:center; gap:12px;">';
		echo '<span class="dashicons dashicons-info" style="font-size:22px; color:#2271b1;"></span>';
		echo '<div>';
		echo '<strong style="color:#2271b1; font-size:15px;">No events found</strong>';
		echo '<p style="margin:4px 0 0 0;">Start adding custom events by editing blocks in the Gutenberg editor and configuring Umami tracking in the block inspector.</p>';
		echo '</div>';
		echo '</div>';
	} else {
		// Combined filter and search bar in one row
		echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; min-height: 32px;">';
		
		// Filter links on the left
		echo '<ul class="subsubsub" style="margin: 0;">';
		
		// All filter
		$all_class = $current_filter === 'all' ? 'current' : '';
		$all_url = remove_query_arg( 'filter' );
		echo '<li class="all"><a href="' . esc_url( $all_url ) . '" class="' . esc_attr( $all_class ) . '">All <span class="count">(' . $all_count . ')</span></a>';
		
		if ( $events_count > 0 || $candidates_count > 0 ) {
			echo ' | ';
		}
		echo '</li>';
		
		// Events filter (configured tracking)
		if ( $events_count > 0 ) {
			$events_class = $current_filter === 'events' ? 'current' : '';
			$events_url = add_query_arg( 'filter', 'events' );
			echo '<li class="events"><a href="' . esc_url( $events_url ) . '" class="' . esc_attr( $events_class ) . '">Events <span class="count">(' . $events_count . ')</span></a>';
			
			if ( $candidates_count > 0 ) {
				echo ' | ';
			}
			echo '</li>';
		}
		
		// Candidates filter (potential tracking targets)
		if ( $candidates_count > 0 ) {
			$candidates_class = $current_filter === 'candidates' ? 'current' : '';
			$candidates_url = add_query_arg( 'filter', 'candidates' );
			echo '<li class="candidates"><a href="' . esc_url( $candidates_url ) . '" class="' . esc_attr( $candidates_class ) . '">Candidates <span class="count">(' . $candidates_count . ')</span></a></li>';
		}
		
		echo '</ul>';
		
		// Search box on the right
		echo '<form method="get" style="margin: 0;">';
		echo '<input type="hidden" name="page" value="umami_connect_events_overview">';
		// Preserve current filter in search
		if ( $current_filter !== 'all' ) {
			echo '<input type="hidden" name="filter" value="' . esc_attr( $current_filter ) . '">';
		}
		echo '<div class="search-box" style="display: flex; align-items: center; gap: 8px;">';
		echo '<label class="screen-reader-text" for="event-search-input">Search Events:</label>';
		echo '<input type="search" id="event-search-input" name="s" value="' . esc_attr( isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '' ) . '" placeholder="Search events..." style="width: 200px;" />';
		echo '<input type="submit" id="search-submit" class="button" value="Search">';
		echo '</div>';
		echo '</form>';
		
		echo '</div>';
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$search   = trim( strtolower( $search ) );
		$filtered = array();
		
		foreach ( $events as $row ) {
			// Apply search filter
			$rowtext = strtolower( $row['event'] . ' ' . $row['post_title'] . ' ' . $row['label'] );
			if ( $search !== '' && strpos( $rowtext, $search ) === false ) {
				continue;
			}
			
			// Apply status filter
			$is_tracked = isset( $row['is_tracked'] ) ? $row['is_tracked'] : true;
			if ( $current_filter === 'events' && ! $is_tracked ) {
				continue;
			}
			if ( $current_filter === 'candidates' && $is_tracked ) {
				continue;
			}
			
			$filtered[] = $row;
		}
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'event';
		$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'asc';
		$order   = strtolower( $order );
		usort(
			$filtered,
			function ( $a, $b ) use ( $orderby, $order ) {
				if ( $orderby === 'block_type' ) {
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
						$a_val        = strtolower( $block_labels[ $a['block_type'] ?? '' ] ?? ( $a['block_type'] ?? '' ) );
						$b_val        = strtolower( $block_labels[ $b['block_type'] ?? '' ] ?? ( $b['block_type'] ?? '' ) );
				} else {
					$a_val = strtolower( $a[ $orderby ] ?? '' );
					$b_val = strtolower( $b[ $orderby ] ?? '' );
				}
				if ( $a_val === $b_val ) {
					return 0;
				}
				if ( $order === 'desc' ) {
					return ( $a_val < $b_val ) ? 1 : -1;
				}
				return ( $a_val < $b_val ) ? -1 : 1;
			}
		);
		function sort_link( $label, $col ) {
			$current = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'event';
			$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'asc';
			$order   = strtolower( $order );
			$next    = ( $current === $col && $order === 'asc' ) ? 'desc' : 'asc';
			$url     = add_query_arg(
				array(
					'orderby' => $col,
					'order'   => $next,
				)
			);
			$arrow   = ( $current === $col ) ? ( ' <span style="font-size:0.9em;">' . ( $order === 'asc' ? '▲' : '▼' ) . '</span>' ) : '';
			return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . $arrow . '</a>';
		}
		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr>';
		echo '<th scope="col">' . sort_link( 'Event name', 'event' ) . '</th>';
		echo '<th scope="col">' . sort_link( 'Page/Post', 'post_title' ) . '</th>';
		echo '<th scope="col">' . sort_link( 'Block type', 'block_type' ) . '</th>';
		echo '<th scope="col">' . sort_link( 'Block label', 'label' ) . '</th>';
		echo '<th scope="col">Data Key-Value-Pairs</th>';
		echo '</tr></thead>';
		echo '<tfoot><tr>';
		echo '<th scope="col">' . sort_link( 'Event name', 'event' ) . '</th>';
		echo '<th scope="col">' . sort_link( 'Page/Post', 'post_title' ) . '</th>';
		echo '<th scope="col">' . sort_link( 'Block type', 'block_type' ) . '</th>';
		echo '<th scope="col">' . sort_link( 'Block label', 'label' ) . '</th>';
		echo '<th scope="col">Data Key-Value-Pairs</th>';
		echo '</tr></tfoot>';
		echo '<tbody>';
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
		foreach ( $filtered as $row ) {
			$block_type   = $row['block_type'] ?? '';
			$block_label  = $block_labels[ $block_type ] ?? $block_type;
			$block_index  = $row['block_index'] ?? '';
			$event_type   = $row['event_type'] ?? 'button';
			$is_tracked   = isset( $row['is_tracked'] ) ? $row['is_tracked'] : true;
			$post_type    = get_post_type( $row['post_id'] );
			
			echo '<tr>';
			
			// Event name column with hover actions
			echo '<td class="title column-title has-row-actions column-primary">';
			
			if ( $is_tracked ) {
				echo '<strong><code>' . esc_html( $row['event'] ) . '</code></strong>';
			} else {
				echo '<strong style="color:#999;"><em>' . esc_html( $row['event'] ) . '</em></strong>';
			}
			
			// Row actions (hover menu)
			if ( $block_index ) {
				echo '<div class="row-actions">';
				
				// Edit in Gutenberg link for both events and candidates
				if ( $post_type === 'page' ) {
					$edit_label = 'Edit Page';
				} else {
					$edit_label = 'Edit Post';
				}
				echo '<span class="edit">';
				echo '<a href="' . esc_url( get_edit_post_link( $row['post_id'] ) ) . '" target="_blank">' . esc_html( $edit_label ) . '</a>';
				
				// Delete link (only for configured events)
				if ( $is_tracked && $event_type !== 'none' ) {
					echo ' | </span>';
					echo '<span class="trash">';
					echo '<a href="#" class="delete-event submitdelete" data-post-id="' . esc_attr( $row['post_id'] ) . '" data-block-index="' . esc_attr( $block_index ) . '" data-event-type="' . esc_attr( $event_type ) . '" style="color:#b32d2e;">Delete</a>';
				}
				echo '</span>';
				echo '</div>';
			}
			echo '<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>';
			echo '</td>';
			
			// Page/Post column
			echo '<td>';
			echo '<a href="' . esc_url( get_edit_post_link( $row['post_id'] ) ) . '" target="_blank">' . esc_html( $row['post_title'] ) . '</a>';
			echo '</td>';
			
			echo '<td>' . esc_html( $block_label ) . '</td>';
			echo '<td>' . esc_html( $row['label'] ) . '</td>';
			echo '<td>';
			if ( ! empty( $row['data_pairs'] ) && is_array( $row['data_pairs'] ) ) {
				$pairs = array();
				foreach ( $row['data_pairs'] as $pair ) {
					if ( ! empty( $pair['key'] ) ) {
						$pairs[] = '<li><span>' . esc_html( $pair['key'] ) . '</span>: <b>' . esc_html( $pair['value'] ) . '</b></li>';
					}
				}
				if ( ! empty( $pairs ) ) {
					echo '<ul style="margin:0 0 0 18px; padding:0; list-style:disc;">' . implode( '', $pairs ) . '</ul>';
				} else {
					echo '<span style="color:#888;">–</span>';
				}
			} else {
				echo '<span style="color:#888;">–</span>';
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		
		// Hidden form for delete actions
		echo '<form id="delete-event-form" method="post" style="display:none;">';
		wp_nonce_field( 'umami_delete_event', 'umami_delete_nonce' );
		echo '<input type="hidden" id="delete-post-id" name="post_id" value="">';
		echo '<input type="hidden" id="delete-block-index" name="block_index" value="">';
		echo '<input type="hidden" id="delete-event-type" name="event_type" value="">';
		echo '<input type="hidden" name="umami_delete_event" value="1">';
		echo '</form>';
		
		// JavaScript for delete actions
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Handle delete links - no confirmation like WordPress posts/pages
			$('.delete-event').on('click', function(e) {
				e.preventDefault();
				
				$('#delete-post-id').val($(this).data('post-id'));
				$('#delete-block-index').val($(this).data('block-index'));
				$('#delete-event-type').val($(this).data('event-type'));
				$('#delete-event-form').submit();
			});
		});
		</script>
		<?php
	}
	echo '</div>';
}

add_filter(
	'umami_connect_get_all_events',
	function ( $events ) {
		global $wpdb;
		$result = array();
		$posts  = $wpdb->get_results( "SELECT ID, post_title, post_content FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type = 'post' OR post_type = 'page')" );

		function find_umami_events( $blocks, &$result, $post_id, $post_title, $parent_path = '' ) {
			foreach ( $blocks as $idx => $block ) {
				$block_path = $parent_path === '' ? (string) $idx : $parent_path . '.' . $idx;
				$block_name = $block['blockName'] ?? '';
				
				// Check if this is a trackable element (button, paragraph, etc.)
				$trackable_blocks = array( 'core/button', 'core/paragraph', 'core/heading', 'core/quote', 'core/pullquote' );
				$is_trackable = in_array( $block_name, $trackable_blocks );
				
				// Check for tracked button events
				if ( ! empty( $block['attrs']['umamiEvent'] ) ) {
					$event = trim( $block['attrs']['umamiEvent'] );
					$label = '';
					if ( ! empty( $block['attrs']['text'] ) ) {
						$label = $block['attrs']['text'];
					} elseif ( ! empty( $block['innerHTML'] ) ) {
						$label = strip_tags( $block['innerHTML'] );
					}
					$data_pairs = array();
					if ( ! empty( $block['attrs']['umamiDataPairs'] ) && is_array( $block['attrs']['umamiDataPairs'] ) ) {
						$data_pairs = $block['attrs']['umamiDataPairs'];
					}
					$result[] = array(
						'event'       => $event,
						'post_id'     => $post_id,
						'post_title'  => $post_title,
						'block_type'  => $block_name,
						'label'       => $label,
						'data_pairs'  => $data_pairs,
						'block_index' => $block_path,
						'event_type'  => 'button',
						'is_tracked'  => true,
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
									'event'       => $event_name !== '' ? $event_name : 'link_click',
									'post_id'     => $post_id,
									'post_title'  => $post_title,
									'block_type'  => $block_name,
									'label'       => $label,
									'data_pairs'  => $pairs,
									'block_index' => $block_path,
									'event_type'  => 'link',
									'is_tracked'  => true,
								);
						}
					}
				}
				
				// Add not-tracked elements - but only if they contain links or are buttons
				if ( $is_trackable && empty( $block['attrs']['umamiEvent'] ) && empty( $block['attrs']['umamiLinkEvents'] ) ) {
					$label = '';
					if ( ! empty( $block['attrs']['text'] ) ) {
						$label = $block['attrs']['text'];
					} elseif ( ! empty( $block['innerHTML'] ) ) {
						$label = strip_tags( $block['innerHTML'] );
					}
					
					// For buttons, always add them if they have content
					if ( $block_name === 'core/button' && ! empty( $label ) ) {
						$result[] = array(
							'event'       => '(Candidate)',
							'post_id'     => $post_id,
							'post_title'  => $post_title,
							'block_type'  => $block_name,
							'label'       => $label,
							'data_pairs'  => array(),
							'block_index' => $block_path,
							'event_type'  => 'none',
							'is_tracked'  => false,
						);
					}
					// For other blocks (paragraph, heading, etc.), only add if they contain links
					elseif ( $block_name !== 'core/button' && ! empty( $block['innerHTML'] ) && strpos( $block['innerHTML'], '<a ' ) !== false ) {
						// Extract link information from innerHTML
						if ( preg_match_all( '/<a\s+[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/i', $block['innerHTML'], $matches, PREG_SET_ORDER ) ) {
							foreach ( $matches as $match ) {
								$link_url = $match[1];
								$link_text = strip_tags( $match[2] );
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

		foreach ( $posts as $post ) {
			$blocks = parse_blocks( $post->post_content );
			find_umami_events( $blocks, $result, $post->ID, $post->post_title );
		}
		return $result;
	}
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
	$blocks = parse_blocks( $content );
	$changed = false;

	// Try the attribute approach first
	remove_event_from_block_by_path( $blocks, $block_index, $event_type, $changed );

	if ( $changed ) {
		// If we changed attributes, also clean the HTML to prevent validation issues
		$new_content = serialize_blocks( $blocks );
		
		// Clean any remaining Umami data attributes from the HTML
		if ( $event_type === 'button' ) {
			// Remove button event tracking
			$new_content = preg_replace('/\s+data-umami-event="[^"]*"/', '', $new_content);
			$new_content = preg_replace('/\s+data-umami-event-[^=]*="[^"]*"/', '', $new_content);
		} elseif ( $event_type === 'link' ) {
			// Remove link event tracking - comprehensive cleanup
			// Remove umami-specific rel attributes (including umami: prefix)
			$new_content = preg_replace('/\s+rel="[^"]*umami:[^"]*"/', '', $new_content);
			$new_content = preg_replace('/\s+rel="[^"]*umami--event--[^"]*"/', '', $new_content);
			$new_content = preg_replace('/\s+data-umami-event="[^"]*"/', '', $new_content);
			$new_content = preg_replace('/\s+data-umami-event-[^=\s]*="[^"]*"/', '', $new_content);
			
			// More aggressive rel cleanup - remove umami tokens from within rel attributes
			$new_content = preg_replace_callback('/\srel="([^"]*)"/', function($matches) {
				$rel_value = $matches[1];
				// Split by spaces, remove umami tokens, rejoin
				$tokens = preg_split('/\s+/', trim($rel_value));
				$clean_tokens = array_filter($tokens, function($token) {
					return !preg_match('/^umami[:\-]/', $token);
				});
				$clean_rel = implode(' ', $clean_tokens);
				
				// If no tokens left, remove the entire rel attribute
				if (empty(trim($clean_rel))) {
					return '';
				}
				return ' rel="' . $clean_rel . '"';
			}, $new_content);
			
			// Clean up any remaining empty rel attributes
			$new_content = preg_replace('/\s+rel=""/', '', $new_content);
			$new_content = preg_replace('/\s+rel="\s*"/', '', $new_content);
		}
		
		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $new_content,
			),
			true
		);

		if ( ! is_wp_error( $result ) ) {
			clean_post_cache( $post_id );
			
			// Force Gutenberg to refresh by clearing additional caches
			wp_cache_delete( $post_id, 'posts' );
			wp_cache_delete( $post_id, 'post_meta' );
			
			// Clear any block-related caches
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
	// Safety: Prevent deep recursion
	if ( $depth > 15 ) {
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
			// Found the target block - remove attributes completely to avoid validation issues
			if ( $event_type === 'button' ) {
				if ( isset( $block['attrs']['umamiEvent'] ) ) {
					unset( $block['attrs']['umamiEvent'] );
					$changed = true;
				}
				if ( isset( $block['attrs']['umamiDataPairs'] ) ) {
					unset( $block['attrs']['umamiDataPairs'] );
					$changed = true;
				}
			} elseif ( $event_type === 'link' ) {
				if ( isset( $block['attrs']['umamiLinkEvents'] ) ) {
					unset( $block['attrs']['umamiLinkEvents'] );
					$changed = true;
				}
			}
			return; // Found and processed, exit
		}
		
		// Only recurse if path could match and we haven't found it yet
		if ( ! $changed && ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			if ( strpos( $target_path, $block_path . '.' ) === 0 ) {
				remove_event_from_block_by_path( $block['innerBlocks'], $target_path, $event_type, $changed, $block_path, $depth + 1 );
			}
		}
	}
}
