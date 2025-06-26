<?php

class Favicon_Prefixer_Admin {
    private const OPTION_GROUP = 'favicon_prefixer_options';
    private const SETTINGS_PAGE = 'favicon-prefixer-settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'handle_cache_clear']);
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_options_page(
            __('Favicon Prefixer Settings', 'favicon-prefixer'),
            __('Favicon Prefixer', 'favicon-prefixer'),
            'manage_options',
            self::SETTINGS_PAGE,
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            self::OPTION_GROUP,
            'favicon_prefixer_post_types',
            [
                'type' => 'array',
                'default' => ['post', 'page'],
                'sanitize_callback' => 'favicon_prefixer_sanitize_post_types'
            ]
        );

        register_setting(
            self::OPTION_GROUP,
            'favicon_prefixer_debug_mode',
            [
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean'
            ]
        );
    }

    /**
     * Handle cache clear button submission
     */
    public function handle_cache_clear() {
        if (
            isset($_POST['favicon_prefixer_clear_cache']) && 
            check_admin_referer('favicon_prefixer_clear_cache')
        ) {
            favicon_prefixer_clear_cache();
            add_settings_error(
                'favicon_prefixer',
                'cache_cleared',
                __('Favicon cache cleared successfully', 'favicon-prefixer'),
                'success'
            );
        }
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Show admin notices
        settings_errors('favicon_prefixer');
        
        require_once FAVICON_PREFIXER_PATH . 'admin/views/settings-page.php';
    }

    /**
     * Get filtered post types for favicon prefixer
     */
    public static function get_filtered_post_types() {
        $post_types = get_post_types(['public' => true], 'objects');
        
        // Filter out attachment (media) post type
        unset($post_types['attachment']);
        
        return $post_types;
    }
}

/**
 * Sanitize post types setting
 */
function favicon_prefixer_sanitize_post_types($value) {
    if (!is_array($value)) {
        return [];
    }
    return array_filter($value, 'post_type_exists');
}

/**
 * Clear favicon cache
 */
function favicon_prefixer_clear_cache() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'favicon-prefixer'));
    }
    
    global $wpdb;
    
    // Delete transients
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like('_transient_favicon_prefixer_') . '%',
            $wpdb->esc_like('_transient_timeout_favicon_prefixer_') . '%'
        )
    );

    // Delete files
    $upload_dir = wp_upload_dir();
    $favicon_dir = $upload_dir['basedir'] . '/favicons';
    
    if (file_exists($favicon_dir)) {
        $files = glob("$favicon_dir/*.png");
        foreach ($files as $file) {
            if (!unlink($file)) {
                debug_log("Failed to delete file: $file");
            }
        }
    }
} 