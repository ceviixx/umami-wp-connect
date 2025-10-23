<?php
add_filter('render_block', function($block_content, $block) {
    if (empty($block['attrs'])) return $block_content;
    $attrs = $block['attrs'];
    $event = isset($attrs['umamiEvent']) ? trim($attrs['umamiEvent']) : '';
    $data_keys = [];
    if (!empty($attrs['umamiDataPairs']) && is_array($attrs['umamiDataPairs'])) {
        foreach ($attrs['umamiDataPairs'] as $pair) {
            if (!empty($pair['key']) && isset($pair['value'])) {
                $key = strtolower(preg_replace('/[^a-z0-9_\-]/', '', $pair['key']));
                $data_keys[$key] = $pair['value'];
            }
        }
    }
    if (!$event && empty($data_keys)) return $block_content;

    // Button Block
    if ($block['blockName'] === 'core/button') {
        $block_content = preg_replace('/(<div[^>]*class="[^"]*wp-block-button[^"]*")([^>]*data-umami-event="[^"]*")?([^>]*data-umami-data-[^=]*="[^"]*")*/i', '$1', $block_content);
        $block_content = preg_replace_callback(
            '/<a([^>]*class="[^"]*wp-block-button__link[^"]*")([^>]*)>/i',
            function($m) use ($event, $data_keys) {
                $attr = '';
                $hasHref = preg_match('/href\s*=/', $m[2]);
                $hasOnClick = preg_match('/on(click|mousedown|mouseup)\s*=/', $m[2]);
                if ($event) $attr .= ' data-umami-event="' . esc_attr($event) . '"';
                foreach ($data_keys as $key => $val) {
                    $attr .= ' data-umami-event-' . esc_attr($key) . '="' . esc_attr($val) . '"';
                }
                if (!$hasHref && !$hasOnClick) {
                    $attr .= ' href="javascript:void(0)"';
                }
                return '<a' . $m[1] . $m[2] . $attr . '>';
            },
            $block_content
        );
        return $block_content;
    }

    // Paragraph-Block: <a>
    if ($block['blockName'] === 'core/paragraph') {
        $block_content = preg_replace_callback(
            '/<a([^>]*)>/i',
            function($m) use ($event, $data_keys) {
                $attr = '';
                if ($event) $attr .= ' data-umami-event="' . esc_attr($event) . '"';
                foreach ($data_keys as $key => $val) {
                    $attr .= ' data-umami-event-' . esc_attr($key) . '="' . esc_attr($val) . '"';
                }
                return '<a' . $m[1] . $attr . '>';
            },
            $block_content
        );
        return $block_content;
    }

    // Excerpt-Block: <p>
    if ($block['blockName'] === 'core/post-excerpt') {
        $block_content = preg_replace_callback(
            '/<p([^>]*)>/i',
            function($m) use ($event, $data_keys) {
                $attr = '';
                if ($event) $attr .= ' data-umami-event="' . esc_attr($event) . '"';
                foreach ($data_keys as $key => $val) {
                    $attr .= ' data-umami-event-' . esc_attr($key) . '="' . esc_attr($val) . '"';
                }
                return '<p' . $m[1] . $attr . '>';
            },
            $block_content
        );
        return $block_content;
    }


    // Heading-Block: <a>
    if ($block['blockName'] === 'core/heading') {
        $block_content = preg_replace_callback(
            '/<a([^>]*)>/i',
            function($m) use ($event, $data_keys) {
                $attr = '';
                if ($event) $attr .= ' data-umami-event="' . esc_attr($event) . '"';
                foreach ($data_keys as $key => $val) {
                    $attr .= ' data-umami-event-' . esc_attr($key) . '="' . esc_attr($val) . '"';
                }
                return '<a' . $m[1] . $attr . '>';
            },
            $block_content
        );
        return $block_content;
    }

    return $block_content;
}, 20, 2);
?>