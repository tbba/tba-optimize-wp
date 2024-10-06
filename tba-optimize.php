<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Plugin Name: 1 TBA Optimization for Speed and GDPR
 * Plugin URI: https://github.com/tbba/tba-optimize-wp
 * Description: A plugin to optimize WordPress for speed and GDPR compliance by removing unnecessary elements.
 * Version: 1.16
 * Author: Carl Erling, TBA-Berlin
 * Author URI: https://www.tba-berlin.de
 * License: GPLv2 or later
 * Text Domain: tba-optimize-wp
 */

// Centralized Logging Function
function tba_optimize_log($message) {
    if (defined('TEST') && TEST === 1 && current_user_can('administrator')) {
        $log_file = plugin_dir_path(__FILE__) . 'tba_optimize_log.txt';

        // Clear the log at the start of each run (only first log call will clear it)
        static $cleared = false;
        if (!$cleared) {
            file_put_contents($log_file, "==========\n" . date('Y-m-d H:i:s') . ": Log cleared\n", LOCK_EX);
            $cleared = true;
        }

        // Append messages to the log
        $log_message = "==========\n" . date('Y-m-d H:i:s') . ": " . $message . "\n";
        file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
    }
}

// Extract and define the plugin version from the plugin header
function tba_optimize_define_version() {
    if (!defined('TBA_OPTIMIZE_VERSION')) {
        $plugin_data = file_get_contents(__FILE__);

        // Extract only the version number and dots using a regular expression
        if (preg_match('/^\s*\*\s*Version:\s*([\d\.]+)/mi', $plugin_data, $matches)) {
            define('TBA_OPTIMIZE_VERSION', trim($matches[1])); // Set the version constant
        } else {
            define('TBA_OPTIMIZE_VERSION', '1.0.0'); // Fallback version if no match
        }
    }

    // Log the defined version
    tba_optimize_log("TBA_OPTIMIZE_VERSION defined: " . TBA_OPTIMIZE_VERSION);
}
add_action('plugins_loaded', 'tba_optimize_define_version');

// Enable auto-updates for this plugin
add_filter('auto_update_plugin', '__return_true');

// Include additional plugin files
require_once plugin_dir_path(__FILE__) . 'settings.php';
require_once plugin_dir_path(__FILE__) . 'optimizations.php';
require_once plugin_dir_path(__FILE__) . 'updater.php'; // Updater is included last since it depends on logging

// Activate optimizations if enabled
$options = get_option('tba_optimize_options', tba_optimize_default_options());
if (isset($options['enable_optimizations']) && $options['enable_optimizations']) {
    add_action('init', 'optimize_wp_for_speed_and_gdpr');
}
