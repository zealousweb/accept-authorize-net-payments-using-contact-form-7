<?php

$post_id = ( isset( $_REQUEST[ 'post' ] ) ? sanitize_text_field( $_REQUEST[ 'post' ] ) : '' ); //phpcs:ignore

if ( empty( $post_id ) ) {
	$wpcf7 = WPCF7_ContactForm::get_current();
	$post_id = $wpcf7->id(); //phpcs:ignore
}
/* Scan Form Tags */
if($post_id!=""){
	$cf7adn_form = WPCF7_ContactForm::get_instance($post_id);
	$cf7adntags = $cf7adn_form->collect_mail_tags();
	if (($key = array_search('paypal-pro', $cf7adntags)) !== false) {
	    unset($cf7adntags[$key]);
	}
}
if ( !function_exists( 'cf7adn_inlineScript_select2' ) ) {
	function cf7adn_inlineScript_select2() {
		ob_start();
		?>
		( function($) {
			jQuery('#cf7adn_currency, #cf7adn_success_returnurl, #cf7adn_cancel_returnurl, #cf7adn_amount, #cf7adn_description, #cf7adn_quantity, #cf7adn_email' ).select2();
		} )( jQuery );
		<?php
		return ob_get_clean();
	}
}

 wp_enqueue_style( 'wp-pointer' );
wp_enqueue_script( 'wp-pointer' );

 wp_enqueue_style( 'select2' );
wp_enqueue_script( 'select2' );
wp_add_inline_script( 'select2', cf7adn_inlineScript_select2() );

wp_enqueue_style( CF7ADN_PREFIX . '_admin_css' );

$use_authorize           = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'use_authorize', true );
$mode_sandbox            = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'mode_sandbox', true );
$debug_authorize         = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'debug', true );
$sandbox_login_id        = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'sandbox_login_id', true );
$sandbox_transaction_key = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'sandbox_transaction_key', true );
$live_login_id           = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'live_login_id', true );
$live_transaction_key    = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'live_transaction_key', true );
$amount                  = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'amount', true );
$quantity                = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'quantity', true );
$email                   = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'email', true );
$description             = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'description', true );

$success_returnURL       = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'success_returnurl', true );
$cancel_returnURL        = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'cancel_returnurl', true );
$message                 = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'message', true );

$currency                = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'currency', true );

$customer_details        = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'customer_details', true );

$first_name              = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'first_name', true );
$last_name               = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'last_name', true );
$company_name            = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'company_name', true );
$address                 = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'address', true );
$city                    = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'city', true );
$state                   = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'state', true );
$zip_code                = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'zip_code', true );
$country                 = get_post_meta( $post_id, CF7ADN_META_PREFIX . 'country', true );


$currency_code = array(
	'AUD' => 'Australian Dollar',
	'BRL' => 'Brazilian Real',
	'CAD' => 'Canadian Dollar',
	'CZK' => 'Czech Koruna',
	'DKK' => 'Danish Krone',
	'EUR' => 'Euro',
	'HKD' => 'Hong Kong Dollar',
	'HUF' => 'Hungarian Forint',
	'ILS' => 'Israeli New Sheqel',
	'JPY' => 'Japanese Yen',
	'MYR' => 'Malaysian Ringgit',
	'MXN' => 'Mexican Peso',
	'NOK' => 'Norwegian Krone',
	'NZD' => 'New Zealand Dollar',
	'PHP' => 'Philippine Peso',
	'PLN' => 'Polish Zloty',
	'GBP' => 'Pound Sterling',
	'RUB' => 'Russian Ruble',
	'SGD' => 'Singapore Dollar',
	'SEK' => 'Swedish Krona',
	'CHF' => 'Swiss Franc',
	'TWD' => 'Taiwan New Dollar',
	'THB' => 'Thai Baht',
	'TRY' => 'Turkish Lira',
	'USD' => 'U.S. Dollar'
);

$selected = '';


$args = array(
	'post_type'      => array( 'page' ),
	'orderby'        => 'title',
	'posts_per_page' => -1
);
$pages = get_posts( $args ); //phpcs:ignore
$all_pages = array();
if ( !empty( $pages ) ) {
	foreach ( $pages as $page ) { //phpcs:ignore
		$all_pages[$page->ID] = $page->post_title;
	}
}

echo '<div class="cf7adn-settings">' .
	'<div class="left-box postbox">' .
		'<input style="display: none;" id="' . esc_attr(CF7ADN_META_PREFIX) . 'customer_details" name="' . esc_attr(CF7ADN_META_PREFIX) . 'customer_details" type="checkbox" value="1" ' . checked( $customer_details, 1, false ) . ' />' .
		'<table class="form-table">' .
			'<tbody>' .
				'<tr class="form-field">' .
					'<th scope="row">' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'use_authorize">' .
							esc_html__( 'Enable Authorize.Net Payment Form', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-enable-authorizenet-payment-form"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'use_authorize" name="' . esc_attr(CF7ADN_META_PREFIX) . 'use_authorize" type="checkbox" class="enable_required" value="1" ' . checked( $use_authorize, 1, false ) . '/>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'mode_sandbox">' .
							esc_html__( 'Enable Test API Mode', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-enable-test-api-mode"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'mode_sandbox" name="' . esc_attr(CF7ADN_META_PREFIX) . 'mode_sandbox" type="checkbox" value="1" ' . checked( $mode_sandbox, 1, false ) . ' />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th scope="row">' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'debug">' .
							esc_html__( 'Enable Debug Mode', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-enable-debug-mode"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'debug" name="' . esc_attr(CF7ADN_META_PREFIX) . 'debug" type="checkbox" value="1" ' . checked( $debug_authorize, 1, false ) . '/>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'sandbox_login_id">' .
							esc_html__( 'Sandbox Login ID (required)', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-sandbox-login-id"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'sandbox_login_id" name="' . esc_attr(CF7ADN_META_PREFIX) . 'sandbox_login_id" type="text" class="large-text form-required-fields" value="' . esc_attr( $sandbox_login_id ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'sandbox_transaction_key">' .
							esc_html__( 'Sandbox Transaction Key (required)', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-sandbox-transaction-key"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'sandbox_transaction_key" name="' . esc_attr(CF7ADN_META_PREFIX) . 'sandbox_transaction_key" type="text" class="large-text form-required-fields" value="' . esc_attr( $sandbox_transaction_key ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'live_login_id">' .
							esc_html__( 'Live Login ID (required)', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-live-login-id"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'live_login_id" name="' . esc_attr(CF7ADN_META_PREFIX) . 'live_login_id" type="text" class="large-text form-required-fields" value="' . esc_attr( $live_login_id ) . '" ' . ( empty( $mode_sandbox ) && !empty( $use_authorize ) ? 'required' : '' ) . '/>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'live_transaction_key">' .
							esc_html__( 'Live Transaction Key (required)', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-live-transaction-key"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'live_transaction_key" name="' . esc_attr(CF7ADN_META_PREFIX) . 'live_transaction_key" type="text" class="large-text form-required-fields" value="' . esc_attr( $live_transaction_key ) . '" ' . ( empty( $mode_sandbox ) && !empty( $use_authorize ) ? 'required' : '' ) . '/>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'amount">' .
							esc_html__( 'Amount Field Name (required)', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-amount-field"></span>' .
					'</th>' .
					'<td>' .
						'<select id="' . esc_attr(CF7ADN_META_PREFIX) . 'amount" class="form-required-fields" name="' . esc_attr(CF7ADN_META_PREFIX) . 'amount" ' . ( !empty( $use_authorize ) ? 'required' : '' ) . '>';
							echo '<option value="">Select Field Name</option>';
									if ( !empty( $cf7adntags ) ) {
										foreach ( $cf7adntags as $key => $value ) {
											echo '<option value="' . esc_attr( $value ) . '" ' . selected( $amount, $value, false ) . '>' . esc_html( $value ) . '</option>';
										}
								}
						echo '</select>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'quantity">' .
							esc_html__( 'Quantity Field Name (Optional)', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-quantity-field-name"></span>' .
					'</th>' .
					'<td>' .
						'<select id="' . esc_attr(CF7ADN_META_PREFIX) . 'quantity" name="' . esc_attr(CF7ADN_META_PREFIX) . 'quantity">';
							echo '<option value="">Select Field Name</option>';
									if ( !empty( $cf7adntags ) ) {
										foreach ( $cf7adntags as $key => $value ) {
											echo '<option value="' . esc_attr( $value ) . '" ' . selected( $quantity, $value, false ) . '>' . esc_html( $value ) . '</option>';
										}
								}
						echo '</select>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'email">' .
							esc_html__( 'Customer Email Field Name (Optional)', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-customer-email-field-name"></span>' .
					'</th>' .
					'<td>' .
						'<select id="' . esc_attr(CF7ADN_META_PREFIX) . 'email" name="' . esc_attr(CF7ADN_META_PREFIX) . 'email">';
						echo '<option value="">Select Field Name</option>';
								if ( !empty( $cf7adntags ) ) {
									foreach ( $cf7adntags as $key => $value ) {
										echo '<option value="' . esc_attr( $value ) . '" ' . selected( $email, $value, false ) . '>' . esc_html( $value ) . '</option>';
									}
							}
						echo '</select>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'description">' .
							esc_html__( 'Description Field Name (Optional)', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-description-field-name"></span>' .
					'</th>' .
					'<td>' .
						'<select id="' . esc_attr(CF7ADN_META_PREFIX) . 'description" name="' . esc_attr(CF7ADN_META_PREFIX) . 'description">';
						echo '<option value="">Select Field Name</option>';
								if ( !empty( $cf7adntags ) ) {
									foreach ( $cf7adntags as $key => $value ) {
										echo '<option value="' . esc_attr( $value ) . '" ' . selected( $description, $value, false ) . '>' . esc_html( $value ) . '</option>';
									}
							}
						echo '</select>' .
					'</td>' .
				'</tr>' .
	 			'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'currency">' .
							esc_html__( 'Select Currency', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-select-currency"></span>' .
					'</th>' .
					'<td>' .
						'<select id="' . esc_attr(CF7ADN_META_PREFIX) . 'currency" name="' . esc_attr(CF7ADN_META_PREFIX) . 'currency">';

							if ( !empty( $currency_code ) ) {
								foreach ( $currency_code as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $currency, $key, false ) . '>' . esc_html( $value ) . '</option>';
								}
							}

						echo '</select>' .
					'</td>' .
				'</tr/>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'success_returnurl">' .
							esc_html__( 'Success Return URL (Optional)', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-success-return-url"></span>' .
					'</th>' .
					'<td>' .
						'<select id="' . esc_attr(CF7ADN_META_PREFIX) . 'success_returnurl" name="' . esc_attr(CF7ADN_META_PREFIX) . 'success_returnurl">' .
							'<option>' . esc_html__( 'Select page', 'accept-authorize-net-payments-using-contact-form-7' ) . '</option>';

							if( !empty( $all_pages ) ) {
								foreach ( $all_pages as $post_id => $title ) {  //phpcs:ignore
									echo '<option value="' . esc_attr( $post_id ) . '" ' . selected( $success_returnURL, $post_id, false )  . '>' . esc_html($title) . '</option>';
								}
							}

						echo '</select>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'cancel_returnurl">' .
							esc_html__( 'Cancel Return URL (Optional)', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-cancel-return-url"></span>' .
					'</th>' .
					'<td>' .
						'<select id="' . esc_attr(CF7ADN_META_PREFIX) . 'cancel_returnurl" name="' . esc_attr(CF7ADN_META_PREFIX) . 'cancel_returnurl">' .
							'<option>' . esc_html__( 'Select page', 'accept-authorize-net-payments-using-contact-form-7' ) . '</option>';

							if( !empty( $all_pages ) ) {
								foreach ( $all_pages as $post_id => $title ) { //phpcs:ignore
									echo '<option value="' . esc_attr( $post_id ) . '" ' . selected( $cancel_returnURL, $post_id, false )  . '>' . esc_html($title) . '</option>';
								}
							}

						echo '</select>' .
					'</td>' .
				'</tr>';

				/**
				 * - Add new field at the middle.
				 *
				 * @var int $post_id
				 */
				do_action(  CF7ADN_PREFIX . '/add/fields/middle', $post_id );

				echo '<tr class="form-field">' .
					'<th colspan="2">' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'customer_details">' .
							'<h3 style="margin: 0;">' .
								esc_html__( 'Customer Details', 'accept-authorize-net-payments-using-contact-form-7' ) .
								'<span class="arrow-switch"></span>' .
							'</h3>' .
						'</label>' .
					'</th>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'first_name">' .
							esc_html__( 'First Name', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'first_name" name="' . esc_attr(CF7ADN_META_PREFIX) . 'first_name" type="text" class="regular-text" value="' . esc_attr( $first_name ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'last_name">' .
							esc_html__( 'Last Name', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'last_name" name="' . esc_attr(CF7ADN_META_PREFIX) . 'last_name" type="text" class="regular-text" value="' . esc_attr( $last_name ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'company_name">' .
							esc_html__( 'Company Name', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'company_name" name="' . esc_attr(CF7ADN_META_PREFIX) . 'company_name" type="text" class="regular-text" value="' . esc_attr( $company_name ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'address">' .
							esc_html__( 'Address', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'address" name="' . esc_attr(CF7ADN_META_PREFIX) . 'address" type="text" class="regular-text" value="' . esc_attr( $address ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'city">' .
							esc_html__( 'City', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'city" name="' . esc_attr(CF7ADN_META_PREFIX) . 'city" type="text" class="regular-text" value="' . esc_attr( $city ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'state">' .
							esc_html__( 'State', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'state" name="' . esc_attr(CF7ADN_META_PREFIX) . 'state" type="text" class="regular-text" value="' . esc_attr( $state ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'zip_code">' .
							esc_html__( 'Zip Code', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'zip_code" name="' . esc_attr(CF7ADN_META_PREFIX) . 'zip_code" type="text" class="regular-text" value="' . esc_attr( $zip_code ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'country">' .
							esc_html__( 'Country', 'accept-authorize-net-payments-using-contact-form-7' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'country" name="' . esc_attr(CF7ADN_META_PREFIX) . 'country" type="text" class="regular-text" value="' . esc_attr( $country ) . '" />' .
					'</td>' .
				'</tr>';

				/**
				 * - Add new field at the end.
				 *
				 * @var int $post_id
				 */
				do_action(  CF7ADN_PREFIX . '/add/fields/end', $post_id );

				echo '<input type="hidden" name="post" value="' . esc_attr( $post_id ) . '">' .
			'</tbody>' .
		'</table>' .
	'</div>' .
	'<div class="right-box">';

		/**
		 * Add new post box to display the information.
		 */
		do_action( CF7ADN_PREFIX . '/postbox' );

		
	echo '</div>' .
'</div>';

add_action('admin_print_footer_scripts', function() {
	ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready( function($) {
			jQuery( '#cf7adn-enable-authorizenet-payment-form' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-enable-authorizenet-payment-form' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
					echo '<h3>'. esc_html__('Enable Authorize.Net Payment','accept-authorize-net-payments-using-contact-form-7') .'</h3>'.
					'<p>'.esc_html__('To make enable Authorize.Net Payment with this Form.','accept-authorize-net-payments-using-contact-form-7') .'</p>';?>',
					position: 'left center',
				} ).pointer('open');
			} );
			//jQuery selector to point to
			jQuery( '#cf7adn-enable-test-api-mode' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-enable-test-api-mode' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						echo '<h3>'. esc_html__('Sandbox mode','accept-authorize-net-payments-using-contact-form-7') .'</h3>'.
						'<p>'.esc_html__('Check the Authorize.Net testing guide','accept-authorize-net-payments-using-contact-form-7').' <a href="https://developer.authorize.net/hello_world/testing_guide/" target="_blank">' .esc_html__('here','accept-authorize-net-payments-using-contact-form-7'). '</a> '. esc_html__('This will display "sandbox mode" warning on checkout.','accept-authorize-net-payments-using-contact-form-7').'</p>'; ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7adn-enable-debug-mode' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-enable-debug-mode' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php echo '<h3>'. esc_html__('Debug mode','accept-authorize-net-payments-using-contact-form-7'). '</h3>'.
						'<p>' .esc_html__('From this we can get the whole response of Payment Gateway and display in each Entry detail Page.','accept-authorize-net-payments-using-contact-form-7').'</p>';?>',
					position: 'left center',
				} ).pointer('open');
			} );
			

			jQuery( '#cf7adn-sandbox-login-id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-sandbox-login-id' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php echo '<h3>'. esc_html__( 'Get Your Sandbox Login ID','accept-authorize-net-payments-using-contact-form-7').'</h3>' .
						'<p>'.esc_html__('Get it from ','accept-authorize-net-payments-using-contact-form-7').'<a href="https://sandbox.authorize.net" target="_blank">'.esc_html__('Sandbox Authorize.net','accept-authorize-net-payments-using-contact-form-7').'</a>'. esc_html__(' then','accept-authorize-net-payments-using-contact-form-7').'<strong>'.esc_html__('Account > Security Settings > API  Credentials & Keys','accept-authorize-net-payments-using-contact-form-7').'</strong>'.esc_html__(' page  in your Authorize.Net account.','accept-authorize-net-payments-using-contact-form-7').'</p>'; ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery('#cf7adn-sandbox-transaction-key').on('mouseenter click', function() {
			    jQuery('body .wp-pointer-buttons .close').trigger('click');
			    jQuery('#cf7adn-sandbox-transaction-key').pointer({
			        pointerClass: 'wp-pointer cf7adn-pointer',
			        content: '<?php echo '<h3>' . esc_html__('Get Your Sandbox Transaction Key', 'accept-authorize-net-payments-using-contact-form-7') . '</h3>' .
			            '<p>' . esc_html__('Get it from', 'accept-authorize-net-payments-using-contact-form-7') . ' <a href="https://sandbox.authorize.net" target="_blank">' . esc_html__('Sandbox Authorize.net', 'accept-authorize-net-payments-using-contact-form-7') . '</a> ' . esc_html__('then', 'accept-authorize-net-payments-using-contact-form-7') . ' <strong>' . esc_html__('Account > Security Settings > API Credentials & Keys', 'accept-authorize-net-payments-using-contact-form-7') . '</strong> ' . esc_html__('page in your Authorize.Net account. For security reasons, you cannot view your Transaction Key, but you will be able to generate a new one.', 'accept-authorize-net-payments-using-contact-form-7') . '</p>'; ?>',
			        position: 'left center',
			    }).pointer('open');
			});

			jQuery('#cf7adn-live-login-id').on('mouseenter click', function() {
			    jQuery('body .wp-pointer-buttons .close').trigger('click');
			    jQuery('#cf7adn-live-login-id').pointer({
			        pointerClass: 'wp-pointer cf7adn-pointer',
			        content: '<?php echo '<h3>' . esc_html__('Get Your Live Login ID', 'accept-authorize-net-payments-using-contact-form-7') . '</h3>' .
			            '<p>' . esc_html__('Get it from', 'accept-authorize-net-payments-using-contact-form-7') . ' <a href="https://account.authorize.net" target="_blank">' . esc_html__('Authorize.net', 'accept-authorize-net-payments-using-contact-form-7') . '</a> ' . esc_html__('then', 'accept-authorize-net-payments-using-contact-form-7') . ' <strong>' . esc_html__('Account > Security Settings > API Credentials & Keys', 'accept-authorize-net-payments-using-contact-form-7') . '</strong> ' . esc_html__('page in your Authorize.Net account.', 'accept-authorize-net-payments-using-contact-form-7') . '</p>'; ?>',
			        position: 'left center',
			    }).pointer('open');
			});

			jQuery('#cf7adn-live-transaction-key').on('mouseenter click', function() {
			    jQuery('body .wp-pointer-buttons .close').trigger('click');
			    jQuery('#cf7adn-live-transaction-key').pointer({
			        pointerClass: 'wp-pointer cf7adn-pointer',
			        content: '<?php echo '<h3>' . esc_html__('Get Your Live Transaction Key', 'accept-authorize-net-payments-using-contact-form-7') . '</h3>' .
			            '<p>' . esc_html__('Get it from', 'accept-authorize-net-payments-using-contact-form-7') . ' <a href="https://account.authorize.net" target="_blank">' . esc_html__('Authorize.net', 'accept-authorize-net-payments-using-contact-form-7') . '</a> ' . esc_html__('then', 'accept-authorize-net-payments-using-contact-form-7') . ' <strong>' . esc_html__('Account > Security Settings > API Credentials & Keys', 'accept-authorize-net-payments-using-contact-form-7') . '</strong> ' . esc_html__('page in your Authorize.Net account. For security reasons, you cannot view your Transaction Key, but you will be able to generate a new one.', 'accept-authorize-net-payments-using-contact-form-7') . '</p>'; ?>',
			        position: 'left center',
			    }).pointer('open');
			});

			jQuery('#cf7adn-amount-field').on('mouseenter click', function() {
			    jQuery('body .wp-pointer-buttons .close').trigger('click');
			    jQuery('#cf7adn-amount-field').pointer({
			        pointerClass: 'wp-pointer cf7adn-pointer',
			        content: '<?php echo '<h3>' . esc_html__('Add Amount Name', 'accept-authorize-net-payments-using-contact-form-7') . '</h3>' .
			            '<p>' . esc_html__('Add here the Name of amount field created in Form. It\'s required because payment will capture payable amount from this field.', 'accept-authorize-net-payments-using-contact-form-7') . '</p>' .
			            '<p><strong><span style="color:red">' . esc_html__('Note:', 'accept-authorize-net-payments-using-contact-form-7') . '</span> ' . esc_html__('Save the FORM details to view the list of fields.', 'accept-authorize-net-payments-using-contact-form-7') . '</strong></p>'; ?>',
			        position: 'left center',
			    }).pointer('open');
			});

			jQuery('#cf7adn-quantity-field-name').on('mouseenter click', function() {
			    jQuery('body .wp-pointer-buttons .close').trigger('click');
			    jQuery('#cf7adn-quantity-field-name').pointer({
			        pointerClass: 'wp-pointer cf7adn-pointer',
			        content: '<?php echo '<h3>' . esc_html__('Add Quantity Field Name', 'accept-authorize-net-payments-using-contact-form-7') . '</h3>' .
			            '<p>' . esc_html__('Add here the Name of quantity field created in Form.', 'accept-authorize-net-payments-using-contact-form-7') . '</p>' .
			            '<p><strong><span style="color:red">' . esc_html__('Note:', 'accept-authorize-net-payments-using-contact-form-7') . '</span> ' . esc_html__('Save the FORM details to view the list of fields.', 'accept-authorize-net-payments-using-contact-form-7') . '</strong></p>'; ?>',
			        position: 'left center',
			    }).pointer('open');
			});

			jQuery('#cf7adn-customer-email-field-name').on('mouseenter click', function() {
			    jQuery('body .wp-pointer-buttons .close').trigger('click');
			    jQuery('#cf7adn-customer-email-field-name').pointer({
			        pointerClass: 'wp-pointer cf7adn-pointer',
			        content: '<?php echo '<h3>' . esc_html__('Add Customer Email Field Name', 'accept-authorize-net-payments-using-contact-form-7') . '</h3>' .
			            '<p>' . esc_html__('Add here the Name of customer email field created in Form.', 'accept-authorize-net-payments-using-contact-form-7') . '</p>' .
			            '<p><strong><span style="color:red">' . esc_html__('Note:', 'accept-authorize-net-payments-using-contact-form-7') . '</span> ' . esc_html__('Save the FORM details to view the list of fields.', 'accept-authorize-net-payments-using-contact-form-7') . '</strong></p>'; ?>',
			        position: 'left center',
			    }).pointer('open');
			});

			jQuery('#cf7adn-description-field-name').on('mouseenter click', function() {
			    jQuery('body .wp-pointer-buttons .close').trigger('click');
			    jQuery('#cf7adn-description-field-name').pointer({
			        pointerClass: 'wp-pointer cf7adn-pointer',
			        content: '<?php echo '<h3>' . esc_html__('Add Description Field Name', 'accept-authorize-net-payments-using-contact-form-7') . '</h3>' .
			            '<p>' . esc_html__('Add here the Name of description field created in Form.', 'accept-authorize-net-payments-using-contact-form-7') . '</p>' .
			            '<p><strong><span style="color:red">' . esc_html__('Note:', 'accept-authorize-net-payments-using-contact-form-7') . '</span> ' . esc_html__('Save the FORM details to view the list of fields.', 'accept-authorize-net-payments-using-contact-form-7') . '</strong></p>'; ?>',
			        position: 'left center',
			    }).pointer('open');
			});

			jQuery('#cf7adn-select-currency').on('mouseenter click', function() {
			    jQuery('body .wp-pointer-buttons .close').trigger('click');
			    jQuery('#cf7adn-select-currency').pointer({
			        pointerClass: 'wp-pointer cf7adn-pointer',
			        content: '<?php echo '<h3>' . esc_html__('Select Currency', 'accept-authorize-net-payments-using-contact-form-7') . '</h3>' .
			            '<p>' . esc_html__('Select the currency which is selected from your authorize.net merchant account.', 'accept-authorize-net-payments-using-contact-form-7') . '<br/><strong>' . esc_html__('Note:', 'accept-authorize-net-payments-using-contact-form-7') . '</strong>' . esc_html__('Authorize.net does not provide multiple currencies for a single account.', 'accept-authorize-net-payments-using-contact-form-7') . '</p>'; ?>',
			        position: 'left center',
			    }).pointer('open');
			});

			jQuery('#cf7adn-cancel-return-url').on('mouseenter click', function() {
			    jQuery('body .wp-pointer-buttons .close').trigger('click');
			    jQuery('#cf7adn-cancel-return-url').pointer({
			        pointerClass: 'wp-pointer cf7adn-pointer',
			        content: '<?php echo '<h3>' . esc_html__('Select Cancel Page URL', 'accept-authorize-net-payments-using-contact-form-7') . '</h3>' .
			            '<p>' . esc_html__('Here is the list of all your WP pages. You need to create your Cancel Page and select that page from this dropdown. So, when any payment is canceled, it will redirect to this Cancel Page.', 'accept-authorize-net-payments-using-contact-form-7') . '</p>'; ?>',
			        position: 'left center',
			    }).pointer('open');
			});

			jQuery('#cf7adn-success-return-url').on('mouseenter click', function() {
			    jQuery('body .wp-pointer-buttons .close').trigger('click');
			    jQuery('#cf7adn-success-return-url').pointer({
			        pointerClass: 'wp-pointer cf7adn-pointer',
			        content: '<?php echo '<h3>' . esc_html__('Select Success Page URL', 'accept-authorize-net-payments-using-contact-form-7') . '</h3>' .
			            '<p>' . esc_html__('Here is the list of all your WP pages. You need to create your Success Page and select that page from this dropdown. So, when any payment is successfully done, it will redirect to this Success Page.', 'accept-authorize-net-payments-using-contact-form-7') . '<br/>' . esc_html__('On the Success Page, you can use our shortcode', 'accept-authorize-net-payments-using-contact-form-7') . ' <b>[authorize-details]</b> ' . esc_html__('to show transaction details.', 'accept-authorize-net-payments-using-contact-form-7') . '</p>'; ?>',
			        position: 'left center',
			    }).pointer('open');
			});
		} );
	</script>
	<?php
	echo ob_get_clean(); //phpcs:ignore
} );