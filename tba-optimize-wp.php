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


// Define the version from the plugin header
function tba_optimize_define_version() {
    if (!defined('TBA_OPTIMIZE_VERSION')) {
        $plugin_data = get_file_data(__FILE__, ['Version' => 'Version']);
        define('TBA_OPTIMIZE_VERSION', $plugin_data['Version']);
    }
}
add_action('plugins_loaded', 'tba_optimize_define_version');

// Manually include the necessary WP-Updater files
require_once plugin_dir_path(__FILE__) . 'vendor/wp-updater/src/Boot.php';
require_once plugin_dir_path(__FILE__) . 'vendor/wp-updater/src/Updater.php';
require_once plugin_dir_path(__FILE__) . 'vendor/wp-updater/src/Plugin-Updater.php';


// Initialize WP-Updater after the plugin is loaded
function tba_optimize_init_updater() {
    if (is_admin()) {
        // Initialize the updater using the MakeitWorkPress namespace
        $updater = \MakeitWorkPress\WP_Updater\Boot::instance();

        // Add your GitHub repository information
        $updater->add([
            'type'   => 'plugin',
            'source' => 'https://github.com/tbba/tba-optimize-wp',
        ]);
    }
}
add_action('plugins_loaded', 'tba_optimize_init_updater', 20);

// Enable auto-updates for this plugin
add_filter('auto_update_plugin', '__return_true');

// Include additional plugin files
require_once plugin_dir_path(__FILE__) . 'settings.php';
require_once plugin_dir_path(__FILE__) . 'optimizations.php';

// Activate optimizations if enabled
$options = get_option('tba_optimize_options', tba_optimize_default_options());
if (isset($options['enable_optimizations']) && $options['enable_optimizations']) {
    add_action('init', 'optimize_wp_for_speed_and_gdpr');
}
