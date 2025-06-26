<?php
/**
 * Plugin Name: Favicon Prefixer
 * Description: Prefixes favicons to links within content
 * Version: 1.0.1
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Text Domain: favicon-prefixer
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('FAVICON_PREFIXER_VERSION', '1.0.1');
define('FAVICON_PREFIXER_FILE', __FILE__);
define('FAVICON_PREFIXER_PATH', plugin_dir_path(__FILE__));
define('FAVICON_PREFIXER_URL', plugin_dir_url(__FILE__));

// Load dependencies
require_once FAVICON_PREFIXER_PATH . 'vendor/autoload.php';
require_once FAVICON_PREFIXER_PATH . 'includes/class-utils.php';
require_once FAVICON_PREFIXER_PATH . 'includes/class-favicon-service.php';

// Initialize plugin
add_action('plugins_loaded', 'favicon_prefixer_init');

// Activation hook
register_activation_hook(__FILE__, 'favicon_prefixer_activate');

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
    // Only process enabled post types
    if (!favicon_prefixer_is_enabled_post_type()) {
        return $content;
    }

    // Check cache
    $cache_key = 'favicon_prefixer_' . md5($content);
    $cached_content = wp_cache_get($cache_key, 'favicon_prefixer');
    if ($cached_content !== false) {
        return $cached_content;
    }

    debug_log("Process content called");

    try {
        $html = \voku\helper\HtmlDomParser::str_get_html($content);
        if (!$html) {
            debug_log('Failed to parse HTML content');
            return $content;
        }

        $favicon_service = new Favicon_Service();
        $links = $html->findMulti('a[href]:not([rel*="nofollow"])');
        debug_log("Found " . count($links) . " candidate links in content");

        foreach ($links as $link) {
            $url = $link->getAttribute('href');
            debug_log("Processing URL: $url");
            
            // Skip if already has favicon
            if ($link->getAttribute('data-has-favicon')) {
                debug_log("Skipping - already has favicon");
                continue;
            }

            // Skip if link contains an image
            $images = $link->findMulti('img');
            if (count($images) > 0) {
                debug_log("Skipping - contains image");
                continue;
            }

            if (empty($url) || !$favicon_service->is_external_url($url)) {
                debug_log("Skipping - empty URL or not external");
                continue;
            }

            debug_log("About to get favicon for URL: $url");
            $favicon_path = $favicon_service->get_favicon($url);
            if (!$favicon_path) {
                debug_log("No favicon path returned for URL: $url");
                continue;
            }

            $favicon_url = str_replace(wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $favicon_path);
            $favicon_html = sprintf(
                '<img src="%s" class="favicon-prefix" alt="" />',
                esc_url($favicon_url)
            );

            // Wrap favicon and link content
            $link->innerhtml = $favicon_html . $link->innerhtml;
            $link->outerhtml = '<span class="favicon-nowrap">' . $link->outerhtml . '</span>';
        }

        $processed_content = $html->save();
        
        // Cache the processed content
        wp_cache_set($cache_key, $processed_content, 'favicon_prefixer', HOUR_IN_SECONDS);
        
        return $processed_content;
        
    } catch (Exception $e) {
        debug_log('Error processing content: ' . $e->getMessage());
        return $content;
    }
}

/**
 * Check if current post type is enabled
 */
function favicon_prefixer_is_enabled_post_type() {
    $post_type = get_post_type();
    $enabled_types = get_option('favicon_prefixer_post_types', []);
    
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
    $upload_dir = wp_upload_dir();
    $favicon_dir = $upload_dir['basedir'] . '/favicons';
    
    if (!file_exists($favicon_dir)) {
        wp_mkdir_p($favicon_dir);
    }
}
