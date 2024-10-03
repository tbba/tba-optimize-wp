<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Default settings for the plugin
function tba_optimize_default_options() {
    return array(
        'disable_emojis' => true,
        'remove_embeds' => true,
        'disable_rest_api' => true,
        'disable_dashicons' => true,
        'remove_jquery_migrate' => true,
        'remove_html_comments' => true
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

// Render the settings page
function tba_optimize_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>TBA Optimization Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('tba_optimize_options_group');
            $options = get_option('tba_optimize_options', tba_optimize_default_options());
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Disable Emojis</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[disable_emojis]" value="1" <?php checked(1, isset($options['disable_emojis']) ? $options['disable_emojis'] : 0); ?> />
                        <label for="tba_optimize_options[disable_emojis]">Disable Emojis</label>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Remove Embeds</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[remove_embeds]" value="1" <?php checked(1, isset($options['remove_embeds']) ? $options['remove_embeds'] : 0); ?> />
                        <label for="tba_optimize_options[remove_embeds]">Remove Embeds</label>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Disable REST API Links</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[disable_rest_api]" value="1" <?php checked(1, isset($options['disable_rest_api']) ? $options['disable_rest_api'] : 0); ?> />
                        <label for="tba_optimize_options[disable_rest_api]">Disable REST API Links and Headers</label>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Disable Dashicons for Non-Logged-In Users</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[disable_dashicons]" value="1" <?php checked(1, isset($options['disable_dashicons']) ? $options['disable_dashicons'] : 0); ?> />
                        <label for="tba_optimize_options[disable_dashicons]">Disable Dashicons for Non-Logged-In Users</label>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Remove jQuery Migrate</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[remove_jquery_migrate]" value="1" <?php checked(1, isset($options['remove_jquery_migrate']) ? $options['remove_jquery_migrate'] : 0); ?> />
                        <label for="tba_optimize_options[remove_jquery_migrate]">Remove jQuery Migrate</label>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Remove HTML Comments</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[remove_html_comments]" value="1" <?php checked(1, isset($options['remove_html_comments']) ? $options['remove_html_comments'] : 0); ?> />
                        <label for="tba_optimize_options[remove_html_comments]">Remove HTML Comments</label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Validate and sanitize options
function tba_optimize_validate_options($input) {
    $input['disable_emojis'] = isset($input['disable_emojis']) ? 1 : 0;
    $input['remove_embeds'] = isset($input['remove_embeds']) ? 1 : 0;
    $input['disable_rest_api'] = isset($input['disable_rest_api']) ? 1 : 0;
    $input['disable_dashicons'] = isset($input['disable_dashicons']) ? 1 : 0;
    $input['remove_jquery_migrate'] = isset($input['remove_jquery_migrate']) ? 1 : 0;
    $input['remove_html_comments'] = isset($input['remove_html_comments']) ? 1 : 0;
    return $input;
}
