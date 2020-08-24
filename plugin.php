<?php
/**
 * Plugin Name: Starter Content Export CLI
 * Plugin URI: https://profiles.wordpress.org/gutengeek/
 * Description: Starter Content export CLI for WordPress developer
 * Author: GutenGeek
 * Author URI: https://gutengeek.com/
 * Version: 0.0.1
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package gutengeek
 * @since 0.0.1
 */

defined( 'ABSPATH' ) || exit();

define( 'SCEC_DEV_FILE', __FILE__ );
define( 'SCEC_DEV_BASENAME', plugin_basename( __FILE__ ) );
define( 'SCEC_DEV_PATH', plugin_dir_path( __FILE__ ) );
define( 'SCEC_DEV_URI', plugins_url( '/', SCEC_DEV_FILE ) );
define( 'SCEC_DEV_VERSION', '0.0.1' );

if ( ! class_exists( 'SC_Develop' ) ) {
	require_once SCEC_DEV_PATH . '/inc/class-sc-develop.php';
}

if ( ! function_exists( 'sc_develop' ) ) {
	function sc_develop() {
		return SC_Develop::instance();
	}
}

$GLOBALS['sc_develop'] = sc_develop();