<?php
if (!defined('ABSPATH')) exit;

function umami_connect_render_events_overview_page() {
    echo '<div class="wrap">';
    echo '<h1><b>umami Connect</b></h1>
        <h3>Event overview</h3>';
    $events = apply_filters('umami_connect_get_all_events', array());
    if (empty($events)) {
        echo '<p>No events found.</p>';
    } else {
        echo '<form method="get" style="margin-bottom: 12px;">';
        echo '<input type="hidden" name="page" value="umami_connect_events_overview">';
        echo '<p class="search-box">';
        echo '<label class="screen-reader-text" for="event-search-input">Search Events:</label>';
        echo '<input type="search" id="event-search-input" name="s" value="' . esc_attr($_GET['s'] ?? '') . '" />';
        echo '<input type="submit" id="search-submit" class="button" value="Search Events">';
        echo '</p>';
        echo '</form>';
        $search = isset($_GET['s']) ? trim(strtolower($_GET['s'])) : '';
        $filtered = array();
        foreach ($events as $row) {
            $rowtext = strtolower($row['event'] . ' ' . $row['post_title'] . ' ' . $row['label']);
            if ($search === '' || strpos($rowtext, $search) !== false) {
                $filtered[] = $row;
            }
        }
        $orderby = $_GET['orderby'] ?? 'event';
        $order = strtolower($_GET['order'] ?? 'asc');
            usort($filtered, function($a, $b) use ($orderby, $order) {
                if ($orderby === 'block_type') {
                    $block_labels = [
                        'core/button' => 'Button',
                        'core/paragraph' => 'Paragraph',
                        'core/post-excerpt' => 'Excerpt',
                        'core/heading' => 'Heading',
                    ];
                    $A = strtolower($block_labels[$a['block_type'] ?? ''] ?? ($a['block_type'] ?? ''));
                    $B = strtolower($block_labels[$b['block_type'] ?? ''] ?? ($b['block_type'] ?? ''));
                } else {
                    $A = strtolower($a[$orderby] ?? '');
                    $B = strtolower($b[$orderby] ?? '');
                }
                if ($A === $B) return 0;
                if ($order === 'desc') return ($A < $B) ? 1 : -1;
                return ($A < $B) ? -1 : 1;
            });
        function sort_link($label, $col) {
            $current = $_GET['orderby'] ?? 'event';
            $order = strtolower($_GET['order'] ?? 'asc');
            $next = ($current === $col && $order === 'asc') ? 'desc' : 'asc';
            $url = add_query_arg(['orderby' => $col, 'order' => $next]);
            $arrow = ($current === $col) ? (' <span style="font-size:0.9em;">' . ($order === 'asc' ? '▲' : '▼') . '</span>') : '';
            return '<a href="' . esc_url($url) . '">' . esc_html($label) . $arrow . '</a>';
        }
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th scope="col">' . sort_link('Event name', 'event') . '</th>';
        echo '<th scope="col">' . sort_link('Page/Post', 'post_title') . '</th>';
            echo '<th scope="col">' . sort_link('Block type', 'block_type') . '</th>';
        echo '<th scope="col">' . sort_link('Block label', 'label') . '</th>';
        echo '<th scope="col">Data Key-Value-Pairs</th>';
        echo '</tr></thead>';
        echo '<tfoot><tr>';
        echo '<th scope="col">' . sort_link('Event name', 'event') . '</th>';
        echo '<th scope="col">' . sort_link('Page/Post', 'post_title') . '</th>';
            echo '<th scope="col">' . sort_link('Block type', 'block_type') . '</th>';
        echo '<th scope="col">' . sort_link('Block label', 'label') . '</th>';
        echo '<th scope="col">Data Key-Value-Pairs</th>';
        echo '</tr></tfoot>';
        echo '<tbody>';
        $block_labels = [
            'core/button' => 'Button',
            'core/paragraph' => 'Paragraph',
            'core/post-excerpt' => 'Excerpt',
            'core/heading' => 'Heading',
        ];
        foreach ($filtered as $row) {
            $block_type = $row['block_type'] ?? '';
            $block_label = $block_labels[$block_type] ?? $block_type;
            echo '<tr>';
            echo '<td><code>' . esc_html($row['event']) . '</code></td>';
            echo '<td><a href="' . esc_url(get_edit_post_link($row['post_id'])) . '" target="_blank">' . esc_html($row['post_title']) . '</a></td>';
            echo '<td>' . esc_html($block_label) . '</td>';
            echo '<td>' . esc_html($row['label']) . '</td>';
            echo '<td>';
            if (!empty($row['data_pairs']) && is_array($row['data_pairs'])) {
                $pairs = array();
                foreach ($row['data_pairs'] as $pair) {
                    if (!empty($pair['key'])) {
                        $pairs[] = '<li><span>' . esc_html($pair['key']) . '</span>: <b>' . esc_html($pair['value']) . '</b></li>';
                    }
                }
                if (!empty($pairs)) {
                    echo '<ul style="margin:0 0 0 18px; padding:0; list-style:disc;">' . implode('', $pairs) . '</ul>';
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
    }
    echo '</div>';
}

add_filter('umami_connect_get_all_events', function($events) {
    global $wpdb;
    $result = array();
    $posts = $wpdb->get_results("SELECT ID, post_title, post_content FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type = 'post' OR post_type = 'page')");

    function find_umami_events($blocks, &$result, $post_id, $post_title) {
        foreach ($blocks as $block) {
            if (!empty($block['attrs']['umamiEvent'])) {
                $event = trim($block['attrs']['umamiEvent']);
                $label = '';
                if (!empty($block['attrs']['text'])) {
                    $label = $block['attrs']['text'];
                } elseif (!empty($block['innerHTML'])) {
                    $label = strip_tags($block['innerHTML']);
                }
                $data_pairs = array();
                if (!empty($block['attrs']['umamiDataPairs']) && is_array($block['attrs']['umamiDataPairs'])) {
                    $data_pairs = $block['attrs']['umamiDataPairs'];
                }
                $result[] = array(
                    'event' => $event,
                    'post_id' => $post_id,
                    'post_title' => $post_title,
                    'block_type' => $block['blockName'] ?? '',
                    'label' => $label,
                    'data_pairs' => $data_pairs,
                );
            }
            if (!empty($block['innerBlocks'])) {
                find_umami_events($block['innerBlocks'], $result, $post_id, $post_title);
            }
        }
    }

    foreach ($posts as $post) {
        $blocks = parse_blocks($post->post_content);
        find_umami_events($blocks, $result, $post->ID, $post->post_title);
    }
    return $result;
});
