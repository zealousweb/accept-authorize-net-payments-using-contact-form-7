( function( $ ) {

	var current_frm, next_frm, previous_frm; //fieldsets
	var has_response = false;

	jQuery( document ).ready( function() {
		jQuery( 'form.wpcf7-form' ).each( function( index, el ) {
			var totalFieldset = 0;
			var findFieldset = jQuery(el).find( 'fieldset.fieldset-cf7adn' );

			if ( findFieldset.length > 0 ) {
				jQuery.each( findFieldset, function( i, e ) {
					if ( i == 0 ) {
						jQuery( e ).addClass( 'cf7adn_current_frm' );
					} else {
						jQuery( e ).addClass( 'cf7adn_hide_frm' );
					}

					jQuery( e ).attr( 'data--cf7adn-order', i );
					totalFieldset = totalFieldset + 1;

					//disable next button if the fieldset has  wpcf7-acceptance
					var acceptances = jQuery( e ).find( 'input:checkbox.wpcf7-acceptance' );
					if ( acceptances.length ) {
						cf7adn_toggle_next_btn( acceptances, e );
					}
				} );

				jQuery.each( findFieldset, function(i, e) {
					if ( i == 0 ) {
						jQuery( e ).find('.cf7adn_back').remove();
					}
					if (i == ( totalFieldset - 1 ) ) {
						jQuery( e ).find( '.cf7adn_next' ).remove();
					}
				} );

				jQuery( el ).attr('data-count-fieldset', totalFieldset );
			}
		} );
	} );

	jQuery( document ).on( 'click', '.cf7adn_next', function( event ) {

		event.preventDefault();
		var $this = jQuery( this );

		$this.addClass( 'sending' );
		current_frm = $this.closest( '.fieldset-cf7adn' );
		next_frm = current_frm.next();

		//validation
		var form = $this.parent().closest( 'form.wpcf7-form' );

		var form_data = new FormData();
		jQuery.each( form.find( 'input[type="file"]' ), function( index, el ) {
			form_data.append( jQuery(el).attr( 'name' ), jQuery(el)[0].files[0] );
		} );

		var other_data = form.serializeArray();

		jQuery.each(other_data,function( key, input ){
			form_data.append( input.name, input.value );
		} );


		jQuery.ajax( {
			url: cf7adn_object.ajax_url + '?action=cf7_cf7adn_validation',
			type: 'POST',
			data: form_data,
			processData: false,
			contentType: false,
		} ) .done( function( msg ) {
			$this.removeClass('sending');
			var json = jQuery.parseJSON( msg );

			/*
			 * Insert _form_data_id if 'json variable' has
			 */
			if ( typeof json._cf7adn_db_form_data_id != 'undefined' ) {
				if ( !form.find('input[name="_cf7adn_db_form_data_id"]' ).length ) {
					form.append( '<input type="hidden" name="_cf7adn_db_form_data_id" value="' + json._cf7adn_db_form_data_id +'" />' );
				}
			}

			if ( !json.success ) {
				var checkError = 0;

				//reset error messages
				current_frm.find( '.wpcf7-form-control-wrap' ).removeClass( 'cf7adn-invalid' );
				current_frm.find( '.wpcf7-form-control-wrap .wpcf7-not-valid-tip' ).remove();

				if ( has_response ) {
					current_frm.find( '.wpcf7-response-output.wpcf7-validation-errors' ).removeClass( 'wpcf7-validation-errors' );
				} else {
					current_frm.find( '.wpcf7-response-output.wpcf7-validation-errors' ).remove();
				}

				jQuery.each( json.invalid_fields, function( index, el ) {
					if ( current_frm.find('input[name="' + index + '"]').length ||
						current_frm.find('input[name="' + index + '[]"]').length ||
						current_frm.find('select[name="' + index + '"]').length ||
						current_frm.find('select[name="' + index + '[]"]').length ||
						current_frm.find('textarea[name="' + index + '"]').length ||
						current_frm.find('textarea[name="' + index + '[]"]').length
					) {
						checkError = checkError + 1;

						var controlWrap = jQuery('.wpcf7-form-control-wrap.' + index, form );
						controlWrap.addClass( 'cf7adn-invalid' );
						controlWrap.find( 'span.wpcf7-not-valid-tip' ).remove();
						controlWrap.append( '<span role="alert" class="wpcf7-not-valid-tip">' + el.reason + '</span>' );
						//return false;
					}
				} );

				if ( checkError == 0 ) {
					json.success = true;
					has_response = false;
				} else {
					if ( $this.parents( 'form.wpcf7-form' ).find( '.wpcf7-response-output' ).length ) {
						has_response = true;
						$this.parents( 'form.wpcf7-form' ).find( '.wpcf7-response-output' ).addClass( 'wpcf7-validation-errors' ).show().text( json.message );
					} else {
						has_response = false;
						$this.parents( 'form.wpcf7-form' ).append( '<div class="wpcf7-response-output wpcf7-display-none wpcf7-validation-errors" style="display: block;" role="alert">' + json.message + '</div>' );
					}
				}
			}

			if ( json.success ) {

				current_frm.css( {
					height: '0px',
					overflow: 'hidden',
					opacity: '0',
					'visibility': 'hidden'
				} ).removeClass( 'cf7adn_current_frm' ).addClass( 'cf7adn_hide_frm' );

				next_frm.css( {
					height: 'auto',
					overflow: 'visible',
					opacity: '1',
					'visibility': 'visible'
				} ).addClass( 'cf7adn_current_frm' ).removeClass( 'cf7adn_hide_frm' );

				dhScrollTo(form);

				return false;

			} else {

			}
		} )
		.fail( function() {
			$this.removeClass( 'sending' );
		} )
		.always(function() {
			$this.removeClass( 'sending' );
		} );
		return false;
	} );

	function dhScrollTo( el ) {
		if ( cf7adn_object.scroll_step == 'true' ) {
			jQuery( 'html, body' ).animate( {
				scrollTop: el.offset().top
			}, 'slow' );
		} else if ( cf7adn_object.scroll_step == 'scroll_to_top' ) {
			jQuery( 'html, body' ).animate({
				scrollTop: jQuery( 'body' ).offset().top
			}, 'slow' );
		}
	}

	function cf7adn_toggle_next_btn( acceptances, fieldset ) {
		if ( acceptances.length > 0 ) {
			var ii = 0;
			jQuery.each( acceptances, function( i, v ) {
				if ( jQuery(v).is(':checked' ) ) {
					//console.log('checked');
				} else {
					ii++;
				}
			} );
			if ( ii > 0 ) {
				//console.log(ii);
				jQuery( fieldset ).find( '.cf7adn_next' ).attr( 'disabled', 'disabled' );
			} else {
				jQuery( fieldset ).find( '.cf7adn_next' ).removeAttr( 'disabled' );
			}
		}
	}

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
