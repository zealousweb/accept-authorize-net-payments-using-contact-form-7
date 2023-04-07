<?php
/**
 * Plugin Name: Accept Authorize.NET Payments Using Contact Form 7
 * Plugin URL: https://wordpress.org/plugins/accept-authorize-net-payments-using-contact-form-7/
 * Description:  This plugin will integrate Authorize.NET payment gateway for making your payments through Contact Form 7.
 * Version: 1.8
 * Author: ZealousWeb
 * Author URI: https://www.zealousweb.com
 * Developer: The Zealousweb Team
 * Developer E-Mail: opensource@zealousweb.com
 * Text Domain: accept-authorize.net-payments-using-contact-form-7
 * Domain Path: /languages
 *
 * Copyright: © 2009-2021 ZealousWeb Technologies.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Basic plugin definitions
 *
 * @package Accept Authorize.NET Payments Using Contact Form 7
 * @since 1.2
 */

if ( !defined( 'CF7ADN_VERSION' ) ) {
	define( 'CF7ADN_VERSION', '1.8' ); // Version of plugin
}

if ( !defined( 'CF7ADN_FILE' ) ) {
	define( 'CF7ADN_FILE', __FILE__ ); // Plugin File
}

if ( !defined( 'CF7ADN_DIR' ) ) {
	define( 'CF7ADN_DIR', dirname( __FILE__ ) ); // Plugin dir
}

if ( !defined( 'CF7ADN_URL' ) ) {
	define( 'CF7ADN_URL', plugin_dir_url( __FILE__ ) ); // Plugin url
}

if ( !defined( 'CF7ADN_PLUGIN_BASENAME' ) ) {
	define( 'CF7ADN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // Plugin base name
}

if ( !defined( 'CF7ADN_META_PREFIX' ) ) {
	define( 'CF7ADN_META_PREFIX', 'cf7adn_' ); // Plugin metabox prefix
}

if ( !defined( 'CF7ADN_PREFIX' ) ) {
	define( 'CF7ADN_PREFIX', 'cf7adn' ); // Plugin prefix
}

if ( !defined( 'CFADZW_PRODUCT' ) ) {
	define( 'CFADZW_PRODUCT', 'https://www.zealousweb.com/store/accept-authorize-net-payments-using-contact-form-7' ); // Plugin Document Link
}

/**
 * Initialize the main class
 */
if ( !function_exists( 'CF7ADN' ) ) {

	if ( is_admin() ) {
		require_once( CF7ADN_DIR . '/inc/admin/class.' . CF7ADN_PREFIX . '.admin.php' );
		require_once( CF7ADN_DIR . '/inc/admin/class.' . CF7ADN_PREFIX . '.admin.action.php' );
		require_once( CF7ADN_DIR . '/inc/admin/class.' . CF7ADN_PREFIX . '.admin.filter.php' );
	} else {
		require_once( CF7ADN_DIR . '/inc/front/class.' . CF7ADN_PREFIX . '.front.php' );
		require_once( CF7ADN_DIR . '/inc/front/class.' . CF7ADN_PREFIX . '.front.action.php' );
		require_once( CF7ADN_DIR . '/inc/front/class.' . CF7ADN_PREFIX . '.front.filter.php' );
	}

	require_once( CF7ADN_DIR . '/inc/lib/class.' . CF7ADN_PREFIX . '.lib.php' );

	//Initialize all the things.
	require_once( CF7ADN_DIR . '/inc/class.' . CF7ADN_PREFIX . '.php' );
}
