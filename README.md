# Favicon Prefixer

A WordPress plugin that automatically prefixes favicons to external links in your content.

**Version:** 1.0.5

## Features

- **Automatic favicon detection** - Uses Google's favicon service to fetch favicons
- **Smart caching** - Caches favicons locally for better performance
- **Post type control** - Choose which post types display favicons
- **Internal link control** - Option to process or ignore internal links
- **Selective exclusion** - Use `rel="nofavicon"` to exclude specific links
- **Debug logging** - Built-in debugging for troubleshooting
- **WP-CLI support** - Command-line tools for cache management
- **Clean uninstall** - Removes all data when plugin is deleted

## Installation

1. Click the **"<> Code"** button on this GitHub page
2. Select **"Download ZIP"**
3. Upload the ZIP file to your WordPress site via **Plugins > Add New > Upload Plugin**
4. Activate the plugin

## Updates

This plugin supports automatic updates via the [Git Updater](https://github.com/afragen/git-updater) plugin:

1. After installing the plugin as described above, install the [Git Updater](https://github.com/afragen/git-updater/releases/) plugin
2. Activate it and click 'Activate Free License' (no license required)
3. The plugin will automatically detect this repository and offer updates

## Configuration

The plugin has sensible defaults, so should require no configuration on installation. The following configuration settings are available.

### Post Types
Select which post types should display favicons. Defaults to Posts and Pages.

### Ignore internal links
When enabled, only external links get favicons. Defaults to "On".

### Debug Mode
Enable debug logging to troubleshoot issues. Logs are written to WordPress debug log. Defaults to "off".

## Usage

### Automatic Processing
The plugin automatically processes content when posts are displayed. No additional setup required.

### Excluding Specific Links
To exclude a specific link from getting a favicon, add `rel="nofavicon"` to the link:

```html
<a href="https://example.com" rel="nofavicon">This link won't get a favicon</a>
```

You can combine this with other rel attributes:
```html
<a href="https://example.com" rel="nofollow nofavicon">Nofollow link without favicon</a>
```

### Cache Management
Use the "Clear Cache" button in settings to remove all cached favicons.

## WP-CLI Commands

### List cached favicons
```bash
wp favicon cache_list [--format=table|csv|json|yaml]
```

### Clear cache
```bash
wp favicon cache_clear
```

### Test a URL
```bash
wp favicon autotest https://example.com [--debug]
```

## Technical Details

### Favicon Storage
Favicons are stored in `wp-content/uploads/favicons/` and cached for 30 days.

### Content Processing
- Uses HTML DOM parsing for reliable link detection
- Processes content through WordPress `the_content` filter
- Caches processed content for 1 hour to improve performance

### Caching Strategy
- **Transient caching** - Favicon file paths cached for 30 days
- **Object caching** - Processed content cached for 1 hour
- **File caching** - Favicon files stored locally

---

**Made this for us at [Edible Sites](https://ediblesites.com), sharing it with you ❤️** We're also behind:

* [PayPerFax.com](https://payperfax.com/), a pay-per-use online fax service, and
* [Faxbeep.com](https://faxbeep.com), a free fax testing service