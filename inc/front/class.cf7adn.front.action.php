<?php
/**
 * CF7ADN_Front_Action Class
 *
 * Handles the Frontend Actions.
 *
 * @package WordPress
 * @subpackage Accept Authorize.NET Payments Using Contact Form 7
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7ADN_Front_Action' ) ){

	/**
	 *  The CF7ADN_Front_Action Class
	 */
	class CF7ADN_Front_Action {

		function __construct()  {

			add_action( 'wp_enqueue_scripts', array( $this, 'action__wp_enqueue_scripts' ) );

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

		function action__wp_enqueue_scripts() {
			 wp_enqueue_script( CF7ADN_PREFIX . '_front_js', CF7ADN_URL . 'assets/js/front.min.js', array( 'jquery-core' ), CF7ADN_VERSION, true );
			wp_localize_script( CF7ADN_PREFIX . '_front_js', 'cf7adn_object',
				array(
					'ajax_url' => admin_url('admin-ajax.php'),
				)
			);

			wp_enqueue_style( CF7ADN_PREFIX . '_front_css', CF7ADN_URL . 'assets/css/front-style.min.css', array(), CF7ADN_VERSION );
		}



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
