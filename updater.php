<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// --- GitHub Updater Integration ---
define('TEST', 1); // Enable debugging and logging (1 for ON, 0 for OFF)

class TBA_Optimize_Updater {
    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $plugin_data;
    private $version;
    private $github_api_result;
    private $log_file;

    // Central Configuration
    private $config = [
        'username'     => 'tbba', // GitHub username
        'repository'   => 'tba-optimize-wp', // GitHub repository name
        'api_base'     => 'https://api.github.com/repos/', // API base URL
        'plugin_slug'  => 'tba-optimize-wp', // Expected Plugin slug
        'plugin'       => 'tba-optimize-wp/tba-optimize-wp.php', // Path to the plugin's main file
        'plugin_url'   => 'https://github.com/tbba/tba-optimize-wp', // Plugin URL
        'requires'     => '5.0', // Minimum WP version
        'tested'       => '5.8', // Tested up to WP version
        'requires_php' => '7.0', // Minimum PHP version
    ];

    private $testing = TEST; // Toggle for test mode

    public function __construct($file) {
        $this->file = $file;
        $this->log_file = plugin_dir_path(__FILE__) . 'tba_optimize_log.txt'; // Log file location

        if ($this->testing) {
            $this->check_log_file();
        }

        add_action('admin_init', array($this, 'set_plugin_properties'));
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 5, 1); // timing 5 = early
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }

    // Check if the log file exists, create it if it doesn't, and check write permissions
    private function check_log_file() {
        if (!file_exists($this->log_file)) {
            file_put_contents($this->log_file, "==========\n" . date('Y-m-d H:i:s') . ": Log file created\n", FILE_APPEND);
        }

        if (!is_writable($this->log_file)) {
            // If the log file is not writable, use error_log as a fallback
            error_log("Log file {$this->log_file} is not writable.");
            return false;
        }

        $this->log("Logging is enabled.");
        return true;
    }

    private function log($message) {
        if ($this->testing && is_writable($this->log_file)) {
            $log_message = "==========\n" . date('Y-m-d H:i:s') . ": " . $message . "\n";
            file_put_contents($this->log_file, $log_message, FILE_APPEND);
        } else {
            error_log($message); // Fallback to error_log if the file is not writable
        }
    }

    public function set_plugin_properties() {
        $this->plugin   = get_plugin_data($this->file);
        $this->basename = plugin_basename($this->file);
        $this->active   = is_plugin_active($this->basename);
        if (defined('TBA_OPTIMIZE_VERSION')) {
            $this->version = TBA_OPTIMIZE_VERSION;
            $this->log("TBA_OPTIMIZE_VERSION set: " . TBA_OPTIMIZE_VERSION);
        } else {
            // Fallback if version is not found
            $this->version = '1.0.0';
            $this->log("Version fallback to 1.0.0");
        }
    }

    private function get_repository_info() {
        if (is_null($this->github_api_result)) {
            // Create the API URL for the repository
            $url = "{$this->config['api_base']}{$this->config['username']}/{$this->config['repository']}/releases/latest";
            $response = wp_remote_get($url);

            // Log the API request and response
            if (is_wp_error($response)) {
                $this->log("GitHub API Error: " . $response->get_error_message());
                return;
            }

            if (wp_remote_retrieve_response_code($response) !== 200) {
                $this->log("GitHub API Response Error: " . wp_remote_retrieve_response_code($response));
                return;
            }

            $this->github_api_result = wp_remote_retrieve_body($response);
            if (!empty($this->github_api_result)) {
                $this->github_api_result = json_decode($this->github_api_result);
                $this->log("GitHub API Result: " . print_r($this->github_api_result, true));
            }
        }
    }

    public function modify_transient($transient) {
        if (!is_object($transient)) {
            $transient = new stdClass();
        }

        $this->get_repository_info();

        $this->log("modify_transient called");

        if ($this->github_api_result) {
            $remote_version = ltrim($this->github_api_result->tag_name, 'v');
            $this->log("Remote version: " . $remote_version);
            $this->log("Current version: " . $this->version);

            if (version_compare($this->version, $remote_version, '<')) {
                // Validate WordPress and PHP versions
                if (
                    version_compare(get_bloginfo('version'), $this->config['requires'], '>=') &&
                    version_compare(PHP_VERSION, $this->config['requires_php'], '>=')
                ) {

                    $package = $this->github_api_result->zipball_url;

                    $obj = new stdClass();
                    $obj->slug = $this->config['plugin_slug'];  // Use the central slug
                    $obj->plugin = $this->config['plugin']; // Path to the main plugin file
                    $obj->new_version = $remote_version;
                    $obj->url = $this->config['plugin_url'];
                    $obj->package = $package;
                    $obj->requires = $this->config['requires'];
                    $obj->tested = $this->config['tested'];
                    $obj->requires_php = $this->config['requires_php'];

                    // Check for slug consistency
                    if ($obj->slug !== $this->config['plugin_slug']) {
                        $this->log("Plugin slug inconsistency detected! Expected: " . $this->config['plugin_slug'] . " but got: " . $obj->slug);
                    } else {
                        $this->log("Plugin slug consistency check passed.");
                    }

                    // Correctly set the plugin in the transient
                    $transient->response[$obj->plugin] = $obj;

                    $this->log("Update detected: " . print_r($obj, true));
                } else {
                    $this->log('Version requirements not met.');
                }
            } else {
                $this->log('No update needed.');
            }
        } else {
            $this->log('GitHub API result is empty.');
        }

        // Log the contents of the transient to check if the update is registered
        $this->log("Transient contents after modify_transient: \n" . print_r($transient, true));

        return $transient;
    }

    public function plugin_popup($result, $action, $args) {
        if (!empty($args->slug) && $args->slug === $this->config['plugin_slug']) {
            $this->get_repository_info();
            if ($this->github_api_result) {
                $result = new stdClass();
                $result->name = $this->plugin['Name'];
                $result->slug = $this->config['plugin_slug'];
                $result->version = ltrim($this->github_api_result->tag_name, 'v');
                $result->author = $this->plugin['AuthorName'];
                $result->homepage = $this->config['plugin_url'];
                $result->requires = $this->config['requires'];
                $result->tested = $this->config['tested'];
                $result->requires_php = $this->config['requires_php'];
                $result->download_link = $this->github_api_result->zipball_url;
                $result->sections = array(
                    'description' => $this->plugin['Description'],
                    'changelog'   => $this->github_api_result->body
                );

                $this->log("Plugin popup result: " . print_r($result, true));
                return $result;
            }
        }

        return $result;
    }

    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        $install_directory = plugin_dir_path($this->file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;

        if ($this->active) {
            activate_plugin($this->basename);
        }

        $this->log("After install completed. Plugin activated.");

        return $result;
    }
}

if (is_admin()) {
    add_action('init', function() {
        new TBA_Optimize_Updater(__FILE__);
    }, 5);  // Run early
}
