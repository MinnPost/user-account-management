'use strict';

(function ($) {

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
})(jQuery);
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInVzZXIuanMiXSwibmFtZXMiOlsiJCIsInNob3dQYXNzd29yZCIsIiRzdWJtaXQiLCIkZmllbGQiLCJzaG93X3Bhc3MiLCJhZnRlciIsIiR0b2dnbGUiLCJvbiIsImUiLCJjaGVja2JveCIsImlzIiwiYXR0ciIsImNoZWNrUGFzc3dvcmRTdHJlbmd0aCIsIiRwYXNzd29yZCIsIiRzdHJlbmd0aE1ldGVyIiwiJHN0cmVuZ3RoVGV4dCIsIiRzdWJtaXRCdXR0b24iLCJibGFja2xpc3RBcnJheSIsInBhc3N3b3JkIiwidmFsIiwicmVtb3ZlQ2xhc3MiLCJjb25jYXQiLCJ3cCIsInBhc3N3b3JkU3RyZW5ndGgiLCJ1c2VySW5wdXRCbGFja2xpc3QiLCJzdHJlbmd0aCIsIm1ldGVyIiwiYWRkQ2xhc3MiLCJodG1sIiwicHdzTDEwbiIsImJhZCIsImdvb2QiLCJzdHJvbmciLCJtaXNtYXRjaCIsInNob3J0IiwiY2hlY2taaXBDb3VudHJ5IiwiY2l0eV9maWVsZCIsInN0YXRlX2ZpZWxkIiwiemlwX2ZpZWxkIiwiY291bnRyeV9maWVsZCIsImNvdW50cnkiLCJ6aXAiLCJsb2NhdGlvbiIsInppcF9jb2RlIiwialF1ZXJ5IiwiYWpheCIsInR5cGUiLCJ1cmwiLCJ1c2VyX2FjY291bnRfbWFuYWdlbWVudF9yZXN0Iiwic2l0ZV91cmwiLCJyZXN0X25hbWVzcGFjZSIsImRhdGEiLCJkYXRhVHlwZSIsInN1Y2Nlc3MiLCJyZXNwb25zZSIsInN0YXR1cyIsImNpdHkiLCJzdGF0ZSIsInRleHQiLCJkb2N1bWVudCIsInJlYWR5IiwibGVuZ3RoIiwiJGJlZm9yZSIsImV2ZW50IiwiaGlkZSIsInByb3AiLCJhcHBlbmQiLCJoYXNDbGFzcyIsInJlbW92ZSIsInNob3ciLCJibHVyIiwiY2hhbmdlIiwiY2xpY2siLCJzbGlkZURvd24iXSwibWFwcGluZ3MiOiI7O0FBQUEsQ0FBQyxVQUFTQSxDQUFULEVBQVc7O0FBRVgsVUFBU0MsWUFBVCxHQUF3QjtBQUN2QjtBQUNBLE1BQUlD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bnQiOlsiKGZ1bmN0aW9uKCQpe1xuXG5cdGZ1bmN0aW9uIHNob3dQYXNzd29yZCgpIHtcblx0XHQvLyBDYWNoZSBvdXIganF1ZXJ5IGVsZW1lbnRzXG5cdFx0dmFyICRzdWJtaXQgPSAkKCcuYnRuLXN1Ym1pdCcpO1xuXHRcdHZhciAkZmllbGQgPSAkKCcucGFzc3dvcmQtc2hvdycpO1xuXHRcdHZhciBzaG93X3Bhc3MgPSAnPGRpdiBjbGFzcz1cImEtZm9ybS1zaG93LXBhc3N3b3JkIGEtZm9ybS1jYXB0aW9uXCI+PGxhYmVsPjxpbnB1dCB0eXBlPVwiY2hlY2tib3hcIiBuYW1lPVwic2hvd19wYXNzd29yZFwiIGlkPVwic2hvdy1wYXNzd29yZC1jaGVja2JveFwiIHZhbHVlPVwiMVwiPiBTaG93IHBhc3N3b3JkPC9sYWJlbD48L2Rpdj4nO1xuXHRcdC8vIEluamVjdCB0aGUgdG9nZ2xlIGJ1dHRvbiBpbnRvIHRoZSBwYWdlXG5cdFx0JGZpZWxkLmFmdGVyKCBzaG93X3Bhc3MgKTtcblx0XHQvLyBDYWNoZSB0aGUgdG9nZ2xlIGJ1dHRvblxuXHRcdHZhciAkdG9nZ2xlID0gJCgnI3Nob3ctcGFzc3dvcmQtY2hlY2tib3gnKTtcblx0XHQvLyBUb2dnbGUgdGhlIGZpZWxkIHR5cGVcblx0XHQkdG9nZ2xlLm9uKCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcblx0XHRcdHZhciBjaGVja2JveCA9ICQodGhpcyk7XG5cdFx0XHRpZiAoY2hlY2tib3guaXMoJzpjaGVja2VkJykpIHtcblx0XHRcdFx0JGZpZWxkLmF0dHIoJ3R5cGUnLCAndGV4dCcpO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0JGZpZWxkLmF0dHIoJ3R5cGUnLCAncGFzc3dvcmQnKTtcblx0XHRcdH1cblx0XHR9KTtcblx0XHQvLyBTZXQgdGhlIGZvcm0gZmllbGQgYmFjayB0byBhIHJlZ3VsYXIgcGFzc3dvcmQgZWxlbWVudFxuXHRcdCRzdWJtaXQub24oICdjbGljaycsIGZ1bmN0aW9uKGUpIHtcblx0XHRcdCRmaWVsZC5hdHRyKCd0eXBlJywgJ3Bhc3N3b3JkJyk7XG5cdFx0fSk7XG5cdH1cblxuXHRmdW5jdGlvbiBjaGVja1Bhc3N3b3JkU3RyZW5ndGgoICRwYXNzd29yZCwgJHN0cmVuZ3RoTWV0ZXIsICRzdHJlbmd0aFRleHQsICRzdWJtaXRCdXR0b24sIGJsYWNrbGlzdEFycmF5ICkge1xuXHQgICAgdmFyIHBhc3N3b3JkID0gJHBhc3N3b3JkLnZhbCgpO1xuXG5cdCAgICAvLyBSZXNldCB0aGUgZm9ybSAmIG1ldGVyXG5cdCAgICAvLyRzdWJtaXRCdXR0b24uYXR0ciggJ2Rpc2FibGVkJywgJ2Rpc2FibGVkJyApO1xuXHQgICAgJHN0cmVuZ3RoVGV4dC5yZW1vdmVDbGFzcyggJ3Nob3J0IGJhZCBnb29kIHN0cm9uZycgKTtcblxuXHQgICAgLy8gRXh0ZW5kIG91ciBibGFja2xpc3QgYXJyYXkgd2l0aCB0aG9zZSBmcm9tIHRoZSBpbnB1dHMgJiBzaXRlIGRhdGFcblx0ICAgIGJsYWNrbGlzdEFycmF5ID0gYmxhY2tsaXN0QXJyYXkuY29uY2F0KCB3cC5wYXNzd29yZFN0cmVuZ3RoLnVzZXJJbnB1dEJsYWNrbGlzdCgpIClcblxuXHQgICAgLy8gR2V0IHRoZSBwYXNzd29yZCBzdHJlbmd0aFxuXHQgICAgdmFyIHN0cmVuZ3RoID0gd3AucGFzc3dvcmRTdHJlbmd0aC5tZXRlciggcGFzc3dvcmQsIGJsYWNrbGlzdEFycmF5LCBwYXNzd29yZCApO1xuXG5cdCAgICAvLyBBZGQgdGhlIHN0cmVuZ3RoIG1ldGVyIHJlc3VsdHNcblx0ICAgIHN3aXRjaCAoIHN0cmVuZ3RoICkge1xuXHQgICAgICAgIGNhc2UgMjpcblx0ICAgICAgICAgICAgJHN0cmVuZ3RoVGV4dC5hZGRDbGFzcyggJ2JhZCcgKS5odG1sKCAnU3RyZW5ndGg6IDxzdHJvbmc+JyArIHB3c0wxMG4uYmFkICsgJzwvc3Ryb25nPicgKTtcblx0ICAgICAgICAgICAgYnJlYWs7XG5cdCAgICAgICAgY2FzZSAzOlxuXHQgICAgICAgICAgICAkc3RyZW5ndGhUZXh0LmFkZENsYXNzKCAnZ29vZCcgKS5odG1sKCAnU3RyZW5ndGg6IDxzdHJvbmc+JyArIHB3c0wxMG4uZ29vZCArICc8L3N0cm9uZz4nICk7XG5cdCAgICAgICAgICAgIGJyZWFrO1xuXHQgICAgICAgIGNhc2UgNDpcblx0ICAgICAgICAgICAgJHN0cmVuZ3RoVGV4dC5hZGRDbGFzcyggJ3N0cm9uZycgKS5odG1sKCAnU3RyZW5ndGg6IDxzdHJvbmc+JyArIHB3c0wxMG4uc3Ryb25nICsgJzwvc3Ryb25nPicgKTtcblx0ICAgICAgICAgICAgYnJlYWs7XG5cdCAgICAgICAgY2FzZSA1OlxuXHQgICAgICAgICAgICAkc3RyZW5ndGhUZXh0LmFkZENsYXNzKCAnc2hvcnQnICkuaHRtbCggJ1N0cmVuZ3RoOiA8c3Ryb25nPicgKyBwd3NMMTBuLm1pc21hdGNoICsgJzwvc3Ryb25nPicgKTtcblx0ICAgICAgICAgICAgYnJlYWs7XG5cdCAgICAgICAgZGVmYXVsdDpcblx0ICAgICAgICAgICAgJHN0cmVuZ3RoVGV4dC5hZGRDbGFzcyggJ3Nob3J0JyApLmh0bWwoICdTdHJlbmd0aDogPHN0cm9uZz4nICsgcHdzTDEwbi5zaG9ydCArICc8L3N0cm9uZz4nICk7XG5cdCAgICB9XG5cdCAgICAkc3RyZW5ndGhNZXRlci52YWwoc3RyZW5ndGgpO1xuXG5cdCAgICAvLyBPbmx5IGVuYWJsZSB0aGUgc3VibWl0IGJ1dHRvbiBpZiB0aGUgcGFzc3dvcmQgaXMgc3Ryb25nXG5cdCAgICAvKlxuXHQgICAgaWYgKCA0ID09PSBzdHJlbmd0aCApIHtcblx0ICAgICAgICAkc3VibWl0QnV0dG9uLnJlbW92ZUF0dHIoICdkaXNhYmxlZCcgKTtcblx0ICAgIH0qL1xuXG5cdCAgICByZXR1cm4gc3RyZW5ndGg7XG5cdH1cblxuXHRmdW5jdGlvbiBjaGVja1ppcENvdW50cnkoY2l0eV9maWVsZCwgc3RhdGVfZmllbGQsIHppcF9maWVsZCwgY291bnRyeV9maWVsZCkge1xuXG5cdFx0dmFyIGNvdW50cnkgPSBjb3VudHJ5X2ZpZWxkLnZhbCgpO1xuXHRcdGlmIChjb3VudHJ5ID09ICcnKSB7XG5cdFx0XHRjb3VudHJ5ID0gJ1VTJztcblx0XHRcdGNvdW50cnlfZmllbGQudmFsKGNvdW50cnkpO1xuXHRcdH1cblx0XHR2YXIgemlwID0gemlwX2ZpZWxkLnZhbCgpO1xuXG5cdFx0aWYgKHppcCAhPT0gJycpIHtcblxuXHRcdFx0dmFyIGxvY2F0aW9uID0ge1xuXHRcdFx0XHR6aXBfY29kZTogemlwLFxuXHRcdFx0XHRjb3VudHJ5OiBjb3VudHJ5XG5cdFx0XHR9XG5cblx0XHRcdGpRdWVyeS5hamF4KHtcblx0XHQgICAgICAgIHR5cGU6ICdHRVQnLFxuXHRcdCAgICAgICAgdXJsOiB1c2VyX2FjY291bnRfbWFuYWdlbWVudF9yZXN0LnNpdGVfdXJsICsgdXNlcl9hY2NvdW50X21hbmFnZW1lbnRfcmVzdC5yZXN0X25hbWVzcGFjZSArICcvY2hlY2stemlwJyxcblx0XHQgICAgICAgIGRhdGE6IGxvY2F0aW9uLFxuXHRcdCAgICAgICAgZGF0YVR5cGU6ICdqc29uJyxcblx0XHQgICAgICAgIHN1Y2Nlc3M6IGZ1bmN0aW9uKHJlc3BvbnNlKSB7XG5cdFx0ICAgICAgICBcdGlmIChyZXNwb25zZS5zdGF0dXMgPT09ICdzdWNjZXNzJykge1xuXHRcdFx0XHRcdFx0dmFyIGxvY2F0aW9uID0gJyc7XG5cdFx0XHRcdFx0XHRsb2NhdGlvbiArPSByZXNwb25zZS5jaXR5O1xuXHRcdFx0XHRcdFx0JChjaXR5X2ZpZWxkKS52YWwocmVzcG9uc2UuY2l0eSk7XG5cdFx0XHRcdFx0XHRpZiAocmVzcG9uc2UuY2l0eSAhPT0gcmVzcG9uc2Uuc3RhdGUpIHtcblx0XHRcdFx0XHRcdFx0bG9jYXRpb24gKz0gJywgJyArIHJlc3BvbnNlLnN0YXRlO1xuXHRcdFx0XHRcdFx0XHQkKHN0YXRlX2ZpZWxkKS52YWwocmVzcG9uc2Uuc3RhdGUpO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0aWYgKGNvdW50cnkgIT09ICdVUycpIHtcblx0XHRcdFx0XHRcdFx0bG9jYXRpb24gKz0gJywgJyArIGNvdW50cnk7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHQkKCcubG9jYXRpb24gc21hbGwnKS50ZXh0KGxvY2F0aW9uKTtcblx0XHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdFx0JCgnLmxvY2F0aW9uIHNtYWxsJykudGV4dCgnJyk7XG5cdFx0XHRcdFx0fVxuXHRcdCAgICAgICAgfVxuXHRcdCAgICB9KTtcblx0XHR9XG5cdH1cblxuXHQkKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbigpIHtcblxuXHRcdC8vIHN0YXJ0XG5cdFx0aWYgKCQoJyNyaC1uYW1lJykubGVuZ3RoID4gMCApIHtcblx0XHRcdCQoICcjcmgtbmFtZScgKS52YWwoICcnICk7XG5cdFx0fVxuXG5cdFx0Ly8gc2hvdyBwYXNzd29yZCBpZiB1c2VyIGNsaWNrc1xuXHRcdGlmICgkKCcucGFzc3dvcmQtc2hvdycpLmxlbmd0aCA+IDAgKSB7XG5cdFx0XHRzaG93UGFzc3dvcmQoKTtcblx0XHR9XG5cdFx0Ly8gY2hlY2tQYXNzd29yZFN0cmVuZ3RoXG5cdFx0aWYgKCQoJy5wYXNzd29yZC1zdHJlbmd0aC1jaGVjaycpLmxlbmd0aCA+IDAgKSB7XG5cdFx0XHR2YXIgJGJlZm9yZSA9ICQoJy5hLWZvcm0tc2hvdy1wYXNzd29yZCcpO1xuXHRcdFx0JGJlZm9yZS5hZnRlciggJCgnPG1ldGVyIG1heD1cIjRcIiBpZD1cInBhc3N3b3JkLXN0cmVuZ3RoXCI+PGRpdj48L2Rpdj48L21ldGVyPjxwIGlkPVwicGFzc3dvcmQtc3RyZW5ndGgtdGV4dFwiPjwvcD4nKSk7XG5cdFx0ICAgICQoICdib2R5JyApLm9uKCAna2V5dXAnLCAnaW5wdXRbbmFtZT1wYXNzd29yZF0sIGlucHV0W25hbWU9bmV3X3Bhc3N3b3JkXScsXG5cdFx0ICAgICAgICBmdW5jdGlvbiggZXZlbnQgKSB7XG5cdFx0ICAgICAgICAgICAgY2hlY2tQYXNzd29yZFN0cmVuZ3RoKFxuXHRcdCAgICAgICAgICAgICAgICAkKCdpbnB1dFtuYW1lPXBhc3N3b3JkXSwgaW5wdXRbbmFtZT1uZXdfcGFzc3dvcmRdJyksIC8vIFBhc3N3b3JkIGZpZWxkXG5cdFx0ICAgICAgICAgICAgICAgICQoJyNwYXNzd29yZC1zdHJlbmd0aCcpLCAgICAgICAgICAgLy8gU3RyZW5ndGggbWV0ZXJcblx0XHQgICAgICAgICAgICAgICAgJCgnI3Bhc3N3b3JkLXN0cmVuZ3RoLXRleHQnKSwgICAgICAvLyBTdHJlbmd0aCB0ZXh0IGluZGljYXRvclxuXHRcdCAgICAgICAgICAgICAgICAkKCdpbnB1dFt0eXBlPXN1Ym1pdF0nKSwgICAgICAgICAgIC8vIFN1Ym1pdCBidXR0b25cblx0XHQgICAgICAgICAgICAgICAgWydibGFjaycsICdsaXN0ZWQnLCAnd29yZCddICAgICAgICAvLyBCbGFja2xpc3RlZCB3b3Jkc1xuXHRcdCAgICAgICAgICAgICk7XG5cdFx0ICAgICAgICB9XG5cdFx0ICAgICk7XG5cdFx0fVxuXG5cdFx0Ly8gemlwL2NvdW50cnkgdGhpbmdcblx0XHR2YXIgY291bnRyeSA9ICQoJy5tLWZvcm0tY291bnRyeSAjY291bnRyeScpLnZhbCgpO1xuXHRcdGlmICgkKCcubS1mb3JtLXppcC1jb2RlICN6aXAtY29kZScpLmxlbmd0aCkge1xuXHRcdFx0aWYgKGNvdW50cnkgPT0gJycgfHwgY291bnRyeSA9PSAnVVMnKSB7XG5cdFx0XHRcdCQoJy5tLWZvcm0tY291bnRyeScpLmhpZGUoKTtcblx0XHRcdFx0JCgnLm0tZm9ybS16aXAtY29kZSAjemlwLWNvZGUnKS5wcm9wKCd0eXBlJywgJ3RlbCcpO1xuXHRcdFx0XHQkKCcubS1mb3JtLXppcC1jb2RlJykuYXBwZW5kKCc8ZGl2IGNsYXNzPVwiYS1mb3JtLWNhcHRpb24gbG9jYXRpb25cIj48c21hbGw+PC9zbWFsbD48L2Rpdj48ZGl2IGNsYXNzPVwiYS1mb3JtLWNhcHRpb24gc2hvdy1jb3VudHJ5XCI+PGEgaHJlZj1cIiNcIiBpZD1cInJlZ2lzdHJhdGlvbl9zaG93X2NvdW50cnlcIj48c21hbGw+Tm90IGluIHRoZSBVUz88L3NtYWxsPjwvYT48L2Rpdj4nKTtcblx0XHRcdH0gZWxzZSBpZiAoJCgnLm0tZm9ybS1jb3VudHJ5ICNjb3VudHJ5JykubGVuZ3RoID4gMCkge1xuXHRcdFx0XHQkKCcubS1mb3JtLXppcC1jb2RlIGxhYmVsJykuaHRtbCgnUG9zdGFsIENvZGU6IDxzcGFuIHRpdGxlPVwiVGhpcyBmaWVsZCBpcyByZXF1aXJlZC5cIiBjbGFzcz1cImEtZm9ybS1pdGVtLXJlcXVpcmVkXCI+Kjwvc3Bhbj4nKTtcblx0XHRcdFx0JCgnLm0tZm9ybS16aXAtY29kZSAjemlwLWNvZGUnKS5wcm9wKCd0eXBlJywgJ3RleHQnKTtcblx0XHRcdH1cblx0XHRcdGlmICgkKCcubS1mb3JtLWNvdW50cnkgI2NvdW50cnknKS5sZW5ndGggPiAwKSB7XG5cdFx0XHRcdGlmICgkKCdzZWxlY3QsIGlucHV0JywgJy5tLWZvcm0tY291bnRyeScpLmhhc0NsYXNzKCdub3QtaW4tdXMnKSkge1xuXHRcdFx0XHRcdCQoJy5zaG93LWNvdW50cnknLCAnLm0tZm9ybS16aXAtY29kZScpLnJlbW92ZSgpO1xuXHRcdFx0XHRcdCQoJy5tLWZvcm0tY291bnRyeScpLnNob3coKTtcblx0XHRcdFx0XHQkKCcubS1mb3JtLXppcC1jb2RlICN6aXAtY29kZScpLnByb3AoJ3R5cGUnLCAndGV4dCcpO1xuXHRcdFx0XHRcdCQoJy5tLWZvcm0temlwLWNvZGUgbGFiZWwnKS5odG1sKCdQb3N0YWwgQ29kZTogPHNwYW4gdGl0bGU9XCJUaGlzIGZpZWxkIGlzIHJlcXVpcmVkLlwiIGNsYXNzPVwiYS1mb3JtLWl0ZW0tcmVxdWlyZWRcIj4qPC9zcGFuPicpO1xuXHRcdFx0XHR9XG5cdFx0XHRcdGlmICgkKCcubS1mb3JtLWNpdHkgI2NpdHknKS5sZW5ndGggPT0gMCAmJiAkKCcubS1mb3JtLXN0YXRlICNzdGF0ZScpLmxlbmd0aCA9PSAwKSB7XG5cdFx0XHRcdFx0JCgnLm0tZm9ybS1jb3VudHJ5ICNjb3VudHJ5LCAubS1mb3JtLXppcC1jb2RlICN6aXAtY29kZScpLmJsdXIoZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0XHRjaGVja1ppcENvdW50cnkoJCgnaW5wdXRbbmFtZT1cImNpdHlcIl0nKSwgJCgnaW5wdXRbbmFtZT1cInN0YXRlXCJdJyksICQoJy5tLWZvcm0temlwLWNvZGUgI3ppcC1jb2RlJyksICQoJy5tLWZvcm0tY291bnRyeSAjY291bnRyeScpKTtcblx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHQkKCcubS1mb3JtLWNvdW50cnkgI2NvdW50cnknKS5jaGFuZ2UoZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0XHRjaGVja1ppcENvdW50cnkoJCgnaW5wdXRbbmFtZT1cImNpdHlcIl0nKSwgJCgnaW5wdXRbbmFtZT1cInN0YXRlXCJdJyksICQoJy5tLWZvcm0temlwLWNvZGUgI3ppcC1jb2RlJyksICQoJy5tLWZvcm0tY291bnRyeSAjY291bnRyeScpKTtcblx0XHRcdFx0XHR9KTtcblx0XHRcdFx0fVxuXHRcdFx0XHQkKCcjcmVnaXN0cmF0aW9uX3Nob3dfY291bnRyeScpLmNsaWNrKGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdCQoJy5tLWZvcm0temlwLWNvZGUgbGFiZWwnKS5odG1sKCdQb3N0YWwgQ29kZTogPHNwYW4gdGl0bGU9XCJUaGlzIGZpZWxkIGlzIHJlcXVpcmVkLlwiIGNsYXNzPVwiYS1mb3JtLWl0ZW0tcmVxdWlyZWRcIj4qPC9zcGFuPicpO1xuXHRcdFx0XHRcdCQoJy5tLWZvcm0tY291bnRyeScpLnNsaWRlRG93bigpO1xuXHRcdFx0XHRcdCQoJy5tLWZvcm0temlwLWNvZGUgI3ppcC1jb2RlJykucHJvcCgndHlwZScsICd0ZXh0Jyk7XG5cdFx0XHRcdFx0JCh0aGlzKS5oaWRlKCk7XG5cdFx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0XHR9KTtcblx0XHRcdH1cblx0XHR9XG5cblx0fSk7XG5cbn0pKGpRdWVyeSk7XG4iXX0=
