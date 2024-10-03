<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Default setting for enabling optimizations
function tba_optimize_default_options() {
    return array(
        'enable_optimizations' => true,
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
                    <th scope="row">Enable Optimizations</th>
                    <td>
                        <input type="checkbox" name="tba_optimize_options[enable_optimizations]" value="1" <?php checked(1, isset($options['enable_optimizations']) ? $options['enable_optimizations'] : 0); ?> />
                        <label for="tba_optimize_options[enable_optimizations]">Enable all optimization options (default: on)</label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Validate options
function tba_optimize_validate_options($input) {
    $input['enable_optimizations'] = isset($input['enable_optimizations']) ? 1 : 0;
    return $input;
}

// Add a settings link in the plugin list
function tba_optimize_plugin_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=tba-optimize-settings">Settings</a>';
    array_push($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'tba_optimize_plugin_settings_link');
