<?php

use League\Uri\Uri;

class Favicon_Service {
    private const GOOGLE_FAVICON_URL = 'https://www.google.com/s2/favicons';
    private const FAVICON_SIZE = 64;
    private $cache_manager;

    public function __construct() {
        $this->cache_manager = new Cache_Manager();
    }

    /**
     * Get favicon for a URL, using cache if available
     */
    public function get_favicon($url) {
        if (!$this->is_valid_url($url)) {
            debug_log("Invalid URL: $url");
            return false;
        }

        $domain = $this->get_domain($url);
        if (!$domain) {
            debug_log("Couldn't extract domain from URL: $url");
            return false;
        }

        $cached = $this->cache_manager->get_cached_favicon($domain);
        if ($cached) {
            debug_log("Using cached favicon for domain: $domain");
            return $cached;
        }

        debug_log("Fetching new favicon for domain: $domain");
        return $this->fetch_and_cache_favicon($domain);
    }

    /**
     * Check if URL is external to the site
     */
    public function is_external_url($url) {
        if (!$this->is_valid_url($url)) {
            return false;
        }

        $site_domain = parse_url(home_url(), PHP_URL_HOST);
        $url_domain = $this->get_domain($url);

        return $url_domain && $url_domain !== $site_domain;
    }

    /**
     * Validate URL format
     */
    public function is_valid_url($url) {
        if (empty($url)) {
            return false;
        }

        try {
            Uri::createFromString($url);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Extract and normalize domain from URL
     */
    private function get_domain($url) {
        try {
            $uri = Uri::createFromString($url);
            $host = strtolower($uri->getHost());
            debug_log("get_domain: URL=$url, extracted_host=$host");
            return $host;
        } catch (Exception $e) {
            debug_log("get_domain: Error extracting domain from $url: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch favicon from Google service and cache it
     */
    private function fetch_and_cache_favicon($domain) {
        $favicon_url = add_query_arg([
            'domain' => $domain,
            'sz' => self::FAVICON_SIZE
        ], self::GOOGLE_FAVICON_URL);

        $response = wp_remote_get($favicon_url);
        
        if (is_wp_error($response)) {
            debug_log("Failed to fetch favicon for $domain: " . $response->get_error_message());
            return false;
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            debug_log("Invalid response code for favicon fetch: " . wp_remote_retrieve_response_code($response));
            return false;
        }

        $favicon_data = wp_remote_retrieve_body($response);
        return $this->save_favicon($domain, $favicon_data);
    }

    /**
     * Save favicon to local filesystem and cache its location
     */
    private function save_favicon($domain, $favicon_data) {
        debug_log("save_favicon: Starting with domain=$domain");
        
        $favicon_dir = favicon_prefixer_get_favicon_dir();
        
        // Custom domain sanitization that preserves dots
        $filename = favicon_prefixer_sanitize_domain_filename($domain) . '.png';
        debug_log("save_favicon: domain=$domain, sanitized_filename=$filename");
        
        $filepath = $favicon_dir . '/' . $filename;
        debug_log("save_favicon: full_filepath=$filepath");

        if (!file_put_contents($filepath, $favicon_data)) {
            debug_log("Failed to save favicon for domain: $domain");
            return false;
        }

        $this->cache_manager->cache_favicon($domain, $filepath);
        debug_log("Saved favicon for domain: $domain at $filepath");
        
        return $filepath;
    }
} 