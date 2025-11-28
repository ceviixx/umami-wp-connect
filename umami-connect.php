<?php
/**
 * Plugin Name: umami Connect
 * Description: Simple integration of Umami Analytics in WordPress for  Cloud and Self-hosted.
 * Version: 0.0.0
 * Author: ceviixx
 * Author URI: https://ceviixx.github.io/
 * Plugin URI: https://github.com/ceviixx/umami-wp-connect
 * Tested up to: 6.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UMAMI_CONNECT_PLUGIN_FILE', __FILE__ );

require_once plugin_dir_path( __FILE__ ) . 'includes/core/autoloader.php';
Umami_Connect_Autoloader::init( __FILE__ );
// Import GitHub update logic
require_once plugin_dir_path( __FILE__ ) . 'includes/core/github.php';
Umami_Connect_Github::init();
