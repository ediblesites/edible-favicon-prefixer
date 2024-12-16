<?php

use League\Uri\Uri;

class Favicon_Service {
    private const GOOGLE_FAVICON_URL = 'https://www.google.com/s2/favicons';
    private const FAVICON_SIZE = 16;
    private const CACHE_DURATION = 30 * DAY_IN_SECONDS;

    /**
     * Get favicon for a URL, using cache if available
     */
    public function get_favicon($url) {
        if (!$this->is_valid_url($url)) {
            error_log("Invalid URL: $url");
            return false;
        }

        $domain = $this->get_domain($url);
        if (!$domain) {
            error_log("Couldn't extract domain from URL: $url");
            return false;
        }

        $cached = $this->get_cached_favicon($domain);
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
    private function is_valid_url($url) {
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
            return strtolower($uri->getHost());
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get cached favicon path if valid
     */
    private function get_cached_favicon($domain) {
        $path = get_transient('favicon_prefixer_' . md5($domain));
        if (!$path || !file_exists($path)) {
            return false;
        }
        return $path;
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
            error_log("Failed to fetch favicon for $domain: " . $response->get_error_message());
            return false;
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            error_log("Invalid response code for favicon fetch: " . wp_remote_retrieve_response_code($response));
            return false;
        }

        $favicon_data = wp_remote_retrieve_body($response);
        return $this->save_favicon($domain, $favicon_data);
    }

    /**
     * Save favicon to local filesystem and cache its location
     */
    private function save_favicon($domain, $favicon_data) {
        $upload_dir = wp_upload_dir();
        $favicon_dir = $upload_dir['basedir'] . '/favicons';
        
        $filename = $domain . '.png';
        $filepath = $favicon_dir . '/' . $filename;

        if (!file_put_contents($filepath, $favicon_data)) {
            error_log("Failed to save favicon for domain: $domain");
            return false;
        }

        set_transient('favicon_prefixer_' . md5($domain), $filepath, self::CACHE_DURATION);
        debug_log("Saved favicon for domain: $domain at $filepath");
        
        return $filepath;
    }
} 