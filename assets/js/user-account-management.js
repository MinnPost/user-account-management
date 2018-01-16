'use strict';

var $ = window.jQuery;

function showPassword() {
	// Cache our jquery elements
	var $submit = $('.btn-submit');
	var $field = $('.password-show');
	var show_pass = '<div class="a-form-show-password a-form-caption"><label><input type="checkbox" name="show_password" id="show-password-checkbox" value="1"> Show password</label></div>';
	// Inject the toggle button into the page
	$field.after(show_pass);
	// Cache the toggle button
	var $toggle = $('#show-password-checkbox');
	// Toggle the field type
	$toggle.on('click', function (e) {
		var checkbox = $(this);
		if (checkbox.is(':checked')) {
			$field.attr('type', 'text');
		} else {
			$field.attr('type', 'password');
		}
	});
	// Set the form field back to a regular password element
	$submit.on('click', function (e) {
		$field.attr('type', 'password');
	});
}

function checkPasswordStrength($password, $strengthMeter, $strengthText, $submitButton, blacklistArray) {
	var password = $password.val();

	// Reset the form & meter
	//$submitButton.attr( 'disabled', 'disabled' );
	$strengthText.removeClass('short bad good strong');

	// Extend our blacklist array with those from the inputs & site data
	blacklistArray = blacklistArray.concat(wp.passwordStrength.userInputBlacklist());

	// Get the password strength
	var strength = wp.passwordStrength.meter(password, blacklistArray, password);

	// Add the strength meter results
	switch (strength) {
		case 2:
			$strengthText.addClass('bad').html('Strength: <strong>' + pwsL10n.bad + '</strong>');
			break;
		case 3:
			$strengthText.addClass('good').html('Strength: <strong>' + pwsL10n.good + '</strong>');
			break;
		case 4:
			$strengthText.addClass('strong').html('Strength: <strong>' + pwsL10n.strong + '</strong>');
			break;
		case 5:
			$strengthText.addClass('short').html('Strength: <strong>' + pwsL10n.mismatch + '</strong>');
			break;
		default:
			$strengthText.addClass('short').html('Strength: <strong>' + pwsL10n.short + '</strong>');
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
		};

		jQuery.ajax({
			type: 'GET',
			url: user_account_management_rest.site_url + user_account_management_rest.rest_namespace + '/check-zip',
			data: location,
			dataType: 'json',
			success: function success(response) {
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

$(document).ready(function () {

	// start
	if ($('#rh-name').length > 0) {
		$('#rh-name').val('');
	}

	// show password if user clicks
	if ($('.password-show').length > 0) {
		showPassword();
	}
	// checkPasswordStrength
	if ($('.password-strength-check').length > 0) {
		var $before = $('.a-form-show-password');
		$before.after($('<meter max="4" id="password-strength"><div></div></meter><p id="password-strength-text"></p>'));
		$('body').on('keyup', 'input[name=password], input[name=new_password]', function (event) {
			checkPasswordStrength($('input[name=password], input[name=new_password]'), // Password field
			$('#password-strength'), // Strength meter
			$('#password-strength-text'), // Strength text indicator
			$('input[type=submit]'), // Submit button
			['black', 'listed', 'word'] // Blacklisted words
			);
		});
	}

	// zip/country thing
	var country = $('.m-form-country #country').val();
	if ($('.m-form-zip-code #zip-code').length) {
		if (country == '' || country == 'US') {
			$('.m-form-country').hide();
			$('.m-form-zip-code #zip-code').prop('type', 'tel');
			$('.m-form-zip-code').append('<div class="a-form-caption location"><small></small></div><div class="a-form-caption show-country"><a href="#" id="registration_show_country"><small>Not in the US?</small></a></div>');
		} else if ($('.m-form-country #country').length > 0) {
			$('.m-form-zip-code label').html('Postal Code: <span title="This field is required." class="a-form-item-required">*</span>');
			$('.m-form-zip-code #zip-code').prop('type', 'text');
		}
		if ($('.m-form-country #country').length > 0) {
			if ($('select, input', '.m-form-country').hasClass('not-in-us')) {
				$('.show-country', '.m-form-zip-code').remove();
				$('.m-form-country').show();
				$('.m-form-zip-code #zip-code').prop('type', 'text');
				$('.m-form-zip-code label').html('Postal Code: <span title="This field is required." class="a-form-item-required">*</span>');
			}
			if ($('.m-form-city #city').length == 0 && $('.m-form-state #state').length == 0) {
				$('.m-form-country #country, .m-form-zip-code #zip-code').blur(function () {
					checkZipCountry($('input[name="city"]'), $('input[name="state"]'), $('.m-form-zip-code #zip-code'), $('.m-form-country #country'));
				});
				$('.m-form-country #country').change(function () {
					checkZipCountry($('input[name="city"]'), $('input[name="state"]'), $('.m-form-zip-code #zip-code'), $('.m-form-country #country'));
				});
			}
			$('#registration_show_country').click(function () {
				$('.m-form-zip-code label').html('Postal Code: <span title="This field is required." class="a-form-item-required">*</span>');
				$('.m-form-country').slideDown();
				$('.m-form-zip-code #zip-code').prop('type', 'text');
				$(this).hide();
				return false;
			});
		}
	}
});
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInVzZXIuanMiXSwibmFtZXMiOlsiJCIsIndpbmRvdyIsImpRdWVyeSIsInNob3dQYXNzd29yZCIsIiRzdWJtaXQiLCIkZmllbGQiLCJzaG93X3Bhc3MiLCJhZnRlciIsIiR0b2dnbGUiLCJvbiIsImUiLCJjaGVja2JveCIsImlzIiwiYXR0ciIsImNoZWNrUGFzc3dvcmRTdHJlbmd0aCIsIiRwYXNzd29yZCIsIiRzdHJlbmd0aE1ldGVyIiwiJHN0cmVuZ3RoVGV4dCIsIiRzdWJtaXRCdXR0b24iLCJibGFja2xpc3RBcnJheSIsInBhc3N3b3JkIiwidmFsIiwicmVtb3ZlQ2xhc3MiLCJjb25jYXQiLCJ3cCIsInBhc3N3b3JkU3RyZW5ndGgiLCJ1c2VySW5wdXRCbGFja2xpc3QiLCJzdHJlbmd0aCIsIm1ldGVyIiwiYWRkQ2xhc3MiLCJodG1sIiwicHdzTDEwbiIsImJhZCIsImdvb2QiLCJzdHJvbmciLCJtaXNtYXRjaCIsInNob3J0IiwiY2hlY2taaXBDb3VudHJ5IiwiY2l0eV9maWVsZCIsInN0YXRlX2ZpZWxkIiwiemlwX2ZpZWxkIiwiY291bnRyeV9maWVsZCIsImNvdW50cnkiLCJ6aXAiLCJsb2NhdGlvbiIsInppcF9jb2RlIiwiYWpheCIsInR5cGUiLCJ1cmwiLCJ1c2VyX2FjY291bnRfbWFuYWdlbWVudF9yZXN0Iiwic2l0ZV91cmwiLCJyZXN0X25hbWVzcGFjZSIsImRhdGEiLCJkYXRhVHlwZSIsInN1Y2Nlc3MiLCJyZXNwb25zZSIsInN0YXR1cyIsImNpdHkiLCJzdGF0ZSIsInRleHQiLCJkb2N1bWVudCIsInJlYWR5IiwibGVuZ3RoIiwiJGJlZm9yZSIsImV2ZW50IiwiaGlkZSIsInByb3AiLCJhcHBlbmQiLCJoYXNDbGFzcyIsInJlbW92ZSIsInNob3ciLCJibHVyIiwiY2hhbmdlIiwiY2xpY2siLCJzbGlkZURvd24iXSwibWFwcGluZ3MiOiI7O0FBQUEsSUFBSUEsSUFBSUMsT0FBT0MsTUFBZjs7QUFFQSxTQUFTQyxZQUFULEdBQXdCO0FBQ3ZC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aW5kb3cualF1ZXJ5O1xuXG5mdW5jdGlvbiBzaG93UGFzc3dvcmQoKSB7XG5cdC8vIENhY2hlIG91ciBqcXVlcnkgZWxlbWVudHNcblx0dmFyICRzdWJtaXQgPSAkKCcuYnRuLXN1Ym1pdCcpO1xuXHR2YXIgJGZpZWxkID0gJCgnLnBhc3N3b3JkLXNob3cnKTtcblx0dmFyIHNob3dfcGFzcyA9ICc8ZGl2IGNsYXNzPVwiYS1mb3JtLXNob3ctcGFzc3dvcmQgYS1mb3JtLWNhcHRpb25cIj48bGFiZWw+PGlucHV0IHR5cGU9XCJjaGVja2JveFwiIG5hbWU9XCJzaG93X3Bhc3N3b3JkXCIgaWQ9XCJzaG93LXBhc3N3b3JkLWNoZWNrYm94XCIgdmFsdWU9XCIxXCI+IFNob3cgcGFzc3dvcmQ8L2xhYmVsPjwvZGl2Pic7XG5cdC8vIEluamVjdCB0aGUgdG9nZ2xlIGJ1dHRvbiBpbnRvIHRoZSBwYWdlXG5cdCRmaWVsZC5hZnRlciggc2hvd19wYXNzICk7XG5cdC8vIENhY2hlIHRoZSB0b2dnbGUgYnV0dG9uXG5cdHZhciAkdG9nZ2xlID0gJCgnI3Nob3ctcGFzc3dvcmQtY2hlY2tib3gnKTtcblx0Ly8gVG9nZ2xlIHRoZSBmaWVsZCB0eXBlXG5cdCR0b2dnbGUub24oJ2NsaWNrJywgZnVuY3Rpb24oZSkge1xuXHRcdHZhciBjaGVja2JveCA9ICQodGhpcyk7XG5cdFx0aWYgKGNoZWNrYm94LmlzKCc6Y2hlY2tlZCcpKSB7XG5cdFx0XHQkZmllbGQuYXR0cigndHlwZScsICd0ZXh0Jyk7XG5cdFx0fSBlbHNlIHtcblx0XHRcdCRmaWVsZC5hdHRyKCd0eXBlJywgJ3Bhc3N3b3JkJyk7XG5cdFx0fVxuXHR9KTtcblx0Ly8gU2V0IHRoZSBmb3JtIGZpZWxkIGJhY2sgdG8gYSByZWd1bGFyIHBhc3N3b3JkIGVsZW1lbnRcblx0JHN1Ym1pdC5vbiggJ2NsaWNrJywgZnVuY3Rpb24oZSkge1xuXHRcdCRmaWVsZC5hdHRyKCd0eXBlJywgJ3Bhc3N3b3JkJyk7XG5cdH0pO1xufVxuXG5mdW5jdGlvbiBjaGVja1Bhc3N3b3JkU3RyZW5ndGgoICRwYXNzd29yZCwgJHN0cmVuZ3RoTWV0ZXIsICRzdHJlbmd0aFRleHQsICRzdWJtaXRCdXR0b24sIGJsYWNrbGlzdEFycmF5ICkge1xuICAgIHZhciBwYXNzd29yZCA9ICRwYXNzd29yZC52YWwoKTtcblxuICAgIC8vIFJlc2V0IHRoZSBmb3JtICYgbWV0ZXJcbiAgICAvLyRzdWJtaXRCdXR0b24uYXR0ciggJ2Rpc2FibGVkJywgJ2Rpc2FibGVkJyApO1xuICAgICRzdHJlbmd0aFRleHQucmVtb3ZlQ2xhc3MoICdzaG9ydCBiYWQgZ29vZCBzdHJvbmcnICk7XG5cbiAgICAvLyBFeHRlbmQgb3VyIGJsYWNrbGlzdCBhcnJheSB3aXRoIHRob3NlIGZyb20gdGhlIGlucHV0cyAmIHNpdGUgZGF0YVxuICAgIGJsYWNrbGlzdEFycmF5ID0gYmxhY2tsaXN0QXJyYXkuY29uY2F0KCB3cC5wYXNzd29yZFN0cmVuZ3RoLnVzZXJJbnB1dEJsYWNrbGlzdCgpIClcblxuICAgIC8vIEdldCB0aGUgcGFzc3dvcmQgc3RyZW5ndGhcbiAgICB2YXIgc3RyZW5ndGggPSB3cC5wYXNzd29yZFN0cmVuZ3RoLm1ldGVyKCBwYXNzd29yZCwgYmxhY2tsaXN0QXJyYXksIHBhc3N3b3JkICk7XG5cbiAgICAvLyBBZGQgdGhlIHN0cmVuZ3RoIG1ldGVyIHJlc3VsdHNcbiAgICBzd2l0Y2ggKCBzdHJlbmd0aCApIHtcbiAgICAgICAgY2FzZSAyOlxuICAgICAgICAgICAgJHN0cmVuZ3RoVGV4dC5hZGRDbGFzcyggJ2JhZCcgKS5odG1sKCAnU3RyZW5ndGg6IDxzdHJvbmc+JyArIHB3c0wxMG4uYmFkICsgJzwvc3Ryb25nPicgKTtcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICBjYXNlIDM6XG4gICAgICAgICAgICAkc3RyZW5ndGhUZXh0LmFkZENsYXNzKCAnZ29vZCcgKS5odG1sKCAnU3RyZW5ndGg6IDxzdHJvbmc+JyArIHB3c0wxMG4uZ29vZCArICc8L3N0cm9uZz4nICk7XG4gICAgICAgICAgICBicmVhaztcbiAgICAgICAgY2FzZSA0OlxuICAgICAgICAgICAgJHN0cmVuZ3RoVGV4dC5hZGRDbGFzcyggJ3N0cm9uZycgKS5odG1sKCAnU3RyZW5ndGg6IDxzdHJvbmc+JyArIHB3c0wxMG4uc3Ryb25nICsgJzwvc3Ryb25nPicgKTtcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICBjYXNlIDU6XG4gICAgICAgICAgICAkc3RyZW5ndGhUZXh0LmFkZENsYXNzKCAnc2hvcnQnICkuaHRtbCggJ1N0cmVuZ3RoOiA8c3Ryb25nPicgKyBwd3NMMTBuLm1pc21hdGNoICsgJzwvc3Ryb25nPicgKTtcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgJHN0cmVuZ3RoVGV4dC5hZGRDbGFzcyggJ3Nob3J0JyApLmh0bWwoICdTdHJlbmd0aDogPHN0cm9uZz4nICsgcHdzTDEwbi5zaG9ydCArICc8L3N0cm9uZz4nICk7XG4gICAgfVxuICAgICRzdHJlbmd0aE1ldGVyLnZhbChzdHJlbmd0aCk7XG5cbiAgICAvLyBPbmx5IGVuYWJsZSB0aGUgc3VibWl0IGJ1dHRvbiBpZiB0aGUgcGFzc3dvcmQgaXMgc3Ryb25nXG4gICAgLypcbiAgICBpZiAoIDQgPT09IHN0cmVuZ3RoICkge1xuICAgICAgICAkc3VibWl0QnV0dG9uLnJlbW92ZUF0dHIoICdkaXNhYmxlZCcgKTtcbiAgICB9Ki9cblxuICAgIHJldHVybiBzdHJlbmd0aDtcbn1cblxuZnVuY3Rpb24gY2hlY2taaXBDb3VudHJ5KGNpdHlfZmllbGQsIHN0YXRlX2ZpZWxkLCB6aXBfZmllbGQsIGNvdW50cnlfZmllbGQpIHtcblxuXHR2YXIgY291bnRyeSA9IGNvdW50cnlfZmllbGQudmFsKCk7XG5cdGlmIChjb3VudHJ5ID09ICcnKSB7XG5cdFx0Y291bnRyeSA9ICdVUyc7XG5cdFx0Y291bnRyeV9maWVsZC52YWwoY291bnRyeSk7XG5cdH1cblx0dmFyIHppcCA9IHppcF9maWVsZC52YWwoKTtcblxuXHRpZiAoemlwICE9PSAnJykge1xuXG5cdFx0dmFyIGxvY2F0aW9uID0ge1xuXHRcdFx0emlwX2NvZGU6IHppcCxcblx0XHRcdGNvdW50cnk6IGNvdW50cnlcblx0XHR9XG5cblx0XHRqUXVlcnkuYWpheCh7XG5cdCAgICAgICAgdHlwZTogJ0dFVCcsXG5cdCAgICAgICAgdXJsOiB1c2VyX2FjY291bnRfbWFuYWdlbWVudF9yZXN0LnNpdGVfdXJsICsgdXNlcl9hY2NvdW50X21hbmFnZW1lbnRfcmVzdC5yZXN0X25hbWVzcGFjZSArICcvY2hlY2stemlwJyxcblx0ICAgICAgICBkYXRhOiBsb2NhdGlvbixcblx0ICAgICAgICBkYXRhVHlwZTogJ2pzb24nLFxuXHQgICAgICAgIHN1Y2Nlc3M6IGZ1bmN0aW9uKHJlc3BvbnNlKSB7XG5cdCAgICAgICAgXHRpZiAocmVzcG9uc2Uuc3RhdHVzID09PSAnc3VjY2VzcycpIHtcblx0XHRcdFx0XHR2YXIgbG9jYXRpb24gPSAnJztcblx0XHRcdFx0XHRsb2NhdGlvbiArPSByZXNwb25zZS5jaXR5O1xuXHRcdFx0XHRcdCQoY2l0eV9maWVsZCkudmFsKHJlc3BvbnNlLmNpdHkpO1xuXHRcdFx0XHRcdGlmIChyZXNwb25zZS5jaXR5ICE9PSByZXNwb25zZS5zdGF0ZSkge1xuXHRcdFx0XHRcdFx0bG9jYXRpb24gKz0gJywgJyArIHJlc3BvbnNlLnN0YXRlO1xuXHRcdFx0XHRcdFx0JChzdGF0ZV9maWVsZCkudmFsKHJlc3BvbnNlLnN0YXRlKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0aWYgKGNvdW50cnkgIT09ICdVUycpIHtcblx0XHRcdFx0XHRcdGxvY2F0aW9uICs9ICcsICcgKyBjb3VudHJ5O1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0XHQkKCcubG9jYXRpb24gc21hbGwnKS50ZXh0KGxvY2F0aW9uKTtcblx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHQkKCcubG9jYXRpb24gc21hbGwnKS50ZXh0KCcnKTtcblx0XHRcdFx0fVxuXHQgICAgICAgIH1cblx0ICAgIH0pO1xuXHR9XG59XG5cbiQoZG9jdW1lbnQpLnJlYWR5KGZ1bmN0aW9uKCkge1xuXG5cdC8vIHN0YXJ0XG5cdGlmICgkKCcjcmgtbmFtZScpLmxlbmd0aCA+IDAgKSB7XG5cdFx0JCggJyNyaC1uYW1lJyApLnZhbCggJycgKTtcblx0fVxuXG5cdC8vIHNob3cgcGFzc3dvcmQgaWYgdXNlciBjbGlja3Ncblx0aWYgKCQoJy5wYXNzd29yZC1zaG93JykubGVuZ3RoID4gMCApIHtcblx0XHRzaG93UGFzc3dvcmQoKTtcblx0fVxuXHQvLyBjaGVja1Bhc3N3b3JkU3RyZW5ndGhcblx0aWYgKCQoJy5wYXNzd29yZC1zdHJlbmd0aC1jaGVjaycpLmxlbmd0aCA+IDAgKSB7XG5cdFx0dmFyICRiZWZvcmUgPSAkKCcuYS1mb3JtLXNob3ctcGFzc3dvcmQnKTtcblx0XHQkYmVmb3JlLmFmdGVyKCAkKCc8bWV0ZXIgbWF4PVwiNFwiIGlkPVwicGFzc3dvcmQtc3RyZW5ndGhcIj48ZGl2PjwvZGl2PjwvbWV0ZXI+PHAgaWQ9XCJwYXNzd29yZC1zdHJlbmd0aC10ZXh0XCI+PC9wPicpKTtcblx0ICAgICQoICdib2R5JyApLm9uKCAna2V5dXAnLCAnaW5wdXRbbmFtZT1wYXNzd29yZF0sIGlucHV0W25hbWU9bmV3X3Bhc3N3b3JkXScsXG5cdCAgICAgICAgZnVuY3Rpb24oIGV2ZW50ICkge1xuXHQgICAgICAgICAgICBjaGVja1Bhc3N3b3JkU3RyZW5ndGgoXG5cdCAgICAgICAgICAgICAgICAkKCdpbnB1dFtuYW1lPXBhc3N3b3JkXSwgaW5wdXRbbmFtZT1uZXdfcGFzc3dvcmRdJyksIC8vIFBhc3N3b3JkIGZpZWxkXG5cdCAgICAgICAgICAgICAgICAkKCcjcGFzc3dvcmQtc3RyZW5ndGgnKSwgICAgICAgICAgIC8vIFN0cmVuZ3RoIG1ldGVyXG5cdCAgICAgICAgICAgICAgICAkKCcjcGFzc3dvcmQtc3RyZW5ndGgtdGV4dCcpLCAgICAgIC8vIFN0cmVuZ3RoIHRleHQgaW5kaWNhdG9yXG5cdCAgICAgICAgICAgICAgICAkKCdpbnB1dFt0eXBlPXN1Ym1pdF0nKSwgICAgICAgICAgIC8vIFN1Ym1pdCBidXR0b25cblx0ICAgICAgICAgICAgICAgIFsnYmxhY2snLCAnbGlzdGVkJywgJ3dvcmQnXSAgICAgICAgLy8gQmxhY2tsaXN0ZWQgd29yZHNcblx0ICAgICAgICAgICAgKTtcblx0ICAgICAgICB9XG5cdCAgICApO1xuXHR9XG5cblx0Ly8gemlwL2NvdW50cnkgdGhpbmdcblx0dmFyIGNvdW50cnkgPSAkKCcubS1mb3JtLWNvdW50cnkgI2NvdW50cnknKS52YWwoKTtcblx0aWYgKCQoJy5tLWZvcm0temlwLWNvZGUgI3ppcC1jb2RlJykubGVuZ3RoKSB7XG5cdFx0aWYgKGNvdW50cnkgPT0gJycgfHwgY291bnRyeSA9PSAnVVMnKSB7XG5cdFx0XHQkKCcubS1mb3JtLWNvdW50cnknKS5oaWRlKCk7XG5cdFx0XHQkKCcubS1mb3JtLXppcC1jb2RlICN6aXAtY29kZScpLnByb3AoJ3R5cGUnLCAndGVsJyk7XG5cdFx0XHQkKCcubS1mb3JtLXppcC1jb2RlJykuYXBwZW5kKCc8ZGl2IGNsYXNzPVwiYS1mb3JtLWNhcHRpb24gbG9jYXRpb25cIj48c21hbGw+PC9zbWFsbD48L2Rpdj48ZGl2IGNsYXNzPVwiYS1mb3JtLWNhcHRpb24gc2hvdy1jb3VudHJ5XCI+PGEgaHJlZj1cIiNcIiBpZD1cInJlZ2lzdHJhdGlvbl9zaG93X2NvdW50cnlcIj48c21hbGw+Tm90IGluIHRoZSBVUz88L3NtYWxsPjwvYT48L2Rpdj4nKTtcblx0XHR9IGVsc2UgaWYgKCQoJy5tLWZvcm0tY291bnRyeSAjY291bnRyeScpLmxlbmd0aCA+IDApIHtcblx0XHRcdCQoJy5tLWZvcm0temlwLWNvZGUgbGFiZWwnKS5odG1sKCdQb3N0YWwgQ29kZTogPHNwYW4gdGl0bGU9XCJUaGlzIGZpZWxkIGlzIHJlcXVpcmVkLlwiIGNsYXNzPVwiYS1mb3JtLWl0ZW0tcmVxdWlyZWRcIj4qPC9zcGFuPicpO1xuXHRcdFx0JCgnLm0tZm9ybS16aXAtY29kZSAjemlwLWNvZGUnKS5wcm9wKCd0eXBlJywgJ3RleHQnKTtcblx0XHR9XG5cdFx0aWYgKCQoJy5tLWZvcm0tY291bnRyeSAjY291bnRyeScpLmxlbmd0aCA+IDApIHtcblx0XHRcdGlmICgkKCdzZWxlY3QsIGlucHV0JywgJy5tLWZvcm0tY291bnRyeScpLmhhc0NsYXNzKCdub3QtaW4tdXMnKSkge1xuXHRcdFx0XHQkKCcuc2hvdy1jb3VudHJ5JywgJy5tLWZvcm0temlwLWNvZGUnKS5yZW1vdmUoKTtcblx0XHRcdFx0JCgnLm0tZm9ybS1jb3VudHJ5Jykuc2hvdygpO1xuXHRcdFx0XHQkKCcubS1mb3JtLXppcC1jb2RlICN6aXAtY29kZScpLnByb3AoJ3R5cGUnLCAndGV4dCcpO1xuXHRcdFx0XHQkKCcubS1mb3JtLXppcC1jb2RlIGxhYmVsJykuaHRtbCgnUG9zdGFsIENvZGU6IDxzcGFuIHRpdGxlPVwiVGhpcyBmaWVsZCBpcyByZXF1aXJlZC5cIiBjbGFzcz1cImEtZm9ybS1pdGVtLXJlcXVpcmVkXCI+Kjwvc3Bhbj4nKTtcblx0XHRcdH1cblx0XHRcdGlmICgkKCcubS1mb3JtLWNpdHkgI2NpdHknKS5sZW5ndGggPT0gMCAmJiAkKCcubS1mb3JtLXN0YXRlICNzdGF0ZScpLmxlbmd0aCA9PSAwKSB7XG5cdFx0XHRcdCQoJy5tLWZvcm0tY291bnRyeSAjY291bnRyeSwgLm0tZm9ybS16aXAtY29kZSAjemlwLWNvZGUnKS5ibHVyKGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdGNoZWNrWmlwQ291bnRyeSgkKCdpbnB1dFtuYW1lPVwiY2l0eVwiXScpLCAkKCdpbnB1dFtuYW1lPVwic3RhdGVcIl0nKSwgJCgnLm0tZm9ybS16aXAtY29kZSAjemlwLWNvZGUnKSwgJCgnLm0tZm9ybS1jb3VudHJ5ICNjb3VudHJ5JykpO1xuXHRcdFx0XHR9KTtcblx0XHRcdFx0JCgnLm0tZm9ybS1jb3VudHJ5ICNjb3VudHJ5JykuY2hhbmdlKGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdGNoZWNrWmlwQ291bnRyeSgkKCdpbnB1dFtuYW1lPVwiY2l0eVwiXScpLCAkKCdpbnB1dFtuYW1lPVwic3RhdGVcIl0nKSwgJCgnLm0tZm9ybS16aXAtY29kZSAjemlwLWNvZGUnKSwgJCgnLm0tZm9ybS1jb3VudHJ5ICNjb3VudHJ5JykpO1xuXHRcdFx0XHR9KTtcblx0XHRcdH1cblx0XHRcdCQoJyNyZWdpc3RyYXRpb25fc2hvd19jb3VudHJ5JykuY2xpY2soZnVuY3Rpb24oKSB7XG5cdFx0XHRcdCQoJy5tLWZvcm0temlwLWNvZGUgbGFiZWwnKS5odG1sKCdQb3N0YWwgQ29kZTogPHNwYW4gdGl0bGU9XCJUaGlzIGZpZWxkIGlzIHJlcXVpcmVkLlwiIGNsYXNzPVwiYS1mb3JtLWl0ZW0tcmVxdWlyZWRcIj4qPC9zcGFuPicpO1xuXHRcdFx0XHQkKCcubS1mb3JtLWNvdW50cnknKS5zbGlkZURvd24oKTtcblx0XHRcdFx0JCgnLm0tZm9ybS16aXAtY29kZSAjemlwLWNvZGUnKS5wcm9wKCd0eXBlJywgJ3RleHQnKTtcblx0XHRcdFx0JCh0aGlzKS5oaWRlKCk7XG5cdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdH0pO1xuXHRcdH1cblx0fVxuXG59KTtcbiJdfQ==
