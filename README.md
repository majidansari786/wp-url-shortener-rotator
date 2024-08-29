# WP URL Shortener Rotator

**Version:** 1.5
**Author:** Mr_godfather9  
**License:** GPLv2 or later

## Description

**WP URL Shortener Rotator** is a WordPress plugin that automatically shortens URLs in your posts using multiple custom shorteners. The plugin rotates the shortened links on user clicks, providing a flexible and dynamic way to manage your URL shortening needs.

## Features

- **Multiple Custom Shorteners:** Supports multiple API tokens, allowing you to use different URL shortening services.
- **Automatic URL Shortening:** Automatically shortens all URLs in your post content.
- **Link Rotation:** Rotates between different shortened URLs each time a user clicks on the link.
- **Admin Settings Page:** Provides an easy-to-use settings page in the WordPress admin dashboard for managing API tokens.

## Installation

1. Download the plugin and extract it.
2. Upload the `wp-url-shortener-rotator` folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage

1. **Configure API Tokens:**
   - Navigate to `Settings > URL Shortener` in your WordPress admin dashboard.
   - Enter your API tokens for the supported URL shorteners (e.g., SetURL and ithers).
   - Save the settings.

2. **Create or Edit Posts:**
   - Create or edit a post with URLs in the content. The plugin will automatically shorten the URLs and replace them with custom short URLs like `yourdomain.com/?id=...`.

3. **URL Rotation:**
   - When a user clicks on the shortened URL, the plugin will rotate between the different shortened URLs each time.

## Customization

- **API Integration:** The plugin supports adding custom shorteners by integrating their API. You can modify the `class-url-shortener.php` file to add new shorteners.
- **Frontend Design:** The plugin includes custom CSS to enhance the appearance of the settings page in the WordPress admin area.

## Screenshots

1. **Admin Settings Page:**
   ![Admin Settings Page](https://telegra.ph/file/5c90002788a4727cf0b2d.png)

2. **Post Content with Shortened URLs:**
   ![Post Content](https://telegra.ph/file/73781ed373a9678466cc6.png)

## Changelog

### 1.0
- Initial release with support for multiple custom shorteners and URL rotation.

## License

This plugin is licensed under the GPLv2 or later.
