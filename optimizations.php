<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// All the optimizations are stored in this function
function optimize_wp_for_speed_and_gdpr() {
    // ---- Task 1: Disable Emojis ----
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');

    // ---- Task 2: Remove Embeds (oEmbed Discovery Links and Related) ----
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
    add_filter('embed_oembed_discover', '__return_false');

    // ---- Task 3: Disable REST API Discovery Links and Headers ----
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('template_redirect', 'rest_output_link_header', 11, 0);

    // ---- Task 4: Remove Other Unnecessary Links in the Header ----
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');

    // ---- Task 5: Disable Gravatar and Comment Cookies ----
    add_filter('get_avatar', '__return_false');
    update_option('show_avatars', 0);
    add_filter('comment_form_defaults', 'disable_comment_cookies');
    function disable_comment_cookies($defaults) {
        $defaults['cookies'] = '';
        return $defaults;
    }

    // ---- Task 6: Remove Comment URL Field ----
    add_filter('comment_form_default_fields', 'remove_comment_url_field');
    function remove_comment_url_field($fields) {
        if (isset($fields['url'])) {
            unset($fields['url']);
        }
        return $fields;
    }

    // ---- Task 7: Disable RSS Feeds ----
    function disable_rss_feeds() {
        wp_die(__('No feed available, please visit the <a href="'. get_bloginfo('url') .'">homepage</a>!'));
    }
    add_action('do_feed', 'disable_rss_feeds', 1);
    add_action('do_feed_rdf', 'disable_rss_feeds', 1);
    add_action('do_feed_rss', 'disable_rss_feeds', 1);
    add_action('do_feed_rss2', 'disable_rss_feeds', 1);
    add_action('do_feed_atom', 'disable_rss_feeds', 1);

    // ---- Task 8: Remove gmpg.org Profile Link ----
    add_filter('avf_profile_head_tag', 'avia_remove_profile');
    function avia_remove_profile() {
        return false;
    }

    // ---- Task 9: Disable Dashicons for Non-Logged-In Users Only ----
    if (!is_user_logged_in()) {
        wp_deregister_style('dashicons');
    }
}
