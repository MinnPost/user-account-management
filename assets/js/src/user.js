function showPasswordField() {
	const theField = document.querySelector('.password-show');
	if (theField) {
		const toggleField = document.createElement('div');
		toggleField.innerHTML =
			'<div class="a-form-show-password a-form-caption"><label><input type="checkbox" name="show_password" id="show-password-checkbox" value="1"> Show password</label></div>';
		theField.after(toggleField);
		const checkboxField = document.querySelector('#show-password-checkbox');
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

function passwordStrengthChecker(
	password,
	strengthMeter,
	strengthText,
	submitButton,
	disallowListArray
) {
	strengthText.classList.remove('short', 'bad', 'good', 'strong');

	// Extend our disallowList array with those from the inputs & site data
	disallowListArray = disallowListArray.concat(
		wp.passwordStrength.userInputDisallowedList()
	);

	// Get the password strength
	const strength = wp.passwordStrength.meter(
		password,
		disallowListArray,
		password
	);

	switch (strength) {
		case 2:
			strengthText.classList.add('bad');
			strengthText.innerHTML =
				'Strength: <strong>' + pwsL10n.bad + '</strong>';
			break;
		case 3:
			strengthText.classList.add('good');
			strengthText.innerHTML =
				'Strength: <strong>' + pwsL10n.good + '</strong>';
			break;
		case 4:
			strengthText.classList.add('strong');
			strengthText.innerHTML =
				'Strength: <strong>' + pwsL10n.strong + '</strong>';
			break;
		case 5:
			strengthText.classList.add('short');
			strengthText.innerHTML =
				'Strength: <strong>' + pwsL10n.mismatch + '</strong>';
			break;
		default:
			strengthText.classList.add('short');
			strengthText.innerHTML =
				'Strength: <strong>' + pwsL10n.short + '</strong>';
	}
	strengthMeter.setAttribute('value', strength);
	// Only enable the submit button if the password is strong
	/*
	if ( 4 === strength ) {
		submitButton.removeAttr( 'disabled' );
	}*/
	return strength;
}

function setupPasswordStrength() {
	const checkPasswordStrength = document.querySelector(
		'.password-strength-check'
	);
	if (checkPasswordStrength) {
		const beforePasswordChecker = document.querySelector(
			'.a-form-show-password'
		);
		const passwordMeter = document.createElement('meter');
		const passwordMeterDiv = document.createElement('div');
		const passwordMeterText = document.createElement('p');
		const registerButton = document.querySelector('.register-button');
		passwordMeter.setAttribute('max', '4');
		passwordMeter.setAttribute('id', 'password-strength');
		passwordMeterText.setAttribute('id', 'password-strength-text');
		passwordMeter.appendChild(passwordMeterDiv);
		beforePasswordChecker.after(passwordMeter);
		beforePasswordChecker.after(passwordMeterText);

		const passwordFields = document.querySelectorAll(
			'input[name=password], input[name=new_password]'
		);
		if (0 < passwordFields.length && registerButton) {
			passwordFields.forEach(function (passwordField) {
				passwordField.addEventListener('keyup', function (event) {
					passwordStrengthChecker(
						event.target.value, // Password field
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
	const countryField = document.querySelector('.m-form-country #country');
	if (countryField) {
		if ('undefined' === typeof clickedNotUS) {
			clickedNotUS = false;
		}
		const countrySelector = countryField.querySelectorAll(
			'select',
			'input'
		);
		if (0 < countrySelector.length) {
			countrySelector.forEach(function (countrySelectorField) {
				if (!countrySelectorField.classList.contains('not-in-us')) {
					clickedNotUS = true;
				}
			});
		}
		const zipParent = document.querySelector('.m-form-zip-code');
		if (zipParent) {
			toggleZipCountrySelector(countryField, zipParent, clickedNotUS);
		}
	}
}

function toggleZipCountrySelector(countryField, zipParent, clickedNotUS) {
	const zipField = zipParent.querySelector('#zip-code');
	const zipLabel = zipParent.querySelector('label');
	const showCountryMessage = document.createElement('div');
	const countryMessageText = document.createElement('small');
	const notInUs = document.createElement('div');
	const countryValue = countryField.value;
	showCountryMessage.setAttribute('class', 'a-form-caption location');
	showCountryMessage.innerHTML = '<small></small>';
	zipParent.append(showCountryMessage);
	notInUs.setAttribute('class', 'a-form-caption show-country');
	setZipSettings(countryValue, zipField, zipLabel);
	if (
		false === clickedNotUS &&
		(countryValue === '' || countryValue === 'US')
	) {
		countryField.parentNode.style.display = 'none';
		zipField.innerHTML = countryMessageText;
		notInUs.innerHTML =
			'<a href="#" id="registration_show_country"><small>Not in the US?</small></a>';
		zipParent.append(notInUs);
	} else {
		countryField.parentNode.style.display = 'block';
		// could also do a slidedown thing, maybe.
	}
}

function setZipSettings(countryValue, zipField, zipLabel) {
	if ('' === countryValue || 'US' === countryValue) {
		zipField.setAttribute('type', 'tel');
		zipLabel.innerHTML =
			'Zip Code: <span title="This field is required." class="a-form-item-required">*</span>';
	} else {
		zipField.setAttribute('type', 'text');
		zipLabel.innerHTML =
			'Postal Code: <span title="This field is required." class="a-form-item-required">*</span>';
	}
}

function showOutsideUsFields() {
	const showCountry = document.querySelector('#registration_show_country');
	if (showCountry) {
		showCountry.addEventListener('click', function (event) {
			event.preventDefault();
			event.target.style.display = 'none';
			setupCountryField(true);
		});
	}
}

function getCityStateFromZip() {
	const cityField = document.querySelector('input[name="city"]');
	const stateField = document.querySelector('input[name="state"]');
	const zipField = document.querySelector('#zip-code');
	const countryField = document.querySelector('.m-form-country #country');
	if (zipField) {
		zipField.addEventListener('blur', () => {
			checkZipCountry(cityField, stateField, zipField, countryField);
		});
	}
	if (countryField) {
		countryField.addEventListener('change', () => {
			checkZipCountry(cityField, stateField, zipField, countryField);
		});
	}
}

function checkZipCountry(cityField, stateField, zipField, countryField) {
	let countryValue = countryField.value;
	if ('' === countryValue) {
		countryValue = 'US';
		countryField.value = countryValue;
	}
	const zipValue = zipField.value;
	if ('' !== zipValue) {
		const location = {
			zip_code: zipValue,
			country: countryValue,
		};
		const locationElement = document.querySelector('.location small');
		if (locationElement) {
			let url =
				user_account_management_rest.site_url +
				user_account_management_rest.rest_namespace +
				'/check-zip';
			url += '?' + new URLSearchParams(location).toString();
			fetch(url)
				.then((response) => response.json())
				.then((data) => {
					if ('success' === data.status) {
						let locationString = '';
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
				})
				.catch(() => {
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
