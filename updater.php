<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// --- GitHub Updater Integration ---

class TBA_Optimize_Updater {
    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $username = 'tbba'; // GitHub username
    private $repository = 'tba-optimize-wp'; // GitHub repository name
    private $github_api_result;
    private $plugin_data;
    private $version;

    public function __construct($file) {
        $this->file = $file;
        add_action('admin_init', array($this, 'set_plugin_properties'));
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }

    public function set_plugin_properties() {
        $this->plugin     = get_plugin_data($this->file);
        $this->basename   = plugin_basename($this->file);
        $this->active     = is_plugin_active($this->basename);
        $this->version    = $this->plugin['Version'];
    }

    private function get_repository_info() {
        if (is_null($this->github_api_result)) {
            $url = "https://api.github.com/repos/{$this->username}/{$this->repository}/releases/latest";
            $response = wp_remote_get($url);
            $this->github_api_result = wp_remote_retrieve_body($response);

            if (!empty($this->github_api_result)) {
                $this->github_api_result = @json_decode($this->github_api_result);

                // Debugging: Log the API response to ensure it's valid
                if (is_wp_error($response)) {
                    error_log('GitHub API Error: ' . $response->get_error_message());
                } else {
                    error_log('GitHub API Result: ' . print_r($this->github_api_result, true));
                }
            }
        }
    }

public function modify_transient($transient) {
    if (!is_object($transient)) {
        $transient = new stdClass();
    }

    $this->get_repository_info();

    // Ensure we have a valid GitHub API response and check if an update is needed
    if ($this->github_api_result && version_compare($this->version, ltrim($this->github_api_result->tag_name, 'v'), '<')) {
        $package = $this->github_api_result->zipball_url;

        $obj = new stdClass();
        // Remove any version numbers and -main from the folder name
        $obj->slug = preg_replace('/(-main|-?\d+(\.\d+)*)$/', '', plugin_basename($this->file)); 
        $obj->new_version = ltrim($this->github_api_result->tag_name, 'v');
        $obj->url = $this->plugin['PluginURI'];
        $obj->package = $package;

        // Assign the update to the transient response
        $transient->response[$obj->slug] = $obj;

        // Debugging: Log the update data to ensure it's set correctly
        error_log('Update Detected: ' . print_r($obj, true));
    }

    return $transient;
}


    public function plugin_popup($result, $action, $args) {
        if (!empty($args->slug) && $args->slug === $this->basename) {
            $this->get_repository_info();
            $result = new stdClass();
            $result->name = $this->plugin['Name'];
            $result->slug = $this->basename;
            $result->version = ltrim($this->github_api_result->tag_name, 'v');
            $result->author = $this->plugin['AuthorName'];
            $result->homepage = $this->plugin['PluginURI'];
            $result->requires = '5.0';
            $result->tested = '5.8';
            $result->download_link = $this->github_api_result->zipball_url;
            $result->sections = array(
                'description' => $this->plugin['Description'],
                'changelog' => $this->github_api_result->body
            );

            return $result;
        }

        return false;
    }

    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        $install_directory = plugin_dir_path($this->file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;

        if ($this->active) {
            activate_plugin($this->basename);
        }

        return $result;
    }
}

if (is_admin()) {
    new TBA_Optimize_Updater(__FILE__);
}
