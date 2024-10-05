<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// All the optimizations are stored in this function
function optimize_wp_for_speed_and_gdpr() {
    $options = get_option('tba_optimize_options', tba_optimize_default_options());

    // ---- Task 1: Disable Emojis ----
    if (isset($options['disable_emojis']) && $options['disable_emojis']) {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
    }

    // ---- Task 2: Remove Embeds (oEmbed Discovery Links and Related) ----
    if (isset($options['remove_embeds']) && $options['remove_embeds']) {
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        add_filter('embed_oembed_discover', '__return_false');
    }

    // ---- Task 3: Disable REST API Discovery Links and Headers ----
    if (isset($options['disable_rest_api']) && $options['disable_rest_api']) {
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('template_redirect', 'rest_output_link_header', 11, 0);
    }

    // ---- Task 4: Disable Dashicons for Non-Logged-In Users ----
    if (isset($options['disable_dashicons']) && $options['disable_dashicons']) {
        if (!is_user_logged_in()) {
            wp_deregister_style('dashicons');
        }
    }

    // ---- Task 5: Remove jQuery Migrate for Guests ----
    if (isset($options['remove_jquery_migrate']) && $options['remove_jquery_migrate']) {
        if (!is_user_logged_in()) { // Apply only to guests
            add_action('wp_default_scripts', 'remove_jquery_migrate');
        }
    }

    // ---- Task 6-7: Buffer Output for HTML Comment and Whitespace Removal ----
    if (should_buffer_output()) {
        if (!is_user_logged_in()) { // Apply only to guests
            add_action('template_redirect', 'start_optimization_buffer');
            add_action('shutdown', 'end_optimization_buffer');
        }
    }
}

// Function to determine if buffering is needed
function should_buffer_output() {
    $options = get_option('tba_optimize_options', tba_optimize_default_options());

    // Start buffering only if either HTML comment removal or whitespace removal is enabled
    return (isset($options['remove_html_comments']) && $options['remove_html_comments']) ||
           (isset($options['remove_whitespace']) && $options['remove_whitespace']);
}

// ---- Remove jQuery Migrate for Guests ----
function remove_jquery_migrate($scripts) {
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $jquery_dependencies = $scripts->registered['jquery']->deps;
        $scripts->registered['jquery']->deps = array_diff($jquery_dependencies, array('jquery-migrate'));
    }
}

// ---- Buffer to apply HTML Comments and Whitespace optimizations ----
function start_optimization_buffer() {
    ob_start('apply_output_optimizations');
}

function end_optimization_buffer() {
    ob_end_flush();
}

// Function that applies the optimizations based on settings
function apply_output_optimizations($buffer) {
    $options = get_option('tba_optimize_options', tba_optimize_default_options());

    // ---- Task 6: Remove HTML Comments ----
    if (isset($options['remove_html_comments']) && $options['remove_html_comments']) {
        // Remove HTML comments
        $buffer = preg_replace('/<!--(.*?)-->/', '', $buffer);
    }

    // ---- Task 7: Remove Whitespace ----
    if (isset($options['remove_whitespace']) && $options['remove_whitespace']) {
        // Collapse multiple spaces, newlines, and tabs into a single space
        $buffer = preg_replace('/\s+/', ' ', $buffer);
    }

    // Return the optimized buffer
    return $buffer;
}
