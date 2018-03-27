<form id="account-settings-form" action="<?php echo $attributes['current_url']; ?>" method="post" class="m-form m-form-standalone m-form-user m-form-account-settings">
	<?php if ( isset( $_GET['user_id'] ) ) : ?>
		<input type="hidden" name="user_id" value="<?php echo $_GET['user_id']; ?>">
	<?php endif; ?>
	<input type="hidden" name="user_account_management_action" value="account-settings-update">
	<input type="hidden" name="user_account_management_redirect" value="<?php echo $attributes['redirect']; ?>"/>
	<input type="hidden" name="user_account_management_account_settings_nonce" value="<?php echo wp_create_nonce( 'uam-account-settings-nonce' ); ?>"/>
	<?php if ( '1' === $attributes['include_city_state'] && '1' === $attributes['hidden_city_state'] ) : ?>
		<input type="hidden" name="city" value="<?php echo isset( $_POST['city'] ) ? $_POST['city'] : isset( $attributes['user_meta']['_city'][0] ) ? $attributes['user_meta']['_city'][0] : ''; ?>">
		<input type="hidden" name="state" value="<?php echo isset( $_POST['state'] ) ? $_POST['state'] : isset( $attributes['user_meta']['_state'][0] ) ? $attributes['user_meta']['_state'][0] : ''; ?>">
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

	<?php if ( ! empty( $_GET['account-settings-update'] ) && 'true' === esc_attr( $_GET['account-settings-update'] ) ) : ?>
		<div class="m-form-message m-form-message-info">
			<p class="login-info">
				<?php _e( 'Your account settings were successfully updated.', 'user-account-management' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<fieldset>
		<div class="m-form-item m-form-email m-form-change-email">
			<label for="email"><?php _e( 'Email Address:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="email" name="email" id="email" value="<?php echo isset( $_POST['email'] ) ? $_POST['email'] : isset( $attributes['user']->user_email ) ? $attributes['user']->user_email : ''; ?>" required>
		</div>

		<div class="m-form-item m-form-first-name m-form-change-first-name">
			<label for="first-name"><?php _e( 'First Name:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="text" name="first_name" id="first-name" value="<?php echo isset( $_POST['first_name'] ) ? $_POST['first_name'] : isset( $attributes['user']->first_name ) ? $attributes['user']->first_name : ''; ?>" required>
		</div>

		<div class="m-form-item m-form-last-name m-form-change-last-name">
			<label for="last-name"><?php _e( 'Last Name:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="text" name="last_name" id="last-name" value="<?php echo isset( $_POST['last_name'] ) ? $_POST['last_name'] : isset( $attributes['user']->last_name ) ? $attributes['user']->last_name : ''; ?>" required>
		</div>

		<?php if ( '1' === $attributes['include_city_state'] && '1' !== $attributes['hidden_city_state'] ) : ?>
			<div class="m-form-item m-form-city m-form-change-city">
				<label for="city"><?php _e( 'City:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
				<input type="text" name="city" id="city" value="<?php echo isset( $_POST['city'] ) ? $_POST['city'] : isset( $attributes['user_meta']['_city'][0] ) ? $attributes['user_meta']['_city'][0] : ''; ?>"  required>
			</div>
			<div class="m-form-item m-form-state m-form-change-state">
				<label for="state"><?php _e( 'State:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
				<input type="text" name="state" id="state" value="<?php echo isset( $_POST['state'] ) ? $_POST['state'] : isset( $attributes['user_meta']['_state'][0] ) ? $attributes['user_meta']['_state'][0] : ''; ?>" required>
			</div>
		<?php endif; ?>

		<div class="m-form-item m-form-zip-code m-form-change-zip-code">
			<label for="zip-code"><?php _e( 'Zip Code:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="tel" name="zip_code" id="zip-code" value="<?php echo isset( $_POST['zip_code'] ) ? $_POST['zip_code'] : isset( $attributes['user_meta']['_zip_code'][0] ) ? $attributes['user_meta']['_zip_code'][0] : ''; ?>" required>
		</div>

		<?php if ( isset( $attributes['countries'] ) ) : ?>
			<div class="m-form-item m-form-country m-form-change-country">
				<label for="country"><?php _e( 'Country:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
				<select name="country" id="country">
					<option value="">Choose country</option>
					<?php foreach ( $attributes['countries'] as $country ) : ?>
						<?php if ( isset( $attributes['user_meta']['_country'][0] ) && ( $country['alpha2Code'] === $attributes['user_meta']['_country'][0] || $country['name'] === $attributes['user_meta']['_country'][0]) ) : ?>
							<option value="<?php echo $country['alpha2Code']; ?>" selected><?php echo $country['name']; ?></option>
						<?php else : ?>
							<option value="<?php echo $country['alpha2Code']; ?>"><?php echo $country['name']; ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>

		<div class="m-form-item m-form-item-rh-name">
			<label for="rh_name"><?php _e( 'Only fill in if you are not human:', 'user-account-management' ); ?></label>
			<input type="text" name="rh_name" id="rh-name" value="" autocomplete="off" />
		</div>

		<div class="m-form-actions">
			<input type="submit" name="submit" id="change-button" value="<?php _e( 'Save Changes', 'user-account-management' ); ?>" class="btn btn-submit btn-account-settings">
		</div>
	</fieldset>
</form>
