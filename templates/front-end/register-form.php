<form id="register-form" action="<?php echo $attributes['action']; ?>" method="post" class="m-form m-form-standalone m-form-user m-form-register">

	<?php if ( '1' === $attributes['include_city_state'] && '1' === $attributes['hidden_city_state'] ) : ?>
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
			<input type="email" name="email" id="email" required>
		</div>

		<div class="m-form-item m-form-password m-form-register-password">
			<label for="password"><?php _e( 'Password:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="password" name="password" id="password" class="password-show password-help password-help-not-shown password-strength-check" required>
		</div>

		<div class="m-form-item m-form-first-name m-form-register-first-name">
			<label for="first_name"><?php _e( 'First Name:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="text" name="first_name" id="first-name" required>
		</div>

		<div class="m-form-item m-form-last-name m-form-register-last-name">
			<label for="last_name"><?php _e( 'Last Name:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="text" name="last_name" id="last-name" required>
		</div>

		<?php if ( '1' === $attributes['include_city_state'] && '1' !== $attributes['hidden_city_state'] ) : ?>
			<div class="m-form-item m-form-city m-form-register-city">
				<label for="zip_code"><?php _e( 'City:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
				<input type="text" name="city" id="city" required>
			</div>
			<div class="m-form-item m-form-state m-form-register-state">
				<label for="zip_code"><?php _e( 'State:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
				<input type="text" name="state" id="state" required>
			</div>
		<?php endif; ?>

		<div class="m-form-item m-form-zip-code m-form-register-zip-code">
			<label for="zip_code"><?php _e( 'Zip Code:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="tel" name="zip_code" id="zip-code" required>
		</div>

		<?php if ( isset( $attributes['countries'] ) ) : ?>
			<div class="m-form-item m-form-country m-form-register-country">
				<label for="country"><?php _e( 'Country:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
				<select name="country" id="country">
					<option value="">Choose country</option>
					<?php foreach ( $attributes['countries'] as $country ) : ?>
						<option value="<?php echo $country['alpha2Code']; ?>"><?php echo $country['name']; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>

		<div class="m-form-item m-form-item-rh-name">
			<label for="rh_name"><?php _e( 'Only fill in if you are not human:', 'user-account-management' ); ?></label>
			<input type="text" name="rh_name" id="rh-name" value="" autocomplete="off" />
		</div>

		<div class="m-form-actions">
			<input type="submit" name="submit" class="register-button" value="<?php _e( 'Create new account', 'user-account-management' ); ?>" class="btn btn-submit btn-submit-register">
		</div>
	</fieldset>

	<?php if ( ! empty( $attributes['privacy_terms'] ) ) : ?>
	<?php echo $attributes['privacy_terms']; ?>
	<?php endif; ?>

</form>
