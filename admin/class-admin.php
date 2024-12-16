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
            'Favicon Prefixer Settings',
            'Favicon Prefixer',
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
                'default' => [],
                'sanitize_callback' => [$this, 'sanitize_post_types']
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

    public function sanitize_post_types($value) {
        if (!is_array($value)) {
            return [];
        }
        return array_filter($value, 'post_type_exists');
    }

    /**
     * Handle cache clear button submission
     */
    public function handle_cache_clear() {
        if (
            isset($_POST['favicon_prefixer_clear_cache']) && 
            check_admin_referer('favicon_prefixer_clear_cache')
        ) {
            $this->clear_cache();
            add_settings_error(
                'favicon_prefixer',
                'cache_cleared',
                'Favicon cache cleared successfully',
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
     * Clear favicon cache
     */
    public function clear_cache() {
        global $wpdb;
        
        // Delete transients
        $deleted_transients = $wpdb->query(
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
            $count = 0;
            foreach ($files as $file) {
                if (unlink($file)) {
                    $count++;
                } else {
                    debug_log("Failed to delete file: $file");
                }
            }
        }
    }
} 