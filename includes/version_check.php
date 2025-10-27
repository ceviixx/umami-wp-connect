<?php
function umami_connect_get_version_info() {
    $main_plugin_file = dirname(__DIR__) . '/umami-connect.php';
    
    if (!file_exists($main_plugin_file)) {
        return [
            'current' => 'unknown',
            'latest' => '–',
            'github_url' => 'https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/releases/latest',
        ];
    }
    
    $plugin_data = get_file_data($main_plugin_file, ['Version' => 'Version']);
    $current_version = isset($plugin_data['Version']) ? $plugin_data['Version'] : 'unknown';
    
    $latest_release = get_transient('umami_connect_latest_release');
    
    if ($latest_release === false) {
        $github_api_url = 'https://api.github.com/repos/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/releases/latest';
        $args = [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'umami-wp-connect-plugin'
            ],
            'timeout' => 5
        ];
        
        $response = wp_remote_get($github_api_url, $args);
        
        if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($body['tag_name'])) {
                $latest_release = esc_html($body['tag_name']);
            }
        } elseif (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] === 404) {
            $releases_url = 'https://api.github.com/repos/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/releases';
            $response2 = wp_remote_get($releases_url, $args);
            if (!is_wp_error($response2) && isset($response2['response']['code']) && $response2['response']['code'] === 200) {
                $body2 = json_decode(wp_remote_retrieve_body($response2), true);
                if (is_array($body2) && !empty($body2[0]['tag_name'])) {
                    $latest_release = esc_html($body2[0]['tag_name']);
                }
            }
        }
        
        if ($latest_release && $latest_release !== '–') {
            set_transient('umami_connect_latest_release', $latest_release, 6 * HOUR_IN_SECONDS);
        }
    }
    
    return [
        'current' => $current_version,
        'latest' => $latest_release ?: '–',
        'github_url' => 'https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/releases/latest',
    ];
}

function umami_connect_version_compare($v1, $v2) {
    $v1 = trim(ltrim($v1, "vV"));
    $v2 = trim(ltrim($v2, "vV"));
    return version_compare($v1, $v2);
}

function umami_connect_has_update() {
    $info = umami_connect_get_version_info();
    if ($info['latest'] === '–') return false;
    return umami_connect_version_compare($info['current'], $info['latest']) === -1;
}
