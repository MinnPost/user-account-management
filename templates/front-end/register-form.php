<form id="register-form" action="<?php echo $attributes['action']; ?>" method="post" class="m-form m-form-standalone m-form-user m-form-register">

	<?php if ( 1 === filter_var( $attributes['include_city_state'], FILTER_VALIDATE_BOOLEAN ) && 1 === filter_var( $attributes['hidden_city_state'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
		<input type="hidden" name="city" value="">
		<input type="hidden" name="state" value="">
	<?php endif; ?>

	<?php if ( isset( $attributes['redirect'] ) && ! empty( $attributes['redirect'] ) ) : ?>
		<input type="hidden" name="redirect_to" value="<?php echo $attributes['redirect']; ?>">
	<?php endif; ?>

	<?php if ( ! empty( $attributes['instructions'] ) ) : ?>
		<?php echo $attributes['instructions']; ?>
	<?php endif; ?>

	<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
		<div class="m-form-message m-form-message-error">
			<?php if ( count( $attributes['errors'] ) > 1 ) : ?>
				<ul>
					<?php foreach ( $attributes['errors'] as $error ) : ?>
						<li><?php echo $error; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p><?php echo $attributes['errors'][0]; ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<fieldset>

		<div class="m-form-item m-form-email m-form-register-email">
			<label for="email"><?php _e( 'Email Address:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="email" name="email" id="email" required<?php if ( isset( $attributes['form_data']['user_email'] ) ) { echo ' value="' . $attributes['form_data']['user_email'] . '"'; } ?>>
		</div>

		<div class="m-form-item m-form-password m-form-register-password">
			<label for="password"><?php _e( 'Password:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="password" name="password" id="password" class="password-show password-help password-help-not-shown password-strength-check" required>
		</div>

		<div class="m-form-item m-form-first-name m-form-register-first-name">
			<label for="first-name"><?php _e( 'First Name:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="text" name="first_name" id="first-name" required<?php if ( isset( $attributes['form_data']['first_name'] ) ) { echo ' value="' . $attributes['form_data']['first_name'] . '"'; } ?>>
		</div>

		<div class="m-form-item m-form-last-name m-form-register-last-name">
			<label for="last-name"><?php _e( 'Last Name:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="text" name="last_name" id="last-name" required<?php if ( isset( $attributes['form_data']['last_name'] ) ) { echo ' value="' . $attributes['form_data']['last_name'] . '"'; } ?>>
		</div>

		<?php if ( true === filter_var( $attributes['include_city_state'], FILTER_VALIDATE_BOOLEAN ) && false === filter_var( $attributes['hidden_city_state'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
			<div class="m-form-item m-form-city m-form-register-city">
				<label for="city"><?php _e( 'City:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
				<input type="text" name="city" id="city" required<?php if ( isset( $attributes['form_data']['city'] ) ) { echo ' value="' . $attributes['form_data']['city'] . '"'; } ?>>
			</div>
			<div class="m-form-item m-form-state m-form-register-state">
				<label for="state"><?php _e( 'State:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
				<input type="text" name="state" id="state" required<?php if ( isset( $attributes['form_data']['state'] ) ) { echo ' value="' . $attributes['form_data']['state'] . '"'; } ?>>
			</div>
		<?php endif; ?>

		<div class="m-form-item m-form-zip-code m-form-register-zip-code">
			<label for="zip-code"><?php _e( 'Zip Code:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="tel" name="zip_code" id="zip-code" required<?php if ( isset( $attributes['form_data']['zip_code'] ) ) { echo ' value="' . $attributes['form_data']['zip_code'] . '"'; } ?>>
		</div>

		<?php if ( isset( $attributes['countries'] ) ) : ?>
			<div class="m-form-item m-form-country m-form-register-country">
				<label for="country"><?php _e( 'Country:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
				<select name="country" id="country">
					<option value="">Choose country</option>
					<?php foreach ( $attributes['countries'] as $country ) : ?>
						<?php
						$selected = '';
						if ( isset( $attributes['form_data']['country'] ) ) {
							$selected = ' selected';
						}
						?>
						<option value="<?php echo $country['alpha2Code']; ?>"<?php echo $selected; ?>><?php echo $country['name']; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>

		<div class="m-form-actions">
			<input type="submit" name="submit" class="register-button" value="<?php _e( 'Create new account', 'user-account-management' ); ?>" class="btn btn-submit btn-submit-register">
		</div>
	</fieldset>

	<?php if ( ! empty( $attributes['privacy_terms'] ) ) : ?>
		<?php echo $attributes['privacy_terms']; ?>
	<?php endif; ?>

</form>
