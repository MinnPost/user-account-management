var $ = window.jQuery;

function showPassword() {
	// Cache our jquery elements
	var $submit = $('.btn-submit');
	var $field = $('.password-show');
	var show_pass = '<div class="a-form-show-password a-form-caption"><label><input type="checkbox" name="show_password" id="show-password-checkbox" value="1"> Show password</label></div>';
	// Inject the toggle button into the page
	$field.after( show_pass );
	// Cache the toggle button
	var $toggle = $('#show-password-checkbox');
	// Toggle the field type
	$toggle.on('click', function(e) {
		var checkbox = $(this);
		if (checkbox.is(':checked')) {
			$field.attr('type', 'text');
		} else {
			$field.attr('type', 'password');
		}
	});
	// Set the form field back to a regular password element
	$submit.on( 'click', function(e) {
		$field.attr('type', 'password');
	});
}

function checkPasswordStrength( $password, $strengthMeter, $strengthText, $submitButton, blacklistArray ) {
    var password = $password.val();

    // Reset the form & meter
    //$submitButton.attr( 'disabled', 'disabled' );
    $strengthText.removeClass( 'short bad good strong' );

    // Extend our blacklist array with those from the inputs & site data
    blacklistArray = blacklistArray.concat( wp.passwordStrength.userInputBlacklist() )

    // Get the password strength
    var strength = wp.passwordStrength.meter( password, blacklistArray, password );

    // Add the strength meter results
    switch ( strength ) {
        case 2:
            $strengthText.addClass( 'bad' ).html( 'Strength: <strong>' + pwsL10n.bad + '</strong>' );
            break;
        case 3:
            $strengthText.addClass( 'good' ).html( 'Strength: <strong>' + pwsL10n.good + '</strong>' );
            break;
        case 4:
            $strengthText.addClass( 'strong' ).html( 'Strength: <strong>' + pwsL10n.strong + '</strong>' );
            break;
        case 5:
            $strengthText.addClass( 'short' ).html( 'Strength: <strong>' + pwsL10n.mismatch + '</strong>' );
            break;
        default:
            $strengthText.addClass( 'short' ).html( 'Strength: <strong>' + pwsL10n.short + '</strong>' );
    }
    $strengthMeter.val(strength);

    // Only enable the submit button if the password is strong
    /*
    if ( 4 === strength ) {
        $submitButton.removeAttr( 'disabled' );
    }*/

    return strength;
}

function checkZipCountry(city_field, state_field, zip_field, country_field) {

	var country = country_field.val();
	if (country == '') {
		country = 'US';
		country_field.val(country);
	}
	var zip = zip_field.val();

	if (zip !== '') {

		var location = {
			zip_code: zip,
			country: country
		}

		jQuery.ajax({
	        type: 'GET',
	        url: user_account_management_rest.site_url + user_account_management_rest.rest_namespace + '/check-zip',
	        data: location,
	        dataType: 'json',
	        success: function(response) {
	        	if (response.status === 'success') {
					var location = '';
					location += response.city;
					$(city_field).val(response.city);
					if (response.city !== response.state) {
						location += ', ' + response.state;
						$(state_field).val(response.state);
					}
					if (country !== 'US') {
						location += ', ' + country;
					}
					$('.location small').text(location);
				} else {
					$('.location small').text('');
				}
	        }
	    });
	}
}

$(document).ready(function() {

	// start
	if ($('#rh-name').length > 0 ) {
		$( '#rh-name' ).val( '' );
	}

	// show password if user clicks
	if ($('.password-show').length > 0 ) {
		showPassword();
	}
	// checkPasswordStrength
	if ($('.password-strength-check').length > 0 ) {
		var $before = $('.a-form-show-password');
		$before.after( $('<meter max="4" id="password-strength"><div></div></meter><p id="password-strength-text"></p>'));
	    $( 'body' ).on( 'keyup', 'input[name=password], input[name=new_password]',
	        function( event ) {
	            checkPasswordStrength(
	                $('input[name=password], input[name=new_password]'), // Password field
	                $('#password-strength'),           // Strength meter
	                $('#password-strength-text'),      // Strength text indicator
	                $('input[type=submit]'),           // Submit button
	                ['black', 'listed', 'word']        // Blacklisted words
	            );
	        }
	    );
	}

	// zip/country thing
	var country = $('.m-form-country #country').val();
	if ($('.m-form-zip-code #zip-code').length) {
		if (country == '' || country == 'US') {
			$('.m-form-country').hide();
			$('.m-form-zip-code #zip-code').prop('type', 'tel');
			$('.m-form-zip-code').append('<div class="a-form-caption location"><small></small></div><div class="a-form-caption show-country"><a href="#" id="registration_show_country"><small>Not in the US?</small></a></div>');
		} else {
			$('.m-form-zip-code label').html('Postal Code: <span title="This field is required." class="a-form-item-required">*</span>');
			$('.m-form-zip-code #zip-code').prop('type', 'text');
		}
		if ($('select, input', '.m-form-country').hasClass('not-in-us')) {
			$('.show-country', '.m-form-zip-code').remove();
			$('.m-form-country').show();
			$('.m-form-zip-code #zip-code').prop('type', 'text');
			$('.m-form-zip-code label').html('Postal Code: <span title="This field is required." class="a-form-item-required">*</span>');
		}
		$('.m-form-country #country, .m-form-zip-code #zip-code').blur(function() {
			checkZipCountry($('input[name="city"]'), $('input[name="state"]'), $('.m-form-zip-code #zip-code'), $('.m-form-country #country'));
		});
		$('.m-form-country #country').change(function() {
			checkZipCountry($('input[name="city"]'), $('input[name="state"]'), $('.m-form-zip-code #zip-code'), $('.m-form-country #country'));
		});
		$('#registration_show_country').click(function() {
			$('.m-form-zip-code label').html('Postal Code: <span title="This field is required." class="a-form-item-required">*</span>');
			$('.m-form-country').slideDown();
			$('.m-form-zip-code #zip-code').prop('type', 'text');
			$(this).hide();
			return false;
		});
	}

});
