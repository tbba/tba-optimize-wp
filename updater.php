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
    private $github_api_result;

    // Zentrale Konfigurationsinformationen
    private $config = [
        'username'     => 'tbba', // GitHub-Benutzername
        'repository'   => 'tba-optimize-wp', // GitHub-Repository-Name
        'api_base'     => 'https://api.github.com/repos/', // Basis-URL für API-Aufrufe
        'plugin_slug'  => 'tba-optimize-wp', // Plugin-Slug
        'plugin'       => 'tba-optimize-wp/tba-optimize-wp.php', // Pfad zur Haupt-Plugin-Datei relativ zum Plugins-Verzeichnis
        'plugin_url'   => 'https://github.com/tbba/tba-optimize-wp', // Plugin-URL
        'requires'     => '5.0', // Mindestanforderung für WordPress-Version
        'tested'       => '5.8', // Getestet bis WordPress-Version
        'requires_php' => '7.0', // Mindestanforderung für PHP-Version
    ];

    // Logging file location
    private $log_file;

    public function __construct($file) {
        $this->file = $file;
        $this->log_file = plugin_dir_path(__FILE__) . 'tba-optimize-errors.log'; // Path to the log file

        add_action('admin_init', array($this, 'set_plugin_properties'));
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }

    public function log($message) {
        if (defined('TEST') && TEST === 1) {
            // Log to the specific file in the plugin directory
            error_log($message . "\n", 3, $this->log_file);
        }
    }

    public function set_plugin_properties() {
        $this->plugin   = get_plugin_data($this->file);
        $this->basename = plugin_basename($this->file);
        $this->active   = is_plugin_active($this->basename);

        if (defined('TBA_OPTIMIZE_VERSION')) {
            $this->version = TBA_OPTIMIZE_VERSION;
            $this->log('TBA_OPTIMIZE_VERSION defined: ' . $this->version);
        } else {
            // Fallback if version is not found
            $this->version = '1.0.0';
            $this->log('Error: TBA_OPTIMIZE_VERSION not defined, falling back to version 1.0.0');
        }
    }

    private function get_repository_info() {
        if (is_null($this->github_api_result)) {
            // Erstelle die API-URL für das Repository
            $url = "{$this->config['api_base']}{$this->config['username']}/{$this->config['repository']}/releases/latest";
            $response = wp_remote_get($url);

            // Debug: Überprüfen, ob die API-Abfrage funktioniert
            if (is_wp_error($response)) {
                $this->log('GitHub API Error: ' . $response->get_error_message());
                return;
            }

            if (wp_remote_retrieve_response_code($response) !== 200) {
                $this->log('GitHub API Response Error: ' . wp_remote_retrieve_response_code($response));
                return;
            }

            $this->github_api_result = wp_remote_retrieve_body($response);

            if (!empty($this->github_api_result)) {
                $this->github_api_result = json_decode($this->github_api_result);
                $this->log('GitHub API Result: ' . print_r($this->github_api_result, true));
            }
        }
    }

    public function modify_transient($transient) {
        if (!is_object($transient)) {
            $transient = new stdClass();
        }

        $this->get_repository_info();
        $this->log('modify_transient called');

        if ($this->github_api_result) {
            $remote_version = ltrim($this->github_api_result->tag_name, 'v');
            $this->log('Remote version: ' . $remote_version);
            $this->log('Current version: ' . $this->version);

            if (version_compare($this->version, $remote_version, '<')) {
                // WordPress-Version und PHP-Version validieren
                if (
                    version_compare(get_bloginfo('version'), $this->config['requires'], '>=') &&
                    version_compare(PHP_VERSION, $this->config['requires_php'], '>=')
                ) {
                    $package = $this->github_api_result->zipball_url;

                    $obj = new stdClass();
                    $obj->slug = $this->config['plugin_slug'];  // Verwende den zentralen Slug
                    $obj->plugin = $this->config['plugin']; // Pfad zur Haupt-Plugin-Datei
                    $obj->new_version = $remote_version;
                    $obj->url = $this->config['plugin_url'];
                    $obj->package = $package;
                    $obj->requires = $this->config['requires'];
                    $obj->tested = $this->config['tested'];
                    $obj->requires_php = $this->config['requires_php'];

                    // Setze das Plugin korrekt im Transient
                    $transient->response[$obj->plugin] = $obj;

                    $this->log('Update detected: ' . print_r($obj, true));
                } else {
                    $this->log('Version requirements not met.');
                }
            } else {
                $this->log('No update needed.');
            }
        } else {
            $this->log('GitHub API result is empty.');
        }

        return $transient;
    }

    public function plugin_popup($result, $action, $args) {
        if (!empty($args->slug) && $args->slug === $this->config['plugin_slug']) {
            $this->get_repository_info();
            if ($this->github_api_result) {
                $result = new stdClass();
                $result->name           = $this->plugin['Name'];
                $result->slug           = $this->config['plugin_slug'];
                $result->version        = ltrim($this->github_api_result->tag_name, 'v');
                $result->author         = $this->plugin['AuthorName'];
                $result->homepage       = $this->config['plugin_url'];  // URL zentral aus der Konfiguration
                $result->requires       = $this->config['requires']; // Mindestanforderung an WordPress-Version
                $result->tested         = $this->config['tested'];     // Getestet bis WordPress-Version
                $result->requires_php   = $this->config['requires_php']; // Mindestanforderung an PHP-Version
                $result->download_link  = $this->github_api_result->zipball_url;
                $result->sections       = array(
                    'description' => $this->plugin['Description'],
                    'changelog'   => $this->github_api_result->body
                );

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

        return $result;
    }
} // Schließe die Klasse korrekt

if (is_admin()) {
    // Nur zu Testzwecken: Transient löschen, um Updates zu erzwingen
    delete_site_transient('update_plugins');
    new TBA_Optimize_Updater(__FILE__);
}
