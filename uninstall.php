<?php
/**
 * Uninstall script for Favicon Prefixer plugin
 * 
 * This file is executed when the plugin is deleted from WordPress admin
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('favicon_prefixer_post_types');
delete_option('favicon_prefixer_debug_mode');

// Clear all transients
global $wpdb;
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_favicon_prefixer_') . '%',
        $wpdb->esc_like('_transient_timeout_favicon_prefixer_') . '%'
    )
);

// Clear object cache
wp_cache_flush_group('favicon_prefixer');

// Delete favicon files
$upload_dir = wp_upload_dir();
$favicon_dir = $upload_dir['basedir'] . '/favicons';

if (file_exists($favicon_dir)) {
    $files = glob("$favicon_dir/*.png");
    foreach ($files as $file) {
        unlink($file);
    }
    
    // Remove the favicon directory if it's empty
    if (is_dir($favicon_dir) && count(scandir($favicon_dir)) <= 2) {
        rmdir($favicon_dir);
    }
} 