<?php
add_filter('plugin_action_links_' . plugin_basename(dirname(__DIR__, 2) . '/umami-connect.php'), function($links) {
    $settings_link = '<a href="admin.php?page=umami_connect">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
});
?>