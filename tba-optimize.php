<?php
/**
 * Plugin Name: TBA Optimization for Speed and GDPR
 * Plugin URI: https://github.com/tbba/tba-optimize-wp
 * Description: A plugin to optimize WordPress for speed and GDPR compliance by removing unnecessary elements.
 * Version: 1.0.1
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

// Require the updater file
require_once plugin_dir_path(__FILE__) . 'updater.php';

// --- WordPress Optimization Functions ---

function optimize_wp_for_speed_and_gdpr() {

    // ---- Task 1: Disable Emojis ----
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');

    // ---- Task 2: Remove Embeds (oEmbed Discovery Links and Related) ----
    remove_action('wp_head', 'wp_oembed_add_discovery_links');  // Remove oEmbed discovery links
    remove_action('wp_head', 'wp_oembed_add_host_js');  // Remove oEmbed host JS
    add_filter('embed_oembed_discover', '__return_false');  // Disable oEmbed discovery completely

    // ---- Task 3: Disable REST API Discovery Links and Headers ----
    remove_action('wp_head', 'rest_output_link_wp_head', 10);  // Remove REST API link from head
    remove_action('template_redirect', 'rest_output_link_header', 11, 0);  // Remove REST API link from HTTP headers

    // ---- Task 4: Remove Other Unnecessary Links in the Header ----
    remove_action('wp_head', 'wp_generator');  // Remove WordPress version number
    remove_action('wp_head', 'rsd_link');  // Disable RSD link (Really Simple Discovery)
    remove_action('wp_head', 'wlwmanifest_link');  // Disable WLW manifest link (Windows Live Writer)
    remove_action('wp_head', 'wp_shortlink_wp_head');  // Disable shortlink for posts

    // ---- Task 5: Disable Gravatar and Comment Cookies ----
    add_filter('get_avatar', '__return_false');  // Disable Gravatar to prevent external requests
    update_option('show_avatars', 0);  // Ensure avatars are turned off in settings
    add_filter('comment_form_defaults', 'disable_comment_cookies');
    function disable_comment_cookies($defaults) {
        $defaults['cookies'] = '';  // Disable comment cookies for GDPR compliance
        return $defaults;
    }

    // ---- Task 6: Remove Comment URL Field ----
    add_filter('comment_form_default_fields', 'remove_comment_url_field');
    function remove_comment_url_field($fields) {
        if (isset($fields['url'])) {
            unset($fields['url']);  // Remove URL field from comment form to reduce spam
        }
        return $fields;
    }

    // ---- Task 7: Disable RSS Feeds ----
    function disable_rss_feeds() {
        wp_die(__('No feed available, please visit the <a href="'. get_bloginfo('url') .'">homepage</a>!'));  // Disable RSS feeds
    }
    add_action('do_feed', 'disable_rss_feeds', 1);
    add_action('do_feed_rdf', 'disable_rss_feeds', 1);
    add_action('do_feed_rss', 'disable_rss_feeds', 1);
    add_action('do_feed_rss2', 'disable_rss_feeds', 1);
    add_action('do_feed_atom', 'disable_rss_feeds', 1);

    // ---- Task 8: Remove gmpg.org Profile Link ----
    add_filter('avf_profile_head_tag', 'avia_remove_profile');
    function avia_remove_profile() {
        return false;  // Remove gmpg.org profile link for privacy
    }

    // ---- Task 9: Disable Dashicons for Non-Logged-In Users Only ----
    if (!is_user_logged_in()) {
        wp_deregister_style('dashicons');  // Prevent loading Dashicons for non-logged-in users only
    }
}
add_action('init', 'optimize_wp_for_speed_and_gdpr');

// ---- Remove jQuery Migrate ----
function remove_jquery_migrate($scripts) {
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $jquery_dependencies = $scripts->registered['jquery']->deps;
        $scripts->registered['jquery']->deps = array_diff($jquery_dependencies, array('jquery-migrate'));
    }
}
add_action('wp_default_scripts', 'remove_jquery_migrate');

// ---- Remove HTML Comments and Add Custom Performance Comment ----
function optimize_html_output($buffer) {
    // Remove HTML comments
    $buffer = preg_replace('/<!--(.|\s)*?-->/', '', $buffer);
    
    // Collapse multiple spaces, newlines, and tabs into a single space
    $buffer = preg_replace('/\s+/', ' ', $buffer);

    // Add performance comment at the end of the HTML output
    $buffer .= "\n<!-- Performance optimized by TBA-Optimize -->";

    return $buffer;
}

function buffer_start() { 
    ob_start('optimize_html_output'); 
}

function buffer_end() { 
    ob_end_flush(); 
}

if (!is_admin()) {
    add_action('template_redirect', 'buffer_start', 1);
    add_action('shutdown', 'buffer_end', 1);
}
