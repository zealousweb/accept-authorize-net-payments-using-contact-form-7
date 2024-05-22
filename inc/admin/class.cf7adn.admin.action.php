<?php
/**
 * CF7ADN_Admin_Action Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @subpackage Accept Authorize.NET Payments Using Contact Form 7
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7ADN_Admin_Action' ) ){

	/**
	 *  The CF7ADN_Admin_Action Class
	 */
	class CF7ADN_Admin_Action {

		function __construct()  {

			add_action( 'init',           array( $this, 'action__init' ) );
			add_action( 'init',           array( $this, 'action__init_99' ), 99 );
			add_action( 'add_meta_boxes', array( $this, 'action__add_meta_boxes' ) );
			add_action( 'setup_theme', array( $this, 'action__setup_theme' ) );
			// Create import functionality page
			add_action( 'admin_menu', array( $this,'action__admin_menu' ) );
			add_action( 'admin_init', array( $this,'action__admin_init' ) );

			// Save settings of contact form 7 admin
			add_action( 'wpcf7_save_contact_form',                array( $this, 'action__wpcf7_save_contact_form' ), 20, 2 );

			add_action( 'manage_cf7adn_data_posts_custom_column', array( $this, 'action__manage_cf7adn_data_posts_custom_column' ), 10, 2 );

			add_action( 'pre_get_posts',         array( $this, 'action__pre_get_posts' ) );
			add_action( 'restrict_manage_posts', array( $this, 'action__restrict_manage_posts' ) );
			add_action( 'parse_query',           array( $this, 'action__parse_query' ) );

			add_action( CF7ADN_PREFIX . '/postbox', array( $this, 'action__acf7adn_postbox' ) );



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
		 * Action: init
		 *
		 * - Register neccessary assets for backend.
		 *
		 * @method action__init
		 */
		function action__init() {
			 wp_register_style( CF7ADN_PREFIX . '_admin_css', CF7ADN_URL . 'assets/css/admin.min.css', array(), CF7ADN_VERSION );
			wp_register_script( CF7ADN_PREFIX . '_admin_js', CF7ADN_URL . 'assets/js/admin.min.js', array( 'jquery-core' ), CF7ADN_VERSION );

			 wp_register_style( 'select2', CF7ADN_URL . 'assets/css/select2.min.css', array(), '4.0.7' );
			wp_register_script( 'select2', CF7ADN_URL . 'assets/js/select2.min.js', array( 'jquery-core' ), '4.0.7' );
		}

		/**
		 * Action: init 99
		 *
		 * - Used to perform the CSV export functionality.
		 *
		 */
		function action__init_99() {
			if (
				   isset( $_REQUEST['export_csv'] )
				&& isset( $_REQUEST['form-id'] )
				&& !empty( $_REQUEST['form-id'] )
			) {
				$form_id = sanitize_text_field($_REQUEST['form-id']);

				$exceed_ct = sanitize_text_field( substr( get_option( '_exceed_cfauzw_l' ), 6 ) );

				if ( 'all' == $form_id ) {
					add_action( 'admin_notices', array( $this, 'action__admin_notices_export' ) );
					return;
				}

				$args = array(
					'post_type' => 'cf7adn_data',
					'posts_per_page' => ($exceed_ct)?:(-1),
				);

				$exported_data = get_posts( $args );

				if ( empty( $exported_data ) )
					return;

				/** CSV Export **/
				$filename = 'cf7adn-' . $form_id . '-' . time() . '.csv';

				$header_row = array(
					'_form_id'            => 'Form ID/Name',
					'_email'              => 'Email Address',
					'_transaction_id'     => 'Transaction ID',
					'_invoice_no'         => 'Invoice ID',
					'_amount'             => 'Amount',
					'_quantity'           => 'Quantity',
					'_total'              => 'Total',
					'_currency'           => 'Currency code',
					'_submit_time'        => 'Submit Time',
					'_request_Ip'         => 'Request IP',
					'_transaction_status' => 'Transaction status'
				);

				$data_rows = array();

				if ( !empty( $exported_data ) ) {
					foreach ( $exported_data as $entry ) {

						$row = array();

						if ( !empty( $header_row ) ) {
							foreach ( $header_row as $key => $value ) {

								if (
									   $key != '_transaction_status'
									&& $key != '_submit_time'
								) {

									$row[$key] = __(
										(
											(
												'_form_id' == $key
												&& !empty( get_the_title( get_post_meta( $entry->ID, $key, true ) ) )
											)
											? get_the_title( get_post_meta( $entry->ID, $key, true ) )
											: get_post_meta( $entry->ID, $key, true )
										)
									);

								} else if ( $key == '_transaction_status' ) {

									$row[$key] = __(
										(
											(
												!empty( CF7ADN()->lib->response_status )
												&& array_key_exists( get_post_meta( $entry->ID , $key, true ), CF7ADN()->lib->response_status )
											)
											? CF7ADN()->lib->response_status[get_post_meta( $entry->ID , $key, true )]
											: get_post_meta( $entry->ID , $key, true )
										)
									);

								} else if ( '_submit_time' == $key ) {
									$row[$key] = __( get_the_date( 'd, M Y H:i:s', $entry->ID ) );
								}
							}
						}

						/* form_data */
						$data = get_post_meta( $entry->ID, '_form_data', true );
						$hide_data = apply_filters( CF7ADN_PREFIX . '/hide-display', array( '_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag', '_wpcf7_container_post' ) );
						foreach ( $hide_data as $key => $value ) {
							if ( array_key_exists( $value, $data ) ) {
								unset( $data[$value] );
							}
						}

						if ( !empty( $data ) ) {
							foreach ( $data as $key => $value ) {
								if ( strpos( $key, 'authorize-' ) === false ) {

									if ( !in_array( $key, $header_row ) ) {
										$header_row[$key] = $key;
									}

									$row[$key] = ( is_array( $value ) ? implode( ', ', $value ) : __( $value ) );

								}
							}
						}

						$data_rows[] = $row;

					}
				}

				ob_start();

				$fh = @fopen( 'php://output', 'w' );
				fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
				header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
				header( 'Content-Description: File Transfer' );
				header( 'Content-type: text/csv' );
				header( "Content-Disposition: attachment; filename={$filename}" );
				header( 'Expires: 0' );
				header( 'Pragma: public' );
				fputcsv( $fh, $header_row );
				foreach ( $data_rows as $data_row ) {
					fputcsv( $fh, $data_row );
				}
				fclose( $fh );

				ob_end_flush();
				die();

			}
		}

		/**
		* Action: admin_menu
		*
		* - Add stripe import data menu
		*/
		function action__admin_menu() {
			add_submenu_page(
				'wpcf7',
				'Import Authorize.net Data',
				'Import Authorize.net Data',
				'manage_options',
				'cfadn-import',
				array( $this, 'cfadn_import_submenu_page_callback')
			);
		}

		/**
		* Action: admin_init
		*
		* - Import csv logic
		*/
		function action__admin_init(){
			// checking on form submit
			if( array_key_exists('cfadnimport-plugin-submit', $_REQUEST ) && sanitize_text_field($_REQUEST['cfadnimport-plugin-submit']) != '' ) {
				$error = array();
				// checking the nonce first
				if (isset($_REQUEST['_wpnonce_cfadn']) && sanitize_text_field($_REQUEST['_wpnonce_cfadn']) != '') {
					if( ! wp_verify_nonce( sanitize_text_field($_REQUEST['_wpnonce_cfadn']), 'cfadn_import' ) ){
						add_action( 'admin_notices', array( $this, 'action__admin_notices_import_nonce_issue' ) );
						return;
					}
				}

				if (isset($_FILES['cfadn_importcsv']['type']) && !empty($_FILES['cfadn_importcsv']['type']) &&
					isset($_FILES['cfadn_importcsv']['name']) && !empty($_FILES['cfadn_importcsv']['name']) &&
					isset($_FILES['cfadn_importcsv']['tmp_name']) && !empty($_FILES['cfadn_importcsv']['tmp_name'])) {
					$fileName = $_FILES['cfadn_importcsv']['name']; 
					$fileArray = explode('.', $fileName);
					$fileExtension = end($fileArray);
					$ext = strtolower($fileExtension);
					$type = $_FILES['cfadn_importcsv']['type']; 
					$tmpName = $_FILES['cfadn_importcsv']['tmp_name']; 

					// check the file is a csv
					if( $ext === 'csv' ){
						if(($handle = fopen($tmpName, 'r')) !== FALSE) {
							// necessary if a large csv file
							set_time_limit(0);

							$row  = 0;
							$flag = true;

							$col_count =  count(file($tmpName, FILE_SKIP_EMPTY_LINES));

							while( ($data = fgetcsv( $handle, 10000, ',') ) !== FALSE ) {

								// Check data is blank or not
								if ( !empty( $data ) ) {

									// Skipped the first record
									if( $row == 0 && $data[2] != "Transaction ID") {
										// File Format is not belongs to our format
										add_action( 'admin_notices', array( $this, 'action__admin_notices_import_file_format' ) );
									} else {
										if( $flag === true ) {

                                            $form_name = 'Imported Data Form';                                         
											if (isset($_REQUEST['formname']) && !empty($_REQUEST['formname'])) {
												$form_name = sanitize_text_field($_REQUEST['formname']);
											
											}	
											$sa_import_contactform_id = wp_insert_post( array (
												'post_type' => 'wpcf7_contact_form',
												'post_title' => $form_name, // email/invoice_no
												'post_status' => 'publish',
												'comment_status' => 'closed',
												'ping_status' => 'closed',
											) );
                                            add_post_meta($sa_import_contactform_id,'cf7adn_use_stripe','1',true);
											$flag = false;
										}

										if( $row > 0 ) {
											//Finally inserting the data
											$form_name      = $data[0];
											$email          = $data[1];
											$txn_id         = $data[2];
											$invoice_no     = $data[3];
											$amount_val     = $data[4];
											$quanity_val    = $data[5];
											$paidAmount     = $data[6];
											$paidCurrency   = $data[7];
											$submitTime     = $data[8];
											$ip_address     = $data[9];
											$payment_status = $data[10];
											$stored_data    = $data[11];
											$charge         = $data[12];
											$attachent      = $data[13];

											try {
												//Finally inserting the data
												$sa_import_post_id = wp_insert_post( array (
													'post_type' => 'cf7adn_data',
													'post_title' => ( !empty( $email ) ? $email : $invoice_no ), // email/invoice_no
													'post_status' => 'publish',
													'comment_status' => 'closed',
													'ping_status' => 'closed',
												) );

												if ( !empty( $sa_import_post_id ) ) {

													add_post_meta( $sa_import_post_id, '_form_name', sanitize_text_field($form_name) );
													add_post_meta( $sa_import_post_id, '_form_id', sanitize_text_field($sa_import_contactform_id) );
													add_post_meta( $sa_import_post_id, '_email', sanitize_email($email) );
													add_post_meta( $sa_import_post_id, '_transaction_id', sanitize_text_field($txn_id) );
													add_post_meta( $sa_import_post_id, '_invoice_no', sanitize_text_field($invoice_no) );
													add_post_meta( $sa_import_post_id, '_amount', sanitize_text_field($amount_val) );
													add_post_meta( $sa_import_post_id, '_quantity', sanitize_text_field($quanity_val) );
													add_post_meta( $sa_import_post_id, '_total', sanitize_text_field($paidAmount) );
													add_post_meta( $sa_import_post_id, '_request_Ip', sanitize_text_field($ip_address) );
													add_post_meta( $sa_import_post_id, '_currency', sanitize_text_field($paidCurrency) );
													add_post_meta( $sa_import_post_id, '_form_data',  (array)$stored_data );
													add_post_meta( $sa_import_post_id, '_transaction_response', (array)$charge );
													add_post_meta( $sa_import_post_id, '_transaction_status', sanitize_text_field($payment_status) );
													add_post_meta( $sa_import_post_id, '_attachment', sanitize_text_field($attachent) );

												}

											} catch( Exception $e ) {
												// Handele the exception and store for the support team
												$errorArray = array();
												$errorArray['row'] = $row;
												$errorArray['message'] = $e->getMessage();
												update_option('import_error', $errorArray);
												add_action( 'admin_notices', array( $this, 'action__admin_notices_import_fail' ) );
												break;
											}
										}
									}
									// increament the row
									$row++;

								}
							}

							if( $row ==  $col_count){
								//Import success message
								add_action( 'admin_notices', array( $this, 'action__admin_notices_import_done' ) );
							}

							// File Close
							fclose($handle);
						}
					} else {
						// File type error
						add_action( 'admin_notices', array( $this, 'action__admin_notices_import_file_type' ) );
					}

				} else {
					// File type error
					add_action( 'admin_notices', array( $this, 'action__admin_notices_import_file_type' ) );
				}
			}

		}


		/**
		 * Action: add_meta_boxes
		 *
		 * - Add mes boxes for the CPT "cf7adn_data"
		 */
		function action__add_meta_boxes() {
			add_meta_box( 'cfadn-data', esc_html__( 'From Data', 'contact-form-7-authorize-net-addon' ), array( $this, 'cfadn_show_from_data' ), 'cf7adn_data', 'normal', 'high' );
			add_meta_box( 'cfadn-help', esc_html__( 'Do you need help for configuration?', 'contact-form-7-authorize-net-addon' ), array( $this, 'cfadn_show_help_data' ), 'cf7adn_data', 'side', 'high' );
		}

		/**
		 * Action: wpcf7_save_contact_form
		 *
		 * - Save setting fields data.
		 *
		 * @param object $WPCF7_form
		 */
		public function action__wpcf7_save_contact_form( $WPCF7_form ) {

			$wpcf7 = WPCF7_ContactForm::get_current();

			if ( !empty( $wpcf7 ) ) {
				$post_id = $wpcf7->id;
			}

			$form_fields = array(
				CF7ADN_META_PREFIX . 'use_authorize',
				CF7ADN_META_PREFIX . 'mode_sandbox',
				CF7ADN_META_PREFIX . 'debug',
				CF7ADN_META_PREFIX . 'sandbox_login_id',
				CF7ADN_META_PREFIX . 'sandbox_transaction_key',
				CF7ADN_META_PREFIX . 'live_login_id',
				CF7ADN_META_PREFIX . 'live_transaction_key',
				CF7ADN_META_PREFIX . 'amount',
				CF7ADN_META_PREFIX . 'quantity',
				CF7ADN_META_PREFIX . 'email',
				CF7ADN_META_PREFIX . 'description',
				CF7ADN_META_PREFIX . 'currency',
				CF7ADN_META_PREFIX . 'success_returnurl',
				CF7ADN_META_PREFIX . 'cancel_returnurl',

				// Customer Details fields
				CF7ADN_META_PREFIX . 'customer_details',
				CF7ADN_META_PREFIX . 'first_name',
				CF7ADN_META_PREFIX . 'last_name',
				CF7ADN_META_PREFIX . 'company_name',
				CF7ADN_META_PREFIX . 'address',
				CF7ADN_META_PREFIX . 'city',
				CF7ADN_META_PREFIX . 'state',
				CF7ADN_META_PREFIX . 'zip_code',
				CF7ADN_META_PREFIX . 'country',
			);

			/**
			 * Save custom form setting fields
			 *
			 * @var array $form_fields
			 */
			$form_fields = apply_filters( CF7ADN_PREFIX . '/save_fields', $form_fields );

			if(!get_option('_exceed_cfauzw_l')){
				add_option('_exceed_cfauzw_l', 'cfauzw10');
			}

			if ( !empty( $form_fields ) ) {
				foreach ( $form_fields as $key ) {
					if (isset($_REQUEST[$key])) { 
					$keyval = sanitize_text_field( $_REQUEST[ $key ] ); 
					update_post_meta( $post_id, $key, $keyval );
					}
				}
			}		

		}

		/**
		 * Action: manage_cf7adn_data_posts_custom_column
		 *
		 * @method action__manage_cf7adn_data_posts_custom_column
		 *
		 * @param  string  $column
		 * @param  int     $post_id
		 *
		 * @return string
		 */
		function action__manage_cf7adn_data_posts_custom_column( $column, $post_id ) {
			$data_ct = $this->cfauzw_check_data_ct( sanitize_text_field( $post_id ) );
			switch ( $column ) {

				case 'form_id' :
					if( $data_ct ){
						echo "<a href='" . esc_url( CFADZW_PRODUCT ) . "' target='_blank'>To unlock more features consider upgrading to PRO.</a>";
					}else{
						echo (
							!empty( get_post_meta( $post_id , '_form_id', true ) )
							? (
								!empty( get_the_title( get_post_meta( $post_id , '_form_id', true ) ) )
								?esc_html__(get_the_title( get_post_meta( $post_id , '_form_id', true ) ))
								:esc_html__(get_post_meta( $post_id , '_form_id', true ))
							)
							: ''
						);
					}
				break;

				case 'transaction_status' :
					if( $data_ct ){
						echo "<a href='" . esc_url( CFADZW_PRODUCT ) ."' target='_blank'>To unlock more features consider upgrading to PRO.</a>";
					}else{
						echo (
							!empty( get_post_meta( $post_id , '_transaction_status', true ) )
							? (
								(
									!empty( CF7ADN()->lib->response_status )
									&& array_key_exists( get_post_meta( $post_id , '_transaction_status', true ), CF7ADN()->lib->response_status)
								)
								? esc_html__(CF7ADN()->lib->response_status[get_post_meta( $post_id , '_transaction_status', true )])
								:esc_html__(get_post_meta( $post_id , '_transaction_status', true ))
							)
							: ''
						);
					}
				break;

				case 'total' :
					if( $data_ct ){
						echo "<a href='" . esc_url( CFADZW_PRODUCT ) ."' target='_blank'>To unlock more features consider upgrading to PRO.</a>";
					}else{
						echo ( !empty( get_post_meta( $post_id , '_total', true ) ) ? esc_html__(get_post_meta( $post_id , '_total', true )) : '' ) .' ' .
						( !empty( get_post_meta( $post_id , '_currency', true ) ) ? esc_html__(get_post_meta( $post_id , '_currency', true )) : '' );
					}
				break;

			}
		}

		/**
		 * Action: pre_get_posts
		 *
		 * - Used to perform order by into CPT List.
		 *
		 * @method action__pre_get_posts
		 *
		 * @param  object $query WP_Query
		 */
		function action__pre_get_posts( $query ) {

			if (
				! is_admin()
				|| !in_array ( $query->get( 'post_type' ), array( 'cf7adn_data' ) )
			)
				return;


			$orderby = $query->get( 'orderby' );

			if ( '_transaction_status' == $orderby ) {
				$query->set( 'meta_key', '_transaction_status' );
				$query->set( 'orderby', 'meta_value_num' );
			}

			if ( '_form_id' == $orderby ) {
				$query->set( 'meta_key', '_form_id' );
				$query->set( 'orderby', 'meta_value_num' );
			}

			if ( '_total' == $orderby ) {
				$query->set( 'meta_key', '_total' );
				$query->set( 'orderby', 'meta_value_num' );
			}
		}

		/**
		 * Action: restrict_manage_posts
		 *
		 * - Used to creat filter by form and export functionality.
		 *
		 * @method action__restrict_manage_posts
		 *
		 * @param  string $post_type
		 */
		function action__restrict_manage_posts( $post_type ) {

			if ( 'cf7adn_data' != $post_type ) {
				return;
			}

			$posts = get_posts(
				array(
					'post_type'        => 'wpcf7_contact_form',
					'post_status'      => 'publish',
					'suppress_filters' => false,
					'posts_per_page'   => -1
				)
			);

			if ( empty( $posts ) ) {
				return;
			}

			$selected = ( isset( $_GET['form-id'] ) ? sanitize_text_field($_GET['form-id']) : '' );

			echo '<select name="form-id" id="form-id">';
			echo '<option value="all">' . esc_html__( 'Select Form', 'contact-form-7-authorize-net-addon' ) . '</option>';
			foreach ( $posts as $post ) {
				echo '<option value="' . esc_attr( $post->ID ) . '" ' . selected( $selected, $post->ID, false ) . '>' . esc_html__( $post->post_title ) . '</option>';

			}
			echo '</select>';

			echo '<input type="submit" id="export_csv" name="export_csv" class="button action" value="Export CSV">';

		}

		/**
		 * Action: parse_query
		 *
		 * - Filter data by form id.
		 *
		 * @method action__parse_query
		 *
		 * @param  object $query WP_Query
		 */
		function action__parse_query( $query ) {
			if (
				! is_admin()
				|| !in_array ( $query->get( 'post_type' ), array( 'cf7adn_data' ) )
			)
				return;

			if (
				is_admin()
				&& isset( $_GET['form-id'] )
				&& 'all' != $_GET['form-id']
			) {
				$query->query_vars['meta_key']     = '_form_id';
				$query->query_vars['meta_value']   = sanitize_text_field($_GET['form-id']);
				$query->query_vars['meta_compare'] = '=';
			}

		}

		/**
		 * Action: admin_notices
		 *
		 * - Added use notice when trying to export without selecting the form.
		 *
		 * @method action__admin_notices_export
		 */
		function action__admin_notices_export() {
			echo '<div class="error">' .
				'<p>' .
				esc_html__( 'Please select Form to export.', 'contact-form-7-authorize-net-addon' ) .
				'</p>' .
			'</div>';
		}

		/**
		 * Action: CF7ADN_PREFIX /postbox
		 *
		 * - Added metabox for the setting fields in backend.
		 *
		 * @method action__acf7adn_postbox
		 */
		function action__acf7adn_postbox() {
			echo '<div id="configuration-help" class="postbox">' .
			apply_filters(
					CF7ADN_PREFIX . '/help/postbox',
					'<h3>' . esc_html__( 'Do you need help for configuration?', CF7ADN_PREFIX ) . '</h3>' .
					'<p></p>' .
					'<ol>' .
						'<li><a href="https://www.zealousweb.com/wordpress-plugins/product/accept-authorize-net-payments-using-contact-form-7/" target="_blank">Refer the document.</a></li>' .
						'<li><a href="https://www.zealousweb.com/contact/" target="_blank">Contact Us</a></li>' .
						'<li><a href="mailto:opensource@zealousweb.com">Email us</a></li>' .
					'</ol>'
				).
			'</div>';
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
		 * - Used to display the form data in CPT detail page.
		 *
		 * @method cfadn_show_from_data
		 *
		 * @param  object $post WP_Post
		 */
		function cfadn_show_from_data( $post ) {

			$fields = CF7ADN()->lib->data_fields;

			$form_id = get_post_meta( $post->ID, '_form_id', true );

			$data_ct = $this->cfauzw_check_data_ct( sanitize_text_field( $post->ID ) );

			echo '<table class="cf7adn-box-data form-table">' .
				'<style>.inside-field td, .inside-field th{ padding-top: 5px; padding-bottom: 5px;}</style>';

				if ( !empty( $fields ) ) {

					if( $data_ct ){
						echo'<tr class="inside-field"><th scope="row">You are using Free Accept Qpay payments Using Contact form 7 - no license needed. Enjoy! 🙂</th></tr>';
							echo'<tr class="inside-field"><th scope="row"><a href="https://www.zealousweb.com/wordpress-plugins/accept-authorize-net-payments-using-contact-form-7/" target="_blank">To unlock more features consider upgrading to PRO.</a></th></tr>';
					}else{

						if ( array_key_exists( '_transaction_response', $fields ) && empty( get_post_meta( $form_id, CF7ADN_META_PREFIX . 'debug', true ) ) ) {
							unset( $fields['_transaction_response'] );
						}

						$attachment = ( !empty( get_post_meta( $post->ID, '_attachment', true ) ) ? unserialize( get_post_meta( $post->ID, '_attachment', true ) ) : '' );
						$root_path = get_home_path();

						foreach ( $fields as $key => $value ) {

							if (
								!empty( get_post_meta( $post->ID, $key, true ) )
								&& $key != '_form_data'
								&& $key != '_transaction_response'
								&& $key != '_transaction_status'
							) {

								$val = get_post_meta( $post->ID, $key, true );

								echo '<tr class="form-field">' .
									'<th scope="row">' .
										'<label for="hcf_author">' . esc_html__( sprintf( '%s', $value ), 'contact-form-7-authorize-net-addon' ) . '</label>' .
									'</th>' .
									'<td>' .
										(
											(
												'_form_id' == $key
												&& !empty( get_the_title( get_post_meta( $post->ID, $key, true ) ) )
											)
											? esc_html__(get_the_title( get_post_meta( $post->ID, $key, true ) ))
											: esc_html__(get_post_meta( $post->ID, $key, true ))
										) .
									'</td>' .
								'</tr>';

							} else if (
								!empty( get_post_meta( $post->ID, $key, true ) )
								&& $key == '_transaction_status'
							) {

								echo '<tr class="form-field">' .
									'<th scope="row">' .
										'<label for="hcf_author">' . esc_html__( sprintf( '%s', $value ), 'contact-form-7-authorize-net-addon' ) . '</label>' .
									'</th>' .
									'<td>' .
										(
											(
												!empty( CF7ADN()->lib->response_status )
												&& array_key_exists( get_post_meta( $post->ID , $key, true ), CF7ADN()->lib->response_status )
											)
											? esc_html__(CF7ADN()->lib->response_status[get_post_meta( $post->ID , $key, true )] )
											: esc_html__(get_post_meta( $post->ID , $key, true ))
										) .
									'</td>' .
								'</tr>';

							} else if (
								!empty( get_post_meta( $post->ID, $key, true ) )
								&& $key == '_form_data'
							) {

								echo '<tr class="form-field">' .
									'<th scope="row">' .
										'<label for="hcf_author">' . esc_html__( sprintf( '%s', $value ), 'contact-form-7-authorize-net-addon' ) . '</label>' .
									'</th>' .
									'<td>' .
										'<table>';

											$data = get_post_meta( $post->ID, $key, true );
											$hide_data = apply_filters( CF7ADN_PREFIX . '/hide-display', array( '_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag', '_wpcf7_container_post' ) );
											foreach ( $hide_data as $key => $value ) {
												if ( array_key_exists( $value, $data ) ) {
													unset( $data[$value] );
												}
											}

											if ( !empty( $data ) ) {
												foreach ( $data as $key => $value ) {
													if ( strpos( $key, 'authorize-' ) === false ) {
														echo '<tr class="inside-field">' .
															'<th scope="row">' .
															esc_html__( sprintf( '%s', $key ), 'contact-form-7-authorize-net-addon' ) .
															'</th>' .
															'<td>' .
																(
																	(
																		!empty( $attachment )
																		&& array_key_exists( $key, $attachment )
																	)
																	? '<a href="' . esc_url( home_url( str_replace( $root_path, '/', $attachment[$key] ) ) ) . '" target="_blank" download>' . esc_html( substr($attachment[$key], strrpos($attachment[$key], '/') + 1), 'contact-form-7-authorize-net-addon' ) . '</a>'
																	: esc_html__( sprintf( '%s', ( is_array($value) ? implode( ', ', $value ) :  $value ) ), 'contact-form-7-authorize-net-addon' )
																) .
															'</td>' .
														'</tr>';
													}
												}
											}

										echo '</table>' .
									'</td>
								</tr>';

							} else if (
								!empty( get_post_meta( $post->ID, $key, true ) )
								&& $key == '_transaction_response'
							) {

								echo '<tr class="form-field">' .
									'<th scope="row">' .
										'<label for="hcf_author">' . esc_html__( sprintf( '%s', $value ), 'contact-form-7-authorize-net-addon' ) . '</label>' .
									'</th>' .
									'<td>' .
										'<code style="word-break: break-all;">' .
											(
												esc_html__(get_post_meta( $post->ID , $key, true ))
											) .
										'</code>' .
									'</td>' .
								'</tr>';

							}

						}
					}
				}

			echo '</table>';
		}

		/**
		* check data ct
		*/
		function cfauzw_check_data_ct( $post_id ){

			$data = get_post_meta( $post_id, '_form_data', true );
			if( !empty( get_post_meta( $post_id, '_form_data', true ) ) && isset( $data['_exceed_num_cfauzw'] ) && !empty( $data['_exceed_num_cfauzw'] ) ){
				return $data['_exceed_num_cfauzw'];
			}else{
				return '';
			}

		}

		/**
		 * - Used to add meta box in CPT detail page.
		 */
		function cfadn_show_help_data() {
			echo '<div id="cf7adn-data-help">' .
			esc_html__(apply_filters(
					CF7ADN_PREFIX . '/help/cf7adn_data/postbox',
					'<ol>' .
						'<li><a href="https://www.zealousweb.com/wordpress-plugins/product/accept-authorize-net-payments-using-contact-form-7/" target="_blank">Refer the document.</a></li>' .
						'<li><a href="https://www.zealousweb.com/contact/" target="_blank">Contact Us</a></li>' .
						'<li><a href="mailto:opensource@zealousweb.in">Email us</a></li>' .
					'</ol>'
				) ).
			'</div>';
		}


		/**
		 * - Add import submenu page callback
		 */
		function cfadn_import_submenu_page_callback() {
			echo '<div class="wrap cfadn_wrap_import show-upload-view">';
				echo '<h1 class ="wp-heading-inline">'. esc_html__( 'Import your CSV.', 'contact-form-7-stripe-addon' ) .'</h1>';
				echo '<div class="upload-plugin">
						<p class ="install-help">'. esc_html__( 'Check demo CSV ', 'contact-form-7-stripe-addon' ) .'<a download href="'.esc_url(CF7ADN_URL).'import-example/cf7adn-16-1588577363.csv">'. esc_html__( 'here..', 'contact-form-7-stripe-addon' ) .'</a></p>
						<form method="post" enctype="multipart/form-data" class="wp-upload-form" style="max-width:780px;">
							<label style="margin-right:6px;">'.esc_html__( 'Enter New Form Name','contact-form-7-stripe-addon' ).'
							<input type="text" placeholder="Enter New Form Name" name="formname" /></label>
							<label>'. esc_html__( 'Upload File','contact-form-7-stripe-addon' ) .'
							<input type="hidden" id="_wpnonce" name="_wpnonce_cfadn" value="'. esc_attr(wp_create_nonce( 'cfadn_import' )) .'">
							<input type="file" id="pluginzip" name="cfadn_importcsv"></label>
							<label><input type="submit" name="cfadnimport-plugin-submit" id="install-plugin-submit" class="button" value="Import Now" disabled=""></label>
						</form>
					</div>';
			echo '</div>';
		}

		/**
		 * - Import is success notice
		 */
		function action__admin_notices_import_done() {
			echo '<div class="updated">' .
				sprintf(
					/* translators: Contact Form 7 - Stripe Add-on */
					esc_html__( '<p>Import is done successfully.</p>', 'contact-form-7-stripe-addon' ),
					'Contact Form 7 - Stripe Add-on'
				) .
			'</div>';
		}

		/**
		 * Import nonce issue notice
		 */
		function action__admin_notices_import_nonce_issue(){
			echo '<div class="error">' .
				sprintf(
					/* translators: Contact Form 7 - Stripe Add-on */
					esc_html__( '<p>Nonce issue.. Please try again.</p>', 'contact-form-7-stripe-addon' ),
					'Contact Form 7 - Stripe Add-on'
				) .
			'</div>';
		}

		/**
		 * - Import file format notice
		 */
		function action__admin_notices_import_file_format() {
			echo '<div class="error">' .
				sprintf(
					/* translators: Contact Form 7 - Stripe Add-on */
					esc_html__( '<p>File Format is not suported.</p>', 'contact-form-7-stripe-addon' ),
					'Contact Form 7 - Stripe Add-on'
				) .
			'</div>';
		}

		/**
		 * Import file type notice
		 */
		function action__admin_notices_import_file_type() {
			echo '<div class="error">' .
				sprintf(
					/* translators: Contact Form 7 - Stripe Add-on */
					esc_html__( '<p>File type is not correct. Please upload CSV.</p>', 'contact-form-7-stripe-addon' ),
					'Contact Form 7 - Stripe Add-on'
				) .
			'</div>';
		}

		/**
		 * - Import fail notice
		 */
		function action__admin_notices_import_fail(){
			echo '<div class="error">' .
				sprintf(
					/* translators: Contact Form 7 - Stripe Add-on */
					esc_html__( '<p>Import is failed contact plugin author.</p>', 'contact-form-7-stripe-addon' ),
					'Contact Form 7 - Stripe Add-on'
				) .
			'</div>';
		}


	}

}
