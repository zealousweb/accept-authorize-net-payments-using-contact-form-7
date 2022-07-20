<?php
/**
 * CF7ADN_Admin Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @subpackage Accept Authorize.NET Payments Using Contact Form 7
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7ADN_Admin' ) ) {

	/**
	 * The CF7ADN_Admin Class
	 */
	class CF7ADN_Admin {

		var $action = null,
		    $filter = null;

		function __construct() {
			add_action( 'admin_menu',    array( $this, 'action__cf7adn_admin_menu' ) );

		}

		/*
		   ###     ######  ######## ####  #######  ##    ##  ######
		  ## ##   ##    ##    ##     ##  ##     ## ###   ## ##    ##
		 ##   ##  ##          ##     ##  ##     ## ####  ## ##
		##     ## ##          ##     ##  ##     ## ## ## ##  ######
		######### ##          ##     ##  ##     ## ##  ####       ##
		##     ## ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##     ##  ######     ##    ####  #######  ##    ##  ######
		*/

		/**
		 * Action: admin_menu
		 *
		 * - Used for removing the "submitdiv" metabox "cf7adn_data" CPT
		 *
		 * @method action__cf7adn_admin_menu
		 */
		function action__cf7adn_admin_menu() {
			remove_meta_box( 'submitdiv', 'cf7adn_data', 'side' );
		}


		/*
		######## #### ##       ######## ######## ########   ######
		##        ##  ##          ##    ##       ##     ## ##    ##
		##        ##  ##          ##    ##       ##     ## ##
		######    ##  ##          ##    ######   ########   ######
		##        ##  ##          ##    ##       ##   ##         ##
		##        ##  ##          ##    ##       ##    ##  ##    ##
		##       #### ########    ##    ######## ##     ##  ######
		*/


		/*
		######## ##     ## ##    ##  ######  ######## ####  #######  ##    ##  ######
		##       ##     ## ###   ## ##    ##    ##     ##  ##     ## ###   ## ##    ##
		##       ##     ## ####  ## ##          ##     ##  ##     ## ####  ## ##
		######   ##     ## ## ## ## ##          ##     ##  ##     ## ## ## ##  ######
		##       ##     ## ##  #### ##          ##     ##  ##     ## ##  ####       ##
		##       ##     ## ##   ### ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##        #######  ##    ##  ######     ##    ####  #######  ##    ##  ######
		*/


	}

}
