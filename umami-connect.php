<?php
/**
 * Plugin Name: umami Connect
 * Description: Simple integration of Umami Analytics in WordPress for  Cloud and Self-hosted.
 * Version: v0.0.0
 * Author: ceviixx
 * Author URI: https://ceviixx.github.io/
 * Plugin URI: https://github.com/ceviixx/umami-wp-connect
 */

if (!defined('ABSPATH')) exit;

# Constants
require_once plugin_dir_path(__FILE__) . 'includes/constants.php';

// Sidebar navigation
include_once plugin_dir_path(__FILE__) . 'includes/menu.php';

// Pages
include_once plugin_dir_path(__FILE__) . 'includes/pages/general.php';
include_once plugin_dir_path(__FILE__) . 'includes/pages/self_protection.php';
include_once plugin_dir_path(__FILE__) . 'includes/pages/automation.php';
include_once plugin_dir_path(__FILE__) . 'includes/pages/support_update.php';
include_once plugin_dir_path(__FILE__) . 'includes/pages/events_overview.php';

// Filters
include_once plugin_dir_path(__FILE__) . 'includes/add_filter/plugin_action_links.php';
include_once plugin_dir_path(__FILE__) . 'includes/add_filter/render_block.php';
include_once plugin_dir_path(__FILE__) . 'includes/add_filter/autotrack.php';

// Dashboard 
include_once plugin_dir_path(__FILE__) . 'includes/dashboard/widget.php';

// Non excluded functions...

add_action('admin_init', function () {
    register_setting('umami_connect_general', 'umami_script_loading', [
        'type' => 'string',
        'sanitize_callback' => function ($v) {
            $v = strtolower(trim((string)$v));
            return in_array($v, ['async', 'defer'], true) ? $v : 'async';
        },
        'default' => 'async',
    ]);

    register_setting('umami_connect_general', 'umami_mode', [
        'type' => 'string',
        'sanitize_callback' => function ($v) {
            $v = strtolower(trim((string)$v));
            return in_array($v, ['cloud', 'self'], true) ? $v : 'cloud';
        },
        'default' => 'cloud',
    ]);

    register_setting('umami_connect_general', 'umami_host', [
        'type' => 'string',
        'sanitize_callback' => function ($value) {
            $mode = get_option('umami_mode', 'cloud'); 
            $value = trim((string)$value);

            if ($mode === 'self' && $value === '') {
                add_settings_error(
                    'umami_host',
                    'umami_host_required',
                    'For self-hosted, a Host URL is required.',
                    'error'
                );
                return get_option('umami_host', '');
            }

            if ($value !== '') {
                if (!preg_match('~^https?://~i', $value)) {
                    $value = 'https://' . $value;
                }
                $value = esc_url_raw(rtrim($value, '/'));
            }
            return $value;
        },
        'default' => UMAMI_CONNECT_DEFAULT_HOST,
    ]);

    register_setting('umami_connect_general', 'umami_website_id', [
        'type'              => 'string',
        'sanitize_callback' => function ($value) {
            $value = trim((string)$value);
            if ($value === '') return '';
            if (preg_match('/^[a-f0-9-]{10,}$/i', $value)) return $value;
            return preg_replace('/[^a-zA-Z0-9\-_]/', '', $value);
        },
        'default' => '',
    ]);

    register_setting('umami_connect_automation', 'umami_autotrack_links', [
        'type' => 'string',
        'sanitize_callback' => fn($v) => $v ? '1' : '0',
        'default' => '1',
    ]);

    register_setting('umami_connect_automation', 'umami_autotrack_buttons', [
        'type' => 'string',
        'sanitize_callback' => fn($v) => $v ? '1' : '0',
        'default' => '1',
    ]);

    register_setting('umami_connect_automation', 'umami_autotrack_forms', [
        'type' => 'string',
        'sanitize_callback' => fn($v) => $v ? '1' : '0',
        'default' => '1',
    ]);

    add_settings_section(
        'umami_connect_main',
        '',
        function () {
            echo '<p>Select Cloud or Self-hosted. In Cloud mode the base host <code>' . esc_html(UMAMI_CONNECT_DEFAULT_HOST) . '</code> is used automatically.</p>';
            if (empty(get_option('permalink_structure'))) {
                echo '<div class="notice notice-warning" style="margin:16px 0 20px 0; padding:12px 16px 12px 12px; display:flex; align-items:center; gap:12px;">';
                echo '<span class="dashicons dashicons-warning" style="font-size:22px; color:#d48806;"></span>';
                echo '<div>';
                echo '<strong style="color:#d48806; font-size:15px;">Permalinks are not enabled</strong>';
                echo '<div style="color:#b32d2e; font-size:12px; margin-top:2px;">Page views cannot be tracked correctly. <a href="options-permalink.php" style="color:#d48806; text-decoration:underline;">Enable permalinks</a> for optimal tracking.</div>';
                echo '</div>';
                echo '</div>';
            } else {
                $structure = get_option('permalink_structure');
                echo '<div class="notice notice-success" style="margin:16px 0 20px 0; padding:12px 16px 12px 12px; display:flex; align-items:center; gap:12px;">';
                echo '<span class="dashicons dashicons-yes" style="font-size:22px; color:#389e0d;"></span>';
                echo '<div>';
                echo '<strong style="color:#227a13; font-size:15px;">Permalinks are enabled</strong>';
                echo '<div style="color:#444; font-size:13px; margin:2px 0 0 0;">Current structure: <code style="font-size:13px;">' . esc_html($structure) . '</code></div>';
                echo '</div>';
                echo '</div>';
            }
        },
        'umami_connect'
    );

    add_settings_field(
        'umami_mode',
        'Mode',
        function () {
            $mode = esc_attr(get_option('umami_mode', 'cloud'));
            ?>
            <select name="umami_mode" id="umami_mode">
                <option value="cloud" <?php selected($mode, 'cloud'); ?>>Cloud</option>
                <option value="self"  <?php selected($mode, 'self');  ?>>Self-hosted</option>
            </select>
            <p class="description">Cloud uses <?php echo esc_html(UMAMI_CONNECT_DEFAULT_HOST); ?>. Self-hosted allows a custom host.</p>
            <?php
        },
        'umami_connect',
        'umami_connect_main'
    );
    
    add_settings_field(
        'umami_host',
        'Host URL',
        function () {
            $value = esc_attr(get_option('umami_host', UMAMI_CONNECT_DEFAULT_HOST));
            echo '<input required type="url" name="umami_host" id="umami_host" value="' . $value . '" class="regular-text" placeholder="https://umami.example.com">';
            echo '<p class="description">Base URL of your Umami server (without <code>/script.js</code>). We append the script filename if it is missing.</p>';
        },
        'umami_connect',
        'umami_connect_main'
    );

    add_settings_field(
        'umami_website_id',
        'Website ID',
        function () {
            $value = esc_attr(get_option('umami_website_id', ''));
            echo '<input required type="text" name="umami_website_id" id="umami_website_id" value="' . $value . '" class="regular-text" placeholder="Website UUID">';
            echo '<div id="umami-website-id-error" style="color:#b32d2e; margin-top:8px; display:none;"></div>';
            echo '<p class="description">Your Umami Website ID.</p>';
        },
        'umami_connect',
        'umami_connect_main'
    );

    add_settings_field(
        'umami_script_loading',
        'Script loading',
        function () {
            $mode = esc_attr(get_option('umami_script_loading', 'defer'));
            ?>
            <select name="umami_script_loading" id="umami_script_loading">
                <option value="defer" <?php selected($mode, 'defer'); ?>>defer</option>
                <option value="async" <?php selected($mode, 'async'); ?>>async</option>
            </select>
            <ul class="description">
                <li><b>defer</b> to execute after the HTML is parsed (recommended for most cases).</li>
                <li><b>async</b> to load the script as soon as possible (may execute before DOM is ready).</li>
            </ul>
            <?php
        },
        'umami_connect',
        'umami_connect_main'
    );

    add_settings_section(
        'umami_connect_self_protection',
        '',
        function () {
            echo '<p>Controls whether logged-in users (e.g., admins, editors) are <em>not</em> tracked by default.</p>';
        },
        'umami_connect_self_protection'
    );

    add_settings_section(
        'umami_connect_automation',
        '',
        function () {
            echo '<p>Automatic client-side tracking for links, buttons and forms. You can override on elements using <code>data-umami-event</code> and <code>data-umami-data</code>.</p>';
        },
        'umami_connect_automation'
    );

    add_settings_field('umami_autotrack_links', 'Auto-track links', function () {
        $v = get_option('umami_autotrack_links', '1');
        echo '<label><input type="checkbox" name="umami_autotrack_links" value="1" '.checked($v,'1',false).'> Add default click events to <code>&lt;a&gt;</code> links</label>';
    }, 'umami_connect_automation', 'umami_connect_automation');

    add_settings_field('umami_autotrack_buttons', 'Auto-track buttons', function () {
        $v = get_option('umami_autotrack_buttons', '1');
        echo '<label><input type="checkbox" name="umami_autotrack_buttons" value="1" '.checked($v,'1',false).'> Add default click events to <code>&lt;button&gt;</code> elements</label>';
    }, 'umami_connect_automation', 'umami_connect_automation');

    add_settings_field('umami_autotrack_forms', 'Auto-track forms', function () {
        $v = get_option('umami_autotrack_forms', '1');
        echo '<label><input type="checkbox" name="umami_autotrack_forms" value="1" '.checked($v,'1',false).'> Send a submit event on <code>&lt;form&gt;</code> submit</label>';
    }, 'umami_connect_automation', 'umami_connect_automation');

    add_settings_field('umami_consent_required', 'Require consent', function () {
        $v = get_option('umami_consent_required', '0');
        echo '<label><input type="checkbox" name="umami_consent_required" value="1" '.checked($v,'1',false).'> Only run Umami after consent was granted</label>';
        echo '<p class="description">If enabled, tracking waits for the consent flag below to be truthy.</p>';
    }, 'umami_connect', 'umami_connect_consent');

    add_settings_field('umami_consent_flag', 'Consent flag (global)', function () {
        $v = esc_attr(get_option('umami_consent_flag', 'umamiConsentGranted'));
        echo '<input type="text" name="umami_consent_flag" value="'.$v.'" class="regular-text" placeholder="umamiConsentGranted">';
        echo '<p class="description">Example: <code>window.umamiConsentGranted = true</code> after your CMP grants consent.</p>';
    }, 'umami_connect', 'umami_connect_consent');

    add_settings_field('umami_debug', 'Verbose console logging', function () {
        $v = get_option('umami_debug', '0');
        echo '<label><input type="checkbox" name="umami_debug" value="1" '.checked($v,'1',false).'> Enable debug logs in the browser console</label>';
    }, 'umami_connect', 'umami_connect_debug');

    register_setting('umami_connect_self', 'umami_exclude_logged_in', [
        'type'              => 'string',
        'sanitize_callback' => function ($value) {
            return $value ? '1' : '0';
        },
        'default' => '1', // enabled by default
    ]);

    add_settings_field(
        'umami_exclude_logged_in',
        'Do not track my own visits',
        function () {
            $value = get_option('umami_exclude_logged_in', '1');
            ?>
            <label>
                <input type="checkbox" name="umami_exclude_logged_in" id="umami_exclude_logged_in" value="1" <?php checked($value, '1'); ?>>
                Automatically exclude logged-in users from Umami
            </label>
            <p class="description">Sets <code>localStorage.umami.disabled = "true"</code> for logged-in users. Disable if you want to measure admin visits as well.</p>
            <?php
        },
        'umami_connect_self_protection',
        'umami_connect_self_protection'
    );
    
});



register_activation_hook(__FILE__, function () {
    if (get_option('umami_mode', '') === '') {
        add_option('umami_mode', 'cloud'); // allowed: 'cloud' | 'self'
    }
    if (get_option('umami_host', '') === '') {
        add_option('umami_host', UMAMI_CONNECT_DEFAULT_HOST);
    }
    add_option('umami_website_id', get_option('umami_website_id', ''));

    if (get_option('umami_exclude_logged_in', '') === '') {
        add_option('umami_exclude_logged_in', '1');
    }
});






add_action('wp_head', function () {
    if (is_admin()) return; // never load in WP admin
    $mode       = get_option('umami_mode', 'cloud');
    $hostInput  = get_option('umami_host', UMAMI_CONNECT_DEFAULT_HOST);
    $website_id = get_option('umami_website_id', '');

    if (!$website_id) return;

    $baseHost = ($mode === 'self')
        ? ($hostInput ?: '')
        : UMAMI_CONNECT_DEFAULT_HOST;

    if (!$baseHost) return;

    $scriptUrl = $baseHost;
    if (!preg_match('~\.js(\?.*)?$~i', $scriptUrl)) {
        $scriptUrl = rtrim($scriptUrl, '/') . '/script.js';
    }

    $script_loading = get_option('umami_script_loading', 'async');
    $attr = ($script_loading === 'defer') ? 'defer' : 'async';
    echo "\n" . '<script ' . $attr . ' src="' . esc_url($scriptUrl) . '" data-website-id="' . esc_attr($website_id) . '"></script>' . "\n";
}, 20);

add_action('wp_enqueue_scripts', function () {
    if (is_admin()) return;

    wp_enqueue_script(
        'umami-autotrack',
        plugins_url('assets/umami-autotrack.js', __FILE__),
        [],
        '1.2.0',
        true
    );

    $cfg = [
        'autotrackLinks' => get_option('umami_autotrack_links', '1') === '1',
        'autotrackButtons' => get_option('umami_autotrack_buttons', '1') === '1',
        'autotrackForms' => get_option('umami_autotrack_forms', '1') === '1',
        'consentRequired' => get_option('umami_consent_required', '0') === '1',
        'consentFlag' => (string)get_option('umami_consent_flag', 'umamiConsentGranted'),
        'debug' => get_option('umami_debug', '0') === '1',
    ];
    wp_add_inline_script('umami-autotrack', 'window.__UMAMI_CONNECT__ = '.wp_json_encode($cfg).';', 'before');
}, 30);

add_action('wp_head', function () {
    if (is_admin()) return;
    $exclude = get_option('umami_exclude_logged_in', '1') === '1';
    if ($exclude && is_user_logged_in()) {
        echo "<script>try{localStorage.setItem('umami.disabled','true');}catch(e){}</script>\n";
    } elseif (!$exclude && is_user_logged_in()) {
        echo "<script>try{localStorage.removeItem('umami.disabled');}catch(e){}</script>\n";
    }
}, 5);

add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_script(
        'umami-extend-core-button',
        plugins_url('assets/editor/umami-extend.js', __FILE__),
        [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-compose', 'wp-hooks', 'wp-rich-text' ],
        '1.0.8',
        true
    );

});



