<?php

$new_post_id = ( isset( $_REQUEST[ 'post' ] ) ? sanitize_text_field( $_REQUEST[ 'post' ] ) : '' );

if ( empty( $new_post_id ) ) {
	$wpcf7 = WPCF7_ContactForm::get_current();
	$new_post_id = $wpcf7->id(); //phpcs:ignore
}
/* Scan Form Tags */
if($new_post_id!=""){
	$cf7adn_form = WPCF7_ContactForm::get_instance($new_post_id);
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

$use_authorize           = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'use_authorize', true );
$mode_sandbox            = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'mode_sandbox', true );
$debug_authorize         = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'debug', true );
$sandbox_login_id        = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'sandbox_login_id', true );
$sandbox_transaction_key = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'sandbox_transaction_key', true );
$live_login_id           = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'live_login_id', true );
$live_transaction_key    = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'live_transaction_key', true );
$amount                  = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'amount', true );
$quantity                = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'quantity', true );
$email                   = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'email', true );
$description             = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'description', true );

$success_returnURL       = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'success_returnurl', true );
$cancel_returnURL        = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'cancel_returnurl', true );
$message                 = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'message', true );

$currency                = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'currency', true );

$customer_details        = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'customer_details', true );

$first_name              = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'first_name', true );
$last_name               = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'last_name', true );
$company_name            = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'company_name', true );
$address                 = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'address', true );
$city                    = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'city', true );
$state                   = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'state', true );
$zip_code                = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'zip_code', true );
$country                 = get_post_meta( $new_post_id, CF7ADN_META_PREFIX . 'country', true );


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
$new_pages = get_posts( $args ); 
$all_pages = array();
if ( !empty( $new_pages ) ) {
	foreach ( $new_pages as $new ) { 
		$all_pages[$new->ID] = $new->post_title;
	}
}

echo '<div class="cf7adn-settings">' .
	'<div class="left-box postbox">' .
		'<input style="display: none;" id="' . esc_attr(CF7ADN_META_PREFIX) . 'customer_details" name="'. esc_attr(CF7ADN_META_PREFIX) . 'customer_details" type="checkbox" value="1" ' . checked( $customer_details, 1, false ) . ' />' .
		'<table class="form-table">' .
			'<tbody>' .
				'<tr class="form-field">' .
					'<th scope="row">' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'use_authorize">' .
							esc_html__( 'Enable Authorize.Net Payment Form', 'contact-form-7-authorize-net-addon' ) .
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
						esc_html__( 'Enable Test API Mode', 'contact-form-7-authorize-net-addon' ) .
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
						esc_html__( 'Enable Debug Mode', 'contact-form-7-authorize-net-addon' ) .
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
						esc_html__( 'Sandbox Login ID (required)', 'contact-form-7-authorize-net-addon' ) .
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
						esc_html__( 'Sandbox Transaction Key (required)', 'contact-form-7-authorize-net-addon' ) .
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
							esc_html__( 'Live Login ID (required)', 'contact-form-7-authorize-net-addon' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-live-login-id"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'live_login_id" name="' . esc_attr(CF7ADN_META_PREFIX) . 'live_login_id" type="text" class="large-text form-required-fields" value="' . esc_attr( $live_login_id ) . '" ' . ( empty( $mode_sandbox ) && !empty( $use_authorize ) ? 'required' : '' ) . '/>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="'  . esc_attr(CF7ADN_META_PREFIX) . 'live_transaction_key">' .
							esc_html__( 'Live Transaction Key (required)', 'contact-form-7-authorize-net-addon' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-live-transaction-key"></span>' .
					'</th>' .
					'<td>' .
						'<input id="'  . esc_attr(CF7ADN_META_PREFIX) . 'live_transaction_key" name="'  . esc_attr(CF7ADN_META_PREFIX) . 'live_transaction_key" type="text" class="large-text form-required-fields" value="' . esc_attr( $live_transaction_key ) . '" ' . ( empty( $mode_sandbox ) && !empty( $use_authorize ) ? 'required' : '' ) . '/>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="'  . esc_attr(CF7ADN_META_PREFIX) . 'amount">' .
						esc_html__( 'Amount Field Name (required)', 'contact-form-7-authorize-net-addon' ) .
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
							esc_html__( 'Quantity Field Name (Optional)', 'contact-form-7-authorize-net-addon' ) .
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
						esc_html__( 'Customer Email Field Name (Optional)', 'contact-form-7-authorize-net-addon' ) .
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
						esc_html__( 'Description Field Name (Optional)', 'contact-form-7-authorize-net-addon' ) .
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
						esc_html__( 'Select Currency', 'contact-form-7-authorize-net-addon' ) .
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
							esc_html__( 'Success Return URL (Optional)', 'contact-form-7-authorize-net-addon' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-success-return-url"></span>' .
					'</th>' .
					'<td>' .
						'<select id="' . esc_attr(CF7ADN_META_PREFIX) . 'success_returnurl" name="' . esc_attr(CF7ADN_META_PREFIX) . 'success_returnurl">' .
							'<option>' . esc_html__( 'Select page', 'contact-form-7-authorize-net-addon' ) . '</option>';
							if( !empty( $all_pages ) ) {
								foreach ( $all_pages as $new_post_id => $new_title ) {  
									echo '<option value="' . esc_attr( $new_post_id ) . '" ' . selected( $success_returnURL, $new_post_id, false )  . '>' . esc_html($new_title) . '</option>';
								}
							}
						echo '</select>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'cancel_returnurl">' .
						esc_html__( 'Cancel Return URL (Optional)', 'contact-form-7-authorize-net-addon' ) .
						'</label>' .
						'<span class="cf7adn-tooltip hide-if-no-js" id="cf7adn-cancel-return-url"></span>' .
					'</th>' .
					'<td>' .
						'<select id="' . esc_attr(CF7ADN_META_PREFIX) . 'cancel_returnurl" name="' . esc_attr(CF7ADN_META_PREFIX) . 'cancel_returnurl">' .
							'<option>' . esc_html__( 'Select page', 'contact-form-7-authorize-net-addon' ) . '</option>';

							if( !empty( $all_pages ) ) {
								foreach ( $all_pages as $new_post_id => $new_title ) { 
									echo '<option value="' . esc_attr( $new_post_id ) . '" ' . selected( $cancel_returnURL, $new_post_id, false )  . '>' . esc_html($new_title) . '</option>';
								}
							}

						echo '</select>' .
					'</td>' .
				'</tr>';

				/**
				 * - Add new field at the middle.
				 *
				 * @var int $new_post_id
				 */
				do_action(  CF7ADN_PREFIX . '/add/fields/middle', $new_post_id );

				echo '<tr class="form-field">' .
					'<th colspan="2">' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'customer_details">' .
							'<h3 style="margin: 0;">' .
							esc_html__( 'Customer Details', 'contact-form-7-authorize-net-addon' ) .
								'<span class="arrow-switch"></span>' .
							'</h3>' .
						'</label>' .
					'</th>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'first_name">' .
						esc_html__( 'First Name', 'contact-form-7-authorize-net-addon' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'first_name" name="' . esc_attr(CF7ADN_META_PREFIX) . 'first_name" type="text" class="regular-text" value="' . esc_attr( $first_name ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'last_name">' .
						esc_html__( 'Last Name', 'contact-form-7-authorize-net-addon' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'last_name" name="' . esc_attr(CF7ADN_META_PREFIX) . 'last_name" type="text" class="regular-text" value="' . esc_attr( $last_name ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'company_name">' .
						esc_html__( 'Company Name', 'contact-form-7-authorize-net-addon' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'company_name" name="' . esc_attr(CF7ADN_META_PREFIX) . 'company_name" type="text" class="regular-text" value="' . esc_attr( $company_name ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'address">' .
						esc_html__( 'Address', 'contact-form-7-authorize-net-addon' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'address" name="' . esc_attr(CF7ADN_META_PREFIX) . 'address" type="text" class="regular-text" value="' . esc_attr( $address ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'city">' .
						esc_html__( 'City', 'contact-form-7-authorize-net-addon' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'city" name="' . esc_attr(CF7ADN_META_PREFIX) . 'city" type="text" class="regular-text" value="' . esc_attr( $city ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'state">' .
						esc_html__( 'State', 'contact-form-7-authorize-net-addon' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'state" name="' . esc_attr(CF7ADN_META_PREFIX) . 'state" type="text" class="regular-text" value="' . esc_attr( $state ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'zip_code">' .
						esc_html__( 'Zip Code', 'contact-form-7-authorize-net-addon' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'zip_code" name="' . esc_attr(CF7ADN_META_PREFIX) . 'zip_code" type="text" class="regular-text" value="' . esc_attr( $zip_code ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field hide-show">' .
					'<th>' .
						'<label for="' . esc_attr(CF7ADN_META_PREFIX) . 'country">' .
						esc_html__( 'Country', 'contact-form-7-authorize-net-addon' ) .
						'</label>' .
					'</th>' .
					'<td>' .
						'<input id="' . esc_attr(CF7ADN_META_PREFIX) . 'country" name="' . esc_attr(CF7ADN_META_PREFIX) . 'country" type="text" class="regular-text" value="' . esc_attr( $country ) . '" />' .
					'</td>' .
				'</tr>';

				/**
				 * - Add new field at the end.
				 *
				 * @var int $new_post_id
				 */
				do_action(  CF7ADN_PREFIX . '/add/fields/end', $new_post_id );

				echo '<input type="hidden" name="post" value="' . esc_attr( $new_post_id ) . '">' .
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
						_e( '<h3>Enable Authorize.Net Payment</h3>' .
						'<p>To make enable Authorize.Net Payment with this Form.</p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );
			//jQuery selector to point to
			jQuery( '#cf7adn-enable-test-api-mode' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-enable-test-api-mode' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Sandbox mode</h3>' .
						'<p>Check the Authorize.Net testing guide <a href="https://developer.authorize.net/hello_world/testing_guide/" target="_blank">here</a>.This will display "sandbox mode" warning on checkout.</p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7adn-enable-debug-mode' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-enable-debug-mode' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Debug mode</h3>' .
						'<p>From this we can get the whole response of Payment Gateway and display in each Entry detail Page.</p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );
			

			jQuery( '#cf7adn-sandbox-login-id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-sandbox-login-id' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Get Your Sandbox Login ID</h3>' .
						'<p>Get it from <a href="https://sandbox.authorize.net" target="_blank"> Sandbox Authorize.net</a> then <strong> Account > Security Settings > API  Credentials & Keys </strong> page  in your Authorize.Net account.</p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7adn-sandbox-transaction-key' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-sandbox-transaction-key' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Get Your Sandbox Transaction Key</h3>' .
						'<p>Get it from <a href="https://sandbox.authorize.net" target="_blank"> Sandbox Authorize.net</a> then <strong>Account > Security Settings > API Credentials & Keys </strong> page in your Authorize.Net account. For security reasons, you cannot view your Transaction Key, but you will be able to generate a new one. </p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7adn-live-login-id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-live-login-id' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Get Your Live Login ID</h3>' .
						'<p>Get it from <a href="https://account.authorize.net" target="_blank">Authorize.net</a> then <strong>Account > Security Settings > API Credentials & Keys </strong> page  in your Authorize.Net account.</p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7adn-live-transaction-key' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-live-transaction-key' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Get Your Live Transaction Key</h3>' .
						'<p>Get it from <a href="https://account.authorize.net" target="_blank">Authorize.net</a> then <strong> Account > Security Settings > API Credentials & Keys </strong> page in your Authorize.Net account. For security reasons, you cannot view your Transaction Key, but you will be able to generate a new one. </p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7adn-amount-field' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-amount-field' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Add Amount Name</h3>' .
						'<p>Add here the Name of amount field created in Form. Its required because payment will capture payble amount from this field.</p>'.
						'<p><strong><span style="color:red">Note:</span> Save the FORM details to view the list of fields.</strong></p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7adn-quantity-field-name' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-quantity-field-name' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Add Quantity Field Name</h3>' .
						'<p>Add here the Name of quantity field created in Form.</p>'.
						'<p><strong><span style="color:red">Note:</span> Save the FORM details to view the list of fields.</strong></p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7adn-customer-email-field-name' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-customer-email-field-name' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Add Customer Email Field Name</h3>' .
						'<p>Add here the Name of customer email field created in Form.</p>'.
						'<p><strong><span style="color:red">Note:</span> Save the FORM details to view the list of fields.</strong></p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7adn-description-field-name' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-description-field-name' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Add Description Field Name</h3>' .
						'<p>Add here the Name of description field created in Form.</p>'.
						'<p><strong><span style="color:red">Note:</span> Save the FORM details to view the list of fields.</strong></p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7adn-select-currency' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-select-currency' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Select Currency</h3>' .
						'<p>Select the currency which is selected from your authorize.net merchant account.<br/><strong>Note:</strong>Authorize.net dont provide multiple currencies for single account</p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7adn-cancel-return-url' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-cancel-return-url' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Select Cancel Page URL</h3>' .
						'<p>Here is the list of all your WP pages. You need to create your Cancel Page and select that page from this dropdown. So, when any payment will canceled then on return it will redirect on this Cancel Page.</p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7adn-success-return-url' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7adn-success-return-url' ).pointer({
					pointerClass: 'wp-pointer cf7adn-pointer',
					content: '<?php
						_e( '<h3>Select Success Page URL</h3>' .
						'<p>Here is the list of all your WP pages. You need to create your Success Page and select that page from this dropdown. <br/>So, when any payment will successfully done then on return it will redirect on this Success Page.<br/> On success Page you can use our shortcode <b>[authorize-details]</b> to show transaction detail.</p>',
						'contact-form-7-authorize-net-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

		} );
	</script>
	<?php
	ob_get_clean(); // Get the buffered output and clean the buffer
} );