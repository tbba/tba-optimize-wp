<?php
/**
 * Plugin Name: TBA Optimization for Speed and GDPR
 * Plugin URI: https://github.com/tbba/tba-optimize-wp
 * Description: A plugin to optimize WordPress for speed and GDPR compliance by removing unnecessary elements.
 * Version: 1.1.0
 * Author: Carl Erling, TBA-Berlin
 * Author URI: https://www.tba-berlin.de
 * License: GPLv2 or later
 * Text Domain: tba-optimize-wp
 *
 * GitHub Plugin URI: https://github.com/tbba/tba-optimize-wp
 * GitHub Release Asset: true
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Enable auto-updates for this plugin
add_filter('auto_update_plugin', '__return_true');


// Include separate files
require_once plugin_dir_path(__FILE__) . 'settings.php';
require_once plugin_dir_path(__FILE__) . 'optimizations.php';
require_once plugin_dir_path(__FILE__) . 'updater.php';

// Use settings to toggle optimizations
$options = get_option('tba_optimize_options', tba_optimize_default_options());
if (isset($options['enable_optimizations']) && $options['enable_optimizations']) {
    // Enable optimizations
    add_action('init', 'optimize_wp_for_speed_and_gdpr');
}
