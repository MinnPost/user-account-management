;(function() {
"use strict";

"use strict";

function showPasswordField() {
  var theField = document.querySelector('.password-show');

  if (theField) {
    var toggleField = document.createElement('div');
    toggleField.innerHTML = '<div class="a-form-show-password a-form-caption"><label><input type="checkbox" name="show_password" id="show-password-checkbox" value="1"> Show password</label></div>';
    theField.after(toggleField);
    var checkboxField = document.querySelector('#show-password-checkbox');

    if (checkboxField) {
      checkboxField.addEventListener('click', function () {
        if (event.currentTarget.checked === true) {
          theField.setAttribute('type', 'text');
        } else {
          theField.setAttribute('type', 'password');
        }
      });
    }
  }
}

function passwordStrengthChecker(password, strengthMeter, strengthText, submitButton, disallowListArray) {
  strengthText.classList.remove('short', 'bad', 'good', 'strong'); // Extend our disallowList array with those from the inputs & site data

  disallowListArray = disallowListArray.concat(wp.passwordStrength.userInputDisallowedList()); // Get the password strength

  var strength = wp.passwordStrength.meter(password, disallowListArray, password);

  switch (strength) {
    case 2:
      strengthText.classList.add('bad');
      strengthText.innerHTML = 'Strength: <strong>' + pwsL10n.bad + '</strong>';
      break;

    case 3:
      strengthText.classList.add('good');
      strengthText.innerHTML = 'Strength: <strong>' + pwsL10n.good + '</strong>';
      break;

    case 4:
      strengthText.classList.add('strong');
      strengthText.innerHTML = 'Strength: <strong>' + pwsL10n.strong + '</strong>';
      break;

    case 5:
      strengthText.classList.add('short');
      strengthText.innerHTML = 'Strength: <strong>' + pwsL10n.mismatch + '</strong>';
      break;

    default:
      strengthText.classList.add('short');
      strengthText.innerHTML = 'Strength: <strong>' + pwsL10n.short + '</strong>';
  }

  strengthMeter.setAttribute('value', strength); // Only enable the submit button if the password is strong

  /*
  if ( 4 === strength ) {
  	submitButton.removeAttr( 'disabled' );
  }*/

  return strength;
}

function setupPasswordStrength() {
  var checkPasswordStrength = document.querySelector('.password-strength-check');

  if (checkPasswordStrength) {
    var beforePasswordChecker = document.querySelector('.a-form-show-password');
    var passwordMeter = document.createElement('meter');
    var passwordMeterDiv = document.createElement('div');
    var passwordMeterText = document.createElement('p');
    var registerButton = document.querySelector('.register-button');
    passwordMeter.setAttribute('max', '4');
    passwordMeter.setAttribute('id', 'password-strength');
    passwordMeterText.setAttribute('id', 'password-strength-text');
    passwordMeter.appendChild(passwordMeterDiv);
    beforePasswordChecker.after(passwordMeter);
    beforePasswordChecker.after(passwordMeterText);
    var passwordFields = document.querySelectorAll('input[name=password], input[name=new_password]');

    if (0 < passwordFields.length && registerButton) {
      passwordFields.forEach(function (passwordField) {
        passwordField.addEventListener('keyup', function (event) {
          passwordStrengthChecker(event.target.value, // Password field
          passwordMeter, // Strength meter
          passwordMeterText, // Strength text indicator
          registerButton, // Submit button
          ['disallowed', 'listed', 'word'] // disallowed words
          );
        });
      });
    }
  }
}

function setupCountryField(clickedNotUS) {
  var countryField = document.querySelector('.m-form-country #country');
  var zipParent = document.querySelector('.m-form-zip-code');

  if ('undefined' === typeof clickedNotUS) {
    clickedNotUS = false;
  }

  var countrySelector = countryField.querySelectorAll('select', 'input');

  if (0 < countrySelector.length) {
    countrySelector.forEach(function (countrySelectorField) {
      if (!countrySelectorField.classList.contains('not-in-us')) {
        clickedNotUS = true;
      }
    });
  }

  if (countryField && zipParent) {
    toggleZipCountrySelector(countryField, zipParent, clickedNotUS);
  }
}

function toggleZipCountrySelector(countryField, zipParent, clickedNotUS) {
  var zipField = zipParent.querySelector('#zip-code');
  var zipLabel = zipParent.querySelector('label');
  var showCountryMessage = document.createElement('div');
  var countryMessageText = document.createElement('small');
  var notInUs = document.createElement('div');
  var countryValue = countryField.value;
  showCountryMessage.setAttribute('class', 'a-form-caption location');
  showCountryMessage.innerHTML = '<small></small>';
  zipParent.append(showCountryMessage);
  notInUs.setAttribute('class', 'a-form-caption show-country');
  setZipSettings(countryValue, zipField, zipLabel);

  if (false === clickedNotUS && (countryValue === '' || countryValue === 'US')) {
    countryField.parentNode.style.display = 'none';
    zipField.innerHTML = countryMessageText;
    notInUs.innerHTML = '<a href="#" id="registration_show_country"><small>Not in the US?</small></a>';
    zipParent.append(notInUs);
  } else {
    countryField.parentNode.style.display = 'block'; // could also do a slidedown thing, maybe.
  }
}

function setZipSettings(countryValue, zipField, zipLabel) {
  if ('' === countryValue || 'US' === countryValue) {
    zipField.setAttribute('type', 'tel');
    zipLabel.innerHTML = 'Zip Code: <span title="This field is required." class="a-form-item-required">*</span>';
  } else {
    zipField.setAttribute('type', 'text');
    zipLabel.innerHTML = 'Postal Code: <span title="This field is required." class="a-form-item-required">*</span>';
  }
}

function showOutsideUsFields() {
  var showCountry = document.querySelector('#registration_show_country');

  if (showCountry) {
    showCountry.addEventListener('click', function (event) {
      event.preventDefault();
      event.target.style.display = 'none';
      setupCountryField(true);
    });
  }
}

function getCityStateFromZip() {
  var cityField = document.querySelector('input[name="city"]');
  var stateField = document.querySelector('input[name="state"]');
  var zipField = document.querySelector('#zip-code');
  var countryField = document.querySelector('.m-form-country #country');

  if (zipField) {
    zipField.addEventListener('blur', function () {
      checkZipCountry(cityField, stateField, zipField, countryField);
    });
  }

  if (countryField) {
    countryField.addEventListener('change', function () {
      checkZipCountry(cityField, stateField, zipField, countryField);
    });
  }
}

function checkZipCountry(cityField, stateField, zipField, countryField) {
  var countryValue = countryField.value;

  if ('' === countryValue) {
    countryValue = 'US';
    countryField.value = countryValue;
  }

  var zipValue = zipField.value;

  if ('' !== zipValue) {
    var location = {
      zip_code: zipValue,
      country: countryValue
    };
    var locationElement = document.querySelector('.location small');

    if (locationElement) {
      var url = user_account_management_rest.site_url + user_account_management_rest.rest_namespace + '/check-zip';
      url += '?' + new URLSearchParams(location).toString();
      fetch(url).then(function (response) {
        return response.json();
      }).then(function (data) {
        if ('success' === data.status) {
          var locationString = '';
          locationString += data.city;

          if (cityField) {
            cityField.value = data.city;
          }

          if (data.city !== data.state) {
            locationString += ', ' + data.state;

            if (stateField) {
              stateField.value = data.state;
            }
          }

          if ('US' !== countryValue) {
            locationString += ', ' + countryValue;
          }

          locationElement.innerHTML = locationString;
        } else {
          locationElement.innerHTML = '';
        }
      }).catch(function () {
        locationElement.innerHTML = '';
      });
    }
  }
}

document.addEventListener('DOMContentLoaded', function () {
  showPasswordField();
  setupPasswordStrength();
  setupCountryField();
  showOutsideUsFields();
  getCityStateFromZip();
});
}());

//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInVzZXIuanMiXSwibmFtZXMiOlsiZG9jdW1lbnQiXSwibWFwcGluZ3MiOiI7OztBQUFBO0FBQ0E7QUFEQTtBQUdBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUZDO0FBSUQ7QUFGQTtBQUlBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFZQTtBQUNBO0FBQ0E7QUFWQTtBQUNBO0FBV0M7QUFURDtBQVdBO0FBVEE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQWlCQztBQWZEO0FBaUJBO0FBZkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQWVDO0FBYkQ7QUFlQTtBQWJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFtQkM7QUFqQkQ7QUFtQkE7QUFqQkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFxQkM7QUFuQkQ7QUFxQkE7QUFuQkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBb0JDO0FBbEJEO0FBb0JBO0FBbEJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBa0JDO0FBaEJEO0FBa0JBO0FBaEJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQWFDO0FBWEQ7QUFhQUE7QUFYQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBYUMiLCJmaWxlIjoidXNlci1hY2NvdW50LW1hbmFnZW1lbnQuanMiLCJzb3VyY2VzQ29udGVudCI6WyJmdW5jdGlvbiBzaG93UGFzc3dvcmRGaWVsZCgpIHtcblx0Y29uc3QgdGhlRmllbGQgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcucGFzc3dvcmQtc2hvdycpO1xuXHRpZiAodGhlRmllbGQpIHtcblx0XHRjb25zdCB0b2dnbGVGaWVsZCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuXHRcdHRvZ2dsZUZpZWxkLmlubmVySFRNTCA9XG5cdFx0XHQnPGRpdiBjbGFzcz1cImEtZm9ybS1zaG93LXBhc3N3b3JkIGEtZm9ybS1jYXB0aW9uXCI+PGxhYmVsPjxpbnB1dCB0eXBlPVwiY2hlY2tib3hcIiBuYW1lPVwic2hvd19wYXNzd29yZFwiIGlkPVwic2hvdy1wYXNzd29yZC1jaGVja2JveFwiIHZhbHVlPVwiMVwiPiBTaG93IHBhc3N3b3JkPC9sYWJlbD48L2Rpdj4nO1xuXHRcdHRoZUZpZWxkLmFmdGVyKHRvZ2dsZUZpZWxkKTtcblx0XHRjb25zdCBjaGVja2JveEZpZWxkID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignI3Nob3ctcGFzc3dvcmQtY2hlY2tib3gnKTtcblx0XHRpZiAoY2hlY2tib3hGaWVsZCkge1xuXHRcdFx0Y2hlY2tib3hGaWVsZC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uICgpIHtcblx0XHRcdFx0aWYgKGV2ZW50LmN1cnJlbnRUYXJnZXQuY2hlY2tlZCA9PT0gdHJ1ZSkge1xuXHRcdFx0XHRcdHRoZUZpZWxkLnNldEF0dHJpYnV0ZSgndHlwZScsICd0ZXh0Jyk7XG5cdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0dGhlRmllbGQuc2V0QXR0cmlidXRlKCd0eXBlJywgJ3Bhc3N3b3JkJyk7XG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXHRcdH1cblx0fVxufVxuXG5mdW5jdGlvbiBwYXNzd29yZFN0cmVuZ3RoQ2hlY2tlcihcblx0cGFzc3dvcmQsXG5cdHN0cmVuZ3RoTWV0ZXIsXG5cdHN0cmVuZ3RoVGV4dCxcblx0c3VibWl0QnV0dG9uLFxuXHRkaXNhbGxvd0xpc3RBcnJheVxuKSB7XG5cdHN0cmVuZ3RoVGV4dC5jbGFzc0xpc3QucmVtb3ZlKCdzaG9ydCcsICdiYWQnLCAnZ29vZCcsICdzdHJvbmcnKTtcblxuXHQvLyBFeHRlbmQgb3VyIGRpc2FsbG93TGlzdCBhcnJheSB3aXRoIHRob3NlIGZyb20gdGhlIGlucHV0cyAmIHNpdGUgZGF0YVxuXHRkaXNhbGxvd0xpc3RBcnJheSA9IGRpc2FsbG93TGlzdEFycmF5LmNvbmNhdChcblx0XHR3cC5wYXNzd29yZFN0cmVuZ3RoLnVzZXJJbnB1dERpc2FsbG93ZWRMaXN0KClcblx0KTtcblxuXHQvLyBHZXQgdGhlIHBhc3N3b3JkIHN0cmVuZ3RoXG5cdGNvbnN0IHN0cmVuZ3RoID0gd3AucGFzc3dvcmRTdHJlbmd0aC5tZXRlcihcblx0XHRwYXNzd29yZCxcblx0XHRkaXNhbGxvd0xpc3RBcnJheSxcblx0XHRwYXNzd29yZFxuXHQpO1xuXG5cdHN3aXRjaCAoc3RyZW5ndGgpIHtcblx0XHRjYXNlIDI6XG5cdFx0XHRzdHJlbmd0aFRleHQuY2xhc3NMaXN0LmFkZCgnYmFkJyk7XG5cdFx0XHRzdHJlbmd0aFRleHQuaW5uZXJIVE1MID1cblx0XHRcdFx0J1N0cmVuZ3RoOiA8c3Ryb25nPicgKyBwd3NMMTBuLmJhZCArICc8L3N0cm9uZz4nO1xuXHRcdFx0YnJlYWs7XG5cdFx0Y2FzZSAzOlxuXHRcdFx0c3RyZW5ndGhUZXh0LmNsYXNzTGlzdC5hZGQoJ2dvb2QnKTtcblx0XHRcdHN0cmVuZ3RoVGV4dC5pbm5lckhUTUwgPVxuXHRcdFx0XHQnU3RyZW5ndGg6IDxzdHJvbmc+JyArIHB3c0wxMG4uZ29vZCArICc8L3N0cm9uZz4nO1xuXHRcdFx0YnJlYWs7XG5cdFx0Y2FzZSA0OlxuXHRcdFx0c3RyZW5ndGhUZXh0LmNsYXNzTGlzdC5hZGQoJ3N0cm9uZycpO1xuXHRcdFx0c3RyZW5ndGhUZXh0LmlubmVySFRNTCA9XG5cdFx0XHRcdCdTdHJlbmd0aDogPHN0cm9uZz4nICsgcHdzTDEwbi5zdHJvbmcgKyAnPC9zdHJvbmc+Jztcblx0XHRcdGJyZWFrO1xuXHRcdGNhc2UgNTpcblx0XHRcdHN0cmVuZ3RoVGV4dC5jbGFzc0xpc3QuYWRkKCdzaG9ydCcpO1xuXHRcdFx0c3RyZW5ndGhUZXh0LmlubmVySFRNTCA9XG5cdFx0XHRcdCdTdHJlbmd0aDogPHN0cm9uZz4nICsgcHdzTDEwbi5taXNtYXRjaCArICc8L3N0cm9uZz4nO1xuXHRcdFx0YnJlYWs7XG5cdFx0ZGVmYXVsdDpcblx0XHRcdHN0cmVuZ3RoVGV4dC5jbGFzc0xpc3QuYWRkKCdzaG9ydCcpO1xuXHRcdFx0c3RyZW5ndGhUZXh0LmlubmVySFRNTCA9XG5cdFx0XHRcdCdTdHJlbmd0aDogPHN0cm9uZz4nICsgcHdzTDEwbi5zaG9ydCArICc8L3N0cm9uZz4nO1xuXHR9XG5cdHN0cmVuZ3RoTWV0ZXIuc2V0QXR0cmlidXRlKCd2YWx1ZScsIHN0cmVuZ3RoKTtcblx0Ly8gT25seSBlbmFibGUgdGhlIHN1Ym1pdCBidXR0b24gaWYgdGhlIHBhc3N3b3JkIGlzIHN0cm9uZ1xuXHQvKlxuXHRpZiAoIDQgPT09IHN0cmVuZ3RoICkge1xuXHRcdHN1Ym1pdEJ1dHRvbi5yZW1vdmVBdHRyKCAnZGlzYWJsZWQnICk7XG5cdH0qL1xuXHRyZXR1cm4gc3RyZW5ndGg7XG59XG5cbmZ1bmN0aW9uIHNldHVwUGFzc3dvcmRTdHJlbmd0aCgpIHtcblx0Y29uc3QgY2hlY2tQYXNzd29yZFN0cmVuZ3RoID0gZG9jdW1lbnQucXVlcnlTZWxlY3Rvcihcblx0XHQnLnBhc3N3b3JkLXN0cmVuZ3RoLWNoZWNrJ1xuXHQpO1xuXHRpZiAoY2hlY2tQYXNzd29yZFN0cmVuZ3RoKSB7XG5cdFx0Y29uc3QgYmVmb3JlUGFzc3dvcmRDaGVja2VyID0gZG9jdW1lbnQucXVlcnlTZWxlY3Rvcihcblx0XHRcdCcuYS1mb3JtLXNob3ctcGFzc3dvcmQnXG5cdFx0KTtcblx0XHRjb25zdCBwYXNzd29yZE1ldGVyID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnbWV0ZXInKTtcblx0XHRjb25zdCBwYXNzd29yZE1ldGVyRGl2ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG5cdFx0Y29uc3QgcGFzc3dvcmRNZXRlclRleHQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdwJyk7XG5cdFx0Y29uc3QgcmVnaXN0ZXJCdXR0b24gPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcucmVnaXN0ZXItYnV0dG9uJyk7XG5cdFx0cGFzc3dvcmRNZXRlci5zZXRBdHRyaWJ1dGUoJ21heCcsICc0Jyk7XG5cdFx0cGFzc3dvcmRNZXRlci5zZXRBdHRyaWJ1dGUoJ2lkJywgJ3Bhc3N3b3JkLXN0cmVuZ3RoJyk7XG5cdFx0cGFzc3dvcmRNZXRlclRleHQuc2V0QXR0cmlidXRlKCdpZCcsICdwYXNzd29yZC1zdHJlbmd0aC10ZXh0Jyk7XG5cdFx0cGFzc3dvcmRNZXRlci5hcHBlbmRDaGlsZChwYXNzd29yZE1ldGVyRGl2KTtcblx0XHRiZWZvcmVQYXNzd29yZENoZWNrZXIuYWZ0ZXIocGFzc3dvcmRNZXRlcik7XG5cdFx0YmVmb3JlUGFzc3dvcmRDaGVja2VyLmFmdGVyKHBhc3N3b3JkTWV0ZXJUZXh0KTtcblxuXHRcdGNvbnN0IHBhc3N3b3JkRmllbGRzID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbChcblx0XHRcdCdpbnB1dFtuYW1lPXBhc3N3b3JkXSwgaW5wdXRbbmFtZT1uZXdfcGFzc3dvcmRdJ1xuXHRcdCk7XG5cdFx0aWYgKDAgPCBwYXNzd29yZEZpZWxkcy5sZW5ndGggJiYgcmVnaXN0ZXJCdXR0b24pIHtcblx0XHRcdHBhc3N3b3JkRmllbGRzLmZvckVhY2goZnVuY3Rpb24gKHBhc3N3b3JkRmllbGQpIHtcblx0XHRcdFx0cGFzc3dvcmRGaWVsZC5hZGRFdmVudExpc3RlbmVyKCdrZXl1cCcsIGZ1bmN0aW9uIChldmVudCkge1xuXHRcdFx0XHRcdHBhc3N3b3JkU3RyZW5ndGhDaGVja2VyKFxuXHRcdFx0XHRcdFx0ZXZlbnQudGFyZ2V0LnZhbHVlLCAvLyBQYXNzd29yZCBmaWVsZFxuXHRcdFx0XHRcdFx0cGFzc3dvcmRNZXRlciwgLy8gU3RyZW5ndGggbWV0ZXJcblx0XHRcdFx0XHRcdHBhc3N3b3JkTWV0ZXJUZXh0LCAvLyBTdHJlbmd0aCB0ZXh0IGluZGljYXRvclxuXHRcdFx0XHRcdFx0cmVnaXN0ZXJCdXR0b24sIC8vIFN1Ym1pdCBidXR0b25cblx0XHRcdFx0XHRcdFsnZGlzYWxsb3dlZCcsICdsaXN0ZWQnLCAnd29yZCddIC8vIGRpc2FsbG93ZWQgd29yZHNcblx0XHRcdFx0XHQpO1xuXHRcdFx0XHR9KTtcblx0XHRcdH0pO1xuXHRcdH1cblx0fVxufVxuXG5mdW5jdGlvbiBzZXR1cENvdW50cnlGaWVsZChjbGlja2VkTm90VVMpIHtcblx0Y29uc3QgY291bnRyeUZpZWxkID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignLm0tZm9ybS1jb3VudHJ5ICNjb3VudHJ5Jyk7XG5cdGNvbnN0IHppcFBhcmVudCA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5tLWZvcm0temlwLWNvZGUnKTtcblx0aWYgKCd1bmRlZmluZWQnID09PSB0eXBlb2YgY2xpY2tlZE5vdFVTKSB7XG5cdFx0Y2xpY2tlZE5vdFVTID0gZmFsc2U7XG5cdH1cblxuXHRjb25zdCBjb3VudHJ5U2VsZWN0b3IgPSBjb3VudHJ5RmllbGQucXVlcnlTZWxlY3RvckFsbCgnc2VsZWN0JywgJ2lucHV0Jyk7XG5cdGlmICgwIDwgY291bnRyeVNlbGVjdG9yLmxlbmd0aCkge1xuXHRcdGNvdW50cnlTZWxlY3Rvci5mb3JFYWNoKGZ1bmN0aW9uIChjb3VudHJ5U2VsZWN0b3JGaWVsZCkge1xuXHRcdFx0aWYgKCFjb3VudHJ5U2VsZWN0b3JGaWVsZC5jbGFzc0xpc3QuY29udGFpbnMoJ25vdC1pbi11cycpKSB7XG5cdFx0XHRcdGNsaWNrZWROb3RVUyA9IHRydWU7XG5cdFx0XHR9XG5cdFx0fSk7XG5cdH1cblxuXHRpZiAoY291bnRyeUZpZWxkICYmIHppcFBhcmVudCkge1xuXHRcdHRvZ2dsZVppcENvdW50cnlTZWxlY3Rvcihjb3VudHJ5RmllbGQsIHppcFBhcmVudCwgY2xpY2tlZE5vdFVTKTtcblx0fVxufVxuXG5mdW5jdGlvbiB0b2dnbGVaaXBDb3VudHJ5U2VsZWN0b3IoY291bnRyeUZpZWxkLCB6aXBQYXJlbnQsIGNsaWNrZWROb3RVUykge1xuXHRjb25zdCB6aXBGaWVsZCA9IHppcFBhcmVudC5xdWVyeVNlbGVjdG9yKCcjemlwLWNvZGUnKTtcblx0Y29uc3QgemlwTGFiZWwgPSB6aXBQYXJlbnQucXVlcnlTZWxlY3RvcignbGFiZWwnKTtcblx0Y29uc3Qgc2hvd0NvdW50cnlNZXNzYWdlID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG5cdGNvbnN0IGNvdW50cnlNZXNzYWdlVGV4dCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ3NtYWxsJyk7XG5cdGNvbnN0IG5vdEluVXMgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTtcblx0Y29uc3QgY291bnRyeVZhbHVlID0gY291bnRyeUZpZWxkLnZhbHVlO1xuXHRzaG93Q291bnRyeU1lc3NhZ2Uuc2V0QXR0cmlidXRlKCdjbGFzcycsICdhLWZvcm0tY2FwdGlvbiBsb2NhdGlvbicpO1xuXHRzaG93Q291bnRyeU1lc3NhZ2UuaW5uZXJIVE1MID0gJzxzbWFsbD48L3NtYWxsPic7XG5cdHppcFBhcmVudC5hcHBlbmQoc2hvd0NvdW50cnlNZXNzYWdlKTtcblx0bm90SW5Vcy5zZXRBdHRyaWJ1dGUoJ2NsYXNzJywgJ2EtZm9ybS1jYXB0aW9uIHNob3ctY291bnRyeScpO1xuXHRzZXRaaXBTZXR0aW5ncyhjb3VudHJ5VmFsdWUsIHppcEZpZWxkLCB6aXBMYWJlbCk7XG5cdGlmIChcblx0XHRmYWxzZSA9PT0gY2xpY2tlZE5vdFVTICYmXG5cdFx0KGNvdW50cnlWYWx1ZSA9PT0gJycgfHwgY291bnRyeVZhbHVlID09PSAnVVMnKVxuXHQpIHtcblx0XHRjb3VudHJ5RmllbGQucGFyZW50Tm9kZS5zdHlsZS5kaXNwbGF5ID0gJ25vbmUnO1xuXHRcdHppcEZpZWxkLmlubmVySFRNTCA9IGNvdW50cnlNZXNzYWdlVGV4dDtcblx0XHRub3RJblVzLmlubmVySFRNTCA9XG5cdFx0XHQnPGEgaHJlZj1cIiNcIiBpZD1cInJlZ2lzdHJhdGlvbl9zaG93X2NvdW50cnlcIj48c21hbGw+Tm90IGluIHRoZSBVUz88L3NtYWxsPjwvYT4nO1xuXHRcdHppcFBhcmVudC5hcHBlbmQobm90SW5Vcyk7XG5cdH0gZWxzZSB7XG5cdFx0Y291bnRyeUZpZWxkLnBhcmVudE5vZGUuc3R5bGUuZGlzcGxheSA9ICdibG9jayc7XG5cdFx0Ly8gY291bGQgYWxzbyBkbyBhIHNsaWRlZG93biB0aGluZywgbWF5YmUuXG5cdH1cbn1cblxuZnVuY3Rpb24gc2V0WmlwU2V0dGluZ3MoY291bnRyeVZhbHVlLCB6aXBGaWVsZCwgemlwTGFiZWwpIHtcblx0aWYgKCcnID09PSBjb3VudHJ5VmFsdWUgfHwgJ1VTJyA9PT0gY291bnRyeVZhbHVlKSB7XG5cdFx0emlwRmllbGQuc2V0QXR0cmlidXRlKCd0eXBlJywgJ3RlbCcpO1xuXHRcdHppcExhYmVsLmlubmVySFRNTCA9XG5cdFx0XHQnWmlwIENvZGU6IDxzcGFuIHRpdGxlPVwiVGhpcyBmaWVsZCBpcyByZXF1aXJlZC5cIiBjbGFzcz1cImEtZm9ybS1pdGVtLXJlcXVpcmVkXCI+Kjwvc3Bhbj4nO1xuXHR9IGVsc2Uge1xuXHRcdHppcEZpZWxkLnNldEF0dHJpYnV0ZSgndHlwZScsICd0ZXh0Jyk7XG5cdFx0emlwTGFiZWwuaW5uZXJIVE1MID1cblx0XHRcdCdQb3N0YWwgQ29kZTogPHNwYW4gdGl0bGU9XCJUaGlzIGZpZWxkIGlzIHJlcXVpcmVkLlwiIGNsYXNzPVwiYS1mb3JtLWl0ZW0tcmVxdWlyZWRcIj4qPC9zcGFuPic7XG5cdH1cbn1cblxuZnVuY3Rpb24gc2hvd091dHNpZGVVc0ZpZWxkcygpIHtcblx0Y29uc3Qgc2hvd0NvdW50cnkgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcjcmVnaXN0cmF0aW9uX3Nob3dfY291bnRyeScpO1xuXHRpZiAoc2hvd0NvdW50cnkpIHtcblx0XHRzaG93Q291bnRyeS5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uIChldmVudCkge1xuXHRcdFx0ZXZlbnQucHJldmVudERlZmF1bHQoKTtcblx0XHRcdGV2ZW50LnRhcmdldC5zdHlsZS5kaXNwbGF5ID0gJ25vbmUnO1xuXHRcdFx0c2V0dXBDb3VudHJ5RmllbGQodHJ1ZSk7XG5cdFx0fSk7XG5cdH1cbn1cblxuZnVuY3Rpb24gZ2V0Q2l0eVN0YXRlRnJvbVppcCgpIHtcblx0Y29uc3QgY2l0eUZpZWxkID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignaW5wdXRbbmFtZT1cImNpdHlcIl0nKTtcblx0Y29uc3Qgc3RhdGVGaWVsZCA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJ2lucHV0W25hbWU9XCJzdGF0ZVwiXScpO1xuXHRjb25zdCB6aXBGaWVsZCA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJyN6aXAtY29kZScpO1xuXHRjb25zdCBjb3VudHJ5RmllbGQgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcubS1mb3JtLWNvdW50cnkgI2NvdW50cnknKTtcblx0aWYgKHppcEZpZWxkKSB7XG5cdFx0emlwRmllbGQuYWRkRXZlbnRMaXN0ZW5lcignYmx1cicsICgpID0+IHtcblx0XHRcdGNoZWNrWmlwQ291bnRyeShjaXR5RmllbGQsIHN0YXRlRmllbGQsIHppcEZpZWxkLCBjb3VudHJ5RmllbGQpO1xuXHRcdH0pO1xuXHR9XG5cdGlmIChjb3VudHJ5RmllbGQpIHtcblx0XHRjb3VudHJ5RmllbGQuYWRkRXZlbnRMaXN0ZW5lcignY2hhbmdlJywgKCkgPT4ge1xuXHRcdFx0Y2hlY2taaXBDb3VudHJ5KGNpdHlGaWVsZCwgc3RhdGVGaWVsZCwgemlwRmllbGQsIGNvdW50cnlGaWVsZCk7XG5cdFx0fSk7XG5cdH1cbn1cblxuZnVuY3Rpb24gY2hlY2taaXBDb3VudHJ5KGNpdHlGaWVsZCwgc3RhdGVGaWVsZCwgemlwRmllbGQsIGNvdW50cnlGaWVsZCkge1xuXHRsZXQgY291bnRyeVZhbHVlID0gY291bnRyeUZpZWxkLnZhbHVlO1xuXHRpZiAoJycgPT09IGNvdW50cnlWYWx1ZSkge1xuXHRcdGNvdW50cnlWYWx1ZSA9ICdVUyc7XG5cdFx0Y291bnRyeUZpZWxkLnZhbHVlID0gY291bnRyeVZhbHVlO1xuXHR9XG5cdGNvbnN0IHppcFZhbHVlID0gemlwRmllbGQudmFsdWU7XG5cdGlmICgnJyAhPT0gemlwVmFsdWUpIHtcblx0XHRjb25zdCBsb2NhdGlvbiA9IHtcblx0XHRcdHppcF9jb2RlOiB6aXBWYWx1ZSxcblx0XHRcdGNvdW50cnk6IGNvdW50cnlWYWx1ZSxcblx0XHR9O1xuXHRcdGNvbnN0IGxvY2F0aW9uRWxlbWVudCA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5sb2NhdGlvbiBzbWFsbCcpO1xuXHRcdGlmIChsb2NhdGlvbkVsZW1lbnQpIHtcblx0XHRcdGxldCB1cmwgPVxuXHRcdFx0XHR1c2VyX2FjY291bnRfbWFuYWdlbWVudF9yZXN0LnNpdGVfdXJsICtcblx0XHRcdFx0dXNlcl9hY2NvdW50X21hbmFnZW1lbnRfcmVzdC5yZXN0X25hbWVzcGFjZSArXG5cdFx0XHRcdCcvY2hlY2stemlwJztcblx0XHRcdHVybCArPSAnPycgKyBuZXcgVVJMU2VhcmNoUGFyYW1zKGxvY2F0aW9uKS50b1N0cmluZygpO1xuXHRcdFx0ZmV0Y2godXJsKVxuXHRcdFx0XHQudGhlbigocmVzcG9uc2UpID0+IHJlc3BvbnNlLmpzb24oKSlcblx0XHRcdFx0LnRoZW4oKGRhdGEpID0+IHtcblx0XHRcdFx0XHRpZiAoJ3N1Y2Nlc3MnID09PSBkYXRhLnN0YXR1cykge1xuXHRcdFx0XHRcdFx0bGV0IGxvY2F0aW9uU3RyaW5nID0gJyc7XG5cdFx0XHRcdFx0XHRsb2NhdGlvblN0cmluZyArPSBkYXRhLmNpdHk7XG5cdFx0XHRcdFx0XHRpZiAoY2l0eUZpZWxkKSB7XG5cdFx0XHRcdFx0XHRcdGNpdHlGaWVsZC52YWx1ZSA9IGRhdGEuY2l0eTtcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdGlmIChkYXRhLmNpdHkgIT09IGRhdGEuc3RhdGUpIHtcblx0XHRcdFx0XHRcdFx0bG9jYXRpb25TdHJpbmcgKz0gJywgJyArIGRhdGEuc3RhdGU7XG5cdFx0XHRcdFx0XHRcdGlmIChzdGF0ZUZpZWxkKSB7XG5cdFx0XHRcdFx0XHRcdFx0c3RhdGVGaWVsZC52YWx1ZSA9IGRhdGEuc3RhdGU7XG5cdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdGlmICgnVVMnICE9PSBjb3VudHJ5VmFsdWUpIHtcblx0XHRcdFx0XHRcdFx0bG9jYXRpb25TdHJpbmcgKz0gJywgJyArIGNvdW50cnlWYWx1ZTtcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdGxvY2F0aW9uRWxlbWVudC5pbm5lckhUTUwgPSBsb2NhdGlvblN0cmluZztcblx0XHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdFx0bG9jYXRpb25FbGVtZW50LmlubmVySFRNTCA9ICcnO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSlcblx0XHRcdFx0LmNhdGNoKCgpID0+IHtcblx0XHRcdFx0XHRsb2NhdGlvbkVsZW1lbnQuaW5uZXJIVE1MID0gJyc7XG5cdFx0XHRcdH0pO1xuXHRcdH1cblx0fVxufVxuXG5kb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgZnVuY3Rpb24gKCkge1xuXHRzaG93UGFzc3dvcmRGaWVsZCgpO1xuXHRzZXR1cFBhc3N3b3JkU3RyZW5ndGgoKTtcblx0c2V0dXBDb3VudHJ5RmllbGQoKTtcblx0c2hvd091dHNpZGVVc0ZpZWxkcygpO1xuXHRnZXRDaXR5U3RhdGVGcm9tWmlwKCk7XG59KTtcbiJdfQ==
