<?php

class Content_Processor {
    private $favicon_service;

    public function __construct() {
        $this->favicon_service = new Favicon_Service();
    }

    /**
     * Process content to add favicons to external links
     */
    public function process_content($content) {
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

            $links = $html->findMulti('a[href]:not([rel*="nofavicon"])');
            debug_log("Found " . count($links) . " candidate links in content");

            foreach ($links as $link) {
                $this->process_link($link);
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
     * Process a single link element
     */
    private function process_link($link) {
        $url = $link->getAttribute('href');
        debug_log("Processing URL: $url");
        
        // Skip if already has favicon
        if ($link->getAttribute('data-has-favicon')) {
            debug_log("Skipping - already has favicon");
            return;
        }

        // Skip if link contains an image
        $images = $link->findMulti('img');
        if (count($images) > 0) {
            debug_log("Skipping - contains image");
            return;
        }

        if (empty($url)) {
            debug_log("Skipping - empty URL");
            return;
        }

        // Check if we should ignore internal links
        $ignore_internal = get_option('favicon_prefixer_ignore_internal', true);
        if ($ignore_internal && !$this->favicon_service->is_external_url($url)) {
            debug_log("Skipping - internal link and ignore_internal is enabled");
            return;
        }

        // If not ignoring internal links, still check if it's a valid URL
        if (!$this->favicon_service->is_valid_url($url)) {
            debug_log("Skipping - invalid URL");
            return;
        }

        debug_log("About to get favicon for URL: $url");
        $favicon_path = $this->favicon_service->get_favicon($url);
        if (!$favicon_path) {
            debug_log("No favicon path returned for URL: $url");
            return;
        }

        $this->add_favicon_to_link($link, $favicon_path);
    }

    /**
     * Add favicon to a link element
     */
    private function add_favicon_to_link($link, $favicon_path) {
        $favicon_url = str_replace(wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $favicon_path);
        $favicon_html = sprintf(
            '<img src="%s" class="favicon-prefix" alt="" />',
            esc_url($favicon_url)
        );

        // Wrap favicon and link content
        $link->innerhtml = $favicon_html . $link->innerhtml;
        $link->outerhtml = '<span class="favicon-nowrap">' . $link->outerhtml . '</span>';
    }
} 