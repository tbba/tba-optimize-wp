<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Exit if accessed directly
}

// Remove plugin options from the database
delete_option('tba_optimize_options');
delete_site_option('tba_optimize_options'); // In case it was a multisite option
