<?php
/**
 * Server-side Auto-Tracking (scoped to post content)
 * Adds data-umami-event attributes to HTML elements inside the_content.
 * Navigation, header, footer etc. bleiben unberührt.
 */

add_filter('the_content', function($content) {
    // Only apply to frontend main content
    if (is_admin()) return $content;

    // Check if auto-tracking is enabled
    $autotrack_links = get_option('umami_autotrack_links', '1') === '1';
    $autotrack_buttons = get_option('umami_autotrack_buttons', '1') === '1';

    if (!$autotrack_links && !$autotrack_buttons) return $content;
    if (!is_string($content) || $content === '') return $content;

    // Load HTML with DOMDocument
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);

    // Add wrapper to preserve encoding and allow fragment parsing
    $wrapped = '<?xml encoding="UTF-8"?><div>' . $content . '</div>';
    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Move per-link Umami event markers (span.umami-event or descendants with data-umami-event)
    // up to the enclosing <a> element and remove the marker
    $anchorsWithMarkers = $xpath->query('//a[descendant::span[contains(concat(" ", normalize-space(@class), " "), " umami-event ")] or descendant::*[@data-umami-event or @*[starts-with(name(), "data-umami-event-")]]]');
    if ($anchorsWithMarkers && $anchorsWithMarkers->length) {
        foreach ($anchorsWithMarkers as $a) {
            // Find nearest descendant with data-umami-event or span.umami-event
            $marker = null;
            foreach ($a->getElementsByTagName('*') as $child) {
                $isUmamiSpan = (strcasecmp($child->nodeName, 'span') === 0 && $child->hasAttribute('class') && strpos(' ' . $child->getAttribute('class') . ' ', ' umami-event ') !== false);
                $hasAnyUmamiAttr = $child->hasAttribute('data-umami-event');
                if (!$hasAnyUmamiAttr) {
                    // check any data-umami-event-* attributes
                    foreach ($child->attributes as $attr) {
                        if (strpos($attr->name, 'data-umami-event-') === 0) { $hasAnyUmamiAttr = true; break; }
                    }
                }
                if ($isUmamiSpan || $hasAnyUmamiAttr) {
                    $marker = $child;
                    break;
                }
            }
            if ($marker) {
                // Copy main event if missing on anchor
                if ($marker->hasAttribute('data-umami-event') && !$a->hasAttribute('data-umami-event')) {
                    $a->setAttribute('data-umami-event', $marker->getAttribute('data-umami-event'));
                }
                // Copy data-umami-event-* attributes
                foreach ($marker->attributes as $attr) {
                    $name = $attr->name;
                    if (strpos($name, 'data-umami-event-') === 0 && !$a->hasAttribute($name)) {
                        $a->setAttribute($name, $attr->value);
                    }
                }
                // unwrap marker: replace it by its children
                $parent = $marker->parentNode;
                if ($parent) {
                    while ($marker->firstChild) {
                        $parent->insertBefore($marker->firstChild, $marker);
                    }
                    $parent->removeChild($marker);
                }
            }
        }
    }

    // Auto-track buttons (Gutenberg button blocks)
    if ($autotrack_buttons) {
        $buttons = $xpath->query('//a[contains(@class, "wp-block-button__link") or contains(@class, "wp-element-button")]');
        foreach ($buttons as $button) {
            // Skip if already has umami-event or umami-skip
            if ($button->hasAttribute('data-umami-event') || $button->hasAttribute('data-umami-skip')) {
                continue;
            }

            // Get button text
            $text = trim($button->textContent);
            $event_name = $text ? 'button:' . $text : 'button_click';

            $button->setAttribute('data-umami-event', $event_name);
        }
    }

    // Auto-track links (excluding buttons)
    if ($autotrack_links) {
        $links = $xpath->query('//a[not(contains(@class, "wp-block-button__link")) and not(contains(@class, "wp-element-button"))]');
        foreach ($links as $link) {
            // Skip if already has umami-event or umami-skip
            if ($link->hasAttribute('data-umami-event') || $link->hasAttribute('data-umami-skip')) {
                continue;
            }

            // Get href or text
            $href = $link->getAttribute('href');
            $text = trim($link->textContent);

            if ($href && $href !== '#') {
                $event_name = $href;
            } else if ($text) {
                $event_name = 'link:' . $text;
            } else {
                $event_name = 'click';
            }

            $link->setAttribute('data-umami-event', $event_name);
        }
    }

    // Save HTML
    $container = $dom->getElementsByTagName('div')->item(0);
    $html = '';
    if ($container) {
        foreach ($container->childNodes as $node) {
            $html .= $dom->saveHTML($node);
        }
    }

    return $html ?: $content;
}, 20);

// Auto-track forms (clientseitig ergänzen, da Forms häufig dynamisch sind)
add_action('wp_footer', function() {
    if (is_admin()) return;

    $autotrack_forms = get_option('umami_autotrack_forms', '1') === '1';
    if (!$autotrack_forms) return;

    ?>
    <script>
    (function() {
        // Add data-umami-event to forms without it
        document.addEventListener('DOMContentLoaded', function() {
            var forms = document.querySelectorAll('form:not([data-umami-event]):not([data-umami-skip])');
            forms.forEach(function(form) {
                var id = form.getAttribute('id');
                var name = form.getAttribute('name');
                var eventName = 'form_submit';

                if (id) {
                    eventName = 'form:' + id;
                } else if (name) {
                    eventName = 'form:' + name;
                }

                form.setAttribute('data-umami-event', eventName);
            });
        });
    })();
    </script>
    <?php
}, 100);
