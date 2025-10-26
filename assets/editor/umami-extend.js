 (function () {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { InspectorControls, RichTextToolbarButton } = wp.blockEditor || wp.editor;
    const { PanelBody, Popover, TextControl, Button, Flex, FlexItem } = wp.components;
    const { Fragment, useState, useEffect } = wp.element;
    const { getActiveFormat, applyFormat, removeFormat, registerFormatType } = wp.richText;

    function addUmamiAttributes(settings, name) {
        if (name === 'core/button') {
            settings.attributes = Object.assign({}, settings.attributes, {
                umamiEvent: { type: 'string', default: '' },
                umamiDataPairs: { type: 'array', default: [] },
            });
        }
        if (name === 'core/paragraph' || name === 'core/heading' || name === 'core/post-excerpt' || name === 'core/quote' || name === 'core/pullquote' || name === 'core/list' || name === 'core/list-item' || name === 'core/columns' || name === 'core/cover' || name === 'core/group') {
            settings.attributes = Object.assign({}, settings.attributes, {
                umamiLinkEvents: { type: 'array', default: [] },
            });
        }
        return settings;
    }
    addFilter('blocks.registerBlockType', 'umami/extend/attributes', addUmamiAttributes);

    const withInspectorControls = createHigherOrderComponent((BlockEdit) => {
        return function (props) {
            if (props.name !== 'core/button') return wp.element.createElement(BlockEdit, props);

            const attributes = props.attributes;
            const setAttributes = props.setAttributes;
            const umamiEvent = attributes.umamiEvent || '';
            const umamiDataPairs = Array.isArray(attributes.umamiDataPairs) ? attributes.umamiDataPairs : [];

            function updatePair(idx, field, value) {
                const next = umamiDataPairs.slice();
                next[idx] = Object.assign({}, next[idx] || {}, { [field]: value });
                setAttributes({ umamiDataPairs: next });
            }
            function addPair() {
                setAttributes({ umamiDataPairs: umamiDataPairs.concat([{ key: '', value: '' }]) });
            }
            function removePair(idx) {
                const next = umamiDataPairs.slice();
                next.splice(idx, 1);
                setAttributes({ umamiDataPairs: next });
            }

            return wp.element.createElement(Fragment, {},
                wp.element.createElement(BlockEdit, props),
                wp.element.createElement(InspectorControls, {},
                    wp.element.createElement(PanelBody, { title: 'Umami Tracking', initialOpen: false },
                        wp.element.createElement(TextControl, {
                            label: 'Event Name',
                            value: umamiEvent,
                            onChange: function (val) { setAttributes({ umamiEvent: val }); },
                            placeholder: 'e.g. button_click'
                        }),
                        wp.element.createElement('div', { style: { marginTop: 12 } },
                            wp.element.createElement('label', { style: { display: 'block', fontSize: 12, fontWeight: 600, marginBottom: 6 } }, 'Event Data (Key/Value)'),
                            umamiDataPairs.map(function (pair, idx) {
                                return wp.element.createElement(Flex, { key: idx, align: 'center', style: { gap: 8, marginBottom: 8 } },
                                    wp.element.createElement(FlexItem, { isBlock: true, style: { flex: 1 } },
                                        wp.element.createElement(TextControl, {
                                            value: pair.key || '',
                                            placeholder: 'Key',
                                            onChange: function (val) { updatePair(idx, 'key', val.replace(/[^a-zA-Z0-9_\\-]/g, '')); }
                                        })
                                    ),
                                    wp.element.createElement(FlexItem, { isBlock: true, style: { flex: 1 } },
                                        wp.element.createElement(TextControl, {
                                            value: pair.value || '',
                                            placeholder: 'Value',
                                            onChange: function (val) { updatePair(idx, 'value', val); }
                                        })
                                    ),
                                    wp.element.createElement(FlexItem, null,
                                        wp.element.createElement(Button, { isDestructive: true, isSmall: true, onClick: function () { removePair(idx); }, 'aria-label': 'Remove row' }, '×')
                                    )
                                );
                            }),
                            wp.element.createElement(Button, { variant: 'secondary', isSmall: true, onClick: addPair }, '+ Add field')
                        )
                    )
                )
            );
        };
    }, 'withInspectorControls');
    addFilter('editor.BlockEdit', 'umami/extend/inspector', withInspectorControls);

    registerFormatType('umami/link-event', {
        title: 'Umami Event',
        tagName: 'span',
        className: 'umami-link-event',
        edit: function (props) {
            var isOpenState = wp.element.useState(false);
            var isOpen = isOpenState[0];
            var setOpen = isOpenState[1];
            var eventNameState = wp.element.useState('');
            var eventName = eventNameState[0];
            var setEventName = eventNameState[1];
            var pairsState = wp.element.useState([{ key: '', value: '' }]);
            var pairs = pairsState[0];
            var setPairs = pairsState[1];

            var value = props.value;
            var onChange = props.onChange;
            var activeLink = getActiveFormat(value, 'core/link');
            var activeUmami = getActiveFormat(value, 'umami/link-event');

            function getLinkRange(val) {
                var start = (typeof val.start === 'number') ? val.start : 0;
                var end = (typeof val.end === 'number') ? val.end : start;
                var formats = Array.isArray(val.formats) ? val.formats : [];
                if (start === end && formats.length) {
                    var left = start;
                    var right = end;
                    while (left > 0) {
                        var f = formats[left - 1];
                        var hasLink = Array.isArray(f) && f.some(function (ff) { return ff && ff.type === 'core/link'; });
                        if (!hasLink) break;
                        left--;
                    }
                    while (right < formats.length) {
                        var f2 = formats[right];
                        var hasLink2 = Array.isArray(f2) && f2.some(function (ff) { return ff && ff.type === 'core/link'; });
                        if (!hasLink2) break;
                        right++;
                    }
                    start = left;
                    end = right;
                }
                return { start: start, end: end };
            }

            function normalizeText(str) {
                if (!str) return '';
                return String(str).replace(/\s+/g, ' ').trim();
            }

            useEffect(function () {
                if (!isOpen) return;
                try {
                    var select = wp.data.select('core/block-editor');
                    var clientId = select.getSelectedBlockClientId();
                    var block = clientId ? select.getBlock(clientId) : null;
                    if (!block) return;
                    var attrs = block.attributes || {};
                    var list = Array.isArray(attrs.umamiLinkEvents) ? attrs.umamiLinkEvents : [];
                    var range = getLinkRange(value);
                    var start = range.start, end = range.end;
                    var linkText = normalizeText((value.text || '').slice(start, end) || '');
                    var linkAttrsObj = (activeLink && activeLink.attributes) ? activeLink.attributes : {};
                    var linkUrl = linkAttrsObj.url || linkAttrsObj.href || '';
                    
                    var relStr = linkAttrsObj.rel || '';
                    var relMatch = relStr && relStr.match(/(^|\s)umami:([a-z0-9\-]+)/i);
                    var relId = relMatch ? relMatch[2] : '';
                    
                    var found = null;
                    if (relId) {
                        found = list.find(function (e) { return e && e.id === relId; }) || null;
                    }
                    if (!found) {
                        found = list.find(function (e) { return normalizeText(e.linkText) === linkText && e.linkUrl === linkUrl; }) || null;
                    }
                    if (found) {
                        setEventName(found.event || '');
                        setPairs(Array.isArray(found.pairs) && found.pairs.length ? found.pairs : [{ key: '', value: '' }]);
                    } else {
                        setEventName('');
                        setPairs([{ key: '', value: '' }]);
                    }
                } catch (e) {}
            }, [isOpen]);

            useEffect(function () {
                if (!activeLink && isOpen) {
                    setOpen(false);
                }
            }, [isOpen, !!activeLink]);

            function addPair() { setPairs(pairs.concat([{ key: '', value: '' }])); }
            function updatePair(idx, field, val) {
                var next = pairs.slice();
                next[idx] = Object.assign({}, next[idx] || {}, { [field]: field === 'key' ? val.replace(/[^a-zA-Z0-9_\\-]/g, '') : val });
                setPairs(next);
            }
            function removePair(idx) { var next = pairs.slice(); next.splice(idx, 1); setPairs(next); }

            function onSave() {
                try {
                    var select = wp.data.select('core/block-editor');
                    var dispatch = wp.data.dispatch('core/block-editor');
                    var clientId = select.getSelectedBlockClientId();
                    var block = clientId ? select.getBlock(clientId) : null;
                    if (block) {
                        var current = Array.isArray(block.attributes.umamiLinkEvents) ? block.attributes.umamiLinkEvents : [];
                        var range = getLinkRange(value);
                        var start = range.start, end = range.end;
                        var linkText = normalizeText((value.text || '').slice(start, end) || '');
                        var linkAttrsObj = (activeLink && activeLink.attributes) ? activeLink.attributes : {};
                        var linkUrl = linkAttrsObj.url || linkAttrsObj.href || '';
                        
                        if (!linkText && value.text) {
                            var formats = value.formats || [];
                            for (var i = start; i < end && i < formats.length; i++) {
                                if (formats[i]) {
                                    for (var j = 0; j < formats[i].length; j++) {
                                        if (formats[i][j] && formats[i][j].type === 'core/link') {
                                            linkText = normalizeText(value.text.slice(start, end) || value.text);
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        
                        var activeRel = linkAttrsObj.rel || '';
                        var relMatch = activeRel && activeRel.match(/(^|\s)umami:([a-z0-9\-]+)/i);
                        var relId = relMatch ? relMatch[2] : '';

                        var existingIndex = -1;
                        if (relId) {
                            existingIndex = current.findIndex(function (e) { return e && e.id === relId; });
                        }
                        if (existingIndex < 0) {
                            existingIndex = current.findIndex(function (e) { return normalizeText(e.linkText) === linkText && e.linkUrl === linkUrl; });
                        }
                        var existing = existingIndex >= 0 ? current[existingIndex] : null;
                        var id = relId || (existing && existing.id) || ('u' + Math.random().toString(36).slice(2, 10));
                        var cleanPairs = currentPairsClean(pairs);
                        var entry = { id: id, linkText: linkText, linkUrl: linkUrl, event: eventName || '', pairs: cleanPairs };
                        var hasData = !!entry.event || entry.pairs.length > 0;
                        var next = current.slice();
                        if (existingIndex >= 0) { if (hasData) next[existingIndex] = entry; else next.splice(existingIndex, 1); } else if (hasData) { next.push(entry); }
                        dispatch.updateBlockAttributes(clientId, { umamiLinkEvents: next });

                        if (hasData) {
                            var relTokens = activeRel.split(/\s+/).filter(Boolean).filter(function (t) { return !/^umami:/.test(t); });
                            relTokens.push('umami:' + id);
                            var relJoined = Array.from(new Set(relTokens)).join(' ');
                            
                            var updatedLinkAttrs = Object.assign({}, linkAttrsObj, { rel: relJoined });
                            
                            var newValueLink = applyFormat(value, { 
                                type: 'core/link', 
                                attributes: updatedLinkAttrs,
                                unregisteredAttributes: {}
                            }, start, end);
                            
                            newValueLink = removeFormat(newValueLink, 'umami/link-event', start, end);
                            newValueLink = applyFormat(newValueLink, { 
                                type: 'umami/link-event', 
                                attributes: {} 
                            }, start, end);
                            
                            onChange(newValueLink);
                        } else {
                            var v = removeFormat(value, 'umami/link-event', start, end);
                            onChange(v);
                        }
                    }
                } catch (e) {
                    console.error('Umami link tracking save error:', e);
                }
                setOpen(false);
            }

            function currentPairsClean(arr) {
                var out = [];
                for (var i = 0; i < arr.length; i++) {
                    var p = arr[i] || {};
                    if (p.key && p.value !== undefined && p.value !== '') out.push({ key: p.key, value: p.value });
                }
                return out;
            }

            if (!activeLink) return null;

            return wp.element.createElement(Fragment, {},
                wp.element.createElement(RichTextToolbarButton, {
                    icon: 'chart-area',
                    title: 'Umami Tracking',
                    onClick: function () { setOpen(!isOpen); },
                    isActive: !!activeUmami,
                    className: 'umami-tracking-button'
                }),
                (isOpen) && wp.element.createElement(Popover, { position: 'bottom center', onClose: function () { setOpen(false); }, className: 'umami-tracking-popover components-card' },
                    wp.element.createElement('div', { className: 'umami-popover-inner' },
                        wp.element.createElement('div', { className: 'umami-popover-header' },
                            wp.element.createElement('span', { className: 'umami-popover-title' }, 'Umami Link Tracking')
                        ),
                        wp.element.createElement('div', { className: 'umami-popover-section' },
                            wp.element.createElement(TextControl, { label: 'Event Name', value: eventName, onChange: setEventName, placeholder: 'e.g. link_click' })
                        ),
                        wp.element.createElement('div', { className: 'umami-popover-section' },
                            wp.element.createElement('div', { className: 'components-base-control__label' }, 'Event Data (Key/Value)'),
                            pairs.map(function (pair, idx) {
                                return wp.element.createElement(Flex, { key: idx, className: 'umami-pair-row', align: 'center' },
                                    wp.element.createElement(FlexItem, { isBlock: true, style: { flex: 1 } },
                                        wp.element.createElement(TextControl, {
                                            value: pair.key || '',
                                            placeholder: 'Key',
                                            onChange: function (val) { updatePair(idx, 'key', val); }
                                        })
                                    ),
                                    wp.element.createElement(FlexItem, { isBlock: true, style: { flex: 1 } },
                                        wp.element.createElement(TextControl, {
                                            value: pair.value || '',
                                            placeholder: 'Value',
                                            onChange: function (val) { updatePair(idx, 'value', val); }
                                        })
                                    ),
                                    wp.element.createElement(FlexItem, null,
                                        wp.element.createElement(Button, { isSmall: true, isDestructive: true, onClick: function () { removePair(idx); }, 'aria-label': 'Remove row' }, '×')
                                    )
                                );
                            }),
                            wp.element.createElement(Button, { variant: 'secondary', onClick: addPair, isSmall: true }, '+ Add field')
                        ),
                        wp.element.createElement('div', { className: 'umami-popover-footer' },
                            wp.element.createElement(Button, { variant: 'tertiary', onClick: function () { setOpen(false); } }, 'Cancel'),
                            wp.element.createElement(Button, { variant: 'primary', onClick: onSave }, 'Save')
                        )
                    )
                )
            );
        }
    });

    function addExtraProps(saveProps, blockType, attributes) {
        if (blockType.name === 'core/button') {
            if (attributes.umamiEvent) saveProps['data-umami-event'] = attributes.umamiEvent;
            if (Array.isArray(attributes.umamiDataPairs)) {
                for (var i = 0; i < attributes.umamiDataPairs.length; i++) {
                    var p = attributes.umamiDataPairs[i];
                    if (p && p.key && p.value) saveProps['data-umami-event-' + p.key] = p.value;
                }
            }
        }
        return saveProps;
    }
    addFilter('blocks.getSaveContent.extraProps', 'umami/extend/save-props', addExtraProps);
})();
