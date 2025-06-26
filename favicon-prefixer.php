<?php
/**
 * Plugin Name: Favicon Prefixer
 * Description: Prefixes favicons to links within content
 * Version: 1.0.6
 * Author: Edible Sites
 * Author URI: https://ediblesites.com
 * Plugin URI: https://github.com/ediblesites/edible-favicon-prefixer
 * GitHub Plugin URI: https://github.com/ediblesites/edible-favicon-prefixer
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: favicon-prefixer
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('FAVICON_PREFIXER_VERSION', '1.0.6');
define('FAVICON_PREFIXER_FILE', __FILE__);
define('FAVICON_PREFIXER_PATH', plugin_dir_path(__FILE__));
define('FAVICON_PREFIXER_URL', plugin_dir_url(__FILE__));
define('FAVICON_PREFIXER_DIR', 'favicons'); 

// Load dependencies
require_once FAVICON_PREFIXER_PATH . 'vendor/autoload.php';
require_once FAVICON_PREFIXER_PATH . 'includes/class-utils.php';
require_once FAVICON_PREFIXER_PATH . 'includes/class-favicon-service.php';
require_once FAVICON_PREFIXER_PATH . 'includes/class-content-processor.php';
require_once FAVICON_PREFIXER_PATH . 'includes/class-cache-manager.php';

// Initialize plugin
add_action('plugins_loaded', 'favicon_prefixer_init');

// Activation hook
register_activation_hook(__FILE__, 'favicon_prefixer_activate');

// Uninstall hook
register_uninstall_hook(__FILE__, 'favicon_prefixer_uninstall');

/**
 * Check if required dependencies are available
 */
function favicon_prefixer_check_dependencies() {
    if (!file_exists(FAVICON_PREFIXER_PATH . 'vendor/autoload.php')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>Favicon Prefixer: Required dependencies are missing. Please run composer install.</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Initialize the plugin
 */
function favicon_prefixer_init() {
    // Check dependencies first
    if (!favicon_prefixer_check_dependencies()) {
        return;
    }

    // Load admin if needed
    if (is_admin()) {
        require_once FAVICON_PREFIXER_PATH . 'admin/class-admin.php';
        new Favicon_Prefixer_Admin();
    }

    // Load CLI if needed
    if (defined('WP_CLI') && WP_CLI) {
        require_once FAVICON_PREFIXER_PATH . 'cli/class-cli.php';
        WP_CLI::add_command('favicon', 'Favicon_Prefixer_CLI');
    }

    // Add content filter
    add_filter('the_content', 'favicon_prefixer_process_content', 10);
    add_action('wp_enqueue_scripts', 'favicon_prefixer_enqueue_styles');
}

/**
 * Process content to add favicons
 */
function favicon_prefixer_process_content($content) {
    static $processor = null;
    
    if ($processor === null) {
        $processor = new Content_Processor();
    }
    
    return $processor->process_content($content);
}

/**
 * Check if current post type is enabled
 */
function favicon_prefixer_is_enabled_post_type() {
    $post_type = get_post_type();
    $enabled_types = get_option('favicon_prefixer_post_types', ['post', 'page']);
    
    debug_log("Current post type: $post_type");
    debug_log("Enabled post types: " . print_r($enabled_types, true));
    
    return in_array($post_type, $enabled_types);
}

/**
 * Enqueue styles
 */
function favicon_prefixer_enqueue_styles() {
    wp_enqueue_style(
        'favicon-prefixer',
        FAVICON_PREFIXER_URL . 'assets/css/style.css',
        [],
        FAVICON_PREFIXER_VERSION
    );
}

/**
 * Activation hook
 */
function favicon_prefixer_activate() {
    // Create upload directory for favicons
    $favicon_dir = favicon_prefixer_get_favicon_dir();
    
    if (!file_exists($favicon_dir)) {
        wp_mkdir_p($favicon_dir);
    }
}

/**
 * Uninstallation hook
 */
function favicon_prefixer_uninstall() {
    // Delete all favicon transients and files
    $cache_manager = new Cache_Manager();
    $cache_manager->delete_favicon_transients();
    $cache_manager->delete_favicon_files();
    $cache_manager->delete_favicon_directory_if_empty();

    // Delete plugin options
    delete_option('favicon_prefixer_post_types');
    delete_option('favicon_prefixer_debug_mode');
    delete_option('favicon_prefixer_ignore_internal');

    // Clear object cache
    wp_cache_flush_group('favicon_prefixer');
}
