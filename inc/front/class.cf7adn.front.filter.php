<?php
/**
 * CF7ADN_Front_Filter Class
 *
 * Handles the Frontend Filters.
 *
 * @package WordPress
 * @subpackage Accept Authorize.NET Payments Using Contact Form 7
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7ADN_Front_Filter' ) ) {

	/**
	 *  The CF7ADN_Front_Filter Class
	 */
	class CF7ADN_Front_Filter {

		function __construct() {

			/**
			 * Wrap form
			 */
			add_filter( 'wpcf7_form_elements', array( $this, 'filter__wpcf7_form_elements' ), 10 );

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

		function filter__wpcf7_form_elements( $code ) {

			/* If the form has multistep's shortcode */
			if ( strpos( $code, '<fieldset class="fieldset-cf7adn' ) ) {

				if ( defined( 'WPCF7_AUTOP ') && ( WPCF7_AUTOP == true ) ) {
					$code = preg_replace('#<p>(.*?)<\/fieldset><fieldset class=\"fieldset-cf7adn\"><\/p>#', '$1</fieldset><fieldset class="fieldset-cf7adn">', $code);
				}

				$code = '<fieldset class="fieldset-cf7adn">' . $code;

				$code .= '</fieldset>';
			}

			return $code;
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
