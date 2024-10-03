<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Exit if accessed directly
}

// Delete the options saved by the plugin
delete_option('tba_optimize_options');
