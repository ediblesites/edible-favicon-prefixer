<?php
/**
 * Plugin Name: Favicon Prefixer
 * Description: Prefixes favicons to links within content
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FAVICON_PREFIXER_VERSION', '1.0.0');
define('FAVICON_PREFIXER_FILE', __FILE__);
define('FAVICON_PREFIXER_PATH', plugin_dir_path(__FILE__));
define('FAVICON_PREFIXER_URL', plugin_dir_url(__FILE__));

// Register WP-CLI command
if (defined('WP_CLI') && WP_CLI) {
    require_once FAVICON_PREFIXER_PATH . 'cli/class-cli.php';
    WP_CLI::add_command('favicon', 'Favicon_Prefixer_CLI');
}

// Load Composer autoloader
require_once FAVICON_PREFIXER_PATH . 'vendor/autoload.php';

// Load includes
require_once FAVICON_PREFIXER_PATH . 'includes/class-favicon-service.php';
require_once FAVICON_PREFIXER_PATH . 'includes/class-favicon-prefixer.php';

/**
 * Initialize the plugin
 */
function favicon_prefixer_init() {
    $plugin = new Favicon_Prefixer();
    $plugin->init();
}
add_action('plugins_loaded', 'favicon_prefixer_init');

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function() {
    // Create upload directory for favicons
    $upload_dir = wp_upload_dir();
    $favicon_dir = $upload_dir['basedir'] . '/favicons';
    
    if (!file_exists($favicon_dir)) {
        wp_mkdir_p($favicon_dir);
    }
});
