<?php
function umami_connect_support_page() {
    $active_tab = isset($_GET['tab']) && $_GET['tab'] === 'update' ? 'update' : 'support';

    echo '<div class="wrap">';
    echo '<h1><b>umami Connect</b></h1>';
    echo '<h2 class="nav-tab-wrapper" style="margin-bottom:18px;">';
    echo '<a href="' . esc_url(admin_url('admin.php?page=umami_connect_support&tab=support')) . '" class="nav-tab' . ($active_tab === 'support' ? ' nav-tab-active' : '') . '">Support</a>';
    echo '<a href="' . esc_url(admin_url('admin.php?page=umami_connect_support&tab=update')) . '" class="nav-tab' . ($active_tab === 'update' ? ' nav-tab-active' : '') . '">Update</a>';
    echo '</h2>';

    if ($active_tab === 'update') {
        include __DIR__ . '/support_update_tab_update.php';
    } else {
        include __DIR__ . '/support_update_tab_support.php';
    }
    echo '</div>';
}