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
    $submitButton.attr( 'disabled', 'disabled' );
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

$(document).ready(function() {
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
});
