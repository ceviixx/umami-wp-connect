<?php

if (isset($_POST['umami_connect_self_update']) && check_admin_referer('umami_connect_self_update', 'umami_connect_self_update_nonce')) {
    $update_version = sanitize_text_field($_POST['umami_update_version'] ?? '');
    $release_api_url = 'https://api.github.com/repos/' . UMAMI_CONNECT_GITHUB_USER . '/'. UMAMI_CONNECT_GITHUB_REPO . '/releases/tags/' . urlencode($update_version);
    $args = [
        'headers' => [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'umami-wp-connect-plugin'
        ],
        'timeout' => 10
    ];
    $response = wp_remote_get($release_api_url, $args);
    $zip_url = '';
    if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] === 200) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!empty($body['assets']) && is_array($body['assets'])) {
            foreach ($body['assets'] as $asset) {
                if (!empty($asset['name']) && !empty($asset['browser_download_url']) &&
                    preg_match('/^umami-wp-connect-.*\\.zip$/', $asset['name'])) {
                    $zip_url = $asset['browser_download_url'];
                    break;
                }
            }
        }
        if (!$zip_url && !empty($body['zipball_url'])) {
            $zip_url = $body['zipball_url'];
        }
    }
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['umami_connect_self_update'])
        && check_admin_referer('umami_connect_self_update', 'umami_connect_self_update_nonce')
    ) {
        if ($zip_url) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        if (!defined('FS_METHOD')) {
            define('FS_METHOD', 'direct');
        }
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $tmp = download_url($zip_url, 60);
        $plugin_dir = dirname(__DIR__, 2);
        $backup_zip = $plugin_dir . '-backup-' . date('Ymd-His') . '.zip';
        $unzip_dir = $plugin_dir . '-update';
        $rollback_error = false;
        if (!is_wp_error($tmp)) {
            if (!file_exists($backup_zip)) {
                if (class_exists('ZipArchive')) {
                    $zip = new ZipArchive();
                    if ($zip->open($backup_zip, ZipArchive::CREATE) === TRUE) {
                        $dir = new RecursiveDirectoryIterator($plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS);
                        $files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::SELF_FIRST);
                        foreach ($files as $file) {
                            $filePath = $file->getRealPath();
                            $localPath = substr($filePath, strlen($plugin_dir) + 1);
                            if ($file->isDir()) {
                                $zip->addEmptyDir($localPath);
                            } else {
                                $zip->addFile($filePath, $localPath);
                            }
                        }
                        $zip->close();
                    }
                }
            }
            if (file_exists($unzip_dir)) {
                $it = new RecursiveDirectoryIterator($unzip_dir, RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                foreach($files as $file) {
                    if ($file->isDir()) {
                        @rmdir($file->getRealPath());
                    } else {
                        @unlink($file->getRealPath());
                    }
                }
                @rmdir($unzip_dir);
            }
            $result = unzip_file($tmp, $unzip_dir);
            
            @unlink($tmp);
            if (!is_wp_error($result)) {
                $entries = glob($unzip_dir . '/*', GLOB_ONLYDIR);
                if ($entries && is_dir($entries[0])) {
                    $new_dir = $entries[0];
                    $plugin_dir_parent = dirname($plugin_dir);
                    $plugin_dir_name = basename($plugin_dir);
                    $archive_dir = $plugin_dir . '-archived-' . date('Ymd-His');

                    if (@rename($plugin_dir, $archive_dir)) {
                        if (@rename($new_dir, $plugin_dir)) {
                            @rmdir($unzip_dir);
                            $main_plugin_file_rel = basename(dirname($plugin_dir)) . '/' . basename($plugin_dir) . '/umami-connect.php';
                            if (!is_plugin_active($main_plugin_file_rel)) {
                                activate_plugin($main_plugin_file_rel);
                            }
                            $it = new RecursiveDirectoryIterator($archive_dir, RecursiveDirectoryIterator::SKIP_DOTS);
                            $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                            foreach($files as $file) {
                                if ($file->isDir()) {
                                    @rmdir($file->getRealPath());
                                } else {
                                    @unlink($file->getRealPath());
                                }
                            }
                            @rmdir($archive_dir);

                            echo '<div class="notice notice-success"><b>Update successful!</b> The plugin was updated and reactivated.</div>';
                        } else {
                            @rename($archive_dir, $plugin_dir);
                            echo '<div class="notice notice-error"><b>Error:</b> Could not rename new plugin folder. Rollback performed.</div>';
                        }
                    } else {
                        echo '<div class="notice notice-error"><b>Error:</b> Could not archive old plugin folder.</div>';
                    }
                } else {
                    echo '<div class="notice notice-error"><b>Error:</b> Unpacked directory not found.</div>';
                }
            } else {
                $rollback_error = false;
                if (class_exists('ZipArchive') && file_exists($backup_zip)) {
                    $zip = new ZipArchive();
                    if ($zip->open($backup_zip) === TRUE) {
                        $zip->extractTo(dirname($plugin_dir));
                        $zip->close();
                    } else {
                        $rollback_error = true;
                    }
                } else {
                    $rollback_error = true;
                }
                $fs_hint = '';
                if (is_wp_error($result) && strpos($result->get_error_message(), 'filesystem') !== false) {
                    $fs_hint = '<br><b>Hint:</b> Check the file system permissions and possibly <code>wp-config.php</code> for <code>define(\'FS_METHOD\', \'direct\');</code>';
                }
                echo '<div class="notice notice-error"><b>Error while unpacking:</b> ' . esc_html($result->get_error_message()) . ($rollback_error ? ' Rollback failed!' : ' Backup was restored.') . $fs_hint . '</div>';
            }
        } else {
            echo '<div class="notice notice-error"><b>Error during download:</b> ' . esc_html($tmp->get_error_message()) . '</div>';
        }
        } else {
            echo '<div class="notice notice-error"><b>Error:</b> ZIP-URL could not be determined.</div>';
        }
    }
}









$main_plugin_file = dirname(__DIR__, 2) . '/umami-connect.php';
$plugin_data = get_file_data($main_plugin_file, ['Version' => 'Version']);
$current_version = isset($plugin_data['Version']) ? $plugin_data['Version'] : 'unbekannt';
$github_url = 'https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/releases/latest';


$github_api_url = 'https://api.github.com/repos/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/releases';
$args = [
    'headers' => [
        'Accept' => 'application/vnd.github.v3+json',
        'User-Agent' => 'umami-wp-connect-plugin'
    ],
    'timeout' => 8
];
$releases = [];
$latest_version = '–';
$latest_body = '';

$response = wp_remote_get($github_api_url, $args);
if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] === 200) {
    $releases = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($releases) && is_array($releases)) {
        $latest_version = $releases[0]['tag_name'] ?? '–';
        $latest_body = $releases[0]['body'] ?? '';
    }
}

echo '<div class="wrap"><h3>Support</h3>';
echo '<p><b>Current Version:</b> ' . esc_html($current_version) . '</p>';
echo '<p><b>Latest release on GitHub:</b> ' . esc_html($latest_version) . ' ';
echo '<a href="' . esc_url($github_url) . '" target="_blank">(Releases on GitHub)</a></p>';
if ($latest_body) {
    echo '<div style="background:#f8f8f8;border:1px solid #ddd;padding:10px;margin-bottom:15px;"><b>Changelog:</b><br>';
    echo nl2br(esc_html($latest_body));
    echo '</div>';
}

if (!empty($releases) && is_array($releases)) {
    echo '<form method="post">';
    echo '<input type="hidden" name="umami_connect_self_update" value="1">';
    wp_nonce_field('umami_connect_self_update', 'umami_connect_self_update_nonce');
    echo '<label for="umami_update_version"><b>Select version to install:</b></label> ';
    echo '<select name="umami_update_version" id="umami_update_version">';
    foreach ($releases as $rel) {
        $tag = $rel['tag_name'] ?? '';
        echo '<option value="' . esc_attr($tag) . '"' . ($tag === $latest_version ? ' selected' : '') . '>' . esc_html($tag) . '</option>';
    }
    echo '</select> ';
    echo '<button type="submit" class="button button-primary">Perform update</button>';
    echo '</form>';
}
echo '</div>';