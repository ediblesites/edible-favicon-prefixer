<?php

class Favicon_Prefixer {
    private $favicon_service;

    public function __construct($favicon_service = null) {
        if ($favicon_service) {
            $this->favicon_service = $favicon_service;
        }
    }

    public function init() {
        if (!$this->favicon_service) {
            require_once FAVICON_PREFIXER_PATH . 'includes/class-favicon-service.php';
            $this->favicon_service = new Favicon_Service();
        }
        
        if (is_admin()) {
            require_once FAVICON_PREFIXER_PATH . 'admin/class-admin.php';
            new Favicon_Prefixer_Admin();
        }

        add_filter('the_content', [$this, 'process_content']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function process_content($content) {
        debug_log("Process content called");
        
        try {
            $html = \voku\helper\HtmlDomParser::str_get_html($content);
            if (!$html) {
                debug_log('Failed to parse HTML content');
                return $content;
            }

            $links = $html->findMulti('a[href]:not([rel*="nofollow"])');
            debug_log("Found " . count($links) . " candidate links in content");

            foreach ($links as $link) {
                $url = $link->getAttribute('href');
                debug_log("Processing URL: $url");
                
                // Skip if already has a favicon
                if ($link->getAttribute('data-has-favicon')) {
                    debug_log("Skipping - already has favicon");
                    continue;
                }

                // Skip if link contains an image
                $images = $link->findMulti('img');
                debug_log("Found " . count($images) . " images in link");
                if (count($images) > 0) {
                    debug_log("Skipping - contains image");
                    continue;
                }

                if (empty($url) || !$this->favicon_service->is_external_url($url)) {
                    debug_log("Skipping - empty URL or not external");
                    continue;
                }

                $favicon_path = $this->favicon_service->get_favicon($url);
                if (!$favicon_path) {
                    continue;
                }

                $favicon_url = str_replace(wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $favicon_path);
                $favicon_html = sprintf(
                    '<img src="%s" class="favicon-prefix" alt="" />',
                    esc_url($favicon_url)
                );

                // Wrap favicon and link content while preserving the link tag
                $link->innerhtml = $favicon_html . $link->innerhtml;
                $link->outerhtml = '<span class="favicon-nowrap">' . $link->outerhtml . '</span>';
            }

            return $html->save();
            
        } catch (Exception $e) {
            debug_log('Error processing content: ' . $e->getMessage());
            return $content;
        }
    }

    private function is_enabled_post_type() {
        $post_type = get_post_type();
        $enabled_types = get_option('favicon_prefixer_post_types', []);
        
        debug_log("Current post type: $post_type");
        debug_log("Enabled post types: " . print_r($enabled_types, true));
        
        return in_array($post_type, $enabled_types);
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'favicon-prefixer',
            FAVICON_PREFIXER_URL . 'assets/css/style.css',
            [],
            FAVICON_PREFIXER_VERSION
        );
    }
} 