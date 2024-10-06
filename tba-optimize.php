<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('TEST', 1); // Enable debugging and logging (1 for ON, 0 for OFF)

/**
 * Plugin Name: 1 TBA Optimization for Speed and GDPR
 * Plugin URI: https://github.com/tbba/tba-optimize-wp
 * Description: A plugin to optimize WordPress for speed and GDPR compliance by removing unnecessary elements.
 * Version: 1.15
 * Author: Carl Erling, TBA-Berlin
 * Author URI: https://www.tba-berlin.de
 * License: GPLv2 or later
 * Text Domain: tba-optimize-wp
 */

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
}
add_action('plugins_loaded', 'tba_optimize_define_version');

// Enable auto-updates for this plugin
add_filter('auto_update_plugin', '__return_true');

// Include additional plugin files
require_once plugin_dir_path(__FILE__) . 'settings.php';
require_once plugin_dir_path(__FILE__) . 'optimizations.php';
require_once plugin_dir_path(__FILE__) . 'updater.php';

// Activate optimizations if enabled
$options = get_option('tba_optimize_options', tba_optimize_default_options());
if (isset($options['enable_optimizations']) && $options['enable_optimizations']) {
    add_action('init', 'optimize_wp_for_speed_and_gdpr');
}
