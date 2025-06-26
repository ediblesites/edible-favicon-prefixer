<?php
if (!defined('ABSPATH')) {
    exit;
}

$post_types = get_post_types(['public' => true], 'objects');
$enabled_types = get_option('favicon_prefixer_post_types', []);
$debug_mode = get_option('favicon_prefixer_debug_mode', false);
?>

<div class="wrap">
    <h1>Favicon Prefixer Settings</h1>
    
    <!-- Plugin Settings Form -->
    <form method="post" action="options.php">
        <?php settings_fields(Favicon_Prefixer_Admin::OPTION_GROUP); ?>

        <h2>Post Types</h2>
        <p>Select which post types should display favicons:</p>
        <?php
        foreach ($post_types as $type) {
            ?>
            <label>
                <input type="checkbox" 
                       name="favicon_prefixer_post_types[]" 
                       value="<?php echo esc_attr($type->name); ?>"
                       <?php checked(in_array($type->name, $enabled_types)); ?>>
                <?php echo esc_html($type->label); ?>
            </label><br>
            <?php
        }
        ?>

        <h2>Debug Mode</h2>
        <p>
            <label>
                <input type="checkbox" 
                       name="favicon_prefixer_debug_mode" 
                       value="1"
                       <?php checked($debug_mode); ?>>
                Enable debug logging
            </label>
        </p>

        <?php submit_button(); ?>
    </form>

    <!-- Cache Management Form -->
    <form method="post" action="">
        <h2>Cache Management</h2>
        <?php 
        wp_nonce_field('favicon_prefixer_clear_cache');
        submit_button('Clear Cache', 'secondary', 'favicon_prefixer_clear_cache');
        ?>
    </form>

    <h2>CLI Commands</h2>
    <p>The following WP-CLI commands are available for managing favicons:</p>
    
    <div class="card">
        <h3>List Cached Favicons</h3>
        <code>wp favicon cache_list [--format=&lt;format&gt;]</code>
        <p>Shows all cached favicons with their domains, file sizes, and last modified dates.</p>
        <p>Available formats: table (default), csv, json, yaml</p>
    </div>

    <div class="card">
        <h3>Clear Cache</h3>
        <code>wp favicon cache_clear</code>
        <p>Removes all cached favicons and their associated transients.</p>
    </div>

    <div class="card">
        <h3>Cache Status</h3>
        <code>wp favicon cache_status</code>
        <p>Displays cache statistics including number of favicons and total cache size.</p>
    </div>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
    max-width: 800px;
}
.card h3 {
    margin-top: 0;
}
.card code {
    display: block;
    padding: 10px;
    background: #f0f0f1;
    margin: 10px 0;
}
</style>