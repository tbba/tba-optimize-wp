<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Default settings for the plugin
// Task 1-7 in order
function tba_optimize_default_options() {
    return array(
        'disable_emojis' => true,            // Task 1
        'remove_embeds' => true,             // Task 2
        'disable_rest_api' => true,          // Task 3
        'disable_dashicons' => true,         // Task 4
        'remove_jquery_migrate' => true,     // Task 5
        'remove_html_comments' => false,     // Task 6
        'remove_whitespace' => false         // Task 7
    );
}

// Register plugin settings
function tba_optimize_register_settings() {
    register_setting('tba_optimize_options_group', 'tba_optimize_options', 'tba_optimize_validate_options');
}
add_action('admin_init', 'tba_optimize_register_settings');

// Add settings page to WP Settings menu
function tba_optimize_add_settings_page() {
    add_options_page(
        'TBA Optimization Settings',  // Page title
        'TBA Optimization',           // Menu title
        'manage_options',             // Capability
        'tba-optimize-settings',      // Menu slug
        'tba_optimize_render_settings_page' // Function to display the settings page
    );
}
add_action('admin_menu', 'tba_optimize_add_settings_page');

/// Add a settings link in the plugin list
function tba_optimize_add_action_links($actions) {
    // Add the Settings link
    $mylinks = array(
        '<a href="' . admin_url('options-general.php?page=tba-optimize-settings') . '">Settings</a>',
    );

    // Merge the custom settings link with the existing actions
    $actions = array_merge($mylinks, $actions);
    return $actions;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'tba_optimize_add_action_links');

// Render the settings page
function tba_optimize_render_settings_page() {
    ?>
    <div class="wrap">
        <?php
        require_once plugin_dir_path(__FILE__) . 'description.php';
        ?>
        <h1>TBA Optimization Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('tba_optimize_options_group');
            $options = get_option('tba_optimize_options', tba_optimize_default_options());
            ?>
            <table class="form-table">
                <!-- Task 1: Disable Emojis -->
                <tr valign="top">
                    <th scope="row">Disable Emojis (Task 1)</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[disable_emojis]" value="1" <?php checked(1, isset($options['disable_emojis']) ? $options['disable_emojis'] : 0); ?> />
                        <label for="tba_optimize_options[disable_emojis]">Disable Emojis</label>
                    </td>
                </tr>

                <!-- Task 2: Remove Embeds -->
                <tr valign="top">
                    <th scope="row">Remove Embeds (Task 2)</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[remove_embeds]" value="1" <?php checked(1, isset($options['remove_embeds']) ? $options['remove_embeds'] : 0); ?> />
                        <label for="tba_optimize_options[remove_embeds]">Remove embeds (oEmbed Discovery Links) from content to avoid unnecessary resource consumption.</label>
                    </td>
                </tr>

                <!-- Task 3: Disable REST API Links -->
                <tr valign="top">
                    <th scope="row">Disable REST API Links (Task 3)</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[disable_rest_api]" value="1" <?php checked(1, isset($options['disable_rest_api']) ? $options['disable_rest_api'] : 0); ?> />
                        <label for="tba_optimize_options[disable_rest_api]">Disable REST API links and headers for guests.</label>
                    </td>
                </tr>

                <!-- Task 4: Disable Dashicons -->
                <tr valign="top">
                    <th scope="row">Disable Dashicons (Task 4)</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[disable_dashicons]" value="1" <?php checked(1, isset($options['disable_dashicons']) ? $options['disable_dashicons'] : 0); ?> />
                        <label for="tba_optimize_options[disable_dashicons]">Disable loading Dashicons for non-logged-in users, reducing front-end resource usage.</label>
                    </td>
                </tr>

                <!-- Task 5: Remove jQuery Migrate -->
                <tr valign="top">
                    <th scope="row">Remove jQuery Migrate (Task 5)</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[remove_jquery_migrate]" value="1" <?php checked(1, isset($options['remove_jquery_migrate']) ? $options['remove_jquery_migrate'] : 0); ?> />
                        <label for="tba_optimize_options[remove_jquery_migrate]">Remove jQuery Migrate script for guests, improving front-end performance.</label>
                    </td>
                </tr>

                <!-- Task 6: Remove HTML Comments -->
                <tr valign="top">
                    <th scope="row">Remove HTML Comments (Task 6)</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[remove_html_comments]" value="1" <?php checked(1, isset($options['remove_html_comments']) ? $options['remove_html_comments'] : 0); ?> />
                        <label for="tba_optimize_options[remove_html_comments]">Remove HTML comments from the output for guests.</label>
                    </td>
                </tr>

                <!-- Task 7: Remove Whitespace -->
                <tr valign="top">
                    <th scope="row">Remove Whitespace (Task 7)</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[remove_whitespace]" value="1" <?php checked(1, isset($options['remove_whitespace']) ? $options['remove_whitespace'] : 0); ?> />
                        <label for="tba_optimize_options[remove_whitespace]">Remove unnecessary whitespaces from the HTML output for guests. (This can slightly slow down WordPress on large pages, if no cache plugin is enabled.)</label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Validate and sanitize options
// Task 1-7 validation
function tba_optimize_validate_options($input) {
    $input['disable_emojis'] = isset($input['disable_emojis']) ? 1 : 0;
    $input['remove_embeds'] = isset($input['remove_embeds']) ? 1 : 0;
    $input['disable_rest_api'] = isset($input['disable_rest_api']) ? 1 : 0;
    $input['disable_dashicons'] = isset($input['disable_dashicons']) ? 1 : 0;
    $input['remove_jquery_migrate'] = isset($input['remove_jquery_migrate']) ? 1 : 0;
    $input['remove_html_comments'] = isset($input['remove_html_comments']) ? 1 : 0;
    $input['remove_whitespace'] = isset($input['remove_whitespace']) ? 1 : 0;
    return $input;
}
