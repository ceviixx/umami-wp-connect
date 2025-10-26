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

    if ($block['blockName'] === 'core/button') {
        if (!$event && empty($data_keys)) return $block_content;
        $block_content = preg_replace('/(<div[^>]*class="[^"]*wp-block-button[^"]*")([^>]*data-umami-event="[^"]*")?([^>]*data-umami-data-[^=]*="[^"]*")*/i', '$1', $block_content);
        $block_content = preg_replace_callback(
            '/<a([^>]*class="[^\"]*wp-block-button__link[^\"]*")([^>]*)>/i',
            function($m) use ($event, $data_keys) {
                $attr = '';
                $hrefVal = null;
                if (preg_match('/href\s*=\s*([\"\"])\s*(.*?)\s*\1/i', $m[2], $hm)) {
                    $hrefVal = trim($hm[2]);
                } elseif (preg_match('/href\s*=\s*([^\s>]+)/i', $m[2], $hm2)) {
                    $hrefVal = trim($hm2[1]);
                }
                $hasValidHref = (is_string($hrefVal) && $hrefVal !== '');

                if ($event) $attr .= ' data-umami-event="' . esc_attr($event) . '"';
                foreach ($data_keys as $key => $val) {
                    $attr .= ' data-umami-event-' . esc_attr($key) . '="' . esc_attr($val) . '"';
                }
                if (!$hasValidHref) {
                    $attr .= ' href="javascript:void(0)"';
                }
                return '<a' . $m[1] . $m[2] . $attr . '>';
            },
            $block_content
        );
        return $block_content;
    }

    $supportsLinkEvents = in_array($block['blockName'], ['core/paragraph', 'core/heading', 'core/post-excerpt', 'core/quote', 'core/pullquote', 'core/list', 'core/list-item', 'core/columns', 'core/cover', 'core/group'], true);
    $linkEvents = isset($attrs['umamiLinkEvents']) && is_array($attrs['umamiLinkEvents']) ? $attrs['umamiLinkEvents'] : [];

    if (!$supportsLinkEvents && !$event && empty($data_keys)) {
        return $block_content;
    }

    if ($supportsLinkEvents || $event || !empty($data_keys)) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $wrapped = '<div>' . $block_content . '</div>';
        $dom->loadHTML('<?xml encoding="UTF-8"?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $byId = [];
        foreach ($linkEvents as $ev) {
            if (!empty($ev['id'])) $byId[$ev['id']] = $ev;
        }

        $norm = function($s) {
            $s = html_entity_decode((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $s = preg_replace('/\s+/u', ' ', $s ?? '');
            $s = trim($s);
            return mb_strtolower($s, 'UTF-8');
        };

        $anchorNodes = $xpath->query('//a');
        if ($anchorNodes && $anchorNodes->length) {
            foreach ($anchorNodes as $a) {
                $matched = null;

                $rel = $a->hasAttribute('rel') ? $a->getAttribute('rel') : '';
                if ($rel && preg_match('/(^|\s)umami:([a-z0-9\-]+)/i', $rel, $mm)) {
                    $rid = $mm[2];
                    if (isset($byId[$rid])) {
                        $matched = $byId[$rid];
                    }
                }

                if (!$matched && !empty($linkEvents)) {
                    $href = $a->hasAttribute('href') ? $a->getAttribute('href') : '';
                    $text = $norm($a->textContent);
                    foreach ($linkEvents as $ev) {
                        $evUrl = isset($ev['linkUrl']) ? (string)$ev['linkUrl'] : '';
                        $evText = $norm(isset($ev['linkText']) ? (string)$ev['linkText'] : '');
                        if ($evUrl !== '' && $href !== '' && $href === $evUrl && $evText !== '' && $text === $evText) {
                            $matched = $ev;
                            break;
                        }
                    }
                }

                if (!$matched && !empty($linkEvents)) {
                    $href = $a->hasAttribute('href') ? $a->getAttribute('href') : '';
                    if ($href !== '') {
                        $candidates = array_values(array_filter($linkEvents, function($ev) use ($href) {
                            return isset($ev['linkUrl']) && (string)$ev['linkUrl'] === $href;
                        }));
                        if (count($candidates) === 1) {
                            $matched = $candidates[0];
                        }
                    }
                }

                if ($matched && (!empty($matched['event']) || (!empty($matched['pairs']) && is_array($matched['pairs'])))) {
                    if (!empty($matched['event'])) {
                        $a->setAttribute('data-umami-event', (string)$matched['event']);
                    }
                    if (!empty($matched['pairs']) && is_array($matched['pairs'])) {
                        foreach ($matched['pairs'] as $pair) {
                            if (!empty($pair['key']) && isset($pair['value'])) {
                                $key = strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string)$pair['key']));
                                $a->setAttribute('data-umami-event-' . $key, (string)$pair['value']);
                            }
                        }
                    }
                    continue;
                }

                if ($event || !empty($data_keys)) {
                    if ($event && !$a->hasAttribute('data-umami-event')) {
                        $a->setAttribute('data-umami-event', esc_attr($event));
                    }
                    foreach ($data_keys as $k => $v) {
                        $attrName = 'data-umami-event-' . $k;
                        if (!$a->hasAttribute($attrName)) {
                            $a->setAttribute($attrName, esc_attr($v));
                        }
                    }
                }
            }
        }

        $container = $dom->getElementsByTagName('div')->item(0);
        $html = '';
        if ($container) {
            foreach ($container->childNodes as $node) {
                $html .= $dom->saveHTML($node);
            }
            return $html;
        }
    }

    return $block_content;
}, 20, 2);
?>