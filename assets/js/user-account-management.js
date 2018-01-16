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
		$('.m-form-country #country, .m-form-zip-code #zip-code').blur(function () {
			checkZipCountry($('input[name="city"]'), $('input[name="state"]'), $('.m-form-zip-code #zip-code'), $('.m-form-country #country'));
		});
		$('.m-form-country #country').change(function () {
			checkZipCountry($('input[name="city"]'), $('input[name="state"]'), $('.m-form-zip-code #zip-code'), $('.m-form-country #country'));
		});
		$('#registration_show_country').click(function () {
			$('.m-form-zip-code label').html('Postal Code: <span title="This field is required." class="a-form-item-required">*</span>');
			$('.m-form-country').slideDown();
			$('.m-form-zip-code #zip-code').prop('type', 'text');
			$(this).hide();
			return false;
		});
	}
});
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInVzZXIuanMiXSwibmFtZXMiOlsiJCIsIndpbmRvdyIsImpRdWVyeSIsInNob3dQYXNzd29yZCIsIiRzdWJtaXQiLCIkZmllbGQiLCJzaG93X3Bhc3MiLCJhZnRlciIsIiR0b2dnbGUiLCJvbiIsImUiLCJjaGVja2JveCIsImlzIiwiYXR0ciIsImNoZWNrUGFzc3dvcmRTdHJlbmd0aCIsIiRwYXNzd29yZCIsIiRzdHJlbmd0aE1ldGVyIiwiJHN0cmVuZ3RoVGV4dCIsIiRzdWJtaXRCdXR0b24iLCJibGFja2xpc3RBcnJheSIsInBhc3N3b3JkIiwidmFsIiwicmVtb3ZlQ2xhc3MiLCJjb25jYXQiLCJ3cCIsInBhc3N3b3JkU3RyZW5ndGgiLCJ1c2VySW5wdXRCbGFja2xpc3QiLCJzdHJlbmd0aCIsIm1ldGVyIiwiYWRkQ2xhc3MiLCJodG1sIiwicHdzTDEwbiIsImJhZCIsImdvb2QiLCJzdHJvbmciLCJtaXNtYXRjaCIsInNob3J0IiwiY2hlY2taaXBDb3VudHJ5IiwiY2l0eV9maWVsZCIsInN0YXRlX2ZpZWxkIiwiemlwX2ZpZWxkIiwiY291bnRyeV9maWVsZCIsImNvdW50cnkiLCJ6aXAiLCJsb2NhdGlvbiIsInppcF9jb2RlIiwiYWpheCIsInR5cGUiLCJ1cmwiLCJ1c2VyX2FjY291bnRfbWFuYWdlbWVudF9yZXN0Iiwic2l0ZV91cmwiLCJyZXN0X25hbWVzcGFjZSIsImRhdGEiLCJkYXRhVHlwZSIsInN1Y2Nlc3MiLCJyZXNwb25zZSIsInN0YXR1cyIsImNpdHkiLCJzdGF0ZSIsInRleHQiLCJkb2N1bWVudCIsInJlYWR5IiwibGVuZ3RoIiwiJGJlZm9yZSIsImV2ZW50IiwiaGlkZSIsInByb3AiLCJhcHBlbmQiLCJoYXNDbGFzcyIsInJlbW92ZSIsInNob3ciLCJibHVyIiwiY2hhbmdlIiwiY2xpY2siLCJzbGlkZURvd24iXSwibWFwcGluZ3MiOiI7O0FBQUEsSUFBSUEsSUFBSUMsT0FBT0MsTUFBZjs7QUFFQSxTQUFTQyxZQUFULEdBQXdCO0FBQ3ZC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QTdDO0FBQ0FsRSxLQUFFLHdCQUFGLEVBQTRCOEIsSUFBNUIsQ0FBaUMsMEZBQWpDO0FBQ0E7QUFDRDlCLElBQUUsc0RBQUYsRUFBMER1RSxJQUExRCxDQUErRCxZQUFXO0FBQ3pFbEMsbUJBQWdCckMsRUFBRSxvQkFBRixDQUFoQixFQUF5Q0EsRUFBRSxxQkFBRixDQUF6QyxFQUFtRUEsRUFBRSw0QkFBRixDQUFuRSxFQUFvR0EsRUFBRSwwQkFBRixDQUFwRztBQUNBLEdBRkQ7QUFHQUEsSUFBRSwwQkFBRixFQUE4QndFLE1BQTlCLENBQXFDLFlBQVc7QUFDL0NuQyxtQkFBZ0JyQyxFQUFFLG9CQUFGLENBQWhCLEVBQXlDQSxFQUFFLHFCQUFGLENBQXpDLEVBQW1FQSxFQUFFLDRCQUFGLENBQW5FLEVBQW9HQSxFQUFFLDBCQUFGLENBQXBHO0FBQ0EsR0FGRDtBQUdBQSxJQUFFLDRCQUFGLEVBQWdDeUUsS0FBaEMsQ0FBc0MsWUFBVztBQUNoRHpFLEtBQUUsd0JBQUYsRUFBNEI4QixJQUE1QixDQUFpQywwRkFBakM7QUFDQTlCLEtBQUUsaUJBQUYsRUFBcUIwRSxTQUFyQjtBQUNBMUUsS0FBRSw0QkFBRixFQUFnQ2tFLElBQWhDLENBQXFDLE1BQXJDLEVBQTZDLE1BQTdDO0FBQ0FsRSxLQUFFLElBQUYsRUFBUWlFLElBQVI7QUFDQSxVQUFPLEtBQVA7QUFDQSxHQU5EO0FBT0E7QUFFRCxDQTVERCIsImZpbGUiOiJ1c2VyLWFjY291bnQtbWFuYWdlbWVudC5qcyIsInNvdXJjZXNDb250ZW50IjpbInZhciAkID0gd2luZG93LmpRdWVyeTtcblxuZnVuY3Rpb24gc2hvd1Bhc3N3b3JkKCkge1xuXHQvLyBDYWNoZSBvdXIganF1ZXJ5IGVsZW1lbnRzXG5cdHZhciAkc3VibWl0ID0gJCgnLmJ0bi1zdWJtaXQnKTtcblx0dmFyICRmaWVsZCA9ICQoJy5wYXNzd29yZC1zaG93Jyk7XG5cdHZhciBzaG93X3Bhc3MgPSAnPGRpdiBjbGFzcz1cImEtZm9ybS1zaG93LXBhc3N3b3JkIGEtZm9ybS1jYXB0aW9uXCI+PGxhYmVsPjxpbnB1dCB0eXBlPVwiY2hlY2tib3hcIiBuYW1lPVwic2hvd19wYXNzd29yZFwiIGlkPVwic2hvdy1wYXNzd29yZC1jaGVja2JveFwiIHZhbHVlPVwiMVwiPiBTaG93IHBhc3N3b3JkPC9sYWJlbD48L2Rpdj4nO1xuXHQvLyBJbmplY3QgdGhlIHRvZ2dsZSBidXR0b24gaW50byB0aGUgcGFnZVxuXHQkZmllbGQuYWZ0ZXIoIHNob3dfcGFzcyApO1xuXHQvLyBDYWNoZSB0aGUgdG9nZ2xlIGJ1dHRvblxuXHR2YXIgJHRvZ2dsZSA9ICQoJyNzaG93LXBhc3N3b3JkLWNoZWNrYm94Jyk7XG5cdC8vIFRvZ2dsZSB0aGUgZmllbGQgdHlwZVxuXHQkdG9nZ2xlLm9uKCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcblx0XHR2YXIgY2hlY2tib3ggPSAkKHRoaXMpO1xuXHRcdGlmIChjaGVja2JveC5pcygnOmNoZWNrZWQnKSkge1xuXHRcdFx0JGZpZWxkLmF0dHIoJ3R5cGUnLCAndGV4dCcpO1xuXHRcdH0gZWxzZSB7XG5cdFx0XHQkZmllbGQuYXR0cigndHlwZScsICdwYXNzd29yZCcpO1xuXHRcdH1cblx0fSk7XG5cdC8vIFNldCB0aGUgZm9ybSBmaWVsZCBiYWNrIHRvIGEgcmVndWxhciBwYXNzd29yZCBlbGVtZW50XG5cdCRzdWJtaXQub24oICdjbGljaycsIGZ1bmN0aW9uKGUpIHtcblx0XHQkZmllbGQuYXR0cigndHlwZScsICdwYXNzd29yZCcpO1xuXHR9KTtcbn1cblxuZnVuY3Rpb24gY2hlY2tQYXNzd29yZFN0cmVuZ3RoKCAkcGFzc3dvcmQsICRzdHJlbmd0aE1ldGVyLCAkc3RyZW5ndGhUZXh0LCAkc3VibWl0QnV0dG9uLCBibGFja2xpc3RBcnJheSApIHtcbiAgICB2YXIgcGFzc3dvcmQgPSAkcGFzc3dvcmQudmFsKCk7XG5cbiAgICAvLyBSZXNldCB0aGUgZm9ybSAmIG1ldGVyXG4gICAgLy8kc3VibWl0QnV0dG9uLmF0dHIoICdkaXNhYmxlZCcsICdkaXNhYmxlZCcgKTtcbiAgICAkc3RyZW5ndGhUZXh0LnJlbW92ZUNsYXNzKCAnc2hvcnQgYmFkIGdvb2Qgc3Ryb25nJyApO1xuXG4gICAgLy8gRXh0ZW5kIG91ciBibGFja2xpc3QgYXJyYXkgd2l0aCB0aG9zZSBmcm9tIHRoZSBpbnB1dHMgJiBzaXRlIGRhdGFcbiAgICBibGFja2xpc3RBcnJheSA9IGJsYWNrbGlzdEFycmF5LmNvbmNhdCggd3AucGFzc3dvcmRTdHJlbmd0aC51c2VySW5wdXRCbGFja2xpc3QoKSApXG5cbiAgICAvLyBHZXQgdGhlIHBhc3N3b3JkIHN0cmVuZ3RoXG4gICAgdmFyIHN0cmVuZ3RoID0gd3AucGFzc3dvcmRTdHJlbmd0aC5tZXRlciggcGFzc3dvcmQsIGJsYWNrbGlzdEFycmF5LCBwYXNzd29yZCApO1xuXG4gICAgLy8gQWRkIHRoZSBzdHJlbmd0aCBtZXRlciByZXN1bHRzXG4gICAgc3dpdGNoICggc3RyZW5ndGggKSB7XG4gICAgICAgIGNhc2UgMjpcbiAgICAgICAgICAgICRzdHJlbmd0aFRleHQuYWRkQ2xhc3MoICdiYWQnICkuaHRtbCggJ1N0cmVuZ3RoOiA8c3Ryb25nPicgKyBwd3NMMTBuLmJhZCArICc8L3N0cm9uZz4nICk7XG4gICAgICAgICAgICBicmVhaztcbiAgICAgICAgY2FzZSAzOlxuICAgICAgICAgICAgJHN0cmVuZ3RoVGV4dC5hZGRDbGFzcyggJ2dvb2QnICkuaHRtbCggJ1N0cmVuZ3RoOiA8c3Ryb25nPicgKyBwd3NMMTBuLmdvb2QgKyAnPC9zdHJvbmc+JyApO1xuICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgIGNhc2UgNDpcbiAgICAgICAgICAgICRzdHJlbmd0aFRleHQuYWRkQ2xhc3MoICdzdHJvbmcnICkuaHRtbCggJ1N0cmVuZ3RoOiA8c3Ryb25nPicgKyBwd3NMMTBuLnN0cm9uZyArICc8L3N0cm9uZz4nICk7XG4gICAgICAgICAgICBicmVhaztcbiAgICAgICAgY2FzZSA1OlxuICAgICAgICAgICAgJHN0cmVuZ3RoVGV4dC5hZGRDbGFzcyggJ3Nob3J0JyApLmh0bWwoICdTdHJlbmd0aDogPHN0cm9uZz4nICsgcHdzTDEwbi5taXNtYXRjaCArICc8L3N0cm9uZz4nICk7XG4gICAgICAgICAgICBicmVhaztcbiAgICAgICAgZGVmYXVsdDpcbiAgICAgICAgICAgICRzdHJlbmd0aFRleHQuYWRkQ2xhc3MoICdzaG9ydCcgKS5odG1sKCAnU3RyZW5ndGg6IDxzdHJvbmc+JyArIHB3c0wxMG4uc2hvcnQgKyAnPC9zdHJvbmc+JyApO1xuICAgIH1cbiAgICAkc3RyZW5ndGhNZXRlci52YWwoc3RyZW5ndGgpO1xuXG4gICAgLy8gT25seSBlbmFibGUgdGhlIHN1Ym1pdCBidXR0b24gaWYgdGhlIHBhc3N3b3JkIGlzIHN0cm9uZ1xuICAgIC8qXG4gICAgaWYgKCA0ID09PSBzdHJlbmd0aCApIHtcbiAgICAgICAgJHN1Ym1pdEJ1dHRvbi5yZW1vdmVBdHRyKCAnZGlzYWJsZWQnICk7XG4gICAgfSovXG5cbiAgICByZXR1cm4gc3RyZW5ndGg7XG59XG5cbmZ1bmN0aW9uIGNoZWNrWmlwQ291bnRyeShjaXR5X2ZpZWxkLCBzdGF0ZV9maWVsZCwgemlwX2ZpZWxkLCBjb3VudHJ5X2ZpZWxkKSB7XG5cblx0dmFyIGNvdW50cnkgPSBjb3VudHJ5X2ZpZWxkLnZhbCgpO1xuXHRpZiAoY291bnRyeSA9PSAnJykge1xuXHRcdGNvdW50cnkgPSAnVVMnO1xuXHRcdGNvdW50cnlfZmllbGQudmFsKGNvdW50cnkpO1xuXHR9XG5cdHZhciB6aXAgPSB6aXBfZmllbGQudmFsKCk7XG5cblx0aWYgKHppcCAhPT0gJycpIHtcblxuXHRcdHZhciBsb2NhdGlvbiA9IHtcblx0XHRcdHppcF9jb2RlOiB6aXAsXG5cdFx0XHRjb3VudHJ5OiBjb3VudHJ5XG5cdFx0fVxuXG5cdFx0alF1ZXJ5LmFqYXgoe1xuXHQgICAgICAgIHR5cGU6ICdHRVQnLFxuXHQgICAgICAgIHVybDogdXNlcl9hY2NvdW50X21hbmFnZW1lbnRfcmVzdC5zaXRlX3VybCArIHVzZXJfYWNjb3VudF9tYW5hZ2VtZW50X3Jlc3QucmVzdF9uYW1lc3BhY2UgKyAnL2NoZWNrLXppcCcsXG5cdCAgICAgICAgZGF0YTogbG9jYXRpb24sXG5cdCAgICAgICAgZGF0YVR5cGU6ICdqc29uJyxcblx0ICAgICAgICBzdWNjZXNzOiBmdW5jdGlvbihyZXNwb25zZSkge1xuXHQgICAgICAgIFx0aWYgKHJlc3BvbnNlLnN0YXR1cyA9PT0gJ3N1Y2Nlc3MnKSB7XG5cdFx0XHRcdFx0dmFyIGxvY2F0aW9uID0gJyc7XG5cdFx0XHRcdFx0bG9jYXRpb24gKz0gcmVzcG9uc2UuY2l0eTtcblx0XHRcdFx0XHQkKGNpdHlfZmllbGQpLnZhbChyZXNwb25zZS5jaXR5KTtcblx0XHRcdFx0XHRpZiAocmVzcG9uc2UuY2l0eSAhPT0gcmVzcG9uc2Uuc3RhdGUpIHtcblx0XHRcdFx0XHRcdGxvY2F0aW9uICs9ICcsICcgKyByZXNwb25zZS5zdGF0ZTtcblx0XHRcdFx0XHRcdCQoc3RhdGVfZmllbGQpLnZhbChyZXNwb25zZS5zdGF0ZSk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHRcdGlmIChjb3VudHJ5ICE9PSAnVVMnKSB7XG5cdFx0XHRcdFx0XHRsb2NhdGlvbiArPSAnLCAnICsgY291bnRyeTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0JCgnLmxvY2F0aW9uIHNtYWxsJykudGV4dChsb2NhdGlvbik7XG5cdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0JCgnLmxvY2F0aW9uIHNtYWxsJykudGV4dCgnJyk7XG5cdFx0XHRcdH1cblx0ICAgICAgICB9XG5cdCAgICB9KTtcblx0fVxufVxuXG4kKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbigpIHtcblxuXHQvLyBzdGFydFxuXHRpZiAoJCgnI3JoLW5hbWUnKS5sZW5ndGggPiAwICkge1xuXHRcdCQoICcjcmgtbmFtZScgKS52YWwoICcnICk7XG5cdH1cblxuXHQvLyBzaG93IHBhc3N3b3JkIGlmIHVzZXIgY2xpY2tzXG5cdGlmICgkKCcucGFzc3dvcmQtc2hvdycpLmxlbmd0aCA+IDAgKSB7XG5cdFx0c2hvd1Bhc3N3b3JkKCk7XG5cdH1cblx0Ly8gY2hlY2tQYXNzd29yZFN0cmVuZ3RoXG5cdGlmICgkKCcucGFzc3dvcmQtc3RyZW5ndGgtY2hlY2snKS5sZW5ndGggPiAwICkge1xuXHRcdHZhciAkYmVmb3JlID0gJCgnLmEtZm9ybS1zaG93LXBhc3N3b3JkJyk7XG5cdFx0JGJlZm9yZS5hZnRlciggJCgnPG1ldGVyIG1heD1cIjRcIiBpZD1cInBhc3N3b3JkLXN0cmVuZ3RoXCI+PGRpdj48L2Rpdj48L21ldGVyPjxwIGlkPVwicGFzc3dvcmQtc3RyZW5ndGgtdGV4dFwiPjwvcD4nKSk7XG5cdCAgICAkKCAnYm9keScgKS5vbiggJ2tleXVwJywgJ2lucHV0W25hbWU9cGFzc3dvcmRdLCBpbnB1dFtuYW1lPW5ld19wYXNzd29yZF0nLFxuXHQgICAgICAgIGZ1bmN0aW9uKCBldmVudCApIHtcblx0ICAgICAgICAgICAgY2hlY2tQYXNzd29yZFN0cmVuZ3RoKFxuXHQgICAgICAgICAgICAgICAgJCgnaW5wdXRbbmFtZT1wYXNzd29yZF0sIGlucHV0W25hbWU9bmV3X3Bhc3N3b3JkXScpLCAvLyBQYXNzd29yZCBmaWVsZFxuXHQgICAgICAgICAgICAgICAgJCgnI3Bhc3N3b3JkLXN0cmVuZ3RoJyksICAgICAgICAgICAvLyBTdHJlbmd0aCBtZXRlclxuXHQgICAgICAgICAgICAgICAgJCgnI3Bhc3N3b3JkLXN0cmVuZ3RoLXRleHQnKSwgICAgICAvLyBTdHJlbmd0aCB0ZXh0IGluZGljYXRvclxuXHQgICAgICAgICAgICAgICAgJCgnaW5wdXRbdHlwZT1zdWJtaXRdJyksICAgICAgICAgICAvLyBTdWJtaXQgYnV0dG9uXG5cdCAgICAgICAgICAgICAgICBbJ2JsYWNrJywgJ2xpc3RlZCcsICd3b3JkJ10gICAgICAgIC8vIEJsYWNrbGlzdGVkIHdvcmRzXG5cdCAgICAgICAgICAgICk7XG5cdCAgICAgICAgfVxuXHQgICAgKTtcblx0fVxuXG5cdC8vIHppcC9jb3VudHJ5IHRoaW5nXG5cdHZhciBjb3VudHJ5ID0gJCgnLm0tZm9ybS1jb3VudHJ5ICNjb3VudHJ5JykudmFsKCk7XG5cdGlmICgkKCcubS1mb3JtLXppcC1jb2RlICN6aXAtY29kZScpLmxlbmd0aCkge1xuXHRcdGlmIChjb3VudHJ5ID09ICcnIHx8IGNvdW50cnkgPT0gJ1VTJykge1xuXHRcdFx0JCgnLm0tZm9ybS1jb3VudHJ5JykuaGlkZSgpO1xuXHRcdFx0JCgnLm0tZm9ybS16aXAtY29kZSAjemlwLWNvZGUnKS5wcm9wKCd0eXBlJywgJ3RlbCcpO1xuXHRcdFx0JCgnLm0tZm9ybS16aXAtY29kZScpLmFwcGVuZCgnPGRpdiBjbGFzcz1cImEtZm9ybS1jYXB0aW9uIGxvY2F0aW9uXCI+PHNtYWxsPjwvc21hbGw+PC9kaXY+PGRpdiBjbGFzcz1cImEtZm9ybS1jYXB0aW9uIHNob3ctY291bnRyeVwiPjxhIGhyZWY9XCIjXCIgaWQ9XCJyZWdpc3RyYXRpb25fc2hvd19jb3VudHJ5XCI+PHNtYWxsPk5vdCBpbiB0aGUgVVM/PC9zbWFsbD48L2E+PC9kaXY+Jyk7XG5cdFx0fSBlbHNlIHtcblx0XHRcdCQoJy5tLWZvcm0temlwLWNvZGUgbGFiZWwnKS5odG1sKCdQb3N0YWwgQ29kZTogPHNwYW4gdGl0bGU9XCJUaGlzIGZpZWxkIGlzIHJlcXVpcmVkLlwiIGNsYXNzPVwiYS1mb3JtLWl0ZW0tcmVxdWlyZWRcIj4qPC9zcGFuPicpO1xuXHRcdFx0JCgnLm0tZm9ybS16aXAtY29kZSAjemlwLWNvZGUnKS5wcm9wKCd0eXBlJywgJ3RleHQnKTtcblx0XHR9XG5cdFx0aWYgKCQoJ3NlbGVjdCwgaW5wdXQnLCAnLm0tZm9ybS1jb3VudHJ5JykuaGFzQ2xhc3MoJ25vdC1pbi11cycpKSB7XG5cdFx0XHQkKCcuc2hvdy1jb3VudHJ5JywgJy5tLWZvcm0temlwLWNvZGUnKS5yZW1vdmUoKTtcblx0XHRcdCQoJy5tLWZvcm0tY291bnRyeScpLnNob3coKTtcblx0XHRcdCQoJy5tLWZvcm0temlwLWNvZGUgI3ppcC1jb2RlJykucHJvcCgndHlwZScsICd0ZXh0Jyk7XG5cdFx0XHQkKCcubS1mb3JtLXppcC1jb2RlIGxhYmVsJykuaHRtbCgnUG9zdGFsIENvZGU6IDxzcGFuIHRpdGxlPVwiVGhpcyBmaWVsZCBpcyByZXF1aXJlZC5cIiBjbGFzcz1cImEtZm9ybS1pdGVtLXJlcXVpcmVkXCI+Kjwvc3Bhbj4nKTtcblx0XHR9XG5cdFx0JCgnLm0tZm9ybS1jb3VudHJ5ICNjb3VudHJ5LCAubS1mb3JtLXppcC1jb2RlICN6aXAtY29kZScpLmJsdXIoZnVuY3Rpb24oKSB7XG5cdFx0XHRjaGVja1ppcENvdW50cnkoJCgnaW5wdXRbbmFtZT1cImNpdHlcIl0nKSwgJCgnaW5wdXRbbmFtZT1cInN0YXRlXCJdJyksICQoJy5tLWZvcm0temlwLWNvZGUgI3ppcC1jb2RlJyksICQoJy5tLWZvcm0tY291bnRyeSAjY291bnRyeScpKTtcblx0XHR9KTtcblx0XHQkKCcubS1mb3JtLWNvdW50cnkgI2NvdW50cnknKS5jaGFuZ2UoZnVuY3Rpb24oKSB7XG5cdFx0XHRjaGVja1ppcENvdW50cnkoJCgnaW5wdXRbbmFtZT1cImNpdHlcIl0nKSwgJCgnaW5wdXRbbmFtZT1cInN0YXRlXCJdJyksICQoJy5tLWZvcm0temlwLWNvZGUgI3ppcC1jb2RlJyksICQoJy5tLWZvcm0tY291bnRyeSAjY291bnRyeScpKTtcblx0XHR9KTtcblx0XHQkKCcjcmVnaXN0cmF0aW9uX3Nob3dfY291bnRyeScpLmNsaWNrKGZ1bmN0aW9uKCkge1xuXHRcdFx0JCgnLm0tZm9ybS16aXAtY29kZSBsYWJlbCcpLmh0bWwoJ1Bvc3RhbCBDb2RlOiA8c3BhbiB0aXRsZT1cIlRoaXMgZmllbGQgaXMgcmVxdWlyZWQuXCIgY2xhc3M9XCJhLWZvcm0taXRlbS1yZXF1aXJlZFwiPio8L3NwYW4+Jyk7XG5cdFx0XHQkKCcubS1mb3JtLWNvdW50cnknKS5zbGlkZURvd24oKTtcblx0XHRcdCQoJy5tLWZvcm0temlwLWNvZGUgI3ppcC1jb2RlJykucHJvcCgndHlwZScsICd0ZXh0Jyk7XG5cdFx0XHQkKHRoaXMpLmhpZGUoKTtcblx0XHRcdHJldHVybiBmYWxzZTtcblx0XHR9KTtcblx0fVxuXG59KTtcbiJdfQ==
