<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// The Updater class for TBA Optimize Plugin
class TBA_Optimize_Updater {
    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $version;
    private $github_api_result;

    // Configuration for the GitHub repository
    private $config = [
        'username'     => 'tbba',
        'repository'   => 'tba-optimize-wp',
        'api_base'     => 'https://api.github.com/repos/',
        'plugin_slug'  => 'tba-optimize-wp',
        'plugin'       => 'tba-optimize-wp/tba-optimize-wp.php',
        'plugin_url'   => 'https://github.com/tbba/tba-optimize-wp',
        'requires'     => '5.0',
        'tested'       => '5.8',
        'requires_php' => '7.0',
    ];

    public function __construct($file) {
        $this->file = $file;

        // Set plugin properties and hooks
        add_action('admin_init', [$this, 'set_plugin_properties']);
        add_filter('pre_set_site_transient_update_plugins', [$this, 'modify_transient'], 10, 1);
        add_filter('plugins_api', [$this, 'plugin_popup'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
    }

    // Log function to write messages (uses main log file from the main plugin file)
    private function log($message) {
        if (function_exists('tba_optimize_log')) {
            tba_optimize_log($message);
        }
    }

    public function set_plugin_properties() {
        $this->plugin   = get_plugin_data($this->file);
        $this->basename = plugin_basename($this->file);
        $this->active   = is_plugin_active($this->basename);
        if (defined('TBA_OPTIMIZE_VERSION')) {
            $this->version = TBA_OPTIMIZE_VERSION;
            $this->log("TBA_OPTIMIZE_VERSION set in updater: " . TBA_OPTIMIZE_VERSION);
        } else {
            $this->version = '1.0.0';
            $this->log("Version fallback to 1.0.0 in updater.");
        }
    }

    private function get_repository_info() {
        if (is_null($this->github_api_result)) {
            $url = "{$this->config['api_base']}{$this->config['username']}/{$this->config['repository']}/releases/latest";
            $response = wp_remote_get($url);

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
                if (
                    version_compare(get_bloginfo('version'), $this->config['requires'], '>=') &&
                    version_compare(PHP_VERSION, $this->config['requires_php'], '>=')
                ) {
                    $package = $this->github_api_result->zipball_url;

                    $obj = new stdClass();
                    $obj->slug = $this->config['plugin_slug'];
                    $obj->plugin = $this->config['plugin'];
                    $obj->new_version = $remote_version;
                    $obj->url = $this->config['plugin_url'];
                    $obj->package = $package;
                    $obj->requires = $this->config['requires'];
                    $obj->tested = $this->config['tested'];
                    $obj->requires_php = $this->config['requires_php'];

                    if (!isset($transient->response[$this->config['plugin']])) {
                        $transient->response[$this->config['plugin']] = $obj;
                    }

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
    add_action('plugins_loaded', function() {
        new TBA_Optimize_Updater(__FILE__);
    }, 15);
}
