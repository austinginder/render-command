# Render Command

A WordPress plugin that provides a WP-CLI command to render a WordPress URL path and output the raw HTML. It includes an optional flag to exclude specific plugins from the request, useful for debugging or performance testing.

## Description

The **Render Command** plugin adds a `wp render` command to WP-CLI, allowing you to render a WordPress page by specifying its URL path. The plugin supports excluding specific plugins during the request using the `--without-plugins` flag, which can help isolate issues or test performance without certain plugins.

When activated, the plugin creates a must-use (mu-plugin) file to handle plugin exclusion logic via a query parameter. This mu-plugin is automatically removed upon deactivation.

## Installation

1. Download the plugin from [GitHub](https://github.com/austinginder/render-command).
2. Place the plugin folder in your WordPress `/wp-content/plugins/` directory.
3. Activate the plugin via the WordPress admin panel or using WP-CLI:
   ```bash
   wp plugin activate render-command
   ```
4. Ensure WP-CLI is installed and configured on your server.

## Usage

The plugin provides the following WP-CLI command:

```bash
wp render <path> [--without-plugins=<plugins>]
```

### Options

- `<path>`: The URL path to render (e.g., `/about-us/`).
- `[--without-plugins=<plugins>]`: A comma-separated list of plugin slugs to exclude from the request (e.g., `jetpack,wordpress-seo`).

### Examples

- Render the homepage:
  ```bash
  wp render "/"
  ```

- Render an about page excluding specific plugins:
  ```bash
  wp render "/about-us/" --without-plugins="jetpack,wordpress-seo"
  ```

## How It Works

- **Plugin Exclusion**: The `--without-plugins` flag adds a query parameter (`exclude_plugins`) to the request URL. The mu-plugin filters the `option_active_plugins` hook to exclude plugins based on their slugs.
- **MU-Plugin**: On activation, the plugin creates an mu-plugin (`render-command.php`) in the `/wp-content/mu-plugins/` directory to handle plugin exclusion logic. This file is removed on deactivation.
- **Output**: The command outputs the raw HTML of the rendered page to the terminal.

## Requirements

- WordPress 5.0 or higher
- WP-CLI 2.0 or higher
- PHP 7.4 or higher

## License

This plugin is licensed under the [MIT License](https://opensource.org/licenses/MIT).

## Author

- **Austin Ginder**
- Website: [austinginder.com](https://austinginder.com)
- GitHub: [github.com/austinginder](https://github.com/austinginder)

## Contributing

Contributions are welcome! Please submit issues or pull requests on the [GitHub repository](https://github.com/austinginder/render-command).

## Changelog

### 1.0.0
- Initial release with `wp render` command and plugin exclusion functionality.