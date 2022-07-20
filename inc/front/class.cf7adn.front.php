<?php
/**
 * CF7ADN_Front Class
 *
 * Handles the Frontend functionality.
 *
 * @package WordPress
 * @subpackage Accept Authorize.NET Payments Using Contact Form 7
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7ADN_Front' ) ) {

	/**
	 * The CF7ADN_Front Class
	 */
	class CF7ADN_Front {

		/**
		 * @var string Base URL endpoint for pages.
		 */
		const BASE_ENDPOINT = 'cf7adn-phpinfo';

		var $action = null,
		    $filter = null;

		function __construct() {
			add_filter( 'query_vars',                array( $this, 'filter__query_vars' ) );
			add_filter( 'template_include',          array( $this, 'filter__template_include' ) );

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


		/*
		######## #### ##       ######## ######## ########   ######
		##        ##  ##          ##    ##       ##     ## ##    ##
		##        ##  ##          ##    ##       ##     ## ##
		######    ##  ##          ##    ######   ########   ######
		##        ##  ##          ##    ##       ##   ##         ##
		##        ##  ##          ##    ##       ##    ##  ##    ##
		##       #### ########    ##    ######## ##     ##  ######
		*/
		/**
		 * Filter: query_vars
		 *
		 * - added query variable for custom endpoint.
		 *
		 * @param array $vars
		 *
		 * @return array
		 */
		function filter__query_vars( $vars ) {
			$vars[] = $this::BASE_ENDPOINT;
			return $vars;
		}

		/**
		 * Filter: template_include
		 *
		 * - change template call for the server configuration
		 *
		 * @param string $template
		 * @return string
		 */
		function filter__template_include( $template ) {
			global $wp_query;

			if ( !isset( $wp_query->query_vars[$this::BASE_ENDPOINT] ) )
				return $template;


			return CF7ADN_DIR . '/inc/front/template/cf7adn-info.php';

			return $template;
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
