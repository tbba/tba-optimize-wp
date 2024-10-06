<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Plugin Name: 1 TBA Optimization for Speed and GDPR
 * Plugin URI: https://github.com/tbba/tba-optimize-wp
 * Description: A plugin to optimize WordPress for speed and GDPR compliance by removing unnecessary elements.
 * Version: 1.12
 * Author: Carl Erling, TBA-Berlin
 * Author URI: https://www.tba-berlin.de
 * License: GPLv2 or later
 * Text Domain: tba-optimize-wp
 */

// Define TEST for debugging purposes
define('TEST', 1); // Set to 1 to enable logging, 0 to disable

// Define log file path
$log_file = plugin_dir_path(__FILE__) . 'tba_optimize_log.txt';

// Function to log messages (only for admins)
function tba_optimize_log($message) {
    if (current_user_can('administrator') && defined('TEST') && TEST === 1) {
        global $log_file;

        // Clear log file at the beginning of the run
        if (!file_exists($log_file)) {
            file_put_contents($log_file, ""); // Clear the log file
        }

        $log_message = "==========\n" . date('Y-m-d H:i:s') . ": " . $message . "\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}

// Log the start of the plugin run
tba_optimize_log("Starting plugin execution.");

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

        tba_optimize_log("TBA_OPTIMIZE_VERSION set: " . TBA_OPTIMIZE_VERSION);
    }
}
add_action('plugins_loaded', 'tba_optimize_define_version');

// Enable auto-updates for this plugin
add_filter('auto_update_plugin', '__return_true');

// Include additional plugin files
require_once plugin_dir_path(__FILE__) . 'settings.php';
require_once plugin_dir_path(__FILE__) . 'optimizations.php';

// Include the new GitHub updater
require_once plugin_dir_path(__FILE__) . 'vendor/WordPress-GitHub-Plugin-Updater/updater.php';

// Activate the new GitHub updater
add_action('plugins_loaded', 'tba_optimize_init_updater');
function tba_optimize_init_updater() {
    if (is_admin()) {
        $config = array(
            'slug' => plugin_basename(__FILE__), // Plugin slug
            'proper_folder_name' => 'tba-optimize-wp', // Plugin folder name
            'api_url' => 'https://api.github.com/repos/tbba/tba-optimize-wp', // GitHub API URL
            'raw_url' => 'https://raw.githubusercontent.com/tbba/tba-optimize-wp/main', // URL to raw GitHub files
            'github_url' => 'https://github.com/tbba/tba-optimize-wp', // GitHub URL
            'zip_url' => 'https://github.com/tbba/tba-optimize-wp/archive/refs/tags', // Zip download URL
            'sslverify' => true, // Verify SSL certificate
            'requires' => '5.0', // Required WordPress version
            'tested' => '5.8', // Tested up to WordPress version
            'readme' => 'README.md', // Readme file location
            'access_token' => '', // If you have a private repo, use an access token here.
        );

        new WP_GitHub_Updater($config);
    }
}

// Log the end of the plugin run
tba_optimize_log("Plugin execution completed.");
