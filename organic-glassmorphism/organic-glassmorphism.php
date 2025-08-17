<?php
/**
 * Plugin Name:       Organic Glassmorphism
 * Plugin URI:        https://example.com/
 * Description:       Applies an organic glassmorphism design system across a WordPress site.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Jules
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       organic-glassmorphism
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define core plugin constants.
define( 'ORGANIC_GLASSMORPHISM_VERSION', '1.0.0' );
define( 'ORGANIC_GLASSMORPHISM_PATH', plugin_dir_path( __FILE__ ) );
define( 'ORGANIC_GLASSMORPHISM_URL', plugin_dir_url( __FILE__ ) );

// Include the main plugin class.
require_once ORGANIC_GLASSMORPHISM_PATH . 'includes/class-og-plugin.php';

/**
 * The main function for running the plugin.
 *
 * @since 1.0.0
 */
function organic_glassmorphism_run() {
	return OG_Plugin::instance();
}
$og_plugin_instance = organic_glassmorphism_run();

// Register the deactivation hook to clean up when the plugin is deactivated.
register_deactivation_hook( __FILE__, array( $og_plugin_instance, 'on_deactivation' ) );
