<?php

/**
 * Simple debug logging function
 */
if (!function_exists('debug_log')) {
    function debug_log($message) {
        if (get_option('favicon_prefixer_debug_mode', false)) {
            error_log('[Favicon Prefixer] ' . $message);
        }
    }
}

/**
 * Get the favicon directory path
 */
if (!function_exists('favicon_prefixer_get_favicon_dir')) {
    function favicon_prefixer_get_favicon_dir() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/' . FAVICON_PREFIXER_DIR;
    }
}

/**
 * Sanitize domain name for filename while preserving dots
 */
if (!function_exists('favicon_prefixer_sanitize_domain_filename')) {
    function favicon_prefixer_sanitize_domain_filename($domain) {
        // Replace only characters that are problematic for filenames
        // but preserve dots for domain names
        $sanitized = $domain;
        
        // Replace characters that are invalid in filenames
        $sanitized = str_replace(['<', '>', ':', '"', '|', '?', '*', '\\', '/'], '_', $sanitized);
        
        // Replace spaces with underscores
        $sanitized = str_replace(' ', '_', $sanitized);
        
        // Remove any other non-alphanumeric characters except dots and hyphens
        $sanitized = preg_replace('/[^a-zA-Z0-9.-]/', '_', $sanitized);
        
        // Remove multiple consecutive underscores
        $sanitized = preg_replace('/_+/', '_', $sanitized);
        
        // Remove leading/trailing underscores
        $sanitized = trim($sanitized, '_');
        
        return $sanitized;
    }
} 