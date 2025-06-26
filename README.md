# Favicon Prefixer

A WordPress plugin that automatically adds favicons to external links in your content.

## Features

- Automatically detects external links in post content
- Fetches favicons from Google's favicon service
- Caches favicons locally for performance
- Configurable post type support
- Debug logging for troubleshooting
- WP-CLI commands for management
- Clear favicon cache when needed

## Installation

1. Upload the plugin files to `/wp-content/plugins/favicon-prefixer/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure settings at Settings → Favicon Prefixer

## Usage

### Basic Usage

Once activated, the plugin will automatically add favicons to external links in your content. No additional configuration required.

### Configuration

Go to **Settings → Favicon Prefixer** to configure:

- **Post Types**: Select which post types should display favicons
- **Debug Mode**: Enable detailed logging for troubleshooting

### Cache Management

Clear the favicon cache:
```
wp favicon cache_clear
```

Show cache statistics:
```
wp favicon cache_status
```

## WP-CLI Commands

### Clear Cache
```
wp favicon cache_clear
```

### Show Cache Statistics
```
wp favicon cache_status
```

### List Cached Favicons
```
wp favicon cache_list [--format=<format>]
```

Available formats: table (default), csv, json, yaml

## How It Works

1. Scans post content for external links
2. Extracts domain from each external URL
3. Checks local cache for favicon
4. Fetches favicon from Google service if not cached
5. Adds favicon image before link text
6. Caches processed content for performance

## Requirements

- WordPress 6.0 or higher
- PHP 8.2 or higher
- Composer dependencies (installed automatically)

## Version

**Current Version:** 1.0.3