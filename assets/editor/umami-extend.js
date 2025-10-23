(function () {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { InspectorControls } = wp.blockEditor || wp.editor;
    const { PanelBody, TextControl } = wp.components;
    const { Fragment } = wp.element;


    function addUmamiAttributes(settings, name) {
        if (name === 'core/button' || name === 'core/paragraph' || name === 'core/post-excerpt' || name === 'core/heading') {
            settings.attributes = Object.assign({}, settings.attributes, {
                umamiEvent: { type: 'string', default: '' },
                umamiDataPairs: { type: 'array', default: [] },
            });
        }
        return settings;
    }
    addFilter('blocks.registerBlockType', 'umami/extend-button/attributes', addUmamiAttributes);

    const withInspectorControls = createHigherOrderComponent((BlockEdit) => {
        return (props) => {
            if (props.name !== 'core/button' && props.name !== 'core/paragraph' && props.name !== 'core/post-excerpt' && props.name !== 'core/heading') {
                return wp.element.createElement(BlockEdit, props);
            }

            const { attributes, setAttributes } = props;
            const { umamiEvent, umamiDataPairs = [] } = attributes;

            function updatePair(idx, field, value) {
                const newPairs = [...umamiDataPairs];
                newPairs[idx] = { ...newPairs[idx], [field]: value };
                setAttributes({ umamiDataPairs: newPairs });
            }
            function addPair() {
                setAttributes({ umamiDataPairs: [...umamiDataPairs, { key: '', value: '' }] });
            }
            function removePair(idx) {
                const newPairs = [...umamiDataPairs];
                newPairs.splice(idx, 1);
                setAttributes({ umamiDataPairs: newPairs });
            }

            return wp.element.createElement(Fragment, {},
                wp.element.createElement(BlockEdit, props),
                wp.element.createElement(InspectorControls, {},
                    wp.element.createElement(PanelBody, { title: 'Umami Tracking', initialOpen: false },
                        wp.element.createElement('div', { style: { marginBottom: 2 } },
                            wp.element.createElement('span', {
                                style: {
                                    display: 'block',
                                    fontWeight: 500,
                                    fontSize: 13,
                                    color: '#222',
                                    marginBottom: 2
                                }
                            }, 'Event name'),
                            wp.element.createElement('div', {
                                style: {
                                    display: 'flex',
                                    alignItems: 'center',
                                    background: 'transparent',
                                    gap: 10,
                                    borderRadius: 8,
                                    border: '1px solid #e0e6ef',
                                    padding: '4px 10px 4px 10px',
                                    boxShadow: '0 1px 2px rgba(0,0,0,0.02)'
                                }
                            },
                                wp.element.createElement('input', {
                                    type: 'text',
                                    value: umamiEvent,
                                    placeholder: 'Event name',
                                    onChange: (e) => setAttributes({ umamiEvent: e.target.value }),
                                    style: {
                                        width: 230,
                                        minWidth: 80,
                                        fontSize: 14,
                                        height: 28,
                                        border: 'none',
                                        background: 'transparent',
                                        outline: 'none',
                                        marginRight: 0,
                                        borderRadius: 0,
                                    }
                                })
                            )
                        ),
                        wp.element.createElement('div', { style: { marginTop: 16, marginBottom: 8 } },
                            wp.element.createElement('strong', { style: { display: 'block', marginBottom: 6 } }, 'Data Key-Value-Pairs'),
                            umamiDataPairs.length === 0 && wp.element.createElement('div', { style: { color: '#888', fontSize: 13, marginBottom: 8 } }, 'No key-value pairs added yet.'),
                            umamiDataPairs.map((pair, idx) =>
                                wp.element.createElement('div', {
                                    key: idx,
                                    style: {
                                        display: 'flex',
                                        alignItems: 'center',
                                        background: 'transparent',
                                        gap: 1,
                                        marginBottom: 7,
                                        borderRadius: 8,
                                        border: '1px solid #e0e6ef',
                                        padding: '4px 10px 4px 10px',
                                        boxShadow: '0 1px 2px rgba(0,0,0,0.02)'
                                    }
                                },
                                    wp.element.createElement('input', {
                                        type: 'text',
                                        value: pair.key,
                                        placeholder: 'Key',
                                        onChange: (e) => updatePair(idx, 'key', e.target.value.replace(/[^a-zA-Z0-9_\-]/g, '')),
                                        style: {
                                            width: 120,
                                            minWidth: 60,
                                            fontSize: 14,
                                            height: 28,
                                            border: 'none',
                                            background: 'transparent',
                                            outline: 'none',
                                            marginRight: 0,
                                            borderRadius: 0,
                                        }
                                    }),
                                    wp.element.createElement('div', {
                                        style: {
                                            width: 1,
                                            height: 22,
                                            background: '#e0e6ef',
                                            margin: '0 10px',
                                            borderRadius: 1
                                        }
                                    }),
                                    wp.element.createElement('input', {
                                        type: 'text',
                                        value: pair.value,
                                        placeholder: 'Value',
                                        onChange: (e) => updatePair(idx, 'value', e.target.value),
                                        style: {
                                            width: 120,
                                            minWidth: 60,
                                            fontSize: 14,
                                            height: 28,
                                            border: 'none',
                                            background: 'transparent',
                                            outline: 'none',
                                            marginRight: 0,
                                            borderRadius: 0,
                                            transition: 'border-color 0.2s',
                                        }
                                    }),
                                    wp.element.createElement('button', {
                                        type: 'button',
                                        className: 'umami-remove-btn',
                                        onClick: () => removePair(idx),
                                        style: {
                                            height: 28,
                                            width: 28,
                                            padding: 0,
                                            marginLeft: 6,
                                            background: 'none',
                                            border: 'none',
                                            color: '#b32d2e',
                                            borderRadius: '50%',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            cursor: 'pointer',
                                            transition: 'background 0.2s',
                                            fontSize: 18
                                        },
                                        title: 'Remove',
                                        tabIndex: 0
                                    },
                                        wp.element.createElement('span', {
                                            style: { display: 'block', width: 15, height: 15 }
                                        },
                                            wp.element.createElement('svg', {
                                                xmlns: 'http://www.w3.org/2000/svg',
                                                viewBox: '0 0 199.219 199.316',
                                                width: 15,
                                                height: 15,
                                                style: { display: 'block' }
                                            },
                                                wp.element.createElement('g', {},
                                                    wp.element.createElement('rect', {
                                                        height: '199.316',
                                                        opacity: '0',
                                                        width: '199.219',
                                                        x: '0',
                                                        y: '0'
                                                    }),
                                                    wp.element.createElement('path', {
                                                        d: 'M99.6094 199.219C154.59 199.219 199.219 154.59 199.219 99.6094C199.219 44.6289 154.59 0 99.6094 0C44.6289 0 0 44.6289 0 99.6094C0 154.59 44.6289 199.219 99.6094 199.219ZM99.6094 182.617C53.7109 182.617 16.6016 145.508 16.6016 99.6094C16.6016 53.7109 53.7109 16.6016 99.6094 16.6016C145.508 16.6016 182.617 53.7109 182.617 99.6094C182.617 145.508 145.508 182.617 99.6094 182.617Z',
                                                        fill: '#b32d2e',
                                                        fillOpacity: '0.95'
                                                    }),
                                                    wp.element.createElement('path', {
                                                        d: 'M71.875 138.477L138.379 71.875C139.941 70.4102 140.723 68.5547 140.723 66.4062C140.723 62.1094 137.207 58.6914 132.91 58.6914C130.762 58.6914 129.004 59.4727 127.539 61.0352L60.7422 127.441C59.2773 129.004 58.3984 130.762 58.3984 133.008C58.3984 137.402 61.9141 140.918 66.2109 140.918C68.5547 140.918 70.4102 139.941 71.875 138.477ZM127.344 138.477C128.809 139.941 130.664 140.918 132.91 140.918C137.207 140.918 140.723 137.402 140.723 133.008C140.723 130.762 139.941 129.004 138.379 127.441L71.6797 61.0352C70.2148 59.4727 68.3594 58.6914 66.2109 58.6914C61.9141 58.6914 58.3984 62.1094 58.3984 66.4062C58.3984 68.5547 59.2773 70.4102 60.7422 71.875Z',
                                                        fill: '#b32d2e',
                                                        fillOpacity: '0.95'
                                                    })
                                                )
                                            )
                                        )
                                    )
                                )
                            ),
                            wp.element.createElement('button', {
                                type: 'button',
                                className: 'button',
                                onClick: addPair,
                                style: {
                                    marginTop: 8,
                                    width: '100%',
                                    background: '#e7f3ff',
                                    border: '1px solid #b6d4fe',
                                    color: '#0969da',
                                    fontWeight: 500,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    gap: 8,
                                    fontSize: 15
                                },
                                title: 'Key-Value'
                            },
                                wp.element.createElement('span', { style: { display: 'flex', alignItems: 'center', height: 18 } },
                                    wp.element.createElement('svg', {
                                        xmlns: 'http://www.w3.org/2000/svg',
                                        viewBox: '0 0 199.219 199.316',
                                        width: 16,
                                        height: 16,
                                        style: { display: 'block' }
                                    },
                                        wp.element.createElement('g', {},
                                            wp.element.createElement('rect', {
                                                height: '199.316',
                                                opacity: '0',
                                                width: '199.219',
                                                x: '0',
                                                y: '0'
                                            }),
                                            wp.element.createElement('path', {
                                                d: 'M99.6094 199.219C154.59 199.219 199.219 154.59 199.219 99.6094C199.219 44.6289 154.59 0 99.6094 0C44.6289 0 0 44.6289 0 99.6094C0 154.59 44.6289 199.219 99.6094 199.219ZM99.6094 182.617C53.7109 182.617 16.6016 145.508 16.6016 99.6094C16.6016 53.7109 53.7109 16.6016 99.6094 16.6016C145.508 16.6016 182.617 53.7109 182.617 99.6094C182.617 145.508 145.508 182.617 99.6094 182.617Z',
                                                fill: 'blue',
                                                fillOpacity: '0.85'
                                            }),
                                            wp.element.createElement('path', {
                                                d: 'M107.715 136.426L107.715 62.5977C107.715 57.6172 104.297 54.1992 99.4141 54.1992C94.6289 54.1992 91.3086 57.6172 91.3086 62.5977L91.3086 136.426C91.3086 141.309 94.6289 144.727 99.4141 144.727C104.297 144.727 107.715 141.406 107.715 136.426ZM62.5977 107.617L136.523 107.617C141.406 107.617 144.824 104.395 144.824 99.6094C144.824 94.7266 141.406 91.3086 136.523 91.3086L62.5977 91.3086C57.6172 91.3086 54.2969 94.7266 54.2969 99.6094C54.2969 104.395 57.6172 107.617 62.5977 107.617Z',
                                                fill: 'blue',
                                                fillOpacity: '0.85'
                                            })
                                        )
                                    )
                                ),
                                'Key-Value'
                            )
                        )
                    )
                )
            );
        };
    }, 'withInspectorControls');
    addFilter('editor.BlockEdit', 'umami/extend-button/inspector', withInspectorControls);

    function addExtraProps(saveProps, blockType, attributes) {
    if (blockType.name === 'core/button' || blockType.name === 'core/paragraph' || blockType.name === 'core/post-excerpt' || blockType.name === 'core/heading') {
            if (attributes.umamiEvent) {
                saveProps['data-umami-event'] = attributes.umamiEvent;
            }
            if (Array.isArray(attributes.umamiDataPairs)) {
                attributes.umamiDataPairs.forEach(pair => {
                    if (pair.key && pair.value) {
                        saveProps['data-umami-data-' + pair.key] = pair.value;
                    }
                });
            }
        }
        return saveProps;
    }
    wp.hooks.addFilter('blocks.getSaveContent.extraProps', 'umami/extend-button/save-props', addExtraProps);
})();
