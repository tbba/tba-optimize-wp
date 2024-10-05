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
    private $plugin_data;
    private $version;

    // Zentrale Konfigurationsinformationen (redundante Informationen entfernen)
    private $config = [
        'username'    => 'tbba', // GitHub-Benutzername
        'repository'  => 'tba-optimize-wp', // GitHub-Repository-Name
        'api_base'    => 'https://api.github.com/repos/', // Basis-URL für API-Aufrufe
        'plugin_slug' => 'tba-optimize-wp/tba-optimize-wp.php', // Plugin-Slug
        'plugin_url'  => 'https://github.com/tbba/tba-optimize-wp', // Plugin-URL
    ];

    private $testing = true;  // Toggle für Testmodus

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
        $this->version    = TBA_OPTIMIZE_VERSION;  // Version aus dem statischen Header
    }

    private function get_repository_info() {
        if (is_null($this->github_api_result)) {
            // Erstelle die API-URL für das Repository
            $url = "{$this->config['api_base']}{$this->config['username']}/{$this->config['repository']}/releases/latest";
            $response = wp_remote_get($url);
            $this->github_api_result = wp_remote_retrieve_body($response);

            if (!empty($this->github_api_result)) {
                $this->github_api_result = json_decode($this->github_api_result);
            }
        }
    }

    public function modify_transient($transient) {
        if (!is_object($transient)) {
            $transient = new stdClass();
        }

        $this->get_repository_info();

        if ($this->github_api_result && version_compare($this->version, ltrim($this->github_api_result->tag_name, 'v'), '<')) {
            $package = $this->github_api_result->zipball_url;

            $obj = new stdClass();
            $obj->slug = $this->config['plugin_slug'];  // Verwende den zentralen Slug
            $obj->new_version = ltrim($this->github_api_result->tag_name, 'v');
            $obj->url = $this->config['plugin_url'];    // Plugin-URL aus der Konfiguration
            $obj->package = $package;

            $transient->response[$this->config['plugin_slug']] = $obj;

            if ($this->testing) {
                error_log('Update Detected: ' . print_r($obj, true));
            }
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
            $result->homepage = $this->config['plugin_url'];  // URL zentral aus der Konfiguration
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

    // --- Send testing info to the admin email ---
    private function send_testing_info($subject, $message) {
        $admin_email = get_option('admin_email');
        $full_subject = '[TBA Optimize Testing] ' . $subject;
        wp_mail($admin_email, $full_subject, $message);
    }
}

if (is_admin()) {
    new TBA_Optimize_Updater(__FILE__);
}
