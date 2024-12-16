# Favicon Prefixer

A WordPress plugin that automatically adds favicons in front of external links in your content. Uses Google's Favicon service with local caching for performance.

## Features

- Automatically adds favicons to external links
- Caches favicons locally for performance
- Configurable per post type
- Smart handling of image links (skips favicons for linked images)
- Support for opting out specific links
- Built-in debugging and testing tools
- Clean, accessible HTML output
- CLI tools for testing and maintenance

## Requirements

- WordPress 6.0 or higher
- PHP 8.2 or higher
- Composer for dependency management

### Composer Dependencies
- League URI ([league/uri](https://github.com/thephpleague/uri)): For URL validation and domain extraction
- Simple HTML DOM ([voku/simple_html_dom](https://github.com/voku/simple_html_dom)): For reliable HTML parsing

## Installation

1. Upload the plugin files to `/wp-content/plugins/favicon-prefixer`
2. If installing from source, run:
   ```bash
   cd wp-content/plugins/favicon-prefixer
   composer install
   ```
3. Activate the plugin through WordPress admin
4. Configure which post types should display favicons in Settings > Favicon Prefixer

## Configuration

### Admin Settings
- Navigate to Settings > Favicon Prefixer
- Select which post types should display favicons
- Enable/disable debug mode
- Clear favicon cache when needed

### Styling
The plugin adds minimal styling for favicons:
```css
.favicon-prefix {
    margin-right: 5px;
    vertical-align: middle;
}
```

## Testing

### CLI Commands
The plugin includes WP-CLI commands for testing and maintenance:

```bash
 Clear the favicon cache
wp favicon cache_clear

# Show cache statistics
wp favicon cache_status

# Test favicon retrieval and display for a URL
wp favicon autotest https://wordpress.org
```

## Architecture

### Core Components
- `class-favicon-prefixer.php`: Main plugin logic
- `class-favicon-service.php`: URL handling and favicon operations
- `class-cli.php`: WP-CLI integration
- `class-admin.php`: Admin interface and settings

### Processing Flow
1. Content filter detects external links
2. Validates URLs and extracts domains
3. Checks local cache for favicon
4. Retrieves from Google Favicon service if needed
5. Adds favicon image before link in content

## Development

### Debug Mode
Enable debug mode in settings to log:
- URL processing
- Favicon retrieval
- Cache operations
- Error conditions

### Custom Hooks
- `favicon_prefixer_post_types`: Filter for modifying enabled post types
- Various processing stage filters for customization

## License

This project is licensed under the GPL v2 or later. 

## Usage

### Basic Usage
Links will automatically get favicons added when the post type is enabled.

### Excluding Links
You can exclude specific links from getting favicons in two ways:

1. Links containing images are automatically skipped
2. Links with `rel="nofollow"` are skipped (can be set in WordPress's link editor)