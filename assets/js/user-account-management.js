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

  if (countryField) {
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

    var zipParent = document.querySelector('.m-form-zip-code');

    if (zipParent) {
      toggleZipCountrySelector(countryField, zipParent, clickedNotUS);
    }
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

//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInVzZXIuanMiXSwibmFtZXMiOlsiZG9jdW1lbnQiXSwibWFwcGluZ3MiOiI7OztBQUFBO0FBQ0E7QUFEQTtBQUdBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUZDO0FBSUQ7QUFGQTtBQUlBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFZQTtBQUNBO0FBQ0E7QUFWQTtBQUNBO0FBV0M7QUFURDtBQVdBO0FBVEE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQWlCQztBQWZEO0FBaUJBO0FBZkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQWVDO0FBYkQ7QUFlQTtBQWJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFtQkM7QUFqQkQ7QUFtQkE7QUFqQkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFxQkM7QUFuQkQ7QUFxQkE7QUFuQkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBb0JDO0FBbEJEO0FBb0JBO0FBbEJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBa0JDO0FBaEJEO0FBa0JBO0FBaEJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQWFDO0FBWEQ7QUFhQUE7QUFYQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBYUMiLCJmaWxlIjoidXNlci1hY2NvdW50LW1hbmFnZW1lbnQuanMiLCJzb3VyY2VzQ29udGVudCI6WyJmdW5jdGlvbiBzaG93UGFzc3dvcmRGaWVsZCgpIHtcblx0Y29uc3QgdGhlRmllbGQgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcucGFzc3dvcmQtc2hvdycpO1xuXHRpZiAodGhlRmllbGQpIHtcblx0XHRjb25zdCB0b2dnbGVGaWVsZCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuXHRcdHRvZ2dsZUZpZWxkLmlubmVySFRNTCA9XG5cdFx0XHQnPGRpdiBjbGFzcz1cImEtZm9ybS1zaG93LXBhc3N3b3JkIGEtZm9ybS1jYXB0aW9uXCI+PGxhYmVsPjxpbnB1dCB0eXBlPVwiY2hlY2tib3hcIiBuYW1lPVwic2hvd19wYXNzd29yZFwiIGlkPVwic2hvdy1wYXNzd29yZC1jaGVja2JveFwiIHZhbHVlPVwiMVwiPiBTaG93IHBhc3N3b3JkPC9sYWJlbD48L2Rpdj4nO1xuXHRcdHRoZUZpZWxkLmFmdGVyKHRvZ2dsZUZpZWxkKTtcblx0XHRjb25zdCBjaGVja2JveEZpZWxkID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignI3Nob3ctcGFzc3dvcmQtY2hlY2tib3gnKTtcblx0XHRpZiAoY2hlY2tib3hGaWVsZCkge1xuXHRcdFx0Y2hlY2tib3hGaWVsZC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uICgpIHtcblx0XHRcdFx0aWYgKGV2ZW50LmN1cnJlbnRUYXJnZXQuY2hlY2tlZCA9PT0gdHJ1ZSkge1xuXHRcdFx0XHRcdHRoZUZpZWxkLnNldEF0dHJpYnV0ZSgndHlwZScsICd0ZXh0Jyk7XG5cdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0dGhlRmllbGQuc2V0QXR0cmlidXRlKCd0eXBlJywgJ3Bhc3N3b3JkJyk7XG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXHRcdH1cblx0fVxufVxuXG5mdW5jdGlvbiBwYXNzd29yZFN0cmVuZ3RoQ2hlY2tlcihcblx0cGFzc3dvcmQsXG5cdHN0cmVuZ3RoTWV0ZXIsXG5cdHN0cmVuZ3RoVGV4dCxcblx0c3VibWl0QnV0dG9uLFxuXHRkaXNhbGxvd0xpc3RBcnJheVxuKSB7XG5cdHN0cmVuZ3RoVGV4dC5jbGFzc0xpc3QucmVtb3ZlKCdzaG9ydCcsICdiYWQnLCAnZ29vZCcsICdzdHJvbmcnKTtcblxuXHQvLyBFeHRlbmQgb3VyIGRpc2FsbG93TGlzdCBhcnJheSB3aXRoIHRob3NlIGZyb20gdGhlIGlucHV0cyAmIHNpdGUgZGF0YVxuXHRkaXNhbGxvd0xpc3RBcnJheSA9IGRpc2FsbG93TGlzdEFycmF5LmNvbmNhdChcblx0XHR3cC5wYXNzd29yZFN0cmVuZ3RoLnVzZXJJbnB1dERpc2FsbG93ZWRMaXN0KClcblx0KTtcblxuXHQvLyBHZXQgdGhlIHBhc3N3b3JkIHN0cmVuZ3RoXG5cdGNvbnN0IHN0cmVuZ3RoID0gd3AucGFzc3dvcmRTdHJlbmd0aC5tZXRlcihcblx0XHRwYXNzd29yZCxcblx0XHRkaXNhbGxvd0xpc3RBcnJheSxcblx0XHRwYXNzd29yZFxuXHQpO1xuXG5cdHN3aXRjaCAoc3RyZW5ndGgpIHtcblx0XHRjYXNlIDI6XG5cdFx0XHRzdHJlbmd0aFRleHQuY2xhc3NMaXN0LmFkZCgnYmFkJyk7XG5cdFx0XHRzdHJlbmd0aFRleHQuaW5uZXJIVE1MID1cblx0XHRcdFx0J1N0cmVuZ3RoOiA8c3Ryb25nPicgKyBwd3NMMTBuLmJhZCArICc8L3N0cm9uZz4nO1xuXHRcdFx0YnJlYWs7XG5cdFx0Y2FzZSAzOlxuXHRcdFx0c3RyZW5ndGhUZXh0LmNsYXNzTGlzdC5hZGQoJ2dvb2QnKTtcblx0XHRcdHN0cmVuZ3RoVGV4dC5pbm5lckhUTUwgPVxuXHRcdFx0XHQnU3RyZW5ndGg6IDxzdHJvbmc+JyArIHB3c0wxMG4uZ29vZCArICc8L3N0cm9uZz4nO1xuXHRcdFx0YnJlYWs7XG5cdFx0Y2FzZSA0OlxuXHRcdFx0c3RyZW5ndGhUZXh0LmNsYXNzTGlzdC5hZGQoJ3N0cm9uZycpO1xuXHRcdFx0c3RyZW5ndGhUZXh0LmlubmVySFRNTCA9XG5cdFx0XHRcdCdTdHJlbmd0aDogPHN0cm9uZz4nICsgcHdzTDEwbi5zdHJvbmcgKyAnPC9zdHJvbmc+Jztcblx0XHRcdGJyZWFrO1xuXHRcdGNhc2UgNTpcblx0XHRcdHN0cmVuZ3RoVGV4dC5jbGFzc0xpc3QuYWRkKCdzaG9ydCcpO1xuXHRcdFx0c3RyZW5ndGhUZXh0LmlubmVySFRNTCA9XG5cdFx0XHRcdCdTdHJlbmd0aDogPHN0cm9uZz4nICsgcHdzTDEwbi5taXNtYXRjaCArICc8L3N0cm9uZz4nO1xuXHRcdFx0YnJlYWs7XG5cdFx0ZGVmYXVsdDpcblx0XHRcdHN0cmVuZ3RoVGV4dC5jbGFzc0xpc3QuYWRkKCdzaG9ydCcpO1xuXHRcdFx0c3RyZW5ndGhUZXh0LmlubmVySFRNTCA9XG5cdFx0XHRcdCdTdHJlbmd0aDogPHN0cm9uZz4nICsgcHdzTDEwbi5zaG9ydCArICc8L3N0cm9uZz4nO1xuXHR9XG5cdHN0cmVuZ3RoTWV0ZXIuc2V0QXR0cmlidXRlKCd2YWx1ZScsIHN0cmVuZ3RoKTtcblx0Ly8gT25seSBlbmFibGUgdGhlIHN1Ym1pdCBidXR0b24gaWYgdGhlIHBhc3N3b3JkIGlzIHN0cm9uZ1xuXHQvKlxuXHRpZiAoIDQgPT09IHN0cmVuZ3RoICkge1xuXHRcdHN1Ym1pdEJ1dHRvbi5yZW1vdmVBdHRyKCAnZGlzYWJsZWQnICk7XG5cdH0qL1xuXHRyZXR1cm4gc3RyZW5ndGg7XG59XG5cbmZ1bmN0aW9uIHNldHVwUGFzc3dvcmRTdHJlbmd0aCgpIHtcblx0Y29uc3QgY2hlY2tQYXNzd29yZFN0cmVuZ3RoID0gZG9jdW1lbnQucXVlcnlTZWxlY3Rvcihcblx0XHQnLnBhc3N3b3JkLXN0cmVuZ3RoLWNoZWNrJ1xuXHQpO1xuXHRpZiAoY2hlY2tQYXNzd29yZFN0cmVuZ3RoKSB7XG5cdFx0Y29uc3QgYmVmb3JlUGFzc3dvcmRDaGVja2VyID0gZG9jdW1lbnQucXVlcnlTZWxlY3Rvcihcblx0XHRcdCcuYS1mb3JtLXNob3ctcGFzc3dvcmQnXG5cdFx0KTtcblx0XHRjb25zdCBwYXNzd29yZE1ldGVyID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnbWV0ZXInKTtcblx0XHRjb25zdCBwYXNzd29yZE1ldGVyRGl2ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG5cdFx0Y29uc3QgcGFzc3dvcmRNZXRlclRleHQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdwJyk7XG5cdFx0Y29uc3QgcmVnaXN0ZXJCdXR0b24gPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcucmVnaXN0ZXItYnV0dG9uJyk7XG5cdFx0cGFzc3dvcmRNZXRlci5zZXRBdHRyaWJ1dGUoJ21heCcsICc0Jyk7XG5cdFx0cGFzc3dvcmRNZXRlci5zZXRBdHRyaWJ1dGUoJ2lkJywgJ3Bhc3N3b3JkLXN0cmVuZ3RoJyk7XG5cdFx0cGFzc3dvcmRNZXRlclRleHQuc2V0QXR0cmlidXRlKCdpZCcsICdwYXNzd29yZC1zdHJlbmd0aC10ZXh0Jyk7XG5cdFx0cGFzc3dvcmRNZXRlci5hcHBlbmRDaGlsZChwYXNzd29yZE1ldGVyRGl2KTtcblx0XHRiZWZvcmVQYXNzd29yZENoZWNrZXIuYWZ0ZXIocGFzc3dvcmRNZXRlcik7XG5cdFx0YmVmb3JlUGFzc3dvcmRDaGVja2VyLmFmdGVyKHBhc3N3b3JkTWV0ZXJUZXh0KTtcblxuXHRcdGNvbnN0IHBhc3N3b3JkRmllbGRzID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbChcblx0XHRcdCdpbnB1dFtuYW1lPXBhc3N3b3JkXSwgaW5wdXRbbmFtZT1uZXdfcGFzc3dvcmRdJ1xuXHRcdCk7XG5cdFx0aWYgKDAgPCBwYXNzd29yZEZpZWxkcy5sZW5ndGggJiYgcmVnaXN0ZXJCdXR0b24pIHtcblx0XHRcdHBhc3N3b3JkRmllbGRzLmZvckVhY2goZnVuY3Rpb24gKHBhc3N3b3JkRmllbGQpIHtcblx0XHRcdFx0cGFzc3dvcmRGaWVsZC5hZGRFdmVudExpc3RlbmVyKCdrZXl1cCcsIGZ1bmN0aW9uIChldmVudCkge1xuXHRcdFx0XHRcdHBhc3N3b3JkU3RyZW5ndGhDaGVja2VyKFxuXHRcdFx0XHRcdFx0ZXZlbnQudGFyZ2V0LnZhbHVlLCAvLyBQYXNzd29yZCBmaWVsZFxuXHRcdFx0XHRcdFx0cGFzc3dvcmRNZXRlciwgLy8gU3RyZW5ndGggbWV0ZXJcblx0XHRcdFx0XHRcdHBhc3N3b3JkTWV0ZXJUZXh0LCAvLyBTdHJlbmd0aCB0ZXh0IGluZGljYXRvclxuXHRcdFx0XHRcdFx0cmVnaXN0ZXJCdXR0b24sIC8vIFN1Ym1pdCBidXR0b25cblx0XHRcdFx0XHRcdFsnZGlzYWxsb3dlZCcsICdsaXN0ZWQnLCAnd29yZCddIC8vIGRpc2FsbG93ZWQgd29yZHNcblx0XHRcdFx0XHQpO1xuXHRcdFx0XHR9KTtcblx0XHRcdH0pO1xuXHRcdH1cblx0fVxufVxuXG5mdW5jdGlvbiBzZXR1cENvdW50cnlGaWVsZChjbGlja2VkTm90VVMpIHtcblx0Y29uc3QgY291bnRyeUZpZWxkID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignLm0tZm9ybS1jb3VudHJ5ICNjb3VudHJ5Jyk7XG5cdGlmIChjb3VudHJ5RmllbGQpIHtcblx0XHRpZiAoJ3VuZGVmaW5lZCcgPT09IHR5cGVvZiBjbGlja2VkTm90VVMpIHtcblx0XHRcdGNsaWNrZWROb3RVUyA9IGZhbHNlO1xuXHRcdH1cblx0XHRjb25zdCBjb3VudHJ5U2VsZWN0b3IgPSBjb3VudHJ5RmllbGQucXVlcnlTZWxlY3RvckFsbChcblx0XHRcdCdzZWxlY3QnLFxuXHRcdFx0J2lucHV0J1xuXHRcdCk7XG5cdFx0aWYgKDAgPCBjb3VudHJ5U2VsZWN0b3IubGVuZ3RoKSB7XG5cdFx0XHRjb3VudHJ5U2VsZWN0b3IuZm9yRWFjaChmdW5jdGlvbiAoY291bnRyeVNlbGVjdG9yRmllbGQpIHtcblx0XHRcdFx0aWYgKCFjb3VudHJ5U2VsZWN0b3JGaWVsZC5jbGFzc0xpc3QuY29udGFpbnMoJ25vdC1pbi11cycpKSB7XG5cdFx0XHRcdFx0Y2xpY2tlZE5vdFVTID0gdHJ1ZTtcblx0XHRcdFx0fVxuXHRcdFx0fSk7XG5cdFx0fVxuXHRcdGNvbnN0IHppcFBhcmVudCA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5tLWZvcm0temlwLWNvZGUnKTtcblx0XHRpZiAoemlwUGFyZW50KSB7XG5cdFx0XHR0b2dnbGVaaXBDb3VudHJ5U2VsZWN0b3IoY291bnRyeUZpZWxkLCB6aXBQYXJlbnQsIGNsaWNrZWROb3RVUyk7XG5cdFx0fVxuXHR9XG59XG5cbmZ1bmN0aW9uIHRvZ2dsZVppcENvdW50cnlTZWxlY3Rvcihjb3VudHJ5RmllbGQsIHppcFBhcmVudCwgY2xpY2tlZE5vdFVTKSB7XG5cdGNvbnN0IHppcEZpZWxkID0gemlwUGFyZW50LnF1ZXJ5U2VsZWN0b3IoJyN6aXAtY29kZScpO1xuXHRjb25zdCB6aXBMYWJlbCA9IHppcFBhcmVudC5xdWVyeVNlbGVjdG9yKCdsYWJlbCcpO1xuXHRjb25zdCBzaG93Q291bnRyeU1lc3NhZ2UgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTtcblx0Y29uc3QgY291bnRyeU1lc3NhZ2VUZXh0ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnc21hbGwnKTtcblx0Y29uc3Qgbm90SW5VcyA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuXHRjb25zdCBjb3VudHJ5VmFsdWUgPSBjb3VudHJ5RmllbGQudmFsdWU7XG5cdHNob3dDb3VudHJ5TWVzc2FnZS5zZXRBdHRyaWJ1dGUoJ2NsYXNzJywgJ2EtZm9ybS1jYXB0aW9uIGxvY2F0aW9uJyk7XG5cdHNob3dDb3VudHJ5TWVzc2FnZS5pbm5lckhUTUwgPSAnPHNtYWxsPjwvc21hbGw+Jztcblx0emlwUGFyZW50LmFwcGVuZChzaG93Q291bnRyeU1lc3NhZ2UpO1xuXHRub3RJblVzLnNldEF0dHJpYnV0ZSgnY2xhc3MnLCAnYS1mb3JtLWNhcHRpb24gc2hvdy1jb3VudHJ5Jyk7XG5cdHNldFppcFNldHRpbmdzKGNvdW50cnlWYWx1ZSwgemlwRmllbGQsIHppcExhYmVsKTtcblx0aWYgKFxuXHRcdGZhbHNlID09PSBjbGlja2VkTm90VVMgJiZcblx0XHQoY291bnRyeVZhbHVlID09PSAnJyB8fCBjb3VudHJ5VmFsdWUgPT09ICdVUycpXG5cdCkge1xuXHRcdGNvdW50cnlGaWVsZC5wYXJlbnROb2RlLnN0eWxlLmRpc3BsYXkgPSAnbm9uZSc7XG5cdFx0emlwRmllbGQuaW5uZXJIVE1MID0gY291bnRyeU1lc3NhZ2VUZXh0O1xuXHRcdG5vdEluVXMuaW5uZXJIVE1MID1cblx0XHRcdCc8YSBocmVmPVwiI1wiIGlkPVwicmVnaXN0cmF0aW9uX3Nob3dfY291bnRyeVwiPjxzbWFsbD5Ob3QgaW4gdGhlIFVTPzwvc21hbGw+PC9hPic7XG5cdFx0emlwUGFyZW50LmFwcGVuZChub3RJblVzKTtcblx0fSBlbHNlIHtcblx0XHRjb3VudHJ5RmllbGQucGFyZW50Tm9kZS5zdHlsZS5kaXNwbGF5ID0gJ2Jsb2NrJztcblx0XHQvLyBjb3VsZCBhbHNvIGRvIGEgc2xpZGVkb3duIHRoaW5nLCBtYXliZS5cblx0fVxufVxuXG5mdW5jdGlvbiBzZXRaaXBTZXR0aW5ncyhjb3VudHJ5VmFsdWUsIHppcEZpZWxkLCB6aXBMYWJlbCkge1xuXHRpZiAoJycgPT09IGNvdW50cnlWYWx1ZSB8fCAnVVMnID09PSBjb3VudHJ5VmFsdWUpIHtcblx0XHR6aXBGaWVsZC5zZXRBdHRyaWJ1dGUoJ3R5cGUnLCAndGVsJyk7XG5cdFx0emlwTGFiZWwuaW5uZXJIVE1MID1cblx0XHRcdCdaaXAgQ29kZTogPHNwYW4gdGl0bGU9XCJUaGlzIGZpZWxkIGlzIHJlcXVpcmVkLlwiIGNsYXNzPVwiYS1mb3JtLWl0ZW0tcmVxdWlyZWRcIj4qPC9zcGFuPic7XG5cdH0gZWxzZSB7XG5cdFx0emlwRmllbGQuc2V0QXR0cmlidXRlKCd0eXBlJywgJ3RleHQnKTtcblx0XHR6aXBMYWJlbC5pbm5lckhUTUwgPVxuXHRcdFx0J1Bvc3RhbCBDb2RlOiA8c3BhbiB0aXRsZT1cIlRoaXMgZmllbGQgaXMgcmVxdWlyZWQuXCIgY2xhc3M9XCJhLWZvcm0taXRlbS1yZXF1aXJlZFwiPio8L3NwYW4+Jztcblx0fVxufVxuXG5mdW5jdGlvbiBzaG93T3V0c2lkZVVzRmllbGRzKCkge1xuXHRjb25zdCBzaG93Q291bnRyeSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJyNyZWdpc3RyYXRpb25fc2hvd19jb3VudHJ5Jyk7XG5cdGlmIChzaG93Q291bnRyeSkge1xuXHRcdHNob3dDb3VudHJ5LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgZnVuY3Rpb24gKGV2ZW50KSB7XG5cdFx0XHRldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdFx0ZXZlbnQudGFyZ2V0LnN0eWxlLmRpc3BsYXkgPSAnbm9uZSc7XG5cdFx0XHRzZXR1cENvdW50cnlGaWVsZCh0cnVlKTtcblx0XHR9KTtcblx0fVxufVxuXG5mdW5jdGlvbiBnZXRDaXR5U3RhdGVGcm9tWmlwKCkge1xuXHRjb25zdCBjaXR5RmllbGQgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCdpbnB1dFtuYW1lPVwiY2l0eVwiXScpO1xuXHRjb25zdCBzdGF0ZUZpZWxkID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignaW5wdXRbbmFtZT1cInN0YXRlXCJdJyk7XG5cdGNvbnN0IHppcEZpZWxkID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignI3ppcC1jb2RlJyk7XG5cdGNvbnN0IGNvdW50cnlGaWVsZCA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5tLWZvcm0tY291bnRyeSAjY291bnRyeScpO1xuXHRpZiAoemlwRmllbGQpIHtcblx0XHR6aXBGaWVsZC5hZGRFdmVudExpc3RlbmVyKCdibHVyJywgKCkgPT4ge1xuXHRcdFx0Y2hlY2taaXBDb3VudHJ5KGNpdHlGaWVsZCwgc3RhdGVGaWVsZCwgemlwRmllbGQsIGNvdW50cnlGaWVsZCk7XG5cdFx0fSk7XG5cdH1cblx0aWYgKGNvdW50cnlGaWVsZCkge1xuXHRcdGNvdW50cnlGaWVsZC5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCAoKSA9PiB7XG5cdFx0XHRjaGVja1ppcENvdW50cnkoY2l0eUZpZWxkLCBzdGF0ZUZpZWxkLCB6aXBGaWVsZCwgY291bnRyeUZpZWxkKTtcblx0XHR9KTtcblx0fVxufVxuXG5mdW5jdGlvbiBjaGVja1ppcENvdW50cnkoY2l0eUZpZWxkLCBzdGF0ZUZpZWxkLCB6aXBGaWVsZCwgY291bnRyeUZpZWxkKSB7XG5cdGxldCBjb3VudHJ5VmFsdWUgPSBjb3VudHJ5RmllbGQudmFsdWU7XG5cdGlmICgnJyA9PT0gY291bnRyeVZhbHVlKSB7XG5cdFx0Y291bnRyeVZhbHVlID0gJ1VTJztcblx0XHRjb3VudHJ5RmllbGQudmFsdWUgPSBjb3VudHJ5VmFsdWU7XG5cdH1cblx0Y29uc3QgemlwVmFsdWUgPSB6aXBGaWVsZC52YWx1ZTtcblx0aWYgKCcnICE9PSB6aXBWYWx1ZSkge1xuXHRcdGNvbnN0IGxvY2F0aW9uID0ge1xuXHRcdFx0emlwX2NvZGU6IHppcFZhbHVlLFxuXHRcdFx0Y291bnRyeTogY291bnRyeVZhbHVlLFxuXHRcdH07XG5cdFx0Y29uc3QgbG9jYXRpb25FbGVtZW50ID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignLmxvY2F0aW9uIHNtYWxsJyk7XG5cdFx0aWYgKGxvY2F0aW9uRWxlbWVudCkge1xuXHRcdFx0bGV0IHVybCA9XG5cdFx0XHRcdHVzZXJfYWNjb3VudF9tYW5hZ2VtZW50X3Jlc3Quc2l0ZV91cmwgK1xuXHRcdFx0XHR1c2VyX2FjY291bnRfbWFuYWdlbWVudF9yZXN0LnJlc3RfbmFtZXNwYWNlICtcblx0XHRcdFx0Jy9jaGVjay16aXAnO1xuXHRcdFx0dXJsICs9ICc/JyArIG5ldyBVUkxTZWFyY2hQYXJhbXMobG9jYXRpb24pLnRvU3RyaW5nKCk7XG5cdFx0XHRmZXRjaCh1cmwpXG5cdFx0XHRcdC50aGVuKChyZXNwb25zZSkgPT4gcmVzcG9uc2UuanNvbigpKVxuXHRcdFx0XHQudGhlbigoZGF0YSkgPT4ge1xuXHRcdFx0XHRcdGlmICgnc3VjY2VzcycgPT09IGRhdGEuc3RhdHVzKSB7XG5cdFx0XHRcdFx0XHRsZXQgbG9jYXRpb25TdHJpbmcgPSAnJztcblx0XHRcdFx0XHRcdGxvY2F0aW9uU3RyaW5nICs9IGRhdGEuY2l0eTtcblx0XHRcdFx0XHRcdGlmIChjaXR5RmllbGQpIHtcblx0XHRcdFx0XHRcdFx0Y2l0eUZpZWxkLnZhbHVlID0gZGF0YS5jaXR5O1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0aWYgKGRhdGEuY2l0eSAhPT0gZGF0YS5zdGF0ZSkge1xuXHRcdFx0XHRcdFx0XHRsb2NhdGlvblN0cmluZyArPSAnLCAnICsgZGF0YS5zdGF0ZTtcblx0XHRcdFx0XHRcdFx0aWYgKHN0YXRlRmllbGQpIHtcblx0XHRcdFx0XHRcdFx0XHRzdGF0ZUZpZWxkLnZhbHVlID0gZGF0YS5zdGF0ZTtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0aWYgKCdVUycgIT09IGNvdW50cnlWYWx1ZSkge1xuXHRcdFx0XHRcdFx0XHRsb2NhdGlvblN0cmluZyArPSAnLCAnICsgY291bnRyeVZhbHVlO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0bG9jYXRpb25FbGVtZW50LmlubmVySFRNTCA9IGxvY2F0aW9uU3RyaW5nO1xuXHRcdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0XHRsb2NhdGlvbkVsZW1lbnQuaW5uZXJIVE1MID0gJyc7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9KVxuXHRcdFx0XHQuY2F0Y2goKCkgPT4ge1xuXHRcdFx0XHRcdGxvY2F0aW9uRWxlbWVudC5pbm5lckhUTUwgPSAnJztcblx0XHRcdFx0fSk7XG5cdFx0fVxuXHR9XG59XG5cbmRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ0RPTUNvbnRlbnRMb2FkZWQnLCBmdW5jdGlvbiAoKSB7XG5cdHNob3dQYXNzd29yZEZpZWxkKCk7XG5cdHNldHVwUGFzc3dvcmRTdHJlbmd0aCgpO1xuXHRzZXR1cENvdW50cnlGaWVsZCgpO1xuXHRzaG93T3V0c2lkZVVzRmllbGRzKCk7XG5cdGdldENpdHlTdGF0ZUZyb21aaXAoKTtcbn0pO1xuIl19
