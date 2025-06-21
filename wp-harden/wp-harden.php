<?php
/**
 * Plugin Name: WP Harden
 * Description: Simple Plugin to harden WP installations
 * Version: 0.1.0
 * Author: Big Ears Webagentur
 * Author URI: https://bigears.work
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

// 1. Disable file editing in admin
define('DISALLOW_FILE_EDIT', true);

