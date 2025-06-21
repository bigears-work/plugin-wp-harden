<?php
/**
 * Plugin Name: WP Harden
 * Description: Simple Plugin to harden WP installations
 * Version: 0.2.0
 * Author: Big Ears Webagentur
 * Author URI: https://bigears.work
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

// 1. Disable file editing in admin
define('DISALLOW_FILE_EDIT', true);

// 2. Block PHP execution in /uploads/
register_activation_hook(__FILE__, function () {
    $htaccess = WP_CONTENT_DIR . '/uploads/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Options -ExecCGI\n<FilesMatch \"\\.php$\">\nDeny from all\n</FilesMatch>");
    }
});

// 3. Remove WordPress version from head
add_filter('the_generator', '__return_empty_string');

// 4. Disable XML-RPC
add_filter('xmlrpc_enabled', '__return_false');
add_filter('xmlrpc_methods', function () {
    return [];
});
add_filter('pings_open', '__return_false');

add_action('init', function () {
    remove_action('wp_head', 'rsd_link');
});


// 5. Limit login attempts
add_action('wp_login_failed', 'wpha_register_failed_login');
function wpha_register_failed_login($username) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'wpha_login_attempts_' . $ip;
    $attempts = (int) get_transient($key);
    set_transient($key, $attempts + 1, 30 * MINUTE_IN_SECONDS);
}

add_filter('authenticate', function ($user, $username, $password) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'wpha_login_attempts_' . $ip;
    $attempts = (int) get_transient($key);

    if ($attempts >= 5) {
        return new WP_Error('too_many_attempts', __('Too many failed login attempts. Please try again in 30 minutes.'));
    }

    return $user;
}, 30, 3);