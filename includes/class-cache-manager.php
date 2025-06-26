<?php

class Cache_Manager {
    private const CACHE_GROUP = 'favicon_prefixer';
    private const CACHE_DURATION = 30 * DAY_IN_SECONDS;

    /**
     * Get cached favicon path if valid
     */
    public function get_cached_favicon($domain) {
        $path = get_transient('favicon_prefixer_' . md5($domain));
        if (!$path || !file_exists($path)) {
            return false;
        }
        return $path;
    }

    /**
     * Cache favicon path for a domain
     */
    public function cache_favicon($domain, $filepath) {
        set_transient('favicon_prefixer_' . md5($domain), $filepath, self::CACHE_DURATION);
        debug_log("Cached favicon for domain: $domain at $filepath");
    }

    /**
     * Clear all favicon cache
     */
    public function clear_cache() {
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
        $this->delete_favicon_files();

        debug_log("Cache cleared successfully");
    }

    /**
     * Get cache statistics
     */
    public function get_cache_status() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_favicon_prefixer_') . '%'
            )
        );

        $favicon_dir = favicon_prefixer_get_favicon_dir();
        $size = 0;
        
        if (file_exists($favicon_dir)) {
            foreach (glob("$favicon_dir/*.*") as $file) {
                $size += filesize($file);
            }
        }

        return [
            'count' => $count,
            'size' => $size
        ];
    }

    /**
     * Get list of cached favicons
     */
    public function get_cached_favicons() {
        global $wpdb;
        
        // Get all favicon transients
        $transients = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM $wpdb->options 
                WHERE option_name LIKE %s 
                AND option_name NOT LIKE %s",
                $wpdb->esc_like('_transient_favicon_prefixer_') . '%',
                $wpdb->esc_like('_transient_timeout_favicon_prefixer_') . '%'
            )
        );

        $items = [];
        foreach ($transients as $transient) {
            $filepath = $transient->option_value;
            if (!file_exists($filepath)) {
                continue;
            }

            $filename = basename($filepath);
            $filesize = filesize($filepath);
            $modified = filemtime($filepath);

            $items[] = [
                'domain' => str_replace('.png', '', $filename),
                'file' => $filename,
                'size' => $filesize,
                'modified' => $modified
            ];
        }

        return $items;
    }

    /**
     * Delete all favicon files
     */
    public function delete_favicon_files() {
        $favicon_dir = favicon_prefixer_get_favicon_dir();
        
        if (file_exists($favicon_dir)) {
            $files = glob("$favicon_dir/*.png");
            foreach ($files as $file) {
                if (!unlink($file)) {
                    debug_log("Failed to delete file: $file");
                }
            }
        }
    }

    /**
     * Delete favicon directory if empty
     */
    public function delete_favicon_directory_if_empty() {
        $favicon_dir = favicon_prefixer_get_favicon_dir();
        
        if (is_dir($favicon_dir) && count(scandir($favicon_dir)) <= 2) {
            rmdir($favicon_dir);
        }
    }

    /**
     * Delete all favicon transients (no capability check)
     */
    public function delete_favicon_transients() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like('_transient_favicon_prefixer_') . '%',
                $wpdb->esc_like('_transient_timeout_favicon_prefixer_') . '%'
            )
        );
    }
} 