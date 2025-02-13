<?php
/**
 * CF7ADN_Lib Class
 *
 * Handles the Library functionality.
 *
 * @package WordPress
 * @subpackage Accept Authorize.NET Payments Using Contact Form 7
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

require __DIR__ . '/autoload.php'; 

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

if ( !class_exists( 'CF7ADN_Lib' ) ) {

	if (!defined('AUTHORIZENET_LOG_FOLDER')) {
		define( 'AUTHORIZENET_LOG_FOLDER', CF7ADN_DIR . '/log' );
	}

	if (!defined('AUTHORIZENET_LOG_FILE')) {
		define( 'AUTHORIZENET_LOG_FILE', CF7ADN_DIR . '/log/authorize.log' );
	}

	if (!file_exists(AUTHORIZENET_LOG_FOLDER)) {
		mkdir(AUTHORIZENET_LOG_FOLDER, 0777, true);
	}

	class CF7ADN_Lib {

		private $lib_version = '2.0.0[7fa78e6]';

		var $context = '';

		var $data_fields = array(
			'_form_id'              => 'Form ID/Name',
			'_email'                => 'Email Address',
			'_transaction_id'       => 'Transaction ID',
			'_invoice_no'           => 'Invoice ID',
			'_amount'               => 'Amount',
			'_quantity'             => 'Quantity',
			'_total'                => 'Total',
			'_submit_time'          => 'Submit Time',
			'_request_Ip'           => 'Request IP',
			'_currency'             => 'Currency code',
			'_form_data'            => 'Form data',
			'_transaction_response' => 'Transaction response',
			'_transaction_status'   => 'Transaction status',
		);

		var $response_status = array(
			'1' => 'Approved',
			'2' => 'Declined',
			'3' => 'Error',
			'4' => 'Action Required',
		);

		function __construct() {

			add_action( 'init',       array( $this, 'action__init' ) );
			add_action( 'wpcf7_init', array( $this, 'action__wpcf7_init' ), 10, 0 );

			add_action( 'wpcf7_before_send_mail', array( $this, 'action__wpcf7_before_send_mail' ), 20, 3 );
			add_shortcode( 'authorize-details', array( $this, 'shortcode__authorize_details' ) );

			add_action( 'wpcf7_init', array( $this, 'action__wpcf7_verify_version' ), 20, 2 );

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
		 * - Start session to store the data into session.
		 *
		 * @method action__init
		 *
		 */
		function action__init() {
			if ( !isset( $_SESSION ) || session_status() == PHP_SESSION_NONE ) {
				session_start(['read_and_close' => true]);
			}
		}

		/**
		* Authorize Verify CF7 dependencies.
		*
		* @method action__wpcf7_verify_version
		*
		*/
		function action__wpcf7_verify_version(){

			$cf7_verify = $this->wpcf7_version();
			if ( version_compare($cf7_verify, '5.2') >= 0 ) {
				add_filter( 'wpcf7_feedback_response',   array( $this, 'filter__wpcf7_ajax_json_echo'   ), 20, 2 );
			} else{
				add_filter( 'wpcf7_ajax_json_echo',   array( $this, 'filter__wpcf7_ajax_json_echo'   ), 20, 2 );
			}

		}

		/**
		 * Get current conatct from 7 version.
		 *
		 * @method wpcf7_version
		 *
		 * @return string
		 */
		function wpcf7_version() {

			$wpcf7_path = plugin_dir_path( CF7ADN_DIR ) . 'contact-form-7/wp-contact-form-7.php';

			if( ! function_exists('get_plugin_data') ){
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$plugin_data = get_plugin_data( $wpcf7_path );

			return $plugin_data['Version'];
		}

		/**
		 * Action: wpcf7_init
		 *
		 * - Added new form tag and render the form into frontend with validation.
		 *
		 * @method action__wpcf7_init
		 *
		 */
		function action__wpcf7_init() {
			wpcf7_add_form_tag(
				array( 'authorize', 'authorize*' ),
				array( $this, 'wpcf7_add_form_tag_authorize_net' ),
				array( 'name-attr' => true )
			);

			add_filter( 'wpcf7_validate_authorize',  array( $this, 'wpcf7_authorize_validation_filter' ), 10, 2 );
			add_filter( 'wpcf7_validate_authorize*', array( $this, 'wpcf7_authorize_validation_filter' ), 10, 2 );
		}

		/**
		 * Action: CF7 before send email
		 *
		 * @method action__wpcf7_before_send_mail
		 *
		 * @param  object $contact_form WPCF7_ContactForm::get_instance()
		 * @param  bool   $abort
		 * @param  object $contact_form WPCF7_Submission class
		 *
		 */
		function action__wpcf7_before_send_mail( $contact_form, $abort, $wpcf7_submission ) {

			$submission    = WPCF7_Submission::get_instance(); // CF7 Submission Instance
			$form_ID       = $contact_form->id();
			$form_instance = WPCF7_ContactForm::get_instance($form_ID); // CF7 From Instance

			if ( $submission ) {
				// CF7 posted data
				$posted_data = $submission->get_posted_data();
			}

			if ( !empty( $form_ID ) ) {

				$use_authorize = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'use_authorize', true );

				if ( empty( $use_authorize ) )
					return;

				$mode_sandbox            = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'mode_sandbox', true );
				$sandbox_login_id        = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'sandbox_login_id', true );
				$sandbox_transaction_key = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'sandbox_transaction_key', true );
				$live_login_id           = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'live_login_id', true );
				$live_transaction_key    = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'live_transaction_key', true );
				$amount                  = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'amount', true );
				$quantity                = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'quantity', true );
				$email                   = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'email', true );
				$description             = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'description', true );

				$success_returnURL       = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'success_returnurl', true );
				$cancel_returnURL        = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'cancel_returnurl', true );
				$message                 = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'message', true );

				// Set some example data for the payment.
				$currency               = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'currency', true );
				$customer_details        = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'customer_details', true );

				$email = ( ( !empty( $email ) && array_key_exists( $email, $posted_data ) ) ? $posted_data[$email] : '' );
				$description = ( ( !empty( $description ) && array_key_exists( $description, $posted_data ) ) ? $posted_data[$description] : get_bloginfo( 'name' ) );

				$amount_val  = ( ( !empty( $amount ) && array_key_exists( $amount, $posted_data ) ) ? floatval( $posted_data[$amount] ) : '0' );
				$quanity_val = ( ( !empty( $quantity ) && array_key_exists( $quantity, $posted_data ) ) ? floatval( $posted_data[$quantity] ) : '' );

				if (
					!empty( $amount )
					&& array_key_exists( $amount, $posted_data )
					&& is_array( $posted_data[$amount] )
					&& !empty( $posted_data[$amount] )
				) {
					$val = 0;
					foreach ( $posted_data[$amount] as $k => $value ) {
						$val = $val + floatval($value);
					}
					$amount_val = $val;
				}

				if (
					!empty( $quantity )
					&& array_key_exists( $quantity, $posted_data )
					&& is_array( $posted_data[$quantity] )
					&& !empty( $posted_data[$quantity] )
				) {
					$qty_val = 0;
					foreach ( $posted_data[$quantity] as $k => $qty ) {
						$qty_val = $qty_val + floatval($qty);
					}
					$quanity_val = $qty_val;
				}

				if ( empty( $amount_val ) ) {
					add_filter( 'wpcf7_skip_mail', array( $this, 'filter__wpcf7_skip_mail' ), 20 );
					$_SESSION[ CF7ADN_META_PREFIX . 'amount_error' . $form_ID ] = esc_html__( 'Empty Amount field or Invalid configuration.', 'accept-authorize-net-payments-using-contact-form-7' );
					return;
				}

				$attachent = '';

				if ( !empty( $submission->uploaded_files() ) ) {
					$cf7_verify = $this->wpcf7_version();
					if ( version_compare( $cf7_verify, '5.4' ) >= 0 ) {
						$uploaded_files = $this->zw_cf7_upload_files( $submission->uploaded_files(), 'new' );
					}else{
						$uploaded_files = $this->zw_cf7_upload_files( array($submission->uploaded_files()), 'old' );
					}

					if ( !empty( $uploaded_files ) ) {
						$attachent = serialize( $uploaded_files );
					}
				}

				$amountPayable = (float) ( empty( $quanity_val ) ? $amount_val : ( $quanity_val * $amount_val ) );

				$login_id        = ( !empty( $mode_sandbox ) ? $sandbox_login_id : $live_login_id );
				$transaction_key = ( !empty( $mode_sandbox ) ? $sandbox_transaction_key : $live_transaction_key );



				$merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
				$merchantAuthentication->setName( $login_id );
				$merchantAuthentication->setTransactionKey( $transaction_key );

				$refId = 'cf7adn-ref' . time();

				$data = $posted_data['authorize'];

				extract( $data );
				$card_number = trim(preg_replace('/\s+/', '', $card_number ) );
				$exp_year = trim(preg_replace('/\s+/', '', $exp_year ) );
				$exp_month = trim(preg_replace('/\s+/', '', $exp_month ) );
				$cvv_number = trim(preg_replace('/\s+/', '', $cvv_number ) );

				$creditCard = new AnetAPI\CreditCardType();
				$creditCard->setCardNumber( preg_replace( '/[^A-Za-z0-9\-]/', '', $card_number ) );
				$creditCard->setExpirationDate( preg_replace( '/[^A-Za-z0-9\-]/', '', $exp_year ) . '-' . preg_replace( '/[^A-Za-z0-9\-]/', '', $exp_month ) );
				$creditCard->setCardCode( preg_replace( '/[^A-Za-z0-9\-]/', '', $cvv_number ) );

				// Add the payment data to a paymentType object
				$paymentOne = new AnetAPI\PaymentType();
				$paymentOne->setCreditCard( $creditCard );

				$invoice_no = 'cf7adn/' . wp_rand();

				// Create order information
				$order = new AnetAPI\OrderType();
				$order->setInvoiceNumber( $invoice_no );
				$order->setDescription( $description );

				// Set the customer's Bill To address
				$customerAddress = new AnetAPI\CustomerAddressType();

				if ( !empty( $customer_details ) ) {

					$first_name              = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'first_name', true );
					$last_name               = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'last_name', true );
					$company_name            = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'company_name', true );
					$address                 = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'address', true );
					$city                    = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'city', true );
					$state                   = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'state', true );
					$zip_code                = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'zip_code', true );
					$country                 = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'country', true );

					if (
						!empty( $first_name )
						and $first_name_data = ( ( !empty( $first_name ) && array_key_exists( $first_name, $posted_data ) ) ? $posted_data[$first_name] : '' )
					) {
						$customerAddress->setFirstName( $first_name_data );
					}

					if (
						!empty( $last_name )
						and $last_name_data = ( ( !empty( $last_name ) && array_key_exists( $last_name, $posted_data ) ) ? $posted_data[$last_name] : '' )
					) {
						$customerAddress->setLastName( $last_name_data );
					}

					if (
						!empty( $company_name )
						and $company_name_data = ( ( !empty( $company_name ) && array_key_exists( $company_name, $posted_data ) ) ? $posted_data[$company_name] : '' )
					) {
						$customerAddress->setCompany( $company_name_data );
					}

					if (
						!empty( $address )
						and $address_data = ( ( !empty( $address ) && array_key_exists( $address, $posted_data ) ) ? $posted_data[$address] : '' )
					) {
						$customerAddress->setAddress( $address_data );
					}

					if (
						!empty( $city )
						and $city_data = ( ( !empty( $city ) && array_key_exists( $city, $posted_data ) ) ? $posted_data[$city] : '' )
					) {
						if(is_array($city_data)){
							$customerAddress->setCity( $city_data['0'] );
							
						}else{
							$customerAddress->setCity( $city_data );
						}
					}

					if (
						!empty( $state )
						and $state_data = ( ( !empty( $state ) && array_key_exists( $state, $posted_data ) ) ? $posted_data[$state] : '' )
					) {
						if(gettype($state_data)){
							$customerAddress->setState( $state_data['0'] );
							
						}else{
							$customerAddress->setState( $state_data );
						}
					}

					if (
						!empty( $zip_code )
						and $zip_code_data = ( ( !empty( $zip_code ) && array_key_exists( $zip_code, $posted_data ) ) ? $posted_data[$zip_code] : '' )
					) {
						$customerAddress->setZip( $zip_code_data );
					}

					if (
						!empty( $country )
						and $country_data = ( ( !empty( $country ) && array_key_exists( $country, $posted_data ) ) ? $posted_data[$country] : '' )
					) {
						if(gettype($country_data)){
							$customerAddress->setCountry( $country_data['0'] );
							
						}else{
							$customerAddress->setCountry( $country_data );
						}
					}

				}

				/**
				 * - Modify request data for the customer Address
				 *
				 * @var object $customerAddress AnetAPI\CustomerAddressType
				 * @var int    $form_ID         Form ID
				 */
				do_action( CF7ADN_PREFIX . '/request/customer/address', $customerAddress, $form_ID );

				// Set the customer's identifying information
				$customerData = new AnetAPI\CustomerDataType();
				$customerData->setType( 'individual' );

				if ( !empty( $email ) && is_email( $email ) ) {
					$customerData->setEmail( $email );
				}

				/**
				 * - Modify request data for the customer data
				 *
				 * @var object $customerAddress AnetAPI\CustomerDataType
				 * @var int    $form_ID         Form ID
				 */
				do_action( CF7ADN_PREFIX . '/request/customer/data', $customerAddress, $form_ID );

				// Add values for transaction settings
				$duplicateWindowSetting = new AnetAPI\SettingType();
				$duplicateWindowSetting->setSettingName( 'duplicateWindow' );
				$duplicateWindowSetting->setSettingValue( '60' );

				// Create a TransactionRequestType object and add the previous objects to it
				$transactionRequestType = new AnetAPI\TransactionRequestType();
				$transactionRequestType->setTransactionType( 'authCaptureTransaction' );
				$transactionRequestType->setAmount( $amountPayable );
				$transactionRequestType->setOrder( $order );
				$transactionRequestType->setPayment( $paymentOne );

				// $currency
				$transactionRequestType->setcurrencyCode( $currency );
				$transactionRequestType->setBillTo( $customerAddress );
				$transactionRequestType->setCustomer( $customerData );
				$transactionRequestType->addToTransactionSettings( $duplicateWindowSetting );
				$transactionRequestType->setCustomerIP( $this->getUserIpAddr() );

				/**
				 * - Modify request data for the TransactionRequestType
				 *
				 * @var object $transactionRequestType AnetAPI\TransactionRequestType
				 * @var int    $form_ID         Form ID
				 */
				do_action( CF7ADN_PREFIX . '/request/transaction', $transactionRequestType, $form_ID );

				// Assemble the complete transaction request
				$request = new AnetAPI\CreateTransactionRequest();
				$request->setMerchantAuthentication( $merchantAuthentication );
				$request->setRefId( $refId );
				$request->setTransactionRequest( $transactionRequestType );

				// Create the controller and get the response
				$controller = new AnetController\CreateTransactionController( $request );

				$response = (
					!empty( $mode_sandbox )
					? $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX )
					: $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION )
				);

				$exceed_ct		= sanitize_text_field( substr( get_option( '_exceed_cfauzw_l' ), 6 ) );

				if ( !empty( $response ) ) {

					// Check to see if the API request was successfully received and acted upon
					if ( $response->getMessages()->getResultCode() == 'Ok' ) {

						// Since the API request was successful, look for a transaction response
						// and parse it to display the results of authorizing the card
						$tresponse = $response->getTransactionResponse();

						if ( $tresponse != null && $tresponse->getMessages() != null ) {

							$adn_post_id = wp_insert_post( array (
								'post_type' => 'cf7adn_data',
								'post_title' => ( !empty( $email ) ? $email : $invoice_no ), // email/invoice_no
								'post_status' => 'publish',
								'comment_status' => 'closed',
								'ping_status' => 'closed',
							) );

							if ( !empty( $adn_post_id ) ) {

								$stored_data = $posted_data;
								unset( $stored_data['authorize'] );

								if(!get_option('_exceed_cfauzw')){
									sanitize_text_field( add_option('_exceed_cfauzw', '1') );
								}else{
									$exceed_val = sanitize_text_field( get_option( '_exceed_cfauzw' ) ) + 1;
									update_option( '_exceed_cfauzw', $exceed_val );
								}

								if ( !empty( sanitize_text_field( get_option( '_exceed_cfauzw' ) ) ) && sanitize_text_field( get_option( '_exceed_cfauzw' ) ) > $exceed_ct ) {
									$stored_data['_exceed_num_cfauzw'] = '1';
								}

								add_post_meta( $adn_post_id, '_form_id', sanitize_text_field($form_ID) );
								add_post_meta( $adn_post_id, '_email', sanitize_email($email) );
								add_post_meta( $adn_post_id, '_transaction_id', sanitize_text_field($tresponse->getTransId()) );
								add_post_meta( $adn_post_id, '_invoice_no', sanitize_text_field($invoice_no) );
								add_post_meta( $adn_post_id, '_amount', sanitize_text_field($amount_val) );
								add_post_meta( $adn_post_id, '_quantity', sanitize_text_field($quanity_val) );
								add_post_meta( $adn_post_id, '_total', sanitize_text_field($amountPayable) );
								add_post_meta( $adn_post_id, '_request_Ip', sanitize_text_field($this->getUserIpAddr()) );
								add_post_meta( $adn_post_id, '_currency', sanitize_text_field($currency) );
								add_post_meta( $adn_post_id, '_form_data', (array)$stored_data);
								add_post_meta( $adn_post_id, '_attachment', sanitize_text_field($attachent) );
								add_post_meta( $adn_post_id, '_transaction_response', wp_json_encode( $tresponse ) );
								add_post_meta( $adn_post_id, '_transaction_status', sanitize_text_field($tresponse->getResponseCode()) );
							}

							$_SESSION[ CF7ADN_META_PREFIX . 'form_message' . $form_ID ] = serialize( $tresponse->getMessages()[0]->getDescription() );
							$_SESSION[ CF7ADN_META_PREFIX . 'failed' . $form_ID ] = false;

							add_filter( 'wpcf7_mail_tag_replaced_authorize*', function( $replaced, $submitted, $html, $mail_tag ) use ( $tresponse, $invoice_no ) {

									$data = array();
									$data[] = 'Transaction ID: ' . $tresponse->getTransId();
									$data[] = 'Transaction Code: ' . $tresponse->getResponseCode();
									$data[] = 'Transaction Message: ' . $tresponse->getMessages()[0]->getDescription();
									$data[] = 'Auth Code: ' . $tresponse->getAuthCode();
									$data[] = 'Invoice Number: ' . $invoice_no;

									if ( !empty( $html ) ) {
										return implode( '<br/>', $data );
									} else {
										return implode( "\n", $data );
									}


								return $replaced;
							}, 10, 5 );

							if ( !empty( $success_returnURL ) && $success_returnURL != "Select page" ) {

								$redirect_url = add_query_arg(
									array(
										'form'          => $form_ID,
										'invoice'       => $invoice_no,
										'transactionId' =>  $tresponse->getTransId(),
									),
									esc_url( get_permalink( $success_returnURL ) )
								);

								$_SESSION[ CF7ADN_META_PREFIX . 'return_url' . $form_ID ] = $redirect_url;

								if ( !$submission->is_restful() ) {
									wp_redirect( $redirect_url );
									exit;
								}
							} else {

							$_SESSION[ CF7ADN_META_PREFIX . 'return_url' . $form_ID ] = "";

							}


						} else {

							$adn_post_id = wp_insert_post( array (
								'post_type' => 'cf7adn_data',
								'post_title' => ( !empty( $email ) ? $email : $invoice_no ), // email/invoice_no
								'post_status' => 'publish',
								'comment_status' => 'closed',
								'ping_status' => 'closed',
							) );

							if ( !empty( $adn_post_id ) ) {

								$stored_data = $posted_data;
								unset( $stored_data['authorize'] );

								add_post_meta( $adn_post_id, '_form_id', sanitize_text_field($form_ID) );
								add_post_meta( $adn_post_id, '_email', sanitize_email($email) );
								add_post_meta( $adn_post_id, '_transaction_id', sanitize_text_field($tresponse->getTransId()) );
								add_post_meta( $adn_post_id, '_invoice_no', sanitize_text_field($invoice_no) );
								add_post_meta( $adn_post_id, '_amount', sanitize_text_field($amount_val) );
								add_post_meta( $adn_post_id, '_quantity', sanitize_text_field($quanity_val) );
								add_post_meta( $adn_post_id, '_total', sanitize_text_field($amountPayable) );
								add_post_meta( $adn_post_id, '_request_Ip', sanitize_text_field($this->getUserIpAddr()) );
								add_post_meta( $adn_post_id, '_currency', sanitize_text_field($currency) );
								add_post_meta( $adn_post_id, '_form_data', serialize( $stored_data ) );
								add_post_meta( $adn_post_id, '_transaction_response', wp_json_encode( $tresponse ) );
								add_post_meta( $adn_post_id, '_transaction_status', sanitize_text_field($tresponse->getResponseCode()) );
							}

							if ( $tresponse->getErrors() != null ) {

								$_SESSION[ CF7ADN_META_PREFIX . 'form_message' . $form_ID ] = serialize( str_replace('AnetApi/xml/v1/schema/AnetApiSchema.xsd:', '', $tresponse->getErrors()[0]->getErrorText() ) );
								$_SESSION[ CF7ADN_META_PREFIX . 'failed' . $form_ID ] = true;

								add_filter( 'wpcf7_skip_mail', array( $this, 'filter__wpcf7_skip_mail' ), 20 );
								$wpcf7_submission->set_status( 'mail_failed' );
								$wpcf7_submission->set_response( $contact_form->message( 'mail_sent_ng' ) );

								if ( !empty( $cancel_returnURL ) && $cancel_returnURL != "Select page" ) {

									$redirect_url = add_query_arg(
										array(
											'form'          => $form_ID,
											'invoice'       => $invoice_no,
											'transactionId' =>  $tresponse->getTransId(),
										),
										esc_url( get_permalink( $cancel_returnURL ) )
									);

									$_SESSION[ CF7ADN_META_PREFIX . 'return_url' . $form_ID ] = $redirect_url;

									if ( !$submission->is_restful() ) {
										wp_redirect( $redirect_url );
										exit;
									}

								} else {

								$_SESSION[ CF7ADN_META_PREFIX . 'return_url' . $form_ID ] = "";

		          				}

							}
						}

						// Or, print errors if the API request wasn't successful
					} else {

						$tresponse = $response->getTransactionResponse();

						if ( $tresponse != null && $tresponse->getErrors() != null ) {

							$_SESSION[ CF7ADN_META_PREFIX . 'form_message' . $form_ID ] = serialize( str_replace('AnetApi/xml/v1/schema/AnetApiSchema.xsd:', '', $tresponse->getErrors()[0]->getErrorText() ) );
							$_SESSION[ CF7ADN_META_PREFIX . 'failed' . $form_ID ] = true;

							add_filter( 'wpcf7_skip_mail', array( $this, 'filter__wpcf7_skip_mail' ), 20 );
							$submission->set_status( 'mail_failed' );
							$submission->set_response( $contact_form->message( 'mail_sent_ng' ) );

						} else {

							$_SESSION[ CF7ADN_META_PREFIX . 'form_message' . $form_ID ] = serialize( str_replace('AnetApi/xml/v1/schema/AnetApiSchema.xsd:', '', $response->getMessages()->getMessage()[0]->getText() ) );
							$_SESSION[ CF7ADN_META_PREFIX . 'failed' . $form_ID ] = true;

							add_filter( 'wpcf7_skip_mail', array( $this, 'filter__wpcf7_skip_mail' ), 20 );
							$submission->set_status( 'mail_failed' );
							$submission->set_response( $contact_form->message( 'mail_sent_ng' ) );
						}
					}

				}
			}

			return $submission;
		}

		function shortcode__authorize_details() {

			if(isset($_REQUEST['_wpnonce_cfadn']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce_cfadn'])), 'cfadn_import')){
					return '';
			}
			$form_ID = (int)( isset( $_REQUEST['form'] ) ? sanitize_text_field($_REQUEST['form']) : '' );
			$transactionId = ( isset( $_REQUEST['transactionId'] ) ? sanitize_text_field($_REQUEST['transactionId']) : '' );

			if ( empty( $form_ID ) || empty( $transactionId ) )
				return '<p style="color: #f00">' . __( 'Something goes wrong! Please try again.', 'accept-authorize-net-payments-using-contact-form-7' ) . '</p>';

			$use_authorize = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'use_authorize', true );

			if ( empty( $use_authorize ) )
				return '<p style="color: #f00">' . __( 'Something goes wrong! Please try again.', 'accept-authorize-net-payments-using-contact-form-7' ) . '</p>';

			ob_start();

			$mode_sandbox            = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'mode_sandbox', true );
			$sandbox_login_id        = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'sandbox_login_id', true );
			$sandbox_transaction_key = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'sandbox_transaction_key', true );
			$live_login_id           = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'live_login_id', true );
			$live_transaction_key    = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'live_transaction_key', true );
			$currency                = get_post_meta( $form_ID, CF7ADN_META_PREFIX . 'currency', true );

			$login_id        = ( !empty( $mode_sandbox ) ? $sandbox_login_id : $live_login_id );
			$transaction_key = ( !empty( $mode_sandbox ) ? $sandbox_transaction_key : $live_transaction_key );

			$merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
			$merchantAuthentication->setName( $login_id );
			$merchantAuthentication->setTransactionKey( $transaction_key );

			$refId = 'ref' . time();

			$request = new AnetAPI\GetTransactionDetailsRequest();
			$request->setMerchantAuthentication( $merchantAuthentication );
			$request->setTransId( $transactionId );

			$controller = new AnetController\GetTransactionDetailsController( $request );

			$response = (
				!empty( $mode_sandbox )
				? $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX )
				: $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION )
			);

			if (
				( $response != null )
				&& ( $response->getMessages()->getResultCode() == "Ok" )
			) {

			if($response->getTransaction()->getResponseReasonDescription() == 'Approval'):
					$status = 'Approved';
			else:
				$status = $response->getTransaction()->getResponseReasonDescription();
			endif;

				echo '<table class="cf7adn-transaction-details" align="left">' .
					'<tr>'.
						'<th align="left">' . esc_html__( 'Transaction Amount :', 'accept-authorize-net-payments-using-contact-form-7' ) . '</th>'.
						'<td align="left">' . esc_html($response->getTransaction()->getAuthAmount()) . ' ' . esc_html($currency) . '</td>'.
					'</tr>' .
					'<tr>'.
						'<th align="left">' . esc_html__( 'Payment Status :', 'accept-authorize-net-payments-using-contact-form-7' ) . '</th>'.
						'<td align="left">' . esc_html($status) . '</td>'.
					'</tr>' .
					'<tr>'.
						'<th align="left">' . esc_html__( 'Transaction Id :', 'accept-authorize-net-payments-using-contact-form-7' ) . '</th>'.
						'<td align="left">' . esc_html($response->getTransaction()->getTransId()) . '</td>'.
					'</tr>' .
				'</table>';

			} else {

				echo '<table class="cf7adn-transaction-details" align="left">' .
					'<tr>'.
						'<th align="left" colspan="2">' . esc_html__( 'ERROR :  Invalid response', 'accept-authorize-net-payments-using-contact-form-7' ) . '</th>'.
					'</tr>' .
					'<tr>'.
						'<th align="left">' . esc_html__( 'Response :', 'accept-authorize-net-payments-using-contact-form-7' ) . '</th>'.
						'<td align="left">' . esc_html($errorMessages[0]->getCode()) . "  " .esc_html($errorMessages[0]->getText()) . '</td>'.
					'</tr>' .
				'</table>';

			}

			return ob_get_clean();

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
		 * Filter: wpcf7_validate_authorize
		 *
		 * - Perform Validation on authorize card details.
		 *
		 * @param  object  $result WPCF7_Validation
		 * @param  object  $tag    Form tag
		 *
		 * @return object
		 */
		function wpcf7_authorize_validation_filter( $result, $tag ) {

			if(isset($_REQUEST['_wpnonce_cfadn']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce_cfadn'])), 'cfadn_import')){
					return '';
			}

			$authorize = isset( $_POST['authorize'] ) ? (array)$_POST['authorize'] : array();

			$id = isset( $_POST['_wpcf7'] ) ? sanitize_text_field($_POST['_wpcf7']) : '';

			if ( !empty( $id ) ) {
				$id = (int) $_POST[ '_wpcf7' ];
			} else {
				return $result;
			}

			$use_authorize = get_post_meta( $id, CF7ADN_META_PREFIX . 'use_authorize', true );

			if ( empty( $use_authorize ) )
				return $result;

			$error = array();

			if ( empty( sanitize_text_field($authorize['cardholdername']) ) )
				$error[] = esc_html__( 'Card holder name', 'accept-authorize-net-payments-using-contact-form-7' );

			if ( empty( sanitize_text_field($authorize['card_number']) ) )
				$error[] = esc_html__( 'Card number', 'accept-authorize-net-payments-using-contact-form-7' );

			if ( empty( sanitize_text_field($authorize['exp_month']) ) || empty( $authorize['exp_year'] ) )
				$error[] = esc_html__( 'Expiry date and year', 'accept-authorize-net-payments-using-contact-form-7' );

			if ( empty( sanitize_text_field($authorize['cvv_number']) ) )
				$error[] = esc_html__( 'CVV', 'accept-authorize-net-payments-using-contact-form-7' );

			if ( !empty( $error ) )
				$result->invalidate( $tag, 'Please enter ' . implode(', ', $error ) );

			$card_number = trim(preg_replace('/\s+/', '', $authorize['card_number'] ) );
			$exp_year = trim(preg_replace('/\s+/', '', $authorize['exp_year'] ) );
			$exp_month = trim(preg_replace('/\s+/', '', $authorize['exp_month'] ) );
			$cvv_number = trim(preg_replace('/\s+/', '', $authorize['cvv_number'] ) );


			$creditCard = preg_replace( '/[^A-Za-z0-9\-]/', '', $card_number );
			$exp_year = preg_replace( '/[^A-Za-z0-9\-]/', '', $exp_year );
			$exp_month = preg_replace( '/[^A-Za-z0-9\-]/', '', $exp_month );
			$cvv_number = preg_replace( '/[^A-Za-z0-9\-]/', '', $cvv_number );


			if ( empty( $creditCard ) || !is_numeric( $creditCard ) ) {
				$result->invalidate( $tag, 'The credit card number is invalid.' );
				return $result;
			}

			if ( empty( $exp_year ) || !is_numeric( $exp_year ) || strlen( $exp_year ) !== 4 || $exp_year < gmdate( 'Y' ) ) {
				$result->invalidate( $tag, 'Credit card expiration date is invalid.' );
				return $result;
			}

			if ( empty( $exp_month ) || !is_numeric( $exp_month ) || strlen( $exp_month ) > 2 || (int) $exp_month > 12 ) {
				$result->invalidate( $tag, 'Credit card expiration month is invalid.' );
				return $result;
			}

			if ( empty( $cvv_number ) || !is_numeric( $cvv_number ) ) {
				$result->invalidate( $tag, 'The cardholder authentication value is invalid.' );
				return $result;
			}

			return $result;
		}

		/**
		 * Filter: Modify the contact form 7 response.
		 *
		 * @method filter__wpcf7_ajax_json_echo
		 *
		 * @param  array $response
		 * @param  array $result
		 *
		 * @return array
		 */
		function filter__wpcf7_ajax_json_echo( $response, $result ) {

			if (
				   array_key_exists( 'contact_form_id' , $result )
				&& array_key_exists( 'status' , $result )
				&& !empty( $result[ 'contact_form_id' ] )
				&& !empty( $_SESSION[ CF7ADN_META_PREFIX . 'form_message' . $result[ 'contact_form_id' ] ] )
				&& $result[ 'status' ] == 'mail_sent'
			) {

				$tmp = $response[ 'message' ];
				$response[ 'message' ] = unserialize( $_SESSION[ CF7ADN_META_PREFIX . 'form_message' . $result[ 'contact_form_id' ] ] );
				unset( $_SESSION[ CF7ADN_META_PREFIX . 'form_message' . $result[ 'contact_form_id' ] ] );

				if ( isset( $_SESSION[ CF7ADN_META_PREFIX . 'return_url' . $result[ 'contact_form_id' ] ] ) ) {
					$response[ 'redirection_url' ] = $_SESSION[ CF7ADN_META_PREFIX . 'return_url' . $result[ 'contact_form_id' ] ];
					unset( $_SESSION[ CF7ADN_META_PREFIX . 'return_url' . $result[ 'contact_form_id' ] ] );
				}

				if (
					!empty( $_SESSION[ CF7ADN_META_PREFIX . 'failed' . $result[ 'contact_form_id' ] ] )
				) {
					$response[ 'status' ] = 'mail_failed';
					unset( $_SESSION[ CF7ADN_META_PREFIX . 'failed' . $result[ 'contact_form_id' ] ] );
				} else {
					$response[ 'message' ] = $response[ 'message' ] . ' ' . $tmp;
				}

			}

			if (
				   array_key_exists( 'contact_form_id' , $result )
				&& array_key_exists( 'status' , $result )
				&& !empty( $result[ 'contact_form_id' ] )
				&& !empty( $_SESSION[ CF7ADN_META_PREFIX . 'amount_error' . $result[ 'contact_form_id' ] ] )
				&& $result[ 'status' ] == 'mail_sent'
			) {

				$response[ 'message' ] = $_SESSION[ CF7ADN_META_PREFIX . 'amount_error' . $result[ 'contact_form_id' ] ];
				$response[ 'status' ] = 'mail_failed';
				unset( $_SESSION[ CF7ADN_META_PREFIX . 'amount_error' . $result[ 'contact_form_id' ] ] );
			}

			return $response;
		}

		/**
		 * Filter: Skip email when Authorize.Net enable.
		 *
		 * @method filter__wpcf7_skip_mail
		 *
		 * @param  bool $bool
		 *
		 * @return bool
		 */
		function filter__wpcf7_skip_mail( $bool ) {
			return true;
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
		 * - Render CF7 Shortcode on front end.
		 *
		 * @method wpcf7_add_form_tag_authorize_net
		 *
		 * @param $tag
		 *
		 * @return html
		 */
		function wpcf7_add_form_tag_authorize_net( $tag ) {

			if ( empty( $tag->name ) ) {
				return '';
			}

			$validation_error = wpcf7_get_validation_error( $tag->name );

			$class = wpcf7_form_controls_class( $tag->type, 'wpcf7-text' );

			if ( in_array( $tag->basetype, array( 'email', 'url', 'tel' ) ) ) {
				$class .= ' wpcf7-validates-as-' . $tag->basetype;
			}

			if ( $validation_error ) {
				$class .= ' wpcf7-not-valid';
			}

			$atts = array();

			if ( $tag->is_required() ) {
				$atts['aria-required'] = 'true';
			}

			$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

			$atts['value'] = 1;

			$atts['type'] = 'hidden';
			$atts['name'] = $tag->name;
			$atts = wpcf7_format_atts( $atts );

			$form_instance = WPCF7_ContactForm::get_current();
			$form_id = $form_instance->id();

			$use_authorize           = get_post_meta( $form_id, CF7ADN_META_PREFIX . 'use_authorize', true );
			$mode_sandbox            = get_post_meta( $form_id, CF7ADN_META_PREFIX . 'mode_sandbox', true );
			$sandbox_login_id        = get_post_meta( $form_id, CF7ADN_META_PREFIX . 'sandbox_login_id', true );
			$sandbox_transaction_key = get_post_meta( $form_id, CF7ADN_META_PREFIX . 'sandbox_transaction_key', true );
			$live_login_id           = get_post_meta( $form_id, CF7ADN_META_PREFIX . 'live_login_id', true );
			$live_transaction_key    = get_post_meta( $form_id, CF7ADN_META_PREFIX . 'live_transaction_key', true );

			if ( empty( $use_authorize ) ) {
				return;
			}

			if ( !empty( $this->_validate_fields( $form_id ) ) )
				return $this->_validate_fields( $form_id );

			$login_id        = ( !empty( $mode_sandbox ) ? $sandbox_login_id : $live_login_id );
			$transaction_key = ( !empty( $mode_sandbox ) ? $sandbox_transaction_key : $live_transaction_key );

			$merchant_validate = $this->validate_merchant( $login_id, $transaction_key, $mode_sandbox );

			$value = (string) reset( $tag->values );

			$found = 0;
			$html = '';

			if ( !empty( $merchant_validate ) && $merchant_validate != 1 ) {
				return '<div class="cf7adn-error">' . wp_kses_post( $merchant_validate ) . '</div>';
			}

			ob_start();

			if ( $contact_form = wpcf7_get_current_contact_form() ) {
				$form_tags = $contact_form->scan_form_tags();

				foreach ( $form_tags as $k => $v ) {

					if ( $v['type'] == $tag->type ) {
						$found++;
					}

					if ( $v['name'] == $tag->name ) {
						if ( $found <= 1 ) {

							echo '</fieldset>' .
							'<fieldset class="fieldset-cf7adn">' .
								'<div class="cf7adn-form-code">' .
									sprintf(
										'<span class="credit_card_details wpcf7-form-control-wrap %1$s" data-name="%1$s">%2$s%3$s</span>',
										sanitize_html_class( $tag->name ),
										'<h4>'.esc_html__( "Your Credit Card Detail", "accept-authorize-net-payments-using-contact-form-7" ).':</h4>
										<p>
											<label>'.esc_html__( "Card holder name", "accept-authorize-net-payments-using-contact-form-7" ).'</label>
											<span class="authorize-cardholdername">
												<input type="text" name="' . esc_attr($tag->basetype) . '[cardholdername]" size="20" class="' . esc_attr($class) . '" required/>
											</span>
										</p>
										<p>
											<label>'.esc_html__( "Card Number (required)", "accept-authorize-net-payments-using-contact-form-7" ).'</label>
											<span class="authorize-cardnumber">
												<input type="number" name="' . esc_attr($tag->basetype) . '[card_number]" data-authorize="number" class="' . esc_attr($class) . '" size="16" required/>
											</span>
										</p>
										<p>
											<label>'.esc_html__( "Card Expiry Date (required)", "accept-authorize-net-payments-using-contact-form-7" ).'</label>
											<span class="authorize-expires">
												<input type="number" name="' . esc_attr($tag->basetype) . '[exp_month]" data-authorize="exp-month" size="2" maxlength="2" class="' . esc_attr($class) . '" id="authorize-month" placeholder="MM" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" maxlength = "2" required/>
												<span class="cart-separation"></span>
												<input type="number" name="' . esc_attr($tag->basetype) . '[exp_year]" data-authorize="exp-year" min="' . esc_attr( gmdate( 'Y' ) ) . '" max="2050" size="4" class="' . esc_attr($class) . '" id="authorize-year" placeholder="YYYY" maxlength="4" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" maxlength = "4" required/>
											</span>
										</p>
										<p>
											<label>'.esc_html__( "Card CVV (required)", "accept-authorize-net-payments-using-contact-form-7" ).'</label>
											<span class="authorize-cvv">
												<input type="text" name="' . esc_attr($tag->basetype) . '[cvv_number]" data-authorize="cvc" class="' . esc_attr($class) . '" size="4" placeholder="CVV" required/>
											</span>
										</p>',
										esc_attr($validation_error)
									) .
								'</div>';
						}

						break;
					}
				}
			}

			return ob_get_clean();
		}

		/**
		 * Function: validate_merchant
		 *
		 * - Used to validate the Merchant information to show the card form.
		 *
		 * @param  string  $login_id        Login ID
		 * @param  string  $transaction_key Transaction Key
		 * @param  bool    $env             Live/Debug mode.
		 *
		 * @return mixed
		 */
		function validate_merchant( $login_id, $transaction_key, $env = null ) {
			/* Create a merchantAuthenticationType object with authentication details
			retrieved from the constants file */
			$merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
			$merchantAuthentication->setName( $login_id );
			$merchantAuthentication->setTransactionKey( $transaction_key );

			// Set the transaction's refId
			$refId = 'ref' . time();

			// Get all existing customer profile ID's
			$request = new AnetAPI\GetCustomerProfileIdsRequest();
			$request->setMerchantAuthentication($merchantAuthentication);
			$controller = new AnetController\GetCustomerProfileIdsController($request);

			$response = (
				!empty( $env )
				? $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX )
				: $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION )
			);

			if (
				( $response != null )
				&& ( $response->getMessages()->getResultCode() == 'Ok' )
			) {
				return true;
			} else {
				$errorMessages = $response->getMessages()->getMessage();

				$messages = array();

				if ( !empty( $errorMessages ) ) {
					foreach ( $errorMessages as $k => $v ) {
						$messages[] = $v->getText();
					}
				}

				return implode( '\n', $messages );
			}
			return false;
		}

		/**
		 * Function: _validate_fields
		 *
		 * @method _validate_fields
		 *
		 * @param int $form_id
		 *
		 * @return string
		 */
		function _validate_fields( $form_id ) {
			$mode_sandbox            = get_post_meta( $form_id, CF7ADN_META_PREFIX . 'mode_sandbox', true );
			$sandbox_login_id        = get_post_meta( $form_id, CF7ADN_META_PREFIX . 'sandbox_login_id', true );
			$sandbox_transaction_key = get_post_meta( $form_id, CF7ADN_META_PREFIX . 'sandbox_transaction_key', true );
			$live_login_id           = get_post_meta( $form_id, CF7ADN_META_PREFIX . 'live_login_id', true );
			$live_transaction_key    = get_post_meta( $form_id, CF7ADN_META_PREFIX . 'live_transaction_key', true );

			if ( !empty( $mode_sandbox ) ) {

				if ( empty( $sandbox_login_id ) )
					return __( 'Please enter Sandbox Login ID.', 'accept-authorize-net-payments-using-contact-form-7' );

				if ( empty( $sandbox_transaction_key ) )
					return __( 'Please enter Sandbox Transaction Key.', 'accept-authorize-net-payments-using-contact-form-7' );
			}

			if ( empty( $mode_sandbox ) ) {

				if ( empty( $live_login_id ) )
					return __( 'Please enter Merchant Login ID.', 'accept-authorize-net-payments-using-contact-form-7' );

				if ( empty( $live_transaction_key ) )
					return __( 'Please enter Merchant Transaction Key.', 'accept-authorize-net-payments-using-contact-form-7' );
			}

			return false;
		}

		/**
		 * Function: getUserIpAddr
		 *
		 * @method getUserIpAddr
		 *
		 * @return string
		 */
		function getUserIpAddr() {
			$ip = '';
			if ( !empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				//ip from share internet
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			} else if ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				//ip pass from proxy
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			return $ip;
		}


		/**
		 * Get the attachment upload directory from plugin.
		 *
		 * @method zw_wpcf7_upload_tmp_dir
		 *
		 * @return string
		 */
		function zw_wpcf7_upload_tmp_dir() {

			$upload = wp_upload_dir();
			$upload_dir = $upload['basedir'];
			$cf7adn_upload_dir = $upload_dir . '/cf7adn-uploaded-files';

			if ( !is_dir( $cf7adn_upload_dir ) ) {
				mkdir( $cf7adn_upload_dir, 0755 );
			}

			return $cf7adn_upload_dir;
		}

		/**
		 * Copy the attachment into the plugin folder.
		 *
		 * @method zw_cf7_upload_files
		 *
		 * @param  array $attachment
		 *
		 * @uses $this->zw_wpcf7_upload_tmp_dir(), WPCF7::wpcf7_maybe_add_random_dir()
		 *
		 * @return array
		 */
		 function zw_cf7_upload_files( $attachment, $version ) {
  			if( empty( $attachment ) )
  				return;

  			$new_attachment = $attachment;
 			foreach ( $attachment as $key => $value ) {
  				$tmp_name = $value;
  				$uploads_dir = wpcf7_maybe_add_random_dir( $this->zw_wpcf7_upload_tmp_dir() );
  				foreach ($tmp_name as $newkey => $file_path) {
 					$get_file_name = explode( '/', $file_path );
  					$new_file = path_join( $uploads_dir, end( $get_file_name ) );
  					if ( copy( $file_path, $new_file ) ) {
  						chmod( $new_file, 0755 );
  						if($version == 'old'){
  							$new_attachment_file[$newkey] = $new_file;
  						}else{
  							$new_attachment_file[$key] = $new_file;
  						}
  					}
  				}
  			}
 			return $new_attachment_file;
  		}

	}

}
