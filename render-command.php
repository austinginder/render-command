<?php
/*
 * Plugin Name:       Render Command
 * Plugin URI:        https://github.com/austinginder/render-command
 * Description:       WP-CLI command to render HTML for a WordPress URL path. Use --without-plugins flag to exclude plugins from the request.
 * Version:           1.0.0
 * Author:            Austin Ginder
 * Author URI:        https://austinginder.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       render-command
 *
 * Usage: wp render <url> [--without-plugins=<plugins>] [--format=<format>]
 * Example: wp render "/" --without-plugins="jetpack,wordpress-seo"
 * Example: wp render "/contact" --format=http_code
 */

namespace WP_CLI\RenderCommand;
use WP_CLI;
use WP_CLI\Utils;
use function register_activation_hook;
use function register_deactivation_hook;

if ( ! defined( 'RENDER_COMMAND_MU_PLUGIN_PATH' ) ) {
    define( 'RENDER_COMMAND_MU_PLUGIN_PATH', WPMU_PLUGIN_DIR . '/render-command.php' );
}

// Hook into plugin activation/deactivation within the namespace
register_activation_hook( __FILE__, __NAMESPACE__ . '\activate' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deactivate' );

/**
 * Generate a token for plugin exclusion based on AUTH_SALT.
 *
 * @return string
 */
function generate_exclusion_token() {
    $salt = defined( 'AUTH_SALT' ) && AUTH_SALT ? AUTH_SALT : 'render-command-fallback-salt';
    return hash( 'sha256', $salt . '::render-command-exclusion' );
}

/**
 * Function to run on plugin activation.
 * Creates the mu-plugin file.
 */
function activate() {
    // Ensure the mu-plugins directory exists
    if ( ! \file_exists( WPMU_PLUGIN_DIR ) ) {
        \wp_mkdir_p( WPMU_PLUGIN_DIR );
    }

    // The content for the mu-plugin file
    $mu_plugin_content = <<<'PHP'
<?php

add_filter( 'option_active_plugins', 'handle_render_command_plugin_exclusion_logic', 10, 1 );
function handle_render_command_plugin_exclusion_logic( $plugins ) {
    // Only apply this logic on the frontend
    if ( is_admin() ) {
        return $plugins;
    }

    // Check for exclude_plugins and exclusion_token
    if ( ! isset( $_GET['exclude_plugins'] ) || ! isset( $_GET['exclusion_token'] ) ) {
        return $plugins;
    }

    // Validate the token
    $provided_token = sanitize_text_field( wp_unslash( $_GET['exclusion_token'] ) );
    $expected_token = generate_exclusion_token();
    if ( ! hash_equals( $expected_token, $provided_token ) ) {
        error_log( 'Invalid exclusion token provided in request.' );
        return $plugins;
    }

    $plugins_to_exclude_slugs = explode( ',', sanitize_text_field( wp_unslash( $_GET['exclude_plugins'] ) ) );

    if ( empty( $plugins_to_exclude_slugs ) ) {
        return $plugins;
    }

    $updated_plugins = [];

    // Iterate through active plugins and exclude based on partial match
    foreach ( $plugins as $plugin_path ) {
        $exclude = false;
        foreach ( $plugins_to_exclude_slugs as $slug ) {
            // Check if the plugin path contains the slug
            if ( strpos( $plugin_path, $slug . '/' ) === 0 || strpos( $plugin_path, '/' . $slug . '/' ) !== false ) {
                $exclude = true;
                break; // No need to check other slugs for this plugin
            }
        }

        // If the plugin should not be excluded, add it to the updated list
        if ( ! $exclude ) {
            $updated_plugins[] = $plugin_path;
        }
    }

    return $updated_plugins;
}

function generate_exclusion_token() {
    $salt = defined( 'AUTH_SALT' ) && AUTH_SALT ? AUTH_SALT : 'render-command-fallback-salt';
    return hash( 'sha256', $salt . '::render-command-exclusion' );
}
PHP;

    // Write the content to the mu-plugin file
    \file_put_contents( RENDER_COMMAND_MU_PLUGIN_PATH, $mu_plugin_content );
}

/**
 * Function to run on plugin deactivation.
 * Removes the mu-plugin file.
 */
function deactivate() {
    // Check if the mu-plugin file exists before attempting to delete
    if ( \file_exists( RENDER_COMMAND_MU_PLUGIN_PATH ) ) {
        \unlink( RENDER_COMMAND_MU_PLUGIN_PATH );
    }
}

class RenderCommand {

    /**
     * Render a WordPress page and output the result based on format.
     *
     * ## OPTIONS
     *
     * <path>
     * : The URL path to render (e.g., "/about-us/").
     *
     * [--without-plugins=<plugins>]
     * : A comma-separated list of plugins to exclude for the request
     *   (e.g., "jetpack,wordpress-seo"). Requires a valid AUTH_SALT.
     *
     * [--format=<format>]
     * : Determine the output format.
     *   ---
     *   default: raw
     *   options:
     *     - raw
     *     - http_code
     *   ---
     *
     * ## EXAMPLES
     *
     *     # Get raw HTML for the homepage
     *     wp render "/"
     *
     *     # Get raw HTML for /about-us/ excluding jetpack and wordpress-seo
     *     wp render "/about-us/" --without-plugins="jetpack,wordpress-seo"
     *
     *     # Get only the HTTP status code for /contact
     *     wp render "/contact" --format=http_code
     *
     * @param array $args      Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function __invoke( array $args, array $assoc_args ) {
        if ( empty( $args[0] ) ) {
            WP_CLI::error( 'Please provide a path to render.' );
        }

        $path = $args[0];
        $plugins_to_exclude = [];
        $expected_token = null;

        // Check for --without-plugins
        if ( isset( $assoc_args['without-plugins'] ) ) {
            $expected_token = generate_exclusion_token();
            $plugins_to_exclude = explode( ',', $assoc_args['without-plugins'] );
        }

        // Get the format, default to 'raw'
        $format = Utils\get_flag_value( $assoc_args, 'format', 'raw' );

        // Validate format
        if ( ! in_array( $format, [ 'raw', 'http_code' ] ) ) {
            WP_CLI::error( "Invalid format specified. Available formats: 'raw', 'http_code'." );
        }

        // Build the full URL with query parameters if needed
        $url = site_url( $path );
        if ( ! empty( $plugins_to_exclude ) && $expected_token ) {
            $query_param = http_build_query( [
                'exclude_plugins' => implode( ',', $plugins_to_exclude ),
                'exclusion_token' => $expected_token,
            ] );
            // Ensure proper URL construction whether path has query string or not
            $url .= ( parse_url( $url, PHP_URL_QUERY ) ? '&' : '?' ) . $query_param;
            WP_CLI::log( 'Requesting URL with plugin exclusion: ' . $url );
        } else {
             WP_CLI::log( 'Requesting URL: ' . $url );
        }

        // Make the request
        $response = wp_remote_get( $url, [ 'timeout' => 120 ] );

        if ( is_wp_error( $response ) ) {
            WP_CLI::error( $response->get_error_message() );
        }

        // Output based on the requested format
        if ( 'http_code' === $format ) {
            $http_code = wp_remote_retrieve_response_code( $response );
            if ( $http_code ) {
                WP_CLI::line( (string) $http_code ); // Cast to string for output
            } else {
                // This case is unlikely if no WP_Error occurred, but good practice
                WP_CLI::warning( 'Could not retrieve HTTP status code, although the request seemed successful.' );
            }
        } else { // Default to 'raw' format
            $body = wp_remote_retrieve_body( $response );
            WP_CLI::line( $body );
        }
    }

}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'render', new RenderCommand() );
}
