# Test Post for Favicon Prefixer

This is a test post to verify that the Favicon Prefixer plugin is working correctly. It contains various types of links to test different scenarios.

## External Links (Should Show Favicons)

Here are some external links that should display favicons:

- [WordPress.org](https://wordpress.org) - The official WordPress website
- [GitHub](https://github.com) - Code hosting platform
- [Stack Overflow](https://stackoverflow.com) - Developer Q&A site
- [Mozilla Developer Network](https://developer.mozilla.org) - Web development resources
- [CSS-Tricks](https://css-tricks.com) - Web design and development blog

## Mixed Content Links

Some links with different content types:

- [Google](https://google.com) - Simple text link
- [YouTube](https://youtube.com) - Video platform
- [Twitter](https://twitter.com) - Social media
- [Reddit](https://reddit.com) - Community platform
- [Wikipedia](https://wikipedia.org) - Online encyclopedia

## Links That Should NOT Show Favicons

### Internal Links
- [Home page](/)
- [About page](/about/)
- [Contact page](/contact/)

### Links with Images
- <a href="https://example.com"><img src="https://via.placeholder.com/100x50" alt="Example" /> Link with image</a>

### Nofollow Links
- [Sponsored link](https://example.com) (this would have rel="nofollow" in real scenarios)

## Multiple Links in Paragraphs

This paragraph contains multiple external links to test how the plugin handles [WordPress.org](https://wordpress.org) and [GitHub](https://github.com) links in the same content block. It should show favicons for both [Stack Overflow](https://stackoverflow.com) and [MDN](https://developer.mozilla.org) links.

## Technical Test Links

Testing various URL formats:

- [HTTP link](http://example.com) - Should work
- [HTTPS link](https://example.com) - Should work  
- [Subdomain](https://www.example.com) - Should work
- [Path with query](https://example.com/page?param=value) - Should work
- [Port number](https://example.com:8080) - Should work

## Performance Test

This section has many links to test performance:

- [Link 1](https://wordpress.org)
- [Link 2](https://github.com) 
- [Link 3](https://stackoverflow.com)
- [Link 4](https://developer.mozilla.org)
- [Link 5](https://css-tricks.com)
- [Link 6](https://google.com)
- [Link 7](https://youtube.com)
- [Link 8](https://twitter.com)
- [Link 9](https://reddit.com)
- [Link 10](https://wikipedia.org)

## Expected Behavior

After the plugin processes this content:

1. **External links** should show small favicon images next to the link text
2. **Internal links** should remain unchanged (no favicons)
3. **Links with images** should remain unchanged (no favicons)
4. **Nofollow links** should remain unchanged (no favicons)
5. **Favicons should be cached** - subsequent page loads should be faster

## Debug Information

If debug mode is enabled in the plugin settings, you should see log messages in your WordPress debug log showing:
- Which links were processed
- Which favicons were fetched
- Any errors that occurred

Check your WordPress debug log at `wp-content/debug.log` for detailed information about the plugin's operation. 