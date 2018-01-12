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

function checkPasswordStrength( $password, $strengthResult, $submitButton, blacklistArray ) {
    var password = $password.val();

    // Reset the form & meter
    $submitButton.attr( 'disabled', 'disabled' );
    $strengthResult.removeClass( 'short bad good strong' );

    // Extend our blacklist array with those from the inputs & site data
    blacklistArray = blacklistArray.concat( wp.passwordStrength.userInputBlacklist() )

    // Get the password strength
    var strength = wp.passwordStrength.meter( password, blacklistArray, password );

    // Add the strength meter results
    switch ( strength ) {
        case 2:
            $strengthResult.addClass( 'bad' ).html( pwsL10n.bad );
            break;
        case 3:
            $strengthResult.addClass( 'good' ).html( pwsL10n.good );
            break;
        case 4:
            $strengthResult.addClass( 'strong' ).html( pwsL10n.strong );
            break;
        case 5:
            $strengthResult.addClass( 'short' ).html( pwsL10n.mismatch );
            break;
        default:
            $strengthResult.addClass( 'short' ).html( pwsL10n.short );
    }

    // Only enable the submit button if the password is strong
    if ( 4 === strength ) {
        $submitButton.removeAttr( 'disabled' );
    }

    return strength;
}

$(document).ready(function() {
	showPassword();
	// checkPasswordStrength
	var $before = $('.a-form-show-password');
	$before.after( $('<span id="password-strength"></span>'));
    $( 'body' ).on( 'keyup', 'input[name=password], input[name=new_password]',
        function( event ) {
            checkPasswordStrength(
                $('input[name=password], input[name=new_password]'),         // First password field
                $('#password-strength'),           // Strength meter
                $('input[type=submit]'),           // Submit button
                ['black', 'listed', 'word']        // Blacklisted words
            );
        }
    );
});
