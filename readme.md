# Render Command

A WordPress plugin that provides a WP-CLI command to render a WordPress URL path and output the raw HTML, HTTP status code or the URL itself. It includes an optional flag to exclude specific plugins from the request, useful for debugging or performance testing.

## Description

The **Render Command** plugin adds a `wp render` command to WP-CLI, allowing you to render a WordPress page by specifying its URL path. The plugin supports excluding specific plugins during the request using the `--without-plugins` flag, which can help isolate issues or test performance without certain plugins.

When activated, the plugin creates a must-use (mu-plugin) file to handle plugin exclusion logic via secure query parameters. This mu-plugin is automatically removed upon deactivation. You can also use the `--link` flag to generate and output the URL (including exclusion parameters) without actually making the HTTP request.

## Installation

Download the plugin from [GitHub](https://github.com/austinginder/render-command/releases/latest/download/render-command.zip) or install and activate using WP-CLI:

```bash
wp plugin install https://github.com/austinginder/render-command/releases/latest/download/render-command.zip --activate --force
```

## Usage

The plugin provides the following WP-CLI command:

```bash
wp render <path> [--without-plugins[=<plugins>]] [--format=<format>] [--link]
```

### Options

- `<path>`: The URL path to render (e.g., `/about-us/`).
- `[--without-plugins[=<plugins>]]`: Exclude plugins for the request. If the flag is provided without a value, all plugins are excluded. If a comma-separated list of plugin slugs is provided (e.g., `jetpack,wordpress-seo`), only those specific plugins are excluded. Requires a valid `AUTH_SALT` defined in `wp-config.php`. This option modifies the URL generated when used with `--link`.
- `[--format=<format>]`: Determine the output format when making the request (ignored if `--link` is used).
  - `raw` (default): Outputs the full HTML response body.
  - `http_code`: Outputs only the HTTP status code (e.g., `200`, `404`).
- `[--link]`: If present, output the generated URL (including any exclusion parameters) instead of making the HTTP request.

### Examples

- Render the homepage:
  ```bash
  wp render "/"
  ```

- Render an about page excluding the `jetpack` and `wordpress-seo` plugins:
  ```bash
  wp render "/about-us/" --without-plugins="jetpack,wordpress-seo"
  ```

- Render a contact page excluding *all* plugins:
  ```bash
  wp render "/contact" --without-plugins
  ```

- Get only the HTTP status code for the shop page, excluding all plugins:
  ```bash
  wp render "/shop" --without-plugins --format=http_code
  ```

- Output the URL that *would* be requested for the products page, excluding all plugins, without making the request:
  ```bash
  wp render "/products" --without-plugins --link
  ```

## How It Works

- **Plugin Exclusion**: The `--without-plugins` flag adds secure query parameters (`exclude_plugins` and `exclusion_token`) to the request URL. The mu-plugin validates the token (based on `AUTH_SALT`) and filters the `option_active_plugins` hook to exclude plugins based on their slugs for that specific request only.
- **MU-Plugin**: On activation, the plugin creates an mu-plugin (`render-command.php`) in the `/wp-content/mu-plugins/` directory to handle plugin exclusion logic. This file is removed on deactivation.
- **Output**: The command outputs the raw HTML, HTTP status code, or the generated URL to the terminal based on the provided flags (`--format`, `--link`).

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

### 1.2.0 - 2025-04-25
- **Added `--link` flag** to output the generated URL (including exclusion parameters) instead of making the HTTP request.

### 1.1.0 - 2025-04-21
- **Added `--without-plugins` flag without value** to exclude all plugins for the request (e.g., `wp render "/" --without-plugins`).
- **Introduced `--format` option** to control output format:
  - `raw` (default): Outputs the full HTML response.
  - `http_code`: Outputs only the HTTP status code (e.g., `200`, `404`).
- **Added request timeout**:
  - Set a 120-second timeout for `wp_remote_get` to handle slow responses.
  - Disabled SSL verification (`sslverify => false`) for flexibility in local or testing environments.

### 1.0.0 - 2025-04-20
- Initial release with `wp render` command and plugin exclusion functionality.
- Added --without-plugins=<plugins> flag for excluding specific plugins via comma-separated slugs.
- Implemented MU-plugin for handling exclusion logic based on exclude_plugins and exclusion_token query parameters.