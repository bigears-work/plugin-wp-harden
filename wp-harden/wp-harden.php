<?php
/**
 * Plugin Name: WP Harden
 * Description: Simple Plugin to harden WP installations
 * Version: 0.3.0
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


// 5. Limit login attempts and show remaining tries
add_filter('authenticate', function ($user, $username, $password) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'wpha_login_attempts_' . $ip;
    $attempts = (int) get_transient($key);
    $max_attempts = 5;

    if (is_wp_error($user)) {
        // Failed login
        $new_attempts = $attempts + 1;
        set_transient($key, $new_attempts, 30 * MINUTE_IN_SECONDS);

        if ($new_attempts >= $max_attempts) {
            return new WP_Error('too_many_attempts', __('Too many failed login attempts. Please try again in 30 minutes.'));
        }

        $remaining = $max_attempts - $new_attempts;
        return new WP_Error('login_warning', sprintf(__('Incorrect credentials. You have %d attempt(s) remaining.'), $remaining));
    }

    // Successful login → reset counter
    delete_transient($key);
    return $user;
}, 30, 3);
