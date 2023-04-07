( function($) {
	"use strict";

	function cf7adn_sandbox_validate() {
		if ( jQuery( '.cf7adn-settings #cf7adn_use_authorize' ).prop( 'checked' ) == true && jQuery( '.cf7adn-settings #cf7adn_mode_sandbox' ).prop( 'checked' ) != true ) {
			jQuery( '.cf7adn-settings #cf7adn_live_login_id, .cf7adn-settings #cf7adn_live_transaction_key' ).prop( 'required', true );
		} else {
			jQuery( '.cf7adn-settings #cf7adn_live_login_id, .cf7adn-settings #cf7adn_live_transaction_key' ).removeAttr( 'required' );
		}
	}

	function cf7adn_live_validate() {
		if ( jQuery( '.cf7adn-settings #cf7adn_use_authorize' ).prop( 'checked' ) == true && jQuery( '.cf7adn-settings #cf7adn_mode_sandbox' ).prop( 'checked' ) == true ) {
			jQuery( '.cf7adn-settings #cf7adn_sandbox_login_id, .cf7adn-settings #cf7adn_live_transaction_key' ).prop( 'required', true );
		} else {
			jQuery( '.cf7adn-settings #cf7adn_sandbox_login_id, .cf7adn-settings #cf7adn_live_transaction_key' ).removeAttr( 'required' );
		}
	}

	jQuery( document ).on( 'change', '.cf7adn-settings .enable_required', function() {
		if ( jQuery( this ).prop( 'checked' ) == true ) {
			jQuery( '.cf7adn-settings #cf7adn_amount' ).prop( 'required', true );
		} else {
			jQuery( '.cf7adn-settings #cf7adn_amount' ).removeAttr( 'required' );
		}

		cf7adn_live_validate();
		cf7adn_sandbox_validate();

	} );

	jQuery( document ).on( 'change', '.cf7adn-settings #cf7adn_mode_sandbox', function() {
		cf7adn_live_validate();
		cf7adn_sandbox_validate();
	} );

	jQuery( document ).on( 'input', '.cf7adn-settings .required', function() {
		cf7adn_live_validate();
		cf7adn_sandbox_validate();
	} );

} )( jQuery );

function check_authorize_field_validation(){		

	jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
	if( jQuery( '.cf7adn-settings #cf7adn_use_authorize' ).prop( 'checked' ) == true ){
			
		jQuery('.cf7adn-settings .form-required-fields').each(function() {
			if (jQuery.trim(jQuery(this).val()) == '') {
			  jQuery("#authorize-net-add-on-tab .ui-tabs-anchor").find('span').remove();
			  jQuery("#authorize-net-add-on-tab .ui-tabs-anchor").append('<span class="icon-in-circle" aria-hidden="true">!</span>');
		   }
		});
	   
   }else{
	   jQuery("#authorize-net-add-on-tab .ui-tabs-anchor").find('span').remove();
   }
			
}

jQuery( document ).ready( function() { check_authorize_field_validation() });
jQuery( document ).on('click',".ui-state-default",function() { check_authorize_field_validation() });