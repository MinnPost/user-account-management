"use strict";

(function ($) {
  function showPassword() {
    // Cache our jquery elements
    var $submit = $('.btn-submit');
    var $field = $('.password-show');
    var show_pass = '<div class="a-form-show-password a-form-caption"><label><input type="checkbox" name="show_password" id="show-password-checkbox" value="1"> Show password</label></div>'; // Inject the toggle button into the page

    $field.after(show_pass); // Cache the toggle button

    var $toggle = $('#show-password-checkbox'); // Toggle the field type

    $toggle.on('click', function (e) {
      var checkbox = $(this);

      if (checkbox.is(':checked')) {
        $field.attr('type', 'text');
      } else {
        $field.attr('type', 'password');
      }
    }); // Set the form field back to a regular password element

    $submit.on('click', function (e) {
      $field.attr('type', 'password');
    });
  }

  function checkPasswordStrength($password, $strengthMeter, $strengthText, $submitButton, blacklistArray) {
    var password = $password.val(); // Reset the form & meter
    //$submitButton.attr( 'disabled', 'disabled' );

    $strengthText.removeClass('short bad good strong'); // Extend our blacklist array with those from the inputs & site data

    blacklistArray = blacklistArray.concat(wp.passwordStrength.userInputBlacklist()); // Get the password strength

    var strength = wp.passwordStrength.meter(password, blacklistArray, password); // Add the strength meter results

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

    $strengthMeter.val(strength); // Only enable the submit button if the password is strong

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
    } // show password if user clicks


    if ($('.password-show').length > 0) {
      showPassword();
    } // checkPasswordStrength


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
    } // zip/country thing


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
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInVzZXIuanMiXSwibmFtZXMiOlsiJCIsInNob3dQYXNzd29yZCIsIiRzdWJtaXQiLCIkZmllbGQiLCJzaG93X3Bhc3MiLCJhZnRlciIsIiR0b2dnbGUiLCJvbiIsImUiLCJjaGVja2JveCIsImlzIiwiYXR0ciIsImNoZWNrUGFzc3dvcmRTdHJlbmd0aCIsIiRwYXNzd29yZCIsIiRzdHJlbmd0aE1ldGVyIiwiJHN0cmVuZ3RoVGV4dCIsIiRzdWJtaXRCdXR0b24iLCJibGFja2xpc3RBcnJheSIsInBhc3N3b3JkIiwidmFsIiwicmVtb3ZlQ2xhc3MiLCJjb25jYXQiLCJ3cCIsInBhc3N3b3JkU3RyZW5ndGgiLCJ1c2VySW5wdXRCbGFja2xpc3QiLCJzdHJlbmd0aCIsIm1ldGVyIiwiYWRkQ2xhc3MiLCJodG1sIiwicHdzTDEwbiIsImJhZCIsImdvb2QiLCJzdHJvbmciLCJtaXNtYXRjaCIsInNob3J0IiwiY2hlY2taaXBDb3VudHJ5IiwiY2l0eV9maWVsZCIsInN0YXRlX2ZpZWxkIiwiemlwX2ZpZWxkIiwiY291bnRyeV9maWVsZCIsImNvdW50cnkiLCJ6aXAiLCJsb2NhdGlvbiIsInppcF9jb2RlIiwialF1ZXJ5IiwiYWpheCIsInR5cGUiLCJ1cmwiLCJ1c2VyX2FjY291bnRfbWFuYWdlbWVudF9yZXN0Iiwic2l0ZV91cmwiLCJyZXN0X25hbWVzcGFjZSIsImRhdGEiLCJkYXRhVHlwZSIsInN1Y2Nlc3MiLCJyZXNwb25zZSIsInN0YXR1cyIsImNpdHkiLCJzdGF0ZSIsInRleHQiLCJkb2N1bWVudCIsInJlYWR5IiwibGVuZ3RoIiwiJGJlZm9yZSIsImV2ZW50IiwiaGlkZSIsInByb3AiLCJhcHBlbmQiLCJoYXNDbGFzcyIsInJlbW92ZSIsInNob3ciLCJibHVyIiwiY2hhbmdlIiwiY2xpY2siLCJzbGlkZURvd24iXSwibWFwcGluZ3MiOiI7O0FBQUEsQ0FBQyxVQUFTQSxDQUFULEVBQVc7QUFFWCxXQUFTQyxZQUFULEdBQXdCO0FBQ3ZCO0FBQ0EsUUFBSUMs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QUFBLENBQUMsQ0FBQyx3QkFBRCxDQUFELENBQTRCNEIsSUFBNUIsQ0FBaUMsMEZBQWpDO0FBQ0E1QixVQUFBQSxDQUFDLENBQUMsaUJBQUQsQ0FBRCxDQUFxQnlFLFNBQXJCO0FBQ0F6RSxVQUFBQSxDQUFDLENBQUMsNEJBQUQsQ0FBRCxDQUFnQ2lFLElBQWhDLENBQXFDLE1BQXJDLEVBQTZDLE1BQTdDO0FBQ0FqRSxVQUFBQSxDQUFDLENBQUMsSUFBRCxDQUFELENBQVFnRSxJQUFSO0FBQ0EsaUJBQU8sS0FBUDtBQUNBLFNBTkQ7QUFPQTtBQUNEO0FBRUQsR0FoRUQ7QUFrRUEsQ0EvS0QsRUErS0dwQixNQS9LSCIsImZpbGUiOiJ1c2VyLWFjY291bnQtbWFuYWdlbWVudC5qcyIsInNvdXJjZXNDb250ZW50IjpbIihmdW5jdGlvbigkKXtcblxuXHRmdW5jdGlvbiBzaG93UGFzc3dvcmQoKSB7XG5cdFx0Ly8gQ2FjaGUgb3VyIGpxdWVyeSBlbGVtZW50c1xuXHRcdHZhciAkc3VibWl0ID0gJCgnLmJ0bi1zdWJtaXQnKTtcblx0XHR2YXIgJGZpZWxkID0gJCgnLnBhc3N3b3JkLXNob3cnKTtcblx0XHR2YXIgc2hvd19wYXNzID0gJzxkaXYgY2xhc3M9XCJhLWZvcm0tc2hvdy1wYXNzd29yZCBhLWZvcm0tY2FwdGlvblwiPjxsYWJlbD48aW5wdXQgdHlwZT1cImNoZWNrYm94XCIgbmFtZT1cInNob3dfcGFzc3dvcmRcIiBpZD1cInNob3ctcGFzc3dvcmQtY2hlY2tib3hcIiB2YWx1ZT1cIjFcIj4gU2hvdyBwYXNzd29yZDwvbGFiZWw+PC9kaXY+Jztcblx0XHQvLyBJbmplY3QgdGhlIHRvZ2dsZSBidXR0b24gaW50byB0aGUgcGFnZVxuXHRcdCRmaWVsZC5hZnRlciggc2hvd19wYXNzICk7XG5cdFx0Ly8gQ2FjaGUgdGhlIHRvZ2dsZSBidXR0b25cblx0XHR2YXIgJHRvZ2dsZSA9ICQoJyNzaG93LXBhc3N3b3JkLWNoZWNrYm94Jyk7XG5cdFx0Ly8gVG9nZ2xlIHRoZSBmaWVsZCB0eXBlXG5cdFx0JHRvZ2dsZS5vbignY2xpY2snLCBmdW5jdGlvbihlKSB7XG5cdFx0XHR2YXIgY2hlY2tib3ggPSAkKHRoaXMpO1xuXHRcdFx0aWYgKGNoZWNrYm94LmlzKCc6Y2hlY2tlZCcpKSB7XG5cdFx0XHRcdCRmaWVsZC5hdHRyKCd0eXBlJywgJ3RleHQnKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdCRmaWVsZC5hdHRyKCd0eXBlJywgJ3Bhc3N3b3JkJyk7XG5cdFx0XHR9XG5cdFx0fSk7XG5cdFx0Ly8gU2V0IHRoZSBmb3JtIGZpZWxkIGJhY2sgdG8gYSByZWd1bGFyIHBhc3N3b3JkIGVsZW1lbnRcblx0XHQkc3VibWl0Lm9uKCAnY2xpY2snLCBmdW5jdGlvbihlKSB7XG5cdFx0XHQkZmllbGQuYXR0cigndHlwZScsICdwYXNzd29yZCcpO1xuXHRcdH0pO1xuXHR9XG5cblx0ZnVuY3Rpb24gY2hlY2tQYXNzd29yZFN0cmVuZ3RoKCAkcGFzc3dvcmQsICRzdHJlbmd0aE1ldGVyLCAkc3RyZW5ndGhUZXh0LCAkc3VibWl0QnV0dG9uLCBibGFja2xpc3RBcnJheSApIHtcblx0ICAgIHZhciBwYXNzd29yZCA9ICRwYXNzd29yZC52YWwoKTtcblxuXHQgICAgLy8gUmVzZXQgdGhlIGZvcm0gJiBtZXRlclxuXHQgICAgLy8kc3VibWl0QnV0dG9uLmF0dHIoICdkaXNhYmxlZCcsICdkaXNhYmxlZCcgKTtcblx0ICAgICRzdHJlbmd0aFRleHQucmVtb3ZlQ2xhc3MoICdzaG9ydCBiYWQgZ29vZCBzdHJvbmcnICk7XG5cblx0ICAgIC8vIEV4dGVuZCBvdXIgYmxhY2tsaXN0IGFycmF5IHdpdGggdGhvc2UgZnJvbSB0aGUgaW5wdXRzICYgc2l0ZSBkYXRhXG5cdCAgICBibGFja2xpc3RBcnJheSA9IGJsYWNrbGlzdEFycmF5LmNvbmNhdCggd3AucGFzc3dvcmRTdHJlbmd0aC51c2VySW5wdXRCbGFja2xpc3QoKSApXG5cblx0ICAgIC8vIEdldCB0aGUgcGFzc3dvcmQgc3RyZW5ndGhcblx0ICAgIHZhciBzdHJlbmd0aCA9IHdwLnBhc3N3b3JkU3RyZW5ndGgubWV0ZXIoIHBhc3N3b3JkLCBibGFja2xpc3RBcnJheSwgcGFzc3dvcmQgKTtcblxuXHQgICAgLy8gQWRkIHRoZSBzdHJlbmd0aCBtZXRlciByZXN1bHRzXG5cdCAgICBzd2l0Y2ggKCBzdHJlbmd0aCApIHtcblx0ICAgICAgICBjYXNlIDI6XG5cdCAgICAgICAgICAgICRzdHJlbmd0aFRleHQuYWRkQ2xhc3MoICdiYWQnICkuaHRtbCggJ1N0cmVuZ3RoOiA8c3Ryb25nPicgKyBwd3NMMTBuLmJhZCArICc8L3N0cm9uZz4nICk7XG5cdCAgICAgICAgICAgIGJyZWFrO1xuXHQgICAgICAgIGNhc2UgMzpcblx0ICAgICAgICAgICAgJHN0cmVuZ3RoVGV4dC5hZGRDbGFzcyggJ2dvb2QnICkuaHRtbCggJ1N0cmVuZ3RoOiA8c3Ryb25nPicgKyBwd3NMMTBuLmdvb2QgKyAnPC9zdHJvbmc+JyApO1xuXHQgICAgICAgICAgICBicmVhaztcblx0ICAgICAgICBjYXNlIDQ6XG5cdCAgICAgICAgICAgICRzdHJlbmd0aFRleHQuYWRkQ2xhc3MoICdzdHJvbmcnICkuaHRtbCggJ1N0cmVuZ3RoOiA8c3Ryb25nPicgKyBwd3NMMTBuLnN0cm9uZyArICc8L3N0cm9uZz4nICk7XG5cdCAgICAgICAgICAgIGJyZWFrO1xuXHQgICAgICAgIGNhc2UgNTpcblx0ICAgICAgICAgICAgJHN0cmVuZ3RoVGV4dC5hZGRDbGFzcyggJ3Nob3J0JyApLmh0bWwoICdTdHJlbmd0aDogPHN0cm9uZz4nICsgcHdzTDEwbi5taXNtYXRjaCArICc8L3N0cm9uZz4nICk7XG5cdCAgICAgICAgICAgIGJyZWFrO1xuXHQgICAgICAgIGRlZmF1bHQ6XG5cdCAgICAgICAgICAgICRzdHJlbmd0aFRleHQuYWRkQ2xhc3MoICdzaG9ydCcgKS5odG1sKCAnU3RyZW5ndGg6IDxzdHJvbmc+JyArIHB3c0wxMG4uc2hvcnQgKyAnPC9zdHJvbmc+JyApO1xuXHQgICAgfVxuXHQgICAgJHN0cmVuZ3RoTWV0ZXIudmFsKHN0cmVuZ3RoKTtcblxuXHQgICAgLy8gT25seSBlbmFibGUgdGhlIHN1Ym1pdCBidXR0b24gaWYgdGhlIHBhc3N3b3JkIGlzIHN0cm9uZ1xuXHQgICAgLypcblx0ICAgIGlmICggNCA9PT0gc3RyZW5ndGggKSB7XG5cdCAgICAgICAgJHN1Ym1pdEJ1dHRvbi5yZW1vdmVBdHRyKCAnZGlzYWJsZWQnICk7XG5cdCAgICB9Ki9cblxuXHQgICAgcmV0dXJuIHN0cmVuZ3RoO1xuXHR9XG5cblx0ZnVuY3Rpb24gY2hlY2taaXBDb3VudHJ5KGNpdHlfZmllbGQsIHN0YXRlX2ZpZWxkLCB6aXBfZmllbGQsIGNvdW50cnlfZmllbGQpIHtcblxuXHRcdHZhciBjb3VudHJ5ID0gY291bnRyeV9maWVsZC52YWwoKTtcblx0XHRpZiAoY291bnRyeSA9PSAnJykge1xuXHRcdFx0Y291bnRyeSA9ICdVUyc7XG5cdFx0XHRjb3VudHJ5X2ZpZWxkLnZhbChjb3VudHJ5KTtcblx0XHR9XG5cdFx0dmFyIHppcCA9IHppcF9maWVsZC52YWwoKTtcblxuXHRcdGlmICh6aXAgIT09ICcnKSB7XG5cblx0XHRcdHZhciBsb2NhdGlvbiA9IHtcblx0XHRcdFx0emlwX2NvZGU6IHppcCxcblx0XHRcdFx0Y291bnRyeTogY291bnRyeVxuXHRcdFx0fVxuXG5cdFx0XHRqUXVlcnkuYWpheCh7XG5cdFx0ICAgICAgICB0eXBlOiAnR0VUJyxcblx0XHQgICAgICAgIHVybDogdXNlcl9hY2NvdW50X21hbmFnZW1lbnRfcmVzdC5zaXRlX3VybCArIHVzZXJfYWNjb3VudF9tYW5hZ2VtZW50X3Jlc3QucmVzdF9uYW1lc3BhY2UgKyAnL2NoZWNrLXppcCcsXG5cdFx0ICAgICAgICBkYXRhOiBsb2NhdGlvbixcblx0XHQgICAgICAgIGRhdGFUeXBlOiAnanNvbicsXG5cdFx0ICAgICAgICBzdWNjZXNzOiBmdW5jdGlvbihyZXNwb25zZSkge1xuXHRcdCAgICAgICAgXHRpZiAocmVzcG9uc2Uuc3RhdHVzID09PSAnc3VjY2VzcycpIHtcblx0XHRcdFx0XHRcdHZhciBsb2NhdGlvbiA9ICcnO1xuXHRcdFx0XHRcdFx0bG9jYXRpb24gKz0gcmVzcG9uc2UuY2l0eTtcblx0XHRcdFx0XHRcdCQoY2l0eV9maWVsZCkudmFsKHJlc3BvbnNlLmNpdHkpO1xuXHRcdFx0XHRcdFx0aWYgKHJlc3BvbnNlLmNpdHkgIT09IHJlc3BvbnNlLnN0YXRlKSB7XG5cdFx0XHRcdFx0XHRcdGxvY2F0aW9uICs9ICcsICcgKyByZXNwb25zZS5zdGF0ZTtcblx0XHRcdFx0XHRcdFx0JChzdGF0ZV9maWVsZCkudmFsKHJlc3BvbnNlLnN0YXRlKTtcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdGlmIChjb3VudHJ5ICE9PSAnVVMnKSB7XG5cdFx0XHRcdFx0XHRcdGxvY2F0aW9uICs9ICcsICcgKyBjb3VudHJ5O1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0JCgnLmxvY2F0aW9uIHNtYWxsJykudGV4dChsb2NhdGlvbik7XG5cdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdCQoJy5sb2NhdGlvbiBzbWFsbCcpLnRleHQoJycpO1xuXHRcdFx0XHRcdH1cblx0XHQgICAgICAgIH1cblx0XHQgICAgfSk7XG5cdFx0fVxuXHR9XG5cblx0JChkb2N1bWVudCkucmVhZHkoZnVuY3Rpb24oKSB7XG5cblx0XHQvLyBzdGFydFxuXHRcdGlmICgkKCcjcmgtbmFtZScpLmxlbmd0aCA+IDAgKSB7XG5cdFx0XHQkKCAnI3JoLW5hbWUnICkudmFsKCAnJyApO1xuXHRcdH1cblxuXHRcdC8vIHNob3cgcGFzc3dvcmQgaWYgdXNlciBjbGlja3Ncblx0XHRpZiAoJCgnLnBhc3N3b3JkLXNob3cnKS5sZW5ndGggPiAwICkge1xuXHRcdFx0c2hvd1Bhc3N3b3JkKCk7XG5cdFx0fVxuXHRcdC8vIGNoZWNrUGFzc3dvcmRTdHJlbmd0aFxuXHRcdGlmICgkKCcucGFzc3dvcmQtc3RyZW5ndGgtY2hlY2snKS5sZW5ndGggPiAwICkge1xuXHRcdFx0dmFyICRiZWZvcmUgPSAkKCcuYS1mb3JtLXNob3ctcGFzc3dvcmQnKTtcblx0XHRcdCRiZWZvcmUuYWZ0ZXIoICQoJzxtZXRlciBtYXg9XCI0XCIgaWQ9XCJwYXNzd29yZC1zdHJlbmd0aFwiPjxkaXY+PC9kaXY+PC9tZXRlcj48cCBpZD1cInBhc3N3b3JkLXN0cmVuZ3RoLXRleHRcIj48L3A+JykpO1xuXHRcdCAgICAkKCAnYm9keScgKS5vbiggJ2tleXVwJywgJ2lucHV0W25hbWU9cGFzc3dvcmRdLCBpbnB1dFtuYW1lPW5ld19wYXNzd29yZF0nLFxuXHRcdCAgICAgICAgZnVuY3Rpb24oIGV2ZW50ICkge1xuXHRcdCAgICAgICAgICAgIGNoZWNrUGFzc3dvcmRTdHJlbmd0aChcblx0XHQgICAgICAgICAgICAgICAgJCgnaW5wdXRbbmFtZT1wYXNzd29yZF0sIGlucHV0W25hbWU9bmV3X3Bhc3N3b3JkXScpLCAvLyBQYXNzd29yZCBmaWVsZFxuXHRcdCAgICAgICAgICAgICAgICAkKCcjcGFzc3dvcmQtc3RyZW5ndGgnKSwgICAgICAgICAgIC8vIFN0cmVuZ3RoIG1ldGVyXG5cdFx0ICAgICAgICAgICAgICAgICQoJyNwYXNzd29yZC1zdHJlbmd0aC10ZXh0JyksICAgICAgLy8gU3RyZW5ndGggdGV4dCBpbmRpY2F0b3Jcblx0XHQgICAgICAgICAgICAgICAgJCgnaW5wdXRbdHlwZT1zdWJtaXRdJyksICAgICAgICAgICAvLyBTdWJtaXQgYnV0dG9uXG5cdFx0ICAgICAgICAgICAgICAgIFsnYmxhY2snLCAnbGlzdGVkJywgJ3dvcmQnXSAgICAgICAgLy8gQmxhY2tsaXN0ZWQgd29yZHNcblx0XHQgICAgICAgICAgICApO1xuXHRcdCAgICAgICAgfVxuXHRcdCAgICApO1xuXHRcdH1cblxuXHRcdC8vIHppcC9jb3VudHJ5IHRoaW5nXG5cdFx0dmFyIGNvdW50cnkgPSAkKCcubS1mb3JtLWNvdW50cnkgI2NvdW50cnknKS52YWwoKTtcblx0XHRpZiAoJCgnLm0tZm9ybS16aXAtY29kZSAjemlwLWNvZGUnKS5sZW5ndGgpIHtcblx0XHRcdGlmIChjb3VudHJ5ID09ICcnIHx8IGNvdW50cnkgPT0gJ1VTJykge1xuXHRcdFx0XHQkKCcubS1mb3JtLWNvdW50cnknKS5oaWRlKCk7XG5cdFx0XHRcdCQoJy5tLWZvcm0temlwLWNvZGUgI3ppcC1jb2RlJykucHJvcCgndHlwZScsICd0ZWwnKTtcblx0XHRcdFx0JCgnLm0tZm9ybS16aXAtY29kZScpLmFwcGVuZCgnPGRpdiBjbGFzcz1cImEtZm9ybS1jYXB0aW9uIGxvY2F0aW9uXCI+PHNtYWxsPjwvc21hbGw+PC9kaXY+PGRpdiBjbGFzcz1cImEtZm9ybS1jYXB0aW9uIHNob3ctY291bnRyeVwiPjxhIGhyZWY9XCIjXCIgaWQ9XCJyZWdpc3RyYXRpb25fc2hvd19jb3VudHJ5XCI+PHNtYWxsPk5vdCBpbiB0aGUgVVM/PC9zbWFsbD48L2E+PC9kaXY+Jyk7XG5cdFx0XHR9IGVsc2UgaWYgKCQoJy5tLWZvcm0tY291bnRyeSAjY291bnRyeScpLmxlbmd0aCA+IDApIHtcblx0XHRcdFx0JCgnLm0tZm9ybS16aXAtY29kZSBsYWJlbCcpLmh0bWwoJ1Bvc3RhbCBDb2RlOiA8c3BhbiB0aXRsZT1cIlRoaXMgZmllbGQgaXMgcmVxdWlyZWQuXCIgY2xhc3M9XCJhLWZvcm0taXRlbS1yZXF1aXJlZFwiPio8L3NwYW4+Jyk7XG5cdFx0XHRcdCQoJy5tLWZvcm0temlwLWNvZGUgI3ppcC1jb2RlJykucHJvcCgndHlwZScsICd0ZXh0Jyk7XG5cdFx0XHR9XG5cdFx0XHRpZiAoJCgnLm0tZm9ybS1jb3VudHJ5ICNjb3VudHJ5JykubGVuZ3RoID4gMCkge1xuXHRcdFx0XHRpZiAoJCgnc2VsZWN0LCBpbnB1dCcsICcubS1mb3JtLWNvdW50cnknKS5oYXNDbGFzcygnbm90LWluLXVzJykpIHtcblx0XHRcdFx0XHQkKCcuc2hvdy1jb3VudHJ5JywgJy5tLWZvcm0temlwLWNvZGUnKS5yZW1vdmUoKTtcblx0XHRcdFx0XHQkKCcubS1mb3JtLWNvdW50cnknKS5zaG93KCk7XG5cdFx0XHRcdFx0JCgnLm0tZm9ybS16aXAtY29kZSAjemlwLWNvZGUnKS5wcm9wKCd0eXBlJywgJ3RleHQnKTtcblx0XHRcdFx0XHQkKCcubS1mb3JtLXppcC1jb2RlIGxhYmVsJykuaHRtbCgnUG9zdGFsIENvZGU6IDxzcGFuIHRpdGxlPVwiVGhpcyBmaWVsZCBpcyByZXF1aXJlZC5cIiBjbGFzcz1cImEtZm9ybS1pdGVtLXJlcXVpcmVkXCI+Kjwvc3Bhbj4nKTtcblx0XHRcdFx0fVxuXHRcdFx0XHRpZiAoJCgnLm0tZm9ybS1jaXR5ICNjaXR5JykubGVuZ3RoID09IDAgJiYgJCgnLm0tZm9ybS1zdGF0ZSAjc3RhdGUnKS5sZW5ndGggPT0gMCkge1xuXHRcdFx0XHRcdCQoJy5tLWZvcm0tY291bnRyeSAjY291bnRyeSwgLm0tZm9ybS16aXAtY29kZSAjemlwLWNvZGUnKS5ibHVyKGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0Y2hlY2taaXBDb3VudHJ5KCQoJ2lucHV0W25hbWU9XCJjaXR5XCJdJyksICQoJ2lucHV0W25hbWU9XCJzdGF0ZVwiXScpLCAkKCcubS1mb3JtLXppcC1jb2RlICN6aXAtY29kZScpLCAkKCcubS1mb3JtLWNvdW50cnkgI2NvdW50cnknKSk7XG5cdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0JCgnLm0tZm9ybS1jb3VudHJ5ICNjb3VudHJ5JykuY2hhbmdlKGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0Y2hlY2taaXBDb3VudHJ5KCQoJ2lucHV0W25hbWU9XCJjaXR5XCJdJyksICQoJ2lucHV0W25hbWU9XCJzdGF0ZVwiXScpLCAkKCcubS1mb3JtLXppcC1jb2RlICN6aXAtY29kZScpLCAkKCcubS1mb3JtLWNvdW50cnkgI2NvdW50cnknKSk7XG5cdFx0XHRcdFx0fSk7XG5cdFx0XHRcdH1cblx0XHRcdFx0JCgnI3JlZ2lzdHJhdGlvbl9zaG93X2NvdW50cnknKS5jbGljayhmdW5jdGlvbigpIHtcblx0XHRcdFx0XHQkKCcubS1mb3JtLXppcC1jb2RlIGxhYmVsJykuaHRtbCgnUG9zdGFsIENvZGU6IDxzcGFuIHRpdGxlPVwiVGhpcyBmaWVsZCBpcyByZXF1aXJlZC5cIiBjbGFzcz1cImEtZm9ybS1pdGVtLXJlcXVpcmVkXCI+Kjwvc3Bhbj4nKTtcblx0XHRcdFx0XHQkKCcubS1mb3JtLWNvdW50cnknKS5zbGlkZURvd24oKTtcblx0XHRcdFx0XHQkKCcubS1mb3JtLXppcC1jb2RlICN6aXAtY29kZScpLnByb3AoJ3R5cGUnLCAndGV4dCcpO1xuXHRcdFx0XHRcdCQodGhpcykuaGlkZSgpO1xuXHRcdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdFx0fSk7XG5cdFx0XHR9XG5cdFx0fVxuXG5cdH0pO1xuXG59KShqUXVlcnkpO1xuIl19
