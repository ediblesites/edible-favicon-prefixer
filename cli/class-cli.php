<?php

class Favicon_Prefixer_CLI extends WP_CLI_Command {
    private $favicon_service;
    private $plugin;

    public function __construct() {
        parent::__construct();
        
        // Initialize dependencies
        $this->favicon_service = new Favicon_Service();
        
        // Create plugin instance with dependencies
        $this->plugin = new Favicon_Prefixer($this->favicon_service);
    }

    /**
     * Run automated test on a URL
     * 
     * ## OPTIONS
     * 
     * <url>
     * : The full URL to test (e.g., https://example.com)
     * 
     * [--debug]
     * : Show detailed debugging information
     * 
     * ## EXAMPLES
     * 
     *     wp favicon autotest https://wordpress.org
     *     wp favicon autotest https://example.com --debug
     */
    public function autotest($args, $assoc_args) {
        list($url) = $args;
        $debug = isset($assoc_args['debug']);

        WP_CLI::log("Starting automated test for URL: $url");

        // Step 1: URL Validation
        WP_CLI::log("\n1. Testing URL validation...");
        try {
            if (!$this->favicon_service->is_external_url($url)) {
                WP_CLI::error("URL must be external for testing");
                return;
            }
            WP_CLI::success("URL validation successful");
        } catch (Exception $e) {
            WP_CLI::error("URL validation failed: " . $e->getMessage());
            return;
        }

        // Step 2: Favicon Retrieval
        WP_CLI::log("\n2. Testing favicon retrieval...");
        try {
            $start = microtime(true);
            $favicon_path = $this->favicon_service->get_favicon($url);
            $time = round(microtime(true) - $start, 2);
            
            if (!$favicon_path) {
                WP_CLI::error("Favicon retrieval failed");
                return;
            }
            WP_CLI::success("Favicon retrieved in {$time}s");
            if ($debug) WP_CLI::log("Favicon saved at: $favicon_path");
        } catch (Exception $e) {
            WP_CLI::error("Favicon retrieval failed: " . $e->getMessage());
            return;
        }

        // Step 3: Content Processing
        WP_CLI::log("\n3. Testing content processing...");
        try {
            $test_content = sprintf(
                '<article><p>Testing external link to <a href="%s">test website</a></p></article>',
                esc_url($url)
            );
            
            $processed_content = $this->plugin->process_content($test_content);
            
            if ($processed_content === $test_content) {
                WP_CLI::error("Content was not modified");
                return;
            }
            
            if (!strpos($processed_content, 'favicon-prefix')) {
                WP_CLI::error("Favicon was not added to content");
                return;
            }

            WP_CLI::success("Content processing successful");
            
            if ($debug) {
                WP_CLI::log("\nOriginal content:");
                WP_CLI::log($test_content);
                WP_CLI::log("\nProcessed content:");
                WP_CLI::log($processed_content);
            }
        } catch (Exception $e) {
            WP_CLI::error("Content processing failed: " . $e->getMessage());
            return;
        }

        WP_CLI::success("\nAutomated test completed successfully!");
    }

    /**
     * Clear the favicon cache
     * 
     * ## EXAMPLES
     * 
     *     wp favicon cache_clear
     */
    public function cache_clear($args, $assoc_args) {
        debug_log("CLI: Starting cache clear");
        
        $admin = new Favicon_Prefixer_Admin();
        $admin->clear_cache();
        
        debug_log("CLI: Cache clear completed");
        WP_CLI::success("Favicon cache cleared");
    }

    /**
     * Show cache statistics
     */
    public function cache_status() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_favicon_prefixer_') . '%'
            )
        );

        $upload_dir = wp_upload_dir();
        $favicon_dir = $upload_dir['basedir'] . '/favicons';
        $size = 0;
        
        if (file_exists($favicon_dir)) {
            foreach (glob("$favicon_dir/*.*") as $file) {
                $size += filesize($file);
            }
        }

        WP_CLI::log("Cached favicons: $count");
        WP_CLI::log("Cache size: " . size_format($size));
    }

    /**
     * List all cached favicons
     * 
     * ## OPTIONS
     * 
     * [--format=<format>]
     * : Output format (table, csv, json, yaml)
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - json
     *   - yaml
     * ---
     * 
     * ## EXAMPLES
     * 
     *     wp favicon cache_list
     *     wp favicon cache_list --format=json
     */
    public function cache_list($args, $assoc_args) {
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
            $filesize = size_format(filesize($filepath));
            $modified = date('Y-m-d H:i:s', filemtime($filepath));

            $items[] = [
                'domain' => str_replace('.png', '', $filename),
                'file' => $filename,
                'size' => $filesize,
                'modified' => $modified
            ];
        }

        if (empty($items)) {
            WP_CLI::warning('No cached favicons found');
            return;
        }

        WP_CLI\Utils\format_items(
            $assoc_args['format'] ?? 'table',
            $items,
            ['domain', 'file', 'size', 'modified']
        );
    }
} 