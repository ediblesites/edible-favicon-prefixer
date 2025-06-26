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

        register_setting(
            self::OPTION_GROUP,
            'favicon_prefixer_ignore_internal',
            [
                'type' => 'boolean',
                'default' => true,
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
            $cache_manager = new Cache_Manager();
            $cache_manager->clear_cache();
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
        
        require_once FAVICON_PREFIXER_PATH . 'admin/settings-page.php';
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