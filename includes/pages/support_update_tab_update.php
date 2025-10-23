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
                    $it = new RecursiveDirectoryIterator($plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS);
                    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                    foreach($files as $file) {
                        if ($file->isDir()) {
                            @rmdir($file->getRealPath());
                        } else {
                            @unlink($file->getRealPath());
                        }
                    }
                    @rmdir($plugin_dir);
                    if (@rename($entries[0], $plugin_dir)) {
                        @rmdir($unzip_dir);
                        $main_plugin_file_rel = basename(dirname($plugin_dir)) . '/' . basename($plugin_dir) . '/umami-connect.php';
                        if (!is_plugin_active($main_plugin_file_rel)) {
                            activate_plugin($main_plugin_file_rel);
                        }
                        echo '<div class="notice notice-success"><b>Update successful!</b> The plugin was updated and reactivated. The page will reload...</div>';
                        echo '<script>setTimeout(function(){ location.reload(); }, 1800);</script>';
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
                        echo '<div class="notice notice-error"><b>Error:</b> Plugin could not be replaced.' . ($rollback_error ? ' Rollback failed!' : ' Backup was restored.') . '</div>';
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








$main_plugin_file = dirname(__DIR__, 2) . '/umami-connect.php';
$plugin_data = get_file_data($main_plugin_file, ['Version' => 'Version']);
$current_version = isset($plugin_data['Version']) ? $plugin_data['Version'] : 'unknown';
$github_url = 'https://github.com/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/releases/latest';

$latest_release = '–';
$github_api_url = 'https://api.github.com/repos/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/releases/latest';
$args = [
    'headers' => [
        'Accept' => 'application/vnd.github.v3+json',
        'User-Agent' => 'umami-wp-connect-plugin'
    ],
    'timeout' => 5
];
$response = wp_remote_get($github_api_url, $args);
$latest_release_description = '';
if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] === 200) {
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($body['tag_name'])) {
        $latest_release = esc_html($body['tag_name']);
        if (!empty($body['body'])) {
            $latest_release_description = wp_kses_post(nl2br($body['body']));
        }
    } else {
        $latest_release = '<span style="color:red">Error: tag_name missing in API response</span>';
    }
} else if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] === 404) {
    $releases_url = 'https://api.github.com/repos/' . UMAMI_CONNECT_GITHUB_USER . '/' . UMAMI_CONNECT_GITHUB_REPO . '/releases';
    $response2 = wp_remote_get($releases_url, $args);
    if (!is_wp_error($response2) && isset($response2['response']['code']) && $response2['response']['code'] === 200) {
        $body2 = json_decode(wp_remote_retrieve_body($response2), true);
        if (is_array($body2) && !empty($body2[0]['tag_name'])) {
            $latest_release = esc_html($body2[0]['tag_name']);
            if (!empty($body2[0]['body'])) {
                $latest_release_description = wp_kses_post(nl2br($body2[0]['body']));
            }
        } else {
            $latest_release = '<span style="color:red">Error: No release found</span>';
        }
    } else {
        $error_msg = 'Unknown error';
        if (is_wp_error($response2)) {
            $error_msg = $response2->get_error_message();
        } elseif (isset($response2['response']['code'])) {
            $error_msg = 'HTTP-Code: ' . $response2['response']['code'];
        }
        $latest_release = '<span style="color:red">Error during fallback: ' . esc_html($error_msg) . '</span>';
    }
} else {
    $error_msg = 'Unknown error';
    if (is_wp_error($response)) {
        $error_msg = $response->get_error_message();
    } elseif (isset($response['response']['code'])) {
        $error_msg = 'HTTP-Code: ' . $response['response']['code'];
    }
    $latest_release = '<span style="color:red">Error while loading: ' . esc_html($error_msg) . '</span>';
}

function umami_version_compare($v1, $v2) {
    $v1 = ltrim($v1, 'vV');
    $v2 = ltrim($v2, 'vV');
    return version_compare($v1, $v2);
}
$is_update_available = (
    $current_version !== 'unknown'
    && $latest_release
    && strpos($latest_release, 'Fehler') === false
    && $latest_release !== '–'
    && umami_version_compare($current_version, $latest_release) === -1
);
$is_reinstall_available = (
    $current_version !== 'unknown'
    && $latest_release
    && strpos($latest_release, 'Fehler') === false
    && $latest_release !== '–'
    && umami_version_compare($current_version, $latest_release) === 0
);
$is_admin = current_user_can('update_plugins');
?>


<h3>Update & Reinstall</h3>

<?php if ($is_update_available && $is_admin): ?>
    <div style="background:#fff3cd; border:1px solid #ffeeba; color:#856404; border-radius:5px; padding:14px 18px; margin-bottom:18px; font-size:15px;">
        <b>Warning:</b> Updating the plugin may result in data loss or unexpected behavior. Please make sure to backup your data and settings before proceeding. If you have custom modifications, they may be overwritten. Proceed with caution!
    </div>
<?php elseif ($is_reinstall_available && $is_admin): ?>
    <div style="background:#fff3cd; border:1px solid #ffeeba; color:#856404; border-radius:5px; padding:14px 18px; margin-bottom:18px; font-size:15px;">
        <b>Warning:</b> Reinstalling the plugin will overwrite all plugin files and may result in data loss or unexpected behavior. Please backup your data and settings before proceeding. Custom modifications will be lost. Proceed with caution!
    </div>
<?php endif; ?>

<ul style="margin-bottom:18px; font-size:15px;">
    <li>Current version: <b><?php echo esc_html($current_version); ?></b></li>
    <li>Latest release: <?php if ($latest_release !== '–') { ?><a href="<?php echo esc_url($github_url); ?>" target="_blank"><b><?php echo $latest_release; ?></b></a><?php } else { echo '–'; } ?></li>
</ul>
<?php if (!empty($latest_release_description)): ?>
    <div style="background:#f8f8ff; border:1.5px solid #b3c6e0; border-radius:7px; padding:18px 22px; margin-bottom:20px; max-width:720px;">
        <div style="font-weight:bold; margin-bottom:4px; color:#1a237e; font-size:16px; letter-spacing:0.5px;">
            Release Notes for <span style="color:#1565c0;">Latest Release</span> <span style="background:#e3f2fd; color:#1976d2; border-radius:4px; padding:2px 8px; font-size:13px; margin-left:6px; vertical-align:middle;"><b><?php echo esc_html($latest_release); ?></b></span>
        </div>
        <div style="font-size:15px; color:#222; line-height:1.6; margin-top:8px;">
            <?php echo $latest_release_description; ?>
        </div>
    </div>
<?php endif; ?>
<?php if ($is_update_available && $is_admin): ?>
    <form method="post" style="margin-bottom:24px;">
        <?php wp_nonce_field('umami_connect_self_update', 'umami_connect_self_update_nonce'); ?>
        <input type="hidden" name="umami_update_version" value="<?php echo esc_attr($latest_release); ?>">
        <button type="submit" name="umami_connect_self_update" class="button button-primary">
            Update to latest version
        </button>
    </form>
<?php elseif ($is_reinstall_available && $is_admin): ?>
    <form method="post" style="margin-bottom:24px;">
        <?php wp_nonce_field('umami_connect_self_update', 'umami_connect_self_update_nonce'); ?>
        <input type="hidden" name="umami_update_version" value="<?php echo esc_attr($latest_release); ?>">
        <button type="submit" name="umami_connect_self_update" class="button">
            Reinstall current version
        </button>
    </form>
<?php endif; ?>