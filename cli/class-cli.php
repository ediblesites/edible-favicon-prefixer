<?php

class Favicon_Prefixer_CLI extends WP_CLI_Command {
    private $favicon_service;

    public function __construct() {
        parent::__construct();
        
        // Initialize dependencies
        $this->favicon_service = new Favicon_Service();
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
            
            $processed_content = favicon_prefixer_process_content($test_content);
            
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
        $cache_manager = new Cache_Manager();
        $cache_manager->clear_cache();
        debug_log("CLI: Cache clear completed");
        WP_CLI::success("Favicon cache cleared");
    }

    /**
     * Show cache statistics
     */
    public function cache_status() {
        $cache_manager = new Cache_Manager();
        $status = $cache_manager->get_cache_status();
        WP_CLI::log("Cached favicons: {$status['count']}");
        WP_CLI::log("Cache size: " . size_format($status['size']));
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
        $cache_manager = new Cache_Manager();
        $items = $cache_manager->get_cached_favicons();

        $format = $assoc_args['format'] ?? 'table';
        foreach ($items as &$item) {
            $item['size'] = size_format($item['size']);
            $item['modified'] = date('Y-m-d H:i:s', $item['modified']);
        }
        unset($item);

        if (empty($items)) {
            WP_CLI::log('No cached favicons found.');
            return;
        }

        $fields = ['domain', 'file', 'size', 'modified'];
        WP_CLI\Utils\format_items($format, $items, $fields);
    }
} 