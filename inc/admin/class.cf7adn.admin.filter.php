<?php
/**
 * CF7ADN_Admin_Filter Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @subpackage Accept Authorize.NET Payments Using Contact Form 7
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7ADN_Admin_Filter' ) ) {

	/**
	 *  The CF7ADN_Admin_Filter Class
	 */
	class CF7ADN_Admin_Filter {

		function __construct() {

			// Adding Authorize.Net setting tab
			add_filter( 'wpcf7_editor_panels', array( $this, 'filter__wpcf7_editor_panels' ), 10, 3 );
			add_filter( 'post_row_actions',    array( $this, 'filter__post_row_actions' ), 10, 3 );
			add_filter( 'plugin_action_links',array( $this,'filter__admin_plugin_links'), 10, 2 ); 

			add_filter( 'manage_edit-cf7adn_data_sortable_columns', array( $this, 'filter__manage_cf7adn_data_sortable_columns' ), 10, 3 );
			add_filter( 'manage_cf7adn_data_posts_columns',         array( $this, 'filter__manage_cf7adn_data_posts_columns' ), 10, 3 );
			add_filter( 'bulk_actions-edit-cf7adn_data',            array( $this, 'filter__bulk_actions_edit_cf7adn_data' ) );


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
		/**
		 * Authorize.Net tab
		 * Adding tab in contact form 7
		 *
		 * @param $panels
		 *
		 * @return array
		 */
		public function filter__wpcf7_editor_panels( $panels ) {

			$panels[ 'authorize-net-add-on' ] = array(
				'title'    => __( 'Authorize.Net', 'accept-authorize-net-payments-using-contact-form-7' ),
				'callback' => array( $this, 'wpcf7_admin_after_additional_settings' )
			);

			return $panels;
		}

		/**
		 * Filter: post_row_actions
		 *
		 * - Used to modify the post list action buttons.
		 *
		 * @method filter__post_row_actions
		 *
		 * @param  array $actions
		 *
		 * @return array
		 */
		function filter__post_row_actions( $actions ) {

			if ( get_post_type() === 'cf7adn_data' ) {
				unset( $actions['view'] );
				unset( $actions['inline hide-if-no-js'] );
			}

			return $actions;
		}

		/**
		 * Filter: manage_edit-cf7adn_data_sortable_columns
		 *
		 * - Used to add the sortable fields into "cf7adn_data" CPT
		 *
		 * @method filter__manage_cf7adn_data_sortable_columns
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		function filter__manage_cf7adn_data_sortable_columns( $columns ) {
			$columns['form_id'] = '_form_id';
			$columns['transaction_status'] = '_transaction_status';
			$columns['total'] = '_total';
			return $columns;
		}

		/**
		 * Filter: manage_cf7adn_data_posts_columns
		 *
		 * - Used to add new column fields for the "cf7adn_data" CPT
		 *
		 * @method filter__manage_cf7adn_data_posts_columns
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		function filter__manage_cf7adn_data_posts_columns( $columns ) {
			unset( $columns['date'] );
			$columns['form_id'] = __( 'Form ID', 'accept-authorize-net-payments-using-contact-form-7' );
			$columns['transaction_status'] = __( 'Transaction Status', 'accept-authorize-net-payments-using-contact-form-7' );
			$columns['total'] = __( 'Total Amount', 'accept-authorize-net-payments-using-contact-form-7' );
			$columns['date'] = __( 'Submitted Date', 'accept-authorize-net-payments-using-contact-form-7' );
			return $columns;
		}

		/**
		 * Filter: bulk_actions-edit-cf7adn_data
		 *
		 * - Add/Remove bulk actions for "cf7adn_data" CPT
		 *
		 * @method filter__bulk_actions_edit_cf7adn_data
		 *
		 * @param  array $actions
		 *
		 * @return array
		 */
		function filter__bulk_actions_edit_cf7adn_data( $actions ) {
			unset( $actions['edit'] );
			return $actions;
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
		/**
		 * Adding Authorize.Net fields in Authorize.Net tab
		 *
		 * @param $cf7
		 */
		public function wpcf7_admin_after_additional_settings( $cf7 ) {

			wp_enqueue_script( CF7ADN_PREFIX . '_admin_js' );

			require_once( CF7ADN_DIR . '/inc/admin/template/' . CF7ADN_PREFIX . '.template.php' );

		}

		 /**
        * add documentation link in plugins
        */

        function filter__admin_plugin_links( $links, $file ) {
            if ( $file != CF7ADN_PLUGIN_BASENAME ) {
                return $links;
            }
        
            if ( ! current_user_can( 'wpcf7_read_contact_forms' ) ) {
                return $links;
            }
            
            $documentLink = '<a target="_blank" href="https://store.zealousweb.com/wordpress-plugins/accept-authorize-net-payments-using-contact-form-7">' . __( 'Document Link', 'accept-authorize-net-payments-using-contact-form-7' ) . '</a>';
            array_unshift( $links , $documentLink);
        
            return $links;
        }

	}
}
