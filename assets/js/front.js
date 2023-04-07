( function( $ ) {

	document.addEventListener('wpcf7mailsent', function( event ) {
		jQuery( event.target ).find( '.fieldset-cf7adn' ).css( {
			height: '0px',
			overflow: 'hidden',
			opacity: '0',
			'visibility': 'hidden'
		} ).removeClass( 'cf7adn_current_frm' ).addClass( 'cf7adn_hide_frm' );

		jQuery( event.target ).find( '.fieldset-cf7adn' ).first().css( {
			height: 'auto',
			overflow: 'visible',
			opacity: '1',
			'visibility': 'visible'
		} ).addClass( 'cf7adn_current_frm' ).removeClass( 'cf7adn_hide_frm' );

		var contactform_id = event.detail.contactFormId;
		var redirection_url = event.detail.apiResponse.redirection_url;
		if ( redirection_url != '' && redirection_url != undefined ) {
			window.location = redirection_url;
		}
	} );

} )( jQuery );
