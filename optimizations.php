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

    // ---- Task 5: Remove jQuery Migrate ----
    if (isset($options['remove_jquery_migrate']) && $options['remove_jquery_migrate']) {
        add_action('wp_default_scripts', 'remove_jquery_migrate');
    }

    // ---- Task 6: Remove HTML Comments and Add Custom Comments ----
    if (isset($options['remove_html_comments']) && $options['remove_html_comments']) {
        add_action('template_redirect', 'start_html_buffer');
        add_action('shutdown', 'end_html_buffer');
    }
}

// ---- Remove jQuery Migrate ----
function remove_jquery_migrate($scripts) {
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $jquery_dependencies = $scripts->registered['jquery']->deps;
        $scripts->registered['jquery']->deps = array_diff($jquery_dependencies, array('jquery-migrate'));
    }
}

// ---- Buffer to remove HTML Comments ----
function start_html_buffer() {
    ob_start('optimize_html_output');
}

function end_html_buffer() {
    ob_end_flush();
}

function optimize_html_output($buffer) {
    // Remove HTML comments
    $buffer = preg_replace('/<!--(.|\s)*?-->/', '', $buffer);
    
    // Collapse multiple spaces, newlines, and tabs into a single space
    $buffer = preg_replace('/\s+/', ' ', $buffer);

    // Add custom comments at the end of the HTML output
    $buffer .= "\n<!-- *** WEBSITE by TBA-Berlin.de *** -->";
    $buffer .= "\n<!-- Performance optimized by TBA-ClearIT -->";

    return $buffer;
}
